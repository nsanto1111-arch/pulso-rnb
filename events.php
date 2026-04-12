<?php
declare(strict_types=1);

use App\Event\BuildRoutes;
use App\Event\BuildView;

return function (\App\CallableEventDispatcher $dispatcher): void {
    
    // ========== ROTAS ==========
    $dispatcher->addListener(
        BuildRoutes::class,
        function (BuildRoutes $event): void {
            $app = $event->getApp();

            // ========== ROTAS ADMIN (Dashboard) ==========
            $app->get('/public/programacao/{station_id}', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':indexAction');

            // ========== PROGRAMAS ==========
            $app->get('/public/programacao/{station_id}/programas', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':programasAction');
            $app->get('/public/programacao/{station_id}/programas/novo', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':programaFormAction');
            $app->post('/public/programacao/{station_id}/programas/novo', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':programaSaveAction');
            $app->get('/public/programacao/{station_id}/programas/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':programaFormAction');
            $app->post('/public/programacao/{station_id}/programas/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':programaSaveAction');
            $app->post('/public/programacao/{station_id}/programas/{id}/excluir', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':programaDeleteAction');

            // ========== LOCUTORES ==========
            $app->get('/public/programacao/{station_id}/locutores', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':locutoresAction');
            $app->get('/public/programacao/{station_id}/locutores/novo', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':locutorFormAction');
            $app->post('/public/programacao/{station_id}/locutores/novo', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':locutorSaveAction');
            $app->get('/public/programacao/{station_id}/locutores/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':locutorFormAction');
            $app->post('/public/programacao/{station_id}/locutores/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':locutorSaveAction');
            $app->post('/public/programacao/{station_id}/locutores/{id}/excluir', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':locutorDeleteAction');

            

            // ========== SYNC WORDPRESS ==========
            $app->post('/pulso/sync-wordpress', function ($request, $response) {
                require __DIR__ . '/public/sync-wordpress.php';
                return $response->withStatus(200);
            });
            // ========== SYNC WORDPRESS ==========
            $app->post('/pulso/api-sync', function ($request, $response) {
                require __DIR__ . '/public/api-sync.php';
                return $response;
            });

            // ========== PULSO LOCUTOR ==========
            $app->get('/pulso/api/locutor', function ($request, $response) {
                require __DIR__ . '/public/api-locutor.php';
                return $response->withStatus(200);
            });
            $app->post('/pulso/api/locutor', function ($request, $response) {
                require __DIR__ . '/public/api-locutor.php';
                return $response->withStatus(200);
            });

            $app->get('/pulso/static/{file}', function (\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $params) {
                $file = basename($params['file']);
                $path = __DIR__ . '/public/' . $file;
                if (!file_exists($path)) return $response->withStatus(404);
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $mime = ['js'=>'application/javascript','css'=>'text/css'][$ext] ?? 'text/plain';
                $response->getBody()->write(file_get_contents($path));
                return $response->withHeader('Content-Type', $mime)->withHeader('Cache-Control','no-cache');
            });
            $app->get('/pulso/locutor', function (\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response) {
                require __DIR__ . '/public/locutor.php';
                return $response->withStatus(200);
            });
            

            // ========== WEBHOOK WORDPRESS ==========
            $app->post('/pulso/api/sync-wp', function ($request, $response) {
                require __DIR__ . '/public/api-sync.php';
                return $response->withStatus(200);
            });
            
// ========== PLAYER PÚBLICO ==========
            $app->get("/public/programacao/{station_id}/player", \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ":playerAction");
            // ========== CARROSSEL ==========
            $app->get('/public/programacao/{station_id}/carrossel/{id}/clonar', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':carrosselDuplicateAction');
            $app->get('/public/programacao/{station_id}/carrossel/exportar', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':carrosselExportAction');
            $app->post('/public/programacao/{station_id}/carrossel/importar', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':carrosselImportAction');
            $app->get('/public/programacao/{station_id}/estatisticas', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':estatisticasAction');
            $app->get('/public/programacao/{station_id}/carrossel', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':carrosselAction');
            $app->get('/public/programacao/{station_id}/carrossel/novo', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':carrosselFormAction');
            $app->post('/public/programacao/{station_id}/carrossel/novo', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':carrosselSaveAction');
            $app->get('/public/programacao/{station_id}/carrossel/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':carrosselFormAction');
            $app->post('/public/programacao/{station_id}/carrossel/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':carrosselSaveAction');
            $app->post('/public/programacao/{station_id}/carrossel/{id}/excluir', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':carrosselDeleteAction');
            $app->get('/public/programacao/{station_id}/carrossel/{id}/toggle', \Plugin\ProgramacaoPlugin\Controller\ProgramacaoAdminController::class . ':carrosselToggleAction');

            // ========== API WIDGET PLAYER ==========
            $app->get("/api/station/{station_id}/programacao/widget", \Plugin\ProgramacaoPlugin\Controller\ProgramacaoApiController::class . ":widgetAction");
            $app->get("/pulso/widget", \Plugin\ProgramacaoPlugin\Controller\ProgramacaoApiController::class . ":widgetDefaultAction");
            // ========== API PÚBLICA ==========

            // ========== PULSO - INTELIGÊNCIA DE AUDIÊNCIA ==========
            // Middleware de autenticação para rotas PULSO
            // ========== PULSO ROTAS ==========
            $app->get('/public/pulso/{station_id}', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':dashboardAction');
            $app->get('/public/pulso/{station_id}/ouvintes', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':ouvintesAction');
            $app->get('/public/pulso/{station_id}/ouvintes/novo', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':novoOuvinteAction');
            $app->get('/public/pulso/{station_id}/ouvintes/enriquecer', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':enriquecerAction');
            $app->post('/public/pulso/{station_id}/ouvintes/enriquecer/arquivar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':arquivarFantasmasAction');
            $app->post('/public/pulso/{station_id}/ouvintes/salvar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':salvarOuvinteAction');
            $app->get('/public/pulso/{station_id}/ouvintes/{id}/ficha', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':ouvinteFichaAction');
            $app->get('/public/financas/{station_id}/plano-contas', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpPlanoContasAction');
            $app->post('/public/financas/{station_id}/plano-contas/salvar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpContaSalvarAction');
            $app->get('/public/financas/{station_id}/plano-contas/{id}/detalhe', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpContaDetalheAction');
            $app->get('/public/financas/{station_id}/lancamentos', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpLancamentosAction');
            $app->post('/public/financas/{station_id}/lancamentos/salvar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpLancamentoSalvarAction');
            $app->post('/public/financas/{station_id}/lancamentos/{id}/cancelar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpLancamentoCancelarAction');
            $app->get('/public/financas/{station_id}/contas-pagar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpContasPagarAction');
            $app->get('/public/financas/{station_id}/contas-receber', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpContasReceberAction');
            $app->post('/public/financas/{station_id}/contas-movimento/salvar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpMovimentoSalvarAction');
            $app->post('/public/financas/{station_id}/contas-movimento/{id}/baixar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpMovimentoBaixarAction');
            $app->post('/public/financas/{station_id}/contas-movimento/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpMovimentoEditarAction');
            $app->post('/public/financas/{station_id}/contas-movimento/{id}/cancelar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpMovimentoCancelarAction');
            $app->post('/public/financas/{station_id}/patrocinadores/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':patrocinadorEditarAction');
            $app->post('/public/financas/{station_id}/patrocinadores/{id}/excluir', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':patrocinadorExcluirAction');
            $app->post('/public/financas/{station_id}/contratos/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':contratoEditarAction');
            $app->post('/public/financas/{station_id}/contratos/{id}/excluir', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':contratoExcluirAction');
            $app->get('/public/financas/{station_id}/conta-corrente', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpContaCorrenteAction');
            $app->post('/public/financas/{station_id}/conta-corrente/movimento', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpContaCorrenteMovimentoAction');
            $app->get('/public/financas/{station_id}/comissoes', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpComissoesAction');
            $app->post('/public/financas/{station_id}/comissoes/salvar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpComissaoSalvarAction');
            $app->post('/public/financas/{station_id}/comissoes/{id}/pagar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpComissaoPagarAction');
            $app->get('/public/financas/{station_id}/fluxo-caixa', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpFluxoCaixaAction');
            $app->get('/public/financas/{station_id}/dre', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpDreAction');
            $app->get('/public/financas/{station_id}/exportar-pdf/{tipo}', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpExportarPdfAction');
            $app->get('/public/financas/{station_id}/relatorios-fp', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpRelatoriosAction');
            $app->get('/public/financas/{station_id}/centros-custo', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':fpCentrosCustoAction');
            // ─── RNB RH ──────────────────────────────────────────
            
            
            
            
            
            
        
    // DEBUG: Testar login do portal
    $app->map(['GET', 'POST'], '/public/portal/debug', function (
        ServerRequest $request,
        Response $response
    ) use ($di) {
        $db = $di->get(Doctrine\DBAL\Connection::class);
        
        ob_start();
        echo "<h1>Debug Portal Login</h1>";
        echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:2rem}h1{color:#ff0}pre{background:#000;padding:1rem;border:1px solid #0f0}input,button{padding:0.5rem;margin:0.5rem 0;font-size:14px}</style>";
        
        if ($request->getMethod() === 'POST') {
            $post = $request->getParsedBody();
            echo "<h2>POST RECEBIDO</h2>";
            echo "<pre>Username: " . ($post['username'] ?? 'VAZIO') . "</pre>";
            echo "<pre>Password: " . (isset($post['password']) ? 'FORNECIDA' : 'VAZIA') . "</pre>";
            
            $username = $post['username'] ?? '';
            $password = $post['password'] ?? '';
            
            // Buscar utilizador
            echo "<h2>1. BUSCAR UTILIZADOR</h2>";
            $stmt = $db->prepare("SELECT * FROM rnb_portal_users WHERE username = ? AND activo = 1");
            $stmt->bindValue(1, $username);
            $result = $stmt->executeQuery();
            $user = $result->fetchAssociative();
            
            if ($user) {
                echo "<pre>✅ Utilizador encontrado: ID={$user['id']}, Anunciante={$user['anunciante_id']}</pre>";
            } else {
                echo "<pre>❌ Utilizador NÃO encontrado</pre>";
                echo ob_get_clean();
                return $response->withHeader('Content-Type', 'text/html');
            }
            
            // Verificar password
            echo "<h2>2. VERIFICAR PASSWORD</h2>";
            if (password_verify($password, $user['password_hash'])) {
                echo "<pre>✅ Password correcta</pre>";
            } else {
                echo "<pre>❌ Password incorrecta</pre>";
                echo ob_get_clean();
                return $response->withHeader('Content-Type', 'text/html');
            }
            
            // Criar token
            echo "<h2>3. CRIAR TOKEN</h2>";
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 28800);
            echo "<pre>Token: " . substr($token, 0, 20) . "...</pre>";
            echo "<pre>Expires: {$expires}</pre>";
            
            // Limpar tokens antigos
            echo "<h2>4. LIMPAR TOKENS ANTIGOS</h2>";
            $db->executeStatement("DELETE FROM rnb_portal_tokens WHERE user_id=?", [$user['id']]);
            echo "<pre>✅ Tokens antigos apagados</pre>";
            
            // Inserir token
            echo "<h2>5. INSERIR TOKEN NA BD</h2>";
            try {
                $db->insert('rnb_portal_tokens', [
                    'token' => $token,
                    'user_id' => $user['id'],
                    'anunciante_id' => $user['anunciante_id'],
                    'station_id' => $user['station_id'] ?? 1,
                    'expires_at' => $expires,
                ]);
                echo "<pre>✅ Token inserido</pre>";
            } catch (Exception $e) {
                echo "<pre>❌ ERRO: " . $e->getMessage() . "</pre>";
                echo ob_get_clean();
                return $response->withHeader('Content-Type', 'text/html');
            }
            
            // Definir cookie
            echo "<h2>6. DEFINIR COOKIE</h2>";
            $cookieSet = setcookie('RNB_PORTAL', $token, [
                'expires' => time() + 28800,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            echo "<pre>setcookie() retornou: " . ($cookieSet ? '✅ TRUE' : '❌ FALSE') . "</pre>";
            
            // Verificar se cookie foi enviado
            echo "<h2>7. VERIFICAR COOKIE NO BROWSER</h2>";
            echo "<pre>Verifica F12 → Application → Cookies → rnb.radionewband.ao</pre>";
            echo "<pre>Deve haver um cookie 'RNB_PORTAL' com valor: " . substr($token, 0, 20) . "...</pre>";
            
            echo "<h2>8. TESTAR ACESSO AO DASHBOARD</h2>";
            echo "<a href='/public/portal/1' style='color:#0ff;font-size:18px'>Clica aqui para ir ao Dashboard</a>";
            
        } else {
            echo "<form method='POST'>";
            echo "Username: <input name='username' value='teste' style='width:200px'><br>";
            echo "Password: <input name='password' type='password' value='Portal2026!' style='width:200px'><br>";
            echo "<button type='submit'>TESTAR LOGIN</button>";
            echo "</form>";
        }
        
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });

    // Portal do Anunciante
            
            // RNB Media Sync
            $app->get('/api/rnb/media/sync-azuracast',  \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':mediaSyncAzAction');
            $app->get('/api/rnb/media/divergencias',    \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':mediaDivAction');
            $app->get('/api/rnb/media/analise',         \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':mediaAnaliseAction');
            $app->get('/api/rnb/media/pesquisar',       \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':mediaPesquisarAction');
            
            // WordPress Sync + Relatório Semanal
            $app->get('/api/rnb/wp-sync',       \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':wpSyncAction');
            $app->get('/api/rnb/relatorio-semanal', \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':relatorioSemanalAction');
            $app->get('/api/rnb/relatorio-semanal/html', \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':relatorioSemanalHtmlAction');
            
            // RNB Intelligence — programação + audiência cruzada
            $app->get('/api/rnb/intelligence',   \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':intelligenceAction');
            $app->get('/api/rnb/programa-no-ar', \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':programaNoArAction');
            
            // RNB Sync — sincronização automática
            $app->get('/api/rnb/sync/performance', \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':syncPerformanceAction');
            $app->get('/api/rnb/sync/comercial',   \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':syncComercialAction');
            $app->get('/api/rnb/prova-emissao',    \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':provaEmissaoAction');
            $app->get('/api/rnb/top-musicas',      \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':topMusicasAction');
            
            // RNB Signal Layer — API unificada
            $app->get('/api/rnb/now-playing', \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':nowPlayingAction');
            $app->get('/api/rnb/status',      \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':statusAction');
            $app->get('/api/rnb/history',     \Plugin\ProgramacaoPlugin\Controller\ApiController::class . ':historyAction');
            
            // RH — Autenticação (DEVE estar antes das rotas dinâmicas)
            $app->get('/public/rh/login', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':loginAction');
            $app->post('/public/rh/login', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':loginPostAction');
            $app->get('/public/rh/logout', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':logoutAction');
                        $app->get('/public/rh/{station_id}', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':indexAction');
            $app->get('/public/rh/{station_id}/funcionarios', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':funcionariosAction');
            $app->post('/public/rh/{station_id}/funcionarios/salvar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':funcionarioSalvarAction');
            $app->get('/public/rh/{station_id}/folha-pagamento', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':folhaAction');
            $app->post('/public/rh/{station_id}/folha-pagamento/processar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':folhaProcessarAction');
            $app->post('/public/rh/{station_id}/folha-pagamento/{id}/pagar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':folhaPagarAction');
            $app->get('/public/rh/{station_id}/ferias', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':feriasAction');
            $app->post('/public/rh/{station_id}/ferias/salvar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':feriasSalvarAction');
            $app->post('/public/rh/{station_id}/ferias/{id}/aprovar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':feriasAprovarAction');
            $app->get('/public/rh/{station_id}/escalas', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':escalasAction');
            $app->post('/public/rh/{station_id}/escalas/salvar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':escalaSalvarAction');
            $app->get('/public/rh/{station_id}/relatorios', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':relatoriosAction');
            $app->get('/public/rh/{station_id}/performance', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':performanceAction');
            $app->post('/public/rh/{station_id}/performance/calcular', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':performanceCalcularAction');
            $app->get('/public/rh/{station_id}/contratos', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':contratosAction');
            $app->post('/public/rh/{station_id}/contratos/salvar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':contratoSalvarAction');
            $app->get('/public/rh/{station_id}/alertas', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':alertasAction');
            $app->post('/public/rh/{station_id}/alertas/gerar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':alertasGerarAction');
            
            // RH — Skills e Documentos
            $app->get('/public/rh/{station_id}/funcionario/{id}', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':perfilAction');
            $app->post('/public/rh/{station_id}/skills/salvar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':skillSalvarAction');
            $app->post('/public/rh/{station_id}/skills/{id}/apagar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':skillApagarAction');
            $app->post('/public/rh/{station_id}/documentos/upload', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':documentoUploadAction');
            $app->get('/public/rh/{station_id}/documentos/{id}/download', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':documentoDownloadAction');
            $app->post('/public/rh/{station_id}/documentos/{id}/apagar', \Plugin\ProgramacaoPlugin\Controller\RhController::class . ':documentoApagarAction');
            // ────────────────────────────────────────────────────
            // ─── RNB NEWS ────────────────────────────────────────
            $app->get('/public/news/{station_id}', \Plugin\ProgramacaoPlugin\Controller\NewsController::class . ':indexAction');
            $app->post('/public/news/{station_id}/salvar', \Plugin\ProgramacaoPlugin\Controller\NewsController::class . ':salvarAction');
            $app->post('/public/news/{station_id}/{id}/apagar', \Plugin\ProgramacaoPlugin\Controller\NewsController::class . ':apagarAction');
            $app->post('/public/news/{station_id}/{id}/marcar-lida', \Plugin\ProgramacaoPlugin\Controller\NewsController::class . ':marcarLidaAction');
            $app->get('/public/news/{station_id}/tabua', \Plugin\ProgramacaoPlugin\Controller\NewsController::class . ':tabuaAction');
            $app->get('/public/news/{station_id}/agenda', \Plugin\ProgramacaoPlugin\Controller\NewsController::class . ':agendaAction');
            $app->post('/public/news/{station_id}/agenda/salvar', \Plugin\ProgramacaoPlugin\Controller\NewsController::class . ':agendaSalvarAction');
            $app->get('/public/news/{station_id}/arquivo', \Plugin\ProgramacaoPlugin\Controller\NewsController::class . ':arquivoAction');
            // ─────────────────────────────────────────────────────
            // ─── DASHBOARD EXECUTIVO ─────────────────────────────
                        // Dashboard APEX — novo design system
            $app->get('/public/apex/{station_id}', \Plugin\ProgramacaoPlugin\Controller\DashboardApexController::class . ':indexAction');
            $app->get('/public/dashboard/{station_id}', \Plugin\ProgramacaoPlugin\Controller\DashboardController::class . ':indexAction');
            // ─────────────────────────────────────────────────────
            // ─── PORTAL DO ANUNCIANTE ────────────────────────────────
            $app->get('/public/portal/login', \Plugin\ProgramacaoPlugin\Controller\PortalController::class . ':loginAction');
            $app->post('/public/portal/login', \Plugin\ProgramacaoPlugin\Controller\PortalController::class . ':loginPostAction');
            $app->get('/public/portal/logout', \Plugin\ProgramacaoPlugin\Controller\PortalController::class . ':logoutAction');
            $app->get('/public/portal/{station_id}', \Plugin\ProgramacaoPlugin\Controller\PortalController::class . ':dashboardAction');
            $app->get('/public/portal/{station_id}/campanhas', \Plugin\ProgramacaoPlugin\Controller\PortalController::class . ':campanhasAction');
            $app->get('/public/portal/{station_id}/prova-emissao/pdf', \Plugin\ProgramacaoPlugin\Controller\PortalController::class . ':provaEmissaoPdfAction');
            $app->get('/public/portal/{station_id}/prova-emissao', \Plugin\ProgramacaoPlugin\Controller\PortalController::class . ':provaEmissaoAction');
            $app->get('/public/portal/{station_id}/facturas', \Plugin\ProgramacaoPlugin\Controller\PortalController::class . ':facturasAction');
            // ─────────────────────────────────────────────────────────────────
            
            // ─── RNB COMERCIAL ───────────────────────────────────
            $app->get('/public/comercial/{station_id}', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':dashboardAction');
            $app->get('/public/comercial/{station_id}/anunciantes', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':anunciantesAction');
            $app->post('/public/comercial/{station_id}/anunciantes/salvar', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':anuncianteSalvarAction');
            $app->post('/public/comercial/{station_id}/anunciantes/{id}/apagar', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':anuncianteApagarAction');
            $app->get('/public/comercial/{station_id}/propostas', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':propostasAction');
            $app->post('/public/comercial/{station_id}/propostas/salvar', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':propostaSalvarAction');
            $app->post('/public/comercial/{station_id}/propostas/{id}/estado', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':propostaEstadoAction');
            $app->get('/public/comercial/{station_id}/contratos', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':contratosAction');
            $app->post('/public/comercial/{station_id}/contratos/salvar', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':contratoSalvarAction');
            $app->get('/public/comercial/{station_id}/campanhas', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':campanhasAction');
            $app->post('/public/comercial/{station_id}/campanhas/salvar', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':campanhaSalvarAction');
            $app->get('/public/comercial/{station_id}/pipeline', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':pipelineAction');
            $app->get('/public/comercial/{station_id}/equipa', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':equipaAction');
            $app->post('/public/comercial/{station_id}/equipa/salvar', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':equipaSalvarAction');
            $app->post('/public/comercial/{station_id}/contratos/{id}/gerar-fatura', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':gerarFaturaAction');
            $app->post('/public/comercial/{station_id}/contratos/{id}/sincronizar', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':sincronizarFinancasAction');
            $app->get('/public/comercial/{station_id}/relatorios', \Plugin\ProgramacaoPlugin\Controller\ComercialController::class . ':relatoriosAction');
            // ─────────────────────────────────────────────────────
            $app->get('/public/financas/{station_id}', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':financasAction');
            $app->get('/public/financas/{station_id}/patrocinadores', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':patrocinadoresAction');
            $app->post('/public/financas/{station_id}/patrocinadores/salvar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':patrocinadorSalvarAction');
            $app->get('/public/financas/{station_id}/contratos', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':contratosAction');
            $app->post('/public/financas/{station_id}/contratos/salvar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':contratoSalvarAction');
            $app->post('/public/financas/{station_id}/receitas/salvar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':receitaSalvarAction');
            $app->post('/public/financas/{station_id}/despesas/salvar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':despesaSalvarAction');
            $app->post('/public/financas/{station_id}/metas/salvar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':metaSalvarAction');
            $app->get('/public/pulso/{station_id}/stream', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':streamAction');
            $app->get('/pulso/api/stream/{station_id}', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':streamApiAction');
            $app->get('/public/pulso/{station_id}/aniversarios', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':aniversariosAction');
            $app->post('/public/pulso/{station_id}/aniversarios/sortear', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':sortearAniversariosAction');
            $app->get('/public/pulso/{station_id}/atendimento', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':atendimentoAction');
            $app->post('/public/pulso/{station_id}/atendimento/buscar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':atendimentoBuscarAction');
            $app->post('/public/pulso/{station_id}/atendimento/registar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':atendimentoRegistarAction');
            $app->get('/public/pulso/{station_id}/atendimento/historico', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':atendimentoHistoricoAction');
            $app->get('/public/pulso/{station_id}/premios', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':premiosAction');
            $app->get('/public/pulso/{station_id}/premios/novo', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':premioFormAction');
            $app->post('/public/pulso/{station_id}/premios/novo', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':premioSaveAction');
            $app->get('/public/pulso/{station_id}/premios/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':premioFormAction');
            $app->post('/public/pulso/{station_id}/premios/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':premioSaveAction');
            $app->post('/public/pulso/{station_id}/premios/{id}/excluir', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':premioDeleteAction');
            $app->get('/public/pulso/{station_id}/entregas', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':entregasAction');
            $app->post('/public/pulso/{station_id}/entregas/{id}/estado', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':entregaEstadoAction');
            $app->get('/public/pulso/{station_id}/ranking', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':rankingAction');
            $app->get('/public/pulso/{station_id}/promocoes', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':promocoesAction');
            $app->get('/public/pulso/{station_id}/sorteios', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':sorteiosAction');
            $app->get('/public/pulso/{station_id}/relatorios/exportar-pdf', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':relatoriosExportarPdfAction');
            $app->get('/public/pulso/{station_id}/relatorios/exportar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':relatoriosExportarAction');
            $app->get('/public/pulso/{station_id}/relatorios', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':relatoriosAction');
            $app->get('/public/pulso/{station_id}/musicas', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':musicasAction');
            $app->get('/public/pulso/{station_id}/demograficos', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':demograficosAction');
            $app->get('/public/pulso/{station_id}/demograficos-pro', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':demograficosProAction');
            $app->get('/pulso/api/{station_id}/ouvintes', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':demograficosApiOuvintesAction');
            
            // OUVINTES - EDITAR/EXCLUIR
            $app->get('/public/pulso/{station_id}/ouvintes/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':ouvinteFormAction');
            $app->post('/public/pulso/{station_id}/ouvintes/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':ouvinteSaveAction');
            $app->post('/public/pulso/{station_id}/ouvintes/{id}/excluir', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':ouvinteDeleteAction');
            $app->get('/public/pulso/{station_id}/ouvintes/{id}/excluir', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':ouvinteDeleteAction');
            
            // PROMOÇÕES - CRUD COMPLETO
            $app->get('/public/pulso/{station_id}/promocoes/nova', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':promocaoFormAction');
            $app->post('/public/pulso/{station_id}/promocoes/nova', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':promocaoSaveAction');
            $app->get('/public/pulso/{station_id}/promocoes/{id}/participantes',  \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':promocaoParticipantesAction');
            $app->post('/public/pulso/{station_id}/promocoes/{id}/participantes/inscrever', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':inscreverOuvinteAction');
            $app->post('/public/pulso/{station_id}/promocoes/{id}/participantes/remover', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':removerParticipanteAction');
            $app->get('/public/pulso/{station_id}/promocoes/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':promocaoFormAction');
            $app->post('/public/pulso/{station_id}/promocoes/{id}/editar', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':promocaoSaveAction');
            $app->get('/public/pulso/{station_id}/promocoes/{id}/excluir', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':promocaoDeleteAction');
            
            // SORTEIOS - SORTEAR E RESULTADO
            $app->post('/public/pulso/{station_id}/sorteios/{id}/sortear', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':sortearAction');
            $app->get('/public/pulso/{station_id}/sorteios/{id}/resultado', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':resultadoSorteioAction');
            
            // ANTI-FRAUDE (IMPORTANTE!)
            $app->get('/public/pulso/{station_id}/antifraude', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':antiFraudeAction');
            $app->post('/public/pulso/{station_id}/antifraude/{id}/resolver', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':resolverAlertaAction');
            $app->post('/public/pulso/{station_id}/antifraude/desbloquear/{ouvinte_id}', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':desbloquearOuvinteAction');
            $app->post('/public/pulso/{station_id}/antifraude/bloquear/{ouvinte_id}', \Plugin\ProgramacaoPlugin\Controller\PulsoController::class . ':bloquearOuvinteAction');
        }
    );
};
