<?php
$kpis            = $dados['kpis'] ?? [];
$insights        = $dados['insights'] ?? [];
$horaria            = $dados['horaria'] ?? [];
$horariosPreferidos = $dados['horarios_preferidos'] ?? [];
$previsao        = $dados['previsao'] ?? [];
$retencao        = $dados['retencao'] ?? [];
$distribuicao    = $dados['distribuicao'] ?? [];
$paises          = $dados['paises'] ?? [];
$idades          = $dados['idades'] ?? [];
$generos         = $dados['generos'] ?? [];
$generosMusicais = $dados['generosMusicais'] ?? [];
$crescimento     = $dados['crescimento'] ?? [];
$municipios      = $dados['municipios'] ?? [];

$totalOuvintes   = $distribuicao['stats']['total'] ?? 0;
$totalCidades    = $distribuicao['stats']['cidades_diferentes'] ?? 0;

// Preparar JSON para charts
$jPaisesLabels   = json_encode(array_column($paises, 'pais'));
$jPaisesData     = json_encode(array_column($paises, 'total'));
$jIdadesLabels   = json_encode(array_keys($idades));
$jIdadesData     = json_encode(array_values($idades));
$jGenerosLabels  = json_encode(array_keys($generos));
$jGenerosData    = json_encode(array_values($generos));
$jGMLabels       = json_encode(array_keys($generosMusicais));
$jGMData         = json_encode(array_values($generosMusicais));
$jCrescLabels    = json_encode(array_column($crescimento, 'data'));
$jCrescData      = json_encode(array_column($crescimento, 'novos'));
$jHoraLabels     = json_encode(array_column($horaria, 'hora'));
$jHoraData       = json_encode(array_column($horaria, 'total'));
?>
<style>
.dp { font-family: 'Inter', sans-serif; }

/* KPI STRIP */
.dp-kpis { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.75rem; }
.dp-kpi {
    background:rgba(255,255,255,0.03);
    border:1px solid rgba(255,255,255,0.07);
    border-radius:14px; padding:1.25rem 1.5rem;
    transition: border-color .2s;
}
.dp-kpi:hover { border-color:rgba(255,255,255,0.14); }
.dp-kpi-label { font-size:11px; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; color:#71717a; margin-bottom:.5rem; }
.dp-kpi-value { font-size:36px; font-weight:900; line-height:1; color:#fff; }
.dp-kpi-sub { font-size:12px; color:#71717a; margin-top:.4rem; }
.dp-kpi-up { color:#10b981; } .dp-kpi-down { color:#ef4444; }

/* INSIGHTS */
.dp-insights {
    background:rgba(0,229,255,0.04);
    border:1px solid rgba(0,229,255,0.12);
    border-radius:14px; padding:1.25rem 1.5rem;
    margin-bottom:1.75rem;
    display:grid; grid-template-columns:repeat(3,1fr); gap:1rem;
}
.dp-insight { padding:1rem; background:rgba(0,0,0,0.2); border-radius:10px; border:1px solid rgba(255,255,255,0.05); }
.dp-insight-icon { font-size:24px; margin-bottom:.5rem; }
.dp-insight-title { font-size:13px; font-weight:700; color:#fff; margin-bottom:.3rem; }
.dp-insight-desc { font-size:12px; color:#71717a; margin-bottom:.5rem; line-height:1.5; }
.dp-insight-action { font-size:11px; color:#00e5ff; font-weight:600; }

/* TABS */
.dp-tabs-nav {
    display:flex; gap:.25rem; margin-bottom:1.5rem;
    border-bottom:1px solid rgba(255,255,255,0.07);
    padding-bottom:.75rem;
}
.dp-tab {
    padding:.6rem 1.25rem; border:none; background:transparent;
    color:#71717a; font-size:13px; font-weight:600; cursor:pointer;
    border-radius:8px; transition:all .2s; white-space:nowrap;
}
.dp-tab:hover { background:rgba(255,255,255,0.05); color:#a1a1aa; }
.dp-tab.active { background:rgba(0,229,255,0.1); color:#00e5ff; border:1px solid rgba(0,229,255,0.2); }

/* CARDS */
.dp-card {
    background:rgba(255,255,255,0.02);
    border:1px solid rgba(255,255,255,0.07);
    border-radius:14px; padding:1.5rem;
    margin-bottom:1.25rem;
}
.dp-card-title { font-size:14px; font-weight:700; color:#fff; margin-bottom:1.25rem; display:flex; align-items:center; gap:.5rem; }
.dp-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; }
.dp-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:1.25rem; }

/* TABELA */
.dp-table { width:100%; border-collapse:collapse; font-size:13px; }
.dp-table th { text-align:left; padding:.6rem 1rem; color:#71717a; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:1px; border-bottom:1px solid rgba(255,255,255,0.07); }
.dp-table td { padding:.75rem 1rem; border-bottom:1px solid rgba(255,255,255,0.04); color:#e4e4e7; }
.dp-table tr:last-child td { border-bottom:none; }
.dp-table tr:hover td { background:rgba(255,255,255,0.02); }

/* BARRA */
.dp-bar-wrap { display:flex; align-items:center; gap:.75rem; }
.dp-bar-bg { flex:1; height:6px; background:rgba(255,255,255,0.08); border-radius:3px; overflow:hidden; }
.dp-bar-fill { height:100%; border-radius:3px; background:#00e5ff; transition:width .4s; }
.dp-bar-pct { font-size:12px; color:#71717a; width:36px; text-align:right; }

/* ANEL */
.dp-donut-wrap { display:flex; flex-direction:column; align-items:center; }

/* EMPTY */
.dp-empty { text-align:center; padding:3rem; color:#52525b; }
.dp-empty-icon { font-size:40px; margin-bottom:.75rem; }
.dp-empty-text { font-size:14px; }

/* TABS CONTENT */
.dp-pane { display:none; } .dp-pane.active { display:block; }

@media(max-width:768px){
    .dp-kpis { grid-template-columns:repeat(2,1fr); }
    .dp-insights { grid-template-columns:1fr; }
    .dp-grid-2,.dp-grid-3 { grid-template-columns:1fr; }
}
</style>

<div class="dp">

<!-- KPIs -->
<div class="dp-kpis">
    <div class="dp-kpi">
        <div class="dp-kpi-label">Total Ouvintes</div>
        <div class="dp-kpi-value"><?= number_format($totalOuvintes) ?></div>
        <div class="dp-kpi-sub">activos na plataforma</div>
    </div>
    <div class="dp-kpi">
        <div class="dp-kpi-label">Novos (30 dias)</div>
        <div class="dp-kpi-value"><?= $kpis['novos_30d'] ?? 0 ?></div>
        <div class="dp-kpi-sub">
            <?php $v7 = $kpis['novos_7d'] ?? 0; ?>
            <span class="<?= $v7 > 0 ? 'dp-kpi-up' : '' ?>"><?= $v7 > 0 ? '↑' : '' ?> <?= $v7 ?> esta semana</span>
        </div>
    </div>
    <div class="dp-kpi">
        <div class="dp-kpi-label">Engajamento</div>
        <div class="dp-kpi-value"><?= $kpis['taxa_engagement'] ?? '0' ?></div>
        <div class="dp-kpi-sub">participações por ouvinte</div>
    </div>
    <div class="dp-kpi">
        <div class="dp-kpi-label">Cidades</div>
        <div class="dp-kpi-value"><?= $totalCidades ?></div>
        <div class="dp-kpi-sub"><?= count($paises) ?> país(es) representados</div>
    </div>
</div>

<!-- INSIGHTS -->
<?php if (!empty($insights)): ?>
<div class="dp-insights">
    <?php foreach (array_slice($insights, 0, 3) as $i): ?>
    <div class="dp-insight">
        <div class="dp-insight-icon"><?= $i['icone'] ?></div>
        <div class="dp-insight-title"><?= htmlspecialchars($i['titulo']) ?></div>
        <div class="dp-insight-desc"><?= htmlspecialchars($i['descricao']) ?></div>
        <div class="dp-insight-action">💡 <?= htmlspecialchars($i['accao']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- TABS -->
<div class="dp-tabs-nav">
    <button class="dp-tab active" onclick="dpTab('geral',this)">📊 Visão Geral</button>
    <button class="dp-tab" onclick="dpTab('geo',this)">🌍 Geografia</button>
    <button class="dp-tab" onclick="dpTab('audiencia',this)">👥 Audiência</button>
    <button class="dp-tab" onclick="dpTab('gostos',this)">🎵 Gostos Musicais</button>
    <button class="dp-tab" onclick="dpTab('engajamento',this)">📈 Engajamento</button>
</div>

<!-- ABA: VISÃO GERAL -->
<div class="dp-pane active" id="dp-geral">
    <div class="dp-grid-2">
        <div class="dp-card">
            <div class="dp-card-title">📈 Crescimento (90 dias)</div>
            <?php if (!empty($crescimento)): ?>
            <canvas id="chartCrescent" height="200"></canvas>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">📭</div><div class="dp-empty-text">Sem dados suficientes</div></div>
            <?php endif; ?>
        </div>
        <div class="dp-card">
            <div class="dp-card-title">🎯 Previsão Próximo Mês</div>
            <div style="text-align:center;padding:2rem 1rem">
                <div style="font-size:56px;font-weight:900;color:#00e5ff;line-height:1">+<?= $previsao['previsao_proximo_mes'] ?? 0 ?></div>
                <div style="font-size:13px;color:#71717a;margin:.75rem 0">novos ouvintes esperados</div>
                <div style="background:rgba(0,229,255,0.06);border:1px solid rgba(0,229,255,0.12);border-radius:10px;padding:1rem;margin-top:1rem">
                    <div style="font-size:11px;color:#71717a;margin-bottom:.25rem">Média diária</div>
                    <div style="font-size:28px;font-weight:800;color:#00e5ff"><?= $previsao['media_diaria'] ?? 0 ?></div>
                    <div style="font-size:11px;color:#71717a">cadastros/dia</div>
                </div>
            </div>
        </div>
    </div>

    <div class="dp-grid-3">
        <div class="dp-card">
            <div class="dp-card-title">👥 Por Género</div>
            <?php if (!empty($generos)): ?>
            <canvas id="chartGeneroGeral" height="180"></canvas>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">👤</div><div class="dp-empty-text">Sem dados</div></div>
            <?php endif; ?>
        </div>
        <div class="dp-card">
            <div class="dp-card-title">🎵 Top Géneros Musicais</div>
            <?php if (!empty($generosMusicais)):
                $top5gm = array_slice($generosMusicais, 0, 5, true);
                $maxGM = max(array_values($top5gm) ?: [1]);
            ?>
            <?php foreach($top5gm as $g => $n): ?>
            <div style="margin-bottom:.75rem">
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:.3rem">
                    <span style="color:#e4e4e7;font-weight:600"><?= htmlspecialchars($g) ?></span>
                    <span style="color:#71717a"><?= $n ?></span>
                </div>
                <div class="dp-bar-bg"><div class="dp-bar-fill" style="width:<?= round($n/$maxGM*100) ?>%"></div></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">🎵</div><div class="dp-empty-text">Sem dados</div></div>
            <?php endif; ?>
        </div>
        <div class="dp-card">
            <div class="dp-card-title">📅 Por Faixa Etária</div>
            <?php if (!empty($idades)):
                $maxId = max(array_values($idades) ?: [1]);
            ?>
            <?php foreach($idades as $faixa => $n): ?>
            <div style="margin-bottom:.75rem">
                <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:.3rem">
                    <span style="color:#e4e4e7;font-weight:600"><?= $faixa ?> anos</span>
                    <span style="color:#71717a"><?= $n ?></span>
                </div>
                <div class="dp-bar-bg"><div class="dp-bar-fill" style="width:<?= round($n/$maxId*100) ?>%;background:#8b5cf6"></div></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">📅</div><div class="dp-empty-text">Sem dados</div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ABA: GEOGRAFIA -->
<div class="dp-pane" id="dp-geo">
    <div class="dp-grid-2">
        <div class="dp-card">
            <div class="dp-card-title">🌍 Por País</div>
            <?php if (!empty($paises)): ?>
            <canvas id="chartPaises" height="250"></canvas>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">🌍</div><div class="dp-empty-text">Sem dados de país</div></div>
            <?php endif; ?>
        </div>
        <div class="dp-card">
            <div class="dp-card-title">🏙️ Top Cidades</div>
            <?php if (!empty($distribuicao['cidades'])):
                $maxCidade = max(array_column($distribuicao['cidades'], 'total') ?: [1]);
            ?>
            <table class="dp-table">
                <thead><tr><th>#</th><th>Cidade</th><th>Ouvintes</th><th style="width:120px">%</th></tr></thead>
                <tbody>
                <?php foreach ($distribuicao['cidades'] as $i => $c): ?>
                <tr>
                    <td style="color:#71717a"><?= $i+1 ?></td>
                    <td style="font-weight:600"><?= htmlspecialchars($c['cidade']) ?></td>
                    <td style="color:#00e5ff;font-weight:700"><?= $c['total'] ?></td>
                    <td>
                        <div class="dp-bar-wrap">
                            <div class="dp-bar-bg"><div class="dp-bar-fill" style="width:<?= round($c['total']/$maxCidade*100) ?>%"></div></div>
                            <div class="dp-bar-pct"><?= $c['percentagem'] ?>%</div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">🏙️</div><div class="dp-empty-text">Sem dados de cidade</div></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($distribuicao['bairros'])): ?>
    <div class="dp-card">
        <div class="dp-card-title">📍 Distribuição por Bairro</div>
        <?php
        $maxBairro = max(array_column($distribuicao['bairros'], 'total') ?: [1]);
        ?>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem">
        <?php foreach($distribuicao['bairros'] as $b): ?>
        <div style="padding:.75rem;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:8px">
            <div style="font-size:13px;font-weight:600;color:#e4e4e7;margin-bottom:.4rem"><?= htmlspecialchars($b['bairro']) ?></div>
            <div class="dp-bar-bg"><div class="dp-bar-fill" style="width:<?= round($b['total']/$maxBairro*100) ?>%;background:#8b5cf6"></div></div>
            <div style="font-size:11px;color:#71717a;margin-top:.3rem"><?= $b['total'] ?> ouvinte(s)</div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ABA: AUDIÊNCIA -->
<div class="dp-pane" id="dp-audiencia">
    <div class="dp-grid-2">
        <div class="dp-card">
            <div class="dp-card-title">👥 Distribuição por Género</div>
            <?php if (!empty($generos)): ?>
            <canvas id="chartGenero" height="250"></canvas>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">👤</div><div class="dp-empty-text">Sem dados de género</div></div>
            <?php endif; ?>
        </div>
        <div class="dp-card">
            <div class="dp-card-title">📅 Pirâmide Etária</div>
            <?php if (!empty($idades)): ?>
            <canvas id="chartIdades" height="250"></canvas>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">📅</div><div class="dp-empty-text">Sem dados de idade</div></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dp-card">
        <div class="dp-card-title">🗂️ Perfis por Segmento e Nível</div>
        <?php
        $segmentos = [
            'embaixador' => ['cor'=>'#f59e0b','label'=>'Embaixador','icone'=>'👑'],
            'veterano'   => ['cor'=>'#8b5cf6','label'=>'Veterano',  'icone'=>'🏆'],
            'regular'    => ['cor'=>'#10b981','label'=>'Regular',   'icone'=>'⭐'],
            'novo'       => ['cor'=>'#3b82f6','label'=>'Novo',      'icone'=>'🌱'],
            'inactivo'   => ['cor'=>'#71717a','label'=>'Inactivo',  'icone'=>'💤'],
        ];
        ?>
        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem">
        <?php foreach($segmentos as $seg => $info): ?>
        <div style="text-align:center;padding:1.25rem;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:12px">
            <div style="font-size:28px;margin-bottom:.5rem"><?= $info['icone'] ?></div>
            <div style="font-size:24px;font-weight:800;color:<?= $info['cor'] ?>;margin-bottom:.25rem"><?= $totalOuvintes ?></div>
            <div style="font-size:12px;color:#71717a;font-weight:600"><?= $info['label'] ?></div>
        </div>
        <?php endforeach; ?>
        </div>
        <div style="margin-top:1rem;padding:1rem;background:rgba(245,158,11,0.06);border:1px solid rgba(245,158,11,0.15);border-radius:8px;font-size:12px;color:#a1a1aa">
            💡 A segmentação automática actualiza-se após cada participação. Complete os perfis para activar análises mais precisas.
        </div>
    </div>
</div>

<!-- ABA: GOSTOS MUSICAIS -->
<div class="dp-pane" id="dp-gostos">
    <div class="dp-grid-2">
        <div class="dp-card">
            <div class="dp-card-title">🎵 Géneros Musicais Favoritos</div>
            <?php if (!empty($generosMusicais)): ?>
            <canvas id="chartGM" height="300"></canvas>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">🎵</div><div class="dp-empty-text">Sem dados de géneros</div></div>
            <?php endif; ?>
        </div>
        <div class="dp-card">
            <div class="dp-card-title">📋 Ranking Completo</div>
            <?php if (!empty($generosMusicais)):
                $total_gm = array_sum($generosMusicais);
            ?>
            <table class="dp-table">
                <thead><tr><th>#</th><th>Género</th><th>Ouvintes</th><th>%</th></tr></thead>
                <tbody>
                <?php $rank = 1; foreach($generosMusicais as $g => $n): ?>
                <tr>
                    <td style="color:#71717a"><?= $rank++ ?></td>
                    <td style="font-weight:600"><?= htmlspecialchars($g) ?></td>
                    <td style="color:#8b5cf6;font-weight:700"><?= $n ?></td>
                    <td style="color:#71717a"><?= $total_gm > 0 ? round($n/$total_gm*100) : 0 ?>%</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">📋</div><div class="dp-empty-text">Sem dados</div></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dp-grid-2">
        <div class="dp-card">
            <div class="dp-card-title">📻 Programas Favoritos</div>
            <?php $progs = $dados['programas'] ?? []; ?>
            <?php if (!empty($progs)): ?>
            <table class="dp-table">
                <thead><tr><th>Programa</th><th>Fãs</th></tr></thead>
                <tbody>
                <?php foreach($progs as $p): ?>
                <tr><td style="font-weight:600"><?= htmlspecialchars($p['programa_favorito']) ?></td><td style="color:#00e5ff;font-weight:700"><?= $p['n'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">📻</div><div class="dp-empty-text">Sem dados de programa</div></div>
            <?php endif; ?>
        </div>
        <div class="dp-card">
            <div class="dp-card-title">🎙️ Locutores Favoritos</div>
            <?php $locs = $dados['locutores'] ?? []; ?>
            <?php if (!empty($locs)): ?>
            <table class="dp-table">
                <thead><tr><th>Locutor</th><th>Fãs</th></tr></thead>
                <tbody>
                <?php foreach($locs as $l): ?>
                <tr><td style="font-weight:600"><?= htmlspecialchars($l['locutor_favorito']) ?></td><td style="color:#f59e0b;font-weight:700"><?= $l['n'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">🎙️</div><div class="dp-empty-text">Sem dados de locutor</div></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ABA: ENGAJAMENTO -->
<div class="dp-pane" id="dp-engajamento">
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">
        <div class="dp-card" style="margin-bottom:0">
            <div class="dp-card-title">⏰ Participações por Hora</div>
            <?php if (!empty($horaria)): ?>
            <canvas id="chartHoraria" height="220"></canvas>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">⏰</div><div class="dp-empty-text">Sem dados de actividade</div></div>
            <?php endif; ?>
        </div>
        <div class="dp-card" style="margin-bottom:0">
            <div class="dp-card-title">🕐 Horário Preferido</div>
            <?php if (!empty($horariosPreferidos)):
                $maxHP   = max(array_column($horariosPreferidos,'total') ?: [1]);
                $totalHP = array_sum(array_column($horariosPreferidos,'total'));
                $icones  = ['Madrugada'=>'🌙','Manhã'=>'🌅','Tarde'=>'☀️','Noite'=>'🌆','Qualquer'=>'🎵'];
            ?>
            <?php foreach($horariosPreferidos as $h):
                $pct = round($h['total']/$totalHP*100);
                $ico = '🎵';
                foreach($icones as $k=>$i) { if(str_contains($h['horario'],$k)){$ico=$i;break;} }
            ?>
            <div style="margin-bottom:.875rem">
                <div style="display:flex;justify-content:space-between;margin-bottom:.375rem">
                    <span style="font-size:13px;font-weight:600;color:#e4e4e7"><?= $ico ?> <?= htmlspecialchars($h['horario']) ?></span>
                    <span style="font-size:12px;color:#00e5ff;font-weight:700"><?= $h['total'] ?> <span style="color:#71717a">(<?= $pct ?>%)</span></span>
                </div>
                <div class="dp-bar-bg"><div class="dp-bar-fill" style="width:<?= round($h['total']/$maxHP*100) ?>%"></div></div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <div class="dp-empty"><div class="dp-empty-icon">🕐</div><div class="dp-empty-text">Preencha os perfis para ver</div></div>
            <?php endif; ?>
        </div>
        <div class="dp-card">
            <div class="dp-card-title">🔄 Retenção de Audiência</div>
            <?php
            $retTotal    = $retencao['total'] ?? 0;
            $retRetidos  = $retencao['retidos'] ?? 0;
            $retTaxa     = $retTotal > 0 ? round($retRetidos / $retTotal * 100) : 0;
            ?>
            <div style="text-align:center;padding:1.5rem 1rem">
                <div style="position:relative;width:140px;height:140px;margin:0 auto 1.25rem">
                    <canvas id="chartRetencao" width="140" height="140"></canvas>
                    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center">
                        <div style="font-size:28px;font-weight:900;color:#10b981"><?= $retTaxa ?>%</div>
                        <div style="font-size:10px;color:#71717a">retidos</div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:.5rem">
                    <div style="padding:.75rem;background:rgba(16,185,129,0.08);border-radius:8px;border:1px solid rgba(16,185,129,0.15)">
                        <div style="font-size:20px;font-weight:800;color:#10b981"><?= $retRetidos ?></div>
                        <div style="font-size:11px;color:#71717a">voltaram</div>
                    </div>
                    <div style="padding:.75rem;background:rgba(239,68,68,0.08);border-radius:8px;border:1px solid rgba(239,68,68,0.15)">
                        <div style="font-size:20px;font-weight:800;color:#ef4444"><?= $retTotal - $retRetidos ?></div>
                        <div style="font-size:11px;color:#71717a">apenas 1 vez</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dp-card">
        <div class="dp-card-title">📊 Como Conheceram a Rádio</div>
        <?php $origens = $dados['origens'] ?? []; ?>
        <?php if (!empty($origens)):
            $maxOrigem = max(array_column($origens, 'n') ?: [1]);
            $totalOrigem = array_sum(array_column($origens, 'n'));
        ?>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem">
        <?php foreach($origens as $o):
            $pct = round($o['n']/$totalOrigem*100);
        ?>
        <div style="padding:1rem;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:10px">
            <div style="display:flex;justify-content:space-between;margin-bottom:.5rem">
                <span style="font-size:13px;font-weight:600;color:#e4e4e7"><?= htmlspecialchars($o['como_conheceu']) ?></span>
                <span style="font-size:13px;font-weight:700;color:#00e5ff"><?= $o['n'] ?></span>
            </div>
            <div class="dp-bar-bg"><div class="dp-bar-fill" style="width:<?= $pct ?>%;background:#00e5ff"></div></div>
            <div style="font-size:11px;color:#71717a;margin-top:.3rem"><?= $pct ?>% do total</div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="dp-empty"><div class="dp-empty-icon">🤷</div><div class="dp-empty-text">Sem dados de origem</div></div>
        <?php endif; ?>
    </div>
</div>

</div><!-- /.dp -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Tab switcher
function dpTab(id, btn) {
    document.querySelectorAll('.dp-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.dp-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('dp-' + id).classList.add('active');
    btn.classList.add('active');
    // Render charts lazy
    if (!window['dpChartInit_' + id]) {
        window['dpChartInit_' + id] = true;
        renderCharts(id);
    }
}

const COLORS = ['#00e5ff','#8b5cf6','#10b981','#f59e0b','#ef4444','#3b82f6','#ec4899','#14b8a6','#fbbf24','#06b6d4'];

const chartDefaults = {
    plugins: { legend: { labels: { color:'#a1a1aa', font:{ size:12 } } } },
    scales: {
        x: { ticks:{ color:'#71717a' }, grid:{ color:'rgba(255,255,255,0.05)' } },
        y: { ticks:{ color:'#71717a' }, grid:{ color:'rgba(255,255,255,0.05)' }, beginAtZero:true }
    }
};

function renderCharts(tab) {
    if (tab === 'geral') {
        // Crescimento
        const cEl = document.getElementById('chartCrescent');
        if (cEl && <?= json_encode(!empty($crescimento)) ?>) {
            new Chart(cEl, { type:'line', data:{
                labels: <?= $jCrescLabels ?>,
                datasets:[{ label:'Novos', data:<?= $jCrescData ?>, borderColor:'#00e5ff', backgroundColor:'rgba(0,229,255,0.08)', fill:true, tension:.4, pointRadius:3 }]
            }, options:{...chartDefaults, plugins:{...chartDefaults.plugins, legend:{display:false}}} });
        }
        // Género Geral
        const gEl = document.getElementById('chartGeneroGeral');
        if (gEl && <?= json_encode(!empty($generos)) ?>) {
            new Chart(gEl, { type:'doughnut', data:{
                labels: <?= $jGenerosLabels ?>.map(g => g==='masculino'?'Masculino':g==='feminino'?'Feminino':'Outro'),
                datasets:[{ data:<?= $jGenerosData ?>, backgroundColor:['#3b82f6','#ec4899','#8b5cf6'], borderWidth:0 }]
            }, options:{ plugins:{ legend:{ position:'bottom', labels:{color:'#a1a1aa'} } } } });
        }
    }
    if (tab === 'geo') {
        const pEl = document.getElementById('chartPaises');
        if (pEl && <?= json_encode(!empty($paises)) ?>) {
            new Chart(pEl, { type:'doughnut', data:{
                labels: <?= $jPaisesLabels ?>,
                datasets:[{ data:<?= $jPaisesData ?>, backgroundColor:COLORS, borderWidth:0 }]
            }, options:{ plugins:{ legend:{ position:'bottom', labels:{color:'#a1a1aa'} } } } });
        }
    }
    if (tab === 'audiencia') {
        const gEl = document.getElementById('chartGenero');
        if (gEl && <?= json_encode(!empty($generos)) ?>) {
            new Chart(gEl, { type:'pie', data:{
                labels: <?= $jGenerosLabels ?>.map(g => g==='masculino'?'Masculino':g==='feminino'?'Feminino':'Outro'),
                datasets:[{ data:<?= $jGenerosData ?>, backgroundColor:['#3b82f6','#ec4899','#8b5cf6'], borderWidth:0 }]
            }, options:{ plugins:{ legend:{ position:'bottom', labels:{color:'#a1a1aa'} } } } });
        }
        const iEl = document.getElementById('chartIdades');
        if (iEl && <?= json_encode(!empty($idades)) ?>) {
            new Chart(iEl, { type:'bar', data:{
                labels: <?= $jIdadesLabels ?>,
                datasets:[{ label:'Ouvintes', data:<?= $jIdadesData ?>, backgroundColor:'#8b5cf6', borderRadius:6 }]
            }, options:{...chartDefaults, plugins:{...chartDefaults.plugins, legend:{display:false}}} });
        }
    }
    if (tab === 'gostos') {
        const gmEl = document.getElementById('chartGM');
        if (gmEl && <?= json_encode(!empty($generosMusicais)) ?>) {
            new Chart(gmEl, { type:'bar', data:{
                labels: <?= $jGMLabels ?>,
                datasets:[{ label:'Ouvintes', data:<?= $jGMData ?>, backgroundColor:COLORS, borderRadius:6 }]
            }, options:{...chartDefaults, indexAxis:'y', plugins:{...chartDefaults.plugins, legend:{display:false}}} });
        }
    }
    if (tab === 'engajamento') {
        const hEl = document.getElementById('chartHoraria');
        if (hEl && <?= json_encode(!empty($horaria)) ?>) {
            new Chart(hEl, { type:'bar', data:{
                labels: <?= $jHoraLabels ?>.map(h => h+'h'),
                datasets:[{ label:'Participações', data:<?= $jHoraData ?>, backgroundColor:'rgba(0,229,255,0.7)', borderRadius:4 }]
            }, options:{...chartDefaults, plugins:{...chartDefaults.plugins, legend:{display:false}}} });
        }
        const rEl = document.getElementById('chartRetencao');
        if (rEl) {
            const retidos = <?= $retencao['retidos'] ?? 0 ?>;
            const total   = <?= $retencao['total'] ?? 0 ?>;
            new Chart(rEl, { type:'doughnut', data:{
                labels:['Retidos','Únicos'],
                datasets:[{ data:[retidos, Math.max(0,total-retidos)], backgroundColor:['#10b981','rgba(255,255,255,0.08)'], borderWidth:0 }]
            }, options:{ cutout:'75%', plugins:{legend:{display:false}} } });
        }
    }
}

// Render da aba inicial
renderCharts('geral');
</script>
