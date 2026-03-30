<?php
/**
 * PULSO - Painel do Locutor
 * Teleprompter-style para uso em estudio de radio
 */
$stationId = 1;
?>
<!doctype html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>PULSO Locutor</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
--bg0:#050508;--bg1:#0d0d12;--bg2:#14141c;--bg3:#1a1a24;
--t1:#f0f0f5;--t2:#8888a0;--t3:#555566;
--blue:#00b4ff;--gold:#ffc247;--green:#00e676;--red:#ff3d5a;--purple:#b388ff;
--bdr:#222233;
--ff:'Plus Jakarta Sans',sans-serif;--fm:'JetBrains Mono',monospace;
--tr:0.25s cubic-bezier(0.4,0,0.2,1);
}
body{font-family:var(--ff);background:var(--bg0);color:var(--t1);overflow:hidden;height:100vh;user-select:none}
.hdr{display:flex;align-items:center;justify-content:space-between;padding:12px 24px;background:var(--bg1);border-bottom:1px solid var(--bdr);height:56px}
.hdr-l{display:flex;align-items:center;gap:16px}
.hdr-logo{font:800 15px var(--fm);letter-spacing:2px;color:var(--blue)}
.hdr-div{width:1px;height:24px;background:var(--bdr)}
.hdr-info{font-size:13px;color:var(--t2)}
.hdr-r{display:flex;align-items:center;gap:12px}
.hdr-listen{font:13px var(--fm);color:var(--t2)}
.hdr-clock{font:700 18px var(--fm);color:var(--t1);letter-spacing:1px}
.btn-mode{display:flex;align-items:center;gap:8px;padding:8px 18px;border:none;border-radius:8px;font:700 13px var(--ff);cursor:pointer;transition:var(--tr);letter-spacing:.5px}
.btn-mode.off{background:var(--red);color:#fff}
.btn-mode.on{background:var(--bg3);color:var(--t2);border:1px solid var(--bdr)}
.btn-mode:hover{opacity:.9;transform:scale(1.02)}
.ldot{width:8px;height:8px;background:#fff;border-radius:50%;animation:pdot 1.5s infinite}
@keyframes pdot{0%,100%{opacity:1}50%{opacity:.3}}
.npbar{display:flex;align-items:center;gap:16px;padding:10px 24px;background:var(--bg1);border-bottom:1px solid var(--bdr);height:48px}
.np-lb{font:700 10px var(--fm);letter-spacing:2px;color:var(--green);text-transform:uppercase;white-space:nowrap}
.np-song{font-size:14px;font-weight:600;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1}
.np-art{color:var(--t2);font-weight:400}
.np-tw{display:flex;align-items:center;gap:10px;flex-shrink:0}
.np-tm{font:700 16px var(--fm);min-width:50px;text-align:right}
.np-tm.u{color:var(--red)}.np-tm.w{color:var(--gold)}.np-tm.k{color:var(--green)}
.np-pb{width:120px;height:4px;background:var(--bg3);border-radius:2px;overflow:hidden}
.np-pf{height:100%;border-radius:2px;transition:width 1s linear,background .5s}
.np-nx{font-size:12px;color:var(--t3);white-space:nowrap}
.main{height:calc(100vh - 56px - 48px);overflow:hidden}
.m-noar{display:flex;flex-direction:column;height:100%;padding:24px 48px}
.noar-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.noar-tl{display:flex;align-items:center;gap:12px}
.noar-badge{font:700 12px var(--fm);letter-spacing:1px;color:var(--t2)}
.noar-new{font-size:12px;font-weight:600;color:var(--gold);background:rgba(255,194,71,.1);padding:3px 10px;border-radius:20px;display:none}
.noar-card{flex:1;display:flex;flex-direction:column;justify-content:center;background:var(--bg1);border:1px solid var(--bdr);border-radius:16px;padding:48px 56px;position:relative;overflow:hidden;transition:var(--tr);min-height:0}
.noar-card::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;border-radius:16px 0 0 16px}
.noar-card.ph::before{background:var(--red)}.noar-card.pm::before{background:var(--gold)}.noar-card.pl::before{background:var(--t3)}.noar-card.pn::before{background:var(--purple)}
.noar-card.fg{animation:fg .4s}.noar-card.fy{animation:fy .4s}
@keyframes fg{0%{border-color:var(--green);box-shadow:0 0 40px rgba(0,230,118,.15)}100%{border-color:var(--bdr);box-shadow:none}}
@keyframes fy{0%{border-color:var(--t3);box-shadow:0 0 40px rgba(85,85,102,.15)}100%{border-color:var(--bdr);box-shadow:none}}
.noar-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px}
.noar-nm{font-size:36px;font-weight:800;color:var(--blue);line-height:1.1}
.noar-meta{display:flex;align-items:center;gap:12px;font-size:14px;color:var(--t2)}
.noar-seg{padding:4px 12px;border-radius:20px;font:700 11px var(--ff);letter-spacing:1px;text-transform:uppercase}
.sn{background:rgba(179,136,255,.15);color:var(--purple)}.sr{background:rgba(0,180,255,.15);color:var(--blue)}.sv{background:rgba(255,194,71,.15);color:var(--gold)}.se{background:rgba(255,61,90,.15);color:var(--red)}
.noar-mus{font-size:28px;font-weight:600;color:var(--gold);margin-bottom:20px;line-height:1.3}
.noar-msg{font-size:22px;color:var(--t2);font-style:italic;line-height:1.5;margin-bottom:24px;padding-left:20px;border-left:3px solid var(--bdr)}
.noar-tip{font-size:16px;color:var(--green);padding:12px 18px;background:rgba(0,230,118,.06);border-radius:10px;border-left:3px solid var(--green)}
.noar-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--t3)}
.noar-empty b{font-size:22px;display:block;margin-bottom:8px}
.noar-btm{display:flex;align-items:center;justify-content:space-between;margin-top:20px;gap:16px}
.noar-acts{display:flex;gap:12px}
.btn-a{display:flex;align-items:center;gap:10px;padding:16px 32px;border:none;border-radius:12px;font:700 16px var(--ff);cursor:pointer;transition:var(--tr)}
.btn-a:hover{transform:translateY(-2px)}.btn-a:active{transform:scale(.97)}
.btn-a .k{font:11px var(--fm);background:rgba(255,255,255,.15);padding:2px 8px;border-radius:4px;opacity:.7}
.btn-l{background:var(--green);color:#050508}
.btn-s{background:var(--bg3);color:var(--t2);border:1px solid var(--bdr)}
.btn-p{background:var(--bg3);color:var(--gold);border:1px solid rgba(255,194,71,.3)}.btn-p.act{background:rgba(255,194,71,.15)}
.noar-prx{display:flex;flex-direction:column;gap:4px;text-align:right}
.noar-prx-t{font:700 10px var(--fm);letter-spacing:2px;color:var(--t3);text-transform:uppercase}
.noar-prx-i{font-size:13px;color:var(--t2)}
.noar-prx-i em{color:var(--t3);font:11px var(--fm);margin-right:6px;font-style:normal}
.noar-prog{display:flex;align-items:center;gap:12px;margin-top:16px}
.noar-pgb{flex:1;height:6px;background:var(--bg3);border-radius:3px;overflow:hidden}
.noar-pgf{height:100%;background:linear-gradient(90deg,var(--blue),var(--green));border-radius:3px;transition:width .3s}
.noar-pgt{font:13px var(--fm);color:var(--t2);white-space:nowrap}
.m-panel{display:grid;grid-template-columns:380px 1fr;height:100%;overflow:hidden}
.p-fila{border-right:1px solid var(--bdr);display:flex;flex-direction:column;overflow:hidden}
.fl-hdr{padding:16px 20px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;justify-content:space-between}
.fl-ttl{font-size:14px;font-weight:700;letter-spacing:1px}
.fl-bgs{display:flex;gap:8px}
.fl-bg{font:11px var(--fm);padding:2px 8px;border-radius:10px}
.fl-bg.p{background:rgba(0,180,255,.12);color:var(--blue)}.fl-bg.d{background:rgba(0,230,118,.12);color:var(--green)}
.fl-list{flex:1;overflow-y:auto;padding:8px}
.fl-list::-webkit-scrollbar{width:4px}.fl-list::-webkit-scrollbar-track{background:0 0}.fl-list::-webkit-scrollbar-thumb{background:var(--bdr);border-radius:2px}
.fl-it{padding:14px 16px;border-radius:10px;margin-bottom:4px;cursor:pointer;transition:var(--tr);border-left:3px solid transparent}
.fl-it:hover{background:var(--bg2)}.fl-it.act{background:var(--bg3);border-left-color:var(--blue)}
.fl-it.done{opacity:.4}.fl-it.skp{opacity:.25;text-decoration:line-through}
.fl-it.ph{border-left-color:var(--red)}.fl-it.pm{border-left-color:var(--gold)}.fl-it.pn{border-left-color:var(--purple)}
.fl-it-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px}
.fl-it-nm{font-size:14px;font-weight:700;color:var(--blue)}
.fl-it-tm{font:11px var(--fm);color:var(--t3)}
.fl-it-sg{font-size:13px;color:var(--gold);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fl-it-ms{font-size:12px;color:var(--t3);font-style:italic;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fl-it-lb{display:inline-block;font:700 9px var(--ff);letter-spacing:1px;text-transform:uppercase;padding:1px 6px;border-radius:8px;margin-left:8px}
.p-ficha{display:flex;flex-direction:column;padding:24px 32px;overflow-y:auto}
.fc-empty{flex:1;display:flex;align-items:center;justify-content:center;color:var(--t3);font-size:16px}
.fc-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px}
.fc-nm{font-size:28px;font-weight:800;color:var(--blue)}
.fc-acts{display:flex;gap:8px}
.fc-btn{padding:10px 20px;border:none;border-radius:8px;font:700 13px var(--ff);cursor:pointer;transition:var(--tr)}
.fc-btn:hover{transform:scale(1.03)}
.fc-btn.g{background:var(--green);color:#050508}.fc-btn.y{background:var(--bg3);color:var(--t2);border:1px solid var(--bdr)}
.fc-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}
.fc-st{background:var(--bg1);border:1px solid var(--bdr);border-radius:10px;padding:14px;text-align:center}
.fc-sv{font:800 24px var(--fm);color:var(--t1)}
.fc-sl{font-size:11px;color:var(--t3);margin-top:4px;text-transform:uppercase;letter-spacing:1px}
.fc-sec{margin-bottom:20px}
.fc-sec-t{font:700 11px var(--fm);letter-spacing:2px;text-transform:uppercase;color:var(--t3);margin-bottom:10px}
.fc-hi{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(34,34,51,.5);font-size:13px}
.fc-hs{color:var(--t1)}.fc-hd{color:var(--t3);font:12px var(--fm)}
.fc-sug{background:rgba(0,230,118,.06);border-left:3px solid var(--green);padding:10px 14px;border-radius:0 8px 8px 0;margin-bottom:6px;font-size:14px;color:var(--green)}
.hid{display:none!important}
@media(max-width:900px){.m-panel{grid-template-columns:1fr}.p-fila{max-height:50vh}.m-noar{padding:16px 20px}.noar-nm{font-size:28px}.noar-mus{font-size:22px}.noar-msg{font-size:18px}.btn-a{padding:14px 24px;font-size:14px}}

.fc-notas{margin-top:12px}
.fc-nota-form{display:flex;gap:8px;margin-bottom:12px}
.fc-nota-input{flex:1;padding:10px 14px;background:var(--bg3);border:1px solid var(--bdr);border-radius:8px;color:var(--t1);font:14px var(--ff);outline:none}
.fc-nota-input:focus{border-color:var(--blue)}
.fc-nota-input::placeholder{color:var(--t3)}
.fc-nota-send{padding:10px 18px;background:var(--blue);color:#050508;border:none;border-radius:8px;font:700 13px var(--ff);cursor:pointer;transition:var(--tr)}
.fc-nota-send:hover{opacity:.9}
.fc-nota-item{padding:10px 14px;background:var(--bg1);border-radius:8px;margin-bottom:6px;position:relative}
.fc-nota-text{font-size:14px;color:var(--t1)}
.fc-nota-meta{font:11px var(--fm);color:var(--t3);margin-top:4px}
.fc-nota-del{position:absolute;top:8px;right:10px;background:none;border:none;color:var(--t3);cursor:pointer;font-size:14px}
.fc-nota-del:hover{color:var(--red)}
.tocar-wrap{margin-top:12px;padding:12px;background:var(--bg1);border:1px solid var(--bdr);border-radius:10px}
.tocar-hdr{display:flex;align-items:center;gap:8px;margin-bottom:8px}
.tocar-btn{padding:6px 14px;background:var(--green);color:#050508;border:none;border-radius:6px;font:700 12px var(--ff);cursor:pointer;transition:var(--tr)}
.tocar-btn:hover{opacity:.9}
.tocar-btn.req{background:var(--gold)}
.tocar-res{font-size:13px;color:var(--t2)}
.tocar-item{display:flex;justify-content:space-between;align-items:center;padding:6px 0;border-bottom:1px solid rgba(34,34,51,.3)}
.tocar-item:last-child{border:none}
.tocar-info{font-size:13px;color:var(--t1)}
.tocar-art{color:var(--t2)}
.toast{position:fixed;top:70px;right:24px;background:var(--green);color:#050508;padding:12px 24px;border-radius:10px;font:700 14px var(--ff);z-index:999;opacity:0;transform:translateY(-10px);transition:all .3s;pointer-events:none}
.toast.show{opacity:1;transform:translateY(0)}
.toast.err{background:var(--red);color:#fff}

/* Banner de Notificação */
.notif-banner{position:fixed;top:80px;right:20px;max-width:420px;background:linear-gradient(135deg,rgba(255,215,0,0.95),rgba(255,140,0,0.95));padding:20px 24px;border-radius:16px;box-shadow:0 12px 40px rgba(255,140,0,0.4);z-index:9999;animation:slideIn 0.4s cubic-bezier(0.68,-0.55,0.265,1.55);backdrop-filter:blur(10px);border:2px solid rgba(255,255,255,0.3)}
@keyframes slideIn{from{transform:translateX(500px);opacity:0}to{transform:translateX(0);opacity:1}}
.notif-header{display:flex;align-items:center;gap:12px;margin-bottom:14px}
.notif-icon{font-size:32px;animation:bounce 1s infinite}
@keyframes bounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.notif-title{font:700 18px var(--ff);color:#1a1a1a;letter-spacing:0.5px}
.notif-msg{font:600 15px var(--ff);color:#2a2a2a;margin-bottom:16px;line-height:1.5}
.notif-premio{background:rgba(255,255,255,0.5);padding:8px 12px;border-radius:8px;font:700 14px var(--fm);color:#1a1a1a;margin-bottom:16px;text-align:center}
.notif-btns{display:flex;gap:10px}
.notif-btn{flex:1;padding:10px;border:none;border-radius:8px;font:700 13px var(--ff);cursor:pointer;transition:var(--tr);letter-spacing:0.5px}
.notif-btn.primary{background:#1a1a1a;color:#ffd700}
.notif-btn.secondary{background:rgba(255,255,255,0.5);color:#1a1a1a}
.notif-btn:hover{transform:scale(1.05);box-shadow:0 4px 12px rgba(0,0,0,0.2)}
.notif-close{position:absolute;top:8px;right:8px;width:24px;height:24px;border:none;background:rgba(0,0,0,0.2);color:#fff;border-radius:50%;cursor:pointer;font-size:16px;line-height:1}
.notif-close:hover{background:rgba(0,0,0,0.4)}
</style>
</head>
<body>
<header class="hdr">
<div class="hdr-l">
<span class="hdr-logo">PULSO</span><div class="hdr-div"></div>
<span class="hdr-info">Radio New Band</span>
</div>
<div class="hdr-r">
<span class="hdr-listen" id="xlist">0 ouvintes</span><div class="hdr-div"></div>
<span class="hdr-clock" id="xclock">--:--</span><div class="hdr-div"></div>
<button class="btn-mode off" id="xmode" onclick="toggleMode()"><span class="ldot"></span> NO AR</button>
</div>
</header>
<div class="npbar">
<span class="np-lb">NO AR</span>
<span class="np-song" id="xnps">A carregar...</span>
<div class="np-tw"><div class="np-pb"><div class="np-pf" id="xnppf" style="width:0%"></div></div><span class="np-tm k" id="xnpt">--:--</span></div>
<span class="np-nx" id="xnpn">Prox: --</span>
</div>
<div class="main">
<div class="m-noar hid" id="vNoar">
<div class="noar-top"><div class="noar-tl"><span class="noar-badge" id="xbadge">DEDICATORIA 0 de 0</span><span class="noar-new" id="xnew">+0 novas</span></div></div>
<div id="xcard"><div class="noar-empty"><b>Sem dedicatorias pendentes</b><span>Quando os ouvintes enviarem, aparece aqui</span></div></div>
<div class="noar-btm" id="xbtm" style="display:none">
<div class="noar-acts">
<button class="btn-a btn-l" onclick="mLida()">LIDA <span class="k">ESPACO</span></button>
<button class="btn-a btn-s" onclick="mSkip()">SKIP <span class="k">&rarr;</span></button>
<button class="btn-a btn-p" id="xpause" onclick="tPausa()">PAUSA <span class="k">P</span></button>
</div>
<div class="noar-prx" id="xprx"></div>
</div>
<div class="noar-prog" id="xprog" style="display:none"><div class="noar-pgb"><div class="noar-pgf" id="xpgf" style="width:0%"></div></div><span class="noar-pgt" id="xpgt">0/0</span></div>
</div>
<div class="m-panel" id="vPanel">
<div class="p-fila">
<div class="fl-hdr"><span class="fl-ttl">FILA</span><div class="fl-bgs"><span class="fl-bg p" id="xfp">0 pendentes</span><span class="fl-bg d" id="xfd">0 lidas</span></div></div>
<div class="fl-list" id="xfl"><div style="padding:40px;text-align:center;color:var(--t3)">A carregar...</div></div>
</div>
<div class="p-ficha" id="xfc"><div class="fc-empty">Clica numa dedicatoria para ver a ficha</div></div>
</div>
</div>
<script>
var S={mode:'panel',fila:[],pend:[],ci:0,paused:false,selOuv:null,selPart:null,initC:0,newC:0,npr:0};
var API='/pulso/api/locutor';
function uClock(){var n=new Date();var h=String(n.getHours()).padStart(2,'0');var m=String(n.getMinutes()).padStart(2,'0');document.getElementById('xclock').textContent=h+':'+m;}
setInterval(uClock,1000);uClock();
function toggleMode(){
if(S.mode==='panel'){
S.mode='noar';
document.getElementById('vPanel').classList.add('hid');
document.getElementById('vNoar').classList.remove('hid');
document.getElementById('xmode').className='btn-mode on';
document.getElementById('xmode').innerHTML='PAINEL';
S.ci=0;S.initC=S.fila.length;S.newC=0;
rNoar();
}else{
S.mode='panel';
document.getElementById('vNoar').classList.add('hid');
document.getElementById('vPanel').classList.remove('hid');
document.getElementById('xmode').className='btn-mode off';
document.getElementById('xmode').innerHTML='<span class="ldot"></span> NO AR';
rPanel();
}
}
function fFila(){
fetch(API+'?action=fila&station_id=1').then(function(r){return r.json();}).then(function(d){
if(d.status==='ok'){
var oc=S.fila.length;
S.fila=d.fila;
S.pend=S.fila.filter(function(m){return !m.lido_no_ar&&!m.skip;});
if(oc>0&&S.fila.length>oc){
S.newC+=(S.fila.length-oc);
showNotif('Nova dedicatoria! +'+(S.fila.length-oc));
}
if(S.mode==='noar')rNoar();else rPanel();
}
}).catch(function(e){console.error('Erro fila:',e);});
}
function fNP(){
fetch(API+'?action=nowplaying&station_id=1').then(function(r){return r.json();}).then(function(d){
if(d.status==='ok'&&d.song){
var s=d.song;
document.getElementById('xnps').innerHTML='<strong>'+esc(s.title)+'</strong> <span class="np-art">- '+esc(s.artist)+'</span>';
var rem=Math.max(0,s.remaining);S.npr=rem;
var mm=Math.floor(rem/60),ss=rem%60;
var te=document.getElementById('xnpt');
te.textContent=mm+':'+String(ss).padStart(2,'0');
te.className='np-tm '+(rem<15?'u':rem<45?'w':'k');
var pct=s.duration>0?((s.elapsed/s.duration)*100):0;
var pe=document.getElementById('xnppf');
pe.style.width=pct+'%';
pe.style.background=rem<15?'var(--red)':rem<45?'var(--gold)':'var(--green)';
if(d.next&&d.next.title)document.getElementById('xnpn').textContent='Prox: '+d.next.artist+' - '+d.next.title;
document.getElementById('xlist').textContent=(d.listeners||0)+' ouvintes';
}
}).catch(function(e){});
}
function rNoar(){
var p=S.pend,t=S.fila.length,l=S.fila.filter(function(m){return m.lido_no_ar;}).length;
document.getElementById('xbadge').textContent=p.length>0?'DEDICATORIA '+(S.ci+1)+' de '+p.length:'SEM DEDICATORIAS';
var ne=document.getElementById('xnew');
if(S.newC>0){ne.textContent='+'+S.newC+' novas';ne.style.display='inline';}else{ne.style.display='none';}
var btm=document.getElementById('xbtm'),prg=document.getElementById('xprog');
if(p.length===0){
document.getElementById('xcard').innerHTML='<div class="noar-empty"><b>Sem dedicatorias pendentes</b><span>Programa: '+l+' lidas de '+t+' total</span></div>';
btm.style.display='none';prg.style.display='none';return;
}
btm.style.display='flex';prg.style.display='flex';
if(S.ci>=p.length)S.ci=p.length-1;
var m=p[S.ci];
var pc='pl';
if(m.is_novo)pc='pn';else if(m.prioridade_calc>=50)pc='ph';else if(m.prioridade_calc>=25)pc='pm';
var sm={novo:['NOVO','sn'],regular:['REGULAR','sr'],veterano:['VETERANO','sv'],embaixador:['EMBAIXADOR','se']};
var sg=sm[m.segmento]||['-','sn'];
var h='<div class="noar-card '+pc+'" id="xc">';
h+='<div class="noar-hdr"><span class="noar-nm">'+esc(m.nome)+'</span>';
h+='<div class="noar-meta"><span class="noar-seg '+sg[1]+'">'+sg[0]+'</span>';
h+='<span>'+m.ouvinte_pontos+' pts</span><span>'+m.total_participacoes+'x</span></div></div>';
if(m.musica)h+='<div class="noar-mus">'+esc(m.musica)+'</div>';
if(m.mensagem)h+='<div class="noar-msg">"'+esc(m.mensagem)+'"</div>';
h+='<div class="noar-tip">'+esc(m.dica)+'</div></div>';
document.getElementById('xcard').innerHTML=h;
var px=p.slice(S.ci+1,S.ci+4),pe=document.getElementById('xprx');
if(px.length>0){
var ph='<span class="noar-prx-t">PROXIMAS</span>';
for(var i=0;i<px.length;i++){ph+='<span class="noar-prx-i"><em>'+(S.ci+2+i)+'.</em>'+esc(px[i].nome)+' - '+esc(px[i].musica||'sem musica')+'</span>';}
pe.innerHTML=ph;
}else{pe.innerHTML='<span class="noar-prx-t">ULTIMA!</span>';}
document.getElementById('xpgf').style.width=t>0?((l/t)*100)+'%':'0%';
document.getElementById('xpgt').textContent=l+'/'+t+' lidas | '+p.length+' pendentes';
}
function rPanel(){
var f=S.fila,p=f.filter(function(m){return !m.lido_no_ar&&!m.skip;}),l=f.filter(function(m){return m.lido_no_ar;});
document.getElementById('xfp').textContent=p.length+' pendentes';
document.getElementById('xfd').textContent=l.length+' lidas';
var el=document.getElementById('xfl');
if(f.length===0){el.innerHTML='<div style="padding:40px;text-align:center;color:var(--t3)">Sem dedicatorias</div>';return;}
var h='';
for(var i=0;i<f.length;i++){
var m=f[i],c='fl-it';
if(m.lido_no_ar)c+=' done';
if(m.skip)c+=' skp';
if(m.ouvinte_id==S.selOuv)c+=' act';
if(m.is_novo)c+=' pn';else if(m.prioridade_calc>=50)c+=' ph';else if(m.prioridade_calc>=25)c+=' pm';
var sm={novo:'sn',regular:'sr',veterano:'sv',embaixador:'se'};
var sc=sm[m.segmento]||'sn';
h+='<div class="'+c+'" onclick="selOuv('+m.ouvinte_id+','+m.id+')">';
h+='<div class="fl-it-top"><span class="fl-it-nm">'+esc(m.nome)+'<span class="fl-it-lb '+sc+'">'+(m.segmento||'').toUpperCase()+'</span></span>';
h+='<span class="fl-it-tm">'+m.tempo_relativo+'</span></div>';
h+=m.musica?'<div class="fl-it-sg">'+esc(m.musica)+'</div>':'<div class="fl-it-sg" style="color:var(--t3)">sem musica</div>';
if(m.mensagem)h+='<div class="fl-it-ms">"'+esc(m.mensagem)+'"</div>';
h+='</div>';
}
el.innerHTML=h;
}
function selOuv(oid,pid){
S.selOuv=oid;S.selPart=pid;rPanel();
var fc=document.getElementById('xfc');
fc.innerHTML='<div class="fc-empty">A carregar...</div>';
fetch(API+'?action=ouvinte&id='+oid).then(function(r){return r.json();}).then(function(d){
if(d.status!=='ok')return;
var o=d.ouvinte,hi=d.historico;
var h='<div class="fc-hdr"><span class="fc-nm">'+esc(o.nome)+'</span>';
h+='<div class="fc-acts"><button class="fc-btn g" onclick="mLidaP('+pid+')">LIDA NO AR</button>';
h+='<button class="fc-btn y" onclick="mSkipP('+pid+')">SKIP</button></div></div>';
h+='<div class="fc-stats">';
h+='<div class="fc-st"><div class="fc-sv">'+o.pontos+'</div><div class="fc-sl">Pontos</div></div>';
h+='<div class="fc-st"><div class="fc-sv">'+o.total_participacoes+'</div><div class="fc-sl">Dedicatorias</div></div>';
h+='<div class="fc-st"><div class="fc-sv">'+d.total_premios+'</div><div class="fc-sl">Premios</div></div>';
h+='<div class="fc-st"><div class="fc-sv">'+d.dias_ouvinte+'d</div><div class="fc-sl">Desde registo</div></div></div>';
if(d.sugestoes.length>0){
h+='<div class="fc-sec"><div class="fc-sec-t">Sugestoes</div>';
for(var i=0;i<d.sugestoes.length;i++)h+='<div class="fc-sug">'+esc(d.sugestoes[i])+'</div>';
h+='</div>';
}
var lastMusic=null;
for(var i=0;i<hi.length;i++){if(hi[i].musica){lastMusic=hi[i].musica;break;}}
if(lastMusic){
h+='<div class="fc-sec"><div class="fc-sec-t">Tocar Musica Pedida</div>';
h+='<div class="tocar-wrap"><div class="tocar-hdr"><span style="font-size:13px;color:var(--t2)">'+esc(lastMusic)+'</span> ';
h+='<button class="tocar-btn" onclick="searchTocar(this);" data-q="'+lastMusic.replace(/"/g,'&quot;')+'">Pesquisar</button></div>';
h+='<div class="tocar-res-box"></div></div></div>';
}
h+='<div class="fc-sec"><div class="fc-sec-t">Historico</div>';
for(var i=0;i<hi.length;i++){
h+='<div class="fc-hi"><span class="fc-hs">'+(hi[i].lido_no_ar?'[v] ':'')+esc(hi[i].musica||'Dedicatoria')+'</span>';
h+='<span class="fc-hd">'+hi[i].tempo_relativo+'</span></div>';
}
h+='</div>';
h+='<div class="fc-sec fc-notas"><div class="fc-sec-t">Notas do Locutor</div>';
h+='<div class="fc-nota-form"><input class="fc-nota-input" id="xnotainp" placeholder="Nota sobre este ouvinte..." onkeydown="if(event.keyCode==13)saveNota('+oid+')"><button class="fc-nota-send" onclick="saveNota('+oid+')">Guardar</button></div>';
h+='<div id="xnotalist">A carregar notas...</div></div>';
fc.innerHTML=h;
loadNotas(oid);
}).catch(function(e){fc.innerHTML='<div class="fc-empty">Erro ao carregar</div>';});
}
function mLida(){
if(S.pend.length===0||S.paused)return;var m=S.pend[S.ci];if(!m)return;
var c=document.getElementById('xc');if(c){c.classList.add('fg');setTimeout(function(){c.classList.remove('fg');},400);}
pAct('marcar_lida',m.id).then(fFila);
}
function mSkip(){
if(S.pend.length===0||S.paused)return;var m=S.pend[S.ci];if(!m)return;
var c=document.getElementById('xc');if(c){c.classList.add('fy');setTimeout(function(){c.classList.remove('fy');},400);}
pAct('marcar_skip',m.id).then(fFila);
}
function mLidaP(id){pAct('marcar_lida',id).then(fFila);}
function mSkipP(id){pAct('marcar_skip',id).then(fFila);}
function pAct(a,id){
return fetch(API+'?action='+a+'&station_id=1',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id})}).catch(function(e){console.error(e);});
}
function tPausa(){
S.paused=!S.paused;var b=document.getElementById('xpause');
b.classList.toggle('act',S.paused);
b.innerHTML=S.paused?'RETOMAR <span class="k">P</span>':'PAUSA <span class="k">P</span>';
}
document.addEventListener('keydown',function(e){
if(e.target.tagName==='INPUT'||e.target.tagName==='TEXTAREA')return;
if(e.code==='Space'){e.preventDefault();if(S.mode==='noar')mLida();}
if(e.code==='ArrowRight'){e.preventDefault();if(S.mode==='noar')mSkip();}
if(e.code==='ArrowLeft'){e.preventDefault();if(S.mode==='noar'&&S.ci>0){S.ci--;rNoar();}}
if(e.code==='KeyP'){e.preventDefault();tPausa();}
if(e.code==='KeyN'){e.preventDefault();toggleMode();}
if(e.code==='KeyF'){e.preventDefault();if(document.fullscreenElement)document.exitFullscreen();else document.documentElement.requestFullscreen();}
if(e.code==='Escape'&&S.mode==='noar')toggleMode();
});
function esc(s){if(!s)return '';var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function showNotif(msg){
var t=document.getElementById('xtoast');if(!t)return;
t.textContent=msg;t.className='toast show';
setTimeout(function(){t.className='toast';},4000);
var hdr=document.querySelector('.hdr');if(hdr){hdr.style.boxShadow='0 0 20px rgba(0,230,118,0.4)';setTimeout(function(){hdr.style.boxShadow='none';},2000);}
}
function showToast(msg,isErr){
var t=document.getElementById('xtoast');if(!t)return;
t.textContent=msg;t.className=isErr?'toast err show':'toast show';
setTimeout(function(){t.className='toast';},3000);
}
function loadNotas(ouvId){
fetch(API+'?action=notas&id='+ouvId).then(function(r){return r.json();}).then(function(d){
var el=document.getElementById('xnotalist');if(!el)return;
if(d.status!=='ok'||d.notas.length===0){el.innerHTML='<div style="font-size:13px;color:var(--t3);padding:4px">Sem notas</div>';return;}
var h='';
for(var i=0;i<d.notas.length;i++){
var n=d.notas[i];
h+='<div class="fc-nota-item"><div class="fc-nota-text">'+esc(n.nota)+'</div>';
h+='<div class="fc-nota-meta">'+n.data_criacao+'</div>';
h+='<button class="fc-nota-del" onclick="delNota('+n.id+','+ouvId+')" title="Apagar">x</button></div>';
}
el.innerHTML=h;
});
}
function saveNota(ouvId){
var inp=document.getElementById('xnotainp');if(!inp)return;
var nota=inp.value.trim();if(!nota)return;
fetch(API+'?action=guardar_nota&station_id=1',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ouvinte_id:ouvId,nota:nota})}).then(function(r){return r.json();}).then(function(d){
if(d.status==='ok'){showToast('Nota guardada!');loadNotas(ouvId);}
inp.value='';
}).catch(function(){showToast('Erro',true);});
}
function delNota(id,ouvId){
fetch(API+'?action=apagar_nota&station_id=1',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id})}).then(function(r){return r.json();}).then(function(d){
if(d.status==='ok'){showToast('Nota apagada');loadNotas(ouvId);}
});
}
function searchTocar(btn){
var q=btn.getAttribute('data-q');
var box=btn.parentElement.parentElement.querySelector('.tocar-res-box');
if(!box)return;
box.innerHTML='<div class="tocar-res">A pesquisar...</div>';
fetch(API+'?action=tocar&station_id=1',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({termo:q})}).then(function(r){return r.json();}).then(function(d){
if(d.status==='ok'&&d.resultados&&d.resultados.length>0){
var h='';
for(var i=0;i<d.resultados.length;i++){
var r=d.resultados[i];
h+='<div class="tocar-item"><span class="tocar-info">'+esc(r.title)+' <span class="tocar-art">- '+esc(r.artist)+'</span></span>';
h+='<button class="tocar-btn req" data-mid="'+r.id+'" onclick="reqTocar(this,this.dataset.mid)">Tocar</button></div>';
}
box.innerHTML=h;
}else{box.innerHTML='<div class="tocar-res">Nao encontrada na biblioteca</div>';}
}).catch(function(){box.innerHTML='<div class="tocar-res">Erro</div>';});
}
function reqTocar(btn,mid){
fetch(API+'?action=request&station_id=1',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({media_id:mid})}).then(function(r){return r.json();}).then(function(){
btn.textContent='Enviado!';btn.disabled=true;showToast('Musica na fila!');
}).catch(function(){showToast('Erro',true);});
}
fFila();fNP();setInterval(fFila,10000);setInterval(fNP,5000);
</script>
<div class="toast" id="xtoast"></div>

<div id="notifBanner" style="display:none"></div>
<audio id="notifSound" src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBi6L0fPTgjMGHm7A7+OZUQ8PVKXi8Lplhh6L0fPTgjMGHm7A7+OZURAOVKXh8L1lIA4" preload="auto"></audio>
<script>
let lastNotifCheck = 0;
let activeNotif = null;

// Verificar notificações a cada 5 segundos
setInterval(checkNotifications, 5000);
checkNotifications(); // Verificar imediatamente

function checkNotifications() {
    fetch('/pulso/api/locutor?action=notificacoes&station_id=1')
        .then(r => r.json())
        .then(data => {
            if (data.notificacoes && data.notificacoes.length > 0) {
                const notif = data.notificacoes[0];
                if (notif.id !== lastNotifCheck) {
                    showNotification(notif);
                    lastNotifCheck = notif.id;
                    // Tocar som
                    document.getElementById('notifSound').play().catch(e => console.log('Som bloqueado'));
                }
            }
        })
        .catch(e => console.error('Erro ao buscar notificações:', e));
}

function showNotification(notif) {
    const banner = document.getElementById('notifBanner');
    const dados = JSON.parse(notif.dados || '{}');
    const vencedor = dados.vencedores ? dados.vencedores[0].nome : 'N/A';
    const premio = dados.premio || 'N/A';
    
    banner.innerHTML = `
        <div class="notif-banner">
            <button class="notif-close" onclick="closeNotification(${notif.id})">×</button>
            <div class="notif-header">
                <div class="notif-icon">🏆</div>
                <div class="notif-title">${notif.titulo}</div>
            </div>
            <div class="notif-msg">${vencedor} é o vencedor!</div>
            <div class="notif-premio">Prêmio: ${premio}</div>
            <div class="notif-btns">
                <button class="notif-btn primary" onclick="announceWinner('${vencedor}', '${premio}', ${notif.id})">
                    📢 ANUNCIAR
                </button>
                <button class="notif-btn secondary" onclick="closeNotification(${notif.id})">
                    Dispensar
                </button>
            </div>
        </div>
    `;
    banner.style.display = 'block';
    activeNotif = notif.id;
}

function announceWinner(vencedor, premio, notifId) {
    alert(`🎉 ANUNCIANDO AO VIVO:\n\nVencedor: ${vencedor}\nPrêmio: ${premio}\n\n(Aqui você pode adicionar integração com sistema de áudio)`);
    closeNotification(notifId);
}

function closeNotification(notifId) {
    // Marcar como lida
    fetch('/pulso/api/locutor?action=marcar_lida&station_id=1', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + notifId
    });
    
    document.getElementById('notifBanner').style.display = 'none';
    activeNotif = null;
}
</script>
</body>
</html>