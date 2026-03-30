<?php
/**
 * RNB OS — Navegação Global Unificada
 * Incluir no topo de qualquer módulo: require __DIR__.'/rnb-nav.php';
 */
$_rnb_sid   = $_rnb_sid ?? 1;
$_rnb_atual = $_rnb_atual ?? '';

// Dados em tempo real
try {
    $__pdo = new PDO('mysql:host=127.0.0.1;dbname=azuracast;charset=utf8mb4','azuracast','CKxR234fxpJG');
    $__listeners  = 0;
    $__np_song    = '';
    $__np_artist  = '';
    $__alertas    = 0;

    // Now playing via API
    $__np = @json_decode(@file_get_contents("http://localhost/api/station/{$_rnb_sid}/nowplaying"), true);
    if ($__np) {
        $__listeners = $__np['listeners']['current'] ?? 0;
        $__np_song   = $__np['now_playing']['song']['title'] ?? '';
        $__np_artist = $__np['now_playing']['song']['artist'] ?? '';
    }

    // Alertas
    $__prop = (int)$__pdo->query("SELECT COUNT(*) FROM rnb_propostas WHERE station_id={$_rnb_sid} AND estado IN('enviada','em_negociacao')")->fetchColumn();
    $__venc = (int)$__pdo->query("SELECT COUNT(*) FROM fp_contas_movimento WHERE station_id={$_rnb_sid} AND estado='pendente' AND data_vencimento<=CURDATE()")->fetchColumn();
    $__alertas = $__prop + $__venc;
} catch(Exception $e) {
    $__listeners = $__alertas = 0;
    $__np_song = $__np_artist = '';
}

$__modulos = [
    'dashboard'   => ['url'=>"/public/dashboard/{$_rnb_sid}",     'ico'=>'speedometer2',    'nome'=>'Dashboard',    'cor'=>'#00e5ff'],
    'audiencia'   => ['url'=>"/public/pulso/{$_rnb_sid}",          'ico'=>'people-fill',     'nome'=>'Audiência',    'cor'=>'#00e5ff'],
    'comercial'   => ['url'=>"/public/comercial/{$_rnb_sid}",      'ico'=>'building',        'nome'=>'Comercial',    'cor'=>'#f59e0b'],
    'financas'    => ['url'=>"/public/financas/{$_rnb_sid}",       'ico'=>'wallet2',         'nome'=>'Finanças',     'cor'=>'#10b981'],
    'studio'      => ['url'=>"/pulso/locutor",                      'ico'=>'mic-fill',        'nome'=>'Studio',       'cor'=>'#a78bfa'],
    'programacao' => ['url'=>"/public/programacao/{$_rnb_sid}",    'ico'=>'calendar3',       'nome'=>'Programação',  'cor'=>'#f472b6'],
    'news'        => ['url'=>"/public/news/{$_rnb_sid}",           'ico'=>'newspaper',       'nome'=>'News',         'cor'=>'#fb923c'],
    'rh'          => ['url'=>"/public/rh/{$_rnb_sid}",             'ico'=>'person-badge',    'nome'=>'RH',           'cor'=>'#38bdf8'],
];
?>
<style>
.rnb-nav{
    position:fixed;left:0;top:50%;transform:translateY(-50%);
    z-index:500;
    display:flex;flex-direction:column;gap:3px;
    padding:8px 5px;
    background:rgba(5,5,16,.88);
    backdrop-filter:blur(16px);
    border-right:1px solid rgba(255,255,255,.07);
    border-radius:0 12px 12px 0;
    box-shadow:2px 0 20px rgba(0,0,0,.3);
    transition:all .3s cubic-bezier(.4,0,.2,1);
    opacity:.5;
}
.rnb-nav:hover{opacity:1;padding:8px 8px}
.rnb-nav-item{
    position:relative;
    display:flex;align-items:center;justify-content:center;
    width:30px;height:30px;border-radius:7px;
    text-decoration:none;
    color:rgba(255,255,255,.4);
    font-size:14px;
    transition:all .2s ease;
    cursor:pointer;
    flex-shrink:0;
}
.rnb-nav-item:hover{
    background:rgba(255,255,255,.07);
    color:rgba(255,255,255,.9);
    text-decoration:none;
}
.rnb-nav-item.on{
    color:#fff;
}
.rnb-nav-item.em-breve{opacity:.35;cursor:not-allowed}
.rnb-nav-item.em-breve:hover{background:none;color:rgba(255,255,255,.35)}

/* Tooltip */
.rnb-nav-item::after{
    content:attr(data-tip);
    position:absolute;left:calc(100% + 10px);top:50%;transform:translateY(-50%);
    background:rgba(26,26,46,.98);
    border:1px solid rgba(255,255,255,.1);
    color:#fff;font-size:11px;font-weight:600;
    padding:5px 10px;border-radius:7px;
    white-space:nowrap;pointer-events:none;
    opacity:0;transition:opacity .15s;
    font-family:'Inter',-apple-system,sans-serif;
}
.rnb-nav-item:hover::after{opacity:1}

/* Indicador activo */
.rnb-nav-item.on::before{
    content:'';
    position:absolute;left:-6px;top:50%;transform:translateY(-50%);
    width:3px;height:20px;border-radius:0 3px 3px 0;
    background:var(--rnb-cor,#00e5ff);
}

/* Divisor */
.rnb-nav-sep{
    width:24px;height:1px;
    background:rgba(255,255,255,.08);
    margin:3px auto;
    border-radius:1px;
}

/* NP mini pill */
.rnb-np{
    position:fixed;bottom:12px;left:50%;transform:translateX(-50%);
    z-index:9998;
    display:flex;align-items:center;gap:10px;
    padding:8px 16px;
    background:rgba(5,5,16,.92);
    backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.1);
    border-radius:30px;
    box-shadow:0 4px 24px rgba(0,0,0,.4);
    font-family:'Inter',-apple-system,sans-serif;
    font-size:12px;
    max-width:600px;
    transition:all .3s ease;
}
.rnb-np-dot{
    width:6px;height:6px;border-radius:50%;
    background:#10b981;
    box-shadow:0 0 8px #10b981;
    animation:rnb-pulse 1.4s infinite;
    flex-shrink:0;
}
@keyframes rnb-pulse{0%,100%{opacity:1}50%{opacity:.4}}
.rnb-np-song{
    font-weight:700;color:#fff;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
    max-width:250px;
}
.rnb-np-art{color:rgba(255,255,255,.5);font-weight:400}
.rnb-np-list{
    display:flex;align-items:center;gap:5px;
    color:rgba(255,255,255,.5);
    border-left:1px solid rgba(255,255,255,.1);
    padding-left:10px;flex-shrink:0;
}
.rnb-np-list b{color:#00e5ff;font-size:13px;font-weight:800}
.rnb-np-alerta{
    display:flex;align-items:center;justify-content:center;
    width:18px;height:18px;border-radius:50%;
    background:#ef4444;color:#fff;
    font-size:9px;font-weight:800;
    border-left:1px solid rgba(255,255,255,.1);
    margin-left:2px;flex-shrink:0;
}
.rnb-np-close{
    color:rgba(255,255,255,.3);cursor:pointer;font-size:14px;
    padding:0 2px;transition:color .15s;flex-shrink:0;
}
.rnb-np-close:hover{color:rgba(255,255,255,.8)}
</style>

<!-- NAVEGAÇÃO LATERAL -->
<nav class="rnb-nav" id="rnb-nav">
    <?php foreach($__modulos as $key => $mod):
        $isOn = $key === $_rnb_atual;
        $isEB = !empty($mod['em_breve']);
        $tip  = $mod['nome'] . ($isEB ? ' (em breve)' : '');
        $cls  = 'rnb-nav-item' . ($isOn?' on':'') . ($isEB?' em-breve':'');
        $href = $isEB ? '#' : $mod['url'];
        if($key === 'studio'): // divisor antes do Studio
    ?>
    <div class="rnb-nav-sep"></div>
    <?php endif ?>
    <?php if($key === 'news'): // divisor antes do News ?>
    <div class="rnb-nav-sep"></div>
    <?php endif ?>
    <a href="<?= $href ?>"
       class="<?= $cls ?>"
       data-tip="<?= htmlspecialchars($tip) ?>"
       style="--rnb-cor:<?= $mod['cor'] ?><?= $isOn ? ';background:'.str_replace(')','.12)',$mod['cor'].'18') : '' ?>">
        <i class="bi bi-<?= $mod['ico'] ?>"></i>
    </a>
    <?php endforeach ?>
</nav>

<!-- NOW PLAYING PILL -->
<?php if($__np_song): ?>
<div class="rnb-np" id="rnb-np-pill">
    <div class="rnb-np-dot"></div>
    <span class="rnb-np-song">
        <?= htmlspecialchars($__np_song) ?>
        <?php if($__np_artist): ?>
        <span class="rnb-np-art"> — <?= htmlspecialchars($__np_artist) ?></span>
        <?php endif ?>
    </span>
    <div class="rnb-np-list">
        <i class="bi bi-headphones" style="font-size:11px"></i>
        <b><?= $__listeners ?></b>
    </div>
    <?php if($__alertas > 0): ?>
    <div class="rnb-np-alerta" title="<?= $__alertas ?> alertas pendentes">
        <?= $__alertas ?>
    </div>
    <?php endif ?>
    <span class="rnb-np-close" onclick="document.getElementById('rnb-np-pill').style.display='none'">✕</span>
</div>
<?php endif ?>
