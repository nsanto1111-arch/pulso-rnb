<?php
$segmentos = ['novo'=>'Novo','regular'=>'Regular','veterano'=>'Veterano','embaixador'=>'Embaixador','inactivo'=>'Inactivo'];
$segCores  = ['novo'=>'#3b82f6','regular'=>'#10b981','veterano'=>'#8b5cf6','embaixador'=>'#f59e0b','inactivo'=>'#71717a'];

// Score de completude por ouvinte
function scoreCompletude(array $o): int {
    return (int)(
        (!empty($o['provincia'])        ? 1 : 0) +
        (!empty($o['genero'])           ? 1 : 0) +
        (!empty($o['data_nascimento'])  ? 1 : 0) +
        (!empty($o['generos_musicais']) ? 1 : 0) +
        (!empty($o['como_conheceu'])    ? 1 : 0)
    );
}
?>
<style>
.ol-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.ol-title{font-size:22px;font-weight:800;color:#fff}
.ol-subtitle{font-size:13px;color:#71717a;margin-top:3px}
.ol-actions{display:flex;gap:.625rem}
.ol-btn{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.125rem;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none;transition:all .2s;border:1px solid transparent}
.ol-btn-primary{background:linear-gradient(135deg,#00e5ff,#0891b2);color:#000;border-color:transparent}
.ol-btn-primary:hover{opacity:.9;text-decoration:none;color:#000}
.ol-btn-outline{background:rgba(255,255,255,0.04);color:#a1a1aa;border-color:rgba(255,255,255,0.1)}
.ol-btn-outline:hover{background:rgba(255,255,255,0.08);color:#fff;text-decoration:none}

.ol-filters{display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap;align-items:center}
.ol-filter{padding:.5rem 1rem;border:1px solid rgba(255,255,255,0.08);border-radius:8px;background:transparent;color:#71717a;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;white-space:nowrap}
.ol-filter:hover{border-color:rgba(255,255,255,0.15);color:#a1a1aa;text-decoration:none}
.ol-filter.active{background:rgba(0,229,255,0.1);border-color:rgba(0,229,255,0.3);color:#00e5ff}

.ol-search{flex:1;min-width:250px;position:relative}
.ol-search input{width:100%;padding:.625rem 1rem .625rem 2.5rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;color:#fff;font-size:13px;outline:none;transition:border-color .2s}
.ol-search input:focus{border-color:rgba(0,229,255,0.3)}
.ol-search input::placeholder{color:#52525b}
.ol-search-icon{position:absolute;left:.875rem;top:50%;transform:translateY(-50%);color:#52525b;font-size:14px}

.ol-table-wrap{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden}
.ol-table{width:100%;border-collapse:collapse}
.ol-table thead th{padding:.875rem 1.25rem;text-align:left;font-size:11px;font-weight:700;color:#71717a;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid rgba(255,255,255,0.06);background:rgba(0,0,0,0.15);white-space:nowrap}
.ol-table tbody tr{border-bottom:1px solid rgba(255,255,255,0.04);transition:background .15s}
.ol-table tbody tr:last-child{border-bottom:none}
.ol-table tbody tr:hover{background:rgba(255,255,255,0.02)}
.ol-table tbody td{padding:.875rem 1.25rem;vertical-align:middle}

.ol-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#00e5ff,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#000;flex-shrink:0}
.ol-nome{font-size:14px;font-weight:600;color:#fff;text-decoration:none}
.ol-nome:hover{color:#00e5ff}
.ol-sub{font-size:12px;color:#71717a;margin-top:2px}

.ol-seg{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700}
.ol-pts{font-size:16px;font-weight:800;color:#00e5ff}
.ol-pts-zero{color:#52525b}

.ol-completude{display:flex;align-items:center;gap:.5rem}
.ol-comp-bar{width:60px;height:5px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden}
.ol-comp-fill{height:100%;border-radius:3px}
.ol-comp-txt{font-size:11px;color:#71717a;width:24px}

.ol-btns{display:flex;gap:.375rem}
.ol-btn-sm{width:30px;height:30px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;text-decoration:none;transition:all .2s;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.03);color:#71717a}
.ol-btn-sm:hover{background:rgba(255,255,255,0.08);color:#fff;text-decoration:none}
.ol-btn-sm.view:hover{border-color:rgba(0,229,255,0.3);color:#00e5ff}
.ol-btn-sm.edit:hover{border-color:rgba(245,158,11,0.3);color:#f59e0b}
.ol-btn-sm.del:hover{border-color:rgba(239,68,68,0.3);color:#ef4444}

.ol-empty{text-align:center;padding:4rem;color:#52525b}
.ol-empty-icon{font-size:48px;margin-bottom:1rem;opacity:.3}
</style>

<!-- HEADER -->
<div class="ol-header">
    <div>
        <div class="ol-title">👥 Ouvintes</div>
        <div class="ol-subtitle"><?= count($ouvintes) ?> ouvinte<?= count($ouvintes) !== 1 ? 's' : '' ?> registado<?= count($ouvintes) !== 1 ? 's' : '' ?></div>
    </div>
    <div class="ol-actions">
        <a href="/public/pulso/<?= $stationId ?>/ouvintes/enriquecer" class="ol-btn ol-btn-outline">
            <i class="bi bi-bar-chart-steps"></i> Enriquecer Perfis
        </a>
        <a href="/public/pulso/<?= $stationId ?>/ouvintes/novo" class="ol-btn ol-btn-primary">
            <i class="bi bi-person-plus-fill"></i> Novo Ouvinte
        </a>
    </div>
</div>

<!-- FILTROS -->
<div class="ol-filters">
    <a href="/public/pulso/<?= $stationId ?>/ouvintes" class="ol-filter <?= !$segmento ? 'active' : '' ?>">
        Todos <span style="margin-left:.25rem;opacity:.6"><?= count($ouvintes) ?></span>
    </a>
    <?php foreach($segmentos as $val => $label): ?>
    <a href="/public/pulso/<?= $stationId ?>/ouvintes?segmento=<?= $val ?>" 
       class="ol-filter <?= $segmento === $val ? 'active' : '' ?>"
       style="<?= $segmento === $val ? 'border-color:'.($segCores[$val] ?? '#71717a').';color:'.($segCores[$val] ?? '#71717a').';background:'.($segCores[$val] ?? '#71717a').'18' : '' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>

    <div class="ol-search">
        <i class="bi bi-search ol-search-icon"></i>
        <form method="GET" style="margin:0">
            <?php if ($segmento): ?>
            <input type="hidden" name="segmento" value="<?= htmlspecialchars($segmento) ?>">
            <?php endif; ?>
            <input type="text" name="busca" placeholder="Buscar por nome, telefone ou email..."
                   value="<?= htmlspecialchars($busca ?? '') ?>"
                   onchange="this.form.submit()">
        </form>
    </div>
</div>

<!-- TABELA -->
<div class="ol-table-wrap">
    <?php if (!empty($ouvintes)): ?>
    <table class="ol-table">
        <thead>
            <tr>
                <th>Ouvinte</th>
                <th>Segmento</th>
                <th>Pontos</th>
                <th>Participações</th>
                <th>Perfil</th>
                <th>Registado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($ouvintes as $o):
            $ini     = mb_strtoupper(mb_substr($o['nome'], 0, 1));
            $seg     = $o['segmento'] ?? 'novo';
            $cor     = $segCores[$seg] ?? '#71717a';
            $score   = scoreCompletude($o);
            $scorePct = round($score / 5 * 100);
            $scoreCor = $score >= 4 ? '#10b981' : ($score >= 2 ? '#f59e0b' : '#ef4444');
            $data    = date('d/m/Y', strtotime($o['data_registo'] ?? 'now'));
            $pts     = (int)($o['pontos'] ?? 0);
            $part    = (int)($o['total_participacoes'] ?? 0);
            $bloq    = $o['bloqueado'] ?? 0;
        ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:.875rem">
                    <div class="ol-avatar" style="<?= $bloq ? 'opacity:.4' : '' ?>"><?= $ini ?></div>
                    <div>
                        <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/ficha" class="ol-nome">
                            <?= htmlspecialchars($o['nome']) ?>
                            <?php if ($bloq): ?><span style="font-size:10px;color:#ef4444;margin-left:.5rem">🚫 Bloqueado</span><?php endif; ?>
                        </a>
                        <div class="ol-sub">
                            <?= htmlspecialchars($o['telefone'] ?? '') ?>
                            <?php if (!empty($o['provincia'])): ?>
                            · <?= htmlspecialchars($o['provincia']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <span class="ol-seg" style="background:<?= $cor ?>18;color:<?= $cor ?>;border:1px solid <?= $cor ?>40">
                    <?= ucfirst($seg) ?>
                </span>
            </td>
            <td>
                <span class="ol-pts <?= $pts === 0 ? 'ol-pts-zero' : '' ?>">
                    <?= number_format($pts) ?>
                </span>
            </td>
            <td style="color:#a1a1aa;font-size:13px">
                <?= $part ?>
                <?php if ($part > 0): ?>
                <span style="font-size:10px;color:#52525b;margin-left:.375rem"> interacções</span>
                <?php endif; ?>
            </td>
            <td>
                <div class="ol-completude">
                    <div class="ol-comp-bar">
                        <div class="ol-comp-fill" style="width:<?= $scorePct ?>%;background:<?= $scoreCor ?>"></div>
                    </div>
                    <span class="ol-comp-txt"><?= $score ?>/5</span>
                </div>
            </td>
            <td style="color:#71717a;font-size:12px"><?= $data ?></td>
            <td>
                <div class="ol-btns">
                    <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/ficha"
                       class="ol-btn-sm view" title="Ver Ficha"><i class="bi bi-eye"></i></a>
                    <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/editar"
                       class="ol-btn-sm edit" title="Editar"><i class="bi bi-pencil"></i></a>
                    <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $o['id'] ?>/excluir"
                       class="ol-btn-sm del" title="Excluir"
                       onclick="return confirm('Excluir <?= htmlspecialchars($o['nome']) ?>?')">
                       <i class="bi bi-trash"></i></a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="ol-empty">
        <div class="ol-empty-icon">👥</div>
        <div style="font-size:15px;font-weight:600;color:#a1a1aa;margin-bottom:.5rem">
            <?= $busca ? 'Nenhum resultado para "'.htmlspecialchars($busca).'"' : 'Nenhum ouvinte registado' ?>
        </div>
        <?php if (!$busca): ?>
        <a href="/public/pulso/<?= $stationId ?>/ouvintes/novo" class="ol-btn ol-btn-primary" style="display:inline-flex;margin-top:1rem">
            <i class="bi bi-person-plus-fill"></i> Adicionar Primeiro Ouvinte
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
