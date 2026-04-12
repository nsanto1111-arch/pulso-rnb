<?php
declare(strict_types=1);

namespace Plugin\ProgramacaoPlugin\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Http\Response;
use Doctrine\DBAL\Connection;
use Plugin\ProgramacaoPlugin\PortalAuth;

class PortalController
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Obter utilizador autenticado
     */
    private function getUser(): ?array
    {
        return PortalAuth::getUser($this->db);
    }

    /**
     * Verificar autenticação e redirecionar se necessário
     */
    private function requireAuth(Response $response): ?ResponseInterface
    {
        if (!$this->getUser()) {
            return $response->withHeader('Location', '/public/portal/login')->withStatus(302);
        }
        return null;
    }

    /**
     * GET /public/portal/login
     */
    public function loginAction(ServerRequest $request, Response $response): ResponseInterface
    {
        // Limpar cookies antigos
        setcookie('rnb_portal_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true
        ]);
        
        // Se já está autenticado, redirecionar
        if ($this->getUser()) {
            return $response->withHeader('Location', '/public/portal/1')->withStatus(302);
        }

        $html = $this->renderLogin('');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * POST /public/portal/login
     */
    public function loginPostAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $post = $request->getParsedBody();
        $username = trim($post['username'] ?? '');
        $password = $post['password'] ?? '';

        if (!$username || !$password) {
            return $this->loginError($response, 'Preencha todos os campos');
        }

        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM rnb_portal_users WHERE username = ? AND activo = 1"
            );
            $stmt->bindValue(1, $username);
            $result = $stmt->executeQuery();
            $user = $result->fetchAssociative();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return $this->loginError($response, 'Utilizador ou senha incorrectos');
            }

            // Criar sessão
            $token = PortalAuth::login($user, $this->db);
            
            // Adicionar cookie diretamente no response
            $cookieValue = sprintf(
                '%s; Expires=%s; Path=/; HttpOnly; Secure; SameSite=Lax',
                $token,
                gmdate('D, d M Y H:i:s T', time() + 28800)
            );

            return $response
                ->withHeader('Set-Cookie', 'RNB_PORTAL=' . $cookieValue)
                ->withHeader('Location', '/public/portal/1')
                ->withStatus(302);

        } catch (\Exception $e) {
            error_log("PORTAL LOGIN ERROR: " . $e->getMessage());
            return $this->loginError($response, 'Erro ao processar login');
        }
    }

    /**
     * GET /public/portal/logout
     */
    public function logoutAction(ServerRequest $request, Response $response): ResponseInterface
    {
        PortalAuth::logout($this->db);
        return $response->withHeader('Location', '/public/portal/login')->withStatus(302);
    }

    /**
     * GET /public/portal/{station_id}
     */
    public function dashboardAction(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $user = $this->getUser();
        $anuncianteId = $user['anunciante_id'];
        
        // Buscar nome do anunciante para usar na prova de emissão
        $anunciante = $this->db->fetchAssociative(
            "SELECT nome FROM rnb_anunciantes WHERE id = ?",
            [$anuncianteId]
        );
        $nomeAnunciante = $anunciante['nome'];

        // KPIs
        $campanhasActivas = (int)$this->db->fetchOne(
            "SELECT COUNT(*) FROM rnb_campanhas WHERE anunciante_id = ? AND estado = 'activa'",
            [$anuncianteId]
        );

        $spotsEmitidos = (int)$this->db->fetchOne(
            "SELECT COUNT(*) FROM rnb_prova_emissao WHERE cliente = ?",
            [$nomeAnunciante]
        );

        $spotsContratados = (int)$this->db->fetchOne(
            "SELECT SUM(spots_contratados) FROM rnb_campanhas WHERE anunciante_id = ?",
            [$anuncianteId]
        );

        $taxaEmissao = $spotsContratados > 0 
            ? round(($spotsEmitidos / $spotsContratados) * 100, 1)
            : 0;

        // Últimas campanhas
        $campanhas = $this->db->fetchAllAssociative(
            "SELECT * FROM rnb_campanhas WHERE anunciante_id = ? ORDER BY data_inicio DESC LIMIT 5",
            [$anuncianteId]
        );

        foreach ($campanhas as &$c) {
            $emitidos = (int)$this->db->fetchOne(
                "SELECT COUNT(*) FROM rnb_prova_emissao WHERE campanha_id = ?",
                [$c['id']]
            );
            $c['spots_emitidos'] = $emitidos;
            $c['progresso'] = $c['spots_contratados'] > 0
                ? round(($emitidos / $c['spots_contratados']) * 100)
                : 0;
        }

        $html = $this->renderDashboard($user, [
            'campanhas_activas' => $campanhasActivas,
            'spots_emitidos' => $spotsEmitidos,
            'spots_contratados' => $spotsContratados,
            'taxa_emissao' => $taxaEmissao,
        ], $campanhas);

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * GET /public/portal/{station_id}/campanhas
     */
    public function campanhasAction(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $user = $this->getUser();
        $campanhas = $this->db->fetchAllAssociative(
            "SELECT * FROM rnb_campanhas WHERE anunciante_id = ? ORDER BY data_inicio DESC",
            [$user['anunciante_id']]
        );

        foreach ($campanhas as &$c) {
            $emitidos = (int)$this->db->fetchOne(
                "SELECT COUNT(*) FROM rnb_prova_emissao WHERE campanha_id = ?",
                [$c['id']]
            );
            $c['spots_emitidos'] = $emitidos;
            $c['progresso'] = $c['spots_contratados'] > 0
                ? round(($emitidos / $c['spots_contratados']) * 100)
                : 0;
        }

        $html = $this->renderCampanhas($user, $campanhas);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * GET /public/portal/{station_id}/prova-emissao
     */
    public function provaEmissaoAction(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $user = $this->getUser();
        
        // Buscar nome do anunciante
        $anunciante = $this->db->fetchAssociative(
            "SELECT nome FROM rnb_anunciantes WHERE id = ?",
            [$user['anunciante_id']]
        );
        $nomeAnunciante = $anunciante['nome'];

        $sql = "SELECT * FROM rnb_prova_emissao WHERE cliente = ? ORDER BY data_emissao DESC LIMIT 100";
        $provas = $this->db->fetchAllAssociative($sql, [$nomeAnunciante]);

        $html = $this->renderProvaEmissao($user, $provas);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * GET /public/portal/{station_id}/facturas
     */
    public function facturasAction(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $user = $this->getUser();
        
        // Buscar dados do anunciante
        $anunciante = $this->db->fetchAssociative(
            "SELECT * FROM rnb_anunciantes WHERE id = ?",
            [$user['anunciante_id']]
        );
        
        // Buscar cliente correspondente no Finance Pro (por NIF ou nome)
        $cliente = null;
        if (!empty($anunciante['nif'])) {
            $cliente = $this->db->fetchAssociative(
                "SELECT * FROM finance_clientes WHERE nif = ?",
                [$anunciante['nif']]
            );
        }
        
        if (!$cliente) {
            // Tentar por nome
            $clienteResult = $this->db->fetchAssociative(
                "SELECT * FROM finance_clientes WHERE nome LIKE ?",
                ['%' . $anunciante['nome'] . '%']
            );
            $cliente = $clienteResult ?: null;
        }
        
        $facturas = [];
        $totais = [
            'total_emitido' => 0,
            'total_pago' => 0,
            'total_pendente' => 0,
        ];
        
        if ($cliente) {
            // Buscar facturas do cliente
            $facturas = $this->db->fetchAllAssociative(
                "SELECT * FROM finance_facturas 
                 WHERE cliente_id = ? 
                 ORDER BY data_emissao DESC",
                [$cliente['id']]
            );
            
            // Calcular totais
            foreach ($facturas as $f) {
                $totais['total_emitido'] += $f['total'];
                $totais['total_pago'] += $f['valor_pago'];
                $totais['total_pendente'] += $f['saldo'];
            }
        }
        
        $html = $this->renderFacturas($user, $facturas, $totais, $cliente);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function loginError(Response $response, string $msg): ResponseInterface
    {
        $html = $this->renderLogin($msg);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function renderLogin(string $erro): string
    {
        ob_start();
        ?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal do Anunciante — Rádio New Band</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg: #0f172a;
            --bg-card: #1e293b;
            --border: #334155;
            --text: #f1f5f9;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --danger: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary), #60a5fa);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            color: var(--text-muted);
            font-size: 14px;
        }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: var(--primary-hover);
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <div class="logo-icon">🎙️</div>
            <h1>Portal do Anunciante</h1>
            <div class="subtitle">Rádio New Band</div>
        </div>
        
        <div class="card">
            <?php if ($erro): ?>
            <div class="error">
                <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($erro) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Utilizador</label>
                    <input type="text" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    private function renderDashboard(array $user, array $kpis, array $campanhas): string
    {
        ob_start();
        include __DIR__ . '/../Views/portal_dashboard.php';
        return ob_get_clean();
    }

    private function renderCampanhas(array $user, array $campanhas): string
    {
        ob_start();
        include __DIR__ . '/../Views/portal_campanhas.php';
        return ob_get_clean();
    }

    private function renderProvaEmissao(array $user, array $provas): string
    {
        ob_start();
        include __DIR__ . '/../Views/portal_prova_emissao.php';
        return ob_get_clean();
    }

    private function renderFacturas(array $user, array $facturas, array $totais, ?array $cliente): string
    {
        ob_start();
        include __DIR__ . '/../Views/portal_facturas.php';
        return ob_get_clean();
    }

    /**
     * GET /public/portal/{station_id}/prova-emissao/pdf
     * Gera PDF da prova de emissão
     */
    public function provaEmissaoPdfAction(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        if ($redirect = $this->requireAuth($response)) {
            return $redirect;
        }

        $user = $this->getUser();
        $query = $request->getQueryParams();
        $campanhaId = $query['campanha_id'] ?? null;
        
        // Buscar dados
        $anunciante = $this->db->fetchAssociative(
            "SELECT nome FROM rnb_anunciantes WHERE id = ?",
            [$user['anunciante_id']]
        );
        $nomeAnunciante = $anunciante['nome'];
        
        $sql = "SELECT * FROM rnb_prova_emissao WHERE cliente = ?";
        $params = [$nomeAnunciante];
        
        if ($campanhaId) {
            // Buscar campanha
            $campanha = $this->db->fetchAssociative(
                "SELECT titulo FROM rnb_campanhas WHERE id = ?",
                [$campanhaId]
            );
            $sql .= " AND titulo LIKE ?";
            $params[] = '%' . $campanha['titulo'] . '%';
        }
        
        $sql .= " ORDER BY data_emissao DESC LIMIT 100";
        $provas = $this->db->fetchAllAssociative($sql, $params);
        
        // Gerar PDF
        $html = $this->renderProvaEmissaoPdf($user, $anunciante, $provas, $campanhaId);
        
        // Usar Dompdf
        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled' => true,
            'defaultFont' => 'helvetica'
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'prova_emissao_' . date('Y-m-d') . '.pdf';
        
        $response->getBody()->write($dompdf->output());
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
    
    private function renderProvaEmissaoPdf(array $user, array $anunciante, array $provas, ?string $campanhaId): string
    {
        $totalSpots = count($provas);
        $totalDuracao = array_sum(array_column($provas, 'duracao_seg'));
        $docNumero = 'PE-' . date('YmdHis');
        
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #333; }
        h1 { font-size: 24px; margin: 0 0 5px 0; }
        .tagline { font-size: 11px; color: #D4A84B; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 30px; }
        .doc-info { background: #f5f5f5; padding: 15px; margin-bottom: 20px; }
        .doc-info h2 { font-size: 18px; margin: 0 0 10px 0; color: #D4A84B; }
        .doc-info p { margin: 5px 0; font-size: 11px; }
        .client-box { border-left: 4px solid #D4A84B; background: #fafafa; padding: 15px; margin-bottom: 25px; }
        .client-box h3 { font-size: 10px; text-transform: uppercase; color: #999; margin: 0 0 10px 0; }
        .client-box .name { font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .stats { margin-bottom: 25px; }
        .stats table { width: 100%; border-collapse: collapse; }
        .stats td { width: 33.33%; text-align: center; padding: 15px; background: #f5f5f5; }
        .stats td.highlight { background: #D4A84B; color: white; }
        .stats .label { font-size: 9px; text-transform: uppercase; margin-bottom: 5px; }
        .stats .value { font-size: 20px; font-weight: bold; }
        .section-title { font-size: 12px; font-weight: bold; margin: 20px 0 10px 0; padding-bottom: 5px; border-bottom: 2px solid #ddd; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead { background: #333; color: white; }
        .data-table th { padding: 10px; text-align: left; font-size: 9px; text-transform: uppercase; }
        .data-table td { padding: 10px; border-bottom: 1px solid #ddd; font-size: 10px; }
        .data-table tbody tr:nth-child(even) { background: #f9f9f9; }
        .footer { margin-top: 40px; padding-top: 15px; border-top: 2px solid #ddd; text-align: center; font-size: 9px; color: #999; }
        .footer .company { font-weight: bold; color: #333; }
    </style>
</head>
<body>
    <h1>RÁDIO NEW BAND</h1>
    <div class="tagline">Angola · Luanda · 24/7</div>
    
    <div class="doc-info">
        <h2>PROVA DE EMISSÃO #<?= $docNumero ?></h2>
        <p><strong>Data:</strong> <?= date('d/m/Y') ?></p>
        <p><strong>Período:</strong> 
            <?php if ($totalSpots > 0): ?>
                <?= date('d/m/Y', strtotime($provas[count($provas)-1]['data_emissao'])) ?> - 
                <?= date('d/m/Y', strtotime($provas[0]['data_emissao'])) ?>
            <?php else: ?>
                N/A
            <?php endif; ?>
        </p>
    </div>
    
    <div class="client-box">
        <h3>Emitido Para</h3>
        <div class="name"><?= htmlspecialchars($anunciante['nome']) ?></div>
        <div>Luanda, Angola</div>
    </div>
    
    <div class="stats">
        <table>
            <tr>
                <td>
                    <div class="label">Total Spots</div>
                    <div class="value"><?= $totalSpots ?></div>
                </td>
                <td class="highlight">
                    <div class="label">Duração Total</div>
                    <div class="value"><?= gmdate('i:s', $totalDuracao) ?></div>
                </td>
                <td>
                    <div class="label">Média</div>
                    <div class="value"><?= $totalSpots > 0 ? round($totalDuracao / $totalSpots) : 0 ?>s</div>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="section-title">Registo Detalhado</div>
    
    <?php if (empty($provas)): ?>
        <p style="text-align: center; color: #999; padding: 40px;">Sem emissões registadas.</p>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Hora</th>
                    <th>Spot</th>
                    <th>Duração</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($provas as $p): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($p['data_emissao'])) ?></td>
                    <td><?= date('H:i', strtotime($p['data_emissao'])) ?></td>
                    <td><strong><?= htmlspecialchars($p['titulo']) ?></strong></td>
                    <td><?= $p['duracao_seg'] ?>s</td>
                    <td><?= htmlspecialchars($p['tipo']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <div class="footer">
        <div class="company">Rádio New Band Angola</div>
        <div>Prova de Emissão Oficial · radionewband.ao</div>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }

}
