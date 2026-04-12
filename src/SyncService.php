<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin;

/**
 * RNB Sync Service
 * Sincroniza dados do Myriad com o RNB RH:
 * - Performance real dos locutores
 * - Prova de emissão para anunciantes
 */
class SyncService
{
    private \PDO $pdo;
    private int  $sid;

    // Mapeamento: nome no Myriad → funcionario_id na BD
    // Chave: MyriadLoginName | Valor: id em rnb_funcionarios
    private const LOCUTOR_MAP = [
        'Newton'            => 2,
        'Newton dos Santos' => 2,
        // DJs sem ficha RH ainda — adicionar quando forem registados
        // 'Deejay AM'      => X,
        // 'Deejay Mr. Legend' => X,
        // 'Deejay Tanas'   => X,
        // 'Raul Gomes'     => X,
    ];

    // Nomes a ignorar (sistema automático)
    private const IGNORE = ['RÁDIO NEW BAND', 'AutoDJ', '', 'RÁDIO NEW BAND'];

    public function __construct(int $stationId = 1)
    {
        $this->sid = $stationId;
        $this->pdo = new \PDO(
            'mysql:host=127.0.0.1;dbname=azuracast;charset=utf8mb4',
            'azuracast', 'CKxR234fxpJG',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    // ── SINCRONIZAR PERFORMANCE ───────────────────────────────
    public function syncPerformance(string $date = ''): array
    {
        $date    = $date ?: date('Y-m-d');
        $results = ['date' => $date, 'synced' => 0, 'skipped' => 0, 'errors' => []];

        // Obter histórico do Myriad via Bridge
        $bridge  = BridgeClient::myriadHistory(500, $date);

        if(empty($bridge)) {
            $results['errors'][] = "Sem dados do Myriad para {$date}";
            return $results;
        }

        // Agrupar por locutor e programa
        $byLocutor = [];
        foreach($bridge as $item) {
            $locutor = $item['locutor'] ?? '';
            if(in_array($locutor, self::IGNORE)) continue;
            if(!isset(self::LOCUTOR_MAP[$locutor])) continue;

            $fid     = self::LOCUTOR_MAP[$locutor];
            $inicio  = $item['inicio']   ?? '';
            $hora    = substr($inicio, 11, 2); // hora do dia
            $periodo = $this->getPeriodo((int)$hora);
            $key     = "{$fid}_{$periodo}";

            if(!isset($byLocutor[$key])) {
                $byLocutor[$key] = [
                    'funcionario_id' => $fid,
                    'periodo'        => $periodo,
                    'programa_nome'  => $this->getProgramaNome($periodo),
                    'musicas'        => 0,
                    'duracao_total'  => 0,
                    'tipos'          => [],
                ];
            }
            $byLocutor[$key]['musicas']++;
            $byLocutor[$key]['duracao_total'] += (float)($item['duracao'] ?? 0);
            $byLocutor[$key]['tipos'][] = $item['tipo'] ?? 'musica';
        }

        // Obter audiência do AzuraCast para o dia
        $listeners = BridgeClient::azuracastListeners();

        // Inserir/actualizar performance
        foreach($byLocutor as $key => $data) {
            try {
                // Calcular score baseado em participações e duração
                $score = $this->calcScore(
                    $data['musicas'],
                    $data['duracao_total'],
                    $listeners
                );

                // Verificar se já existe registo para hoje/periodo
                $existe = $this->pdo->prepare(
                    "SELECT id FROM rnb_rh_performance
                     WHERE funcionario_id=? AND station_id=? AND data=? AND periodo=?"
                );
                $existe->execute([$data['funcionario_id'], $this->sid, $date, $data['periodo']]);
                $rec = $existe->fetchColumn();

                if($rec) {
                    // Actualizar
                    $this->pdo->prepare(
                        "UPDATE rnb_rh_performance SET
                            audiencia_media=?, participacoes=?,
                            performance_score=?, updated_at=NOW()
                         WHERE id=?"
                    )->execute([$listeners, $data['musicas'], $score, $rec]);
                } else {
                    // Inserir novo
                    $this->pdo->prepare(
                        "INSERT INTO rnb_rh_performance
                            (station_id,funcionario_id,programa_nome,data,
                             audiencia_media,participacoes,engagement_score,
                             performance_score,periodo,notas,created_at)
                         VALUES (?,?,?,?,?,?,?,?,?,?,NOW())"
                    )->execute([
                        $this->sid,
                        $data['funcionario_id'],
                        $data['programa_nome'],
                        $date,
                        $listeners,
                        $data['musicas'],
                        round($data['duracao_total'] / 60, 1), // minutos em ar
                        $score,
                        $data['periodo'],
                        "Sync automático Myriad — {$data['musicas']} músicas",
                    ]);
                }
                $results['synced']++;
            } catch(\Exception $e) {
                $results['errors'][] = "Erro funcionário {$data['funcionario_id']}: ".$e->getMessage();
            }
        }

        return $results;
    }

    // ── PROVA DE EMISSÃO COMERCIAL ────────────────────────────
    public function syncProvaEmissao(string $date = ''): array
    {
        $date    = $date ?: date('Y-m-d');
        $results = ['date' => $date, 'spots' => 0, 'inseridos' => 0, 'errors' => []];

        $spots = BridgeClient::myriadCommercials($date);
        $results['spots'] = count($spots);

        if(empty($spots)) {
            // Tentar com ContentType do histórico geral
            $history = BridgeClient::myriadHistory(500, $date);
            $spots   = array_filter($history, fn($r) =>
                in_array($r['tipo'] ?? '', ['spot','jingle','vinheta'])
            );
            $spots = array_values($spots);
            $results['spots'] = count($spots);
        }

        // Criar tabela de prova de emissão se não existir
        $this->ensureProvaEmissaoTable();

        foreach($spots as $spot) {
            try {
                $titulo   = $spot['titulo']  ?? '—';
                $cliente  = $spot['cliente'] ?? $spot['artista'] ?? '—';
                $emitido  = $spot['inicio']  ?? $spot['emitido_em'] ?? date('Y-m-d H:i:s');
                $duracao  = round((float)($spot['duracao'] ?? 0));
                $ref      = $spot['ref_musicmaster'] ?? $spot['referencia'] ?? '';

                // Verificar se já existe (por referência + horário)
                $check = $this->pdo->prepare(
                    "SELECT id FROM rnb_prova_emissao
                     WHERE station_id=? AND data_emissao=? AND referencia_myriad=?"
                );
                $check->execute([$this->sid, $emitido, $ref]);
                if($check->fetchColumn()) {
                    continue; // Já existe
                }

                $this->pdo->prepare(
                    "INSERT INTO rnb_prova_emissao
                        (station_id,titulo,cliente,data_emissao,duracao_seg,
                         referencia_myriad,tipo,created_at)
                     VALUES (?,?,?,?,?,?,?,NOW())"
                )->execute([
                    $this->sid, $titulo, $cliente, $emitido,
                    $duracao, $ref, $spot['tipo'] ?? 'spot',
                ]);
                $results['inseridos']++;
            } catch(\Exception $e) {
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }

    // ── RELATÓRIO DE PROVA DE EMISSÃO (por cliente/data) ─────
    public function getProvaEmissao(string $cliente = '', string $dataIni = '', string $dataFim = ''): array
    {
        $dataIni = $dataIni ?: date('Y-m-01');
        $dataFim = $dataFim ?: date('Y-m-d');

        $sql    = "SELECT * FROM rnb_prova_emissao
                   WHERE station_id=?
                   AND DATE(data_emissao) BETWEEN ? AND ?";
        $params = [$this->sid, $dataIni, $dataFim];

        if($cliente) {
            $sql    .= " AND cliente LIKE ?";
            $params[] = "%{$cliente}%";
        }

        $sql .= " ORDER BY data_emissao DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Sumário por cliente
        $sumario = [];
        foreach($rows as $r) {
            $c = $r['cliente'];
            if(!isset($sumario[$c])) {
                $sumario[$c] = ['cliente'=>$c,'spots'=>0,'duracao_total'=>0,'primeiro'=>$r['data_emissao'],'ultimo'=>$r['data_emissao']];
            }
            $sumario[$c]['spots']++;
            $sumario[$c]['duracao_total'] += (int)$r['duracao_seg'];
            if($r['data_emissao'] < $sumario[$c]['primeiro']) $sumario[$c]['primeiro'] = $r['data_emissao'];
            if($r['data_emissao'] > $sumario[$c]['ultimo'])   $sumario[$c]['ultimo']   = $r['data_emissao'];
        }

        return [
            'periodo'  => ['inicio'=>$dataIni,'fim'=>$dataFim],
            'total'    => count($rows),
            'sumario'  => array_values($sumario),
            'detalhes' => $rows,
        ];
    }

    // ── TOP MÚSICAS ───────────────────────────────────────────
    public function getTopMusicas(string $dataIni = '', string $dataFim = '', int $limit = 20): array
    {
        // Obter histórico dos últimos 7 dias via Bridge
        $result = [];
        $dias   = $dataIni ? (int)((strtotime($dataFim?:date('Y-m-d'))-strtotime($dataIni))/86400)+1 : 7;
        $dias   = min($dias, 30);

        for($i=0; $i<$dias; $i++) {
            $d    = date('Y-m-d', strtotime("-{$i} days"));
            $hist = BridgeClient::myriadHistory(500, $d);
            foreach($hist as $r) {
                if(($r['tipo']??'') !== 'musica') continue;
                $key = ($r['artista']??'').'|||'.($r['titulo']??'');
                if(!isset($result[$key])) {
                    $result[$key] = [
                        'titulo'  => $r['titulo']  ?? '—',
                        'artista' => $r['artista'] ?? '—',
                        'ano'     => $r['ano']     ?? null,
                        'toques'  => 0,
                        'duracao_total' => 0,
                    ];
                }
                $result[$key]['toques']++;
                $result[$key]['duracao_total'] += (float)($r['duracao'] ?? 0);
            }
        }

        usort($result, fn($a,$b) => $b['toques'] - $a['toques']);
        return array_slice(array_values($result), 0, $limit);
    }

    // ── HELPERS ───────────────────────────────────────────────
    private function getPeriodo(int $hora): string
    {
        if($hora >= 5  && $hora < 10) return 'manha';
        if($hora >= 10 && $hora < 13) return 'almoco';
        if($hora >= 13 && $hora < 18) return 'tarde';
        if($hora >= 18 && $hora < 22) return 'noite';
        return 'madrugada';
    }

    private function getProgramaNome(string $periodo): string
    {
        return [
            'manha'     => 'Morning Show',
            'almoco'    => 'Meio-Dia',
            'tarde'     => 'Tarde New Band',
            'noite'     => 'Noite New Band',
            'madrugada' => 'Madrugada',
        ][$periodo] ?? 'Programa';
    }

    private function calcScore(int $participacoes, float $duracao, int $listeners): float
    {
        // Score baseado em: participações (40%) + tempo em ar (40%) + audiência (20%)
        $scorePartic = min(100, $participacoes * 5);
        $scoreDur    = min(100, ($duracao / 3600) * 100); // % de 1 hora
        $scoreAud    = min(100, $listeners / 100);        // normalizado por 10K
        return round($scorePartic * 0.4 + $scoreDur * 0.4 + $scoreAud * 0.2, 1);
    }

    // ── INTELIGÊNCIA DE PROGRAMAÇÃO ──────────────────────────
    /**
     * Cruzar audiência PULSO com PlayLogs Myriad
     * → Descobrir que músicas/horários geram mais audiência
     */
    public function getIntelligenceProgramacao(string $dataIni = '', string $dataFim = ''): array
    {
        $dataIni = $dataIni ?: date('Y-m-d', strtotime('-30 days'));
        $dataFim = $dataFim ?: date('Y-m-d');

        // Audiência por hora do dia (do PULSO)
        $porHora = $this->pdo->prepare(
            "SELECT HOUR(created_at) AS hora,
                    AVG(listeners_total) AS media_listeners,
                    MAX(listeners_total) AS max_listeners,
                    COUNT(*) AS amostras
             FROM plugin_pulso_stream_stats
             WHERE station_id=? AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY HOUR(created_at)
             ORDER BY hora"
        );
        $porHora->execute([$this->sid, $dataIni, $dataFim]);
        $audienciaPorHora = $porHora->fetchAll(\PDO::FETCH_ASSOC);

        // Programa com mais audiência (cruzar horário do programa com stats)
        $porPrograma = $this->pdo->prepare(
            "SELECT pr.nome AS programa,
                    pr.hora_inicio, pr.hora_fim,
                    lo.nome AS locutor,
                    AVG(s.listeners_total) AS media_aud,
                    MAX(s.listeners_total) AS max_aud,
                    COUNT(*) AS amostras
             FROM plugin_prog_programas pr
             LEFT JOIN plugin_prog_programa_locutor pl ON pl.programa_id=pr.id AND pl.is_principal=1
             LEFT JOIN plugin_prog_locutores lo ON lo.id=pl.locutor_id
             JOIN plugin_pulso_stream_stats s ON s.station_id=pr.station_id
               AND HOUR(s.created_at) >= HOUR(pr.hora_inicio)
               AND HOUR(s.created_at) < HOUR(pr.hora_fim)
               AND DATE(s.created_at) BETWEEN ? AND ?
             WHERE pr.station_id=? AND pr.ativo=1
             GROUP BY pr.id
             ORDER BY media_aud DESC"
        );
        $porPrograma->execute([$dataIni, $dataFim, $this->sid]);
        $porPrograma = $porPrograma->fetchAll(\PDO::FETCH_ASSOC);

        // Top músicas por audiência (PULSO stream_stats tem song_title!)
        $topMusicas = $this->pdo->prepare(
            "SELECT song_title AS titulo,
                    song_artist AS artista,
                    AVG(listeners_total) AS media_aud,
                    MAX(listeners_total) AS max_aud,
                    COUNT(*) AS amostras
             FROM plugin_pulso_stream_stats
             WHERE station_id=? AND DATE(created_at) BETWEEN ? AND ?
               AND song_title != '' AND song_title IS NOT NULL
               AND listeners_total > 0
             GROUP BY song_title, song_artist
             ORDER BY media_aud DESC
             LIMIT 20"
        );
        $topMusicas->execute([$this->sid, $dataIni, $dataFim]);
        $topMusicas = $topMusicas->fetchAll(\PDO::FETCH_ASSOC);

        // Dias da semana com mais audiência
        $porDia = $this->pdo->prepare(
            "SELECT DAYOFWEEK(created_at) AS dia_num,
                    DAYNAME(created_at) AS dia_nome,
                    AVG(listeners_total) AS media_aud,
                    MAX(listeners_total) AS max_aud
             FROM plugin_pulso_stream_stats
             WHERE station_id=? AND DATE(created_at) BETWEEN ? AND ?
             GROUP BY DAYOFWEEK(created_at)
             ORDER BY media_aud DESC"
        );
        $porDia->execute([$this->sid, $dataIni, $dataFim]);
        $porDia = $porDia->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'periodo'           => ['inicio'=>$dataIni, 'fim'=>$dataFim],
            'audiencia_por_hora'=> $audienciaPorHora,
            'programas_ranking' => $porPrograma,
            'top_musicas_aud'   => $topMusicas,
            'dias_semana'       => $porDia,
        ];
    }

    /**
     * Programa actualmente no ar — baseado na grade real
     */
    public function getProgramaNoAr(): array
    {
        $agora  = date('H:i:s');
        $diaPt  = ['Sunday'=>'dom','Monday'=>'seg','Tuesday'=>'ter',
                   'Wednesday'=>'qua','Thursday'=>'qui',
                   'Friday'=>'sex','Saturday'=>'sab'][date('l')];

        $stmt = $this->pdo->prepare(
            "SELECT pr.*, lo.nome AS locutor_nome, lo.foto AS locutor_foto,
                    lo.funcionario_id, f.cargo
             FROM plugin_prog_programas pr
             LEFT JOIN plugin_prog_programa_locutor pl ON pl.programa_id=pr.id AND pl.is_principal=1
             LEFT JOIN plugin_prog_locutores lo ON lo.id=pl.locutor_id
             LEFT JOIN rnb_funcionarios f ON f.id=lo.funcionario_id
             WHERE pr.station_id=? AND pr.ativo=1
               AND ? BETWEEN pr.hora_inicio AND pr.hora_fim
             LIMIT 1"
        );
        $stmt->execute([$this->sid, $agora]);
        $prog = $stmt->fetch(\PDO::FETCH_ASSOC);

        if(!$prog) return ['programa'=>null,'locutor'=>null];

        // Escala de hoje
        $escala = null;
        if($prog['funcionario_id']) {
            $es = $this->pdo->prepare(
                "SELECT * FROM rnb_rh_escalas
                 WHERE funcionario_id=? AND station_id=? AND data=?
                   AND prog_programa_id=?"
            );
            $es->execute([$prog['funcionario_id'],$this->sid,date('Y-m-d'),$prog['id']]);
            $escala = $es->fetch(\PDO::FETCH_ASSOC);
        }

        return ['programa'=>$prog,'escala'=>$escala];
    }

        private function ensureProvaEmissaoTable(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS rnb_prova_emissao (
            id INT AUTO_INCREMENT PRIMARY KEY,
            station_id INT NOT NULL DEFAULT 1,
            titulo VARCHAR(300) NOT NULL,
            cliente VARCHAR(300) DEFAULT '',
            data_emissao DATETIME NOT NULL,
            duracao_seg INT DEFAULT 0,
            referencia_myriad VARCHAR(100) DEFAULT '',
            tipo VARCHAR(50) DEFAULT 'spot',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_station (station_id),
            INDEX idx_data (data_emissao),
            INDEX idx_cliente (cliente)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
