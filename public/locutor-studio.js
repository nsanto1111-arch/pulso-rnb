const SID=<?= $stationId ?>, API='/pulso/api/locutor', LOC='<?= $locutor ?>';
let card=null, onAir=false, elapsed=0, dur=0, npTick=null;
let urgOn=false, nmId=null, notaEditId=null;
let canalF='todos', premData=[], escData=null, escDia=-1;
const seenNotifs=new Set();

/* RELÓGIO */
setInterval(()=>{
    const n=new Date();
    document.getElementById('hdr-clock').textContent=
        [n.getHours(),n.getMinutes(),n.getSeconds()].map(v=>String(v).padStart(2,'0')).join(':');
},1000);

/* ON AIR */
function toggleOA(){
    onAir=!onAir;
    const btn=document.getElementById('btn-oa');
    const dot=document.getElementById('oa-dot');
    btn.className='';
    btn.className=onAir?'on':'off';
    dot.style.display=onAir?'block':'none';
    btn.innerHTML=(onAir?'<div class="oa-dot" style="display:block"></div>AO VIVO':'ON AIR');
}

/* FULLSCREEN */
function toggleFS(){
    if(!document.fullscreenElement) document.documentElement.requestFullscreen().catch(()=>{});
    else document.exitFullscreen();
}
document.addEventListener('fullscreenchange',()=>{
    document.getElementById('fs-ico').className=document.fullscreenElement?'bi bi-fullscreen-exit':'bi bi-fullscreen';
});

/* NOW PLAYING */
function fetchNP(){
    fetch(API+'?action=nowplaying&station_id='+SID)
        .then(r=>r.json()).then(d=>{
            if(d.status!=='ok') return;
            const s=d.song;
            document.getElementById('np-song').innerHTML='<b>'+esc(s.title)+'</b> <span class="np-art">— '+esc(s.artist)+'</span>';
            document.getElementById('hdr-list').textContent=d.listeners||0;
            elapsed=s.elapsed||0; dur=s.duration||0;
            if(npTick) clearInterval(npTick);
            npTick=setInterval(()=>{
                elapsed++;
                const rem=Math.max(0,dur-elapsed);
                const m=Math.floor(rem/60),s2=rem%60;
                const te=document.getElementById('np-timer');
                const fl=document.getElementById('np-fill');
                const pb=document.getElementById('np-progress-bg');
                te.textContent='-'+String(m).padStart(2,'0')+':'+String(s2).padStart(2,'0');
                const pct=dur>0?Math.min(100,Math.round(elapsed/dur*100)):0;
                fl.style.width=pct+'%';
                pb.style.width=pct+'%';
                if(rem<=10){te.className='r';fl.style.background='var(--red)';pb.style.background='rgba(239,68,68,.06)';}
                else if(rem<=30){te.className='a';fl.style.background='var(--gold)';pb.style.background='rgba(251,191,36,.05)';}
                else{te.className='g';fl.style.background='var(--green)';pb.style.background='rgba(16,185,129,.04)';}
            },1000);
            if(d.next) document.getElementById('np-next').innerHTML='→ <b>'+esc(d.next.title)+'</b>';
        }).catch(()=>{});
}
fetchNP(); setInterval(fetchNP,12000);

/* PROGRAMA COUNTDOWN */
function updateProgCD(){
    if(!escData) return;
    const hoje=escData.hoje, slots=escData.semana[hoje]||[];
    const n=new Date(), hs=n.getHours()*3600+n.getMinutes()*60+n.getSeconds();
    const pz=document.getElementById('prog-zone');
    for(const sl of slots){
        if(!sl.hora_inicio||!sl.hora_fim) continue;
        const hI=sl.hora_inicio.split(':').reduce((a,b,i)=>a+parseInt(b)*[3600,60,1][i],0);
        const hF=sl.hora_fim.split(':').reduce((a,b,i)=>a+parseInt(b)*[3600,60,1][i],0);
        if(hs>=hI&&hs<=hF){
            const rem=hF-hs, h=Math.floor(rem/3600), m=Math.floor((rem%3600)/60), s=rem%60;
            pz.style.display='flex';
            document.getElementById('pz-nome').textContent=sl.programa.toUpperCase();
            document.getElementById('pz-cd').textContent=(h>0?String(h).padStart(2,'0')+':':'')+String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
            return;
        }
    }
    pz.style.display='none';
}
setInterval(updateProgCD,1000);

/* FILA */
let lastFila=[];
function fetchFila(){
    fetch(API+'?action=fila&station_id='+SID)
        .then(r=>r.json()).then(d=>{ lastFila=d.fila||[]; renderFila(); }).catch(()=>{});
}
function setCanal(c,btn){
    canalF=c;
    document.querySelectorAll('.cf').forEach(b=>b.classList.remove('on'));
    btn.classList.add('on');
    renderFila();
}
function renderFila(){
    let items=lastFila;
    if(canalF!=='todos') items=items.filter(p=>{
        const t=(p.tipo||'').toLowerCase();
        if(canalF==='whatsapp') return t.includes('whatsapp');
        if(canalF==='dedicatoria') return t.includes('dedic')||t.includes('pedido')||t.includes('promo');
        if(canalF==='chamada') return t.includes('chamada')||t.includes('telefon');
        if(canalF==='sms') return t.includes('sms');
        return true;
    });
    document.getElementById('fila-cnt').textContent=items.length;
    const list=document.getElementById('fila-lista');
    if(!items.length){ list.innerHTML='<div class="empty"><i class="bi bi-inbox"></i><p>Sem participações</p></div>'; return; }

    // IA strip
    if(lastFila.length){
        document.getElementById('ia-strip').classList.add('show');
        const novos=lastFila.filter(p=>p.segmento==='novo').length;
        const pts=lastFila.reduce((a,p)=>a+parseInt(p.ouvinte_pontos||0),0);
        document.getElementById('ia-grid').innerHTML=[
            ['Na Fila',lastFila.length,'total'],
            ['Novos',novos,'hoje'],
            ['Média',Math.round(pts/(lastFila.length||1)),'pts'],
        ].map(([n,v,s])=>`<div class="ia-cell"><div class="ia-v">${v}</div><div class="ia-n">${n}</div><div class="ia-s">${s}</div></div>`).join('');
    }

    const ci=tipo=>{
        const t=(tipo||'').toLowerCase();
        if(t.includes('whatsapp')) return {ico:'📱',lbl:'WhatsApp',c:'#25d366'};
        if(t.includes('dedic')||t.includes('pedido')) return {ico:'💜',lbl:'Dedicatória',c:'var(--purple)'};
        if(t.includes('chamada')||t.includes('telefon')) return {ico:'📞',lbl:'Chamada',c:'var(--accent)'};
        if(t.includes('sms')) return {ico:'💬',lbl:'SMS',c:'var(--gold)'};
        if(t.includes('promo')) return {ico:'🎉',lbl:'Promoção',c:'var(--red)'};
        return {ico:'🌐',lbl:'Web',c:'var(--text-3)'};
    };
    list.innerHTML=items.map(p=>{
        const isSel=card&&card.id==p.id;
        const segC={novo:'sg-n',regular:'sg-r',vip:'sg-v',especial:'sg-e'}[p.segmento]||'sg-r';
        const segL={novo:'NOVO',regular:'REG',vip:'VIP',especial:'ESP'}[p.segmento]||'REG';
        const c=ci(p.tipo);
        return `<div class="fc${isSel?' sel':''}${p.segmento==='novo'?' novo':''}"
            onclick="selCard(this,${JSON.stringify(JSON.stringify(p))})" data-id="${p.id}">
            <div class="fc-r1">
                <span class="fc-nome">${esc(p.nome||'Ouvinte')}</span>
                <span class="fc-seg ${segC}">${segL}</span>
            </div>
            <div class="fc-canal" style="color:${c.c}">${c.ico} ${c.lbl}</div>
            ${p.musica?`<div class="fc-mus">🎵 ${esc(p.musica)}</div>`:''}
            ${p.mensagem?`<div class="fc-msg">"${esc(p.mensagem)}"</div>`:''}
            <div class="fc-r3">
                <div class="fc-meta">
                    ${p.cidade&&p.cidade!=='Luanda'?`<span>${esc(p.cidade)}</span>`:''}
                    ${p.ouvinte_pontos?`<span style="color:var(--purple)">${p.ouvinte_pontos}pt</span>`:''}
                    <span>${p.tempo_relativo||''}</span>
                </div>
                <div class="fc-btns">
                    <button class="fb fb-ok" onclick="event.stopPropagation();marcarLida(${p.id})" title="Lida">✓</button>
                    <button class="fb fb-sk" onclick="event.stopPropagation();marcarSkip(${p.id})" title="Ignorar">✕</button>
                </div>
            </div>
        </div>`;
    }).join('');
}
fetchFila(); setInterval(fetchFila,8000);

/* SELECT CARD */
function selCard(el,pJson){
    const p=JSON.parse(pJson); card=p;
    document.querySelectorAll('.fc').forEach(c=>c.classList.remove('sel'));
    el.classList.add('sel');
    renderCenter(p);
    fetch(API+'?action=ouvinte&station_id='+SID+'&id='+p.ouvinte_id)
        .then(r=>r.json()).then(d=>{ if(d.status==='ok') enrichCenter(p,d); }).catch(()=>{});
}

function renderCenter(p){
    const cores=['#00e5ff','#10b981','#fbbf24','#a78bfa','#ef4444','#f472b6'];
    const cor=cores[p.ouvinte_id%cores.length];
    const ini=(p.nome||'?')[0].toUpperCase();
    const cMap=tipo=>{
        const t=(tipo||'').toLowerCase();
        if(t.includes('whatsapp')) return {ico:'📱',l:'WHATSAPP',c:'#25d366',bg:'rgba(37,211,102,.12)'};
        if(t.includes('dedic')||t.includes('pedido')) return {ico:'💜',l:'DEDICATÓRIA',c:'var(--purple)',bg:'rgba(167,139,250,.1)'};
        if(t.includes('chamada')||t.includes('telefon')) return {ico:'📞',l:'CHAMADA',c:'var(--accent)',bg:'rgba(0,229,255,.1)'};
        if(t.includes('sms')) return {ico:'💬',l:'SMS',c:'var(--gold)',bg:'rgba(251,191,36,.1)'};
        if(t.includes('promo')) return {ico:'🎉',l:'PROMOÇÃO',c:'var(--red)',bg:'rgba(239,68,68,.1)'};
        return {ico:'🌐',l:'WEB',c:'var(--text-3)',bg:'rgba(255,255,255,.05)'};
    };
    const cm=cMap(p.tipo);

    const oi=document.getElementById('ov-info');
    oi.style.display='block';
    const avEl=document.getElementById('oi-av');
    avEl.textContent=ini;
    avEl.style.cssText=`width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:900;flex-shrink:0;background:${cor}18;color:${cor};border:1.5px solid ${cor}28`;
    document.getElementById('oi-nome').textContent=p.nome||'Ouvinte';
    document.getElementById('oi-tags').innerHTML=[
        p.telefone?`<span class="oi-tag" style="background:rgba(16,185,129,.07);color:var(--green);border:1px solid rgba(16,185,129,.15)"><i class="bi bi-telephone" style="font-size:9px"></i>${esc(p.telefone)}</span>`:'',
        p.cidade&&p.cidade!=='Luanda'?`<span class="oi-tag" style="background:rgba(0,229,255,.07);color:var(--accent);border:1px solid rgba(0,229,255,.14)"><i class="bi bi-geo-alt" style="font-size:9px"></i>${esc(p.cidade)}</span>`:'',
        p.segmento==='novo'?'<span class="oi-tag" style="background:rgba(16,185,129,.08);color:var(--green);border:1px solid rgba(16,185,129,.18)">★ NOVO</span>':'',
    ].join('');
    document.getElementById('oi-stats').innerHTML=[
        ['Pts',p.ouvinte_pontos||0],['Part.',p.total_participacoes||0],
    ].map(([l,v])=>`<div class="oi-st"><div class="oi-sv">${v}</div><div class="oi-sl">${l}</div></div>`).join('');
    document.getElementById('oi-pfbtn').onclick=()=>openPF(p.ouvinte_id);

    // Zona central
    document.getElementById('zl-empty').style.display='none';
    const msg=document.getElementById('zl-msg');
    msg.style.display='flex';

    document.getElementById('zl-canal').innerHTML=`
        <div class="zl-canal-ico" style="background:${cm.bg}">${cm.ico}</div>
        <span class="zl-canal-t" style="color:${cm.c}">${cm.l}</span>
        <span class="zl-canal-sub">${p.tempo_relativo||''}</span>`;

    const musEl=document.getElementById('zl-mus');
    if(p.musica){
        musEl.style.display='flex';
        musEl.innerHTML='<span class="zl-mus-ico">🎵</span>'+esc(p.musica);
    } else musEl.style.display='none';

    const txtEl=document.getElementById('zl-txt');
    if(p.mensagem){ txtEl.textContent='"'+p.mensagem+'"'; txtEl.style.display='block'; }
    else if(p.musica) txtEl.style.display='none';
    else { txtEl.textContent='Sem mensagem adicional'; txtEl.style.display='block'; txtEl.style.opacity='.4'; }

    const dicaEl=document.getElementById('zl-dica');
    if(p.dica){ dicaEl.style.display='flex'; dicaEl.innerHTML='<i class="bi bi-lightbulb-fill" style="flex-shrink:0"></i>'+esc(p.dica); }
    else dicaEl.style.display='none';

    document.getElementById('zl-sugs').innerHTML='';
    document.getElementById('zl-hist').style.display='none';

    const acoes=document.getElementById('zl-acoes');
    acoes.style.display='flex';
    document.getElementById('btn-lida').onclick=()=>marcarLida(p.id);
    document.getElementById('btn-skip').onclick=()=>marcarSkip(p.id);
}

function enrichCenter(p,d){
    const o=d.ouvinte;
    const stats=document.getElementById('oi-stats');
    if(stats) stats.innerHTML=[
        ['Pts',o.pontos||0,'var(--purple)'],
        ['Part.',o.total_participacoes||0,'var(--accent)'],
        ['Prém.',d.total_premios||0,'var(--gold)'],
        ['Dias',d.dias_ouvinte||0,'var(--text-2)'],
    ].map(([l,v,c])=>`<div class="oi-st"><div class="oi-sv" style="color:${c}">${v}</div><div class="oi-sl">${l}</div></div>`).join('');
    if(d.sugestoes&&d.sugestoes.length)
        document.getElementById('zl-sugs').innerHTML=d.sugestoes.map(s=>`<span class="zl-sug">${esc(s)}</span>`).join('');
    if(d.historico&&d.historico.length){
        const h=document.getElementById('zl-hist');
        h.style.display='block';
        h.innerHTML='<div class="zh-t">Histórico</div>'+
            d.historico.slice(0,4).map(hi=>`<div class="zh-i"><span>🎵</span><span style="flex:1;color:var(--text-2)">${esc(hi.musica||'Participação')}</span><span style="color:var(--text-3);font-size:9px">${hi.tempo_relativo}</span></div>`).join('');
    }
}

function openPF(id){
    fetch(API+'?action=ouvinte&station_id='+SID+'&id='+id)
        .then(r=>r.json()).then(d=>{
            if(d.status!=='ok') return;
            const o=d.ouvinte;
            const cor=['#00e5ff','#10b981','#fbbf24','#a78bfa','#ef4444'][id%5];
            document.getElementById('pf-body').innerHTML=`
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
                    <div style="width:50px;height:50px;border-radius:13px;background:${cor}18;color:${cor};border:1.5px solid ${cor}28;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900">${(o.nome||'?')[0].toUpperCase()}</div>
                    <div>
                        <div style="font-size:17px;font-weight:800;color:var(--text-1)">${esc(o.nome)}</div>
                        <div style="font-size:11px;color:var(--text-2);margin-top:3px">${esc(o.telefone||'')} ${o.cidade?'· '+esc(o.cidade):''}</div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-bottom:14px">
                    ${[['Pontos',o.pontos||0,'var(--purple)'],['Particip.',o.total_participacoes||0,'var(--accent)'],['Prémios',d.total_premios||0,'var(--gold)']].map(([l,v,c])=>`<div class="oi-st"><div class="oi-sv" style="color:${c}">${v}</div><div class="oi-sl">${l}</div></div>`).join('')}
                </div>
                <div style="margin-bottom:12px">
                    <div class="zh-t" style="margin-bottom:6px">Histórico</div>
                    ${(d.historico||[]).slice(0,5).map(h=>`<div class="zh-i"><span>🎵</span><span style="flex:1;color:var(--text-2)">${esc(h.musica||'Participação')}</span><span style="color:var(--text-3);font-size:9px">${h.tempo_relativo}</span></div>`).join('')}
                </div>
                <a href="/public/pulso/${SID}/ouvintes/${id}" target="_blank"
                   style="display:flex;align-items:center;justify-content:center;gap:7px;padding:12px;background:linear-gradient(135deg,rgba(0,229,255,.08),rgba(124,58,237,.08));border:1px solid rgba(0,229,255,.2);border-radius:10px;color:var(--accent);text-decoration:none;font-weight:700;font-size:12px">
                    <i class="bi bi-box-arrow-up-right"></i> Abrir Ficha no PULSO
                </a>`;
            document.getElementById('pfmod').classList.add('open');
        }).catch(()=>{});
}
function closePF(){ document.getElementById('pfmod').classList.remove('open'); }
document.getElementById('pfmod').addEventListener('click',e=>{ if(e.target===document.getElementById('pfmod')) closePF(); });

/* ACÇÕES */
function clearCenter(){
    card=null;
    document.getElementById('ov-info').style.display='none';
    document.getElementById('zl-empty').style.display='flex';
    document.getElementById('zl-msg').style.display='none';
    document.getElementById('zl-acoes').style.display='none';
    document.getElementById('zl-hist').style.display='none';
}
function marcarLida(id){
    const fd=new FormData(); fd.append('id',id);
    fetch(API+'?action=marcar_lida&station_id='+SID,{method:'POST',body:fd}).then(()=>{ if(card&&card.id==id) clearCenter(); fetchFila(); });
}
function marcarSkip(id){
    const fd=new FormData(); fd.append('id',id);
    fetch(API+'?action=marcar_skip&station_id='+SID,{method:'POST',body:fd}).then(()=>{ if(card&&card.id==id) clearCenter(); fetchFila(); });
}

/* TABS */
function selTab(t){
    document.querySelectorAll('.tnav').forEach(b=>b.classList.remove('on'));
    document.querySelectorAll('.tab-p').forEach(p=>p.classList.remove('on'));
    document.getElementById('tn-'+t).classList.add('on');
    document.getElementById('tp-'+t).classList.add('on');
    ({notas:fetchNotas,chat:fetchChat,premios:fetchPremios,aniv:fetchAniv,escala:fetchEscala,prog:fetchProg})[t]?.();
}

/* NOTAS */
let notaEditId=null;
function fetchNotas(){
    fetch(API+'?action=notas_estudio&station_id='+SID)
        .then(r=>r.json()).then(d=>{
            const notas=d.notas||[];
            const bd=document.getElementById('bd-notas');
            const urg=notas.filter(n=>n.prioridade==='urgente').length;
            bd.style.display=urg?'flex':'none'; if(urg) bd.textContent=urg;
            const list=document.getElementById('notas-lista');
            if(!notas.length){ list.innerHTML='<div class="empty"><i class="bi bi-journal-x"></i><p>Sem notas activas</p></div>'; return; }
            const ico={jornalistica:'📰',promocional:'🎉',comercial:'💼',aviso:'📌',locutor:'🎙'};
            list.innerHTML=notas.map(n=>`
                <div class="nc ${n.prioridade}">
                    <div onclick="openNM(${n.id},${JSON.stringify(n.titulo)},${JSON.stringify(n.conteudo)},'${n.tipo}','${n.autor}',${n.total_leituras},${n.max_leituras})" style="cursor:pointer">
                        <div class="nc-tipo"><span>${ico[n.tipo]||'📌'} ${n.tipo}</span>${n.prioridade==='urgente'?'<span class="ub ub-r">URGENTE</span>':n.prioridade==='alta'?'<span class="ub ub-a">ALTA</span>':''}</div>
                        <div class="nc-t">${esc(n.titulo)}</div>
                        <div class="nc-p">${esc(n.conteudo)}</div>
                        <div class="nc-meta"><span>${esc(n.autor)}</span><span class="nc-leit">📖 ${n.total_leituras}${n.max_leituras>0?'/'+n.max_leituras:''}×</span></div>
                    </div>
                    <div class="nc-acts">
                        <button class="nc-ab nc-edt" onclick="startEdit(${n.id},${JSON.stringify(n.titulo)},${JSON.stringify(n.conteudo)},'${n.tipo}','${n.prioridade}')">✏ Editar</button>
                        <button class="nc-ab nc-del" onclick="delNota(${n.id})">🗑 Apagar</button>
                    </div>
                </div>`).join('');
        }).catch(()=>{});
}
fetchNotas(); setInterval(fetchNotas,30000);

function openNM(id,titulo,conteudo,tipo,autor,leit,maxL){
    nmId=id;
    document.getElementById('nm-t').textContent=titulo;
    document.getElementById('nm-body').textContent=conteudo;
    const ico={jornalistica:'📰',promocional:'🎉',comercial:'💼',aviso:'📌',locutor:'🎙'};
    document.getElementById('nm-bds').innerHTML=`
        <span class="nm-bd" style="background:rgba(0,229,255,.08);color:var(--accent)">${ico[tipo]||'📌'} ${tipo}</span>
        <span class="nm-bd" style="background:rgba(255,255,255,.05);color:var(--text-2)">✍ ${autor}</span>
        <span class="nm-bd" style="background:rgba(167,139,250,.08);color:var(--purple)">📖 ${leit}${maxL>0?'/'+maxL:''}×</span>`;
    document.getElementById('nmod').classList.add('open');
}
function closeNM(){ document.getElementById('nmod').classList.remove('open'); nmId=null; }
document.getElementById('nmod').addEventListener('click',e=>{ if(e.target===document.getElementById('nmod')) closeNM(); });
document.getElementById('nm-ler').onclick=function(){
    if(!nmId) return; this.textContent='...'; this.disabled=true;
    fetch(API+'?action=nota_estudio_ler&station_id='+SID,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:nmId,locutor:LOC})})
        .then(()=>{ closeNM(); fetchNotas(); });
};
function toggleNF(){ document.getElementById('n-form').classList.toggle('open'); }
function cancelNF(){
    notaEditId=null;
    document.getElementById('n-t').value='';
    document.getElementById('n-b').value='';
    document.getElementById('n-ok-btn').textContent='✓';
    document.getElementById('n-ok-btn').onclick=saveNota;
    document.getElementById('n-form').classList.remove('open');
}
function saveNota(){
    const t=document.getElementById('n-t').value.trim(), b=document.getElementById('n-b').value.trim();
    if(!b) return;
    const action=notaEditId?'nota_editar':'nota_estudio_salvar';
    const body={titulo:t||'Nota',conteudo:b,tipo:document.getElementById('n-tp').value,prioridade:document.getElementById('n-pr').value,autor:LOC};
    if(notaEditId) body.id=notaEditId;
    fetch(API+'?action='+action+'&station_id='+SID,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)})
        .then(()=>{ cancelNF(); fetchNotas(); });
}
function startEdit(id,titulo,conteudo,tipo,prio){
    notaEditId=id;
    document.getElementById('n-t').value=titulo;
    document.getElementById('n-b').value=conteudo;
    document.getElementById('n-tp').value=tipo;
    document.getElementById('n-pr').value=prio;
    const btn=document.getElementById('n-ok-btn');
    btn.textContent='✓ Actualizar'; btn.onclick=saveNota;
    document.getElementById('n-form').classList.add('open');
}
function delNota(id){
    if(!confirm('Apagar esta nota?')) return;
    fetch(API+'?action=nota_apagar&station_id='+SID,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})})
        .then(()=>fetchNotas());
}

/* CHAT */
function fetchChat(){
    fetch(API+'?action=chat_mensagens&station_id='+SID)
        .then(r=>r.json()).then(d=>{
            const msgs=d.mensagens||[];
            const nl=msgs.filter(m=>m.departamento!=='locutor'&&!m.lida).length;
            ['bd-chat','chat-tb-bdg'].forEach(id=>{ const el=document.getElementById(id); el.style.display=nl?'flex':'none'; if(nl)el.textContent=nl; });
            const c=document.getElementById('chat-msgs');
            const atBot=c.scrollTop+c.clientHeight>=c.scrollHeight-20;
            const dep={promocao:'Promoção',atendimento:'Atend.',comercial:'Comercial',admin:'Admin',locutor:'Eu'};
            c.innerHTML=msgs.map(m=>`<div class="cm ${m.departamento==='locutor'?'meu':'deles'} ${m.urgente?'urg':''}">
                ${m.departamento!=='locutor'?`<div class="cm-aut">${dep[m.departamento]||m.departamento} · ${esc(m.autor)}</div>`:''}
                ${m.urgente?'<span style="font-size:8px;font-weight:700;color:var(--red);background:rgba(239,68,68,.1);padding:1px 5px;border-radius:3px;margin-right:3px">URG</span>':''}${esc(m.mensagem)}
                <div class="cm-t">${(m.data_criacao||'').substring(11,16)}</div>
            </div>`).join('');
            if(atBot) c.scrollTop=c.scrollHeight;
        }).catch(()=>{});
}
fetchChat(); setInterval(fetchChat,6000);
let urgMsg=false;
function toggleUrg(){ urgMsg=!urgMsg; document.getElementById('ci-ub').classList.toggle('on',urgMsg); }
function sendChat(){
    const ta=document.getElementById('ci-ta'), msg=ta.value.trim(); if(!msg) return;
    fetch(API+'?action=chat_enviar&station_id='+SID,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({autor:LOC,departamento:'locutor',mensagem:msg,urgente:urgMsg?1:0})})
        .then(()=>{ ta.value=''; urgMsg=false; document.getElementById('ci-ub').classList.remove('on'); fetchChat(); });
}

/* PRÉMIOS */
function fetchPremios(){
    fetch(API+'?action=premios_locutor&station_id='+SID)
        .then(r=>r.json()).then(d=>{
            premData=d.premios||[];
            document.getElementById('pm-prog').textContent=d.prog_atual||'—';
            const list=document.getElementById('premios-lista');
            if(!premData.length){ list.innerHTML=`<div class="pc-empty"><i class="bi bi-trophy"></i><div style="font-size:11px;font-weight:600;color:var(--text-2)">Sem prémios para anunciar</div><div style="font-size:9px">Nenhum sorteio nas últimas 24h</div></div>`; return; }
            const cores=['#fbbf24','#c0c0c8','#cd7f32','#00e5ff','#a78bfa'];
            list.innerHTML=premData.map((p,i)=>`<div class="pc" id="pc-${p.id}">
                <div class="pc-promo">${esc(p.promo_nome||'Promoção')} · ${(p.data_sorteio||'').substring(11,16)}</div>
                <div class="pc-ov">
                    <div class="pc-av" style="background:${cores[i%cores.length]}">${(p.nome||'?')[0].toUpperCase()}</div>
                    <div><div class="pc-nome">${esc(p.nome)}</div>${p.telefone?`<div class="pc-tel">${esc(p.telefone)}</div>`:''}</div>
                </div>
                ${(p.bairro||p.cidade)?`<div class="pc-loc"><i class="bi bi-geo-alt"></i> ${esc(p.bairro||p.cidade)}</div>`:''}
                <button class="pc-btn" id="pb-${p.id}" onclick="anunciar(${p.id},${i})">
                    <i class="bi bi-megaphone-fill"></i> Anunciar ao Vivo
                </button>
            </div>`).join('');
        }).catch(()=>{});
}
function anunciar(id,i){
    const p=premData[i]; if(!p) return;
    fetch(API+'?action=premios_anunciar&station_id='+SID,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
    const btn=document.getElementById('pb-'+id);
    if(btn){ btn.innerHTML='<i class="bi bi-check2-circle"></i> Anunciado!'; btn.disabled=true; btn.style.cssText='opacity:.4;cursor:default'; }
    addNotif('🏆',p.promo_nome||'Prémio','Vencedor: '+p.nome+(p.telefone?' · '+p.telefone:'')+(p.bairro?' · '+p.bairro:''));
    setTimeout(()=>{ const c=document.getElementById('pc-'+id); if(c){ c.style.opacity='0'; c.style.transition='opacity .4s'; setTimeout(()=>c.remove(),400); } },5000);
}

/* ANIVERSÁRIOS */
function fetchAniv(){
    fetch(API+'?action=aniversariantes&station_id='+SID)
        .then(r=>r.json()).then(d=>{
            const a=d.aniversariantes||[];
            const bd=document.getElementById('bd-aniv');
            bd.style.display=a.length?'flex':'none'; if(a.length) bd.textContent=a.length;
            const list=document.getElementById('aniv-lista');
            if(!a.length){ list.innerHTML='<div class="empty"><i class="bi bi-balloon-heart"></i><p>Sem aniversários hoje</p></div>'; return; }
            list.innerHTML=a.map(av=>`<div class="ac">
                <div class="ac-av">${(av.nome||'?')[0].toUpperCase()}</div>
                <div style="flex:1"><div class="ac-nome">${esc(av.nome)}</div><div class="ac-sub">🎂 ${av.idade} anos hoje</div>${av.telefone?`<div class="ac-tel">${esc(av.telefone)}</div>`:''}</div>
            </div>`).join('');
        }).catch(()=>{});
}
fetchAniv();

/* ESCALA */
function fetchEscala(){
    fetch(API+'?action=escala&station_id='+SID)
        .then(r=>r.json()).then(d=>{ escData=d; renderEscalaDias(d); renderEscalaSlots(d,d.hoje); }).catch(()=>{});
}
function renderEscalaDias(d){
    const nav=document.getElementById('esc-dias'); if(!nav) return;
    const s=['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'], hoje=d.hoje;
    nav.innerHTML=s.map((l,i)=>{
        const has=(d.semana[i]||[]).length>0, isH=i===hoje;
        const isOn=(escDia===-1&&isH)||escDia===i;
        return `<button class="esc-dbt${isH?' hj':''}${isOn?' on':''}"
            onclick="selEscDia(${i})" ${!has?'style="opacity:.25;pointer-events:none"':''}>
            ${l}${isH?'<span>hoje</span>':''}
        </button>`;
    }).join('');
}
function selEscDia(di){ escDia=di; renderEscalaDias(escData); renderEscalaSlots(escData,di); }
function renderEscalaSlots(d,di){
    const list=document.getElementById('escala-lista'); if(!list) return;
    const slots=d.semana[di]||[], hoje=d.hoje;
    const n=new Date(), hs=n.getHours()*3600+n.getMinutes()*60+n.getSeconds();
    if(!slots.length){ list.innerHTML='<div class="empty"><i class="bi bi-calendar-x"></i><p>Sem programas</p></div>'; return; }
    const diasL=['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
    const isHoje=di===hoje;
    list.innerHTML=`<div style="padding:4px 11px 8px;font-size:9px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:${isHoje?'var(--green)':'var(--text-3)'}">
        ${isHoje?'<span style="animation:livepulse 1.5s infinite;display:inline-block">●</span> ':''} ${diasL[di]}
    </div>`+slots.map(sl=>{
        const hI=sl.hora_inicio?sl.hora_inicio.split(':').reduce((a,b,i)=>a+parseInt(b)*[3600,60,1][i],0):0;
        const hF=sl.hora_fim?sl.hora_fim.split(':').reduce((a,b,i)=>a+parseInt(b)*[3600,60,1][i],0):0;
        const isAt=isHoje&&hs>=hI&&hs<=hF;
        const cores=['#00e5ff','#10b981','#fbbf24','#a78bfa','#ef4444','#f472b6'];
        const cor=cores[Math.abs((sl.programa||'').charCodeAt(0)||0)%cores.length];
        return `<div class="esc-slot${isAt?' atual':''}">
            <div class="esc-bar" style="background:${cor}"></div>
            <div style="flex:1"><div class="esc-prog">${esc(sl.programa)}</div></div>
            <div class="esc-h">${(sl.hora_inicio||'').substring(0,5)}<br><span style="opacity:.4;font-size:7px">até</span><br>${(sl.hora_fim||'').substring(0,5)}</div>
        </div>`;
    }).join('');
}
fetchEscala();

/* PROGRAMAÇÃO */
function fetchProg(){
    fetch(API+'?action=programacao_dia&station_id='+SID)
        .then(r=>r.json()).then(d=>{
            const list=document.getElementById('prog-lista'), h=d.historico||[];
            if(!h.length){ list.innerHTML='<div class="empty"><i class="bi bi-music-note-list"></i><p>Sem histórico</p></div>'; return; }
            list.innerHTML=h.map((s,i)=>`<div class="pi">
                <div class="pi-n">${i+1}</div>
                <div style="flex:1;min-width:0"><div class="pi-s">${esc(s.titulo)}</div><div class="pi-a">${esc(s.artista)}</div></div>
                <div class="pi-t">${s.tocou_em?new Date(s.tocou_em*1000).toLocaleTimeString('pt',{hour:'2-digit',minute:'2-digit'}):''}</div>
            </div>`).join('');
        }).catch(()=>{});
}

/* NOTIFICAÇÕES */
function fetchNotifs(){
    fetch(API+'?action=notificacoes&station_id='+SID)
        .then(r=>r.json()).then(d=>{
            (d.notificacoes||[]).forEach(n=>{
                if(seenNotifs.has(n.id)) return;
                seenNotifs.add(n.id);
                addNotif(n.tipo==='sorteio'?'🏆':'📢',n.titulo,n.mensagem,n.id);
                fetch(API+'?action=marcar_notificacao_lida&station_id='+SID,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:n.id})}).catch(()=>{});
            });
        }).catch(()=>{});
}
setInterval(fetchNotifs,15000); fetchNotifs();

function addNotif(ico,titulo,msg,id=null){
    const z=document.getElementById('notifs-zone');
    const div=document.createElement('div');
    div.className='notif'; if(id) div.setAttribute('data-nid',id);
    div.innerHTML=`<div class="notif-ic">${ico}</div>
        <div class="notif-body"><div class="notif-t">${esc(titulo)}</div><div class="notif-m">${esc(msg)}</div></div>
        <button class="notif-x" onclick="this.parentElement.remove()">✕</button>`;
    z.prepend(div);
    setTimeout(()=>{ if(div.parentElement) div.remove(); },30000);
}

function esc(s){ if(!s)return''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

setTimeout(()=>{ const f=document.querySelector('.fc'); if(f&&!card) f.click(); },2500);
