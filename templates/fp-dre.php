<?php
$meses  = $dados['meses']  ?? [];
$totais = $dados['totais'] ?? [];
$ano    = $dados['ano']    ?? date('Y');
$fmtKz  = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';

$linhas = [
    ['key'=>'receita',     'label'=>'(+) Receita Bruta',           'cor'=>'#10b981', 'bold'=>true,  'sep'=>false, 'pct'=>false],
    ['key'=>'despesa',     'label'=>'(-) Custos Operacionais',     'cor'=>'#ef4444', 'bold'=>false, 'sep'=>false, 'pct'=>false],
    ['key'=>'lucro_bruto', 'label'=>'= Lucro Bruto',               'cor'=>'#00e5ff', 'bold'=>true,  'sep'=>true,  'pct'=>false],
    ['key'=>'comissoes',   'label'=>'(-) Comissões de Vendas',     'cor'=>'#f59e0b', 'bold'=>false, 'sep'=>false, 'pct'=>false],
    ['key'=>'ebitda',      'label'=>'= EBITDA / Resultado Líquido','cor'=>'#8b5cf6', 'bold'=>true,  'sep'=>true,  'pct'=>false],
    ['key'=>'margem',      'label'=>'Margem Líquida (%)',          'cor'=>'#8892a4', 'bold'=>false, 'sep'=>false, 'pct'=>true],
];
?>
<style>
:root{--fp-green:#10b981;--fp-red:#ef4444;--fp-gold:#f59e0b;--fp-cyan:#00e5ff;--fp-purple:#8b5cf6;--fp-blue:#3b82f6;--fp-bg2:#161b27;--fp-bg3:#1e2535;--fp-text:#f0f4ff;--fp-text2:#8892a4;--fp-text3:#4a5568;--fp-border:rgba(255,255,255,.07)}
.fpdre-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.fpdre-kpi{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:14px;padding:1.25rem;position:relative;overflow:hidden}
.fpdre-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;border-radius:14px 14px 0 0}
.fpdre-kpi.g::before{background:var(--fp-green)}.fpdre-kpi.r::before{background:var(--fp-red)}
.fpdre-kpi.c::before{background:var(--fp-cyan)}.fpdre-kpi.p::before{background:var(--fp-purple)}
.fpdre-kpi-lbl{font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:.5rem}
.fpdre-kpi-val{font-size:20px;font-weight:900}
.fpdre-kpi-sub{font-size:10px;color:var(--fp-text3);margin-top:.375rem}
.fpdre-table-wrap{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow-x:auto}
.fpdre-table{width:100%;border-collapse:collapse;font-size:11px}
.fpdre-table th{padding:.625rem .875rem;font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px;border-bottom:1px solid var(--fp-border);text-align:right;white-space:nowrap;background:var(--fp-bg3)}
.fpdre-table th:first-child{text-align:left;position:sticky;left:0;background:var(--fp-bg3);z-index:1;min-width:210px}
.fpdre-table td{padding:.625rem .875rem;text-align:right;border-bottom:1px solid rgba(255,255,255,.025);white-space:nowrap}
.fpdre-table td:first-child{text-align:left;position:sticky;left:0;background:var(--fp-bg2);z-index:1}
.fpdre-table tr.sep td{border-top:2px solid rgba(255,255,255,.08)}
.fpdre-table tr.bold td{font-weight:800}
.fpdre-table .total-col{border-left:1px solid rgba(255,255,255,.08);font-weight:800}
.fpdre-margem-bar{height:3px;background:rgba(255,255,255,.06);border-radius:2px;margin-top:.5rem}
</style>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem">
    <div>
        <div style="font-size:24px;font-weight:900;color:var(--fp-text)">Demonstração de <span style="color:var(--fp-purple)">Resultados</span></div>
        <div style="font-size:13px;color:var(--fp-text2);margin-top:4px">DRE · Exercício <?= $ano ?></div>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center">
        <a href="?ano=<?= $ano-1 ?>" style="padding:.5rem .875rem;border-radius:8px;background:var(--fp-bg2);border:1px solid var(--fp-border);color:var(--fp-text2);text-decoration:none;font-size:12px;font-weight:600">← <?= $ano-1 ?></a>
        <span style="font-size:14px;font-weight:900;color:var(--fp-text);padding:0 .5rem"><?= $ano ?></span>
        <?php if ($ano < (int)date('Y')): ?>
        <a href="?ano=<?= $ano+1 ?>" style="padding:.5rem .875rem;border-radius:8px;background:var(--fp-bg2);border:1px solid var(--fp-border);color:var(--fp-text2);text-decoration:none;font-size:12px;font-weight:600"><?= $ano+1 ?> →</a>
        <?php endif; ?>
    </div>
    <a href="/public/financas/<?= $stationId ?>/exportar-pdf/dre?ano=<?= $ano ?>"
       style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:10px;color:#ef4444;font-size:13px;font-weight:700;text-decoration:none">
        <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
    </a>
</div>

<!-- KPIs -->
<div class="fpdre-kpis">
    <div class="fpdre-kpi g">
        <div class="fpdre-kpi-lbl">Receita Bruta</div>
        <div class="fpdre-kpi-val" style="color:var(--fp-green)"><?= $fmtKz($totais['receita'] ?? 0) ?></div>
        <div class="fpdre-kpi-sub">Total do ano</div>
    </div>
    <div class="fpdre-kpi r">
        <div class="fpdre-kpi-lbl">Custos Totais</div>
        <div class="fpdre-kpi-val" style="color:var(--fp-red)"><?= $fmtKz(($totais['despesa']??0) + ($totais['comissoes']??0)) ?></div>
        <div class="fpdre-kpi-sub">Despesas + Comissões</div>
    </div>
    <div class="fpdre-kpi c">
        <div class="fpdre-kpi-lbl">EBITDA</div>
        <div class="fpdre-kpi-val" style="color:<?= ($totais['ebitda']??0)>=0?'var(--fp-cyan)':'var(--fp-red)' ?>"><?= $fmtKz($totais['ebitda'] ?? 0) ?></div>
        <div class="fpdre-kpi-sub">Resultado líquido</div>
    </div>
    <div class="fpdre-kpi p">
        <div class="fpdre-kpi-lbl">Margem Líquida</div>
        <div class="fpdre-kpi-val" style="color:var(--fp-purple)"><?= $totais['margem'] ?? 0 ?>%</div>
        <div class="fpdre-margem-bar">
            <div style="width:<?= abs((float)($totais['margem']??0)) ?>%;height:100%;background:var(--fp-purple);border-radius:2px"></div>
        </div>
    </div>
</div>

<!-- TABELA DRE -->
<?php if (!empty($meses)): ?>
<div class="fpdre-table-wrap">
    <table class="fpdre-table">
        <thead>
            <tr>
                <th>Indicador</th>
                <?php foreach($meses as $m): ?>
                <th><?= $m['mes_curto'] ?></th>
                <?php endforeach; ?>
                <th class="total-col">TOTAL</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($linhas as $linha):
            $cls = ($linha['sep']?'sep ':'').($linha['bold']?'bold ':'');
        ?>
        <tr class="<?= $cls ?>">
            <td style="color:<?= $linha['cor'] ?>;font-size:12px"><?= $linha['label'] ?></td>
            <?php foreach($meses as $m):
                $val = (float)($m[$linha['key']] ?? 0);
                $display = $linha['pct'] ? $val.'%' : ($val != 0 ? $fmtKz($val) : '<span style="color:#4a5568">—</span>');
                $cor = $linha['pct']
                    ? ($val>=0?'var(--fp-green)':'var(--fp-red)')
                    : ($val >= 0 ? $linha['cor'] : 'var(--fp-red)');
            ?>
            <td style="color:<?= $cor ?>"><?= $display ?></td>
            <?php endforeach; ?>
            <?php
                $tval = (float)($totais[$linha['key']] ?? 0);
                $tdisplay = $linha['pct'] ? $tval.'%' : $fmtKz($tval);
                $tcor = $tval >= 0 ? $linha['cor'] : 'var(--fp-red)';
            ?>
            <td class="total-col" style="color:<?= $tcor ?>"><?= $tdisplay ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div style="background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;padding:3rem;text-align:center;color:var(--fp-text3)">
    <div style="font-size:48px;margin-bottom:1rem;opacity:.2">📊</div>
    <div style="font-size:14px;font-weight:600;color:var(--fp-text2);margin-bottom:.5rem">Sem dados para <?= $ano ?></div>
    <div style="font-size:13px">Regista lançamentos em <a href="/public/financas/<?= $stationId ?>/lancamentos" style="color:var(--fp-green)">Lançamentos</a> para ver a DRE</div>
</div>
<?php endif; ?>
