<?php
$contas     = $dados['contas']      ?? [];
$saldoTotal = $dados['saldo_total'] ?? 0;
$evolucao   = $dados['evolucao']    ?? [];
$fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';
$contaActiva = (int)($_GET['conta'] ?? ($contas[0]['id'] ?? 0));
$contaActual = null;
foreach ($contas as $c) if ($c['id'] == $contaActiva) { $contaActual = $c; break; }
if (!$contaActual && !empty($contas)) $contaActual = $contas[0];

$jMeses    = json_encode(array_column($evolucao, 'mes'));
$jEntradas = json_encode(array_map('floatval', array_column($evolucao, 'entradas')));
$jSaidas   = json_encode(array_map('floatval', array_column($evolucao, 'saidas')));
?>
<style>
.fpcc-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.fpcc-saldo-total{background:linear-gradient(135deg,rgba(0,229,255,.08),rgba(16,185,129,.05));border:1px solid rgba(0,229,255,.2);border-radius:16px;padding:1.5rem 2rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between}
.fpcc-tabs{display:flex;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap}
.fpcc-tab{padding:.75rem 1.25rem;border-radius:12px;border:1px solid var(--fp-border);background:var(--fp-bg2);cursor:pointer;text-decoration:none;transition:all .2s;min-width:180px}
.fpcc-tab:hover{border-color:rgba(0,229,255,.3);text-decoration:none}
.fpcc-tab.active{border-color:rgba(0,229,255,.4);background:rgba(0,229,255,.06)}
.fpcc-tab-banco{font-size:11px;color:var(--fp-text3);margin-bottom:3px}
.fpcc-tab-nome{font-size:13px;font-weight:700;color:var(--fp-text)}
.fpcc-tab-saldo{font-size:16px;font-weight:900;margin-top:4px}

.fpcc-layout{display:grid;grid-template-columns:1fr 340px;gap:1.25rem}
.fpcc-extrato{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden}
.fpcc-extrato-head{padding:1rem 1.5rem;border-bottom:1px solid var(--fp-border);background:var(--fp-bg3);display:flex;align-items:center;justify-content:space-between}
.fpcc-row{display:grid;grid-template-columns:90px 1fr 100px 110px 44px;gap:.75rem;padding:.75rem 1.5rem;align-items:center;border-bottom:1px solid rgba(255,255,255,.03);transition:background .12s}
.fpcc-row:last-child{border-bottom:none}
.fpcc-row:hover{background:rgba(255,255,255,.02)}
.fpcc-row-header{background:var(--fp-bg3);font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px}
.fpcc-data{font-size:11px;color:var(--fp-text2)}
.fpcc-desc{font-size:12px;font-weight:600;color:var(--fp-text)}
.fpcc-ref{font-size:10px;color:var(--fp-text3)}
.fpcc-valor{font-size:13px;font-weight:800;text-align:right}
.fpcc-saldo-linha{font-size:11px;font-weight:600;color:var(--fp-text2);text-align:right}
.fpcc-conciliar{width:24px;height:24px;border-radius:6px;border:1px solid var(--fp-border);background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--fp-text3);transition:all .15s;opacity:0}
.fpcc-row:hover .fpcc-conciliar{opacity:1}
.fpcc-conciliar:hover{border-color:var(--fp-green);color:var(--fp-green)}

.fpcc-right{display:flex;flex-direction:column;gap:1.25rem}
.fpcc-card{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden}
.fpcc-card-head{padding:1rem 1.5rem;border-bottom:1px solid var(--fp-border);font-size:13px;font-weight:700;color:var(--fp-text)}

.fpcc-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center}
.fpcc-modal-bg.open{display:flex}
.fpcc-modal{background:var(--fp-bg1);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2rem;width:90%;max-width:480px}
.fpcc-tipo-sel{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1.25rem}
.fpcc-tipo-btn{padding:.75rem;border-radius:10px;border:1px solid var(--fp-border);background:var(--fp-bg3);font-size:13px;font-weight:700;cursor:pointer;text-align:center;transition:all .15s;color:var(--fp-text2)}
.fpcc-tipo-btn.credito{border-color:var(--fp-green);background:rgba(16,185,129,.1);color:var(--fp-green)}
.fpcc-tipo-btn.debito{border-color:var(--fp-red);background:rgba(239,68,68,.1);color:var(--fp-red)}
.fpcc-field{margin-bottom:1rem}
.fpcc-field label{display:block;font-size:10px;font-weight:700;color:var(--fp-text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem}
.fpcc-input{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none;color-scheme:dark}
.fpcc-input:focus{border-color:rgba(16,185,129,.5)}
.fpcc-input::placeholder{color:var(--fp-text3)}
.fpcc-select{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none}
.fpcc-modal-footer{display:flex;gap:.75rem;margin-top:1.5rem}
.fpcc-btn-save{flex:1;padding:.875rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:14px;font-weight:800;cursor:pointer}
.fpcc-btn-cancel{flex:1;padding:.875rem;background:rgba(255,255,255,.04);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text2);font-size:14px;cursor:pointer}
@media(max-width:1100px){.fpcc-layout{grid-template-columns:1fr}}
</style>

<!-- HEADER -->
<div class="fpcc-header">
    <div>
        <div style="font-size:24px;font-weight:900;color:var(--fp-text)">Conta <span style="color:var(--fp-cyan)">Corrente</span></div>
        <div style="font-size:13px;color:var(--fp-text2);margin-top:4px">Gestão bancária e conciliação</div>
    </div>
    <a href="/public/financas/<?= $stationId ?>/exportar-pdf/extracto?conta=<?= $contaActiva ?>"
       style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:10px;color:#ef4444;font-size:13px;font-weight:700;text-decoration:none">
        <i class="bi bi-file-earmark-pdf"></i> Exportar Extracto
    </a>
    <button onclick="document.getElementById('fpcc-modal').classList.add('open')" 
            style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:var(--fp-cyan);border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;cursor:pointer">
        <i class="bi bi-plus-lg"></i> Novo Movimento
    </button>
</div>

<!-- SALDO TOTAL -->
<div class="fpcc-saldo-total">
    <div>
        <div style="font-size:11px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:.5rem">SALDO CONSOLIDADO</div>
        <div style="font-size:32px;font-weight:900;color:var(--fp-cyan)"><?= $fmtKz($saldoTotal) ?></div>
        <div style="font-size:11px;color:var(--fp-text3);margin-top:4px"><?= count($contas) ?> conta<?= count($contas)!==1?'s':'' ?> activa<?= count($contas)!==1?'s':'' ?></div>
    </div>
    <?php if (!empty($evolucao)): ?>
    <div style="width:200px;height:60px"><canvas id="fpcc-mini-chart"></canvas></div>
    <?php endif; ?>
</div>

<!-- TABS DE CONTAS -->
<div class="fpcc-tabs">
    <?php foreach($contas as $c):
        $isActive = $c['id'] == ($contaActual['id'] ?? 0);
        $saldoCor = $c['saldo_atual'] >= 0 ? 'var(--fp-green)' : 'var(--fp-red)';
    ?>
    <a href="/public/financas/<?= $stationId ?>/conta-corrente?conta=<?= $c['id'] ?>"
       class="fpcc-tab <?= $isActive ? 'active' : '' ?>">
        <div class="fpcc-tab-banco">🏦 <?= htmlspecialchars($c['banco'] ?? 'Banco') ?></div>
        <div class="fpcc-tab-nome"><?= htmlspecialchars($c['nome']) ?></div>
        <div class="fpcc-tab-saldo" style="color:<?= $saldoCor ?>"><?= $fmtKz($c['saldo_atual']) ?></div>
    </a>
    <?php endforeach; ?>
</div>

<!-- LAYOUT PRINCIPAL -->
<?php if ($contaActual): ?>
<div class="fpcc-layout">

    <!-- EXTRATO -->
    <div class="fpcc-extrato">
        <div class="fpcc-extrato-head">
            <span style="font-size:13px;font-weight:700;color:var(--fp-text)">
                📋 Extrato — <?= htmlspecialchars($contaActual['nome']) ?>
            </span>
            <div style="display:flex;align-items:center;gap:.75rem">
                <span style="font-size:11px;color:var(--fp-text3)"><?= count($contaActual['movimentos'] ?? []) ?> movimentos</span>
            </div>
        </div>

        <!-- Header da tabela -->
        <div class="fpcc-row fpcc-row-header">
            <span>Data</span>
            <span>Descrição</span>
            <span style="text-align:right">Valor</span>
            <span style="text-align:right">Saldo</span>
            <span></span>
        </div>

        <!-- Saldo inicial -->
        <div class="fpcc-row" style="background:rgba(255,255,255,.02)">
            <div class="fpcc-data">—</div>
            <div>
                <div class="fpcc-desc" style="color:var(--fp-text3)">Saldo Inicial</div>
            </div>
            <div></div>
            <div class="fpcc-saldo-linha" style="color:var(--fp-text2);font-weight:700">
                <?= $fmtKz($contaActual['saldo_inicial']) ?>
            </div>
            <div></div>
        </div>

        <?php if (!empty($contaActual['movimentos'])):
            foreach(array_reverse($contaActual['movimentos']) as $mv):
                $isCredito = $mv['tipo'] === 'credito';
                $cor = $isCredito ? 'var(--fp-green)' : 'var(--fp-red)';
                $sinal = $isCredito ? '+' : '-';
        ?>
        <div class="fpcc-row">
            <div class="fpcc-data"><?= date('d/m/Y', strtotime($mv['data_movimento'])) ?></div>
            <div>
                <div class="fpcc-desc"><?= htmlspecialchars($mv['descricao']) ?></div>
                <?php if (!empty($mv['referencia'])): ?>
                <div class="fpcc-ref"><?= htmlspecialchars($mv['referencia']) ?></div>
                <?php endif; ?>
            </div>
            <div class="fpcc-valor" style="color:<?= $cor ?>">
                <?= $sinal ?><?= $fmtKz($mv['valor']) ?>
            </div>
            <div class="fpcc-saldo-linha">
                <?= $mv['saldo_apos'] !== null ? $fmtKz($mv['saldo_apos']) : '—' ?>
            </div>
            <div>
                <button class="fpcc-conciliar" title="Conciliar" onclick="this.style.color='var(--fp-green)';this.style.borderColor='var(--fp-green)'">
                    <i class="bi bi-check2"></i>
                </button>
            </div>
        </div>
        <?php endforeach; else: ?>
        <div class="fp-empty">
            <div class="fp-empty-icon">🏦</div>
            <div class="fp-empty-text">Nenhum movimento registado</div>
        </div>
        <?php endif; ?>
    </div>

    <!-- PAINEL DIREITO -->
    <div class="fpcc-right">

        <!-- RESUMO DA CONTA -->
        <div class="fpcc-card">
            <div class="fpcc-card-head">📊 Resumo da Conta</div>
            <div style="padding:1.25rem 1.5rem">
                <?php
                $items = [
                    ['Saldo Inicial', $fmtKz($contaActual['saldo_inicial']), 'var(--fp-text2)'],
                    ['Total Entradas', '+ '.$fmtKz($contaActual['total_creditos']), 'var(--fp-green)'],
                    ['Total Saídas', '- '.$fmtKz($contaActual['total_debitos']), 'var(--fp-red)'],
                ];
                foreach($items as $it): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.04)">
                    <span style="font-size:12px;color:var(--fp-text3)"><?= $it[0] ?></span>
                    <span style="font-size:12px;font-weight:700;color:<?= $it[2] ?>"><?= $it[1] ?></span>
                </div>
                <?php endforeach; ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:.75rem 0 0">
                    <span style="font-size:13px;font-weight:700;color:var(--fp-text)">Saldo Actual</span>
                    <span style="font-size:18px;font-weight:900;color:<?= $contaActual['saldo_atual']>=0?'var(--fp-cyan)':'var(--fp-red)' ?>">
                        <?= $fmtKz($contaActual['saldo_atual']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- GRÁFICO EVOLUÇÃO -->
        <?php if (!empty($evolucao)): ?>
        <div class="fpcc-card">
            <div class="fpcc-card-head">📈 Evolução — 6 Meses</div>
            <div style="padding:1.25rem 1.5rem">
                <canvas id="fpcc-chart" height="180"></canvas>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>
<?php endif; ?>

<!-- MODAL NOVO MOVIMENTO -->
<div class="fpcc-modal-bg" id="fpcc-modal">
    <div class="fpcc-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)">🏦 Novo Movimento Bancário</div>
            <button onclick="document.getElementById('fpcc-modal').classList.remove('open')"
                    style="background:rgba(255,255,255,.06);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>

        <input type="hidden" id="fpcc-tipo-val" value="credito">
        <div class="fpcc-tipo-sel">
            <button class="fpcc-tipo-btn credito" onclick="fpccTipo('credito',this)">
                ↑ Entrada / Crédito
            </button>
            <button class="fpcc-tipo-btn" onclick="fpccTipo('debito',this)">
                ↓ Saída / Débito
            </button>
        </div>

        <div class="fpcc-field">
            <label>Conta Bancária *</label>
            <select id="fpcc-conta" class="fpcc-select">
                <?php foreach($contas as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $c['id']==$contaActiva?'selected':'' ?>>
                    <?= htmlspecialchars($c['nome'].' — '.$fmtKz($c['saldo_atual'])) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fpcc-field">
            <label>Descrição *</label>
            <input type="text" id="fpcc-desc" class="fpcc-input" placeholder="Ex: Recebimento TPA — Fev 2026...">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
            <div class="fpcc-field">
                <label>Valor (Kz) *</label>
                <input type="text" id="fpcc-valor" class="fpcc-input" placeholder="Ex: 250.000,00">
            </div>
            <div class="fpcc-field">
                <label>Data *</label>
                <input type="date" id="fpcc-data" class="fpcc-input" value="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="fpcc-field">
            <label>Referência</label>
            <input type="text" id="fpcc-ref" class="fpcc-input" placeholder="Nº transferência, cheque...">
        </div>

        <div class="fpcc-modal-footer">
            <button class="fpcc-btn-save" onclick="fpccSalvar()">✅ Registar Movimento</button>
            <button class="fpcc-btn-cancel" onclick="document.getElementById('fpcc-modal').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const FP_SID = <?= $stationId ?>;
let fpccTipoVal = 'credito';

function fpccTipo(tipo, btn) {
    fpccTipoVal = tipo;
    document.getElementById('fpcc-tipo-val').value = tipo;
    document.querySelectorAll('.fpcc-tipo-btn').forEach(b => b.className = 'fpcc-tipo-btn');
    btn.className = 'fpcc-tipo-btn ' + tipo;
}

function fpccSalvar() {
    const btn = document.querySelector('#fpcc-modal .fpcc-btn-save');
    btn.textContent = '...'; btn.disabled = true;
    fetch('/public/financas/' + FP_SID + '/conta-corrente/movimento', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            conta_bancaria_id: document.getElementById('fpcc-conta').value,
            tipo:              fpccTipoVal,
            descricao:         document.getElementById('fpcc-desc').value,
            valor:             document.getElementById('fpcc-valor').value,
            data_movimento:    document.getElementById('fpcc-data').value,
            referencia:        document.getElementById('fpcc-ref').value,
        })
    }).then(r => r.json()).then(() => location.reload());
}

document.getElementById('fpcc-modal').addEventListener('click', e => {
    if(e.target===document.getElementById('fpcc-modal'))
        document.getElementById('fpcc-modal').classList.remove('open');
});

<?php if (!empty($evolucao)): ?>
// Gráfico principal
new Chart(document.getElementById('fpcc-chart'), {
    type: 'bar',
    data: {
        labels: <?= $jMeses ?>,
        datasets: [
            {
                label: 'Entradas',
                data: <?= $jEntradas ?>,
                backgroundColor: 'rgba(16,185,129,.35)',
                borderColor: '#10b981',
                borderWidth: 1.5,
                borderRadius: 6,
            },
            {
                label: 'Saídas',
                data: <?= $jSaidas ?>,
                backgroundColor: 'rgba(239,68,68,.25)',
                borderColor: '#ef4444',
                borderWidth: 1.5,
                borderRadius: 6,
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: '#8892a4', font: { size: 10 } } },
            tooltip: {
                backgroundColor: '#1a2233',
                callbacks: { label: ctx => '  ' + Number(ctx.raw).toLocaleString('pt-AO') + ' Kz' }
            }
        },
        scales: {
            x: { ticks:{color:'#4a5568',font:{size:9}}, grid:{color:'rgba(255,255,255,.03)'} },
            y: { ticks:{color:'#4a5568',callback:v=>(v/1000).toFixed(0)+'K'}, grid:{color:'rgba(255,255,255,.03)'}, beginAtZero:true }
        }
    }
});

// Mini chart no saldo total
new Chart(document.getElementById('fpcc-mini-chart'), {
    type: 'line',
    data: {
        labels: <?= $jMeses ?>,
        datasets: [{
            data: <?= $jEntradas ?>,
            borderColor: '#00e5ff',
            borderWidth: 2,
            pointRadius: 0,
            fill: true,
            backgroundColor: 'rgba(0,229,255,.08)',
            tension: 0.4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
        scales: { x: { display: false }, y: { display: false, beginAtZero: true } }
    }
});
<?php endif; ?>
</script>
