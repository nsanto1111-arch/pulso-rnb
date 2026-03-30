<?php
/**
 * Webhook de Sincronização WordPress → Programação
 */

header('Content-Type: application/json');

// Config BD
$host = '127.0.0.1';
$port = 3306;
$dbname = 'azuracast';
$user = 'azuracast';
$pass = 'CKxR234fxpJG';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$stationId = 1; // Radio New Band

// Receber dados do WordPress
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$type = $data['type']; // 'programa', 'locutor', 'cronograma'
$action = $data['action'] ?? 'save'; // 'save' ou 'delete'

switch ($type) {
    case 'programa':
        syncPrograma($pdo, $stationId, $data, $action);
        break;
    
    case 'locutor':
        syncLocutor($pdo, $stationId, $data, $action);
        break;
    
    case 'cronograma':
        syncCronograma($pdo, $stationId, $data, $action);
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown type']);
        exit;
}

function syncPrograma($pdo, $stationId, $data, $action) {
    $wpId = $data['wp_id'];
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM plugin_programacao_programas WHERE station_id = ? AND wp_post_id = ?");
        $stmt->execute([$stationId, $wpId]);
        echo json_encode(['success' => true, 'message' => 'Programa deleted']);
        return;
    }
    
    // Verificar se já existe
    $stmt = $pdo->prepare("SELECT id FROM plugin_programacao_programas WHERE station_id = ? AND wp_post_id = ?");
    $stmt->execute([$stationId, $wpId]);
    $existing = $stmt->fetch();
    
    $nome = $data['titulo'] ?? 'Sem título';
    $descricao = $data['descricao'] ?? '';
    $imagem = $data['imagem'] ?? null;
    
    if ($existing) {
        // Atualizar
        $stmt = $pdo->prepare("UPDATE plugin_programacao_programas SET nome = ?, descricao = ?, imagem_url = ? WHERE id = ?");
        $stmt->execute([$nome, $descricao, $imagem, $existing['id']]);
        echo json_encode(['success' => true, 'message' => 'Programa updated', 'id' => $existing['id']]);
    } else {
        // Criar
        $stmt = $pdo->prepare("INSERT INTO plugin_programacao_programas (station_id, nome, descricao, imagem_url, wp_post_id, activo) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$stationId, $nome, $descricao, $imagem, $wpId]);
        echo json_encode(['success' => true, 'message' => 'Programa created', 'id' => $pdo->lastInsertId()]);
    }
}

function syncLocutor($pdo, $stationId, $data, $action) {
    $wpId = $data['wp_id'];
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM plugin_programacao_locutores WHERE station_id = ? AND wp_post_id = ?");
        $stmt->execute([$stationId, $wpId]);
        echo json_encode(['success' => true, 'message' => 'Locutor deleted']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM plugin_programacao_locutores WHERE station_id = ? AND wp_post_id = ?");
    $stmt->execute([$stationId, $wpId]);
    $existing = $stmt->fetch();
    
    $nome = $data['titulo'] ?? 'Sem nome';
    $bio = $data['bio'] ?? '';
    $foto = $data['foto'] ?? null;
    $email = $data['email'] ?? null;
    
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE plugin_programacao_locutores SET nome = ?, bio = ?, foto_url = ?, email = ? WHERE id = ?");
        $stmt->execute([$nome, $bio, $foto, $email, $existing['id']]);
        echo json_encode(['success' => true, 'message' => 'Locutor updated', 'id' => $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO plugin_programacao_locutores (station_id, nome, bio, foto_url, email, wp_post_id, activo) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$stationId, $nome, $bio, $foto, $email, $wpId]);
        echo json_encode(['success' => true, 'message' => 'Locutor created', 'id' => $pdo->lastInsertId()]);
    }
}

function syncCronograma($pdo, $stationId, $data, $action) {
    // Cronograma = horários do programa
    $programaWpId = $data['programa_wp_id'];
    $diaSemana = $data['dia_semana']; // 0-6
    $horaInicio = $data['hora_inicio']; // HH:MM
    $horaFim = $data['hora_fim']; // HH:MM
    
    // Buscar programa pelo wp_id
    $stmt = $pdo->prepare("SELECT id FROM plugin_programacao_programas WHERE wp_post_id = ?");
    $stmt->execute([$programaWpId]);
    $programa = $stmt->fetch();
    
    if (!$programa) {
        http_response_code(404);
        echo json_encode(['error' => 'Programa not found']);
        return;
    }
    
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM plugin_programacao_horarios WHERE programa_id = ? AND dia_semana = ?");
        $stmt->execute([$programa['id'], $diaSemana]);
        echo json_encode(['success' => true, 'message' => 'Horário deleted']);
        return;
    }
    
    // Verificar se já existe
    $stmt = $pdo->prepare("SELECT id FROM plugin_programacao_horarios WHERE programa_id = ? AND dia_semana = ?");
    $stmt->execute([$programa['id'], $diaSemana]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE plugin_programacao_horarios SET hora_inicio = ?, hora_fim = ? WHERE id = ?");
        $stmt->execute([$horaInicio, $horaFim, $existing['id']]);
        echo json_encode(['success' => true, 'message' => 'Horário updated']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO plugin_programacao_horarios (programa_id, dia_semana, hora_inicio, hora_fim) VALUES (?, ?, ?, ?)");
        $stmt->execute([$programa['id'], $diaSemana, $horaInicio, $horaFim]);
        echo json_encode(['success' => true, 'message' => 'Horário created']);
    }
}
