<?php
declare(strict_types=1);

namespace Plugin\ProgramacaoPlugin\Service;

use Doctrine\DBAL\Connection;

class PulsoService
{
    private Connection $db;
    private string $timezone = 'Africa/Luanda';

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    // ==================== OUVINTES ====================

    public function getOuvintes(int $stationId, ?string $segmento = null, ?string $busca = null): array
    {
        $sql = "SELECT * FROM plugin_pulso_ouvintes WHERE station_id = :station_id AND ativo = 1";
        $params = ['station_id' => $stationId];

        if ($segmento) {
            $sql .= " AND segmento = :segmento";
            $params['segmento'] = $segmento;
        }

        if ($busca) {
            $sql .= " AND (nome LIKE :busca OR telefone LIKE :busca2 OR email LIKE :busca3)";
            $params['busca'] = "%{$busca}%";
            $params['busca2'] = "%{$busca}%";
            $params['busca3'] = "%{$busca}%";
        }

        $sql .= " ORDER BY ultima_actividade DESC, data_registo DESC";

        return $this->db->fetchAllAssociative($sql, $params);
    }

    public function getOuvinte(int $id): ?array
    {
        return $this->db->fetchAssociative(
            "SELECT * FROM plugin_pulso_ouvintes WHERE id = :id",
            ['id' => $id]
        ) ?: null;
    }

    public function getOuvintePorTelefone(string $telefone): ?array
    {
        return $this->db->fetchAssociative(
            "SELECT * FROM plugin_pulso_ouvintes WHERE telefone = :telefone",
            ['telefone' => $telefone]
        ) ?: null;
    }

    public function saveOuvinte(array $data): int
    {
        if (isset($data['id']) && $data['id']) {
            $id = (int) $data['id'];
            unset($data['id']);
            $this->db->update('plugin_pulso_ouvintes', $data, ['id' => $id]);
            return $id;
        } else {
            unset($data['id']);
            $this->db->insert('plugin_pulso_ouvintes', $data);
            return (int) $this->db->lastInsertId();
        }
    }

    public function deleteOuvinte(int $id): void
    {
        $this->db->update('plugin_pulso_ouvintes', ['ativo' => 0], ['id' => $id]);
    }

    public function actualizarActividade(int $ouvinteId): void
    {
        $this->db->update('plugin_pulso_ouvintes', [
            'ultima_actividade' => date('Y-m-d H:i:s')
        ], ['id' => $ouvinteId]);
    }

    public function actualizarSegmento(int $ouvinteId): void
    {
        $ouvinte = $this->getOuvinte($ouvinteId);
        if (!$ouvinte) return;

        $diasRegisto = (int) ((time() - strtotime($ouvinte['data_registo'])) / 86400);
        $participacoes = (int) $ouvinte['total_participacoes'];

        $segmento = 'novo';
        if ($diasRegisto > 180 && $participacoes >= 20) {
            $segmento = 'embaixador';
        } elseif ($diasRegisto > 60 && $participacoes >= 10) {
            $segmento = 'veterano';
        } elseif ($diasRegisto > 7 && $participacoes >= 2) {
            $segmento = 'regular';
        }

        // Verificar inactividade (só se já teve actividade)
        if (!empty($ouvinte['ultima_actividade'])) {
            $diasInactivo = (int) ((time() - strtotime($ouvinte['ultima_actividade'])) / 86400);
            if ($diasInactivo > 60 && $participacoes > 0) {
                $segmento = 'inactivo';
            }
        }

        $this->db->update('plugin_pulso_ouvintes', ['segmento' => $segmento], ['id' => $ouvinteId]);
    }

    // ==================== PARTICIPAÇÕES ====================

    public function registarParticipacao(int $ouvinteId, string $tipo, array $dados = []): int
    {
        $participacao = [
            'station_id' => $dados['station_id'] ?? 1,
            'ouvinte_id' => $ouvinteId,
            'tipo' => $tipo,
            'descricao' => $dados['descricao'] ?? null,
            'promocao_id' => $dados['promocao_id'] ?? null,
            'ganhou' => $dados['ganhou'] ?? 0,
            'premio' => $dados['premio'] ?? null,
            'pontos_ganhos' => $dados['pontos_ganhos'] ?? $this->calcularPontos($tipo),
            'lido_no_ar' => $dados['lido_no_ar'] ?? 0,
            'data_participacao' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('plugin_pulso_participacoes', $participacao);
        $participacaoId = (int) $this->db->lastInsertId();

        // Actualizar contadores do ouvinte
        $this->db->executeStatement(
            "UPDATE plugin_pulso_ouvintes SET 
                total_participacoes = total_participacoes + 1,
                pontos = pontos + :pontos,
                ultima_actividade = NOW()
             WHERE id = :id",
            ['pontos' => $participacao['pontos_ganhos'], 'id' => $ouvinteId]
        );

        // Actualizar segmento
        $this->actualizarSegmento($ouvinteId);

        return $participacaoId;
    }

    public function getParticipacoes(int $ouvinteId, int $limit = 20): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT * FROM plugin_pulso_participacoes WHERE ouvinte_id = :ouvinte_id ORDER BY data_participacao DESC LIMIT 50",
            ['ouvinte_id' => $ouvinteId]
        );
    }

    private function calcularPontos(string $tipo): int
    {
        $pontos = [
            'mensagem' => 5,
            'pedido_musica' => 10,
            'promocao' => 15,
            'votacao' => 5,
            'ligacao' => 20,
            'sms' => 10,
            'whatsapp' => 10,
            'app' => 5,
        ];
        return $pontos[$tipo] ?? 5;
    }

    // ==================== PROMOÇÕES ====================

    public function getPromocoes(int $stationId, ?string $estado = null): array
    {
        $sql = "SELECT * FROM plugin_pulso_promocoes WHERE station_id = :station_id";
        $params = ['station_id' => $stationId];

        if ($estado) {
            $sql .= " AND estado = :estado";
            $params['estado'] = $estado;
        }

        $sql .= " ORDER BY data_inicio DESC";

        return $this->db->fetchAllAssociative($sql, $params);
    }

    public function getPromocao(int $id): ?array
    {
        return $this->db->fetchAssociative(
            "SELECT * FROM plugin_pulso_promocoes WHERE id = :id",
            ['id' => $id]
        ) ?: null;
    }

    public function getPromocoesActivas(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT * FROM plugin_pulso_promocoes 
             WHERE station_id = :station_id 
             AND estado = 'activa' 
             AND NOW() BETWEEN data_inicio AND data_fim
             ORDER BY data_fim ASC",
            ['station_id' => $stationId]
        );
    }

    public function savePromocao(array $data): int
    {
        if (isset($data['id']) && $data['id']) {
            $id = (int) $data['id'];
            unset($data['id']);
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->update('plugin_pulso_promocoes', $data, ['id' => $id]);
            return $id;
        } else {
            unset($data['id']);
            $this->db->insert('plugin_pulso_promocoes', $data);
            return (int) $this->db->lastInsertId();
        }
    }

    public function deletePromocao(int $id): void
    {
        $this->db->delete('plugin_pulso_promocoes', ['id' => $id]);
    }

    // ==================== ESTATÍSTICAS ====================

    public function getEstatisticasGerais(int $stationId): array
    {
        $totalOuvintes = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes WHERE station_id = :station_id AND ativo = 1",
            ['station_id' => $stationId]
        );

        $novosEsteMes = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes 
             WHERE station_id = :station_id AND ativo = 1 
             AND MONTH(data_registo) = MONTH(NOW()) AND YEAR(data_registo) = YEAR(NOW())",
            ['station_id' => $stationId]
        );

        $participacoesHoje = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON p.ouvinte_id = o.id
             WHERE o.station_id = :station_id AND DATE(p.data_participacao) = CURDATE()",
            ['station_id' => $stationId]
        );

        $porSegmento = $this->db->fetchAllAssociative(
            "SELECT segmento, COUNT(*) as total FROM plugin_pulso_ouvintes 
             WHERE station_id = :station_id AND ativo = 1 
             GROUP BY segmento ORDER BY total DESC",
            ['station_id' => $stationId]
        );

        $topOuvintes = $this->db->fetchAllAssociative(
            "SELECT id, nome, pontos, total_participacoes, segmento 
             FROM plugin_pulso_ouvintes 
             WHERE station_id = :station_id AND ativo = 1 
             ORDER BY pontos DESC LIMIT 10",
            ['station_id' => $stationId]
        );

        $emRisco = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes 
             WHERE station_id = :station_id AND ativo = 1 
             AND (segmento = 'inactivo' OR risco_abandono = 'alto')",
            ['station_id' => $stationId]
        );

        return [
            'total_ouvintes' => $totalOuvintes,
            'novos_este_mes' => $novosEsteMes,
            'participacoes_hoje' => $participacoesHoje,
            'por_segmento' => $porSegmento,
            'top_ouvintes' => $topOuvintes,
            'em_risco' => $emRisco,
        ];
    }

    // ==================== FICHA DO OUVINTE ====================

    public function getFichaCompleta(int $ouvinteId): array
    {
        $ouvinte = $this->getOuvinte($ouvinteId);
        if (!$ouvinte) return [];

        $participacoes = $this->getParticipacoes($ouvinteId, 50);

        $ultimaVitoria = $this->db->fetchAssociative(
            "SELECT * FROM plugin_pulso_participacoes 
             WHERE ouvinte_id = :ouvinte_id AND ganhou = 1 
             ORDER BY data_participacao DESC LIMIT 1",
            ['ouvinte_id' => $ouvinteId]
        );

        $diasSemGanhar = 0;
        if ($ultimaVitoria) {
            $diasSemGanhar = (int) ((time() - strtotime($ultimaVitoria['data_participacao'])) / 86400);
        } else {
            $diasSemGanhar = (int) ((time() - strtotime($ouvinte['data_registo'])) / 86400);
        }

        // Parsear música e mensagem de cada participação
        $participacoes = array_map(function($p) {
            $desc = $p['descricao'] ?? '';
            $musica = '';
            $msg = '';
            if (preg_match('/Dedicatoria:\s*(.+?)\s*\[wp_post_id/', $desc, $m)) {
                $musica = trim($m[1]);
            }
            if (preg_match('/\|\s*Msg:\s*(.+)/s', $desc, $m2)) {
                $msg = trim($m2[1]);
            }
            if (empty($musica) && str_contains($desc, 'Música:')) {
                preg_match('/Música:\s*(.+?)(\||$)/s', $desc, $m3);
                $musica = trim($m3[1] ?? '');
            }
            $p['musica']   = $musica;
            $p['mensagem'] = $msg;
            return $p;
        }, $participacoes);

        // Géneros musicais
        $generosMusicais = [];
        if (!empty($ouvinte['generos_musicais'])) {
            $generosMusicais = json_decode($ouvinte['generos_musicais'], true) ?? [];
        }

        // Score de completude
        $scoreCompletude = (
            (!empty($ouvinte['provincia'])        ? 1 : 0) +
            (!empty($ouvinte['genero'])            ? 1 : 0) +
            (!empty($ouvinte['data_nascimento'])   ? 1 : 0) +
            (!empty($ouvinte['generos_musicais'])  ? 1 : 0) +
            (!empty($ouvinte['como_conheceu'])     ? 1 : 0)
        );

        return [
            'ouvinte'           => $ouvinte,
            'participacoes'     => $participacoes,
            'ultima_vitoria'    => $ultimaVitoria,
            'dias_sem_ganhar'   => $diasSemGanhar,
            'dias_como_ouvinte' => (int) ((time() - strtotime($ouvinte['data_registo'])) / 86400),
            'generos_musicais'  => $generosMusicais,
            'score_completude'  => $scoreCompletude,
            'badges'            => $this->getBadgesOuvinte($ouvinte['id']),
        ];
    }

    // ==================== WEBHOOK / INTEGRAÇÃO WORDPRESS ====================

    /**
     * Processa webhook do WordPress (Pro Radio Dedications)
     * Deduplicação: email > telefone > nome+IP > nome
     */
    public function processarDedicatoriaWebhook(array $dados, int $stationId = 1): array
    {
        $nome = trim($dados['nome'] ?? 'Ouvinte Anonimo');
        $email = !empty($dados['email']) ? strtolower(trim($dados['email'])) : null;
        $telefone = !empty($dados['telefone']) ? $this->normalizarTelefone($dados['telefone']) : null;
        $ip = $dados['ip'] ?? null;
        $musica = $dados['musica'] ?? null;
        $mensagem = $dados['mensagem'] ?? null;
        $wpPostId = $dados['wp_post_id'] ?? null;

        // Verificar duplicata por wp_post_id
        if ($wpPostId) {
            $existe = $this->db->fetchAssociative(
                "SELECT id FROM plugin_pulso_participacoes WHERE descricao LIKE :ref",
                ['ref' => "%wp_post_id:{$wpPostId}%"]
            );
            if ($existe) {
                return [
                    'status' => 'duplicado',
                    'mensagem' => "Dedicatoria WP #{$wpPostId} ja processada",
                    'participacao_id' => (int) $existe['id'],
                ];
            }
        }

        // Encontrar ou criar ouvinte
        $ouvinte = null;
        $isNovo = false;

        // 1. Por email
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $ouvinte = $this->db->fetchAssociative(
                "SELECT * FROM plugin_pulso_ouvintes WHERE email = :email AND station_id = :sid AND ativo = 1",
                ['email' => $email, 'sid' => $stationId]
            );
        }

        // 2. Por telefone
        if (!$ouvinte && $telefone) {
            $ouvinte = $this->db->fetchAssociative(
                "SELECT * FROM plugin_pulso_ouvintes WHERE telefone = :tel AND station_id = :sid AND ativo = 1",
                ['tel' => $telefone, 'sid' => $stationId]
            );
        }

        // 3. Por nome + IP
        if (!$ouvinte && $nome && $ip) {
            $ouvinte = $this->db->fetchAssociative(
                "SELECT * FROM plugin_pulso_ouvintes WHERE LOWER(nome) = LOWER(:nome) AND ultimo_ip = :ip AND station_id = :sid AND ativo = 1",
                ['nome' => $nome, 'ip' => $ip, 'sid' => $stationId]
            );
        }

        // 4. Por nome exacto
        if (!$ouvinte && $nome && $nome !== 'Ouvinte Anonimo') {
            $ouvinte = $this->db->fetchAssociative(
                "SELECT * FROM plugin_pulso_ouvintes WHERE LOWER(nome) = LOWER(:nome) AND station_id = :sid AND ativo = 1 LIMIT 1",
                ['nome' => $nome, 'sid' => $stationId]
            );
        }

        // 5. Criar novo
        if (!$ouvinte) {
            $isNovo = true;
            $novoId = $this->saveOuvinte([
                'station_id' => $stationId,
                'nome' => $nome,
                'email' => $email,
                'telefone' => $telefone,
                'cidade' => $dados['cidade'] ?? 'Luanda',
                'ultimo_ip' => $ip,
                'segmento' => 'novo',
            ]);
            $ouvinte = $this->getOuvinte($novoId);
        } else {
            // Actualizar dados que faltavam
            $updates = ['ultima_actividade' => date('Y-m-d H:i:s')];
            if ($ip) $updates['ultimo_ip'] = $ip;
            if ($email && empty($ouvinte['email'])) $updates['email'] = $email;
            if ($telefone && empty($ouvinte['telefone'])) $updates['telefone'] = $telefone;
            $this->db->update('plugin_pulso_ouvintes', $updates, ['id' => $ouvinte['id']]);
        }

        $ouvinteId = (int) $ouvinte['id'];

        // Registar participacao
        $descricao = $musica ? "Dedicatoria: {$musica}" : "Dedicatoria musical";
        if ($wpPostId) {
            $descricao .= " [wp_post_id:{$wpPostId}]";
        }
        if ($mensagem) {
            $descricao .= " | Msg: " . mb_substr($mensagem, 0, 200);
        }

        $participacaoId = $this->registarParticipacao($ouvinteId, 'pedido_musica', [
            'station_id' => $stationId,
            'descricao' => $descricao,
            'pontos_ganhos' => $isNovo ? 30 : 10,
        ]);

        return [
            'status' => 'ok',
            'ouvinte_id' => $ouvinteId,
            'is_novo' => $isNovo,
            'pontos_ganhos' => $isNovo ? 30 : 10,
            'participacao_id' => $participacaoId,
            'nome' => $ouvinte['nome'],
            'pontos_total' => (int) ($ouvinte['pontos'] ?? 0) + ($isNovo ? 30 : 10),
        ];
    }

    /**
     * Normalizar telefone angolano
     */
    private function normalizarTelefone(string $telefone): string
    {
        $tel = preg_replace('/[^\d+]/', '', $telefone);
        if (str_starts_with($tel, '00')) {
            $tel = '+' . substr($tel, 2);
        }
        if (preg_match('/^9\d{8}$/', $tel)) {
            $tel = '+244' . $tel;
        }
        return $tel;
    }

    /**
     * Estatisticas do webhook (para debug)
     */
    public function getWebhookStats(): array
    {
        $totalProcessadas = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes WHERE descricao LIKE '%wp_post_id:%'"
        );
        $ultimaProcessada = $this->db->fetchAssociative(
            "SELECT p.*, o.nome FROM plugin_pulso_participacoes p JOIN plugin_pulso_ouvintes o ON p.ouvinte_id = o.id WHERE p.descricao LIKE '%wp_post_id:%' ORDER BY p.data_participacao DESC LIMIT 1"
        );
        return [
            'total_processadas' => $totalProcessadas,
            'ultima' => $ultimaProcessada,
        ];
    }

    // ==================== RANKING ====================

    public function getRankingSemanal(int $stationId, string $filtro = 'semana'): array
    {
        switch ($filtro) {
            case 'semana_ant':
                $inicio = date('Y-m-d', strtotime('monday last week'));
                $fim    = date('Y-m-d', strtotime('sunday last week'));
                $label  = 'Semana Anterior';
                break;
            case 'mes':
                $inicio = date('Y-m-01');
                $fim    = date('Y-m-d');
                $label  = 'Este Mês';
                break;
            case 'tudo':
                $inicio = '2000-01-01';
                $fim    = date('Y-m-d');
                $label  = 'Todos os Tempos';
                break;
            default:
                $inicio = date('Y-m-d', strtotime('monday this week'));
                $fim    = date('Y-m-d');
                $label  = 'Esta Semana';
        }

        $ranking = $this->db->fetchAllAssociative(
            "SELECT o.id, o.nome, o.pontos, o.segmento,
                COALESCE(COUNT(p.id), 0) as participacoes_semana,
                COALESCE(SUM(CASE WHEN p.lido_no_ar = 1 THEN 1 ELSE 0 END), 0) as lidas_semana,
                COALESCE(SUM(CASE WHEN p.ganhou = 1 THEN 1 ELSE 0 END), 0) as vitorias_semana
             FROM plugin_pulso_ouvintes o
             LEFT JOIN plugin_pulso_participacoes p ON p.ouvinte_id = o.id
                 AND DATE(p.data_participacao) BETWEEN :inicio AND :fim
             WHERE o.station_id = :station_id AND o.ativo = 1
             GROUP BY o.id ORDER BY participacoes_semana DESC, o.pontos DESC
             LIMIT 20",
            ['station_id' => $stationId, 'inicio' => $inicio, 'fim' => $fim]
        );

        // Tendência
        $inicioAnt  = date('Y-m-d', strtotime($inicio . ' -7 days'));
        $fimAnt     = date('Y-m-d', strtotime($inicio . ' -1 day'));
        $rankingAnt = $this->db->fetchAllAssociative(
            "SELECT o.id, COALESCE(COUNT(p.id), 0) as participacoes_semana
             FROM plugin_pulso_ouvintes o
             LEFT JOIN plugin_pulso_participacoes p ON p.ouvinte_id = o.id
                 AND DATE(p.data_participacao) BETWEEN :inicio AND :fim
             WHERE o.station_id = :station_id AND o.ativo = 1
             GROUP BY o.id ORDER BY participacoes_semana DESC",
            ['station_id' => $stationId, 'inicio' => $inicioAnt, 'fim' => $fimAnt]
        );
        $posAnt = [];
        foreach ($rankingAnt as $pos => $r) {
            $posAnt[$r['id']] = $pos + 1;
        }
        foreach ($ranking as $pos => &$r) {
            $posAtual    = $pos + 1;
            $posAnterior = $posAnt[$r['id']] ?? null;
            if ($posAnterior === null)        { $r['tendencia'] = 'novo';   $r['tendencia_delta'] = 0; }
            elseif ($posAtual < $posAnterior) { $r['tendencia'] = 'subiu';  $r['tendencia_delta'] = $posAnterior - $posAtual; }
            elseif ($posAtual > $posAnterior) { $r['tendencia'] = 'desceu'; $r['tendencia_delta'] = $posAtual - $posAnterior; }
            else                              { $r['tendencia'] = 'igual';  $r['tendencia_delta'] = 0; }
        }
        unset($r);

        $totalOuvintes = (int)$this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes WHERE station_id = ? AND ativo = 1",
            [$stationId]
        );

        return [
            'ranking'        => $ranking,
            'inicio_semana'  => $inicio,
            'fim_semana'     => $fim,
            'periodo'        => date('d/m/Y', strtotime($inicio)) . ' - ' . date('d/m/Y', strtotime($fim)),
            'label'          => $label,
            'filtro'         => $filtro,
            'total_ouvintes' => $totalOuvintes,
            'total_part'     => array_sum(array_column($ranking, 'participacoes_semana')),
        ];
    }


    public function getMusicasMaisPedidas(int $stationId, string $periodo = 'hoje', int $limit = 20): array
    {
        $dataInicio = match($periodo) {
            'hoje' => date('Y-m-d 00:00:00'),
            'semana' => date('Y-m-d 00:00:00', strtotime('monday this week')),
            'mes' => date('Y-m-01 00:00:00'),
            default => '2000-01-01 00:00:00',
        };
        
        $sql = "SELECT 
                SUBSTRING_INDEX(SUBSTRING_INDEX(p.descricao, 'Dedicatoria: ', -1), ' [wp_post_id', 1) as musica_completa,
                SUBSTRING_INDEX(SUBSTRING_INDEX(p.descricao, 'Dedicatoria: ', -1), ' - ', 1) as artista,
                SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(p.descricao, 'Dedicatoria: ', -1), ' [wp_post_id', 1), ' - ', -1) as musica,
                COUNT(DISTINCT p.ouvinte_id) as ouvintes_unicos,
                COUNT(*) as pedidos_totais
            FROM plugin_pulso_participacoes p
            JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
            WHERE o.station_id = :sid
                AND p.tipo = 'pedido_musica'
                AND p.descricao LIKE 'Dedicatoria:%'
                AND p.data_participacao >= :data_inicio
            GROUP BY musica_completa, artista, musica
            HAVING LENGTH(artista) > 0 AND LENGTH(musica) > 0
            ORDER BY ouvintes_unicos DESC, pedidos_totais DESC
            LIMIT " . (int)$limit;
        
        $musicas = $this->db->fetchAllAssociative($sql, ['sid' => $stationId, 'data_inicio' => $dataInicio]);
        return ['musicas' => $musicas, 'periodo' => $periodo, 'total' => count($musicas)];
    }

    public function getArtistasMaisPedidos(int $stationId, string $periodo = 'semana', int $limit = 10): array
    {
        $dataInicio = match($periodo) {
            'hoje' => date('Y-m-d 00:00:00'),
            'semana' => date('Y-m-d 00:00:00', strtotime('monday this week')),
            'mes' => date('Y-m-01 00:00:00'),
            default => '2000-01-01 00:00:00',
        };
        
        $sql = "SELECT 
                SUBSTRING_INDEX(SUBSTRING_INDEX(p.descricao, 'Dedicatoria: ', -1), ' - ', 1) as artista,
                COUNT(DISTINCT p.ouvinte_id) as ouvintes_unicos,
                COUNT(*) as pedidos_totais
            FROM plugin_pulso_participacoes p
            JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
            WHERE o.station_id = :sid
                AND p.tipo = 'pedido_musica'
                AND p.descricao LIKE 'Dedicatoria:%'
                AND p.data_participacao >= :data_inicio
            GROUP BY artista
            HAVING LENGTH(artista) > 2
            ORDER BY ouvintes_unicos DESC
            LIMIT " . (int)$limit;
        
        return $this->db->fetchAllAssociative($sql, ['sid' => $stationId, 'data_inicio' => $dataInicio]);
    }

    public function getDistribuicaoGeografica(int $stationId): array
    {
        $cidades = $this->db->fetchAllAssociative(
            "SELECT cidade, COUNT(*) as total,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM plugin_pulso_ouvintes WHERE station_id = :sid AND ativo=1), 1) as percentagem
             FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND ativo = 1 AND cidade IS NOT NULL AND cidade != ''
             GROUP BY cidade ORDER BY total DESC LIMIT 10",
            ['sid' => $stationId]
        );
        
        $bairros = $this->db->fetchAllAssociative(
            "SELECT bairro, COUNT(*) as total
             FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND ativo = 1 AND bairro IS NOT NULL
             GROUP BY bairro ORDER BY total DESC LIMIT 15",
            ['sid' => $stationId]
        );
        
        $stats = $this->db->fetchAssociative(
            "SELECT COUNT(*) as total,
                    COUNT(DISTINCT cidade) as cidades_diferentes,
                    COUNT(DISTINCT bairro) as bairros_diferentes
             FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND ativo = 1",
            ['sid' => $stationId]
        );
        
        return ['cidades' => $cidades, 'bairros' => $bairros, 'stats' => $stats];
    }

    public function getBadgesOuvinte(int $ouvinteId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT * FROM plugin_pulso_badges WHERE ouvinte_id = :oid ORDER BY data_conquista DESC",
            ['oid' => $ouvinteId]
        );
    }

    public function getEstatisticasBadges(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT codigo, COUNT(*) as total FROM plugin_pulso_badges b
             JOIN plugin_pulso_ouvintes o ON o.id = b.ouvinte_id
             WHERE o.station_id = :sid
             GROUP BY codigo ORDER BY total DESC",
            ['sid' => $stationId]
        );
    }

    public function verificarBadges(int $ouvinteId): void
    {
        $ouvinte = $this->getOuvinte($ouvinteId);
        if (!$ouvinte) return;
        
        // Badge Estreia
        if ($ouvinte['total_participacoes'] == 1) {
            $this->atribuirBadge($ouvinteId, 'estreia', 'Estreia', 'Primeira participação', '⭐');
        }
        
        // Badge Veterano
        $diasCadastro = (time() - strtotime($ouvinte['data_registo'])) / 86400;
        if ($diasCadastro >= 365) {
            $this->atribuirBadge($ouvinteId, 'veterano', 'Veterano', 'Mais de 1 ano', '🏆');
        }
        
        // Badge Fiel
        if ($ouvinte['total_participacoes'] >= 100) {
            $this->atribuirBadge($ouvinteId, 'fiel', 'Fiel', '100+ participações', '💎');
        }
        
        // Badge Sortudo
        if ($ouvinte['total_vitorias'] >= 3) {
            $this->atribuirBadge($ouvinteId, 'sortudo', 'Sortudo', '3+ vitórias', '🎯');
        }
    }

    private function atribuirBadge(int $ouvinteId, string $codigo, string $nome, string $descricao, string $icone): void
    {
        try {
            $this->db->insert('plugin_pulso_badges', [
                'ouvinte_id' => $ouvinteId,
                'codigo' => $codigo,
                'nome' => $nome,
                'descricao' => $descricao,
                'icone' => $icone,
                'data_conquista' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Badge já existe, ignorar
        }
    }

    public function getAnalisePorPais(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT pais, COUNT(*) as total,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM plugin_pulso_ouvintes WHERE station_id = :sid AND ativo = 1), 1) as percentagem
             FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND ativo = 1 AND pais IS NOT NULL AND pais != ''
             GROUP BY pais ORDER BY total DESC",
            ['sid' => $stationId]
        );
    }

    public function getAnalisePorIdade(int $stationId): array
    {
        $result = $this->db->fetchAllAssociative(
            "SELECT 
                CASE
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                    WHEN TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) BETWEEN 46 AND 55 THEN '46-55'
                    ELSE '56+'
                END as faixa_etaria, COUNT(*) as total
             FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND ativo = 1 AND data_nascimento IS NOT NULL
             GROUP BY faixa_etaria",
            ['sid' => $stationId]
        );
        
        $data = ['18-25' => 0, '26-35' => 0, '36-45' => 0, '46-55' => 0, '56+' => 0];
        foreach ($result as $row) $data[$row['faixa_etaria']] = (int) $row['total'];
        return $data;
    }

    public function getAnalisePorGenero(int $stationId): array
    {
        $result = $this->db->fetchAllAssociative(
            "SELECT genero, COUNT(*) as total FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND ativo = 1 AND genero IS NOT NULL GROUP BY genero",
            ['sid' => $stationId]
        );
        
        $data = [];
        foreach ($result as $row) $data[$row['genero']] = (int) $row['total'];
        return $data;
    }

    public function getAnaliseGenerosMusicais(int $stationId): array
    {
        $result = $this->db->fetchAllAssociative(
            "SELECT generos_musicais FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND ativo = 1 AND generos_musicais IS NOT NULL",
            ['sid' => $stationId]
        );
        
        $contagem = [];
        foreach ($result as $row) {
            $generos = json_decode($row['generos_musicais'], true);
            if (is_array($generos)) {
                foreach ($generos as $g) $contagem[$g] = ($contagem[$g] ?? 0) + 1;
            }
        }
        arsort($contagem);
        return array_slice($contagem, 0, 15, true);
    }

    public function getCrescimentoTemporal(int $stationId, int $dias = 90): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT DATE(data_registo) as data, COUNT(*) as novos
             FROM plugin_pulso_ouvintes WHERE station_id = :sid 
             AND data_registo >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
             GROUP BY DATE(data_registo) ORDER BY data",
            ['sid' => $stationId, 'dias' => $dias]
        );
    }

    public function getOuvintesComFiltros(int $stationId, array $filtros): array
    {
        $where = ['o.station_id = :sid', 'o.ativo = 1'];
        $params = ['sid' => $stationId];
        
        if (!empty($filtros['pais'])) {
            $where[] = 'o.pais = :pais';
            $params['pais'] = $filtros['pais'];
        }
        
        if (!empty($filtros['provincia'])) {
            $where[] = 'o.provincia = :provincia';
            $params['provincia'] = $filtros['provincia'];
        }
        
        $sql = "SELECT o.* FROM plugin_pulso_ouvintes o WHERE " . implode(' AND ', $where) . " ORDER BY o.nome LIMIT 100";
        return $this->db->fetchAllAssociative($sql, $params);
    }

    // ==================== DEMOGRÁFICOS 2.0 - ANÁLISES AVANÇADAS ====================
    
    // KPIs Executivos
    public function getKPIsExecutivos(int $stationId): array
    {
        $hoje = date('Y-m-d');
        $ontem = date('Y-m-d', strtotime('-1 day'));
        $semanaPassada = date('Y-m-d', strtotime('-7 days'));
        $mesPassado = date('Y-m-d', strtotime('-30 days'));
        
        // Ouvintes activos hoje
        $activosHoje = $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes WHERE DATE(data_participacao) = :hoje",
            ['hoje' => $hoje]
        );
        
        // Comparação com ontem
        $activosOntem = $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes WHERE DATE(data_participacao) = :ontem",
            ['ontem' => $ontem]
        );
        
        // Novos últimos 7 dias
        $novos7d = $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes WHERE station_id = :sid AND data_registo >= :data",
            ['sid' => $stationId, 'data' => $semanaPassada]
        );
        
        // Taxa de crescimento
        $totalMesPassado = $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes WHERE station_id = :sid AND data_registo < :data",
            ['sid' => $stationId, 'data' => $mesPassado]
        );
        $totalAgora = $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes WHERE station_id = :sid AND ativo = 1",
            ['sid' => $stationId]
        );
        
        $taxaCrescimento = $totalMesPassado > 0 ? round((($totalAgora - $totalMesPassado) / $totalMesPassado) * 100, 1) : 0;
        
        // Taxa de engagement (participações / total ouvintes)
        $totalParticipacoes = $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes p 
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id 
             WHERE o.station_id = :sid",
            ['sid' => $stationId]
        );
        $taxaEngagement = $totalAgora > 0 ? round(($totalParticipacoes / $totalAgora), 2) : 0;
        
        // Valor estimado da audiência (€1 por ouvinte activo/mês - métrica standard)
        $valorAudiencia = $totalAgora * 1;
        
        return [
            'activos_hoje' => (int)$activosHoje,
            'activos_ontem' => (int)$activosOntem,
            'variacao_diaria' => $activosOntem > 0 ? round((($activosHoje - $activosOntem) / $activosOntem) * 100, 1) : 0,
            'novos_7d' => (int)$novos7d,
            'taxa_crescimento_mes' => $taxaCrescimento,
            'taxa_engagement' => $taxaEngagement,
            'valor_audiencia' => $valorAudiencia,
            'total_ouvintes' => (int)$totalAgora,
        ];
    }
    
    // Análise temporal detalhada (por hora do dia)
    public function getAnaliseHoraria(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT HOUR(data_participacao) as hora, COUNT(*) as total
             FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = :sid AND data_participacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY HOUR(data_participacao)
             ORDER BY hora",
            ['sid' => $stationId]
        );
    }
    
    // Análise de retenção (quantos voltam)
    public function getAnaliseRetencao(int $stationId): array
    {
        // Ouvintes que participaram mais de uma vez
        $retidos = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT ouvinte_id) FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = :sid
             GROUP BY ouvinte_id HAVING COUNT(*) > 1",
            ['sid' => $stationId]
        );
        
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes WHERE station_id = :sid",
            ['sid' => $stationId]
        );
        
        return [
            'ouvintes_retidos' => (int)$retidos,
            'taxa_retencao' => $total > 0 ? round(($retidos / $total) * 100, 1) : 0,
        ];
    }
    
    // Top municípios (não só cidades)
    public function getTopMunicipios(int $stationId, int $limit = 15): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT municipio, COUNT(*) as total,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM plugin_pulso_ouvintes WHERE station_id = :sid AND ativo = 1), 1) as percentagem
             FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND ativo = 1 AND municipio IS NOT NULL AND municipio != ''
             GROUP BY municipio
             ORDER BY total DESC
             LIMIT " . (int)$limit,
            ['sid' => $stationId]
        );
    }
    
    // Comparação entre períodos
    public function getComparacaoPeriodos(int $stationId, string $periodoA, string $periodoB): array
    {
        // Período A
        $dadosA = $this->db->fetchAllAssociative(
            "SELECT DATE(data_registo) as data, COUNT(*) as novos
             FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND data_registo BETWEEN :inicio AND :fim
             GROUP BY DATE(data_registo)",
            ['sid' => $stationId, 'inicio' => $periodoA . '-01', 'fim' => $periodoA . '-31']
        );
        
        // Período B
        $dadosB = $this->db->fetchAllAssociative(
            "SELECT DATE(data_registo) as data, COUNT(*) as novos
             FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND data_registo BETWEEN :inicio AND :fim
             GROUP BY DATE(data_registo)",
            ['sid' => $stationId, 'inicio' => $periodoB . '-01', 'fim' => $periodoB . '-31']
        );
        
        return [
            'periodo_a' => $dadosA,
            'periodo_b' => $dadosB,
            'total_a' => array_sum(array_column($dadosA, 'novos')),
            'total_b' => array_sum(array_column($dadosB, 'novos')),
        ];
    }
    
    // Previsão próximo mês (média móvel simples)
    public function getPrevisaoCrescimento(int $stationId): array
    {
        $ultimos30 = $this->db->fetchAllAssociative(
            "SELECT DATE(data_registo) as data, COUNT(*) as novos
             FROM plugin_pulso_ouvintes
             WHERE station_id = :sid AND data_registo >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY DATE(data_registo)",
            ['sid' => $stationId]
        );
        
        $mediaDiaria = count($ultimos30) > 0 ? array_sum(array_column($ultimos30, 'novos')) / count($ultimos30) : 0;
        $previsao30dias = round($mediaDiaria * 30);
        
        return [
            'media_diaria' => round($mediaDiaria, 1),
            'previsao_proximo_mes' => $previsao30dias,
            'confianca' => count($ultimos30) >= 20 ? 'Alta' : 'Média',
        ];
    }
    
    // Análise de correlação (quem gosta de X também gosta de Y)
    public function getCorrelacoesGeneros(int $stationId): array
    {
        $ouvintes = $this->db->fetchAllAssociative(
            "SELECT generos_musicais FROM plugin_pulso_ouvintes 
             WHERE station_id = :sid AND generos_musicais IS NOT NULL",
            ['sid' => $stationId]
        );
        
        $correlacoes = [];
        foreach ($ouvintes as $o) {
            $generos = json_decode($o['generos_musicais'], true);
            if (is_array($generos) && count($generos) >= 2) {
                foreach ($generos as $g1) {
                    foreach ($generos as $g2) {
                        if ($g1 !== $g2) {
                            $key = $g1 . ' + ' . $g2;
                            $correlacoes[$key] = ($correlacoes[$key] ?? 0) + 1;
                        }
                    }
                }
            }
        }
        
        arsort($correlacoes);
        return array_slice($correlacoes, 0, 10, true);
    }
    
    // Insights automáticos com IA simples
    public function getInsightsAutomaticos(int $stationId): array
    {
        $insights = [];
        
        // Insight 1: Género musical em alta
        $generos = $this->getAnaliseGenerosMusicais($stationId);
        if (!empty($generos)) {
            $topGenero = array_key_first($generos);
            $insights[] = [
                'tipo' => 'tendencia',
                'icone' => '🎵',
                'titulo' => ucfirst($topGenero) . ' domina preferências',
                'descricao' => $generos[$topGenero] . ' ouvintes escolheram este género.',
                'accao' => 'Criar playlist especial de ' . $topGenero,
            ];
        }
        
        // Insight 2: Crescimento geográfico
        $cidades = $this->getDistribuicaoGeografica($stationId);
        if (!empty($cidades['cidades']) && $cidades['cidades'][0]['total'] > 5) {
            $insights[] = [
                'tipo' => 'geografia',
                'icone' => '📍',
                'titulo' => 'Forte presença em ' . $cidades['cidades'][0]['cidade'],
                'descricao' => $cidades['cidades'][0]['percentagem'] . '% da audiência concentrada nesta cidade.',
                'accao' => 'Criar campanha local direcionada',
            ];
        }
        
        // Insight 3: Horário de pico
        $horaria = $this->getAnaliseHoraria($stationId);
        if (!empty($horaria)) {
            $horarioPico = array_reduce($horaria, fn($carry, $item) => $item['total'] > ($carry['total'] ?? 0) ? $item : $carry, []);
            if (!empty($horarioPico)) {
                $insights[] = [
                    'tipo' => 'horario',
                    'icone' => '⏰',
                    'titulo' => 'Pico de audiência às ' . $horarioPico['hora'] . 'h',
                    'descricao' => 'Melhor horário para lançar promoções importantes.',
                    'accao' => 'Agendar conteúdo premium para este horário',
                ];
            }
        }
        
        return $insights;
    }

    public function getParticipacoesRecentes(int $limit = 30): array
    {
        $qb = $this->db->createQueryBuilder();
        
        return $qb->select('p.*', 'o.nome', 'o.telefone')
            ->from('plugin_pulso_participacoes', 'p')
            ->leftJoin('p', 'plugin_pulso_ouvintes', 'o', 'o.id = p.ouvinte_id')
            ->orderBy('p.data_participacao', 'DESC')
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();
    }
    
    public function getParticipacoesDia(string $data): array
    {
        $sql = "SELECT p.*, o.nome
                FROM plugin_pulso_participacoes p
                LEFT JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
                WHERE DATE(p.data_participacao) = :data
                ORDER BY p.data_participacao DESC";
        
        return $this->db->fetchAllAssociative($sql, ['data' => $data]);
    }


    public function getDadosEnriquecimento(int $stationId): array
    {
        $ouvintes = $this->db->fetchAllAssociative("
            SELECT *,
            (
                CASE WHEN provincia IS NOT NULL AND provincia != '' THEN 1 ELSE 0 END +
                CASE WHEN genero IS NOT NULL AND genero != '' THEN 1 ELSE 0 END +
                CASE WHEN data_nascimento IS NOT NULL THEN 1 ELSE 0 END +
                CASE WHEN generos_musicais IS NOT NULL AND generos_musicais != '' THEN 1 ELSE 0 END +
                CASE WHEN como_conheceu IS NOT NULL AND como_conheceu != '' THEN 1 ELSE 0 END
            ) as score
            FROM plugin_pulso_ouvintes
            WHERE station_id = ? AND ativo = 1 AND nome IS NOT NULL AND nome != ''
            ORDER BY score ASC, data_registo DESC
        ", [$stationId]);

        $totalFantasmas = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1 AND (nome IS NULL OR nome = '')",
            [$stationId]
        );

        $completos   = array_filter($ouvintes, fn($o) => $o['score'] == 5);
        $incompletos = array_filter($ouvintes, fn($o) => $o['score'] < 5);

        return [
            'ouvintes'    => $ouvintes,
            'completos'   => array_values($completos),
            'incompletos' => array_values($incompletos),
            'fantasmas'   => $totalFantasmas,
            'stats' => [
                'completos'   => count($completos),
                'incompletos' => count($incompletos),
                'fantasmas'   => $totalFantasmas,
            ],
        ];
    }

    public function arquivarFantasmas(int $stationId): int
    {
        return (int) $this->db->executeStatement(
            "UPDATE plugin_pulso_ouvintes SET ativo = 0
             WHERE station_id = ? AND (nome IS NULL OR nome = '')",
            [$stationId]
        );
    }


    public function getDadosDemograficosPro(int $stationId): array
    {
        return [
            'distribuicao'      => $this->getDistribuicaoGeografica($stationId),
            'paises'            => $this->getAnalisePorPais($stationId),
            'idades'            => $this->getAnalisePorIdade($stationId),
            'generos'           => $this->getAnalisePorGenero($stationId),
            'generosMusicais'   => $this->getAnaliseGenerosMusicais($stationId),
            'crescimento'       => $this->getCrescimentoTemporal($stationId, 90),
            'kpis'              => $this->getKPIsExecutivos($stationId),
            'horaria'           => $this->getAnaliseHoraria($stationId),
            'retencao'          => $this->getAnaliseRetencao($stationId),
            'municipios'        => $this->getTopMunicipios($stationId),
            'insights'          => $this->getInsightsAutomaticos($stationId),
            'previsao'          => $this->getPrevisaoCrescimento($stationId),
            'programas'         => $this->getProgramasFavoritos($stationId),
            'locutores'         => $this->getLocutoresFavoritos($stationId),
            'origens'           => $this->getOrigensConhecimento($stationId),
            'horarios_preferidos' => $this->getHorariosPreferidos($stationId),
        ];
    }

    public function getProgramasFavoritos(int $stationId, int $limit = 10): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT programa_favorito, COUNT(*) n FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND programa_favorito IS NOT NULL AND programa_favorito != '' AND ativo = 1
             GROUP BY programa_favorito ORDER BY n DESC LIMIT " . (int)$limit,
            [$stationId]
        );
    }

    public function getLocutoresFavoritos(int $stationId, int $limit = 10): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT locutor_favorito, COUNT(*) n FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND locutor_favorito IS NOT NULL AND locutor_favorito != '' AND ativo = 1
             GROUP BY locutor_favorito ORDER BY n DESC LIMIT " . (int)$limit,
            [$stationId]
        );
    }

    public function getOrigensConhecimento(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT como_conheceu, COUNT(*) n FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND como_conheceu IS NOT NULL AND como_conheceu != '' AND ativo = 1
             GROUP BY como_conheceu ORDER BY n DESC",
            [$stationId]
        );
    }

    public function getDadosDashboard(int $stationId): array
    {
        $kpis = $this->getKPIsExecutivos($stationId);

        $topOuvintes = $this->db->fetchAllAssociative(
            "SELECT id, nome, pontos, total_participacoes, segmento, nivel
             FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1
             ORDER BY pontos DESC LIMIT 5",
            [$stationId]
        );

        $porSegmento = $this->db->fetchAllAssociative(
            "SELECT segmento, COUNT(*) as total FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1
             GROUP BY segmento ORDER BY total DESC",
            [$stationId]
        );

        $participacoesHoje = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND DATE(p.data_participacao) = CURDATE()",
            [$stationId]
        );

        // Actividade recente com parsing de música e mensagem
        $recentesRaw = $this->db->fetchAllAssociative(
            "SELECT p.id, p.tipo, p.descricao, p.lido_no_ar, p.ganhou, p.data_participacao,
                    o.nome, o.id as ouvinte_id
             FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ?
             ORDER BY p.data_participacao DESC LIMIT 10",
            [$stationId]
        );

        $recentes = array_map(function($r) {
            $desc = $r['descricao'] ?? '';
            $musica = '';
            $msg = '';
            if (preg_match('/Dedicatoria:\s*(.+?)\s*\[wp_post_id/', $desc, $m)) {
                $musica = $m[1];
            }
            if (preg_match('/\|\s*Msg:\s*(.+)/s', $desc, $m2)) {
                $msg = trim($m2[1]);
            }
            // Pedido simples sem formato dedicatória
            if (empty($musica) && str_contains($desc, 'Música:')) {
                preg_match('/Música:\s*(.+?)(\||$)/s', $desc, $m3);
                $musica = trim($m3[1] ?? '');
            }
            $r['musica'] = $musica;
            $r['mensagem'] = $msg;
            return $r;
        }, $recentesRaw);

        // Crescimento 30 dias
        $crescimento30d = $this->db->fetchAllAssociative(
            "SELECT DATE(data_registo) as data, COUNT(*) as novos
             FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND data_registo >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY DATE(data_registo) ORDER BY data",
            [$stationId]
        );

        // Pulse Score real
        $pedidosUltimos7d = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND p.data_participacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            [$stationId]
        );
        $lidosNoAr = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND p.lido_no_ar = 1
             AND p.data_participacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            [$stationId]
        );
        $promocoesActivas = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_promocoes
             WHERE station_id = ? AND estado = 'ativa'",
            [$stationId]
        );
        $novos30d = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND data_registo >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            [$stationId]
        );

        // Fórmula: pedidos(40%) + lidos no ar(25%) + promoções activas(20%) + crescimento(15%)
        $scorePedidos   = min(40, $pedidosUltimos7d * 4);
        $scoreLidos     = min(25, $lidosNoAr * 5);
        $scorePromocoes = min(20, $promocoesActivas * 10);
        $scoreCrescimento = min(15, $novos30d * 3);
        $pulseScore = $scorePedidos + $scoreLidos + $scorePromocoes + $scoreCrescimento;

        $emRisco = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1
             AND (segmento = 'inactivo' OR risco_abandono = 'alto')",
            [$stationId]
        );

        $insights = $this->getInsightsAutomaticos($stationId);

        return [
            'kpis'               => $kpis,
            'top_ouvintes'       => $topOuvintes,
            'por_segmento'       => $porSegmento,
            'participacoes_hoje' => $participacoesHoje,
            'recentes'           => $recentes,
            'crescimento_30d'    => $crescimento30d,
            'em_risco'           => $emRisco,
            'insights'           => $insights,
            'pulse'              => [
                'score'            => $pulseScore,
                'pedidos_7d'       => $pedidosUltimos7d,
                'lidos_no_ar_30d'  => $lidosNoAr,
                'promocoes_ativas' => $promocoesActivas,
                'novos_30d'        => $novos30d,
            ],
        ];
    }

    public function getHorariosPreferidos(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT horario_preferido as horario, COUNT(*) as total
             FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1
             AND horario_preferido IS NOT NULL AND horario_preferido != ''
             GROUP BY horario_preferido ORDER BY total DESC",
            [$stationId]
        );
    }

    public function getParticipantesPromocao(int $promocaoId): array
    {
        // Buscar participantes a partir dos sorteios registados
        return $this->db->fetchAllAssociative(
            "SELECT s.id, s.data_sorteio as data_participacao,
                    CASE WHEN s.resultado = 'vencedor' THEN 1 ELSE 0 END as ganhou,
                    0 as lido_no_ar,
                    s.probabilidade, s.pontos_na_hora, s.resultado,
                    o.id as ouvinte_id, o.nome, o.telefone, o.segmento, o.pontos,
                    o.provincia, o.pais
             FROM plugin_pulso_sorteios s
             JOIN plugin_pulso_ouvintes o ON o.id = s.ouvinte_id
             WHERE s.promocao_id = ?
             ORDER BY s.resultado DESC, s.data_sorteio DESC",
            [$promocaoId]
        );
    }

    public function getEstatisticasPromocao(int $promocaoId): array
    {
        $stats = $this->db->fetchAssociative(
            "SELECT
                COUNT(*) as total_participacoes,
                COUNT(DISTINCT ouvinte_id) as total_participantes,
                SUM(CASE WHEN resultado = 'vencedor' THEN 1 ELSE 0 END) as total_vencedores,
                0 as total_lidos
             FROM plugin_pulso_sorteios
             WHERE promocao_id = ?",
            [$promocaoId]
        );
        return $stats ?: ['total_participacoes'=>0,'total_participantes'=>0,'total_vencedores'=>0,'total_lidos'=>0];
    }

    public function getDadosSorteios(int $stationId): array
    {
        $promocoes = $this->getPromocoes($stationId, 'activa');

        $historico = $this->db->fetchAllAssociative(
            "SELECT s.*, o.nome, o.telefone, p.nome as promocao_nome, p.premio
             FROM plugin_pulso_sorteios s
             JOIN plugin_pulso_ouvintes o ON o.id = s.ouvinte_id
             JOIN plugin_pulso_promocoes p ON p.id = s.promocao_id
             WHERE p.station_id = ? AND s.resultado = 'vencedor'
             ORDER BY s.data_sorteio DESC LIMIT 20",
            [$stationId]
        );

        return [
            'promocoes_activas' => $promocoes,
            'historico'         => $historico,
        ];
    }

    public function realizarSorteio(int $stationId, int $promocaoId, int $numVencedores = 1): array
    {
        $promocao = $this->getPromocao($promocaoId);
        if (!$promocao) return ['erro' => 'Promoção não encontrada'];

        // Buscar inscritos na promoção (inscrição manual)
        $inscritos = $this->db->fetchAllAssociative(
            "SELECT DISTINCT o.id, o.nome, o.telefone, o.pontos, o.total_vitorias,
                    o.dias_sem_ganhar, o.data_registo, o.segmento,
                    COUNT(p.id) as total_participacoes_promo
             FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE p.promocao_id = ? AND p.tipo = 'promocao'
             AND o.ativo = 1 AND o.bloqueado = 0
             GROUP BY o.id",
            [$promocaoId]
        );

        // Se há inscritos manuais usar apenas esses, senão usar todos os ouvintes activos
        if (!empty($inscritos)) {
            $candidatos = $inscritos;
        } else {
            $candidatos = $this->db->fetchAllAssociative(
                "SELECT id, nome, telefone, pontos, total_vitorias, dias_sem_ganhar,
                        data_registo, segmento, 0 as total_participacoes_promo
                 FROM plugin_pulso_ouvintes
                 WHERE station_id = ? AND ativo = 1 AND bloqueado = 0",
                [$stationId]
            );
        }

        if (empty($candidatos)) return ['erro' => 'Nenhum candidato elegível'];

        // Calcular peso de cada candidato
        // Fórmula: pontos(40%) + dias_sem_ganhar(40%) + participacoes(20%)
        $maxPontos = max(array_column($candidatos, 'pontos') ?: [1]);
        $maxDias   = max(array_column($candidatos, 'dias_sem_ganhar') ?: [1]);
        $maxPart   = max(array_column($candidatos, 'total_participacoes_promo') ?: [1]);

        $totalPeso = 0;
        foreach ($candidatos as &$c) {
            $pesoPontos = $maxPontos > 0 ? ($c['pontos'] / $maxPontos) * 40 : 0;
            $pesoDias   = $maxDias   > 0 ? ($c['dias_sem_ganhar'] / $maxDias) * 40 : 0;
            $pesoPart   = $maxPart   > 0 ? ($c['total_participacoes_promo'] / $maxPart) * 20 : 0;
            $c['peso']  = round($pesoPontos + $pesoDias + $pesoPart + 1, 2); // +1 para todos terem chance
            $totalPeso += $c['peso'];
        }
        unset($c);

        // Calcular probabilidade
        foreach ($candidatos as &$c) {
            $c['probabilidade'] = $totalPeso > 0 ? round($c['peso'] / $totalPeso * 100, 1) : 0;
        }
        unset($c);

        // Seleccionar vencedores por peso
        $vencedores = [];
        $pool = $candidatos;
        $numVencedores = min($numVencedores, count($pool), $promocao['max_vencedores'] ?? 1);

        for ($i = 0; $i < $numVencedores; $i++) {
            if (empty($pool)) break;

            $rand = mt_rand(1, (int)($totalPeso * 100)) / 100;
            $acumulado = 0;
            $vencedor = end($pool);

            foreach ($pool as $c) {
                $acumulado += $c['peso'];
                if ($rand <= $acumulado) { $vencedor = $c; break; }
            }

            $vencedores[] = $vencedor;

            // Remover vencedor do pool e recalcular pesos
            $totalPeso -= $vencedor['peso'];
            $pool = array_filter($pool, fn($c) => $c['id'] !== $vencedor['id']);
            $pool = array_values($pool);
        }

        // Registar na BD
        $agora = date('Y-m-d H:i:s');
        foreach ($candidatos as $c) {
            $resultado = in_array($c['id'], array_column($vencedores, 'id')) ? 'vencedor' : 'participante';
            $this->db->insert('plugin_pulso_sorteios', [
                'station_id'                  => $stationId,
                'promocao_id'                 => $promocaoId,
                'ouvinte_id'                  => $c['id'],
                'pontos_na_hora'              => $c['pontos'],
                'probabilidade'               => $c['probabilidade'],
                'dias_sem_ganhar_na_hora'     => $c['dias_sem_ganhar'],
                'total_participacoes_na_hora' => $c['total_participacoes_promo'],
                'resultado'                   => $resultado,
                'data_sorteio'                => $agora,
            ]);

            if ($resultado === 'vencedor') {
                $this->db->update('plugin_pulso_ouvintes', [
                    'total_vitorias'  => $c['total_vitorias'] + 1,
                    'dias_sem_ganhar' => 0,
                    'ultima_vitoria'  => $agora,
                ], ['id' => $c['id']]);
            }
        }

        // Actualizar total_participantes na promoção
        $this->db->executeStatement(
            "UPDATE plugin_pulso_promocoes SET total_participantes = ? WHERE id = ?",
            [count($candidatos), $promocaoId]
        );

        // Inserir notificação para o locutor
        if (!empty($vencedores)) {
            $this->db->insert('pulso_notificacoes', [
                'station_id'   => $stationId,
                'tipo'         => 'sorteio',
                'titulo'       => '🏆 Sorteio: ' . $promocao['nome'],
                'mensagem'     => 'Vencedor: ' . $vencedores[0]['nome'],
                'dados'        => json_encode([
                    'promocao_id' => $promocaoId,
                    'promocao'    => $promocao['nome'],
                    'premio'      => $promocao['premio'],
                    'vencedores'  => array_map(fn($v) => [
                        'id'    => $v['id'],
                        'nome'  => $v['nome'],
                        'telefone' => $v['telefone'] ?? '',
                        'probabilidade' => $v['probabilidade'],
                    ], $vencedores),
                ]),
                'lida'         => 0,
                'data_criacao' => date('Y-m-d H:i:s'),
            ]);
        }

        return [
            'promocao'        => $promocao['nome'],
            'premio'          => $promocao['premio'],
            'vencedores'      => $vencedores,
            'total_candidatos'=> count($candidatos),
            'candidatos'      => $candidatos,
        ];
    }

    public function getHistoricoSorteios(int $stationId, ?int $promocaoId = null): array
    {
        $sql = "SELECT s.*, o.nome, o.telefone, o.segmento, p.nome as promocao_nome, p.premio
                FROM plugin_pulso_sorteios s
                JOIN plugin_pulso_ouvintes o ON o.id = s.ouvinte_id
                JOIN plugin_pulso_promocoes p ON p.id = s.promocao_id
                WHERE p.station_id = ? AND s.resultado = 'vencedor'";
        $params = [$stationId];

        if ($promocaoId) {
            $sql .= " AND s.promocao_id = ?";
            $params[] = $promocaoId;
        }

        $sql .= " ORDER BY s.data_sorteio DESC LIMIT 50";
        return $this->db->fetchAllAssociative($sql, $params);
    }

    public function getOuvintesDisponiveis(int $stationId, int $promocaoId, string $busca = ''): array
    {
        $sql = "SELECT o.id, o.nome, o.telefone, o.segmento, o.pontos
                FROM plugin_pulso_ouvintes o
                WHERE o.station_id = ? AND o.ativo = 1
                AND o.id NOT IN (
                    SELECT ouvinte_id FROM plugin_pulso_participacoes
                    WHERE promocao_id = ? AND tipo = 'promocao'
                )";
        $params = [$stationId, $promocaoId];

        if (!empty($busca)) {
            $sql .= " AND (o.nome LIKE ? OR o.telefone LIKE ?)";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
        }

        $sql .= " ORDER BY o.nome ASC LIMIT 20";
        return $this->db->fetchAllAssociative($sql, $params);
    }

    public function inscreverOuvintePromocao(int $stationId, int $promocaoId, int $ouvinteId): bool
    {
        // Verificar se já está inscrito
        $existe = $this->db->fetchOne(
            "SELECT id FROM plugin_pulso_participacoes
             WHERE promocao_id = ? AND ouvinte_id = ? AND tipo = 'promocao'",
            [$promocaoId, $ouvinteId]
        );
        if ($existe) return false;

        $this->db->insert('plugin_pulso_participacoes', [
            'station_id'        => $stationId,
            'ouvinte_id'        => $ouvinteId,
            'tipo'              => 'promocao',
            'promocao_id'       => $promocaoId,
            'descricao'         => 'Inscrição manual',
            'pontos_ganhos'     => 0,
            'data_participacao' => date('Y-m-d H:i:s'),
        ]);

        // Actualizar contador
        $this->db->executeStatement(
            "UPDATE plugin_pulso_promocoes
             SET total_participantes = (
                SELECT COUNT(DISTINCT ouvinte_id) FROM plugin_pulso_participacoes
                WHERE promocao_id = ? AND tipo = 'promocao'
             ) WHERE id = ?",
            [$promocaoId, $promocaoId]
        );

        return true;
    }

    public function removerParticipantePromocao(int $promocaoId, int $ouvinteId): void
    {
        $this->db->executeStatement(
            "DELETE FROM plugin_pulso_participacoes
             WHERE promocao_id = ? AND ouvinte_id = ? AND tipo = 'promocao'",
            [$promocaoId, $ouvinteId]
        );

        $this->db->executeStatement(
            "UPDATE plugin_pulso_promocoes
             SET total_participantes = (
                SELECT COUNT(DISTINCT ouvinte_id) FROM plugin_pulso_participacoes
                WHERE promocao_id = ? AND tipo = 'promocao'
             ) WHERE id = ?",
            [$promocaoId, $promocaoId]
        );
    }

    public function getInscritosPromocao(int $promocaoId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT p.id as participacao_id, p.data_participacao,
                    o.id as ouvinte_id, o.nome, o.telefone, o.segmento, o.pontos
             FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE p.promocao_id = ? AND p.tipo = 'promocao'
             ORDER BY p.data_participacao DESC",
            [$promocaoId]
        );
    }

    public function getDadosAntiFraude(int $stationId): array
    {
        // Executar scan de fraude
        $this->scanFraude($stationId);

        $alertas = $this->db->fetchAllAssociative(
            "SELECT a.*, o.nome, o.telefone, o.bloqueado
             FROM plugin_pulso_antifraude a
             LEFT JOIN plugin_pulso_ouvintes o ON o.id = a.ouvinte_id
             WHERE a.station_id = ? AND a.resolvido = 0
             ORDER BY FIELD(a.severidade,'alta','media','baixa'), a.data_deteccao DESC",
            [$stationId]
        );

        $bloqueados = $this->db->fetchAllAssociative(
            "SELECT id, nome, telefone, motivo_bloqueio, ultima_actividade, total_participacoes
             FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND bloqueado = 1",
            [$stationId]
        );

        $stats = [
            'total_alertas'  => count($alertas),
            'alta'           => count(array_filter($alertas, fn($a) => $a['severidade'] === 'alta')),
            'media'          => count(array_filter($alertas, fn($a) => $a['severidade'] === 'media')),
            'baixa'          => count(array_filter($alertas, fn($a) => $a['severidade'] === 'baixa')),
            'bloqueados'     => count($bloqueados),
        ];

        $historico = $this->db->fetchAllAssociative(
            "SELECT a.*, o.nome, o.telefone
             FROM plugin_pulso_antifraude a
             LEFT JOIN plugin_pulso_ouvintes o ON o.id = a.ouvinte_id
             WHERE a.station_id = ? AND a.resolvido = 1
             ORDER BY a.data_deteccao DESC LIMIT 20",
            [$stationId]
        );

        return [
            'alertas'    => $alertas,
            'bloqueados' => $bloqueados,
            'historico'  => $historico,
            'stats'      => $stats,
        ];
    }

    public function scanFraude(int $stationId): void
    {
        // 1. Detectar IPs duplicados (múltiplos ouvintes do mesmo IP)
        $ips = $this->db->fetchAllAssociative(
            "SELECT ultimo_ip, COUNT(*) as total, GROUP_CONCAT(id) as ids, GROUP_CONCAT(nome) as nomes
             FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1 AND ultimo_ip IS NOT NULL AND ultimo_ip != ''
             GROUP BY ultimo_ip HAVING COUNT(*) > 2",
            [$stationId]
        );

        foreach ($ips as $ip) {
            $existe = $this->db->fetchOne(
                "SELECT id FROM plugin_pulso_antifraude
                 WHERE station_id = ? AND tipo = 'ip_duplicado' AND ip_relacionado = ? AND resolvido = 0",
                [$stationId, $ip['ultimo_ip']]
            );
            if (!$existe) {
                $this->db->insert('plugin_pulso_antifraude', [
                    'station_id'          => $stationId,
                    'ouvinte_id'          => explode(',', $ip['ids'])[0],
                    'tipo'                => 'ip_duplicado',
                    'descricao'           => $ip['total'] . ' ouvintes registados com o IP ' . $ip['ultimo_ip'] . ': ' . $ip['nomes'],
                    'ip_relacionado'      => $ip['ultimo_ip'],
                    'ouvintes_envolvidos' => $ip['ids'],
                    'severidade'          => $ip['total'] > 4 ? 'alta' : 'media',
                    'resolvido'           => 0,
                    'data_deteccao'       => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 2. Detectar spam (muitas participações em pouco tempo)
        $spam = $this->db->fetchAllAssociative(
            "SELECT o.id, o.nome, COUNT(p.id) as total
             FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND p.data_participacao >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             GROUP BY o.id HAVING COUNT(p.id) > 5",
            [$stationId]
        );

        foreach ($spam as $s) {
            $existe = $this->db->fetchOne(
                "SELECT id FROM plugin_pulso_antifraude
                 WHERE station_id = ? AND ouvinte_id = ? AND tipo = 'spam'
                 AND data_deteccao >= DATE_SUB(NOW(), INTERVAL 24 HOUR) AND resolvido = 0",
                [$stationId, $s['id']]
            );
            if (!$existe) {
                $this->db->insert('plugin_pulso_antifraude', [
                    'station_id'    => $stationId,
                    'ouvinte_id'    => $s['id'],
                    'tipo'          => 'spam',
                    'descricao'     => $s['nome'] . ' fez ' . $s['total'] . ' participações na última hora.',
                    'severidade'    => $s['total'] > 10 ? 'alta' : 'media',
                    'resolvido'     => 0,
                    'data_deteccao' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 3. Detectar multi-conta (mesmo nome, IPs diferentes)
        $multiConta = $this->db->fetchAllAssociative(
            "SELECT SOUNDEX(nome) sx, COUNT(DISTINCT ultimo_ip) ips, COUNT(*) total,
                    GROUP_CONCAT(nome SEPARATOR ' / ') as nomes,
                    GROUP_CONCAT(id) as ids
             FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1
             GROUP BY SOUNDEX(nome) HAVING COUNT(*) > 1 AND COUNT(DISTINCT ultimo_ip) > 1",
            [$stationId]
        );

        foreach ($multiConta as $m) {
            $existe = $this->db->fetchOne(
                "SELECT id FROM plugin_pulso_antifraude
                 WHERE station_id = ? AND tipo = 'multi_conta'
                 AND ouvintes_envolvidos = ? AND resolvido = 0",
                [$stationId, $m['ids']]
            );
            if (!$existe) {
                $this->db->insert('plugin_pulso_antifraude', [
                    'station_id'          => $stationId,
                    'ouvinte_id'          => explode(',', $m['ids'])[0],
                    'tipo'                => 'multi_conta',
                    'descricao'           => 'Possíveis contas duplicadas: ' . $m['nomes'],
                    'ouvintes_envolvidos' => $m['ids'],
                    'severidade'          => 'media',
                    'resolvido'           => 0,
                    'data_deteccao'       => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function resolverAlerta(int $alertaId): void
    {
        $this->db->update('plugin_pulso_antifraude', ['resolvido' => 1], ['id' => $alertaId]);
    }

    public function bloquearOuvinte(int $ouvinteId, string $motivo): void
    {
        $this->db->update('plugin_pulso_ouvintes', [
            'bloqueado'       => 1,
            'motivo_bloqueio' => $motivo,
        ], ['id' => $ouvinteId]);
    }

    public function desbloquearOuvinte(int $ouvinteId): void
    {
        $this->db->update('plugin_pulso_ouvintes', [
            'bloqueado'       => 0,
            'motivo_bloqueio' => null,
        ], ['id' => $ouvinteId]);
    }

    public function gerarRelatorio(int $stationId, string $data): void
    {
        // Verificar se já existe
        $existe = $this->db->fetchOne(
            "SELECT id FROM plugin_pulso_relatorios WHERE station_id = ? AND data_ref = ? AND tipo = 'diario'",
            [$stationId, $data]
        );

        $totalPart = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND DATE(p.data_participacao) = ?",
            [$stationId, $data]
        );

        $ouvUnicos = (int) $this->db->fetchOne(
            "SELECT COUNT(DISTINCT p.ouvinte_id) FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND DATE(p.data_participacao) = ?",
            [$stationId, $data]
        );

        $novos = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND DATE(data_registo) = ?",
            [$stationId, $data]
        );

        $lidas = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND DATE(p.data_participacao) = ? AND p.lido_no_ar = 1",
            [$stationId, $data]
        );

        $skips = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND DATE(p.data_participacao) = ? AND p.skip = 1",
            [$stationId, $data]
        );

        $sorteios = (int) $this->db->fetchOne(
            "SELECT COUNT(DISTINCT data_sorteio) FROM plugin_pulso_sorteios s
             JOIN plugin_pulso_promocoes p ON p.id = s.promocao_id
             WHERE p.station_id = ? AND DATE(s.data_sorteio) = ?",
            [$stationId, $data]
        );

        $alertas = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_pulso_antifraude
             WHERE station_id = ? AND DATE(data_deteccao) = ?",
            [$stationId, $data]
        );

        // Música mais pedida
        $musicaRow = $this->db->fetchAssociative(
            "SELECT descricao, COUNT(*) n FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND DATE(p.data_participacao) = ?
             AND p.tipo = 'pedido_musica' AND p.descricao IS NOT NULL
             GROUP BY descricao ORDER BY n DESC LIMIT 1",
            [$stationId, $data]
        );
        $musicaMaisPedida = '';
        if ($musicaRow) {
            preg_match('/Dedicatoria:\s*(.+?)\s*\[wp_post_id/', $musicaRow['descricao'] ?? '', $m);
            $musicaMaisPedida = $m[1] ?? mb_substr($musicaRow['descricao'] ?? '', 0, 80);
        }

        // Ouvinte mais activo
        $ouvinteRow = $this->db->fetchAssociative(
            "SELECT o.nome, COUNT(p.id) n FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND DATE(p.data_participacao) = ?
             GROUP BY o.id ORDER BY n DESC LIMIT 1",
            [$stationId, $data]
        );
        $ouvinteMaisActivo = $ouvinteRow ? $ouvinteRow['nome'] : '';

        $campos = [
            'station_id'            => $stationId,
            'data_ref'              => $data,
            'tipo'                  => 'diario',
            'total_participacoes'   => $totalPart,
            'total_ouvintes_unicos' => $ouvUnicos,
            'novos_ouvintes'        => $novos,
            'dedicatorias_lidas'    => $lidas,
            'dedicatorias_skip'     => $skips,
            'musica_mais_pedida'    => $musicaMaisPedida,
            'ouvinte_mais_activo'   => $ouvinteMaisActivo,
            'sorteios_realizados'   => $sorteios,
            'alertas_fraude'        => $alertas,
            'created_at'            => date('Y-m-d H:i:s'),
        ];

        if ($existe) {
            $this->db->update('plugin_pulso_relatorios', $campos, ['id' => $existe]);
        } else {
            $this->db->insert('plugin_pulso_relatorios', $campos);
        }
    }

    public function getDadosRelatorios(int $stationId, string $periodo = '30d', ?string $dataInicio = null, ?string $dataFim = null): array
    {
        // Calcular range de datas
        $hoje = date('Y-m-d');
        switch ($periodo) {
            case '7d':   $inicio = date('Y-m-d', strtotime('-6 days'));  $fim = $hoje; break;
            case '30d':  $inicio = date('Y-m-d', strtotime('-29 days')); $fim = $hoje; break;
            case '90d':  $inicio = date('Y-m-d', strtotime('-89 days')); $fim = $hoje; break;
            case 'mes':  $inicio = date('Y-m-01'); $fim = date('Y-m-t'); break;
            case 'mesant': $inicio = date('Y-m-01', strtotime('first day of last month')); $fim = date('Y-m-t', strtotime('last day of last month')); break;
            case 'custom': $inicio = $dataInicio ?? $hoje; $fim = $dataFim ?? $hoje; break;
            default:     $inicio = date('Y-m-d', strtotime('-29 days')); $fim = $hoje;
        }

        // Gerar relatórios para o período
        $current = $inicio;
        while ($current <= $fim) {
            $this->gerarRelatorio($stationId, $current);
            $current = date('Y-m-d', strtotime($current . ' +1 day'));
        }

        $relatorios = $this->db->fetchAllAssociative(
            "SELECT * FROM plugin_pulso_relatorios
             WHERE station_id = ? AND tipo = 'diario'
             AND data_ref BETWEEN ? AND ?
             ORDER BY data_ref DESC",
            [$stationId, $inicio, $fim]
        );

        // Período anterior para comparação
        $dias = (strtotime($fim) - strtotime($inicio)) / 86400 + 1;
        $inicioAnt = date('Y-m-d', strtotime($inicio . " -{$dias} days"));
        $fimAnt    = date('Y-m-d', strtotime($inicio . ' -1 day'));

        $relatoriosAnt = $this->db->fetchAllAssociative(
            "SELECT * FROM plugin_pulso_relatorios
             WHERE station_id = ? AND tipo = 'diario'
             AND data_ref BETWEEN ? AND ?",
            [$stationId, $inicioAnt, $fimAnt]
        );

        // KPIs período actual
        $kpis = [
            'participacoes'   => array_sum(array_column($relatorios, 'total_participacoes')),
            'ouvintes_unicos' => array_sum(array_column($relatorios, 'total_ouvintes_unicos')),
            'novos'           => array_sum(array_column($relatorios, 'novos_ouvintes')),
            'lidas'           => array_sum(array_column($relatorios, 'dedicatorias_lidas')),
            'sorteios'        => array_sum(array_column($relatorios, 'sorteios_realizados')),
            'alertas'         => array_sum(array_column($relatorios, 'alertas_fraude')),
        ];

        // KPIs período anterior
        $kpisAnt = [
            'participacoes'   => array_sum(array_column($relatoriosAnt, 'total_participacoes')),
            'ouvintes_unicos' => array_sum(array_column($relatoriosAnt, 'total_ouvintes_unicos')),
            'novos'           => array_sum(array_column($relatoriosAnt, 'novos_ouvintes')),
            'lidas'           => array_sum(array_column($relatoriosAnt, 'dedicatorias_lidas')),
        ];

        // Variações percentuais
        $variacoes = [];
        foreach ($kpis as $k => $v) {
            $ant = $kpisAnt[$k] ?? 0;
            $variacoes[$k] = $ant > 0 ? round((($v - $ant) / $ant) * 100, 1) : ($v > 0 ? 100 : 0);
        }

        // Top ouvintes do período
        $topOuvintes = $this->db->fetchAllAssociative(
            "SELECT o.id, o.nome, o.segmento, COUNT(p.id) as participacoes, o.pontos
             FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND DATE(p.data_participacao) BETWEEN ? AND ?
             GROUP BY o.id ORDER BY participacoes DESC LIMIT 5",
            [$stationId, $inicio, $fim]
        );

        // Hora de pico
        $horaPico = $this->db->fetchAssociative(
            "SELECT HOUR(p.data_participacao) as hora, COUNT(*) as total
             FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND DATE(p.data_participacao) BETWEEN ? AND ?
             GROUP BY HOUR(p.data_participacao) ORDER BY total DESC LIMIT 1",
            [$stationId, $inicio, $fim]
        );

        // Top músicas do período
        $topMusicas = $this->db->fetchAllAssociative(
            "SELECT descricao, COUNT(*) n FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND p.tipo = 'pedido_musica'
             AND DATE(p.data_participacao) BETWEEN ? AND ?
             AND p.descricao IS NOT NULL
             GROUP BY descricao ORDER BY n DESC LIMIT 5",
            [$stationId, $inicio, $fim]
        );

        // Parsear músicas
        $topMusicas = array_map(function($m) {
            preg_match('/Dedicatoria:\s*(.+?)\s*\[wp_post_id/', $m['descricao'] ?? '', $match);
            $m['musica'] = $match[1] ?? mb_substr($m['descricao'] ?? '', 0, 60);
            return $m;
        }, $topMusicas);

        // Insights automáticos
        $insights = $this->gerarInsightsPeriodo($stationId, $kpis, $kpisAnt, $variacoes, $horaPico);

        return [
            'relatorios'  => $relatorios,
            'kpis'        => $kpis,
            'kpisAnt'     => $kpisAnt,
            'variacoes'   => $variacoes,
            'topOuvintes' => $topOuvintes,
            'topMusicas'  => $topMusicas,
            'horaPico'    => $horaPico,
            'insights'    => $insights,
            'hoje'        => $relatorios[0] ?? null,
            'periodo'     => $periodo,
            'inicio'      => $inicio,
            'fim'         => $fim,
            'dias'        => $dias,
        ];
    }

    public function gerarInsightsPeriodo(int $stationId, array $kpis, array $kpisAnt, array $variacoes, ?array $horaPico): array
    {
        $insights = [];

        // Tendência de participações
        if ($variacoes['participacoes'] >= 50) {
            $insights[] = ['tipo'=>'positivo','icon'=>'🔥','titulo'=>'Engajamento em alta','desc'=>'Participações subiram ' . $variacoes['participacoes'] . '% vs período anterior.','accao'=>'Aproveite para lançar uma promoção agora.'];
        } elseif ($variacoes['participacoes'] <= -30) {
            $insights[] = ['tipo'=>'alerta','icon'=>'⚠️','titulo'=>'Queda no engajamento','desc'=>'Participações caíram ' . abs($variacoes['participacoes']) . '% vs período anterior.','accao'=>'Lance um sorteio para reactivar a audiência.'];
        } elseif ($kpis['participacoes'] === 0) {
            $insights[] = ['tipo'=>'critico','icon'=>'🚨','titulo'=>'Sem actividade','desc'=>'Nenhuma participação no período.','accao'=>'Active uma promoção urgentemente.'];
        }

        // Taxa de leitura no ar
        $taxaLeitura = $kpis['participacoes'] > 0 ? round($kpis['lidas'] / $kpis['participacoes'] * 100) : 0;
        if ($taxaLeitura === 0 && $kpis['participacoes'] > 0) {
            $insights[] = ['tipo'=>'alerta','icon'=>'📻','titulo'=>'Zero leituras no ar','desc'=>'Nenhuma dedicatória foi lida no ar neste período.','accao'=>'O locutor deve ler mais mensagens ao vivo.'];
        } elseif ($taxaLeitura >= 50) {
            $insights[] = ['tipo'=>'positivo','icon'=>'📻','titulo'=>'Alta taxa de leitura','desc'=>$taxaLeitura . '% das participações foram lidas no ar.','accao'=>'Excelente interacção com a audiência.'];
        }

        // Hora de pico
        if (!empty($horaPico)) {
            $insights[] = ['tipo'=>'info','icon'=>'⏰','titulo'=>'Hora de ouro: ' . $horaPico['hora'] . 'h','desc'=>'A maioria das interacções acontece às ' . $horaPico['hora'] . 'h.','accao'=>'Agende promoções e sorteios neste horário.'];
        }

        // Novos ouvintes
        if ($variacoes['novos'] >= 100) {
            $insights[] = ['tipo'=>'positivo','icon'=>'👥','titulo'=>'Crescimento acelerado','desc'=>'Novos ouvintes subiram ' . $variacoes['novos'] . '% vs período anterior.','accao'=>'Complete os perfis dos novos ouvintes.'];
        } elseif ($kpis['novos'] === 0) {
            $insights[] = ['tipo'=>'info','icon'=>'👥','titulo'=>'Sem novos ouvintes','desc'=>'Nenhum ouvinte novo no período.','accao'=>'Partilhe o link da rádio nas redes sociais.'];
        }

        return $insights;
    }

    public function getDadosRelatorioCompleto(int $stationId, string $periodo = '30d', ?string $dataInicio = null, ?string $dataFim = null): array
    {
        $base = $this->getDadosRelatorios($stationId, $periodo, $dataInicio, $dataFim);
        $kpis = $base['kpis'];

        // Mapa de calor
        $mapaCalor = [];
        for ($dia = 0; $dia <= 6; $dia++)
            for ($hora = 0; $hora <= 23; $hora++)
                $mapaCalor[$dia][$hora] = 0;

        $heatData = $this->db->fetchAllAssociative(
            "SELECT DAYOFWEEK(p.data_participacao)-1 as dia, HOUR(p.data_participacao) as hora, COUNT(*) as total
             FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND DATE(p.data_participacao) BETWEEN ? AND ?
             GROUP BY dia, hora",
            [$stationId, $base['inicio'], $base['fim']]
        );
        foreach ($heatData as $h)
            $mapaCalor[$h['dia']][$h['hora']] = (int)$h['total'];

        // Score de saúde
        $scoreEngajamento = min(25, ($kpis['participacoes'] ?? 0) * 2);
        $scoreCrescimento = min(25, ($kpis['novos'] ?? 0) * 5);
        $scoreRetencao    = min(25, count($base['topOuvintes'] ?? []) * 5);
        $scoreLeitura     = ($kpis['participacoes'] ?? 0) > 0
            ? min(25, round(($kpis['lidas'] ?? 0) / $kpis['participacoes'] * 25)) : 0;
        $scoreTotal = $scoreEngajamento + $scoreCrescimento + $scoreRetencao + $scoreLeitura;

        // Previsão
        $mediadiaria = $base['dias'] > 0 ? round(($kpis['participacoes'] ?? 0) / $base['dias'], 1) : 0;
        $previsaoNovosMes = $base['dias'] > 0 ? round(($kpis['novos'] ?? 0) / $base['dias'] * 30) : 0;

        // Narrativa
        $narrativa = $this->gerarNarrativa($stationId, $base, $scoreTotal);

        // Exportação
        $dadosExportacao = array_map(fn($r) => [
            'Data'            => $r['data_ref'],
            'Participações'   => $r['total_participacoes'],
            'Ouvintes Únicos' => $r['total_ouvintes_unicos'],
            'Novos Ouvintes'  => $r['novos_ouvintes'],
            'Lidas no Ar'     => $r['dedicatorias_lidas'],
            'Sorteios'        => $r['sorteios_realizados'],
            'Top Música'      => $r['musica_mais_pedida'],
        ], $base['relatorios']);

        return array_merge($base, [
            'mapaCalor'        => $mapaCalor,
            'scoreSaude'       => $scoreTotal,
            'scoreDetalhes'    => [
                'engajamento'  => $scoreEngajamento,
                'crescimento'  => $scoreCrescimento,
                'retencao'     => $scoreRetencao,
                'leitura'      => $scoreLeitura,
            ],
            'mediaDiaria'      => $mediadiaria,
            'previsaoNovosMes' => $previsaoNovosMes,
            'narrativa'        => $narrativa,
            'dadosExportacao'  => $dadosExportacao,
        ]);
    }

    public function gerarNarrativa(int $stationId, array $base, int|float $score): array
    {
        $kpis     = $base['kpis'];
        $var      = $base['variacoes'];
        $horaPico = $base['horaPico'];
        $dias     = $base['dias'];
        $inicio   = $base['inicio'];
        $fim      = $base['fim'];
        $paragrafos = [];

        $estado = $score >= 70 ? 'excelente' : ($score >= 40 ? 'moderado' : 'em desenvolvimento');
        $totalOuvintes = $this->db->fetchOne("SELECT COUNT(*) FROM plugin_pulso_ouvintes WHERE station_id = ? AND ativo = 1", [$stationId]);
        $paragrafos[] = "No período de " . date('d/m/Y', strtotime($inicio)) . " a " . date('d/m/Y', strtotime($fim))
            . " ({$dias} dias), a Rádio New Band registou um desempenho {$estado}, com {$kpis['participacoes']} interacções"
            . " de {$kpis['ouvintes_unicos']} ouvintes únicos. A rádio conta actualmente com {$totalOuvintes} ouvintes activos.";

        if (($var['participacoes'] ?? 0) > 0)
            $paragrafos[] = "Comparando com o período anterior, o engajamento cresceu " . $var['participacoes'] . "%, indicando tendência positiva.";
        elseif (($var['participacoes'] ?? 0) < 0)
            $paragrafos[] = "Face ao período anterior, verificou-se uma redução de " . abs($var['participacoes'] ?? 0) . "% no engajamento.";

        if (!empty($horaPico))
            $paragrafos[] = "O horário de maior actividade é às {$horaPico['hora']}h, com {$horaPico['total']} interacções. Ideal para promoções e sorteios ao vivo.";

        if (($kpis['novos'] ?? 0) > 0)
            $paragrafos[] = "O período registou {$kpis['novos']} novos ouvintes cadastrados, demonstrando crescimento orgânico.";

        $taxaLeitura = ($kpis['participacoes'] ?? 0) > 0 ? round(($kpis['lidas'] ?? 0) / $kpis['participacoes'] * 100) : 0;
        if ($taxaLeitura > 0)
            $paragrafos[] = "A taxa de leitura no ar situou-se em {$taxaLeitura}%, "
                . ($taxaLeitura >= 50 ? "reflectindo forte interacção entre locutores e ouvintes." : "com oportunidade de melhoria na interacção ao vivo.");

        return $paragrafos;
    }

    public function exportarCSV(int $stationId, string $periodo = '30d'): string
    {
        $dados = $this->getDadosRelatorioCompleto($stationId, $periodo);
        $rows  = $dados['dadosExportacao'];
        if (empty($rows)) return '';
        $csv = implode(',', array_keys($rows[0])) . "\n";
        foreach ($rows as $row)
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string)($v ?? '')) . '"', $row)) . "\n";
        return $csv;
    }

    public function getAnaliseMusicaCompleta(int $stationId, string $periodo = 'tudo'): array
    {
        $dataInicio = match($periodo) {
            'hoje'   => date('Y-m-d 00:00:00'),
            'semana' => date('Y-m-d 00:00:00', strtotime('monday this week')),
            'mes'    => date('Y-m-01 00:00:00'),
            default  => '2000-01-01 00:00:00',
        };

        $musicasBase = $this->db->fetchAllAssociative(
            "SELECT p.descricao, p.data_participacao, p.ouvinte_id,
                    HOUR(p.data_participacao) as hora,
                    DAYOFWEEK(p.data_participacao) as dia_semana,
                    o.nome, o.segmento, o.generos_musicais
             FROM plugin_pulso_participacoes p
             JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
             WHERE o.station_id = ? AND p.tipo = 'pedido_musica' AND p.data_participacao >= ?
             ORDER BY p.data_participacao DESC",
            [$stationId, $dataInicio]
        );

        $generosMap = [
            'kizomba'       => ['kizomba','semba','tarrachinha'],
            'zouk'          => ['zouk','kompa'],
            'kuduro'        => ['kuduro','afrohouse'],
            'amapiano'      => ['amapiano'],
            'rnb_soul'      => ['r&b','soul','rhythm'],
            'pop'           => ['pop','billie eilish','bruno mars','ed sheeran','teddy swims'],
            'romantico'     => ['love','amor','perdoa','forgive','natural woman','versace','decision','dime','human nature'],
            'afrobeat'      => ['afrobeat','wizkid','burna'],
            'internacional' => ['michael jackson','céline dion','chris de burgh','bryan adams','usher'],
            'angola'        => ['dj malvado','heavy c','yuri da cunha','candongueiro','mussulo'],
        ];

        $porGenero = []; $porHora = array_fill(0, 24, 0);
        $porSegmento = []; $porDiaSemana = array_fill(1, 7, 0);
        $historicoRico = [];

        foreach ($musicasBase as $p) {
            preg_match('/Dedicatoria:\s*(.+?)\s*\[wp_post_id/', $p['descricao'] ?? '', $m);
            preg_match('/Msg:\s*(.+)$/', $p['descricao'] ?? '', $msg);
            $musicaCompleta = $m[1] ?? mb_substr($p['descricao'] ?? '', 0, 60);
            $mensagem = trim($msg[1] ?? '');

            $generoDetectado = 'outros';
            $musicaLower = mb_strtolower($musicaCompleta);
            foreach ($generosMap as $genero => $keywords)
                foreach ($keywords as $kw)
                    if (strpos($musicaLower, $kw) !== false) { $generoDetectado = $genero; break 2; }

            $porGenero[$generoDetectado] = ($porGenero[$generoDetectado] ?? 0) + 1;
            $porHora[(int)$p['hora']]++;
            $porDiaSemana[(int)$p['dia_semana']]++;
            $seg = $p['segmento'] ?? 'novo';
            $porSegmento[$seg][$generoDetectado] = ($porSegmento[$seg][$generoDetectado] ?? 0) + 1;
            $historicoRico[] = [
                'musica'   => $musicaCompleta,
                'mensagem' => $mensagem,
                'nome'     => $p['nome'],
                'segmento' => $seg,
                'hora'     => $p['hora'],
                'genero'   => $generoDetectado,
                'data'     => $p['data_participacao'],
            ];
        }
        arsort($porGenero);

        $ouvintesGeneros = $this->db->fetchAllAssociative(
            "SELECT generos_musicais FROM plugin_pulso_ouvintes WHERE station_id = ? AND ativo = 1 AND generos_musicais IS NOT NULL AND generos_musicais != ''",
            [$stationId]
        );
        $generosDeclarados = [];
        foreach ($ouvintesGeneros as $o) {
            $generos = json_decode($o['generos_musicais'] ?? '[]', true) ?: [];
            foreach ($generos as $g)
                $generosDeclarados[$g] = ($generosDeclarados[$g] ?? 0) + 1;
        }
        arsort($generosDeclarados);

        $insights = $this->gerarInsightsMusicais($porGenero, $generosDeclarados, $porHora, $porSegmento);

        return [
            'musicas'           => $this->getMusicasMaisPedidas($stationId, $periodo, 30)['musicas'],
            'artistas'          => $this->getArtistasMaisPedidos($stationId, $periodo, 10),
            'porGenero'         => $porGenero,
            'porHora'           => $porHora,
            'porDiaSemana'      => $porDiaSemana,
            'porSegmento'       => $porSegmento,
            'generosDeclarados' => $generosDeclarados,
            'historicoRico'     => $historicoRico,
            'insights'          => $insights,
            'total'             => count($musicasBase),
            'periodo'           => $periodo,
        ];
    }

    public function gerarInsightsMusicais(array $porGenero, array $generosDeclarados, array $porHora, array $porSegmento): array
    {
        $insights = [];
        $nomes = ['romantico'=>'Romântico','pop'=>'Pop','rnb_soul'=>'R&B/Soul','kizomba'=>'Kizomba',
                  'angola'=>'Angolano','internacional'=>'Internacional','zouk'=>'Zouk',
                  'kuduro'=>'Kuduro','amapiano'=>'Amapiano','afrobeat'=>'Afrobeat','outros'=>'Outros'];

        if (!empty($porGenero)) {
            $topPedido = array_key_first($porGenero);
            $insights[] = ['tipo'=>'positivo','icon'=>'🎵',
                'titulo'=>'Género mais pedido: ' . ($nomes[$topPedido] ?? $topPedido),
                'desc'=>'Representa ' . $porGenero[$topPedido] . ' pedidos no período.',
                'accao'=>'Aumentar a rotação deste género na programação.'];

            $topDeclarado = !empty($generosDeclarados) ? array_key_first($generosDeclarados) : null;
            if ($topDeclarado && mb_strtolower($topDeclarado) !== $topPedido)
                $insights[] = ['tipo'=>'info','icon'=>'📊',
                    'titulo'=>'Divergência: declarado vs pedido',
                    'desc'=>"Ouvintes declaram preferir {$topDeclarado} mas pedem mais " . ($nomes[$topPedido] ?? $topPedido) . ".",
                    'accao'=>'Considerar programação híbrida.'];
        }

        $maxHora = array_search(max($porHora), $porHora);
        if (max($porHora) > 0)
            $insights[] = ['tipo'=>'info','icon'=>'⏰',
                'titulo'=>"Pico de pedidos às {$maxHora}h",
                'desc'=>max($porHora) . ' pedidos neste horário.',
                'accao'=>"Programar músicas mais pedidas neste bloco horário."];

        if (!empty($porSegmento)) {
            $segMaisActivo = ''; $maxSeg = 0;
            foreach ($porSegmento as $seg => $generos) {
                $total = array_sum($generos);
                if ($total > $maxSeg) { $maxSeg = $total; $segMaisActivo = $seg; }
            }
            if ($segMaisActivo)
                $insights[] = ['tipo'=>'positivo','icon'=>'👥',
                    'titulo'=>"Segmento mais activo: " . ucfirst($segMaisActivo),
                    'desc'=>"Ouvintes {$segMaisActivo} são os que mais pedem músicas ({$maxSeg} pedidos).",
                    'accao'=>'Criar promoções direccionadas a este segmento.'];
        }
        return $insights;
    }

    public function getDadosPremios(int $stationId): array
    {
        $premios = $this->db->fetchAllAssociative(
            "SELECT * FROM plugin_pulso_premios WHERE station_id = ? ORDER BY ativo DESC, nome ASC",
            [$stationId]
        );

        $stats = [
            'total'       => count($premios),
            'disponiveis' => array_sum(array_column($premios, 'quantidade_disponivel')),
            'reservados'  => array_sum(array_column($premios, 'quantidade_reservada')),
            'entregues'   => array_sum(array_column($premios, 'quantidade_entregue')),
        ];

        return ['premios' => $premios, 'stats' => $stats];
    }

    public function getPremio(int $id): ?array
    {
        return $this->db->fetchAssociative("SELECT * FROM plugin_pulso_premios WHERE id = ?", [$id]) ?: null;
    }

    public function salvarPremio(int $stationId, array $dados, ?int $id = null): int
    {
        $campos = [
            'station_id'            => $stationId,
            'nome'                  => $dados['nome'] ?? '',
            'descricao'             => $dados['descricao'] ?? '',
            'quantidade_total'      => (int)($dados['quantidade_total'] ?? 0),
            'quantidade_disponivel' => (int)($dados['quantidade_total'] ?? 0),
            'valor_estimado'        => (float)str_replace(['.', ','], ['', '.'], $dados['valor_estimado'] ?? '0'),
            'fornecedor'            => $dados['fornecedor'] ?? '',
            'ativo'                 => isset($dados['ativo']) ? 1 : 0,
        ];

        if ($id) {
            // Ao editar, ajustar disponível proporcionalmente
            $atual = $this->getPremio($id);
            if ($atual) {
                $diff = $campos['quantidade_total'] - $atual['quantidade_total'];
                $campos['quantidade_disponivel'] = max(0, $atual['quantidade_disponivel'] + $diff);
                unset($campos['station_id']);
            }
            $this->db->update('plugin_pulso_premios', $campos, ['id' => $id]);
            return $id;
        }

        $this->db->insert('plugin_pulso_premios', $campos);
        return (int)$this->db->lastInsertId();
    }

    public function excluirPremio(int $id): void
    {
        $this->db->delete('plugin_pulso_premios', ['id' => $id]);
    }

    public function getDadosEntregas(int $stationId, string $estado = ''): array
    {
        $sql = "SELECT e.*, o.nome as ouvinte_nome, o.telefone, p.nome as premio_nome,
                       pr.nome as promocao_nome
                FROM plugin_pulso_entregas e
                JOIN plugin_pulso_ouvintes o ON o.id = e.ouvinte_id
                JOIN plugin_pulso_premios p ON p.id = e.premio_id
                LEFT JOIN plugin_pulso_promocoes pr ON pr.id = e.promocao_id
                WHERE e.station_id = ?";
        $params = [$stationId];

        if ($estado) {
            $sql .= " AND e.estado = ?";
            $params[] = $estado;
        }

        $sql .= " ORDER BY e.data_reserva DESC";
        $entregas = $this->db->fetchAllAssociative($sql, $params);

        $stats = [
            'reservado'   => 0, 'notificado' => 0, 'confirmado' => 0,
            'entregue'    => 0, 'devolvido'  => 0, 'cancelado'  => 0,
        ];
        foreach ($entregas as $e) {
            $stats[$e['estado']] = ($stats[$e['estado']] ?? 0) + 1;
        }

        return ['entregas' => $entregas, 'stats' => $stats, 'estado' => $estado];
    }

    public function actualizarEstadoEntrega(int $entregaId, string $estado, string $notas = '', string $entregue_por = '', string $documento = ''): void
    {
        $campos = ['estado' => $estado];
        if ($notas)      $campos['notas']        = $notas;
        if ($entregue_por) $campos['entregue_por'] = $entregue_por;
        if ($documento)  $campos['documento_id'] = $documento;

        $agora = date('Y-m-d H:i:s');
        match($estado) {
            'notificado'  => $campos['data_notificacao'] = $agora,
            'confirmado'  => $campos['data_confirmacao'] = $agora,
            'entregue'    => $campos['data_entrega'] = $agora,
            default       => null
        };

        $this->db->update('plugin_pulso_entregas', $campos, ['id' => $entregaId]);

        // Actualizar stock se entregue ou devolvido
        $entrega = $this->db->fetchAssociative("SELECT * FROM plugin_pulso_entregas WHERE id = ?", [$entregaId]);
        if ($entrega) {
            if ($estado === 'entregue') {
                $this->db->executeStatement(
                    "UPDATE plugin_pulso_premios SET quantidade_reservada = GREATEST(0, quantidade_reservada-1), quantidade_entregue = quantidade_entregue+1 WHERE id = ?",
                    [$entrega['premio_id']]
                );
            } elseif ($estado === 'devolvido' || $estado === 'cancelado') {
                $this->db->executeStatement(
                    "UPDATE plugin_pulso_premios SET quantidade_reservada = GREATEST(0, quantidade_reservada-1), quantidade_disponivel = quantidade_disponivel+1 WHERE id = ?",
                    [$entrega['premio_id']]
                );
            }
        }
    }

    public function criarEntregaAposSorteio(int $stationId, int $sorteioId, int $premioId): void
    {
        $sorteio = $this->db->fetchAssociative("SELECT * FROM plugin_pulso_sorteios WHERE id = ?", [$sorteioId]);
        if (!$sorteio || $sorteio['resultado'] !== 'vencedor') return;

        $this->db->insert('plugin_pulso_entregas', [
            'station_id'   => $stationId,
            'premio_id'    => $premioId,
            'promocao_id'  => $sorteio['promocao_id'],
            'sorteio_id'   => $sorteioId,
            'ouvinte_id'   => $sorteio['ouvinte_id'],
            'estado'       => 'reservado',
            'data_reserva' => date('Y-m-d H:i:s'),
        ]);

        // Reservar no stock
        $this->db->executeStatement(
            "UPDATE plugin_pulso_premios SET quantidade_disponivel = GREATEST(0, quantidade_disponivel-1), quantidade_reservada = quantidade_reservada+1 WHERE id = ?",
            [$premioId]
        );
    }

    public function verificarSorteiosAutomaticos(int $stationId): array
    {
        $promocoes = $this->db->fetchAllAssociative(
            "SELECT * FROM plugin_pulso_promocoes
             WHERE station_id = ?
             AND estado = 'activa'
             AND sorteio_automatico = 1
             AND sorteio_realizado = 0
             AND data_fim IS NOT NULL
             AND data_fim <= NOW()",
            [$stationId]
        );

        $resultados = [];
        foreach ($promocoes as $p) {
            $resultado = $this->realizarSorteio($stationId, $p['id'], $p['max_vencedores']);

            $this->db->update('plugin_pulso_promocoes', [
                'sorteio_realizado' => 1,
                'sorteio_data'      => date('Y-m-d H:i:s'),
                'estado'            => 'encerrada',
            ], ['id' => $p['id']]);

            $resultados[] = [
                'promocao_id'   => $p['id'],
                'promocao_nome' => $p['nome'],
                'vencedores'    => $resultado['vencedores'] ?? [],
                'erro'          => $resultado['erro'] ?? null,
            ];

            error_log("PULSO: Sorteio automático #{$p['id']} — {$p['nome']}");
        }

        return $resultados;
    }

    public function getDadosAtendimento(int $stationId): array
    {
        $promocoesActivas = $this->db->fetchAllAssociative(
            "SELECT id, nome, premio FROM plugin_pulso_promocoes
             WHERE station_id = ? AND estado = 'activa' ORDER BY nome",
            [$stationId]
        );

        $recentes = $this->db->fetchAllAssociative(
            "SELECT a.*, o.nome as ouvinte_nome, o.telefone, o.segmento
             FROM plugin_pulso_atendimentos a
             LEFT JOIN plugin_pulso_ouvintes o ON o.id = a.ouvinte_id
             WHERE a.station_id = ? AND DATE(a.data_atendimento) = CURDATE()
             ORDER BY a.data_atendimento DESC LIMIT 20",
            [$stationId]
        );

        $stats = [
            'hoje'        => (int)$this->db->fetchOne("SELECT COUNT(*) FROM plugin_pulso_atendimentos WHERE station_id = ? AND DATE(data_atendimento) = CURDATE()", [$stationId]),
            'esta_semana' => (int)$this->db->fetchOne("SELECT COUNT(*) FROM plugin_pulso_atendimentos WHERE station_id = ? AND data_atendimento >= DATE_SUB(NOW(), INTERVAL 7 DAY)", [$stationId]),
            'total'       => (int)$this->db->fetchOne("SELECT COUNT(*) FROM plugin_pulso_atendimentos WHERE station_id = ?", [$stationId]),
        ];

        return [
            'promocoes_activas' => $promocoesActivas,
            'recentes'          => $recentes,
            'stats'             => $stats,
        ];
    }

    public function buscarOuvinteAtendimento(int $stationId, string $busca): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT id, nome, telefone, segmento, pontos, total_participacoes, cidade
             FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1
             AND (nome LIKE ? OR telefone LIKE ?)
             ORDER BY total_participacoes DESC LIMIT 10",
            [$stationId, "%{$busca}%", "%{$busca}%"]
        );
    }

    public function registarAtendimento(int $stationId, array $dados): int
    {
        $ouvinteId = !empty($dados['ouvinte_id']) ? (int)$dados['ouvinte_id'] : null;

        // Criar ouvinte novo se necessário
        if (!$ouvinteId && !empty($dados['novo_nome'])) {
            $this->db->insert('plugin_pulso_ouvintes', [
                'station_id'   => $stationId,
                'nome'         => $dados['novo_nome'],
                'telefone'     => $dados['novo_telefone'] ?? '',
                'ativo'        => 1,
                'segmento'     => 'novo',
                'data_registo' => date('Y-m-d H:i:s'),
            ]);
            $ouvinteId = (int)$this->db->lastInsertId();
        }

        // Registar atendimento
        $this->db->insert('plugin_pulso_atendimentos', [
            'station_id'      => $stationId,
            'ouvinte_id'      => $ouvinteId,
            'atendente'       => $dados['atendente'] ?? '',
            'tipo'            => $dados['tipo'] ?? 'pedido_musical',
            'canal'           => $dados['canal'] ?? 'telefone',
            'duracao_segundos'=> (int)($dados['duracao_segundos'] ?? 0),
            'descricao'       => $dados['descricao'] ?? '',
            'musica_pedida'   => $dados['musica_pedida'] ?? '',
            'promocao_id'     => !empty($dados['promocao_id']) ? (int)$dados['promocao_id'] : null,
            'resultado'       => $dados['resultado'] ?? 'atendido',
            'notas'           => $dados['notas'] ?? '',
            'data_atendimento'=> date('Y-m-d H:i:s'),
        ]);
        $atendimentoId = (int)$this->db->lastInsertId();

        // Registar participação se tiver promoção
        if ($ouvinteId && !empty($dados['promocao_id'])) {
            $this->inscreverOuvintePromocao($stationId, (int)$dados['promocao_id'], $ouvinteId);
        }

        // Registar pedido musical como participação
        if ($ouvinteId && !empty($dados['musica_pedida'])) {
            $this->db->insert('plugin_pulso_participacoes', [
                'station_id'       => $stationId,
                'ouvinte_id'       => $ouvinteId,
                'tipo'             => 'pedido_musica',
                'descricao'        => 'Tel: ' . ($dados['musica_pedida'] ?? ''),
                'data_participacao'=> date('Y-m-d H:i:s'),
            ]);
            // Actualizar pontos
            $this->db->executeStatement(
                "UPDATE plugin_pulso_ouvintes SET pontos = pontos + 5, total_participacoes = total_participacoes + 1, ultima_actividade = NOW() WHERE id = ?",
                [$ouvinteId]
            );
        }

        return $atendimentoId;
    }

    public function getHistoricoAtendimentos(int $stationId, string $data = '', string $tipo = ''): array
    {
        $sql = "SELECT a.*, o.nome as ouvinte_nome, o.telefone, o.segmento
                FROM plugin_pulso_atendimentos a
                LEFT JOIN plugin_pulso_ouvintes o ON o.id = a.ouvinte_id
                WHERE a.station_id = ?";
        $params = [$stationId];

        if ($data) { $sql .= " AND DATE(a.data_atendimento) = ?"; $params[] = $data; }
        if ($tipo)  { $sql .= " AND a.tipo = ?"; $params[] = $tipo; }

        $sql .= " ORDER BY a.data_atendimento DESC LIMIT 100";
        return $this->db->fetchAllAssociative($sql, $params);
    }

    public function getAniversariantes(int $stationId, string $tipo = 'hoje'): array
    {
        switch ($tipo) {
            case 'mes':
                $where = "DATE_FORMAT(data_nascimento,'%m') = DATE_FORMAT(NOW(),'%m')";
                $label = 'Este Mês';
                break;
            case '7dias':
                $where = "DATE_FORMAT(data_nascimento,'%m-%d') BETWEEN DATE_FORMAT(NOW(),'%m-%d') AND DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 7 DAY),'%m-%d')";
                $label = 'Próximos 7 Dias';
                break;
            default: // hoje
                $where = "DATE_FORMAT(data_nascimento,'%m-%d') = DATE_FORMAT(NOW(),'%m-%d')";
                $label = 'Hoje';
        }

        $ouvintes = $this->db->fetchAllAssociative(
            "SELECT id, nome, telefone, data_nascimento, segmento, pontos,
                    TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) as idade
             FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1 AND data_nascimento IS NOT NULL
             AND {$where}
             ORDER BY DATE_FORMAT(data_nascimento,'%m-%d') ASC",
            [$stationId]
        );

        // Próximos aniversários (sempre mostrar)
        $proximos = $this->db->fetchAllAssociative(
            "SELECT id, nome, data_nascimento,
                    DATE_FORMAT(data_nascimento,'%d/%m') as data_fmt,
                    TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) + 1 as proxima_idade,
                    DATEDIFF(
                        DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(data_nascimento), '-', DAY(data_nascimento))),
                        CURDATE()
                    ) as dias_restantes
             FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1 AND data_nascimento IS NOT NULL
             HAVING dias_restantes >= 0
             ORDER BY dias_restantes ASC
             LIMIT 10",
            [$stationId]
        );

        return [
            'aniversariantes' => $ouvintes,
            'proximos'        => $proximos,
            'tipo'            => $tipo,
            'label'           => $label,
            'total'           => count($ouvintes),
        ];
    }

    public function sortearAniversariantes(int $stationId, array $ouvinteIds, string $premio = ''): array
    {
        if (empty($ouvinteIds)) return ['erro' => 'Nenhum aniversariante seleccionado'];

        // Buscar dados dos seleccionados
        $placeholders = implode(',', array_fill(0, count($ouvinteIds), '?'));
        $candidatos = $this->db->fetchAllAssociative(
            "SELECT id, nome, telefone, pontos, segmento, dias_sem_ganhar, data_nascimento
             FROM plugin_pulso_ouvintes WHERE id IN ({$placeholders}) AND ativo = 1",
            $ouvinteIds
        );

        if (empty($candidatos)) return ['erro' => 'Ouvintes não encontrados'];

        // Sortear aleatoriamente (aniversários são sorteio justo — todos têm igual chance)
        $idx      = array_rand($candidatos);
        $vencedor = $candidatos[$idx];

        // Registar notificação para o locutor
        $this->db->insert('pulso_notificacoes', [
            'station_id'   => $stationId,
            'tipo'         => 'aniversario',
            'titulo'       => '🎂 Aniversariante Sorteado!',
            'mensagem'     => 'Parabéns: ' . $vencedor['nome'],
            'dados'        => json_encode([
                'vencedor' => ['id' => $vencedor['id'], 'nome' => $vencedor['nome'], 'telefone' => $vencedor['telefone']],
                'premio'   => $premio,
                'total_candidatos' => count($candidatos),
            ]),
            'lida'         => 0,
            'data_criacao' => date('Y-m-d H:i:s'),
        ]);

        return [
            'vencedor'   => $vencedor,
            'candidatos' => $candidatos,
            'premio'     => $premio,
            'total'      => count($candidatos),
        ];
    }

    public function getAniversariantesHoje(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT id, nome, telefone, TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) as idade
             FROM plugin_pulso_ouvintes
             WHERE station_id = ? AND ativo = 1 AND data_nascimento IS NOT NULL
             AND DATE_FORMAT(data_nascimento,'%m-%d') = DATE_FORMAT(NOW(),'%m-%d')",
            [$stationId]
        );
    }

    public function getStreamAnalytics(int $stationId): array
    {
        $stationShortcode = $this->db->fetchOne(
            "SELECT short_name FROM station WHERE id = ?", [$stationId]
        ) ?: 'rnb';

        $apiKey = $this->db->fetchOne(
            "SELECT valor FROM plugin_pulso_config WHERE station_id = ? AND chave = 'azuracast_api_key'",
            [$stationId]
        ) ?: '';

        // Buscar dados da API pública
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $raw = @file_get_contents("http://127.0.0.1/api/nowplaying/{$stationShortcode}", false, $ctx);
        $api = $raw ? json_decode($raw, true) : [];

        if (empty($api)) {
            return ['erro' => 'API indisponível', 'listeners' => ['total'=>0,'unique'=>0,'current'=>0]];
        }

        $nowPlaying = $api['now_playing'] ?? [];
        $song       = $nowPlaying['song'] ?? [];
        $listeners  = $api['listeners'] ?? ['total'=>0,'unique'=>0,'current'=>0];
        $history    = $api['song_history'] ?? [];
        $next       = $api['playing_next'] ?? [];
        $live       = $api['live'] ?? [];
        $mounts     = $api['station']['mounts'] ?? [];

        // Gravar histórico de listeners na BD para gráfico
        $this->db->executeStatement(
            "INSERT INTO plugin_pulso_stream_stats (station_id, listeners_total, listeners_unique, song_title, song_artist, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE listeners_total = VALUES(listeners_total)",
            [$stationId, $listeners['total'], $listeners['unique'], $song['title'] ?? '', $song['artist'] ?? '']
        );

        // Histórico de listeners últimas 24h
        $historicoListeners = $this->db->fetchAllAssociative(
            "SELECT listeners_total, listeners_unique, DATE_FORMAT(created_at,'%H:%i') as hora
             FROM plugin_pulso_stream_stats
             WHERE station_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY created_at ASC",
            [$stationId]
        );

        // Top músicas tocadas (do histórico do AzuraCast)
        // Buscar histórico rico via API autenticada
        $ctxAuth = stream_context_create(['http' => [
            'header' => "X-API-Key: {$apiKey}
",
            'timeout' => 3
        ]]);
        $histRaw = @file_get_contents("http://127.0.0.1/api/station/{$stationId}/history?rows=20", false, $ctxAuth);
        $histRico = $histRaw ? json_decode($histRaw, true) : [];

        // Análise de energy e humor
        $energyCount = ['Up' => 0, 'Down' => 0, 'Heavy' => 0];
        $topMusicas = [];
        foreach (array_slice($histRico ?: $history, 0, 10) as $h) {
            $energy = $h['song']['custom_fields']['energy_level'] ?? '';
            if (isset($energyCount[$energy])) $energyCount[$energy]++;
            $topMusicas[] = [
                'titulo'   => $h['song']['title'] ?? '—',
                'artista'  => $h['song']['artist'] ?? '—',
                'art'      => $h['song']['art'] ?? '',
                'tocou'    => date('H:i', $h['played_at'] ?? time()),
                'playlist' => $h['playlist'] ?? '',
                'energy'   => $energy,
                'humor'    => $h['song']['custom_fields']['Humor'] ?? '',
            ];
        }

        return [
            'listeners'          => $listeners,
            'now_playing'        => [
                'titulo'    => $song['title'] ?? '—',
                'artista'   => $song['artist'] ?? '—',
                'art'       => $song['art'] ?? '',
                'duracao'   => $nowPlaying['duration'] ?? 0,
                'elapsed'   => $nowPlaying['elapsed'] ?? 0,
                'playlist'  => $nowPlaying['playlist'] ?? '',
            ],
            'playing_next'       => [
                'titulo'  => $next['song']['title'] ?? '—',
                'artista' => $next['song']['artist'] ?? '—',
                'art'     => $next['song']['art'] ?? '',
            ],
            'live'               => $live,
            'mounts'             => $mounts,
            'historico_listeners'=> $historicoListeners,
            'top_musicas'        => $topMusicas,
            'listen_url'         => $api['station']['listen_url'] ?? '',
        ];
    }

    public function gerarRelatorioPdf(int $stationId, string $periodo = '30d', string $modo = 'interno'): string
    {
        require_once __DIR__ . '/../../vendor-plugin/autoload.php';

        $dados = $this->getDadosRelatorioCompleto($stationId, $periodo);
        $kpis  = $dados['kpis'] ?? [];
        $var   = $dados['variacoes'] ?? [];
        $score = $dados['scoreSaude'] ?? 0;
        $narrativa = $dados['narrativa'] ?? [];
        $relatorios = $dados['relatorios'] ?? [];
        $inicio = $dados['inicio'] ?? '';
        $fim    = $dados['fim'] ?? '';

        $station = $this->db->fetchAssociative("SELECT name FROM station WHERE id = ?", [$stationId]);
        $nomeEstacao = $station['name'] ?? 'Rádio New Band';

        $scoreColor = $score >= 70 ? '#10b981' : ($score >= 40 ? '#f59e0b' : '#ef4444');
        $periodoLabel = [
            '7d' => 'Últimos 7 dias', '30d' => 'Últimos 30 dias',
            '90d' => 'Últimos 90 dias',
        ][$periodo] ?? $periodo;

        // Tabela de dados diários
        $tabelaRows = '';
        foreach (array_slice(array_reverse($relatorios), 0, 30) as $r) {
            $tabelaRows .= '<tr>
                <td>' . date('d/m/Y', strtotime($r['data_ref'])) . '</td>
                <td style="text-align:center">' . ($r['total_participacoes'] ?? 0) . '</td>
                <td style="text-align:center">' . ($r['total_ouvintes_unicos'] ?? 0) . '</td>
                <td style="text-align:center">' . ($r['novos_ouvintes'] ?? 0) . '</td>
                <td style="text-align:center">' . ($r['dedicatorias_lidas'] ?? 0) . '</td>
            </tr>';
        }

        // Variação formatada
        $fmtVar = function($v) {
            if ($v === null) return '<span style="color:#71717a">—</span>';
            $cor = $v >= 0 ? '#10b981' : '#ef4444';
            $sinal = $v >= 0 ? '↑' : '↓';
            return "<span style='color:{$cor};font-weight:700'>{$sinal} " . abs((int)$v) . "%</span>";
        };

        $html = '<!DOCTYPE html><html>
<head><meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a2e; margin: 0; }
.header { background: linear-gradient(135deg, #0f0f1f, #1a1a2e); color: white; padding: 30px 40px; }
.header h1 { font-size: 22px; margin: 0 0 5px 0; }
.header .sub { font-size: 12px; color: #a0aec0; }
.header .periodo { font-size: 13px; color: #00e5ff; margin-top: 8px; }
.body { padding: 30px 40px; }
.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
.kpi { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; text-align: center; }
.kpi-val { font-size: 28px; font-weight: 900; color: #0f0f1f; }
.kpi-lbl { font-size: 10px; color: #718096; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
.kpi-var { font-size: 11px; margin-top: 4px; }
.section { margin-bottom: 24px; }
.section-title { font-size: 14px; font-weight: 700; color: #0f0f1f; border-bottom: 2px solid #00e5ff; padding-bottom: 6px; margin-bottom: 12px; }
.score-box { background: #f8fafc; border: 2px solid ' . $scoreColor . '; border-radius: 10px; padding: 16px; display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
.score-num { font-size: 48px; font-weight: 900; color: ' . $scoreColor . '; }
.narrativa p { margin: 0 0 8px 0; line-height: 1.6; color: #2d3748; font-size: 11px; }
table { width: 100%; border-collapse: collapse; font-size: 11px; }
table thead th { background: #1a1a2e; color: white; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
table tbody tr:nth-child(even) { background: #f8fafc; }
table tbody td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; }
.footer { margin-top: 30px; padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #718096; text-align: center; }
.watermark { color: #e2e8f0; font-size: 10px; }
</style>
</head>
<body>

<div class="header">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
        <div>
            <h1>📊 Relatório de Audiência</h1>
            <div class="sub">' . htmlspecialchars($nomeEstacao) . '</div>
            <div class="periodo">Período: ' . $periodoLabel . ' · ' . date('d/m/Y', strtotime($inicio)) . ' a ' . date('d/m/Y', strtotime($fim)) . '</div>
        </div>
        <div style="text-align:right;color:#a0aec0;font-size:10px">
            Gerado em ' . date('d/m/Y H:i') . '<br>
            PULSO · Sistema de Inteligência de Audiência
        </div>
    </div>
</div>

<div class="body">

<div class="section">
    <div class="section-title">📈 KPIs do Período</div>
    <table style="width:100%;border-collapse:collapse">
    <tr>
        <td style="width:25%;padding:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;text-align:center">
            <div style="font-size:28px;font-weight:900;color:#0f0f1f">' . number_format($kpis['participacoes'] ?? 0) . '</div>
            <div style="font-size:10px;color:#718096;text-transform:uppercase;letter-spacing:1px">Participações</div>
            <div style="font-size:11px;margin-top:4px">' . $fmtVar($var['participacoes'] ?? null) . '</div>
        </td>
        <td style="width:5%"></td>
        <td style="width:25%;padding:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;text-align:center">
            <div style="font-size:28px;font-weight:900;color:#0f0f1f">' . number_format($kpis['ouvintes_unicos'] ?? 0) . '</div>
            <div style="font-size:10px;color:#718096;text-transform:uppercase;letter-spacing:1px">Ouvintes Únicos</div>
            <div style="font-size:11px;margin-top:4px">' . $fmtVar($var['ouvintes_unicos'] ?? null) . '</div>
        </td>
        <td style="width:5%"></td>
        <td style="width:25%;padding:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;text-align:center">
            <div style="font-size:28px;font-weight:900;color:#0f0f1f">' . number_format($kpis['novos'] ?? 0) . '</div>
            <div style="font-size:10px;color:#718096;text-transform:uppercase;letter-spacing:1px">Novos Ouvintes</div>
            <div style="font-size:11px;margin-top:4px">' . $fmtVar($var['novos'] ?? null) . '</div>
        </td>
        <td style="width:5%"></td>
        <td style="width:25%;padding:8px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;text-align:center">
            <div style="font-size:28px;font-weight:900;color:#0f0f1f">' . number_format($kpis['lidas'] ?? 0) . '</div>
            <div style="font-size:10px;color:#718096;text-transform:uppercase;letter-spacing:1px">Lidas no Ar</div>
            <div style="font-size:11px;margin-top:4px">' . $fmtVar($var['lidas'] ?? null) . '</div>
        </td>
    </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">💚 Score de Saúde da Audiência</div>
    <table style="width:100%">
    <tr>
        <td style="width:120px;text-align:center;vertical-align:middle">
            <div style="font-size:52px;font-weight:900;color:' . $scoreColor . '">' . $score . '</div>
            <div style="font-size:10px;color:#718096">/ 100</div>
        </td>
        <td style="padding-left:20px;vertical-align:middle">
            <div style="margin-bottom:8px">
                <div style="font-size:11px;color:#718096;margin-bottom:3px">Engajamento</div>
                <div style="background:#e2e8f0;border-radius:4px;height:8px;overflow:hidden">
                    <div style="background:#00e5ff;width:' . ($dados['scoreDetalhes']['engajamento'] ?? 0) * 4 . '%;height:100%"></div>
                </div>
            </div>
            <div style="margin-bottom:8px">
                <div style="font-size:11px;color:#718096;margin-bottom:3px">Crescimento</div>
                <div style="background:#e2e8f0;border-radius:4px;height:8px;overflow:hidden">
                    <div style="background:#8b5cf6;width:' . ($dados['scoreDetalhes']['crescimento'] ?? 0) * 4 . '%;height:100%"></div>
                </div>
            </div>
            <div style="margin-bottom:8px">
                <div style="font-size:11px;color:#718096;margin-bottom:3px">Retenção</div>
                <div style="background:#e2e8f0;border-radius:4px;height:8px;overflow:hidden">
                    <div style="background:#10b981;width:' . ($dados['scoreDetalhes']['retencao'] ?? 0) * 4 . '%;height:100%"></div>
                </div>
            </div>
            <div>
                <div style="font-size:11px;color:#718096;margin-bottom:3px">Taxa de Leitura</div>
                <div style="background:#e2e8f0;border-radius:4px;height:8px;overflow:hidden">
                    <div style="background:#f59e0b;width:' . ($dados['scoreDetalhes']['leitura'] ?? 0) * 4 . '%;height:100%"></div>
                </div>
            </div>
        </td>
    </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">📝 Análise Narrativa</div>
    <div class="narrativa">' . implode('', array_map(fn($p) => '<p>' . htmlspecialchars($p) . '</p>', $narrativa)) . '</div>
</div>

<div class="section">
    <div class="section-title">📅 Dados Diários (últimos 30 dias)</div>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th style="text-align:center">Participações</th>
                <th style="text-align:center">Ouvintes Únicos</th>
                <th style="text-align:center">Novos</th>
                <th style="text-align:center">Lidas no Ar</th>
            </tr>
        </thead>
        <tbody>' . $tabelaRows . '</tbody>
    </table>
</div>

<div class="footer">
    Relatório gerado automaticamente pelo sistema PULSO · ' . htmlspecialchars($nomeEstacao) . ' · ' . date('d/m/Y H:i') . '<br>
    <span class="watermark">Confidencial — uso interno</span>
</div>

</div>
</body></html>';

        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4',
            'margin_top'    => 0,
            'margin_bottom' => 10,
            'margin_left'   => 0,
            'margin_right'  => 0,
            'tempDir'       => '/tmp/mpdf_' . $stationId,
        ]);

        @mkdir('/tmp/mpdf_' . $stationId, 0777, true);
        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S'); // Retorna string binária
    }

    // ==================== FINANÇAS ====================

    public function getDadosFinancasDashboard(int $stationId): array
    {
        $mesAtual = date('Y-m');
        $anoAtual = (int)date('Y');
        $mesNum   = (int)date('m');
        $mesAnt   = date('Y-m', strtotime('-1 month'));

        // ── Receitas do mês (fp_contas_movimento tipo=receber) ──────────
        $receitaMes = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='receber'
             AND DATE_FORMAT(COALESCE(data_pagamento, data_emissao),'%Y-%m')=?",
            [$stationId, $mesAtual]
        );

        // ── Despesas do mês ─────────────────────────────────────────────
        $despesaMes = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='pagar'
             AND DATE_FORMAT(COALESCE(data_pagamento, data_emissao),'%Y-%m')=?",
            [$stationId, $mesAtual]
        );

        // ── Mês anterior ────────────────────────────────────────────────
        $receitaAnt = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='receber'
             AND DATE_FORMAT(COALESCE(data_pagamento, data_emissao),'%Y-%m')=?",
            [$stationId, $mesAnt]
        );
        $despesaAnt = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='pagar'
             AND DATE_FORMAT(COALESCE(data_pagamento, data_emissao),'%Y-%m')=?",
            [$stationId, $mesAnt]
        );

        // ── Valores em aberto (pendentes/vencidos) ───────────────────────
        $receitasPendentes = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_total - valor_pago),0) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='receber' AND estado IN ('pendente','vencido')",
            [$stationId]
        );
        $despesasPendentes = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_total - valor_pago),0) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='pagar' AND estado IN ('pendente','vencido')",
            [$stationId]
        );

        // ── Vencidos ────────────────────────────────────────────────────
        $receitasVencidas = (int)$this->db->fetchOne(
            "SELECT COUNT(*) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='receber' AND estado='vencido'",
            [$stationId]
        );
        $despesasVencidas = (int)$this->db->fetchOne(
            "SELECT COUNT(*) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='pagar' AND estado='vencido'",
            [$stationId]
        );

        // ── Saldo bancário real ──────────────────────────────────────────
        $saldoBancario = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(saldo_atual),0) FROM fp_contas_bancarias
             WHERE station_id=? AND ativo=1",
            [$stationId]
        );

        $contasBancarias = $this->db->fetchAllAssociative(
            "SELECT nome, banco, saldo_atual, moeda FROM fp_contas_bancarias
             WHERE station_id=? AND ativo=1 ORDER BY saldo_atual DESC",
            [$stationId]
        );

        // ── Últimos lançamentos (fp_lancamentos) ─────────────────────────
        $ultimosLancamentos = $this->db->fetchAllAssociative(
            "SELECT l.*, cd.nome as conta_debito_nome, cc.nome as conta_credito_nome
             FROM fp_lancamentos l
             LEFT JOIN fp_plano_contas cd ON cd.id = l.conta_debito_id
             LEFT JOIN fp_plano_contas cc ON cc.id = l.conta_credito_id
             WHERE l.station_id=? AND l.estado='confirmado'
             ORDER BY l.data_lancamento DESC LIMIT 8",
            [$stationId]
        );

        // ── Movimentos recentes (contas a pagar/receber) ─────────────────
        $movimentosRecentes = $this->db->fetchAllAssociative(
            "SELECT m.*, p.nome as patrocinador_nome
             FROM fp_contas_movimento m
             LEFT JOIN plugin_pulso_patrocinadores p ON p.id = m.patrocinador_id
             WHERE m.station_id=?
             ORDER BY m.created_at DESC LIMIT 10",
            [$stationId]
        );

        // ── Evolução 6 meses (receitas vs despesas) ──────────────────────
        $evolucao = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = date('Y-m', strtotime("-$i month"));
            $mesLabel = date('M/y', strtotime("-$i month"));
            $rec = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
                 WHERE station_id=? AND tipo='receber'
                 AND DATE_FORMAT(COALESCE(data_pagamento,data_emissao),'%Y-%m')=?",
                [$stationId, $mes]
            );
            $desp = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
                 WHERE station_id=? AND tipo='pagar'
                 AND DATE_FORMAT(COALESCE(data_pagamento,data_emissao),'%Y-%m')=?",
                [$stationId, $mes]
            );
            $evolucao[] = ['mes' => $mesLabel, 'receita' => $rec, 'despesa' => $desp, 'lucro' => $rec - $desp];
        }

        // ── Top patrocinadores ───────────────────────────────────────────
        $topPat = $this->db->fetchAllAssociative(
            "SELECT p.nome, COALESCE(SUM(m.valor_total),0) as total
             FROM plugin_pulso_patrocinadores p
             LEFT JOIN fp_contas_movimento m ON m.patrocinador_id=p.id AND m.tipo='receber'
             WHERE p.station_id=? AND p.ativo=1
             GROUP BY p.id ORDER BY total DESC LIMIT 5",
            [$stationId]
        );

        // ── Meta do mês ──────────────────────────────────────────────────
        $meta = null;
        try {
            $meta = $this->db->fetchAssociative(
                "SELECT * FROM plugin_pulso_metas WHERE station_id=? AND ano=? AND mes=?",
                [$stationId, $anoAtual, $mesNum]
            ) ?: null;
        } catch (\Throwable $e) { $meta = null; }

        // ── Alertas ──────────────────────────────────────────────────────
        $alertas = $this->getFpAlertas($stationId);

        $lucroMes   = $receitaMes - $despesaMes;
        $lucroAnt   = $receitaAnt - $despesaAnt;
        $varReceita = $receitaAnt > 0 ? round(($receitaMes - $receitaAnt) / $receitaAnt * 100) : 0;
        $varDespesa = $despesaAnt > 0 ? round(($despesaMes - $despesaAnt) / $despesaAnt * 100) : 0;
        $margem     = $receitaMes > 0 ? round($lucroMes / $receitaMes * 100) : 0;
        $pctMeta    = ($meta && $meta['meta_receita'] > 0)
                    ? min(100, round($receitaMes / $meta['meta_receita'] * 100)) : 0;

        return [
            'receita_mes'         => $receitaMes,
            'despesa_mes'         => $despesaMes,
            'lucro_mes'           => $lucroMes,
            'receita_ant'         => $receitaAnt,
            'despesa_ant'         => $despesaAnt,
            'lucro_ant'           => $lucroAnt,
            'var_receita'         => $varReceita,
            'var_despesa'         => $varDespesa,
            'margem'              => $margem,
            'receitas_pendentes'  => $receitasPendentes,
            'despesas_pendentes'  => $despesasPendentes,
            'receitas_vencidas'   => $receitasVencidas,
            'despesas_vencidas'   => $despesasVencidas,
            'saldo_bancario'      => $saldoBancario,
            'contas_bancarias'    => $contasBancarias,
            'ultimos_lancamentos' => $ultimosLancamentos,
            'movimentos_recentes' => $movimentosRecentes,
            'evolucao'            => $evolucao,
            'top_patrocinadores'  => $topPat,
            'meta'                => $meta,
            'pct_meta'            => $pctMeta,
            'alertas'             => $alertas,
            'mes_label'           => date('F Y'),
        ];
    }


    public function fpEditarMovimento(int $id, int $stationId, array $d): bool
    {
        $mov = $this->db->fetchAssociative(
            "SELECT * FROM fp_contas_movimento WHERE id=? AND station_id=?",
            [$id, $stationId]
        );
        if (!$mov || $mov['estado'] === 'pago') return false;

        $valorTotal = (float)str_replace(['.', ','], ['', '.'], $d['valor_total'] ?? $mov['valor_total']);

        $this->db->update('fp_contas_movimento', [
            'descricao'        => $d['descricao']        ?? $mov['descricao'],
            'entidade_nome'    => $d['entidade_nome']    ?? $mov['entidade_nome'],
            'valor_total'      => $valorTotal,
            'data_emissao'     => $d['data_emissao']     ?? $mov['data_emissao'],
            'data_vencimento'  => $d['data_vencimento']  ?? $mov['data_vencimento'],
            'metodo_pagamento' => $d['metodo_pagamento'] ?? $mov['metodo_pagamento'],
            'documento_ref'    => $d['documento_ref']    ?? $mov['documento_ref'],
            'patrocinador_id'  => !empty($d['patrocinador_id']) ? (int)$d['patrocinador_id'] : $mov['patrocinador_id'],
            'conta_id'         => !empty($d['conta_id']) ? (int)$d['conta_id'] : $mov['conta_id'],
            'notas'            => $d['notas']            ?? $mov['notas'],
        ], ['id' => $id, 'station_id' => $stationId]);

        return true;
    }

    public function fpCancelarMovimento(int $id, int $stationId, string $motivo = ''): bool
    {
        $mov = $this->db->fetchAssociative(
            "SELECT * FROM fp_contas_movimento WHERE id=? AND station_id=?",
            [$id, $stationId]
        );
        if (!$mov || $mov['estado'] === 'cancelado') return false;

        $this->db->update('fp_contas_movimento', [
            'estado' => 'cancelado',
            'notas'  => ($mov['notas'] ? $mov['notas'] . "\n" : '') . '[Cancelado em ' . date('d/m/Y H:i') . ']' . ($motivo ? ': ' . $motivo : ''),
        ], ['id' => $id, 'station_id' => $stationId]);

        return true;
    }

    public function fpExcluirPatrocinador(int $id, int $stationId): void
    {
        $this->db->update('plugin_pulso_patrocinadores', ['ativo' => 0], ['id' => $id, 'station_id' => $stationId]);
    }

    public function fpExcluirContrato(int $id, int $stationId): void
    {
        $this->db->delete('plugin_pulso_contratos', ['id' => $id, 'station_id' => $stationId]);
    }

    public function getPatrocinadores(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT p.*,
                COUNT(DISTINCT c.id) as total_contratos,
                COALESCE(SUM(r.valor),0) as total_recebido
             FROM plugin_pulso_patrocinadores p
             LEFT JOIN plugin_pulso_contratos c ON c.patrocinador_id=p.id
             LEFT JOIN plugin_pulso_receitas r ON r.patrocinador_id=p.id
             WHERE p.station_id=?
             GROUP BY p.id ORDER BY total_recebido DESC",
            [$stationId]
        );
    }

    public function salvarPatrocinador(int $stationId, array $d, ?int $id=null): int
    {
        $campos = [
            'station_id' => $stationId,
            'nome'       => $d['nome'] ?? '',
            'contacto'   => $d['contacto'] ?? '',
            'telefone'   => $d['telefone'] ?? '',
            'email'      => $d['email'] ?? '',
            'sector'     => $d['sector'] ?? '',
            'notas'      => $d['notas'] ?? '',
            'ativo'      => isset($d['ativo']) ? 1 : 0,
        ];
        if ($id) {
            unset($campos['station_id']);
            $this->db->update('plugin_pulso_patrocinadores', $campos, ['id'=>$id]);
            return $id;
        }
        $this->db->insert('plugin_pulso_patrocinadores', $campos);
        return (int)$this->db->lastInsertId();
    }

    public function salvarReceita(int $stationId, array $d): int
    {
        $this->db->insert('plugin_pulso_receitas', [
            'station_id'     => $stationId,
            'patrocinador_id'=> !empty($d['patrocinador_id']) ? (int)$d['patrocinador_id'] : null,
            'contrato_id'    => !empty($d['contrato_id']) ? (int)$d['contrato_id'] : null,
            'descricao'      => $d['descricao'] ?? '',
            'valor'          => (float)str_replace(['.', ','], ['', '.'], $d['valor'] ?? '0'),
            'data_receita'   => $d['data_receita'] ?? date('Y-m-d'),
            'metodo'         => $d['metodo'] ?? 'transferencia',
            'referencia'     => $d['referencia'] ?? '',
            'notas'          => $d['notas'] ?? '',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function salvarDespesa(int $stationId, array $d): int
    {
        $this->db->insert('plugin_pulso_despesas', [
            'station_id'   => $stationId,
            'categoria_id' => !empty($d['categoria_id']) ? (int)$d['categoria_id'] : null,
            'descricao'    => $d['descricao'] ?? '',
            'valor'        => (float)str_replace(['.', ','], ['', '.'], $d['valor'] ?? '0'),
            'data_despesa' => $d['data_despesa'] ?? date('Y-m-d'),
            'recorrente'   => isset($d['recorrente']) ? 1 : 0,
            'metodo'       => $d['metodo'] ?? 'transferencia',
            'notas'        => $d['notas'] ?? '',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function salvarMeta(int $stationId, array $d): void
    {
        $ano = (int)($d['ano'] ?? date('Y'));
        $mes = (int)($d['mes'] ?? date('m'));
        $this->db->executeStatement(
            "INSERT INTO plugin_pulso_metas (station_id,ano,mes,meta_receita,meta_despesa,notas)
             VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE meta_receita=VALUES(meta_receita), meta_despesa=VALUES(meta_despesa), notas=VALUES(notas)",
            [
                $stationId, $ano, $mes,
                (float)str_replace(['.', ','], ['', '.'], $d['meta_receita'] ?? '0'),
                (float)str_replace(['.', ','], ['', '.'], $d['meta_despesa'] ?? '0'),
                $d['notas'] ?? '',
            ]
        );
    }

    public function getContratos(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT c.*, p.nome as patrocinador_nome,
                    COALESCE(SUM(r.valor),0) as total_recebido
             FROM plugin_pulso_contratos c
             JOIN plugin_pulso_patrocinadores p ON p.id=c.patrocinador_id
             LEFT JOIN plugin_pulso_receitas r ON r.contrato_id=c.id
             WHERE c.station_id=?
             GROUP BY c.id ORDER BY c.created_at DESC",
            [$stationId]
        );
    }

    public function salvarContrato(int $stationId, array $d, ?int $id=null): int
    {
        $campos = [
            'station_id'     => $stationId,
            'patrocinador_id'=> (int)($d['patrocinador_id'] ?? 0),
            'nome'           => $d['nome'] ?? '',
            'tipo'           => $d['tipo'] ?? 'spot',
            'valor_total'    => (float)str_replace(['.', ','], ['', '.'], $d['valor_total'] ?? '0'),
            'data_inicio'    => $d['data_inicio'] ?? date('Y-m-d'),
            'data_fim'       => $d['data_fim'] ?? null,
            'spots_por_dia'  => (int)($d['spots_por_dia'] ?? 0),
            'estado'         => $d['estado'] ?? 'negociacao',
            'notas'          => $d['notas'] ?? '',
        ];
        if ($id) {
            unset($campos['station_id']);
            $this->db->update('plugin_pulso_contratos', $campos, ['id'=>$id]);
            return $id;
        }
        $this->db->insert('plugin_pulso_contratos', $campos);
        return (int)$this->db->lastInsertId();
    }

    public function getCategoriasDespesa(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT * FROM plugin_pulso_despesa_categorias WHERE station_id=? ORDER BY nome",
            [$stationId]
        );
    }

    // ==================== FINANCE PRO ====================

    public function getFpPlanoConta(int $stationId): array
    {
        $contas = $this->db->fetchAllAssociative(
            "SELECT c.*,
                    p.nome as pai_nome,
                    (SELECT COUNT(*) FROM fp_lancamentos l
                     WHERE (l.conta_debito_id=c.id OR l.conta_credito_id=c.id)
                     AND l.station_id=? AND l.estado='confirmado') as total_lancamentos
             FROM fp_plano_contas c
             LEFT JOIN fp_plano_contas p ON p.id=c.conta_pai_id
             WHERE c.station_id=?
             ORDER BY c.codigo ASC",
            [$stationId, $stationId]
        );

        $stats = [
            'total'       => count($contas),
            'ativas'      => count(array_filter($contas, fn($c) => $c['ativo'])),
            'sinteticas'  => count(array_filter($contas, fn($c) => $c['tipo']==='sintetica')),
            'analiticas'  => count(array_filter($contas, fn($c) => $c['tipo']==='analitica')),
        ];

        return ['contas' => $contas, 'stats' => $stats];
    }

    public function getFpContaById(int $id, int $stationId): ?array
    {
        $conta = $this->db->fetchAssociative(
            "SELECT c.*, p.nome as pai_nome, p.codigo as pai_codigo
             FROM fp_plano_contas c
             LEFT JOIN fp_plano_contas p ON p.id=c.conta_pai_id
             WHERE c.id=? AND c.station_id=?",
            [$id, $stationId]
        ) ?: null;

        if (!$conta) return null;

        // Saldo actual (débitos - créditos para contas devedoras, inverso para credoras)
        $debitos = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor),0) FROM fp_lancamentos WHERE conta_debito_id=? AND estado='confirmado' AND station_id=?",
            [$id, $stationId]
        );
        $creditos = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor),0) FROM fp_lancamentos WHERE conta_credito_id=? AND estado='confirmado' AND station_id=?",
            [$id, $stationId]
        );
        $conta['saldo'] = $conta['natureza']==='devedora' ? $debitos - $creditos : $creditos - $debitos;

        // Evolução 6 meses
        $conta['evolucao'] = $this->db->fetchAllAssociative(
            "SELECT DATE_FORMAT(data_lancamento,'%Y-%m') as mes, SUM(valor) as total
             FROM fp_lancamentos
             WHERE (conta_debito_id=? OR conta_credito_id=?) AND estado='confirmado' AND station_id=?
             AND data_lancamento >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY mes ORDER BY mes ASC",
            [$id, $id, $stationId]
        );

        // Subconto
        $conta['filhos'] = $this->db->fetchAllAssociative(
            "SELECT * FROM fp_plano_contas WHERE conta_pai_id=? AND station_id=? ORDER BY codigo",
            [$id, $stationId]
        );

        return $conta;
    }

    public function fpSalvarConta(int $stationId, array $d, ?int $id=null): int
    {
        // Auto-calcular nível baseado no código
        $nivel = substr_count($d['codigo'] ?? '1', '.') ;

        $campos = [
            'station_id'         => $stationId,
            'codigo'             => $d['codigo'] ?? '',
            'nome'               => $d['nome'] ?? '',
            'tipo'               => $d['tipo'] ?? 'analitica',
            'natureza'           => $d['natureza'] ?? 'devedora',
            'classe'             => (int)($d['classe'] ?? 1),
            'conta_pai_id'       => !empty($d['conta_pai_id']) ? (int)$d['conta_pai_id'] : null,
            'nivel'              => $nivel,
            'centro_custo_padrao'=> $d['centro_custo_padrao'] ?? '',
            'ativo'              => isset($d['ativo']) ? 1 : 0,
            'notas'              => $d['notas'] ?? '',
        ];

        if ($id) {
            unset($campos['station_id']);
            $this->db->update('fp_plano_contas', $campos, ['id'=>$id]);
            return $id;
        }
        $this->db->insert('fp_plano_contas', $campos);
        return (int)$this->db->lastInsertId();
    }

    public function getFpCentrosCusto(int $stationId): array
    {
        return $this->db->fetchAllAssociative(
            "SELECT cc.*,
                    COUNT(DISTINCT l.id) as total_lancamentos,
                    COALESCE(SUM(CASE WHEN l.tipo='despesa' THEN l.valor ELSE 0 END),0) as total_despesas,
                    COALESCE(SUM(CASE WHEN l.tipo='receita' THEN l.valor ELSE 0 END),0) as total_receitas
             FROM fp_centros_custo cc
             LEFT JOIN fp_lancamentos l ON l.centro_custo_id=cc.id AND l.station_id=cc.station_id
                 AND DATE_FORMAT(l.data_lancamento,'%Y-%m')=DATE_FORMAT(NOW(),'%Y-%m')
             WHERE cc.station_id=?
             GROUP BY cc.id ORDER BY cc.codigo",
            [$stationId]
        );
    }

    public function getFpDashboard(int $stationId): array
    {
        $mes = date('Y-m');
        $ano = date('Y');

        // Receitas confirmadas (pagas) + pendentes do mês via fp_contas_movimento
        $receitaMes = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='receber'
             AND DATE_FORMAT(COALESCE(data_pagamento, data_emissao),'%Y-%m')=?",
            [$stationId, $mes]
        );
        $despesaMes = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='pagar'
             AND DATE_FORMAT(COALESCE(data_pagamento, data_emissao),'%Y-%m')=?",
            [$stationId, $mes]
        );
        $aReceber = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_total-valor_pago),0) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='receber' AND estado IN ('pendente','parcial','vencido')",
            [$stationId]
        );
        $aPagar = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_total-valor_pago),0) FROM fp_contas_movimento
             WHERE station_id=? AND tipo='pagar' AND estado IN ('pendente','parcial','vencido')",
            [$stationId]
        );
        $vencidosReceber = (int)$this->db->fetchOne(
            "SELECT COUNT(*) FROM fp_contas_movimento WHERE station_id=? AND tipo='receber' AND estado='vencido'",
            [$stationId]
        );
        $vencidosPagar = (int)$this->db->fetchOne(
            "SELECT COUNT(*) FROM fp_contas_movimento WHERE station_id=? AND tipo='pagar' AND estado='vencido'",
            [$stationId]
        );

        // Saldo bancário total
        $saldoBancario = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(saldo_atual),0) FROM fp_contas_bancarias WHERE station_id=? AND ativo=1",
            [$stationId]
        );

        // Últimos lançamentos
        $ultimosLancamentos = $this->db->fetchAllAssociative(
            "SELECT l.*,
                    cd.codigo as cod_debito, cd.nome as nom_debito,
                    cc.codigo as cod_credito, cc.nome as nom_credito,
                    ccu.nome as centro_custo_nome
             FROM fp_lancamentos l
             LEFT JOIN fp_plano_contas cd ON cd.id=l.conta_debito_id
             LEFT JOIN fp_plano_contas cc ON cc.id=l.conta_credito_id
             LEFT JOIN fp_centros_custo ccu ON ccu.id=l.centro_custo_id
             WHERE l.station_id=? AND l.estado='confirmado'
             ORDER BY l.data_lancamento DESC, l.id DESC LIMIT 10",
            [$stationId]
        );

        // Evolução 6 meses
        $evolucao = $this->db->fetchAllAssociative(
            "SELECT DATE_FORMAT(data_lancamento,'%Y-%m') as mes,
                    SUM(CASE WHEN tipo='receita' THEN valor ELSE 0 END) as receitas,
                    SUM(CASE WHEN tipo='despesa' THEN valor ELSE 0 END) as despesas
             FROM fp_lancamentos WHERE station_id=? AND estado='confirmado'
             AND data_lancamento >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY mes ORDER BY mes ASC",
            [$stationId]
        );

        // Despesas por centro de custo
        $despPorCC = $this->db->fetchAllAssociative(
            "SELECT ccu.nome, ccu.codigo, COALESCE(SUM(l.valor),0) as total
             FROM fp_centros_custo ccu
             LEFT JOIN fp_lancamentos l ON l.centro_custo_id=ccu.id
                 AND l.tipo='despesa' AND l.estado='confirmado'
                 AND DATE_FORMAT(l.data_lancamento,'%Y-%m')=?
             WHERE ccu.station_id=?
             GROUP BY ccu.id ORDER BY total DESC",
            [$mes, $stationId]
        );

        // Meta financeira (reutilizar tabela existente)
        $meta = $this->db->fetchAssociative(
            "SELECT * FROM plugin_pulso_metas WHERE station_id=? AND ano=? AND mes=?",
            [$stationId, (int)date('Y'), (int)date('m')]
        ) ?: ['meta_receita'=>0,'meta_despesa'=>0];

        return [
            'receita_mes'         => $receitaMes,
            'despesa_mes'         => $despesaMes,
            'lucro_mes'           => $receitaMes - $despesaMes,
            'a_receber'           => $aReceber,
            'a_pagar'             => $aPagar,
            'vencidos_receber'    => $vencidosReceber,
            'vencidos_pagar'      => $vencidosPagar,
            'saldo_bancario'      => $saldoBancario,
            'ultimos_lancamentos' => $ultimosLancamentos,
            'evolucao'            => $evolucao,
            'desp_por_cc'         => $despPorCC,
            'meta'                => $meta,
            'pct_meta'            => $meta['meta_receita'] > 0 ? min(100, round($receitaMes / $meta['meta_receita'] * 100)) : 0,
            'mes_label'           => date('F Y'),
        ];
    }

    public function getFpLancamentos(int $stationId, array $filtros = []): array
    {
        $where = ["l.station_id = $stationId", "l.estado != 'cancelado'"];
        $params = [];

        if (!empty($filtros['tipo'])) {
            $where[] = "l.tipo = ?"; $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['conta_id'])) {
            $where[] = "(l.conta_debito_id = ? OR l.conta_credito_id = ?)";
            $params[] = $filtros['conta_id']; $params[] = $filtros['conta_id'];
        }
        if (!empty($filtros['centro_id'])) {
            $where[] = "l.centro_custo_id = ?"; $params[] = $filtros['centro_id'];
        }
        if (!empty($filtros['mes'])) {
            $where[] = "DATE_FORMAT(l.data_lancamento,'%Y-%m') = ?"; $params[] = $filtros['mes'];
        }

        $sql = "SELECT l.*,
                    cd.codigo as cod_debito,  cd.nome as nom_debito,
                    cc2.codigo as cod_credito, cc2.nome as nom_credito,
                    ccu.nome as centro_nome, ccu.codigo as centro_codigo,
                    p.nome as patrocinador_nome
                FROM fp_lancamentos l
                LEFT JOIN fp_plano_contas cd  ON cd.id  = l.conta_debito_id
                LEFT JOIN fp_plano_contas cc2 ON cc2.id = l.conta_credito_id
                LEFT JOIN fp_centros_custo ccu ON ccu.id = l.centro_custo_id
                LEFT JOIN plugin_pulso_patrocinadores p ON p.id = l.patrocinador_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY l.data_lancamento DESC, l.id DESC
                LIMIT 200";

        $lancamentos = $this->db->fetchAllAssociative($sql, $params);

        // Totais
        $totalReceitas = array_sum(array_map(
            fn($l) => $l['tipo'] === 'receita' ? (float)$l['valor'] : 0, $lancamentos
        ));
        $totalDespesas = array_sum(array_map(
            fn($l) => $l['tipo'] === 'despesa' ? (float)$l['valor'] : 0, $lancamentos
        ));

        return [
            'lancamentos'   => $lancamentos,
            'total_receitas'=> $totalReceitas,
            'total_despesas'=> $totalDespesas,
            'saldo'         => $totalReceitas - $totalDespesas,
            'total'         => count($lancamentos),
        ];
    }

    public function fpSalvarLancamento(int $stationId, array $d): int
    {
        // Gerar número sequencial
        $ultimo = $this->db->fetchOne(
            "SELECT MAX(CAST(SUBSTRING(numero,5) AS UNSIGNED)) FROM fp_lancamentos WHERE station_id=?",
            [$stationId]
        ) ?? 0;
        $numero = 'LAN-' . str_pad((int)$ultimo + 1, 5, '0', STR_PAD_LEFT);

        $id = 0;
        $this->db->insert('fp_lancamentos', [
            'station_id'      => $stationId,
            'numero'          => $numero,
            'data_lancamento' => $d['data_lancamento'] ?? date('Y-m-d'),
            'data_competencia'=> $d['data_competencia'] ?? $d['data_lancamento'] ?? date('Y-m-d'),
            'historico'       => $d['historico'] ?? '',
            'conta_debito_id' => (int)($d['conta_debito_id'] ?? 0),
            'conta_credito_id'=> (int)($d['conta_credito_id'] ?? 0),
            'centro_custo_id' => !empty($d['centro_custo_id']) ? (int)$d['centro_custo_id'] : null,
            'valor'           => (float)str_replace(['.', ','], ['', '.'], $d['valor'] ?? '0'),
            'tipo'            => $d['tipo'] ?? 'receita',
            'documento_ref'   => !empty($d['documento_ref'])
                ? $d['documento_ref']
                : (($d['tipo']??'pagar') === 'receber' ? 'REC' : 'PAG')
                  . '-' . date('Y')
                  . '-' . str_pad((int)$this->db->fetchOne("SELECT COUNT(*)+1 FROM fp_contas_movimento WHERE station_id=? AND tipo=?", [$stationId, $d['tipo']??'pagar']), 4, '0', STR_PAD_LEFT),
            'patrocinador_id' => !empty($d['patrocinador_id']) ? (int)$d['patrocinador_id'] : null,
            'estado'          => 'confirmado',
            'created_by'      => 'Newton dos Santos',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function fpCancelarLancamento(int $id, int $stationId): void
    {
        $this->db->update('fp_lancamentos', ['estado'=>'cancelado'], ['id'=>$id,'station_id'=>$stationId]);
    }

    public function getFpMovimentos(int $stationId, string $tipo, array $filtros = []): array
    {
        // Actualizar estados vencidos automaticamente
        $this->db->executeStatement(
            "UPDATE fp_contas_movimento
             SET estado = 'vencido'
             WHERE station_id = ? AND tipo = ? AND estado = 'pendente'
             AND data_vencimento < CURDATE()",
            [$stationId, $tipo]
        );

        $where = ["m.station_id = $stationId", "m.tipo = '$tipo'", "m.estado != 'cancelado'"];
        if (!empty($filtros['estado'])) {
            $where[] = "m.estado = '{$filtros['estado']}'";
        }
        if (!empty($filtros['mes'])) {
            $where[] = "DATE_FORMAT(m.data_vencimento,'%Y-%m') = '{$filtros['mes']}'";
        }

        $movimentos = $this->db->fetchAllAssociative(
            "SELECT m.*,
                    p.nome as patrocinador_nome,
                    c.nome as conta_nome, c.codigo as conta_codigo
             FROM fp_contas_movimento m
             LEFT JOIN plugin_pulso_patrocinadores p ON p.id = m.patrocinador_id
             LEFT JOIN fp_plano_contas c ON c.id = m.conta_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY m.data_vencimento ASC, m.id DESC",
            []
        );

        $totais = [
            'pendente' => 0, 'parcial' => 0,
            'pago'     => 0, 'vencido' => 0,
        ];
        $totalGeral = 0;
        $totalPago  = 0;

        foreach ($movimentos as $m) {
            $restante = (float)$m['valor_total'] - (float)$m['valor_pago'];
            if (isset($totais[$m['estado']])) {
                $totais[$m['estado']] += $restante;
            }
            $totalGeral += (float)$m['valor_total'];
            $totalPago  += (float)$m['valor_pago'];
        }

        return [
            'movimentos'  => $movimentos,
            'totais'      => $totais,
            'total_geral' => $totalGeral,
            'total_pago'  => $totalPago,
            'total_pendente' => $totais['pendente'] + $totais['parcial'] + $totais['vencido'],
            'count_vencidos' => count(array_filter($movimentos, fn($m) => $m['estado'] === 'vencido')),
        ];
    }

    public function fpSalvarMovimento(int $stationId, array $d): int
    {
        $valorTotal = (float)str_replace(['.', ','], ['', '.'], $d['valor_total'] ?? '0');
        $numParcelas = max(1, (int)($d['num_parcelas'] ?? 1));

        $this->db->insert('fp_contas_movimento', [
            'station_id'      => $stationId,
            'tipo'            => $d['tipo'] ?? 'pagar',
            'descricao'       => $d['descricao'] ?? '',
            'entidade_nome'   => $d['entidade_nome'] ?? '',
            'conta_id'        => !empty($d['conta_id']) ? (int)$d['conta_id'] : null,
            'patrocinador_id' => !empty($d['patrocinador_id']) ? (int)$d['patrocinador_id'] : null,
            'valor_total'     => $valorTotal,
            'valor_pago'      => 0,
            'num_parcelas'    => $numParcelas,
            'data_emissao'    => $d['data_emissao'] ?? date('Y-m-d'),
            'data_vencimento' => $d['data_vencimento'] ?? date('Y-m-d'),
            'documento_ref'   => $d['documento_ref'] ?? '',
            'estado'          => 'pendente',
            'metodo_pagamento'=> $d['metodo_pagamento'] ?? 'transferencia',
            'banco'           => $d['banco'] ?? '',
            'notas'           => $d['notas'] ?? '',
        ]);
        $id = (int)$this->db->lastInsertId();

        // Gerar parcelas se necessário
        if ($numParcelas > 1) {
            $valorParcela = round($valorTotal / $numParcelas, 2);
            for ($i = 1; $i <= $numParcelas; $i++) {
                $dataVenc = date('Y-m-d', strtotime($d['data_vencimento'] . " +{$i} months"));
                $this->db->insert('fp_parcelas', [
                    'movimento_id'   => $id,
                    'numero'         => $i,
                    'valor'          => $i === $numParcelas
                        ? $valorTotal - ($valorParcela * ($numParcelas - 1))
                        : $valorParcela,
                    'data_vencimento'=> $dataVenc,
                    'estado'         => 'pendente',
                ]);
            }
        }

        return $id;
    }

    public function fpBaixarMovimento(int $id, int $stationId, array $d): void
    {
        $movimento = $this->db->fetchAssociative(
            "SELECT * FROM fp_contas_movimento WHERE id=? AND station_id=?",
            [$id, $stationId]
        );
        if (!$movimento) return;

        $valorBaixa = (float)str_replace(['.', ','], ['', '.'], $d['valor_pago'] ?? '0');
        $novoPago   = (float)$movimento['valor_pago'] + $valorBaixa;
        $novoEstado = $novoPago >= (float)$movimento['valor_total'] ? 'pago' : 'parcial';

        $this->db->update('fp_contas_movimento', [
            'valor_pago'      => $novoPago,
            'estado'          => $novoEstado,
            'data_pagamento'  => $d['data_pagamento'] ?? date('Y-m-d'),
            'metodo_pagamento'=> $d['metodo_pagamento'] ?? 'transferencia',
        ], ['id' => $id]);

        // Lançamento automático
        if ($novoEstado === 'pago') {
            $tipoLanc = $movimento['tipo'] === 'receber' ? 'receita' : 'despesa';
            $this->fpSalvarLancamento($stationId, [
                'historico'       => 'Baixa automática: ' . $movimento['descricao'],
                'data_lancamento' => $d['data_pagamento'] ?? date('Y-m-d'),
                'valor'           => $movimento['valor_total'],
                'tipo'            => $tipoLanc,
                'conta_debito_id' => $movimento['conta_id'] ?? 0,
                'conta_credito_id'=> $movimento['conta_id'] ?? 0,
                'documento_ref'   => $movimento['documento_ref'] ?? '',
                'patrocinador_id' => $movimento['patrocinador_id'],
            ]);
        }
    }

    public function getFpContaCorrente(int $stationId): array
    {
        $contas = $this->db->fetchAllAssociative(
            "SELECT * FROM fp_contas_bancarias WHERE station_id=? AND ativo=1 ORDER BY id",
            [$stationId]
        );

        // Recalcular saldo actual de cada conta
        foreach ($contas as &$conta) {
            $creditos = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor),0) FROM fp_movimentos_bancarios WHERE conta_bancaria_id=? AND tipo='credito'",
                [$conta['id']]
            );
            $debitos = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor),0) FROM fp_movimentos_bancarios WHERE conta_bancaria_id=? AND tipo='debito'",
                [$conta['id']]
            );
            $conta['saldo_atual'] = (float)$conta['saldo_inicial'] + $creditos - $debitos;
            $conta['total_creditos'] = $creditos;
            $conta['total_debitos']  = $debitos;

            // Movimentos recentes
            $conta['movimentos'] = $this->db->fetchAllAssociative(
                "SELECT * FROM fp_movimentos_bancarios WHERE conta_bancaria_id=?
                 ORDER BY data_movimento DESC, id DESC LIMIT 30",
                [$conta['id']]
            );
        }

        $saldoTotal = array_sum(array_column($contas, 'saldo_atual'));

        // Evolução saldo 6 meses (todos os bancos)
        $evolucao = $this->db->fetchAllAssociative(
            "SELECT DATE_FORMAT(data_movimento,'%Y-%m') as mes,
                    SUM(CASE WHEN tipo='credito' THEN valor ELSE 0 END) as entradas,
                    SUM(CASE WHEN tipo='debito'  THEN valor ELSE 0 END) as saidas
             FROM fp_movimentos_bancarios mb
             JOIN fp_contas_bancarias cb ON cb.id=mb.conta_bancaria_id
             WHERE cb.station_id=? AND mb.data_movimento >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY mes ORDER BY mes ASC",
            [$stationId]
        );

        return [
            'contas'      => $contas,
            'saldo_total' => $saldoTotal,
            'evolucao'    => $evolucao,
        ];
    }

    public function fpRegistarMovimentoBancario(int $stationId, array $d): void
    {
        $contaId = (int)$d['conta_bancaria_id'];
        $valor   = (float)str_replace(['.', ','], ['', '.'], $d['valor'] ?? '0');
        $tipo    = $d['tipo'] ?? 'credito';

        // Buscar saldo actual
        $creditos = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor),0) FROM fp_movimentos_bancarios WHERE conta_bancaria_id=? AND tipo='credito'",
            [$contaId]
        );
        $debitos = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor),0) FROM fp_movimentos_bancarios WHERE conta_bancaria_id=? AND tipo='debito'",
            [$contaId]
        );
        $conta = $this->db->fetchAssociative(
            "SELECT saldo_inicial FROM fp_contas_bancarias WHERE id=?", [$contaId]
        );
        $saldoActual = (float)$conta['saldo_inicial'] + $creditos - $debitos;
        $saldoApos   = $tipo === 'credito' ? $saldoActual + $valor : $saldoActual - $valor;

        $this->db->insert('fp_movimentos_bancarios', [
            'conta_bancaria_id' => $contaId,
            'station_id'        => $stationId,
            'data_movimento'    => $d['data_movimento'] ?? date('Y-m-d'),
            'descricao'         => $d['descricao'] ?? '',
            'tipo'              => $tipo,
            'valor'             => $valor,
            'saldo_apos'        => $saldoApos,
            'referencia'        => $d['referencia'] ?? '',
            'conciliado'        => 0,
        ]);

        // Actualizar saldo na tabela principal
        $this->db->update('fp_contas_bancarias', ['saldo_atual' => $saldoApos], ['id' => $contaId]);
    }

    // ===== COMISSÕES =====
    public function getFpComissoes(int $stationId): array
    {
        $comissoes = $this->db->fetchAllAssociative(
            "SELECT c.*,
                    ct.nome as contrato_nome,
                    p.nome as patrocinador_nome
             FROM fp_comissoes c
             LEFT JOIN plugin_pulso_contratos ct ON ct.id=c.contrato_id
             LEFT JOIN plugin_pulso_patrocinadores p ON p.id=c.patrocinador_id
             WHERE c.station_id=?
             ORDER BY c.mes_referencia DESC, c.id DESC",
            [$stationId]
        );

        // Stats
        $pendente = array_sum(array_map(fn($c)=>$c['estado']==='pendente'?(float)$c['valor_comissao']:0, $comissoes));
        $pago     = array_sum(array_map(fn($c)=>$c['estado']==='pago'?(float)$c['valor_comissao']:0, $comissoes));

        // Por executivo
        $porExecutivo = [];
        foreach ($comissoes as $c) {
            $nome = $c['executivo_nome'];
            if (!isset($porExecutivo[$nome])) $porExecutivo[$nome] = ['nome'=>$nome,'total'=>0,'pendente'=>0,'pago'=>0,'count'=>0];
            $porExecutivo[$nome]['total']   += (float)$c['valor_comissao'];
            $porExecutivo[$nome]['count']++;
            if ($c['estado']==='pendente') $porExecutivo[$nome]['pendente'] += (float)$c['valor_comissao'];
            if ($c['estado']==='pago')     $porExecutivo[$nome]['pago']     += (float)$c['valor_comissao'];
        }
        usort($porExecutivo, fn($a,$b) => $b['total'] <=> $a['total']);

        return ['comissoes'=>$comissoes,'pendente'=>$pendente,'pago'=>$pago,'por_executivo'=>$porExecutivo];
    }

    public function fpSalvarComissao(int $stationId, array $d): int
    {
        $valorContrato  = (float)str_replace(['.', ','], ['', '.'], $d['valor_contrato'] ?? '0');
        $percentagem    = (float)($d['percentagem'] ?? 10);
        $valorComissao  = !empty($d['valor_comissao'])
            ? (float)str_replace(['.', ','], ['', '.'], $d['valor_comissao'])
            : round($valorContrato * $percentagem / 100, 2);

        $this->db->insert('fp_comissoes', [
            'station_id'     => $stationId,
            'executivo_nome' => $d['executivo_nome'] ?? '',
            'contrato_id'    => !empty($d['contrato_id']) ? (int)$d['contrato_id'] : null,
            'patrocinador_id'=> !empty($d['patrocinador_id']) ? (int)$d['patrocinador_id'] : null,
            'valor_contrato' => $valorContrato,
            'percentagem'    => $percentagem,
            'valor_comissao' => $valorComissao,
            'mes_referencia' => $d['mes_referencia'] ?? date('Y-m-01'),
            'estado'         => 'pendente',
            'notas'          => $d['notas'] ?? '',
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function fpPagarComissao(int $id, int $stationId): void
    {
        $this->db->update('fp_comissoes', [
            'estado'          => 'pago',
            'data_pagamento'  => date('Y-m-d'),
        ], ['id'=>$id, 'station_id'=>$stationId]);
    }

    // ===== FLUXO DE CAIXA =====
    public function getFpFluxoCaixa(int $stationId, int $meses = 6): array
    {
        $fluxo = [];
        for ($i = -2; $i <= $meses; $i++) {
            $data   = date('Y-m-01', strtotime("$i months"));
            $mesFmt = date('Y-m', strtotime("$i months"));
            $mesPt  = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                       'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][(int)date('m', strtotime("$i months"))-1];

            $receitas = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
                 WHERE station_id=? AND tipo='receber'
                 AND DATE_FORMAT(COALESCE(data_pagamento,data_emissao),'%Y-%m')=?",
                [$stationId, $mesFmt]
            );
            $despesas = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
                 WHERE station_id=? AND tipo='pagar'
                 AND DATE_FORMAT(COALESCE(data_pagamento,data_emissao),'%Y-%m')=?",
                [$stationId, $mesFmt]
            );
            $aReceber = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor_total-valor_pago),0) FROM fp_contas_movimento
                 WHERE station_id=? AND tipo='receber' AND estado IN ('pendente','parcial')
                 AND DATE_FORMAT(data_vencimento,'%Y-%m')=?",
                [$stationId, $mesFmt]
            );
            $aPagar = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor_total-valor_pago),0) FROM fp_contas_movimento
                 WHERE station_id=? AND tipo='pagar' AND estado IN ('pendente','parcial')
                 AND DATE_FORMAT(data_vencimento,'%Y-%m')=?",
                [$stationId, $mesFmt]
            );

            $fluxo[] = [
                'mes'         => $mesFmt,
                'mes_label'   => $mesPt . ' ' . date('Y', strtotime("$i months")),
                'mes_curto'   => substr($mesPt, 0, 3),
                'realizado'   => $i < 0,
                'atual'       => $i === 0,
                'receitas'    => $receitas,
                'despesas'    => $despesas,
                'saldo'       => $receitas - $despesas,
                'a_receber'   => $aReceber,
                'a_pagar'     => $aPagar,
                'previsto'    => $aReceber - $aPagar,
            ];
        }

        // Saldo acumulado
        $acumulado = 0;
        foreach ($fluxo as &$f) {
            $acumulado += $f['saldo'];
            $f['acumulado'] = $acumulado;
        }

        return $fluxo;
    }

    // ===== DRE =====
    public function getFpDre(int $stationId, int $ano = 0): array
    {
        if (!$ano) $ano = (int)date('Y');

        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $mesFmt = sprintf('%04d-%02d', $ano, $m);
            $mesPt  = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
                       'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][$m-1];

            $receita = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
                 WHERE station_id=? AND tipo='receber'
                 AND DATE_FORMAT(COALESCE(data_pagamento,data_emissao),'%Y-%m')=?",
                [$stationId, $mesFmt]
            );
            $despesa = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor_total),0) FROM fp_contas_movimento
                 WHERE station_id=? AND tipo='pagar'
                 AND DATE_FORMAT(COALESCE(data_pagamento,data_emissao),'%Y-%m')=?",
                [$stationId, $mesFmt]
            );
            $comissoes = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor_comissao),0) FROM fp_comissoes
                 WHERE station_id=? AND DATE_FORMAT(mes_referencia,'%Y-%m')=?",
                [$stationId, $mesFmt]
            );

            $lucroBruto = $receita - $despesa;
            $ebitda     = $lucroBruto - $comissoes;

            $meses[] = [
                'mes'         => $mesFmt,
                'mes_label'   => $mesPt,
                'mes_curto'   => substr($mesPt, 0, 3),
                'receita'     => $receita,
                'despesa'     => $despesa,
                'comissoes'   => $comissoes,
                'lucro_bruto' => $lucroBruto,
                'ebitda'      => $ebitda,
                'margem'      => $receita > 0 ? round($ebitda / $receita * 100, 1) : 0,
            ];
        }

        // Totais anuais
        $totais = [
            'receita'     => array_sum(array_column($meses, 'receita')),
            'despesa'     => array_sum(array_column($meses, 'despesa')),
            'comissoes'   => array_sum(array_column($meses, 'comissoes')),
            'lucro_bruto' => array_sum(array_column($meses, 'lucro_bruto')),
            'ebitda'      => array_sum(array_column($meses, 'ebitda')),
        ];
        $totais['margem'] = $totais['receita'] > 0
            ? round($totais['ebitda'] / $totais['receita'] * 100, 1) : 0;

        return ['meses'=>$meses, 'totais'=>$totais, 'ano'=>$ano];
    }

    public function getFpAlertas(int $stationId): array
    {
        $alertas = [];

        // Contas vencidas a pagar
        $vencPagar = $this->db->fetchAllAssociative(
            "SELECT descricao, valor_total-valor_pago as restante, data_vencimento
             FROM fp_contas_movimento
             WHERE station_id=? AND tipo='pagar' AND estado='vencido'
             ORDER BY data_vencimento ASC LIMIT 5",
            [$stationId]
        );
        foreach($vencPagar as $v) {
            $dias = (int)((time() - strtotime($v['data_vencimento'])) / 86400);
            $alertas[] = ['tipo'=>'danger','icone'=>'exclamation-triangle','titulo'=>'Conta vencida há '.$dias.' dia'.($dias!==1?'s':''),'msg'=>$v['descricao'].' · '.number_format($v['restante'],2,',','.').' Kz','link'=>'contas-pagar'];
        }

        // Vencimentos próximos (7 dias)
        $proximos = $this->db->fetchAllAssociative(
            "SELECT descricao, valor_total-valor_pago as restante, data_vencimento
             FROM fp_contas_movimento
             WHERE station_id=? AND tipo='pagar' AND estado='pendente'
             AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             ORDER BY data_vencimento ASC LIMIT 3",
            [$stationId]
        );
        foreach($proximos as $v) {
            $dias = (int)((strtotime($v['data_vencimento']) - time()) / 86400);
            $alertas[] = ['tipo'=>'warning','icone'=>'clock','titulo'=>'Vence em '.$dias.' dia'.($dias!==1?'s':''),'msg'=>$v['descricao'].' · '.number_format($v['restante'],2,',','.').' Kz','link'=>'contas-pagar'];
        }

        // Meta do mês em risco
        $meta = $this->db->fetchAssociative(
            "SELECT meta_receita FROM plugin_pulso_metas WHERE station_id=? AND ano=? AND mes=?",
            [$stationId, (int)date('Y'), (int)date('m')]
        );
        if ($meta && $meta['meta_receita'] > 0) {
            $receitaMes = (float)$this->db->fetchOne(
                "SELECT COALESCE(SUM(valor),0) FROM fp_lancamentos WHERE station_id=? AND tipo='receita' AND estado='confirmado' AND DATE_FORMAT(data_lancamento,'%Y-%m')=?",
                [$stationId, date('Y-m')]
            );
            $pct = round($receitaMes / $meta['meta_receita'] * 100);
            if ($pct < 50) {
                $alertas[] = ['tipo'=>'danger','icone'=>'graph-down','titulo'=>"Meta apenas a {$pct}%",'msg'=>'Receita actual: '.number_format($receitaMes,2,',','.').' Kz de '.number_format($meta['meta_receita'],2,',','.').' Kz','link'=>''];
            } elseif ($pct < 80) {
                $alertas[] = ['tipo'=>'warning','icone'=>'bullseye','titulo'=>"Meta a {$pct}% — atenção",'msg'=>'Faltam '.number_format($meta['meta_receita']-$receitaMes,2,',','.').' Kz para atingir a meta','link'=>''];
            }
        }

        // Comissões pendentes
        $comPend = (float)$this->db->fetchOne(
            "SELECT COALESCE(SUM(valor_comissao),0) FROM fp_comissoes WHERE station_id=? AND estado='pendente'",
            [$stationId]
        );
        if ($comPend > 0) {
            $nCom = (int)$this->db->fetchOne(
                "SELECT COUNT(*) FROM fp_comissoes WHERE station_id=? AND estado='pendente'", [$stationId]
            );
            $alertas[] = ['tipo'=>'info','icone'=>'person-check','titulo'=>"{$nCom} comissão".($nCom!==1?'ões':'')." pendente".($nCom!==1?'s':''),'msg'=>'Total: '.number_format($comPend,2,',','.').' Kz por pagar','link'=>'comissoes'];
        }

        return $alertas;
    }

    public function gerarPdfFinancas(int $stationId, string $tipo, array $params = []): string
    {
        require_once __DIR__ . '/../../vendor-plugin/autoload.php';

        @mkdir('/tmp/mpdf_fp', 0777, true);
        $formato = ($tipo === 'extracto') ? 'A4' : 'A4-L';
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => $formato,
            'margin_top'    => 0,
            'margin_bottom' => 8,
            'margin_left'   => 0,
            'margin_right'  => 0,
            'tempDir'       => '/tmp/mpdf_fp',
        ]);

        $station = $this->db->fetchAssociative("SELECT name FROM station WHERE id=?", [$stationId]);
        $nomeSt  = $station['name'] ?? 'Rádio New Band';
        $gerado  = date('d/m/Y H:i');
        $meses_pt = ['January'=>'Janeiro','February'=>'Fevereiro','March'=>'Março',
                     'April'=>'Abril','May'=>'Maio','June'=>'Junho','July'=>'Julho',
                     'August'=>'Agosto','September'=>'Setembro','October'=>'Outubro',
                     'November'=>'Novembro','December'=>'Dezembro'];

        $fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';
        $fmtKzS = fn($v) => ($v >= 1000000 ? number_format($v/1000000, 1, ',', '.').'M' : ($v >= 1000 ? number_format($v/1000, 0, ',', '.').'K' : number_format($v, 2, ',', '.'))) . ' Kz';

        $css = '
        body{font-family:Arial,sans-serif;font-size:9pt;color:#1a1a2e;margin:0}
        .header{background:linear-gradient(135deg,#0f0f1f,#1a1a2e);color:white;padding:20px 28px;margin-bottom:0}
        .header h1{font-size:16pt;margin:0 0 4px 0;font-weight:900}
        .header .sub{font-size:9pt;color:#a0aec0}
        .header .right{text-align:right;color:#a0aec0;font-size:8pt}
        .body{padding:20px 28px}
        .section-title{font-size:11pt;font-weight:700;color:#0f0f1f;border-bottom:2px solid #10b981;padding-bottom:5px;margin:16px 0 10px}
        table{width:100%;border-collapse:collapse;font-size:8pt}
        table thead th{background:#1a1a2e;color:white;padding:7px 10px;text-align:left;font-size:7.5pt;text-transform:uppercase;letter-spacing:0.5px}
        table thead th.right{text-align:right}
        table tbody td{padding:6px 10px;border-bottom:1px solid #e2e8f0;vertical-align:middle}
        table tbody tr:nth-child(even) td{background:#f8fafc}
        table tbody tr.bold td{font-weight:700;background:#f1f5f9}
        table tbody tr.sep td{border-top:2px solid #cbd5e0}
        table tbody tr.total td{background:#e2e8f0;font-weight:900;font-size:9pt}
        .right{text-align:right}
        .green{color:#059669;font-weight:700}
        .red{color:#dc2626;font-weight:700}
        .blue{color:#2563eb;font-weight:700}
        .cyan{color:#0891b2;font-weight:700}
        .gold{color:#d97706;font-weight:700}
        .purple{color:#7c3aed;font-weight:700}
        .gray{color:#6b7280}
        .kpi-grid{display:grid}
        .kpi-box{border:1px solid #e2e8f0;border-radius:6px;padding:12px;text-align:center;margin:0 4px}
        .kpi-val{font-size:14pt;font-weight:900}
        .kpi-lbl{font-size:7pt;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin-top:3px}
        .footer{border-top:1px solid #e2e8f0;padding:8px 28px;font-size:7pt;color:#6b7280;display:flex}
        .watermark{color:#d1d5db}
        .badge{display:inline-block;padding:2px 7px;border-radius:3px;font-size:7pt;font-weight:700}
        .badge-green{background:#d1fae5;color:#059669}
        .badge-red{background:#fee2e2;color:#dc2626}
        .badge-gold{background:#fef3c7;color:#d97706}
        .badge-gray{background:#f3f4f6;color:#6b7280}
        ';

        if ($tipo === 'dre') {
            $ano   = (int)($params['ano'] ?? date('Y'));
            $dados = $this->getFpDre($stationId, $ano);
            $meses = $dados['meses'];
            $tot   = $dados['totais'];

            $linhas = [
                ['key'=>'receita',    'label'=>'(+) Receita Bruta',          'cls'=>'green', 'bold'=>true,  'sep'=>false],
                ['key'=>'despesa',    'label'=>'(-) Custos Operacionais',    'cls'=>'red',   'bold'=>false, 'sep'=>false],
                ['key'=>'lucro_bruto','label'=>'= Lucro Bruto',              'cls'=>'cyan',  'bold'=>true,  'sep'=>true],
                ['key'=>'comissoes',  'label'=>'(-) Comissões de Vendas',    'cls'=>'gold',  'bold'=>false, 'sep'=>false],
                ['key'=>'ebitda',     'label'=>'= EBITDA',                   'cls'=>'purple','bold'=>true,  'sep'=>true],
                ['key'=>'margem',     'label'=>'Margem Líquida (%)',         'cls'=>'gray',  'bold'=>false, 'sep'=>false, 'pct'=>true],
            ];

            // Cabeçalhos curtos
            $thMeses = '';
            foreach($meses as $m) $thMeses .= '<th class="right">' . $m['mes_curto'] . '</th>';

            $rows = '';
            foreach ($linhas as $ln) {
                $rowCls = ($ln['sep']?'sep ':'').($ln['bold']?'bold ':'');
                $tds = '';
                foreach($meses as $m) {
                    $val = (float)($m[$ln['key']] ?? 0);
                    $display = !empty($ln['pct']) ? $val.'%' : ($val != 0 ? $fmtKzS($val) : '—');
                    $cor = !empty($ln['pct']) ? ($val>=0?'green':'red') : (($val>=0)?$ln['cls']:'red');
                    $tds .= '<td class="right ' . $cor . '">' . $display . '</td>';
                }
                $tval = (float)($tot[$ln['key']] ?? 0);
                $tdisplay = !empty($ln['pct']) ? $tval.'%' : $fmtKzS($tval);
                $rows .= '<tr class="' . $rowCls . '">'
                    . '<td class="' . $ln['cls'] . '">' . $ln['label'] . '</td>'
                    . $tds
                    . '<td class="right ' . $ln['cls'] . '" style="border-left:2px solid #cbd5e0;font-weight:900">' . $tdisplay . '</td>'
                    . '</tr>';
            }

            $kpiHtml = '
            <table style="width:100%;margin-bottom:16px">
            <tr>
                <td style="width:25%;padding:0 4px 0 0"><div class="kpi-box"><div class="kpi-val green">'.$fmtKzS($tot['receita']).'</div><div class="kpi-lbl">Receita Bruta</div></div></td>
                <td style="width:25%;padding:0 4px"><div class="kpi-box"><div class="kpi-val red">'.$fmtKzS($tot['despesa']+$tot['comissoes']).'</div><div class="kpi-lbl">Custos Totais</div></div></td>
                <td style="width:25%;padding:0 4px"><div class="kpi-box"><div class="kpi-val '.($tot['ebitda']>=0?'cyan':'red').'">'.$fmtKzS($tot['ebitda']).'</div><div class="kpi-lbl">EBITDA</div></div></td>
                <td style="width:25%;padding:0 0 0 4px"><div class="kpi-box"><div class="kpi-val purple">'.$tot['margem'].'%</div><div class="kpi-lbl">Margem Líquida</div></div></td>
            </tr>
            </table>';

            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'.$css.'</style></head><body>
            <div class="header">
                <table style="width:100%"><tr>
                    <td><h1>📊 Demonstração de Resultados</h1><div class="sub">'.$nomeSt.' · Exercício '.$ano.'</div></td>
                    <td class="right">Gerado em '.$gerado.'<br><span style="color:#00e5ff">Finance Pro · Rádio New Band Angola</span></td>
                </tr></table>
            </div>
            <div class="body">
            '.$kpiHtml.'
            <div class="section-title">DRE Mensal — Exercício '.$ano.'</div>
            <table>
                <thead><tr><th>Indicador</th>'.$thMeses.'<th class="right" style="border-left:2px solid rgba(255,255,255,.3)">TOTAL</th></tr></thead>
                <tbody>'.$rows.'</tbody>
            </table>
            </div>
            <div class="footer"><span>Finance Pro · '.$nomeSt.'</span><span style="margin-left:auto" class="watermark">Confidencial — uso interno</span></div>
            </body></html>';

            $mpdf->SetTitle('DRE '.$ano.' — '.$nomeSt);

        } elseif ($tipo === 'fluxo') {
            $fluxo = $this->getFpFluxoCaixa($stationId, 9);

            $rows = '';
            foreach ($fluxo as $f) {
                $saldoCls = (float)$f['saldo'] >= 0 ? 'green' : 'red';
                $acumCls  = (float)$f['acumulado'] >= 0 ? 'cyan' : 'red';
                $rowCls   = $f['atual'] ? 'bold' : ($f['realizado'] ? '' : '');
                $actualBadge = $f['atual'] ? '<span class="badge badge-green">ACTUAL</span>' : ($f['realizado'] ? '' : '<span class="badge badge-gray">prev.</span>');
                $rows .= '<tr class="'.$rowCls.'">
                    <td>'.$f['mes_label'].' '.$actualBadge.'</td>
                    <td class="right green">'.($f['receitas']>0?$fmtKz($f['receitas']):'—').'</td>
                    <td class="right red">'.($f['despesas']>0?$fmtKz($f['despesas']):'—').'</td>
                    <td class="right '.$saldoCls.'">'.($f['saldo']!=0?$fmtKz($f['saldo']):'—').'</td>
                    <td class="right green">'.($f['a_receber']>0?$fmtKz($f['a_receber']):'—').'</td>
                    <td class="right red">'.($f['a_pagar']>0?$fmtKz($f['a_pagar']):'—').'</td>
                    <td class="right '.$acumCls.'" style="font-weight:900;border-left:2px solid #cbd5e0">'.$fmtKz($f['acumulado']).'</td>
                </tr>';
            }

            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'.$css.'</style></head><body>
            <div class="header">
                <table style="width:100%"><tr>
                    <td><h1>💸 Fluxo de Caixa</h1><div class="sub">'.$nomeSt.' · '.strtr(date('F Y'), $meses_pt).'</div></td>
                    <td class="right">Gerado em '.$gerado.'<br><span style="color:#00e5ff">Finance Pro · Rádio New Band Angola</span></td>
                </tr></table>
            </div>
            <div class="body">
            <div class="section-title">Mapa de Entradas e Saídas</div>
            <table>
                <thead><tr>
                    <th>Período</th>
                    <th class="right">Receitas</th>
                    <th class="right">Despesas</th>
                    <th class="right">Saldo</th>
                    <th class="right">A Receber</th>
                    <th class="right">A Pagar</th>
                    <th class="right" style="border-left:2px solid rgba(255,255,255,.3)">Acumulado</th>
                </tr></thead>
                <tbody>'.$rows.'</tbody>
            </table>
            </div>
            <div class="footer"><span>Finance Pro · '.$nomeSt.'</span><span style="margin-left:auto" class="watermark">Confidencial</span></div>
            </body></html>';

            $mpdf->SetTitle('Fluxo de Caixa — '.$nomeSt);

        } elseif ($tipo === 'extracto') {
            $contaId = (int)($params['conta'] ?? 0);
            $dados   = $this->getFpContaCorrente($stationId);
            $contas  = $dados['contas'];
            $conta   = null;
            foreach ($contas as $c) if ($c['id'] == $contaId || (!$contaId && !$conta)) $conta = $c;

            if (!$conta) return '';

            $rows = '';
            $movimentos = array_reverse($conta['movimentos'] ?? []);
            foreach ($movimentos as $mv) {
                $isC = $mv['tipo']==='credito';
                $cor = $isC ? 'green' : 'red';
                $sinal = $isC ? '+' : '-';
                $rows .= '<tr>
                    <td>'.date('d/m/Y', strtotime($mv['data_movimento'])).'</td>
                    <td>'.htmlspecialchars($mv['descricao']).'</td>
                    <td class="right '.$cor.'">'.$sinal.$fmtKz($mv['valor']).'</td>
                    <td class="right">'.($mv['saldo_apos']!==null?$fmtKz($mv['saldo_apos']):'—').'</td>
                    <td>'.htmlspecialchars($mv['referencia']??'').'</td>
                    <td class="right">'.($mv['conciliado']?'<span class="badge badge-green">✓</span>':'<span class="badge badge-gray">—</span>').'</td>
                </tr>';
            }

            $saldoCls = $conta['saldo_atual'] >= 0 ? 'green' : 'red';
            $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'.$css.'</style></head><body>
            <div class="header">
                <table style="width:100%"><tr>
                    <td><h1>🏦 Extracto Bancário</h1><div class="sub">'.$nomeSt.' · '.htmlspecialchars($conta['nome']).'</div></td>
                    <td class="right">Banco: '.htmlspecialchars($conta['banco']??'—').'<br>Gerado em '.$gerado.'</td>
                </tr></table>
            </div>
            <div class="body">
            <table style="width:100%;margin-bottom:16px">
            <tr>
                <td style="width:33%;padding:0 4px 0 0"><div class="kpi-box"><div class="kpi-val green">'.($conta['total_creditos']?$fmtKzS($conta['total_creditos']):'0,00 Kz').'</div><div class="kpi-lbl">Total Entradas</div></div></td>
                <td style="width:33%;padding:0 4px"><div class="kpi-box"><div class="kpi-val red">'.($conta['total_debitos']?$fmtKzS($conta['total_debitos']):'0,00 Kz').'</div><div class="kpi-lbl">Total Saídas</div></div></td>
                <td style="width:33%;padding:0 0 0 4px"><div class="kpi-box"><div class="kpi-val '.$saldoCls.'">'.$fmtKzS($conta['saldo_atual']).'</div><div class="kpi-lbl">Saldo Actual</div></div></td>
            </tr>
            </table>
            <div class="section-title">Movimentos — '.htmlspecialchars($conta['nome']).'</div>
            <table>
                <thead><tr>
                    <th>Data</th><th>Descrição</th>
                    <th class="right">Valor</th><th class="right">Saldo</th>
                    <th>Referência</th><th class="right">Conciliado</th>
                </tr></thead>
                <tbody>'.$rows.'</tbody>
            </table>
            </div>
            <div class="footer"><span>Finance Pro · '.$nomeSt.' · '.htmlspecialchars($conta['nome']).'</span><span style="margin-left:auto" class="watermark">Confidencial</span></div>
            </body></html>';

            $mpdf->SetTitle('Extracto '.($conta['nome']).' — '.$nomeSt);
        } else {
            return '';
        }

        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S');
    }
}
