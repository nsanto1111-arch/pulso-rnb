<?php
$receitaMes   = $dados['receita_mes']         ?? 0;
$despesaMes   = $dados['despesa_mes']         ?? 0;
$lucroMes     = $dados['lucro_mes']           ?? 0;
$aReceber     = $dados['a_receber']           ?? 0;
$aPagar       = $dados['a_pagar']             ?? 0;
$saldoBanc    = $dados['saldo_bancario']      ?? 0;
$pctMeta      = $dados['pct_meta']            ?? 0;
$meta         = $dados['meta']                ?? ['meta_receita'=>0,'meta_despesa'=>0];
$ultLanc      = $dados['ultimos_lancamentos'] ?? [];
$evolucao     = $dados['evolucao']            ?? [];
$despPorCC    = $dados['desp_por_cc']         ?? [];
$alertas      = $alertas ?? [];
$patrocinadores = $patrocinadores ?? [];
$categorias     = $categorias ?? [];
$fluxo          = $fluxo ?? [];

$fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';
$meses_pt = ['January'=>'Janeiro','February'=>'Fevereiro','March'=>'Março',
             'April'=>'Abril','May'=>'Maio','June'=>'Junho','July'=>'Julho',
             'August'=>'Agosto','September'=>'Setembro','October'=>'Outubro',
             'November'=>'Novembro','December'=>'Dezembro'];
$mesLabel = strtr(date('F Y'), $meses_pt);

$jMeses    = json_encode(array_map(fn($f)=>$f['mes_curto'], $fluxo));
$jReceitas = json_encode(array_map(fn($f)=>(float)$f['receitas'], $fluxo));
$jDespesas = json_encode(array_map(fn($f)=>(float)$f['despesas'], $fluxo));
?>
<style>
/* Dashboard específico */
.fpd-alerts{margin-bottom:1.25rem;display:flex;flex-direction:column;gap:.5rem}
.fpd-alert{display:flex;align-items:center;gap:.875rem;padding:.75rem 1.25rem;border-radius:12px;border:1px solid;font-size:12px;cursor:pointer;transition:opacity .15s}
.fpd-alert:hover{opacity:.85}
.fpd-alert.danger{background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.25);color:#fca5a5}
.fpd-alert.warning{background:rgba(245,158,11,.07);border-color:rgba(245,158,11,.25);color:#fcd34d}
.fpd-alert.info{background:rgba(59,130,246,.07);border-color:rgba(59,130,246,.25);color:#93c5fd}
.fpd-alert.success{background:rgba(16,185,129,.07);border-color:rgba(16,185,129,.25);color:#6ee7b7}
.fpd-alert-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:14px}
.fpd-alert.danger .fpd-alert-icon{background:rgba(239,68,68,.15)}
.fpd-alert.warning .fpd-alert-icon{background:rgba(245,158,11,.15)}
.fpd-alert.info .fpd-alert-icon{background:rgba(59,130,246,.15)}
.fpd-alert-titulo{font-weight:700;margin-bottom:1px}
.fpd-alert-msg{font-size:11px;opacity:.7}
.fpd-alert-arrow{margin-left:auto;opacity:.5;font-size:11px}

.fpd-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.25rem}
.fpd-kpi{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;padding:1.25rem 1.5rem;position:relative;overflow:hidden;transition:transform .2s,border-color .2s;cursor:default}
.fpd-kpi:hover{transform:translateY(-2px)}
.fpd-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:16px 16px 0 0}
.fpd-kpi.green::before{background:var(--fp-green)}.fpd-kpi.red::before{background:var(--fp-red)}
.fpd-kpi.cyan::before{background:var(--fp-cyan)}.fpd-kpi.purple::before{background:var(--fp-purple)}
.fpd-kpi.gold::before{background:var(--fp-gold)}
.fpd-kpi-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:.875rem}
.fpd-kpi-icon{width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:18px}
.fpd-kpi-lbl{font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:.375rem}
.fpd-kpi-val{font-size:20px;font-weight:900;line-height:1;margin-bottom:.375rem}
.fpd-kpi-sub{font-size:10px;color:var(--fp-text3)}
.fpd-kpi-trend{font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px}

.fpd-grid{display:grid;grid-template-columns:1fr 360px;gap:1.25rem}
.fpd-chart-card{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden;margin-bottom:1.25rem}
.fpd-card-head{padding:1rem 1.5rem;border-bottom:1px solid var(--fp-border);display:flex;align-items:center;justify-content:space-between}
.fpd-card-title{font-size:13px;font-weight:700;color:var(--fp-text);display:flex;align-items:center;gap:.5rem}
.fpd-card-link{font-size:11px;color:var(--fp-text2);text-decoration:none;font-weight:600}
.fpd-card-link:hover{color:var(--fp-text);text-decoration:none}

.fpd-lanc-row{display:flex;align-items:center;gap:.875rem;padding:.75rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.03);transition:background .12s}
.fpd-lanc-row:last-child{border-bottom:none}
.fpd-lanc-row:hover{background:rgba(255,255,255,.02)}
.fpd-lanc-tipo{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}

.fpd-right-card{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden;margin-bottom:1.25rem}
.fpd-meta-bar{height:6px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden;margin-top:5px}
.fpd-meta-fill{height:100%;border-radius:3px;transition:width .5s}

.fpd-quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1.25rem}
.fpd-quick-btn{display:flex;flex-direction:column;align-items:flex-start;padding:1rem 1.25rem;background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:14px;text-decoration:none;transition:all .2s;cursor:pointer}
.fpd-quick-btn:hover{border-color:rgba(255,255,255,.15);transform:translateY(-2px);text-decoration:none}
.fpd-quick-icon{font-size:22px;margin-bottom:.5rem}
.fpd-quick-label{font-size:12px;font-weight:700;color:var(--fp-text);margin-bottom:2px}
.fpd-quick-sub{font-size:10px;color:var(--fp-text3)}

.fpd-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center}
.fpd-modal-bg.open{display:flex}
.fpd-modal{background:var(--fp-bg1);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2rem;width:90%;max-width:500px;max-height:90vh;overflow-y:auto}
.fpd-field{margin-bottom:1rem}
.fpd-field label{display:block;font-size:10px;font-weight:700;color:var(--fp-text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem}
.fpd-input{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none;color-scheme:dark}
.fpd-input:focus{border-color:rgba(16,185,129,.5)}
.fpd-input::placeholder{color:var(--fp-text3)}
.fpd-select{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none}
.fpd-form-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.fpd-modal-footer{display:flex;gap:.75rem;margin-top:1.5rem}
.fpd-btn-save{flex:1;padding:.875rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:14px;font-weight:800;cursor:pointer}
.fpd-btn-cancel{flex:1;padding:.875rem;background:rgba(255,255,255,.04);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text2);font-size:14px;cursor:pointer}

@media(max-width:1100px){.fpd-kpis{grid-template-columns:1fr 1fr}.fpd-grid{grid-template-columns:1fr}}
@media(max-width:600px){.fpd-kpis{grid-template-columns:1fr}}
</style>

<!-- ALERTAS -->
<?php if (!empty($alertas)): ?>
<div class="fpd-alerts">
    <?php foreach(array_slice($alertas, 0, 4) as $al):
        $href = !empty($al['link']) ? "/public/financas/{$stationId}/{$al['link']}" : '#';
        $iconMap = ['exclamation-triangle'=>'⚠️','clock'=>'⏰','graph-down'=>'📉','bullseye'=>'🎯','person-check'=>'💼'];
        $icon = $iconMap[$al['icone']] ?? '📌';
    ?>
    <div class="fpd-alert <?= $al['tipo'] ?>" onclick="<?= $href!='#'?"window.location='{$href}'":'void(0)' ?>">
        <div class="fpd-alert-icon"><?= $icon ?></div>
        <div style="min-width:0">
            <div class="fpd-alert-titulo"><?= htmlspecialchars($al['titulo']) ?></div>
            <div class="fpd-alert-msg"><?= htmlspecialchars($al['msg']) ?></div>
        </div>
        <?php if ($href !== '#'): ?><div class="fpd-alert-arrow">→</div><?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- HEADER -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:1rem">
    <div>
        <div style="font-size:22px;font-weight:900;color:var(--fp-text)">Visão Geral <span style="color:var(--fp-text2);font-weight:400;font-size:16px">— <?= $mesLabel ?></span></div>
    </div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap">
        <button onclick="document.getElementById('fpd-modal-rec').classList.add('open')"
                style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;cursor:pointer">
            <i class="bi bi-plus-lg"></i> + Receita
        </button>
        <button onclick="document.getElementById('fpd-modal-desp').classList.add('open')"
                style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:10px;color:#ef4444;font-size:13px;font-weight:700;cursor:pointer">
            <i class="bi bi-dash-lg"></i> + Despesa
        </button>
        <button onclick="document.getElementById('fpd-modal-meta').classList.add('open')"
                style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:rgba(255,255,255,.04);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text2);font-size:13px;font-weight:600;cursor:pointer">
            🎯 Meta
        </button>
    </div>
</div>

<!-- KPIs -->
<div class="fpd-kpis">
    <div class="fpd-kpi green">
        <div class="fpd-kpi-top">
            <div class="fpd-kpi-icon" style="background:rgba(16,185,129,.12)">💰</div>
            <?php $v=$dados['var_receita']??0; $vc=$v>=0?'var(--fp-green)':'var(--fp-red)'; ?>
            <div class="fpd-kpi-trend" style="background:<?= $v>=0?'rgba(16,185,129,.12)':'rgba(239,68,68,.12)' ?>;color:<?= $vc ?>">
                <?= $v >= 0 ? '↑' : '↓' ?> <?= abs($v) ?>%
            </div>
        </div>
        <div class="fpd-kpi-lbl">Receitas do Mês</div>
        <div class="fpd-kpi-val" style="color:var(--fp-green)"><?= $fmtKz($receitaMes) ?></div>
        <div class="fpd-kpi-sub">vs mês anterior</div>
    </div>
    <div class="fpd-kpi red">
        <div class="fpd-kpi-top">
            <div class="fpd-kpi-icon" style="background:rgba(239,68,68,.1)">📉</div>
            <?php $margem = $receitaMes > 0 ? round((1-$despesaMes/$receitaMes)*100) : 0; ?>
            <div class="fpd-kpi-trend" style="background:rgba(255,255,255,.06);color:var(--fp-text2)">
                <?= $margem ?>% margem
            </div>
        </div>
        <div class="fpd-kpi-lbl">Despesas do Mês</div>
        <div class="fpd-kpi-val" style="color:var(--fp-red)"><?= $fmtKz($despesaMes) ?></div>
        <div class="fpd-kpi-sub">Custos operacionais</div>
    </div>
    <div class="fpd-kpi <?= $lucroMes>=0?'cyan':'red' ?>">
        <div class="fpd-kpi-top">
            <div class="fpd-kpi-icon" style="background:<?= $lucroMes>=0?'rgba(0,229,255,.1)':'rgba(239,68,68,.1)' ?>"><?= $lucroMes>=0?'📈':'📉' ?></div>
            <div class="fpd-kpi-trend" style="background:<?= $lucroMes>=0?'rgba(0,229,255,.1)':'rgba(239,68,68,.1)' ?>;color:<?= $lucroMes>=0?'var(--fp-cyan)':'var(--fp-red)' ?>">
                <?= $lucroMes>=0?'Lucro':'Prejuízo' ?>
            </div>
        </div>
        <div class="fpd-kpi-lbl">Resultado Líquido</div>
        <div class="fpd-kpi-val" style="color:<?= $lucroMes>=0?'var(--fp-cyan)':'var(--fp-red)' ?>"><?= $fmtKz(abs($lucroMes)) ?></div>
        <div class="fpd-kpi-sub">Receita − Despesas</div>
    </div>
    <div class="fpd-kpi gold">
        <div class="fpd-kpi-top">
            <div class="fpd-kpi-icon" style="background:rgba(245,158,11,.1)">🏦</div>
            <div class="fpd-kpi-trend" style="background:rgba(245,158,11,.1);color:var(--fp-gold)">
                BFA + BPC
            </div>
        </div>
        <div class="fpd-kpi-lbl">Saldo Bancário</div>
        <div class="fpd-kpi-val" style="color:var(--fp-gold)"><?= $fmtKz($saldoBanc) ?></div>
        <div class="fpd-kpi-sub"><a href="/public/financas/<?= $stationId ?>/conta-corrente" style="color:var(--fp-gold);text-decoration:none">Ver conta corrente →</a></div>
    </div>
</div>

<!-- MAIN GRID -->
<div class="fpd-grid">
    <!-- ESQUERDA -->
    <div>
        <!-- GRÁFICO FLUXO DE CAIXA -->
        <div class="fpd-chart-card">
            <div class="fpd-card-head">
                <div class="fpd-card-title"><span>📊</span> Fluxo de Caixa — 6 Meses</div>
                <a href="/public/financas/<?= $stationId ?>/fluxo-caixa" class="fpd-card-link">Ver completo →</a>
            </div>
            <div style="padding:1.25rem 1.5rem">
                <?php if (!empty($fluxo)): ?>
                <canvas id="fpd-chart" height="180"></canvas>
                <?php else: ?>
                <div style="height:180px;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--fp-text3)">
                    <div style="font-size:36px;opacity:.2;margin-bottom:.75rem">📊</div>
                    <div style="font-size:13px">Regista lançamentos para ver o gráfico</div>
                    <a href="/public/financas/<?= $stationId ?>/lancamentos"
                       style="margin-top:.75rem;padding:.5rem 1rem;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:8px;color:var(--fp-green);text-decoration:none;font-size:12px;font-weight:700">
                        + Primeiro Lançamento
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ÚLTIMOS LANÇAMENTOS -->
        <div class="fpd-chart-card">
            <div class="fpd-card-head">
                <div class="fpd-card-title"><span>📒</span> Últimos Lançamentos</div>
                <a href="/public/financas/<?= $stationId ?>/lancamentos" class="fpd-card-link">Ver todos →</a>
            </div>
            <?php if (!empty($ultLanc)): foreach(array_slice($ultLanc,0,6) as $l):
                $isRec = $l['tipo']==='receita';
                $cor   = $isRec ? 'var(--fp-green)' : 'var(--fp-red)';
                $bgIco = $isRec ? 'rgba(16,185,129,.1)' : 'rgba(239,68,68,.08)';
                $ico   = $isRec ? '💰' : '📉';
                $sinal = $isRec ? '+' : '-';
            ?>
            <div class="fpd-lanc-row">
                <div class="fpd-lanc-tipo" style="background:<?= $bgIco ?>"><?= $ico ?></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:12px;font-weight:600;color:var(--fp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($l['historico']) ?></div>
                    <div style="font-size:10px;color:var(--fp-text3)"><?= date('d/m/Y', strtotime($l['data_lancamento'])) ?> · <?= htmlspecialchars($l['centro_custo_nome'] ?? '—') ?></div>
                </div>
                <div style="font-size:13px;font-weight:800;color:<?= $cor ?>;white-space:nowrap"><?= $sinal ?><?= $fmtKz($l['valor']) ?></div>
            </div>
            <?php endforeach; else: ?>
            <div style="padding:2.5rem;text-align:center;color:var(--fp-text3)">
                <div style="font-size:36px;opacity:.2;margin-bottom:.75rem">📒</div>
                <div style="font-size:13px;margin-bottom:1rem">Nenhum lançamento ainda</div>
                <a href="/public/financas/<?= $stationId ?>/lancamentos"
                   style="padding:.625rem 1.25rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;text-decoration:none">
                    + Criar Primeiro Lançamento
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- DIREITA -->
    <div>
        <!-- META DO MÊS -->
        <div class="fpd-right-card">
            <div class="fpd-card-head">
                <div class="fpd-card-title"><span>🎯</span> Meta de <?= $mesLabel ?></div>
                <button onclick="document.getElementById('fpd-modal-meta').classList.add('open')"
                        style="font-size:10px;color:var(--fp-text2);background:none;border:none;cursor:pointer;font-weight:600">Definir</button>
            </div>
            <div style="padding:1.25rem 1.5rem">
                <?php if ($meta['meta_receita'] > 0): ?>
                <div style="margin-bottom:1rem">
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:5px">
                        <span style="color:var(--fp-text2)">💰 Receita</span>
                        <span style="color:var(--fp-text);font-weight:700"><?= $pctMeta ?>%</span>
                    </div>
                    <div class="fpd-meta-bar">
                        <div class="fpd-meta-fill" style="width:<?= $pctMeta ?>%;background:<?= $pctMeta>=100?'var(--fp-gold)':($pctMeta>=80?'var(--fp-green)':'var(--fp-purple)') ?>"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--fp-text3);margin-top:4px">
                        <span><?= $fmtKz($receitaMes) ?></span>
                        <span><?= $fmtKz($meta['meta_receita']) ?></span>
                    </div>
                </div>
                <?php $pd = $meta['meta_despesa']>0 ? min(100,round($despesaMes/$meta['meta_despesa']*100)) : 0; ?>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:5px">
                        <span style="color:var(--fp-text2)">📉 Despesas</span>
                        <span style="color:<?= $pd>=90?'var(--fp-red)':'var(--fp-text)' ?>;font-weight:700"><?= $pd ?>%</span>
                    </div>
                    <div class="fpd-meta-bar">
                        <div class="fpd-meta-fill" style="width:<?= $pd ?>%;background:<?= $pd>=90?'var(--fp-red)':($pd>=70?'var(--fp-gold)':'var(--fp-green)') ?>"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--fp-text3);margin-top:4px">
                        <span><?= $fmtKz($despesaMes) ?></span>
                        <span>Limite: <?= $fmtKz($meta['meta_despesa']) ?></span>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:1rem">
                    <div style="font-size:28px;margin-bottom:.5rem;opacity:.3">🎯</div>
                    <div style="font-size:12px;color:var(--fp-text3);margin-bottom:.75rem">Sem meta definida para este mês</div>
                    <button onclick="document.getElementById('fpd-modal-meta').classList.add('open')"
                            style="padding:.5rem 1rem;background:rgba(139,92,246,.1);border:1px solid rgba(139,92,246,.3);border-radius:8px;color:var(--fp-purple);font-size:11px;font-weight:700;cursor:pointer">
                        + Definir Meta
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- A PAGAR / RECEBER -->
        <div class="fpd-right-card">
            <div class="fpd-card-head">
                <div class="fpd-card-title"><span>⇄</span> Contas em Aberto</div>
            </div>
            <div style="padding:1rem 1.5rem">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.875rem">
                    <a href="/public/financas/<?= $stationId ?>/contas-pagar"
                       style="text-align:center;padding:1rem;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);border-radius:12px;text-decoration:none;transition:all .15s;display:block">
                        <div style="font-size:24px;font-weight:900;color:var(--fp-red)"><?= $fmtKz($aPagar) ?></div>
                        <div style="font-size:10px;color:var(--fp-text3);margin-top:3px">A Pagar</div>
                        <?php $nPagar=(int)($dados['vencidos_pagar']??0); if($nPagar>0): ?>
                        <div style="font-size:9px;font-weight:700;color:var(--fp-red);margin-top:4px">⚠ <?= $nPagar ?> vencido<?= $nPagar!==1?'s':'' ?></div>
                        <?php endif; ?>
                    </a>
                    <a href="/public/financas/<?= $stationId ?>/contas-receber"
                       style="text-align:center;padding:1rem;background:rgba(16,185,129,.07);border:1px solid rgba(16,185,129,.2);border-radius:12px;text-decoration:none;transition:all .15s;display:block">
                        <div style="font-size:24px;font-weight:900;color:var(--fp-green)"><?= $fmtKz($aReceber) ?></div>
                        <div style="font-size:10px;color:var(--fp-text3);margin-top:3px">A Receber</div>
                        <?php $nRec=(int)($dados['vencidos_receber']??0); if($nRec>0): ?>
                        <div style="font-size:9px;font-weight:700;color:var(--fp-gold);margin-top:4px">⏰ <?= $nRec ?> vencido<?= $nRec!==1?'s':'' ?></div>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- ACESSO RÁPIDO -->
        <div class="fpd-quick-grid">
            <a href="/public/financas/<?= $stationId ?>/lancamentos" class="fpd-quick-btn">
                <div class="fpd-quick-icon">📒</div>
                <div class="fpd-quick-label">Lançamentos</div>
                <div class="fpd-quick-sub">Registar movimentos</div>
            </a>
            <a href="/public/financas/<?= $stationId ?>/dre" class="fpd-quick-btn">
                <div class="fpd-quick-icon">📈</div>
                <div class="fpd-quick-label">DRE</div>
                <div class="fpd-quick-sub">Resultados do ano</div>
            </a>
            <a href="/public/financas/<?= $stationId ?>/fluxo-caixa" class="fpd-quick-btn">
                <div class="fpd-quick-icon">💸</div>
                <div class="fpd-quick-label">Fluxo de Caixa</div>
                <div class="fpd-quick-sub">Projecções futuras</div>
            </a>
            <a href="/public/financas/<?= $stationId ?>/relatorios-fp" class="fpd-quick-btn">
                <div class="fpd-quick-icon">📋</div>
                <div class="fpd-quick-label">Relatórios</div>
                <div class="fpd-quick-sub">Resumo consolidado</div>
            </a>
        </div>

        <!-- DESPESAS POR CENTRO -->
        <?php if (!empty(array_filter($despPorCC, fn($c) => $c['total'] > 0))): ?>
        <div class="fpd-right-card">
            <div class="fpd-card-head">
                <div class="fpd-card-title"><span>⊞</span> Despesas por Centro</div>
                <a href="/public/financas/<?= $stationId ?>/centros-custo" class="fpd-card-link">Ver →</a>
            </div>
            <div style="padding:1rem 1.5rem">
                <?php
                $maxCC = max(array_column($despPorCC, 'total')) ?: 1;
                $coreCC = ['#10b981','#00e5ff','#8b5cf6','#f59e0b','#3b82f6'];
                $ci = 0;
                foreach($despPorCC as $cc):
                    if ($cc['total'] <= 0) continue;
                    $pctCC = round($cc['total'] / $maxCC * 100);
                    $cor = $coreCC[$ci % count($coreCC)]; $ci++;
                ?>
                <div style="margin-bottom:.875rem">
                    <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px">
                        <span style="color:var(--fp-text2);font-weight:600"><?= htmlspecialchars($cc['nome']) ?></span>
                        <span style="color:var(--fp-text);font-weight:700"><?= $fmtKz($cc['total']) ?></span>
                    </div>
                    <div class="fpd-meta-bar">
                        <div class="fpd-meta-fill" style="width:<?= $pctCC ?>%;background:<?= $cor ?>;opacity:.7"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAIS -->
<!-- RECEITA -->
<div class="fpd-modal-bg" id="fpd-modal-rec">
    <div class="fpd-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)">💰 Nova Receita</div>
            <button onclick="document.getElementById('fpd-modal-rec').classList.remove('open')"
                    style="background:rgba(255,255,255,.06);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <div class="fpd-field"><label>Descrição *</label><input type="text" id="r_desc" class="fpd-input" placeholder="Ex: Patrocínio TPA — Março 2026"></div>
        <div class="fpd-form-2">
            <div class="fpd-field"><label>Valor (Kz) *</label><input type="text" id="r_valor" class="fpd-input" placeholder="Ex: 250.000,00"></div>
            <div class="fpd-field"><label>Data *</label><input type="date" id="r_data" class="fpd-input" value="<?= date('Y-m-d') ?>"></div>
        </div>
        <div class="fpd-field">
            <label>Patrocinador</label>
            <select id="r_pat" class="fpd-select">
                <option value="">— Seleccionar —</option>
                <?php foreach($patrocinadores as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fpd-form-2">
            <div class="fpd-field"><label>Método</label><select id="r_met" class="fpd-select"><option value="transferencia">🏦 Transferência</option><option value="dinheiro">💵 Dinheiro</option><option value="cheque">📄 Cheque</option></select></div>
            <div class="fpd-field"><label>Referência</label><input type="text" id="r_ref" class="fpd-input" placeholder="Nº transferência..."></div>
        </div>
        <div class="fpd-modal-footer">
            <button class="fpd-btn-save" onclick="fpdSalvarReceita()">✅ Guardar</button>
            <button class="fpd-btn-cancel" onclick="document.getElementById('fpd-modal-rec').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<!-- DESPESA -->
<div class="fpd-modal-bg" id="fpd-modal-desp">
    <div class="fpd-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)">📉 Nova Despesa</div>
            <button onclick="document.getElementById('fpd-modal-desp').classList.remove('open')"
                    style="background:rgba(255,255,255,.06);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <div class="fpd-field"><label>Descrição *</label><input type="text" id="d_desc" class="fpd-input" placeholder="Ex: Servidor VPS — Março 2026"></div>
        <div class="fpd-form-2">
            <div class="fpd-field"><label>Valor (Kz) *</label><input type="text" id="d_valor" class="fpd-input" placeholder="Ex: 45.000,00"></div>
            <div class="fpd-field"><label>Data *</label><input type="date" id="d_data" class="fpd-input" value="<?= date('Y-m-d') ?>"></div>
        </div>
        <div class="fpd-field">
            <label>Categoria</label>
            <select id="d_cat" class="fpd-select">
                <?php foreach($categorias as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['icone'].' '.$c['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fpd-modal-footer">
            <button class="fpd-btn-save" style="background:var(--fp-red);color:#fff" onclick="fpdSalvarDespesa()">✅ Guardar</button>
            <button class="fpd-btn-cancel" onclick="document.getElementById('fpd-modal-desp').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<!-- META -->
<div class="fpd-modal-bg" id="fpd-modal-meta">
    <div class="fpd-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)">🎯 Meta do Mês</div>
            <button onclick="document.getElementById('fpd-modal-meta').classList.remove('open')"
                    style="background:rgba(255,255,255,.06);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <div class="fpd-form-2">
            <div class="fpd-field">
                <label>Mês</label>
                <select id="m_mes" class="fpd-select">
                    <?php $mpt=['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
                    for($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $m==(int)date('m')?'selected':'' ?>><?= $mpt[$m-1] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="fpd-field"><label>Ano</label><input type="number" id="m_ano" class="fpd-input" value="<?= date('Y') ?>"></div>
        </div>
        <div class="fpd-field"><label>Meta de Receita (Kz)</label><input type="text" id="m_rec" class="fpd-input" placeholder="Ex: 1.500.000,00" value="<?= $meta['meta_receita']>0?number_format($meta['meta_receita'],2,',','.'):'' ?>"></div>
        <div class="fpd-field"><label>Limite de Despesas (Kz)</label><input type="text" id="m_desp" class="fpd-input" placeholder="Ex: 500.000,00" value="<?= $meta['meta_despesa']>0?number_format($meta['meta_despesa'],2,',','.'):'' ?>"></div>
        <div class="fpd-modal-footer">
            <button class="fpd-btn-save" onclick="fpdSalvarMeta()">✅ Guardar</button>
            <button class="fpd-btn-cancel" onclick="document.getElementById('fpd-modal-meta').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const FP_SID = <?= $stationId ?>;

<?php if (!empty($fluxo)): ?>
new Chart(document.getElementById('fpd-chart'), {
    type: 'bar',
    data: {
        labels: <?= $jMeses ?>,
        datasets: [
            {
                label: 'Receitas',
                data: <?= $jReceitas ?>,
                backgroundColor: function(ctx) {
                    const c=ctx.chart.ctx, g=c.createLinearGradient(0,0,0,200);
                    g.addColorStop(0,'rgba(16,185,129,.6)');
                    g.addColorStop(1,'rgba(16,185,129,.1)');
                    return g;
                },
                borderColor: '#10b981',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            },
            {
                label: 'Despesas',
                data: <?= $jDespesas ?>,
                backgroundColor: 'rgba(239,68,68,.25)',
                borderColor: '#ef4444',
                borderWidth: 1.5,
                borderRadius: 6,
                borderSkipped: false,
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
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
            y: { ticks:{color:'#4a5568',callback:v=>(v/1000).toFixed(0)+'K'}, grid:{color:'rgba(255,255,255,.03)'}, beginAtZero:true }
        }
    }
});
<?php endif; ?>

function fpdPost(url, data) {
    return fetch(url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams(data)}).then(r=>r.json());
}

function fpdSalvarReceita() {
    const btn = document.querySelector('#fpd-modal-rec .fpd-btn-save');
    btn.textContent='...'; btn.disabled=true;
    fpdPost('/public/financas/'+FP_SID+'/receitas/salvar', {
        descricao: document.getElementById('r_desc').value,
        valor: document.getElementById('r_valor').value,
        data_receita: document.getElementById('r_data').value,
        patrocinador_id: document.getElementById('r_pat').value,
        metodo: document.getElementById('r_met').value,
        referencia: document.getElementById('r_ref').value,
    }).then(()=>{document.getElementById('fpd-modal-rec').classList.remove('open');location.reload();});
}

function fpdSalvarDespesa() {
    const btn = document.querySelector('#fpd-modal-desp .fpd-btn-save');
    btn.textContent='...'; btn.disabled=true;
    fpdPost('/public/financas/'+FP_SID+'/despesas/salvar', {
        descricao: document.getElementById('d_desc').value,
        valor: document.getElementById('d_valor').value,
        data_despesa: document.getElementById('d_data').value,
        categoria_id: document.getElementById('d_cat').value,
    }).then(()=>{document.getElementById('fpd-modal-desp').classList.remove('open');location.reload();});
}

function fpdSalvarMeta() {
    fpdPost('/public/financas/'+FP_SID+'/metas/salvar', {
        mes: document.getElementById('m_mes').value,
        ano: document.getElementById('m_ano').value,
        meta_receita: document.getElementById('m_rec').value,
        meta_despesa: document.getElementById('m_desp').value,
    }).then(()=>{document.getElementById('fpd-modal-meta').classList.remove('open');location.reload();});
}

document.querySelectorAll('.fpd-modal-bg').forEach(m=>{
    m.addEventListener('click',e=>{if(e.target===m)m.classList.remove('open');});
});
</script>
