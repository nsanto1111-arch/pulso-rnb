<?php
$movimentos    = $dados['movimentos']     ?? [];
$totais        = $dados['totais']         ?? [];
$totalGeral    = $dados['total_geral']    ?? 0;
$totalPago     = $dados['total_pago']     ?? 0;
$totalPendente = $dados['total_pendente'] ?? 0;
$countVencidos = $dados['count_vencidos'] ?? 0;
$contas        = $contas ?? [];
$patrocinadores= $patrocinadores ?? [];
$tipo          = $tipo ?? 'pagar';
$fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';

$isPagar    = $tipo === 'pagar';
$corPrimary = $isPagar ? '#ef4444' : '#10b981';
$titulo     = $isPagar ? 'Contas a Pagar' : 'Contas a Receber';
$icone      = $isPagar ? '📤' : '📥';
$urlSalvar  = "/public/financas/{$stationId}/contas-movimento/salvar";

$meses_pt = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
             'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];

$filtroEstado = $_GET['estado'] ?? '';
$filtroMes    = $_GET['mes']    ?? '';

$estadoCor   = ['pendente'=>'gold','parcial'=>'blue','pago'=>'green','vencido'=>'red'];
$estadoLabel = ['pendente'=>'Pendente','parcial'=>'Parcial','pago'=>'Pago','vencido'=>'Vencido'];

$contasAnaliticas = array_filter($contas, fn($c) => $c['tipo'] === 'analitica');
?>
<style>
.fpm-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
.fpm-kpi{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:14px;padding:1.25rem 1.5rem;position:relative;overflow:hidden}
.fpm-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;border-radius:14px 14px 0 0}
.fpm-kpi.green::before{background:var(--fp-green)}
.fpm-kpi.red::before{background:var(--fp-red)}
.fpm-kpi.gold::before{background:var(--fp-gold)}
.fpm-kpi.blue::before{background:var(--fp-blue)}
.fpm-kpi-lbl{font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:.5rem}
.fpm-kpi-val{font-size:20px;font-weight:900}
.fpm-kpi-sub{font-size:10px;color:var(--fp-text3);margin-top:.375rem}

.fpm-toolbar{display:flex;align-items:center;gap:.75rem;margin-bottom:1.25rem;flex-wrap:wrap}
.fpm-filter-group{display:flex;align-items:center;gap:.375rem;background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:10px;padding:3px}
.fpm-filter-btn{padding:.375rem .875rem;border-radius:7px;font-size:11px;font-weight:600;cursor:pointer;border:none;background:none;color:var(--fp-text2);transition:all .15s}
.fpm-filter-btn.active{color:#000}
.fpm-filter-btn:hover:not(.active){background:rgba(255,255,255,.05);color:var(--fp-text)}

.fpm-card{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden}
.fpm-row{display:grid;grid-template-columns:1fr 140px 110px 110px 90px 80px 106px;gap:.75rem;padding:.875rem 1.5rem;align-items:center;border-bottom:1px solid rgba(255,255,255,.03);transition:background .12s}
.fpm-row:last-child{border-bottom:none}
.fpm-row:hover{background:rgba(255,255,255,.02)}
.fpm-row-head{background:var(--fp-bg3);font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px;padding:.625rem 1.5rem;display:grid;grid-template-columns:1fr 140px 110px 110px 90px 80px 106px;gap:.75rem}
.fpm-desc{font-size:12px;font-weight:600;color:var(--fp-text)}
.fpm-entity{font-size:10px;color:var(--fp-text3);margin-top:2px}
.fpm-venc{font-size:11px;font-weight:600}
.fpm-valor-total{font-size:13px;font-weight:800;color:var(--fp-text);text-align:right}
.fpm-valor-pago{font-size:11px;font-weight:600;text-align:right}
.fpm-progress-mini{height:4px;background:rgba(255,255,255,.06);border-radius:2px;margin-top:3px}
.fpm-actions{display:flex;align-items:center;gap:4px;opacity:0;transition:opacity .15s}
.fpm-row:hover .fpm-actions{opacity:1}
.fpm-action-btn{
    position:relative;
    width:30px;height:30px;
    border-radius:8px;
    border:1px solid;
    cursor:pointer;
    display:flex;align-items:center;justify-content:center;
    font-size:13px;
    transition:all .15s;
    flex-shrink:0;
}
.fpm-action-btn.pay{
    background:rgba(16,185,129,.08);
    border-color:rgba(16,185,129,.2);
    color:#10b981;
}
.fpm-action-btn.pay:hover{
    background:rgba(16,185,129,.18);
    border-color:rgba(16,185,129,.4);
    transform:translateY(-1px);
    box-shadow:0 4px 12px rgba(16,185,129,.2);
}
.fpm-action-btn.edit{
    background:rgba(99,102,241,.08);
    border-color:rgba(99,102,241,.2);
    color:#6366f1;
}
.fpm-action-btn.edit:hover{
    background:rgba(99,102,241,.18);
    border-color:rgba(99,102,241,.4);
    transform:translateY(-1px);
    box-shadow:0 4px 12px rgba(99,102,241,.2);
}
.fpm-action-btn.cancel{
    background:rgba(239,68,68,.06);
    border-color:rgba(239,68,68,.15);
    color:#ef4444;
}
.fpm-action-btn.cancel:hover{
    background:rgba(239,68,68,.15);
    border-color:rgba(239,68,68,.35);
    transform:translateY(-1px);
    box-shadow:0 4px 12px rgba(239,68,68,.15);
}
/* Tooltip */
.fpm-action-btn::after{
    content:attr(data-tip);
    position:absolute;
    bottom:calc(100% + 6px);
    left:50%;
    transform:translateX(-50%);
    background:#1e2535;
    border:1px solid rgba(255,255,255,.1);
    color:#f0f4ff;
    font-size:10px;
    font-weight:600;
    white-space:nowrap;
    padding:4px 8px;
    border-radius:6px;
    pointer-events:none;
    opacity:0;
    transition:opacity .15s;
    z-index:10;
}
.fpm-action-btn:hover::after{opacity:1}
.fpm-status-cancelado{
    display:inline-flex;align-items:center;gap:4px;
    font-size:9px;font-weight:700;letter-spacing:.5px;
    color:#4a5568;padding:3px 8px;
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.06);
    border-radius:20px;
}

/* MODAL */
.fpm-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);z-index:1000;align-items:center;justify-content:center}
.fpm-modal-bg.open{display:flex}
.fpm-modal{background:var(--fp-bg1);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2rem;width:90%;max-width:540px;max-height:90vh;overflow-y:auto}
.fpm-form-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.fpm-field{margin-bottom:1rem}
.fpm-field label{display:block;font-size:10px;font-weight:700;color:var(--fp-text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem}
.fpm-input{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none;color-scheme:dark}
.fpm-input:focus{border-color:rgba(16,185,129,.5)}
.fpm-input::placeholder{color:var(--fp-text3)}
.fpm-select{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none}
.fpm-modal-footer{display:flex;gap:.75rem;margin-top:1.5rem}
.fpm-btn-save{flex:1;padding:.875rem;border:none;border-radius:10px;color:#000;font-size:14px;font-weight:800;cursor:pointer;background:<?= $corPrimary ?>}
.fpm-btn-cancel{flex:1;padding:.875rem;background:rgba(255,255,255,.04);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text2);font-size:14px;cursor:pointer}
</style>

<!-- KPIs -->
<div class="fpm-kpis">
    <div class="fpm-kpi <?= $isPagar ? 'red' : 'green' ?>">
        <div class="fpm-kpi-lbl">Total <?= $isPagar ? 'a Pagar' : 'a Receber' ?></div>
        <div class="fpm-kpi-val" style="color:<?= $corPrimary ?>"><?= $fmtKz($totalPendente) ?></div>
        <div class="fpm-kpi-sub">Em aberto</div>
    </div>
    <div class="fpm-kpi gold">
        <div class="fpm-kpi-lbl">Vencidos</div>
        <div class="fpm-kpi-val" style="color:var(--fp-gold)"><?= $fmtKz($totais['vencido'] ?? 0) ?></div>
        <div class="fpm-kpi-sub"><?= $countVencidos ?> em atraso</div>
    </div>
    <div class="fpm-kpi green">
        <div class="fpm-kpi-lbl"><?= $isPagar ? 'Já Pago' : 'Já Recebido' ?></div>
        <div class="fpm-kpi-val" style="color:var(--fp-green)"><?= $fmtKz($totalPago) ?></div>
        <div class="fpm-kpi-sub">Liquidado</div>
    </div>
    <div class="fpm-kpi blue">
        <div class="fpm-kpi-lbl">Total Geral</div>
        <div class="fpm-kpi-val" style="color:var(--fp-blue)"><?= $fmtKz($totalGeral) ?></div>
        <div class="fpm-kpi-sub"><?= count($movimentos) ?> registos</div>
    </div>
</div>

<!-- TOOLBAR -->
<div class="fpm-toolbar">
    <div class="fpm-filter-group">
        <?php foreach([''=>'Todos','pendente'=>'Pendentes','vencido'=>'Vencidos','parcial'=>'Parciais','pago'=>'Pagos'] as $k=>$lbl):
            $cor = match($k) { 'pendente'=>'#f59e0b','vencido'=>'#ef4444','parcial'=>'#3b82f6','pago'=>'#10b981', default=>'#8892a4' };
        ?>
        <button class="fpm-filter-btn <?= $filtroEstado===$k?'active':'' ?>"
                style="<?= $filtroEstado===$k ? "background:{$cor}22;color:{$cor}" : '' ?>"
                onclick="fpmFiltrar('<?= $k ?>')">
            <?= $lbl ?>
        </button>
        <?php endforeach; ?>
    </div>
    <div style="margin-left:auto">
        <button onclick="document.getElementById('fpm-modal').classList.add('open')"
                style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:<?= $corPrimary ?>;border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;cursor:pointer">
            <i class="bi bi-plus-lg"></i> <?= $icone ?> Nova <?= $isPagar ? 'Conta a Pagar' : 'Conta a Receber' ?>
        </button>
    </div>
</div>

<!-- TABELA -->
<div class="fpm-card">
    <div class="fpm-row fpm-row-head">
        <span>Descrição / Entidade</span>
        <span>Vencimento</span>
        <span>Estado</span>
        <span style="text-align:right">Total</span>
        <span style="text-align:right"><?= $isPagar ? 'Pago' : 'Recebido' ?></span>
        <span>Método</span>
        <span style="text-align:center">Acções</span>
    </div>

    <?php if (!empty($movimentos)): foreach($movimentos as $m):
        $pct = $m['valor_total'] > 0 ? min(100, round($m['valor_pago'] / $m['valor_total'] * 100)) : 0;
        $estado = $m['estado'];
        $estadoCls = $estadoCor[$estado] ?? 'gray';
        $vencData = strtotime($m['data_vencimento']);
        $isVencido = $estado === 'vencido';
        $vencCor = $isVencido ? 'var(--fp-red)' : ($estado === 'pago' ? 'var(--fp-green)' : 'var(--fp-text2)');
        $metodoIcon = ['transferencia'=>'🏦','dinheiro'=>'💵','cheque'=>'📄','outro'=>'📋'][$m['metodo_pagamento']??'outro'] ?? '💰';
    ?>
    <div class="fpm-row">
        <div>
            <div class="fpm-desc"><?= htmlspecialchars($m['descricao']) ?></div>
            <div class="fpm-entity">
                <?= htmlspecialchars($m['entidade_nome'] ?? $m['patrocinador_nome'] ?? '—') ?>
                <?php if (!empty($m['documento_ref'])): ?> · <?= htmlspecialchars($m['documento_ref']) ?><?php endif; ?>
            </div>
        </div>
        <div>
            <div class="fpm-venc" style="color:<?= $vencCor ?>">
                <?= date('d/m/Y', $vencData) ?>
            </div>
            <?php if ($isVencido): ?>
            <div style="font-size:9px;color:var(--fp-red);font-weight:700">
                <?= (int)((time() - $vencData) / 86400) ?> dias em atraso
            </div>
            <?php endif; ?>
        </div>
        <div>
            <span class="fp-status <?= $estadoCls ?>"><?= $estadoLabel[$estado] ?? $estado ?></span>
        </div>
        <div style="text-align:right">
            <div class="fpm-valor-total"><?= $fmtKz($m['valor_total']) ?></div>
        </div>
        <div style="text-align:right">
            <div class="fpm-valor-pago" style="color:var(--fp-green)"><?= $fmtKz($m['valor_pago']) ?></div>
            <div class="fpm-progress-mini">
                <div style="width:<?= $pct ?>%;height:100%;background:var(--fp-green);border-radius:2px"></div>
            </div>
        </div>
        <div style="font-size:14px;text-align:center"><?= $metodoIcon ?></div>
        <div class="fpm-actions">
            <?php if ($estado !== 'pago' && $estado !== 'cancelado'): ?>
            <button class="fpm-action-btn pay"
                    data-tip="<?= $isPagar ? 'Registar Pagamento' : 'Registar Recebimento' ?>"
                    onclick="fpmAbrirBaixa(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['descricao'])) ?>', <?= $m['valor_total'] - $m['valor_pago'] ?>)">
                <i class="bi bi-check2-circle"></i>
            </button>
            <button class="fpm-action-btn edit"
                    data-tip="Editar"
                    onclick="fpmAbrirEditar(<?= $m['id'] ?>, <?= htmlspecialchars(json_encode($m)) ?>)">
                <i class="bi bi-pencil-square"></i>
            </button>
            <button class="fpm-action-btn cancel"
                    data-tip="Anular"
                    onclick="fpmCancelar(<?= $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['descricao'])) ?>')">
                <i class="bi bi-slash-circle"></i>
            </button>
            <?php elseif ($estado === 'cancelado'): ?>
            <span class="fpm-status-cancelado">⊘ Anulado</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; else: ?>
    <div class="fp-empty">
        <div class="fp-empty-icon"><?= $icone ?></div>
        <div class="fp-empty-text">Nenhum registo encontrado</div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL NOVA CONTA -->

<!-- MODAL EDITAR -->
<div class="fpm-modal-bg" id="fpm-modal-edit">
    <div class="fpm-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)">✏️ Editar Registo</div>
            <button onclick="document.getElementById('fpm-modal-edit').classList.remove('open')"
                    style="background:rgba(255,255,255,.06);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <input type="hidden" id="fpm-edit-id">
        <div class="fpm-field">
            <label>Descrição *</label>
            <input type="text" id="fpm-edit-desc" class="fpm-input">
        </div>
        <div class="fpm-form-2">
            <div class="fpm-field">
                <label>Entidade</label>
                <input type="text" id="fpm-edit-entity" class="fpm-input">
            </div>
            <div class="fpm-field">
                <label>Valor Total (Kz) *</label>
                <input type="text" id="fpm-edit-valor" class="fpm-input">
            </div>
        </div>
        <div class="fpm-form-2">
            <div class="fpm-field">
                <label>Data de Emissão</label>
                <input type="date" id="fpm-edit-emissao" class="fpm-input">
            </div>
            <div class="fpm-field">
                <label>Data de Vencimento</label>
                <input type="date" id="fpm-edit-venc" class="fpm-input">
            </div>
        </div>
        <div class="fpm-field">
            <label>Documento de Referência</label>
            <input type="text" id="fpm-edit-docref" class="fpm-input">
        </div>
        <div class="fpm-field">
            <label>Notas Internas</label>
            <input type="text" id="fpm-edit-notas" class="fpm-input" placeholder="Observações...">
        </div>
        <div style="padding:.75rem;background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.2);border-radius:10px;margin-bottom:1rem">
            <div style="font-size:11px;color:#f59e0b;font-weight:600">⚠️ Só é possível editar registos que ainda não foram pagos.</div>
        </div>
        <div class="fpm-modal-footer">
            <button class="fpm-btn-save" style="background:#3b82f6" onclick="fpmSalvarEditar()">✅ Guardar Alterações</button>
            <button class="fpm-btn-cancel" onclick="document.getElementById('fpm-modal-edit').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL ANULAR -->
<div class="fpm-modal-bg" id="fpm-modal-cancelar">
    <div class="fpm-modal" style="max-width:420px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)">🚫 Anular Registo</div>
            <button onclick="document.getElementById('fpm-modal-cancelar').classList.remove('open')"
                    style="background:rgba(255,255,255,.06);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <input type="hidden" id="fpm-cancel-id">
        <div id="fpm-cancel-desc" style="font-size:13px;color:var(--fp-text2);margin-bottom:1.25rem;padding:.75rem;background:var(--fp-bg3);border-radius:8px;font-weight:600"></div>
        <div class="fpm-field">
            <label>Motivo de Anulação</label>
            <input type="text" id="fpm-cancel-motivo" class="fpm-input" placeholder="Ex: Erro de lançamento, duplicado, acordo cancelado...">
        </div>
        <div style="padding:.75rem;background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:10px;margin-bottom:1rem">
            <div style="font-size:11px;color:var(--fp-red);font-weight:600">⚠️ Esta acção fica registada no histórico e não pode ser revertida. O registo ficará marcado como Anulado.</div>
        </div>
        <div class="fpm-modal-footer">
            <button class="fpm-btn-save" style="background:var(--fp-red)" onclick="fpmConfirmarCancelamento()">🚫 Confirmar Anulação</button>
            <button class="fpm-btn-cancel" onclick="document.getElementById('fpm-modal-cancelar').classList.remove('open')">Voltar</button>
        </div>
    </div>
</div>

<div class="fpm-modal-bg" id="fpm-modal">
    <div class="fpm-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)"><?= $icone ?> Nova <?= $titulo ?></div>
            <button onclick="document.getElementById('fpm-modal').classList.remove('open')"
                    style="background:rgba(255,255,255,.06);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <div class="fpm-field">
            <label>Descrição *</label>
            <input type="text" id="fpm-desc" class="fpm-input" placeholder="Ex: Factura servidor VPS — Abril...">
        </div>
        <div class="fpm-form-2">
            <div class="fpm-field">
                <label>Entidade / <?= $isPagar ? 'Fornecedor' : 'Cliente' ?></label>
                <input type="text" id="fpm-entity" class="fpm-input" placeholder="Nome da empresa...">
            </div>
            <?php if (!$isPagar): ?>
            <div class="fpm-field">
                <label>Patrocinador</label>
                <select id="fpm-pat" class="fpm-select">
                    <option value="">— Nenhum —</option>
                    <?php foreach($patrocinadores as $p): ?>
                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php else: ?>
            <div class="fpm-field">
                <label>Conta Contábil</label>
                <select id="fpm-conta" class="fpm-select">
                    <option value="">— Seleccionar —</option>
                    <?php foreach($contasAnaliticas as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['codigo'].' — '.$c['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
        <div class="fpm-form-2">
            <div class="fpm-field">
                <label>Valor Total (Kz) *</label>
                <input type="text" id="fpm-valor" class="fpm-input" placeholder="Ex: 150.000,00">
            </div>
            <div class="fpm-field">
                <label>Nº de Parcelas</label>
                <select id="fpm-parcelas" class="fpm-select">
                    <?php for($i=1;$i<=12;$i++): ?>
                    <option value="<?= $i ?>"><?= $i === 1 ? 'À vista' : "$i parcelas" ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div class="fpm-form-2">
            <div class="fpm-field">
                <label>Data de Emissão</label>
                <input type="date" id="fpm-emissao" class="fpm-input" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="fpm-field">
                <label>Data de Vencimento *</label>
                <input type="date" id="fpm-vencimento" class="fpm-input" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
            </div>
        </div>
        <div class="fpm-form-2">
            <div class="fpm-field">
                <label>Método de Pagamento</label>
                <select id="fpm-metodo" class="fpm-select">
                    <option value="transferencia">🏦 Transferência</option>
                    <option value="dinheiro">💵 Dinheiro</option>
                    <option value="cheque">📄 Cheque</option>
                    <option value="outro">📋 Outro</option>
                </select>
            </div>
            <div class="fpm-field">
                <label>Documento de Referência</label>
                <input type="text" id="fpm-docref" class="fpm-input" placeholder="Nº Factura, Contrato...">
            </div>
        </div>
        <div class="fpm-modal-footer">
            <button class="fpm-btn-save" onclick="fpmSalvar()">✅ Guardar</button>
            <button class="fpm-btn-cancel" onclick="document.getElementById('fpm-modal').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<!-- MODAL BAIXA -->
<div class="fpm-modal-bg" id="fpm-modal-baixa">
    <div class="fpm-modal" style="max-width:420px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)">✅ <?= $isPagar ? 'Registar Pagamento' : 'Registar Recebimento' ?></div>
            <button onclick="document.getElementById('fpm-modal-baixa').classList.remove('open')"
                    style="background:rgba(255,255,255,.06);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <input type="hidden" id="baixa-id" value="">
        <div id="baixa-desc" style="font-size:13px;color:var(--fp-text2);margin-bottom:1.25rem;padding:.75rem;background:var(--fp-bg3);border-radius:8px"></div>
        <div class="fpm-field">
            <label>Valor <?= $isPagar ? 'Pago' : 'Recebido' ?> (Kz) *</label>
            <input type="text" id="baixa-valor" class="fpm-input" placeholder="Valor desta liquidação...">
        </div>
        <div class="fpm-form-2">
            <div class="fpm-field">
                <label>Data</label>
                <input type="date" id="baixa-data" class="fpm-input" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="fpm-field">
                <label>Método</label>
                <select id="baixa-metodo" class="fpm-select">
                    <option value="transferencia">🏦 Transferência</option>
                    <option value="dinheiro">💵 Dinheiro</option>
                    <option value="cheque">📄 Cheque</option>
                </select>
            </div>
        </div>
        <div class="fpm-modal-footer">
            <button class="fpm-btn-save" onclick="fpmBaixar()">✅ Confirmar</button>
            <button class="fpm-btn-cancel" onclick="document.getElementById('fpm-modal-baixa').classList.remove('open')">Cancelar</button>
        </div>
    </div>
</div>

<script>
const FP_SID = <?= $stationId ?>;
const FPM_TIPO = '<?= $tipo ?>';

function fpmFiltrar(estado) {
    const url = new URL(window.location);
    if (estado) url.searchParams.set('estado', estado);
    else url.searchParams.delete('estado');
    window.location = url.toString();
}

function fpmSalvar() {
    const btn = document.querySelector('#fpm-modal .fpm-btn-save');
    btn.textContent = '...'; btn.disabled = true;
    fetch('/public/financas/' + FP_SID + '/contas-movimento/salvar', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            tipo:            FPM_TIPO,
            descricao:       document.getElementById('fpm-desc').value,
            entidade_nome:   document.getElementById('fpm-entity').value,
            valor_total:     document.getElementById('fpm-valor').value,
            num_parcelas:    document.getElementById('fpm-parcelas').value,
            data_emissao:    document.getElementById('fpm-emissao').value,
            data_vencimento: document.getElementById('fpm-vencimento').value,
            metodo_pagamento:document.getElementById('fpm-metodo').value,
            documento_ref:   document.getElementById('fpm-docref').value,
            patrocinador_id: document.getElementById('fpm-pat') ? document.getElementById('fpm-pat').value : '',
            conta_id:        document.getElementById('fpm-conta') ? document.getElementById('fpm-conta').value : '',
        })
    }).then(r => r.json()).then(() => location.reload());
}

function fpmAbrirBaixa(id, desc, restante) {
    document.getElementById('baixa-id').value = id;
    document.getElementById('baixa-desc').textContent = desc;
    document.getElementById('baixa-valor').value = restante.toFixed(2).replace('.', ',');
    document.getElementById('fpm-modal-baixa').classList.add('open');
}

function fpmBaixar() {
    const btn = document.querySelector('#fpm-modal-baixa .fpm-btn-save');
    btn.textContent = '...'; btn.disabled = true;
    const id = document.getElementById('baixa-id').value;
    fetch('/public/financas/' + FP_SID + '/contas-movimento/' + id + '/baixar', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            valor_pago:       document.getElementById('baixa-valor').value,
            data_pagamento:   document.getElementById('baixa-data').value,
            metodo_pagamento: document.getElementById('baixa-metodo').value,
        })
    }).then(r => r.json()).then(() => location.reload());
}

document.querySelectorAll('.fpm-modal-bg').forEach(m => {
    m.addEventListener('click', e => { if(e.target===m) m.classList.remove('open'); });
});

// ── Editar ───────────────────────────────────────────────────
function fpmAbrirEditar(id, m) {
    document.getElementById('fpm-edit-id').value      = id;
    document.getElementById('fpm-edit-desc').value    = m.descricao || '';
    document.getElementById('fpm-edit-entity').value  = m.entidade_nome || '';
    document.getElementById('fpm-edit-valor').value   = parseFloat(m.valor_total).toFixed(2).replace('.', ',');
    document.getElementById('fpm-edit-emissao').value = m.data_emissao || '';
    document.getElementById('fpm-edit-venc').value    = m.data_vencimento || '';
    document.getElementById('fpm-edit-docref').value  = m.documento_ref || '';
    document.getElementById('fpm-edit-notas').value   = m.notas || '';
    document.getElementById('fpm-modal-edit').classList.add('open');
}

function fpmSalvarEditar() {
    const btn = document.querySelector('#fpm-modal-edit .fpm-btn-save');
    btn.textContent = '...'; btn.disabled = true;
    const id = document.getElementById('fpm-edit-id').value;
    fetch('/public/financas/' + FP_SID + '/contas-movimento/' + id + '/editar', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            descricao:       document.getElementById('fpm-edit-desc').value,
            entidade_nome:   document.getElementById('fpm-edit-entity').value,
            valor_total:     document.getElementById('fpm-edit-valor').value,
            data_emissao:    document.getElementById('fpm-edit-emissao').value,
            data_vencimento: document.getElementById('fpm-edit-venc').value,
            documento_ref:   document.getElementById('fpm-edit-docref').value,
            notas:           document.getElementById('fpm-edit-notas').value,
        })
    }).then(r => r.json()).then(d => {
        if (d.sucesso) location.reload();
        else { alert(d.erro || 'Erro ao editar'); btn.textContent='✅ Guardar'; btn.disabled=false; }
    });
}

// ── Cancelar/Anular ──────────────────────────────────────────
function fpmCancelar(id, desc) {
    document.getElementById('fpm-cancel-id').value   = id;
    document.getElementById('fpm-cancel-desc').textContent = desc;
    document.getElementById('fpm-cancel-motivo').value = '';
    document.getElementById('fpm-modal-cancelar').classList.add('open');
}

function fpmConfirmarCancelamento() {
    const btn = document.querySelector('#fpm-modal-cancelar .fpm-btn-save');
    btn.textContent = '...'; btn.disabled = true;
    const id = document.getElementById('fpm-cancel-id').value;
    fetch('/public/financas/' + FP_SID + '/contas-movimento/' + id + '/cancelar', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ motivo: document.getElementById('fpm-cancel-motivo').value })
    }).then(r => r.json()).then(() => location.reload());
}
</script>
