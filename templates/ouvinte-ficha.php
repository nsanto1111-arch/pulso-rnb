<?php
$o              = $ficha['ouvinte'];
$participacoes  = $ficha['participacoes'] ?? [];
$badges         = $ficha['badges'] ?? [];
$generosMusicais = $ficha['generos_musicais'] ?? [];
$scoreCompletude = $ficha['score_completude'] ?? 0;
$diasOuvinte    = $ficha['dias_como_ouvinte'] ?? 0;
$diasSemGanhar  = $ficha['dias_sem_ganhar'] ?? 0;

$ini    = mb_strtoupper(mb_substr($o['nome'], 0, 1));
$seg    = $o['segmento'] ?? 'novo';
$segCores = ['novo'=>'#3b82f6','regular'=>'#10b981','veterano'=>'#8b5cf6','embaixador'=>'#f59e0b','inactivo'=>'#71717a'];
$segCor = $segCores[$seg] ?? '#71717a';

$idade = '';
if (!empty($o['data_nascimento'])) {
    $idade = date('Y') - (int)substr($o['data_nascimento'], 0, 4);
}

// Localização
$locParts = array_filter([$o['bairro'] ?? '', $o['municipio'] ?? '', $o['provincia'] ?? '']);
$locStr = implode(', ', $locParts) ?: '';
if (!empty($o['pais']) && $o['pais'] !== 'Angola') $locStr .= ($locStr ? ' · ' : '') . $o['pais'];

// Timeline
$tiposInfo = [
    'pedido_musica' => ['icon'=>'bi-music-note-beamed', 'cor'=>'#8b5cf6', 'label'=>'Pedido Musical'],
    'mensagem'      => ['icon'=>'bi-chat-dots',         'cor'=>'#3b82f6', 'label'=>'Mensagem'],
    'promocao'      => ['icon'=>'bi-gift',              'cor'=>'#f59e0b', 'label'=>'Promoção'],
    'votacao'       => ['icon'=>'bi-check2-square',     'cor'=>'#10b981', 'label'=>'Votação'],
    'ligacao'       => ['icon'=>'bi-telephone',         'cor'=>'#06b6d4', 'label'=>'Ligação'],
    'whatsapp'      => ['icon'=>'bi-whatsapp',          'cor'=>'#25d366', 'label'=>'WhatsApp'],
    'sms'           => ['icon'=>'bi-phone',             'cor'=>'#6b7280', 'label'=>'SMS'],
    'app'           => ['icon'=>'bi-app',               'cor'=>'#e94560', 'label'=>'App'],
];

// Contagem por tipo
$contPorTipo = [];
foreach ($participacoes as $p) {
    $contPorTipo[$p['tipo']] = ($contPorTipo[$p['tipo']] ?? 0) + 1;
}

// Agrupar por data
$porData = [];
foreach ($participacoes as $p) {
    $d = date('Y-m-d', strtotime($p['data_participacao'] ?? 'now'));
    $porData[$d][] = $p;
}
krsort($porData);

$totalParts = count($participacoes);
?>
<style>
.of{font-family:'Inter',sans-serif}
.of-back{display:inline-flex;align-items:center;gap:.5rem;color:#71717a;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:1.25rem;transition:color .2s}
.of-back:hover{color:#fff;text-decoration:none}

/* HEADER */
.of-header{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:16px;padding:1.75rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap}
.of-avatar{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#00e5ff,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:900;color:#000;flex-shrink:0}
.of-nome{font-size:22px;font-weight:800;color:#fff;margin-bottom:.375rem}
.of-meta{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;font-size:13px;color:#71717a}
.of-seg{padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700}
.of-header-actions{margin-left:auto;display:flex;gap:.625rem;flex-shrink:0}
.of-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.125rem;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s;border:1px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.04);color:#a1a1aa}
.of-btn:hover{background:rgba(255,255,255,0.08);color:#fff;text-decoration:none}
.of-btn-danger:hover{border-color:rgba(239,68,68,0.4);color:#ef4444}

/* GRID */
.of-grid-main{display:grid;grid-template-columns:1fr 340px;gap:1.25rem;margin-bottom:1.25rem}
.of-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem}

/* CARD */
.of-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.of-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.of-card-body{padding:1.25rem 1.5rem}

/* KPIs */
.of-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:.875rem;margin-bottom:1.25rem}
.of-kpi{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:12px;padding:1.25rem;text-align:center}
.of-kpi-val{font-size:28px;font-weight:900;line-height:1;margin-bottom:.375rem}
.of-kpi-lbl{font-size:11px;color:#71717a;font-weight:600;text-transform:uppercase;letter-spacing:.5px}

/* INFO */
.of-info-row{display:flex;gap:.75rem;padding:.75rem 0;border-bottom:1px solid rgba(255,255,255,0.04);font-size:13px;align-items:flex-start}
.of-info-row:last-child{border-bottom:none}
.of-info-icon{font-size:15px;color:#52525b;flex-shrink:0;width:20px;text-align:center;margin-top:1px}
.of-info-label{color:#71717a;font-weight:600;min-width:100px;flex-shrink:0}
.of-info-val{color:#e4e4e7;flex:1}

/* COMPLETUDE */
.of-comp-bar{height:6px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden;margin:.5rem 0}
.of-comp-fill{height:100%;border-radius:3px;transition:width .4s}
.of-comp-items{display:grid;grid-template-columns:1fr 1fr;gap:.375rem;margin-top:.75rem}
.of-comp-item{display:flex;align-items:center;gap:.5rem;font-size:12px}
.of-comp-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}

/* BADGES */
.of-badges{display:grid;grid-template-columns:repeat(auto-fill,minmax(90px,1fr));gap:.75rem}
.of-badge{text-align:center;padding:1rem .75rem;background:rgba(0,0,0,0.2);border:1px solid rgba(255,255,255,0.06);border-radius:12px;transition:all .2s;cursor:help}
.of-badge:hover{border-color:rgba(0,229,255,0.3);transform:translateY(-2px)}
.of-badge-icon{font-size:28px;margin-bottom:.5rem}
.of-badge-name{font-size:11px;font-weight:600;color:#a1a1aa}

/* GÉNEROS */
.of-genero-tag{display:inline-flex;align-items:center;padding:.375rem .875rem;background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.25);border-radius:20px;font-size:12px;font-weight:600;color:#a78bfa;margin:.25rem}

/* TIMELINE */
.of-tl-filters{display:flex;gap:.5rem;flex-wrap:wrap;padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06)}
.of-tl-btn{padding:.4rem 1rem;border-radius:20px;border:1px solid rgba(255,255,255,0.08);background:transparent;color:#71717a;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:.4rem}
.of-tl-btn:hover{border-color:rgba(255,255,255,0.15);color:#a1a1aa}
.of-tl-btn.active{background:rgba(0,229,255,0.1);border-color:rgba(0,229,255,0.3);color:#00e5ff}
.of-tl-cnt{font-size:10px;padding:1px 6px;border-radius:10px;background:rgba(255,255,255,0.1);font-weight:700}

.of-tl-body{padding:1.25rem 1.5rem}
.of-tl-date{font-size:11px;font-weight:700;color:#52525b;text-transform:uppercase;letter-spacing:1px;padding:.5rem 0 .75rem;border-bottom:1px solid rgba(255,255,255,0.04);margin-bottom:.875rem}
.of-tl-item{display:flex;gap:.875rem;margin-bottom:1rem;align-items:flex-start}
.of-tl-icon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.of-tl-content{flex:1;background:rgba(0,0,0,0.15);border:1px solid rgba(255,255,255,0.05);border-radius:10px;padding:1rem}
.of-tl-top{display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;margin-bottom:.5rem}
.of-tl-tipo{font-size:13px;font-weight:700}
.of-tl-hora{font-size:11px;color:#52525b;white-space:nowrap}
.of-tl-musica{font-size:13px;font-weight:600;color:#00e5ff;margin-bottom:.375rem;display:flex;align-items:center;gap:.5rem}
.of-tl-msg{font-size:12px;color:#a1a1aa;line-height:1.6;font-style:italic}
.of-tl-badges{display:flex;gap:.375rem;flex-wrap:wrap;margin-top:.5rem}
.of-tl-tag{font-size:10px;padding:2px 8px;border-radius:4px;font-weight:700}

.of-empty{text-align:center;padding:3rem;color:#52525b}

@media(max-width:900px){
    .of-grid-main{grid-template-columns:1fr}
    .of-kpis{grid-template-columns:repeat(2,1fr)}
    .of-grid-2{grid-template-columns:1fr}
}
</style>

<div class="of">

<!-- VOLTAR -->
<a href="/public/pulso/<?= $stationId ?>/ouvintes" class="of-back">
    <i class="bi bi-arrow-left"></i> Voltar aos Ouvintes
</a>

<!-- HEADER -->
<div class="of-header">
    <div class="of-avatar"><?= $ini ?></div>
    <div style="flex:1;min-width:0">
        <div class="of-nome"><?= htmlspecialchars($o['nome']) ?></div>
        <div class="of-meta">
            <span class="of-seg" style="background:<?= $segCor ?>18;color:<?= $segCor ?>;border:1px solid <?= $segCor ?>40">
                <?= ucfirst($seg) ?>
            </span>
            <span>· <?= $diasOuvinte ?> dias de ouvinte</span>
            <?php if ($locStr): ?><span>· <?= htmlspecialchars($locStr) ?></span><?php endif; ?>
            <?php if ($idade): ?><span>· <?= $idade ?> anos</span><?php endif; ?>
            <?php if ($o['bloqueado'] ?? 0): ?><span style="color:#ef4444;font-weight:700">🚫 Bloqueado</span><?php endif; ?>
        </div>
    </div>
    <div class="of-header-actions">
        <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/editar" class="of-btn">
            <i class="bi bi-pencil"></i> Editar
        </a>
        <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/excluir" class="of-btn of-btn-danger"
           onclick="return confirm('Eliminar <?= htmlspecialchars($o['nome']) ?>?')">
            <i class="bi bi-trash"></i>
        </a>
    </div>
</div>

<!-- KPIs -->
<div class="of-kpis">
    <div class="of-kpi">
        <div class="of-kpi-val" style="color:#00e5ff"><?= number_format($o['pontos'] ?? 0) ?></div>
        <div class="of-kpi-lbl">Pontos</div>
    </div>
    <div class="of-kpi">
        <div class="of-kpi-val" style="color:#8b5cf6"><?= $o['total_participacoes'] ?? 0 ?></div>
        <div class="of-kpi-lbl">Participações</div>
    </div>
    <div class="of-kpi">
        <div class="of-kpi-val" style="color:#10b981"><?= $o['total_vitorias'] ?? 0 ?></div>
        <div class="of-kpi-lbl">Vitórias</div>
    </div>
    <div class="of-kpi">
        <div class="of-kpi-val" style="color:#f59e0b"><?= $o['streak_dias'] ?? 0 ?></div>
        <div class="of-kpi-lbl">Streak Dias</div>
    </div>
</div>

<!-- MAIN GRID -->
<div class="of-grid-main">

    <!-- ESQUERDA: TIMELINE -->
    <div>
        <div class="of-card">
            <div class="of-card-head">
                <span>🕐 Histórico de Participações</span>
                <span style="font-size:12px;color:#71717a"><?= $totalParts ?> registos</span>
            </div>

            <!-- Filtros -->
            <?php if ($totalParts > 0): ?>
            <div class="of-tl-filters">
                <button class="of-tl-btn active" onclick="tlFiltrar('todos',this)">
                    Todos <span class="of-tl-cnt"><?= $totalParts ?></span>
                </button>
                <?php foreach($contPorTipo as $tipo => $cnt):
                    $ti = $tiposInfo[$tipo] ?? ['cor'=>'#71717a','label'=>ucfirst($tipo),'icon'=>'bi-circle'];
                ?>
                <button class="of-tl-btn" onclick="tlFiltrar('<?= $tipo ?>',this)" data-tipo="<?= $tipo ?>">
                    <?= $ti['label'] ?> <span class="of-tl-cnt" style="background:<?= $ti['cor'] ?>22;color:<?= $ti['cor'] ?>"><?= $cnt ?></span>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="of-tl-body">
            <?php if (!empty($porData)):
                foreach($porData as $data => $parts):
                    $isHoje = $data === date('Y-m-d');
                    $dataFmt = date('d/m/Y', strtotime($data));
            ?>
            <div class="of-tl-group">
                <div class="of-tl-date">
                    <?= $isHoje ? '🔴 Hoje — ' : '' ?><?= $dataFmt ?>
                </div>
                <?php foreach($parts as $p):
                    $ti   = $tiposInfo[$p['tipo']] ?? ['icon'=>'bi-circle','cor'=>'#71717a','label'=>ucfirst($p['tipo'])];
                    $hora = date('H:i', strtotime($p['data_participacao'] ?? 'now'));
                    $pts  = (int)($p['pontos_ganhos'] ?? 0);
                ?>
                <div class="of-tl-item" data-tipo="<?= $p['tipo'] ?>">
                    <div class="of-tl-icon" style="background:<?= $ti['cor'] ?>18;border:2px solid <?= $ti['cor'] ?>60;color:<?= $ti['cor'] ?>">
                        <i class="bi <?= $ti['icon'] ?>"></i>
                    </div>
                    <div class="of-tl-content">
                        <div class="of-tl-top">
                            <div>
                                <span class="of-tl-tipo" style="color:<?= $ti['cor'] ?>"><?= $ti['label'] ?></span>
                                <div class="of-tl-badges">
                                    <?php if ($p['ganhou']): ?>
                                    <span class="of-tl-tag" style="background:rgba(16,185,129,0.1);color:#10b981">🏆 Ganhou</span>
                                    <?php endif; ?>
                                    <?php if ($p['lido_no_ar']): ?>
                                    <span class="of-tl-tag" style="background:rgba(59,130,246,0.1);color:#3b82f6">📻 Lido no Ar</span>
                                    <?php endif; ?>
                                    <?php if ($pts > 0): ?>
                                    <span class="of-tl-tag" style="background:rgba(0,229,255,0.1);color:#00e5ff">+<?= $pts ?> pts</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="of-tl-hora"><?= $hora ?></div>
                        </div>
                        <?php if (!empty($p['musica'])): ?>
                        <div class="of-tl-musica">
                            <i class="bi bi-music-note"></i>
                            <?= htmlspecialchars($p['musica']) ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($p['mensagem'])): ?>
                        <div class="of-tl-msg">"<?= htmlspecialchars(mb_substr($p['mensagem'], 0, 150)) ?><?= mb_strlen($p['mensagem']) > 150 ? '…' : '' ?>"</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="of-empty">
                <div style="font-size:40px;margin-bottom:.75rem;opacity:.3">🎵</div>
                <div style="font-size:14px;font-weight:600;color:#a1a1aa">Sem participações registadas</div>
                <div style="font-size:12px;margin-top:.375rem">Este ouvinte ainda não interagiu com a rádio</div>
            </div>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- DIREITA: PERFIL + BADGES -->
    <div>

        <!-- COMPLETUDE DO PERFIL -->
        <div class="of-card">
            <div class="of-card-head">
                <span>📋 Perfil</span>
                <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/editar"
                   style="font-size:11px;color:#71717a;text-decoration:none">Completar →</a>
            </div>
            <div class="of-card-body">
                <?php
                $pct = round($scoreCompletude / 5 * 100);
                $corComp = $pct >= 80 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#ef4444');
                ?>
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:.25rem">
                    <span style="color:#71717a;font-weight:600">Completude</span>
                    <span style="color:<?= $corComp ?>;font-weight:700"><?= $scoreCompletude ?>/5</span>
                </div>
                <div class="of-comp-bar">
                    <div class="of-comp-fill" style="width:<?= $pct ?>%;background:<?= $corComp ?>"></div>
                </div>

                <!-- Dados -->
                <div style="margin-top:1rem">
                    <?php
                    $infos = [
                        ['bi-telephone',    'Telefone',  $o['telefone'] ?? ''],
                        ['bi-envelope',     'Email',     $o['email'] ?? ''],
                        ['bi-geo-alt',      'Local',     $locStr],
                        ['bi-gender-ambiguous','Género', !empty($o['genero']) ? ucfirst($o['genero']) : ''],
                        ['bi-calendar',     'Nascimento', !empty($o['data_nascimento']) ? date('d/m/Y', strtotime($o['data_nascimento'])) . ($idade ? " ($idade anos)" : '') : ''],
                        ['bi-tv',           'Programa', $o['programa_favorito'] ?? ''],
                        ['bi-mic',          'Locutor',  $o['locutor_favorito'] ?? ''],
                        ['bi-question-circle','Origem',  $o['como_conheceu'] ?? ''],
                        ['bi-clock',          'Horário',  $o['horario_preferido'] ?? ''],
                        ['bi-clock',          'Horário',  $o['horario_preferido'] ?? ''],
                    ];
                    foreach($infos as $info):
                        if (empty($info[2])) continue;
                    ?>
                    <div class="of-info-row">
                        <i class="bi <?= $info[0] ?> of-info-icon"></i>
                        <span class="of-info-label"><?= $info[1] ?></span>
                        <span class="of-info-val"><?= htmlspecialchars($info[2]) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Géneros musicais -->
                <?php if (!empty($generosMusicais)): ?>
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.06)">
                    <div style="font-size:11px;color:#71717a;font-weight:600;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.5px">🎵 Géneros Favoritos</div>
                    <div>
                        <?php foreach($generosMusicais as $g): ?>
                        <span class="of-genero-tag"><?= htmlspecialchars($g) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Notas internas -->
                <?php if (!empty($o['notas'])): ?>
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.06)">
                    <div style="font-size:11px;color:#71717a;font-weight:600;margin-bottom:.5rem;text-transform:uppercase;letter-spacing:.5px">📝 Notas Internas</div>
                    <div style="font-size:12px;color:#a1a1aa;line-height:1.6;font-style:italic"><?= htmlspecialchars($o['notas']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- BADGES -->
        <?php if (!empty($badges)): ?>
        <div class="of-card">
            <div class="of-card-head">
                <span>🏆 Badges</span>
                <span style="font-size:12px;color:#71717a"><?= count($badges) ?></span>
            </div>
            <div class="of-card-body">
                <div class="of-badges">
                    <?php foreach($badges as $b): ?>
                    <div class="of-badge" title="<?= htmlspecialchars($b['descricao']) ?> — <?= date('d/m/Y', strtotime($b['data_conquista'])) ?>">
                        <div class="of-badge-icon"><?= $b['icone'] ?></div>
                        <div class="of-badge-name"><?= htmlspecialchars($b['nome']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ESTATÍSTICAS EXTRA -->
        <div class="of-card">
            <div class="of-card-head">📊 Estatísticas</div>
            <div class="of-card-body">
                <div class="of-info-row">
                    <i class="bi bi-calendar-plus of-info-icon"></i>
                    <span class="of-info-label">Registado</span>
                    <span class="of-info-val"><?= date('d/m/Y', strtotime($o['data_registo'])) ?></span>
                </div>
                <div class="of-info-row">
                    <i class="bi bi-trophy of-info-icon"></i>
                    <span class="of-info-label">Últimas vitória</span>
                    <span class="of-info-val"><?= $diasSemGanhar ?> dias sem ganhar</span>
                </div>
                <div class="of-info-row">
                    <i class="bi bi-graph-up of-info-icon"></i>
                    <span class="of-info-label">Ranking</span>
                    <span class="of-info-val"><?= $o['ranking_atual'] ? '#'.$o['ranking_atual'] : 'Sem ranking' ?></span>
                </div>
                <div class="of-info-row">
                    <i class="bi bi-shield of-info-icon"></i>
                    <span class="of-info-label">Risco</span>
                    <span class="of-info-val" style="color:<?= $o['risco_abandono'] === 'alto' ? '#ef4444' : ($o['risco_abandono'] === 'medio' ? '#f59e0b' : '#10b981') ?>">
                        <?= ucfirst($o['risco_abandono'] ?? 'baixo') ?>
                    </span>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

<script>
function tlFiltrar(tipo, btn) {
    document.querySelectorAll('.of-tl-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.of-tl-item').forEach(el => {
        el.style.display = (tipo === 'todos' || el.dataset.tipo === tipo) ? 'flex' : 'none';
    });
    document.querySelectorAll('.of-tl-group').forEach(g => {
        const visible = [...g.querySelectorAll('.of-tl-item')].some(i => i.style.display !== 'none');
        g.style.display = visible ? '' : 'none';
    });
}
</script>
