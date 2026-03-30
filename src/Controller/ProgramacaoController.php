<?php

declare(strict_types=1);

namespace Plugin\ProgramacaoPlugin\Controller;

use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use Plugin\ProgramacaoPlugin\Service\ProgramacaoService;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer;

/**
 * Controller para páginas administrativas do plugin de Programação
 */
class ProgramacaoController
{
    private ProgramacaoService $service;

    public function __construct(ProgramacaoService $service)
    {
        $this->service = $service;
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    /**
     * Página principal do plugin (dashboard)
     */
    public function indexAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $stationId = $station->getId();
        
        // Buscar estatísticas
        $stats = $this->service->getEstatisticas($stationId);
        $programas = $this->service->getProgramas($stationId);
        $locutores = $this->service->getLocutores($stationId);
        $programaNoAr = $stats['programa_no_ar'];
        
        // Renderizar view
        $view = $request->getView();
        
        return $view->renderVuePage(
            response: $response,
            component: 'Vue_StationsReports',
            id: 'programacao-dashboard',
            title: 'Programação - ' . $station->getName(),
            props: [
                'station' => $station,
                'stats' => $stats,
                'programas' => $programas,
                'locutores' => $locutores,
                'programaNoAr' => $programaNoAr,
                'pluginView' => 'dashboard',
            ]
        );
    }

    // =========================================================================
    // PROGRAMAS
    // =========================================================================

    /**
     * Lista de programas
     */
    public function programasAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $programas = $this->service->getProgramas($station->getId());
        
        $view = $request->getView();
        
        return $view->renderVuePage(
            response: $response,
            component: 'Vue_StationsReports',
            id: 'programacao-programas',
            title: 'Programas - ' . $station->getName(),
            props: [
                'station' => $station,
                'programas' => $programas,
                'pluginView' => 'programas',
            ]
        );
    }

    /**
     * Formulário de programa (novo ou editar)
     */
    public function programaFormAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $id = $params['id'] ?? null;
        
        $programa = $id ? $this->service->getPrograma((int) $id) : null;
        $locutores = $this->service->getLocutores($station->getId());
        $locutoresDoPrograma = $id ? $this->service->getLocutoresDoPrograma((int) $id) : [];
        
        $view = $request->getView();
        
        return $view->renderVuePage(
            response: $response,
            component: 'Vue_StationsReports',
            id: 'programacao-programa-form',
            title: ($id ? 'Editar' : 'Novo') . ' Programa - ' . $station->getName(),
            props: [
                'station' => $station,
                'programa' => $programa,
                'locutores' => $locutores,
                'locutoresDoPrograma' => $locutoresDoPrograma,
                'pluginView' => 'programa-form',
            ]
        );
    }

    /**
     * Salvar programa
     */
    public function programaSaveAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $id = $params['id'] ?? null;
        $post = $request->getParsedBody();
        
        $data = [
            'station_id' => $station->getId(),
            'nome' => $post['nome'] ?? '',
            'descricao' => $post['descricao'] ?? '',
            'banner' => $post['banner'] ?? null,
            'hora_inicio' => $post['hora_inicio'] ?? '00:00:00',
            'hora_fim' => $post['hora_fim'] ?? '00:00:00',
            'dias_semana' => json_encode($post['dias_semana'] ?? []),
            'ativo' => isset($post['ativo']) ? 1 : 0,
        ];
        
        if ($id) {
            $data['id'] = (int) $id;
        }
        
        $programaId = $this->service->savePrograma($data);
        
        // Atualizar locutores vinculados
        if (isset($post['locutores']) && is_array($post['locutores'])) {
            // Remover todos os vínculos atuais
            foreach ($this->service->getLocutoresDoPrograma($programaId) as $loc) {
                $this->service->desvincularLocutorPrograma($programaId, (int) $loc['id']);
            }
            
            // Adicionar novos vínculos
            foreach ($post['locutores'] as $locutorId) {
                $isPrincipal = (isset($post['locutor_principal']) && (int) $post['locutor_principal'] === (int) $locutorId);
                $this->service->vincularLocutorPrograma($programaId, (int) $locutorId, $isPrincipal);
            }
        }
        
        // Redirecionar para lista
        $router = $request->getRouter();
        $redirectUrl = $router->named('programacao:programas', ['station_id' => $station->getId()]);
        
        return $response->withRedirect($redirectUrl);
    }

    /**
     * Excluir programa
     */
    public function programaDeleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $id = (int) ($params['id'] ?? 0);
        
        if ($id > 0) {
            $this->service->deletePrograma($id);
        }
        
        $router = $request->getRouter();
        $redirectUrl = $router->named('programacao:programas', ['station_id' => $station->getId()]);
        
        return $response->withRedirect($redirectUrl);
    }

    // =========================================================================
    // LOCUTORES
    // =========================================================================

    /**
     * Lista de locutores
     */
    public function locutoresAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $locutores = $this->service->getLocutores($station->getId());
        
        $view = $request->getView();
        
        return $view->renderVuePage(
            response: $response,
            component: 'Vue_StationsReports',
            id: 'programacao-locutores',
            title: 'Locutores - ' . $station->getName(),
            props: [
                'station' => $station,
                'locutores' => $locutores,
                'pluginView' => 'locutores',
            ]
        );
    }

    /**
     * Formulário de locutor (novo ou editar)
     */
    public function locutorFormAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $id = $params['id'] ?? null;
        
        $locutor = $id ? $this->service->getLocutor((int) $id) : null;
        
        $view = $request->getView();
        
        return $view->renderVuePage(
            response: $response,
            component: 'Vue_StationsReports',
            id: 'programacao-locutor-form',
            title: ($id ? 'Editar' : 'Novo') . ' Locutor - ' . $station->getName(),
            props: [
                'station' => $station,
                'locutor' => $locutor,
                'pluginView' => 'locutor-form',
            ]
        );
    }

    /**
     * Salvar locutor
     */
    public function locutorSaveAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $id = $params['id'] ?? null;
        $post = $request->getParsedBody();
        
        $data = [
            'station_id' => $station->getId(),
            'nome' => $post['nome'] ?? '',
            'bio' => $post['bio'] ?? '',
            'foto' => $post['foto'] ?? null,
            'email' => $post['email'] ?? null,
            'instagram' => $post['instagram'] ?? null,
            'twitter' => $post['twitter'] ?? null,
            'facebook' => $post['facebook'] ?? null,
            'ativo' => isset($post['ativo']) ? 1 : 0,
        ];
        
        if ($id) {
            $data['id'] = (int) $id;
        }
        
        $this->service->saveLocutor($data);
        
        $router = $request->getRouter();
        $redirectUrl = $router->named('programacao:locutores', ['station_id' => $station->getId()]);
        
        return $response->withRedirect($redirectUrl);
    }

    /**
     * Excluir locutor
     */
    public function locutorDeleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $id = (int) ($params['id'] ?? 0);
        
        if ($id > 0) {
            $this->service->deleteLocutor($id);
        }
        
        $router = $request->getRouter();
        $redirectUrl = $router->named('programacao:locutores', ['station_id' => $station->getId()]);
        
        return $response->withRedirect($redirectUrl);
    }

    // =========================================================================
    // GRADE SEMANAL
    // =========================================================================

    /**
     * Grade semanal
     */
    public function gradeAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $grade = $this->service->getGradeSemanal($station->getId());
        
        $view = $request->getView();
        
        return $view->renderVuePage(
            response: $response,
            component: 'Vue_StationsReports',
            id: 'programacao-grade',
            title: 'Grade Semanal - ' . $station->getName(),
            props: [
                'station' => $station,
                'grade' => $grade,
                'pluginView' => 'grade',
            ]
        );
    }

    // =========================================================================
    // CONFIGURAÇÕES
    // =========================================================================

    /**
     * Página de configurações
     */
    public function configAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $config = $this->service->getConfig($station->getId());
        
        $view = $request->getView();
        
        return $view->renderVuePage(
            response: $response,
            component: 'Vue_StationsReports',
            id: 'programacao-config',
            title: 'Configurações - ' . $station->getName(),
            props: [
                'station' => $station,
                'config' => $config,
                'pluginView' => 'config',
            ]
        );
    }

    /**
     * Salvar configurações
     */
    public function configSaveAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $post = $request->getParsedBody();
        
        $data = [
            'exibir_api_publica' => isset($post['exibir_api_publica']) ? 1 : 0,
            'exibir_locutor_metadata' => isset($post['exibir_locutor_metadata']) ? 1 : 0,
            'formato_metadata' => $post['formato_metadata'] ?? '{programa} - {locutor}',
            'programa_padrao_nome' => $post['programa_padrao_nome'] ?? 'Programação Musical',
            'programa_padrao_descricao' => $post['programa_padrao_descricao'] ?? '',
        ];
        
        $this->service->saveConfig($station->getId(), $data);
        
        $router = $request->getRouter();
        $redirectUrl = $router->named('programacao:config', ['station_id' => $station->getId()]);
        
        return $response->withRedirect($redirectUrl);
    }
}
