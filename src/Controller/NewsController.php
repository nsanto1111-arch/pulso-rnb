<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin\Controller;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

class NewsController
{
    private Connection $db;
    public function __construct(Connection $db) { $this->db = $db; }

    private function categoriaLabel(string $c): string {
        return ['nacional'=>'Nacional','internacional'=>'Internacional','desporto'=>'Desporto',
                'economia'=>'Economia','cultura'=>'Cultura','entretenimento'=>'Entretenimento',
                'politica'=>'Política','saude'=>'Saúde','tecnologia'=>'Tecnologia','outro'=>'Outro'][$c] ?? ucfirst($c);
    }

    private function categoriaCor(string $c): string {
        return ['nacional'=>'#00e5ff','internacional'=>'#3b82f6','desporto'=>'#10b981',
                'economia'=>'#f59e0b','cultura'=>'#a78bfa','entretenimento'=>'#f472b6',
                'politica'=>'#ef4444','saude'=>'#06b6d4','tecnologia'=>'#8b5cf6','outro'=>'#71717a'][$c] ?? '#71717a';
    }

    private function prioridadeBadge(string $p): string {
        return match($p) {
            'urgente' => '<span class="badge br">🔴 URGENTE</span>',
            'alta'    => '<span class="badge by">⚡ Alta</span>',
            'normal'  => '<span class="badge bd">Normal</span>',
            'baixa'   => '<span class="badge bd" style="opacity:.6">Baixa</span>',
            default   => '<span class="badge bd">'.ucfirst($p).'</span>',
        };
    }

    private function layout(string $titulo, string $body, int $sid, string $active): string
    {
        // Nav global RNB
        $_rnb_sid = $sid; $_rnb_atual = 'news';
        ob_start();
        @require dirname(__DIR__, 2) . '/public/rnb-nav.php';
        $rnbNav = ob_get_clean();

        // Stats para sidebar
        try {
            $nAtivas   = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_noticias WHERE station_id=? AND ativo=1 AND (data_expiracao IS NULL OR data_expiracao>NOW())", [$sid]);
            $nUrgentes = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_noticias WHERE station_id=? AND ativo=1 AND prioridade='urgente'", [$sid]);
            $nHoje     = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_noticias WHERE station_id=? AND DATE(created_at)=CURDATE()", [$sid]);
        } catch(\Exception $e) { $nAtivas=$nUrgentes=$nHoje=0; }

        $nav = [
            'index'  => ['newspaper',    'Newsroom'],
            'tabua'  => ['broadcast',    'Tábua'],
            'agenda' => ['calendar-event','Agenda'],
            'arquivo'=> ['archive',      'Arquivo'],
        ];
        $navHtml = '';
        foreach($nav as $k=>[$ico,$lbl]) {
            $cls = $k===$active?'on':'';
            $url = $k==='index' ? "/public/news/{$sid}" : "/public/news/{$sid}/{$k}";
            $navHtml .= "<a href='{$url}' class='ni {$cls}'><i class='bi bi-{$ico}'></i><span>{$lbl}</span></a>";
        }

        return <<<HTML
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>RNB News — {$titulo}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg0:#050510;--bg1:#0f0f1f;--bg2:#1a1a2e;--bg3:#252538;
    --ac:#e11d48;--ac2:#be123c;
    --blue:#3b82f6;--green:#10b981;--gold:#f59e0b;--pu:#8b5cf6;--cy:#00e5ff;
    --t1:#fff;--t2:#a1a1aa;--t3:#71717a;
    --br:rgba(255,255,255,.08);--br2:rgba(255,255,255,.14);
    --ff:'Inter',-apple-system,sans-serif;
    --tr:all .25s cubic-bezier(.4,0,.2,1);
}
html,body{min-height:100vh;font-family:var(--ff);background:var(--bg0);color:var(--t1);font-size:13px;-webkit-font-smoothing:antialiased}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(circle at 15% 40%,rgba(225,29,72,.06),transparent 50%),radial-gradient(circle at 85% 70%,rgba(59,130,246,.04),transparent 50%);pointer-events:none;z-index:0}
.wrap{display:grid;grid-template-columns:210px 1fr;min-height:100vh;position:relative;z-index:1}
/* SIDEBAR */
.sb{background:rgba(15,15,31,.95);border-right:1px solid var(--br);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto}
.sb::-webkit-scrollbar{width:3px}.sb::-webkit-scrollbar-thumb{background:var(--bg3)}
.sb-hd{padding:18px 16px 12px;border-bottom:1px solid var(--br)}
.sb-logo{display:flex;align-items:center;gap:9px;margin-bottom:3px}
.sb-ico{width:34px;height:34px;background:linear-gradient(135deg,var(--ac),var(--ac2));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 0 20px rgba(225,29,72,.25)}
.sb-name{font-size:14px;font-weight:900;background:linear-gradient(135deg,var(--ac),#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.sb-sub{font-size:8px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--t3);margin-left:43px}
.sb-stats{display:grid;grid-template-columns:1fr 1fr;gap:5px;padding:10px 12px;border-bottom:1px solid var(--br)}
.sb-st{background:rgba(255,255,255,.03);border:1px solid var(--br);border-radius:7px;padding:7px;text-align:center}
.sb-sv{font-size:17px;font-weight:800;color:var(--ac)}
.sb-sl{font-size:8px;color:var(--t3);text-transform:uppercase;letter-spacing:.4px;margin-top:1px}
.nav{padding:8px;flex:1}
.ni{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:9px;text-decoration:none;color:var(--t2);font-size:12px;font-weight:600;transition:var(--tr);margin-bottom:2px;white-space:nowrap}
.ni:hover{background:rgba(255,255,255,.05);color:var(--t1);text-decoration:none}
.ni.on{background:linear-gradient(135deg,rgba(225,29,72,.14),rgba(190,18,60,.08));color:var(--ac);border:1px solid rgba(225,29,72,.2)}
.ni i{font-size:15px;flex-shrink:0}
.sb-ft{padding:10px 12px;border-top:1px solid var(--br)}
.sb-bk{display:flex;align-items:center;gap:7px;padding:7px 11px;border-radius:8px;text-decoration:none;color:var(--t3);font-size:11px;font-weight:600;transition:var(--tr)}
.sb-bk:hover{background:rgba(255,255,255,.04);color:var(--t2);text-decoration:none}
/* MAIN */
.main{display:flex;flex-direction:column;min-height:100vh}
.tbar{padding:14px 24px;border-bottom:1px solid var(--br);background:rgba(15,15,31,.7);backdrop-filter:blur(20px);position:sticky;top:0;z-index:100;display:flex;align-items:center;justify-content:space-between;gap:16px}
.pg-t{font-size:20px;font-weight:900;color:var(--t1)}
.pg-s{font-size:11px;color:var(--t3);margin-top:2px}
.tbar-acts{display:flex;gap:7px;align-items:center}
.cnt{padding:22px 24px;flex:1}
/* CARDS */
.card{background:linear-gradient(135deg,rgba(26,26,46,.95),rgba(21,21,32,.95));border:1px solid var(--br);border-radius:14px;padding:18px;position:relative;overflow:hidden;transition:var(--tr)}
.card:hover{border-color:var(--br2)}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;opacity:0;transition:var(--tr)}
.card.rd::before{background:linear-gradient(90deg,var(--ac),var(--ac2))}
.card.bl::before{background:linear-gradient(90deg,var(--blue),var(--pu))}
.card:hover::before{opacity:1}
.ct{font-size:12px;font-weight:700;color:var(--t1);margin-bottom:12px;display:flex;align-items:center;gap:7px}
.ct i{color:var(--ac)}
.ct a{margin-left:auto;font-size:10px;font-weight:600;color:var(--t3);text-decoration:none;padding:3px 8px;border-radius:5px;border:1px solid var(--br)}
.ct a:hover{color:var(--t2);background:rgba(255,255,255,.04);text-decoration:none}
/* NOTÍCIA CARD */
.nc{background:linear-gradient(135deg,rgba(26,26,46,.9),rgba(21,21,32,.9));border:1px solid var(--br);border-radius:12px;padding:16px;margin-bottom:10px;border-left:3px solid var(--br);transition:var(--tr);position:relative;overflow:hidden}
.nc:hover{border-color:var(--br2);transform:translateX(3px)}
.nc.urgente{border-left-color:var(--ac);background:linear-gradient(135deg,rgba(225,29,72,.06),rgba(21,21,32,.9))}
.nc.alta{border-left-color:var(--gold)}
.nc.normal{border-left-color:rgba(0,229,255,.3)}
.nc::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,var(--ac),transparent);opacity:0;transition:var(--tr)}
.nc:hover::before{opacity:.4}
.nc-hd{display:flex;align-items:flex-start;gap:10px;margin-bottom:8px}
.nc-cat{font-size:8px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;padding:3px 8px;border-radius:10px;flex-shrink:0;margin-top:2px}
.nc-titulo{font-size:14px;font-weight:800;color:var(--t1);line-height:1.4;flex:1}
.nc-resumo{font-size:12px;color:var(--t2);line-height:1.6;margin-bottom:10px}
.nc-corpo{font-size:13px;color:var(--t1);line-height:1.75;white-space:pre-wrap;display:none;padding:12px;background:rgba(0,229,255,.03);border:1px solid rgba(0,229,255,.08);border-radius:8px;margin-bottom:10px}
.nc-corpo.open{display:block}
.nc-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.nc-autor{font-size:10px;font-weight:600;color:var(--t3)}
.nc-time{font-size:10px;color:var(--t3)}
.nc-leit{font-size:10px;font-weight:700;color:var(--pu)}
.nc-acts{display:flex;gap:4px;margin-left:auto}
/* BADGES */
.badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:700}
.bg{background:rgba(16,185,129,.1);color:var(--green);border:1px solid rgba(16,185,129,.2)}
.by{background:rgba(245,158,11,.1);color:var(--gold);border:1px solid rgba(245,158,11,.2)}
.bb{background:rgba(59,130,246,.1);color:var(--blue);border:1px solid rgba(59,130,246,.2)}
.br{background:rgba(225,29,72,.1);color:var(--ac);border:1px solid rgba(225,29,72,.2)}
.bd{background:rgba(255,255,255,.06);color:var(--t2);border:1px solid var(--br)}
/* BTNS */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font:700 12px var(--ff);cursor:pointer;transition:var(--tr);border:none;text-decoration:none;white-space:nowrap}
.btn:hover{transform:translateY(-1px);text-decoration:none}
.btn-p{background:linear-gradient(135deg,var(--ac),var(--ac2));color:#fff;box-shadow:0 0 20px rgba(225,29,72,.2)}
.btn-p:hover{box-shadow:0 0 28px rgba(225,29,72,.35)}
.btn-s{background:rgba(255,255,255,.05);color:var(--t2);border:1px solid var(--br)}
.btn-s:hover{background:rgba(255,255,255,.08);color:var(--t1)}
.btn-sm{padding:4px 10px;font-size:10px}
.btn-lida{background:linear-gradient(135deg,#059669,#10b981);color:#fff}
/* MODAL */
.mbg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);backdrop-filter:blur(14px);z-index:1000;align-items:center;justify-content:center;padding:16px}
.mbg.open{display:flex}
.mbox{background:linear-gradient(135deg,rgba(26,26,46,.99),rgba(15,15,31,.99));border:1px solid var(--br2);border-radius:18px;padding:24px;width:100%;max-width:680px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.6)}
.mt{font-size:16px;font-weight:800;display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.mx{background:rgba(255,255,255,.06);border:1px solid var(--br);color:var(--t2);width:28px;height:28px;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px}
/* FORM */
.fg2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fg{margin-bottom:12px}
.fl{display:block;font-size:9px;font-weight:700;color:var(--t2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
.fi,.fs,.fta{width:100%;padding:9px 12px;background:rgba(255,255,255,.04);border:1px solid var(--br);border-radius:8px;color:var(--t1);font:13px var(--ff);outline:none;transition:var(--tr);color-scheme:dark}
.fi:focus,.fs:focus,.fta:focus{border-color:rgba(225,29,72,.4);background:rgba(255,255,255,.06)}
.fi::placeholder,.fta::placeholder{color:var(--t3)}
.fta{resize:vertical;min-height:120px;line-height:1.7}
.ff2{display:flex;gap:8px;justify-content:flex-end;margin-top:18px;padding-top:14px;border-top:1px solid var(--br)}
/* FILTROS */
.filtros{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px}
.fi-btn{padding:5px 12px;border-radius:20px;border:1px solid var(--br);background:none;color:var(--t3);font:600 10px var(--ff);cursor:pointer;transition:var(--tr)}
.fi-btn:hover{background:rgba(255,255,255,.05);color:var(--t2)}
.fi-btn.on{background:linear-gradient(135deg,rgba(225,29,72,.12),rgba(190,18,60,.08));border-color:rgba(225,29,72,.25);color:var(--ac)}
/* URGENTES STRIP */
.urg-strip{background:linear-gradient(135deg,rgba(225,29,72,.08),rgba(190,18,60,.04));border:1px solid rgba(225,29,72,.2);border-radius:12px;padding:14px 16px;margin-bottom:18px;display:none}
.urg-strip.show{display:block}
.urg-strip-hd{font-size:10px;font-weight:700;color:var(--ac);letter-spacing:1px;text-transform:uppercase;margin-bottom:10px;display:flex;align-items:center;gap:6px}
.urg-items{display:flex;flex-direction:column;gap:6px}
.urg-item{display:flex;align-items:center;gap:10px;padding:8px 12px;background:rgba(225,29,72,.06);border-radius:8px;cursor:pointer}
.urg-item:hover{background:rgba(225,29,72,.12)}
.urg-t{font-size:12px;font-weight:700;color:var(--t1);flex:1}
/* TÁBUA */
.tabua-item{background:rgba(255,255,255,.03);border:1px solid var(--br);border-radius:10px;padding:14px;margin-bottom:8px;display:flex;align-items:flex-start;gap:12px;transition:var(--tr)}
.tabua-item:hover{border-color:var(--br2)}
.tabua-num{width:28px;height:28px;border-radius:7px;background:linear-gradient(135deg,var(--ac),var(--ac2));display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0}
.tabua-titulo{font-size:13px;font-weight:700;color:var(--t1);margin-bottom:3px}
.tabua-meta{font-size:10px;color:var(--t3)}
/* AGENDA */
.ag-item{display:flex;align-items:flex-start;gap:12px;padding:12px 14px;background:rgba(255,255,255,.03);border:1px solid var(--br);border-radius:10px;margin-bottom:7px;transition:var(--tr)}
.ag-item:hover{border-color:var(--br2)}
.ag-data{width:46px;text-align:center;flex-shrink:0}
.ag-dia{font-size:20px;font-weight:900;color:var(--t1);line-height:1}
.ag-mes{font-size:9px;font-weight:700;text-transform:uppercase;color:var(--t3);letter-spacing:.5px}
.ag-titulo{font-size:13px;font-weight:700;color:var(--t1);margin-bottom:3px}
.ag-meta{font-size:10px;color:var(--t3)}
/* GRID */
.g2{display:grid;grid-template-columns:2fr 1fr;gap:18px}
.es{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem;color:var(--t3);text-align:center;gap:10px}
.es i{font-size:40px;opacity:.1}.es h3{font-size:16px;font-weight:700;color:var(--t2)}
@media(max-width:900px){.wrap{grid-template-columns:1fr}.sb{display:none}.g2{grid-template-columns:1fr}}
</style>
</head><body>
{$rnbNav}
<div class="wrap">
<div class="sb">
  <div class="sb-hd">
    <div class="sb-logo"><div class="sb-ico"><i class="bi bi-newspaper" style="color:#fff;font-size:15px"></i></div><div class="sb-name">RNB News</div></div>
    <div class="sb-sub">Rádio New Band</div>
  </div>
  <div class="sb-stats">
    <div class="sb-st"><div class="sb-sv">{$nAtivas}</div><div class="sb-sl">Activas</div></div>
    <div class="sb-st"><div class="sb-sv" style="color:var(--ac)">{$nUrgentes}</div><div class="sb-sl">Urgentes</div></div>
    <div class="sb-st" style="grid-column:span 2"><div class="sb-sv">{$nHoje}</div><div class="sb-sl">Criadas hoje</div></div>
  </div>
  <nav class="nav">{$navHtml}</nav>
  <div class="sb-ft">
    <a href="/public/dashboard/{$sid}" class="sb-bk"><i class="bi bi-arrow-left-circle"></i>Dashboard</a>
  </div>
</div>
<div class="main">{$body}</div>
</div>
</body></html>
HTML;
    }

    /* ─── NEWSROOM PRINCIPAL ─────────────────────────────── */
    public function indexAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $qp  = $request->getQueryParams();
        $cat = $qp['cat'] ?? '';
        $prio = $qp['prio'] ?? '';
        $busca = trim($qp['busca'] ?? '');

        $where = "WHERE station_id=? AND ativo=1 AND (data_expiracao IS NULL OR data_expiracao>NOW())";
        $binds = [$sid];
        if ($cat)   { $where .= " AND categoria=?"; $binds[] = $cat; }
        if ($prio)  { $where .= " AND prioridade=?"; $binds[] = $prio; }
        if ($busca) { $where .= " AND (titulo LIKE ? OR conteudo LIKE ?)"; $binds[] = "%{$busca}%"; $binds[] = "%{$busca}%"; }

        $noticias = $this->db->fetchAllAssociative(
            "SELECT * FROM rnb_noticias {$where} ORDER BY FIELD(prioridade,'urgente','alta','normal','baixa'), created_at DESC",
            $binds
        );

        // Urgentes separadas
        $urgentes = array_filter($noticias, fn($n) => $n['prioridade'] === 'urgente');

        // Categorias disponíveis
        $cats = $this->db->fetchAllAssociative(
            "SELECT categoria, COUNT(*) as n FROM rnb_noticias WHERE station_id=? AND ativo=1 GROUP BY categoria ORDER BY n DESC",
            [$sid]
        );

        $urgStrip = '';
        if ($urgentes) {
            $urgItems = '';
            foreach($urgentes as $u) {
                $urgItems .= "<div class='urg-item' onclick='toggleCorpo(\"urg-{$u['id']}\")'>
                    <i class='bi bi-exclamation-triangle-fill' style='color:var(--ac);flex-shrink:0'></i>
                    <span class='urg-t'>".htmlspecialchars($u['titulo'])."</span>
                    <button class='btn btn-lida btn-sm' onclick='event.stopPropagation();marcarLida({$u['id']},this)'>
                        <i class='bi bi-mic-fill'></i> Lida
                    </button>
                </div>";
            }
            $urgStrip = "<div class='urg-strip show'>
                <div class='urg-strip-hd'><i class='bi bi-exclamation-triangle-fill'></i>".count($urgentes)." NOTÍCIAS URGENTES</div>
                <div class='urg-items'>{$urgItems}</div>
            </div>";
        }

        $filtros = "<div class='filtros'>
            <button class='fi-btn".(!$cat&&!$prio?' on':'')."' onclick=\"location.href='/public/news/{$sid}'\">Todas</button>
            <button class='fi-btn".($prio==='urgente'?' on':'')."' onclick=\"location.href='?prio=urgente'\">🔴 Urgentes</button>
            <button class='fi-btn".($prio==='alta'?' on':'')."' onclick=\"location.href='?prio=alta'\">⚡ Alta</button>";
        foreach ($cats as $c) {
            $cls = $cat===$c['categoria']?' on':'';
            $filtros .= "<button class='fi-btn{$cls}' onclick=\"location.href='?cat={$c['categoria']}'\">".
                $this->categoriaLabel($c['categoria'])." <span style='opacity:.6'>({$c['n']})</span></button>";
        }
        $filtros .= "</div>";

        $cards = '';
        foreach ($noticias as $n) {
            $cor = $this->categoriaCor($n['categoria']);
            $cards .= "<div class='nc {$n['prioridade']}' id='nc-{$n['id']}'>
                <div class='nc-hd'>
                    <span class='nc-cat' style='background:{$cor}18;color:{$cor};border:1px solid {$cor}30'>".
                        $this->categoriaLabel($n['categoria'])."</span>
                    <div class='nc-titulo'>".htmlspecialchars($n['titulo'])."</div>
                    ".$this->prioridadeBadge($n['prioridade'])."
                </div>
                ".($n['resumo'] ? "<div class='nc-resumo'>".htmlspecialchars($n['resumo'])."</div>" : '')."
                <div class='nc-corpo' id='corpo-{$n['id']}'>".htmlspecialchars($n['conteudo'])."</div>
                <div class='nc-meta'>
                    <span class='nc-autor'><i class='bi bi-person' style='font-size:9px'></i> ".htmlspecialchars($n['autor']??'Redacção')."</span>
                    <span class='nc-time'><i class='bi bi-clock' style='font-size:9px'></i> ".date('d/m H:i',strtotime($n['created_at']))."</span>
                    <span class='nc-leit'><i class='bi bi-eye' style='font-size:9px'></i> ".($n['total_leituras']>0?$n['total_leituras'].'×':'Não lida')."</span>
                    ".($n['programa'] ? "<span style='font-size:10px;color:var(--gold)'><i class='bi bi-broadcast' style='font-size:9px'></i> ".htmlspecialchars($n['programa'])."</span>" : '')."
                    <div class='nc-acts'>
                        <button class='btn btn-s btn-sm' onclick='toggleCorpo({$n['id']})' id='btn-toggle-{$n['id']}'>
                            <i class='bi bi-chevron-down'></i> Ler
                        </button>
                        <button class='btn btn-lida btn-sm' onclick='marcarLida({$n['id']},this)'>
                            <i class='bi bi-mic-fill'></i> Lida no ar
                        </button>
                        <button class='btn btn-s btn-sm' style='color:var(--ac)' onclick='apagarNoticia({$n['id']})'>
                            <i class='bi bi-trash'></i>
                        </button>
                    </div>
                </div>
            </div>";
        }

        if (!$cards) $cards = "<div class='es'><i class='bi bi-newspaper'></i><h3>Sem notícias</h3><p>Cria a primeira notícia para a newsroom.</p></div>";

        $html = $this->layout('Newsroom', <<<HTML
<div class="tbar">
    <div><div class="pg-t"><i class="bi bi-newspaper" style="color:var(--ac)"></i> Newsroom</div><div class="pg-s">Centro de notícias em tempo real</div></div>
    <div class="tbar-acts">
        <form method="GET" style="display:flex;gap:6px">
            <input type="text" name="busca" value="{$busca}" placeholder="Pesquisar notícias..." class="fi" style="width:180px;height:34px">
        </form>
        <button class="btn btn-p" onclick="document.getElementById('m-nova').classList.add('open')">
            <i class="bi bi-plus-lg"></i> Nova Notícia
        </button>
    </div>
</div>
<div class="cnt">
    {$urgStrip}
    {$filtros}
    {$cards}
</div>

<!-- MODAL NOVA NOTÍCIA -->
<div class="mbg" id="m-nova">
<div class="mbox">
    <div class="mt">Nova Notícia <button class="mx" onclick="closeM('m-nova')">✕</button></div>
    <form method="POST" action="/public/news/{$sid}/salvar">
        <div class="fg2">
            <div class="fg" style="grid-column:span 2">
                <label class="fl">Título *</label>
                <input type="text" name="titulo" class="fi" required placeholder="Título da notícia">
            </div>
            <div class="fg">
                <label class="fl">Categoria</label>
                <select name="categoria" class="fs">
                    <option value="nacional">Nacional</option>
                    <option value="internacional">Internacional</option>
                    <option value="desporto">Desporto</option>
                    <option value="economia">Economia</option>
                    <option value="cultura">Cultura</option>
                    <option value="entretenimento">Entretenimento</option>
                    <option value="politica">Política</option>
                    <option value="saude">Saúde</option>
                    <option value="tecnologia">Tecnologia</option>
                </select>
            </div>
            <div class="fg">
                <label class="fl">Prioridade</label>
                <select name="prioridade" class="fs">
                    <option value="normal">Normal</option>
                    <option value="alta">Alta</option>
                    <option value="urgente">🔴 Urgente</option>
                    <option value="baixa">Baixa</option>
                </select>
            </div>
            <div class="fg">
                <label class="fl">Tipo</label>
                <select name="tipo" class="fs">
                    <option value="noticia">Notícia</option>
                    <option value="flash">Flash</option>
                    <option value="comentario">Comentário</option>
                    <option value="reportagem">Reportagem</option>
                </select>
            </div>
            <div class="fg">
                <label class="fl">Programa</label>
                <input type="text" name="programa" class="fi" placeholder="Ex: Manhã RNB">
            </div>
            <div class="fg" style="grid-column:span 2">
                <label class="fl">Resumo (para antena)</label>
                <input type="text" name="resumo" class="fi" placeholder="Versão curta para o locutor ler rapidamente...">
            </div>
            <div class="fg" style="grid-column:span 2">
                <label class="fl">Conteúdo completo *</label>
                <textarea name="conteudo" class="fta" required placeholder="Texto completo da notícia..." style="min-height:160px"></textarea>
            </div>
            <div class="fg">
                <label class="fl">Fonte</label>
                <input type="text" name="fonte" class="fi" placeholder="Ex: Angop, Reuters...">
            </div>
            <div class="fg">
                <label class="fl">Expiração</label>
                <input type="datetime-local" name="data_expiracao" class="fi">
            </div>
            <div class="fg">
                <label class="fl">Máx. Leituras (0=ilimitado)</label>
                <input type="number" name="max_leituras" class="fi" value="0" min="0">
            </div>
            <div class="fg">
                <label class="fl">Autor</label>
                <input type="text" name="autor" class="fi" placeholder="Nome do jornalista" value="Redacção RNB">
            </div>
        </div>
        <div class="ff2">
            <button type="button" class="btn btn-s" onclick="closeM('m-nova')">Cancelar</button>
            <button type="submit" class="btn btn-p"><i class="bi bi-check-lg"></i> Publicar</button>
        </div>
    </form>
</div>
</div>

<script>
function closeM(id){ document.getElementById(id).classList.remove('open'); }
document.getElementById('m-nova').addEventListener('click',e=>{ if(e.target===document.getElementById('m-nova')) closeM('m-nova'); });

function toggleCorpo(id){
    const c=document.getElementById('corpo-'+id);
    const b=document.getElementById('btn-toggle-'+id);
    if(!c) return;
    c.classList.toggle('open');
    if(b) b.innerHTML=c.classList.contains('open')?'<i class="bi bi-chevron-up"></i> Fechar':'<i class="bi bi-chevron-down"></i> Ler';
}

function marcarLida(id, btn){
    if(btn){ btn.disabled=true; btn.innerHTML='...'; }
    fetch('/public/news/{$sid}/'+id+'/marcar-lida',{method:'POST'})
        .then(r=>r.json()).then(d=>{
            if(d.status==='ok'){
                const nc=document.getElementById('nc-'+id);
                if(nc){ nc.style.opacity='.4'; nc.style.transition='opacity .5s'; }
                showToast('✅ Notícia marcada como lida','success');
                setTimeout(()=>{ if(nc) nc.remove(); },1500);
            }
        });
}

function apagarNoticia(id){
    if(!confirm('Apagar esta notícia?')) return;
    fetch('/public/news/{$sid}/'+id+'/apagar',{method:'POST'})
        .then(()=>{ const nc=document.getElementById('nc-'+id); if(nc) nc.remove(); });
}

function showToast(msg,type){
    const t=document.createElement('div');
    const c={'success':'var(--green)','error':'var(--ac)','info':'var(--blue)'};
    t.style.cssText='position:fixed;bottom:80px;right:20px;padding:11px 16px;border-radius:9px;font-size:12px;font-weight:700;color:#fff;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.4);background:'+(c[type]||'var(--ac)');
    t.textContent=msg; document.body.appendChild(t);
    setTimeout(()=>t.remove(),3500);
}
</script>
HTML, $sid, 'index');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    /* ─── SALVAR ─────────────────────────────────────────── */
    public function salvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $b   = $request->getParsedBody();
        $this->db->insert('rnb_noticias', [
            'station_id'      => $sid,
            'titulo'          => trim($b['titulo'] ?? ''),
            'conteudo'        => trim($b['conteudo'] ?? ''),
            'resumo'          => trim($b['resumo'] ?? ''),
            'categoria'       => $b['categoria'] ?? 'nacional',
            'prioridade'      => $b['prioridade'] ?? 'normal',
            'tipo'            => $b['tipo'] ?? 'noticia',
            'programa'        => trim($b['programa'] ?? ''),
            'fonte'           => trim($b['fonte'] ?? ''),
            'autor'           => trim($b['autor'] ?? 'Redacção RNB'),
            'max_leituras'    => (int)($b['max_leituras'] ?? 0),
            'data_expiracao'  => $b['data_expiracao'] ? date('Y-m-d H:i:s', strtotime($b['data_expiracao'])) : null,
            'ativo'           => 1,
            'created_at'      => date('Y-m-d H:i:s'),
            'updated_at'      => date('Y-m-d H:i:s'),
        ]);
        return $response->withHeader('Location', "/public/news/{$sid}")->withStatus(302);
    }

    /* ─── MARCAR LIDA ────────────────────────────────────── */
    public function marcarLidaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $id  = (int)$params['id'];
        $b   = $request->getParsedBody();

        $noticia = $this->db->fetchAssociative("SELECT * FROM rnb_noticias WHERE id=? AND station_id=?", [$id, $sid]);
        if (!$noticia) {
            $response->getBody()->write('{"status":"error"}');
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Registar leitura
        $this->db->insert('rnb_noticias_leituras', [
            'noticia_id' => $id,
            'station_id' => $sid,
            'locutor'    => $b['locutor'] ?? 'Locutor',
            'programa'   => $b['programa'] ?? '',
            'lida_em'    => date('Y-m-d H:i:s'),
        ]);

        // Actualizar contador
        $total = $noticia['total_leituras'] + 1;
        $upd = ['total_leituras' => $total, 'updated_at' => date('Y-m-d H:i:s')];

        // Desactivar se atingiu max_leituras
        if ($noticia['max_leituras'] > 0 && $total >= $noticia['max_leituras']) {
            $upd['ativo'] = 0;
        }

        $this->db->update('rnb_noticias', $upd, ['id' => $id]);
        $response->getBody()->write('{"status":"ok","total_leituras":'.$total.'}');
        return $response->withHeader('Content-Type', 'application/json');
    }

    /* ─── APAGAR ─────────────────────────────────────────── */
    public function apagarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $id  = (int)$params['id'];
        $this->db->update('rnb_noticias', ['ativo' => 0, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id, 'station_id' => $sid]);
        $response->getBody()->write('{"status":"ok"}');
        return $response->withHeader('Content-Type', 'application/json');
    }

    /* ─── TÁBUA DE IRRADIAÇÃO ────────────────────────────── */
    public function tabuaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $agora = date('H:i:s');
        $diaSemana = strtolower(['domingo','segunda','terca','quarta','quinta','sexta','sabado'][date('w')]);

        // Notícias activas ordenadas por programa + prioridade
        $noticias = $this->db->fetchAllAssociative(
            "SELECT * FROM rnb_noticias WHERE station_id=? AND ativo=1 AND (data_expiracao IS NULL OR data_expiracao>NOW())
             ORDER BY FIELD(prioridade,'urgente','alta','normal','baixa'), programa ASC, created_at DESC",
            [$sid]
        );

        // Agrupar por programa
        $porPrograma = [];
        foreach ($noticias as $n) {
            $prog = $n['programa'] ?: 'Sem programa definido';
            $porPrograma[$prog][] = $n;
        }

        $blocos = '';
        $num = 0;
        foreach ($porPrograma as $prog => $items) {
            $rows = '';
            foreach ($items as $n) {
                $num++;
                $cor = $this->categoriaCor($n['categoria']);
                $rows .= "<div class='tabua-item' id='ti-{$n['id']}'>
                    <div class='tabua-num'>{$num}</div>
                    <div style='flex:1'>
                        <div class='tabua-titulo'>".htmlspecialchars($n['titulo'])."</div>
                        <div class='tabua-meta'>
                            <span style='color:{$cor}'>".$this->categoriaLabel($n['categoria'])."</span>
                            · ".$this->prioridadeBadge($n['prioridade'])."
                            · <span style='color:var(--pu)'>".$n['total_leituras']."× lida</span>
                            ".($n['resumo'] ? "· <span style='color:var(--t3);font-style:italic'>".htmlspecialchars(substr($n['resumo'],0,60))."...</span>" : '')."
                        </div>
                    </div>
                    <button class='btn btn-lida btn-sm' onclick='marcarLida({$n['id']},this)'>
                        <i class='bi bi-mic-fill'></i> Lida
                    </button>
                </div>";
            }
            $blocos .= "<div class='card rd mb-16' style='margin-bottom:16px'>
                <div class='ct'><i class='bi bi-broadcast'></i>".htmlspecialchars($prog)."
                    <span style='margin-left:auto;font-size:10px;color:var(--t3)'>".count($items)." notícias</span>
                </div>
                {$rows}
            </div>";
        }

        if (!$blocos) $blocos = "<div class='es'><i class='bi bi-broadcast'></i><h3>Sem notícias na tábua</h3><p>Adiciona notícias na Newsroom associando-as a um programa.</p></div>";

        $html = $this->layout('Tábua de Irradiação', <<<HTML
<div class="tbar">
    <div><div class="pg-t"><i class="bi bi-broadcast" style="color:var(--ac)"></i> Tábua de Irradiação</div>
    <div class="pg-s">Notícias organizadas por programa · {$num} notícias activas</div></div>
    <div class="tbar-acts">
        <a href="/public/news/{$sid}" class="btn btn-s"><i class="bi bi-arrow-left"></i> Newsroom</a>
        <button class="btn btn-p" onclick="document.getElementById('m-nova').classList.add('open')"><i class="bi bi-plus-lg"></i> Nova</button>
    </div>
</div>
<div class="cnt">{$blocos}</div>
<script>
function marcarLida(id,btn){
    if(btn){btn.disabled=true;btn.innerHTML='...';}
    fetch('/public/news/{$sid}/'+id+'/marcar-lida',{method:'POST'})
        .then(r=>r.json()).then(d=>{
            if(d.status==='ok'){
                const ti=document.getElementById('ti-'+id);
                if(ti){ti.style.opacity='.3';ti.style.transition='opacity .5s';setTimeout(()=>ti.remove(),1500);}
            }
        });
}
</script>
HTML, $sid, 'tabua');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    /* ─── AGENDA ─────────────────────────────────────────── */
    public function agendaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $hoje = date('Y-m-d');

        $eventos = $this->db->fetchAllAssociative(
            "SELECT * FROM rnb_noticias_agenda WHERE station_id=? AND data_evento>=? ORDER BY data_evento ASC, hora_evento ASC",
            [$sid, $hoje]
        );

        $meses = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun',
                  '07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];

        $rows = '';
        foreach ($eventos as $e) {
            $d = explode('-', $e['data_evento']);
            $mes = $meses[$d[1]] ?? '';
            $isHoje = $e['data_evento'] === $hoje;
            $relCor = ['alta'=>'var(--ac)','media'=>'var(--gold)','baixa'=>'var(--t3)'][$e['relevancia']] ?? 'var(--t3)';
            $rows .= "<div class='ag-item' style='".($isHoje?'border-color:rgba(225,29,72,.3);background:rgba(225,29,72,.04)':'')."'>
                <div class='ag-data'>
                    <div class='ag-dia'>{$d[2]}</div>
                    <div class='ag-mes'>{$mes}</div>
                </div>
                <div style='flex:1'>
                    <div class='ag-titulo'>".($isHoje?'<span style="color:var(--ac);font-size:10px;font-weight:700">HOJE · </span>':'').htmlspecialchars($e['titulo'])."</div>
                    <div class='ag-meta'>
                        ".($e['hora_evento'] ? "<i class='bi bi-clock' style='font-size:9px'></i> ".substr($e['hora_evento'],0,5)." · " : "")."
                        ".($e['local'] ? "<i class='bi bi-geo-alt' style='font-size:9px'></i> ".htmlspecialchars($e['local'])." · " : "")."
                        <span style='color:{$relCor};font-weight:700'>".ucfirst($e['relevancia'])."</span>
                    </div>
                </div>
                <button class='btn btn-p btn-sm' onclick='criarNoticiaDaAgenda(".json_encode(htmlspecialchars($e['titulo'])).",".$e['id'].")'>
                    <i class='bi bi-newspaper'></i> Criar notícia
                </button>
            </div>";
        }

        if (!$rows) $rows = "<div class='es'><i class='bi bi-calendar-event'></i><h3>Agenda vazia</h3><p>Adiciona eventos à agenda editorial.</p></div>";

        $html = $this->layout('Agenda', <<<HTML
<div class="tbar">
    <div><div class="pg-t"><i class="bi bi-calendar-event" style="color:var(--ac)"></i> Agenda Editorial</div>
    <div class="pg-s">Eventos e datas importantes para cobertura</div></div>
    <div class="tbar-acts">
        <button class="btn btn-p" onclick="document.getElementById('m-agenda').classList.add('open')"><i class="bi bi-plus-lg"></i> Novo Evento</button>
    </div>
</div>
<div class="cnt">{$rows}</div>

<div class="mbg" id="m-agenda">
<div class="mbox">
    <div class="mt">Novo Evento <button class="mx" onclick="closeM('m-agenda')">✕</button></div>
    <form method="POST" action="/public/news/{$sid}/agenda/salvar">
        <div class="fg2">
            <div class="fg" style="grid-column:span 2"><label class="fl">Título *</label><input type="text" name="titulo" class="fi" required placeholder="Ex: Eleições Autárquicas"></div>
            <div class="fg"><label class="fl">Data *</label><input type="date" name="data_evento" class="fi" required></div>
            <div class="fg"><label class="fl">Hora</label><input type="time" name="hora_evento" class="fi"></div>
            <div class="fg"><label class="fl">Local</label><input type="text" name="local" class="fi" placeholder="Ex: Luanda"></div>
            <div class="fg"><label class="fl">Relevância</label>
                <select name="relevancia" class="fs">
                    <option value="alta">Alta</option>
                    <option value="media" selected>Média</option>
                    <option value="baixa">Baixa</option>
                </select>
            </div>
            <div class="fg" style="grid-column:span 2"><label class="fl">Descrição</label><textarea name="descricao" class="fta" style="min-height:72px"></textarea></div>
        </div>
        <div class="ff2">
            <button type="button" class="btn btn-s" onclick="closeM('m-agenda')">Cancelar</button>
            <button type="submit" class="btn btn-p">Guardar</button>
        </div>
    </form>
</div>
</div>

<script>
function closeM(id){ document.getElementById(id).classList.remove('open'); }
document.getElementById('m-agenda').addEventListener('click',e=>{ if(e.target===document.getElementById('m-agenda')) closeM('m-agenda'); });
function criarNoticiaDaAgenda(titulo, id){
    document.querySelector('#m-nova input[name=titulo]').value = titulo;
    document.getElementById('m-nova').classList.add('open');
}
</script>
HTML, $sid, 'agenda');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    /* ─── SALVAR AGENDA ──────────────────────────────────── */
    public function agendaSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $b   = $request->getParsedBody();
        $this->db->insert('rnb_noticias_agenda', [
            'station_id'  => $sid,
            'titulo'      => trim($b['titulo'] ?? ''),
            'descricao'   => trim($b['descricao'] ?? ''),
            'local'       => trim($b['local'] ?? ''),
            'data_evento' => $b['data_evento'],
            'hora_evento' => $b['hora_evento'] ?: null,
            'relevancia'  => $b['relevancia'] ?? 'media',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        return $response->withHeader('Location', "/public/news/{$sid}/agenda")->withStatus(302);
    }

    /* ─── ARQUIVO ────────────────────────────────────────── */
    public function arquivoAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];

        $arquivo = $this->db->fetchAllAssociative(
            "SELECT n.*, COUNT(l.id) as leituras_real FROM rnb_noticias n
             LEFT JOIN rnb_noticias_leituras l ON l.noticia_id=n.id
             WHERE n.station_id=? AND (n.ativo=0 OR n.data_expiracao<=NOW())
             GROUP BY n.id ORDER BY n.updated_at DESC LIMIT 50",
            [$sid]
        );

        $rows = '';
        foreach ($arquivo as $n) {
            $cor = $this->categoriaCor($n['categoria']);
            $rows .= "<div style='display:flex;align-items:center;gap:10px;padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.04)'>
                <span style='width:8px;height:8px;border-radius:50%;background:{$cor};flex-shrink:0'></span>
                <div style='flex:1;min-width:0'>
                    <div style='font-size:12px;font-weight:600;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis'>".htmlspecialchars($n['titulo'])."</div>
                    <div style='font-size:10px;color:var(--t3)'>".date('d/m/Y H:i',strtotime($n['created_at']))." · ".$this->categoriaLabel($n['categoria'])."</div>
                </div>
                <span style='color:var(--pu);font-size:11px;font-weight:700;flex-shrink:0'>".$n['leituras_real']."× lida</span>
                <button class='btn btn-s btn-sm' onclick='restaurar({$n['id']})'>↺ Restaurar</button>
            </div>";
        }

        if (!$rows) $rows = "<div class='es'><i class='bi bi-archive'></i><h3>Arquivo vazio</h3><p>Notícias lidas ou expiradas aparecem aqui.</p></div>";

        $html = $this->layout('Arquivo', <<<HTML
<div class="tbar">
    <div><div class="pg-t"><i class="bi bi-archive" style="color:var(--ac)"></i> Arquivo</div>
    <div class="pg-s">Notícias lidas e expiradas</div></div>
</div>
<div class="cnt">
    <div class="card rd">
        <div class="ct"><i class="bi bi-archive"></i> Notícias Arquivadas</div>
        {$rows}
    </div>
</div>
<script>
function restaurar(id){
    fetch('/public/news/{$sid}/'+id+'/restaurar',{method:'POST'}).then(()=>location.reload());
}
</script>
HTML, $sid, 'arquivo');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    /* ─── NOVA (redirect) ────────────────────────────────── */
    public function novaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        return $response->withHeader('Location', "/public/news/{$sid}")->withStatus(302);
    }
}
