<?php
header('Content-Type: application/json');
$logFile = '/tmp/sync-wp-debug.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Request\n", FILE_APPEND);

$json = file_get_contents('php://input');
file_put_contents($logFile, "Payload: $json\n", FILE_APPEND);

$pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=azuracast;charset=utf8mb4", "azuracast", "CKxR234fxpJG");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$data = json_decode($json, true);
if (!$data || !isset($data['type'])) {
    die(json_encode(['error' => 'Invalid']));
}

function gerarSlug($texto) {
    $slug = strtolower($texto);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

$stationId = 1;

if ($data['type'] === 'programa') {
    $wpId = $data['wp_id'];
    
    if (($data['action'] ?? 'save') === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM plugin_prog_programas WHERE station_id = ? AND wp_post_id = ?");
        $stmt->execute([$stationId, $wpId]);
        file_put_contents($logFile, "✅ Deleted\n", FILE_APPEND);
        echo json_encode(['success' => true]);
        exit;
    }
    
    // USAR TABELA CORRETA: plugin_prog_programas
    $stmt = $pdo->prepare("SELECT id FROM plugin_prog_programas WHERE station_id = ? AND wp_post_id = ?");
    $stmt->execute([$stationId, $wpId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // UPDATE - campos: nome, descricao, banner
        $stmt = $pdo->prepare("UPDATE plugin_prog_programas SET nome = ?, descricao = ?, banner = ? WHERE id = ?");
        $stmt->execute([$data['titulo'], $data['descricao'], $data['imagem'], $existing['id']]);
        file_put_contents($logFile, "✅ Updated {$existing['id']}\n", FILE_APPEND);
        echo json_encode(['success' => true, 'id' => $existing['id']]);
    } else {
        // INSERT - campos: hora_inicio, hora_fim (não horario_)
        $stmt = $pdo->prepare("INSERT INTO plugin_prog_programas 
            (station_id, nome, descricao, banner, hora_inicio, hora_fim, dias_semana, wp_post_id, ativo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([
            $stationId,
            $data['titulo'],
            $data['descricao'],
            $data['imagem'],
            '00:00:00',
            '23:59:59',
            '[]',
            $wpId
        ]);
        $newId = $pdo->lastInsertId();
        file_put_contents($logFile, "✅ Created $newId\n", FILE_APPEND);
        echo json_encode(['success' => true, 'id' => $newId]);
    }
    exit;
}

if ($data['type'] === 'locutor') {
    $wpId = $data['wp_id'];
    
    if (($data['action'] ?? 'save') === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM plugin_prog_locutores WHERE station_id = ? AND wp_post_id = ?");
        $stmt->execute([$stationId, $wpId]);
        echo json_encode(['success' => true]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM plugin_prog_locutores WHERE station_id = ? AND wp_post_id = ?");
    $stmt->execute([$stationId, $wpId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE plugin_prog_locutores SET nome = ?, bio = ?, foto = ?, email = ? WHERE id = ?");
        $stmt->execute([$data['titulo'], $data['bio'], $data['foto'], $data['email'], $existing['id']]);
        echo json_encode(['success' => true, 'id' => $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO plugin_prog_locutores (station_id, nome, bio, foto, email, wp_post_id, ativo) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$stationId, $data['titulo'], $data['bio'], $data['foto'], $data['email'], $wpId]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    }
    exit;
}


if ($data['type'] === 'dedicatoria') {
    // Buscar ou criar ouvinte
    $stmt = $pdo->prepare("SELECT id FROM plugin_pulso_ouvintes WHERE telefone = ? AND station_id = ?");
    $stmt->execute([$data['telefone'], $stationId]);
    $ouvinte = $stmt->fetch();
    
    if (!$ouvinte) {
        // Criar novo ouvinte
        $stmt = $pdo->prepare("INSERT INTO plugin_pulso_ouvintes (station_id, nome, telefone, data_registo) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$stationId, $data['nome'], $data['telefone']]);
        $ouvinteId = $pdo->lastInsertId();
    } else {
        $ouvinteId = $ouvinte['id'];
    }
    
    // Criar participação (dedicatória = tipo 'pedido_musica')
    $descricao = ($data['mensagem'] ?? '') . (isset($data['musica']) ? ' | Música: ' . $data['musica'] : '');
    
    $stmt = $pdo->prepare("INSERT INTO plugin_pulso_participacoes 
        (station_id, ouvinte_id, tipo, descricao, data_participacao, lido_no_ar, skip) 
        VALUES (?, ?, 'pedido_musica', ?, NOW(), 0, 0)");
    $stmt->execute([$stationId, $ouvinteId, $descricao]);
    
    $partId = $pdo->lastInsertId();
    file_put_contents($logFile, "✅ Dedicatória criada: ID $partId
", FILE_APPEND);
    
    echo json_encode(['success' => true, 'participacao_id' => $partId]);
    exit;
}

echo json_encode(['error' => 'Unknown type']);
