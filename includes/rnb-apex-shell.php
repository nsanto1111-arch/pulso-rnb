<?php
/**
 * RNB OS — APEX Shell v4.1
 * Sistema de layout base para todos os módulos
 */

define('APEX_TOKENS', [
    'void'=>'#06060F','depth'=>'#09091A','base'=>'#0C0C20',
    'lift'=>'#10102A','raise'=>'#141438',
    'spark'=>'#00EBB5','sparkD'=>'rgba(0,235,181,0.07)',
    'live'=>'#FF1F4B','liveD'=>'rgba(255,31,75,0.08)',
    'amber'=>'#F0A500','amberD'=>'rgba(240,165,0,0.08)',
    'violet'=>'#9D6EFF','violetD'=>'rgba(157,110,255,0.10)',
    'ink'=>'#FFFFFF','ink2'=>'#8A8A9E','ink3'=>'#3E3E6A','ink4'=>'#1C1C40',
    'rule'=>'rgba(255,255,255,0.055)','rule2'=>'rgba(255,255,255,0.10)',
    'fr'=>"'Fraunces',Georgia,serif",'sy'=>"'Syne',sans-serif",
    'dm'=>"'DM Sans',sans-serif",'fi'=>"'Fira Code',monospace",
]);

define('APEX_NAV', [
    'dashboard'   => ['label'=>'Dashboard',   'url_key'=>'dashboard',   'icon'=>'M3 3h8v8H3zm10 0h8v8h-8zM3 13h8v8H3zm10 0h8v8h-8z'],
    'audiencia'   => ['label'=>'Audiência',   'url_key'=>'pulso',       'icon'=>'M2 12 Q5 4 8 12 Q11 20 14 12 Q17 4 20 12'],
    'comercial'   => ['label'=>'Comercial',   'url_key'=>'comercial',   'icon'=>'M7 7V5a5 5 0 0110 0v2h3v14H4V7h3zm2 0h6V5a3 3 0 00-6 0v2z'],
    'financas'    => ['label'=>'Finanças',    'url_key'=>'financas',    'icon'=>'M3 17l5-5 4 4 9-9M17 3h4v4'],
    'rh'          => ['label'=>'RH',          'url_key'=>'rh',          'icon'=>'M9 12a4 4 0 100-8 4 4 0 000 8zm-7 8a7 7 0 0114 0'],
    'news'        => ['label'=>'Newsroom',    'url_key'=>'news',        'icon'=>'M4 4h16v2H4zm0 4h10v2H4zm0 4h16v2H4zm0 4h10v2H4z'],
    'programacao' => ['label'=>'Programação', 'url_key'=>'programacao', 'icon'=>'M4 5h16M4 5v14h16V5M9 3v4m6-4v4M4 10h16'],
    'studio'      => ['label'=>'Studio',      'url_key'=>'locutor',     'icon'=>'M12 2a4 4 0 014 4v6a4 4 0 01-8 0V6a4 4 0 014-4zM5 11a7 7 0 0014 0M12 19v3m-3 0h6'],
]);

function rnb_apex_waveform(): string {
    $bars=[
        [5,'apex-wave-lo','0s',.55,.70],[18,'apex-wave-mid','.18s',.75,.75],
        [24,'apex-wave-hi','.36s',.65,.66],[9,'apex-wave-pulse','.09s',.80,.80],
        [14,'apex-wave-lo','.27s',.70,.73],[22,'apex-wave-mid','.45s',.75,.57],
        [7,'apex-wave-hi','.54s',.60,.70],[26,'apex-wave-pulse','.12s',.80,.65],
        [11,'apex-wave-lo','.33s',.70,.72],[19,'apex-wave-mid','.21s',.65,.78],
        [8,'apex-wave-hi','.48s',.75,.60],[23,'apex-wave-pulse','.06s',.70,.65],
        [15,'apex-wave-lo','.39s',.80,.80],[6,'apex-wave-mid','.57s',.65,.67],
    ];
    $o='<div class="apex-waveform">';
    $t=APEX_TOKENS;
    foreach($bars as [$h,$a,$d,$op,$sp])
        $o.="<div class=\"apex-wave-bar\" style=\"height:{$h}px;animation:{$a} {$sp}s ease-in-out {$d} infinite;opacity:{$op}\"></div>";
    return $o.'</div>';
}

function rnb_apex_pill(string $s): string {
    $t=APEX_TOKENS;
    $m=['live'=>['Ao Vivo','live',true],'active'=>['Activo','active',false],
        'activo'=>['Activo','active',false],'leave'=>['Férias','leave',false],
        'ferias'=>['Férias','leave',false],'done'=>['Fim','done',false],'next'=>['Próximo','next',false]];
    [$l,$c,$p]=$m[$s]??$m['next'];
    $d=$p?'<span class="apex-pill-dot" style="background:var(--live)"></span>':'';
    return "<span class=\"apex-pill apex-pill-{$c}\">{$d}".htmlspecialchars($l)."</span>";
}

function rnb_apex_score(int $sc,int $d=0): string {
    $t=APEX_TOKENS;
    $c=$sc>=85?$t['spark']:($sc>=70?$t['violet']:$t['amber']);
    $dh='';
    if($d!==0){$dc=$d>0?$t['spark']:$t['live'];$ds=$d>0?"+{$d}":(string)$d;$dh="<span class=\"apex-score-delta\" style=\"color:{$dc}\">{$ds}</span>";}
    return "<div style=\"display:flex;align-items:center;gap:6px;flex-shrink:0\"><div class=\"apex-score-bar\"><div class=\"apex-score-fill\" style=\"width:{$sc}%;background:{$c}\"></div></div><span class=\"apex-score-val\">{$sc}</span>{$dh}</div>";
}

function rnb_apex_panel_header(string $title,string $sub='',string $link='',string $href='#'): string {
    $sh=$sub?"<span class=\"apex-panel-sub\">".htmlspecialchars($sub)."</span>":'';
    $lh=$link?"<a class=\"apex-panel-link\" href=\"{$href}\">".htmlspecialchars($link)."</a>":'';
    return "<div class=\"apex-panel-header\"><div style=\"display:flex;align-items:baseline\"><span class=\"apex-panel-title\">".htmlspecialchars($title)."</span>{$sh}</div>{$lh}</div>";
}

function rnb_apex_tel_metric(string $l,string $v,string $delta,string $color): string {
    return "<div class=\"apex-tel-metric\" data-hover><div class=\"apex-tel-metric-accent\" style=\"background:{$color}\"></div><div class=\"apex-tel-metric-label\">".htmlspecialchars($l)."</div><div class=\"apex-tel-metric-val\">".htmlspecialchars($v)."</div><div class=\"apex-tel-metric-delta\" style=\"color:{$color}\">".htmlspecialchars($delta)."</div></div>";
}

function rnb_apex_default_telemetry(int $sid): string {
    $t=APEX_TOKENS;
    $listeners='—';
    try {
        $ctx=stream_context_create(['http'=>['timeout'=>1]]);
        $json=@file_get_contents("http://localhost/api/station/{$sid}/now-playing",false,$ctx);
        if($json){$d=json_decode($json,true);$listeners=number_format((int)($d['listeners']['current']??0),0,',','.');}
    } catch(\Throwable $e){}

    $pts=[18,22,19,25,21,28,24,30,26,32,28,35,30,33,29,36,31,34,27,38];
    $maxP=max($pts);$minP=min($pts);$svgW=240;$svgH=30;
    $coords=[];
    foreach($pts as $i=>$p){
        $x=round($i/(count($pts)-1)*$svgW,1);
        $y=round($svgH-(($p-$minP)/max(1,$maxP-$minP))*($svgH-4)-2,1);
        $coords[]="{$x},{$y}";
    }
    $poly=implode(' ',$coords);
    $area="M0,{$svgH} L".implode(' L',$coords)." L{$svgW},{$svgH} Z";

    $metrics=[
        ['Receita / Mês','—','— Kz',$t['spark']],
        ['Campanhas','—','activas',$t['amber']],
        ['Alertas','—','activos',$t['live']],
        ['Funcionários','—','registados',$t['ink2']],
    ];
    try {
        $pdo=new PDO('mysql:host=127.0.0.1;dbname=azuracast;charset=utf8mb4','azuracast','CKxR234fxpJG',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_SILENT,PDO::ATTR_TIMEOUT=>1]);
        $func=(int)($pdo->query("SELECT COUNT(*) FROM rnb_funcionarios WHERE station_id={$sid} AND estado='activo'")->fetchColumn()??0);
        $alrt=(int)($pdo->query("SELECT COUNT(*) FROM rnb_rh_alertas WHERE station_id={$sid} AND resolvido=0 LIMIT 1")->fetchColumn()??0);
        $metrics[2]=['Alertas',$alrt?:(string)0,$alrt?"{$alrt} activos":'Sistema OK',$alrt>0?$t['live']:$t['spark']];
        $metrics[3]=['Funcionários',$func?:(string)'—',$func?"{$func} activos":'—',$t['ink2']];
    } catch(\Throwable $e){}

    $mh='';
    foreach($metrics as [$l,$v,$delta,$color]) $mh.=rnb_apex_tel_metric($l,$v,$delta,$color);

    return <<<HTML
<div class="apex-tel-listeners">
  <div class="apex-tel-label">Ouvintes ao Vivo</div>
  <div class="apex-tel-big" id="apex-listeners">{$listeners}</div>
  <div class="apex-tel-delta">↑ Actualização automática</div>
  <svg width="{$svgW}" height="{$svgH}" viewBox="0 0 {$svgW} {$svgH}" style="margin-top:12px;display:block" preserveAspectRatio="none">
    <defs><linearGradient id="sfill" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="{$t['spark']}" stop-opacity=".15"/>
      <stop offset="100%" stop-color="{$t['spark']}" stop-opacity="0"/>
    </linearGradient></defs>
    <path d="{$area}" fill="url(#sfill)"/>
    <polyline points="{$poly}" fill="none" stroke="{$t['spark']}" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" opacity=".8"/>
  </svg>
</div>
{$mh}
<div>
  <div class="apex-panel-header">
    <span class="apex-panel-title">Financeiro</span>
    <a class="apex-panel-link" href="/public/financas/{$sid}">Finanças →</a>
  </div>
  <div class="apex-tel-fin-row" style="border-bottom:1px solid {$t['rule']}">
    <span class="apex-tel-fin-label">Receita / Mês</span>
    <span class="apex-tel-fin-val" style="color:{$t['spark']}">—</span>
  </div>
  <div class="apex-tel-fin-row" style="border-bottom:1px solid {$t['rule']}">
    <span class="apex-tel-fin-label">A receber</span>
    <span class="apex-tel-fin-val" style="color:{$t['ink']}">—</span>
  </div>
  <div class="apex-tel-fin-row">
    <span class="apex-tel-fin-label">A pagar</span>
    <span class="apex-tel-fin-val" style="color:{$t['amber']}">—</span>
  </div>
</div>
<script>
(function(){
  function upd(){
    fetch('/api/station/{$sid}/now-playing')
      .then(r=>r.json())
      .then(d=>{
        const el=document.getElementById('apex-listeners');
        if(el&&d.listeners)el.textContent=d.listeners.current.toLocaleString('pt-PT');
        const lives=document.querySelectorAll('.apex-ticker-item.live');
        if(lives.length&&d.now_playing&&d.now_playing.song){
          const s=d.now_playing.song;
          lives.forEach(el=>el.textContent='● NO AR · '+s.title+' — '+s.artist);
        }
      }).catch(()=>{});
  }
  upd();setInterval(upd,15000);
})();
</script>
HTML;
}

function rnb_apex_layout(string $titulo,string $corpo,int $sid,string $modulo='dashboard',array $user=[],array $opts=[]): string {
    $t=APEX_TOKENS;
    $nome=htmlspecialchars($user['nome']??'Newton');
    $ini=strtoupper(substr($user['nome']??'N',0,1));
    $role=htmlspecialchars($user['role']??'Admin');
    $date=(new DateTime())->format('D, d M Y');
    $showTicker=$opts['ticker']??true;

    // CSS completo
    $css=<<<CSS
@import url('https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;1,9..144,300;1,9..144,400;1,9..144,600&family=Syne:wght@500;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500&family=Fira+Code:wght@300;400;500&display=swap');
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{color-scheme:dark}
html,body{height:100%;background:{$t['void']};overflow:hidden}
body{font-family:'DM Sans',sans-serif;font-size:13px;color:{$t['ink']};line-height:1.55;-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
@keyframes apex-onair{0%,100%{opacity:1}50%{opacity:.25}}
@keyframes apex-scan-badge{0%{left:-40%;opacity:0}5%{opacity:1}95%{opacity:1}100%{left:140%;opacity:0}}
@keyframes apex-ticker{from{transform:translateX(0)}to{transform:translateX(-50%)}}
@keyframes apex-wave-lo{0%,100%{transform:scaleY(1)}50%{transform:scaleY(2.8)}}
@keyframes apex-wave-mid{0%,100%{transform:scaleY(1.8)}50%{transform:scaleY(.5)}}
@keyframes apex-wave-hi{0%,100%{transform:scaleY(2.6)}50%{transform:scaleY(.9)}}
@keyframes apex-wave-pulse{0%,100%{transform:scaleY(1.2)}50%{transform:scaleY(2.2)}}
@keyframes apex-flipin{from{opacity:0;transform:translateY(-5px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes apex-fadein{from{opacity:0;transform:translateY(5px)}to{opacity:1;transform:translateY(0)}}
@keyframes apex-hero-scan{0%{transform:translateY(-100%);opacity:0}8%{opacity:1}92%{opacity:1}100%{transform:translateY(300%);opacity:0}}
.apex-root{animation:apex-fadein .4s ease both}
[data-hover]:hover{background:{$t['lift']} !important;transition:background .1s !important}
[data-ni]:hover{background:{$t['sparkD']} !important}
[data-ni]:hover [data-tip]{opacity:1 !important;transform:translateX(0) !important}
.apex-panel-link:hover{color:{$t['ink2']} !important}
::-webkit-scrollbar{width:2px}::-webkit-scrollbar-thumb{background:{$t['ink4']}}
.apex-shell{display:flex;flex-direction:column;height:100vh;overflow:hidden;position:relative}
.apex-scanlines{position:absolute;inset:0;pointer-events:none;z-index:9999;background-image:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,0.04) 2px,rgba(0,0,0,0.04) 4px)}
.apex-ticker{height:30px;flex-shrink:0;background:{$t['depth']};border-bottom:1px solid {$t['rule']};overflow:hidden;display:flex;align-items:center;position:relative}
.apex-ticker-badge{position:absolute;left:0;top:0;bottom:0;display:flex;align-items:center;padding:0 12px;background:{$t['depth']};z-index:3;border-right:1px solid {$t['rule']}}
.apex-ticker-badge span{font-family:'Syne',sans-serif;font-size:9px;font-weight:800;color:{$t['void']};background:{$t['spark']};padding:2px 7px;letter-spacing:.8px}
.apex-ticker-fade-l{position:absolute;left:68px;top:0;bottom:0;width:40px;background:linear-gradient(to right,{$t['depth']},transparent);z-index:2;pointer-events:none}
.apex-ticker-fade-r{position:absolute;right:80px;top:0;bottom:0;width:40px;background:linear-gradient(to left,{$t['depth']},transparent);z-index:2;pointer-events:none}
.apex-ticker-scroll{display:flex;padding-left:110px;animation:apex-ticker 55s linear infinite}
.apex-ticker-item{font-family:'Fira Code',monospace;font-size:9px;color:#fff;white-space:nowrap;width:220px;flex-shrink:0;letter-spacing:.8px;opacity:.75}
.apex-ticker-item.live{color:{$t['live']};opacity:1}
.apex-ticker-clock{position:absolute;right:0;top:0;bottom:0;display:flex;align-items:center;padding:0 14px;background:{$t['depth']};border-left:1px solid {$t['rule']};z-index:3;font-family:'Fira Code',monospace;font-size:11px;color:{$t['ink']};letter-spacing:.5px}
.apex-body{flex:1;display:flex;overflow:hidden}
.apex-sidebar{width:52px;flex-shrink:0;background:{$t['depth']};border-right:1px solid {$t['rule']};display:flex;flex-direction:column;align-items:center;padding:14px 0}
.apex-logo{width:28px;height:28px;background:{$t['spark']};display:flex;align-items:flex-end;justify-content:center;gap:2.5px;padding:5px 5px 4px;margin-bottom:18px;flex-shrink:0}
.apex-logo-bar{width:3px;background:{$t['void']};border-radius:1px}
.apex-sep{width:24px;height:1px;background:{$t['rule']};margin-bottom:12px}
.apex-nav{flex:1;display:flex;flex-direction:column;gap:2px;width:100%;align-items:center;padding:0 8px}
.apex-ni{position:relative;display:flex;align-items:center;justify-content:center;width:36px;height:36px;cursor:pointer;border-radius:6px;transition:background .1s}
.apex-ni.active{background:{$t['sparkD']}}
.apex-ni-accent{position:absolute;left:-8px;top:50%;transform:translateY(-50%);width:2px;height:20px;background:{$t['spark']};display:none}
.apex-ni.active .apex-ni-accent{display:block}
.apex-tip{position:absolute;left:calc(100% + 10px);top:50%;transform:translateY(-50%) translateX(-4px);background:{$t['raise']};color:{$t['ink']};font-family:'Fira Code',monospace;font-size:9px;padding:4px 9px;white-space:nowrap;pointer-events:none;opacity:0;transition:all .15s;z-index:100;border:1px solid {$t['rule2']}}
.apex-user{margin-top:12px;width:28px;height:28px;border-radius:50%;background:{$t['violetD']};border:1px solid rgba(157,110,255,.25);display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:10px;font-weight:700;color:{$t['violet']};cursor:pointer}
.apex-content{flex:1;display:flex;overflow:hidden;min-width:0}
.apex-editorial{flex:3 1 0;min-width:0;display:flex;flex-direction:column;overflow:hidden;border-right:1px solid {$t['rule']}}
.apex-telemetry{flex:1 1 0;width:clamp(220px,25%,300px);flex-shrink:0;display:flex;flex-direction:column;overflow:hidden;background:{$t['depth']};overflow-y:auto}
.apex-topbar{height:44px;display:flex;align-items:center;justify-content:space-between;padding:0 24px;border-bottom:1px solid {$t['rule']};flex-shrink:0}
.apex-topbar-title{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:{$t['ink']};letter-spacing:-.3px}
.apex-topbar-date{font-family:'Fira Code',monospace;font-size:10px;color:{$t['ink3']};margin-left:12px}
.apex-topbar-actions{display:flex;gap:8px}
.apex-btn{padding:5px 12px;font-size:11px;font-family:'DM Sans',sans-serif;cursor:pointer;transition:opacity .12s}
.apex-btn:hover{opacity:.78}
.apex-btn-ghost{background:transparent;border:1px solid {$t['rule2']};color:{$t['ink2']}}
.apex-btn-primary{background:{$t['spark']};border:none;color:{$t['void']};font-weight:600}
.apex-hero{padding:28px 24px 22px;border-bottom:1px solid {$t['rule']};position:relative;overflow:hidden}
.apex-onair{display:inline-flex;align-items:center;gap:8px;padding:4px 11px;background:{$t['live']};position:relative;overflow:hidden}
.apex-onair-scan{position:absolute;top:0;bottom:0;width:35%;background:rgba(255,255,255,0.07);animation:apex-scan-badge 3s linear infinite;pointer-events:none}
.apex-onair-dot{width:6px;height:6px;border-radius:50%;background:#fff;display:inline-block;animation:apex-onair 1.6s ease-in-out infinite;flex-shrink:0}
.apex-onair-label{font-family:'Fira Code',monospace;font-size:9px;color:#fff;letter-spacing:2px;text-transform:uppercase;font-weight:500}
.apex-hero-src{font-family:'Fira Code',monospace;font-size:9px;color:{$t['ink3']};letter-spacing:1.5px;text-transform:uppercase;margin-left:12px}
.apex-waveform{display:flex;align-items:center;gap:3px;height:30px}
.apex-wave-bar{width:2.5px;background:{$t['spark']};border-radius:1.5px;transform-origin:center bottom}
.apex-track-title{font-family:'Fraunces',Georgia,serif;font-size:52px;font-weight:400;font-style:italic;color:{$t['ink']};line-height:1;letter-spacing:-2px;margin-bottom:8px;font-variant-numeric:oldstyle-nums}
.apex-track-artist{font-family:'DM Sans',sans-serif;font-size:17px;font-weight:300;color:#8A8A9E;margin-bottom:8px}
.apex-track-album{font-family:'Fira Code',monospace;font-size:10px;color:{$t['ink3']};margin-left:16px}
.apex-track-meta{display:flex;gap:8px;margin-bottom:20px}
.apex-track-tag{font-family:'Fira Code',monospace;font-size:9px;color:{$t['ink3']};padding:2px 8px;border:1px solid {$t['rule']};letter-spacing:.6px}
.apex-progress{position:relative;height:2px;background:{$t['ink4']};margin-bottom:7px}
.apex-progress-fill{position:absolute;left:0;top:0;height:100%;background:{$t['spark']};transition:width 1s linear}
.apex-progress-cursor{position:absolute;top:50%;transform:translate(-50%,-50%);width:9px;height:9px;border-radius:50%;background:{$t['spark']};border:2px solid {$t['base']};transition:left 1s linear}
.apex-progress-times{display:flex;justify-content:space-between;font-family:'Fira Code',monospace;font-size:10px;color:{$t['ink3']}}
.apex-progress-next{color:{$t['ink2']};font-size:11px}
.apex-progress-next span{color:{$t['ink']}}
.apex-grid2{display:grid;grid-template-columns:1fr 1fr;border-top:1px solid {$t['rule']}}
.apex-grid2>:first-child{border-right:1px solid {$t['rule']}}
.apex-panel-header{padding:9px 16px;border-bottom:1px solid {$t['rule']};display:flex;align-items:center;justify-content:space-between}
.apex-panel-title{font-family:'Syne',sans-serif;font-size:11px;font-weight:600;color:{$t['ink']};letter-spacing:-.1px}
.apex-panel-sub{font-family:'Fira Code',monospace;font-size:9px;color:{$t['ink3']};margin-left:8px}
.apex-panel-link{font-family:'Fira Code',monospace;font-size:9px;color:{$t['ink3']};border-bottom:1px solid {$t['rule2']};padding-bottom:1px;cursor:pointer}
.apex-sched-row{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid {$t['rule']};border-left:2px solid transparent;transition:background .1s}
.apex-sched-row.live{background:{$t['liveD']};border-left-color:{$t['live']}}
.apex-sched-time{font-family:'Fira Code',monospace;font-size:10px;width:36px;flex-shrink:0}
.apex-sched-prog{font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.apex-sched-pres{font-family:'Fira Code',monospace;font-size:10px;color:{$t['ink3']};margin-top:2px}
.apex-team-avatar{width:28px;height:28px;border-radius:50%;background:{$t['lift']};border:1px solid {$t['rule2']};display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-size:10px;font-weight:700;color:{$t['ink2']};flex-shrink:0}
.apex-team-name{font-size:12.5px;font-weight:500;color:{$t['ink']};white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.apex-team-dept{font-family:'Fira Code',monospace;font-size:10px;color:{$t['ink3']};margin-top:1px}
.apex-score-bar{width:44px;height:2px;background:{$t['ink4']};flex-shrink:0}
.apex-score-fill{height:100%}
.apex-score-val{font-family:'Fira Code',monospace;font-size:10px;color:{$t['ink3']};min-width:20px;text-align:right}
.apex-score-delta{font-family:'Fira Code',monospace;font-size:9px;min-width:18px}
.apex-pill{display:inline-flex;align-items:center;gap:4px;padding:2px 7px;font-family:'Fira Code',monospace;font-size:9px;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap;flex-shrink:0}
.apex-pill-dot{width:4px;height:4px;border-radius:50%;display:inline-block;animation:apex-onair 1.8s ease-in-out infinite}
.apex-pill-live{background:{$t['liveD']};color:{$t['live']}}
.apex-pill-active{background:{$t['sparkD']};color:{$t['spark']}}
.apex-pill-leave{background:{$t['amberD']};color:{$t['amber']}}
.apex-pill-done{color:{$t['ink4']}}
.apex-pill-next{color:{$t['ink3']}}
.apex-tel-listeners{padding:20px 18px 16px;border-bottom:1px solid {$t['rule']}}
.apex-tel-label{font-family:'Fira Code',monospace;font-size:9px;color:{$t['ink3']};text-transform:uppercase;letter-spacing:1px;margin-bottom:8px}
.apex-tel-big{font-family:'Fraunces',Georgia,serif;font-size:48px;font-weight:300;font-style:italic;color:{$t['spark']};line-height:1;letter-spacing:-2px;font-variant-numeric:oldstyle-nums;animation:apex-flipin .35s cubic-bezier(.22,.68,0,1.2) both}
.apex-tel-delta{font-family:'Fira Code',monospace;font-size:10px;color:{$t['spark']};margin-top:5px;opacity:.7}
.apex-tel-metric{padding:13px 18px;border-bottom:1px solid {$t['rule']};position:relative;transition:background .1s}
.apex-tel-metric-accent{position:absolute;top:0;left:0;bottom:0;width:2px;opacity:.65}
.apex-tel-metric-label{font-family:'Fira Code',monospace;font-size:9px;color:{$t['ink3']};text-transform:uppercase;letter-spacing:.8px;margin-bottom:5px}
.apex-tel-metric-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;color:{$t['ink']};line-height:1;letter-spacing:-.5px}
.apex-tel-metric-delta{font-family:'Fira Code',monospace;font-size:10px;margin-top:4px}
.apex-tel-fin-row{display:flex;justify-content:space-between;align-items:baseline;padding:9px 18px}
.apex-tel-fin-label{font-family:'Fira Code',monospace;font-size:9px;color:{$t['ink3']};text-transform:uppercase;letter-spacing:.5px}
.apex-tel-fin-val{font-family:'Fraunces',Georgia,serif;font-size:15px;font-style:italic}
CSS;

    // Nav HTML
    $navHtml='';
    foreach(APEX_NAV as $key=>$item){
        $active=$modulo===$key?' active':'';
        $url="/{$item['url_key']}/{$sid}";
        if($item['url_key']==='dashboard') $url="/public/dashboard/{$sid}";
        else $url="/public/{$item['url_key']}/{$sid}";
        $stroke=$modulo===$key?$t['spark']:$t['ink3'];
        $label=htmlspecialchars($item['label']);
        $icon=$item['icon'];
        $navHtml.=<<<HTML
<a href="{$url}" class="apex-ni{$active}" data-ni title="{$label}">
  <div class="apex-ni-accent"></div>
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{$stroke}" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="{$icon}"/></svg>
  <div class="apex-tip" data-tip>{$label}</div>
</a>
HTML;
    }

    // Ticker HTML
    $tickerItems=[
        ['● NO AR · A carregar...', true],
        ['SISTEMA  OPERACIONAL', false],
        ['RNB OS  APEX  v4.1', false],
        ['SLA  ONLINE', false],
        ['FONTE  MYRIAD PLAYOUT 6', false],
        ['● NO AR · A carregar...', true],
        ['SISTEMA  OPERACIONAL', false],
        ['RNB OS  APEX  v4.1', false],
        ['SLA  ONLINE', false],
        ['FONTE  MYRIAD PLAYOUT 6', false],
    ];
    $tickerHtml='';
    foreach($tickerItems as [$text,$isLive]){
        $cls=$isLive?' live':'';
        $tickerHtml.="<span class=\"apex-ticker-item{$cls}\">".htmlspecialchars($text)."</span>";
    }

    $telHtml=$opts['telemetria']??rnb_apex_default_telemetry($sid);

    return <<<HTML
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>RNB OS — {$titulo}</title>
<style>{$css}</style>
<style>:root{--void:{$t['void']};--depth:{$t['depth']};--base:{$t['base']};--lift:{$t['lift']};--spark:{$t['spark']};--live:{$t['live']};--amber:{$t['amber']};--violet:{$t['violet']};--ink:{$t['ink']};--ink2:{$t['ink2']};--ink3:{$t['ink3']};--ink4:{$t['ink4']};--rule:{$t['rule']};--rule2:{$t['rule2']}}</style>
</head>
<body>
<div class="apex-root apex-shell">
  <div class="apex-scanlines"></div>
  <div class="apex-ticker">
    <div class="apex-ticker-badge"><span>RNB</span></div>
    <div class="apex-ticker-fade-l"></div>
    <div class="apex-ticker-fade-r"></div>
    <div class="apex-ticker-scroll">{$tickerHtml}</div>
    <div class="apex-ticker-clock" id="apex-clock">--:--:--</div>
  </div>
  <div class="apex-body">
    <aside class="apex-sidebar">
      <div class="apex-logo" title="RNB OS">
        <div class="apex-logo-bar" style="height:7px"></div>
        <div class="apex-logo-bar" style="height:13px"></div>
        <div class="apex-logo-bar" style="height:5px"></div>
        <div class="apex-logo-bar" style="height:11px"></div>
        <div class="apex-logo-bar" style="height:9px"></div>
      </div>
      <div class="apex-sep"></div>
      <nav class="apex-nav">{$navHtml}</nav>
      <div class="apex-user" title="{$nome} · {$role}">{$ini}</div>
    </aside>
    <div class="apex-content">
      <div class="apex-editorial">
        <div class="apex-topbar">
          <div style="display:flex;align-items:baseline">
            <span class="apex-topbar-title">{$titulo}</span>
            <span class="apex-topbar-date">{$date}</span>
          </div>
          <div class="apex-topbar-actions">
            <button class="apex-btn apex-btn-ghost" onclick="window.print()">Exportar</button>
          </div>
        </div>
        <div style="flex:1;overflow:auto">{$corpo}</div>
      </div>
      <div class="apex-telemetry">{$telHtml}</div>
    </div>
  </div>
</div>
<script>
(function(){
  const el=document.getElementById('apex-clock');
  if(!el)return;
  function tick(){const n=new Date(),p=n=>String(n).padStart(2,'0');el.textContent=p(n.getHours())+':'+p(n.getMinutes())+':'+p(n.getSeconds());}
  tick();setInterval(tick,1000);
})();
</script>
</body>
</html>
HTML;
}
