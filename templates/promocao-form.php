<?php
$isEdit = !empty($promocao['id']);
$id     = $promocao['id'] ?? '';
$action = $isEdit
    ? "/public/pulso/{$stationId}/promocoes/{$id}/editar"
    : "/public/pulso/{$stationId}/promocoes/nova";

$dataInicio = $promocao['data_inicio'] ?? date('Y-m-d') . 'T' . date('H:i');
$dataFim    = $promocao['data_fim'] ?? date('Y-m-d', strtotime('+7 days')) . 'T' . date('H:i');
if (str_contains($dataInicio, ' ')) $dataInicio = str_replace(' ', 'T', substr($dataInicio, 0, 16));
if (str_contains($dataFim, ' '))   $dataFim    = str_replace(' ', 'T', substr($dataFim, 0, 16));

// Activar directamente se vier do botão Activar
$estadoDefault = isset($_GET['activar']) ? 'activa' : ($promocao['estado'] ?? 'rascunho');
?>
<style>
.pf-back{display:inline-flex;align-items:center;gap:.5rem;color:#71717a;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:1.25rem;transition:color .2s}
.pf-back:hover{color:#fff;text-decoration:none}
.pf-header{margin-bottom:1.5rem}
.pf-title{font-size:22px;font-weight:800;color:#fff}
.pf-subtitle{font-size:13px;color:#71717a;margin-top:3px}
.pf-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.pf-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;gap:.625rem}
.pf-card-head i{color:#00e5ff}
.pf-card-body{padding:1.5rem}
.pf-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.pf-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem}
.pf-group{display:flex;flex-direction:column;gap:.5rem;margin-bottom:.875rem}
.pf-group:last-child{margin-bottom:0}
.pf-label{font-size:12px;font-weight:700;color:#71717a;text-transform:uppercase;letter-spacing:.5px}
.pf-label span{color:#ef4444;margin-left:2px}
.pf-input,.pf-select,.pf-textarea{width:100%;padding:.75rem 1rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#fff;font-size:14px;font-family:inherit;transition:border-color .2s;outline:none}
.pf-input:focus,.pf-select:focus,.pf-textarea:focus{border-color:rgba(0,229,255,0.4);background:rgba(0,229,255,0.04)}
.pf-input::placeholder{color:#52525b}
.pf-select option{background:#1a1a2e}
.pf-textarea{resize:vertical;min-height:90px;line-height:1.6}
.pf-hint{font-size:11px;color:#52525b;margin-top:.25rem}
.pf-estado-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.625rem;margin-top:.5rem}
.pf-estado-opt{padding:.75rem;border:1px solid rgba(255,255,255,0.08);border-radius:8px;cursor:pointer;transition:all .2s;text-align:center}
.pf-estado-opt input{display:none}
.pf-estado-opt-label{font-size:12px;font-weight:600;color:#71717a;display:block;margin-top:.375rem}
.pf-estado-rascunho.selected{border-color:#71717a;background:rgba(113,113,122,0.12);color:#a1a1aa}
.pf-estado-activa.selected{border-color:#10b981;background:rgba(16,185,129,0.12)}
.pf-estado-activa.selected .pf-estado-opt-label{color:#10b981}
.pf-estado-encerrada.selected{border-color:#ef4444;background:rgba(239,68,68,0.12)}
.pf-estado-encerrada.selected .pf-estado-opt-label{color:#ef4444}
.pf-estado-cancelada.selected{border-color:#6b7280;background:rgba(107,114,128,0.12)}
.pf-estado-cancelada.selected .pf-estado-opt-label{color:#6b7280}
.pf-actions{display:flex;gap:.875rem;margin-top:1.75rem;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,0.07)}
.pf-btn-save{display:inline-flex;align-items:center;gap:.625rem;padding:.875rem 2rem;background:linear-gradient(135deg,#00e5ff,#0891b2);color:#000;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:opacity .2s}
.pf-btn-save:hover{opacity:.9}
.pf-btn-cancel{display:inline-flex;align-items:center;gap:.625rem;padding:.875rem 1.5rem;background:rgba(255,255,255,0.04);color:#a1a1aa;border:1px solid rgba(255,255,255,0.1);border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;transition:all .2s}
.pf-btn-cancel:hover{background:rgba(255,255,255,0.07);color:#fff;text-decoration:none}
.pf-info-box{padding:1rem 1.25rem;background:rgba(0,229,255,0.04);border:1px solid rgba(0,229,255,0.12);border-radius:10px;font-size:12px;color:#a1a1aa;line-height:1.6;margin-bottom:1.25rem}
@media(max-width:768px){.pf-grid,.pf-grid-3,.pf-estado-grid{grid-template-columns:1fr}}
</style>

<a href="/public/pulso/<?= $stationId ?>/promocoes" class="pf-back">
    <i class="bi bi-arrow-left"></i> Voltar às Promoções
</a>

<div class="pf-header">
    <div class="pf-title"><?= $isEdit ? '✏️ Editar Promoção' : '🎁 Nova Promoção' ?></div>
    <div class="pf-subtitle"><?= $isEdit ? 'Actualizar detalhes da promoção' : 'Crie uma nova promoção para a sua audiência' ?></div>
</div>

<form method="POST" action="<?= $action ?>">

    <!-- INFORMAÇÕES BÁSICAS -->
    <div class="pf-card">
        <div class="pf-card-head"><i class="bi bi-info-circle"></i> Informações Básicas</div>
        <div class="pf-card-body">
            <div class="pf-grid">
                <div class="pf-group">
                    <div class="pf-label">Nome da Promoção <span>*</span></div>
                    <input type="text" name="nome" required class="pf-input"
                           value="<?= htmlspecialchars($promocao['nome'] ?? '') ?>"
                           placeholder="Ex: Sorteio de Fim de Semana">
                </div>
                <div class="pf-group">
                    <div class="pf-label">Prémio <span>*</span></div>
                    <input type="text" name="premio" required class="pf-input"
                           value="<?= htmlspecialchars($promocao['premio'] ?? '') ?>"
                           placeholder="Ex: 2 Bilhetes de Cinema, Vale de 5000 Kz">
                </div>
            </div>
            <div class="pf-group">
                <div class="pf-label">Descrição / Regras</div>
                <textarea name="descricao" class="pf-textarea"
                    placeholder="Descreva as regras e detalhes da promoção..."><?= htmlspecialchars($promocao['descricao'] ?? '') ?></textarea>
                <div class="pf-hint">Visível para a equipa — descreva como participar e as regras</div>
            </div>
        </div>
    </div>

    <!-- PERÍODO -->
    <div class="pf-card">
        <div class="pf-card-head"><i class="bi bi-calendar-event"></i> Período da Promoção</div>
        <div class="pf-card-body">
            <div class="pf-grid">
                <div class="pf-group">
                    <div class="pf-label">Data e Hora de Início <span>*</span></div>
                    <input type="datetime-local" name="data_inicio" required class="pf-input"
                           value="<?= $dataInicio ?>">
                </div>
                <div class="pf-group">
                    <div class="pf-label">Data e Hora de Fim <span>*</span></div>
                    <input type="datetime-local" name="data_fim" required class="pf-input"
                           value="<?= $dataFim ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- CONFIGURAÇÕES -->
    <div class="pf-card">
        <div class="pf-card-head"><i class="bi bi-gear"></i> Configurações</div>
        <div class="pf-card-body">
            <div class="pf-grid-3" style="margin-bottom:1rem">
                <div class="pf-group">
                    <div class="pf-label">Máx. Participantes</div>
                    <input type="number" name="max_participantes" class="pf-input" min="0"
                           value="<?= $promocao['max_participantes'] ?? 0 ?>">
                    <div class="pf-hint">0 = ilimitado</div>
                </div>
                <div class="pf-group">
                    <div class="pf-label">Nº de Vencedores</div>
                    <input type="number" name="max_vencedores" class="pf-input" min="1"
                           value="<?= $promocao['max_vencedores'] ?? 1 ?>">
                </div>
                <div class="pf-group">
                    <div class="pf-label">Part. por Pessoa</div>
                    <input type="number" name="participacoes_por_pessoa" class="pf-input" min="1"
                           value="<?= $promocao['participacoes_por_pessoa'] ?? 1 ?>">
                    <div class="pf-hint">Máx. de participações por ouvinte</div>
                </div>
            </div>
            <div class="pf-grid">
                <div class="pf-group">
                    <div class="pf-label">Pontos Mínimos</div>
                    <input type="number" name="pontos_minimos" class="pf-input" min="0"
                           value="<?= $promocao['pontos_minimos'] ?? 0 ?>">
                    <div class="pf-hint">0 = sem requisito de pontos</div>
                </div>
                <div class="pf-group">
                    <div class="pf-label">Dias Mínimos como Ouvinte</div>
                    <input type="number" name="dias_minimo_ouvinte" class="pf-input" min="0"
                           value="<?= $promocao['dias_minimo_ouvinte'] ?? 0 ?>">
                    <div class="pf-hint">0 = qualquer ouvinte pode participar</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ESTADO -->
    <!-- SORTEIO AUTOMÁTICO -->
    <div style="background:rgba(0,229,255,0.04);border:1px solid rgba(0,229,255,0.12);border-radius:12px;padding:1.25rem;margin-bottom:1.25rem">
        <div style="font-size:13px;font-weight:700;color:#fff;margin-bottom:.5rem">🤖 Sorteio Automático</div>
        <div style="font-size:12px;color:#71717a;margin-bottom:1rem">Quando a data de fim for atingida, o sorteio é realizado automaticamente.</div>
        <label style="display:flex;align-items:center;gap:.75rem;cursor:pointer">
            <input type="checkbox" name="sorteio_automatico" value="1"
                   <?= !empty($promocao['sorteio_automatico']) ? 'checked' : '' ?>
                   style="width:18px;height:18px;accent-color:#00e5ff;cursor:pointer">
            <div>
                <div style="font-size:13px;font-weight:600;color:#fff">Activar sorteio automático</div>
                <div style="font-size:11px;color:#71717a">Requer data de fim definida</div>
            </div>
        </label>
    </div>

    <div class="pf-card">
        <div class="pf-card-head"><i class="bi bi-toggle-on"></i> Estado</div>
        <div class="pf-card-body">
            <div class="pf-estado-grid" id="estadoGrid">
                <?php foreach([
                    'rascunho'  => ['emoji'=>'📝','label'=>'Rascunho', 'desc'=>'Em preparação'],
                    'activa'    => ['emoji'=>'✅','label'=>'Activa',   'desc'=>'A decorrer'],
                    'encerrada' => ['emoji'=>'🔒','label'=>'Encerrada','desc'=>'Terminada'],
                    'cancelada' => ['emoji'=>'❌','label'=>'Cancelada','desc'=>'Cancelada'],
                ] as $val => $info):
                    $sel = $estadoDefault === $val ? 'selected' : '';
                ?>
                <div class="pf-estado-opt pf-estado-<?= $val ?> <?= $sel ?>" onclick="selectEstado('<?= $val ?>', this)">
                    <input type="radio" name="estado" value="<?= $val ?>" <?= $sel ? 'checked' : '' ?>>
                    <div style="font-size:24px"><?= $info['emoji'] ?></div>
                    <span class="pf-estado-opt-label"><?= $info['label'] ?></span>
                    <div style="font-size:10px;color:#52525b;margin-top:2px"><?= $info['desc'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ACÇÕES -->
    <div class="pf-actions">
        <button type="submit" class="pf-btn-save">
            <i class="bi bi-check-circle-fill"></i>
            <?= $isEdit ? 'Guardar Alterações' : 'Criar Promoção' ?>
        </button>
        <a href="/public/pulso/<?= $stationId ?>/promocoes" class="pf-btn-cancel">
            <i class="bi bi-x-circle"></i> Cancelar
        </a>
    </div>

</form>

<script>
function selectEstado(val, el) {
    document.querySelectorAll('.pf-estado-opt').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    el.querySelector('input[type=radio]').checked = true;
}
</script>
