<?php
$kpis        = $dados['kpis'] ?? [];
$topOuvintes = $dados['top_ouvintes'] ?? [];
$segmentos   = $dados['por_segmento'] ?? [];
$recentes    = $dados['recentes'] ?? [];
$cresc30d    = $dados['crescimento_30d'] ?? [];
$emRisco     = $dados['em_risco'] ?? 0;
$insights    = $dados['insights'] ?? [];
$partHoje    = $dados['participacoes_hoje'] ?? 0;
$pulse       = $dados['pulse'] ?? [];

$psScore     = min(100, $pulse['score'] ?? 0);
$psCor       = $psScore >= 80 ? '#10b981' : ($psScore >= 50 ? '#f59e0b' : ($psScore >= 20 ? '#00e5ff' : '#6b7280'));
$psLabel     = $psScore >= 80 ? '🔥 ON FIRE' : ($psScore >= 50 ? '📈 Activo' : ($psScore >= 20 ? '💡 A crescer' : '❄️ Fria'));
$psInsight   = $psScore >= 80
    ? 'Audiência excelente — óptimo momento para lançar uma promoção.'
    : ($psScore >= 50
        ? 'Boa performance. Mais interacções sobem o score.'
        : ($psScore >= 20
            ? 'A ganhar tração. Complete perfis e crie promoções.'
            : 'Sistema pronto. Lance uma promoção para activar a audiência.'));

$totalOuv    = $kpis['total_ouvintes'] ?? 0;
$novos7d     = $kpis['novos_7d'] ?? 0;
$engagement  = $kpis['taxa_engagement'] ?? 0;
$varDiaria   = $kpis['variacao_diaria'] ?? 0;
$taxaCresc   = $kpis['taxa_crescimento_mes'] ?? 0;

$jSegLabels  = json_encode(array_column($segmentos, 'segmento'));
$jSegData    = json_encode(array_column($segmentos, 'total'));
$jCrescLabels = json_encode(array_column($cresc30d, 'data'));
$jCrescData   = json_encode(array_column($cresc30d, 'novos'));
?>
<?php if (!empty($aniversariantesHoje)): ?>
<div style="background:linear-gradient(135deg,rgba(236,72,153,0.12),rgba(139,92,246,0.06));border:1px solid rgba(236,72,153,0.3);border-radius:14px;padding:1.25rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
    <div style="font-size:36px">🎂</div>
    <div style="flex:1">
        <div style="font-size:14px;font-weight:800;color:#ec4899;margin-bottom:.375rem">
            Aniversários Hoje!
        </div>
        <div style="font-size:13px;color:#a1a1aa">
            <?php foreach($aniversariantesHoje as $i => $anv): ?>
            <?= $i > 0 ? ', ' : '' ?>
            <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $anv['id'] ?>/ficha"
               style="color:#ec4899;font-weight:700;text-decoration:none">
                <?= htmlspecialchars($anv['nome']) ?>
            </a>
            <?php if ($anv['idade']): ?><span style="color:#71717a">(<?= $anv['idade'] ?> anos)</span><?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <a href="/public/pulso/<?= $stationId ?>/aniversarios"
       style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.125rem;background:rgba(236,72,153,0.15);border:1px solid rgba(236,72,153,0.35);border-radius:8px;color:#ec4899;text-decoration:none;font-size:13px;font-weight:700;white-space:nowrap">
        🎉 Ver Aniversários
    </a>
</div>
<?php endif; ?>
<style>
.db{font-family:'Inter',sans-serif}
.db-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.75rem}
.db-kpi{border-radius:14px;padding:1.5rem;border:1px solid rgba(255,255,255,0.08);position:relative;overflow:hidden;transition:border-color .2s}
.db-kpi:hover{border-color:rgba(255,255,255,0.15)}
.db-kpi-bg{position:absolute;top:-20px;right:-20px;font-size:80px;opacity:.06}
.db-kpi-label{font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#71717a;margin-bottom:.5rem}
.db-kpi-value{font-size:40px;font-weight:900;line-height:1;margin-bottom:.4rem}
.db-kpi-sub{font-size:12px;color:#71717a}
.db-up{color:#10b981}.db-down{color:#ef4444}
.db-main{display:grid;grid-template-columns:2fr 1fr;gap:1.25rem}
.db-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.db-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.db-card-body{padding:1.25rem 1.5rem}
.db-pulse{background:rgba(255,255,255,0.02);border:1px solid rgba(0,229,255,0.15);border-radius:14px;padding:1.5rem;margin-bottom:1.75rem;display:grid;grid-template-columns:auto 1fr;gap:2rem;align-items:center}
.db-pulse-label{font-size:10px;font-weight:700;letter-spacing:2px;color:#71717a;text-transform:uppercase;margin-bottom:.75rem;text-align:center}
.db-pulse-ring{position:relative;width:110px;height:110px}
.db-pulse-ring-val{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center}
.db-pulse-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem;margin-bottom:1rem}
.db-pulse-stat{text-align:center;padding:.875rem;border-radius:10px;border:1px solid rgba(255,255,255,0.06);background:rgba(0,0,0,0.2)}
.db-pulse-stat-val{font-size:22px;font-weight:800}
.db-pulse-stat-lbl{font-size:10px;color:#71717a;margin-top:3px;font-weight:600;letter-spacing:.5px}
.db-insight-bar{padding:.875rem 1.25rem;background:rgba(0,229,255,0.05);border-left:3px solid #00e5ff;border-radius:0 8px 8px 0;font-size:13px;color:#e4e4e7}
.db-top-row{display:flex;align-items:center;gap:1rem;padding:.875rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.db-top-row:last-child{border-bottom:none}
.db-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#00e5ff,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:15px;font-weight:800;color:#000;flex-shrink:0}
.db-top-nome{font-size:14px;font-weight:600;color:#fff;text-decoration:none}
.db-top-nome:hover{color:#00e5ff}
.db-top-seg{font-size:11px;color:#71717a;margin-top:2px}
.db-rec-row{display:flex;gap:.875rem;padding:.875rem 0;border-bottom:1px solid rgba(255,255,255,0.04);align-items:flex-start}
.db-rec-row:last-child{border-bottom:none}
.db-rec-avatar{width:32px;height:32px;border-radius:50%;background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.2);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#00e5ff;flex-shrink:0;margin-top:2px}
.db-rec-nome{font-size:13px;font-weight:700;color:#e4e4e7}
.db-rec-musica{font-size:12px;color:#00e5ff;margin:.2rem 0;font-weight:600}
.db-rec-msg{font-size:11px;color:#71717a;line-height:1.4}
.db-rec-hora{font-size:10px;color:#52525b;white-space:nowrap;margin-top:2px}
.db-rec-tipo{display:inline-block;font-size:10px;padding:2px 7px;border-radius:4px;font-weight:700;margin-left:.5rem}
.db-tipo-pedido{background:rgba(0,229,255,0.1);color:#00e5ff}
.db-tipo-promocao{background:rgba(245,158,11,0.1);color:#f59e0b}
.db-insights-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.25rem}
.db-insight-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:1.25rem}
.db-action-btn{display:flex;align-items:center;gap:.75rem;padding:.875rem 1.25rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:10px;color:#a1a1aa;text-decoration:none;font-size:13px;font-weight:600;transition:all .2s;margin-bottom:.625rem}
.db-action-btn:hover{background:rgba(255,255,255,0.06);color:#fff;border-color:rgba(255,255,255,0.12);text-decoration:none}
.db-action-btn i{font-size:15px}
.db-risco{background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.2);border-radius:10px;padding:1rem 1.25rem;display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;text-decoration:none;transition:background .2s}
.db-risco:hover{background:rgba(239,68,68,0.1)}
.db-empty{text-align:center;padding:2.5rem;color:#52525b}
.db-empty-icon{font-size:36px;margin-bottom:.5rem;opacity:.5}
@media(max-width:900px){
    .db-kpis{grid-template-columns:repeat(2,1fr)}
    .db-main{grid-template-columns:1fr}
    .db-pulse{grid-template-columns:1fr}
    .db-pulse-stats{grid-template-columns:repeat(2,1fr)}
    .db-insights-grid{grid-template-columns:1fr}
}
</style>

<div class="db">

<!-- PULSE -->
<div class="db-pulse">
    <div>
        <div class="db-pulse-label">Pulse Score</div>
        <div class="db-pulse-ring">
            <svg viewBox="0 0 100 100" width="110" height="110" style="transform:rotate(-90deg)">
                <circle cx="50" cy="50" r="42" fill="none" stroke="rgba(255,255,255,0.07)" stroke-width="8"/>
                <circle cx="50" cy="50" r="42" fill="none" stroke="<?= $psCor ?>" stroke-width="8"
                    stroke-dasharray="<?= round($psScore/100*264) ?> 264" stroke-linecap="round"/>
            </svg>
            <div class="db-pulse-ring-val">
                <div style="font-size:28px;font-weight:900;color:<?= $psCor ?>"><?= $psScore ?></div>
                <div style="font-size:9px;color:#71717a">/100</div>
            </div>
        </div>
        <div style="text-align:center;font-size:12px;color:<?= $psCor ?>;font-weight:700;margin-top:.5rem"><?= $psLabel ?></div>
    </div>
    <div>
        <div class="db-pulse-stats">
            <div class="db-pulse-stat">
                <div class="db-pulse-stat-val" style="color:#00e5ff"><?= $pulse['pedidos_7d'] ?? 0 ?></div>
                <div class="db-pulse-stat-lbl">PEDIDOS 7D</div>
            </div>
            <div class="db-pulse-stat">
                <div class="db-pulse-stat-val" style="color:#10b981"><?= $pulse['lidos_no_ar_30d'] ?? 0 ?></div>
                <div class="db-pulse-stat-lbl">LIDOS NO AR</div>
            </div>
            <div class="db-pulse-stat">
                <div class="db-pulse-stat-val" style="color:#f59e0b"><?= $pulse['promocoes_ativas'] ?? 0 ?></div>
                <div class="db-pulse-stat-lbl">PROMOÇÕES</div>
            </div>
            <div class="db-pulse-stat">
                <div class="db-pulse-stat-val" style="color:#8b5cf6"><?= $pulse['novos_30d'] ?? 0 ?></div>
                <div class="db-pulse-stat-lbl">NOVOS 30D</div>
            </div>
        </div>
        <div class="db-insight-bar">💡 <?= $psInsight ?></div>
    </div>
</div>

<!-- KPIs -->
<div class="db-kpis">
    <div class="db-kpi" style="background:linear-gradient(135deg,rgba(59,130,246,0.12),rgba(37,99,235,0.03));border-color:rgba(59,130,246,0.25)">
        <div class="db-kpi-bg">👥</div>
        <div class="db-kpi-label">Total Ouvintes</div>
        <div class="db-kpi-value" style="color:#3b82f6"><?= number_format($totalOuv) ?></div>
        <div class="db-kpi-sub">activos na plataforma</div>
    </div>
    <div class="db-kpi" style="background:linear-gradient(135deg,rgba(16,185,129,0.12),rgba(5,150,105,0.03));border-color:rgba(16,185,129,0.25)">
        <div class="db-kpi-bg">📈</div>
        <div class="db-kpi-label">Novos (7 dias)</div>
        <div class="db-kpi-value" style="color:#10b981">+<?= $novos7d ?></div>
        <div class="db-kpi-sub">
            <span class="<?= $taxaCresc >= 0 ? 'db-up' : 'db-down' ?>"><?= $taxaCresc >= 0 ? '↑' : '↓' ?> <?= abs($taxaCresc) ?>% este mês</span>
        </div>
    </div>
    <div class="db-kpi" style="background:linear-gradient(135deg,rgba(139,92,246,0.12),rgba(124,58,237,0.03));border-color:rgba(139,92,246,0.25)">
        <div class="db-kpi-bg">⚡</div>
        <div class="db-kpi-label">Part. Hoje</div>
        <div class="db-kpi-value" style="color:#8b5cf6"><?= $partHoje ?></div>
        <div class="db-kpi-sub">
            <span class="<?= $varDiaria >= 0 ? 'db-up' : 'db-down' ?>"><?= $varDiaria >= 0 ? '↑' : '↓' ?> <?= abs($varDiaria) ?>% vs ontem</span>
        </div>
    </div>
    <div class="db-kpi" style="background:linear-gradient(135deg,rgba(245,158,11,0.12),rgba(217,119,6,0.03));border-color:rgba(245,158,11,0.25)">
        <div class="db-kpi-bg">🎯</div>
        <div class="db-kpi-label">Engagement</div>
        <div class="db-kpi-value" style="color:#f59e0b"><?= $engagement ?></div>
        <div class="db-kpi-sub">participações/ouvinte</div>
    </div>
</div>

<!-- INSIGHTS -->
<?php if (!empty($insights)): ?>
<div class="db-insights-grid">
    <?php foreach(array_slice($insights,0,3) as $ins): ?>
    <div class="db-insight-card">
        <div style="font-size:22px;margin-bottom:.5rem"><?= $ins['icone'] ?></div>
        <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:.375rem"><?= htmlspecialchars($ins['titulo']) ?></div>
        <div style="font-size:12px;color:#71717a;line-height:1.5;margin-bottom:.5rem"><?= htmlspecialchars($ins['descricao']) ?></div>
        <div style="font-size:11px;color:#00e5ff;font-weight:600">💡 <?= htmlspecialchars($ins['accao']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- MAIN GRID -->
<div class="db-main">

    <!-- ESQUERDA -->
    <div>
        <!-- TOP 5 -->
        <div class="db-card">
            <div class="db-card-head">
                🏆 Top 5 Ouvintes
                <a href="/public/pulso/<?= $stationId ?>/ouvintes" style="font-size:12px;color:#71717a;text-decoration:none">Ver todos →</a>
            </div>
            <div class="db-card-body">
                <?php if (!empty($topOuvintes)):
                    $medalhas = ['🥇','🥈','🥉','4️⃣','5️⃣'];
                    foreach($topOuvintes as $i => $o):
                        $ini = mb_strtoupper(mb_substr($o['nome'],0,1));
                ?>
                <div class="db-top-row">
                    <div style="font-size:20px;width:28px;text-align:center"><?= $medalhas[$i] ?? ($i+1) ?></div>
                    <div class="db-avatar"><?= $ini ?></div>
                    <div style="flex:1;min-width:0">
                        <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/ficha" class="db-top-nome"><?= htmlspecialchars($o['nome']) ?></a>
                        <div class="db-top-seg"><?= ucfirst($o['segmento'] ?? 'novo') ?> · <?= $o['total_participacoes'] ?? 0 ?> participações</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:20px;font-weight:800;color:#00e5ff"><?= number_format($o['pontos'] ?? 0) ?></div>
                        <div style="font-size:10px;color:#71717a">PTS</div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="db-empty">
                    <div class="db-empty-icon">🎯</div>
                    <div>Sem ouvintes ainda</div>
                    <a href="/public/pulso/<?= $stationId ?>/ouvintes/novo" style="display:inline-block;margin-top:.75rem;padding:.5rem 1.25rem;background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);color:#00e5ff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600">+ Adicionar</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ACTIVIDADE RECENTE -->
        <div class="db-card">
            <div class="db-card-head">
                🎵 Actividade Recente
                <span style="font-size:12px;color:#71717a"><?= count($recentes) ?> eventos</span>
            </div>
            <div class="db-card-body">
                <?php if (!empty($recentes)): foreach($recentes as $r):
                    $ini = mb_strtoupper(mb_substr($r['nome'] ?? '?', 0, 1));
                    $tipoClass = $r['tipo'] === 'pedido_musica' ? 'db-tipo-pedido' : 'db-tipo-promocao';
                    $tipoLabel = $r['tipo'] === 'pedido_musica' ? '🎵 Pedido' : '🎁 Promoção';
                    $hora = date('d/m H:i', strtotime($r['data_participacao'] ?? 'now'));
                ?>
                <div class="db-rec-row">
                    <div class="db-rec-avatar"><?= $ini ?></div>
                    <div style="flex:1;min-width:0">
                        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                            <span class="db-rec-nome"><?= htmlspecialchars($r['nome'] ?? '') ?></span>
                            <span class="db-rec-tipo <?= $tipoClass ?>"><?= $tipoLabel ?></span>
                            <?php if ($r['lido_no_ar']): ?>
                            <span style="font-size:10px;background:rgba(16,185,129,0.1);color:#10b981;padding:2px 7px;border-radius:4px;font-weight:700">✓ No Ar</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($r['musica'])): ?>
                        <div class="db-rec-musica">♪ <?= htmlspecialchars($r['musica']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($r['mensagem'])): ?>
                        <div class="db-rec-msg">"<?= htmlspecialchars(mb_substr($r['mensagem'], 0, 80)) ?><?= mb_strlen($r['mensagem']) > 80 ? '…' : '' ?>"</div>
                        <?php endif; ?>
                    </div>
                    <div class="db-rec-hora"><?= $hora ?></div>
                </div>
                <?php endforeach; else: ?>
                <div class="db-empty">
                    <div class="db-empty-icon">📭</div>
                    <div>Sem actividade recente</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- DIREITA -->
    <div>
        <!-- SEGMENTOS -->
        <div class="db-card">
            <div class="db-card-head">📊 Segmentos</div>
            <div class="db-card-body">
                <?php if (!empty($segmentos)): ?>
                <canvas id="dbChartSeg" height="180"></canvas>
                <?php else: ?>
                <div class="db-empty"><div class="db-empty-icon">📊</div><div>Sem dados</div></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CRESCIMENTO 30D -->
        <div class="db-card">
            <div class="db-card-head">
                📅 Crescimento (30 dias)
                <span style="font-size:11px;color:#71717a"><?= array_sum(array_column($cresc30d,'novos')) ?> total</span>
            </div>
            <div class="db-card-body">
                <?php if (!empty($cresc30d)): ?>
                <canvas id="dbChartCresc" height="140"></canvas>
                <?php else: ?>
                <div class="db-empty"><div class="db-empty-icon">📅</div><div>Sem cadastros este mês</div></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- EM RISCO -->
        <?php if ($emRisco > 0): ?>
        <a href="/public/pulso/<?= $stationId ?>/ouvintes?segmento=inactivo" class="db-risco">
            <div style="font-size:24px">⚠️</div>
            <div>
                <div style="font-size:24px;font-weight:900;color:#ef4444"><?= $emRisco ?></div>
                <div style="font-size:12px;color:#a1a1aa">ouvintes em risco</div>
            </div>
        </a>
        <?php endif; ?>

        <!-- ACÇÕES RÁPIDAS -->
        <div class="db-card">
            <div class="db-card-head">⚡ Acções Rápidas</div>
            <div class="db-card-body" style="padding:1rem 1.25rem">
                <a href="/public/pulso/<?= $stationId ?>/ouvintes/novo" class="db-action-btn"><i class="bi bi-person-plus"></i> Novo Ouvinte</a>
                <a href="/public/pulso/<?= $stationId ?>/ouvintes/enriquecer" class="db-action-btn"><i class="bi bi-bar-chart-steps"></i> Enriquecer Perfis</a>
                <a href="/public/pulso/<?= $stationId ?>/promocoes/nova" class="db-action-btn"><i class="bi bi-gift"></i> Nova Promoção</a>
                <a href="/public/pulso/<?= $stationId ?>/sorteios" class="db-action-btn"><i class="bi bi-trophy"></i> Sorteios</a>
                <a href="/public/pulso/<?= $stationId ?>/demograficos-pro" class="db-action-btn"><i class="bi bi-graph-up"></i> Demográficos</a>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const segEl = document.getElementById('dbChartSeg');
if (segEl) new Chart(segEl, { type:'doughnut', data:{
    labels: <?= $jSegLabels ?>,
    datasets:[{ data:<?= $jSegData ?>, backgroundColor:['#3b82f6','#10b981','#8b5cf6','#f59e0b','#71717a'], borderWidth:0 }]
}, options:{ cutout:'60%', plugins:{ legend:{ position:'bottom', labels:{color:'#a1a1aa',font:{size:11}} } } } });

const crescEl = document.getElementById('dbChartCresc');
if (crescEl) new Chart(crescEl, { type:'bar', data:{
    labels: <?= $jCrescLabels ?>.map(d => d.slice(5)),
    datasets:[{ data:<?= $jCrescData ?>, backgroundColor:'rgba(0,229,255,0.5)', borderRadius:4, borderSkipped:false }]
}, options:{ plugins:{legend:{display:false}}, scales:{
    x:{ticks:{color:'#71717a',font:{size:10}}, grid:{color:'rgba(255,255,255,0.04)'}},
    y:{ticks:{color:'#71717a'}, grid:{color:'rgba(255,255,255,0.04)'}, beginAtZero:true}
}} });
</script>
