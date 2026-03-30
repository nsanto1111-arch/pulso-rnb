<?php
$estadoCores = [
    'rascunho'  => ['cor'=>'#71717a','bg'=>'rgba(113,113,122,0.12)','label'=>'📝 Rascunho'],
    'activa'    => ['cor'=>'#10b981','bg'=>'rgba(16,185,129,0.12)', 'label'=>'✅ Activa'],
    'encerrada' => ['cor'=>'#ef4444','bg'=>'rgba(239,68,68,0.12)',  'label'=>'🔒 Encerrada'],
    'cancelada' => ['cor'=>'#6b7280','bg'=>'rgba(107,114,128,0.12)','label'=>'❌ Cancelada'],
];
$contPorEstado = ['rascunho'=>0,'activa'=>0,'encerrada'=>0,'cancelada'=>0];
foreach($promocoes as $p) $contPorEstado[$p['estado']] = ($contPorEstado[$p['estado']] ?? 0) + 1;
$estadoActivo = $estado ?? null;
?>
<style>
.pl-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.pl-title{font-size:22px;font-weight:800;color:#fff}
.pl-subtitle{font-size:13px;color:#71717a;margin-top:3px}
.pl-btn-new{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:linear-gradient(135deg,#00e5ff,#0891b2);color:#000;border:none;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none}
.pl-btn-new:hover{opacity:.9;text-decoration:none;color:#000}
.pl-filters{display:flex;gap:.5rem;margin-bottom:1.5rem;flex-wrap:wrap}
.pl-filter{padding:.5rem 1rem;border:1px solid rgba(255,255,255,0.08);border-radius:8px;background:transparent;color:#71717a;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;transition:all .2s}
.pl-filter:hover{color:#a1a1aa;text-decoration:none}
.pl-filter.active{background:rgba(0,229,255,0.1);border-color:rgba(0,229,255,0.3);color:#00e5ff}
.pl-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(400px,1fr));gap:1.25rem}
.pl-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;transition:border-color .2s}
.pl-card:hover{border-color:rgba(255,255,255,0.12)}
.pl-card-top{padding:1.25rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06)}
.pl-card-nome{font-size:16px;font-weight:800;color:#fff;margin-bottom:.5rem}
.pl-card-premio{display:flex;align-items:center;gap:.5rem;font-size:13px;color:#f59e0b;font-weight:600;margin-bottom:.5rem}
.pl-card-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:.5rem;padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06)}
.pl-stat{text-align:center;padding:.625rem;background:rgba(0,0,0,0.15);border-radius:8px}
.pl-stat-val{font-size:18px;font-weight:800;line-height:1;margin-bottom:.25rem}
.pl-stat-lbl{font-size:10px;color:#71717a;text-transform:uppercase;letter-spacing:.5px;font-weight:600}
.pl-prog-wrap{padding:.875rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06)}
.pl-prog-bar{height:6px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden;margin:.375rem 0}
.pl-prog-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,#00e5ff,#10b981)}
.pl-card-actions{display:flex;gap:.625rem;padding:1rem 1.5rem}
.pl-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1rem;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s;border:1px solid transparent;flex:1;justify-content:center}
.pl-btn-edit{background:rgba(255,255,255,0.04);color:#a1a1aa;border-color:rgba(255,255,255,0.08)}
.pl-btn-edit:hover{background:rgba(255,255,255,0.08);color:#fff;text-decoration:none}
.pl-btn-sortear{background:rgba(245,158,11,0.1);color:#f59e0b;border-color:rgba(245,158,11,0.25)}
.pl-btn-sortear:hover{background:rgba(245,158,11,0.2);text-decoration:none;color:#f59e0b}
.pl-btn-activar{background:rgba(16,185,129,0.1);color:#10b981;border-color:rgba(16,185,129,0.25)}
.pl-btn-activar:hover{background:rgba(16,185,129,0.2);text-decoration:none;color:#10b981}
.pl-btn-del{background:rgba(239,68,68,0.08);color:#ef4444;border-color:rgba(239,68,68,0.2);flex:0;padding:.625rem .875rem}
.pl-btn-del:hover{background:rgba(239,68,68,0.15);text-decoration:none;color:#ef4444}
.pl-badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;flex-shrink:0}
.pl-urgente{margin-top:.5rem;padding:.375rem .75rem;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:6px;font-size:11px;color:#f87171;font-weight:700}
.pl-empty{text-align:center;padding:4rem;color:#52525b}
@media(max-width:768px){.pl-grid{grid-template-columns:1fr}}
</style>

<div class="pl-header">
    <div>
        <div class="pl-title">🎁 Promoções</div>
        <div class="pl-subtitle"><?= count($promocoes) ?> promoção<?= count($promocoes) !== 1 ? 'ões' : '' ?></div>
    </div>
    <a href="/public/pulso/<?= $stationId ?>/promocoes/nova" class="pl-btn-new">
        <i class="bi bi-plus-lg"></i> Nova Promoção
    </a>
</div>

<div class="pl-filters">
    <a href="/public/pulso/<?= $stationId ?>/promocoes" class="pl-filter <?= !$estadoActivo ? 'active' : '' ?>">
        Todas <span style="opacity:.6;margin-left:.25rem"><?= count($promocoes) ?></span>
    </a>
    <?php foreach($estadoCores as $est => $info): ?>
    <a href="/public/pulso/<?= $stationId ?>/promocoes?estado=<?= $est ?>"
       class="pl-filter <?= $estadoActivo === $est ? 'active' : '' ?>"
       style="<?= $estadoActivo === $est ? "border-color:{$info['cor']};color:{$info['cor']};background:{$info['bg']}" : '' ?>">
        <?= $info['label'] ?>
        <?php if ($contPorEstado[$est] > 0): ?>
        <span style="opacity:.6;margin-left:.25rem"><?= $contPorEstado[$est] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (!empty($promocoes)): ?>
<div class="pl-grid">
<?php foreach($promocoes as $p):
    $est  = $p['estado'] ?? 'rascunho';
    $info = $estadoCores[$est] ?? $estadoCores['rascunho'];
    $pct  = $p['max_participantes'] > 0 ? min(100, round($p['total_participantes'] / $p['max_participantes'] * 100)) : 0;
    $diasRestantes = '';
    $urgente = false;
    if ($est === 'activa' && !empty($p['data_fim'])) {
        $diff = (strtotime($p['data_fim']) - time()) / 86400;
        if ($diff < 0)      { $diasRestantes = 'Expirada'; $urgente = true; }
        elseif ($diff < 1)  { $diasRestantes = '⚠️ Termina hoje!'; $urgente = true; }
        elseif ($diff < 2)  { $diasRestantes = '⚠️ Termina amanhã'; $urgente = true; }
        else                  $diasRestantes = '📅 Termina em ' . ceil($diff) . ' dias';
    }
?>
<div class="pl-card">
    <div class="pl-card-top">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:.625rem">
            <div class="pl-card-nome"><?= htmlspecialchars($p['nome']) ?></div>
            <span class="pl-badge" style="background:<?= $info['bg'] ?>;color:<?= $info['cor'] ?>;border:1px solid <?= $info['cor'] ?>30">
                <?= $info['label'] ?>
            </span>
        </div>
        <div class="pl-card-premio">
            <i class="bi bi-trophy-fill"></i>
            <?= htmlspecialchars($p['premio'] ?? '—') ?>
        </div>
        <?php if (!empty($p['descricao'])): ?>
        <div style="font-size:12px;color:#71717a;line-height:1.5"><?= htmlspecialchars(mb_substr($p['descricao'],0,90)) ?><?= mb_strlen($p['descricao']??'') > 90 ? '…' : '' ?></div>
        <?php endif; ?>
        <?php if ($urgente): ?><div class="pl-urgente"><?= $diasRestantes ?></div>
        <?php elseif ($diasRestantes): ?><div style="font-size:11px;color:#71717a;margin-top:.5rem"><?= $diasRestantes ?></div>
        <?php endif; ?>
    </div>

    <div class="pl-card-stats">
        <div class="pl-stat">
            <div class="pl-stat-val" style="color:#00e5ff"><?= $p['total_participantes'] ?></div>
            <div class="pl-stat-lbl">Participantes</div>
        </div>
        <div class="pl-stat">
            <div class="pl-stat-val" style="color:#10b981"><?= $p['max_vencedores'] ?? 1 ?></div>
            <div class="pl-stat-lbl">Vencedores</div>
        </div>
        <div class="pl-stat">
            <div class="pl-stat-val" style="color:#a1a1aa;font-size:13px"><?= !empty($p['data_inicio']) ? date('d/m',strtotime($p['data_inicio'])) : '—' ?></div>
            <div class="pl-stat-lbl">Início</div>
        </div>
        <div class="pl-stat">
            <div class="pl-stat-val" style="color:#a1a1aa;font-size:13px"><?= !empty($p['data_fim']) ? date('d/m',strtotime($p['data_fim'])) : '—' ?></div>
            <div class="pl-stat-lbl">Fim</div>
        </div>
    </div>

    <?php if ($p['max_participantes'] > 0): ?>
    <div class="pl-prog-wrap">
        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:.25rem">
            <span style="color:#71717a;font-weight:600">Capacidade</span>
            <span style="color:#a1a1aa"><?= $p['total_participantes'] ?> / <?= $p['max_participantes'] ?></span>
        </div>
        <div class="pl-prog-bar"><div class="pl-prog-fill" style="width:<?= $pct ?>%"></div></div>
    </div>
    <?php endif; ?>

    <div class="pl-card-actions">
        <a href="/public/pulso/<?= $stationId ?>/promocoes/<?= $p['id'] ?>/participantes" class="pl-btn pl-btn-edit">
            <i class="bi bi-people"></i> Participantes
        </a>
        <a href="/public/pulso/<?= $stationId ?>/promocoes/<?= $p['id'] ?>/editar" class="pl-btn pl-btn-edit" style="flex:0;padding:.625rem .875rem" title="Editar">
            <i class="bi bi-pencil"></i>
        </a>
        <?php if ($est === 'activa'): ?>
        <a href="/public/pulso/<?= $stationId ?>/sorteios" class="pl-btn pl-btn-sortear">
            <i class="bi bi-shuffle"></i> Sortear
        </a>
        <?php elseif ($est === 'rascunho'): ?>
        <a href="/public/pulso/<?= $stationId ?>/promocoes/<?= $p['id'] ?>/editar?activar=1" class="pl-btn pl-btn-activar">
            <i class="bi bi-play-fill"></i> Activar
        </a>
        <?php endif; ?>
        <a href="/public/pulso/<?= $stationId ?>/promocoes/<?= $p['id'] ?>/excluir"
           class="pl-btn pl-btn-del" onclick="return confirm('Eliminar esta promoção?')" title="Eliminar">
            <i class="bi bi-trash"></i>
        </a>
    </div>
</div>
<?php endforeach; ?>
</div>

<?php else: ?>
<div class="pl-empty">
    <div style="font-size:48px;margin-bottom:1rem;opacity:.3">🎁</div>
    <div style="font-size:15px;font-weight:600;color:#a1a1aa;margin-bottom:.5rem">
        <?= $estadoActivo ? 'Nenhuma promoção ' . $estadoActivo : 'Nenhuma promoção criada' ?>
    </div>
    <?php if (!$estadoActivo): ?>
    <a href="/public/pulso/<?= $stationId ?>/promocoes/nova" class="pl-btn-new" style="display:inline-flex;margin-top:1rem">
        <i class="bi bi-plus-lg"></i> Criar Primeira Promoção
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>
