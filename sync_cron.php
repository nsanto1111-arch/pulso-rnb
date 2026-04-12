#!/usr/bin/env php
<?php
chdir('/var/azuracast/www/plugins/programacao-plugin');
require_once __DIR__.'/src/BridgeClient.php';
require_once __DIR__.'/src/SyncService.php';
require_once __DIR__.'/src/WordPressSync.php';
require_once __DIR__.'/src/WeeklyReport.php';

use Plugin\ProgramacaoPlugin\SyncService;
use Plugin\ProgramacaoPlugin\WordPressSync;
use Plugin\ProgramacaoPlugin\WeeklyReport;

$log = function(string $msg) {
    $line = '['.date('Y-m-d H:i:s').'] '.$msg.PHP_EOL;
    echo $line;
    file_put_contents(__DIR__.'/sync.log', $line, FILE_APPEND);
};

$log("=== RNB SYNC START ===");

// 1. WordPress sync (sempre)
$log("WordPress sync...");
$wp = new WordPressSync(1);
$r  = $wp->syncAll();
$log("  Locutores: synced:{$r['locutores']['synced']} new:{$r['locutores']['created']}");
$log("  Programas: synced:{$r['programas']['synced']} new:{$r['programas']['created']}");
foreach($r['errors'] as $e) $log("  ERRO: $e");

// 2. Performance e comercial (sempre)
$sync = new SyncService(1);
foreach([date('Y-m-d'), date('Y-m-d', strtotime('-1 day'))] as $date) {
    $log("Performance {$date}...");
    $r = $sync->syncPerformance($date);
    $log("  synced:{$r['synced']}");

    $log("Comercial {$date}...");
    $r = $sync->syncProvaEmissao($date);
    $log("  spots:{$r['spots']} inseridos:{$r['inseridos']}");
}

// 3. Relatório semanal (só ao domingo)
if(date('N') === '7') {
    $log("Relatório semanal a gerar...");
    $rep  = new WeeklyReport(1);
    $data = $rep->generate();
    $html = $rep->generateHtml($data);
    $path = __DIR__.'/reports/semana-'.date('Y-W').'.html';
    @mkdir(__DIR__.'/reports', 0755, true);
    file_put_contents($path, $html);
    $log("  Relatório guardado: $path");
    $log("  Top música: ".($data['top_aud'][0]['titulo'] ?? '—'));
}

$log("=== RNB SYNC END ===");
