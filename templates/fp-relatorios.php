<?php
$dre       = $dre ?? ['totais'=>[],'meses'=>[],'ano'=>date('Y')];
$dashboard = $dashboard ?? [];
$fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';
$totais = $dre['totais'] ?? [];
$ano    = $dre['ano']    ?? date('Y');
$meses_pt = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
?>
<style>
.fpr-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.fpr-kpi{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:14px;padding:1.25rem;position:relative;overflow:hidden;cursor:pointer;transition:border-color .2s}
.fpr-kpi:hover{border-color:rgba(255,255,255,.15)}
.fpr-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;border-radius:14px 14px 0 0}
.fpr-kpi.g::before{background:var(--fp-green)}.fpr-kpi.r::before{background:var(--fp-red)}
.fpr-kpi.c::before{background:var(--fp-cyan)}.fpr-kpi.p::before{background:var(--fp-purple)}
.fpr-kpi.gold::before{background:var(--fp-gold)}
.fpr-kpi-icon{font-size:28px;margin-bottom:.75rem;opacity:.7}
.fpr-kpi-lbl{font-size:11px;font-weight:700;color:var(--fp-text2);margin-bottom:.375rem}
.fpr-kpi-val{font-size:20px;font-weight:900}
.fpr-kpi-sub{font-size:10px;color:var(--fp-text3);margin-top:.375rem}
.fpr-section{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden;margin-bottom:1.5rem}
.fpr-section-head{padding:1rem 1.5rem;border-bottom:1px solid var(--fp-border);font-size:13px;font-weight:700;color:var(--fp-text);display:flex;align-items:center;justify-content:space-between}
.fpr-export-btn{display:inline-flex;align-items:center;gap:.375rem;padding:.375rem .875rem;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;border:1px solid rgba(16,185,129,.3);background:rgba(16,185,129,.08);color:var(--fp-green);transition:all .15s}
.fpr-export-btn:hover{background:rgba(16,185,129,.15)}
.fpr-resumo-row{display:flex;justify-content:space-between;align-items:center;padding:.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.03)}
.fpr-resumo-row:last-child{border-bottom:none}
@media(max-width:900px){.fpr-grid{grid-template-columns:1fr 1fr}}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <div>
        <div style="font-size:24px;font-weight:900;color:var(--fp-text)">Relatórios <span style="color:var(--fp-text2)">Financeiros</span></div>
        <div style="font-size:13px;color:var(--fp-text2);margin-top:4px">Resumo consolidado · Exercício <?= $ano ?></div>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="/public/financas/<?= $stationId ?>/exportar-pdf/dre?ano=<?= $ano ?>"
           style="display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.3);border-radius:9px;color:#8b5cf6;font-size:12px;font-weight:700;text-decoration:none">
            <i class="bi bi-file-earmark-pdf"></i> DRE PDF
        </a>
        <a href="/public/financas/<?= $stationId ?>/exportar-pdf/fluxo"
           style="display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:9px;color:#10b981;font-size:12px;font-weight:700;text-decoration:none">
            <i class="bi bi-file-earmark-pdf"></i> Fluxo PDF
        </a>
        <a href="?ano=<?= $ano-1 ?>" style="padding:.5rem .875rem;border-radius:8px;background:var(--fp-bg3);border:1px solid var(--fp-border);color:var(--fp-text2);text-decoration:none;font-size:12px">← <?= $ano-1 ?></a>
        <span style="font-size:13px;font-weight:800;color:var(--fp-text);padding:0 .5rem"><?= $ano ?></span>
    </div>
</div>

<!-- KPI GRID -->
<div class="fpr-grid">
    <div class="fpr-kpi g" onclick="window.location='/public/financas/<?= $stationId ?>/lancamentos'">
        <div class="fpr-kpi-icon">💰</div>
        <div class="fpr-kpi-lbl">Receita Total do Ano</div>
        <div class="fpr-kpi-val" style="color:var(--fp-green)"><?= $fmtKz($totais['receita'] ?? 0) ?></div>
        <div class="fpr-kpi-sub">Ver lançamentos →</div>
    </div>
    <div class="fpr-kpi r" onclick="window.location='/public/financas/<?= $stationId ?>/lancamentos?tipo=despesa'">
        <div class="fpr-kpi-icon">📉</div>
        <div class="fpr-kpi-lbl">Custos Totais do Ano</div>
        <div class="fpr-kpi-val" style="color:var(--fp-red)"><?= $fmtKz(($totais['despesa']??0) + ($totais['comissoes']??0)) ?></div>
        <div class="fpr-kpi-sub">Despesas + Comissões →</div>
    </div>
    <div class="fpr-kpi c" onclick="window.location='/public/financas/<?= $stationId ?>/dre'">
        <div class="fpr-kpi-icon">📈</div>
        <div class="fpr-kpi-lbl">EBITDA do Ano</div>
        <div class="fpr-kpi-val" style="color:<?= ($totais['ebitda']??0)>=0?'var(--fp-cyan)':'var(--fp-red)' ?>"><?= $fmtKz($totais['ebitda'] ?? 0) ?></div>
        <div class="fpr-kpi-sub">Margem: <?= $totais['margem'] ?? 0 ?>% →</div>
    </div>
    <div class="fpr-kpi gold" onclick="window.location='/public/financas/<?= $stationId ?>/contas-receber'">
        <div class="fpr-kpi-icon">📥</div>
        <div class="fpr-kpi-lbl">A Receber</div>
        <div class="fpr-kpi-val" style="color:var(--fp-gold)"><?= $fmtKz($dashboard['a_receber'] ?? 0) ?></div>
        <div class="fpr-kpi-sub">Contas a receber →</div>
    </div>
    <div class="fpr-kpi r" onclick="window.location='/public/financas/<?= $stationId ?>/contas-pagar'">
        <div class="fpr-kpi-icon">📤</div>
        <div class="fpr-kpi-lbl">A Pagar</div>
        <div class="fpr-kpi-val" style="color:var(--fp-red)"><?= $fmtKz($dashboard['a_pagar'] ?? 0) ?></div>
        <div class="fpr-kpi-sub">Contas a pagar →</div>
    </div>
    <div class="fpr-kpi p" onclick="window.location='/public/financas/<?= $stationId ?>/conta-corrente'">
        <div class="fpr-kpi-icon">🏦</div>
        <div class="fpr-kpi-lbl">Saldo Bancário</div>
        <div class="fpr-kpi-val" style="color:var(--fp-purple)"><?= $fmtKz($dashboard['saldo_bancario'] ?? 0) ?></div>
        <div class="fpr-kpi-sub">Conta corrente →</div>
    </div>
</div>

<!-- DRE RESUMO -->
<div class="fpr-section">
    <div class="fpr-section-head">
        <span>📊 DRE — Resumo Anual <?= $ano ?></span>
        <a href="/public/financas/<?= $stationId ?>/dre" class="fpr-export-btn">Ver completo →</a>
    </div>
    <?php
    $dreLinhas = [
        ['label'=>'Receita Bruta',         'val'=>$totais['receita']??0,     'cor'=>'var(--fp-green)', 'bold'=>true],
        ['label'=>'(-) Custos Operacionais','val'=>$totais['despesa']??0,     'cor'=>'var(--fp-red)',   'bold'=>false],
        ['label'=>'= Lucro Bruto',         'val'=>$totais['lucro_bruto']??0, 'cor'=>'var(--fp-cyan)',  'bold'=>true],
        ['label'=>'(-) Comissões',         'val'=>$totais['comissoes']??0,   'cor'=>'var(--fp-gold)',  'bold'=>false],
        ['label'=>'= EBITDA',              'val'=>$totais['ebitda']??0,      'cor'=>'var(--fp-purple)','bold'=>true],
    ];
    foreach($dreLinhas as $l): ?>
    <div class="fpr-resumo-row">
        <span style="font-size:12px;<?= $l['bold']?'font-weight:700;color:var(--fp-text)':'color:var(--fp-text2)' ?>"><?= $l['label'] ?></span>
        <span style="font-size:<?= $l['bold']?'14':'12' ?>px;font-weight:<?= $l['bold']?'800':'600' ?>;color:<?= ($l['val']??0)>=0?$l['cor']:'var(--fp-red)' ?>">
            <?= $fmtKz($l['val']) ?>
        </span>
    </div>
    <?php endforeach; ?>
</div>

<!-- LINKS RÁPIDOS -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:.75rem">
    <?php
    $links = [
        ['/plano-contas','📊','Plano de Contas','Estrutura contábil'],
        ['/centros-custo','⊞','Centro de Custo','Análise por departamento'],
        ['/fluxo-caixa','📈','Fluxo de Caixa','Projecção de entradas/saídas'],
        ['/comissoes','💼','Comissões','Pagamentos a executivos'],
    ];
    foreach($links as $l): ?>
    <a href="/public/financas/<?= $stationId ?><?= $l[0] ?>"
       style="background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:12px;padding:1rem 1.25rem;text-decoration:none;transition:border-color .2s;display:block">
        <div style="font-size:22px;margin-bottom:.5rem"><?= $l[1] ?></div>
        <div style="font-size:12px;font-weight:700;color:var(--fp-text);margin-bottom:2px"><?= $l[2] ?></div>
        <div style="font-size:10px;color:var(--fp-text3)"><?= $l[3] ?></div>
    </a>
    <?php endforeach; ?>
</div>
