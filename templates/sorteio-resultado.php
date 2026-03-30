<?php
$vencedores  = $resultado['vencedores'] ?? [];
$candidatos  = $resultado['candidatos'] ?? [];
$promocao    = $resultado['promocao'] ?? '';
$premio      = $resultado['premio'] ?? '';
$totalCand   = $resultado['total_candidatos'] ?? 0;
$erro        = $resultado['erro'] ?? null;
usort($candidatos, fn($a,$b) => $b['probabilidade'] <=> $a['probabilidade']);
$vencedoresIds = array_column($vencedores, 'id');
?>
<style>
.sr-back{display:inline-flex;align-items:center;gap:.5rem;color:#71717a;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:1.25rem;transition:color .2s}
.sr-back:hover{color:#fff;text-decoration:none}
.sr-erro{background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:2rem;text-align:center;color:#f87171}

/* ANIMAÇÃO */
.sr-anim{text-align:center;padding:3rem 1rem;margin-bottom:2rem}
.sr-drum{font-size:80px;animation:drum 0.5s ease-in-out infinite alternate}
@keyframes drum{from{transform:scale(1) rotate(-5deg)}to{transform:scale(1.1) rotate(5deg)}}
.sr-drum.stop{animation:none;font-size:80px}
.sr-names{font-size:28px;font-weight:900;color:#f59e0b;margin:1rem 0;min-height:44px;font-family:'JetBrains Mono',monospace}
.sr-countdown{font-size:80px;font-weight:900;color:#00e5ff;display:none}
.sr-reveal{display:none}

/* VENCEDOR */
.sr-vencedor{background:linear-gradient(135deg,rgba(245,158,11,0.15),rgba(217,119,6,0.05));border:2px solid rgba(245,158,11,0.4);border-radius:16px;padding:1.5rem;display:flex;align-items:center;gap:1.25rem;margin-bottom:1rem;position:relative;overflow:hidden;opacity:0;transform:translateY(20px);transition:all .5s ease}
.sr-vencedor.show{opacity:1;transform:translateY(0)}
.sr-vencedor::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#f59e0b,#fbbf24)}
.sr-v-avatar{width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:900;color:#000;flex-shrink:0}
.sr-v-nome{font-size:18px;font-weight:800;color:#fff;margin-bottom:.375rem}
.sr-v-sub{font-size:13px;color:#a1a1aa}
.sr-v-prob{font-size:24px;font-weight:900;color:#f59e0b}
.sr-v-prob-lbl{font-size:10px;color:#71717a;font-weight:600;text-transform:uppercase}
.sr-medalha{font-size:40px;flex-shrink:0}

/* ACÇÕES */
.sr-acoes{display:grid;grid-template-columns:repeat(3,1fr);gap:.875rem;margin:1.5rem 0}
.sr-acao{display:flex;flex-direction:column;align-items:center;gap:.5rem;padding:1.25rem;border-radius:12px;border:1px solid;cursor:pointer;transition:all .2s;text-decoration:none;font-family:inherit}
.sr-acao:hover{transform:translateY(-2px);text-decoration:none}
.sr-acao-icon{font-size:28px}
.sr-acao-label{font-size:12px;font-weight:700;text-align:center}
.sr-acao-sub{font-size:10px;text-align:center;opacity:.7}
.sr-acao-locutor{background:rgba(0,230,118,0.08);border-color:rgba(0,230,118,0.3);color:#00e676}
.sr-acao-locutor:hover{background:rgba(0,230,118,0.15);color:#00e676}
.sr-acao-sortear{background:rgba(245,158,11,0.08);border-color:rgba(245,158,11,0.3);color:#f59e0b}
.sr-acao-sortear:hover{background:rgba(245,158,11,0.15);color:#f59e0b}
.sr-acao-voltar{background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.1);color:#a1a1aa}
.sr-acao-voltar:hover{background:rgba(255,255,255,0.08);color:#fff}

/* TABELA */
.sr-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-top:1.5rem}
.sr-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.sr-table{width:100%;border-collapse:collapse;font-size:13px}
.sr-table th{padding:.75rem 1.25rem;text-align:left;font-size:11px;font-weight:700;color:#71717a;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid rgba(255,255,255,0.06)}
.sr-table td{padding:.875rem 1.25rem;border-bottom:1px solid rgba(255,255,255,0.04)}
.sr-table tr:last-child td{border-bottom:none}
.sr-table tr.vencedor td{background:rgba(245,158,11,0.05)}
.sr-bar-bg{width:80px;height:5px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden;display:inline-block}
.sr-bar-fill{height:100%;border-radius:3px;background:#00e5ff}

/* CONFETTI */
.confetti-piece{position:fixed;width:10px;height:10px;top:-20px;opacity:0;animation:confetti-fall linear forwards}
@keyframes confetti-fall{0%{opacity:1;transform:translateX(0) rotate(0)}100%{opacity:0;transform:translateX(var(--dx)) translateY(105vh) rotate(720deg)}}

@media(max-width:768px){.sr-acoes{grid-template-columns:1fr}}
</style>

<a href="/public/pulso/<?= $stationId ?>/sorteios" class="sr-back">
    <i class="bi bi-arrow-left"></i> Voltar aos Sorteios
</a>

<?php if ($erro): ?>
<div class="sr-erro">
    <div style="font-size:40px;margin-bottom:1rem">⚠️</div>
    <div style="font-size:18px;font-weight:700;margin-bottom:.5rem">Erro no Sorteio</div>
    <div><?= htmlspecialchars($erro) ?></div>
</div>

<?php else: ?>

<!-- ANIMAÇÃO DE SORTEIO -->
<div class="sr-anim" id="srAnim">
    <div class="sr-drum" id="srDrum">🥁</div>
    <div class="sr-names" id="srNames">A sortear...</div>
    <div class="sr-countdown" id="srCountdown"></div>
</div>

<!-- RESULTADO (oculto até animação terminar) -->
<div class="sr-reveal" id="srReveal">

    <!-- HERO -->
    <div style="text-align:center;margin-bottom:1.5rem">
        <div style="font-size:14px;color:#71717a;margin-bottom:.375rem"><?= htmlspecialchars($promocao) ?> · <?= $totalCand ?> candidatos</div>
        <div style="display:inline-flex;align-items:center;gap:.625rem;padding:.625rem 1.5rem;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:20px;font-size:15px;font-weight:700;color:#f59e0b">
            <i class="bi bi-trophy-fill"></i> <?= htmlspecialchars($premio) ?>
        </div>
    </div>

    <!-- VENCEDORES -->
    <?php
    $medalhas = ['🥇','🥈','🥉','4️⃣','5️⃣'];
    foreach($vencedores as $i => $v):
        $ini = mb_strtoupper(mb_substr($v['nome'], 0, 1));
    ?>
    <div class="sr-vencedor" id="venc-<?= $i ?>">
        <div class="sr-medalha"><?= $medalhas[$i] ?? '🏆' ?></div>
        <div class="sr-v-avatar"><?= $ini ?></div>
        <div style="flex:1">
            <div class="sr-v-nome"><?= htmlspecialchars($v['nome']) ?></div>
            <div class="sr-v-sub">
                <?= htmlspecialchars($v['telefone'] ?? '') ?>
                · <?= number_format($v['pontos'] ?? 0) ?> pts
                · <?= $v['dias_sem_ganhar'] ?? 0 ?> dias sem ganhar
            </div>
        </div>
        <div style="text-align:right">
            <div class="sr-v-prob"><?= $v['probabilidade'] ?? 0 ?>%</div>
            <div class="sr-v-prob-lbl">probabilidade</div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if (!empty($suspeitos)): ?>
    <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:12px;padding:1.25rem 1.5rem;margin-bottom:1.5rem">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">
            <span style="font-size:20px">⚠️</span>
            <div style="font-size:14px;font-weight:700;color:#ef4444">Atenção — Participantes Suspeitos</div>
        </div>
        <div style="font-size:13px;color:#a1a1aa;line-height:1.6">
            Os seguintes participantes têm alertas activos de fraude:
            <strong style="color:#f87171"><?= implode(', ', array_map('htmlspecialchars', $suspeitos)) ?></strong>
        </div>
        <a href="/public/pulso/<?= $stationId ?>/antifraude" style="display:inline-flex;align-items:center;gap:.5rem;margin-top:.75rem;font-size:12px;color:#ef4444;text-decoration:none;font-weight:600">
            <i class="bi bi-shield-exclamation"></i> Ver alertas de fraude
        </a>
    </div>
    <?php endif; ?>

    <!-- ACÇÕES -->
    <div class="sr-acoes">
        <button class="sr-acao sr-acao-locutor" onclick="marcarLidoNoAr()">
            <div class="sr-acao-icon">📻</div>
            <div class="sr-acao-label">Enviar ao Locutor</div>
            <div class="sr-acao-sub">Aparece no painel em directo</div>
        </button>
        <a href="/public/pulso/<?= $stationId ?>/sorteios" class="sr-acao sr-acao-sortear">
            <div class="sr-acao-icon">🔄</div>
            <div class="sr-acao-label">Novo Sorteio</div>
            <div class="sr-acao-sub">Sortear outra vez</div>
        </a>
        <a href="/public/pulso/<?= $stationId ?>/sorteios" class="sr-acao sr-acao-voltar">
            <div class="sr-acao-icon">🏠</div>
            <div class="sr-acao-label">Voltar</div>
            <div class="sr-acao-sub">Ir para sorteios</div>
        </a>
    </div>

    <!-- TABELA DE PROBABILIDADES -->
    <?php if (!empty($candidatos)): ?>
    <div class="sr-card">
        <div class="sr-card-head">
            <span>📊 Probabilidades</span>
            <span style="font-size:12px;color:#71717a"><?= count($candidatos) ?> candidatos</span>
        </div>
        <table class="sr-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ouvinte</th>
                    <th>Pontos</th>
                    <th>Dias s/ ganhar</th>
                    <th>Probabilidade</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach($candidatos as $i => $c):
                $isVenc = in_array($c['id'], $vencedoresIds);
            ?>
            <tr class="<?= $isVenc ? 'vencedor' : '' ?>">
                <td style="color:#71717a"><?= $i+1 ?></td>
                <td>
                    <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $c['id'] ?>/ficha"
                       style="font-weight:600;color:#fff;text-decoration:none"><?= htmlspecialchars($c['nome']) ?></a>
                    <?php if ($isVenc): ?>
                    <span style="font-size:10px;background:rgba(245,158,11,0.1);color:#f59e0b;padding:1px 7px;border-radius:4px;font-weight:700;margin-left:.375rem">🏆 VENCEDOR</span>
                    <?php endif; ?>
                </td>
                <td style="color:#00e5ff;font-weight:600"><?= number_format($c['pontos'] ?? 0) ?></td>
                <td style="color:#8b5cf6;font-weight:600"><?= $c['dias_sem_ganhar'] ?? 0 ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:.5rem">
                        <div class="sr-bar-bg"><div class="sr-bar-fill" style="width:<?= min(100,$c['probabilidade']*2) ?>%"></div></div>
                        <span style="font-size:12px;color:#a1a1aa;font-weight:600"><?= $c['probabilidade'] ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div><!-- /#srReveal -->

<script>
const nomes = <?= json_encode(array_column($candidatos, 'nome')) ?>;
const vencedores = <?= json_encode(array_map(fn($v) => $v['nome'], $vencedores)) ?>;
const stationId = <?= $stationId ?>;

let drumInterval;
let frame = 0;

function launchConfetti() {
    const colors = ['#f59e0b','#00e5ff','#10b981','#8b5cf6','#ef4444','#fff'];
    for (let i = 0; i < 80; i++) {
        const el = document.createElement('div');
        el.className = 'confetti-piece';
        el.style.cssText = `
            left:${Math.random()*100}vw;
            background:${colors[Math.floor(Math.random()*colors.length)]};
            border-radius:${Math.random()>0.5?'50%':'2px'};
            width:${6+Math.random()*8}px;
            height:${6+Math.random()*8}px;
            --dx:${(Math.random()-0.5)*200}px;
            animation-duration:${2+Math.random()*2}s;
            animation-delay:${Math.random()*0.5}s;
        `;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }
}

function startDrum() {
    const drum = document.getElementById('srDrum');
    const names = document.getElementById('srNames');

    drumInterval = setInterval(() => {
        if (nomes.length > 0) {
            const n = nomes[Math.floor(Math.random() * nomes.length)];
            names.textContent = n;
        }
        frame++;
    }, 80);

    // Countdown 3..2..1
    setTimeout(() => {
        clearInterval(drumInterval);
        const countdown = document.getElementById('srCountdown');
        names.style.display = 'none';
        countdown.style.display = 'block';
        drum.textContent = '🎯';

        let count = 3;
        countdown.textContent = count;
        const countInterval = setInterval(() => {
            count--;
            if (count > 0) {
                countdown.textContent = count;
            } else {
                clearInterval(countInterval);
                countdown.textContent = '🎉';
                setTimeout(revealWinner, 500);
            }
        }, 600);

    }, 3000);
}

function revealWinner() {
    document.getElementById('srAnim').style.display = 'none';
    const reveal = document.getElementById('srReveal');
    reveal.style.display = 'block';

    // Animar vencedores
    const vencs = document.querySelectorAll('.sr-vencedor');
    vencs.forEach((el, i) => {
        setTimeout(() => el.classList.add('show'), i * 300);
    });

    launchConfetti();
}

function marcarLidoNoAr() {
    const btn = event.currentTarget;
    btn.style.opacity = '.5';
    btn.style.pointerEvents = 'none';

    fetch('/pulso/api/locutor?action=notificar_sorteio&station_id=' + stationId, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            promocao: <?= json_encode($promocao) ?>,
            premio: <?= json_encode($premio) ?>,
            vencedores: <?= json_encode(array_map(fn($v) => ['nome'=>$v['nome'],'telefone'=>$v['telefone']??''], $vencedores)) ?>
        })
    })
    .then(r => r.json())
    .then(() => {
        btn.innerHTML = '<div class="sr-acao-icon">✅</div><div class="sr-acao-label">Enviado!</div><div class="sr-acao-sub">Aparece no locutor agora</div>';
        btn.style.background = 'rgba(0,230,118,0.15)';
        btn.style.opacity = '1';
    })
    .catch(() => {
        btn.style.opacity = '1';
        alert('Erro ao enviar para o locutor');
    });
}

// Iniciar animação
startDrum();
</script>

<?php endif; ?>
