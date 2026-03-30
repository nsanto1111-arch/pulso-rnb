<?php $isEdit = !empty($premio); ?>
<style>
.pf-back{display:inline-flex;align-items:center;gap:.5rem;color:#71717a;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:1.25rem}
.pf-back:hover{color:#fff;text-decoration:none}
.pf-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.pf-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff}
.pf-card-body{padding:1.5rem;display:grid;grid-template-columns:1fr 1fr;gap:1.25rem}
.pf-group{display:flex;flex-direction:column;gap:.5rem}
.pf-group.full{grid-column:1/-1}
.pf-label{font-size:12px;font-weight:700;color:#a1a1aa;text-transform:uppercase;letter-spacing:.5px}
.pf-input{padding:.75rem 1rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:14px;outline:none;transition:border-color .2s;width:100%}
.pf-input:focus{border-color:rgba(0,229,255,0.4)}
.pf-input::placeholder{color:#52525b}
.pf-actions{display:flex;gap:.875rem;margin-top:1rem}
.pf-btn-save{padding:.875rem 2rem;background:linear-gradient(135deg,#00e5ff,#0891b2);color:#000;border:none;border-radius:10px;font-size:14px;font-weight:800;cursor:pointer;transition:all .2s}
.pf-btn-save:hover{opacity:.9}
.pf-btn-cancel{display:inline-flex;align-items:center;gap:.5rem;padding:.875rem 1.5rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:#a1a1aa;text-decoration:none;font-size:14px;font-weight:600}
.pf-check{display:flex;align-items:center;gap:.75rem;padding:.875rem 1rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:8px;cursor:pointer}
.pf-check input{width:18px;height:18px;cursor:pointer;accent-color:#00e5ff}
</style>

<a href="/public/pulso/<?= $stationId ?>/premios" class="pf-back">
    <i class="bi bi-arrow-left"></i> Voltar ao Estoque
</a>

<form method="POST" action="/public/pulso/<?= $stationId ?>/premios/<?= $isEdit ? $premio['id'].'/editar' : 'novo' ?>">
    <div class="pf-card">
        <div class="pf-card-head"><?= $isEdit ? '✏️ Editar Prémio' : '🎁 Novo Prémio' ?></div>
        <div class="pf-card-body">
            <div class="pf-group full">
                <label class="pf-label">Nome do Prémio *</label>
                <input type="text" name="nome" class="pf-input" required
                       value="<?= htmlspecialchars($premio['nome'] ?? '') ?>"
                       placeholder="Ex: Chinelas Havainas, Vale de Compras...">
            </div>
            <div class="pf-group">
                <label class="pf-label">Quantidade Total *</label>
                <input type="number" name="quantidade_total" class="pf-input" required min="1"
                       value="<?= $premio['quantidade_total'] ?? 1 ?>">
            </div>
            <div class="pf-group">
                <label class="pf-label">Valor Estimado (Kz)</label>
                <input type="number" name="valor_estimado" class="pf-input" step="0.01" min="0"
                       value="<?= $premio['valor_estimado'] ?? '' ?>" placeholder="0.00">
            </div>
            <div class="pf-group">
                <label class="pf-label">Fornecedor / Patrocinador</label>
                <input type="text" name="fornecedor" class="pf-input"
                       value="<?= htmlspecialchars($premio['fornecedor'] ?? '') ?>"
                       placeholder="Nome do patrocinador">
            </div>
            <div class="pf-group full">
                <label class="pf-label">Descrição</label>
                <textarea name="descricao" class="pf-input" rows="3"
                          placeholder="Detalhes do prémio..."><?= htmlspecialchars($premio['descricao'] ?? '') ?></textarea>
            </div>
            <div class="pf-group full">
                <label class="pf-check">
                    <input type="checkbox" name="ativo" value="1" <?= ($premio['ativo'] ?? 1) ? 'checked' : '' ?>>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#fff">Prémio activo</div>
                        <div style="font-size:11px;color:#71717a">Prémios inactivos não aparecem na lista de disponíveis</div>
                    </div>
                </label>
            </div>
        </div>
    </div>
    <div class="pf-actions">
        <button type="submit" class="pf-btn-save">
            <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Guardar Alterações' : 'Criar Prémio' ?>
        </button>
        <a href="/public/pulso/<?= $stationId ?>/premios" class="pf-btn-cancel">Cancelar</a>
    </div>
</form>
