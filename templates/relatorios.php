<?php
$relatorios   = $dados['relatorios'] ?? [];
$kpis         = $dados['kpis'] ?? [];
$kpisAnt      = $dados['kpisAnt'] ?? [];
$variacoes    = $dados['variacoes'] ?? [];
$topOuvintes  = $dados['topOuvintes'] ?? [];
$topMusicas   = $dados['topMusicas'] ?? [];
$horaPico     = $dados['horaPico'] ?? null;
$insights     = $dados['insights'] ?? [];
$hoje         = $dados['hoje'] ?? null;
$periodo      = $dados['periodo'] ?? '30d';
$inicio       = $dados['inicio'] ?? date('Y-m-d', strtotime('-29 days'));
$fim          = $dados['fim'] ?? date('Y-m-d');
$dias         = $dados['dias'] ?? 30;
$mapaCalor    = $dados['mapaCalor'] ?? [];
$scoreSaude   = $dados['scoreSaude'] ?? 0;
$scoreDetalhes= $dados['scoreDetalhes'] ?? [];
$mediaDiaria  = $dados['mediaDiaria'] ?? 0;
$previsao     = $dados['previsaoNovosMes'] ?? 0;
$narrativa    = $dados['narrativa'] ?? [];
$modo         = $_GET['modo'] ?? 'interno';

$periodos = ['7d'=>'7 dias','30d'=>'30 dias','90d'=>'90 dias','mes'=>'Este mês','mesant'=>'Mês anterior','custom'=>'Personalizado'];
$diasSemana = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

$taxaLeitura = ($kpis['participacoes'] ?? 0) > 0 ? round(($kpis['lidas'] ?? 0) / $kpis['participacoes'] * 100) : 0;
$corScore = $scoreSaude >= 70 ? '#10b981' : ($scoreSaude >= 40 ? '#f59e0b' : '#ef4444');
$labelScore = $scoreSaude >= 70 ? 'Excelente' : ($scoreSaude >= 40 ? 'Moderado' : 'Em desenvolvimento');

$jDatas = json_encode(array_map(fn($r) => date('d/m', strtotime($r['data_ref'])), array_reverse($relatorios)));
$jPart  = json_encode(array_map(fn($r) => (int)$r['total_participacoes'], array_reverse($relatorios)));
$jNovos = json_encode(array_map(fn($r) => (int)$r['novos_ouvintes'], array_reverse($relatorios)));
$jLidas = json_encode(array_map(fn($r) => (int)$r['dedicatorias_lidas'], array_reverse($relatorios)));

// Mapa de calor max
$maxCalor = 0;
foreach ($mapaCalor as $dia) foreach ($dia as $v) if ($v > $maxCalor) $maxCalor = $v;
?>
<style>
.ci{font-family:'Inter',sans-serif}

/* MODO TOGGLE */
.ci-modo-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.ci-modo-toggle{display:flex;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:3px;gap:3px}
.ci-modo-btn{padding:.5rem 1.25rem;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;background:transparent;color:#71717a;transition:all .2s}
.ci-modo-btn.active{background:rgba(0,229,255,0.15);color:#00e5ff}
.ci-modo-btn.active-ext{background:rgba(245,158,11,0.15);color:#f59e0b}

/* HEADER */
.ci-title{font-size:24px;font-weight:900;color:#fff}
.ci-title span{background:linear-gradient(135deg,#00e5ff,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.ci-subtitle{font-size:13px;color:#71717a;margin-top:3px}

/* FILTROS */
.ci-filtros{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:center}
.ci-filtro{padding:.5rem 1rem;border:1px solid rgba(255,255,255,0.08);border-radius:8px;background:transparent;color:#71717a;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s;white-space:nowrap}
.ci-filtro:hover{color:#a1a1aa;border-color:rgba(255,255,255,0.15);text-decoration:none}
.ci-filtro.active{background:rgba(0,229,255,0.1);border-color:rgba(0,229,255,0.3);color:#00e5ff}
.ci-custom-wrap{display:none;align-items:center;gap:.5rem;flex-wrap:wrap}
.ci-custom-wrap.show{display:flex}
.ci-date-input{padding:.5rem .875rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:12px;outline:none;color-scheme:dark}
.ci-date-input:focus{border-color:rgba(0,229,255,0.3)}
.ci-btn-aplicar{padding:.5rem 1rem;background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);border-radius:8px;color:#00e5ff;font-size:12px;font-weight:700;cursor:pointer}
.ci-btn-export{display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.25);border-radius:8px;color:#10b981;font-size:12px;font-weight:700;text-decoration:none;transition:all .2s}
.ci-btn-export:hover{background:rgba(16,185,129,0.2);text-decoration:none;color:#10b981}

/* SCORE */
.ci-score-bar{display:grid;grid-template-columns:auto 1fr;gap:2rem;align-items:center;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;padding:1.5rem;margin-bottom:1.5rem}
.ci-score-ring{position:relative;width:100px;height:100px;flex-shrink:0}
.ci-score-ring-val{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
.ci-score-detalhes{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}
.ci-score-item{text-align:center;padding:.875rem;background:rgba(0,0,0,0.15);border-radius:10px}
.ci-score-item-val{font-size:22px;font-weight:800;line-height:1;margin-bottom:.25rem}
.ci-score-item-bar{height:4px;background:rgba(255,255,255,0.08);border-radius:2px;overflow:hidden;margin:.375rem 0}
.ci-score-item-fill{height:100%;border-radius:2px}
.ci-score-item-lbl{font-size:10px;color:#71717a;font-weight:600;text-transform:uppercase;letter-spacing:.5px}

/* KPIs */
.ci-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.ci-kpi{border-radius:14px;padding:1.5rem;position:relative;overflow:hidden;border:1px solid}
.ci-kpi-bg{position:absolute;top:-15px;right:-15px;font-size:70px;opacity:.07}
.ci-kpi-label{font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:.5rem}
.ci-kpi-val{font-size:36px;font-weight:900;line-height:1;margin-bottom:.375rem}
.ci-kpi-comp{font-size:12px;display:flex;align-items:center;gap:.375rem}
.ci-up{color:#10b981}.ci-down{color:#ef4444}.ci-flat{color:#71717a}

/* INSIGHTS */
.ci-insights{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:1.5rem}
.ci-insight{padding:1.25rem;border-radius:12px;border:1px solid;display:flex;gap:.875rem}
.ci-insight.positivo{background:rgba(16,185,129,0.06);border-color:rgba(16,185,129,0.2)}
.ci-insight.alerta{background:rgba(245,158,11,0.06);border-color:rgba(245,158,11,0.2)}
.ci-insight.critico{background:rgba(239,68,68,0.06);border-color:rgba(239,68,68,0.2)}
.ci-insight.info{background:rgba(0,229,255,0.04);border-color:rgba(0,229,255,0.15)}
.ci-insight-icon{font-size:28px;flex-shrink:0}
.ci-insight-titulo{font-size:13px;font-weight:700;color:#fff;margin-bottom:.25rem}
.ci-insight-desc{font-size:12px;color:#a1a1aa;line-height:1.5;margin-bottom:.375rem}
.ci-insight-accao{font-size:11px;font-weight:700}
.positivo .ci-insight-accao{color:#10b981}
.alerta .ci-insight-accao{color:#f59e0b}
.critico .ci-insight-accao{color:#ef4444}
.info .ci-insight-accao{color:#00e5ff}

/* MAIN GRID */
.ci-main{display:grid;grid-template-columns:1fr 360px;gap:1.25rem}
.ci-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.ci-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.ci-card-body{padding:1.5rem}

/* CHART TABS */
.ci-chart-tabs{display:flex;gap:.375rem;padding:.875rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06)}
.ci-chart-tab{padding:.375rem .875rem;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;border:none;background:transparent;color:#71717a;transition:all .2s}
.ci-chart-tab.active{background:rgba(0,229,255,0.1);color:#00e5ff}

/* MAPA DE CALOR */
.ci-heat{display:grid;grid-template-columns:40px repeat(24,1fr);gap:2px;padding:1rem 1.5rem}
.ci-heat-cell{height:20px;border-radius:3px;cursor:pointer;transition:opacity .2s;position:relative}
.ci-heat-cell:hover{opacity:.8}
.ci-heat-label{font-size:9px;color:#71717a;display:flex;align-items:center;font-weight:600}
.ci-heat-hour{font-size:8px;color:#52525b;text-align:center;padding-bottom:4px}

/* TABELA */
.ci-table{width:100%;border-collapse:collapse;font-size:12px}
.ci-table th{padding:.625rem 1rem;text-align:left;font-size:10px;font-weight:700;color:#71717a;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid rgba(255,255,255,0.06);position:sticky;top:0;background:#0f0f1f}
.ci-table td{padding:.625rem 1rem;border-bottom:1px solid rgba(255,255,255,0.04)}
.ci-table tr:last-child td{border-bottom:none}
.ci-table tr:hover td{background:rgba(255,255,255,0.02)}
.ci-table tr.hoje-row td{background:rgba(0,229,255,0.04)}
.ci-mini-bar{height:3px;background:rgba(255,255,255,0.06);border-radius:2px;overflow:hidden;margin-top:3px}
.ci-mini-fill{height:100%;border-radius:2px}

/* SIDEBAR */
.ci-top-row{display:flex;align-items:center;gap:.875rem;padding:.75rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.ci-top-row:last-child{border-bottom:none}
.ci-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#00e5ff,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#000;flex-shrink:0}
.ci-musica-row{display:flex;align-items:center;gap:.75rem;padding:.625rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.ci-musica-row:last-child{border-bottom:none}

/* PREVISÃO */
.ci-previsao{background:linear-gradient(135deg,rgba(0,229,255,0.08),rgba(124,58,237,0.04));border:1px solid rgba(0,229,255,0.15);border-radius:12px;padding:1.25rem;text-align:center}

/* NARRATIVA (MODO EXTERNO) */
.ci-narrativa{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;padding:2rem;margin-bottom:1.5rem}
.ci-narrativa-titulo{font-size:18px;font-weight:800;color:#fff;margin-bottom:.375rem}
.ci-narrativa-periodo{font-size:13px;color:#71717a;margin-bottom:1.5rem;padding-bottom:1rem;border-bottom:1px solid rgba(255,255,255,0.06)}
.ci-narrativa p{font-size:14px;color:#a1a1aa;line-height:1.8;margin-bottom:1rem}
.ci-narrativa p:last-child{margin-bottom:0}

/* MODO EXTERNO — ESTILO RELATÓRIO */
.ci-ext-header{text-align:center;padding:2rem;background:linear-gradient(135deg,rgba(0,229,255,0.08),rgba(124,58,237,0.05));border:1px solid rgba(0,229,255,0.15);border-radius:16px;margin-bottom:2rem}
.ci-ext-logo{font-size:32px;font-weight:900;background:linear-gradient(135deg,#00e5ff,#8b5cf6);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:.5rem}
.ci-ext-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-bottom:2rem}
.ci-ext-kpi{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:1.75rem;text-align:center}
.ci-ext-kpi-val{font-size:48px;font-weight:900;line-height:1;margin-bottom:.5rem}
.ci-ext-kpi-lbl{font-size:13px;color:#71717a;font-weight:600}
.ci-ext-kpi-sub{font-size:11px;color:#52525b;margin-top:.25rem}

@media(max-width:1000px){
    .ci-kpis{grid-template-columns:repeat(2,1fr)}
    .ci-main{grid-template-columns:1fr}
    .ci-insights{grid-template-columns:1fr}
    .ci-score-detalhes{grid-template-columns:repeat(2,1fr)}
    .ci-score-bar{grid-template-columns:1fr}
}
</style>

<div class="ci" id="ciRoot">

<!-- MODO TOGGLE + EXPORT -->
<div class="ci-modo-bar">
    <div>
        <div class="ci-title">🧠 Centro de <span>Inteligência</span></div>
        <div class="ci-subtitle">
            <?= $periodos[$periodo] ?? $periodo ?> &nbsp;·&nbsp;
            <?= date('d/m/Y', strtotime($inicio)) ?> → <?= date('d/m/Y', strtotime($fim)) ?>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:.875rem;flex-wrap:wrap">
        <div class="ci-modo-toggle">
            <button class="ci-modo-btn <?= $modo === 'interno' ? 'active' : '' ?>"
                    onclick="setModo('interno')">⚙️ Interno</button>
            <button class="ci-modo-btn <?= $modo === 'externo' ? 'active-ext' : '' ?>"
                    onclick="setModo('externo')">📊 Patrocinadores</button>
        </div>
        <a href="/public/pulso/<?= $stationId ?>/relatorios/exportar?periodo=<?= $periodo ?>"
           class="ci-btn-export">
            <i class="bi bi-download"></i> Exportar CSV
        </a>
        <a href="/public/pulso/<?= $stationId ?>/relatorios/exportar-pdf?periodo=<?= $periodo ?>"
           class="ci-btn-export" style="background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.3);color:#ef4444;margin-left:.5rem">
            <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
        </a>
    </div>
</div>

<!-- FILTROS -->
<div class="ci-filtros">
    <?php foreach($periodos as $p => $label): if ($p === 'custom') continue; ?>
    <a href="?periodo=<?= $p ?>&modo=<?= $modo ?>" class="ci-filtro <?= $periodo === $p ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <a href="#" class="ci-filtro <?= $periodo === 'custom' ? 'active' : '' ?>"
       onclick="event.preventDefault();document.getElementById('customRange').classList.toggle('show')">
        📅 Personalizado
    </a>
    <div class="ci-custom-wrap <?= $periodo === 'custom' ? 'show' : '' ?>" id="customRange">
        <input type="date" class="ci-date-input" id="dataInicio" value="<?= $inicio ?>">
        <span style="color:#71717a;font-size:12px">→</span>
        <input type="date" class="ci-date-input" id="dataFim" value="<?= $fim ?>">
        <button class="ci-btn-aplicar" onclick="aplicarCustom()">Aplicar</button>
    </div>
</div>

<!-- ==================== MODO INTERNO ==================== -->
<div id="modoInterno" style="display:<?= $modo === 'interno' ? 'block' : 'none' ?>">

    <!-- SCORE DE SAÚDE -->
    <div class="ci-score-bar">
        <div>
            <div style="font-size:11px;font-weight:700;letter-spacing:1.5px;color:#71717a;text-transform:uppercase;margin-bottom:.75rem;text-align:center">Score de Saúde</div>
            <div class="ci-score-ring">
                <svg viewBox="0 0 100 100" width="100" height="100" style="transform:rotate(-90deg)">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="8"/>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="<?= $corScore ?>" stroke-width="8"
                        stroke-dasharray="<?= round($scoreSaude/100*264) ?> 264" stroke-linecap="round"/>
                </svg>
                <div class="ci-score-ring-val">
                    <div style="font-size:24px;font-weight:900;color:<?= $corScore ?>"><?= $scoreSaude ?></div>
                    <div style="font-size:9px;color:#71717a">/100</div>
                </div>
            </div>
            <div style="text-align:center;font-size:12px;color:<?= $corScore ?>;font-weight:700;margin-top:.5rem"><?= $labelScore ?></div>
        </div>
        <div style="flex:1">
            <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:1rem">Componentes do Score</div>
            <div class="ci-score-detalhes">
                <?php foreach([
                    ['Engajamento','#8b5cf6',$scoreDetalhes['engajamento']??0,25],
                    ['Crescimento','#10b981',$scoreDetalhes['crescimento']??0,25],
                    ['Retenção','#00e5ff',$scoreDetalhes['retencao']??0,25],
                    ['Leitura no Ar','#3b82f6',$scoreDetalhes['leitura']??0,25],
                ] as [$lbl,$cor,$val,$max]): ?>
                <div class="ci-score-item">
                    <div class="ci-score-item-val" style="color:<?= $cor ?>"><?= $val ?></div>
                    <div class="ci-score-item-bar"><div class="ci-score-item-fill" style="width:<?= round($val/$max*100) ?>%;background:<?= $cor ?>"></div></div>
                    <div class="ci-score-item-lbl"><?= $lbl ?></div>
                    <div style="font-size:9px;color:#52525b">max <?= $max ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="ci-kpis">
        <?php
        $kpiDefs = [
            ['⚡','Participações','participacoes','#8b5cf6','rgba(139,92,246,0.12)','rgba(139,92,246,0.25)','vs período ant.'],
            ['👥','Ouvintes Únicos','ouvintes_unicos','#00e5ff','rgba(0,229,255,0.12)','rgba(0,229,255,0.25)','vs período ant.'],
            ['🌱','Novos Ouvintes','novos','#10b981','rgba(16,185,129,0.12)','rgba(16,185,129,0.25)','cadastros no período'],
            ['📻','Lidas no Ar','lidas','#3b82f6','rgba(59,130,246,0.12)','rgba(59,130,246,0.25)',$taxaLeitura . '% taxa'],
        ];
        foreach($kpiDefs as [$icon,$label,$key,$cor,$bg,$borda,$sub]):
            $val = $kpis[$key] ?? 0;
            $var = $variacoes[$key] ?? 0;
            $sinal = $var > 0 ? '↑' : ($var < 0 ? '↓' : '→');
            $corVar = $var > 0 ? '#10b981' : ($var < 0 ? '#ef4444' : '#71717a');
        ?>
        <div class="ci-kpi" style="background:<?= $bg ?>;border-color:<?= $borda ?>">
            <div class="ci-kpi-bg"><?= $icon ?></div>
            <div class="ci-kpi-label" style="color:<?= $cor ?>"><?= $label ?></div>
            <div class="ci-kpi-val" style="color:<?= $cor ?>"><?= number_format($val) ?></div>
            <div class="ci-kpi-comp">
                <span style="color:<?= $corVar ?>;font-weight:700"><?= $sinal ?> <?= abs($var) ?>%</span>
                <span style="color:#52525b;font-size:11px"><?= $sub ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- INSIGHTS -->
    <?php if (!empty($insights)): ?>
    <div class="ci-insights">
        <?php foreach($insights as $ins): ?>
        <div class="ci-insight <?= $ins['tipo'] ?>">
            <div class="ci-insight-icon"><?= $ins['icon'] ?></div>
            <div>
                <div class="ci-insight-titulo"><?= htmlspecialchars($ins['titulo']) ?></div>
                <div class="ci-insight-desc"><?= htmlspecialchars($ins['desc']) ?></div>
                <div class="ci-insight-accao">→ <?= htmlspecialchars($ins['accao']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="ci-main">
        <!-- ESQUERDA -->
        <div>
            <!-- GRÁFICO -->
            <div class="ci-card">
                <div class="ci-card-head"><span>📈 Evolução do Período</span></div>
                <div class="ci-chart-tabs">
                    <button class="ci-chart-tab active" onclick="switchChart('part',this)">Participações</button>
                    <button class="ci-chart-tab" onclick="switchChart('novos',this)">Novos</button>
                    <button class="ci-chart-tab" onclick="switchChart('lidas',this)">Lidas</button>
                    <button class="ci-chart-tab" onclick="switchChart('todos',this)">Todos</button>
                </div>
                <div class="ci-card-body">
                    <canvas id="ciChart" height="200"></canvas>
                </div>
            </div>

            <!-- MAPA DE CALOR -->
            <div class="ci-card">
                <div class="ci-card-head">
                    <span>🔥 Mapa de Actividade — Hora × Dia</span>
                    <span style="font-size:11px;color:#71717a">Mais escuro = mais activo</span>
                </div>
                <div style="overflow-x:auto;padding:1rem 1.5rem">
                    <!-- Horas header -->
                    <div style="display:grid;grid-template-columns:40px repeat(24,1fr);gap:2px;margin-bottom:4px">
                        <div></div>
                        <?php for ($h = 0; $h <= 23; $h++): ?>
                        <div style="font-size:8px;color:#52525b;text-align:center"><?= $h ?>h</div>
                        <?php endfor; ?>
                    </div>
                    <!-- Dias -->
                    <?php foreach ($diasSemana as $dIdx => $dNome): ?>
                    <div style="display:grid;grid-template-columns:40px repeat(24,1fr);gap:2px;margin-bottom:2px">
                        <div style="font-size:9px;color:#71717a;display:flex;align-items:center;font-weight:600"><?= $dNome ?></div>
                        <?php for ($h = 0; $h <= 23; $h++):
                            $val = $mapaCalor[$dIdx][$h] ?? 0;
                            $intensity = $maxCalor > 0 ? $val / $maxCalor : 0;
                            $alpha = 0.06 + ($intensity * 0.94);
                            $cor = $val > 0 ? "rgba(0,229,255,{$alpha})" : "rgba(255,255,255,0.03)";
                        ?>
                        <div style="height:18px;border-radius:3px;background:<?= $cor ?>;cursor:pointer"
                             title="<?= $dNome ?> <?= $h ?>h: <?= $val ?> participações"></div>
                        <?php endfor; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TABELA -->
            <div class="ci-card">
                <div class="ci-card-head">
                    <span>📋 Diário Detalhado</span>
                    <span style="font-size:12px;color:#71717a"><?= count($relatorios) ?> dias</span>
                </div>
                <div style="overflow-x:auto;max-height:380px;overflow-y:auto">
                <table class="ci-table">
                    <thead>
                        <tr>
                            <th>Data</th><th>Part.</th><th>Únicos</th>
                            <th>Novos</th><th>No Ar</th><th>Sorteios</th><th>Top Música</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $maxPart = max(array_column($relatorios, 'total_participacoes') ?: [1]);
                    foreach($relatorios as $r):
                        $isHoje = $r['data_ref'] === date('Y-m-d');
                        $pct = $maxPart > 0 ? round($r['total_participacoes'] / $maxPart * 100) : 0;
                        $taxaDia = $r['total_participacoes'] > 0 ? round($r['dedicatorias_lidas'] / $r['total_participacoes'] * 100) : 0;
                    ?>
                    <tr class="<?= $isHoje ? 'hoje-row' : '' ?>">
                        <td>
                            <span style="font-weight:<?= $isHoje?700:400 ?>;color:<?= $isHoje?'#00e5ff':'#a1a1aa' ?>">
                                <?= date('d/m', strtotime($r['data_ref'])) ?>
                            </span>
                            <?php if ($isHoje): ?><span style="font-size:9px;background:rgba(0,229,255,0.1);color:#00e5ff;padding:1px 5px;border-radius:3px;margin-left:3px">HOJE</span><?php endif; ?>
                        </td>
                        <td>
                            <span style="font-weight:700;color:<?= $r['total_participacoes']>0?'#8b5cf6':'#52525b' ?>"><?= $r['total_participacoes'] ?></span>
                            <?php if ($r['total_participacoes']>0): ?><div class="ci-mini-bar"><div class="ci-mini-fill" style="width:<?= $pct ?>%;background:#8b5cf6"></div></div><?php endif; ?>
                        </td>
                        <td style="color:<?= $r['total_ouvintes_unicos']>0?'#00e5ff':'#52525b' ?>;font-weight:600"><?= $r['total_ouvintes_unicos']?:'—' ?></td>
                        <td style="color:<?= $r['novos_ouvintes']>0?'#10b981':'#52525b' ?>;font-weight:600"><?= $r['novos_ouvintes']>0?'+'.$r['novos_ouvintes']:'—' ?></td>
                        <td>
                            <?php if ($r['dedicatorias_lidas']>0): ?>
                            <span style="color:#3b82f6;font-weight:600"><?= $r['dedicatorias_lidas'] ?></span>
                            <span style="color:#52525b;font-size:10px">(<?= $taxaDia ?>%)</span>
                            <?php else: ?><span style="color:#52525b">—</span><?php endif; ?>
                        </td>
                        <td style="color:<?= $r['sorteios_realizados']>0?'#f59e0b':'#52525b' ?>"><?= $r['sorteios_realizados']?:'—' ?></td>
                        <td style="color:#71717a;font-size:11px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                            title="<?= htmlspecialchars($r['musica_mais_pedida']??'') ?>">
                            <?= !empty($r['musica_mais_pedida']) ? htmlspecialchars(mb_substr($r['musica_mais_pedida'],0,25)).(mb_strlen($r['musica_mais_pedida'])>25?'…':'') : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- DIREITA -->
        <div>
            <!-- HOJE -->
            <?php if ($hoje): ?>
            <div class="ci-card" style="background:linear-gradient(135deg,rgba(0,229,255,0.05),rgba(124,58,237,0.03));border-color:rgba(0,229,255,0.15);margin-bottom:1.25rem">
                <div class="ci-card-head" style="border-color:rgba(0,229,255,0.1)">
                    <span>🔴 Hoje — <?= date('d/m/Y') ?></span>
                </div>
                <div class="ci-card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1rem">
                        <?php foreach([
                            [$hoje['total_participacoes'],'#8b5cf6','Part.'],
                            [$hoje['total_ouvintes_unicos'],'#00e5ff','Únicos'],
                            ['+'.$hoje['novos_ouvintes'],'#10b981','Novos'],
                            [$hoje['dedicatorias_lidas'],'#3b82f6','No Ar'],
                        ] as [$v,$c,$l]): ?>
                        <div style="padding:1rem;background:rgba(0,0,0,0.15);border-radius:10px;text-align:center">
                            <div style="font-size:26px;font-weight:900;color:<?= $c ?>;line-height:1"><?= $v ?></div>
                            <div style="font-size:10px;color:#71717a;font-weight:600;margin-top:.25rem;text-transform:uppercase"><?= $l ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($hoje['musica_mais_pedida'])): ?>
                    <div style="padding:.875rem;background:rgba(0,0,0,0.2);border-radius:8px;display:flex;align-items:center;gap:.75rem">
                        <span style="font-size:18px">🎵</span>
                        <div>
                            <div style="font-size:10px;color:#71717a;font-weight:600;text-transform:uppercase">Top hoje</div>
                            <div style="font-size:12px;font-weight:600;color:#fff"><?= htmlspecialchars(mb_substr($hoje['musica_mais_pedida'],0,35)) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- PREVISÃO -->
            <div class="ci-previsao" style="margin-bottom:1.25rem">
                <div style="font-size:11px;color:#00e5ff;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:.5rem">🔮 Previsão Próximos 30 Dias</div>
                <div style="font-size:40px;font-weight:900;color:#fff;line-height:1">+<?= $previsao ?></div>
                <div style="font-size:12px;color:#71717a;margin:.375rem 0">novos ouvintes esperados</div>
                <div style="font-size:11px;color:#52525b">Baseado em média de <?= $mediaDiaria ?>/dia</div>
            </div>

            <!-- TOP OUVINTES -->
            <div class="ci-card">
                <div class="ci-card-head">
                    <span>🏆 Top Ouvintes</span>
                    <span style="font-size:11px;color:#71717a"><?= $periodos[$periodo]??$periodo ?></span>
                </div>
                <div class="ci-card-body" style="padding:1rem 1.5rem">
                    <?php if (!empty($topOuvintes)):
                        $medalhas=['🥇','🥈','🥉','4️⃣','5️⃣'];
                        foreach($topOuvintes as $i=>$o):
                            $ini=mb_strtoupper(mb_substr($o['nome']??'?',0,1));
                            $maxO=$topOuvintes[0]['participacoes']??1;
                            $pctO=round($o['participacoes']/$maxO*100);
                    ?>
                    <div class="ci-top-row">
                        <div style="font-size:16px;width:22px;text-align:center"><?= $medalhas[$i]??($i+1) ?></div>
                        <div class="ci-avatar"><?= $ini ?></div>
                        <div style="flex:1;min-width:0">
                            <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/ficha"
                               style="font-size:12px;font-weight:600;color:#fff;text-decoration:none"><?= htmlspecialchars($o['nome']) ?></a>
                            <div class="ci-mini-bar" style="margin-top:3px"><div class="ci-mini-fill" style="width:<?= $pctO ?>%;background:#00e5ff"></div></div>
                        </div>
                        <div style="font-size:13px;font-weight:800;color:#00e5ff"><?= $o['participacoes'] ?></div>
                    </div>
                    <?php endforeach; else: ?>
                    <div style="text-align:center;padding:1.5rem;color:#52525b;font-size:12px">Sem participações no período</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TOP MÚSICAS -->
            <div class="ci-card">
                <div class="ci-card-head">🎵 Mais Pedidas</div>
                <div class="ci-card-body" style="padding:1rem 1.5rem">
                    <?php if (!empty($topMusicas)):
                        $ranks=['🥇','🥈','🥉','4️⃣','5️⃣'];
                        foreach($topMusicas as $i=>$m):
                    ?>
                    <div class="ci-musica-row">
                        <div style="font-size:14px;width:22px;text-align:center"><?= $ranks[$i]??($i+1) ?></div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:11px;font-weight:600;color:#e4e4e7;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                <?= htmlspecialchars($m['musica']??'') ?>
                            </div>
                        </div>
                        <div style="font-size:12px;font-weight:700;color:#8b5cf6;flex-shrink:0"><?= $m['n'] ?>×</div>
                    </div>
                    <?php endforeach; else: ?>
                    <div style="text-align:center;padding:1.5rem;color:#52525b;font-size:12px">Sem pedidos no período</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- HORA DE OURO -->
            <?php if (!empty($horaPico)): ?>
            <div class="ci-card">
                <div class="ci-card-head">⏰ Hora de Ouro</div>
                <div class="ci-card-body" style="text-align:center">
                    <div style="font-size:52px;font-weight:900;color:#f59e0b;line-height:1"><?= $horaPico['hora'] ?>h</div>
                    <div style="font-size:12px;color:#71717a;margin:.5rem 0"><?= $horaPico['total'] ?> interacções neste horário</div>
                    <div style="font-size:11px;color:#f59e0b;font-weight:600">💡 Ideal para sorteios e promoções</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ==================== MODO EXTERNO (PATROCINADORES) ==================== -->
<div id="modoExterno" style="display:<?= $modo === 'externo' ? 'block' : 'none' ?>">

    <!-- HEADER RELATÓRIO -->
    <div class="ci-ext-header">
        <div class="ci-ext-logo">📻 Rádio New Band</div>
        <div style="font-size:16px;font-weight:700;color:#fff;margin-bottom:.375rem">Relatório de Audiência</div>
        <div style="font-size:13px;color:#71717a">
            <?= date('d/m/Y', strtotime($inicio)) ?> — <?= date('d/m/Y', strtotime($fim)) ?>
            &nbsp;·&nbsp; <?= $dias ?> dias &nbsp;·&nbsp; Gerado em <?= date('d/m/Y H:i') ?>
        </div>
        <div style="display:flex;align-items:center;justify-content:center;gap:1rem;margin-top:1rem">
            <div style="padding:.375rem 1rem;border-radius:20px;font-size:12px;font-weight:700;background:<?= $corScore ?>18;color:<?= $corScore ?>;border:1px solid <?= $corScore ?>40">
                Score de Saúde: <?= $scoreSaude ?>/100 — <?= $labelScore ?>
            </div>
        </div>
    </div>

    <!-- KPIs PRINCIPAIS EXTERNOS -->
    <div class="ci-ext-kpis">
        <div class="ci-ext-kpi">
            <div class="ci-ext-kpi-val" style="color:#8b5cf6"><?= number_format($kpis['participacoes']??0) ?></div>
            <div class="ci-ext-kpi-lbl">Interacções Totais</div>
            <div class="ci-ext-kpi-sub">pedidos e mensagens no período</div>
        </div>
        <div class="ci-ext-kpi">
            <div class="ci-ext-kpi-val" style="color:#00e5ff"><?= number_format($kpis['ouvintes_unicos']??0) ?></div>
            <div class="ci-ext-kpi-lbl">Ouvintes Únicos</div>
            <div class="ci-ext-kpi-sub">audiência activa identificada</div>
        </div>
        <div class="ci-ext-kpi">
            <div class="ci-ext-kpi-val" style="color:#10b981">+<?= number_format($kpis['novos']??0) ?></div>
            <div class="ci-ext-kpi-lbl">Novos Ouvintes</div>
            <div class="ci-ext-kpi-sub">crescimento no período</div>
        </div>
    </div>

    <!-- NARRATIVA -->
    <?php if (!empty($narrativa)): ?>
    <div class="ci-narrativa">
        <div class="ci-narrativa-titulo">📝 Análise do Período</div>
        <div class="ci-narrativa-periodo">
            <?= date('d/m/Y', strtotime($inicio)) ?> a <?= date('d/m/Y', strtotime($fim)) ?>
            &nbsp;·&nbsp; <?= $dias ?> dias analisados
        </div>
        <?php foreach($narrativa as $p): ?>
        <p><?= htmlspecialchars($p) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- GRÁFICO EXTERNO -->
    <div class="ci-card" style="margin-bottom:1.5rem">
        <div class="ci-card-head"><span>📊 Evolução da Audiência</span></div>
        <div class="ci-card-body">
            <canvas id="ciChartExt" height="200"></canvas>
        </div>
    </div>

    <!-- DESTAQUES EXTERNOS -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;margin-bottom:1.5rem">
        <div class="ci-card" style="margin-bottom:0">
            <div class="ci-card-head">🏆 Top Ouvinte</div>
            <div class="ci-card-body" style="text-align:center">
                <?php if (!empty($topOuvintes[0])): $o=$topOuvintes[0]; $ini=mb_strtoupper(mb_substr($o['nome'],0,1)); ?>
                <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#00e5ff,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:900;color:#000;margin:0 auto .875rem"><?= $ini ?></div>
                <div style="font-size:15px;font-weight:700;color:#fff"><?= htmlspecialchars($o['nome']) ?></div>
                <div style="font-size:12px;color:#71717a;margin-top:.25rem"><?= $o['participacoes'] ?> interacções</div>
                <?php else: ?><div style="color:#52525b;font-size:13px">Sem dados</div><?php endif; ?>
            </div>
        </div>
        <div class="ci-card" style="margin-bottom:0">
            <div class="ci-card-head">🎵 Música Mais Pedida</div>
            <div class="ci-card-body" style="text-align:center">
                <?php if (!empty($topMusicas[0])): $m=$topMusicas[0]; ?>
                <div style="font-size:36px;margin-bottom:.75rem">🎶</div>
                <div style="font-size:13px;font-weight:600;color:#fff;line-height:1.4"><?= htmlspecialchars(mb_substr($m['musica'],0,40)) ?></div>
                <div style="font-size:12px;color:#71717a;margin-top:.375rem"><?= $m['n'] ?> pedido<?= $m['n']!==1?'s':'' ?></div>
                <?php else: ?><div style="color:#52525b;font-size:13px">Sem dados</div><?php endif; ?>
            </div>
        </div>
        <div class="ci-card" style="margin-bottom:0">
            <div class="ci-card-head">⏰ Horário de Pico</div>
            <div class="ci-card-body" style="text-align:center">
                <?php if (!empty($horaPico)): ?>
                <div style="font-size:48px;font-weight:900;color:#f59e0b;line-height:1;margin-bottom:.5rem"><?= $horaPico['hora'] ?>h</div>
                <div style="font-size:12px;color:#71717a"><?= $horaPico['total'] ?> interacções</div>
                <?php else: ?><div style="color:#52525b;font-size:13px">Sem dados</div><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RODAPÉ EXTERNO -->
    <div style="text-align:center;padding:1.5rem;border-top:1px solid rgba(255,255,255,0.06);font-size:11px;color:#52525b">
        Rádio New Band &nbsp;·&nbsp; rnb.radionewband.ao &nbsp;·&nbsp; Relatório gerado pelo Sistema PULSO &nbsp;·&nbsp; <?= date('d/m/Y H:i') ?>
    </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const datas = <?= $jDatas ?>;
const part  = <?= $jPart ?>;
const novos = <?= $jNovos ?>;
const lidas = <?= $jLidas ?>;

const baseOpts = {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{ labels:{ color:'#a1a1aa', font:{size:11} } } },
    scales:{
        x:{ ticks:{color:'#71717a',font:{size:10}}, grid:{color:'rgba(255,255,255,0.04)'} },
        y:{ ticks:{color:'#71717a'}, grid:{color:'rgba(255,255,255,0.04)'}, beginAtZero:true }
    }
};

let ciChart = null;

function buildChart(datasets) {
    const ctx = document.getElementById('ciChart');
    if (!ctx) return;
    if (ciChart) ciChart.destroy();
    ciChart = new Chart(ctx, { type:'bar', data:{ labels:datas, datasets }, options:{...baseOpts} });
}

function switchChart(tipo, btn) {
    document.querySelectorAll('.ci-chart-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    if (tipo==='part')  buildChart([{label:'Participações',data:part, backgroundColor:'rgba(139,92,246,0.65)',borderRadius:4}]);
    else if (tipo==='novos') buildChart([{label:'Novos',data:novos,backgroundColor:'rgba(16,185,129,0.65)',borderRadius:4}]);
    else if (tipo==='lidas') buildChart([{label:'Lidas',data:lidas,backgroundColor:'rgba(59,130,246,0.65)',borderRadius:4}]);
    else buildChart([
        {label:'Participações',data:part, backgroundColor:'rgba(139,92,246,0.6)',borderRadius:4},
        {label:'Novos',        data:novos,backgroundColor:'rgba(16,185,129,0.6)', borderRadius:4},
        {label:'Lidas no Ar',  data:lidas,backgroundColor:'rgba(59,130,246,0.6)', borderRadius:4},
    ]);
}

// Gráfico externo
const ctxExt = document.getElementById('ciChartExt');
if (ctxExt) {
    new Chart(ctxExt, { type:'line', data:{ labels:datas, datasets:[
        {label:'Participações',data:part, borderColor:'#8b5cf6',backgroundColor:'rgba(139,92,246,0.1)',fill:true,tension:0.4,pointRadius:3},
        {label:'Novos Ouvintes',data:novos,borderColor:'#10b981',backgroundColor:'rgba(16,185,129,0.1)',fill:true,tension:0.4,pointRadius:3},
    ]}, options:{...baseOpts} });
}

function setModo(modo) {
    document.getElementById('modoInterno').style.display = modo==='interno' ? 'block' : 'none';
    document.getElementById('modoExterno').style.display = modo==='externo' ? 'block' : 'none';
    document.querySelectorAll('.ci-modo-btn').forEach(b => {
        b.classList.remove('active','active-ext');
    });
    event.currentTarget.classList.add(modo==='interno' ? 'active' : 'active-ext');
    const url = new URL(window.location);
    url.searchParams.set('modo', modo);
    window.history.replaceState({}, '', url);
}

function aplicarCustom() {
    const i = document.getElementById('dataInicio').value;
    const f = document.getElementById('dataFim').value;
    if (i && f) window.location = `?periodo=custom&inicio=${i}&fim=${f}&modo=<?= $modo ?>`;
}

switchChart('part', document.querySelector('.ci-chart-tab'));
</script>
