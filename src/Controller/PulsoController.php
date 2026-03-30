<?php
declare(strict_types=1);

namespace Plugin\ProgramacaoPlugin\Controller;

use App\Http\Response;
use App\Http\ServerRequest;
use Plugin\ProgramacaoPlugin\Service\PulsoService;
use Psr\Http\Message\ResponseInterface;

class PulsoController
{
    private PulsoService $service;

    public function __construct(PulsoService $service)
    {
        $this->service = $service;
    }

    public function dashboardAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        // Verificar sorteios automáticos pendentes
        $this->service->verificarSorteiosAutomaticos($stationId);
        // Aniversariantes de hoje
        $aniversariantesHoje = $this->service->getAniversariantesHoje($stationId);
        $dados = $this->service->getDadosDashboard($stationId);

        ob_start();
        include __DIR__ . '/../../templates/dashboard.php';
        $html = $this->renderPage('Dashboard', ob_get_clean(), $stationId, 'dashboard');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function ouvintesAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $queryParams = $request->getQueryParams();
        $segmento = $queryParams['segmento'] ?? null;
        $busca = $queryParams['busca'] ?? null;
        $ouvintes = $this->service->getOuvintes($stationId, $segmento, $busca);

        ob_start();
        include __DIR__ . '/../../templates/ouvintes-lista.php';
        $html = $this->renderPage('Ouvintes', ob_get_clean(), $stationId, 'ouvintes');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    
    
    public function novoOuvinteAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        
        if ($request->getMethod() === 'POST') {
            $post = $request->getParsedBody();
            $erros = $this->service->validarDadosOuvinte($post);
            
            if (empty($erros)) {
                $id = $this->service->criarOuvinte($stationId, $post);
                return $response->withHeader('Location', "/public/pulso/{$stationId}/ouvintes/{$id}/ficha")->withStatus(302);
            }
            
            $dados = $post;
        } else {
            $dados = [];
            $erros = [];
        }
        
        ob_start();
        include __DIR__ . '/../../templates/form-ouvinte.php';
        $formHtml = ob_get_clean();
        
        $html = $this->renderPage('Novo Ouvinte', $formHtml, $stationId, 'ouvintes');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
    
    public function ouvinteFormAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id = (int) $params['id'];
        
        $dados = $this->service->getOuvinte($id);
        $erros = [];
        $isEdit = true;
        
        $templatePath = __DIR__ . '/../../templates/form-ouvinte.php';
        ob_start();
        include $templatePath;
        $formHtml = ob_get_clean();
        
        $html = $this->renderPage('Editar Ouvinte', $formHtml, $stationId, 'ouvintes');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function ouvinteSaveAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $post = $request->getParsedBody();
        $id = isset($params['id']) ? (int) $params['id'] : null;
        $data = [
            'station_id' => $stationId,
            'nome' => $post['nome'] ?? '',
            'telefone' => $post['telefone'] ?? null,
            'email' => $post['email'] ?? null,
            'bairro' => $post['bairro'] ?? null,
            'cidade' => $post['cidade'] ?? 'Luanda',
            'data_nascimento' => !empty($post['data_nascimento']) ? $post['data_nascimento'] : null,
            'programa_favorito' => $post['programa_favorito'] ?? null,
            'locutor_favorito' => $post['locutor_favorito'] ?? null,
            'genero_musical' => $post['genero_musical'] ?? null,
            'notas' => $post['notas'] ?? null,
            'pais' => $post['pais'] ?? 'Angola',
            'provincia' => $post['provincia'] ?? null,
            'municipio' => $post['municipio'] ?? null,
            'genero' => $post['genero'] ?? null,
            'generos_musicais' => !empty($post['generos_musicais']) ? json_encode($post['generos_musicais']) : null,
            'como_conheceu' => $post['como_conheceu'] ?? null,
            'horario_preferido' => $post['horario_preferido'] ?? null,
        ];
        if ($id) { $data['id'] = $id; }
        $savedId = $this->service->saveOuvinte($data);
        // Actualizar segmentação após edição
        $this->service->actualizarSegmento($savedId);
        // Redirecionar para a ficha do ouvinte
        return $response->withHeader('Location', '/public/pulso/' . $stationId . '/ouvintes/' . $savedId . '/ficha')->withStatus(302);
    }

    public function ouvinteDeleteAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->deleteOuvinte((int) $params['id']);
        return $response->withHeader('Location', '/public/pulso/' . $stationId . '/ouvintes')->withStatus(302);
    }

    public function ouvinteFichaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $ficha = $this->service->getFichaCompleta((int) $params['id']);
        if (empty($ficha)) {
            return $response->withHeader('Location', '/public/pulso/' . $stationId . '/ouvintes')->withStatus(302);
        }
        ob_start();
        include __DIR__ . '/../../templates/ouvinte-ficha.php';
        $html = $this->renderPage('Ficha: ' . $ficha['ouvinte']['nome'], ob_get_clean(), $stationId, 'ouvintes');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function promocoesAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $promocoes = $this->service->getPromocoes($stationId);
        ob_start();
        include __DIR__ . '/../../templates/promocoes-lista.php';
        $html = $this->renderPage('Promoções', ob_get_clean(), $stationId, 'promocoes');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function promocaoFormAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id        = isset($params['id']) ? (int) $params['id'] : null;
        $promocao  = $id ? $this->service->getPromocao($id) : null;

        ob_start();
        include __DIR__ . '/../../templates/promocao-form.php';
        $html = $this->renderPage($id ? 'Editar Promoção' : 'Nova Promoção', ob_get_clean(), $stationId, 'promocoes');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function promocaoSaveAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $post = $request->getParsedBody();
        $id = isset($params['id']) ? (int) $params['id'] : null;
        $data = [
            'station_id' => $stationId,
            'nome' => $post['nome'] ?? '',
            'descricao' => $post['descricao'] ?? null,
            'premio' => $post['premio'] ?? '',
            'data_inicio' => $post['data_inicio'] ?? date('Y-m-d H:i:s'),
            'data_fim' => $post['data_fim'] ?? date('Y-m-d H:i:s', strtotime('+7 days')),
            'max_participantes' => (int) ($post['max_participantes'] ?? 0),
            'max_vencedores' => (int) ($post['max_vencedores'] ?? 1),
            'estado'             => $post['estado'] ?? 'rascunho',
            'sorteio_automatico' => isset($post['sorteio_automatico']) ? 1 : 0,
        ];
        if ($id) { $data['id'] = $id; }
        $this->service->savePromocao($data);
        return $response->withHeader('Location', '/public/pulso/' . $stationId . '/promocoes')->withStatus(302);
    }

    public function promocaoDeleteAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id = (int) $params['id'];
        $this->service->deletePromocao($id);
        return $response->withHeader('Location', '/public/pulso/' . $stationId . '/promocoes')->withStatus(302);
    }

    
    // ============================================================
    // SORTEIOS
    // ============================================================
    public function sorteiosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $dados = $this->service->getDadosSorteios($stationId);

        ob_start();
        include __DIR__ . '/../../templates/sorteios.php';
        $html = $this->renderPage('Sorteios', ob_get_clean(), $stationId, 'sorteios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function sortearAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $promoId   = (int) $params['id'];
        $post      = $request->getParsedBody();
        $numVencedores = (int) ($post['num_vencedores'] ?? 1);

        $resultado = $this->service->realizarSorteio($stationId, $promoId, $numVencedores);

        ob_start();
        include __DIR__ . '/../../templates/sorteio-resultado.php';
        $html = $this->renderPage('Resultado do Sorteio', ob_get_clean(), $stationId, 'sorteios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function resultadoSorteioAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $promoId = (int) $params['id'];
        $result = @file_get_contents("http://127.0.0.1/pulso/api/promo?action=historico_sorteios&station_id={$stationId}&promocao_id={$promoId}");
        $data = $result ? json_decode($result, true) : ['sorteios' => []];
        $html = $this->renderPage('Historico de Sorteios', $this->renderHistoricoSorteios($stationId, $promoId, $data), $stationId, 'sorteios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    // ============================================================
    // ANTI-FRAUDE
    // ============================================================
    public function antiFraudeAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $dados = $this->service->getDadosAntiFraude($stationId);

        ob_start();
        include __DIR__ . '/../../templates/antifraude.php';
        $html = $this->renderPage('Anti-Fraude', ob_get_clean(), $stationId, 'antifraude');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function resolverAlertaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->resolverAlerta((int) $params['id']);
        return $response->withHeader('Location', "/public/pulso/{$stationId}/antifraude")->withStatus(302);
    }

    public function bloquearOuvinteAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $ouvId     = (int) $params['ouvinte_id'];
        $post      = $request->getParsedBody();
        $motivo    = $post['motivo'] ?? 'Bloqueado manualmente';
        $this->service->bloquearOuvinte($ouvId, $motivo);
        return $response->withHeader('Location', "/public/pulso/{$stationId}/antifraude")->withStatus(302);
    }

    // ============================================================
    // RELATORIOS
    // ============================================================
    
        public function relatoriosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId  = (int) $params['station_id'];
        $query      = $request->getQueryParams();
        $periodo    = $query['periodo'] ?? '30d';
        $dataInicio = $query['inicio'] ?? null;
        $dataFim    = $query['fim'] ?? null;
        $modo       = $query['modo'] ?? 'interno';
        $dados = $this->service->getDadosRelatorioCompleto($stationId, $periodo, $dataInicio, $dataFim);
        ob_start();
        include __DIR__ . '/../../templates/relatorios.php';
        $html = $this->renderPage('Centro de Inteligência', ob_get_clean(), $stationId, 'relatorios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function relatoriosExportarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $periodo   = $request->getQueryParams()['periodo'] ?? '30d';
        $csv      = $this->service->exportarCSV($stationId, $periodo);
        $filename = 'relatorio-pulso-' . date('Y-m-d') . '.csv';
        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

        public function rankingAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId     = (int) $params['station_id'];
        $filtro        = $request->getQueryParams()['filtro'] ?? 'semana';
        $data          = $this->service->getRankingSemanal($stationId, $filtro);
        $ranking       = $data['ranking']        ?? [];
        $periodo       = $data['periodo']        ?? '';
        $label         = $data['label']          ?? 'Esta Semana';
        $filtroActual  = $data['filtro']         ?? 'semana';
        $inicio_semana = $data['inicio_semana']  ?? date('Y-m-d');
        $totalOuvintes = $data['total_ouvintes'] ?? 0;
        $totalPart     = $data['total_part']     ?? 0;
        ob_start();
        include __DIR__ . '/../../templates/ranking.php';
        $html = $this->renderPage('Ranking', ob_get_clean(), $stationId, 'ranking');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

        public function musicasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $periodo   = $request->getQueryParams()['periodo'] ?? 'tudo';
        $dados = $this->service->getAnaliseMusicaCompleta($stationId, $periodo);
        ob_start();
        include __DIR__ . '/../../templates/musicas.php';
        $html = $this->renderPage('Músicas', ob_get_clean(), $stationId, 'musicas');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    private function renderMusicas(int $stationId, array $data, array $artistas, string $periodo): string
    {
        $periodos = ['hoje' => 'Hoje', 'semana' => 'Esta Semana', 'mes' => 'Este Mês', 'tudo' => 'Todos os Tempos'];
        
        $filtrosHtml = '';
        foreach ($periodos as $key => $label) {
            $active = $periodo === $key ? 'filter-active' : '';
            $filtrosHtml .= '<a href="/public/pulso/'.$stationId.'/musicas?periodo='.$key.'" class="filter-btn '.$active.'">'.$label.'</a>';
        }
        
        $musicasHtml = '';
        $pos = 1;
        foreach ($data['musicas'] as $m) {
            $medalha = match($pos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => $pos };
            $musicasHtml .= '<tr>
                <td style="text-align:center;font-size:20px;width:60px">'.$medalha.'</td>
                <td><strong style="color:var(--text-1)">'.htmlspecialchars($m['musica']).'</strong></td>
                <td style="color:var(--text-3)">'.htmlspecialchars($m['artista']).'</td>
                <td style="text-align:center"><span class="badge-pill" style="background:rgba(0,229,255,0.15);color:#00e5ff">'.$m['ouvintes_unicos'].' 👥</span></td>
                <td style="text-align:center;color:var(--text-3);font-size:12px">'.$m['pedidos_totais'].' total</td>
            </tr>';
            $pos++;
        }
        
        if (empty($data['musicas'])) {
            $musicasHtml = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-3)">Nenhuma música pedida neste período</td></tr>';
        }
        
        $artistasHtml = '';
        foreach ($artistas as $a) {
            $artistasHtml .= '<div style="display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
                <span style="font-weight:600;color:var(--text-2)">'.htmlspecialchars($a['artista']).'</span>
                <span style="color:#00e5ff">'.$a['ouvintes_unicos'].' 👥</span>
            </div>';
        }
        
        if (empty($artistas)) {
            $artistasHtml = '<div style="text-align:center;padding:40px;color:var(--text-3)">Nenhum artista pedido neste período</div>';
        }
        
        return '
        <div class="filters-bar" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap">
            '.$filtrosHtml.'
        </div>
        
        <div class="row row-2">
            <div class="card">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                    <h3><i class="bi bi-music-note-list"></i> Top 30 Músicas</h3>
                    <span style="font-size:12px;color:var(--text-3)">'.$data['total'].' música(s) • '.$periodos[$periodo].'</span>
                </div>
                <div style="overflow-x:auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Música</th>
                                <th>Artista</th>
                                <th style="text-align:center">Ouvintes</th>
                                <th style="text-align:center">Pedidos</th>
                            </tr>
                        </thead>
                        <tbody>'.$musicasHtml.'</tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-person-lines-fill"></i> Top 10 Artistas</h3>
                </div>
                <div style="padding:20px">'.$artistasHtml.'</div>
            </div>
        </div>
        
        <style>
        .filter-btn{padding:8px 20px;border-radius:20px;border:1px solid var(--border);background:transparent;color:var(--text-2);font-size:13px;font-weight:600;text-decoration:none;transition:.2s;display:inline-block}
        .filter-btn:hover{border-color:var(--accent);color:var(--accent)}
        .filter-active{background:var(--accent)!important;color:#fff!important;border-color:var(--accent)!important}
        .badge-pill{padding:4px 12px;border-radius:12px;font-size:12px;font-weight:600}
        </style>';
    }

    // ==================== ANÁLISE DEMOGRÁFICA ====================

    public function demograficosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $data = $this->service->getDistribuicaoGeografica($stationId);
        
        $html = $this->renderPage(
            'Análise Demográfica',
            $this->renderDemograficos($stationId, $data),
            $stationId,
            'demograficos'
        );
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    private function renderDemograficos(int $stationId, array $data): string
    {
        // Preparar dados para Chart.js
        $cidadesLabels = json_encode(array_column($data['cidades'], 'cidade'));
        $cidadesData = json_encode(array_column($data['cidades'], 'total'));
        $cidadesCores = json_encode(['#00e5ff', '#4facfe', '#8b5cf6', '#f59e0b', '#10b981', '#e11d48', '#06b6d4', '#fbbf24', '#ec4899', '#14b8a6']);
        
        $bairrosLabels = json_encode(array_column($data['bairros'], 'bairro'));
        $bairrosData = json_encode(array_column($data['bairros'], 'total'));
        
        // Stats cards
        $stats = $data['stats'];
        $percLuanda = $stats['total'] > 0 ? round($stats['luanda'] * 100 / $stats['total'], 1) : 0;
        
        // Tabela de cidades
        $cidadesHtml = '';
        $pos = 1;
        foreach ($data['cidades'] as $c) {
            $medalha = match($pos) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => $pos };
            $barWidth = min(100, $c['percentagem'] * 2);
            $cidadesHtml .= '<tr>
                <td style="text-align:center;font-size:18px;width:50px">'.$medalha.'</td>
                <td><strong>'.htmlspecialchars($c['cidade']).'</strong></td>
                <td><div style="background:rgba(0,229,255,0.2);border-radius:4px;height:20px;width:100%"><div style="background:#00e5ff;height:100%;width:'.$barWidth.'%;border-radius:4px"></div></div></td>
                <td style="text-align:right;font-weight:600;color:#00e5ff">'.$c['total'].'</td>
                <td style="text-align:right;color:var(--text-3)">'.$c['percentagem'].'%</td>
            </tr>';
            $pos++;
        }
        
        return '
        <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px">
            <div class="stat-card">
                <div class="stat-value">'.number_format($stats['total']).'</div>
                <div class="stat-label">Total Ouvintes</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="background:linear-gradient(135deg,#00e5ff,#4facfe);-webkit-background-clip:text">'.$stats['cidades_diferentes'].'</div>
                <div class="stat-label">Cidades</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="background:linear-gradient(135deg,#f59e0b,#fbbf24);-webkit-background-clip:text">'.$stats['bairros_diferentes'].'</div>
                <div class="stat-label">Bairros</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="background:linear-gradient(135deg,#10b981,#00ffc8);-webkit-background-clip:text">'.$percLuanda.'%</div>
                <div class="stat-label">Luanda</div>
            </div>
        </div>
        
        <div class="row row-2">
            <div class="card">
                <div class="card-header"><h3><i class="bi bi-bar-chart"></i> Top 10 Cidades</h3></div>
                <div style="padding:20px"><canvas id="cidadesChart" style="max-height:300px"></canvas></div>
            </div>
            <div class="card">
                <div class="card-header"><h3><i class="bi bi-pie-chart"></i> Bairros de Luanda</h3></div>
                <div style="padding:20px"><canvas id="bairrosChart" style="max-height:300px"></canvas></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header"><h3><i class="bi bi-geo-alt"></i> Distribuição por Cidade</h3></div>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr><th>#</th><th>Cidade</th><th>Distribuição</th><th>Ouvintes</th><th>%</th></tr>
                    </thead>
                    <tbody>'.$cidadesHtml.'</tbody>
                </table>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
        <script>
        // Gráfico de barras - Cidades
        new Chart(document.getElementById("cidadesChart"), {
            type: "bar",
            data: {
                labels: '.$cidadesLabels.',
                datasets: [{
                    data: '.$cidadesData.',
                    backgroundColor: '.$cidadesCores.'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { color: "#888" } }, x: { ticks: { color: "#888" } } }
            }
        });
        
        // Gráfico de pizza - Bairros
        new Chart(document.getElementById("bairrosChart"), {
            type: "doughnut",
            data: {
                labels: '.$bairrosLabels.',
                datasets: [{
                    data: '.$bairrosData.',
                    backgroundColor: ["#00e5ff","#4facfe","#8b5cf6","#f59e0b","#10b981","#e11d48","#06b6d4","#fbbf24","#ec4899","#14b8a6","#6366f1","#f43f5e","#8b5cf6","#06b6d4","#f59e0b"]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: "right", labels: { color: "#888", font: { size: 11 } } } }
            }
        });
        </script>';
    }
    private function renderFormOuvinte(int $stationId, array $dados = [], array $erros = []): string
    {
        $nome = htmlspecialchars($dados['nome'] ?? '');
        $telefone = htmlspecialchars($dados['telefone'] ?? '');
        $email = htmlspecialchars($dados['email'] ?? '');
        $bairro = htmlspecialchars($dados['bairro'] ?? '');
        $cidade = htmlspecialchars($dados['cidade'] ?? '');
        
        $errosHtml = '';
        if (!empty($erros)) {
            $errosHtml = '<div style="background:#fee;border:1px solid #fcc;padding:1rem;border-radius:8px;margin-bottom:1rem"><ul style="margin:0;padding-left:1.5rem">';
            foreach ($erros as $e) {
                $errosHtml .= '<li>' . htmlspecialchars($e) . '</li>';
            }
            $errosHtml .= '</ul></div>';
        }
        
        return $errosHtml . '
        <div class="card">
            <div class="card-header"><h3>Dados do Ouvinte</h3></div>
            <form method="post" action="/public/pulso/' . $stationId . '/ouvintes/salvar" style="padding:2rem">
                <div style="display:grid;gap:1.5rem">
                    <div>
                        <label style="display:block;margin-bottom:0.5rem;font-weight:600">Nome Completo *</label>
                        <input type="text" name="nome" value="' . $nome . '" required style="width:100%;padding:0.75rem;border:1px solid var(--border);border-radius:8px;background:var(--bg-elevated);color:var(--text-1)">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div>
                            <label style="display:block;margin-bottom:0.5rem;font-weight:600">Telefone *</label>
                            <input type="tel" name="telefone" value="' . $telefone . '" required placeholder="+244 XXX XXX XXX" style="width:100%;padding:0.75rem;border:1px solid var(--border);border-radius:8px;background:var(--bg-elevated);color:var(--text-1)">
                        </div>
                        <div>
                            <label style="display:block;margin-bottom:0.5rem;font-weight:600">Email</label>
                            <input type="email" name="email" value="' . $email . '" placeholder="email@exemplo.com" style="width:100%;padding:0.75rem;border:1px solid var(--border);border-radius:8px;background:var(--bg-elevated);color:var(--text-1)">
                        </div>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div>
                            <label style="display:block;margin-bottom:0.5rem;font-weight:600">Cidade</label>
                            <input type="text" name="cidade" value="' . $cidade . '" style="width:100%;padding:0.75rem;border:1px solid var(--border);border-radius:8px;background:var(--bg-elevated);color:var(--text-1)">
                        </div>
                        <div>
                            <label style="display:block;margin-bottom:0.5rem;font-weight:600">Bairro</label>
                            <input type="text" name="bairro" value="' . $bairro . '" style="width:100%;padding:0.75rem;border:1px solid var(--border);border-radius:8px;background:var(--bg-elevated);color:var(--text-1)">
                        </div>
                    </div>
                    <input type="hidden" name="station_id" value="' . $stationId . '">
                    <div style="display:flex;gap:1rem;margin-top:1rem">
                        <button type="submit" class="btn btn-primary">Salvar Ouvinte</button>
                        <a href="/public/pulso/' . $stationId . '/ouvintes" class="btn btn-outline">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>';
    }


    public function enriquecerAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $dados = $this->service->getDadosEnriquecimento($stationId);
        $arquivados = (int) ($request->getQueryParams()['arquivados'] ?? 0);

        ob_start();
        include __DIR__ . '/../../templates/enriquecer.php';
        $html = $this->renderPage('Enriquecimento de Perfis', ob_get_clean(), $stationId, 'ouvintes');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function arquivarFantasmasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $total = $this->service->arquivarFantasmas($stationId);
        return $response
            ->withHeader('Location', "/public/pulso/{$stationId}/ouvintes/enriquecer?arquivados={$total}")
            ->withStatus(302);
    }


    public function demograficosProAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $dados = $this->service->getDadosDemograficosPro($stationId);

        ob_start();
        include __DIR__ . '/../../templates/demograficos-pro.php';
        $html = $this->renderPage('Demográficos Pro', ob_get_clean(), $stationId, 'demograficos');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function demograficosApiOuvintesAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $filtros = $request->getQueryParams();
        $ouvintes = $this->service->getOuvintesComFiltros($stationId, $filtros);
        $response->getBody()->write(json_encode(['total' => count($ouvintes), 'ouvintes' => $ouvintes]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function promocaoParticipantesAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id = (int) $params['id'];

        $promocao = $this->service->getPromocao($id);
        if (!$promocao) {
            return $response->withHeader('Location', '/public/pulso/' . $stationId . '/promocoes')->withStatus(302);
        }

        $busca         = $request->getQueryParams()['busca'] ?? '';
        $inscritos     = $this->service->getInscritosPromocao($id);
        $disponiveis   = $this->service->getOuvintesDisponiveis($stationId, $id, $busca);
        $estatisticas  = $this->service->getEstatisticasPromocao($id);

        ob_start();
        include __DIR__ . '/../../templates/promocao-participantes.php';
        $html = $this->renderPage('Participantes: ' . $promocao['nome'], ob_get_clean(), $stationId, 'promocoes');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function inscreverOuvinteAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId  = (int) $params['station_id'];
        $promocaoId = (int) $params['id'];
        $post       = $request->getParsedBody();
        $ouvinteId  = (int) ($post['ouvinte_id'] ?? 0);

        if ($ouvinteId > 0) {
            $ok = $this->service->inscreverOuvintePromocao($stationId, $promocaoId, $ouvinteId);
            $resultado = $ok
                ? ['sucesso' => true,  'mensagem' => 'Inscrito com sucesso']
                : ['sucesso' => false, 'mensagem' => 'Ouvinte já inscrito'];
        } else {
            $resultado = ['sucesso' => false, 'mensagem' => 'Ouvinte inválido'];
        }

        $response->getBody()->write(json_encode($resultado));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function removerParticipanteAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId  = (int) $params['station_id'];
        $promocaoId = (int) $params['id'];
        $post       = $request->getParsedBody();
        $ouvinteId  = (int) ($post['ouvinte_id'] ?? 0);

        if ($ouvinteId > 0) {
            $this->service->removerParticipantePromocao($promocaoId, $ouvinteId);
        }

        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function desbloquearOuvinteAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $ouvId     = (int) $params['ouvinte_id'];
        $this->service->desbloquearOuvinte($ouvId);
        return $response->withHeader('Location', "/public/pulso/{$stationId}/antifraude")->withStatus(302);
    }


private function renderPage(string $title, string $content, int $stationId, string $activeMenu = ''): string
    {
        $menuItems = [
            'dashboard' => ['icon' => 'heart-pulse', 'label' => 'Dashboard', 'url' => "/public/pulso/{$stationId}"],
            'ouvintes' => ['icon' => 'people', 'label' => 'Ouvintes', 'url' => "/public/pulso/{$stationId}/ouvintes"],
            'musicas' => ['icon' => 'music-note-list', 'label' => 'Músicas', 'url' => "/public/pulso/{$stationId}/musicas"],
            'demograficos' => ['icon' => 'geo-alt', 'label' => 'Demográficos', 'url' => "/public/pulso/{$stationId}/demograficos-pro"],
            'promocoes' => ['icon' => 'gift', 'label' => 'Promoções', 'url' => "/public/pulso/{$stationId}/promocoes"],
            'sorteios' => ['icon' => 'trophy', 'label' => 'Sorteios', 'url' => "/public/pulso/{$stationId}/sorteios"],
            'antifraude' => ['icon' => 'shield-check', 'label' => 'Anti-Fraude', 'url' => "/public/pulso/{$stationId}/antifraude"],
            'relatorios' => ['icon' => 'graph-up', 'label' => 'Relatórios', 'url' => "/public/pulso/{$stationId}/relatorios"],
            'ranking' => ['icon' => 'trophy-fill', 'label' => 'Ranking', 'url' => "/public/pulso/{$stationId}/ranking"],
            'premios'     => ['icon' => 'box-seam',   'label' => 'Prémios',     'url' => "/public/pulso/{$stationId}/premios"],
            'atendimento'  => ['icon' => 'headset',       'label' => 'Atendimento', 'url' => "/public/pulso/{$stationId}/atendimento"],

        ];
        
        $menuHtml = '';
        foreach ($menuItems as $key => $item) {
            $active = $key === $activeMenu ? 'active' : '';
            $menuHtml .= "<a href=\"{$item['url']}\" class=\"nav-item {$active}\"><i class=\"bi bi-{$item['icon']}\"></i><span>{$item['label']}</span></a>";
        }
        
        return <<<HTML
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title} - PULSO</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
:root{
--bg-0:#050510;--bg-1:#0f0f1f;--bg-2:#1a1a2e;--bg-3:#252538;
--accent:#00e5ff;--accent2:#7c3aed;--gold:#fbbf24;--green:#10b981;--red:#ef4444;
--text-1:#fff;--text-2:#a1a1aa;--text-3:#71717a;
--border:rgba(255,255,255,0.08);--glow:0 0 30px rgba(0,229,255,0.2);
--transition:all .3s cubic-bezier(.4,0,.2,1);
}
body{font-family:'Inter',-apple-system,sans-serif;background:var(--bg-0);color:var(--text-1);line-height:1.6;overflow-x:hidden}
.pulso-wrap{min-height:100vh;background:radial-gradient(circle at 20% 50%,rgba(124,58,237,.08),transparent 50%),radial-gradient(circle at 80% 80%,rgba(0,229,255,.05),transparent 50%),var(--bg-0)}
.header{background:rgba(15,15,31,.8);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:1.25rem 2rem;position:sticky;top:0;z-index:100}
.header-inner{max-width:1600px;margin:0 auto;display:flex;align-items:center;justify-content:space-between}
.logo{display:flex;align-items:center;gap:1rem}
.logo-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:var(--glow)}
.logo-text h1{font-size:22px;font-weight:900;background:linear-gradient(135deg,var(--accent),#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;letter-spacing:-0.5px}
.logo-text p{font-size:10px;color:var(--text-2);font-weight:600;letter-spacing:2px;text-transform:uppercase}
.nav{display:flex;gap:.5rem}
.nav-item{padding:.65rem 1rem;border-radius:10px;text-decoration:none;color:var(--text-2);font-weight:600;font-size:13px;transition:var(--transition);display:flex;align-items:center;gap:.4rem;white-space:nowrap}
.nav-item span{display:inline}
.nav-item:hover{color:var(--text-1);background:rgba(255,255,255,.05)}
.nav-item.active{background:linear-gradient(135deg,rgba(0,229,255,.12),rgba(124,58,237,.12));color:var(--accent);box-shadow:0 0 20px rgba(0,229,255,.15);border:1px solid rgba(0,229,255,.2)}
.nav-item i{font-size:16px}
.main{max-width:1600px;margin:0 auto;padding:2.5rem 2rem}
.page-header{margin-bottom:2.5rem}
.page-title{font-size:32px;font-weight:900;margin-bottom:.5rem;display:flex;align-items:center;gap:1rem}
.page-title i{color:var(--accent)}
.page-subtitle{color:var(--text-2);font-size:15px}
.card{background:linear-gradient(145deg,rgba(26,26,46,.95),rgba(21,21,32,.95));border:1px solid var(--border);border-radius:16px;padding:2rem;box-shadow:0 8px 24px rgba(0,0,0,.4);transition:var(--transition);position:relative;overflow:hidden}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),var(--accent2));opacity:0;transition:var(--transition)}
.card:hover{transform:translateY(-4px);border-color:rgba(255,255,255,.12);box-shadow:0 16px 48px rgba(0,0,0,.5)}
.card:hover::before{opacity:1}
.card-header{margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid var(--border)}
.card-header h3{font-size:17px;font-weight:700;display:flex;align-items:center;gap:.75rem}
.card-header h3 i{color:var(--accent)}
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1.5rem;margin-bottom:2rem}
.stat-card{background:linear-gradient(145deg,rgba(26,26,46,.9),rgba(21,21,32,.9));border:1px solid var(--border);border-radius:14px;padding:1.5rem;transition:var(--transition);position:relative;overflow:hidden}
.stat-card::after{content:'';position:absolute;top:50%;right:-20px;width:90px;height:90px;background:radial-gradient(circle,var(--accent),transparent 70%);opacity:.04;transform:translateY(-50%)}
.stat-card:hover{transform:translateY(-2px);border-color:rgba(255,255,255,.12)}
.stat-label{font-size:11px;font-weight:600;color:var(--text-2);text-transform:uppercase;letter-spacing:1px;margin-bottom:.5rem}
.stat-value{font-size:30px;font-weight:900;background:linear-gradient(135deg,var(--accent),#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.btn{padding:.75rem 1.5rem;border-radius:10px;font-weight:600;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;transition:var(--transition);border:none;cursor:pointer}
.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 4px 16px rgba(0,229,255,.25)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(0,229,255,.35)}
.btn-outline{background:transparent;border:2px solid var(--border);color:var(--text-1)}
.btn-outline:hover{border-color:var(--accent);background:rgba(0,229,255,.05)}
.btn-sm{padding:.5rem 1rem;font-size:12px}
.badge{padding:.35rem .75rem;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.3px}
.badge-activo{background:rgba(16,185,129,.15);color:var(--green);border:1px solid rgba(16,185,129,.25)}
.badge-inactivo{background:rgba(113,113,122,.15);color:var(--text-3);border:1px solid rgba(113,113,122,.25)}
table{width:100%;border-collapse:separate;border-spacing:0}
thead th{padding:1rem;text-align:left;font-size:11px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:1px;border-bottom:2px solid var(--border);background:rgba(255,255,255,.02)}
tbody tr{transition:var(--transition)}
tbody tr:hover{background:rgba(255,255,255,.03)}
tbody td{padding:1rem;border-bottom:1px solid var(--border)}
.back-link{display:inline-flex;align-items:center;gap:.5rem;color:var(--text-2);text-decoration:none;margin-bottom:1.5rem;font-size:13px;font-weight:600}
.back-link:hover{color:var(--accent)}
@media(max-width:1024px){
.nav{overflow-x:auto;overflow-y:hidden;flex-wrap:nowrap;padding-bottom:.5rem;-webkit-overflow-scrolling:touch}
.nav::-webkit-scrollbar{height:2px}
.nav::-webkit-scrollbar-thumb{background:var(--accent);border-radius:2px}
.nav-item{font-size:12px;padding:.6rem .85rem}
.nav-item span{font-size:11px}
}
@media(max-width:768px){
.header{padding:1rem}
.header-inner{flex-direction:column;gap:1rem;align-items:flex-start}
.logo-text h1{font-size:18px}
.nav{width:100%;gap:.3rem}
.nav-item{flex:1;justify-content:center;min-width:auto;padding:.6rem .5rem}
.nav-item span{display:none}
.nav-item i{font-size:18px}
.main{padding:1.5rem 1rem}
.stats-grid{grid-template-columns:1fr}
.page-title{font-size:22px}
.card{padding:1.5rem}
}
</style>
</head>
<body>
<?php
$_rnb_sid_nav = 1; $_rnb_atual_nav = 'audiencia';
$_rnb_sid = 1; $_rnb_atual = 'audiencia';
@include dirname(__DIR__, 2) . '/public/rnb-nav.php';
?>
<div class="pulso-wrap">
<header class="header">
<div class="header-inner">
<div class="logo">
<div class="logo-icon">💎</div>
<div class="logo-text">
<h1>PULSO</h1>
<p>Inteligência de Audiência</p>
</div>
</div>
<nav class="nav">
{$menuHtml}
</nav>
</div>
</header>
<main class="main">{$content}</main>
</div>
<!-- PULSO QUICK ACCESS WIDGET -->
<div id="pqa-trigger" onclick="pqaToggle()" title="PULSO Quick Access">
    <div class="pqa-ring"></div>
    <div class="pqa-icon">⚡</div>
    <div id="pqa-badge" class="pqa-badge" style="display:none"></div>
</div>

<div id="pqa-panel" class="pqa-panel">
    <div class="pqa-header">
        <div class="pqa-header-title">
            <span style="font-size:16px">⚡</span>
            <span>PULSO Quick Access</span>
        </div>
        <button onclick="pqaToggle()" class="pqa-close">✕</button>
    </div>

    <div class="pqa-section">
        <div class="pqa-section-title">📡 Stream ao Vivo</div>
        <div class="pqa-stream">
            <div class="pqa-stream-art" id="pqa-art">🎵</div>
            <div class="pqa-stream-info">
                <div class="pqa-stream-song" id="pqa-song">A carregar...</div>
                <div class="pqa-stream-artist" id="pqa-artist"></div>
                <div class="pqa-stream-listeners">
                    <span class="pqa-dot"></span> <span id="pqa-cnt">0</span> ouvintes
                </div>
            </div>
        </div>
        <div class="pqa-progress-wrap"><div class="pqa-progress" id="pqa-progress"></div></div>
    </div>

    <div class="pqa-section" id="pqa-aniv-section" style="display:none">
        <div class="pqa-section-title">🎂 Aniversários Hoje</div>
        <div id="pqa-aniv-list"></div>
        <a id="pqa-aniv-link" href="#" class="pqa-link-btn">Ver &amp; Sortear →</a>
    </div>

    <div class="pqa-section" id="pqa-notif-section" style="display:none">
        <div class="pqa-section-title">🔔 Notificações</div>
        <div id="pqa-notif-list"></div>
    </div>

    <div class="pqa-section">
        <div class="pqa-section-title">🚀 Acesso Rápido</div>
        <div class="pqa-shortcuts" id="pqa-shortcuts"></div>
    </div>
</div>
<div id="pqa-overlay" onclick="pqaToggle()"></div>

<style>
#pqa-trigger{position:fixed;bottom:28px;right:28px;width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#00e5ff,#7c3aed);cursor:pointer;z-index:9998;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 24px rgba(0,229,255,.35);transition:transform .2s,box-shadow .2s}
#pqa-trigger:hover{transform:scale(1.1);box-shadow:0 8px 32px rgba(0,229,255,.5)}
.pqa-ring{position:absolute;inset:-4px;border-radius:50%;border:2px solid rgba(0,229,255,.4);animation:pqa-pulse 2s infinite}
@keyframes pqa-pulse{0%,100%{transform:scale(1);opacity:.6}50%{transform:scale(1.15);opacity:0}}
.pqa-icon{font-size:22px;position:relative;z-index:1}
.pqa-badge{position:absolute;top:-4px;right:-4px;width:20px;height:20px;background:#ef4444;border-radius:50%;font-size:11px;font-weight:800;color:#fff;display:flex;align-items:center;justify-content:center;border:2px solid #050510}
.pqa-panel{position:fixed;bottom:96px;right:28px;width:320px;max-height:80vh;background:rgba(10,10,25,.97);backdrop-filter:blur(20px);border:1px solid rgba(0,229,255,.2);border-radius:20px;z-index:9999;overflow:hidden;transform:translateY(20px) scale(.95);opacity:0;pointer-events:none;transition:all .25s cubic-bezier(.34,1.56,.64,1);display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(0,0,0,.6),0 0 0 1px rgba(0,229,255,.1)}
.pqa-panel.open{transform:translateY(0) scale(1);opacity:1;pointer-events:all}
.pqa-header{padding:16px 20px;background:linear-gradient(135deg,rgba(0,229,255,.1),rgba(124,58,237,.05));border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between;flex-shrink:0}
.pqa-header-title{display:flex;align-items:center;gap:.625rem;font-size:14px;font-weight:700;color:#fff}
.pqa-close{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:8px;color:#71717a;width:28px;height:28px;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;transition:all .15s}
.pqa-close:hover{color:#fff;background:rgba(255,255,255,.1)}
.pqa-section{padding:14px 20px;border-bottom:1px solid rgba(255,255,255,.04);overflow-y:auto}
.pqa-section:last-child{border-bottom:none}
.pqa-section-title{font-size:10px;font-weight:700;color:#52525b;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px}
.pqa-stream{display:flex;align-items:center;gap:.875rem;margin-bottom:.75rem}
.pqa-stream-art{width:44px;height:44px;border-radius:10px;background:rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;overflow:hidden}
.pqa-stream-art img{width:44px;height:44px;object-fit:cover;border-radius:10px}
.pqa-stream-info{flex:1;min-width:0}
.pqa-stream-song{font-size:13px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pqa-stream-artist{font-size:11px;color:#71717a;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pqa-stream-listeners{font-size:11px;color:#00e5ff;margin-top:4px;display:flex;align-items:center;gap:5px}
.pqa-dot{width:7px;height:7px;border-radius:50%;background:#00e5ff;animation:pqa-pulse 1.5s infinite;display:inline-block}
.pqa-progress-wrap{height:3px;background:rgba(255,255,255,.06);border-radius:2px;overflow:hidden}
.pqa-progress{height:100%;background:linear-gradient(90deg,#00e5ff,#7c3aed);border-radius:2px;transition:width 1s linear}
.pqa-aniv-item{display:flex;align-items:center;gap:.75rem;padding:6px 0}
.pqa-aniv-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#ec4899,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#fff;flex-shrink:0}
.pqa-notif-item{padding:8px 10px;background:rgba(255,255,255,.03);border-radius:8px;margin-bottom:6px;border-left:3px solid #00e5ff}
.pqa-notif-title{font-size:12px;font-weight:700;color:#fff}
.pqa-notif-msg{font-size:11px;color:#71717a;margin-top:2px}
.pqa-shortcuts{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.pqa-shortcut{display:flex;flex-direction:column;align-items:center;gap:5px;padding:10px 6px;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);border-radius:10px;text-decoration:none;color:#a1a1aa;font-size:10px;font-weight:600;transition:all .15s;text-align:center}
.pqa-shortcut:hover{background:rgba(0,229,255,.06);border-color:rgba(0,229,255,.2);color:#00e5ff;text-decoration:none;transform:translateY(-2px)}
.pqa-shortcut span:first-child{font-size:20px}
.pqa-link-btn{display:block;text-align:center;margin-top:8px;padding:7px;background:rgba(236,72,153,.1);border:1px solid rgba(236,72,153,.25);border-radius:8px;color:#ec4899;font-size:11px;font-weight:600;text-decoration:none;transition:all .15s}
.pqa-link-btn:hover{background:rgba(236,72,153,.15);text-decoration:none;color:#ec4899}
#pqa-overlay{position:fixed;inset:0;z-index:9997;display:none}
#pqa-overlay.show{display:block}
</style>

<script>
(function() {
const SID = {$stationId};
let open = false, elapsed = 0, duracao = 0, timer = null;

// Shortcuts
const shortcuts = [
    {icon:'📞', label:'Atendimento', url:'/public/pulso/'+SID+'/atendimento'},
    {icon:'📡', label:'Stream',      url:'/public/pulso/'+SID+'/stream'},
    {icon:'🎉', label:'Sorteios',    url:'/public/pulso/'+SID+'/sorteios'},
    {icon:'🎂', label:'Aniversários',url:'/public/pulso/'+SID+'/aniversarios'},
    {icon:'🎁', label:'Prémios',     url:'/public/pulso/'+SID+'/premios'},
    {icon:'📊', label:'Relatórios',  url:'/public/pulso/'+SID+'/relatorios'},
];
document.getElementById('pqa-shortcuts').innerHTML = shortcuts.map(s =>
    '<a href="'+s.url+'" class="pqa-shortcut"><span>'+s.icon+'</span><span>'+s.label+'</span></a>'
).join('');
document.getElementById('pqa-aniv-link').href = '/public/pulso/'+SID+'/aniversarios';

window.pqaToggle = function() {
    open = !open;
    document.getElementById('pqa-panel').classList.toggle('open', open);
    document.getElementById('pqa-overlay').classList.toggle('show', open);
    if (open) pqaLoad();
};

function pqaLoad() {
    fetch('/api/nowplaying/rnb').then(r=>r.json()).then(d => {
        const np = d.now_playing || {}, song = np.song || {};
        document.getElementById('pqa-song').textContent   = song.title  || '—';
        document.getElementById('pqa-artist').textContent = song.artist || '';
        document.getElementById('pqa-cnt').textContent    = (d.listeners||{}).total || 0;
        const artEl = document.getElementById('pqa-art');
        artEl.innerHTML = song.art ? '<img src="'+song.art+'">' : '🎵';
        elapsed = np.elapsed||0; duracao = np.duration||0;
        if(timer) clearInterval(timer);
        timer = setInterval(function(){
            elapsed++;
            var pct = duracao>0 ? Math.min(100,Math.round(elapsed/duracao*100)) : 0;
            var bar = document.getElementById('pqa-progress');
            if(bar) bar.style.width = pct+'%';
        }, 1000);
    }).catch(function(){});

    fetch('/pulso/api/locutor?action=aniversariantes&station_id='+SID).then(r=>r.json()).then(d => {
        var sec = document.getElementById('pqa-aniv-section');
        var list = document.getElementById('pqa-aniv-list');
        var anivs = (d.aniversariantes||[]);
        if(anivs.length > 0) {
            sec.style.display = 'block';
            list.innerHTML = anivs.map(function(a) {
                var ini = (a.nome||'?')[0].toUpperCase();
                return '<div class="pqa-aniv-item"><div class="pqa-aniv-avatar">'+ini+'</div><div><div style="font-size:12px;font-weight:700;color:#fff">'+a.nome+'</div><div style="font-size:10px;color:#71717a">🎂 '+(a.idade||'')+' anos hoje</div></div></div>';
            }).join('');
            setBadge(anivs.length);
        } else { sec.style.display='none'; }
    }).catch(function(){});

    fetch('/pulso/api/locutor?action=notificacoes&station_id='+SID).then(r=>r.json()).then(d => {
        var notifs = (d.notificacoes||[]).filter(function(n){return !n.lida;}).slice(0,3);
        var sec  = document.getElementById('pqa-notif-section');
        var list = document.getElementById('pqa-notif-list');
        if(notifs.length > 0) {
            sec.style.display = 'block';
            list.innerHTML = notifs.map(function(n){
                return '<div class="pqa-notif-item"><div class="pqa-notif-title">'+n.titulo+'</div><div class="pqa-notif-msg">'+n.mensagem+'</div></div>';
            }).join('');
            setBadge(notifs.length);
        } else { sec.style.display='none'; }
    }).catch(function(){});
}

function setBadge(n) {
    var b = document.getElementById('pqa-badge');
    b.textContent = n; b.style.display = 'flex';
}

setInterval(function(){ if(open) pqaLoad(); }, 15000);
setTimeout(function(){
    fetch('/pulso/api/locutor?action=notificacoes&station_id='+SID).then(r=>r.json()).then(d=>{
        var n = (d.notificacoes||[]).filter(function(x){return !x.lida;}).length;
        if(n>0) setBadge(n);
    }).catch(function(){});
}, 2000);
})();
</script>

</body>
</html>
HTML;
    }



    public function premiosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $dados = $this->service->getDadosPremios($stationId);
        ob_start();
        include __DIR__ . '/../../templates/premios.php';
        $html = $this->renderPage('Estoque de Prémios', ob_get_clean(), $stationId, 'premios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function premioFormAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id = isset($params['id']) ? (int)$params['id'] : null;
        $premio = $id ? $this->service->getPremio($id) : null;
        ob_start();
        include __DIR__ . '/../../templates/premio-form.php';
        $html = $this->renderPage($id ? 'Editar Prémio' : 'Novo Prémio', ob_get_clean(), $stationId, 'premios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function premioSaveAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id = isset($params['id']) ? (int)$params['id'] : null;
        $post = $request->getParsedBody();
        $this->service->salvarPremio($stationId, $post, $id);
        return $response->withHeader('Location', "/public/pulso/{$stationId}/premios")->withStatus(302);
    }

    public function premioDeleteAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->excluirPremio((int)$params['id']);
        return $response->withHeader('Location', "/public/pulso/{$stationId}/premios")->withStatus(302);
    }

    public function entregasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $estado = $request->getQueryParams()['estado'] ?? '';
        $dados = $this->service->getDadosEntregas($stationId, $estado);
        ob_start();
        include __DIR__ . '/../../templates/entregas.php';
        $html = $this->renderPage('Entregas de Prémios', ob_get_clean(), $stationId, 'premios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function entregaEstadoAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id = (int) $params['id'];
        $post = $request->getParsedBody();
        $this->service->actualizarEstadoEntrega(
            $id,
            $post['estado'] ?? 'reservado',
            $post['notas'] ?? '',
            $post['entregue_por'] ?? '',
            $post['documento_id'] ?? ''
        );
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function atendimentoAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $dados = $this->service->getDadosAtendimento($stationId);
        ob_start();
        include __DIR__ . '/../../templates/atendimento.php';
        $html = $this->renderPage('Atendimento', ob_get_clean(), $stationId, 'atendimento');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function atendimentoBuscarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $post      = $request->getParsedBody();
        $busca     = trim($post['busca'] ?? '');
        $resultado = $this->service->buscarOuvinteAtendimento($stationId, $busca);
        $response->getBody()->write(json_encode($resultado));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function atendimentoRegistarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $post      = $request->getParsedBody();
        $id = $this->service->registarAtendimento($stationId, $post);
        $response->getBody()->write(json_encode(['sucesso' => true, 'id' => $id]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function atendimentoHistoricoAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $query     = $request->getQueryParams();
        $data      = $query['data'] ?? date('Y-m-d');
        $tipo      = $query['tipo'] ?? '';
        $dados     = $this->service->getDadosAtendimento($stationId);
        $historico = $this->service->getHistoricoAtendimentos($stationId, $data, $tipo);
        ob_start();
        include __DIR__ . '/../../templates/atendimento-historico.php';
        $html = $this->renderPage('Histórico de Atendimentos', ob_get_clean(), $stationId, 'atendimento');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }



    public function aniversariosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $tipo      = $request->getQueryParams()['tipo'] ?? 'hoje';
        $dados     = $this->service->getAniversariantes($stationId, $tipo);
        ob_start();
        include __DIR__ . '/../../templates/aniversarios.php';
        $html = $this->renderPage('Aniversários', ob_get_clean(), $stationId, 'aniversarios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function sortearAniversariosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $post      = $request->getParsedBody();
        $ids       = array_map('intval', (array)($post['ouvinte_ids'] ?? []));
        $premio    = $post['premio'] ?? '';
        $resultado = $this->service->sortearAniversariantes($stationId, $ids, $premio);
        ob_start();
        include __DIR__ . '/../../templates/aniversarios-resultado.php';
        $html = $this->renderPage('Resultado do Sorteio', ob_get_clean(), $stationId, 'aniversarios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function streamAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $dados = $this->service->getStreamAnalytics($stationId);
        ob_start();
        include __DIR__ . '/../../templates/stream.php';
        $html = $this->renderPage('Stream Analytics', ob_get_clean(), $stationId, 'stream');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function streamApiAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $dados = $this->service->getStreamAnalytics($stationId);
        $response->getBody()->write(json_encode($dados));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function relatoriosExportarPdfAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $periodo   = $request->getQueryParams()['periodo'] ?? '30d';
        $modo      = $request->getQueryParams()['modo'] ?? 'interno';

        $pdf      = $this->service->gerarRelatorioPdf($stationId, $periodo, $modo);
        $filename = 'relatorio-pulso-' . date('Y-m-d') . '.pdf';

        $response->getBody()->write($pdf);
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }


    public function financasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId  = (int) $params['station_id'];
        $dados          = $this->service->getFpDashboard($stationId);
        $alertas        = $this->service->getFpAlertas($stationId);
        $categorias     = $this->service->getCategoriasDespesa($stationId);
        $patrocinadores = $this->service->getPatrocinadores($stationId);
        $fluxo          = $this->service->getFpFluxoCaixa($stationId, 6);
        ob_start();
        include __DIR__ . '/../../templates/fp-dashboard.php';
        $html = $this->renderPageFinancas('Dashboard', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function patrocinadoresAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId      = (int) $params['station_id'];
        $patrocinadores = $this->service->getPatrocinadores($stationId);
        ob_start();
        include __DIR__ . '/../../templates/patrocinadores.php';
        $html = $this->renderPageFinancas('Patrocinadores', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function patrocinadorSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $post      = $request->getParsedBody();
        $id        = !empty($post['id']) ? (int)$post['id'] : null;
        $this->service->salvarPatrocinador($stationId, $post, $id);
        return $response->withHeader('Location', "/public/financas/{$stationId}/patrocinadores")->withStatus(302);
    }

    public function receitaSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->salvarReceita($stationId, $request->getParsedBody());
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function despesaSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->salvarDespesa($stationId, $request->getParsedBody());
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function metaSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->salvarMeta($stationId, $request->getParsedBody());
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function contratosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId      = (int) $params['station_id'];
        $patrocinadores = $this->service->getPatrocinadores($stationId);
        $contratos      = $this->service->getContratos($stationId);
        ob_start();
        include __DIR__ . '/../../templates/contratos.php';
        $html = $this->renderPageFinancas('Contratos', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function contratoSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $post      = $request->getParsedBody();
        $id        = !empty($post['id']) ? (int)$post['id'] : null;
        $this->service->salvarContrato($stationId, $post, $id);
        return $response->withHeader('Location', "/public/financas/{$stationId}/contratos")->withStatus(302);
    }



    private function mesPortugues(int $mes): string
    {
        return ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][$mes - 1] ?? '';
    }

    private function dataPt(string $formato, ?int $timestamp = null): string
    {
        $ts  = $timestamp ?? time();
        $meses = ['January'=>'Janeiro','February'=>'Fevereiro','March'=>'Março',
                  'April'=>'Abril','May'=>'Maio','June'=>'Junho','July'=>'Julho',
                  'August'=>'Agosto','September'=>'Setembro','October'=>'Outubro',
                  'November'=>'Novembro','December'=>'Dezembro'];
        $result = date($formato, $ts);
        return strtr($result, $meses);
    }

    private function renderPageFinancas(string $title, string $content, int $stationId): string
    {
        $_rnb_sid = $stationId; $_rnb_atual = 'financas';
        ob_start();
        @require dirname(__DIR__, 2) . '/public/rnb-nav.php';
        $rnbNav = ob_get_clean();

        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        $mesPt = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                  'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][(int)date('m')-1];
        $periodoLabel = $mesPt . ' ' . date('Y');

        $sections = [
            'VISÃO GERAL' => [
                ['icon'=>'speedometer2','label'=>'Dashboard','url'=>"/public/financas/{$stationId}",'color'=>'#10b981','rgb'=>'16,185,129'],
            ],
            'CONTABILIDADE' => [
                ['icon'=>'diagram-3','label'=>'Plano de Contas','url'=>"/public/financas/{$stationId}/plano-contas",'color'=>'#00e5ff','rgb'=>'0,229,255'],
                ['icon'=>'grid-3x3-gap','label'=>'Centros de Custo','url'=>"/public/financas/{$stationId}/centros-custo",'color'=>'#8b5cf6','rgb'=>'139,92,246'],
                ['icon'=>'journal-text','label'=>'Lançamentos','url'=>"/public/financas/{$stationId}/lancamentos",'color'=>'#3b82f6','rgb'=>'59,130,246'],
            ],
            'MOVIMENTOS' => [
                ['icon'=>'arrow-down-circle','label'=>'Contas a Pagar','url'=>"/public/financas/{$stationId}/contas-pagar",'color'=>'#ef4444','rgb'=>'239,68,68'],
                ['icon'=>'arrow-up-circle','label'=>'Contas a Receber','url'=>"/public/financas/{$stationId}/contas-receber",'color'=>'#10b981','rgb'=>'16,185,129'],
                ['icon'=>'bank','label'=>'Conta Corrente','url'=>"/public/financas/{$stationId}/conta-corrente",'color'=>'#00e5ff','rgb'=>'0,229,255'],
            ],
            'COMERCIAL' => [
                ['icon'=>'building','label'=>'Patrocinadores','url'=>"/public/financas/{$stationId}/patrocinadores",'color'=>'#f59e0b','rgb'=>'245,158,11'],
                ['icon'=>'file-earmark-text','label'=>'Contratos','url'=>"/public/financas/{$stationId}/contratos",'color'=>'#3b82f6','rgb'=>'59,130,246'],
                ['icon'=>'person-check','label'=>'Comissões','url'=>"/public/financas/{$stationId}/comissoes",'color'=>'#8b5cf6','rgb'=>'139,92,246'],
            ],
            'RELATÓRIOS' => [
                ['icon'=>'graph-up-arrow','label'=>'Fluxo de Caixa','url'=>"/public/financas/{$stationId}/fluxo-caixa",'color'=>'#10b981','rgb'=>'16,185,129'],
                ['icon'=>'bar-chart-line','label'=>'DRE','url'=>"/public/financas/{$stationId}/dre",'color'=>'#00e5ff','rgb'=>'0,229,255'],
                ['icon'=>'file-earmark-bar-graph','label'=>'Relatórios','url'=>"/public/financas/{$stationId}/relatorios-fp",'color'=>'#8892a4','rgb'=>'136,146,164'],
            ],
        ];

        $navHtml = '';
        foreach ($sections as $sLabel => $items) {
            $navHtml .= '<div class="fpn-section">';
            $navHtml .= '<div class="fpn-section-label">' . htmlspecialchars($sLabel) . '</div>';
            foreach ($items as $item) {
                $isExact = ($item['url'] === "/public/financas/{$stationId}");
                $active  = $isExact
                    ? ($currentUrl === $item['url'] || $currentUrl === $item['url'] . '/')
                    : str_starts_with($currentUrl, $item['url']);
                $ac  = $active ? ' fpn-active' : '';
                $dot = $active ? '<span class="fpn-dot"></span>' : '';
                $navHtml .= "
                <a href='{$item['url']}' class='fpn-item{$ac}' style='--ic:{$item['color']};--ir:{$item['rgb']}'>
                    <span class='fpn-icon'><i class='bi bi-{$item['icon']}'></i></span>
                    <span class='fpn-label'>{$item['label']}</span>
                    {$dot}
                </a>";
            }
            $navHtml .= '</div>';
        }

        $alertas = $this->service->getFpAlertas($stationId);
        $alertasJson = json_encode($alertas);

        return <<<HTML
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title} · Finance Pro</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300;0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700;0,14..32,800;0,14..32,900;1,14..32,400&display=swap" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════════════════
   FINANCE PRO — DESIGN SYSTEM
   Rádio New Band Angola
═══════════════════════════════════════════════════════════════ */
*{box-sizing:border-box;margin:0;padding:0}

:root{
    /* Cores base */
    --g: #10b981;  /* green  */
    --r: #ef4444;  /* red    */
    --o: #f59e0b;  /* orange */
    --c: #00e5ff;  /* cyan   */
    --p: #8b5cf6;  /* purple */
    --b: #3b82f6;  /* blue   */

    /* Backgrounds — escala de profundidade */
    --bg0: #060810;
    --bg1: #0a0d16;
    --bg2: #0f1320;
    --bg3: #151a2a;
    --bg4: #1c2234;
    --bg5: #222a3e;

    /* Texto */
    --t1: #eef1fb;
    --t2: #8691a8;
    --t3: #3e4a60;

    /* Bordas */
    --bd: rgba(255,255,255,.06);
    --bd2: rgba(255,255,255,.1);

    /* Layout */
    --sb-w: 256px;
    --top-h: 56px;

    /* Aliases para templates legados */
    --fp-green:#10b981;--fp-red:#ef4444;--fp-gold:#f59e0b;
    --fp-cyan:#00e5ff;--fp-purple:#8b5cf6;--fp-blue:#3b82f6;
    --fp-bg:#060810;--fp-bg1:#0a0d16;--fp-bg2:#0f1320;
    --fp-bg3:#151a2a;--fp-bg4:#1c2234;
    --fp-text:#eef1fb;--fp-text2:#8691a8;--fp-text3:#3e4a60;
    --fp-border:rgba(255,255,255,.06);
}

html,body{height:100%;overflow:hidden}
body{
    background:var(--bg0);
    color:var(--t1);
    font-family:'Inter',system-ui,sans-serif;
    font-size:13px;
    line-height:1.5;
    -webkit-font-smoothing:antialiased;
}

/* ── Shell ──────────────────────────────────────────────────── */
.fp-shell{display:flex;height:100vh;overflow:hidden}

/* ── Sidebar ────────────────────────────────────────────────── */
.fp-sb{
    width:var(--sb-w);
    min-width:var(--sb-w);
    background:var(--bg1);
    border-right:1px solid var(--bd);
    display:flex;
    flex-direction:column;
    overflow:hidden;
}

/* Brand */
.fp-brand{
    padding:16px;
    border-bottom:1px solid var(--bd);
    display:flex;
    align-items:center;
    gap:10px;
    flex-shrink:0;
}
.fp-logo{
    width:36px;height:36px;
    border-radius:10px;
    background:linear-gradient(145deg,#0fda8e,#059669);
    display:flex;align-items:center;justify-content:center;
    font-size:13px;font-weight:900;
    color:#011a0d;
    letter-spacing:-.5px;
    flex-shrink:0;
    box-shadow:0 2px 8px rgba(16,185,129,.3);
}
.fp-brand-name{font-size:13.5px;font-weight:800;color:var(--t1);letter-spacing:-.2px}
.fp-brand-sub{font-size:9.5px;color:var(--t3);margin-top:1px;font-weight:500}

/* Período */
.fp-period{
    margin:10px 12px 4px;
    padding:7px 10px;
    background:var(--bg3);
    border:1px solid var(--bd);
    border-radius:8px;
    display:flex;align-items:center;gap:7px;
    flex-shrink:0;
}
.fp-period i{font-size:11px;color:var(--t3)}
.fp-period-txt{font-size:11px;font-weight:600;color:var(--t2);flex:1}
.fp-period-tag{font-size:9px;font-weight:700;color:var(--g);background:rgba(16,185,129,.1);padding:2px 6px;border-radius:12px;border:1px solid rgba(16,185,129,.18)}

/* Nav */
.fp-nav{flex:1;overflow-y:auto;padding:4px 0 8px;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.05) transparent}
.fp-nav::-webkit-scrollbar{width:3px}
.fp-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.06);border-radius:2px}

.fpn-section{padding:8px 0 2px}
.fpn-section-label{
    font-size:9px;font-weight:700;color:var(--t3);
    text-transform:uppercase;letter-spacing:1.3px;
    padding:0 16px 5px;
}
.fpn-item{
    display:flex;align-items:center;gap:8px;
    padding:6px 10px 6px 12px;
    margin:0 5px 1px;
    border-radius:8px;
    text-decoration:none;
    color:var(--t2);
    font-size:12.5px;font-weight:500;
    transition:background .1s,color .1s;
    position:relative;
}
.fpn-item:hover{background:rgba(255,255,255,.04);color:var(--t1);text-decoration:none}
.fpn-item.fpn-active{
    background:rgba(var(--ir),.1);
    color:var(--t1);
    font-weight:650;
}
.fpn-item.fpn-active::before{
    content:'';
    position:absolute;left:0;top:4px;bottom:4px;
    width:2px;border-radius:0 2px 2px 0;
    background:var(--ic,#10b981);
}
.fpn-icon{
    width:24px;height:24px;border-radius:6px;
    display:flex;align-items:center;justify-content:center;
    font-size:12px;flex-shrink:0;
    background:rgba(255,255,255,.03);
    color:var(--t3);
    transition:all .1s;
}
.fpn-item:hover .fpn-icon,
.fpn-item.fpn-active .fpn-icon{
    background:rgba(var(--ir),.14);
    color:var(--ic);
}
.fpn-label{flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fpn-dot{width:4px;height:4px;border-radius:50%;background:var(--ic,#10b981);opacity:.7;flex-shrink:0}

/* Bottom */
.fp-sb-foot{flex-shrink:0;border-top:1px solid var(--bd)}
.fp-back{
    display:flex;align-items:center;gap:7px;
    padding:7px 10px;margin:8px 5px 3px;
    border-radius:8px;border:1px solid var(--bd);
    background:rgba(255,255,255,.02);
    text-decoration:none;color:var(--t2);
    font-size:11.5px;font-weight:600;
    transition:all .13s;
}
.fp-back:hover{background:rgba(255,255,255,.06);color:var(--t1);text-decoration:none}
.fp-back i{font-size:11px}
.fp-util{padding:2px 5px 6px}
.fp-util-a{
    display:flex;align-items:center;gap:7px;
    padding:5px 10px;border-radius:7px;
    color:var(--t3);font-size:11px;font-weight:500;
    text-decoration:none;cursor:pointer;
    transition:color .1s;
}
.fp-util-a:hover{color:var(--t2);text-decoration:none}
.fp-util-a i{font-size:11px;width:13px;text-align:center}
.fp-user{
    padding:10px 14px;
    border-top:1px solid var(--bd);
    display:flex;align-items:center;gap:9px;
    background:rgba(0,0,0,.18);
}
.fp-av{
    width:30px;height:30px;border-radius:50%;
    background:rgba(16,185,129,.12);
    border:1.5px solid rgba(16,185,129,.22);
    display:flex;align-items:center;justify-content:center;
    font-size:11px;font-weight:800;color:#10b981;flex-shrink:0;
}
.fp-uname{font-size:11.5px;font-weight:700;color:var(--t1);line-height:1.3}
.fp-urole{font-size:9.5px;color:var(--t3)}
.fp-udot{width:5px;height:5px;border-radius:50%;background:#10b981;margin-left:auto;box-shadow:0 0 4px #10b981}

/* ── Topbar ─────────────────────────────────────────────────── */
.fp-main{flex:1;min-width:0;display:flex;flex-direction:column;height:100vh;overflow:hidden}
.fp-top{
    height:var(--top-h);min-height:var(--top-h);
    background:var(--bg1);
    border-bottom:1px solid var(--bd);
    display:flex;align-items:center;justify-content:space-between;
    padding:0 1.5rem;flex-shrink:0;gap:1rem;
}
.fp-bc{font-size:12px;color:var(--t3)}
.fp-bc b{color:var(--t1);font-weight:700}
.fp-bc-sep{margin:0 5px;opacity:.4}
.fp-top-r{display:flex;align-items:center;gap:5px;flex-shrink:0}
.fp-top-btn{
    display:inline-flex;align-items:center;gap:5px;
    padding:5px 12px;border-radius:7px;
    font-size:11.5px;font-weight:650;cursor:pointer;
    border:1px solid;transition:all .13s;text-decoration:none;white-space:nowrap;
    font-family:'Inter',sans-serif;
}
.fp-top-btn:hover{transform:translateY(-1px);text-decoration:none}
.fp-top-btn.prim{background:var(--g);border-color:var(--g);color:#011a0d}
.fp-top-btn.prim:hover{background:#0ea572;color:#011a0d}
.fp-top-btn.ghost{background:rgba(255,255,255,.03);border-color:var(--bd);color:var(--t2)}
.fp-top-btn.ghost:hover{color:var(--t1);border-color:var(--bd2)}
.fp-top-icon{
    width:30px;height:30px;border-radius:7px;
    border:1px solid var(--bd);
    background:rgba(255,255,255,.03);
    display:flex;align-items:center;justify-content:center;
    color:var(--t2);font-size:13px;cursor:pointer;
    transition:all .13s;position:relative;
}
.fp-top-icon:hover{color:var(--t1);border-color:var(--bd2)}
.fp-bell-dot{
    position:absolute;top:5px;right:5px;
    width:5px;height:5px;border-radius:50%;
    background:#ef4444;border:1.5px solid var(--bg1);
}

/* ── Content ────────────────────────────────────────────────── */
.fp-body{
    flex:1;overflow-y:auto;
    padding:1.5rem;
    scrollbar-width:thin;
    scrollbar-color:rgba(255,255,255,.06) transparent;
}
.fp-body::-webkit-scrollbar{width:4px}
.fp-body::-webkit-scrollbar-thumb{background:rgba(255,255,255,.06);border-radius:2px}

/* ── Footer ─────────────────────────────────────────────────── */
.fp-foot{
    padding:7px 1.5rem;
    border-top:1px solid var(--bd);
    font-size:9.5px;color:var(--t3);
    display:flex;align-items:center;justify-content:space-between;
    flex-shrink:0;background:var(--bg1);
}

/* ══════════════════════════════════════════════════════════════
   COMPONENTES GLOBAIS — usados em todos os templates
══════════════════════════════════════════════════════════════ */

/* Botões */
.fp-btn{
    display:inline-flex;align-items:center;gap:.4rem;
    padding:.5rem 1rem;border-radius:8px;
    font-size:12.5px;font-weight:650;cursor:pointer;
    border:1px solid;text-decoration:none;
    transition:all .13s;white-space:nowrap;
    font-family:'Inter',sans-serif;
}
.fp-btn:hover{transform:translateY(-1px);text-decoration:none}
.fp-btn-primary{background:var(--g);border-color:var(--g);color:#011a0d}
.fp-btn-primary:hover{background:#0ea572;color:#011a0d}
.fp-btn-danger{background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.22);color:#ef4444}
.fp-btn-danger:hover{background:rgba(239,68,68,.15)}
.fp-btn-ghost{background:rgba(255,255,255,.03);border-color:var(--bd);color:var(--t2)}
.fp-btn-ghost:hover{color:var(--t1);border-color:var(--bd2)}

/* Cards */
.fp-card{
    background:var(--bg2);
    border:1px solid var(--bd);
    border-radius:12px;
    overflow:hidden;
    margin-bottom:1rem;
}
.fp-card-header{
    padding:.875rem 1.25rem;
    border-bottom:1px solid var(--bd);
    display:flex;align-items:center;justify-content:space-between;
}
.fp-card-title{
    font-size:12.5px;font-weight:700;color:var(--t1);
    display:flex;align-items:center;gap:.4rem;
}
.fp-card-body{padding:1.125rem 1.25rem}
.fp-card-body.no-pad{padding:0}

/* Tabelas */
.fp-table{width:100%;border-collapse:collapse;font-size:12.5px}
.fp-table thead th{
    padding:.6rem 1.25rem;
    font-size:9px;font-weight:700;color:var(--t3);
    text-transform:uppercase;letter-spacing:1px;
    border-bottom:1px solid var(--bd);
    text-align:left;
    background:rgba(255,255,255,.015);
    white-space:nowrap;
}
.fp-table tbody td{
    padding:.75rem 1.25rem;
    border-bottom:1px solid rgba(255,255,255,.025);
    vertical-align:middle;
}
.fp-table tbody tr:last-child td{border-bottom:none}
.fp-table tbody tr:hover td{background:rgba(255,255,255,.012)}

/* Status pills */
.fp-pill{
    display:inline-flex;align-items:center;gap:4px;
    padding:2px 8px;border-radius:20px;
    font-size:10px;font-weight:700;border:1px solid;
}
.fp-pill::before{content:'';width:4px;height:4px;border-radius:50%}
.fp-pill.green{background:rgba(16,185,129,.08);color:#10b981;border-color:rgba(16,185,129,.18)}.fp-pill.green::before{background:#10b981}
.fp-pill.red{background:rgba(239,68,68,.08);color:#ef4444;border-color:rgba(239,68,68,.18)}.fp-pill.red::before{background:#ef4444}
.fp-pill.gold{background:rgba(245,158,11,.08);color:#f59e0b;border-color:rgba(245,158,11,.18)}.fp-pill.gold::before{background:#f59e0b}
.fp-pill.blue{background:rgba(59,130,246,.08);color:#3b82f6;border-color:rgba(59,130,246,.18)}.fp-pill.blue::before{background:#3b82f6}
.fp-pill.purple{background:rgba(139,92,246,.08);color:#8b5cf6;border-color:rgba(139,92,246,.18)}.fp-pill.purple::before{background:#8b5cf6}
.fp-pill.gray{background:rgba(255,255,255,.04);color:#8691a8;border-color:rgba(255,255,255,.08)}.fp-pill.gray::before{background:#8691a8}

/* Alias .fp-status → .fp-pill (compatibilidade) */
.fp-status{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700;border:1px solid}
.fp-status::before{content:'';width:4px;height:4px;border-radius:50%}
.fp-status.green{background:rgba(16,185,129,.08);color:#10b981;border-color:rgba(16,185,129,.18)}.fp-status.green::before{background:#10b981}
.fp-status.red{background:rgba(239,68,68,.08);color:#ef4444;border-color:rgba(239,68,68,.18)}.fp-status.red::before{background:#ef4444}
.fp-status.gold{background:rgba(245,158,11,.08);color:#f59e0b;border-color:rgba(245,158,11,.18)}.fp-status.gold::before{background:#f59e0b}
.fp-status.blue{background:rgba(59,130,246,.08);color:#3b82f6;border-color:rgba(59,130,246,.18)}.fp-status.blue::before{background:#3b82f6}
.fp-status.gray{background:rgba(255,255,255,.04);color:#8691a8;border-color:rgba(255,255,255,.08)}.fp-status.gray::before{background:#8691a8}

/* Progress bar */
.fp-progress{height:4px;background:rgba(255,255,255,.06);border-radius:2px;overflow:hidden}
.fp-progress-fill{height:100%;border-radius:2px;transition:width .4s}

/* KPI cards */
.fp-kpi-grid{display:grid;gap:.875rem;margin-bottom:1.25rem}
.fp-kpi-grid.col2{grid-template-columns:repeat(2,1fr)}
.fp-kpi-grid.col3{grid-template-columns:repeat(3,1fr)}
.fp-kpi-grid.col4{grid-template-columns:repeat(4,1fr)}
.fp-kpi{
    background:var(--bg2);
    border:1px solid var(--bd);
    border-radius:12px;
    padding:1.125rem 1.25rem;
    position:relative;overflow:hidden;
}
.fp-kpi::after{
    content:'';position:absolute;
    top:0;left:0;right:0;height:2px;
    border-radius:12px 12px 0 0;
    background:var(--kpi-color,#10b981);
}
.fp-kpi-label{font-size:9.5px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:.5rem}
.fp-kpi-val{font-size:20px;font-weight:900;color:var(--t1);line-height:1.1;margin-bottom:.25rem}
.fp-kpi-sub{font-size:10px;color:var(--t3)}

/* Modais */
.fp-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.72);backdrop-filter:blur(8px);z-index:1000;align-items:center;justify-content:center}
.fp-modal-bg.open{display:flex}
.fp-modal{
    background:var(--bg1);
    border:1px solid var(--bd2);
    border-radius:16px;
    padding:1.625rem;
    width:90%;max-width:480px;
    max-height:90vh;overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.5);
}
.fp-modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}
.fp-modal-title{font-size:14.5px;font-weight:800;color:var(--t1)}
.fp-modal-close{
    background:rgba(255,255,255,.05);border:1px solid var(--bd);
    color:var(--t2);width:27px;height:27px;border-radius:7px;
    cursor:pointer;font-size:12px;
    display:flex;align-items:center;justify-content:center;
    transition:all .12s;
}
.fp-modal-close:hover{color:var(--t1)}
.fp-form-row{display:grid;grid-template-columns:1fr 1fr;gap:.875rem}
.fp-field{margin-bottom:.875rem}
.fp-field-label,.fp-field label{
    display:block;font-size:9.5px;font-weight:700;
    color:var(--t2);text-transform:uppercase;
    letter-spacing:.5px;margin-bottom:.4rem;
}
.fp-input{
    width:100%;padding:.65rem .9rem;
    background:var(--bg3);border:1px solid var(--bd);
    border-radius:8px;color:var(--t1);
    font-size:12.5px;outline:none;
    transition:border-color .15s;color-scheme:dark;
    font-family:'Inter',sans-serif;
}
.fp-input:focus{border-color:rgba(16,185,129,.4)}
.fp-input::placeholder{color:var(--t3)}
.fp-select{
    width:100%;padding:.65rem .9rem;
    background:var(--bg3);border:1px solid var(--bd);
    border-radius:8px;color:var(--t1);
    font-size:12.5px;outline:none;
    font-family:'Inter',sans-serif;
}
.fp-modal-footer{display:flex;gap:.625rem;margin-top:1.25rem}
.fp-btn-confirm{
    flex:1;padding:.75rem;background:var(--g);border:none;
    border-radius:8px;color:#011a0d;font-size:13px;font-weight:800;
    cursor:pointer;transition:background .14s;font-family:'Inter',sans-serif;
}
.fp-btn-confirm:hover{background:#0ea572}
.fp-btn-dismiss{
    flex:1;padding:.75rem;
    background:rgba(255,255,255,.04);border:1px solid var(--bd);
    border-radius:8px;color:var(--t2);font-size:13px;
    cursor:pointer;font-family:'Inter',sans-serif;
}

/* Empty states */
.fp-empty{text-align:center;padding:3.5rem 2rem;color:var(--t3)}
.fp-empty-icon{font-size:40px;margin-bottom:.75rem;opacity:.15}
.fp-empty-text{font-size:13px;color:var(--t2)}

/* Alerta panel */
.fp-alert-panel{
    display:none;position:fixed;
    top:calc(var(--top-h) + 8px);right:1.25rem;
    width:300px;
    background:var(--bg1);
    border:1px solid var(--bd2);
    border-radius:12px;
    z-index:9999;
    box-shadow:0 16px 48px rgba(0,0,0,.55);
    overflow:hidden;
}
.fp-alert-head{
    padding:10px 14px;
    border-bottom:1px solid var(--bd);
    display:flex;align-items:center;justify-content:space-between;
}
.fp-alert-head span{font-size:12px;font-weight:700;color:var(--t1)}
.fp-alert-close{cursor:pointer;color:var(--t3);font-size:12px;transition:color .12s}
.fp-alert-close:hover{color:var(--t2)}
.fp-alert-body{max-height:280px;overflow-y:auto;padding:6px}
.fp-alert-item{
    padding:8px 10px;margin-bottom:3px;
    border-radius:7px;
    background:rgba(255,255,255,.025);
    border-left:2px solid var(--al-color,#8691a8);
}
.fp-alert-item-title{font-size:11px;font-weight:700;color:var(--t1);margin-bottom:1px}
.fp-alert-item-msg{font-size:10.5px;color:var(--t2)}
.fp-alert-empty{padding:20px;text-align:center;font-size:11.5px;color:var(--t3)}

@media(max-width:900px){
    .fp-sb{display:none}
    .fp-body{padding:1rem}
    .fp-kpi-grid.col4{grid-template-columns:1fr 1fr}
    .fp-kpi-grid.col3{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>
{$rnbNav}
<div class="fp-shell">

<!-- ═══ SIDEBAR ═══════════════════════════════════════════════ -->
<aside class="fp-sb">
    <div class="fp-brand">
        <div class="fp-logo">FP</div>
        <div>
            <div class="fp-brand-name">Finance Pro</div>
            <div class="fp-brand-sub">Rádio New Band · Angola</div>
        </div>
    </div>

    <div class="fp-period">
        <i class="bi bi-calendar3"></i>
        <span class="fp-period-txt">{$periodoLabel}</span>
        <span class="fp-period-tag">Actual</span>
    </div>

    <nav class="fp-nav">{$navHtml}</nav>

    <div class="fp-sb-foot">
        <a href="/public/pulso/{$stationId}" class="fp-back">
            <i class="bi bi-arrow-left"></i>
            <span>Voltar ao PULSO</span>
        </a>
        <div class="fp-util">
            <a onclick="fpCfg()" class="fp-util-a"><i class="bi bi-gear"></i> Configurações</a>
            <a onclick="fpUsr()" class="fp-util-a"><i class="bi bi-people"></i> Utilizadores</a>
        </div>
        <div class="fp-user">
            <div class="fp-av">N</div>
            <div>
                <div class="fp-uname">Newton dos Santos</div>
                <div class="fp-urole">Administrador</div>
            </div>
            <div class="fp-udot"></div>
        </div>
    </div>
</aside>

<!-- ═══ MAIN ══════════════════════════════════════════════════ -->
<div class="fp-main">
    <header class="fp-top">
        <div class="fp-bc">Finance Pro <span class="fp-bc-sep">›</span> <b>{$title}</b></div>
        <div class="fp-top-r">
            <a href="/public/financas/{$stationId}/exportar-pdf/dre" class="fp-top-btn ghost" style="font-size:11px">
                <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
            </a>
            <div class="fp-top-icon" id="fp-bell" title="Alertas">
                <i class="bi bi-bell"></i>
                <span class="fp-bell-dot" id="fp-bell-dot"></span>
            </div>
        </div>
    </header>

    <main class="fp-body">{$content}</main>

    <footer class="fp-foot">
        <span>Finance Pro · Rádio New Band Angola · PGC Angola</span>
        <span><?= date('d/m/Y H:i') ?></span>
    </footer>
</div>
</div><!-- shell -->

<!-- ═══ PAINEL ALERTAS ════════════════════════════════════════ -->
<div class="fp-alert-panel" id="fp-alert-panel">
    <div class="fp-alert-head">
        <span>🔔 Alertas</span>
        <span class="fp-alert-close" id="fp-alert-close">✕</span>
    </div>
    <div class="fp-alert-body" id="fp-alert-body"></div>
</div>

<!-- ═══ MODAL CONFIGURAÇÕES ══════════════════════════════════ -->
<div class="fp-modal-bg" id="fp-m-cfg">
    <div class="fp-modal">
        <div class="fp-modal-head">
            <div class="fp-modal-title">⚙️ Configurações</div>
            <button class="fp-modal-close" onclick="fpClose('fp-m-cfg')">✕</button>
        </div>
        <div style="display:flex;flex-direction:column;gap:.625rem">
            <div style="padding:.875rem;background:var(--bg3);border:1px solid var(--bd);border-radius:9px;display:flex;align-items:center;gap:.75rem">
                <span style="font-size:22px">📊</span>
                <div style="flex:1">
                    <div style="font-size:12px;font-weight:700;color:var(--t1)">PGC Angola — 46 contas</div>
                    <div style="font-size:10px;color:var(--t3);margin-top:1px">Plano de Contas activo · Moeda: Kz</div>
                </div>
                <span style="font-size:9.5px;font-weight:700;color:var(--g);background:rgba(16,185,129,.1);padding:2px 7px;border-radius:12px;border:1px solid rgba(16,185,129,.18)">✓ OK</span>
            </div>
            <a href="/public/financas/{$stationId}/conta-corrente" onclick="fpClose('fp-m-cfg')"
               style="padding:.875rem;background:var(--bg3);border:1px solid var(--bd);border-radius:9px;display:flex;align-items:center;gap:.75rem;text-decoration:none;transition:border-color .13s">
                <span style="font-size:22px">🏦</span>
                <div style="flex:1">
                    <div style="font-size:12px;font-weight:700;color:var(--t1)">Contas Bancárias</div>
                    <div style="font-size:10px;color:var(--t3);margin-top:1px">BFA Principal · BPC Operacional</div>
                </div>
                <span style="font-size:12px;color:var(--t3)">→</span>
            </a>
        </div>
    </div>
</div>

<!-- ═══ MODAL UTILIZADORES ═══════════════════════════════════ -->
<div class="fp-modal-bg" id="fp-m-usr">
    <div class="fp-modal" style="max-width:400px">
        <div class="fp-modal-head">
            <div class="fp-modal-title">👥 Utilizadores</div>
            <button class="fp-modal-close" onclick="fpClose('fp-m-usr')">✕</button>
        </div>
        <div style="display:flex;align-items:center;gap:.875rem;padding:.875rem;background:rgba(16,185,129,.06);border:1px solid rgba(16,185,129,.14);border-radius:10px;margin-bottom:.875rem">
            <div style="width:40px;height:40px;border-radius:50%;background:rgba(16,185,129,.14);border:2px solid rgba(16,185,129,.28);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:#10b981">N</div>
            <div>
                <div style="font-size:13px;font-weight:800;color:var(--t1)">Newton dos Santos</div>
                <div style="font-size:10px;color:var(--t3)">Administrador · Acesso total</div>
            </div>
            <span style="margin-left:auto;font-size:9px;font-weight:800;padding:2px 8px;border-radius:20px;background:rgba(16,185,129,.1);color:#10b981;border:1px solid rgba(16,185,129,.2)">ADMIN</span>
        </div>
        <div style="padding:.875rem;background:rgba(255,255,255,.02);border:1px solid var(--bd);border-radius:9px;text-align:center;font-size:11px;color:var(--t3)">
            Gestão multi-utilizador disponível em breve
        </div>
    </div>
</div>

<script>
const FP_SID      = {$stationId};
const FP_ALERTAS  = {$alertasJson};
const AL_CORES    = {danger:'#ef4444',warning:'#f59e0b',info:'#00e5ff',success:'#10b981'};

// Modais
function fpCfg()  { document.getElementById('fp-m-cfg').classList.add('open'); }
function fpUsr()  { document.getElementById('fp-m-usr').classList.add('open'); }
function fpClose(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.fp-modal-bg').forEach(m=>{
    m.addEventListener('click', e=>{ if(e.target===m) m.classList.remove('open'); });
});

// Bell
(function(){
    const bell  = document.getElementById('fp-bell');
    const panel = document.getElementById('fp-alert-panel');
    const body  = document.getElementById('fp-alert-body');
    const close = document.getElementById('fp-alert-close');
    const dot   = document.getElementById('fp-bell-dot');

    if (!FP_ALERTAS || FP_ALERTAS.length === 0) {
        dot.style.display = 'none';
        body.innerHTML = '<div class="fp-alert-empty">✅ Sem alertas activos</div>';
    } else {
        body.innerHTML = FP_ALERTAS.map(a=>`
            <div class="fp-alert-item" style="--al-color:${AL_CORES[a.tipo] || '#8691a8'}">
                <div class="fp-alert-item-title">${a.icon || '⚠️'} ${a.titulo}</div>
                <div class="fp-alert-item-msg">${a.mensagem}</div>
            </div>`).join('');
    }

    bell.addEventListener('click',()=>{
        panel.style.display = panel.style.display==='block' ? 'none' : 'block';
    });
    close.addEventListener('click',()=>{ panel.style.display='none'; });
    document.addEventListener('click',e=>{
        if(!bell.contains(e.target)&&!panel.contains(e.target)) panel.style.display='none';
    });
})();
</script>
</body>
</html>
HTML;
    }


    public function fpPlanoContasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId  = (int) $params['station_id'];
        $filtro     = $request->getQueryParams()['filtro'] ?? 'todas';
        $dados      = $this->service->getFpPlanoConta($stationId);
        $centros    = $this->service->getFpCentrosCusto($stationId);
        $contaSel   = null;
        if (!empty($request->getQueryParams()['conta'])) {
            $contaSel = $this->service->getFpContaById((int)$request->getQueryParams()['conta'], $stationId);
        }
        ob_start();
        include __DIR__ . '/../../templates/fp-plano-contas.php';
        $html = $this->renderPageFinancas('Plano de Contas', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function fpContaSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $post      = $request->getParsedBody();
        $id        = !empty($post['id']) ? (int)$post['id'] : null;
        $this->service->fpSalvarConta($stationId, $post, $id);
        return $response->withHeader('Location', "/public/financas/{$stationId}/plano-contas")->withStatus(302);
    }

    public function fpContaDetalheAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $conta = $this->service->getFpContaById((int)$params['id'], $stationId);
        $response->getBody()->write(json_encode($conta));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fpCentrosCustoAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $centros   = $this->service->getFpCentrosCusto($stationId);
        ob_start();
        include __DIR__ . '/../../templates/fp-centros-custo.php';
        $html = $this->renderPageFinancas('Centros de Custo', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }


    public function fpLancamentosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId  = (int) $params['station_id'];
        $filtros    = $request->getQueryParams();
        $dados      = $this->service->getFpLancamentos($stationId, $filtros);
        $contas     = $this->service->getFpPlanoConta($stationId)['contas'];
        $centros    = $this->service->getFpCentrosCusto($stationId);
        $patrocinadores = $this->service->getPatrocinadores($stationId);
        ob_start();
        include __DIR__ . '/../../templates/fp-lancamentos.php';
        $html = $this->renderPageFinancas('Lançamentos', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function fpLancamentoSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->fpSalvarLancamento($stationId, $request->getParsedBody());
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fpLancamentoCancelarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->fpCancelarLancamento((int)$params['id'], $stationId);
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function fpContasPagarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId      = (int) $params['station_id'];
        $filtros        = $request->getQueryParams();
        $dados          = $this->service->getFpMovimentos($stationId, 'pagar', $filtros);
        $contas         = $this->service->getFpPlanoConta($stationId)['contas'];
        $patrocinadores = $this->service->getPatrocinadores($stationId);
        $tipo           = 'pagar';
        ob_start();
        include __DIR__ . '/../../templates/fp-movimentos.php';
        $html = $this->renderPageFinancas('Contas a Pagar', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function fpContasReceberAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId      = (int) $params['station_id'];
        $filtros        = $request->getQueryParams();
        $dados          = $this->service->getFpMovimentos($stationId, 'receber', $filtros);
        $contas         = $this->service->getFpPlanoConta($stationId)['contas'];
        $patrocinadores = $this->service->getPatrocinadores($stationId);
        $tipo           = 'receber';
        ob_start();
        include __DIR__ . '/../../templates/fp-movimentos.php';
        $html = $this->renderPageFinancas('Contas a Receber', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }


    public function fpMovimentoEditarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id        = (int) $params['id'];
        $post      = $request->getParsedBody();
        $ok        = $this->service->fpEditarMovimento($id, $stationId, $post);
        $response->getBody()->write(json_encode(['sucesso' => $ok, 'erro' => $ok ? null : 'Não é possível editar este registo']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fpMovimentoCancelarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id        = (int) $params['id'];
        $motivo    = $request->getParsedBody()['motivo'] ?? '';
        $ok        = $this->service->fpCancelarMovimento($id, $stationId, $motivo);
        $response->getBody()->write(json_encode(['sucesso' => $ok]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function patrocinadorEditarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id        = (int) $params['id'];
        $post      = $request->getParsedBody();
        $this->service->salvarPatrocinador($stationId, $post, $id);
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function patrocinadorExcluirAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id        = (int) $params['id'];
        $this->service->fpExcluirPatrocinador($id, $stationId);
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function contratoEditarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id        = (int) $params['id'];
        $post      = $request->getParsedBody();
        $this->service->salvarContrato($stationId, $post, $id);
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function contratoExcluirAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $id        = (int) $params['id'];
        $this->service->fpExcluirContrato($id, $stationId);
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fpMovimentoSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $post      = $request->getParsedBody();
        $this->service->fpSalvarMovimento($stationId, $post);
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fpMovimentoBaixarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->fpBaixarMovimento((int)$params['id'], $stationId, $request->getParsedBody());
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function fpContaCorrenteAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $dados     = $this->service->getFpContaCorrente($stationId);
        ob_start();
        include __DIR__ . '/../../templates/fp-conta-corrente.php';
        $html = $this->renderPageFinancas('Conta Corrente', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function fpContaCorrenteMovimentoAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->fpRegistarMovimentoBancario($stationId, $request->getParsedBody());
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function fpComissoesAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId    = (int) $params['station_id'];
        $dados        = $this->service->getFpComissoes($stationId);
        $contratos    = $this->service->getContratos($stationId);
        $patrocinadores = $this->service->getPatrocinadores($stationId);
        ob_start();
        include __DIR__ . '/../../templates/fp-comissoes.php';
        $html = $this->renderPageFinancas('Comissões', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function fpComissaoSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->fpSalvarComissao($stationId, $request->getParsedBody());
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fpComissaoPagarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $this->service->fpPagarComissao((int)$params['id'], $stationId);
        $response->getBody()->write(json_encode(['sucesso' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function fpFluxoCaixaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $meses     = (int)($request->getQueryParams()['meses'] ?? 6);
        $fluxo     = $this->service->getFpFluxoCaixa($stationId, $meses);
        ob_start();
        include __DIR__ . '/../../templates/fp-fluxo-caixa.php';
        $html = $this->renderPageFinancas('Fluxo de Caixa', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function fpDreAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $ano       = (int)($request->getQueryParams()['ano'] ?? date('Y'));
        $dados     = $this->service->getFpDre($stationId, $ano);
        ob_start();
        include __DIR__ . '/../../templates/fp-dre.php';
        $html = $this->renderPageFinancas('DRE', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function fpRelatoriosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $ano       = (int)($request->getQueryParams()['ano'] ?? date('Y'));
        $dre       = $this->service->getFpDre($stationId, $ano);
        $fluxo     = $this->service->getFpFluxoCaixa($stationId, 6);
        $dashboard = $this->service->getFpDashboard($stationId);
        ob_start();
        include __DIR__ . '/../../templates/fp-relatorios.php';
        $html = $this->renderPageFinancas('Relatórios', ob_get_clean(), $stationId);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }


    public function fpExportarPdfAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $stationId = (int) $params['station_id'];
        $tipo      = $params['tipo'] ?? 'dre';
        $qp        = $request->getQueryParams();

        $pdf = $this->service->gerarPdfFinancas($stationId, $tipo, $qp);

        if (!$pdf) {
            $response->getBody()->write('Tipo de PDF inválido');
            return $response->withStatus(400);
        }

        $nomes = [
            'dre'     => 'dre-' . date('Y') . '.pdf',
            'fluxo'   => 'fluxo-caixa-' . date('Y-m') . '.pdf',
            'extracto'=> 'extracto-bancario-' . date('Y-m') . '.pdf',
        ];
        $filename = $nomes[$tipo] ?? 'relatorio.pdf';

        $response->getBody()->write($pdf);
        return $response
            ->withHeader('Content-Type', 'application/pdf')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

}