<?php
$lancamentos    = $dados['lancamentos']    ?? [];
$totalReceitas  = $dados['total_receitas'] ?? 0;
$totalDespesas  = $dados['total_despesas'] ?? 0;
$saldo          = $dados['saldo']          ?? 0;
$total          = $dados['total']          ?? 0;
$contas         = $contas ?? [];
$centros        = $centros ?? [];
$patrocinadores = $patrocinadores ?? [];
$fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';

$tipoLabel = ['receita'=>'Receita','despesa'=>'Despesa','transferencia'=>'Transferência','ajuste'=>'Ajuste'];
$tipoCor   = ['receita'=>'green','despesa'=>'red','transferencia'=>'blue','ajuste'=>'gold'];

// Filtros activos
$filtroTipo   = $_GET['tipo']   ?? '';
$filtroCentro = $_GET['centro_id'] ?? '';
$filtroMes    = $_GET['mes']    ?? date('Y-m');

// Apenas contas analíticas para lançamentos
$contasAnaliticas = array_filter($contas, fn($c) => $c['tipo'] === 'analitica');
?>
<style>
.fpl-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.fpl-kpi{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:14px;padding:1.25rem 1.5rem;position:relative;overflow:hidden}
.fpl-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;border-radius:14px 14px 0 0}
.fpl-kpi.green::before{background:var(--fp-green)}
.fpl-kpi.red::before{background:var(--fp-red)}
.fpl-kpi.cyan::before{background:var(--fp-cyan)}
.fpl-kpi.purple::before{background:var(--fp-purple)}
.fpl-kpi-lbl{font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:.5rem}
.fpl-kpi-val{font-size:20px;font-weight:900;color:var(--fp-text)}
.fpl-kpi-sub{font-size:10px;color:var(--fp-text3);margin-top:.375rem}

.fpl-toolbar{display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap}
.fpl-filter-group{display:flex;align-items:center;gap:.5rem;background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:10px;padding:4px}
.fpl-filter-btn{padding:.375rem .875rem;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;border:none;background:none;color:var(--fp-text2);transition:all .15s;white-space:nowrap}
.fpl-filter-btn.active{background:var(--fp-green);color:#000}
.fpl-filter-btn:hover:not(.active){background:rgba(255,255,255,.05);color:var(--fp-text)}
.fpl-select{padding:.5rem .875rem;background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text2);font-size:11px;outline:none;cursor:pointer}

.fpl-table-wrap{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden}
.fpl-table-head{display:grid;grid-template-columns:100px 80px 1fr 160px 140px 110px 90px 44px;gap:.5rem;padding:.75rem 1.5rem;border-bottom:1px solid var(--fp-border);background:var(--fp-bg3)}
.fpl-table-head span{font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px}
.fpl-row{display:grid;grid-template-columns:100px 80px 1fr 160px 140px 110px 90px 44px;gap:.5rem;padding:.75rem 1.5rem;align-items:center;border-bottom:1px solid rgba(255,255,255,.03);transition:background .12s;cursor:default}
.fpl-row:last-child{border-bottom:none}
.fpl-row:hover{background:rgba(255,255,255,.02)}
.fpl-numero{font-size:10px;font-weight:700;color:var(--fp-text3);font-family:monospace}
.fpl-data{font-size:11px;color:var(--fp-text2)}
.fpl-historico{font-size:12px;font-weight:600;color:var(--fp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fpl-historico-sub{font-size:10px;color:var(--fp-text3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fpl-conta{font-size:10px;color:var(--fp-text2);font-family:monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fpl-valor{font-size:13px;font-weight:800;text-align:right}
.fpl-action-btn{width:28px;height:28px;border-radius:7px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.15);color:#ef4444;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;transition:all .15s;opacity:0}
.fpl-row:hover .fpl-action-btn{opacity:1}
.fpl-action-btn:hover{background:rgba(239,68,68,.18)}

/* MODAL */
.fpl-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center}
.fpl-modal-bg.open{display:flex}
.fpl-modal{background:var(--fp-bg1);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2rem;width:90%;max-width:580px;max-height:90vh;overflow-y:auto}
.fpl-tipo-selector{display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem;margin-bottom:1.25rem}
.fpl-tipo-btn{padding:.625rem;border-radius:10px;border:1px solid var(--fp-border);background:var(--fp-bg3);color:var(--fp-text2);font-size:11px;font-weight:700;cursor:pointer;text-align:center;transition:all .15s}
.fpl-tipo-btn.active-receita{border-color:var(--fp-green);background:rgba(16,185,129,.1);color:var(--fp-green)}
.fpl-tipo-btn.active-despesa{border-color:var(--fp-red);background:rgba(239,68,68,.1);color:var(--fp-red)}
.fpl-tipo-btn.active-transferencia{border-color:var(--fp-blue);background:rgba(59,130,246,.1);color:var(--fp-blue)}
.fpl-tipo-btn.active-ajuste{border-color:var(--fp-gold);background:rgba(245,158,11,.1);color:var(--fp-gold)}
.fpl-form-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.fpl-field{margin-bottom:1rem}
.fpl-field label{display:block;font-size:10px;font-weight:700;color:var(--fp-text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem}
.fpl-input{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none;color-scheme:dark}
.fpl-input:focus{border-color:rgba(16,185,129,.5)}
.fpl-input::placeholder{color:var(--fp-text3)}
.fpl-select-inp{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none}
.fpl-modal-footer{display:flex;gap:.75rem;margin-top:1.5rem}
.fpl-btn-save{flex:1;padding:.875rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:14px;font-weight:800;cursor:pointer}
.fpl-btn-cancel{flex:1;padding:.875rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text2);font-size:14px;cursor:pointer}
@media(max-width:900px){.fpl-kpis{grid-template-columns:1fr 1fr}.fpl-table-head,.fpl-row{grid-template-columns:80px 1fr 90px 36px}}
</style>

<!-- KPIs -->
<div class="fpl-kpis">
    <div class="fpl-kpi green">
        <div class="fpl-kpi-lbl">Total Receitas</div>
        <div class="fpl-kpi-val" style="color:var(--fp-green)"><?= $fmtKz($totalReceitas) ?></div>
        <div class="fpl-kpi-sub">Período filtrado</div>
    </div>
    <div class="fpl-kpi red">
        <div class="fpl-kpi-lbl">Total Despesas</div>
        <div class="fpl-kpi-val" style="color:var(--fp-red)"><?= $fmtKz($totalDespesas) ?></div>
        <div class="fpl-kpi-sub">Período filtrado</div>
    </div>
    <div class="fpl-kpi <?= $saldo >= 0 ? 'cyan' : 'red' ?>">
        <div class="fpl-kpi-lbl">Saldo do Período</div>
        <div class="fpl-kpi-val" style="color:<?= $saldo >= 0 ? 'var(--fp-cyan)' : 'var(--fp-red)' ?>"><?= $fmtKz(abs($saldo)) ?></div>
        <div class="fpl-kpi-sub"><?= $saldo >= 0 ? 'Positivo' : 'Negativo' ?></div>
    </div>
    <div class="fpl-kpi purple">
        <div class="fpl-kpi-lbl">Lançamentos</div>
        <div class="fpl-kpi-val" style="color:var(--fp-purple)"><?= $total ?></div>
        <div class="fpl-kpi-sub">Registos encontrados</div>
    </div>
</div>

<!-- TOOLBAR -->
<div class="fpl-toolbar">
    <div class="fpl-filter-group">
        <?php foreach([''=>'Todos','receita'=>'Receitas','despesa'=>'Despesas','transferencia'=>'Transferências','ajuste'=>'Ajustes'] as $k=>$lbl): ?>
        <button class="fpl-filter-btn <?= $filtroTipo===$k?'active':'' ?>"
                onclick="fplSetTipo('<?= $k ?>')"><?= $lbl ?></button>
        <?php endforeach; ?>
    </div>

    <select class="fpl-select" id="fpl-mes" onchange="fplFiltrar()">
        <?php for($m=0;$m<12;$m++):
            $d=date('Y-m', strtotime("-$m months"));
            $lbl=date('F Y', strtotime("-$m months"));
        ?>
        <option value="<?= $d ?>" <?= $filtroMes===$d?'selected':'' ?>><?= $lbl ?></option>
        <?php endfor; ?>
    </select>

    <select class="fpl-select" id="fpl-centro" onchange="fplFiltrar()">
        <option value="">Todos os centros</option>
        <?php foreach($centros as $cc): ?>
        <option value="<?= $cc['id'] ?>" <?= $filtroCentro==$cc['id']?'selected':'' ?>><?= htmlspecialchars($cc['nome']) ?></option>
        <?php endforeach; ?>
    </select>

    <div style="margin-left:auto">
        <button onclick="document.getElementById('fpl-modal').classList.add('open')"
                style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;cursor:pointer">
            <i class="bi bi-plus-lg"></i> Novo Lançamento
        </button>
    </div>
</div>

<!-- TABELA -->
<div class="fpl-table-wrap">
    <div class="fpl-table-head">
        <span>Número</span>
        <span>Data</span>
        <span>Histórico</span>
        <span>Débito</span>
        <span>Crédito</span>
        <span>Centro Custo</span>
        <span style="text-align:right">Valor</span>
        <span></span>
    </div>

    <?php if (!empty($lancamentos)): foreach($lancamentos as $l):
        $tipo = $l['tipo'] ?? 'receita';
        $cor = ['receita'=>'var(--fp-green)','despesa'=>'var(--fp-red)','transferencia'=>'var(--fp-blue)','ajuste'=>'var(--fp-gold)'][$tipo] ?? 'var(--fp-text)';
        $sinal = $tipo === 'receita' ? '+' : ($tipo === 'despesa' ? '-' : '');
    ?>
    <div class="fpl-row">
        <div class="fpl-numero"><?= htmlspecialchars($l['numero'] ?? '') ?></div>
        <div class="fpl-data"><?= date('d/m/Y', strtotime($l['data_lancamento'])) ?></div>
        <div style="min-width:0">
            <div class="fpl-historico"><?= htmlspecialchars($l['historico']) ?></div>
            <div class="fpl-historico-sub">
                <?= htmlspecialchars($l['patrocinador_nome'] ?? '') ?>
                <?php if (!empty($l['documento_ref'])): ?>· <?= htmlspecialchars($l['documento_ref']) ?><?php endif; ?>
            </div>
        </div>
        <div class="fpl-conta" title="<?= htmlspecialchars(($l['cod_debito']??'').' '.$l['nom_debito']) ?>">
            <span style="color:var(--fp-text3)"><?= htmlspecialchars($l['cod_debito'] ?? '') ?></span>
            <?= htmlspecialchars($l['nom_debito'] ?? '—') ?>
        </div>
        <div class="fpl-conta" title="<?= htmlspecialchars(($l['cod_credito']??'').' '.$l['nom_credito']) ?>">
            <span style="color:var(--fp-text3)"><?= htmlspecialchars($l['cod_credito'] ?? '') ?></span>
            <?= htmlspecialchars($l['nom_credito'] ?? '—') ?>
        </div>
        <div style="font-size:10px;color:var(--fp-text3)"><?= htmlspecialchars($l['centro_nome'] ?? '—') ?></div>
        <div class="fpl-valor" style="color:<?= $cor ?>"><?= $sinal ?><?= $fmtKz($l['valor']) ?></div>
        <div>
            <button class="fpl-action-btn" onclick="fplCancelar(<?= $l['id'] ?>)" title="Cancelar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
    <?php endforeach; else: ?>
    <div class="fp-empty">
        <div class="fp-empty-icon">📒</div>
        <div class="fp-empty-text">Nenhum lançamento encontrado</div>
        <div style="margin-top:1rem">
            <button onclick="document.getElementById('fpl-modal').classList.add('open')"
                    style="padding:.625rem 1.25rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;cursor:pointer">
                + Primeiro Lançamento
            </button>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL NOVO LANÇAMENTO -->
<div class="fpl-modal-bg" id="fpl-modal">
    <div class="fpl-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)">📒 Novo Lançamento</div>
            <button onclick="document.getElementById('fpl-modal').classList.remove('open')"
                    style="background:rgba(255,255,255,.06);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>

        <!-- Tipo selector -->
        <input type="hidden" id="fpl-tipo-val" value="receita">
        <div class="fpl-tipo-selector">
            <button class="fpl-tipo-btn active-receita" onclick="fplSelectTipo('receita',this)">💰 Receita</button>
            <button class="fpl-tipo-btn" onclick="fplSelectTipo('despesa',this)">📉 Despesa</button>
            <button class="fpl-tipo-btn" onclick="fplSelectTipo('transferencia',this)">⇄ Transferência</button>
            <button class="fpl-tipo-btn" onclick="fplSelectTipo('ajuste',this)">🔧 Ajuste</button>
        </div>

        <div class="fpl-field">
            <label>Histórico / Descrição *</label>
            <input type="text" id="fpl-historico" class="fpl-input" placeholder="Ex: Recebimento Patrocínio Multichoice — Março 2026">
        </div>

        <div class="fpl-form-2">
            <div class="fpl-field">
                <label>Data do Lançamento *</label>
                <input type="date" id="fpl-data" class="fpl-input" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="fpl-field">
                <label>Valor (Kz) *</label>
                <input type="text" id="fpl-valor" class="fpl-input" placeholder="Ex: 250.000,00">
            </div>
        </div>

        <div class="fpl-form-2">
            <div class="fpl-field">
                <label>Conta a Débito *</label>
                <select id="fpl-debito" class="fpl-select-inp">
                    <option value="">— Seleccionar —</option>
                    <?php foreach($contasAnaliticas as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['codigo'].' — '.$c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fpl-field">
                <label>Conta a Crédito *</label>
                <select id="fpl-credito" class="fpl-select-inp">
                    <option value="">— Seleccionar —</option>
                    <?php foreach($contasAnaliticas as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['codigo'].' — '.$c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="fpl-form-2">
            <div class="fpl-field">
                <label>Centro de Custo</label>
                <select id="fpl-centro-modal" class="fpl-select-inp">
                    <option value="">— Nenhum —</option>
                    <?php foreach($centros as $cc): ?>
                    <option value="<?= $cc['id'] ?>"><?= htmlspecialchars($cc['codigo'].' — '.$cc['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="fpl-field">
                <label>Patrocinador</label>
                <select id="fpl-pat-modal" class="fpl-select-inp">
                    <option value="">— Nenhum —</option>
                    <?php foreach($patrocinadores as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="fpl-field">
            <label>Documento de Referência</label>
            <input type="text" id="fpl-docref" class="fpl-input" placeholder="Nº Factura, Recibo, Contrato...">
        </div>

        <div class="fpl-modal-footer">
            <button class="fpl-btn-save" id="fpl-save-btn" onclick="fplSalvar()">✅ Confirmar Lançamento</button>
            <button class="fpl-btn-cancel" onclick="document.getElementById('fpl-modal').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<script>
const FP_SID = <?= $stationId ?>;
let fplTipoActual = 'receita';
const tipoClasses = ['receita','despesa','transferencia','ajuste'];

function fplSelectTipo(tipo, btn) {
    fplTipoActual = tipo;
    document.getElementById('fpl-tipo-val').value = tipo;
    document.querySelectorAll('.fpl-tipo-btn').forEach(b => {
        b.className = 'fpl-tipo-btn';
    });
    btn.className = 'fpl-tipo-btn active-' + tipo;

    // Pré-seleccionar contas comuns
    const deb = document.getElementById('fpl-debito');
    const cred = document.getElementById('fpl-credito');
    if (tipo === 'receita') {
        fplSelectContaPorCodigo(deb, '1.1.3');
        fplSelectContaPorCodigo(cred, '3.1.1');
    } else if (tipo === 'despesa') {
        fplSelectContaPorCodigo(deb, '4.2.1');
        fplSelectContaPorCodigo(cred, '2.1.1');
    }
}

function fplSelectContaPorCodigo(sel, codigo) {
    for (const opt of sel.options) {
        if (opt.text.startsWith(codigo + ' ')) { sel.value = opt.value; break; }
    }
}

function fplSetTipo(tipo) {
    const url = new URL(window.location);
    if (tipo) url.searchParams.set('tipo', tipo);
    else url.searchParams.delete('tipo');
    url.searchParams.set('mes', document.getElementById('fpl-mes').value);
    window.location = url.toString();
}

function fplFiltrar() {
    const url = new URL(window.location);
    const mes = document.getElementById('fpl-mes').value;
    const centro = document.getElementById('fpl-centro').value;
    url.searchParams.set('mes', mes);
    if (centro) url.searchParams.set('centro_id', centro);
    else url.searchParams.delete('centro_id');
    window.location = url.toString();
}

function fplSalvar() {
    const btn = document.getElementById('fpl-save-btn');
    btn.textContent = '...'; btn.disabled = true;
    fetch('/public/financas/' + FP_SID + '/lancamentos/salvar', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            tipo:            fplTipoActual,
            historico:       document.getElementById('fpl-historico').value,
            data_lancamento: document.getElementById('fpl-data').value,
            valor:           document.getElementById('fpl-valor').value,
            conta_debito_id: document.getElementById('fpl-debito').value,
            conta_credito_id:document.getElementById('fpl-credito').value,
            centro_custo_id: document.getElementById('fpl-centro-modal').value,
            patrocinador_id: document.getElementById('fpl-pat-modal').value,
            documento_ref:   document.getElementById('fpl-docref').value,
        })
    }).then(r => r.json()).then(() => { location.reload(); });
}

function fplCancelar(id) {
    if (!confirm('Cancelar este lançamento?')) return;
    fetch('/public/financas/' + FP_SID + '/lancamentos/' + id + '/cancelar', {method:'POST'})
        .then(() => location.reload());
}

document.getElementById('fpl-modal').addEventListener('click', e => {
    if (e.target === document.getElementById('fpl-modal'))
        document.getElementById('fpl-modal').classList.remove('open');
});
</script>
