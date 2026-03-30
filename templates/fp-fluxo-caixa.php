<?php
$fluxo  = $fluxo ?? [];
$fmtKz  = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';

$jLabels   = json_encode(array_column($fluxo, 'mes_curto'));
$jReceitas = json_encode(array_map('floatval', array_column($fluxo, 'receitas')));
$jDespesas = json_encode(array_map('floatval', array_column($fluxo, 'despesas')));
$jAcum     = json_encode(array_map('floatval', array_column($fluxo, 'acumulado')));
?>
<style>
:root{--fp-green:#10b981;--fp-red:#ef4444;--fp-gold:#f59e0b;--fp-cyan:#00e5ff;--fp-purple:#8b5cf6;--fp-blue:#3b82f6;--fp-bg2:#161b27;--fp-bg3:#1e2535;--fp-text:#f0f4ff;--fp-text2:#8892a4;--fp-text3:#4a5568;--fp-border:rgba(255,255,255,.07)}
.fpfc-chart-card{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;padding:1.5rem;margin-bottom:1.5rem}
.fpfc-table-wrap{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden}
.fpfc-row{display:grid;grid-template-columns:160px repeat(6,1fr);gap:.5rem;padding:.75rem 1.5rem;align-items:center;border-bottom:1px solid rgba(255,255,255,.03)}
.fpfc-row:last-child{border-bottom:none}
.fpfc-row:hover{background:rgba(255,255,255,.02)}
.fpfc-row-head{background:var(--fp-bg3);font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px}
.fpfc-row.atual{background:rgba(0,229,255,.04);border-left:3px solid var(--fp-cyan)}
.fpfc-row.realizado{opacity:.8}
.fpfc-mes{font-size:12px;font-weight:700;color:var(--fp-text)}
.fpfc-val{font-size:12px;font-weight:700;text-align:right}
.fpfc-period-btn{padding:.375rem .75rem;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;border:1px solid var(--fp-border);background:var(--fp-bg2);color:var(--fp-text2);text-decoration:none;transition:all .15s}
.fpfc-period-btn.active{background:rgba(0,229,255,.1);border-color:rgba(0,229,255,.3);color:var(--fp-cyan)}
.fpfc-period-btn:hover:not(.active){color:var(--fp-text);text-decoration:none}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <div>
        <div style="font-size:24px;font-weight:900;color:var(--fp-text)">Fluxo de <span style="color:var(--fp-green)">Caixa</span></div>
        <div style="font-size:13px;color:var(--fp-text2);margin-top:4px">Entradas, saídas e projecções futuras</div>
    </div>
    <div style="display:flex;gap:.5rem">
        <a href="/public/financas/<?= $stationId ?>/exportar-pdf/fluxo"
           style="display:inline-flex;align-items:center;gap:.375rem;padding:.375rem .875rem;border-radius:7px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#ef4444;font-size:11px;font-weight:700;text-decoration:none">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
        <?php foreach([3=>'3 Meses',6=>'6 Meses',9=>'9 Meses'] as $m=>$lbl): ?>
        <a href="?meses=<?= $m ?>" class="fpfc-period-btn <?= ($_GET['meses']??6)==$m?'active':'' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- GRÁFICO -->
<div class="fpfc-chart-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem">
        <div style="font-size:13px;font-weight:700;color:var(--fp-text)">📊 Receitas vs Despesas</div>
        <div style="display:flex;gap:1rem;font-size:11px;color:var(--fp-text3)">
            <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#10b981;margin-right:4px"></span>Receitas</span>
            <span><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#ef4444;margin-right:4px"></span>Despesas</span>
            <span><span style="display:inline-block;width:20px;height:2px;background:#00e5ff;margin-right:4px;vertical-align:middle"></span>Acumulado</span>
        </div>
    </div>
    <?php if (!empty($fluxo)): ?>
    <canvas id="fpfc-chart" height="200"></canvas>
    <?php else: ?>
    <div style="height:200px;display:flex;align-items:center;justify-content:center;color:var(--fp-text3);font-size:13px">
        Sem dados para mostrar. Regista lançamentos primeiro.
    </div>
    <?php endif; ?>
</div>

<!-- TABELA -->
<div class="fpfc-table-wrap">
    <div class="fpfc-row fpfc-row-head">
        <span>Período</span>
        <span style="text-align:right">Receitas</span>
        <span style="text-align:right">Despesas</span>
        <span style="text-align:right">Saldo</span>
        <span style="text-align:right">A Receber</span>
        <span style="text-align:right">A Pagar</span>
        <span style="text-align:right">Acumulado</span>
    </div>

    <?php if (!empty($fluxo)): foreach($fluxo as $f):
        $saldoCls = (float)$f['saldo'] >= 0 ? '#10b981' : '#ef4444';
        $acumCls  = (float)$f['acumulado'] >= 0 ? '#10b981' : '#ef4444';
        $rowCls   = $f['atual'] ? 'atual' : ($f['realizado'] ? 'realizado' : '');
    ?>
    <div class="fpfc-row <?= $rowCls ?>">
        <div>
            <div class="fpfc-mes">
                <?= $f['mes_label'] ?>
                <?php if ($f['atual']): ?>
                <span style="font-size:9px;background:rgba(0,229,255,.1);color:var(--fp-cyan);padding:1px 6px;border-radius:4px;margin-left:6px;font-weight:700">ACTUAL</span>
                <?php elseif (!$f['realizado']): ?>
                <span style="font-size:9px;color:var(--fp-text3)"> · projecção</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="fpfc-val" style="color:#10b981"><?= $f['receitas'] > 0 ? $fmtKz($f['receitas']) : '<span style="color:#4a5568">—</span>' ?></div>
        <div class="fpfc-val" style="color:#ef4444"><?= $f['despesas'] > 0 ? $fmtKz($f['despesas']) : '<span style="color:#4a5568">—</span>' ?></div>
        <div class="fpfc-val" style="color:<?= $saldoCls ?>"><?= $f['saldo'] != 0 ? $fmtKz($f['saldo']) : '<span style="color:#4a5568">—</span>' ?></div>
        <div class="fpfc-val" style="color:#10b981"><?= $f['a_receber'] > 0 ? $fmtKz($f['a_receber']) : '<span style="color:#4a5568">—</span>' ?></div>
        <div class="fpfc-val" style="color:#ef4444"><?= $f['a_pagar'] > 0 ? $fmtKz($f['a_pagar']) : '<span style="color:#4a5568">—</span>' ?></div>
        <div class="fpfc-val" style="color:<?= $acumCls ?>;font-weight:900"><?= $fmtKz($f['acumulado']) ?></div>
    </div>
    <?php endforeach; else: ?>
    <div style="padding:3rem;text-align:center;color:var(--fp-text3)">
        <div style="font-size:40px;opacity:.2;margin-bottom:.75rem">📈</div>
        <div style="font-size:13px">Sem dados disponíveis. Regista lançamentos para ver o fluxo de caixa.</div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($fluxo)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('fpfc-chart'), {
    type: 'bar',
    data: {
        labels: <?= $jLabels ?>,
        datasets: [
            {
                label: 'Receitas',
                data: <?= $jReceitas ?>,
                backgroundColor: 'rgba(16,185,129,.35)',
                borderColor: '#10b981',
                borderWidth: 1.5,
                borderRadius: 6,
            },
            {
                label: 'Despesas',
                data: <?= $jDespesas ?>,
                backgroundColor: 'rgba(239,68,68,.25)',
                borderColor: '#ef4444',
                borderWidth: 1.5,
                borderRadius: 6,
            },
            {
                label: 'Acumulado',
                data: <?= $jAcum ?>,
                type: 'line',
                borderColor: '#00e5ff',
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#00e5ff',
                fill: false,
                tension: 0.4,
                yAxisID: 'y1',
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1a2233',
                titleColor: '#f0f4ff',
                bodyColor: '#8892a4',
                callbacks: { label: ctx => '  ' + Number(ctx.raw).toLocaleString('pt-AO') + ' Kz' }
            }
        },
        scales: {
            x: { ticks:{color:'#4a5568',font:{size:10}}, grid:{color:'rgba(255,255,255,.03)'} },
            y: { ticks:{color:'#4a5568',callback:v=>(v/1000).toFixed(0)+'K Kz'}, grid:{color:'rgba(255,255,255,.03)'}, beginAtZero:true },
            y1: { position:'right', ticks:{color:'#00e5ff',callback:v=>(v/1000).toFixed(0)+'K'}, grid:{display:false} }
        }
    }
});
</script>
<?php endif; ?>
