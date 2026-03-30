<?php
$musicas           = $dados['musicas'] ?? [];
$artistas          = $dados['artistas'] ?? [];
$porGenero         = $dados['porGenero'] ?? [];
$porHora           = $dados['porHora'] ?? array_fill(0, 24, 0);
$porSegmento       = $dados['porSegmento'] ?? [];
$generosDeclarados = $dados['generosDeclarados'] ?? [];
$horaPorGenero     = $dados['horaPorGenero'] ?? [];
$historicoRico     = $dados['historicoRico'] ?? [];
$insights          = $dados['insights'] ?? [];
$total             = $dados['total'] ?? 0;
$periodo           = $dados['periodo'] ?? 'tudo';

$periodoLabel = ['hoje'=>'Hoje','semana'=>'Esta Semana','mes'=>'Este Mês','tudo'=>'Todos os Tempos'];
$cores = ['#00e5ff','#8b5cf6','#10b981','#f59e0b','#ef4444','#3b82f6','#ec4899','#14b8a6','#f97316','#84cc16'];
$diasNomes = [1=>'Dom',2=>'Seg',3=>'Ter',4=>'Qua',5=>'Qui',6=>'Sex',7=>'Sáb'];
$generoNomes = [
    'romantico'=>'Romântico','pop'=>'Pop','rnb_soul'=>'R&B/Soul',
    'kizomba'=>'Kizomba','angola'=>'Angolano','internacional'=>'Internacional',
    'zouk'=>'Zouk','kuduro'=>'Kuduro','amapiano'=>'Amapiano',
    'afrobeat'=>'Afrobeat','outros'=>'Outros'
];
$generoCores = [
    'romantico'=>'#ef4444','pop'=>'#8b5cf6','rnb_soul'=>'#3b82f6',
    'kizomba'=>'#10b981','angola'=>'#f59e0b','internacional'=>'#00e5ff',
    'zouk'=>'#ec4899','kuduro'=>'#f97316','amapiano'=>'#14b8a6',
    'afrobeat'=>'#84cc16','outros'=>'#71717a'
];

$maxPedidos = !empty($musicas) ? max(array_column($musicas,'pedidos_totais')) : 1;
$maxArtista = !empty($artistas) ? max(array_column($artistas,'pedidos_totais')) : 1;
$maxHoraVal = !empty($porHora) ? max($porHora) : 1;
$maxDiaVal  = !empty($porDiaSemana) ? max($dados['porDiaSemana'] ?? [1]) : 1;
$porDiaSemana = $dados['porDiaSemana'] ?? array_fill(1,7,0);
?>
<style>
.mu-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.mu-title{font-size:22px;font-weight:800;color:#fff}
.mu-subtitle{font-size:13px;color:#71717a;margin-top:3px}
.mu-filtros{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.mu-filtro{padding:.5rem 1.125rem;border:1px solid rgba(255,255,255,0.08);border-radius:8px;background:transparent;color:#71717a;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s}
.mu-filtro:hover{color:#a1a1aa;border-color:rgba(255,255,255,0.15);text-decoration:none}
.mu-filtro.active{background:rgba(139,92,246,0.12);border-color:rgba(139,92,246,0.35);color:#8b5cf6}

/* TABS */
.mu-tabs{display:flex;gap:0;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:3px;margin-bottom:1.5rem;flex-wrap:wrap}
.mu-tab{padding:.625rem 1.25rem;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;background:transparent;color:#71717a;transition:all .2s}
.mu-tab.active{background:rgba(139,92,246,0.15);color:#8b5cf6}
.mu-tab:hover{color:#a1a1aa}

.mu-section{display:none}.mu-section.show{display:block}

/* CARDS */
.mu-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.mu-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.mu-card-body{padding:1.5rem}
.mu-grid2{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem}
.mu-grid3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1.5rem}
.mu-kpi{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:1.25rem;text-align:center}
.mu-kpi-val{font-size:32px;font-weight:900;line-height:1;margin-bottom:.25rem}
.mu-kpi-lbl{font-size:11px;color:#71717a;font-weight:600;text-transform:uppercase;letter-spacing:.5px}

/* PÓDIO */
.mu-podio{display:grid;grid-template-columns:1fr 1.15fr 1fr;gap:1rem;margin-bottom:1.5rem;align-items:end}
.mu-podio-item{border-radius:14px;padding:1.25rem;text-align:center;border:1px solid;position:relative;overflow:hidden}
.mu-podio-item::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.mu-podio-1{background:rgba(251,191,36,0.08);border-color:rgba(251,191,36,0.3)}
.mu-podio-1::before{background:linear-gradient(90deg,#fbbf24,#f59e0b)}
.mu-podio-2{background:rgba(148,163,184,0.06);border-color:rgba(148,163,184,0.2)}
.mu-podio-2::before{background:linear-gradient(90deg,#94a3b8,#64748b)}
.mu-podio-3{background:rgba(180,83,9,0.06);border-color:rgba(180,83,9,0.2)}
.mu-podio-3::before{background:linear-gradient(90deg,#b45309,#92400e)}

/* LISTA MÚSICAS */
.mu-row{display:flex;align-items:center;gap:1rem;padding:.875rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.04);transition:background .15s}
.mu-row:last-child{border-bottom:none}
.mu-row:hover{background:rgba(255,255,255,0.02)}
.mu-disc{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.mu-bar{height:4px;background:rgba(255,255,255,0.06);border-radius:2px;overflow:hidden;margin-top:4px}
.mu-bar-fill{height:100%;border-radius:2px}

/* GÉNEROS */
.mu-genero-row{display:flex;align-items:center;gap:.875rem;padding:.875rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.mu-genero-row:last-child{border-bottom:none}
.mu-genero-pill{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}

/* INSIGHTS */
.mu-insights{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.mu-insight{padding:1.125rem;border-radius:12px;border:1px solid}
.mu-insight.positivo{background:rgba(16,185,129,0.06);border-color:rgba(16,185,129,0.2)}
.mu-insight.info{background:rgba(0,229,255,0.04);border-color:rgba(0,229,255,0.15)}
.mu-insight.alerta{background:rgba(245,158,11,0.06);border-color:rgba(245,158,11,0.2)}
.mu-insight-icon{font-size:24px;margin-bottom:.5rem}
.mu-insight-titulo{font-size:12px;font-weight:700;color:#fff;margin-bottom:.25rem}
.mu-insight-desc{font-size:11px;color:#a1a1aa;line-height:1.5;margin-bottom:.375rem}
.mu-insight-accao{font-size:10px;font-weight:700}
.positivo .mu-insight-accao{color:#10b981}
.info .mu-insight-accao{color:#00e5ff}
.alerta .mu-insight-accao{color:#f59e0b}

/* HISTOGRAMA */
.mu-hist{display:flex;align-items:flex-end;gap:3px;height:80px;padding:0 1.5rem 1rem}
.mu-hist-bar{flex:1;border-radius:3px 3px 0 0;min-height:3px;transition:opacity .2s;cursor:pointer;position:relative}
.mu-hist-bar:hover{opacity:.8}

/* SEGMENTO × GÉNERO */
.mu-seg-table{width:100%;border-collapse:collapse;font-size:12px}
.mu-seg-table th{padding:.5rem .875rem;text-align:left;font-size:10px;font-weight:700;color:#71717a;text-transform:uppercase;border-bottom:1px solid rgba(255,255,255,0.06)}
.mu-seg-table td{padding:.625rem .875rem;border-bottom:1px solid rgba(255,255,255,0.04)}
.mu-seg-table tr:last-child td{border-bottom:none}

/* HISTÓRICO */
.mu-hist-row{display:flex;align-items:flex-start;gap:.875rem;padding:.875rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.mu-hist-row:last-child{border-bottom:none}
.mu-hist-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#000;flex-shrink:0;background:linear-gradient(135deg,#00e5ff,#8b5cf6)}

.mu-empty{text-align:center;padding:3rem;color:#52525b}
@media(max-width:900px){.mu-grid2{grid-template-columns:1fr}.mu-podio{grid-template-columns:1fr}.mu-insights{grid-template-columns:1fr}.mu-grid3{grid-template-columns:1fr 1fr}}
</style>

<!-- HEADER -->
<div class="mu-header">
    <div>
        <div class="mu-title">🎵 Análise Musical</div>
        <div class="mu-subtitle"><?= $periodoLabel[$periodo] ?? $periodo ?> · <?= $total ?> pedido<?= $total!==1?'s':'' ?></div>
    </div>
</div>

<!-- FILTROS -->
<div class="mu-filtros">
    <?php foreach($periodoLabel as $p => $label): ?>
    <a href="?periodo=<?= $p ?>" class="mu-filtro <?= $periodo===$p?'active':'' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php if ($total > 0): ?>

<!-- KPIs -->
<div class="mu-grid3">
    <div class="mu-kpi">
        <div class="mu-kpi-val" style="color:#8b5cf6"><?= count($musicas) ?></div>
        <div class="mu-kpi-lbl">Músicas Únicas</div>
    </div>
    <div class="mu-kpi">
        <div class="mu-kpi-val" style="color:#00e5ff"><?= count($artistas) ?></div>
        <div class="mu-kpi-lbl">Artistas Diferentes</div>
    </div>
    <div class="mu-kpi">
        <div class="mu-kpi-val" style="color:#10b981"><?= count($porGenero) ?></div>
        <div class="mu-kpi-lbl">Géneros Detectados</div>
    </div>
</div>

<!-- INSIGHTS -->
<?php if (!empty($insights)): ?>
<div class="mu-insights">
    <?php foreach($insights as $ins): ?>
    <div class="mu-insight <?= $ins['tipo'] ?>">
        <div class="mu-insight-icon"><?= $ins['icon'] ?></div>
        <div class="mu-insight-titulo"><?= htmlspecialchars($ins['titulo']) ?></div>
        <div class="mu-insight-desc"><?= htmlspecialchars($ins['desc']) ?></div>
        <div class="mu-insight-accao">→ <?= htmlspecialchars($ins['accao']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- TABS -->
<div class="mu-tabs">
    <button class="mu-tab active" onclick="switchTab('ranking',this)">🏆 Ranking</button>
    <button class="mu-tab" onclick="switchTab('generos',this)">🎼 Géneros</button>
    <button class="mu-tab" onclick="switchTab('horarios',this)">⏰ Horários</button>
    <button class="mu-tab" onclick="switchTab('segmentos',this)">👥 Segmentos</button>
    <button class="mu-tab" onclick="switchTab('historico',this)">📋 Histórico</button>
</div>

<!-- TAB: RANKING -->
<div id="tab-ranking" class="mu-section show">
    <?php
    $top3  = array_slice($musicas, 0, 3);
    $ordem = [1, 0, 2];
    $podioClasses  = ['mu-podio-2','mu-podio-1','mu-podio-3'];
    $podioMedalhas = ['🥈','🥇','🥉'];
    $podioCores    = ['#94a3b8','#fbbf24','#b45309'];
    ?>
    <div class="mu-podio">
        <?php foreach($ordem as $idx => $mIdx):
            if (!isset($top3[$mIdx])) { echo '<div></div>'; continue; }
            $m = $top3[$mIdx];
        ?>
        <div class="mu-podio-item <?= $podioClasses[$idx] ?>" <?= $mIdx===0?'style="transform:scale(1.04)"':'' ?>>
            <div style="font-size:28px;margin-bottom:.625rem"><?= $podioMedalhas[$idx] ?></div>
            <div style="font-size:13px;font-weight:700;color:#fff;line-height:1.3;margin-bottom:.25rem"><?= htmlspecialchars($m['musica']??'') ?></div>
            <div style="font-size:11px;color:#71717a;margin-bottom:.625rem"><?= htmlspecialchars($m['artista']??'') ?></div>
            <div style="font-size:26px;font-weight:900;color:<?= $podioCores[$idx] ?>"><?= $m['pedidos_totais'] ?></div>
            <div style="font-size:10px;color:#71717a">pedido<?= $m['pedidos_totais']!==1?'s':'' ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="mu-grid2">
        <!-- Lista músicas -->
        <div class="mu-card">
            <div class="mu-card-head">
                <span>🎵 Todas as Músicas</span>
                <span style="font-size:12px;color:#71717a"><?= count($musicas) ?></span>
            </div>
            <?php foreach($musicas as $i => $m):
                $pct = round($m['pedidos_totais']/$maxPedidos*100);
                $cor = $cores[$i % count($cores)];
            ?>
            <div class="mu-row">
                <div style="width:28px;text-align:center;font-size:<?= $i<3?'18':'13' ?>px;color:<?= $i<3?'inherit':'#71717a' ?>;font-weight:700">
                    <?php if($i===0) echo '🥇'; elseif($i===1) echo '🥈'; elseif($i===2) echo '🥉'; else echo $i+1; ?>
                </div>
                <div class="mu-disc" style="background:<?= $cor ?>15;border:1px solid <?= $cor ?>25">🎵</div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($m['musica']??'') ?></div>
                    <div style="font-size:11px;color:#71717a"><?= htmlspecialchars($m['artista']??'') ?></div>
                    <div class="mu-bar"><div class="mu-bar-fill" style="width:<?= $pct ?>%;background:<?= $cor ?>"></div></div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:16px;font-weight:900;color:<?= $cor ?>"><?= $m['pedidos_totais'] ?></div>
                    <div style="font-size:10px;color:#71717a"><?= $m['ouvintes_unicos'] ?> ouv.</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Top artistas -->
        <div>
            <div class="mu-card">
                <div class="mu-card-head">🎤 Top Artistas</div>
                <div class="mu-card-body" style="padding:1rem 1.5rem">
                    <?php foreach($artistas as $i => $a):
                        $cor = $cores[$i % count($cores)];
                        $ini = mb_strtoupper(mb_substr($a['artista']??'?',0,1));
                        $pct = round($a['pedidos_totais']/$maxArtista*100);
                    ?>
                    <div style="display:flex;align-items:center;gap:.875rem;padding:.625rem 0;border-bottom:1px solid rgba(255,255,255,0.04)">
                        <div style="font-size:12px;color:#71717a;width:20px;text-align:center;font-weight:600"><?= $i+1 ?></div>
                        <div style="width:34px;height:34px;border-radius:50%;background:<?= $cor ?>20;border:1px solid <?= $cor ?>30;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:<?= $cor ?>;flex-shrink:0"><?= $ini ?></div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:12px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($a['artista']) ?></div>
                            <div class="mu-bar"><div class="mu-bar-fill" style="width:<?= $pct ?>%;background:<?= $cor ?>"></div></div>
                        </div>
                        <div style="font-size:13px;font-weight:800;color:<?= $cor ?>"><?= $a['pedidos_totais'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TAB: GÉNEROS -->
<div id="tab-generos" class="mu-section">
    <div class="mu-grid2">
        <!-- Géneros pedidos -->
        <div class="mu-card">
            <div class="mu-card-head">
                <span>🎼 Géneros Mais Pedidos</span>
                <span style="font-size:11px;color:#71717a">detectados automaticamente</span>
            </div>
            <div class="mu-card-body" style="padding:1rem 1.5rem">
                <?php
                $maxGenero = !empty($porGenero) ? max($porGenero) : 1;
                foreach($porGenero as $genero => $qtd):
                    $cor = $generoCores[$genero] ?? '#71717a';
                    $pct = round($qtd/$maxGenero*100);
                    $nome = $generoNomes[$genero] ?? ucfirst($genero);
                ?>
                <div class="mu-genero-row">
                    <div class="mu-genero-pill" style="background:<?= $cor ?>18;color:<?= $cor ?>;border:1px solid <?= $cor ?>30;min-width:90px;text-align:center"><?= $nome ?></div>
                    <div style="flex:1">
                        <div class="mu-bar" style="height:8px">
                            <div class="mu-bar-fill" style="width:<?= $pct ?>%;background:<?= $cor ?>"></div>
                        </div>
                    </div>
                    <div style="font-size:14px;font-weight:800;color:<?= $cor ?>;min-width:30px;text-align:right"><?= $qtd ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Géneros declarados pelos ouvintes -->
        <div class="mu-card">
            <div class="mu-card-head">
                <span>❤️ Géneros Favoritos Declarados</span>
                <span style="font-size:11px;color:#71717a">perfil dos ouvintes</span>
            </div>
            <div class="mu-card-body" style="padding:1rem 1.5rem">
                <?php if (!empty($generosDeclarados)):
                    $maxDecl = max($generosDeclarados);
                    foreach($generosDeclarados as $genero => $qtd):
                        $generoKey = strtolower(str_replace(['&','/','  ',' '],['',' ','_','_'],$genero));
                        $cor = $generoCores[$generoKey] ?? '#71717a';
                        $pct = round($qtd/$maxDecl*100);
                ?>
                <div class="mu-genero-row">
                    <div class="mu-genero-pill" style="background:<?= $cor ?>18;color:<?= $cor ?>;border:1px solid <?= $cor ?>30;min-width:90px;text-align:center"><?= htmlspecialchars($genero) ?></div>
                    <div style="flex:1">
                        <div class="mu-bar" style="height:8px">
                            <div class="mu-bar-fill" style="width:<?= $pct ?>%;background:<?= $cor ?>"></div>
                        </div>
                    </div>
                    <div style="font-size:14px;font-weight:800;color:<?= $cor ?>;min-width:30px;text-align:right"><?= $qtd ?></div>
                </div>
                <?php endforeach; else: ?>
                <div class="mu-empty" style="padding:1.5rem">
                    <div style="font-size:13px">Preenche os perfis dos ouvintes para ver os géneros favoritos</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Gráfico géneros -->
    <div class="mu-card">
        <div class="mu-card-head">📊 Comparação: Pedidos vs Declarados</div>
        <div class="mu-card-body">
            <canvas id="generosChart" height="120"></canvas>
        </div>
    </div>
</div>

<!-- TAB: HORÁRIOS -->
<div id="tab-horarios" class="mu-section">
    <div class="mu-grid2">
        <div class="mu-card">
            <div class="mu-card-head">⏰ Pedidos por Hora</div>
            <div>
                <!-- Histograma horas -->
                <div style="display:flex;align-items:flex-end;gap:2px;height:100px;padding:1rem 1.5rem .5rem;border-bottom:1px solid rgba(255,255,255,0.06)">
                    <?php for($h=0;$h<=23;$h++):
                        $v = $porHora[$h] ?? 0;
                        $pct = $maxHoraVal > 0 ? round($v/$maxHoraVal*100) : 0;
                        $cor = $v > 0 ? '#8b5cf6' : 'rgba(255,255,255,0.06)';
                        $isMax = $v === max($porHora);
                    ?>
                    <div style="flex:1;background:<?= $isMax?'#00e5ff':$cor ?>;height:<?= max(4,$pct) ?>%;border-radius:2px 2px 0 0;cursor:pointer;transition:opacity .2s;position:relative"
                         title="<?= $h ?>h: <?= $v ?> pedidos"></div>
                    <?php endfor; ?>
                </div>
                <div style="display:flex;justify-content:space-between;padding:.5rem 1.5rem;font-size:9px;color:#52525b">
                    <span>0h</span><span>6h</span><span>12h</span><span>18h</span><span>23h</span>
                </div>
                <!-- Top horas -->
                <div style="padding:1rem 1.5rem">
                    <?php
                    $horasOrdenadas = $porHora;
                    arsort($horasOrdenadas);
                    $topHoras = array_slice($horasOrdenadas, 0, 5, true);
                    foreach($topHoras as $h => $v): if($v===0) continue; ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem;background:rgba(0,0,0,0.15);border-radius:8px;margin-bottom:.5rem">
                        <div style="font-size:13px;font-weight:700;color:#fff"><?= $h ?>h</div>
                        <div style="flex:1;margin:0 1rem">
                            <div class="mu-bar"><div class="mu-bar-fill" style="width:<?= round($v/max($porHora)*100) ?>%;background:#8b5cf6"></div></div>
                        </div>
                        <div style="font-size:13px;font-weight:800;color:#8b5cf6"><?= $v ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="mu-card">
            <div class="mu-card-head">📅 Pedidos por Dia da Semana</div>
            <div class="mu-card-body">
                <?php foreach($diasNomes as $dNum => $dNome):
                    $v = $porDiaSemana[$dNum] ?? 0;
                    $pct = $maxDiaVal > 0 ? round($v/$maxDiaVal*100) : 0;
                    $isMax = $v > 0 && $v === max($porDiaSemana);
                ?>
                <div style="display:flex;align-items:center;gap:.875rem;margin-bottom:.75rem">
                    <div style="width:28px;font-size:12px;font-weight:700;color:<?= $isMax?'#f59e0b':'#71717a' ?>"><?= $dNome ?></div>
                    <div style="flex:1">
                        <div class="mu-bar" style="height:10px">
                            <div class="mu-bar-fill" style="width:<?= $pct ?>%;background:<?= $isMax?'#f59e0b':'#71717a' ?>"></div>
                        </div>
                    </div>
                    <div style="font-size:13px;font-weight:<?= $isMax?800:600 ?>;color:<?= $isMax?'#f59e0b':'#71717a' ?>;min-width:20px;text-align:right"><?= $v ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- TAB: SEGMENTOS -->
<div id="tab-segmentos" class="mu-section">
    <div class="mu-card">
        <div class="mu-card-head">
            <span>👥 Géneros por Segmento de Ouvinte</span>
            <span style="font-size:11px;color:#71717a">o que cada segmento pede</span>
        </div>
        <div style="overflow-x:auto">
        <?php if (!empty($porSegmento)): ?>
        <table class="mu-seg-table">
            <thead>
                <tr>
                    <th>Segmento</th>
                    <?php foreach(array_keys($porGenero) as $g): ?>
                    <th style="text-align:center"><?= $generoNomes[$g] ?? ucfirst($g) ?></th>
                    <?php endforeach; ?>
                    <th style="text-align:center">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($porSegmento as $seg => $generos):
                $totalSeg = array_sum($generos);
                $segCores = ['novo'=>'#3b82f6','regular'=>'#10b981','veterano'=>'#8b5cf6','embaixador'=>'#f59e0b','inactivo'=>'#71717a'];
                $segCor = $segCores[$seg] ?? '#71717a';
            ?>
            <tr>
                <td>
                    <span style="padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;background:<?= $segCor ?>18;color:<?= $segCor ?>;border:1px solid <?= $segCor ?>30">
                        <?= ucfirst($seg) ?>
                    </span>
                </td>
                <?php foreach(array_keys($porGenero) as $g):
                    $v = $generos[$g] ?? 0;
                    $cor = $generoCores[$g] ?? '#71717a';
                ?>
                <td style="text-align:center">
                    <?php if($v>0): ?>
                    <span style="font-size:13px;font-weight:700;color:<?= $cor ?>"><?= $v ?></span>
                    <?php else: ?>
                    <span style="color:#52525b">—</span>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
                <td style="text-align:center;font-weight:800;color:#fff"><?= $totalSeg ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="mu-empty">Sem dados suficientes</div>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- TAB: HISTÓRICO -->
<div id="tab-historico" class="mu-section">
    <div class="mu-card">
        <div class="mu-card-head">
            <span>📋 Histórico de Pedidos</span>
            <span style="font-size:12px;color:#71717a"><?= count($historicoRico) ?> registos</span>
        </div>
        <div class="mu-card-body" style="padding:1rem 1.5rem;max-height:500px;overflow-y:auto">
            <?php foreach($historicoRico as $h):
                $ini = mb_strtoupper(mb_substr($h['nome']??'?',0,1));
                $cor = $generoCores[$h['genero']] ?? '#71717a';
                $genNome = $generoNomes[$h['genero']] ?? ucfirst($h['genero']);
                $data = date('d/m H:i', strtotime($h['data']??'now'));
            ?>
            <div class="mu-hist-row">
                <div class="mu-hist-avatar"><?= $ini ?></div>
                <div style="flex:1;min-width:0">
                    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                        <span style="font-size:13px;font-weight:700;color:#fff"><?= htmlspecialchars($h['musica']) ?></span>
                        <span style="padding:1px 7px;border-radius:20px;font-size:10px;font-weight:700;background:<?= $cor ?>15;color:<?= $cor ?>;border:1px solid <?= $cor ?>25"><?= $genNome ?></span>
                    </div>
                    <div style="font-size:11px;color:#71717a;margin-top:2px">
                        <?= htmlspecialchars($h['nome']) ?>
                        <?php if (!empty($h['mensagem'])): ?>
                        · <em style="color:#52525b">"<?= htmlspecialchars(mb_substr($h['mensagem'],0,50)) . (mb_strlen($h['mensagem'])>50?'…':'') ?>"</em>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:11px;color:#52525b"><?= $data ?></div>
                    <div style="font-size:10px;color:#71717a;margin-top:2px"><?= $h['hora'] ?>h</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php else: ?>
<div class="mu-card">
    <div class="mu-empty">
        <div style="font-size:48px;margin-bottom:1rem;opacity:.3">🎵</div>
        <div style="font-size:15px;font-weight:600;color:#a1a1aa;margin-bottom:.5rem">Nenhum pedido musical</div>
        <div style="font-size:13px">Ainda não há pedidos musicais <?= $periodo==='hoje'?'hoje':'neste período' ?></div>
        <?php if($periodo!=='tudo'): ?>
        <a href="?periodo=tudo" style="display:inline-flex;align-items:center;gap:.5rem;margin-top:1rem;padding:.625rem 1.25rem;background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.25);color:#8b5cf6;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600">
            Ver todos os tempos
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function switchTab(id, btn) {
    document.querySelectorAll('.mu-section').forEach(s => s.classList.remove('show'));
    document.querySelectorAll('.mu-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + id).classList.add('show');
    btn.classList.add('active');
    if (id === 'generos') buildGenerosChart();
}

let generosChart = null;
function buildGenerosChart() {
    const ctx = document.getElementById('generosChart');
    if (!ctx || generosChart) return;

    const generosPedidos   = <?= json_encode(array_map(fn($k) => $generoNomes[$k] ?? ucfirst($k), array_keys($porGenero))) ?>;
    const valoresPedidos   = <?= json_encode(array_values($porGenero)) ?>;
    const coresPedidos     = <?= json_encode(array_map(fn($k) => $generoCores[$k] ?? '#71717a', array_keys($porGenero))) ?>;

    generosChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: generosPedidos,
            datasets: [{
                label: 'Pedidos',
                data: valoresPedidos,
                backgroundColor: coresPedidos.map(c => c + '99'),
                borderColor: coresPedidos,
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks:{color:'#71717a',font:{size:11}}, grid:{color:'rgba(255,255,255,0.04)'} },
                y: { ticks:{color:'#71717a'}, grid:{color:'rgba(255,255,255,0.04)'}, beginAtZero:true }
            }
        }
    });
}
</script>
