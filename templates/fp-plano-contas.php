<?php
$contas  = $dados['contas']  ?? [];
$stats   = $dados['stats']   ?? [];
$filtro  = $filtro ?? 'todas';
$contaSel = $contaSel ?? null;
$fmtKz = fn($v) => number_format((float)$v, 2, ',', '.') . ' Kz';

$classeNomes = [1=>'ACTIVO',2=>'PASSIVO',3=>'RENDIMENTOS',4=>'GASTOS',5=>'RESULTADOS'];
$classeCores = [1=>'#00e5ff',2=>'#ef4444',3=>'#10b981',4=>'#f59e0b',5=>'#8b5cf6'];

// Filtrar
$contasFiltradas = $contas;
if ($filtro !== 'todas') {
    $mapFiltro = ['activo'=>1,'passivo'=>2,'rendimentos'=>3,'gastos'=>4,'resultados'=>5];
    $classeNum = $mapFiltro[$filtro] ?? null;
    if ($classeNum) $contasFiltradas = array_filter($contas, fn($c) => $c['classe'] == $classeNum);
}

// Agrupar por classe
$arvore = [];
foreach ($contas as $c) $arvore[$c['id']] = $c;
?>
<style>
:root {
    --fp-green:#10b981;--fp-red:#ef4444;--fp-gold:#f59e0b;
    --fp-cyan:#00e5ff;--fp-purple:#8b5cf6;--fp-blue:#3b82f6;
    --fp-bg:#070b14;--fp-bg1:#0d1117;--fp-bg2:#161b27;--fp-bg3:#1e2535;
    --fp-text:#f0f4ff;--fp-text2:#8892a4;--fp-text3:#4a5568;
    --fp-border:rgba(255,255,255,.07);
}
.fpc-wrap{display:grid;grid-template-columns:1fr 360px;gap:1.25rem;height:calc(100vh - 160px)}
.fpc-left{display:flex;flex-direction:column;gap:1rem;min-height:0}
.fpc-right{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:16px;overflow:hidden;display:flex;flex-direction:column}

/* HEADER */
.fpc-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.fpc-title{font-size:24px;font-weight:900;color:var(--fp-text)}
.fpc-title span{color:var(--fp-green)}

/* STATS */
.fpc-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}
.fpc-stat{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:12px;padding:1rem 1.25rem;position:relative;overflow:hidden}
.fpc-stat::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;border-radius:12px 12px 0 0}
.fpc-stat.green::before{background:var(--fp-green)}
.fpc-stat.cyan::before{background:var(--fp-cyan)}
.fpc-stat.purple::before{background:var(--fp-purple)}
.fpc-stat.gold::before{background:var(--fp-gold)}
.fpc-stat-val{font-size:28px;font-weight:900;color:var(--fp-text);line-height:1}
.fpc-stat-lbl{font-size:10px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px;margin-top:.375rem}

/* TOOLBAR */
.fpc-toolbar{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap}
.fpc-search{flex:1;min-width:200px;position:relative}
.fpc-search input{width:100%;padding:.625rem 1rem .625rem 2.5rem;background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none}
.fpc-search input:focus{border-color:rgba(16,185,129,.4)}
.fpc-search input::placeholder{color:var(--fp-text3)}
.fpc-search-icon{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--fp-text3);font-size:13px}
.fpc-tabs{display:flex;gap:.375rem;background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:10px;padding:3px}
.fpc-tab{padding:.375rem .875rem;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;text-decoration:none;color:var(--fp-text2);transition:all .15s;white-space:nowrap}
.fpc-tab.active{background:var(--fp-green);color:#000}
.fpc-tab:hover:not(.active){background:rgba(255,255,255,.05);color:var(--fp-text);text-decoration:none}

/* TREE TABLE */
.fpc-tree{background:var(--fp-bg2);border:1px solid var(--fp-border);border-radius:14px;overflow:hidden;flex:1;overflow-y:auto}
.fpc-tree-head{display:grid;grid-template-columns:180px 1fr 90px 90px 32px;gap:.5rem;padding:.625rem 1.25rem;border-bottom:1px solid var(--fp-border);position:sticky;top:0;background:var(--fp-bg3);z-index:1}
.fpc-tree-head span{font-size:9px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px}
.fpc-tree-row{display:grid;grid-template-columns:180px 1fr 90px 90px 32px;gap:.5rem;padding:.5rem 1.25rem;align-items:center;cursor:pointer;transition:background .12s;border-bottom:1px solid rgba(255,255,255,.025)}
.fpc-tree-row:last-child{border-bottom:none}
.fpc-tree-row:hover{background:rgba(255,255,255,.025)}
.fpc-tree-row.selected{background:rgba(16,185,129,.07);border-left:2px solid var(--fp-green)}
.fpc-tree-row.level-0{background:rgba(255,255,255,.02)}
.fpc-tree-row.level-0 .fpc-code{font-weight:900;font-size:13px}
.fpc-tree-row.level-0 .fpc-nome{font-weight:800;font-size:13px}
.fpc-code{font-size:12px;font-weight:600;color:var(--fp-text2);font-family:monospace}
.fpc-nome{font-size:12px;color:var(--fp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fpc-badge{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:9px;font-weight:700}
.fpc-expand{color:var(--fp-text3);font-size:10px;width:16px}

/* DETAIL PANEL */
.fpc-detail-header{padding:1.25rem 1.5rem;border-bottom:1px solid var(--fp-border)}
.fpc-detail-code{font-size:36px;font-weight:900;color:var(--fp-cyan);font-family:monospace;line-height:1}
.fpc-detail-nome{font-size:15px;font-weight:700;color:var(--fp-text);margin-top:.375rem}
.fpc-detail-body{padding:1.25rem 1.5rem;flex:1;overflow-y:auto}
.fpc-detail-field{margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid rgba(255,255,255,.04)}
.fpc-detail-field:last-child{border-bottom:none}
.fpc-detail-label{font-size:10px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.375rem}
.fpc-detail-value{font-size:13px;font-weight:600;color:var(--fp-text)}
.fpc-saldo-box{background:var(--fp-bg3);border-radius:12px;padding:1rem;margin-bottom:1rem;text-align:center}
.fpc-saldo-label{font-size:10px;font-weight:700;color:var(--fp-text3);text-transform:uppercase;letter-spacing:.8px}
.fpc-saldo-val{font-size:24px;font-weight:900;color:var(--fp-cyan);margin-top:.25rem}
.fpc-detail-actions{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;padding:1.25rem 1.5rem;border-top:1px solid var(--fp-border)}
.fpc-btn-edit{padding:.75rem;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:10px;color:var(--fp-green);font-size:13px;font-weight:700;cursor:pointer;transition:all .15s}
.fpc-btn-edit:hover{background:rgba(16,185,129,.18)}
.fpc-btn-arch{padding:.75rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text2);font-size:13px;cursor:pointer}
.fpc-empty-detail{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem;color:var(--fp-text3);text-align:center}
.fpc-empty-icon{font-size:48px;opacity:.2;margin-bottom:1rem}

/* MODAL */
.fpc-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center}
.fpc-modal-bg.open{display:flex}
.fpc-modal{background:var(--fp-bg1);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2rem;width:90%;max-width:520px;max-height:90vh;overflow-y:auto}
.fpc-form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.fpc-field{margin-bottom:1rem}
.fpc-field label{display:block;font-size:10px;font-weight:700;color:var(--fp-text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.5rem}
.fpc-input{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none;color-scheme:dark}
.fpc-input:focus{border-color:rgba(16,185,129,.4)}
.fpc-select{width:100%;padding:.75rem 1rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text);font-size:13px;outline:none}
.fpc-modal-footer{display:flex;gap:.75rem;margin-top:1.5rem}
.fpc-btn-save{flex:1;padding:.875rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:14px;font-weight:800;cursor:pointer}
.fpc-btn-cancel{flex:1;padding:.875rem;background:var(--fp-bg3);border:1px solid var(--fp-border);border-radius:10px;color:var(--fp-text2);font-size:14px;cursor:pointer}

@media(max-width:1100px){.fpc-wrap{grid-template-columns:1fr}.fpc-right{display:none}}
@media(max-width:700px){.fpc-stats{grid-template-columns:repeat(2,1fr)}}
</style>

<!-- HEADER -->
<div class="fpc-header">
    <div>
        <div class="fpc-title">Plano de <span>Contas</span></div>
        <div style="font-size:13px;color:var(--fp-text2);margin-top:4px">Estrutura Contábil · Padrão PGC Angola</div>
    </div>
    <div style="display:flex;gap:.75rem">
        <button onclick="document.getElementById('fpc-modal').classList.add('open')"
                style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:var(--fp-green);border:none;border-radius:10px;color:#000;font-size:13px;font-weight:800;cursor:pointer">
            <i class="bi bi-plus-lg"></i> Nova Conta
        </button>
    </div>
</div>

<!-- STATS -->
<div class="fpc-stats" style="margin-bottom:1.25rem">
    <div class="fpc-stat green">
        <div class="fpc-stat-val"><?= $stats['total'] ?? 0 ?></div>
        <div class="fpc-stat-lbl">Total de Contas</div>
    </div>
    <div class="fpc-stat cyan">
        <div class="fpc-stat-val"><?= $stats['ativas'] ?? 0 ?></div>
        <div class="fpc-stat-lbl">Contas Activas</div>
    </div>
    <div class="fpc-stat purple">
        <div class="fpc-stat-val"><?= $stats['sinteticas'] ?? 0 ?></div>
        <div class="fpc-stat-lbl">Sintéticas</div>
    </div>
    <div class="fpc-stat gold">
        <div class="fpc-stat-val"><?= $stats['analiticas'] ?? 0 ?></div>
        <div class="fpc-stat-lbl">Analíticas</div>
    </div>
</div>

<!-- TOOLBAR -->
<div class="fpc-toolbar" style="margin-bottom:1rem">
    <div class="fpc-search">
        <span class="fpc-search-icon"><i class="bi bi-search"></i></span>
        <input type="text" id="fpc-search-input" placeholder="Pesquisar por código ou nome..." oninput="fpcSearch(this.value)">
    </div>
    <div class="fpc-tabs">
        <?php
        $tabs = ['todas'=>'Todas','activo'=>'Activo','passivo'=>'Passivo','rendimentos'=>'Rendimentos','gastos'=>'Gastos'];
        foreach ($tabs as $k => $lbl):
        ?>
        <a href="/public/financas/<?= $stationId ?>/plano-contas?filtro=<?= $k ?>"
           class="fpc-tab <?= $filtro === $k ? 'active' : '' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- MAIN GRID -->
<div class="fpc-wrap">

    <!-- ÁRVORE -->
    <div class="fpc-left">
        <div class="fpc-tree" id="fpc-tree">
            <div class="fpc-tree-head">
                <span>Código</span>
                <span>Descrição</span>
                <span>Tipo</span>
                <span>Natureza</span>
                <span></span>
            </div>

            <?php
            $classeAtual = null;
            foreach ($contasFiltradas as $c):
                $nivel = (int)($c['nivel'] ?? 0);
                $indent = $nivel * 20;
                $classeNum = (int)$c['classe'];
                $cor = $classeCores[$classeNum] ?? '#71717a';
                $isSelected = $contaSel && $contaSel['id'] == $c['id'];
                $natCor = $c['natureza']==='credora' ? 'var(--fp-green)' : 'var(--fp-blue)';

                if ($nivel === 0 && $classeAtual !== $classeNum):
                    $classeAtual = $classeNum;
                endif;
            ?>
            <div class="fpc-tree-row level-<?= $nivel ?> <?= $isSelected ? 'selected' : '' ?>"
                 onclick="fpcSelectConta(<?= $c['id'] ?>)"
                 data-search="<?= htmlspecialchars(strtolower($c['codigo'].' '.$c['nome'])) ?>"
                 style="padding-left:calc(1.25rem + <?= $indent ?>px)">

                <div class="fpc-code" style="color:<?= $cor ?>">
                    <?php if ($nivel < 2): ?><span style="color:var(--fp-text3);font-size:9px;margin-right:4px"><?= $c['tipo']==='sintetica' ? '▾' : '○' ?></span><?php endif; ?>
                    <?= htmlspecialchars($c['codigo']) ?>
                </div>

                <div class="fpc-nome" style="<?= $nivel===0 ? "color:{$cor}" : '' ?>">
                    <?= htmlspecialchars($c['nome']) ?>
                </div>

                <div>
                    <span class="fpc-badge" style="background:<?= $c['tipo']==='sintetica'?'rgba(139,92,246,.12)':'rgba(16,185,129,.1)' ?>;color:<?= $c['tipo']==='sintetica'?'var(--fp-purple)':'var(--fp-green)' ?>">
                        <?= $c['tipo']==='sintetica' ? 'Sint.' : 'Anal.' ?>
                    </span>
                </div>

                <div>
                    <span class="fpc-badge" style="background:<?= $c['natureza']==='credora'?'rgba(16,185,129,.1)':'rgba(59,130,246,.1)' ?>;color:<?= $natCor ?>">
                        <?= ucfirst($c['natureza']) ?>
                    </span>
                </div>

                <div style="text-align:right">
                    <?php if (($c['total_lancamentos'] ?? 0) > 0): ?>
                    <span style="font-size:9px;color:var(--fp-text3)"><?= $c['total_lancamentos'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- PAINEL DETALHE -->
    <div class="fpc-right">
        <?php if ($contaSel): ?>
        <?php
        $classeNum = (int)$contaSel['classe'];
        $cor = $classeCores[$classeNum] ?? '#71717a';
        $saldo = $contaSel['saldo'] ?? 0;
        ?>
        <div class="fpc-detail-header">
            <div class="fpc-detail-code" style="color:<?= $cor ?>"><?= htmlspecialchars($contaSel['codigo']) ?></div>
            <div class="fpc-detail-nome"><?= htmlspecialchars($contaSel['nome']) ?></div>
            <div style="margin-top:.625rem;display:flex;align-items:center;gap:.5rem">
                <span class="fpc-badge" style="background:<?= $contaSel['ativo']?'rgba(16,185,129,.12)':'rgba(255,255,255,.06)' ?>;color:<?= $contaSel['ativo']?'var(--fp-green)':'var(--fp-text2)' ?>">
                    <?= $contaSel['ativo'] ? 'Activa' : 'Inactiva' ?>
                </span>
                <span class="fpc-badge" style="background:rgba(255,255,255,.05);color:var(--fp-text2)">
                    Classe <?= $classeNum ?> — <?= $classeNomes[$classeNum] ?? '' ?>
                </span>
            </div>
        </div>

        <div class="fpc-detail-body">
            <div class="fpc-saldo-box">
                <div class="fpc-saldo-label">Saldo Actual</div>
                <div class="fpc-saldo-val" style="color:<?= $saldo>=0?'var(--fp-cyan)':'var(--fp-red)' ?>"><?= $fmtKz(abs($saldo)) ?></div>
                <div style="font-size:10px;color:var(--fp-text3);margin-top:.25rem">
                    <?= $contaSel['natureza']==='credora' ? 'Conta Credora' : 'Conta Devedora' ?>
                </div>
            </div>

            <div class="fpc-detail-field">
                <div class="fpc-detail-label">Tipo de Conta</div>
                <div class="fpc-detail-value"><?= ucfirst($contaSel['tipo']) ?></div>
            </div>
            <div class="fpc-detail-field">
                <div class="fpc-detail-label">Natureza</div>
                <div class="fpc-detail-value"><?= ucfirst($contaSel['natureza']) ?></div>
            </div>
            <?php if ($contaSel['pai_nome']): ?>
            <div class="fpc-detail-field">
                <div class="fpc-detail-label">Conta Pai</div>
                <div class="fpc-detail-value"><?= htmlspecialchars($contaSel['pai_codigo'].' — '.$contaSel['pai_nome']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($contaSel['centro_custo_padrao']): ?>
            <div class="fpc-detail-field">
                <div class="fpc-detail-label">Centro de Custo Padrão</div>
                <div class="fpc-detail-value"><?= htmlspecialchars($contaSel['centro_custo_padrao']) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($contaSel['filhos'])): ?>
            <div class="fpc-detail-field">
                <div class="fpc-detail-label">Subcontas (<?= count($contaSel['filhos']) ?>)</div>
                <?php foreach($contaSel['filhos'] as $f): ?>
                <div style="padding:.375rem 0;font-size:12px;color:var(--fp-text2);border-bottom:1px solid rgba(255,255,255,.03)">
                    <span style="font-family:monospace;color:var(--fp-text3)"><?= $f['codigo'] ?></span>
                    &nbsp; <?= htmlspecialchars($f['nome']) ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($contaSel['evolucao'])): ?>
            <div style="margin-top:1rem">
                <div class="fpc-detail-label" style="margin-bottom:.75rem">Evolução — 6 Meses</div>
                <?php
                $maxVal = max(array_column($contaSel['evolucao'], 'total'));
                foreach ($contaSel['evolucao'] as $ev):
                    $pct = $maxVal > 0 ? round($ev['total'] / $maxVal * 100) : 0;
                ?>
                <div style="margin-bottom:.5rem">
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--fp-text3);margin-bottom:3px">
                        <span><?= $ev['mes'] ?></span>
                        <span style="color:var(--fp-text2)"><?= $fmtKz($ev['total']) ?></span>
                    </div>
                    <div style="height:4px;background:rgba(255,255,255,.06);border-radius:2px;overflow:hidden">
                        <div style="width:<?= $pct ?>%;height:100%;background:<?= $cor ?>;border-radius:2px;opacity:.7"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="fpc-detail-actions">
            <button class="fpc-btn-edit" onclick="fpcEditarConta(<?= $contaSel['id'] ?>)">
                <i class="bi bi-pencil"></i> Editar
            </button>
            <button class="fpc-btn-arch">
                <i class="bi bi-archive"></i> Arquivar
            </button>
        </div>

        <?php else: ?>
        <div class="fpc-empty-detail">
            <div class="fpc-empty-icon">📊</div>
            <div style="font-size:13px;font-weight:600;color:var(--fp-text2);margin-bottom:.5rem">Selecciona uma conta</div>
            <div style="font-size:12px">Clica numa linha da árvore para ver os detalhes</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL NOVA CONTA -->
<div class="fpc-modal-bg" id="fpc-modal">
    <div class="fpc-modal">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
            <div style="font-size:16px;font-weight:800;color:var(--fp-text)" id="fpc-modal-title">📊 Nova Conta</div>
            <button onclick="document.getElementById('fpc-modal').classList.remove('open')"
                    style="background:var(--fp-bg3);border:1px solid var(--fp-border);color:var(--fp-text2);width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center">✕</button>
        </div>
        <form method="POST" action="/public/financas/<?= $stationId ?>/plano-contas/salvar" id="fpc-form">
            <input type="hidden" name="id" id="fpc-id" value="">
            <div class="fpc-form-row">
                <div class="fpc-field">
                    <label>Código *</label>
                    <input type="text" name="codigo" id="fpc-codigo" class="fpc-input" placeholder="Ex: 3.1.4" required>
                </div>
                <div class="fpc-field">
                    <label>Classe *</label>
                    <select name="classe" id="fpc-classe" class="fpc-select">
                        <option value="1">1 — Activo</option>
                        <option value="2">2 — Passivo</option>
                        <option value="3">3 — Rendimentos</option>
                        <option value="4">4 — Gastos</option>
                        <option value="5">5 — Resultados</option>
                    </select>
                </div>
            </div>
            <div class="fpc-field">
                <label>Nome *</label>
                <input type="text" name="nome" id="fpc-nome" class="fpc-input" placeholder="Ex: Patrocínios de Programas" required>
            </div>
            <div class="fpc-form-row">
                <div class="fpc-field">
                    <label>Tipo</label>
                    <select name="tipo" id="fpc-tipo" class="fpc-select">
                        <option value="analitica">Analítica</option>
                        <option value="sintetica">Sintética</option>
                    </select>
                </div>
                <div class="fpc-field">
                    <label>Natureza</label>
                    <select name="natureza" id="fpc-natureza" class="fpc-select">
                        <option value="devedora">Devedora</option>
                        <option value="credora">Credora</option>
                    </select>
                </div>
            </div>
            <div class="fpc-form-row">
                <div class="fpc-field">
                    <label>Conta Pai</label>
                    <select name="conta_pai_id" id="fpc-pai" class="fpc-select">
                        <option value="">— Nenhuma —</option>
                        <?php foreach($contas as $c): ?>
                        <?php if ($c['tipo']==='sintetica'): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['codigo'].' — '.$c['nome']) ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="fpc-field">
                    <label>Centro de Custo Padrão</label>
                    <select name="centro_custo_padrao" class="fpc-select">
                        <option value="">— Nenhum —</option>
                        <?php foreach($centros as $cc): ?>
                        <option value="<?= htmlspecialchars($cc['nome']) ?>"><?= htmlspecialchars($cc['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="fpc-field">
                <label>Notas</label>
                <input type="text" name="notas" class="fpc-input" placeholder="Observações opcionais...">
            </div>
            <label style="display:flex;align-items:center;gap:.5rem;font-size:13px;color:var(--fp-text2);cursor:pointer;margin-bottom:1rem">
                <input type="checkbox" name="ativo" value="1" checked> Conta activa
            </label>
            <div class="fpc-modal-footer">
                <button type="submit" class="fpc-btn-save">✅ Guardar Conta</button>
                <button type="button" class="fpc-btn-cancel" onclick="document.getElementById('fpc-modal').classList.remove('open')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
const FP_SID = <?= $stationId ?>;

function fpcSelectConta(id) {
    window.location.href = '/public/financas/' + FP_SID + '/plano-contas?filtro=<?= $filtro ?>&conta=' + id;
}

function fpcSearch(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.fpc-tree-row').forEach(row => {
        const s = row.dataset.search || '';
        row.style.display = (!q || s.includes(q)) ? '' : 'none';
    });
}

function fpcEditarConta(id) {
    fetch('/public/financas/' + FP_SID + '/plano-contas/' + id + '/detalhe')
        .then(r => r.json())
        .then(c => {
            document.getElementById('fpc-id').value    = c.id;
            document.getElementById('fpc-codigo').value = c.codigo;
            document.getElementById('fpc-nome').value   = c.nome;
            document.getElementById('fpc-classe').value = c.classe;
            document.getElementById('fpc-tipo').value   = c.tipo;
            document.getElementById('fpc-natureza').value = c.natureza;
            document.getElementById('fpc-pai').value    = c.conta_pai_id || '';
            document.getElementById('fpc-modal-title').textContent = '✏️ Editar Conta';
            document.getElementById('fpc-modal').classList.add('open');
        });
}

document.getElementById('fpc-modal').addEventListener('click', e => {
    if (e.target === document.getElementById('fpc-modal'))
        document.getElementById('fpc-modal').classList.remove('open');
});
</script>
