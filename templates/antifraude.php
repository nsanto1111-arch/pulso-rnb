<?php
$alertas    = $dados['alertas'] ?? [];
$bloqueados = $dados['bloqueados'] ?? [];
$stats      = $dados['stats'] ?? [];
$historico  = $dados['historico'] ?? [];

$sevInfo = [
    'alta'  => ['cor'=>'#ef4444','bg'=>'rgba(239,68,68,0.08)','borda'=>'rgba(239,68,68,0.25)','icon'=>'bi-exclamation-octagon-fill','label'=>'Alta'],
    'media' => ['cor'=>'#f59e0b','bg'=>'rgba(245,158,11,0.08)','borda'=>'rgba(245,158,11,0.25)','icon'=>'bi-exclamation-triangle-fill','label'=>'Média'],
    'baixa' => ['cor'=>'#71717a','bg'=>'rgba(113,113,122,0.08)','borda'=>'rgba(113,113,122,0.2)','icon'=>'bi-info-circle-fill','label'=>'Baixa'],
];

$tipoLabel = [
    'ip_duplicado' => '🌐 IP Duplicado',
    'spam'         => '⚡ Spam',
    'multi_conta'  => '👥 Multi-Conta',
    'nome_similar' => '🔤 Nome Similar',
    'suspeito'     => '⚠️ Suspeito',
];
?>
<style>
.af-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.af-title{font-size:22px;font-weight:800;color:#fff}
.af-subtitle{font-size:13px;color:#71717a;margin-top:3px}
.af-kpis{display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.5rem}
.af-kpi{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:1.25rem;text-align:center}
.af-kpi-val{font-size:32px;font-weight:900;line-height:1;margin-bottom:.375rem}
.af-kpi-lbl{font-size:11px;color:#71717a;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.af-grid{display:grid;grid-template-columns:1fr 380px;gap:1.25rem}
.af-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.af-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.af-alerta{padding:1.25rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.04);display:flex;gap:1rem;align-items:flex-start}
.af-alerta:last-child{border-bottom:none}
.af-alerta:hover{background:rgba(255,255,255,0.02)}
.af-alerta-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.af-alerta-tipo{font-size:13px;font-weight:700;margin-bottom:.25rem}
.af-alerta-desc{font-size:12px;color:#a1a1aa;line-height:1.5;margin-bottom:.625rem}
.af-alerta-meta{display:flex;align-items:center;gap:.625rem;flex-wrap:wrap}
.af-sev{display:inline-block;padding:2px 8px;border-radius:20px;font-size:10px;font-weight:700}
.af-alerta-actions{display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap}
.af-btn{display:inline-flex;align-items:center;gap:.375rem;padding:.4rem .875rem;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;border:1px solid;text-decoration:none}
.af-btn-resolver{background:rgba(16,185,129,0.08);color:#10b981;border-color:rgba(16,185,129,0.25)}
.af-btn-resolver:hover{background:rgba(16,185,129,0.15)}
.af-btn-bloquear{background:rgba(239,68,68,0.08);color:#ef4444;border-color:rgba(239,68,68,0.2)}
.af-btn-bloquear:hover{background:rgba(239,68,68,0.15)}
.af-btn-ver{background:rgba(255,255,255,0.04);color:#a1a1aa;border-color:rgba(255,255,255,0.08)}
.af-btn-ver:hover{background:rgba(255,255,255,0.08);color:#fff;text-decoration:none}
.af-btn-desbloquear{background:rgba(16,185,129,0.08);color:#10b981;border-color:rgba(16,185,129,0.2)}
.af-btn-desbloquear:hover{background:rgba(16,185,129,0.15)}

/* BLOQUEADOS */
.af-bloq-row{display:flex;align-items:center;gap:.875rem;padding:.875rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.04)}
.af-bloq-row:last-child{border-bottom:none}
.af-bloq-avatar{width:36px;height:36px;border-radius:50%;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#ef4444;flex-shrink:0}

/* MODAL */
.af-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center}
.af-modal.show{display:flex}
.af-modal-box{background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:2rem;width:90%;max-width:400px}
.af-modal-title{font-size:16px;font-weight:700;color:#fff;margin-bottom:1rem}
.af-modal-input{width:100%;padding:.75rem 1rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:14px;outline:none;margin-bottom:1rem}
.af-modal-input:focus{border-color:rgba(239,68,68,0.4)}
.af-modal-actions{display:flex;gap:.75rem}
.af-btn-confirmar{flex:1;padding:.75rem;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.4);color:#ef4444;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s}
.af-btn-confirmar:hover{background:rgba(239,68,68,0.25)}
.af-btn-cancelar{flex:1;padding:.75rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);color:#a1a1aa;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer}

.af-ok{text-align:center;padding:3rem;color:#52525b}
@media(max-width:900px){.af-kpis{grid-template-columns:repeat(3,1fr)}.af-grid{grid-template-columns:1fr}}
</style>

<!-- HEADER -->
<div class="af-header">
    <div>
        <div class="af-title">🛡️ Anti-Fraude</div>
        <div class="af-subtitle">Detecção automática de comportamento suspeito</div>
    </div>
    <div style="font-size:12px;color:#52525b">Última verificação: <?= date('H:i') ?></div>
</div>

<!-- KPIs -->
<div class="af-kpis">
    <div class="af-kpi">
        <div class="af-kpi-val" style="color:#ef4444"><?= $stats['total_alertas'] ?? 0 ?></div>
        <div class="af-kpi-lbl">Alertas Activos</div>
    </div>
    <div class="af-kpi">
        <div class="af-kpi-val" style="color:#ef4444"><?= $stats['alta'] ?? 0 ?></div>
        <div class="af-kpi-lbl">Alta</div>
    </div>
    <div class="af-kpi">
        <div class="af-kpi-val" style="color:#f59e0b"><?= $stats['media'] ?? 0 ?></div>
        <div class="af-kpi-lbl">Média</div>
    </div>
    <div class="af-kpi">
        <div class="af-kpi-val" style="color:#71717a"><?= $stats['baixa'] ?? 0 ?></div>
        <div class="af-kpi-lbl">Baixa</div>
    </div>
    <div class="af-kpi">
        <div class="af-kpi-val" style="color:#ef4444"><?= $stats['bloqueados'] ?? 0 ?></div>
        <div class="af-kpi-lbl">Bloqueados</div>
    </div>
</div>

<div class="af-grid">
    <!-- ALERTAS -->
    <div>
        <div class="af-card">
            <div class="af-card-head">
                <span>⚠️ Alertas Activos</span>
                <span style="font-size:12px;color:#71717a"><?= count($alertas) ?></span>
            </div>

            <?php if (!empty($alertas)): foreach($alertas as $a):
                $sev  = $a['severidade'] ?? 'baixa';
                $si   = $sevInfo[$sev] ?? $sevInfo['baixa'];
                $tipo = $tipoLabel[$a['tipo'] ?? ''] ?? ucfirst($a['tipo'] ?? '');
                $data = date('d/m/Y H:i', strtotime($a['data_deteccao'] ?? 'now'));
            ?>
            <div class="af-alerta">
                <div class="af-alerta-icon" style="background:<?= $si['bg'] ?>;border:1px solid <?= $si['borda'] ?>;color:<?= $si['cor'] ?>">
                    <i class="bi <?= $si['icon'] ?>"></i>
                </div>
                <div style="flex:1;min-width:0">
                    <div class="af-alerta-tipo" style="color:<?= $si['cor'] ?>"><?= $tipo ?></div>
                    <div class="af-alerta-desc"><?= htmlspecialchars($a['descricao'] ?? '') ?></div>
                    <div class="af-alerta-meta">
                        <span class="af-sev" style="background:<?= $si['bg'] ?>;color:<?= $si['cor'] ?>;border:1px solid <?= $si['borda'] ?>">
                            <?= $si['label'] ?>
                        </span>
                        <span style="font-size:11px;color:#52525b"><?= $data ?></span>
                        <?php if (!empty($a['nome'])): ?>
                        <span style="font-size:11px;color:#71717a">· <?= htmlspecialchars($a['nome']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="af-alerta-actions">
                        <form method="POST" action="/public/pulso/<?= $stationId ?>/antifraude/<?= $a['id'] ?>/resolver" style="display:inline">
                            <button type="submit" class="af-btn af-btn-resolver">
                                <i class="bi bi-check-lg"></i> Resolver
                            </button>
                        </form>
                        <?php if (!empty($a['ouvinte_id']) && !$a['bloqueado']): ?>
                        <button class="af-btn af-btn-bloquear"
                                onclick="abrirModalBloquear(<?= $a['ouvinte_id'] ?>, '<?= htmlspecialchars($a['nome'] ?? '') ?>')">
                            <i class="bi bi-ban"></i> Bloquear
                        </button>
                        <?php endif; ?>
                        <?php if (!empty($a['ouvinte_id'])): ?>
                        <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $a['ouvinte_id'] ?>/ficha"
                           class="af-btn af-btn-ver" target="_blank">
                            <i class="bi bi-eye"></i> Ver Ficha
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="af-ok">
                <div style="font-size:48px;margin-bottom:1rem">🛡️</div>
                <div style="font-size:15px;font-weight:600;color:#10b981;margin-bottom:.375rem">Sistema Protegido</div>
                <div style="font-size:13px">Nenhum alerta detectado</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- BLOQUEADOS -->
    <div>
        <div class="af-card">
            <div class="af-card-head">
                <span>🚫 Ouvintes Bloqueados</span>
                <span style="font-size:12px;color:#71717a"><?= count($bloqueados) ?></span>
            </div>

            <?php if (!empty($bloqueados)): foreach($bloqueados as $b):
                $ini = mb_strtoupper(mb_substr($b['nome'] ?? '?', 0, 1));
            ?>
            <div class="af-bloq-row">
                <div class="af-bloq-avatar"><?= $ini ?></div>
                <div style="flex:1;min-width:0">
                    <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $b['id'] ?>/ficha"
                       style="font-size:13px;font-weight:700;color:#fff;text-decoration:none">
                        <?= htmlspecialchars($b['nome']) ?>
                    </a>
                    <div style="font-size:11px;color:#71717a;margin-top:2px"><?= htmlspecialchars($b['motivo_bloqueio'] ?? '') ?></div>
                </div>
                <form method="POST" action="/public/pulso/<?= $stationId ?>/antifraude/desbloquear/<?= $b['id'] ?>">
                    <button type="submit" class="af-btn af-btn-desbloquear" onclick="return confirm('Desbloquear <?= htmlspecialchars($b['nome']) ?>?')">
                        <i class="bi bi-unlock"></i>
                    </button>
                </form>
            </div>
            <?php endforeach; else: ?>
            <div class="af-ok" style="padding:2rem">
                <div style="font-size:32px;margin-bottom:.5rem;opacity:.3">🔓</div>
                <div style="font-size:13px">Nenhum ouvinte bloqueado</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- COMO FUNCIONA -->
        <div style="background:rgba(0,229,255,0.04);border:1px solid rgba(0,229,255,0.12);border-radius:12px;padding:1.25rem;font-size:12px;color:#a1a1aa;line-height:1.7">
            <div style="font-weight:700;color:#00e5ff;margin-bottom:.5rem">🔍 O que é detectado</div>
            <div style="display:flex;flex-direction:column;gap:.5rem">
                <div>🌐 <strong style="color:#e4e4e7">IP Duplicado</strong> — múltiplos ouvintes do mesmo IP</div>
                <div>⚡ <strong style="color:#e4e4e7">Spam</strong> — +5 participações numa hora</div>
                <div>👥 <strong style="color:#e4e4e7">Multi-Conta</strong> — nomes semelhantes de IPs diferentes</div>
            </div>
        </div>
    </div>
</div>

<!-- HISTÓRICO -->
<?php if (!empty($historico)): ?>
<div class="af-card" style="margin-bottom:1.25rem">
    <div class="af-card-head">
        <span>📋 Histórico de Alertas Resolvidos</span>
        <span style="font-size:12px;color:#71717a"><?= count($historico) ?></span>
    </div>
    <?php foreach($historico as $h):
        $sev  = $h['severidade'] ?? 'baixa';
        $si   = $sevInfo[$sev] ?? $sevInfo['baixa'];
        $tipo = $tipoLabel[$h['tipo'] ?? ''] ?? ucfirst($h['tipo'] ?? '');
    ?>
    <div style="display:flex;align-items:center;gap:1rem;padding:.875rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.04)">
        <div style="width:8px;height:8px;border-radius:50%;background:<?= $si['cor'] ?>;flex-shrink:0"></div>
        <div style="flex:1;min-width:0">
            <div style="font-size:13px;font-weight:600;color:#a1a1aa"><?= $tipo ?> — <?= !empty($h['nome']) ? htmlspecialchars($h['nome']) : '—' ?></div>
            <div style="font-size:11px;color:#52525b;margin-top:2px"><?= htmlspecialchars(mb_substr($h['descricao'] ?? '', 0, 80)) ?></div>
        </div>
        <div style="text-align:right;flex-shrink:0">
            <div style="font-size:11px;color:#52525b"><?= date('d/m/Y H:i', strtotime($h['data_deteccao'] ?? 'now')) ?></div>
            <div style="font-size:10px;color:#10b981;font-weight:600;margin-top:2px">✓ Resolvido</div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- MODAL BLOQUEAR -->
<div class="af-modal" id="modalBloquear">
    <div class="af-modal-box">
        <div class="af-modal-title">🚫 Bloquear Ouvinte</div>
        <div style="font-size:13px;color:#a1a1aa;margin-bottom:1rem">
            Ouvinte: <strong style="color:#fff" id="modalNome"></strong>
        </div>
        <input type="text" class="af-modal-input" id="motivoInput"
               placeholder="Motivo do bloqueio..." value="Comportamento suspeito">
        <div class="af-modal-actions">
            <button class="af-btn-confirmar" onclick="confirmarBloqueio()">
                🚫 Bloquear
            </button>
            <button class="af-btn-cancelar" onclick="fecharModal()">Cancelar</button>
        </div>
    </div>
</div>

<form id="formBloquear" method="POST" style="display:none">
    <input type="hidden" name="motivo" id="motivoHidden">
</form>

<script>
let ouvinteIdParaBloquear = null;

function abrirModalBloquear(ouvinteId, nome) {
    ouvinteIdParaBloquear = ouvinteId;
    document.getElementById('modalNome').textContent = nome;
    document.getElementById('modalBloquear').classList.add('show');
    document.getElementById('motivoInput').focus();
}

function fecharModal() {
    document.getElementById('modalBloquear').classList.remove('show');
}

function confirmarBloqueio() {
    if (!ouvinteIdParaBloquear) return;
    const motivo = document.getElementById('motivoInput').value || 'Comportamento suspeito';
    const form   = document.getElementById('formBloquear');
    form.action  = `/public/pulso/<?= $stationId ?>/antifraude/bloquear/${ouvinteIdParaBloquear}`;
    document.getElementById('motivoHidden').value = motivo;
    form.submit();
}

document.getElementById('modalBloquear').addEventListener('click', function(e) {
    if (e.target === this) fecharModal();
});
</script>
