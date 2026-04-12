<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Http\Response;
use Plugin\ProgramacaoPlugin\BridgeClient;

class ApiController
{
    public function nowPlayingAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $data = BridgeClient::nowPlaying();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Cache-Control', 'no-cache');
    }

    public function statusAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $data = BridgeClient::systemStatus();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Cache-Control', 'no-cache');
    }

    public function historyAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $qp    = $request->getQueryParams();
        $limit = min((int)($qp['limit'] ?? 10), 50);
        $date  = $qp['date'] ?? date('Y-m-d');
        $source= $qp['source'] ?? 'auto';

        if ($source === 'azuracast') {
            $history = BridgeClient::azuracastHistory($limit);
        } else {
            $history = BridgeClient::myriadHistory($limit, $date);
        }

        $data = ['date' => $date, 'count' => count($history), 'history' => $history];
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
    public function syncPerformanceAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $date = $request->getQueryParams()['date'] ?? date('Y-m-d');
        $sync = new \Plugin\ProgramacaoPlugin\SyncService(1);
        $data = $sync->syncPerformance($date);
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json');
    }

    public function syncComercialAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $date = $request->getQueryParams()['date'] ?? date('Y-m-d');
        $sync = new \Plugin\ProgramacaoPlugin\SyncService(1);
        $data = $sync->syncProvaEmissao($date);
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json');
    }

    public function provaEmissaoAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $qp   = $request->getQueryParams();
        $sync = new \Plugin\ProgramacaoPlugin\SyncService(1);
        $data = $sync->getProvaEmissao(
            $qp['cliente'] ?? '',
            $qp['inicio']  ?? '',
            $qp['fim']     ?? ''
        );
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json');
    }

    public function topMusicasAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $qp   = $request->getQueryParams();
        $sync = new \Plugin\ProgramacaoPlugin\SyncService(1);
        $data = $sync->getTopMusicas(
            $qp['inicio'] ?? '',
            $qp['fim']    ?? '',
            (int)($qp['limit'] ?? 20)
        );
        $response->getBody()->write(json_encode(['count'=>count($data),'musicas'=>$data], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json');
    }

    public function intelligenceAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $qp   = $request->getQueryParams();
        $sync = new \Plugin\ProgramacaoPlugin\SyncService(1);
        $data = $sync->getIntelligenceProgramacao(
            $qp['inicio'] ?? '',
            $qp['fim']    ?? ''
        );
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json')
                        ->withHeader('Access-Control-Allow-Origin','*');
    }

    public function programaNoArAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $sync = new \Plugin\ProgramacaoPlugin\SyncService(1);
        $data = $sync->getProgramaNoAr();
        // Enriquecer com Signal Layer
        $np   = \Plugin\ProgramacaoPlugin\BridgeClient::nowPlaying();
        $data['now_playing'] = $np;
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json')
                        ->withHeader('Access-Control-Allow-Origin','*');
    }

    public function wpSyncAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $sync = new \Plugin\ProgramacaoPlugin\WordPressSync(1);
        $data = $sync->syncAll();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json');
    }

    public function relatorioSemanalAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $qp   = $request->getQueryParams();
        $rep  = new \Plugin\ProgramacaoPlugin\WeeklyReport(1);
        $data = $rep->generate($qp['inicio']??'', $qp['fim']??'');
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json');
    }

    public function relatorioSemanalHtmlAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $qp   = $request->getQueryParams();
        $rep  = new \Plugin\ProgramacaoPlugin\WeeklyReport(1);
        $data = $rep->generate($qp['inicio']??'', $qp['fim']??'');
        $html = $rep->generateHtml($data);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function mediaSyncAzAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $sync = new \Plugin\ProgramacaoPlugin\MediaSyncService(1);
        $data = $sync->syncFromAzuraCast();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json');
    }

    public function mediaDivAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $sync = new \Plugin\ProgramacaoPlugin\MediaSyncService(1);
        $data = $sync->getDivergencias();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json');
    }

    public function mediaAnaliseAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $sync = new \Plugin\ProgramacaoPlugin\MediaSyncService(1);
        $data = $sync->getAnalise();
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json');
    }

    public function mediaPesquisarAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $qp   = $request->getQueryParams();
        $sync = new \Plugin\ProgramacaoPlugin\MediaSyncService(1);
        $data = $sync->pesquisar($qp, (int)($qp['limit']??50));
        $response->getBody()->write(json_encode(['count'=>count($data),'musicas'=>$data], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type','application/json');
    }

}