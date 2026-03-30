<?php
$stationId = 1;
$locutor = htmlspecialchars($_GET['locutor'] ?? 'Locutor');
?><!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0">
<title>RNB Studio — Rádio New Band</title>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg-0:#050510;--bg-1:#0f0f1f;--bg-2:#1a1a2e;--bg-3:#252538;--bg-4:#2e2e45;
    --accent:#00e5ff;--accent2:#7c3aed;
    --green:#10b981;--red:#ef4444;--gold:#fbbf24;--purple:#a78bfa;--pink:#f472b6;
    --text-1:#ffffff;--text-2:#a1a1aa;--text-3:#71717a;
    --border:rgba(255,255,255,.08);--border2:rgba(255,255,255,.14);
    --glow:0 0 30px rgba(0,229,255,.2);
    --glow-green:0 0 20px rgba(16,185,129,.25);
    --glow-red:0 0 20px rgba(239,68,68,.3);
    --tr:all .3s cubic-bezier(.4,0,.2,1);
    --ff:'Inter',-apple-system,sans-serif;
    --fm:'JetBrains Mono',monospace;
}
html,body{
    height:100vh;overflow:hidden;
    font-family:var(--ff);
    background:var(--bg-0);
    color:var(--text-1);
    font-size:13px;
    -webkit-font-smoothing:antialiased;
}
/* Fundo com gradientes radiais como no PULSO */
body::before{
    content:'';position:fixed;inset:0;
    background:
        radial-gradient(circle at 15% 40%,rgba(124,58,237,.07),transparent 45%),
        radial-gradient(circle at 85% 70%,rgba(0,229,255,.05),transparent 45%),
        radial-gradient(circle at 50% 10%,rgba(16,185,129,.03),transparent 40%);
    pointer-events:none;z-index:0;
}
body>*{position:relative;z-index:1}

/* ── HEADER PRINCIPAL ── */
#hdr{
    height:56px;
    background:rgba(15,15,31,.85);
    backdrop-filter:blur(20px);
    border-bottom:1px solid var(--border);
    display:flex;align-items:center;
    padding:0 20px;gap:14px;
    flex-shrink:0;position:relative;z-index:200;
}

/* Logo RNB */
.rnb-logo{
    display:flex;align-items:center;gap:10px;
    flex-shrink:0;padding-right:16px;
    border-right:1px solid var(--border);
}
.rnb-logo-icon{
    width:36px;height:36px;
    background:linear-gradient(135deg,var(--accent),var(--accent2));
    border-radius:10px;
    display:flex;align-items:center;justify-content:center;
    box-shadow:var(--glow);
    flex-shrink:0;
    font-size:0;
    overflow:hidden;
}
.rnb-logo-icon svg{width:28px;height:28px}
.rnb-logo-text{display:flex;flex-direction:column;line-height:1}
.rnb-logo-nome{
    font-size:14px;font-weight:900;
    background:linear-gradient(135deg,var(--accent),#fff 60%);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;
    background-clip:text;
    letter-spacing:-.3px;
}
.rnb-logo-produto{
    font-size:9px;font-weight:700;
    color:var(--text-3);
    letter-spacing:2px;text-transform:uppercase;
    margin-top:2px;
}

/* Barra NOW PLAYING — elemento dominante */
#np-bar{
    flex:1;min-width:0;
    display:flex;align-items:center;gap:12px;
    background:linear-gradient(135deg,rgba(26,26,46,.9),rgba(37,37,56,.9));
    border:1px solid var(--border);
    border-radius:12px;
    padding:0 16px;height:40px;
    position:relative;overflow:hidden;
}
#np-bar::before{
    content:'';position:absolute;
    top:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,var(--green),var(--accent),transparent);
    opacity:.6;
}
#np-progress-bg{
    position:absolute;left:0;top:0;bottom:0;
    background:linear-gradient(90deg,rgba(16,185,129,.06),transparent);
    transition:width 1s linear;pointer-events:none;
    border-radius:12px;
}
.np-live-dot{
    width:7px;height:7px;border-radius:50%;
    background:var(--green);flex-shrink:0;
    animation:livepulse 1.4s ease-in-out infinite;
    box-shadow:0 0 8px var(--green);
}
@keyframes livepulse{
    0%,100%{opacity:1;box-shadow:0 0 8px var(--green)}
    50%{opacity:.6;box-shadow:0 0 4px var(--green)}
}
#np-song{
    font-size:14px;font-weight:700;
    color:var(--text-1);flex:1;min-width:0;
    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
    position:relative;z-index:1;
}
.np-art{color:var(--text-2);font-weight:400}
#np-timer{
    font:800 24px var(--fm);
    flex-shrink:0;min-width:68px;text-align:right;
    transition:color .5s;
    position:relative;z-index:1;
    letter-spacing:-1px;
}
#np-timer.g{color:var(--green);text-shadow:0 0 12px rgba(16,185,129,.4)}
#np-timer.a{color:var(--gold);text-shadow:0 0 12px rgba(251,191,36,.4)}
#np-timer.r{color:var(--red);text-shadow:0 0 16px rgba(239,68,68,.5);animation:timerblink .5s infinite}
@keyframes timerblink{50%{opacity:.7}}
.np-pb{width:100px;height:3px;background:rgba(255,255,255,.06);border-radius:2px;overflow:hidden;flex-shrink:0}
#np-fill{height:100%;border-radius:2px;transition:width 1s linear,background .5s}
#np-next{font-size:10px;color:var(--text-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;flex-shrink:0;position:relative;z-index:1}

/* Header direita */
.hdr-r{display:flex;align-items:center;gap:8px;flex-shrink:0}

/* Programa + countdown */
#prog-zone{
    display:none;
    align-items:center;gap:10px;
    padding:0 14px;
    border-left:1px solid var(--border);
    border-right:1px solid var(--border);
}
.pz-nome{font-size:10px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:1px}
#pz-cd{font:700 15px var(--fm);color:var(--gold);text-shadow:0 0 10px rgba(251,191,36,.3)}

.hdr-stat{display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text-2)}
.hdr-stat b{color:var(--accent);font-family:var(--fm);font-size:13px}

.hdr-ico{
    width:34px;height:34px;border-radius:9px;
    background:rgba(255,255,255,.04);
    border:1px solid var(--border);
    cursor:pointer;display:flex;align-items:center;justify-content:center;
    color:var(--text-2);font-size:15px;
    transition:var(--tr);position:relative;
}
.hdr-ico:hover{background:rgba(0,229,255,.08);border-color:rgba(0,229,255,.25);color:var(--accent)}
.hdr-ico .bdg{
    position:absolute;top:-4px;right:-4px;
    min-width:15px;height:15px;border-radius:8px;
    background:var(--red);color:#fff;
    font-size:8px;font-weight:800;
    display:none;align-items:center;justify-content:center;
    padding:0 3px;border:2px solid var(--bg-0);
}

.hdr-locutor{
    display:flex;align-items:center;gap:7px;
    padding:0 13px;height:34px;
    background:linear-gradient(135deg,rgba(0,229,255,.08),rgba(124,58,237,.08));
    border:1px solid rgba(0,229,255,.2);
    border-radius:9px;
    font-size:12px;font-weight:700;
    background-clip:padding-box;
}
.hdr-locutor-nome{
    background:linear-gradient(135deg,var(--accent),#fff);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;
    background-clip:text;
}

#hdr-clock{
    font:700 16px var(--fm);
    letter-spacing:.5px;color:var(--text-1);
    min-width:72px;text-align:right;
}

#btn-oa{
    display:flex;align-items:center;gap:7px;
    padding:0 16px;height:34px;border:none;border-radius:9px;
    font:700 12px var(--ff);cursor:pointer;
    transition:var(--tr);letter-spacing:.5px;
}
#btn-oa.off{background:rgba(255,255,255,.05);border:1px solid var(--border);color:var(--text-2)}
#btn-oa.on{
    background:linear-gradient(135deg,#dc2626,#ef4444);
    color:#fff;
    box-shadow:var(--glow-red);
    animation:oa-glow 2s ease-in-out infinite;
}
@keyframes oa-glow{
    0%,100%{box-shadow:0 0 14px rgba(239,68,68,.4)}
    50%{box-shadow:0 0 24px rgba(239,68,68,.6)}
}
.oa-dot{width:6px;height:6px;border-radius:50%;background:#fff;display:none;animation:livepulse 1s infinite}

.hdr-fs{
    width:34px;height:34px;border-radius:9px;
    background:rgba(255,255,255,.04);border:1px solid var(--border);
    cursor:pointer;display:flex;align-items:center;justify-content:center;
    color:var(--text-3);font-size:13px;transition:var(--tr);
}
.hdr-fs:hover{color:var(--text-1);border-color:var(--border2)}

/* ── LAYOUT PRINCIPAL ── */
#studio{
    display:grid;
    grid-template-columns:295px 1fr 275px;
    height:calc(100vh - 56px);
    overflow:hidden;
}

/* ══ COLUNA ESQUERDA: FILA ══════════════════════ */
#col-l{
    background:rgba(15,15,31,.6);
    border-right:1px solid var(--border);
    display:flex;flex-direction:column;
    overflow:hidden;
}
.cl-head{
    padding:11px 16px;
    background:rgba(26,26,46,.7);
    border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;
    flex-shrink:0;
}
.cl-head-t{
    font-size:10px;font-weight:700;
    letter-spacing:1.5px;text-transform:uppercase;
    color:var(--text-3);
    display:flex;align-items:center;gap:7px;
}
.cl-head-t i{color:var(--accent);font-size:12px}
#fila-cnt{
    font:700 11px var(--fm);
    background:rgba(251,191,36,.1);color:var(--gold);
    padding:2px 9px;border-radius:20px;
    border:1px solid rgba(251,191,36,.2);
}

/* Filtros canal */
.cl-filtros{
    display:flex;gap:4px;padding:8px 12px;
    border-bottom:1px solid var(--border);
    flex-shrink:0;overflow-x:auto;scrollbar-width:none;
}
.cl-filtros::-webkit-scrollbar{display:none}
.cf{
    padding:4px 10px;border-radius:20px;
    font-size:9px;font-weight:700;letter-spacing:.3px;
    border:1px solid var(--border);background:none;
    color:var(--text-3);cursor:pointer;transition:var(--tr);
    white-space:nowrap;
}
.cf.on{
    background:linear-gradient(135deg,rgba(0,229,255,.1),rgba(124,58,237,.1));
    border-color:rgba(0,229,255,.25);color:var(--accent);
}

/* IA strip */
#ia-strip{
    display:none;padding:8px 12px;
    background:rgba(0,229,255,.03);
    border-bottom:1px solid rgba(0,229,255,.08);
    flex-shrink:0;
}
#ia-strip.show{display:block}
.ia-lbl{
    font-size:8px;font-weight:700;letter-spacing:1.5px;
    color:var(--accent);text-transform:uppercase;
    margin-bottom:6px;display:flex;align-items:center;gap:5px;
}
.ia-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:5px}
.ia-cell{
    background:rgba(255,255,255,.03);
    border:1px solid var(--border);
    border-radius:8px;padding:7px;text-align:center;
}
.ia-v{font-size:15px;font-weight:800;color:var(--accent)}
.ia-n{font-size:9px;font-weight:600;color:var(--text-2);margin-top:1px}
.ia-s{font-size:8px;color:var(--text-3);margin-top:0px;text-transform:uppercase;letter-spacing:.3px}

/* Fila scroll */
#fila-lista{
    flex:1;overflow-y:auto;
    scrollbar-width:thin;scrollbar-color:var(--bg-3) transparent;
}
#fila-lista::-webkit-scrollbar{width:3px}
#fila-lista::-webkit-scrollbar-thumb{background:var(--bg-3);border-radius:2px}

/* Card fila */
.fc{
    padding:11px 14px;
    border-bottom:1px solid rgba(255,255,255,.04);
    cursor:pointer;transition:background var(--tr);
    border-left:2px solid transparent;position:relative;
}
.fc:hover{background:rgba(255,255,255,.03)}
.fc.sel{
    background:linear-gradient(90deg,rgba(0,229,255,.05),transparent);
    border-left-color:var(--accent);
}
.fc.novo{border-left-color:var(--green)}
.fc-r1{display:flex;align-items:flex-start;gap:7px;margin-bottom:3px}
.fc-nome{font-size:12px;font-weight:700;color:var(--text-1);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fc-seg{font-size:8px;font-weight:700;padding:2px 7px;border-radius:10px;letter-spacing:.5px;flex-shrink:0;margin-top:1px}
.sg-n{background:rgba(16,185,129,.1);color:var(--green)}
.sg-r{background:rgba(0,229,255,.08);color:var(--accent)}
.sg-v{background:rgba(251,191,36,.1);color:var(--gold)}
.sg-e{background:rgba(239,68,68,.1);color:var(--red)}
.fc-canal{font-size:9px;font-weight:600;margin-bottom:3px;display:flex;align-items:center;gap:4px;color:var(--text-3)}
.fc-mus{font-size:11px;color:var(--gold);margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fc-msg{font-size:10px;color:var(--text-2);font-style:italic;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fc-r3{display:flex;align-items:center;justify-content:space-between;margin-top:6px}
.fc-meta{font-size:9px;color:var(--text-3);display:flex;gap:5px}
.fc-btns{display:flex;gap:4px;opacity:0;transition:opacity .15s}
.fc:hover .fc-btns{opacity:1}
.fb{width:23px;height:23px;border-radius:5px;border:none;cursor:pointer;font-size:11px;display:flex;align-items:center;justify-content:center;transition:var(--tr)}
.fb-ok{background:rgba(16,185,129,.1);color:var(--green)}
.fb-ok:hover{background:rgba(16,185,129,.25);box-shadow:var(--glow-green)}
.fb-sk{background:rgba(255,255,255,.04);color:var(--text-3)}
.fb-sk:hover{background:rgba(239,68,68,.12);color:var(--red)}

/* ══ COLUNA CENTRAL: LEITURA ════════════════════ */
#col-m{
    display:flex;flex-direction:column;
    overflow:hidden;background:transparent;
}

/* Info do ouvinte */
#ov-info{
    padding:12px 18px 10px;
    background:rgba(26,26,46,.7);
    backdrop-filter:blur(10px);
    border-bottom:1px solid var(--border);
    flex-shrink:0;display:none;
}
.oi-r1{display:flex;align-items:flex-start;gap:12px;margin-bottom:8px}
.oi-av{
    width:44px;height:44px;border-radius:12px;
    display:flex;align-items:center;justify-content:center;
    font-size:18px;font-weight:900;flex-shrink:0;
}
.oi-nome{font-size:18px;font-weight:800;color:var(--text-1);margin-bottom:5px;line-height:1.1}
.oi-tags{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:8px}
.oi-tag{
    font-size:10px;font-weight:600;padding:3px 9px;
    border-radius:20px;display:flex;align-items:center;gap:4px;
}
.oi-stats{display:flex;gap:7px}
.oi-st{
    background:rgba(255,255,255,.04);
    border:1px solid var(--border);
    border-radius:8px;padding:6px 10px;text-align:center;
}
.oi-sv{font-size:16px;font-weight:900;font-family:var(--fm);color:var(--text-1)}
.oi-sl{font-size:8px;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px;margin-top:1px}
.oi-pfbtn{
    display:inline-flex;align-items:center;gap:4px;
    font-size:10px;font-weight:600;color:var(--accent);
    cursor:pointer;background:none;border:none;
    margin-top:4px;transition:var(--tr);
}
.oi-pfbtn:hover{opacity:.75}

/* Notificações inline */
#notifs-zone{padding:0 18px;flex-shrink:0}
.notif{
    display:flex;align-items:flex-start;gap:10px;
    padding:10px 14px;border-radius:10px;
    margin:8px 0 0;
    border-left:3px solid var(--gold);
    background:linear-gradient(135deg,rgba(251,191,36,.05),rgba(251,191,36,.02));
    box-shadow:0 4px 20px rgba(0,0,0,.3);
    animation:notif-in .3s cubic-bezier(.4,0,.2,1);
}
@keyframes notif-in{from{transform:translateY(-8px);opacity:0}to{transform:translateY(0);opacity:1}}
.notif-ic{font-size:18px;flex-shrink:0;margin-top:1px}
.notif-body{flex:1;min-width:0}
.notif-t{font-size:12px;font-weight:700;color:var(--gold);margin-bottom:2px}
.notif-m{font-size:11px;color:var(--text-2)}
.notif-x{
    background:none;border:none;cursor:pointer;
    color:var(--text-3);font-size:13px;
    width:20px;height:20px;border-radius:3px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    transition:var(--tr);
}
.notif-x:hover{background:rgba(255,255,255,.05);color:var(--text-2)}

/* ZONA DE LEITURA */
#zona-leitura{
    flex:1;overflow-y:auto;
    scrollbar-width:thin;scrollbar-color:var(--bg-3) transparent;
    display:flex;flex-direction:column;
}
#zona-leitura::-webkit-scrollbar{width:3px}
#zona-leitura::-webkit-scrollbar-thumb{background:var(--bg-3);border-radius:2px}

/* Estado vazio */
#zl-empty{
    flex:1;display:flex;flex-direction:column;
    align-items:center;justify-content:center;
    gap:12px;
}
.zl-empty-ico{font-size:56px;opacity:.06;line-height:1}
.zl-empty-t{
    font-size:18px;font-weight:700;
    background:linear-gradient(135deg,var(--text-3),var(--text-2));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;
    background-clip:text;
}
.zl-empty-s{font-size:12px;color:var(--text-3)}

/* Bloco de leitura */
#zl-msg{
    display:none;
    flex-direction:column;
    padding:18px 22px;flex:1;
}
/* Strip do canal */
.zl-canal{
    display:flex;align-items:center;gap:8px;
    margin-bottom:16px;padding-bottom:12px;
    border-bottom:1px solid var(--border);
}
.zl-canal-ico{
    width:30px;height:30px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;
    font-size:15px;flex-shrink:0;
}
.zl-canal-t{
    font-size:10px;font-weight:700;
    letter-spacing:1.5px;text-transform:uppercase;
}
.zl-canal-sub{font-size:10px;color:var(--text-3);margin-left:auto}

/* MÚSICA — destaque */
.zl-musica{
    font-size:24px;font-weight:800;
    color:var(--gold);
    line-height:1.2;margin-bottom:16px;
    display:flex;align-items:flex-start;gap:10px;
    text-shadow:0 0 30px rgba(251,191,36,.2);
}
.zl-mus-ico{font-size:20px;flex-shrink:0;margin-top:2px}

/* A MENSAGEM: o elemento central do painel */
.zl-mensagem{
    font-size:21px;
    line-height:1.7;
    color:var(--text-1);
    font-style:italic;
    font-weight:400;
    padding:20px 22px;
    background:linear-gradient(135deg,rgba(26,26,46,.8),rgba(37,37,56,.6));
    border:1px solid var(--border);
    border-radius:14px;
    border-left:3px solid rgba(255,255,255,.15);
    margin-bottom:16px;
    flex:1;
    letter-spacing:.01em;
    position:relative;overflow:hidden;
}
.zl-mensagem::before{
    content:'';position:absolute;
    top:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,var(--accent),var(--accent2),transparent);
    opacity:.3;
}
.zl-dica{
    margin-bottom:14px;
    font-size:12px;color:var(--green);
    padding:9px 13px;
    background:rgba(16,185,129,.05);
    border-radius:8px;border-left:2px solid var(--green);
    display:flex;align-items:flex-start;gap:7px;
}
.zl-sugs{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}
.zl-sug{
    padding:5px 12px;border-radius:20px;
    background:rgba(167,139,250,.08);
    border:1px solid rgba(167,139,250,.2);
    color:var(--purple);font-size:11px;font-weight:600;
}

/* Histórico */
#zl-hist{
    display:none;padding:10px 22px 14px;
    border-top:1px solid var(--border);flex-shrink:0;
}
.zh-t{font-size:8px;font-weight:700;letter-spacing:1px;color:var(--text-3);text-transform:uppercase;margin-bottom:7px}
.zh-i{
    display:flex;align-items:center;gap:8px;
    padding:6px 10px;border-radius:7px;
    background:rgba(255,255,255,.03);
    border:1px solid var(--border);
    margin-bottom:3px;font-size:11px;
}

/* Botões de acção */
#zl-acoes{
    display:none;gap:10px;
    padding:12px 20px;
    border-top:1px solid var(--border);
    background:rgba(15,15,31,.8);
    backdrop-filter:blur(10px);
    flex-shrink:0;
}
.zl-btn{
    flex:1;display:flex;align-items:center;justify-content:center;
    gap:8px;padding:14px;border:none;border-radius:11px;
    font:700 14px var(--ff);cursor:pointer;transition:var(--tr);
    letter-spacing:.3px;
}
.zl-btn:hover{transform:translateY(-2px)}
.zl-btn:active{transform:scale(.98)}
.zl-btn-lida{
    background:linear-gradient(135deg,#059669,#10b981);
    color:#fff;box-shadow:var(--glow-green);
}
.zl-btn-lida:hover{box-shadow:0 0 30px rgba(16,185,129,.4)}
.zl-btn-skip{
    background:rgba(255,255,255,.05);
    color:var(--text-2);
    border:1px solid var(--border);
}
.zl-btn-skip:hover{
    background:rgba(239,68,68,.08);
    color:var(--red);border-color:rgba(239,68,68,.2);
}

/* ══ COLUNA DIREITA: FERRAMENTAS ════════════════ */
#col-r{
    background:rgba(15,15,31,.6);
    border-left:1px solid var(--border);
    display:flex;flex-direction:column;overflow:hidden;
}

/* Tabs */
.tabs-nav{
    display:flex;border-bottom:1px solid var(--border);
    background:rgba(26,26,46,.7);
    flex-shrink:0;overflow-x:auto;scrollbar-width:none;
}
.tabs-nav::-webkit-scrollbar{display:none}
.tnav{
    flex:1;min-width:42px;
    padding:9px 2px;background:none;border:none;
    font:600 8px var(--ff);letter-spacing:.4px;text-transform:uppercase;
    color:var(--text-3);cursor:pointer;
    border-bottom:2px solid transparent;transition:var(--tr);
    display:flex;flex-direction:column;align-items:center;gap:3px;
    position:relative;
}
.tnav i{font-size:14px}
.tnav.on{
    color:var(--accent);
    border-bottom-color:var(--accent);
    background:rgba(0,229,255,.04);
}
.tnav:hover:not(.on){color:var(--text-2)}
.tnav .bdg{
    position:absolute;top:4px;right:5px;
    min-width:13px;height:13px;border-radius:7px;
    background:var(--red);color:#fff;
    font-size:8px;font-weight:800;
    display:none;align-items:center;justify-content:center;
    padding:0 2px;border:1.5px solid var(--bg-0);
}

.tab-p{flex:1;overflow:hidden;display:none;flex-direction:column}
.tab-p.on{display:flex}
.tscroll{
    flex:1;overflow-y:auto;padding:10px 11px;
    scrollbar-width:thin;scrollbar-color:var(--bg-3) transparent;
}
.tscroll::-webkit-scrollbar{width:3px}
.tscroll::-webkit-scrollbar-thumb{background:var(--bg-3);border-radius:2px}

/* ── NOTAS ── */
.n-new{
    margin:8px 11px 0;
    display:flex;align-items:center;justify-content:center;gap:5px;
    padding:8px;border-radius:8px;
    background:rgba(0,229,255,.04);
    border:1px dashed rgba(0,229,255,.2);
    color:var(--accent);font-size:10px;font-weight:700;
    cursor:pointer;transition:var(--tr);flex-shrink:0;
}
.n-new:hover{background:rgba(0,229,255,.09)}
.n-form{display:none;padding:8px 11px;border-bottom:1px solid var(--border);flex-shrink:0}
.n-form.open{display:block}
.ni{
    width:100%;
    background:rgba(255,255,255,.04);
    border:1px solid var(--border);
    border-radius:7px;padding:6px 9px;
    color:var(--text-1);font:12px var(--ff);
    outline:none;resize:none;color-scheme:dark;
    margin-bottom:4px;
}
.ni:focus{border-color:rgba(0,229,255,.35)}
.ni::placeholder{color:var(--text-3)}
.n-row{display:flex;gap:4px}
.nsel{
    flex:1;background:rgba(255,255,255,.04);
    border:1px solid var(--border);
    border-radius:6px;padding:5px 7px;
    color:var(--text-1);font:11px var(--ff);outline:none;
}
.nbtn{padding:5px 11px;border-radius:6px;font:700 10px var(--ff);cursor:pointer;border:none}
.nbtn-ok{background:linear-gradient(135deg,var(--accent),var(--accent2));color:#000}
.nbtn-x{background:rgba(255,255,255,.05);border:1px solid var(--border);color:var(--text-2)}

.nc{
    background:linear-gradient(135deg,rgba(26,26,46,.8),rgba(37,37,56,.6));
    border:1px solid var(--border);
    border-radius:10px;padding:10px 12px;margin-bottom:6px;
    border-left:3px solid var(--border);cursor:pointer;transition:var(--tr);
    position:relative;overflow:hidden;
}
.nc::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,var(--accent),transparent);opacity:0;transition:var(--tr)}
.nc:hover{background:linear-gradient(135deg,rgba(37,37,56,.9),rgba(46,46,69,.7));border-color:var(--border2)}
.nc:hover::before{opacity:.3}
.nc.urgente{border-left-color:var(--red)}
.nc.alta{border-left-color:var(--gold)}
.nc.normal{border-left-color:var(--accent)}
.nc-tipo{font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--text-3);margin-bottom:3px;display:flex;justify-content:space-between}
.nc-t{font-size:12px;font-weight:700;color:var(--text-1);margin-bottom:3px}
.nc-p{font-size:10px;color:var(--text-2);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4}
.nc-meta{font-size:9px;color:var(--text-3);margin-top:5px;display:flex;gap:7px}
.nc-leit{color:var(--purple);font-weight:600}
.nc-acts{display:flex;gap:3px;margin-top:5px;opacity:0;transition:opacity .15s}
.nc:hover .nc-acts{opacity:1}
.nc-ab{padding:2px 7px;border-radius:3px;font:700 9px var(--ff);cursor:pointer;border:none}
.nc-del{background:rgba(239,68,68,.1);color:var(--red)}
.nc-edt{background:rgba(0,229,255,.08);color:var(--accent)}
.ub{font-size:7px;font-weight:700;padding:1px 5px;border-radius:3px}
.ub-r{background:rgba(239,68,68,.12);color:var(--red)}
.ub-a{background:rgba(251,191,36,.12);color:var(--gold)}

/* Nota modal */
.nmod{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);backdrop-filter:blur(16px);z-index:900;align-items:center;justify-content:center}
.nmod.open{display:flex}
.nm-box{
    background:linear-gradient(135deg,rgba(26,26,46,.98),rgba(15,15,31,.98));
    border:1px solid var(--border2);
    border-radius:16px;padding:22px;
    width:90%;max-width:520px;max-height:88vh;overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.6);
}
.nm-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px}
.nm-ht{font-size:16px;font-weight:800;color:var(--text-1)}
.nm-x{
    background:rgba(255,255,255,.06);border:1px solid var(--border);
    color:var(--text-2);width:28px;height:28px;border-radius:7px;
    cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;
}
.nm-bds{display:flex;gap:5px;margin-bottom:12px;flex-wrap:wrap}
.nm-bd{font-size:9px;padding:3px 9px;border-radius:4px}
.nm-body{
    font-size:16px;line-height:1.75;color:var(--text-1);
    white-space:pre-wrap;
    padding:16px;
    background:rgba(0,229,255,.025);
    border:1px solid rgba(0,229,255,.08);
    border-radius:10px;margin-bottom:12px;
    max-height:280px;overflow-y:auto;
}
.nm-meta{font-size:9px;color:var(--text-3);display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.nm-ler{
    display:flex;align-items:center;justify-content:center;gap:7px;
    width:100%;padding:13px;
    background:linear-gradient(135deg,#059669,#10b981);
    border:none;border-radius:10px;
    font:700 14px var(--ff);color:#fff;cursor:pointer;
    box-shadow:var(--glow-green);transition:var(--tr);
}
.nm-ler:hover{opacity:.9}

/* ── CHAT ── */
.chat-msgs{
    flex:1;overflow-y:auto;padding:8px 10px;
    display:flex;flex-direction:column;gap:5px;
    scrollbar-width:thin;scrollbar-color:var(--bg-3) transparent;
}
.chat-msgs::-webkit-scrollbar{width:3px}
.cm{
    max-width:90%;padding:8px 11px;
    border-radius:10px;font-size:12px;line-height:1.5;
}
.cm.deles{
    background:rgba(255,255,255,.05);
    border:1px solid var(--border);
    color:var(--text-1);align-self:flex-start;border-bottom-left-radius:2px;
}
.cm.meu{
    background:linear-gradient(135deg,rgba(0,229,255,.1),rgba(124,58,237,.08));
    border:1px solid rgba(0,229,255,.18);
    color:var(--text-1);align-self:flex-end;border-bottom-right-radius:2px;
}
.cm.urg{border-left:2px solid var(--red);background:rgba(239,68,68,.06)}
.cm-aut{font-size:8px;font-weight:700;color:var(--text-3);margin-bottom:2px;letter-spacing:.5px;text-transform:uppercase}
.cm-t{font-size:8px;color:var(--text-3);margin-top:3px;text-align:right}
.chat-in{
    padding:8px 10px;border-top:1px solid var(--border);
    display:flex;gap:5px;align-items:flex-end;flex-shrink:0;
    background:rgba(26,26,46,.5);
}
.ci-ta{
    flex:1;background:rgba(255,255,255,.04);
    border:1px solid var(--border);
    border-radius:8px;padding:7px 9px;
    color:var(--text-1);font:12px var(--ff);
    outline:none;resize:none;min-height:34px;max-height:72px;
}
.ci-ta:focus{border-color:rgba(0,229,255,.3)}
.ci-ta::placeholder{color:var(--text-3)}
.ci-ub{
    width:30px;height:30px;border-radius:7px;
    background:rgba(255,255,255,.04);border:1px solid var(--border);
    cursor:pointer;color:var(--text-3);font-size:12px;
    display:flex;align-items:center;justify-content:center;transition:var(--tr);
}
.ci-ub.on{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.25);color:var(--red)}
.ci-sb{
    width:30px;height:30px;border-radius:7px;
    background:linear-gradient(135deg,var(--accent),var(--accent2));
    border:none;cursor:pointer;color:#000;font-size:13px;
    display:flex;align-items:center;justify-content:center;transition:var(--tr);
}
.ci-sb:hover{opacity:.85}

/* ── PRÉMIOS ── */
.pm-hdr{
    padding:8px 11px;border-bottom:1px solid var(--border);
    font-size:9px;color:var(--text-3);
    display:flex;justify-content:space-between;flex-shrink:0;
    background:rgba(26,26,46,.5);
}
.pm-prog{
    font-size:9px;font-weight:700;
    background:linear-gradient(135deg,var(--accent),var(--green));
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
}
.pc{
    background:linear-gradient(135deg,rgba(26,26,46,.8),rgba(37,37,56,.6));
    border:1px solid var(--border);
    border-radius:10px;padding:11px 13px;margin-bottom:8px;
    border-left:3px solid var(--gold);
    position:relative;overflow:hidden;
}
.pc::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,var(--gold),transparent);opacity:.4}
.pc-promo{font-size:8px;font-weight:700;letter-spacing:.8px;color:var(--text-3);text-transform:uppercase;margin-bottom:5px}
.pc-ov{display:flex;align-items:center;gap:8px}
.pc-av{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:900;color:#000;flex-shrink:0}
.pc-nome{font-size:13px;font-weight:700;color:var(--text-1)}
.pc-tel{font-size:11px;color:var(--accent);font-family:var(--fm);margin-top:1px}
.pc-loc{font-size:9px;color:var(--text-3);margin-top:3px}
.pc-btn{
    display:flex;align-items:center;justify-content:center;gap:5px;
    width:100%;padding:10px;margin-top:9px;
    background:rgba(251,191,36,.07);border:1px solid rgba(251,191,36,.22);
    border-radius:8px;font:700 12px var(--ff);color:var(--gold);
    cursor:pointer;transition:var(--tr);
}
.pc-btn:hover{background:rgba(251,191,36,.16);box-shadow:0 0 15px rgba(251,191,36,.15)}
.pc-btn:disabled{opacity:.4;cursor:default}
.pc-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem;color:var(--text-3);gap:7px;text-align:center}
.pc-empty i{font-size:26px;opacity:.15}

/* ── ANIVERSÁRIOS ── */
.ac{
    display:flex;align-items:center;gap:9px;
    padding:10px 12px;border-radius:10px;
    background:linear-gradient(135deg,rgba(26,26,46,.8),rgba(37,37,56,.6));
    border:1px solid var(--border);
    margin-bottom:6px;border-left:3px solid #ec4899;
}
.ac-av{
    width:32px;height:32px;border-radius:50%;
    background:linear-gradient(135deg,#ec4899,#8b5cf6);
    display:flex;align-items:center;justify-content:center;
    font-size:13px;font-weight:800;color:#fff;flex-shrink:0;
}
.ac-nome{font-size:12px;font-weight:700;color:var(--text-1)}
.ac-sub{font-size:9px;color:var(--text-3);margin-top:1px}
.ac-tel{font-size:10px;color:var(--accent);font-family:var(--fm);margin-top:2px}

/* ── ESCALA ── */
.esc-dias{
    display:flex;gap:3px;padding:7px 10px;
    border-bottom:1px solid var(--border);flex-shrink:0;
    overflow-x:auto;scrollbar-width:none;
    background:rgba(26,26,46,.5);
}
.esc-dias::-webkit-scrollbar{display:none}
.esc-dbt{
    flex:1;min-width:30px;padding:5px 2px;border-radius:6px;
    background:none;border:1px solid var(--border);
    font-size:8px;font-weight:700;color:var(--text-3);
    cursor:pointer;transition:var(--tr);text-align:center;
    display:flex;flex-direction:column;align-items:center;gap:1px;
}
.esc-dbt span{font-size:7px;font-weight:400;opacity:.5}
.esc-dbt.on{
    background:linear-gradient(135deg,rgba(0,229,255,.08),rgba(124,58,237,.08));
    border-color:rgba(0,229,255,.22);color:var(--accent);
}
.esc-dbt.hj{border-color:rgba(16,185,129,.25);color:var(--green)}
.esc-dbt.hj.on{background:rgba(16,185,129,.07)}
.esc-slot{
    display:flex;align-items:center;gap:7px;
    padding:9px 11px;border-radius:9px;
    background:linear-gradient(135deg,rgba(26,26,46,.7),rgba(37,37,56,.5));
    border:1px solid var(--border);
    margin-bottom:5px;font-size:11px;position:relative;transition:var(--tr);
}
.esc-slot:hover{border-color:var(--border2)}
.esc-slot.atual{
    background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(16,185,129,.04));
    border-color:rgba(16,185,129,.2);
    box-shadow:var(--glow-green);
}
.esc-slot.atual::after{
    content:'◉ AO VIVO';
    position:absolute;top:7px;right:9px;
    font-size:7px;font-weight:800;color:var(--green);
    letter-spacing:.8px;
}
.esc-bar{width:3px;height:32px;border-radius:2px;flex-shrink:0}
.esc-prog{font-weight:700;color:var(--text-1);flex:1}
.esc-h{font:700 10px var(--fm);color:var(--text-2);text-align:right;line-height:1.6;flex-shrink:0}

/* ── PROGRAMAÇÃO ── */
.pi{
    display:flex;align-items:center;gap:8px;padding:7px 10px;
    border-radius:7px;
    background:rgba(255,255,255,.03);
    border:1px solid var(--border);
    margin-bottom:4px;
}
.pi-n{width:18px;height:18px;border-radius:4px;background:rgba(255,255,255,.06);display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;color:var(--text-3);flex-shrink:0}
.pi-s{font-size:11px;font-weight:600;color:var(--text-1);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pi-a{font-size:9px;color:var(--text-2)}
.pi-t{font-size:9px;color:var(--text-3);font-family:var(--fm);flex-shrink:0}

/* ── PERFIL MODAL ── */
.pfmod{display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);backdrop-filter:blur(16px);z-index:800;align-items:center;justify-content:center}
.pfmod.open{display:flex}
.pfbox{
    background:linear-gradient(135deg,rgba(26,26,46,.98),rgba(15,15,31,.98));
    border:1px solid var(--border2);border-radius:16px;padding:22px;
    width:90%;max-width:450px;max-height:88vh;overflow-y:auto;
    box-shadow:0 20px 60px rgba(0,0,0,.6);
}

/* ── UTILS ── */
.empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem;color:var(--text-3);text-align:center;gap:7px}
.empty i{font-size:24px;opacity:.12}
.empty p{font-size:11px}
:fullscreen #studio{height:calc(100vh - 56px)}
</style>
</head>
<body>

<!-- HEADER -->
<div id="hdr">
    <!-- Logo RNB -->
    <div class="rnb-logo">
        <div class="rnb-logo-icon">
            <!-- Logo RNB SVG inline baseado no original -->
            <svg viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg">
                <!-- r: barras em arco -->
                <rect x="2" y="6" width="2.5" height="28" rx="1.2" fill="#0099ff"/>
                <rect x="5.5" y="6" width="2.5" height="6" rx="1.2" fill="#00aaff"/>
                <rect x="9" y="5" width="2.5" height="8" rx="1.2" fill="#00ccff"/>
                <rect x="12.5" y="6" width="2.5" height="7" rx="1.2" fill="#33aaff"/>
                <rect x="15.5" y="8" width="2.5" height="5" rx="1.2" fill="#5599ff"/>
                <rect x="9" y="16" width="2.5" height="7" rx="1.2" fill="#7755ff"/>
                <rect x="12.5" y="18" width="2.5" height="8" rx="1.2" fill="#9933ff"/>
                <!-- n -->
                <rect x="21" y="6" width="2.5" height="28" rx="1.2" fill="#aa22ff"/>
                <rect x="24.5" y="8" width="2.5" height="6" rx="1.2" fill="#cc00ee" transform="rotate(12,25.75,11)"/>
                <rect x="27.5" y="13" width="2.5" height="6" rx="1.2" fill="#ee00cc" transform="rotate(12,28.75,16)"/>
                <rect x="30.5" y="18" width="2.5" height="5" rx="1.2" fill="#ff2288" transform="rotate(10,31.75,20.5)"/>
                <rect x="33" y="6" width="2.5" height="28" rx="1.2" fill="#ff5566"/>
                <!-- b -->
                <rect x="39" y="6" width="2.5" height="28" rx="1.2" fill="#ff8800"/>
                <rect x="42.5" y="6" width="2.5" height="6" rx="1.2" fill="#ffaa00"/>
                <rect x="46" y="5" width="2.5" height="8" rx="1.2" fill="#ffcc00"/>
                <rect x="49.5" y="6" width="2.5" height="7" rx="1.2" fill="#ffdd22"/>
                <rect x="52.5" y="8" width="2.5" height="6" rx="1.2" fill="#ffcc44"/>
                <rect x="52.5" y="17" width="2.5" height="5" rx="1.2" fill="#88ddff"/>
                <rect x="42.5" y="18" width="2.5" height="6" rx="1.2" fill="#66aaff"/>
                <rect x="46" y="17" width="2.5" height="8" rx="1.2" fill="#4488ff"/>
                <rect x="49.5" y="18" width="2.5" height="7" rx="1.2" fill="#2266ff"/>
                <rect x="52.5" y="20" width="2.5" height="6" rx="1.2" fill="#0044ff"/>
            </svg>
        </div>
        <div class="rnb-logo-text">
            <span class="rnb-logo-nome">RNB Studio</span>
            <span class="rnb-logo-produto">Rádio New Band</span>
        </div>
    </div>

    <!-- Now Playing -->
    <div id="np-bar">
        <div id="np-progress-bg"></div>
        <div class="np-live-dot"></div>
        <span id="np-song" style="position:relative;z-index:1">A carregar stream...</span>
        <div id="np-timer" class="g">--:--</div>
        <div class="np-pb"><div id="np-fill" style="background:var(--green);width:0%"></div></div>
        <span id="np-next"></span>
    </div>

    <div class="hdr-r">
        <!-- Programa -->
        <div id="prog-zone">
            <div>
                <div class="pz-nome" id="pz-nome"></div>
                <div id="pz-cd">—</div>
            </div>
        </div>

        <div class="hdr-stat"><i class="bi bi-headphones"></i>&nbsp;<b id="hdr-list">0</b></div>

        <div class="hdr-ico" onclick="switchTab('chat')" title="Chat">
            <i class="bi bi-chat-dots"></i>
            <div class="bdg" id="chat-tb-bdg"></div>
        </div>

        <div class="hdr-locutor">
            <i class="bi bi-mic-fill" style="color:var(--accent);font-size:11px"></i>
            <span class="hdr-locutor-nome"><?= $locutor ?></span>
        </div>

        <div id="hdr-clock">00:00:00</div>

        <button id="btn-oa" class="off" onclick="toggleOA()">
            <div class="oa-dot" id="oa-dot"></div>ON AIR
        </button>

        <div class="hdr-fs" onclick="toggleFS()">
            <i class="bi bi-fullscreen" id="fs-ico"></i>
        </div>
    </div>
</div>

<!-- STUDIO -->
<div id="studio">

    <!-- ESQUERDA -->
    <div id="col-l">
        <div class="cl-head">
            <div class="cl-head-t"><i class="bi bi-people-fill"></i>Participações</div>
            <span id="fila-cnt">0</span>
        </div>
        <div class="cl-filtros">
            <button class="cf on" onclick="setCanal('todos',this)">Todos</button>
            <button class="cf" onclick="setCanal('whatsapp',this)">📱 WA</button>
            <button class="cf" onclick="setCanal('dedicatoria',this)">💜 Ded.</button>
            <button class="cf" onclick="setCanal('chamada',this)">📞 Tel.</button>
            <button class="cf" onclick="setCanal('sms',this)">💬 SMS</button>
        </div>
        <div id="ia-strip">
            <div class="ia-lbl"><i class="bi bi-stars" style="font-size:10px"></i>Audiência em Tempo Real</div>
            <div class="ia-grid" id="ia-grid"></div>
        </div>
        <div id="fila-lista">
            <div class="empty"><i class="bi bi-inbox"></i><p>Sem participações</p></div>
        </div>
    </div>

    <!-- CENTRAL -->
    <div id="col-m">
        <div id="ov-info">
            <div class="oi-r1">
                <div class="oi-av" id="oi-av"></div>
                <div style="flex:1;min-width:0">
                    <div class="oi-nome" id="oi-nome"></div>
                    <div class="oi-tags" id="oi-tags"></div>
                </div>
                <div class="oi-stats" id="oi-stats"></div>
            </div>
            <button class="oi-pfbtn" id="oi-pfbtn">
                <i class="bi bi-person-badge" style="font-size:11px"></i>
                Ver perfil completo no PULSO →
            </button>
        </div>
        <div id="notifs-zone"></div>
        <div id="zona-leitura">
            <div id="zl-empty">
                <div class="zl-empty-ico">🎙</div>
                <div class="zl-empty-t">Selecciona uma participação</div>
                <div class="zl-empty-s">ou aguarda novas mensagens</div>
            </div>
            <div id="zl-msg">
                <div class="zl-canal" id="zl-canal"></div>
                <div class="zl-musica" id="zl-mus" style="display:none"></div>
                <div class="zl-mensagem" id="zl-txt"></div>
                <div class="zl-dica" id="zl-dica" style="display:none"></div>
                <div class="zl-sugs" id="zl-sugs"></div>
            </div>
            <div id="zl-hist"></div>
        </div>
        <div id="zl-acoes">
            <button class="zl-btn zl-btn-lida" id="btn-lida">
                <i class="bi bi-mic-fill"></i> Lida no Ar
            </button>
            <button class="zl-btn zl-btn-skip" id="btn-skip">
                <i class="bi bi-skip-forward"></i> Ignorar
            </button>
        </div>
    </div>

    <!-- DIREITA -->
    <div id="col-r">
        <div class="tabs-nav">
            <button class="tnav on" onclick="selTab('notas')" id="tn-notas"><i class="bi bi-journal-text"></i>Notas<span class="bdg" id="bd-notas"></span></button>
            <button class="tnav" onclick="selTab('chat')" id="tn-chat"><i class="bi bi-chat-dots"></i>Chat<span class="bdg" id="bd-chat"></span></button>
            <button class="tnav" onclick="selTab('premios')" id="tn-premios"><i class="bi bi-trophy"></i>Prémios</button>
            <button class="tnav" onclick="selTab('aniv')" id="tn-aniv"><i class="bi bi-balloon-heart"></i>Aniv.<span class="bdg" id="bd-aniv"></span></button>
            <button class="tnav" onclick="selTab('escala')" id="tn-escala"><i class="bi bi-calendar3"></i>Escala</button>
            <button class="tnav" onclick="selTab('prog')" id="tn-prog"><i class="bi bi-music-note-list"></i>Prog.</button>
        </div>

        <div class="tab-p on" id="tp-notas">
            <div class="n-new" onclick="toggleNF()"><i class="bi bi-plus-circle"></i> Nova Nota</div>
            <div class="n-form" id="n-form">
                <input type="text" class="ni" id="n-t" placeholder="Título..." style="min-height:28px">
                <textarea class="ni" id="n-b" placeholder="Conteúdo..." style="min-height:52px;margin-top:4px"></textarea>
                <div class="n-row">
                    <select class="nsel" id="n-tp"><option value="aviso">📌 Aviso</option><option value="jornalistica">📰 Jorn.</option><option value="promocional">🎉 Promo</option><option value="comercial">💼 Com.</option></select>
                    <select class="nsel" id="n-pr"><option value="normal">Normal</option><option value="alta">⚡ Alta</option><option value="urgente">🔴 Urgente</option></select>
                    <button class="nbtn nbtn-ok" id="n-ok-btn" onclick="saveNota()">✓</button>
                    <button class="nbtn nbtn-x" onclick="cancelNF()">✕</button>
                </div>
            </div>
            <div class="tscroll" id="notas-lista"></div>
        </div>

        <div class="tab-p" id="tp-chat">
            <div class="chat-msgs" id="chat-msgs"></div>
            <div class="chat-in">
                <div class="ci-ub" id="ci-ub" onclick="toggleUrg()"><i class="bi bi-exclamation-triangle"></i></div>
                <textarea class="ci-ta" id="ci-ta" placeholder="Para a equipa..." onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChat()}"></textarea>
                <button class="ci-sb" onclick="sendChat()"><i class="bi bi-send-fill"></i></button>
            </div>
        </div>

        <div class="tab-p" id="tp-premios">
            <div class="pm-hdr"><span>Para anunciar</span><span class="pm-prog" id="pm-prog">—</span></div>
            <div class="tscroll" id="premios-lista"></div>
        </div>

        <div class="tab-p" id="tp-aniv">
            <div class="tscroll" id="aniv-lista"></div>
        </div>

        <div class="tab-p" id="tp-escala">
            <div class="esc-dias" id="esc-dias"></div>
            <div class="tscroll" id="escala-lista" style="padding-top:6px"></div>
        </div>

        <div class="tab-p" id="tp-prog">
            <div class="tscroll" id="prog-lista"></div>
        </div>
    </div>
</div>

<!-- MODAIS -->
<div class="nmod" id="nmod">
    <div class="nm-box">
        <div class="nm-hd"><span class="nm-ht" id="nm-t"></span><button class="nm-x" onclick="closeNM()">✕</button></div>
        <div class="nm-bds" id="nm-bds"></div>
        <div class="nm-body" id="nm-body"></div>
        <div class="nm-meta" id="nm-meta"></div>
        <button class="nm-ler" id="nm-ler"><i class="bi bi-check2-circle"></i> Marcar como Lida</button>
    </div>
</div>

<div class="pfmod" id="pfmod">
    <div class="pfbox">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
            <span style="font-size:15px;font-weight:800;color:var(--text-1)">Perfil do Ouvinte</span>
            <button class="nm-x" onclick="closePF()">✕</button>
        </div>
        <div id="pf-body"></div>
    </div>
</div>

<script src="/pulso/static/locutor-studio.js?v=<?= filemtime(__DIR__.'/locutor-studio.js') ?>"></script>

</body>
</html>
