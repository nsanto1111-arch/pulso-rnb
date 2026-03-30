<?php
$aniversariantes = $dados['aniversariantes'] ?? [];
$proximos        = $dados['proximos'] ?? [];
$tipo            = $dados['tipo'] ?? 'hoje';
$label           = $dados['label'] ?? 'Hoje';
$total           = $dados['total'] ?? 0;

$segCores = ['novo'=>'#3b82f6','regular'=>'#10b981','veterano'=>'#8b5cf6','embaixador'=>'#f59e0b','inactivo'=>'#71717a'];
$meses = ['','Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
?>
<style>
.an-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.an-filtros{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem}
.an-filtro{padding:.5rem 1.125rem;border:1px solid rgba(255,255,255,0.08);border-radius:8px;background:transparent;color:#71717a;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s}
.an-filtro:hover{color:#a1a1aa;text-decoration:none}
.an-filtro.active{background:rgba(236,72,153,0.12);border-color:rgba(236,72,153,0.35);color:#ec4899}
.an-grid{display:grid;grid-template-columns:1fr 320px;gap:1.25rem}
.an-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.an-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.an-card-body{padding:1.5rem}
.an-ouv-item{display:flex;align-items:center;gap:1rem;padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.04);cursor:pointer;transition:background .15s}
.an-ouv-item:last-child{border-bottom:none}
.an-ouv-item:hover{background:rgba(236,72,153,0.04)}
.an-ouv-item.selected{background:rgba(236,72,153,0.08);border-left:3px solid #ec4899}
.an-avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#ec4899,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;color:#fff;flex-shrink:0;position:relative}
.an-cake{position:absolute;top:-6px;right:-6px;font-size:16px}
.an-checkbox{width:20px;height:20px;accent-color:#ec4899;cursor:pointer;flex-shrink:0}
.an-seg{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700}
.an-empty{text-align:center;padding:3rem;color:#52525b}
.an-prox-row{display:flex;align-items:center;gap:.875rem;padding:.75rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.an-prox-row:last-child{border-bottom:none}
.an-dias-badge{min-width:40px;text-align:center;padding:4px 8px;border-radius:8px;font-size:12px;font-weight:800}

/* SORTEIO */
.an-sortear-box{background:rgba(236,72,153,0.06);border:1px solid rgba(236,72,153,0.2);border-radius:12px;padding:1.25rem;margin-top:1rem}
.an-btn-sortear{width:100%;padding:.875rem;background:linear-gradient(135deg,#ec4899,#8b5cf6);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:800;cursor:pointer;transition:all .2s;margin-top:.875rem}
.an-btn-sortear:hover{opacity:.9;transform:translateY(-1px)}
.an-btn-sortear:disabled{opacity:.4;cursor:not-allowed;transform:none}

/* RESULTADO */
.an-resultado{display:none;text-align:center;padding:2rem;background:linear-gradient(135deg,rgba(236,72,153,0.15),rgba(139,92,246,0.05));border:2px solid rgba(236,72,153,0.4);border-radius:16px;margin-top:1rem;position:relative;overflow:hidden}
.an-resultado::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#ec4899,#8b5cf6)}
.an-res-avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#ec4899,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:900;color:#fff;margin:0 auto 1rem}
.an-confetti{position:fixed;pointer-events:none;top:0;left:0;width:100%;height:100%;z-index:9999}

@media(max-width:900px){.an-grid{grid-template-columns:1fr}}
</style>

<!-- HEADER -->
<div class="an-header">
    <div>
        <div style="font-size:22px;font-weight:800;color:#fff">🎂 Aniversários</div>
        <div style="font-size:13px;color:#71717a;margin-top:3px">
            <?= $label ?> — <?= $total ?> aniversariante<?= $total!==1?'s':'' ?>
        </div>
    </div>
</div>

<!-- FILTROS -->
<div class="an-filtros">
    <a href="?tipo=hoje"   class="an-filtro <?= $tipo==='hoje'  ?'active':'' ?>">🎂 Hoje</a>
    <a href="?tipo=7dias"  class="an-filtro <?= $tipo==='7dias' ?'active':'' ?>">📅 Próximos 7 dias</a>
    <a href="?tipo=mes"    class="an-filtro <?= $tipo==='mes'   ?'active':'' ?>">📆 Este Mês</a>
</div>

<div class="an-grid">
    <!-- LISTA + SORTEIO -->
    <div>
        <div class="an-card">
            <div class="an-card-head">
                <span>🎂 Aniversariantes — <?= $label ?></span>
                <span style="font-size:12px;color:#71717a"><?= $total ?></span>
            </div>

            <?php if (!empty($aniversariantes)): ?>
            <div id="listaAniversariantes">
                <?php foreach($aniversariantes as $o):
                    $ini    = mb_strtoupper(mb_substr($o['nome']??'?', 0, 1));
                    $seg    = $o['segmento'] ?? 'novo';
                    $segCor = $segCores[$seg] ?? '#71717a';
                    $idade  = $o['idade'] ?? '?';
                    $dataN  = !empty($o['data_nascimento']) ? date('d/m', strtotime($o['data_nascimento'])) : '';
                ?>
                <div class="an-ouv-item" id="ouv-<?= $o['id'] ?>" onclick="toggleSeleccionar(<?= $o['id'] ?>)">
                    <input type="checkbox" class="an-checkbox" id="chk-<?= $o['id'] ?>"
                           value="<?= $o['id'] ?>" onclick="event.stopPropagation()">
                    <div class="an-avatar">
                        <?= $ini ?>
                        <span class="an-cake">🎂</span>
                    </div>
                    <div style="flex:1;min-width:0">
                        <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/ficha"
                           style="font-size:15px;font-weight:700;color:#fff;text-decoration:none"
                           onclick="event.stopPropagation()">
                            <?= htmlspecialchars($o['nome']) ?>
                        </a>
                        <div style="font-size:12px;color:#71717a;margin-top:2px">
                            <?= htmlspecialchars($o['telefone'] ?? '') ?>
                            <?php if ($dataN): ?>· <?= $dataN ?><?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="font-size:22px;font-weight:900;color:#ec4899"><?= $idade ?></div>
                        <div style="font-size:10px;color:#71717a">anos</div>
                        <span class="an-seg" style="background:<?= $segCor ?>18;color:<?= $segCor ?>;border:1px solid <?= $segCor ?>30">
                            <?= ucfirst($seg) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- SORTEIO -->
            <div style="padding:1.25rem 1.5rem">
                <div class="an-sortear-box">
                    <div style="font-size:13px;font-weight:700;color:#ec4899;margin-bottom:.5rem">🎁 Sortear Prémio</div>
                    <div style="font-size:12px;color:#a1a1aa;margin-bottom:.875rem">Selecciona os aniversariantes que participam no sorteio</div>
                    <input type="text" id="premioInput"
                           style="width:100%;padding:.625rem 1rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:13px;outline:none;margin-bottom:.5rem"
                           placeholder="Prémio a sortear (ex: Bolo de Aniversário)">
                    <div style="font-size:11px;color:#71717a;margin-bottom:.5rem" id="selCount">0 seleccionados</div>
                    <button class="an-btn-sortear" id="btnSortear" onclick="sortearAniversarios()" disabled>
                        🎂 Sortear Aniversariante
                    </button>
                </div>

                <!-- RESULTADO -->
                <div class="an-resultado" id="resultadoDiv">
                    <div style="font-size:40px;margin-bottom:.75rem">🎉</div>
                    <div class="an-res-avatar" id="resAvatar">?</div>
                    <div style="font-size:24px;font-weight:900;color:#fff;margin-bottom:.375rem" id="resNome">—</div>
                    <div style="font-size:13px;color:#a1a1aa;margin-bottom:.875rem" id="resTel">—</div>
                    <div style="display:inline-flex;align-items:center;gap:.5rem;padding:.5rem 1.25rem;background:rgba(236,72,153,0.1);border:1px solid rgba(236,72,153,0.3);border-radius:20px;font-size:14px;font-weight:700;color:#ec4899" id="resPremio"></div>
                </div>
            </div>

            <?php else: ?>
            <div class="an-empty">
                <div style="font-size:48px;margin-bottom:1rem;opacity:.3">🎂</div>
                <div style="font-size:15px;font-weight:600;color:#a1a1aa;margin-bottom:.5rem">
                    Nenhum aniversariante <?= $tipo==='hoje'?'hoje':($tipo==='7dias'?'nos próximos 7 dias':'este mês') ?>
                </div>
                <div style="font-size:13px">Certifica-te que os ouvintes têm data de nascimento preenchida</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PRÓXIMOS ANIVERSÁRIOS -->
    <div>
        <div class="an-card">
            <div class="an-card-head">📅 Próximos Aniversários</div>
            <div class="an-card-body" style="padding:1rem 1.5rem">
                <?php if (!empty($proximos)): foreach($proximos as $p):
                    $diasRestantes = (int)$p['dias_restantes'];
                    $isHoje  = $diasRestantes === 0;
                    $cor     = $isHoje ? '#ec4899' : ($diasRestantes <= 7 ? '#f59e0b' : '#71717a');
                    $bg      = $isHoje ? 'rgba(236,72,153,0.15)' : ($diasRestantes <= 7 ? 'rgba(245,158,11,0.1)' : 'rgba(255,255,255,0.05)');
                    $ini     = mb_strtoupper(mb_substr($p['nome']??'?', 0, 1));
                ?>
                <div class="an-prox-row">
                    <div class="an-dias-badge" style="background:<?= $bg ?>;color:<?= $cor ?>">
                        <?= $isHoje ? '🎂' : $diasRestantes ?>
                        <?php if (!$isHoje): ?><div style="font-size:9px;font-weight:600">dias</div><?php endif; ?>
                    </div>
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#ec4899,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0"><?= $ini ?></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= htmlspecialchars($p['nome']) ?>
                        </div>
                        <div style="font-size:11px;color:#71717a"><?= $p['data_fmt'] ?> · faz <?= $p['proxima_idade'] ?> anos</div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="an-empty" style="padding:1.5rem">
                    <div style="font-size:13px">Sem aniversários próximos</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- DICA -->
        <div style="background:rgba(236,72,153,0.04);border:1px solid rgba(236,72,153,0.15);border-radius:12px;padding:1.25rem;font-size:12px;color:#a1a1aa;line-height:1.7">
            <div style="font-weight:700;color:#ec4899;margin-bottom:.5rem">🎂 Como usar</div>
            Selecciona os aniversariantes que queres incluir no sorteio, define o prémio e clica em Sortear.<br><br>
            O vencedor aparece automaticamente no <strong style="color:#fff">Painel do Locutor</strong>.
        </div>
    </div>
</div>

<script>
const stationId = <?= $stationId ?>;
let seleccionados = new Set();

function toggleSeleccionar(id) {
    const chk  = document.getElementById('chk-' + id);
    const item = document.getElementById('ouv-' + id);
    chk.checked = !chk.checked;
    if (chk.checked) {
        seleccionados.add(id);
        item.classList.add('selected');
    } else {
        seleccionados.delete(id);
        item.classList.remove('selected');
    }
    actualizarContagem();
}

function actualizarContagem() {
    const n = seleccionados.size;
    document.getElementById('selCount').textContent = n + ' seleccionado' + (n !== 1 ? 's' : '');
    document.getElementById('btnSortear').disabled = n === 0;
}

function sortearAniversarios() {
    if (seleccionados.size === 0) return;
    const btn = document.getElementById('btnSortear');
    btn.disabled = true;
    btn.textContent = '🎂 A sortear...';

    const ids    = Array.from(seleccionados).join(',');
    const premio = document.getElementById('premioInput').value;

    fetch(`/public/pulso/${stationId}/aniversarios/sortear`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ouvinte_ids=${ids}&premio=${encodeURIComponent(premio)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.erro) {
            alert('Erro: ' + data.erro);
            btn.disabled = false;
            btn.textContent = '🎂 Sortear Aniversariante';
            return;
        }
        const v = data.vencedor;
        document.getElementById('resAvatar').textContent = v.nome[0].toUpperCase();
        document.getElementById('resNome').textContent   = v.nome;
        document.getElementById('resTel').textContent    = v.telefone || '';
        document.getElementById('resPremio').textContent = premio ? '🎁 ' + premio : '🎂 Parabéns!';
        document.getElementById('resultadoDiv').style.display = 'block';
        lancarConfetti();
        btn.textContent = '🎂 Sortear de Novo';
        btn.disabled = false;
    });
}

function lancarConfetti() {
    const cores = ['#ec4899','#8b5cf6','#f59e0b','#10b981','#fff','#00e5ff'];
    for (let i = 0; i < 80; i++) {
        const el = document.createElement('div');
        el.style.cssText = `position:fixed;width:${6+Math.random()*8}px;height:${6+Math.random()*8}px;background:${cores[Math.floor(Math.random()*cores.length)]};left:${Math.random()*100}vw;top:-20px;border-radius:${Math.random()>.5?'50%':'2px'};animation:fall ${2+Math.random()*2}s linear forwards;z-index:9999;opacity:1;pointer-events:none`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }
}

// CSS animação confetti
const style = document.createElement('style');
style.textContent = '@keyframes fall{from{transform:translateY(0) rotate(0)}to{transform:translateY(105vh) rotate(720deg);opacity:0}}';
document.head.appendChild(style);
</script>
