<?php

declare(strict_types=1);

namespace Plugin\ProgramacaoPlugin\Service;

use Doctrine\DBAL\Connection;

class ProgramacaoService
{
    private Connection $db;
    private string $timezone = 'Africa/Luanda';

    public function __construct(Connection $connection)
    {
        $this->db = $connection;
    }

    public function getProgramas(int $stationId): array
    {
        $sql = "SELECT p.*, 
                       (SELECT l.nome FROM plugin_prog_locutores l 
                        JOIN plugin_prog_programa_locutor pl ON l.id = pl.locutor_id 
                        WHERE pl.programa_id = p.id AND pl.is_principal = 1 
                        LIMIT 1) as locutor_principal
                FROM plugin_prog_programas p 
                WHERE p.station_id = :station_id 
                ORDER BY p.nome ASC";
        
        return $this->db->fetchAllAssociative($sql, ['station_id' => $stationId]);
    }

    public function getPrograma(int $id): ?array
    {
        $sql = "SELECT * FROM plugin_prog_programas WHERE id = :id";
        $result = $this->db->fetchAssociative($sql, ['id' => $id]);
        
        return $result ?: null;
    }

    public function savePrograma(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        
        if (isset($data['id']) && $data['id'] > 0) {
            $data['updated_at'] = $now;
            $id = (int) $data['id'];
            unset($data['id']);
            
            $this->db->update('plugin_prog_programas', $data, ['id' => $id]);
            return $id;
        } else {
            unset($data['id']);
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            
            $this->db->insert('plugin_prog_programas', $data);
            return (int) $this->db->lastInsertId();
        }
    }

    public function deletePrograma(int $id): bool
    {
        $this->db->delete('plugin_prog_programa_locutor', ['programa_id' => $id]);
        return $this->db->delete('plugin_prog_programas', ['id' => $id]) > 0;
    }

    public function getLocutores(int $stationId): array
    {
        $sql = "SELECT l.*,
                       (SELECT COUNT(*) FROM plugin_prog_programa_locutor pl WHERE pl.locutor_id = l.id) as total_programas
                FROM plugin_prog_locutores l 
                WHERE l.station_id = :station_id 
                ORDER BY l.nome ASC";
        
        return $this->db->fetchAllAssociative($sql, ['station_id' => $stationId]);
    }

    public function getLocutor(int $id): ?array
    {
        $sql = "SELECT * FROM plugin_prog_locutores WHERE id = :id";
        $result = $this->db->fetchAssociative($sql, ['id' => $id]);
        
        return $result ?: null;
    }

    public function saveLocutor(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        
        if (isset($data['id']) && $data['id'] > 0) {
            $data['updated_at'] = $now;
            $id = (int) $data['id'];
            unset($data['id']);
            
            $this->db->update('plugin_prog_locutores', $data, ['id' => $id]);
            return $id;
        } else {
            unset($data['id']);
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
            
            $this->db->insert('plugin_prog_locutores', $data);
            return (int) $this->db->lastInsertId();
        }
    }

    public function deleteLocutor(int $id): bool
    {
        $this->db->delete('plugin_prog_programa_locutor', ['locutor_id' => $id]);
        return $this->db->delete('plugin_prog_locutores', ['id' => $id]) > 0;
    }

    public function getGradeSemanal(int $stationId): array
    {
        $sql = "SELECT p.*, 
                       GROUP_CONCAT(l.nome SEPARATOR ', ') as locutores
                FROM plugin_prog_programas p
                LEFT JOIN plugin_prog_programa_locutor pl ON p.id = pl.programa_id
                LEFT JOIN plugin_prog_locutores l ON pl.locutor_id = l.id
                WHERE p.station_id = :station_id AND p.ativo = 1
                GROUP BY p.id
                ORDER BY p.hora_inicio ASC";
        
        $programas = $this->db->fetchAllAssociative($sql, ['station_id' => $stationId]);
        
        $grade = [
            'domingo' => [],
            'segunda' => [],
            'terca' => [],
            'quarta' => [],
            'quinta' => [],
            'sexta' => [],
            'sabado' => [],
        ];
        
        foreach ($programas as $programa) {
            $dias = json_decode($programa['dias_semana'] ?? '[]', true) ?: [];
            foreach ($dias as $dia) {
                $diaKey = strtolower($dia);
                if (isset($grade[$diaKey])) {
                    $grade[$diaKey][] = $programa;
                }
            }
        }
        
        return $grade;
    }

    public function getProgramaNoAr(int $stationId): ?array
    {
        $agora = (new \DateTime('now', new \DateTimeZone($this->timezone)))->format('H:i:s');
        $diaSemana = $this->getDiaSemanaAtual();
        
        $sql = "SELECT p.*, 
                       GROUP_CONCAT(l.nome SEPARATOR ', ') as locutores,
                       (SELECT l2.foto FROM plugin_prog_locutores l2 
                        JOIN plugin_prog_programa_locutor pl2 ON l2.id = pl2.locutor_id 
                        WHERE pl2.programa_id = p.id AND pl2.is_principal = 1 
                        LIMIT 1) as foto_locutor
                FROM plugin_prog_programas p
                LEFT JOIN plugin_prog_programa_locutor pl ON p.id = pl.programa_id
                LEFT JOIN plugin_prog_locutores l ON pl.locutor_id = l.id
                WHERE p.station_id = :station_id 
                  AND p.ativo = 1
                  AND p.hora_inicio <= :hora_atual
                  AND p.hora_fim > :hora_atual
                  AND JSON_CONTAINS(p.dias_semana, :dia_semana)
                GROUP BY p.id
                LIMIT 1";
        
        $result = $this->db->fetchAssociative($sql, [
            'station_id' => $stationId,
            'hora_atual' => $agora,
            'dia_semana' => json_encode($diaSemana),
        ]);
        
        return $result ?: null;
    }

    public function getConfig(int $stationId): array
    {
        $sql = "SELECT * FROM plugin_prog_config WHERE station_id = :station_id";
        $result = $this->db->fetchAssociative($sql, ['station_id' => $stationId]);
        
        if (!$result) {
            return [
                'station_id' => $stationId,
                'exibir_api_publica' => true,
                'exibir_locutor_metadata' => false,
                'formato_metadata' => '{programa} - {locutor}',
                'programa_padrao_nome' => 'Programação Musical',
                'programa_padrao_descricao' => 'A melhor programação musical para você.',
            ];
        }
        
        return $result;
    }

    public function saveConfig(int $stationId, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        
        $exists = $this->db->fetchOne(
            "SELECT id FROM plugin_prog_config WHERE station_id = :station_id",
            ['station_id' => $stationId]
        );
        
        $data['station_id'] = $stationId;
        $data['updated_at'] = $now;
        
        if ($exists) {
            unset($data['station_id']);
            return $this->db->update('plugin_prog_config', $data, ['station_id' => $stationId]) > 0;
        } else {
            $data['created_at'] = $now;
            return $this->db->insert('plugin_prog_config', $data) > 0;
        }
    }

    public function getEstatisticas(int $stationId): array
    {
        $totalProgramas = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_prog_programas WHERE station_id = :station_id",
            ['station_id' => $stationId]
        );
        
        $programasAtivos = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_prog_programas WHERE station_id = :station_id AND ativo = 1",
            ['station_id' => $stationId]
        );
        
        $totalLocutores = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_prog_locutores WHERE station_id = :station_id",
            ['station_id' => $stationId]
        );
        
        $locutoresAtivos = (int) $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_prog_locutores WHERE station_id = :station_id AND ativo = 1",
            ['station_id' => $stationId]
        );
        
        return [
            'total_programas' => $totalProgramas,
            'programas_ativos' => $programasAtivos,
            'total_locutores' => $totalLocutores,
            'locutores_ativos' => $locutoresAtivos,
            'programa_no_ar' => $this->getProgramaNoAr($stationId),
        ];
    }

    private function getDiaSemanaAtual(): string
    {
        $dias = [
            0 => 'domingo',
            1 => 'segunda',
            2 => 'terca',
            3 => 'quarta',
            4 => 'quinta',
            5 => 'sexta',
            6 => 'sabado',
        ];
        
        return $dias[(int) (new \DateTime('now', new \DateTimeZone($this->timezone)))->format('w')];
    }

    public function vincularLocutorPrograma(int $programaId, int $locutorId, bool $isPrincipal = false, ?string $funcao = null): bool
    {
        $exists = $this->db->fetchOne(
            "SELECT id FROM plugin_prog_programa_locutor WHERE programa_id = :programa_id AND locutor_id = :locutor_id",
            ['programa_id' => $programaId, 'locutor_id' => $locutorId]
        );
        
        if ($exists) {
            return $this->db->update('plugin_prog_programa_locutor', [
                'is_principal' => $isPrincipal ? 1 : 0,
                'funcao' => $funcao,
            ], ['programa_id' => $programaId, 'locutor_id' => $locutorId]) > 0;
        }
        
        return $this->db->insert('plugin_prog_programa_locutor', [
            'programa_id' => $programaId,
            'locutor_id' => $locutorId,
            'is_principal' => $isPrincipal ? 1 : 0,
            'funcao' => $funcao,
        ]) > 0;
    }

    public function desvincularLocutorPrograma(int $programaId, int $locutorId): bool
    {
        return $this->db->delete('plugin_prog_programa_locutor', [
            'programa_id' => $programaId,
            'locutor_id' => $locutorId,
        ]) > 0;
    }

    public function getLocutoresDoPrograma(int $programaId): array
    {
        $sql = "SELECT l.*, pl.is_principal, pl.funcao
                FROM plugin_prog_locutores l
                JOIN plugin_prog_programa_locutor pl ON l.id = pl.locutor_id
                WHERE pl.programa_id = :programa_id
                ORDER BY pl.is_principal DESC, l.nome ASC";
        
        return $this->db->fetchAllAssociative($sql, ['programa_id' => $programaId]);
    }
    public function getMensagensCarrossel(int $stationId): array
    {
        $agora = (new \DateTime("now", new \DateTimeZone($this->timezone)));
        $hora = $agora->format("H:i:s");
        $diaSemana = $this->getDiaSemanaAtual();
        $dataHoje = $agora->format("Y-m-d");
        
        $sql = "SELECT * FROM plugin_prog_carrossel 
                WHERE station_id = :station_id 
                  AND ativo = 1 
                  AND hora_inicio <= :hora 
                  AND hora_fim >= :hora 
                  AND JSON_CONTAINS(dias_semana, :dia_semana) 
                  AND (data_inicio IS NULL OR data_inicio <= :data_hoje) 
                  AND (data_fim IS NULL OR data_fim >= :data_hoje) 
                ORDER BY prioridade DESC";
        
        return $this->db->fetchAllAssociative($sql, [
            "station_id" => $stationId,
            "hora" => $hora,
            "dia_semana" => json_encode($diaSemana),
            "data_hoje" => $dataHoje,
        ]);
    }

    public function getCarrosselParaWidget(int $stationId): array
    {
        $mensagens = $this->getMensagensCarrossel($stationId);
        $resultado = [];
        
        foreach ($mensagens as $msg) {
            for ($i = 0; $i < $msg["prioridade"]; $i++) {
                $resultado[] = [
                    "tipo" => $msg["tipo"],
                    "linha1" => $msg["linha1"],
                    "linha2" => $msg["linha2"],
                ];
            }
        }
        
        if (!empty($resultado)) {
            shuffle($resultado);
        }
        
        return $resultado;
    }

    public function getMensagensCarrosselTodas(int $stationId): array
    {
        $sql = "SELECT * FROM plugin_prog_carrossel WHERE station_id = :station_id ORDER BY tipo, prioridade DESC, linha1";
        return $this->db->fetchAllAssociative($sql, ["station_id" => $stationId]);
    }

    public function getMensagemCarrossel(int $id): ?array
    {
        $sql = "SELECT * FROM plugin_prog_carrossel WHERE id = :id";
        $result = $this->db->fetchAssociative($sql, ["id" => $id]);
        return $result ?: null;
    }

    public function saveMensagemCarrossel(array $data): int
    {
        $now = date("Y-m-d H:i:s");
        if (isset($data["id"]) && $data["id"] > 0) {
            $data["updated_at"] = $now;
            $id = (int) $data["id"];
            unset($data["id"]);
            $this->db->update("plugin_prog_carrossel", $data, ["id" => $id]);
            return $id;
        } else {
            unset($data["id"]);
            $data["created_at"] = $now;
            $data["updated_at"] = $now;
            $this->db->insert("plugin_prog_carrossel", $data);
            return (int) $this->db->lastInsertId();
        }
    }

    public function deleteMensagemCarrossel(int $id): bool
    {
        return $this->db->delete("plugin_prog_carrossel", ["id" => $id]) > 0;
    }

    public function toggleMensagemCarrossel(int $id): bool
    {
        $msg = $this->getMensagemCarrossel($id);
        if (!$msg) return false;
        $novoStatus = $msg["ativo"] ? 0 : 1;
        return $this->db->update("plugin_prog_carrossel", ["ativo" => $novoStatus], ["id" => $id]) > 0;
    }

    public function processarVariaveis(string $texto, int $stationId, ?array $programaNoAr = null, ?array $nowPlaying = null): string
    {
        $agora = new \DateTime("now", new \DateTimeZone($this->timezone));
        $hora = (int) $agora->format("H");
        
        // Determinar saudação baseada na hora
        if ($hora >= 6 && $hora < 12) {
            $saudacao = "dia";
        } elseif ($hora >= 12 && $hora < 18) {
            $saudacao = "tarde";
        } elseif ($hora >= 18 && $hora < 24) {
            $saudacao = "noite";
        } else {
            $saudacao = "madrugada";
        }
        
        // Variáveis disponíveis
        $variaveis = [
            "{saudacao}" => $saudacao,
            "{hora}" => $agora->format("H:i"),
            "{data}" => $agora->format("d/m/Y"),
            "{dia_semana}" => $this->getDiaSemanaAtualNome(),
            "{programa}" => $programaNoAr["nome"] ?? "Rádio New Band",
            "{locutor}" => $programaNoAr["locutores"] ?? "",
            "{musica}" => $nowPlaying["titulo"] ?? "",
            "{artista}" => $nowPlaying["artista"] ?? "",
            "{musica_completa}" => $nowPlaying["song"] ?? "",
        ];
        
        return str_replace(array_keys($variaveis), array_values($variaveis), $texto);
    }
    
    private function getDiaSemanaAtualNome(): string
    {
        $agora = new \DateTime("now", new \DateTimeZone($this->timezone));
        $dias = [
            "Sunday" => "Domingo",
            "Monday" => "Segunda-feira", 
            "Tuesday" => "Terça-feira",
            "Wednesday" => "Quarta-feira",
            "Thursday" => "Quinta-feira",
            "Friday" => "Sexta-feira",
            "Saturday" => "Sábado"
        ];
        return $dias[$agora->format("l")] ?? "";
    }

    public function registarExibicao(int $stationId, array $mensagem): void
    {
        $this->db->insert("plugin_prog_carrossel_logs", [
            "station_id" => $stationId,
            "mensagem_id" => $mensagem["id"] ?? 0,
            "tipo" => $mensagem["tipo"] ?? "desconhecido",
            "linha1" => $mensagem["linha1"] ?? "",
            "linha2" => $mensagem["linha2"] ?? "",
            "exibido_em" => date("Y-m-d H:i:s"),
        ]);
    }

    public function getEstatisticasCarrossel(int $stationId): array
    {
        $totalExibicoes = $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_prog_carrossel_logs WHERE station_id = :station_id",
            ["station_id" => $stationId]
        );
        
        $hoje = date("Y-m-d");
        $exibicoesHoje = $this->db->fetchOne(
            "SELECT COUNT(*) FROM plugin_prog_carrossel_logs WHERE station_id = :station_id AND DATE(exibido_em) = :hoje",
            ["station_id" => $stationId, "hoje" => $hoje]
        );
        
        $topMensagens = $this->db->fetchAllAssociative(
            "SELECT mensagem_id, tipo, linha1, linha2, COUNT(*) as total FROM plugin_prog_carrossel_logs WHERE station_id = :station_id AND mensagem_id > 0 GROUP BY mensagem_id, tipo, linha1, linha2 ORDER BY total DESC LIMIT 5",
            ["station_id" => $stationId]
        );
        
        $porTipo = $this->db->fetchAllAssociative(
            "SELECT tipo, COUNT(*) as total FROM plugin_prog_carrossel_logs WHERE station_id = :station_id GROUP BY tipo ORDER BY total DESC",
            ["station_id" => $stationId]
        );
        
        $porHora = $this->db->fetchAllAssociative(
            "SELECT HOUR(exibido_em) as hora, COUNT(*) as total FROM plugin_prog_carrossel_logs WHERE station_id = :station_id AND exibido_em >= DATE_SUB(NOW(), INTERVAL 24 HOUR) GROUP BY HOUR(exibido_em) ORDER BY hora",
            ["station_id" => $stationId]
        );
        
        $ultimos7Dias = $this->db->fetchAllAssociative(
            "SELECT DATE(exibido_em) as dia, COUNT(*) as total FROM plugin_prog_carrossel_logs WHERE station_id = :station_id AND exibido_em >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(exibido_em) ORDER BY dia",
            ["station_id" => $stationId]
        );
        
        return [
            "total_exibicoes" => (int) $totalExibicoes,
            "exibicoes_hoje" => (int) $exibicoesHoje,
            "top_mensagens" => $topMensagens,
            "por_tipo" => $porTipo,
            "por_hora" => $porHora,
            "ultimos_7_dias" => $ultimos7Dias,
        ];
    }

    public function getContagemPorMensagem(int $stationId): array
    {
        $result = $this->db->fetchAllAssociative(
            "SELECT mensagem_id, COUNT(*) as total FROM plugin_prog_carrossel_logs WHERE station_id = :station_id AND mensagem_id > 0 GROUP BY mensagem_id",
            ["station_id" => $stationId]
        );
        
        $contagem = [];
        foreach ($result as $row) {
            $contagem[$row["mensagem_id"]] = (int) $row["total"];
        }
        return $contagem;
    }
}
