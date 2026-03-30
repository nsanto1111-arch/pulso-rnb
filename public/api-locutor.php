<?php
header('Content-Type: application/json');
$action = $_GET['action'] ?? '';
$stationId = (int)($_GET['station_id'] ?? 1);
$pdo = new PDO("mysql:host=127.0.0.1;dbname=azuracast;charset=utf8mb4", "azuracast", "CKxR234fxpJG");

// NOW PLAYING
if ($action === 'nowplaying') {
    try {
        $ch = curl_init("http://127.0.0.1/api/nowplaying/{$stationId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $response = curl_exec($ch);
        @curl_close($ch);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['now_playing'])) {
                $current = $data['now_playing'];
                $next = $data['playing_next'] ?? null;
                echo json_encode([
                    'status' => 'ok',
                    'song' => [
                        'title' => $current['song']['title'] ?? 'Sem título',
                        'artist' => $current['song']['artist'] ?? 'Artista desconhecido',
                        'elapsed' => $current['elapsed'] ?? 0,
                        'duration' => $current['duration'] ?? 0,
                        'remaining' => max(0, ($current['duration'] ?? 0) - ($current['elapsed'] ?? 0))
                    ],
                    'next' => $next ? ['title' => $next['song']['title'] ?? '', 'artist' => $next['song']['artist'] ?? ''] : null,
                    'listeners' => $data['listeners']['current'] ?? 0
                ]);
                exit;
            }
        }
    } catch (Exception $e) {}
    echo json_encode(['status' => 'error', 'message' => 'Nowplaying indisponível']);
    exit;
}

// FILA — CORRIGIDA (MOSTRA TODAS AS PARTICIPAÇÕES NÃO LIDAS + CAMPOS OBRIGATÓRIOS)
if ($action === 'fila') {
    $stmt = $pdo->query("
        SELECT 
            p.*, 
            o.nome, 
            o.telefone, 
            o.bairro, 
            o.cidade,
            o.segmento,
            o.pontos as ouvinte_pontos,
            o.total_participacoes,
            CASE 
                WHEN o.segmento = 'novo' THEN 1
                ELSE 0
            END as is_novo,
            CASE
                WHEN p.lido_no_ar = 0 AND p.skip = 0 AND o.segmento = 'novo' THEN 100
                WHEN p.lido_no_ar = 0 AND p.skip = 0 THEN 50
                ELSE 10
            END as prioridade_calc,
            TIMESTAMPDIFF(MINUTE, p.data_participacao, NOW()) as tempo_relativo_raw,
            p.descricao as raw_descricao
        FROM plugin_pulso_participacoes p
        LEFT JOIN plugin_pulso_ouvintes o ON o.id = p.ouvinte_id
        WHERE p.station_id = {$stationId}
          AND (p.lido_no_ar = 0 AND p.skip = 0)
        ORDER BY prioridade_calc DESC, p.data_participacao DESC
        LIMIT 100
    ");
    
    $fila = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($fila as &$item) {
        $min = (int)$item['tempo_relativo_raw'];
        $item['tempo_relativo'] = $min < 60 ? $min . ' min' : floor($min / 60) . 'h';
        unset($item['tempo_relativo_raw']);
        
        $desc = @json_decode($item['raw_descricao'], true);
        $item['musica'] = $desc['musica'] ?? null;
        $item['mensagem'] = $desc['mensagem'] ?? null;
        $item['dica'] = $item['mensagem'] ? 'Dedicatória de ' . $item['nome'] : ($item['musica'] ? 'Pedido de música: ' . $item['musica'] : 'Participação do ouvinte');
        unset($item['raw_descricao']);
    }
    
    echo json_encode(['status' => 'ok', 'fila' => $fila]);
    exit;
}

// Marcar como lida
if ($action === 'marcar_lida') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->exec("UPDATE plugin_pulso_participacoes SET lido_no_ar = 1, lido_no_ar_data = NOW() WHERE id = {$id}");
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Skip (compatível com ambos os nomes)
if ($action === 'skip' || $action === 'marcar_skip') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->exec("UPDATE plugin_pulso_participacoes SET skip = 1 WHERE id = {$id}");
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// FICHA DO OUVINTE
if ($action === 'ouvinte') {
    $ouvinteId = (int)($_GET['id'] ?? 0);
    if ($ouvinteId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT *, DATEDIFF(NOW(), data_registo) as dias_ouvinte FROM plugin_pulso_ouvintes WHERE id = ?");
    $stmt->execute([$ouvinteId]);
    $ouvinte = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ouvinte) {
        echo json_encode(['status' => 'error', 'message' => 'Ouvinte não encontrado']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT p.*, TIMESTAMPDIFF(MINUTE, p.data_participacao, NOW()) as tempo_relativo_raw FROM plugin_pulso_participacoes p WHERE p.ouvinte_id = ? ORDER BY p.data_participacao DESC LIMIT 10");
    $stmt->execute([$ouvinteId]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($historico as &$h) {
        $min = (int)$h['tempo_relativo_raw'];
        $h['tempo_relativo'] = $min < 60 ? $min . ' min' : floor($min / 60) . 'h';
        unset($h['tempo_relativo_raw']);
        $desc = @json_decode($h['descricao'], true);
        $h['musica'] = $desc['musica'] ?? 'Dedicatória';
        $h['mensagem'] = $desc['mensagem'] ?? null;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM plugin_pulso_participacoes WHERE ouvinte_id = ? AND ganhou = 1");
    $stmt->execute([$ouvinteId]);
    $totalPremios = $stmt->fetchColumn();
    $sugestoes = [];
    if ($ouvinte['segmento'] === 'novo') $sugestoes[] = '🎉 Ouvinte novo! Dar boas-vindas';
    if ($ouvinte['total_participacoes'] > 5) $sugestoes[] = '⭐ Ouvinte frequente! Agradecer fidelidade';
    echo json_encode([
        'status' => 'ok',
        'ouvinte' => $ouvinte,
        'historico' => $historico,
        'total_premios' => (int)$totalPremios,
        'dias_ouvinte' => (int)$ouvinte['dias_ouvinte'],
        'sugestoes' => $sugestoes
    ]);
    exit;
}

// NOTAS
if ($action === 'notas') {
    $ouvinteId = (int)($_GET['id'] ?? 0);
    if ($ouvinteId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT *, DATE_FORMAT(data_criacao, '%d/%m %H:%i') as data_criacao FROM plugin_pulso_notas WHERE ouvinte_id = ? AND station_id = ? ORDER BY data_criacao DESC");
    $stmt->execute([$ouvinteId, $stationId]);
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'ok', 'notas' => $notas]);
    exit;
}

// GUARDAR NOTA
if ($action === 'guardar_nota') {
    $body = json_decode(file_get_contents('php://input'), true);
    $ouvinteId = (int)($body['ouvinte_id'] ?? 0);
    $nota = trim($body['nota'] ?? '');
    if ($ouvinteId <= 0 || empty($nota)) {
        echo json_encode(['status' => 'error', 'message' => 'Dados inválidos']);
        exit;
    }
    $stmt = $pdo->prepare("INSERT INTO plugin_pulso_notas (station_id, ouvinte_id, nota, criado_por, data_criacao) VALUES (?, ?, ?, 'locutor', NOW())");
    $stmt->execute([$stationId, $ouvinteId, $nota]);
    echo json_encode(['status' => 'ok', 'id' => $pdo->lastInsertId()]);
    exit;
}

// APAGAR NOTA
if ($action === 'apagar_nota') {
    $body = json_decode(file_get_contents('php://input'), true);
    $id = (int)($body['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM plugin_pulso_notas WHERE id = ? AND station_id = ?");
    $stmt->execute([$id, $stationId]);
    echo json_encode(['status' => 'ok']);
    exit;
}

// TOCAR (pesquisar na biblioteca)
if ($action === 'tocar') {
    $body = json_decode(file_get_contents('php://input'), true);
    $termo = trim($body['termo'] ?? '');
    if (empty($termo)) {
        echo json_encode(['status' => 'error', 'message' => 'Termo vazio']);
        exit;
    }
    try {
        $ch = curl_init("http://127.0.0.1/api/station/{$stationId}/files?search=" . urlencode($termo) . "&limit=5");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        $response = curl_exec($ch);
        @curl_close($ch);
        if ($response) {
            $data = json_decode($response, true);
            $resultados = [];
            if (is_array($data)) {
                foreach ($data as $item) {
                    $resultados[] = ['id' => $item['id'] ?? null, 'title' => $item['title'] ?? 'Sem título', 'artist' => $item['artist'] ?? 'Artista desconhecido', 'duration' => $item['duration'] ?? 0];
                }
            }
            echo json_encode(['status' => 'ok', 'resultados' => $resultados]);
            exit;
        }
    } catch (Exception $e) {}
    echo json_encode(['status' => 'ok', 'resultados' => []]);
    exit;
}

// REQUEST
if ($action === 'request') {
    $body = json_decode(file_get_contents('php://input'), true);
    $mediaId = (int)($body['media_id'] ?? 0);
    if ($mediaId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit;
    }
    try {
        $ch = curl_init("http://127.0.0.1/api/station/{$stationId}/requests");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['media_id' => $mediaId]));
        $response = curl_exec($ch);
        @curl_close($ch);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['success']) && $data['success']) {
                echo json_encode(['status' => 'ok']);
                exit;
            }
        }
    } catch (Exception $e) {}
    echo json_encode(['status' => 'error', 'message' => 'Falha ao enviar request']);
    exit;
}

// NOTIFICACOES (SORTEIOS)
if ($action === 'notificacoes') {
    try {
        $stmt = $pdo->query("SELECT 1 FROM pulso_notificacoes LIMIT 1");
        $exists = true;
    } catch (Exception $e) {
        $exists = false;
    }
    if ($exists) {
        $stmt = $pdo->query("SELECT * FROM pulso_notificacoes WHERE station_id = {$stationId} AND lida = 0 ORDER BY data_criacao DESC LIMIT 5");
        $notificacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $notificacoes = [];
    }
    echo json_encode(['status' => 'ok', 'notificacoes' => $notificacoes]);
    exit;
}

// Notificar sorteio
if ($action === 'notificar_sorteio') {
    $body = json_decode(file_get_contents('php://input'), true);
    $promocao  = $body['promocao'] ?? '';
    $premio    = $body['premio'] ?? '';
    $vencedores = $body['vencedores'] ?? [];
    if (!empty($vencedores)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO pulso_notificacoes (station_id, tipo, titulo, mensagem, dados, lida, data_criacao) VALUES (?, 'sorteio', ?, ?, ?, 0, NOW())");
            $stmt->execute([$stationId, '🏆 Sorteio: ' . $promocao, 'Vencedor: ' . $vencedores[0]['nome'], json_encode(['promocao' => $promocao, 'premio' => $premio, 'vencedores' => $vencedores])]);
        } catch (Exception $e) {}
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Sem vencedores']);
    }
    exit;
}

// Aniversariantes
if ($action === 'aniversariantes') {
    $stmt = $pdo->prepare("SELECT id, nome, telefone, TIMESTAMPDIFF(YEAR, data_nascimento, CURDATE()) as idade FROM plugin_pulso_ouvintes WHERE station_id = ? AND ativo = 1 AND data_nascimento IS NOT NULL AND DATE_FORMAT(data_nascimento,'%m-%d') = DATE_FORMAT(NOW(),'%m-%d')");
    $stmt->execute([$stationId]);
    $aniversariantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status' => 'ok', 'aniversariantes' => $aniversariantes, 'total' => count($aniversariantes)]);
    exit;
}


// CHAT entre departamentos e estúdio
if ($action === 'chat_mensagens') {
    try { $pdo->query("SELECT 1 FROM pulso_chat_studio LIMIT 1"); }
    catch(Exception $e) {
        $pdo->exec("CREATE TABLE pulso_chat_studio (
            id INT AUTO_INCREMENT PRIMARY KEY,
            station_id INT NOT NULL,
            autor VARCHAR(100),
            departamento ENUM('locutor','promocao','atendimento','comercial','admin') DEFAULT 'locutor',
            mensagem TEXT NOT NULL,
            urgente TINYINT DEFAULT 0,
            lida TINYINT DEFAULT 0,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_station (station_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $stmt = $pdo->prepare("SELECT * FROM pulso_chat_studio WHERE station_id=? ORDER BY data_criacao DESC LIMIT 50");
    $stmt->execute([$stationId]);
    $msgs = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    $pdo->prepare("UPDATE pulso_chat_studio SET lida=1 WHERE station_id=? AND departamento!='locutor'")->execute([$stationId]);
    echo json_encode(['status'=>'ok','mensagens'=>$msgs]);
    exit;
}

if ($action === 'chat_enviar') {
    $b = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("INSERT INTO pulso_chat_studio (station_id,autor,departamento,mensagem,urgente) VALUES (?,?,?,?,?)");
    $stmt->execute([$stationId, $b['autor']??'Locutor', $b['departamento']??'locutor', $b['mensagem']??'', $b['urgente']??0]);
    echo json_encode(['status'=>'ok','id'=>$pdo->lastInsertId()]);
    exit;
}

// NOTAS DE ESTÚDIO (jornalísticas/promocionais com controlo de leituras)
if ($action === 'notas_estudio') {
    try { $pdo->query("SELECT 1 FROM pulso_notas_estudio LIMIT 1"); }
    catch(Exception $e) {
        $pdo->exec("CREATE TABLE pulso_notas_estudio (
            id INT AUTO_INCREMENT PRIMARY KEY,
            station_id INT NOT NULL,
            titulo VARCHAR(255),
            conteudo TEXT NOT NULL,
            tipo ENUM('jornalistica','promocional','comercial','aviso','locutor') DEFAULT 'aviso',
            prioridade ENUM('normal','alta','urgente') DEFAULT 'normal',
            autor VARCHAR(100),
            hora_inicio TIME,
            hora_fim TIME,
            max_leituras INT DEFAULT 0,
            total_leituras INT DEFAULT 0,
            ativo TINYINT DEFAULT 1,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_station (station_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE pulso_notas_leituras (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nota_id INT,
            locutor VARCHAR(100),
            data_leitura DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
    $hora = date('H:i:s');
    $stmt = $pdo->prepare("SELECT n.*, COUNT(l.id) as leituras_hoje FROM pulso_notas_estudio n LEFT JOIN pulso_notas_leituras l ON l.nota_id=n.id AND DATE(l.data_leitura)=CURDATE() WHERE n.station_id=? AND n.ativo=1 AND (n.hora_inicio IS NULL OR n.hora_inicio<=?) AND (n.hora_fim IS NULL OR n.hora_fim>=?) AND (n.max_leituras=0 OR n.total_leituras<n.max_leituras) GROUP BY n.id ORDER BY FIELD(n.prioridade,'urgente','alta','normal'), n.data_criacao DESC");
    $stmt->execute([$stationId, $hora, $hora]);
    echo json_encode(['status'=>'ok','notas'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'nota_estudio_salvar') {
    $b = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("INSERT INTO pulso_notas_estudio (station_id,titulo,conteudo,tipo,prioridade,autor,hora_inicio,hora_fim,max_leituras) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$stationId,$b['titulo']??'Sem título',$b['conteudo']??'',$b['tipo']??'aviso',$b['prioridade']??'normal',$b['autor']??'Locutor',$b['hora_inicio']??null,$b['hora_fim']??null,(int)($b['max_leituras']??0)]);
    echo json_encode(['status'=>'ok','id'=>$pdo->lastInsertId()]);
    exit;
}

if ($action === 'nota_estudio_ler') {
    $b = json_decode(file_get_contents('php://input'), true);
    $id = (int)($b['id']??0);
    $pdo->prepare("INSERT INTO pulso_notas_leituras (nota_id,locutor) VALUES (?,?)")->execute([$id,$b['locutor']??'Locutor']);
    $pdo->prepare("UPDATE pulso_notas_estudio SET total_leituras=total_leituras+1 WHERE id=?")->execute([$id]);
    echo json_encode(['status'=>'ok']);
    exit;
}

// PRÉMIOS (ganhadores recentes + por entregar)
if ($action === 'premios_locutor') {
    // Programa actual com base no horário
    $horaAtual = date('H:i:s');
    $diaSigla  = ['domingo','segunda','terca','quarta','quinta','sexta','sabado'][(int)date('w')];

    $progAtual = null;
    $stmt2 = $pdo->prepare("SELECT nome,hora_inicio,hora_fim,dias_semana FROM plugin_prog_programas WHERE station_id=? AND ativo=1");
    $stmt2->execute([$stationId]);
    foreach($stmt2->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $dias = json_decode($p['dias_semana'] ?? '[]', true) ?: [];
        $dias = array_map(fn($x)=>str_replace(['-feira','-'],['',''],$x),$dias);
        if (in_array($diaSigla,$dias) && $horaAtual>=$p['hora_inicio'] && $horaAtual<=$p['hora_fim']) {
            $progAtual = $p; break;
        }
    }

    // Sorteios do programa actual (nas últimas 24h) ou todos se não há programa
    $stmt = $pdo->prepare("
        SELECT s.*, o.nome, o.telefone, o.bairro, o.cidade,
               pr.nome as promo_nome
        FROM plugin_pulso_sorteios s
        JOIN plugin_pulso_ouvintes o ON o.id = s.ouvinte_id
        LEFT JOIN plugin_pulso_promocoes pr ON pr.id = s.promocao_id
        WHERE s.station_id = ? AND s.resultado = 'vencedor'
        AND s.id IN (
            SELECT MAX(id) FROM plugin_pulso_sorteios
            WHERE resultado = 'vencedor' AND station_id = ?
            GROUP BY promocao_id
        )
        AND s.data_sorteio >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND (s.anunciado_locutor IS NULL OR s.anunciado_locutor = 0)
        ORDER BY s.data_sorteio DESC
        LIMIT 10
    ");
    $stmt->execute([$stationId, $stationId]);
    $premios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'status'       => 'ok',
        'premios'      => $premios,
        'por_entregar' => count($premios),
        'prog_atual'   => $progAtual ? $progAtual['nome'] : null,
    ]);
    exit;
}

if ($action === 'premios_anunciar') {
    $b  = json_decode(file_get_contents('php://input'), true);
    $id = (int)($b['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("UPDATE plugin_pulso_sorteios SET anunciado_locutor=1 WHERE id=?")->execute([$id]);
        echo json_encode(['status'=>'ok']);
    } else {
        echo json_encode(['status'=>'error']);
    }
    exit;
}

// ESCALA DE TRABALHO
if ($action === 'escala') {
    $hoje = (int)date('w'); // 0=Dom...6=Sab
    $diasMap = [0=>'domingo',1=>'segunda',2=>'terca',3=>'quarta',4=>'quinta',5=>'sexta',6=>'sabado'];

    $semana = [];
    // Buscar todos os programas activos
    $stmt = $pdo->prepare("SELECT nome,hora_inicio,hora_fim,dias_semana FROM plugin_prog_programas WHERE station_id=? AND ativo=1 ORDER BY hora_inicio ASC");
    $stmt->execute([$stationId]);
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    for ($d = 0; $d < 7; $d++) {
        $diaSigla = $diasMap[$d];
        $semana[$d] = [];
        foreach ($todos as $p) {
            $dias = json_decode($p['dias_semana'] ?? '[]', true) ?: [];
            // Normalizar: terca-feira -> terca, sabado, domingo
            $diasNorm = array_map(fn($x) => str_replace(['-feira','-'],['',''],$x), $dias);
            if (in_array($diaSigla, $diasNorm)) {
                $semana[$d][] = [
                    'programa'    => $p['nome'],
                    'locutor_nome'=> '',
                    'hora_inicio' => $p['hora_inicio'],
                    'hora_fim'    => $p['hora_fim'],
                    'cor'         => '#00b4ff',
                ];
            }
        }
    }
    echo json_encode(['status'=>'ok','semana'=>$semana,'hoje'=>$hoje]);
    exit;
}

// PROGRAMAÇÃO DO DIA (playlist)
if ($action === 'programacao_dia') {
    $ch = curl_init("http://127.0.0.1/api/station/{$stationId}/history?seconds=7200");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-API-Key: 62beaa587a281c68:c3e116e7fe103515f089aadcb44a5a6c']);
    $r = curl_exec($ch); @curl_close($ch);
    $historia = [];
    if ($r) {
        $data = json_decode($r, true);
        if (is_array($data)) foreach(array_slice($data,0,30) as $item) {
            $historia[] = ['titulo'=>$item['song']['title']??'','artista'=>$item['song']['artist']??'','tocou_em'=>$item['played_at']??0];
        }
    }
    echo json_encode(['status'=>'ok','historico'=>$historia]);
    exit;
}

// CHAT não lidas (badge)
if ($action === 'chat_nao_lidas') {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pulso_chat_studio WHERE station_id=? AND departamento!='locutor' AND lida=0");
        $stmt->execute([$stationId]);
        echo json_encode(['status'=>'ok','total'=>(int)$stmt->fetchColumn()]);
    } catch(Exception $e) { echo json_encode(['status'=>'ok','total'=>0]); }
    exit;
}

// Marcar notificação como lida
if ($action === 'marcar_notificacao_lida') {
    $b = json_decode(file_get_contents('php://input'), true);
    $id = (int)($b['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("UPDATE pulso_notificacoes SET lida=1 WHERE id=?")->execute([$id]);
    }
    echo json_encode(['status'=>'ok']);
    exit;
}

// Apagar nota
if ($action === 'nota_apagar') {
    $b = json_decode(file_get_contents('php://input'), true);
    $id = (int)($b['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("UPDATE pulso_notas_estudio SET ativo=0 WHERE id=? AND station_id=?")->execute([$id,$stationId]);
        echo json_encode(['status'=>'ok']);
    } else { echo json_encode(['status'=>'error']); }
    exit;
}

// Editar nota
if ($action === 'nota_editar') {
    $b = json_decode(file_get_contents('php://input'), true);
    $id = (int)($b['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare("UPDATE pulso_notas_estudio SET titulo=?,conteudo=?,prioridade=?,tipo=? WHERE id=? AND station_id=?")
            ->execute([$b['titulo']??'',$b['conteudo']??'',$b['prioridade']??'normal',$b['tipo']??'aviso',$id,$stationId]);
        echo json_encode(['status'=>'ok']);
    } else { echo json_encode(['status'=>'error']); }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Ação desconhecida']);
