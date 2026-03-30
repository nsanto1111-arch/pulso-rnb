<?php
$patrocinadores = $patrocinadores ?? [];
$fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';
?>
<style>
.pat-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.pat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem}

/* Card */
.pat-card{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;padding:1.5rem;transition:border-color .2s,transform .2s;position:relative}
.pat-card:hover{border-color:rgba(16,185,129,.25);transform:translateY(-2px)}
.pat-card-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem}
.pat-avatar{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:900;color:#000;flex-shrink:0}
.pat-nome{font-size:15px;font-weight:800;color:var(--fp-text);margin-bottom:.25rem;margin-top:.25rem}
.pat-sector{font-size:11px;color:var(--fp-text3);margin-bottom:1rem}
.pat-contact{font-size:11px;color:var(--fp-text2);margin-bottom:1rem;display:flex;flex-direction:column;gap:3px}

.pat-stats{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1.25rem}
.pat-stat{background:var(--fp-bg3);border-radius:10px;padding:.75rem;text-align:center}
.pat-stat-val{font-size:13px;font-weight:800;color:var(--fp-text)}
.pat-stat-lbl{font-size:9px;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.5px;margin-top:2px}

/* Acções */
.pat-actions{display:flex;gap:.5rem;border-top:1px solid var(--fp-border);padding-top:1rem}
.pat-btn{flex:1;padding:.5rem;border-radius:8px;border:1px solid;font-size:11px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.375rem;transition:all .15s}
.pat-btn.edit{background:rgba(99,102,241,.06);border-color:rgba(99,102,241,.2);color:#6366f1}
.pat-btn.edit:hover{background:rgba(99,102,241,.15);border-color:rgba(99,102,241,.4)}
.pat-btn.del{background:rgba(239,68,68,.05);border-color:rgba(239,68,68,.15);color:#ef4444}
.pat-btn.del:hover{background:rgba(239,68,68,.14);border-color:rgba(239,68,68,.35)}

/* Modal */
.pat-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center}
.pat-modal-bg.open{display:flex}
.pat-modal{background:var(--fp-bg1);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2rem;width:90%;max-width:480px;max-height:90vh;overflow-y:auto}
.pat-modal-title{font-size:16px;font-weight:800;color:var(--fp-text)}
.pat-field{margin-bottom:1rem}
.pat-field label{display:block;font-size:10px;font-weight:700;color:var(--fp-text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem}
.pat-input{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none}
.pat-input:focus{border-color:rgba(16,185,129,.5)}
.pat-input::placeholder{color:var(--fp-text3)}
.pat-form2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.pat-footer{display:flex;gap:.75rem;margin-top:1.5rem}
.pat-save{flex:1;padding:.875rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:14px;font-weight:800;cursor:pointer;transition:opacity .15s}
.pat-save:disabled{opacity:.5}
.pat-cancel{flex:1;padding:.875rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text2);font-size:14px;cursor:pointer}

/* Confirm delete */
.pat-confirm{background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:1rem;margin-bottom:1.25rem}
.pat-confirm p{font-size:12px;color:#fca5a5;margin:0}
</style>

<!-- HEADER -->
<div class="pat-header">
    <div>
        <div style="font-size:24px;font-weight:900;color:var(--fp-text)">Patrocinadores</div>
        <div style="font-size:13px;color:var(--fp-text2);margin-top:4px">
            <?= count($patrocinadores) ?> parceiro<?= count($patrocinadores)!==1?'s':'' ?> registado<?= count($patrocinadores)!==1?'s':'' ?>
        </div>
    </div>
    <button onclick="patAbrir()" style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;cursor:pointer">
        <i class="bi bi-plus-lg"></i> Novo Patrocinador
    </button>
</div>

<!-- GRID -->
<?php if (!empty($patrocinadores)): ?>
<div class="pat-grid">
    <?php foreach($patrocinadores as $p):
        $ini   = mb_strtoupper(mb_substr($p['nome'], 0, 2));
        $ativo = $p['ativo'] ?? 1;
        $cores = ['#10b981','#00e5ff','#8b5cf6','#f59e0b','#3b82f6','#ef4444','#ec4899'];
        $cor   = $cores[crc32($p['nome']) % count($cores)];
    ?>
    <div class="pat-card">
        <div class="pat-card-top">
            <div class="pat-avatar" style="background:<?= $cor ?>22;color:<?= $cor ?>"><?= $ini ?></div>
            <span class="fp-status <?= $ativo ? 'green' : 'gray' ?>"><?= $ativo ? 'Activo' : 'Inactivo' ?></span>
        </div>

        <div class="pat-nome"><?= htmlspecialchars($p['nome']) ?></div>
        <div class="pat-sector"><?= htmlspecialchars($p['sector'] ?? 'Sem sector definido') ?></div>

        <?php if (!empty($p['telefone']) || !empty($p['email']) || !empty($p['contacto'])): ?>
        <div class="pat-contact">
            <?php if (!empty($p['contacto'])): ?><span>👤 <?= htmlspecialchars($p['contacto']) ?></span><?php endif; ?>
            <?php if (!empty($p['telefone'])): ?><span>📞 <?= htmlspecialchars($p['telefone']) ?></span><?php endif; ?>
            <?php if (!empty($p['email'])): ?><span>✉️ <?= htmlspecialchars($p['email']) ?></span><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="pat-stats">
            <div class="pat-stat">
                <div class="pat-stat-val" style="color:var(--fp-green)"><?= $fmtKz($p['total_recebido'] ?? 0) ?></div>
                <div class="pat-stat-lbl">Recebido</div>
            </div>
            <div class="pat-stat">
                <div class="pat-stat-val"><?= (int)($p['total_contratos'] ?? 0) ?></div>
                <div class="pat-stat-lbl">Contratos</div>
            </div>
        </div>

        <div class="pat-actions">
            <button class="pat-btn edit" onclick='patEditar(<?= htmlspecialchars(json_encode($p)) ?>)'>
                <i class="bi bi-pencil-square"></i> Editar
            </button>
            <button class="pat-btn del" onclick="patExcluir(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nome'])) ?>')">
                <i class="bi bi-trash3"></i> Remover
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<div style="text-align:center;padding:5rem 2rem;color:var(--fp-text3)">
    <div style="font-size:60px;margin-bottom:1rem;opacity:.15">🏢</div>
    <div style="font-size:16px;font-weight:600;color:var(--fp-text2);margin-bottom:.5rem">Nenhum patrocinador ainda</div>
    <div style="font-size:13px;margin-bottom:1.5rem">Adiciona o teu primeiro parceiro comercial</div>
    <button onclick="patAbrir()" style="padding:.75rem 1.5rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;cursor:pointer">
        + Adicionar Patrocinador
    </button>
</div>
<?php endif; ?>

<!-- MODAL CRIAR/EDITAR -->
<div class="pat-modal-bg" id="pat-modal">
    <div class="pat-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div class="pat-modal-title" id="pat-modal-titulo">🏢 Novo Patrocinador</div>
            <button onclick="patFechar()" style="background:var(--fp-bg3);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <input type="hidden" id="pat-id" value="">
        <div class="pat-field">
            <label>Nome *</label>
            <input type="text" id="pat-nome" class="pat-input" placeholder="Ex: TPA, Multichoice Angola...">
        </div>
        <div class="pat-form2">
            <div class="pat-field">
                <label>Sector</label>
                <input type="text" id="pat-sector" class="pat-input" placeholder="Ex: Telecomunicações">
            </div>
            <div class="pat-field">
                <label>Telefone</label>
                <input type="text" id="pat-telefone" class="pat-input" placeholder="9XX XXX XXX">
            </div>
        </div>
        <div class="pat-form2">
            <div class="pat-field">
                <label>Email</label>
                <input type="email" id="pat-email" class="pat-input" placeholder="contacto@empresa.ao">
            </div>
            <div class="pat-field">
                <label>Contacto</label>
                <input type="text" id="pat-contacto" class="pat-input" placeholder="Nome do responsável">
            </div>
        </div>
        <div class="pat-footer">
            <button class="pat-save" id="pat-save-btn" onclick="patGuardar()">✅ Guardar</button>
            <button class="pat-cancel" onclick="patFechar()">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL CONFIRMAR EXCLUSÃO -->
<div class="pat-modal-bg" id="pat-modal-del">
    <div class="pat-modal" style="max-width:400px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div class="pat-modal-title">🗑️ Remover Patrocinador</div>
            <button onclick="document.getElementById('pat-modal-del').classList.remove('open')"
                    style="background:var(--fp-bg3);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <input type="hidden" id="pat-del-id">
        <div class="pat-confirm">
            <p>Vais remover <strong id="pat-del-nome" style="color:#fff"></strong>. Esta acção marca o patrocinador como inactivo e não elimina os dados históricos.</p>
        </div>
        <div class="pat-footer">
            <button class="pat-save" style="background:#ef4444" onclick="patConfirmarExclusao()">🗑️ Confirmar Remoção</button>
            <button class="pat-cancel" onclick="document.getElementById('pat-modal-del').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<script>
const PAT_SID = <?= $stationId ?>;

function patAbrir() {
    document.getElementById('pat-id').value      = '';
    document.getElementById('pat-nome').value    = '';
    document.getElementById('pat-sector').value  = '';
    document.getElementById('pat-telefone').value= '';
    document.getElementById('pat-email').value   = '';
    document.getElementById('pat-contacto').value= '';
    document.getElementById('pat-modal-titulo').textContent = '🏢 Novo Patrocinador';
    document.getElementById('pat-modal').classList.add('open');
    document.getElementById('pat-nome').focus();
}

function patEditar(p) {
    document.getElementById('pat-id').value      = p.id;
    document.getElementById('pat-nome').value    = p.nome    || '';
    document.getElementById('pat-sector').value  = p.sector  || '';
    document.getElementById('pat-telefone').value= p.telefone|| '';
    document.getElementById('pat-email').value   = p.email   || '';
    document.getElementById('pat-contacto').value= p.contacto|| '';
    document.getElementById('pat-modal-titulo').textContent = '✏️ Editar Patrocinador';
    document.getElementById('pat-modal').classList.add('open');
}

function patFechar() {
    document.getElementById('pat-modal').classList.remove('open');
}

function patGuardar() {
    const nome = document.getElementById('pat-nome').value.trim();
    if (!nome) { document.getElementById('pat-nome').focus(); return; }

    const btn = document.getElementById('pat-save-btn');
    btn.textContent = '...'; btn.disabled = true;

    const id  = document.getElementById('pat-id').value;
    const url = id
        ? '/public/financas/' + PAT_SID + '/patrocinadores/' + id + '/editar'
        : '/public/financas/' + PAT_SID + '/patrocinadores/salvar';

    fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            nome:      nome,
            sector:    document.getElementById('pat-sector').value,
            telefone:  document.getElementById('pat-telefone').value,
            email:     document.getElementById('pat-email').value,
            contacto:  document.getElementById('pat-contacto').value,
        })
    }).then(r => r.json()).then(d => {
        if (d.sucesso) location.reload();
        else { btn.textContent = '✅ Guardar'; btn.disabled = false; }
    }).catch(() => { btn.textContent = '✅ Guardar'; btn.disabled = false; });
}

function patExcluir(id, nome) {
    document.getElementById('pat-del-id').value = id;
    document.getElementById('pat-del-nome').textContent = nome;
    document.getElementById('pat-modal-del').classList.add('open');
}

function patConfirmarExclusao() {
    const id  = document.getElementById('pat-del-id').value;
    fetch('/public/financas/' + PAT_SID + '/patrocinadores/' + id + '/excluir', {method:'POST'})
        .then(r => r.json()).then(() => location.reload());
}

document.querySelectorAll('.pat-modal-bg').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
});
</script>
