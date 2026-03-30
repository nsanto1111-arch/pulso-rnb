<?php
$est = $promocao['estado'] ?? 'rascunho';
$estadoCores = [
    'rascunho'  => ['cor'=>'#71717a','label'=>'📝 Rascunho'],
    'activa'    => ['cor'=>'#10b981','label'=>'✅ Activa'],
    'encerrada' => ['cor'=>'#ef4444','label'=>'🔒 Encerrada'],
    'cancelada' => ['cor'=>'#6b7280','label'=>'❌ Cancelada'],
];
$info    = $estadoCores[$est] ?? $estadoCores['rascunho'];
$segCores = ['novo'=>'#3b82f6','regular'=>'#10b981','veterano'=>'#8b5cf6','embaixador'=>'#f59e0b','inactivo'=>'#71717a'];
$totalInscritos = count($inscritos);
?>
<style>
.pp-back{display:inline-flex;align-items:center;gap:.5rem;color:#71717a;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:1.25rem;transition:color .2s}
.pp-back:hover{color:#fff;text-decoration:none}
.pp-header{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;padding:1.5rem;margin-bottom:1.25rem}
.pp-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.25rem}
.pp-kpi{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:1.25rem;text-align:center}
.pp-kpi-val{font-size:32px;font-weight:900;line-height:1;margin-bottom:.375rem}
.pp-kpi-lbl{font-size:11px;color:#71717a;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.pp-grid{display:grid;grid-template-columns:1fr 340px;gap:1.25rem}
.pp-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.pp-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.pp-table{width:100%;border-collapse:collapse;font-size:13px}
.pp-table th{padding:.75rem 1.25rem;text-align:left;font-size:11px;font-weight:700;color:#71717a;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid rgba(255,255,255,0.06)}
.pp-table td{padding:.875rem 1.25rem;border-bottom:1px solid rgba(255,255,255,0.04);vertical-align:middle}
.pp-table tr:last-child td{border-bottom:none}
.pp-table tr:hover td{background:rgba(255,255,255,0.02)}
.pp-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#00e5ff,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#000;flex-shrink:0}
.pp-seg{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700}
.pp-btn-rem{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);color:#ef4444;cursor:pointer;transition:all .2s;font-size:13px}
.pp-btn-rem:hover{background:rgba(239,68,68,0.15);border-color:#ef4444}
.pp-empty{text-align:center;padding:3rem;color:#52525b}

/* PESQUISA */
.pp-search-wrap{padding:1.25rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06)}
.pp-search{display:flex;gap:.625rem}
.pp-search-input{flex:1;padding:.625rem 1rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:13px;outline:none;transition:border-color .2s}
.pp-search-input:focus{border-color:rgba(0,229,255,0.3)}
.pp-search-input::placeholder{color:#52525b}
.pp-search-btn{padding:.625rem 1rem;background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.25);border-radius:8px;color:#00e5ff;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap}
.pp-search-btn:hover{background:rgba(0,229,255,0.2)}
.pp-disp-list{padding:.75rem 1.5rem;max-height:400px;overflow-y:auto}
.pp-disp-row{display:flex;align-items:center;gap:.875rem;padding:.75rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.pp-disp-row:last-child{border-bottom:none}
.pp-btn-add{padding:.375rem .875rem;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.25);border-radius:7px;color:#10b981;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap}
.pp-btn-add:hover{background:rgba(16,185,129,0.2)}
.pp-btn-add:disabled{opacity:.4;cursor:not-allowed}
.pp-toast{position:fixed;bottom:2rem;right:2rem;padding:.875rem 1.5rem;border-radius:10px;font-size:14px;font-weight:600;z-index:9999;opacity:0;transition:opacity .3s;pointer-events:none}
.pp-toast.show{opacity:1}
.pp-toast.ok{background:rgba(16,185,129,0.9);color:#fff}
.pp-toast.err{background:rgba(239,68,68,0.9);color:#fff}
@media(max-width:900px){.pp-grid{grid-template-columns:1fr}}
</style>

<a href="/public/pulso/<?= $stationId ?>/promocoes" class="pp-back">
    <i class="bi bi-arrow-left"></i> Voltar às Promoções
</a>

<!-- HEADER -->
<div class="pp-header">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <div>
            <div style="font-size:20px;font-weight:800;color:#fff;margin-bottom:.5rem"><?= htmlspecialchars($promocao['nome']) ?></div>
            <div style="display:flex;align-items:center;gap:.5rem;font-size:13px;color:#f59e0b;font-weight:600">
                <i class="bi bi-trophy-fill"></i> <?= htmlspecialchars($promocao['premio'] ?? '—') ?>
            </div>
        </div>
        <div style="display:flex;gap:.625rem;align-items:center;flex-wrap:wrap">
            <span style="background:<?= $info['cor'] ?>18;color:<?= $info['cor'] ?>;border:1px solid <?= $info['cor'] ?>30;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700">
                <?= $info['label'] ?>
            </span>
            <?php if ($est === 'activa' && $totalInscritos > 0): ?>
            <a href="/public/pulso/<?= $stationId ?>/sorteios" style="display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1.125rem;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:8px;color:#f59e0b;text-decoration:none;font-size:13px;font-weight:600">
                <i class="bi bi-shuffle"></i> Realizar Sorteio
            </a>
            <?php endif; ?>
            <a href="/public/pulso/<?= $stationId ?>/promocoes/<?= $promocao['id'] ?>/editar" style="display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#a1a1aa;text-decoration:none;font-size:13px;font-weight:600">
                <i class="bi bi-pencil"></i> Editar
            </a>
        </div>
    </div>
</div>

<!-- KPIs -->
<div class="pp-kpis">
    <div class="pp-kpi">
        <div class="pp-kpi-val" style="color:#00e5ff"><?= $totalInscritos ?></div>
        <div class="pp-kpi-lbl">Inscritos</div>
    </div>
    <div class="pp-kpi">
        <div class="pp-kpi-val" style="color:#10b981"><?= $promocao['max_vencedores'] ?? 1 ?></div>
        <div class="pp-kpi-lbl">Vencedores</div>
    </div>
    <div class="pp-kpi">
        <div class="pp-kpi-val" style="color:#f59e0b"><?= $totalInscritos > 0 && ($promocao['max_participantes'] ?? 0) > 0 ? round($totalInscritos / $promocao['max_participantes'] * 100) . '%' : '—' ?></div>
        <div class="pp-kpi-lbl">Capacidade</div>
    </div>
</div>

<div class="pp-grid">

    <!-- INSCRITOS -->
    <div>
        <div class="pp-card">
            <div class="pp-card-head">
                <span>✅ Inscritos na Promoção</span>
                <span style="font-size:12px;color:#71717a"><?= $totalInscritos ?> ouvinte<?= $totalInscritos !== 1 ? 's' : '' ?></span>
            </div>

            <?php if (!empty($inscritos)): ?>
            <table class="pp-table">
                <thead>
                    <tr>
                        <th>Ouvinte</th>
                        <th>Segmento</th>
                        <th>Pontos</th>
                        <th>Inscrito em</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($inscritos as $p):
                    $ini    = mb_strtoupper(mb_substr($p['nome'] ?? '?', 0, 1));
                    $seg    = $p['segmento'] ?? 'novo';
                    $segCor = $segCores[$seg] ?? '#71717a';
                    $data   = date('d/m/Y H:i', strtotime($p['data_participacao'] ?? 'now'));
                ?>
                <tr id="inscrito-<?= $p['ouvinte_id'] ?>">
                    <td>
                        <div style="display:flex;align-items:center;gap:.875rem">
                            <div class="pp-avatar"><?= $ini ?></div>
                            <div>
                                <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $p['ouvinte_id'] ?>/ficha"
                                   style="font-weight:600;color:#fff;text-decoration:none">
                                    <?= htmlspecialchars($p['nome'] ?? '') ?>
                                </a>
                                <div style="font-size:11px;color:#71717a"><?= htmlspecialchars($p['telefone'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="pp-seg" style="background:<?= $segCor ?>18;color:<?= $segCor ?>;border:1px solid <?= $segCor ?>30">
                            <?= ucfirst($seg) ?>
                        </span>
                    </td>
                    <td style="color:#00e5ff;font-weight:600"><?= number_format($p['pontos'] ?? 0) ?></td>
                    <td style="color:#71717a;font-size:12px"><?= $data ?></td>
                    <td>
                        <button class="pp-btn-rem" title="Remover"
                                onclick="removerParticipante(<?= $p['ouvinte_id'] ?>, this)">
                            <i class="bi bi-x"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="pp-empty">
                <div style="font-size:40px;margin-bottom:.75rem;opacity:.3">👥</div>
                <div style="font-size:14px;font-weight:600;color:#a1a1aa">Nenhum inscrito ainda</div>
                <div style="font-size:12px;margin-top:.375rem">Use a pesquisa ao lado para inscrever ouvintes</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PESQUISA E INSCRIÇÃO -->
    <div>
        <div class="pp-card">
            <div class="pp-card-head">
                <span>🔍 Inscrever Ouvinte</span>
            </div>
            <div class="pp-search-wrap">
                <div class="pp-search">
                    <input type="text" id="buscaInput" class="pp-search-input"
                           placeholder="Nome ou telefone..."
                           value="<?= htmlspecialchars($busca) ?>"
                           onkeydown="if(event.key==='Enter') pesquisar()">
                    <button class="pp-search-btn" onclick="pesquisar()">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
            </div>
            <div class="pp-disp-list" id="dispList">
                <?php if (!empty($disponiveis)): foreach($disponiveis as $o):
                    $ini    = mb_strtoupper(mb_substr($o['nome'] ?? '?', 0, 1));
                    $seg    = $o['segmento'] ?? 'novo';
                    $segCor = $segCores[$seg] ?? '#71717a';
                ?>
                <div class="pp-disp-row" id="disp-<?= $o['id'] ?>">
                    <div class="pp-avatar" style="width:32px;height:32px;font-size:12px"><?= $ini ?></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:13px;font-weight:600;color:#fff"><?= htmlspecialchars($o['nome']) ?></div>
                        <div style="font-size:11px;color:#71717a"><?= htmlspecialchars($o['telefone'] ?? '') ?></div>
                    </div>
                    <button class="pp-btn-add" onclick="inscrever(<?= $o['id'] ?>, this)">
                        + Inscrever
                    </button>
                </div>
                <?php endforeach; elseif (!empty($busca)): ?>
                <div class="pp-empty" style="padding:2rem">
                    <div style="font-size:13px">Nenhum ouvinte encontrado</div>
                </div>
                <?php else: ?>
                <div style="padding:1.5rem;text-align:center;color:#52525b;font-size:13px">
                    Pesquisa um ouvinte para inscrever
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- INFO -->
        <div style="background:rgba(0,229,255,0.04);border:1px solid rgba(0,229,255,0.12);border-radius:12px;padding:1.25rem;font-size:12px;color:#a1a1aa;line-height:1.7">
            <div style="font-weight:700;color:#00e5ff;margin-bottom:.5rem">💡 Como funciona</div>
            Inscreve aqui os ouvintes que pediram para participar — por telefone, WhatsApp ou presencialmente.<br><br>
            Quando realizares o sorteio, <strong style="color:#fff">apenas os inscritos</strong> entram no pool.
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="pp-toast" id="ppToast"></div>

<script>
const stationId = <?= $stationId ?>;
const promocaoId = <?= $promocao['id'] ?>;

function showToast(msg, tipo) {
    const t = document.getElementById('ppToast');
    t.textContent = msg;
    t.className = 'pp-toast show ' + tipo;
    setTimeout(() => t.classList.remove('show'), 3000);
}

function pesquisar() {
    const busca = document.getElementById('buscaInput').value;
    window.location = `/public/pulso/${stationId}/promocoes/${promocaoId}/participantes?busca=${encodeURIComponent(busca)}`;
}

function inscrever(ouvinteId, btn) {
    btn.disabled = true;
    btn.textContent = '...';

    fetch(`/public/pulso/${stationId}/promocoes/${promocaoId}/participantes/inscrever`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ouvinte_id=' + ouvinteId
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            document.getElementById('disp-' + ouvinteId)?.remove();
            showToast('✅ ' + data.mensagem, 'ok');
            // Recarregar lista de inscritos
            setTimeout(() => window.location.reload(), 800);
        } else {
            btn.disabled = false;
            btn.textContent = '+ Inscrever';
            showToast('❌ ' + data.mensagem, 'err');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = '+ Inscrever';
        showToast('❌ Erro de ligação', 'err');
    });
}

function removerParticipante(ouvinteId, btn) {
    if (!confirm('Remover este ouvinte da promoção?')) return;
    btn.style.opacity = '.5';

    fetch(`/public/pulso/${stationId}/promocoes/${promocaoId}/participantes/remover`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ouvinte_id=' + ouvinteId
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            document.getElementById('inscrito-' + ouvinteId)?.remove();
            showToast('✅ Removido', 'ok');
            setTimeout(() => window.location.reload(), 800);
        }
    });
}
</script>
