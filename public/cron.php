<?php
/**
 * PULSO — Cron Job
 * Rádio New Band · programacao-plugin
 *
 * Cron recomendado (cada minuto):
 *   * * * * * php /var/azuracast/www/plugins/programacao-plugin/public/cron.php >> /tmp/pulso_cron.log 2>&1
 */

declare(strict_types=1);

// ── Segurança ────────────────────────────────────────────────
if (PHP_SAPI !== 'cli') {
    $key         = $_GET['key'] ?? '';
    $expectedKey = md5('pulso_cron_rnb_' . date('Y-m-d'));
    if (!hash_equals($expectedKey, $key)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// ── Bootstrap ────────────────────────────────────────────────
$pluginRoot = dirname(__DIR__);
require_once '/var/azuracast/www/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$conn = DriverManager::getConnection([
    'dbname'   => 'azuracast',
    'user'     => 'azuracast',
    'password' => 'CKxR234fxpJG',
    'host'     => '127.0.0.1',
    'driver'   => 'pdo_mysql',
    'charset'  => 'utf8mb4',
]);

// ── Logger ───────────────────────────────────────────────────
$logFile  = '/tmp/pulso_cron.log';
$maxBytes = 2 * 1024 * 1024; // 2 MB — rotação automática
if (file_exists($logFile) && filesize($logFile) > $maxBytes) {
    rename($logFile, $logFile . '.old');
}

$startedAt = microtime(true);
$log       = [];

$write = function(string $line) use (&$log): void {
    $log[] = $line;
    // Flush imediato em CLI para acompanhar em tempo real
    if (PHP_SAPI === 'cli') {
        echo $line . "\n";
    }
};

$write('┌─────────────────────────────────────────');
$write('│ PULSO Cron · ' . date('Y-m-d H:i:s'));
$write('└─────────────────────────────────────────');

// ════════════════════════════════════════════════════════════
// 1. SORTEIOS AUTOMÁTICOS
// ════════════════════════════════════════════════════════════
$write('');
$write('▶ Verificar sorteios automáticos...');

try {
    $promocoes = $conn->fetchAllAssociative(
        "SELECT * FROM plugin_pulso_promocoes
         WHERE estado = 'activa'
           AND sorteio_automatico = 1
           AND sorteio_realizado  = 0
           AND data_fim IS NOT NULL
           AND data_fim <= NOW()"
    );

    if (empty($promocoes)) {
        $write('  Sem sorteios pendentes.');
    }

    foreach ($promocoes as $p) {
        $write("  → [{$p['id']}] {$p['nome']}");

        // Participantes com pontos para sorteio ponderado
        $participantes = $conn->fetchAllAssociative(
            "SELECT DISTINCT pp.ouvinte_id, o.pontos,
                    COALESCE(DATEDIFF(NOW(), MAX(s2.data_sorteio)), 999) as dias_sem_ganhar,
                    COUNT(pp.id) as total_participacoes
             FROM plugin_pulso_participacoes pp
             JOIN plugin_pulso_ouvintes o ON o.id = pp.ouvinte_id
             LEFT JOIN plugin_pulso_sorteios s2
                    ON s2.ouvinte_id = pp.ouvinte_id AND s2.resultado = 'vencedor'
             WHERE pp.promocao_id = ?
               AND pp.ouvinte_id IS NOT NULL
               AND (o.bloqueado IS NULL OR o.bloqueado = 0)
             GROUP BY pp.ouvinte_id, o.pontos",
            [$p['id']]
        );

        if (empty($participantes)) {
            $write("    ⚠ Sem participantes elegíveis — ignorado.");
            continue;
        }

        $total = count($participantes);

        // Sorteio ponderado: peso = pontos + bonus dias sem ganhar
        $pesos = [];
        $somaPesos = 0;
        foreach ($participantes as $part) {
            $peso = max(1, (int)$part['pontos'])
                  + min((int)$part['dias_sem_ganhar'], 30) * 10
                  + (int)$part['total_participacoes'] * 2;
            $pesos[] = $peso;
            $somaPesos += $peso;
        }

        // Seleccionar vencedor
        $rand = mt_rand(1, $somaPesos);
        $acum = 0;
        $vencedorIdx = 0;
        foreach ($pesos as $i => $peso) {
            $acum += $peso;
            if ($rand <= $acum) { $vencedorIdx = $i; break; }
        }
        $vencedor = $participantes[$vencedorIdx];
        $probVencedor = round($pesos[$vencedorIdx] / $somaPesos * 100, 2);

        $agora = date('Y-m-d H:i:s');

        // Registar todos no sorteio
        foreach ($participantes as $i => $part) {
            $isVencedor = ($i === $vencedorIdx);
            $prob       = round($pesos[$i] / $somaPesos * 100, 2);
            $conn->insert('plugin_pulso_sorteios', [
                'station_id'                  => $p['station_id'],
                'promocao_id'                 => $p['id'],
                'ouvinte_id'                  => $part['ouvinte_id'],
                'pontos_na_hora'              => (int)$part['pontos'],
                'probabilidade'               => $prob,
                'dias_sem_ganhar_na_hora'     => (int)$part['dias_sem_ganhar'],
                'total_participacoes_na_hora' => $total,
                'resultado'                   => $isVencedor ? 'vencedor' : 'participante',
                'data_sorteio'                => $agora,
            ]);
        }

        // Marcar promoção como encerrada
        $conn->update('plugin_pulso_promocoes', [
            'sorteio_realizado' => 1,
            'sorteio_data'      => $agora,
            'estado'            => 'encerrada',
        ], ['id' => $p['id']]);

        // Dados do vencedor para notificação
        $ouvVenc = $conn->fetchAssociative(
            "SELECT nome, telefone FROM plugin_pulso_ouvintes WHERE id = ?",
            [$vencedor['ouvinte_id']]
        );

        // Notificação para o locutor
        try {
            $conn->insert('plugin_pulso_notificacoes', [
                'station_id'   => $p['station_id'],
                'tipo'         => 'sorteio_auto',
                'titulo'       => '🎉 Sorteio Automático Realizado',
                'mensagem'     => "Promoção \"{$p['nome']}\" encerrada. Vencedor: " . ($ouvVenc['nome'] ?? "ID {$vencedor['ouvinte_id']}"),
                'dados'        => json_encode([
                    'promocao_id'        => $p['id'],
                    'promocao_nome'      => $p['nome'],
                    'vencedor_id'        => $vencedor['ouvinte_id'],
                    'vencedor_nome'      => $ouvVenc['nome'] ?? '',
                    'vencedor_telefone'  => $ouvVenc['telefone'] ?? '',
                    'probabilidade'      => $probVencedor,
                    'total_participantes'=> $total,
                ]),
                'lida'         => 0,
                'data_criacao' => $agora,
            ]);
        } catch (\Throwable $e) {
            // Tabela pode não existir ainda — não é crítico
            $write("    ℹ Notificação não gravada: " . $e->getMessage());
        }

        $write("    ✅ Vencedor: " . ($ouvVenc['nome'] ?? "ID {$vencedor['ouvinte_id']}") . " ({$probVencedor}% prob.) — {$total} participantes");
    }

} catch (\Throwable $e) {
    $write('  ❌ Erro sorteios: ' . $e->getMessage());
}

// ════════════════════════════════════════════════════════════
// 2. STREAM STATS
// ════════════════════════════════════════════════════════════
$write('');
$write('▶ Capturar stats do stream...');

try {
    $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true]]);
    $raw = @file_get_contents('http://127.0.0.1/api/nowplaying/rnb', false, $ctx);

    if ($raw === false || $raw === '') {
        $write('  ⚠ API AzuraCast sem resposta.');
    } else {
        $data      = json_decode($raw, true);
        $listeners = (int)($data['listeners']['total']  ?? 0);
        $unique    = (int)($data['listeners']['unique'] ?? 0);
        $title     = $data['now_playing']['song']['title']  ?? '';
        $artist    = $data['now_playing']['song']['artist'] ?? '';
        $isLive    = (bool)($data['live']['is_live'] ?? false);

        try {
            $conn->executeStatement(
                "INSERT INTO plugin_pulso_stream_stats
                    (station_id, listeners_total, listeners_unique, song_title, song_artist, is_live, created_at)
                 VALUES (1, ?, ?, ?, ?, ?, NOW())",
                [$listeners, $unique, $title, $artist, $isLive ? 1 : 0]
            );
            $live = $isLive ? ' [AO VIVO]' : '';
            $write("  ✅ {$listeners} ouvintes ({$unique} únicos){$live} — {$artist} · {$title}");
        } catch (\Throwable $e) {
            // Tabela pode não existir — silent fail
            $write('  ℹ stream_stats: ' . $e->getMessage());
        }
    }
} catch (\Throwable $e) {
    $write('  ❌ Erro stream: ' . $e->getMessage());
}

// ════════════════════════════════════════════════════════════
// 3. HOUSEKEEPING — Limpar notificações antigas (> 30 dias)
// ════════════════════════════════════════════════════════════
try {
    $deleted = $conn->executeStatement(
        "DELETE FROM plugin_pulso_notificacoes WHERE lida = 1 AND data_criacao < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    if ($deleted > 0) {
        $write('');
        $write("▶ Housekeeping: {$deleted} notificações antigas removidas.");
    }
} catch (\Throwable $e) {
    // Ignorar — tabela pode não existir
}

// ── Sumário ──────────────────────────────────────────────────
$elapsed = round((microtime(true) - $startedAt) * 1000);
$write('');
$write("✔ Concluído em {$elapsed}ms · " . date('H:i:s'));
$write('');

// ── Gravar log ───────────────────────────────────────────────
file_put_contents($logFile, implode("\n", $log) . "\n", FILE_APPEND);

// ── Resposta HTTP (se chamado via browser com chave) ─────────
if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'elapsed_ms' => $elapsed, 'log' => $log], JSON_PRETTY_PRINT);
}
