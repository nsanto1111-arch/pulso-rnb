<?php
$contratos      = $contratos      ?? [];
$patrocinadores = $patrocinadores ?? [];
$fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';

$tipoLabel   = ['spot'=>'Spot','jingle'=>'Jingle','patrocinio'=>'Patrocínio','evento'=>'Evento','parceria'=>'Parceria','outro'=>'Outro'];
$tipoIcon    = ['spot'=>'📺','jingle'=>'🎵','patrocinio'=>'🏆','evento'=>'🎪','parceria'=>'🤝','outro'=>'📋'];
$estadoCor   = ['negociacao'=>'gold','activo'=>'green','concluido'=>'gray','cancelado'=>'red'];
$estadoLabel = ['negociacao'=>'Negociação','activo'=>'Activo','concluido'=>'Concluído','cancelado'=>'Cancelado'];
?>
<style>
.ct-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}

/* Tabela */
.ct-wrap{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden}
.ct-table{width:100%;border-collapse:collapse}
.ct-table thead th{padding:.75rem 1.25rem;font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px;border-bottom:1px solid var(--fp-border);background:var(--fp-bg3);text-align:left;white-space:nowrap}
.ct-table thead th.r{text-align:right}
.ct-table tbody tr{border-bottom:1px solid rgba(255,255,255,.03);transition:background .12s}
.ct-table tbody tr:last-child{border-bottom:none}
.ct-table tbody tr:hover{background:rgba(255,255,255,.02)}
.ct-table td{padding:.875rem 1.25rem;vertical-align:middle}
.ct-tipo-pill{display:inline-flex;align-items:center;gap:.25rem;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);color:var(--fp-text2)}

/* Acções */
.ct-actions{display:flex;gap:4px;opacity:0;transition:opacity .15s}
.ct-table tbody tr:hover .ct-actions{opacity:1}
.ct-action-btn{width:28px;height:28px;border-radius:7px;border:1px solid;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px;transition:all .15s;position:relative}
.ct-action-btn.edit{background:rgba(99,102,241,.07);border-color:rgba(99,102,241,.2);color:#6366f1}
.ct-action-btn.edit:hover{background:rgba(99,102,241,.18);transform:translateY(-1px)}
.ct-action-btn.del{background:rgba(239,68,68,.06);border-color:rgba(239,68,68,.15);color:#ef4444}
.ct-action-btn.del:hover{background:rgba(239,68,68,.15);transform:translateY(-1px)}
.ct-action-btn::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 5px);left:50%;transform:translateX(-50%);background:#1e2535;border:1px solid rgba(255,255,255,.1);color:#f0f4ff;font-size:10px;font-weight:600;white-space:nowrap;padding:3px 8px;border-radius:5px;pointer-events:none;opacity:0;transition:opacity .15s;z-index:10}
.ct-action-btn:hover::after{opacity:1}

/* Modal */
.ct-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center}
.ct-modal-bg.open{display:flex}
.ct-modal{background:var(--fp-bg1);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2rem;width:90%;max-width:520px;max-height:90vh;overflow-y:auto}
.ct-field{margin-bottom:1rem}
.ct-field label{display:block;font-size:10px;font-weight:700;color:var(--fp-text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem}
.ct-input{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none;color-scheme:dark}
.ct-input:focus{border-color:rgba(16,185,129,.5)}
.ct-input::placeholder{color:var(--fp-text3)}
.ct-select{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none}
.ct-form2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.ct-footer{display:flex;gap:.75rem;margin-top:1.5rem}
.ct-save{flex:1;padding:.875rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:14px;font-weight:800;cursor:pointer}
.ct-save:disabled{opacity:.5}
.ct-dismiss{flex:1;padding:.875rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text2);font-size:14px;cursor:pointer}

/* Confirm */
.ct-confirm{background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:.875rem;margin-bottom:1.25rem;font-size:12px;color:#fca5a5}
</style>

<!-- HEADER -->
<div class="ct-header">
    <div>
        <div style="font-size:24px;font-weight:900;color:var(--fp-text)">Contratos</div>
        <div style="font-size:13px;color:var(--fp-text2);margin-top:4px"><?= count($contratos) ?> contrato<?= count($contratos)!==1?'s':'' ?></div>
    </div>
    <button onclick="ctAbrir()" style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;cursor:pointer">
        <i class="bi bi-plus-lg"></i> Novo Contrato
    </button>
</div>

<!-- TABELA -->
<div class="ct-wrap">
    <?php if (!empty($contratos)): ?>
    <table class="ct-table">
        <thead>
            <tr>
                <th>Patrocinador</th>
                <th>Contrato</th>
                <th>Tipo</th>
                <th class="r">Valor</th>
                <th class="r">Recebido</th>
                <th>Período</th>
                <th>Estado</th>
                <th style="width:72px"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($contratos as $c):
            $estado  = $c['estado'] ?? 'negociacao';
            $pct     = $c['valor_total'] > 0 ? min(100, round($c['total_recebido'] / $c['valor_total'] * 100)) : 0;
            $tipo    = $c['tipo'] ?? 'outro';
        ?>
        <tr>
            <td style="font-weight:700;color:var(--fp-text);font-size:13px"><?= htmlspecialchars($c['patrocinador_nome'] ?? '—') ?></td>
            <td style="color:var(--fp-text2);font-size:12px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($c['nome']) ?></td>
            <td>
                <span class="ct-tipo-pill"><?= $tipoIcon[$tipo] ?? '📋' ?> <?= $tipoLabel[$tipo] ?? 'Outro' ?></span>
            </td>
            <td style="text-align:right;font-weight:800;color:var(--fp-text);font-size:13px"><?= $fmtKz($c['valor_total']) ?></td>
            <td style="text-align:right">
                <div style="font-size:12px;font-weight:700;color:var(--fp-green)"><?= $fmtKz($c['total_recebido']) ?></div>
                <div style="height:3px;background:rgba(255,255,255,.06);border-radius:2px;margin-top:4px;width:80px;margin-left:auto">
                    <div style="width:<?= $pct ?>%;height:100%;background:var(--fp-green);border-radius:2px"></div>
                </div>
            </td>
            <td style="font-size:11px;color:var(--fp-text2);white-space:nowrap">
                <?= $c['data_inicio'] ? date('d/m/y', strtotime($c['data_inicio'])) : '—' ?>
                <?= $c['data_fim'] ? ' → ' . date('d/m/y', strtotime($c['data_fim'])) : '' ?>
            </td>
            <td><span class="fp-status <?= $estadoCor[$estado] ?? 'gray' ?>"><?= $estadoLabel[$estado] ?? $estado ?></span></td>
            <td>
                <div class="ct-actions">
                    <button class="ct-action-btn edit" data-tip="Editar"
                            onclick='ctEditar(<?= htmlspecialchars(json_encode($c)) ?>)'>
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="ct-action-btn del" data-tip="Excluir"
                            onclick="ctExcluir(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nome'])) ?>')">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="padding:3rem;text-align:center;color:var(--fp-text3)">
        <div style="font-size:48px;opacity:.15;margin-bottom:.875rem">📄</div>
        <div style="font-size:14px;font-weight:600;color:var(--fp-text2);margin-bottom:.5rem">Nenhum contrato registado</div>
        <div style="font-size:12px;margin-bottom:1.25rem">Cria o primeiro contrato com um patrocinador</div>
        <button onclick="ctAbrir()" style="padding:.625rem 1.25rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;cursor:pointer">+ Criar Contrato</button>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL CRIAR/EDITAR -->
<div class="ct-modal-bg" id="ct-modal">
    <div class="ct-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)" id="ct-modal-titulo">📄 Novo Contrato</div>
            <button onclick="ctFechar()" style="background:var(--fp-bg3);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <input type="hidden" id="ct-id">

        <div class="ct-field">
            <label>Patrocinador *</label>
            <select id="ct-pat" class="ct-select">
                <option value="">— Seleccionar —</option>
                <?php foreach($patrocinadores as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="ct-field">
            <label>Nome do Contrato *</label>
            <input type="text" id="ct-nome" class="ct-input" placeholder="Ex: Campanha Verão 2026">
        </div>

        <div class="ct-form2">
            <div class="ct-field">
                <label>Tipo</label>
                <select id="ct-tipo" class="ct-select">
                    <option value="spot">📺 Spot</option>
                    <option value="jingle">🎵 Jingle</option>
                    <option value="patrocinio">🏆 Patrocínio</option>
                    <option value="evento">🎪 Evento</option>
                    <option value="parceria">🤝 Parceria</option>
                    <option value="outro">📋 Outro</option>
                </select>
            </div>
            <div class="ct-field">
                <label>Valor Total (Kz)</label>
                <input type="text" id="ct-valor" class="ct-input" placeholder="Ex: 300.000,00">
            </div>
        </div>

        <div class="ct-form2">
            <div class="ct-field">
                <label>Data Início</label>
                <input type="date" id="ct-inicio" class="ct-input" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="ct-field">
                <label>Data Fim</label>
                <input type="date" id="ct-fim" class="ct-input">
            </div>
        </div>

        <div class="ct-form2">
            <div class="ct-field">
                <label>Spots por Dia</label>
                <input type="number" id="ct-spots" class="ct-input" value="0" min="0">
            </div>
            <div class="ct-field">
                <label>Estado</label>
                <select id="ct-estado" class="ct-select">
                    <option value="negociacao">Negociação</option>
                    <option value="activo">Activo</option>
                    <option value="concluido">Concluído</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
        </div>

        <div class="ct-footer">
            <button class="ct-save" id="ct-save-btn" onclick="ctGuardar()">✅ Guardar Contrato</button>
            <button class="ct-dismiss" onclick="ctFechar()">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL EXCLUIR -->
<div class="ct-modal-bg" id="ct-modal-del">
    <div class="ct-modal" style="max-width:400px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)">🗑️ Excluir Contrato</div>
            <button onclick="document.getElementById('ct-modal-del').classList.remove('open')"
                    style="background:var(--fp-bg3);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <input type="hidden" id="ct-del-id">
        <div class="ct-confirm">
            Vais excluir o contrato <strong id="ct-del-nome" style="color:#fff"></strong>. Esta acção não pode ser revertida.
        </div>
        <div class="ct-footer">
            <button class="ct-save" style="background:#ef4444" onclick="ctConfirmarExclusao()">🗑️ Confirmar</button>
            <button class="ct-dismiss" onclick="document.getElementById('ct-modal-del').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<script>
const CT_SID = <?= $stationId ?>;

function ctAbrir() {
    document.getElementById('ct-id').value    = '';
    document.getElementById('ct-pat').value   = '';
    document.getElementById('ct-nome').value  = '';
    document.getElementById('ct-tipo').value  = 'spot';
    document.getElementById('ct-valor').value = '';
    document.getElementById('ct-inicio').value= '<?= date('Y-m-d') ?>';
    document.getElementById('ct-fim').value   = '';
    document.getElementById('ct-spots').value = '0';
    document.getElementById('ct-estado').value= 'negociacao';
    document.getElementById('ct-modal-titulo').textContent = '📄 Novo Contrato';
    document.getElementById('ct-modal').classList.add('open');
}

function ctEditar(c) {
    document.getElementById('ct-id').value    = c.id;
    document.getElementById('ct-pat').value   = c.patrocinador_id || '';
    document.getElementById('ct-nome').value  = c.nome            || '';
    document.getElementById('ct-tipo').value  = c.tipo            || 'spot';
    document.getElementById('ct-valor').value = parseFloat(c.valor_total||0).toFixed(2).replace('.',',');
    document.getElementById('ct-inicio').value= c.data_inicio     || '';
    document.getElementById('ct-fim').value   = c.data_fim        || '';
    document.getElementById('ct-spots').value = c.spots_por_dia   || '0';
    document.getElementById('ct-estado').value= c.estado          || 'negociacao';
    document.getElementById('ct-modal-titulo').textContent = '✏️ Editar Contrato';
    document.getElementById('ct-modal').classList.add('open');
}

function ctFechar() {
    document.getElementById('ct-modal').classList.remove('open');
}

function ctGuardar() {
    if (!document.getElementById('ct-pat').value || !document.getElementById('ct-nome').value.trim()) return;
    const btn = document.getElementById('ct-save-btn');
    btn.textContent = '...'; btn.disabled = true;
    const id  = document.getElementById('ct-id').value;
    const url = id
        ? '/public/financas/' + CT_SID + '/contratos/' + id + '/editar'
        : '/public/financas/' + CT_SID + '/contratos/salvar';

    fetch(url, {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            patrocinador_id: document.getElementById('ct-pat').value,
            nome:            document.getElementById('ct-nome').value,
            tipo:            document.getElementById('ct-tipo').value,
            valor_total:     document.getElementById('ct-valor').value,
            data_inicio:     document.getElementById('ct-inicio').value,
            data_fim:        document.getElementById('ct-fim').value,
            spots_por_dia:   document.getElementById('ct-spots').value,
            estado:          document.getElementById('ct-estado').value,
        })
    }).then(r=>r.json()).then(()=>location.reload())
      .catch(()=>{ btn.textContent='✅ Guardar Contrato'; btn.disabled=false; });
}

function ctExcluir(id, nome) {
    document.getElementById('ct-del-id').value = id;
    document.getElementById('ct-del-nome').textContent = nome;
    document.getElementById('ct-modal-del').classList.add('open');
}

function ctConfirmarExclusao() {
    const id = document.getElementById('ct-del-id').value;
    fetch('/public/financas/' + CT_SID + '/contratos/' + id + '/excluir', {method:'POST'})
        .then(r=>r.json()).then(()=>location.reload());
}

document.querySelectorAll('.ct-modal-bg').forEach(m => {
    m.addEventListener('click', e => { if (e.target===m) m.classList.remove('open'); });
});
</script>
