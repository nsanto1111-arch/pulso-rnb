<?php
date_default_timezone_set("Africa/Luanda");
/**
 * PULSO Widget API - Versao completa
 * Retorna: programa no ar, musica actual, carrossel com variaveis processadas
 */
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$stationId = isset($_GET['station_id']) ? (int)$_GET['station_id'] : 1;

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=azuracast;charset=utf8mb4", "azuracast", "CKxR234fxpJG",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'DB error']);
    exit;
}

// =============================================
// 1. PROGRAMA NO AR
// =============================================
$now = date('H:i:s');
$dias = ['domingo','segunda','terca','quarta','quinta','sexta','sabado'];
$dia = $dias[(int)date('w')];

$stmt = $pdo->prepare("
    SELECT p.id, p.nome, p.descricao, p.banner, p.hora_inicio, p.hora_fim,
           GROUP_CONCAT(DISTINCT l.nome SEPARATOR ', ') as locutores,
           (SELECT l2.foto FROM plugin_prog_programa_locutor pl2
            JOIN plugin_prog_locutores l2 ON l2.id = pl2.locutor_id
            WHERE pl2.programa_id = p.id AND pl2.is_principal = 1 LIMIT 1) as foto_locutor
    FROM plugin_prog_programas p
    LEFT JOIN plugin_prog_programa_locutor pl ON pl.programa_id = p.id
    LEFT JOIN plugin_prog_locutores l ON l.id = pl.locutor_id
    WHERE p.station_id = ?
      AND p.ativo = 1
      AND p.hora_inicio <= ?
      AND p.hora_fim > ?
      AND JSON_CONTAINS(p.dias_semana, ?)
    GROUP BY p.id
    ORDER BY p.hora_inicio ASC
    LIMIT 1
");
$stmt->execute([$stationId, $now, $now, json_encode($dia)]);
$programaNoAr = $stmt->fetch();

if ($programaNoAr) {
    $programaNome = $programaNoAr['nome'];
    $locutor = $programaNoAr['locutores'] ?: '';
    $horaInicio = substr($programaNoAr['hora_inicio'], 0, 5);
    $horaFim = substr($programaNoAr['hora_fim'], 0, 5);
    $banner = $programaNoAr['banner'];
    $fotoLocutor = $programaNoAr['foto_locutor'];
} else {
    $programaNome = 'Programacao Musical';
    $locutor = '';
    $horaInicio = '';
    $horaFim = '';
    $banner = null;
    $fotoLocutor = null;
}

// =============================================
// 2. MUSICA ACTUAL (Now Playing)
// =============================================
$nowPlaying = ['artista' => '', 'titulo' => '', 'song' => 'Radio New Band', 'album' => '', 'art' => ''];
try {
    $ctx = stream_context_create(['http' => ['timeout' => 2]]);
    $json = @file_get_contents("http://localhost/api/nowplaying/{$stationId}", false, $ctx);
    if ($json) {
        $data = json_decode($json, true);
        if (isset($data['now_playing']['song'])) {
            $song = $data['now_playing']['song'];
            $nowPlaying = [
                'artista' => $song['artist'] ?? '',
                'titulo' => $song['title'] ?? '',
                'song' => trim(($song['artist'] ?? '') . ' - ' . ($song['title'] ?? ''), ' -'),
                'album' => $song['album'] ?? '',
                'art' => $song['art'] ?? '',
            ];
        }
    }
} catch (Exception $e) {}

// =============================================
// 3. SAUDACAO
// =============================================
$hora = (int)date('H');
if ($hora >= 5 && $hora < 12) $saudacao = 'Bom dia';
elseif ($hora >= 12 && $hora < 18) $saudacao = 'Boa tarde';
else $saudacao = 'Boa noite';

// =============================================
// 4. PROCESSAR VARIAVEIS
// =============================================
function processarVariaveis($texto, $programa, $locutor, $saudacao, $nowPlaying) {
    $vars = [
        '{programa}' => $programa,
        '{locutor}' => $locutor,
        '{saudacao}' => $saudacao,
        '{musica}' => $nowPlaying['song'],
        '{artista}' => $nowPlaying['artista'],
        '{titulo}' => $nowPlaying['titulo'],
    ];
    return str_replace(array_keys($vars), array_values($vars), $texto);
}

// =============================================
// 5. ESTADOS FIXOS (programa + musica)
// =============================================
$estados = [];

// Estado 1: Programa no ar
$linha1Prog = $horaInicio && $horaFim ? "{$horaInicio} - {$horaFim}" : 'No Ar';
$linha2Prog = $locutor ? "{$programaNome} com {$locutor}" : $programaNome;
$estados[] = ['tipo' => 'programa', 'linha1' => $linha1Prog, 'linha2' => $linha2Prog];

// Estado 2: Musica actual
$estados[] = ['tipo' => 'musica', 'linha1' => 'A tocar agora', 'linha2' => $nowPlaying['song']];

// =============================================
// 6. CARROSSEL (1 mensagem aleatoria, com variaveis processadas)
// =============================================
$stmt2 = $pdo->prepare("
    SELECT linha1, linha2, tipo, prioridade
    FROM plugin_prog_carrossel
    WHERE station_id = ?
      AND ativo = 1
      AND hora_inicio <= ?
      AND hora_fim >= ?
      AND JSON_CONTAINS(dias_semana, ?)
    ORDER BY RAND()
    LIMIT 1
");
$stmt2->execute([$stationId, $now, $now, json_encode($dia)]);
$msg = $stmt2->fetch();

if ($msg) {
    $msg['linha1'] = processarVariaveis($msg['linha1'], $programaNome, $locutor, $saudacao, $nowPlaying);
    $msg['linha2'] = processarVariaveis($msg['linha2'], $programaNome, $locutor, $saudacao, $nowPlaying);
    $estados[] = $msg;
}

// =============================================
// RESPOSTA
// =============================================
echo json_encode([
    'success' => true,
    'data' => [
        'estados' => $estados,
        'programa' => [
            'nome' => $programaNome,
            'locutor' => $locutor,
            'hora_inicio' => $horaInicio,
            'hora_fim' => $horaFim,
            'banner' => $banner,
            'foto_locutor' => $fotoLocutor,
        ],
        'musica' => $nowPlaying,
        'intervalo_segundos' => 8,
    ],
], JSON_UNESCAPED_UNICODE);
