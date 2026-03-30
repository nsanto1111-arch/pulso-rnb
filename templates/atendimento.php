<?php
$promocoesActivas = $dados['promocoes_activas'] ?? [];
$recentes         = $dados['recentes'] ?? [];
$stats            = $dados['stats'] ?? [];

$tipoLabel = [
    'pedido_musical' => ['label'=>'🎵 Pedido Musical',  'cor'=>'#8b5cf6'],
    'participacao'   => ['label'=>'🎁 Participação',    'cor'=>'#10b981'],
    'informacao'     => ['label'=>'ℹ️ Informação',      'cor'=>'#3b82f6'],
    'reclamacao'     => ['label'=>'⚠️ Reclamação',      'cor'=>'#ef4444'],
    'sugestao'       => ['label'=>'💡 Sugestão',        'cor'=>'#f59e0b'],
    'outro'          => ['label'=>'📋 Outro',           'cor'=>'#71717a'],
];
$canalLabel = [
    'telefone'   => '📞 Telefone',
    'whatsapp'   => '💬 WhatsApp',
    'presencial' => '🏢 Presencial',
    'outro'      => '📋 Outro',
];
?>
<style>
.at-wrap{display:grid;grid-template-columns:1fr 380px;gap:1.25rem}
.at-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.at-title{font-size:22px;font-weight:800;color:#fff}
.at-subtitle{font-size:13px;color:#71717a;margin-top:3px}
.at-kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem}
.at-kpi{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:1.25rem;text-align:center}
.at-kpi-val{font-size:32px;font-weight:900;line-height:1;margin-bottom:.375rem}
.at-kpi-lbl{font-size:11px;color:#71717a;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.at-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.at-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.at-card-body{padding:1.5rem}
.at-form-group{margin-bottom:1rem}
.at-label{font-size:12px;font-weight:700;color:#a1a1aa;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:.5rem}
.at-input{width:100%;padding:.75rem 1rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:13px;outline:none;transition:border-color .2s}
.at-input:focus{border-color:rgba(0,229,255,0.4)}
.at-input::placeholder{color:#52525b}
.at-select{width:100%;padding:.75rem 1rem;background:rgba(20,20,40,0.9);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:13px;outline:none}
.at-grid2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.at-btn-buscar{padding:.75rem 1.25rem;background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);border-radius:8px;color:#00e5ff;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .2s}
.at-btn-buscar:hover{background:rgba(0,229,255,0.2)}
.at-btn-save{width:100%;padding:.875rem;background:linear-gradient(135deg,#00e5ff,#0891b2);color:#000;border:none;border-radius:10px;font-size:15px;font-weight:800;cursor:pointer;transition:all .2s;margin-top:.5rem}
.at-btn-save:hover{opacity:.9}
.at-btn-save:disabled{opacity:.5;cursor:not-allowed}
.at-resultado{background:rgba(0,0,0,0.2);border-radius:8px;margin-top:.5rem;max-height:200px;overflow-y:auto}
.at-ouv-item{display:flex;align-items:center;gap:.875rem;padding:.75rem 1rem;cursor:pointer;transition:background .15s;border-bottom:1px solid rgba(255,255,255,0.04)}
.at-ouv-item:last-child{border-bottom:none}
.at-ouv-item:hover{background:rgba(0,229,255,0.06)}
.at-ouv-item.selected{background:rgba(0,229,255,0.1);border-left:3px solid #00e5ff}
.at-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#00e5ff,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;color:#000;flex-shrink:0}
.at-novo-ouvinte{background:rgba(16,185,129,0.05);border:1px solid rgba(16,185,129,0.15);border-radius:10px;padding:1rem;margin-top:.75rem;display:none}
.at-novo-ouvinte.show{display:block}
.at-ouvinte-sel{background:rgba(0,229,255,0.06);border:1px solid rgba(0,229,255,0.2);border-radius:8px;padding:.875rem 1rem;display:none;align-items:center;gap:.875rem;margin-top:.5rem}
.at-ouvinte-sel.show{display:flex}
.at-seg{display:inline-block;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700}
.at-cronometro{font-size:28px;font-weight:900;color:#00e5ff;font-family:monospace;text-align:center;padding:.5rem}
.at-btn-cron{padding:.5rem 1rem;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;border:1px solid;transition:all .2s}
.at-rec-row{display:flex;align-items:center;gap:.875rem;padding:.75rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.at-rec-row:last-child{border-bottom:none}
.at-badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700}
.at-empty{text-align:center;padding:2rem;color:#52525b;font-size:13px}
@media(max-width:900px){.at-wrap{grid-template-columns:1fr}.at-grid2{grid-template-columns:1fr}}
</style>

<div class="at-header">
    <div>
        <div class="at-title">📞 Atendimento</div>
        <div class="at-subtitle">Registo de chamadas e participações</div>
    </div>
    <a href="/public/pulso/<?= $stationId ?>/atendimento/historico"
       style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.125rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#a1a1aa;text-decoration:none;font-size:13px;font-weight:600">
        <i class="bi bi-clock-history"></i> Histórico
    </a>
</div>

<!-- KPIs -->
<div class="at-kpis">
    <div class="at-kpi">
        <div class="at-kpi-val" style="color:#00e5ff"><?= $stats['hoje'] ?></div>
        <div class="at-kpi-lbl">Atendimentos Hoje</div>
    </div>
    <div class="at-kpi">
        <div class="at-kpi-val" style="color:#8b5cf6"><?= $stats['esta_semana'] ?></div>
        <div class="at-kpi-lbl">Esta Semana</div>
    </div>
    <div class="at-kpi">
        <div class="at-kpi-val" style="color:#10b981"><?= $stats['total'] ?></div>
        <div class="at-kpi-lbl">Total</div>
    </div>
</div>

<div class="at-wrap">
    <!-- FORMULÁRIO DE ATENDIMENTO -->
    <div>
        <div class="at-card">
            <div class="at-card-head">📞 Novo Atendimento</div>
            <div class="at-card-body">

                <!-- CRONÓMETRO -->
                <div style="background:rgba(0,0,0,0.2);border-radius:10px;padding:1rem;margin-bottom:1.25rem;text-align:center">
                    <div class="at-cronometro" id="cronometro">00:00</div>
                    <div style="display:flex;gap:.5rem;justify-content:center">
                        <button class="at-btn-cron" style="background:rgba(16,185,129,0.1);border-color:rgba(16,185,129,0.3);color:#10b981"
                                onclick="iniciarCron()" id="btnIniciar">▶ Iniciar</button>
                        <button class="at-btn-cron" style="background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.2);color:#ef4444"
                                onclick="pararCron()" id="btnParar" disabled>⏹ Parar</button>
                        <button class="at-btn-cron" style="background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.1);color:#71717a"
                                onclick="resetCron()">↺ Reset</button>
                    </div>
                </div>

                <!-- PESQUISA OUVINTE -->
                <div class="at-form-group">
                    <label class="at-label">Ouvinte</label>
                    <div style="display:flex;gap:.5rem">
                        <input type="text" id="buscaOuvinte" class="at-input"
                               placeholder="Nome ou telefone..." onkeyup="if(event.key==='Enter') buscarOuvinte()">
                        <button class="at-btn-buscar" onclick="buscarOuvinte()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                    <div class="at-resultado" id="resultadoBusca" style="display:none"></div>

                    <!-- Ouvinte seleccionado -->
                    <div class="at-ouvinte-sel" id="ouvinteSelDiv">
                        <div class="at-avatar" id="ouvinteAvatar">?</div>
                        <div style="flex:1">
                            <div style="font-size:13px;font-weight:700;color:#fff" id="ouvinteNomeDiv">—</div>
                            <div style="font-size:11px;color:#71717a" id="ouvinteTelDiv">—</div>
                        </div>
                        <button onclick="limparOuvinte()" style="background:none;border:none;color:#71717a;cursor:pointer;font-size:16px">✕</button>
                    </div>

                    <!-- Novo ouvinte -->
                    <div class="at-novo-ouvinte" id="novoOuvinteDiv">
                        <div style="font-size:12px;font-weight:700;color:#10b981;margin-bottom:.75rem">✨ Cadastrar Novo Ouvinte</div>
                        <div class="at-grid2" style="gap:.75rem">
                            <div>
                                <label class="at-label">Nome *</label>
                                <input type="text" id="novoNome" class="at-input" placeholder="Nome completo">
                            </div>
                            <div>
                                <label class="at-label">Telefone</label>
                                <input type="text" id="novoTelefone" class="at-input" placeholder="9XX XXX XXX">
                            </div>
                        </div>
                    </div>

                    <button onclick="toggleNovoOuvinte()" id="btnNovoOuvinte"
                            style="margin-top:.625rem;background:none;border:none;color:#10b981;font-size:12px;font-weight:600;cursor:pointer;padding:0">
                        + Ouvinte não cadastrado
                    </button>
                </div>

                <!-- TIPO E CANAL -->
                <div class="at-grid2">
                    <div class="at-form-group">
                        <label class="at-label">Tipo</label>
                        <select id="tipoAtend" class="at-select" onchange="toggleCampos()">
                            <option value="pedido_musical">🎵 Pedido Musical</option>
                            <option value="participacao">🎁 Participação</option>
                            <option value="informacao">ℹ️ Informação</option>
                            <option value="reclamacao">⚠️ Reclamação</option>
                            <option value="sugestao">💡 Sugestão</option>
                            <option value="outro">📋 Outro</option>
                        </select>
                    </div>
                    <div class="at-form-group">
                        <label class="at-label">Canal</label>
                        <select id="canalAtend" class="at-select">
                            <option value="telefone">📞 Telefone</option>
                            <option value="whatsapp">💬 WhatsApp</option>
                            <option value="presencial">🏢 Presencial</option>
                            <option value="outro">📋 Outro</option>
                        </select>
                    </div>
                </div>

                <!-- MÚSICA PEDIDA -->
                <div class="at-form-group" id="campMusica">
                    <label class="at-label">Música Pedida</label>
                    <input type="text" id="musicaPedida" class="at-input"
                           placeholder="Artista - Título da Música">
                </div>

                <!-- PROMOÇÃO -->
                <div class="at-form-group" id="campPromocao" style="display:none">
                    <label class="at-label">Promoção</label>
                    <select id="promocaoId" class="at-select">
                        <option value="">— Nenhuma —</option>
                        <?php foreach($promocoesActivas as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?> — <?= htmlspecialchars($p['premio']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- RESULTADO E NOTAS -->
                <div class="at-grid2">
                    <div class="at-form-group">
                        <label class="at-label">Resultado</label>
                        <select id="resultadoAtend" class="at-select">
                            <option value="atendido">✅ Atendido</option>
                            <option value="nao_atendido">❌ Não Atendido</option>
                            <option value="ocupado">📵 Ocupado</option>
                            <option value="mensagem">✉️ Mensagem</option>
                        </select>
                    </div>
                    <div class="at-form-group">
                        <label class="at-label">Atendente</label>
                        <input type="text" id="atendenteInput" class="at-input" placeholder="Nome do atendente">
                    </div>
                </div>

                <div class="at-form-group">
                    <label class="at-label">Notas</label>
                    <textarea id="notasAtend" class="at-input" rows="2" placeholder="Observações..."></textarea>
                </div>

                <button class="at-btn-save" id="btnSave" onclick="registarAtendimento()">
                    <i class="bi bi-check-lg"></i> Registar Atendimento
                </button>

                <div id="msgSucesso" style="display:none;text-align:center;padding:1rem;background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;color:#10b981;font-weight:600;margin-top:.75rem">
                    ✅ Atendimento registado com sucesso!
                </div>
            </div>
        </div>
    </div>

    <!-- ATENDIMENTOS RECENTES -->
    <div>
        <div class="at-card">
            <div class="at-card-head">
                <span>📋 Hoje</span>
                <span style="font-size:12px;color:#71717a"><?= count($recentes) ?></span>
            </div>
            <div style="padding:.75rem 1.5rem;max-height:600px;overflow-y:auto">
                <?php if (!empty($recentes)): foreach($recentes as $r):
                    $tipo = $tipoLabel[$r['tipo'] ?? 'outro'] ?? $tipoLabel['outro'];
                    $hora = date('H:i', strtotime($r['data_atendimento']));
                    $ini  = mb_strtoupper(mb_substr($r['ouvinte_nome'] ?? '?', 0, 1));
                ?>
                <div class="at-rec-row">
                    <div class="at-avatar" style="width:32px;height:32px;font-size:12px"><?= $ini ?></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;color:#fff">
                            <?= htmlspecialchars($r['ouvinte_nome'] ?? 'Desconhecido') ?>
                        </div>
                        <div style="font-size:11px;color:#71717a">
                            <?= htmlspecialchars($r['musica_pedida'] ? $r['musica_pedida'] : ($r['descricao'] ?? '')) ?>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="font-size:10px;color:#52525b"><?= $hora ?></div>
                        <span class="at-badge" style="background:<?= $tipo['cor'] ?>15;color:<?= $tipo['cor'] ?>;border:1px solid <?= $tipo['cor'] ?>25;font-size:9px">
                            <?= $tipo['label'] ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="at-empty">
                    <div style="font-size:32px;margin-bottom:.5rem;opacity:.3">📞</div>
                    <div>Nenhum atendimento hoje</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const stationId = <?= $stationId ?>;
let ouvinteSelId = null;
let cronTimer = null;
let cronSegundos = 0;
let cronActivo = false;

// CRONÓMETRO
function iniciarCron() {
    if (cronActivo) return;
    cronActivo = true;
    document.getElementById('btnIniciar').disabled = true;
    document.getElementById('btnParar').disabled = false;
    cronTimer = setInterval(() => {
        cronSegundos++;
        const m = String(Math.floor(cronSegundos/60)).padStart(2,'0');
        const s = String(cronSegundos%60).padStart(2,'0');
        document.getElementById('cronometro').textContent = m + ':' + s;
    }, 1000);
}
function pararCron() {
    clearInterval(cronTimer);
    cronActivo = false;
    document.getElementById('btnIniciar').disabled = false;
    document.getElementById('btnParar').disabled = true;
}
function resetCron() {
    pararCron();
    cronSegundos = 0;
    document.getElementById('cronometro').textContent = '00:00';
}

// PESQUISA
function buscarOuvinte() {
    const busca = document.getElementById('buscaOuvinte').value.trim();
    if (busca.length < 2) return;

    fetch(`/public/pulso/${stationId}/atendimento/buscar`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'busca=' + encodeURIComponent(busca)
    })
    .then(r => r.json())
    .then(data => {
        const div = document.getElementById('resultadoBusca');
        if (data.length === 0) {
            div.innerHTML = '<div style="padding:1rem;text-align:center;color:#52525b;font-size:13px">Nenhum ouvinte encontrado</div>';
        } else {
            div.innerHTML = data.map(o => {
                const ini = (o.nome || '?')[0].toUpperCase();
                return `<div class="at-ouv-item" onclick="seleccionarOuvinte(${o.id},'${o.nome.replace(/'/g,"\\'")}','${(o.telefone||'').replace(/'/g,"\\'")}','${o.segmento||'novo'}')">
                    <div class="at-avatar">${ini}</div>
                    <div style="flex:1">
                        <div style="font-size:13px;font-weight:600;color:#fff">${o.nome}</div>
                        <div style="font-size:11px;color:#71717a">${o.telefone || ''} · ${o.pontos} pts</div>
                    </div>
                    <span style="font-size:10px;background:rgba(255,255,255,0.06);color:#a1a1aa;padding:2px 7px;border-radius:4px">${o.segmento}</span>
                </div>`;
            }).join('');
        }
        div.style.display = 'block';
    });
}

function seleccionarOuvinte(id, nome, tel, seg) {
    ouvinteSelId = id;
    document.getElementById('ouvinteAvatar').textContent = nome[0].toUpperCase();
    document.getElementById('ouvinteNomeDiv').textContent = nome;
    document.getElementById('ouvinteTelDiv').textContent = tel || 'Sem telefone';
    document.getElementById('ouvinteSelDiv').classList.add('show');
    document.getElementById('resultadoBusca').style.display = 'none';
    document.getElementById('buscaOuvinte').value = '';
    document.getElementById('novoOuvinteDiv').classList.remove('show');
}

function limparOuvinte() {
    ouvinteSelId = null;
    document.getElementById('ouvinteSelDiv').classList.remove('show');
}

function toggleNovoOuvinte() {
    const div = document.getElementById('novoOuvinteDiv');
    div.classList.toggle('show');
    limparOuvinte();
}

function toggleCampos() {
    const tipo = document.getElementById('tipoAtend').value;
    document.getElementById('campMusica').style.display    = tipo === 'pedido_musical' ? 'block' : 'none';
    document.getElementById('campPromocao').style.display  = tipo === 'participacao' ? 'block' : 'none';
}

function registarAtendimento() {
    const btn = document.getElementById('btnSave');
    btn.disabled = true;
    btn.textContent = '...';

    const novoNome = document.getElementById('novoNome').value.trim();
    const body = new URLSearchParams({
        ouvinte_id:       ouvinteSelId || '',
        novo_nome:        novoNome,
        novo_telefone:    document.getElementById('novoTelefone').value,
        tipo:             document.getElementById('tipoAtend').value,
        canal:            document.getElementById('canalAtend').value,
        musica_pedida:    document.getElementById('musicaPedida').value,
        promocao_id:      document.getElementById('promocaoId').value,
        resultado:        document.getElementById('resultadoAtend').value,
        atendente:        document.getElementById('atendenteInput').value,
        notas:            document.getElementById('notasAtend').value,
        duracao_segundos: cronSegundos,
    });

    if (!ouvinteSelId && !novoNome) {
        alert('Selecciona um ouvinte ou preenche o nome para cadastrar um novo.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Registar Atendimento';
        return;
    }

    fetch(`/public/pulso/${stationId}/atendimento/registar`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body
    })
    .then(r => r.json())
    .then(data => {
        if (data.sucesso) {
            document.getElementById('msgSucesso').style.display = 'block';
            resetCron();
            limparOuvinte();
            document.getElementById('musicaPedida').value = '';
            document.getElementById('notasAtend').value = '';
            document.getElementById('novoNome').value = '';
            document.getElementById('novoTelefone').value = '';
            document.getElementById('novoOuvinteDiv').classList.remove('show');
            setTimeout(() => {
                document.getElementById('msgSucesso').style.display = 'none';
                window.location.reload();
            }, 2000);
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Registar Atendimento';
    });
}

// Init
toggleCampos();
</script>
