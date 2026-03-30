<?php
namespace Plugin\ProgramacaoPlugin\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;

class FinanceController
{
    public function dashboardAction(Request $request, Response $response): ResponseInterface
    {
        $html = '<h1>FINANCE PRO FUNCIONANDO!</h1>';
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    public function apiDashboardAction(Request $request, Response $response, array $params): ResponseInterface
    {
        $data = ['status' => 'ok', 'message' => 'API Working'];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function clientesAction(Request $request, Response $response): ResponseInterface
    {
        $data = [];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
