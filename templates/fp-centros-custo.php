<?php
$centros = $centros ?? [];
$fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';
$coresCentro = ['CC-01'=>'#10b981','CC-02'=>'#00e5ff','CC-03'=>'#8b5cf6','CC-04'=>'#f59e0b','CC-05'=>'#3b82f6'];
?>
<style>
:root{--fp-green:#10b981;--fp-red:#ef4444;--fp-gold:#f59e0b;--fp-cyan:#00e5ff;--fp-purple:#8b5cf6;--fp-blue:#3b82f6;--fp-bg2:#161b27;--fp-bg3:#1e2535;--fp-text:#f0f4ff;--fp-text2:#8892a4;--fp-text3:#4a5568;--fp-border:rgba(255,255,255,.07)}
.cc-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.cc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1.25rem}
.cc-card{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden;transition:border-color .2s,transform .2s}
.cc-card:hover{transform:translateY(-2px)}
.cc-card-top{padding:1.25rem 1.5rem;border-bottom:1px solid var(--fp-border)}
.cc-card-head{display:flex;align-items:center;gap:.875rem;margin-bottom:1rem}
.cc-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:900;color:#000;flex-shrink:0}
.cc-nome{font-size:15px;font-weight:800;color:var(--fp-text)}
.cc-codigo{font-size:11px;color:var(--fp-text2);margin-top:2px}
.cc-kpis{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.cc-kpi{background:var(--fp-bg3);border-radius:10px;padding:.75rem;text-align:center}
.cc-kpi-val{font-size:14px;font-weight:800;color:var(--fp-text)}
.cc-kpi-lbl{font-size:9px;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.5px;margin-top:2px}
.cc-card-bot{padding:1rem 1.5rem}
.cc-bar-wrap{height:6px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden;margin-top:6px}
.cc-bar{height:100%;border-radius:3px}
</style>

<div class="cc-header">
    <div>
        <div style="font-size:24px;font-weight:900;color:var(--fp-text)">Centros de <span style="color:var(--fp-green)">Custo</span></div>
        <div style="font-size:13px;color:var(--fp-text2);margin-top:4px"><?= count($centros) ?> centros activos · Mês actual</div>
    </div>
</div>

<div class="cc-grid">
    <?php
    $totalDesp = array_sum(array_column($centros, 'total_despesas')) ?: 1;
    foreach ($centros as $cc):
        $cor = $coresCentro[$cc['codigo']] ?? '#71717a';
        $pct = round($cc['total_despesas'] / $totalDesp * 100);
        $margem = $cc['total_receitas'] > 0 ? round(($cc['total_receitas'] - $cc['total_despesas']) / $cc['total_receitas'] * 100) : 0;
    ?>
    <div class="cc-card" style="border-top:3px solid <?= $cor ?>">
        <div class="cc-card-top">
            <div class="cc-card-head">
                <div class="cc-icon" style="background:<?= $cor ?>22;color:<?= $cor ?>;font-size:14px;font-weight:900"><?= $cc['codigo'] ?></div>
                <div>
                    <div class="cc-nome"><?= htmlspecialchars($cc['nome']) ?></div>
                    <div class="cc-codigo"><?= htmlspecialchars($cc['descricao'] ?? '') ?></div>
                </div>
            </div>
            <div class="cc-kpis">
                <div class="cc-kpi">
                    <div class="cc-kpi-val" style="color:var(--fp-green)"><?= $fmtKz($cc['total_receitas']) ?></div>
                    <div class="cc-kpi-lbl">Receitas</div>
                </div>
                <div class="cc-kpi">
                    <div class="cc-kpi-val" style="color:var(--fp-red)"><?= $fmtKz($cc['total_despesas']) ?></div>
                    <div class="cc-kpi-lbl">Despesas</div>
                </div>
            </div>
        </div>
        <div class="cc-card-bot">
            <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px">
                <span style="color:var(--fp-text2)"><?= $cc['total_lancamentos'] ?> lançamentos</span>
                <span style="color:<?= $margem>=0?'var(--fp-green)':'var(--fp-red)' ?>;font-weight:700">
                    <?= $margem >= 0 ? '+' : '' ?><?= $margem ?>% margem
                </span>
            </div>
            <div class="cc-bar-wrap">
                <div class="cc-bar" style="width:<?= $pct ?>%;background:<?= $cor ?>;opacity:.75"></div>
            </div>
            <div style="font-size:10px;color:var(--fp-text3);margin-top:4px"><?= $pct ?>% do total de despesas</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
