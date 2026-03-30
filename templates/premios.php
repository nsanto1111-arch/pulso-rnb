<?php
$premios = $dados['premios'] ?? [];
$stats   = $dados['stats'] ?? [];
?>
<style>
.pr-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.pr-title{font-size:22px;font-weight:800;color:#fff}
.pr-subtitle{font-size:13px;color:#71717a;margin-top:3px}
.pr-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.pr-kpi{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:1.25rem;text-align:center}
.pr-kpi-val{font-size:32px;font-weight:900;line-height:1;margin-bottom:.375rem}
.pr-kpi-lbl{font-size:11px;color:#71717a;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.pr-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.pr-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.pr-table{width:100%;border-collapse:collapse;font-size:13px}
.pr-table th{padding:.75rem 1.25rem;text-align:left;font-size:11px;font-weight:700;color:#71717a;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid rgba(255,255,255,0.06)}
.pr-table td{padding:.875rem 1.25rem;border-bottom:1px solid rgba(255,255,255,0.04);vertical-align:middle}
.pr-table tr:last-child td{border-bottom:none}
.pr-table tr:hover td{background:rgba(255,255,255,0.02)}
.pr-stock{display:flex;gap:.375rem;align-items:center}
.pr-stock-item{padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700}
.pr-btn{display:inline-flex;align-items:center;gap:.375rem;padding:.4rem .875rem;border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s;border:1px solid}
.pr-btn-new{background:rgba(0,229,255,0.1);border-color:rgba(0,229,255,0.3);color:#00e5ff}
.pr-btn-new:hover{background:rgba(0,229,255,0.2);text-decoration:none;color:#00e5ff}
.pr-btn-edit{background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.1);color:#a1a1aa}
.pr-btn-edit:hover{background:rgba(255,255,255,0.08);color:#fff;text-decoration:none}
.pr-btn-del{background:rgba(239,68,68,0.06);border-color:rgba(239,68,68,0.2);color:#ef4444}
.pr-btn-del:hover{background:rgba(239,68,68,0.12);text-decoration:none;color:#ef4444}
.pr-empty{text-align:center;padding:3rem;color:#52525b}
.pr-bar{height:6px;background:rgba(255,255,255,0.06);border-radius:3px;overflow:hidden;margin-top:4px}
.pr-bar-fill{height:100%;border-radius:3px}
</style>

<div class="pr-header">
    <div>
        <div class="pr-title">🎁 Estoque de Prémios</div>
        <div class="pr-subtitle">Controlo de stock e entregas</div>
    </div>
    <div style="display:flex;gap:.75rem">
        <a href="/public/pulso/<?= $stationId ?>/entregas" class="pr-btn pr-btn-edit">
            <i class="bi bi-truck"></i> Ver Entregas
        </a>
        <a href="/public/pulso/<?= $stationId ?>/premios/novo" class="pr-btn pr-btn-new">
            <i class="bi bi-plus-lg"></i> Novo Prémio
        </a>
    </div>
</div>

<!-- KPIs -->
<div class="pr-kpis">
    <div class="pr-kpi">
        <div class="pr-kpi-val" style="color:#00e5ff"><?= $stats['total'] ?></div>
        <div class="pr-kpi-lbl">Prémios Cadastrados</div>
    </div>
    <div class="pr-kpi">
        <div class="pr-kpi-val" style="color:#10b981"><?= $stats['disponiveis'] ?></div>
        <div class="pr-kpi-lbl">Disponíveis</div>
    </div>
    <div class="pr-kpi">
        <div class="pr-kpi-val" style="color:#f59e0b"><?= $stats['reservados'] ?></div>
        <div class="pr-kpi-lbl">Reservados</div>
    </div>
    <div class="pr-kpi">
        <div class="pr-kpi-val" style="color:#8b5cf6"><?= $stats['entregues'] ?></div>
        <div class="pr-kpi-lbl">Entregues</div>
    </div>
</div>

<!-- LISTA -->
<div class="pr-card">
    <div class="pr-card-head">
        <span>📦 Inventário</span>
        <span style="font-size:12px;color:#71717a"><?= count($premios) ?> prémio<?= count($premios)!==1?'s':'' ?></span>
    </div>
    <?php if (!empty($premios)): ?>
    <table class="pr-table">
        <thead>
            <tr>
                <th>Prémio</th>
                <th>Stock</th>
                <th>Disponível</th>
                <th>Reservado</th>
                <th>Entregue</th>
                <th>Valor Est.</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($premios as $p):
            $total = max($p['quantidade_total'], 1);
            $pctDisp = round($p['quantidade_disponivel'] / $total * 100);
            $pctRes  = round($p['quantidade_reservada'] / $total * 100);
            $pctEnt  = round($p['quantidade_entregue'] / $total * 100);
        ?>
        <tr>
            <td>
                <div style="font-weight:700;color:#fff"><?= htmlspecialchars($p['nome']) ?></div>
                <?php if (!empty($p['fornecedor'])): ?>
                <div style="font-size:11px;color:#71717a"><?= htmlspecialchars($p['fornecedor']) ?></div>
                <?php endif; ?>
            </td>
            <td style="color:#fff;font-weight:700"><?= $p['quantidade_total'] ?></td>
            <td>
                <span style="color:#10b981;font-weight:700"><?= $p['quantidade_disponivel'] ?></span>
                <div class="pr-bar"><div class="pr-bar-fill" style="width:<?= $pctDisp ?>%;background:#10b981"></div></div>
            </td>
            <td>
                <span style="color:#f59e0b;font-weight:700"><?= $p['quantidade_reservada'] ?></span>
                <div class="pr-bar"><div class="pr-bar-fill" style="width:<?= $pctRes ?>%;background:#f59e0b"></div></div>
            </td>
            <td>
                <span style="color:#8b5cf6;font-weight:700"><?= $p['quantidade_entregue'] ?></span>
                <div class="pr-bar"><div class="pr-bar-fill" style="width:<?= $pctEnt ?>%;background:#8b5cf6"></div></div>
            </td>
            <td style="color:#71717a">
                <?= $p['valor_estimado'] > 0 ? number_format($p['valor_estimado'], 2, ',', '.') . ' Kz' : '—' ?>
            </td>
            <td>
                <?php if ($p['ativo']): ?>
                <span style="background:rgba(16,185,129,0.1);color:#10b981;border:1px solid rgba(16,185,129,0.25);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700">Activo</span>
                <?php else: ?>
                <span style="background:rgba(113,113,122,0.1);color:#71717a;border:1px solid rgba(113,113,122,0.2);padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700">Inactivo</span>
                <?php endif; ?>
            </td>
            <td>
                <div style="display:flex;gap:.5rem">
                    <a href="/public/pulso/<?= $stationId ?>/premios/<?= $p['id'] ?>/editar" class="pr-btn pr-btn-edit">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <form method="POST" action="/public/pulso/<?= $stationId ?>/premios/<?= $p['id'] ?>/excluir" style="display:inline"
                          onsubmit="return confirm('Excluir <?= htmlspecialchars($p['nome']) ?>?')">
                        <button type="submit" class="pr-btn pr-btn-del"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="pr-empty">
        <div style="font-size:48px;margin-bottom:1rem;opacity:.3">🎁</div>
        <div style="font-size:15px;font-weight:600;color:#a1a1aa;margin-bottom:.5rem">Nenhum prémio cadastrado</div>
        <div style="font-size:13px;margin-bottom:1.5rem">Adiciona prémios para controlar o stock</div>
        <a href="/public/pulso/<?= $stationId ?>/premios/novo" class="pr-btn pr-btn-new">
            <i class="bi bi-plus-lg"></i> Adicionar Prémio
        </a>
    </div>
    <?php endif; ?>
</div>
