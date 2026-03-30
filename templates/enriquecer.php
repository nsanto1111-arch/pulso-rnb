<?php
$stats     = $dados['stats'];
$completos = $dados['completos'];
$incompletos = $dados['incompletos'];
$totalFantasmas = $dados['fantasmas'];

$camposLabel = [
    'provincia'       => 'Província',
    'genero'          => 'Género',
    'data_nascimento' => 'Idade',
    'generos_musicais'=> 'Gostos musicais',
    'como_conheceu'   => 'Como conheceu',
];
?>
<style>
.enr-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}
.enr-title { font-size: 24px; font-weight: 800; color: #fff; }
.enr-subtitle { font-size: 13px; color: #888; margin-top: 4px; }

.enr-kpis {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}
.enr-kpi {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    transition: border-color 0.2s;
}
.enr-kpi:hover { border-color: rgba(255,255,255,0.15); }
.enr-kpi-value { font-size: 42px; font-weight: 900; line-height: 1; margin-bottom: 6px; }
.enr-kpi-label { font-size: 12px; color: #888; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; }
.enr-kpi.verde .enr-kpi-value  { color: #10b981; }
.enr-kpi.amarelo .enr-kpi-value { color: #f59e0b; }
.enr-kpi.cinza .enr-kpi-value  { color: #6b7280; }

.enr-section {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.enr-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.06);
    background: rgba(255,255,255,0.02);
}
.enr-section-title {
    font-size: 15px;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 8px;
}
.enr-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    letter-spacing: 0.5px;
}
.enr-badge.verde  { background: rgba(16,185,129,0.15); color: #10b981; }
.enr-badge.amarelo { background: rgba(245,158,11,0.15); color: #f59e0b; }
.enr-badge.cinza  { background: rgba(107,114,128,0.15); color: #9ca3af; }

.enr-row {
    display: flex;
    align-items: center;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    gap: 1rem;
    transition: background 0.15s;
}
.enr-row:last-child { border-bottom: none; }
.enr-row:hover { background: rgba(255,255,255,0.03); }

.enr-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 800;
    flex-shrink: 0;
    background: rgba(255,255,255,0.06);
    color: #fff;
}
.enr-info { flex: 1; min-width: 0; }
.enr-nome { font-size: 14px; font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.enr-meta { font-size: 12px; color: #666; margin-top: 2px; }

.enr-campos-faltam {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    flex-shrink: 0;
}
.enr-tag-falta {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 6px;
    background: rgba(239,68,68,0.1);
    color: #f87171;
    border: 1px solid rgba(239,68,68,0.2);
    white-space: nowrap;
}
.enr-tag-ok {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 6px;
    background: rgba(16,185,129,0.08);
    color: #34d399;
    border: 1px solid rgba(16,185,129,0.15);
    white-space: nowrap;
}

.enr-progress-wrap {
    width: 80px;
    flex-shrink: 0;
}
.enr-progress-bar {
    height: 6px;
    background: rgba(255,255,255,0.08);
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 4px;
}
.enr-progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s;
}
.enr-progress-label {
    font-size: 11px;
    color: #666;
    text-align: right;
}

.enr-btn-edit {
    padding: 7px 16px;
    background: rgba(212,175,55,0.1);
    border: 1px solid rgba(212,175,55,0.3);
    color: #D4AF37;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    transition: all 0.2s;
    flex-shrink: 0;
}
.enr-btn-edit:hover {
    background: rgba(212,175,55,0.2);
    border-color: #D4AF37;
    color: #D4AF37;
    text-decoration: none;
}

.enr-btn-danger {
    padding: 10px 20px;
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.3);
    color: #f87171;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.enr-btn-danger:hover {
    background: rgba(239,68,68,0.2);
    border-color: #ef4444;
}

.enr-fantasmas-box {
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.enr-fantasmas-info { display: flex; align-items: center; gap: 1rem; }
.enr-fantasmas-num { font-size: 36px; font-weight: 900; color: #6b7280; }
.enr-fantasmas-desc { font-size: 13px; color: #888; max-width: 320px; line-height: 1.6; }

.enr-alert-success {
    background: rgba(16,185,129,0.1);
    border: 1px solid rgba(16,185,129,0.3);
    color: #34d399;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    font-size: 14px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}
</style>

<?php if ($arquivados > 0): ?>
<div class="enr-alert-success">
    ✅ <?= $arquivados ?> registos fantasma arquivados com sucesso.
</div>
<?php endif; ?>

<!-- HEADER -->
<div class="enr-header">
    <div>
        <div class="enr-title">🔬 Enriquecimento de Perfis</div>
        <div class="enr-subtitle">Identifica e completa perfis incompletos da audiência</div>
    </div>
    <a href="/public/pulso/<?= $stationId ?>/ouvintes" style="font-size:13px;color:#888;text-decoration:none;">
        ← Voltar a Ouvintes
    </a>
</div>

<!-- KPIs -->
<div class="enr-kpis">
    <div class="enr-kpi verde">
        <div class="enr-kpi-value"><?= $stats['completos'] ?></div>
        <div class="enr-kpi-label">Perfis Completos</div>
    </div>
    <div class="enr-kpi amarelo">
        <div class="enr-kpi-value"><?= $stats['incompletos'] ?></div>
        <div class="enr-kpi-label">Incompletos</div>
    </div>
    <div class="enr-kpi cinza">
        <div class="enr-kpi-value"><?= $stats['fantasmas'] ?></div>
        <div class="enr-kpi-label">Fantasmas</div>
    </div>
</div>

<!-- INCOMPLETOS -->
<?php if (!empty($incompletos)): ?>
<div class="enr-section">
    <div class="enr-section-header">
        <div class="enr-section-title">
            ⚠️ Perfis Incompletos
        </div>
        <span class="enr-badge amarelo"><?= count($incompletos) ?> para completar</span>
    </div>

    <?php foreach ($incompletos as $o):
        $score = (int) $o['score'];
        $pct   = round($score / 5 * 100);
        $cor   = $pct >= 60 ? '#f59e0b' : '#ef4444';

        // Detectar o que falta
        $faltam = [];
        $temCampos = [];
        if (empty($o['provincia']))        $faltam[] = 'Província';
        else                               $temCampos[] = 'Província';
        if (empty($o['genero']))           $faltam[] = 'Género';
        else                               $temCampos[] = 'Género';
        if (empty($o['data_nascimento']))  $faltam[] = 'Idade';
        else                               $temCampos[] = 'Idade';
        if (empty($o['generos_musicais'])) $faltam[] = 'Gostos';
        else                               $temCampos[] = 'Gostos';
        if (empty($o['como_conheceu']))    $faltam[] = 'Origem';
        else                               $temCampos[] = 'Origem';

        $inicial = mb_strtoupper(mb_substr($o['nome'], 0, 1));
    ?>
    <div class="enr-row">
        <div class="enr-avatar"><?= $inicial ?></div>

        <div class="enr-info">
            <div class="enr-nome"><?= htmlspecialchars($o['nome']) ?></div>
            <div class="enr-meta"><?= htmlspecialchars($o['telefone'] ?? 'Sem telefone') ?></div>
        </div>

        <div class="enr-campos-faltam">
            <?php foreach ($faltam as $f): ?>
                <span class="enr-tag-falta">✗ <?= $f ?></span>
            <?php endforeach; ?>
            <?php foreach ($temCampos as $t): ?>
                <span class="enr-tag-ok">✓ <?= $t ?></span>
            <?php endforeach; ?>
        </div>

        <div class="enr-progress-wrap">
            <div class="enr-progress-bar">
                <div class="enr-progress-fill" style="width:<?= $pct ?>%;background:<?= $cor ?>;"></div>
            </div>
            <div class="enr-progress-label"><?= $score ?>/5</div>
        </div>

        <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/editar"
           class="enr-btn-edit">Completar →</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- COMPLETOS -->
<?php if (!empty($completos)): ?>
<div class="enr-section">
    <div class="enr-section-header">
        <div class="enr-section-title">
            ✅ Perfis Completos
        </div>
        <span class="enr-badge verde"><?= count($completos) ?> completos</span>
    </div>

    <?php foreach ($completos as $o):
        $inicial = mb_strtoupper(mb_substr($o['nome'], 0, 1));
    ?>
    <div class="enr-row">
        <div class="enr-avatar" style="background:rgba(16,185,129,0.15);color:#10b981;">
            <?= $inicial ?>
        </div>
        <div class="enr-info">
            <div class="enr-nome"><?= htmlspecialchars($o['nome']) ?></div>
            <div class="enr-meta">
                <?= htmlspecialchars($o['provincia'] ?? '') ?>
                <?= !empty($o['genero']) ? ' · ' . ucfirst($o['genero']) : '' ?>
                <?= !empty($o['data_nascimento']) ? ' · ' . (date('Y') - (int)substr($o['data_nascimento'],0,4)) . ' anos' : '' ?>
            </div>
        </div>
        <div style="flex:1"></div>
        <div class="enr-progress-wrap">
            <div class="enr-progress-bar">
                <div class="enr-progress-fill" style="width:100%;background:#10b981;"></div>
            </div>
            <div class="enr-progress-label" style="color:#10b981;">5/5</div>
        </div>
        <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/editar"
           class="enr-btn-edit">Editar</a>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- FANTASMAS -->
<?php if ($totalFantasmas > 0): ?>
<div class="enr-section">
    <div class="enr-section-header">
        <div class="enr-section-title">👻 Registos Fantasma</div>
        <span class="enr-badge cinza"><?= $totalFantasmas ?> sem nome</span>
    </div>
    <div class="enr-fantasmas-box">
        <div class="enr-fantasmas-info">
            <div class="enr-fantasmas-num"><?= $totalFantasmas ?></div>
            <div class="enr-fantasmas-desc">
                Registos criados automaticamente por participações via WordPress,
                sem nome nem dados identificativos. Não têm utilidade para análise
                e podem ser arquivados em segurança — não serão eliminados.
            </div>
        </div>
        <form method="POST"
              action="/public/pulso/<?= $stationId ?>/ouvintes/enriquecer/arquivar"
              onsubmit="return confirm('Arquivar <?= $totalFantasmas ?> registos fantasma? Esta acção pode ser revertida.')">
            <button type="submit" class="enr-btn-danger">
                🗄️ Arquivar <?= $totalFantasmas ?> fantasmas
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($stats['completos'] === 0 && $stats['incompletos'] === 0 && $totalFantasmas === 0): ?>
<div style="text-align:center;padding:4rem;color:#666;">
    <div style="font-size:48px;margin-bottom:1rem;">🎉</div>
    <div style="font-size:18px;font-weight:700;color:#fff;margin-bottom:0.5rem;">Tudo em ordem!</div>
    <div style="font-size:14px;">Não há perfis para enriquecer.</div>
</div>
<?php endif; ?>
