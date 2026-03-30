<?php
$receitaMes   = $dados['receita_mes'] ?? 0;
$despesaMes   = $dados['despesa_mes'] ?? 0;
$lucroMes     = $dados['lucro_mes'] ?? 0;
$varReceita   = $dados['var_receita'] ?? 0;
$pctMeta      = $dados['pct_meta'] ?? 0;
$meta         = $dados['meta'] ?? ['meta_receita'=>0,'meta_despesa'=>0];
$topPat       = $dados['top_patrocinadores'] ?? [];
$recentes     = $dados['receitas_recentes'] ?? [];
$despRecentes = $dados['despesas_recentes'] ?? [];
$despPorCat   = $dados['desp_por_cat'] ?? [];
$evolucao     = $dados['evolucao'] ?? [];
$mesLabel     = $dados['mes_label'] ?? ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][(int)date('m')-1] . ' ' . date('Y');
$patrocinadores = $patrocinadores ?? [];
$categorias     = $categorias ?? [];
$fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';
$lucroColor = $lucroMes >= 0 ? '#10b981' : '#ef4444';
$varColor   = $varReceita >= 0 ? '#10b981' : '#ef4444';
$jLabels = json_encode(array_column($evolucao, 'mes'));
$jVals   = json_encode(array_map('floatval', array_column($evolucao, 'total')));
?>
<style>
/* ===== FINANCE PRO DESIGN SYSTEM ===== */
:root {
    --fp-green: #10b981; --fp-green-dark: #059669; --fp-green-glow: rgba(16,185,129,.25);
    --fp-red: #ef4444;   --fp-red-dark: #dc2626;   --fp-red-glow: rgba(239,68,68,.2);
    --fp-gold: #f59e0b;  --fp-blue: #3b82f6;        --fp-purple: #8b5cf6;
    --fp-bg: #070b14;    --fp-bg1: #0d1117;          --fp-bg2: #161b27;
    --fp-bg3: #1e2535;   --fp-border: rgba(255,255,255,.07);
    --fp-text: #f0f4ff;  --fp-text2: #8892a4;        --fp-text3: #4a5568;
}

.fp-wrap { max-width: 1280px; }

/* HEADER */
.fp-page-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;
}
.fp-page-title { font-size: 26px; font-weight: 900; color: var(--fp-text); letter-spacing: -.5px; }
.fp-page-title span { color: var(--fp-green); }
.fp-page-sub { font-size: 13px; color: var(--fp-text2); margin-top: 4px; }
.fp-actions { display: flex; gap: .75rem; flex-wrap: wrap; }

/* BUTTONS */
.fp-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    padding: .625rem 1.25rem; border-radius: 10px; font-size: 13px;
    font-weight: 700; cursor: pointer; border: 1px solid; text-decoration: none;
    transition: all .2s; white-space: nowrap;
}
.fp-btn:hover { transform: translateY(-1px); text-decoration: none; }
.fp-btn-primary { background: var(--fp-green); border-color: var(--fp-green); color: #000; }
.fp-btn-primary:hover { background: var(--fp-green-dark); color: #000; }
.fp-btn-danger  { background: rgba(239,68,68,.12); border-color: rgba(239,68,68,.35); color: var(--fp-red); }
.fp-btn-danger:hover  { background: rgba(239,68,68,.2); color: var(--fp-red); }
.fp-btn-ghost   { background: var(--fp-bg3); border-color: var(--fp-border); color: var(--fp-text2); }
.fp-btn-ghost:hover   { color: var(--fp-text); border-color: rgba(255,255,255,.15); }

/* KPI CARDS */
.fp-kpi-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1.25rem; margin-bottom: 2rem; }
.fp-kpi-card {
    background: var(--fp-bg2); border: 1px solid var(--fp-border);
    border-radius: 16px; padding: 1.5rem; position: relative; overflow: hidden;
    transition: border-color .2s, transform .2s;
}
.fp-kpi-card:hover { transform: translateY(-2px); }
.fp-kpi-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
    border-radius: 16px 16px 0 0;
}
.fp-kpi-card.green::before { background: var(--fp-green); }
.fp-kpi-card.red::before   { background: var(--fp-red); }
.fp-kpi-card.gold::before  { background: var(--fp-gold); }
.fp-kpi-card.purple::before{ background: var(--fp-purple); }
.fp-kpi-icon {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; margin-bottom: 1rem;
}
.fp-kpi-card.green .fp-kpi-icon { background: rgba(16,185,129,.12); }
.fp-kpi-card.red .fp-kpi-icon   { background: rgba(239,68,68,.1); }
.fp-kpi-card.gold .fp-kpi-icon  { background: rgba(245,158,11,.1); }
.fp-kpi-card.purple .fp-kpi-icon{ background: rgba(139,92,246,.1); }
.fp-kpi-label { font-size: 11px; font-weight: 700; color: var(--fp-text2); text-transform: uppercase; letter-spacing: .8px; margin-bottom: .5rem; }
.fp-kpi-value { font-size: 20px; font-weight: 900; color: var(--fp-text); line-height: 1; margin-bottom: .5rem; }
.fp-kpi-sub { font-size: 11px; color: var(--fp-text3); display: flex; align-items: center; gap: .375rem; }
.fp-kpi-badge { padding: 2px 7px; border-radius: 20px; font-size: 10px; font-weight: 700; }

/* CONTENT GRID */
.fp-content-grid { display: grid; grid-template-columns: 1fr 380px; gap: 1.5rem; }
.fp-content-grid.wide { grid-template-columns: 1fr; }

/* CARDS */
.fp-card {
    background: var(--fp-bg2); border: 1px solid var(--fp-border);
    border-radius: 16px; overflow: hidden; margin-bottom: 1.5rem;
}
.fp-card-header {
    padding: 1.125rem 1.5rem; border-bottom: 1px solid var(--fp-border);
    display: flex; align-items: center; justify-content: space-between;
}
.fp-card-title { font-size: 14px; font-weight: 700; color: var(--fp-text); display: flex; align-items: center; gap: .625rem; }
.fp-card-title .icon { font-size: 16px; }
.fp-card-action { font-size: 11px; color: var(--fp-text2); text-decoration: none; font-weight: 600; }
.fp-card-action:hover { color: var(--fp-text); text-decoration: none; }
.fp-card-body { padding: 1.25rem 1.5rem; }
.fp-card-body.no-pad { padding: 0; }

/* TABLE */
.fp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.fp-table thead th {
    padding: .75rem 1.5rem; font-size: 10px; font-weight: 700;
    color: var(--fp-text3); text-transform: uppercase; letter-spacing: 1px;
    border-bottom: 1px solid var(--fp-border); text-align: left;
}
.fp-table tbody td { padding: .875rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.03); vertical-align: middle; }
.fp-table tbody tr:last-child td { border-bottom: none; }
.fp-table tbody tr:hover td { background: rgba(255,255,255,.015); }

/* STATUS BADGES */
.fp-status { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.fp-status::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
.fp-status.green  { background: rgba(16,185,129,.12); color: var(--fp-green); }
.fp-status.green::before  { background: var(--fp-green); }
.fp-status.red    { background: rgba(239,68,68,.1);   color: var(--fp-red); }
.fp-status.red::before    { background: var(--fp-red); }
.fp-status.gold   { background: rgba(245,158,11,.1);  color: var(--fp-gold); }
.fp-status.gold::before   { background: var(--fp-gold); }
.fp-status.gray   { background: rgba(255,255,255,.05);color: var(--fp-text2); }
.fp-status.gray::before   { background: var(--fp-text2); }

/* PROGRESS */
.fp-progress { height: 6px; background: rgba(255,255,255,.06); border-radius: 3px; overflow: hidden; }
.fp-progress-fill { height: 100%; border-radius: 3px; transition: width .5s; }

/* META CARD */
.fp-meta-item { margin-bottom: 1.125rem; }
.fp-meta-label { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.fp-meta-name { font-size: 12px; color: var(--fp-text2); font-weight: 600; }
.fp-meta-vals { font-size: 12px; font-weight: 700; color: var(--fp-text); }

/* PATROCINADOR RANK */
.fp-pat-item { display: flex; align-items: center; gap: 1rem; padding: .875rem 0; border-bottom: 1px solid rgba(255,255,255,.03); }
.fp-pat-item:last-child { border-bottom: none; }
.fp-pat-rank { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 900; flex-shrink: 0; }

/* MODALS */
.fp-modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.75); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; }
.fp-modal-bg.open { display: flex; }
.fp-modal { background: var(--fp-bg1); border: 1px solid rgba(255,255,255,.1); border-radius: 20px; padding: 2rem; width: 90%; max-width: 480px; max-height: 90vh; overflow-y: auto; }
.fp-modal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; }
.fp-modal-title { font-size: 16px; font-weight: 800; color: var(--fp-text); }
.fp-modal-close { background: var(--fp-bg3); border: 1px solid var(--fp-border); color: var(--fp-text2); width: 30px; height: 30px; border-radius: 8px; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; }
.fp-modal-close:hover { color: var(--fp-text); }
.fp-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.fp-field { margin-bottom: 1rem; }
.fp-field-label { font-size: 11px; font-weight: 700; color: var(--fp-text2); text-transform: uppercase; letter-spacing: .5px; display: block; margin-bottom: .5rem; }
.fp-input { width: 100%; padding: .75rem 1rem; background: var(--fp-bg3); border: 1px solid var(--fp-border); border-radius: 10px; color: var(--fp-text); font-size: 13px; outline: none; transition: border-color .2s; color-scheme: dark; }
.fp-input:focus { border-color: var(--fp-green); }
.fp-input::placeholder { color: var(--fp-text3); }
.fp-select { width: 100%; padding: .75rem 1rem; background: var(--fp-bg3); border: 1px solid var(--fp-border); border-radius: 10px; color: var(--fp-text); font-size: 13px; outline: none; }
.fp-modal-footer { display: flex; gap: .75rem; margin-top: 1.5rem; }
.fp-btn-confirm { flex: 1; padding: .875rem; background: var(--fp-green); border: none; border-radius: 10px; color: #000; font-size: 14px; font-weight: 800; cursor: pointer; transition: all .2s; }
.fp-btn-confirm:hover { background: var(--fp-green-dark); }
.fp-btn-dismiss { flex: 1; padding: .875rem; background: var(--fp-bg3); border: 1px solid var(--fp-border); border-radius: 10px; color: var(--fp-text2); font-size: 14px; cursor: pointer; }

/* DIVIDER */
.fp-divider { height: 1px; background: var(--fp-border); margin: 1rem 0; }

/* EMPTY STATE */
.fp-empty { text-align: center; padding: 2.5rem; color: var(--fp-text3); }
.fp-empty-icon { font-size: 40px; margin-bottom: .75rem; opacity: .3; }
.fp-empty-text { font-size: 13px; }

@media (max-width: 1100px) { .fp-content-grid { grid-template-columns: 1fr; } }
@media (max-width: 800px)  { .fp-kpi-grid { grid-template-columns: repeat(2,1fr); } }
</style>

<!-- PAGE HEADER -->
<div class="fp-page-header">
    <div>
        <div class="fp-page-title">Gestão <span>Financeira</span></div>
        <div class="fp-page-sub">
            📅 <?= $mesLabel ?>
            &nbsp;·&nbsp;
            <?php
            $totalPat = count($topPat);
            $activePat = count(array_filter($topPat, fn($p) => $p['total'] > 0));
            echo $activePat . ' patrocinador' . ($activePat !== 1 ? 'es' : '') . ' activo' . ($activePat !== 1 ? 's' : '');
            ?>
        </div>
    </div>
    <div class="fp-actions">
        <button onclick="fpModal('receita')" class="fp-btn fp-btn-primary">
            <i class="bi bi-plus-lg"></i> Nova Receita
        </button>
        <button onclick="fpModal('despesa')" class="fp-btn fp-btn-danger">
            <i class="bi bi-dash-lg"></i> Nova Despesa
        </button>
        <a href="/public/financas/<?= $stationId ?>/patrocinadores" class="fp-btn fp-btn-ghost">
            <i class="bi bi-building"></i> Patrocinadores
        </a>
        <a href="/public/financas/<?= $stationId ?>/contratos" class="fp-btn fp-btn-ghost">
            <i class="bi bi-file-earmark-text"></i> Contratos
        </a>
    </div>
</div>

<!-- KPI GRID -->
<div class="fp-kpi-grid">

    <!-- RECEITA -->
    <div class="fp-kpi-card green">
        <div class="fp-kpi-icon">💰</div>
        <div class="fp-kpi-label">Receitas do Mês</div>
        <div class="fp-kpi-value" style="color:var(--fp-green)"><?= $fmtKz($receitaMes) ?></div>
        <div class="fp-kpi-sub">
            <span class="fp-kpi-badge" style="background:<?= $varReceita>=0?'rgba(16,185,129,.15)':'rgba(239,68,68,.12)' ?>;color:<?= $varColor ?>">
                <?= $varReceita >= 0 ? '↑' : '↓' ?> <?= abs($varReceita) ?>%
            </span>
            <span>vs mês anterior</span>
        </div>
    </div>

    <!-- DESPESA -->
    <div class="fp-kpi-card red">
        <div class="fp-kpi-icon">📉</div>
        <div class="fp-kpi-label">Despesas do Mês</div>
        <div class="fp-kpi-value" style="color:var(--fp-red)"><?= $fmtKz($despesaMes) ?></div>
        <div class="fp-kpi-sub">
            <?php $margem = $receitaMes > 0 ? round((1 - $despesaMes/$receitaMes)*100) : 0; ?>
            <span class="fp-kpi-badge" style="background:rgba(255,255,255,.06);color:var(--fp-text2)">
                Margem <?= $margem ?>%
            </span>
        </div>
    </div>

    <!-- LUCRO/PREJUÍZO -->
    <div class="fp-kpi-card <?= $lucroMes >= 0 ? 'green' : 'red' ?>">
        <div class="fp-kpi-icon"><?= $lucroMes >= 0 ? '📈' : '📉' ?></div>
        <div class="fp-kpi-label"><?= $lucroMes >= 0 ? 'Lucro' : 'Prejuízo' ?> Líquido</div>
        <div class="fp-kpi-value" style="color:<?= $lucroColor ?>"><?= $fmtKz(abs($lucroMes)) ?></div>
        <div class="fp-kpi-sub">
            <span style="color:var(--fp-text3)">Receita − Despesa</span>
        </div>
    </div>

    <!-- META -->
    <div class="fp-kpi-card purple">
        <div class="fp-kpi-icon">🎯</div>
        <div class="fp-kpi-label">Meta do Mês</div>
        <div class="fp-kpi-value" style="color:var(--fp-purple)"><?= $pctMeta ?>%</div>
        <div class="fp-progress" style="margin-top:.5rem">
            <div class="fp-progress-fill" style="width:<?= $pctMeta ?>%;background:<?= $pctMeta>=100?'var(--fp-green)':'var(--fp-purple)' ?>"></div>
        </div>
        <div class="fp-kpi-sub" style="margin-top:.5rem">
            <span style="color:var(--fp-text3)"><?= $fmtKz($meta['meta_receita']) ?></span>
            <button onclick="fpModal('meta')" style="background:none;border:none;color:var(--fp-purple);cursor:pointer;font-size:11px;font-weight:700;padding:0">Editar →</button>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="fp-content-grid">

    <!-- COLUNA ESQUERDA -->
    <div>

        <!-- GRÁFICO EVOLUÇÃO -->
        <div class="fp-card">
            <div class="fp-card-header">
                <div class="fp-card-title"><span class="icon">📊</span> Evolução de Receitas — Últimos 6 Meses</div>
            </div>
            <div class="fp-card-body">
                <?php if (!empty($evolucao)): ?>
                <canvas id="fpEvChart" height="140"></canvas>
                <?php else: ?>
                <div class="fp-empty">
                    <div class="fp-empty-icon">📊</div>
                    <div class="fp-empty-text">Os dados acumulam com as receitas registadas</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RECEITAS RECENTES -->
        <div class="fp-card">
            <div class="fp-card-header">
                <div class="fp-card-title"><span class="icon">💚</span> Receitas Recentes</div>
                <button onclick="fpModal('receita')" class="fp-btn fp-btn-primary" style="padding:.375rem .875rem;font-size:11px">+ Nova</button>
            </div>
            <div class="fp-card-body no-pad">
                <?php if (!empty($recentes)):
                    foreach($recentes as $r):
                        $metodo = ['transferencia'=>'🏦','dinheiro'=>'💵','cheque'=>'📄','outro'=>'📋'][$r['metodo']??'outro'] ?? '💰';
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.03)">
                    <div style="display:flex;align-items:center;gap:.875rem">
                        <div style="width:36px;height:36px;border-radius:10px;background:rgba(16,185,129,.1);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0"><?= $metodo ?></div>
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--fp-text)"><?= htmlspecialchars($r['descricao']??'') ?></div>
                            <div style="font-size:11px;color:var(--fp-text2)">
                                <?= htmlspecialchars($r['patrocinador_nome']??'Directo') ?> · <?= date('d/m/Y', strtotime($r['data_receita'])) ?>
                            </div>
                        </div>
                    </div>
                    <div style="font-size:14px;font-weight:900;color:var(--fp-green)"><?= $fmtKz($r['valor']) ?></div>
                </div>
                <?php endforeach; else: ?>
                <div class="fp-empty"><div class="fp-empty-icon">💰</div><div class="fp-empty-text">Nenhuma receita registada</div></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DESPESAS RECENTES -->
        <div class="fp-card">
            <div class="fp-card-header">
                <div class="fp-card-title"><span class="icon">🔴</span> Despesas Recentes</div>
                <button onclick="fpModal('despesa')" class="fp-btn fp-btn-danger" style="padding:.375rem .875rem;font-size:11px">+ Nova</button>
            </div>
            <div class="fp-card-body no-pad">
                <?php if (!empty($despRecentes)):
                    foreach($despRecentes as $d):
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:.875rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.03)">
                    <div style="display:flex;align-items:center;gap:.875rem">
                        <div style="width:36px;height:36px;border-radius:10px;background:rgba(239,68,68,.08);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0"><?= $d['icone']??'💰' ?></div>
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--fp-text)"><?= htmlspecialchars($d['descricao']) ?></div>
                            <div style="font-size:11px;color:var(--fp-text2)"><?= htmlspecialchars($d['categoria_nome']??'Outros') ?> · <?= date('d/m/Y', strtotime($d['data_despesa'])) ?></div>
                        </div>
                    </div>
                    <div style="font-size:14px;font-weight:900;color:var(--fp-red)"><?= $fmtKz($d['valor']) ?></div>
                </div>
                <?php endforeach; else: ?>
                <div class="fp-empty"><div class="fp-empty-icon">📉</div><div class="fp-empty-text">Nenhuma despesa registada</div></div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- COLUNA DIREITA -->
    <div>

        <!-- RESUMO DO MÊS -->
        <div class="fp-card">
            <div class="fp-card-header">
                <div class="fp-card-title"><span class="icon">📋</span> Resumo</div>
                <span style="font-size:11px;color:var(--fp-text2)"><?= ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][(int)date('m')-1] . ' ' . date('Y') ?></span>
            </div>
            <div class="fp-card-body">
                <?php
                $items = [
                    ['label'=>'Total Receitas','val'=>$receitaMes,'cor'=>'var(--fp-green)'],
                    ['label'=>'Total Despesas','val'=>$despesaMes,'cor'=>'var(--fp-red)'],
                ];
                foreach($items as $item): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.625rem 0;border-bottom:1px solid rgba(255,255,255,.04)">
                    <span style="font-size:12px;color:var(--fp-text2)"><?= $item['label'] ?></span>
                    <span style="font-size:13px;font-weight:700;color:<?= $item['cor'] ?>"><?= $fmtKz($item['val']) ?></span>
                </div>
                <?php endforeach; ?>
                <div class="fp-divider"></div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:13px;font-weight:700;color:var(--fp-text)"><?= $lucroMes>=0?'Lucro':'Prejuízo' ?></span>
                    <span style="font-size:16px;font-weight:900;color:<?= $lucroColor ?>"><?= $fmtKz(abs($lucroMes)) ?></span>
                </div>
            </div>
        </div>

        <!-- META -->
        <div class="fp-card">
            <div class="fp-card-header">
                <div class="fp-card-title"><span class="icon">🎯</span> Meta do Mês</div>
                <button onclick="fpModal('meta')" class="fp-btn fp-btn-ghost" style="padding:.375rem .875rem;font-size:11px">Definir</button>
            </div>
            <div class="fp-card-body">
                <?php
                $pctDesp = $meta['meta_despesa'] > 0 ? min(100, round($despesaMes / $meta['meta_despesa'] * 100)) : 0;
                $despBarColor = $pctDesp >= 90 ? 'var(--fp-red)' : ($pctDesp >= 70 ? 'var(--fp-gold)' : 'var(--fp-green)');
                ?>
                <div class="fp-meta-item">
                    <div class="fp-meta-label">
                        <span class="fp-meta-name">💰 Receita</span>
                        <span class="fp-meta-vals"><?= $fmtKz($receitaMes) ?> / <?= $fmtKz($meta['meta_receita']) ?></span>
                    </div>
                    <div class="fp-progress">
                        <div class="fp-progress-fill" style="width:<?= $pctMeta ?>%;background:<?= $pctMeta>=100?'var(--fp-gold)':'var(--fp-green)' ?>"></div>
                    </div>
                    <div style="text-align:right;font-size:10px;color:var(--fp-text3);margin-top:4px"><?= $pctMeta ?>% atingido</div>
                </div>
                <div class="fp-meta-item">
                    <div class="fp-meta-label">
                        <span class="fp-meta-name">📉 Limite Despesas</span>
                        <span class="fp-meta-vals"><?= $fmtKz($despesaMes) ?> / <?= $fmtKz($meta['meta_despesa']) ?></span>
                    </div>
                    <div class="fp-progress">
                        <div class="fp-progress-fill" style="width:<?= $pctDesp ?>%;background:<?= $despBarColor ?>"></div>
                    </div>
                    <div style="text-align:right;font-size:10px;color:var(--fp-text3);margin-top:4px"><?= $pctDesp ?>% do limite</div>
                </div>
            </div>
        </div>

        <!-- TOP PATROCINADORES -->
        <div class="fp-card">
            <div class="fp-card-header">
                <div class="fp-card-title"><span class="icon">🏆</span> Top Patrocinadores</div>
                <a href="/public/financas/<?= $stationId ?>/patrocinadores" class="fp-card-action">Ver todos →</a>
            </div>
            <div class="fp-card-body" style="padding:.75rem 1.5rem">
                <?php if (!empty($topPat)):
                    $rankColors = ['#f59e0b','#a1a1aa','#92400e'];
                    foreach($topPat as $i => $p):
                        $cor = $rankColors[$i] ?? 'rgba(255,255,255,.1)';
                ?>
                <div class="fp-pat-item">
                    <div class="fp-pat-rank" style="background:<?= $cor ?>22;color:<?= $cor ?>"><?= $i+1 ?></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:13px;font-weight:600;color:var(--fp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($p['nome']) ?></div>
                        <div style="font-size:10px;color:var(--fp-text3)">Este mês</div>
                    </div>
                    <div style="font-size:13px;font-weight:800;color:var(--fp-green)"><?= $fmtKz($p['total']) ?></div>
                </div>
                <?php endforeach; else: ?>
                <div class="fp-empty">
                    <div class="fp-empty-icon">🏢</div>
                    <div class="fp-empty-text"><a href="/public/financas/<?= $stationId ?>/patrocinadores" style="color:var(--fp-green)">Adicionar patrocinador →</a></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DESPESAS POR CATEGORIA -->
        <?php if (!empty(array_filter($despPorCat, fn($c) => $c['total'] > 0))): ?>
        <div class="fp-card">
            <div class="fp-card-header">
                <div class="fp-card-title"><span class="icon">🗂️</span> Despesas por Categoria</div>
            </div>
            <div class="fp-card-body">
                <?php foreach($despPorCat as $c):
                    if ($c['total'] <= 0) continue;
                    $pct = $despesaMes > 0 ? round($c['total'] / $despesaMes * 100) : 0;
                ?>
                <div style="margin-bottom:1rem">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px">
                        <div style="display:flex;align-items:center;gap:.5rem;font-size:12px;color:var(--fp-text2)">
                            <span><?= $c['icone'] ?></span><span><?= htmlspecialchars($c['nome']) ?></span>
                            <span style="font-size:10px;color:var(--fp-text3)"><?= $pct ?>%</span>
                        </div>
                        <span style="font-size:12px;font-weight:700;color:var(--fp-red)"><?= $fmtKz($c['total']) ?></span>
                    </div>
                    <div class="fp-progress">
                        <div class="fp-progress-fill" style="width:<?= $pct ?>%;background:<?= htmlspecialchars($c['cor']) ?>;opacity:.75"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- ===== MODALS ===== -->

<!-- MODAL RECEITA -->
<div class="fp-modal-bg" id="fp-modal-receita">
    <div class="fp-modal">
        <div class="fp-modal-head">
            <div class="fp-modal-title">💰 Nova Receita</div>
            <button class="fp-modal-close" onclick="fpCloseAll()">✕</button>
        </div>
        <div class="fp-field">
            <label class="fp-field-label">Descrição *</label>
            <input type="text" id="r_desc" class="fp-input" placeholder="Ex: Patrocínio TPA — Junho 2026">
        </div>
        <div class="fp-form-row">
            <div class="fp-field">
                <label class="fp-field-label">Valor (Kz) *</label>
                <input type="text" id="r_valor" class="fp-input" placeholder="Ex: 150.000,00">
            </div>
            <div class="fp-field">
                <label class="fp-field-label">Data *</label>
                <input type="date" id="r_data" class="fp-input" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="fp-field">
            <label class="fp-field-label">Patrocinador</label>
            <select id="r_pat" class="fp-select">
                <option value="">— Seleccionar —</option>
                <?php foreach($patrocinadores as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fp-form-row">
            <div class="fp-field">
                <label class="fp-field-label">Método de Pagamento</label>
                <select id="r_metodo" class="fp-select">
                    <option value="transferencia">🏦 Transferência</option>
                    <option value="dinheiro">💵 Dinheiro</option>
                    <option value="cheque">📄 Cheque</option>
                    <option value="outro">📋 Outro</option>
                </select>
            </div>
            <div class="fp-field">
                <label class="fp-field-label">Referência</label>
                <input type="text" id="r_ref" class="fp-input" placeholder="Nº transferência...">
            </div>
        </div>
        <div class="fp-modal-footer">
            <button class="fp-btn-confirm" onclick="fpSaveReceita()">✅ Guardar Receita</button>
            <button class="fp-btn-dismiss" onclick="fpCloseAll()">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL DESPESA -->
<div class="fp-modal-bg" id="fp-modal-despesa">
    <div class="fp-modal">
        <div class="fp-modal-head">
            <div class="fp-modal-title">📉 Nova Despesa</div>
            <button class="fp-modal-close" onclick="fpCloseAll()">✕</button>
        </div>
        <div class="fp-field">
            <label class="fp-field-label">Descrição *</label>
            <input type="text" id="d_desc" class="fp-input" placeholder="Ex: Servidor mensal VPS...">
        </div>
        <div class="fp-form-row">
            <div class="fp-field">
                <label class="fp-field-label">Valor (Kz) *</label>
                <input type="text" id="d_valor" class="fp-input" placeholder="Ex: 25.000,00">
            </div>
            <div class="fp-field">
                <label class="fp-field-label">Data *</label>
                <input type="date" id="d_data" class="fp-input" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="fp-form-row">
            <div class="fp-field">
                <label class="fp-field-label">Categoria</label>
                <select id="d_cat" class="fp-select">
                    <?php foreach($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= $c['icone'] ?> <?= htmlspecialchars($c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fp-field">
                <label class="fp-field-label">Método</label>
                <select id="d_metodo" class="fp-select">
                    <option value="transferencia">🏦 Transferência</option>
                    <option value="dinheiro">💵 Dinheiro</option>
                    <option value="cheque">📄 Cheque</option>
                    <option value="outro">📋 Outro</option>
                </select>
            </div>
        </div>
        <div class="fp-modal-footer">
            <button class="fp-btn-confirm" style="background:var(--fp-red);color:#fff" onclick="fpSaveDespesa()">✅ Guardar Despesa</button>
            <button class="fp-btn-dismiss" onclick="fpCloseAll()">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL META -->
<div class="fp-modal-bg" id="fp-modal-meta">
    <div class="fp-modal">
        <div class="fp-modal-head">
            <div class="fp-modal-title">🎯 Definir Meta Mensal</div>
            <button class="fp-modal-close" onclick="fpCloseAll()">✕</button>
        </div>
        <div class="fp-form-row">
            <div class="fp-field">
                <label class="fp-field-label">Mês</label>
                <select id="m_mes" class="fp-select">
                    <?php for($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $m==(int)date('m')?'selected':'' ?>><?= ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'][$m-1] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="fp-field">
                <label class="fp-field-label">Ano</label>
                <input type="number" id="m_ano" class="fp-input" value="<?= date('Y') ?>">
            </div>
        </div>
        <div class="fp-field">
            <label class="fp-field-label">Meta de Receita (Kz)</label>
            <input type="text" id="m_rec" class="fp-input" placeholder="Ex: 500.000,00"
                   value="<?= $meta['meta_receita']>0 ? number_format($meta['meta_receita'],2,',','.') : '' ?>">
        </div>
        <div class="fp-field">
            <label class="fp-field-label">Limite de Despesas (Kz)</label>
            <input type="text" id="m_desp" class="fp-input" placeholder="Ex: 200.000,00"
                   value="<?= $meta['meta_despesa']>0 ? number_format($meta['meta_despesa'],2,',','.') : '' ?>">
        </div>
        <div class="fp-modal-footer">
            <button class="fp-btn-confirm" onclick="fpSaveMeta()">✅ Guardar Meta</button>
            <button class="fp-btn-dismiss" onclick="fpCloseAll()">Cancelar</button>
        </div>
    </div>
</div>

<!-- CHART.JS + SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const FP_SID = <?= $stationId ?>;

<?php if (!empty($evolucao)): ?>
new Chart(document.getElementById('fpEvChart'), {
    type: 'bar',
    data: {
        labels: <?= $jLabels ?>,
        datasets: [{
            label: 'Kz',
            data: <?= $jVals ?>,
            backgroundColor: function(ctx) {
                const c = ctx.chart.ctx, g = c.createLinearGradient(0, 0, 0, 200);
                g.addColorStop(0, 'rgba(16,185,129,.5)');
                g.addColorStop(1, 'rgba(16,185,129,.05)');
                return g;
            },
            borderColor: '#10b981',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: {
            backgroundColor: '#1e2535', titleColor: '#f0f4ff', bodyColor: '#8892a4',
            callbacks: { label: ctx => '  ' + Number(ctx.raw).toLocaleString('pt-AO') + ' Kz' }
        }},
        scales: {
            x: { ticks:{color:'#4a5568',font:{size:10}}, grid:{color:'rgba(255,255,255,.03)'} },
            y: { ticks:{color:'#4a5568',callback: v => (v/1000).toFixed(0)+'K'}, grid:{color:'rgba(255,255,255,.03)'}, beginAtZero:true }
        }
    }
});
<?php endif; ?>

function fpModal(t) {
    fpCloseAll();
    document.getElementById('fp-modal-'+t).classList.add('open');
}
function fpCloseAll() {
    document.querySelectorAll('.fp-modal-bg').forEach(m => m.classList.remove('open'));
}
document.querySelectorAll('.fp-modal-bg').forEach(m => m.addEventListener('click', e => { if(e.target===m) fpCloseAll(); }));

function fpPost(url, data) {
    return fetch(url, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams(data)
    }).then(r=>r.json());
}

function fpSaveReceita() {
    const btn = document.querySelector('#fp-modal-receita .fp-btn-confirm');
    btn.textContent='...'; btn.disabled=true;
    fpPost('/public/financas/'+FP_SID+'/receitas/salvar', {
        descricao: document.getElementById('r_desc').value,
        valor: document.getElementById('r_valor').value,
        data_receita: document.getElementById('r_data').value,
        patrocinador_id: document.getElementById('r_pat').value,
        metodo: document.getElementById('r_metodo').value,
        referencia: document.getElementById('r_ref').value,
    }).then(() => { fpCloseAll(); location.reload(); });
}

function fpSaveDespesa() {
    const btn = document.querySelector('#fp-modal-despesa .fp-btn-confirm');
    btn.textContent='...'; btn.disabled=true;
    fpPost('/public/financas/'+FP_SID+'/despesas/salvar', {
        descricao: document.getElementById('d_desc').value,
        valor: document.getElementById('d_valor').value,
        data_despesa: document.getElementById('d_data').value,
        categoria_id: document.getElementById('d_cat').value,
        metodo: document.getElementById('d_metodo').value,
    }).then(() => { fpCloseAll(); location.reload(); });
}

function fpSaveMeta() {
    fpPost('/public/financas/'+FP_SID+'/metas/salvar', {
        mes: document.getElementById('m_mes').value,
        ano: document.getElementById('m_ano').value,
        meta_receita: document.getElementById('m_rec').value,
        meta_despesa: document.getElementById('m_desp').value,
    }).then(() => { fpCloseAll(); location.reload(); });
}
</script>
