<?php
$listeners   = $dados['listeners'] ?? ['total'=>0,'unique'=>0,'current'=>0];
$nowPlaying  = $dados['now_playing'] ?? [];
$playingNext = $dados['playing_next'] ?? [];
$live        = $dados['live'] ?? [];
$mounts      = $dados['mounts'] ?? [];
$historico   = $dados['historico_listeners'] ?? [];
$topMusicas  = $dados['top_musicas'] ?? [];
$listenUrl   = $dados['listen_url'] ?? '';
$erro        = $dados['erro'] ?? null;

$duracao  = $nowPlaying['duracao'] ?? 0;
$elapsed  = $nowPlaying['elapsed'] ?? 0;
$pctPlay  = $duracao > 0 ? min(100, round($elapsed / $duracao * 100)) : 0;
$remaining = max(0, $duracao - $elapsed);

$jHoras     = json_encode(array_column($historico, 'hora'));
$jListeners = json_encode(array_map('intval', array_column($historico, 'listeners_total')));
?>
<style>
.st-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.st-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.st-kpi{border-radius:14px;padding:1.5rem;border:1px solid;position:relative;overflow:hidden;text-align:center}
.st-kpi-bg{position:absolute;top:-15px;right:-15px;font-size:70px;opacity:.07}
.st-kpi-val{font-size:40px;font-weight:900;line-height:1;margin-bottom:.375rem}
.st-kpi-lbl{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.st-grid{display:grid;grid-template-columns:1fr 360px;gap:1.25rem}
.st-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.st-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.st-card-body{padding:1.5rem}
.st-now{display:flex;align-items:center;gap:1.25rem;padding:1.5rem}
.st-art{width:80px;height:80px;border-radius:12px;object-fit:cover;flex-shrink:0;background:rgba(255,255,255,0.06)}
.st-art-placeholder{width:80px;height:80px;border-radius:12px;background:linear-gradient(135deg,#00e5ff20,#8b5cf620);border:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;font-size:32px;flex-shrink:0}
.st-progress{height:4px;background:rgba(255,255,255,0.08);border-radius:2px;overflow:hidden;margin-top:.75rem}
.st-progress-fill{height:100%;background:linear-gradient(90deg,#00e5ff,#8b5cf6);border-radius:2px;transition:width 1s linear}
.st-live-badge{display:inline-flex;align-items:center;gap:.375rem;padding:4px 12px;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.4);border-radius:20px;font-size:12px;font-weight:700;color:#ef4444}
.st-live-dot{width:8px;height:8px;border-radius:50%;background:#ef4444;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
.st-hist-row{display:flex;align-items:center;gap:.875rem;padding:.75rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.st-hist-row:last-child{border-bottom:none}
.st-mount{display:flex;align-items:center;justify-content:space-between;padding:.875rem;background:rgba(0,0,0,0.15);border-radius:10px;margin-bottom:.75rem}
.st-refresh{display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.25);border-radius:8px;color:#00e5ff;font-size:12px;font-weight:600;cursor:pointer;border:none;transition:all .2s}
.st-refresh:hover{background:rgba(0,229,255,0.2)}
@media(max-width:900px){.st-kpis{grid-template-columns:repeat(2,1fr)}.st-grid{grid-template-columns:1fr}}
</style>

<div class="st-header">
    <div>
        <div style="font-size:22px;font-weight:800;color:#fff">📡 Stream Analytics</div>
        <div style="font-size:13px;color:#71717a;margin-top:3px">Monitorização em tempo real · Actualiza a cada 10 segundos</div>
    </div>
    <div style="display:flex;align-items:center;gap:.875rem">
        <?php if ($live['is_live'] ?? false): ?>
        <div class="st-live-badge">
            <div class="st-live-dot"></div>
            AO VIVO — <?= htmlspecialchars($live['streamer_name'] ?? '') ?>
        </div>
        <?php endif; ?>
        <button class="st-refresh" onclick="window.location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
    </div>
</div>

<!-- KPIs -->
<div class="st-kpis">
    <div class="st-kpi" style="background:rgba(0,229,255,0.08);border-color:rgba(0,229,255,0.25)">
        <div class="st-kpi-bg">👥</div>
        <div class="st-kpi-val" style="color:#00e5ff" id="listenersTotal"><?= $listeners['total'] ?></div>
        <div class="st-kpi-lbl" style="color:#00e5ff">Ouvintes Agora</div>
    </div>
    <div class="st-kpi" style="background:rgba(139,92,246,0.08);border-color:rgba(139,92,246,0.25)">
        <div class="st-kpi-bg">🎯</div>
        <div class="st-kpi-val" style="color:#8b5cf6" id="listenersUnique"><?= $listeners['unique'] ?></div>
        <div class="st-kpi-lbl" style="color:#8b5cf6">Únicos</div>
    </div>
    <div class="st-kpi" style="background:rgba(16,185,129,0.08);border-color:rgba(16,185,129,0.25)">
        <div class="st-kpi-bg">📻</div>
        <div class="st-kpi-val" style="color:#10b981"><?= count($mounts) ?></div>
        <div class="st-kpi-lbl" style="color:#10b981">Streams Activos</div>
    </div>
    <div class="st-kpi" style="background:rgba(245,158,11,0.08);border-color:rgba(245,158,11,0.25)">
        <div class="st-kpi-bg">🎵</div>
        <div class="st-kpi-val" style="color:#f59e0b"><?= count($topMusicas) ?></div>
        <div class="st-kpi-lbl" style="color:#f59e0b">Músicas Hoje</div>
    </div>
</div>

<div class="st-grid">
    <!-- ESQUERDA -->
    <div>
        <!-- NOW PLAYING -->
        <div class="st-card">
            <div class="st-card-head">
                <span>🎵 A Tocar Agora</span>
                <span style="font-size:11px;color:#71717a;font-family:monospace" id="tempoRestante">
                    <?= gmdate('i:s', $remaining) ?>
                </span>
            </div>
            <div class="st-now">
                <?php if (!empty($nowPlaying['art'])): ?>
                <img src="<?= htmlspecialchars($nowPlaying['art']) ?>" class="st-art" id="nowArt">
                <?php else: ?>
                <div class="st-art-placeholder">🎵</div>
                <?php endif; ?>
                <div style="flex:1;min-width:0">
                    <div style="font-size:18px;font-weight:800;color:#fff;margin-bottom:.25rem" id="nowTitle">
                        <?= htmlspecialchars($nowPlaying['titulo'] ?? '—') ?>
                    </div>
                    <div style="font-size:14px;color:#71717a;margin-bottom:.75rem" id="nowArtist">
                        <?= htmlspecialchars($nowPlaying['artista'] ?? '—') ?>
                    </div>
                    <?php if (!empty($nowPlaying['playlist'])): ?>
                    <span style="font-size:11px;background:rgba(139,92,246,0.1);color:#8b5cf6;padding:2px 8px;border-radius:4px;font-weight:600">
                        <?= htmlspecialchars($nowPlaying['playlist']) ?>
                    </span>
                    <?php endif; ?>
                    <div class="st-progress">
                        <div class="st-progress-fill" id="progressBar" style="width:<?= $pctPlay ?>%"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:#52525b;margin-top:4px">
                        <span id="elapsed"><?= gmdate('i:s', $elapsed) ?></span>
                        <span><?= gmdate('i:s', $duracao) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- A SEGUIR -->
        <?php if (!empty($playingNext['titulo']) && $playingNext['titulo'] !== '—'): ?>
        <div class="st-card">
            <div class="st-card-head">⏭️ A Seguir</div>
            <div style="display:flex;align-items:center;gap:1rem;padding:1.25rem 1.5rem">
                <?php if (!empty($playingNext['art'])): ?>
                <img src="<?= htmlspecialchars($playingNext['art']) ?>" style="width:50px;height:50px;border-radius:8px;object-fit:cover">
                <?php else: ?>
                <div style="width:50px;height:50px;border-radius:8px;background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;font-size:20px">🎵</div>
                <?php endif; ?>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#fff"><?= htmlspecialchars($playingNext['titulo']) ?></div>
                    <div style="font-size:12px;color:#71717a"><?= htmlspecialchars($playingNext['artista'] ?? '') ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- GRÁFICO HISTÓRICO -->
        <div class="st-card">
            <div class="st-card-head">
                <span>📈 Ouvintes — Últimas 24h</span>
                <span style="font-size:11px;color:#71717a"><?= count($historico) ?> registos</span>
            </div>
            <div class="st-card-body">
                <?php if (!empty($historico)): ?>
                <canvas id="streamChart" height="150"></canvas>
                <?php else: ?>
                <div style="text-align:center;padding:2rem;color:#52525b;font-size:13px">
                    <div style="font-size:32px;margin-bottom:.5rem;opacity:.3">📊</div>
                    Os dados acumulam com o tempo
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- DIREITA -->
    <div>
        <!-- MOUNTS -->
        <div class="st-card">
            <div class="st-card-head">📻 Streams</div>
            <div class="st-card-body">
                <?php foreach($mounts as $m): ?>
                <div class="st-mount">
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#fff"><?= htmlspecialchars($m['name'] ?? '') ?></div>
                        <div style="font-size:11px;color:#71717a"><?= $m['bitrate'] ?? '' ?>kbps <?= strtoupper($m['format'] ?? '') ?></div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:18px;font-weight:900;color:#00e5ff"><?= $m['listeners']['current'] ?? 0 ?></div>
                        <div style="font-size:10px;color:#71717a">ouvintes</div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($listenUrl)): ?>
                <a href="<?= htmlspecialchars($listenUrl) ?>" target="_blank"
                   style="display:flex;align-items:center;gap:.5rem;padding:.75rem;background:rgba(0,229,255,0.06);border:1px solid rgba(0,229,255,0.15);border-radius:8px;color:#00e5ff;text-decoration:none;font-size:12px;font-weight:600;margin-top:.75rem">
                    <i class="bi bi-play-circle-fill"></i> Ouvir Stream
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- HISTÓRICO DE MÚSICAS -->
        <div class="st-card">
            <div class="st-card-head">
                <span>🎶 Tocadas Recentemente</span>
                <span style="font-size:11px;color:#71717a"><?= count($topMusicas) ?></span>
            </div>
            <div style="padding:.75rem 1.5rem">
                <?php if (!empty($topMusicas)): foreach($topMusicas as $i => $m): ?>
                <div class="st-hist-row">
                    <div style="font-size:11px;color:#52525b;font-family:monospace;flex-shrink:0;width:36px"><?= $m['tocou'] ?></div>
                    <?php if (!empty($m['art'])): ?>
                    <img src="<?= htmlspecialchars($m['art']) ?>" style="width:32px;height:32px;border-radius:6px;object-fit:cover;flex-shrink:0">
                    <?php else: ?>
                    <div style="width:32px;height:32px;border-radius:6px;background:rgba(255,255,255,0.06);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">🎵</div>
                    <?php endif; ?>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:600;color:#a1a1aa;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= htmlspecialchars($m['titulo'] ?? '') ?>
                        </div>
                        <div style="display:flex;gap:.375rem;margin-top:2px">
                            <?php if (!empty($m['energy'])): $ec = ['Up'=>'#10b981','Down'=>'#3b82f6','Heavy'=>'#ef4444'][$m['energy']] ?? '#71717a'; ?>
                            <span style="font-size:9px;background:<?= $ec ?>18;color:<?= $ec ?>;padding:1px 5px;border-radius:3px;font-weight:700"><?= $m['energy'] ?></span>
                            <?php endif; ?>
                            <?php if (!empty($m['humor'])): ?>
                            <span style="font-size:9px;background:rgba(139,92,246,0.1);color:#8b5cf6;padding:1px 5px;border-radius:3px"><?= htmlspecialchars($m['humor']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div style="text-align:center;padding:1.5rem;color:#52525b;font-size:13px">Sem histórico</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Gráfico histórico
<?php if (!empty($historico)): ?>
const ctx = document.getElementById('streamChart');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= $jHoras ?>,
            datasets: [{
                label: 'Ouvintes',
                data: <?= $jListeners ?>,
                borderColor: '#00e5ff',
                backgroundColor: 'rgba(0,229,255,0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 2,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks:{color:'#71717a',font:{size:10}}, grid:{color:'rgba(255,255,255,0.04)'} },
                y: { ticks:{color:'#71717a'}, grid:{color:'rgba(255,255,255,0.04)'}, beginAtZero:true }
            }
        }
    });
}
<?php endif; ?>

// Auto-refresh a cada 10 segundos
let countdown = 10;
const refreshInterval = setInterval(() => {
    countdown--;
    if (countdown <= 0) {
        clearInterval(refreshInterval);
        // Actualizar dados via API
        fetch('/pulso/api/stream/<?= $stationId ?>')
            .then(r => r.json())
            .then(data => {
                // Actualizar KPIs
                document.getElementById('listenersTotal').textContent = data.listeners?.total ?? 0;
                document.getElementById('listenersUnique').textContent = data.listeners?.unique ?? 0;
                // Actualizar now playing
                if (data.now_playing) {
                    document.getElementById('nowTitle').textContent  = data.now_playing.titulo || '—';
                    document.getElementById('nowArtist').textContent = data.now_playing.artista || '—';
                }
                countdown = 10;
            })
            .catch(() => {});
    }
}, 1000);

// Barra de progresso em tempo real
let elapsed = <?= $elapsed ?>;
const duracao = <?= $duracao ?>;
setInterval(() => {
    if (duracao > 0) {
        elapsed++;
        const pct = Math.min(100, Math.round(elapsed / duracao * 100));
        const bar = document.getElementById('progressBar');
        if (bar) bar.style.width = pct + '%';
        const rem = Math.max(0, duracao - elapsed);
        const el  = document.getElementById('elapsed');
        const tr  = document.getElementById('tempoRestante');
        if (el) el.textContent = new Date(elapsed*1000).toISOString().substr(14,5);
        if (tr) tr.textContent = new Date(rem*1000).toISOString().substr(14,5);
    }
}, 1000);
</script>
