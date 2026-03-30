<?php
$promocoesActivas = $dados['promocoes_activas'] ?? [];
$historico        = $dados['historico'] ?? [];
$segCores = ['novo'=>'#3b82f6','regular'=>'#10b981','veterano'=>'#8b5cf6','embaixador'=>'#f59e0b','inactivo'=>'#71717a'];
?>
<style>
.st-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.st-title{font-size:22px;font-weight:800;color:#fff}
.st-subtitle{font-size:13px;color:#71717a;margin-top:3px}
.st-grid{display:grid;grid-template-columns:2fr 1fr;gap:1.25rem}
.st-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.st-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.st-card-body{padding:1.5rem}
.st-promo{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;margin-bottom:1.25rem;overflow:hidden}
.st-promo-top{padding:1.25rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06)}
.st-promo-nome{font-size:18px;font-weight:800;color:#fff;margin-bottom:.5rem}
.st-promo-premio{display:flex;align-items:center;gap:.5rem;font-size:14px;color:#f59e0b;font-weight:700}
.st-promo-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06)}
.st-stat{text-align:center;padding:.875rem;background:rgba(0,0,0,0.15);border-radius:10px}
.st-stat-val{font-size:24px;font-weight:900;line-height:1;margin-bottom:.3rem}
.st-stat-lbl{font-size:10px;color:#71717a;text-transform:uppercase;letter-spacing:.5px;font-weight:600}
.st-promo-form{padding:1.25rem 1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
.st-num-input{width:80px;padding:.625rem;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:8px;color:#fff;font-size:16px;font-weight:700;text-align:center;outline:none}
.st-num-input:focus{border-color:rgba(245,158,11,0.5)}
.st-btn-sortear{display:inline-flex;align-items:center;gap:.625rem;padding:.875rem 2rem;background:linear-gradient(135deg,#f59e0b,#d97706);color:#000;border:none;border-radius:10px;font-size:15px;font-weight:800;cursor:pointer;transition:all .2s;flex:1;justify-content:center}
.st-btn-sortear:hover{opacity:.9;transform:translateY(-1px)}
.st-btn-hist{display:inline-flex;align-items:center;gap:.5rem;padding:.875rem 1.25rem;background:rgba(255,255,255,0.04);color:#a1a1aa;border:1px solid rgba(255,255,255,0.1);border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s}
.st-btn-hist:hover{background:rgba(255,255,255,0.08);color:#fff;text-decoration:none}
.st-hist-row{display:flex;align-items:center;gap:.875rem;padding:.875rem 0;border-bottom:1px solid rgba(255,255,255,0.04)}
.st-hist-row:last-child{border-bottom:none}
.st-hist-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#000;flex-shrink:0}
.st-empty{text-align:center;padding:3rem;color:#52525b}
.st-info-box{background:rgba(0,229,255,0.04);border:1px solid rgba(0,229,255,0.12);border-radius:10px;padding:1rem 1.25rem;font-size:12px;color:#a1a1aa;line-height:1.7;margin-bottom:1.25rem}
@media(max-width:900px){.st-grid{grid-template-columns:1fr}}
</style>

<div class="st-header">
    <div>
        <div class="st-title">🏆 Sorteios</div>
        <div class="st-subtitle">Sistema justo com base em pontos e dias sem ganhar</div>
    </div>
    <a href="/public/pulso/<?= $stationId ?>/promocoes" style="font-size:13px;color:#71717a;text-decoration:none">
        Ver Promoções →
    </a>
</div>

<div class="st-grid">
    <!-- ESQUERDA: PROMOÇÕES ACTIVAS -->
    <div>
        <?php if (!empty($promocoesActivas)): ?>
        <?php foreach($promocoesActivas as $p):
            $diasRestantes = '';
            if (!empty($p['data_fim'])) {
                $diff = (strtotime($p['data_fim']) - time()) / 86400;
                if ($diff < 1)     $diasRestantes = '⚠️ Termina hoje!';
                elseif ($diff < 2) $diasRestantes = '⚠️ Termina amanhã';
                else               $diasRestantes = ceil($diff) . ' dias restantes';
            }
        ?>
        <div class="st-promo">
            <div class="st-promo-top">
                <div class="st-promo-nome"><?= htmlspecialchars($p['nome']) ?></div>
                <div class="st-promo-premio">
                    <i class="bi bi-gift-fill"></i>
                    <?= htmlspecialchars($p['premio'] ?? '—') ?>
                </div>
                <?php if ($diasRestantes): ?>
                <div style="font-size:12px;color:#f59e0b;margin-top:.5rem;font-weight:600"><?= $diasRestantes ?></div>
                <?php endif; ?>
            </div>
            <div class="st-promo-stats">
                <div class="st-stat">
                    <div class="st-stat-val" style="color:#00e5ff"><?= $p['total_participantes'] ?></div>
                    <div class="st-stat-lbl">Participantes</div>
                </div>
                <div class="st-stat">
                    <div class="st-stat-val" style="color:#10b981"><?= $p['max_vencedores'] ?? 1 ?></div>
                    <div class="st-stat-lbl">Vencedores</div>
                </div>
                <div class="st-stat">
                    <div class="st-stat-val" style="color:#f59e0b;font-size:16px"><?= !empty($p['data_fim']) ? date('d/m H:i', strtotime($p['data_fim'])) : '—' ?></div>
                    <div class="st-stat-lbl">Fim</div>
                </div>
            </div>
            <form method="POST" action="/public/pulso/<?= $stationId ?>/sorteios/<?= $p['id'] ?>/sortear">
                <div class="st-promo-form">
                    <div style="display:flex;align-items:center;gap:.75rem">
                        <span style="font-size:13px;color:#a1a1aa;font-weight:600;white-space:nowrap">Nº vencedores:</span>
                        <input type="number" name="num_vencedores" value="<?= $p['max_vencedores'] ?? 1 ?>"
                               min="1" max="<?= $p['max_vencedores'] ?? 10 ?>" class="st-num-input">
                    </div>
                    <button type="submit" class="st-btn-sortear"
                            onclick="return confirm('Realizar sorteio para «<?= htmlspecialchars($p['nome']) ?>»?')">
                        <i class="bi bi-shuffle"></i> SORTEAR AGORA
                    </button>
                    <a href="/public/pulso/<?= $stationId ?>/sorteios/<?= $p['id'] ?>/resultado" class="st-btn-hist">
                        <i class="bi bi-clock-history"></i>
                    </a>
                </div>
            </form>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="st-card">
            <div class="st-empty">
                <div style="font-size:48px;margin-bottom:1rem;opacity:.3">🏆</div>
                <div style="font-size:15px;font-weight:600;color:#a1a1aa;margin-bottom:.5rem">Nenhuma promoção activa</div>
                <div style="font-size:13px;margin-bottom:1.5rem">Activa uma promoção para realizar sorteios</div>
                <a href="/public/pulso/<?= $stationId ?>/promocoes" style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);color:#00e5ff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600">
                    <i class="bi bi-gift"></i> Ver Promoções
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- DIREITA: INFO + HISTÓRICO -->
    <div>
        <!-- Como funciona -->
        <div class="st-card">
            <div class="st-card-head">⚖️ Como Funciona</div>
            <div class="st-card-body">
                <div class="st-info-box">
                    O sorteio é <strong style="color:#fff">ponderado e justo</strong> — não é aleatório puro. Cada ouvinte tem uma probabilidade calculada com base em:
                </div>
                <div style="display:flex;flex-direction:column;gap:.75rem">
                    <div style="display:flex;align-items:center;gap:.875rem;padding:.875rem;background:rgba(0,229,255,0.05);border-radius:10px;border:1px solid rgba(0,229,255,0.1)">
                        <div style="font-size:24px;width:36px;text-align:center">🎯</div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#fff">Pontos <span style="color:#00e5ff">40%</span></div>
                            <div style="font-size:11px;color:#71717a">Mais pontos = maior probabilidade</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.875rem;padding:.875rem;background:rgba(139,92,246,0.05);border-radius:10px;border:1px solid rgba(139,92,246,0.1)">
                        <div style="font-size:24px;width:36px;text-align:center">📅</div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#fff">Dias sem ganhar <span style="color:#8b5cf6">40%</span></div>
                            <div style="font-size:11px;color:#71717a">Quem espera mais tem mais hipótese</div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.875rem;padding:.875rem;background:rgba(16,185,129,0.05);border-radius:10px;border:1px solid rgba(16,185,129,0.1)">
                        <div style="font-size:24px;width:36px;text-align:center">🎵</div>
                        <div>
                            <div style="font-size:13px;font-weight:700;color:#fff">Participações <span style="color:#10b981">20%</span></div>
                            <div style="font-size:11px;color:#71717a">Quem participa mais tem mais peso</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimos vencedores -->
        <div class="st-card">
            <div class="st-card-head">
                🏅 Últimos Vencedores
                <span style="font-size:12px;color:#71717a"><?= count($historico) ?></span>
            </div>
            <div class="st-card-body" style="padding:1rem 1.5rem">
                <?php if (!empty($historico)): foreach($historico as $h):
                    $ini = mb_strtoupper(mb_substr($h['nome'] ?? '?', 0, 1));
                ?>
                <div class="st-hist-row">
                    <div class="st-hist-avatar"><?= $ini ?></div>
                    <div style="flex:1;min-width:0">
                        <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $h['ouvinte_id'] ?>/ficha"
                           style="font-size:13px;font-weight:700;color:#fff;text-decoration:none">
                            <?= htmlspecialchars($h['nome']) ?>
                        </a>
                        <div style="font-size:11px;color:#71717a;margin-top:2px"><?= htmlspecialchars($h['promocao_nome']) ?></div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div style="font-size:11px;color:#f59e0b;font-weight:600"><?= htmlspecialchars($h['premio'] ?? '') ?></div>
                        <div style="font-size:10px;color:#52525b"><?= date('d/m/Y', strtotime($h['data_sorteio'])) ?></div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="st-empty">
                    <div style="font-size:32px;margin-bottom:.5rem;opacity:.3">🏅</div>
                    <div style="font-size:13px">Nenhum sorteio realizado ainda</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
