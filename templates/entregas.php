<?php
$entregas = $dados['entregas'] ?? [];
$stats    = $dados['stats'] ?? [];
$estadoActual = $dados['estado'] ?? '';

$estadoInfo = [
    'reservado'  => ['cor'=>'#71717a','bg'=>'rgba(113,113,122,0.1)','icon'=>'📦','label'=>'Reservado'],
    'notificado' => ['cor'=>'#3b82f6','bg'=>'rgba(59,130,246,0.1)', 'icon'=>'📱','label'=>'Notificado'],
    'confirmado' => ['cor'=>'#f59e0b','bg'=>'rgba(245,158,11,0.1)', 'icon'=>'✅','label'=>'Confirmado'],
    'entregue'   => ['cor'=>'#10b981','bg'=>'rgba(16,185,129,0.1)', 'icon'=>'🎁','label'=>'Entregue'],
    'devolvido'  => ['cor'=>'#ef4444','bg'=>'rgba(239,68,68,0.1)',  'icon'=>'↩️','label'=>'Devolvido'],
    'cancelado'  => ['cor'=>'#52525b','bg'=>'rgba(82,82,91,0.1)',   'icon'=>'❌','label'=>'Cancelado'],
];
?>
<style>
.et-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.et-filtros{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.et-filtro{padding:.5rem 1rem;border:1px solid rgba(255,255,255,0.08);border-radius:8px;background:transparent;color:#71717a;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s}
.et-filtro:hover{color:#a1a1aa;text-decoration:none}
.et-filtro.active{background:rgba(0,229,255,0.1);border-color:rgba(0,229,255,0.3);color:#00e5ff}
.et-kpis{display:grid;grid-template-columns:repeat(6,1fr);gap:.75rem;margin-bottom:1.5rem}
.et-kpi{padding:1rem;text-align:center;border-radius:10px;border:1px solid}
.et-kpi-val{font-size:24px;font-weight:900;line-height:1;margin-bottom:.25rem}
.et-kpi-lbl{font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.et-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden}
.et-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.et-table{width:100%;border-collapse:collapse;font-size:13px}
.et-table th{padding:.75rem 1.25rem;text-align:left;font-size:11px;font-weight:700;color:#71717a;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid rgba(255,255,255,0.06)}
.et-table td{padding:.875rem 1.25rem;border-bottom:1px solid rgba(255,255,255,0.04);vertical-align:middle}
.et-table tr:last-child td{border-bottom:none}
.et-table tr:hover td{background:rgba(255,255,255,0.02)}
.et-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
.et-btn{display:inline-flex;align-items:center;gap:.375rem;padding:.375rem .75rem;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;border:1px solid;transition:all .2s;background:transparent}
.et-empty{text-align:center;padding:3rem;color:#52525b}

/* MODAL */
.et-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center}
.et-modal.show{display:flex}
.et-modal-box{background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:2rem;width:90%;max-width:440px}
.et-modal-title{font-size:16px;font-weight:700;color:#fff;margin-bottom:1.25rem}
.et-form-group{margin-bottom:1rem}
.et-form-label{font-size:12px;font-weight:700;color:#a1a1aa;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:.5rem}
.et-form-input{width:100%;padding:.75rem 1rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:13px;outline:none}
.et-form-input:focus{border-color:rgba(0,229,255,0.4)}
.et-form-select{width:100%;padding:.75rem 1rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:13px;outline:none}
.et-modal-actions{display:flex;gap:.75rem;margin-top:1.25rem}
.et-btn-save{flex:1;padding:.75rem;background:rgba(0,229,255,0.15);border:1px solid rgba(0,229,255,0.4);color:#00e5ff;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer}
.et-btn-cancel{flex:1;padding:.75rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);color:#a1a1aa;border-radius:8px;font-size:14px;cursor:pointer}
@media(max-width:900px){.et-kpis{grid-template-columns:repeat(3,1fr)}}
</style>

<div class="et-header">
    <div>
        <div style="font-size:22px;font-weight:800;color:#fff">🚚 Entregas de Prémios</div>
        <div style="font-size:13px;color:#71717a;margin-top:3px">Controlo de estado das entregas</div>
    </div>
    <a href="/public/pulso/<?= $stationId ?>/premios"
       style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.125rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#a1a1aa;text-decoration:none;font-size:13px;font-weight:600">
        <i class="bi bi-box-seam"></i> Ver Estoque
    </a>
</div>

<!-- KPIs -->
<div class="et-kpis">
    <?php foreach($estadoInfo as $est => $info): ?>
    <div class="et-kpi" style="background:<?= $info['bg'] ?>;border-color:<?= $info['cor'] ?>30">
        <div class="et-kpi-val" style="color:<?= $info['cor'] ?>"><?= $stats[$est] ?? 0 ?></div>
        <div class="et-kpi-lbl" style="color:<?= $info['cor'] ?>"><?= $info['label'] ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- FILTROS -->
<div class="et-filtros">
    <a href="?estado=" class="et-filtro <?= $estadoActual===''?'active':'' ?>">Todas</a>
    <?php foreach($estadoInfo as $est => $info): ?>
    <a href="?estado=<?= $est ?>" class="et-filtro <?= $estadoActual===$est?'active':'' ?>">
        <?= $info['icon'] ?> <?= $info['label'] ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- TABELA -->
<div class="et-card">
    <div class="et-card-head">
        <span>📋 Lista de Entregas</span>
        <span style="font-size:12px;color:#71717a"><?= count($entregas) ?> registo<?= count($entregas)!==1?'s':'' ?></span>
    </div>
    <?php if (!empty($entregas)): ?>
    <div style="overflow-x:auto">
    <table class="et-table">
        <thead>
            <tr>
                <th>Ouvinte</th>
                <th>Prémio</th>
                <th>Promoção</th>
                <th>Estado</th>
                <th>Data Reserva</th>
                <th>Acções</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($entregas as $e):
            $est  = $e['estado'] ?? 'reservado';
            $info = $estadoInfo[$est] ?? $estadoInfo['reservado'];
        ?>
        <tr>
            <td>
                <div style="font-weight:700;color:#fff"><?= htmlspecialchars($e['ouvinte_nome'] ?? '') ?></div>
                <div style="font-size:11px;color:#71717a"><?= htmlspecialchars($e['telefone'] ?? '') ?></div>
            </td>
            <td style="color:#f59e0b;font-weight:600"><?= htmlspecialchars($e['premio_nome'] ?? '') ?></td>
            <td style="color:#71717a;font-size:12px"><?= htmlspecialchars($e['promocao_nome'] ?? '—') ?></td>
            <td>
                <span class="et-badge" style="background:<?= $info['bg'] ?>;color:<?= $info['cor'] ?>;border:1px solid <?= $info['cor'] ?>30">
                    <?= $info['icon'] ?> <?= $info['label'] ?>
                </span>
            </td>
            <td style="color:#71717a;font-size:12px">
                <?= date('d/m/Y H:i', strtotime($e['data_reserva'] ?? 'now')) ?>
            </td>
            <td>
                <?php if ($est !== 'entregue' && $est !== 'cancelado'): ?>
                <button class="et-btn" style="color:#00e5ff;border-color:rgba(0,229,255,0.25)"
                        onclick="abrirModal(<?= $e['id'] ?>, '<?= $est ?>', '<?= htmlspecialchars($e['ouvinte_nome']) ?>')">
                    <i class="bi bi-arrow-right-circle"></i> Actualizar
                </button>
                <?php else: ?>
                <span style="font-size:12px;color:#52525b">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div class="et-empty">
        <div style="font-size:48px;margin-bottom:1rem;opacity:.3">🚚</div>
        <div style="font-size:15px;font-weight:600;color:#a1a1aa;margin-bottom:.5rem">Nenhuma entrega registada</div>
        <div style="font-size:13px">As entregas aparecem automaticamente após um sorteio</div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL ACTUALIZAR ESTADO -->
<div class="et-modal" id="etModal">
    <div class="et-modal-box">
        <div class="et-modal-title">📦 Actualizar Estado da Entrega</div>
        <div style="font-size:13px;color:#a1a1aa;margin-bottom:1.25rem">
            Ouvinte: <strong style="color:#fff" id="modalOuvinte"></strong>
        </div>
        <div class="et-form-group">
            <label class="et-form-label">Novo Estado</label>
            <select class="et-form-select" id="novoEstado">
                <option value="notificado">📱 Notificado</option>
                <option value="confirmado">✅ Confirmado</option>
                <option value="entregue">🎁 Entregue</option>
                <option value="devolvido">↩️ Devolvido</option>
                <option value="cancelado">❌ Cancelado</option>
            </select>
        </div>
        <div class="et-form-group" id="campoEntregue" style="display:none">
            <label class="et-form-label">Entregue por</label>
            <input type="text" class="et-form-input" id="entregue_por" placeholder="Nome do responsável">
        </div>
        <div class="et-form-group" id="campoDocumento" style="display:none">
            <label class="et-form-label">Nº BI / Documento</label>
            <input type="text" class="et-form-input" id="documento_id" placeholder="Número do documento">
        </div>
        <div class="et-form-group">
            <label class="et-form-label">Notas</label>
            <input type="text" class="et-form-input" id="notasInput" placeholder="Observações...">
        </div>
        <div class="et-modal-actions">
            <button class="et-btn-save" onclick="confirmarEstado()">✅ Confirmar</button>
            <button class="et-btn-cancel" onclick="fecharModal()">Cancelar</button>
        </div>
    </div>
</div>

<script>
let entregaId = null;
const stationId = <?= $stationId ?>;

function abrirModal(id, estadoActual, nome) {
    entregaId = id;
    document.getElementById('modalOuvinte').textContent = nome;
    document.getElementById('etModal').classList.add('show');

    // Próximo estado sugerido
    const proximos = {
        'reservado': 'notificado', 'notificado': 'confirmado',
        'confirmado': 'entregue', default: 'entregue'
    };
    const select = document.getElementById('novoEstado');
    select.value = proximos[estadoActual] || 'entregue';
    toggleCamposExtras();
}

function fecharModal() {
    document.getElementById('etModal').classList.remove('show');
    entregaId = null;
}

function toggleCamposExtras() {
    const estado = document.getElementById('novoEstado').value;
    document.getElementById('campoEntregue').style.display  = estado === 'entregue' ? 'block' : 'none';
    document.getElementById('campoDocumento').style.display = estado === 'entregue' ? 'block' : 'none';
}
document.getElementById('novoEstado').addEventListener('change', toggleCamposExtras);

function confirmarEstado() {
    if (!entregaId) return;
    const btn = document.querySelector('.et-btn-save');
    btn.textContent = '...';
    btn.disabled = true;

    fetch(`/public/pulso/${stationId}/entregas/${entregaId}/estado`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            estado:      document.getElementById('novoEstado').value,
            notas:       document.getElementById('notasInput').value,
            entregue_por:document.getElementById('entregue_por').value,
            documento_id:document.getElementById('documento_id').value,
        })
    })
    .then(r => r.json())
    .then(() => { fecharModal(); window.location.reload(); })
    .catch(() => { btn.textContent = '✅ Confirmar'; btn.disabled = false; });
}

document.getElementById('etModal').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>
