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
            $app->get('/public/dashboard/{station_id}', \Plugin\ProgramacaoPlugin\Controller\DashboardController::class . ':indexAction');
            // ─────────────────────────────────────────────────────
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
