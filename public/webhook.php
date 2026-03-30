<?php
header('Content-Type: application/json; charset=utf-8');

$logFile = '/tmp/pulso-webhook-debug.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . " - Request\n", FILE_APPEND);

$json = file_get_contents('php://input');
file_put_contents($logFile, "Payload: $json\n", FILE_APPEND);

// Validar API Key
$apiKey = $_SERVER['HTTP_X_PULSO_KEY'] ?? '';
$expectedKey = 'PULSO_RNB_2024_SECRET';

if (empty($apiKey) || $apiKey !== $expectedKey) {
    http_response_code(401);
    echo json_encode(['status' => 'erro', 'erro' => 'API Key inválida']);
    file_put_contents($logFile, "❌ API Key inválida\n", FILE_APPEND);
    exit;
}

$data = json_decode($json, true);
if (!$data || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['status' => 'erro', 'erro' => 'Payload inválido']);
    exit;
}

$pdo = new PDO("mysql:host=127.0.0.1;dbname=azuracast;charset=utf8mb4", "azuracast", "CKxR234fxpJG");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stationId = 1;

if ($data['action'] === 'ping') {
    echo json_encode(['status' => 'ok', 'mensagem' => 'PULSO está online! 🎉']);
    exit;
}

if ($data['action'] === 'nova_dedicatoria') {
    $dados = $data['dados'] ?? [];
    
    $stmt = $pdo->prepare("SELECT id FROM plugin_pulso_ouvintes WHERE telefone = ? AND station_id = ?");
    $telefone = $dados['telefone'] ?: $dados['ip'] ?: 'sem-tel-' . time();
    $stmt->execute([$telefone, $stationId]);
    $ouvinte = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isNovo = false;
    if (!$ouvinte) {
        $stmt = $pdo->prepare("INSERT INTO plugin_pulso_ouvintes (station_id, nome, telefone, email, data_registo, ip) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([$stationId, $dados['nome'] ?: 'Anónimo', $telefone, $dados['email'] ?: null, $dados['ip'] ?: null]);
        $ouvinteId = (int)$pdo->lastInsertId();
        $isNovo = true;
    } else {
        $ouvinteId = (int)$ouvinte['id'];
    }
    
    $descricao = ($dados['mensagem'] ?? '') . (isset($dados['musica']) ? ' | Música: ' . $dados['musica'] : '');
    $pontosGanhos = $isNovo ? 10 : 5;
    
    $stmt = $pdo->prepare("INSERT INTO plugin_pulso_participacoes (station_id, ouvinte_id, tipo, descricao, data_participacao, lido_no_ar, skip, pontos_ganhos) VALUES (?, ?, 'pedido_musica', ?, NOW(), 0, 0, ?)");
    $stmt->execute([$stationId, $ouvinteId, $descricao, $pontosGanhos]);
    
    $participacaoId = (int)$pdo->lastInsertId();
    
    $stmt = $pdo->prepare("UPDATE plugin_pulso_ouvintes SET pontos = pontos + ? WHERE id = ?");
    $stmt->execute([$pontosGanhos, $ouvinteId]);
    
    file_put_contents($logFile, "✅ ID $participacaoId | Ouvinte $ouvinteId | +$pontosGanhos pts\n", FILE_APPEND);
    
    echo json_encode(['status' => 'ok', 'resultado' => ['ouvinte_id' => $ouvinteId, 'participacao_id' => $participacaoId, 'pontos_ganhos' => $pontosGanhos, 'is_novo' => $isNovo]]);
    exit;
}

echo json_encode(['status' => 'erro', 'erro' => 'Action desconhecida']);
