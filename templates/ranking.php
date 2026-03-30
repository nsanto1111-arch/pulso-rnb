<?php
/**
 * PULSO — Ranking Semanal
 * Vars: $ranking (array), $periodo (string), $inicio_semana (string), $stationId (int)
 */

$ranking       = $ranking       ?? [];
$periodo       = $periodo       ?? 'Esta semana';
$label         = $label         ?? 'Esta Semana';
$filtroActual  = $filtroActual  ?? 'semana';
$totalOuvintes = $totalOuvintes ?? 0;
$totalPart     = $totalPart     ?? 0;
$inicio_semana = $inicio_semana ?? date('Y-m-d', strtotime('monday this week'));
$total = count($ranking);
$top3  = array_slice($ranking, 0, 3);
$resto = array_slice($ranking, 3);
// Pódio: 2º esq, 1º centro, 3º dir
$podiumOrder = [1, 0, 2];

$segLabel = static function(string $s): string {
    return match($s) {
        'embaixador' => 'Embaixador',
        'veterano'   => 'Veterano',
        'regular'    => 'Regular',
        'novo'       => 'Novo',
        'inactivo'   => 'Inactivo',
        default      => ucfirst($s),
    };
$segEmoji = static function(string $s): string {
    return match($s) {
        'embaixador' => '👑',
        'veterano'   => '🌟',
        'regular'    => '⭐',
        'novo'       => '🆕',
        'inactivo'   => '💤',
        default      => '⭐',
    };
};
};

$initials = static function(string $nome): string {
    $parts = explode(' ', trim($nome));
    if (count($parts) >= 2) {
        return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr(end($parts), 0, 1));
    }
    return mb_strtoupper(mb_substr($nome, 0, 2));
};

// Máximos para barras proporcionais
$maxPontos       = $total > 0 ? max(array_column($ranking, 'pontos')) : 1;
$maxParticipacoes = $total > 0 ? max(array_column($ranking, 'participacoes_semana')) : 1;
$maxLidas        = $total > 0 ? max(array_column($ranking, 'lidas_semana')) : 1;
?>
<style>
/* ═══ RANKING — PULSO ═══════════════════════════════════════════ */
.rk-tend-up{color:#10b981;font-size:11px;font-weight:700}
.rk-tend-down{color:#ef4444;font-size:11px;font-weight:700}
.rk-tend-eq{color:#71717a;font-size:11px}
.rk-tend-new{color:#00e5ff;font-size:10px;font-weight:700}
.rk-wrap { max-width: 1000px; }

/* ── Header ── */
.rk-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 32px;
}
.rk-title {
    font-size: 24px;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.5px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.rk-title-icon {
    font-size: 26px;
    filter: drop-shadow(0 0 12px #fbbf24);
}
.rk-periodo-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(251,191,36,0.08);
    border: 1px solid rgba(251,191,36,0.25);
    border-radius: 30px;
    padding: 8px 18px;
    font-size: 12px;
    font-weight: 700;
    color: #fbbf24;
    letter-spacing: 0.3px;
    white-space: nowrap;
}
.rk-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.rk-meta-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #71717a;
}
.rk-meta-item strong { color: #a1a1aa; }

/* ── Separador de secção ── */
.rk-section-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    color: #52525b;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.rk-section-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(255,255,255,0.05);
}

/* ── Pódio ── */
.rk-podium {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 0;
    margin-bottom: 40px;
    padding: 0 16px;
}

.rk-podium-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    max-width: 240px;
    animation: rk-rise 0.6s cubic-bezier(.22,1,.36,1) both;
}
.rk-podium-col:nth-child(1) { animation-delay: 0.1s; }
.rk-podium-col:nth-child(2) { animation-delay: 0.0s; }
.rk-podium-col:nth-child(3) { animation-delay: 0.2s; }

@keyframes rk-rise {
    from { opacity: 0; transform: translateY(24px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Avatar */
.rk-avatar {
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    color: #050510;
    position: relative;
    flex-shrink: 0;
    margin-bottom: 10px;
    font-family: monospace;
    letter-spacing: -1px;
}
.rk-podium-col.rk-pos-1 .rk-avatar {
    width: 76px; height: 76px; font-size: 22px;
    background: radial-gradient(135deg, #ffe066 0%, #fbbf24 60%, #d97706 100%);
    box-shadow: 0 0 0 3px rgba(251,191,36,0.2), 0 0 28px rgba(251,191,36,0.35);
}
.rk-podium-col.rk-pos-2 .rk-avatar {
    width: 62px; height: 62px; font-size: 18px;
    background: radial-gradient(135deg, #e2e8f0 0%, #94a3b8 60%, #64748b 100%);
    box-shadow: 0 0 0 3px rgba(148,163,184,0.15), 0 0 20px rgba(148,163,184,0.2);
}
.rk-podium-col.rk-pos-3 .rk-avatar {
    width: 58px; height: 58px; font-size: 17px;
    background: radial-gradient(135deg, #fcd9a0 0%, #cd7c2f 60%, #92400e 100%);
    box-shadow: 0 0 0 3px rgba(205,124,47,0.15), 0 0 16px rgba(205,124,47,0.2);
}

/* Coroa */
.rk-podium-col.rk-pos-1 .rk-avatar::before {
    content: '👑';
    position: absolute;
    top: -22px;
    font-size: 18px;
    filter: drop-shadow(0 0 6px #fbbf24);
    animation: rk-crown 3s ease-in-out infinite;
}
@keyframes rk-crown {
    0%,100% { transform: translateY(0) rotate(-5deg); }
    50%      { transform: translateY(-4px) rotate(5deg); }
}

.rk-podium-name {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
    text-align: center;
    max-width: 180px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-bottom: 4px;
}

.rk-podium-seg {
    font-size: 10px;
    font-weight: 700;
    padding: 2px 9px;
    border-radius: 20px;
    margin-bottom: 10px;
    letter-spacing: 0.3px;
}

.rk-podium-pts {
    font-weight: 900;
    line-height: 1;
    margin-bottom: 4px;
}
.rk-pos-1 .rk-podium-pts { font-size: 30px; color: #fbbf24; text-shadow: 0 0 16px rgba(251,191,36,0.4); }
.rk-pos-2 .rk-podium-pts { font-size: 24px; color: #94a3b8; }
.rk-pos-3 .rk-podium-pts { font-size: 22px; color: #cd7c2f; }

.rk-podium-pts-lbl { font-size: 10px; color: #52525b; font-weight: 500; margin-bottom: 12px; }

.rk-podium-mini-stats {
    display: flex;
    gap: 12px;
    margin-bottom: 14px;
}
.rk-mini-stat { text-align: center; }
.rk-mini-stat-val { font-size: 14px; font-weight: 800; color: #e4e4e7; line-height: 1; }
.rk-mini-stat-lbl { font-size: 9px; color: #52525b; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

/* Plataforma */
.rk-platform {
    width: 100%;
    border-radius: 10px 10px 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: 900;
    position: relative;
    overflow: hidden;
}
.rk-pos-1 .rk-platform {
    height: 88px;
    background: linear-gradient(180deg, rgba(251,191,36,0.18) 0%, rgba(251,191,36,0.04) 100%);
    border-top: 2px solid rgba(251,191,36,0.35);
    color: rgba(251,191,36,0.2);
}
.rk-pos-2 .rk-platform {
    height: 62px;
    background: linear-gradient(180deg, rgba(148,163,184,0.12) 0%, rgba(148,163,184,0.03) 100%);
    border-top: 2px solid rgba(148,163,184,0.25);
    color: rgba(148,163,184,0.2);
}
.rk-pos-3 .rk-platform {
    height: 44px;
    background: linear-gradient(180deg, rgba(205,124,47,0.12) 0%, rgba(205,124,47,0.03) 100%);
    border-top: 2px solid rgba(205,124,47,0.2);
    color: rgba(205,124,47,0.2);
}
.rk-platform-shine {
    position: absolute;
    top: 0; left: -120%;
    width: 60%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
    animation: rk-shine 4s ease-in-out infinite;
}
@keyframes rk-shine {
    0%   { left: -120%; }
    55%  { left: 150%; }
    100% { left: 150%; }
}

/* ── Segmento cores ── */
.rk-seg-embaixador     { background: rgba(251,191,36,0.12); color: #fbbf24; border: 1px solid rgba(251,191,36,0.28); }
.rk-seg-veterano    { background: rgba(148,163,184,0.12); color: #94a3b8; border: 1px solid rgba(148,163,184,0.28); }
.rk-seg-regular   { background: rgba(205,124,47,0.12); color: #cd7c2f; border: 1px solid rgba(205,124,47,0.28); }
.rk-seg-novo { background: rgba(0,229,255,0.08); color: #00e5ff; border: 1px solid rgba(0,229,255,0.22); }

/* ── Tabela restante ── */
.rk-table-wrap {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    overflow: hidden;
}

.rk-table {
    width: 100%;
    border-collapse: collapse;
}

.rk-table thead th {
    padding: 11px 16px;
    text-align: left;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: #52525b;
    background: rgba(255,255,255,0.02);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    white-space: nowrap;
}
.rk-table thead th.rk-th-c { text-align: center; }

.rk-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.12s;
    animation: rk-fadein 0.35s ease both;
}
.rk-table tbody tr:last-child { border-bottom: none; }
.rk-table tbody tr:hover { background: rgba(255,255,255,0.02); }

@keyframes rk-fadein {
    from { opacity: 0; transform: translateX(-6px); }
    to   { opacity: 1; transform: translateX(0); }
}
<?php foreach(range(1,17) as $d): ?>
.rk-table tbody tr:nth-child(<?= $d ?>){ animation-delay: <?= $d * 0.04 ?>s; }
<?php endforeach; ?>

.rk-table td { padding: 12px 16px; vertical-align: middle; }

.rk-td-pos { width: 52px; text-align: center; }
.rk-pos-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px; height: 28px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 800;
    background: rgba(255,255,255,0.04);
    color: #71717a;
    border: 1px solid rgba(255,255,255,0.06);
}

.rk-ouvinte-cell { display: flex; align-items: center; gap: 10px; }
.rk-mini-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 900;
    color: #050510;
    flex-shrink: 0;
    font-family: monospace;
}
.rk-ouvinte-nome {
    font-size: 13px; font-weight: 600; color: #e4e4e7;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 180px;
}
.rk-ouvinte-link { text-decoration: none; }
.rk-ouvinte-link:hover .rk-ouvinte-nome { color: #00e5ff; }

.rk-seg-pill {
    display: inline-block;
    padding: 2px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

.rk-td-pts { text-align: center; white-space: nowrap; }
.rk-pts-val { font-size: 15px; font-weight: 900; color: #00e5ff; }

.rk-td-bar { min-width: 80px; }
.rk-bar-wrap { position: relative; }
.rk-bar-bg {
    height: 6px;
    background: rgba(255,255,255,0.05);
    border-radius: 3px;
    overflow: hidden;
}
.rk-bar-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.6s cubic-bezier(.22,1,.36,1);
}
.rk-bar-val { font-size: 11px; font-weight: 700; color: #a1a1aa; margin-top: 3px; }

.rk-td-num { text-align: center; font-size: 14px; font-weight: 700; color: #e4e4e7; }
.rk-zero { color: #3f3f46; font-weight: 400; }

.rk-vitoria-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(251,191,36,0.1);
    border: 1px solid rgba(251,191,36,0.22);
    color: #fbbf24;
    border-radius: 20px;
    padding: 3px 9px;
    font-size: 11px; font-weight: 800;
}

/* ── Empty ── */
.rk-empty {
    text-align: center;
    padding: 80px 32px;
    color: #52525b;
}
.rk-empty-icon { font-size: 52px; opacity: 0.25; margin-bottom: 16px; }
.rk-empty-title { font-size: 16px; font-weight: 600; color: #71717a; margin-bottom: 6px; }
.rk-empty-sub { font-size: 13px; }

/* ── Responsive ── */
@media (max-width: 700px) {
    .rk-podium { padding: 0; }
    .rk-podium-mini-stats { gap: 8px; }
    .rk-table .rk-col-lidas,
    .rk-table .rk-col-part  { display: none; }
    .rk-td-bar { min-width: 50px; }
}
@media (max-width: 480px) {
    .rk-table .rk-col-seg { display: none; }
}
</style>

<!-- FILTROS DE PERÍODO -->
<div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem">
    <?php foreach(['semana'=>'Esta Semana','semana_ant'=>'Semana Anterior','mes'=>'Este Mês','tudo'=>'Todos os Tempos'] as $f=>$lbl): ?>
    <a href="?filtro=<?= $f ?>"
       style="padding:.5rem 1.125rem;border:1px solid <?= $filtroActual===$f?'rgba(251,191,36,0.4)':'rgba(255,255,255,0.08)' ?>;border-radius:8px;background:<?= $filtroActual===$f?'rgba(251,191,36,0.1)':'transparent' ?>;color:<?= $filtroActual===$f?'#fbbf24':'#71717a' ?>;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap">
        <?= $lbl ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="rk-wrap">

    <!-- ══ Header ══════════════════════════════════════════════ -->
    <div class="rk-header">
        <div>
            <div class="rk-title">
                <span class="rk-title-icon">🏆</span>
                Ranking Semanal
            </div>
            <div class="rk-meta">
                <span class="rk-meta-item">👥 <strong><?= $total ?></strong> ouvinte<?= $total !== 1 ? 's' : '' ?> activos</span>
                <?php if ($total > 0 && ($maxParticipacoes > 0 || array_sum(array_column($ranking, 'participacoes_semana')) > 0)): ?>
                <span class="rk-meta-item">📨 <strong><?= array_sum(array_column($ranking, 'participacoes_semana')) ?></strong> participações esta semana</span>
                <?php endif; ?>
                <?php $totalVitorias = array_sum(array_column($ranking, 'vitorias_semana')); if ($totalVitorias > 0): ?>
                <span class="rk-meta-item">🏆 <strong><?= $totalVitorias ?></strong> vitórias</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="rk-periodo-pill">
            📅 <?= htmlspecialchars($periodo) ?>
        </div>
    </div>

    <?php if ($total === 0): ?>
    <!-- ══ Empty ═══════════════════════════════════════════════ -->
    <div class="rk-empty">
        <div class="rk-empty-icon">📊</div>
        <div class="rk-empty-title">Sem actividade esta semana</div>
        <div class="rk-empty-sub">Nenhum ouvinte participou ainda desde <?= date('d/m/Y', strtotime($inicio_semana)) ?></div>
    </div>

    <?php else: ?>

    <!-- ══ Pódio ════════════════════════════════════════════════ -->
    <?php if (!empty($top3)): ?>
    <div class="rk-section-label">🥇 Pódio</div>
    <div class="rk-podium">
        <?php foreach ($podiumOrder as $vi):
            if (!isset($top3[$vi])) {
                echo '<div style="flex:1;max-width:240px;"></div>';
                continue;
            }
            $o   = $top3[$vi];
            $pos = $vi + 1;
            $ini = $initials($o['nome']);
            $seg = $o['segmento'] ?? 'novo';

            // Cores por posição
            $avatarBg = match($pos) {
                1 => 'radial-gradient(135deg,#ffe066,#fbbf24,#d97706)',
                2 => 'radial-gradient(135deg,#e2e8f0,#94a3b8,#64748b)',
                3 => 'radial-gradient(135deg,#fcd9a0,#cd7c2f,#92400e)',
                default => '#3f3f46',
            };
            $ptsColor = match($pos) { 1 => '#fbbf24', 2 => '#94a3b8', 3 => '#cd7c2f', default => '#fff' };
        ?>
        <div class="rk-podium-col rk-pos-<?= $pos ?>">

            <div class="rk-avatar" style="background:<?= $avatarBg ?>">
                <?= htmlspecialchars($ini) ?>
            </div>

            <div class="rk-podium-name"><?= htmlspecialchars($o['nome']) ?></div>

            <span class="rk-seg-pill rk-seg-<?= $seg ?>">
                <?= $segLabel($seg) ?>
            </span>

            <div class="rk-podium-pts" style="color:<?= $ptsColor ?>">
                <?= number_format((int)$o['pontos']) ?>
            </div>
            <div class="rk-podium-pts-lbl">pontos acumulados</div>

            <div class="rk-podium-mini-stats">
                <div class="rk-mini-stat">
                    <div class="rk-mini-stat-val"><?= (int)$o['participacoes_semana'] ?></div>
                    <div class="rk-mini-stat-lbl">Partic.</div>
                </div>
                <div class="rk-mini-stat">
                    <div class="rk-mini-stat-val"><?= (int)$o['lidas_semana'] ?></div>
                    <div class="rk-mini-stat-lbl">Lidas</div>
                </div>
                <div class="rk-mini-stat">
                    <div class="rk-mini-stat-val"><?= (int)$o['vitorias_semana'] ?></div>
                    <div class="rk-mini-stat-lbl">Vitórias</div>
                </div>
            </div>

            <div class="rk-platform">
                <div class="rk-platform-shine"></div>
                <?= $pos ?>º
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ══ Tabela classificação completa ═══════════════════════ -->
    <?php if (!empty($resto)): ?>
    <div class="rk-section-label" style="margin-bottom:16px">📋 Classificação Completa</div>
    <div class="rk-table-wrap">
        <table class="rk-table">
            <thead>
                <tr>
                    <th class="rk-th-c">#</th>
                    <th>Ouvinte</th>
                    <th class="rk-th-c rk-col-seg">Segmento</th>
                    <th class="rk-th-c">Pontos</th>
                    <th class="rk-col-part">Participações</th>
                    <th class="rk-col-lidas">Lidas no Ar</th>
                    <th class="rk-th-c">Vitórias</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($resto as $idx => $o):
                $pos = $idx + 4;
                $ini = $initials($o['nome']);
                $seg = $o['segmento'] ?? 'novo';

                // Cor do avatar baseada no segmento
                $miniColor = match($seg) {
                    'embaixador'     => 'radial-gradient(135deg,#ffe066,#d97706)',
                    'veterano'    => 'radial-gradient(135deg,#e2e8f0,#64748b)',
                    'regular'   => 'radial-gradient(135deg,#fcd9a0,#92400e)',
                    default    => 'radial-gradient(135deg,#1e3a5f,#1d4ed8)',
                };

                $pctPontos = $maxPontos > 0 ? round(($o['pontos'] / $maxPontos) * 100) : 0;
                $pctPart   = $maxParticipacoes > 0 ? round(($o['participacoes_semana'] / $maxParticipacoes) * 100) : 0;
                $pctLidas  = $maxLidas > 0 ? round(($o['lidas_semana'] / $maxLidas) * 100) : 0;
                $tend = $o['tendencia'] ?? 'igual';
                $tendDelta = $o['tendencia_delta'] ?? 0;
            ?>
            <tr>
                <td class="rk-td-pos">
                    <span class="rk-pos-num"><?= $pos ?></span>
                    <?php if ($tend === 'subiu'): ?>
                    <div class="rk-tend-up">↑<?= $tendDelta ?></div>
                    <?php elseif ($tend === 'desceu'): ?>
                    <div class="rk-tend-down">↓<?= $tendDelta ?></div>
                    <?php elseif ($tend === 'novo'): ?>
                    <div class="rk-tend-new">NEW</div>
                    <?php else: ?>
                    <div class="rk-tend-eq">—</div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="rk-ouvinte-cell">
                        <div class="rk-mini-avatar" style="background:<?= $miniColor ?>;color:#fff">
                            <?= htmlspecialchars($ini) ?>
                        </div>
                        <div style="min-width:0">
                            <a href="/public/pulso/<?= (int)$stationId ?>/ouvintes/<?= (int)$o['id'] ?>/ficha"
                               class="rk-ouvinte-link">
                                <div class="rk-ouvinte-nome"><?= htmlspecialchars($o['nome']) ?></div>
                            </a>
                        </div>
                    </div>
                </td>
                <td class="rk-th-c rk-col-seg">
                    <span class="rk-seg-pill rk-seg-<?= $seg ?>"><?= $segLabel($seg) ?></span>
                </td>
                <td class="rk-td-pts">
                    <span class="rk-pts-val"><?= number_format((int)$o['pontos']) ?></span>
                </td>
                <td class="rk-td-bar rk-col-part">
                    <?php if ($o['participacoes_semana'] > 0): ?>
                    <div class="rk-bar-wrap">
                        <div class="rk-bar-bg">
                            <div class="rk-bar-fill" style="width:<?= $pctPart ?>%;background:#7c3aed"></div>
                        </div>
                        <div class="rk-bar-val"><?= (int)$o['participacoes_semana'] ?></div>
                    </div>
                    <?php else: ?>
                    <span class="rk-zero">—</span>
                    <?php endif; ?>
                </td>
                <td class="rk-td-bar rk-col-lidas">
                    <?php if ($o['lidas_semana'] > 0): ?>
                    <div class="rk-bar-wrap">
                        <div class="rk-bar-bg">
                            <div class="rk-bar-fill" style="width:<?= $pctLidas ?>%;background:#10b981"></div>
                        </div>
                        <div class="rk-bar-val"><?= (int)$o['lidas_semana'] ?></div>
                    </div>
                    <?php else: ?>
                    <span class="rk-zero">—</span>
                    <?php endif; ?>
                </td>
                <td class="rk-td-num">
                    <?php if ($o['vitorias_semana'] > 0): ?>
                    <span class="rk-vitoria-badge">🏆 <?= (int)$o['vitorias_semana'] ?></span>
                    <?php else: ?>
                    <span class="rk-zero">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php elseif ($total > 0 && $total <= 3): ?>
    <p style="text-align:center;color:#52525b;font-size:13px;padding:16px 0 32px">
        Apenas <?= $total ?> ouvinte<?= $total > 1 ? 's' : '' ?> com actividade esta semana.
    </p>
    <?php endif; ?>

    <?php endif; // fim !empty($ranking) ?>
</div>
