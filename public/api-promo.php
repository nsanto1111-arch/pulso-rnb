<?php
/**
 * PULSO - API Promocoes, Sorteio & Anti-Fraude
 * /pulso/api/promo
 */
header('Content-Type: application/json; charset=utf-8');
$stationId = (int) ($_GET['station_id'] ?? 1);
$action = $_GET['action'] ?? '';

try {
    $pdo = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=azuracast;charset=utf8mb4',
        'azuracast', 'CKxR234fxpJG',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'DB error']);
    exit;
}

switch ($action) {

    // ============================================================
    // PROMOCOES CRUD
    // ============================================================
    case 'listar_promocoes':
        $estado = $_GET['estado'] ?? null;
        $sql = "SELECT * FROM plugin_pulso_promocoes WHERE station_id = :sid";
        $params = ['sid' => $stationId];
        if ($estado) { $sql .= " AND estado = :est"; $params['est'] = $estado; }
        $sql .= " ORDER BY data_inicio DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['status' => 'ok', 'promocoes' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
        exit;

    case 'criar_promocao':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['erro' => 'POST']); exit; }
        $b = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO plugin_pulso_promocoes 
            (station_id, nome, descricao, premio, data_inicio, data_fim, max_participantes, max_vencedores, participacoes_por_pessoa, dias_minimo_ouvinte, pontos_minimos, estado)
            VALUES (:sid, :nome, :desc, :premio, :di, :df, :maxp, :maxv, :ppp, :dmo, :pm, :est)");
        $stmt->execute([
            'sid' => $stationId,
            'nome' => $b['nome'] ?? 'Promocao',
            'desc' => $b['descricao'] ?? '',
            'premio' => $b['premio'] ?? 'Premio',
            'di' => $b['data_inicio'] ?? date('Y-m-d H:i:s'),
            'df' => $b['data_fim'] ?? date('Y-m-d H:i:s', strtotime('+7 days')),
            'maxp' => (int)($b['max_participantes'] ?? 0),
            'maxv' => (int)($b['max_vencedores'] ?? 1),
            'ppp' => (int)($b['participacoes_por_pessoa'] ?? 1),
            'dmo' => (int)($b['dias_minimo_ouvinte'] ?? 0),
            'pm' => (int)($b['pontos_minimos'] ?? 0),
            'est' => $b['estado'] ?? 'activa',
        ]);
        echo json_encode(['status' => 'ok', 'id' => (int)$pdo->lastInsertId()]);
        exit;

    case 'activar_promocao':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $b = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare("UPDATE plugin_pulso_promocoes SET estado = 'activa' WHERE id = :id")->execute(['id' => (int)$b['id']]);
        echo json_encode(['status' => 'ok']);
        exit;

    case 'encerrar_promocao':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $b = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare("UPDATE plugin_pulso_promocoes SET estado = 'encerrada' WHERE id = :id")->execute(['id' => (int)$b['id']]);
        echo json_encode(['status' => 'ok']);
        exit;

    // ============================================================
    // PARTICIPAR EM PROMOCAO
    // ============================================================
    case 'participar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $b = json_decode(file_get_contents('php://input'), true);
        $promoId = (int)($b['promocao_id'] ?? 0);
        $ouvId = (int)($b['ouvinte_id'] ?? 0);
        if (!$promoId || !$ouvId) { echo json_encode(['erro' => 'IDs obrigatorios']); exit; }

        // Verificar promocao activa
        $promo = $pdo->prepare("SELECT * FROM plugin_pulso_promocoes WHERE id = :id AND estado = 'activa'");
        $promo->execute(['id' => $promoId]);
        $promo = $promo->fetch();
        if (!$promo) { echo json_encode(['erro' => 'Promocao nao activa']); exit; }

        // Verificar ouvinte
        $ouv = $pdo->prepare("SELECT * FROM plugin_pulso_ouvintes WHERE id = :id AND ativo = 1");
        $ouv->execute(['id' => $ouvId]);
        $ouv = $ouv->fetch();
        if (!$ouv) { echo json_encode(['erro' => 'Ouvinte nao encontrado']); exit; }

        // Verificar bloqueio
        if ($ouv['bloqueado']) { echo json_encode(['erro' => 'Ouvinte bloqueado', 'motivo' => $ouv['motivo_bloqueio']]); exit; }

        // Verificar requisitos minimos
        if ($promo['pontos_minimos'] > 0 && $ouv['pontos'] < $promo['pontos_minimos']) {
            echo json_encode(['erro' => 'Pontos insuficientes', 'minimo' => $promo['pontos_minimos'], 'actual' => $ouv['pontos']]);
            exit;
        }
        $diasOuv = (int)((time() - strtotime($ouv['data_registo'])) / 86400);
        if ($promo['dias_minimo_ouvinte'] > 0 && $diasOuv < $promo['dias_minimo_ouvinte']) {
            echo json_encode(['erro' => 'Ouvinte muito recente', 'minimo_dias' => $promo['dias_minimo_ouvinte'], 'dias_actual' => $diasOuv]);
            exit;
        }

        // Verificar limite de participacoes
        $partCount = $pdo->prepare("SELECT COUNT(*) as c FROM plugin_pulso_participacoes WHERE ouvinte_id = :oid AND promocao_id = :pid");
        $partCount->execute(['oid' => $ouvId, 'pid' => $promoId]);
        $pc = (int)$partCount->fetch()['c'];
        if ($promo['participacoes_por_pessoa'] > 0 && $pc >= $promo['participacoes_por_pessoa']) {
            echo json_encode(['erro' => 'Limite de participacoes atingido', 'limite' => $promo['participacoes_por_pessoa']]);
            exit;
        }

        // Registar participacao
        $stmt = $pdo->prepare("INSERT INTO plugin_pulso_participacoes (station_id, ouvinte_id, tipo, descricao, promocao_id, pontos_ganhos) VALUES (:sid, :oid, 'promocao', :desc, :pid, 5)");
        $stmt->execute(['sid' => $stationId, 'oid' => $ouvId, 'desc' => 'Participacao: '.$promo['nome'], 'pid' => $promoId]);

        // Actualizar contadores
        $pdo->prepare("UPDATE plugin_pulso_ouvintes SET total_participacoes = total_participacoes + 1, pontos = pontos + 5, ultima_actividade = NOW() WHERE id = :id")->execute(['id' => $ouvId]);
        $pdo->prepare("UPDATE plugin_pulso_promocoes SET total_participantes = total_participantes + 1, total_participacoes = total_participacoes + 1 WHERE id = :id")->execute(['id' => $promoId]);

        echo json_encode(['status' => 'ok', 'msg' => 'Participacao registada']);
        exit;

    // ============================================================
    // MOTOR DE SORTEIO JUSTO
    // ============================================================
    case 'sortear':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $b = json_decode(file_get_contents('php://input'), true);
        $promoId = (int)($b['promocao_id'] ?? 0);
        $numVencedores = (int)($b['num_vencedores'] ?? 1);
        if (!$promoId) { echo json_encode(['erro' => 'promocao_id obrigatorio']); exit; }

        // Buscar promocao
        $promo = $pdo->prepare("SELECT * FROM plugin_pulso_promocoes WHERE id = :id");
        $promo->execute(['id' => $promoId]);
        $promo = $promo->fetch();
        if (!$promo) { echo json_encode(['erro' => 'Promocao nao encontrada']); exit; }

        // Buscar participantes elegiveiss (sem bloqueio, nao venceram esta promo)
        $sql = "SELECT DISTINCT p.ouvinte_id, o.nome, o.pontos, o.total_participacoes, o.total_vitorias, 
                o.dias_sem_ganhar, o.bloqueado, o.ultima_vitoria, o.data_registo,
                COUNT(p.id) as participacoes_nesta
                FROM plugin_pulso_participacoes p
                JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
                WHERE p.promocao_id = :pid AND o.bloqueado = 0 AND o.ativo = 1
                AND p.ouvinte_id NOT IN (
                    SELECT ouvinte_id FROM plugin_pulso_sorteios WHERE promocao_id = :pid2 AND resultado = 'vencedor'
                )
                GROUP BY p.ouvinte_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['pid' => $promoId, 'pid2' => $promoId]);
        $candidatos = $stmt->fetchAll();

        if (empty($candidatos)) {
            echo json_encode(['erro' => 'Sem candidatos elegiveis']);
            exit;
        }

        // CALCULAR PROBABILIDADES (Motor Justo)
        // Formula: peso = base(10) + pontos_factor + participacoes_factor + dias_sem_ganhar_bonus + veterano_bonus
        $totalPeso = 0;
        foreach ($candidatos as &$c) {
            $peso = 10; // base igual para todos

            // Factor pontos (max +20)
            $peso += min(20, floor($c['pontos'] / 10));

            // Factor participacoes nesta promo (max +10)
            $peso += min(10, $c['participacoes_nesta'] * 3);

            // Bonus dias sem ganhar (o mais importante para justica!)
            // +1 por cada 7 dias sem ganhar, max +30
            $peso += min(30, floor($c['dias_sem_ganhar'] / 7));

            // Bonus por nunca ter ganho nada
            if ($c['total_vitorias'] == 0) $peso += 15;

            // Penalidade para quem ganhou recentemente (-20 se ganhou nos ultimos 30 dias)
            if ($c['ultima_vitoria']) {
                $diasDesdeVitoria = (int)((time() - strtotime($c['ultima_vitoria'])) / 86400);
                if ($diasDesdeVitoria < 30) $peso = max(5, $peso - 20);
            }

            // Bonus veterano/embaixador (+5)
            $diasOuvinte = (int)((time() - strtotime($c['data_registo'])) / 86400);
            if ($diasOuvinte > 90) $peso += 5;

            $c['peso'] = $peso;
            $c['probabilidade'] = 0;
            $totalPeso += $peso;
        }
        unset($c);

        // Calcular probabilidades em percentagem
        foreach ($candidatos as &$c) {
            $c['probabilidade'] = round(($c['peso'] / $totalPeso) * 100, 2);
        }
        unset($c);

        // Ordenar por peso descendente
        usort($candidatos, function($a, $b) { return $b['peso'] - $a['peso']; });

        // SORTEAR usando probabilidades ponderadas
        $vencedores = [];
        $pool = $candidatos;
        $numVencedores = min($numVencedores, count($pool));

        for ($v = 0; $v < $numVencedores; $v++) {
            $totalP = array_sum(array_column($pool, 'peso'));
            $rand = mt_rand(1, (int)($totalP * 100)) / 100;
            $acumulado = 0;
            $vencedorIdx = 0;
            foreach ($pool as $idx => $c) {
                $acumulado += $c['peso'];
                if ($acumulado >= $rand) { $vencedorIdx = $idx; break; }
            }
            $vencedores[] = $pool[$vencedorIdx];
            array_splice($pool, $vencedorIdx, 1);
        }

        // Registar na BD
        foreach ($candidatos as $c) {
            $resultado = 'participante';
            foreach ($vencedores as $w) {
                if ($w['ouvinte_id'] == $c['ouvinte_id']) { $resultado = 'vencedor'; break; }
            }
            $pdo->prepare("INSERT INTO plugin_pulso_sorteios (station_id, promocao_id, ouvinte_id, pontos_na_hora, probabilidade, dias_sem_ganhar_na_hora, total_participacoes_na_hora, resultado)
                VALUES (:sid, :pid, :oid, :pts, :prob, :dsg, :tp, :res)")->execute([
                'sid' => $stationId, 'pid' => $promoId, 'oid' => $c['ouvinte_id'],
                'pts' => $c['pontos'], 'prob' => $c['probabilidade'], 'dsg' => $c['dias_sem_ganhar'],
                'tp' => $c['total_participacoes'], 'res' => $resultado
            ]);
        }

        // Actualizar vencedores
        foreach ($vencedores as $w) {
            $pdo->prepare("UPDATE plugin_pulso_ouvintes SET total_vitorias = total_vitorias + 1, dias_sem_ganhar = 0, ultima_vitoria = NOW(), pontos = pontos + 50, total_sorteios = total_sorteios + 1 WHERE id = :id")
                ->execute(['id' => $w['ouvinte_id']]);
            // Registar vitoria na participacao
            $pdo->prepare("UPDATE plugin_pulso_participacoes SET ganhou = 1, premio = :premio WHERE ouvinte_id = :oid AND promocao_id = :pid AND ganhou = 0 LIMIT 1")
                ->execute(['premio' => $promo['premio'], 'oid' => $w['ouvinte_id'], 'pid' => $promoId]);
        }

        // Actualizar nao-vencedores (incrementar dias_sem_ganhar)
        $vIds = array_column($vencedores, 'ouvinte_id');
        foreach ($candidatos as $c) {
            if (!in_array($c['ouvinte_id'], $vIds)) {
                $pdo->prepare("UPDATE plugin_pulso_ouvintes SET dias_sem_ganhar = dias_sem_ganhar + 1, total_sorteios = total_sorteios + 1 WHERE id = :id")
                    ->execute(['id' => $c['ouvinte_id']]);
            }
        }

        echo json_encode([
            'status' => 'ok',
            'promocao' => $promo['nome'],
            'premio' => $promo['premio'],
            'total_candidatos' => count($candidatos),
            'vencedores' => array_map(function($w) {
                return ['id' => $w['ouvinte_id'], 'nome' => $w['nome'], 'pontos' => $w['pontos'], 'probabilidade' => $w['probabilidade'].'%', 'dias_sem_ganhar' => $w['dias_sem_ganhar']];
            }, $vencedores),
            'todos_candidatos' => array_map(function($c) {
                return ['nome' => $c['nome'], 'probabilidade' => $c['probabilidade'].'%', 'peso' => $c['peso']];
            }, $candidatos)
        ], JSON_UNESCAPED_UNICODE);
        exit;

    case 'historico_sorteios':
        $promoId = (int)($_GET['promocao_id'] ?? 0);
        $sql = "SELECT s.*, o.nome FROM plugin_pulso_sorteios s JOIN plugin_pulso_ouvintes o ON o.id = s.ouvinte_id WHERE s.station_id = :sid";
        $params = ['sid' => $stationId];
        if ($promoId) { $sql .= " AND s.promocao_id = :pid"; $params['pid'] = $promoId; }
        $sql .= " ORDER BY s.data_sorteio DESC, s.resultado DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['status' => 'ok', 'sorteios' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
        exit;

    // ============================================================
    // ANTI-FRAUDE
    // ============================================================
    case 'scan_fraude':
        $alertas = [];

        // 1. IPs duplicados (mesmo IP, nomes diferentes)
        $ips = $pdo->query("SELECT ultimo_ip, GROUP_CONCAT(id) as ids, GROUP_CONCAT(nome SEPARATOR '|') as nomes, COUNT(*) as c 
            FROM plugin_pulso_ouvintes WHERE ativo = 1 AND ultimo_ip IS NOT NULL 
            GROUP BY ultimo_ip HAVING c > 1")->fetchAll();
        foreach ($ips as $ip) {
            $alertas[] = [
                'tipo' => 'ip_duplicado',
                'severidade' => $ip['c'] > 3 ? 'alta' : 'media',
                'descricao' => $ip['c'].' ouvintes com mesmo IP: '.$ip['nomes'],
                'ip' => $ip['ultimo_ip'],
                'ouvintes_ids' => $ip['ids']
            ];
        }

        // 2. Nomes muito similares
        $nomes = $pdo->query("SELECT id, nome FROM plugin_pulso_ouvintes WHERE ativo = 1 ORDER BY nome")->fetchAll();
        for ($i = 0; $i < count($nomes); $i++) {
            for ($j = $i + 1; $j < count($nomes); $j++) {
                $n1 = mb_strtolower(trim($nomes[$i]['nome']));
                $n2 = mb_strtolower(trim($nomes[$j]['nome']));
                if ($n1 === $n2 || (strlen($n1) > 3 && strlen($n2) > 3 && levenshtein($n1, $n2) <= 2)) {
                    $alertas[] = [
                        'tipo' => 'nome_similar',
                        'severidade' => $n1 === $n2 ? 'alta' : 'baixa',
                        'descricao' => 'Nomes similares: "'.$nomes[$i]['nome'].'" e "'.$nomes[$j]['nome'].'"',
                        'ouvintes_ids' => $nomes[$i]['id'].','.$nomes[$j]['id']
                    ];
                }
            }
        }

        // 3. Spam (mais de 5 participacoes no mesmo dia)
        $spam = $pdo->query("SELECT ouvinte_id, o.nome, DATE(data_participacao) as dia, COUNT(*) as c 
            FROM plugin_pulso_participacoes p JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
            WHERE data_participacao > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY ouvinte_id, DATE(data_participacao) HAVING c > 5")->fetchAll();
        foreach ($spam as $s) {
            $alertas[] = [
                'tipo' => 'spam',
                'severidade' => $s['c'] > 10 ? 'alta' : 'media',
                'descricao' => $s['nome'].' fez '.$s['c'].' participacoes em '.$s['dia'],
                'ouvintes_ids' => (string)$s['ouvinte_id']
            ];
        }

        // Guardar novos alertas na BD (evitar duplicados do mesmo dia)
        $hoje = date('Y-m-d');
        foreach ($alertas as $a) {
            $exists = $pdo->prepare("SELECT id FROM plugin_pulso_antifraude WHERE tipo = :tipo AND ouvintes_envolvidos = :oids AND DATE(data_deteccao) = :hoje");
            $exists->execute(['tipo' => $a['tipo'], 'oids' => $a['ouvintes_ids'] ?? '', 'hoje' => $hoje]);
            if (!$exists->fetch()) {
                $pdo->prepare("INSERT INTO plugin_pulso_antifraude (station_id, tipo, descricao, ip_relacionado, ouvintes_envolvidos, severidade) VALUES (:sid, :tipo, :desc, :ip, :oids, :sev)")
                    ->execute(['sid' => $stationId, 'tipo' => $a['tipo'], 'desc' => $a['descricao'], 'ip' => $a['ip'] ?? null, 'oids' => $a['ouvintes_ids'] ?? null, 'sev' => $a['severidade']]);
            }
        }

        echo json_encode(['status' => 'ok', 'total_alertas' => count($alertas), 'alertas' => $alertas], JSON_UNESCAPED_UNICODE);
        exit;

    case 'listar_alertas':
        $stmt = $pdo->prepare("SELECT * FROM plugin_pulso_antifraude WHERE station_id = :sid AND resolvido = 0 ORDER BY severidade DESC, data_deteccao DESC LIMIT 50");
        $stmt->execute(['sid' => $stationId]);
        echo json_encode(['status' => 'ok', 'alertas' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
        exit;

    case 'resolver_alerta':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $b = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare("UPDATE plugin_pulso_antifraude SET resolvido = 1 WHERE id = :id")->execute(['id' => (int)$b['id']]);
        echo json_encode(['status' => 'ok']);
        exit;

    case 'bloquear_ouvinte':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $b = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare("UPDATE plugin_pulso_ouvintes SET bloqueado = 1, motivo_bloqueio = :motivo WHERE id = :id")
            ->execute(['id' => (int)$b['ouvinte_id'], 'motivo' => $b['motivo'] ?? 'Bloqueado pelo sistema']);
        echo json_encode(['status' => 'ok']);
        exit;

    case 'desbloquear_ouvinte':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $b = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare("UPDATE plugin_pulso_ouvintes SET bloqueado = 0, motivo_bloqueio = NULL WHERE id = :id")
            ->execute(['id' => (int)$b['ouvinte_id']]);
        echo json_encode(['status' => 'ok']);
        exit;

    // ============================================================
    // RELATORIOS
    // ============================================================
    case 'gerar_relatorio':
        $dataRef = $_GET['data'] ?? date('Y-m-d');
        
        // Total participacoes do dia
        $r1 = $pdo->prepare("SELECT COUNT(*) as c FROM plugin_pulso_participacoes WHERE station_id = :sid AND DATE(data_participacao) = :d");
        $r1->execute(['sid' => $stationId, 'd' => $dataRef]);
        $totalPart = (int)$r1->fetch()['c'];

        // Ouvintes unicos
        $r2 = $pdo->prepare("SELECT COUNT(DISTINCT ouvinte_id) as c FROM plugin_pulso_participacoes WHERE station_id = :sid AND DATE(data_participacao) = :d");
        $r2->execute(['sid' => $stationId, 'd' => $dataRef]);
        $ouvUnicos = (int)$r2->fetch()['c'];

        // Novos ouvintes
        $r3 = $pdo->prepare("SELECT COUNT(*) as c FROM plugin_pulso_ouvintes WHERE station_id = :sid AND DATE(data_registo) = :d");
        $r3->execute(['sid' => $stationId, 'd' => $dataRef]);
        $novos = (int)$r3->fetch()['c'];

        // Lidas e skip
        $r4 = $pdo->prepare("SELECT SUM(lido_no_ar) as lidas, SUM(skip) as skips FROM plugin_pulso_participacoes WHERE station_id = :sid AND DATE(data_participacao) = :d");
        $r4->execute(['sid' => $stationId, 'd' => $dataRef]);
        $ls = $r4->fetch();
        $lidas = (int)($ls['lidas'] ?? 0);
        $skips = (int)($ls['skips'] ?? 0);

        // Musica mais pedida
        $r5 = $pdo->prepare("SELECT descricao, COUNT(*) as c FROM plugin_pulso_participacoes WHERE station_id = :sid AND DATE(data_participacao) = :d AND tipo = 'pedido_musica' GROUP BY descricao ORDER BY c DESC LIMIT 1");
        $r5->execute(['sid' => $stationId, 'd' => $dataRef]);
        $topMusic = $r5->fetch();
        $musicaMaisPedida = $topMusic ? $topMusic['descricao'] : null;

        // Ouvinte mais activo
        $r6 = $pdo->prepare("SELECT o.nome, COUNT(*) as c FROM plugin_pulso_participacoes p JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id WHERE p.station_id = :sid AND DATE(p.data_participacao) = :d GROUP BY p.ouvinte_id ORDER BY c DESC LIMIT 1");
        $r6->execute(['sid' => $stationId, 'd' => $dataRef]);
        $topOuv = $r6->fetch();
        $ouvMaisActivo = $topOuv ? $topOuv['nome'] : null;

        // Sorteios
        $r7 = $pdo->prepare("SELECT COUNT(DISTINCT CONCAT(promocao_id, '-', data_sorteio)) as c FROM plugin_pulso_sorteios WHERE station_id = :sid AND DATE(data_sorteio) = :d");
        $r7->execute(['sid' => $stationId, 'd' => $dataRef]);
        $sorteios = (int)$r7->fetch()['c'];

        // Alertas fraude
        $r8 = $pdo->prepare("SELECT COUNT(*) as c FROM plugin_pulso_antifraude WHERE station_id = :sid AND DATE(data_deteccao) = :d");
        $r8->execute(['sid' => $stationId, 'd' => $dataRef]);
        $alertasFraude = (int)$r8->fetch()['c'];

        // Top 5 ouvintes do dia
        $r9 = $pdo->prepare("SELECT o.nome, o.pontos, COUNT(*) as participacoes FROM plugin_pulso_participacoes p JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id WHERE p.station_id = :sid AND DATE(p.data_participacao) = :d GROUP BY p.ouvinte_id ORDER BY participacoes DESC LIMIT 5");
        $r9->execute(['sid' => $stationId, 'd' => $dataRef]);
        $top5 = $r9->fetchAll();

        // Guardar/actualizar relatorio
        $pdo->prepare("INSERT INTO plugin_pulso_relatorios (station_id, data_ref, tipo, total_participacoes, total_ouvintes_unicos, novos_ouvintes, dedicatorias_lidas, dedicatorias_skip, musica_mais_pedida, ouvinte_mais_activo, sorteios_realizados, alertas_fraude, dados_extra)
            VALUES (:sid, :d, 'diario', :tp, :ou, :no, :li, :sk, :mm, :oa, :sr, :af, :de)
            ON DUPLICATE KEY UPDATE total_participacoes=:tp2, total_ouvintes_unicos=:ou2, novos_ouvintes=:no2, dedicatorias_lidas=:li2, dedicatorias_skip=:sk2, musica_mais_pedida=:mm2, ouvinte_mais_activo=:oa2, sorteios_realizados=:sr2, alertas_fraude=:af2, dados_extra=:de2")
            ->execute([
                'sid'=>$stationId,'d'=>$dataRef,'tp'=>$totalPart,'ou'=>$ouvUnicos,'no'=>$novos,'li'=>$lidas,'sk'=>$skips,
                'mm'=>$musicaMaisPedida,'oa'=>$ouvMaisActivo,'sr'=>$sorteios,'af'=>$alertasFraude,'de'=>json_encode($top5),
                'tp2'=>$totalPart,'ou2'=>$ouvUnicos,'no2'=>$novos,'li2'=>$lidas,'sk2'=>$skips,
                'mm2'=>$musicaMaisPedida,'oa2'=>$ouvMaisActivo,'sr2'=>$sorteios,'af2'=>$alertasFraude,'de2'=>json_encode($top5)
            ]);

        $rel = [
            'data' => $dataRef,
            'total_participacoes' => $totalPart,
            'ouvintes_unicos' => $ouvUnicos,
            'novos_ouvintes' => $novos,
            'dedicatorias_lidas' => $lidas,
            'dedicatorias_skip' => $skips,
            'musica_mais_pedida' => $musicaMaisPedida,
            'ouvinte_mais_activo' => $ouvMaisActivo,
            'sorteios_realizados' => $sorteios,
            'alertas_fraude' => $alertasFraude,
            'top5_ouvintes' => $top5
        ];

        echo json_encode(['status' => 'ok', 'relatorio' => $rel], JSON_UNESCAPED_UNICODE);
        exit;

    case 'listar_relatorios':
        $stmt = $pdo->prepare("SELECT * FROM plugin_pulso_relatorios WHERE station_id = :sid ORDER BY data_ref DESC LIMIT 30");
        $stmt->execute(['sid' => $stationId]);
        echo json_encode(['status' => 'ok', 'relatorios' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
        exit;

    default:
        echo json_encode(['erro' => 'Accao desconhecida', 'accoes' => [
            'listar_promocoes','criar_promocao','activar_promocao','encerrar_promocao',
            'participar','sortear','historico_sorteios',
            'scan_fraude','listar_alertas','resolver_alerta','bloquear_ouvinte','desbloquear_ouvinte',
            'gerar_relatorio','listar_relatorios'
        ]]);
}
