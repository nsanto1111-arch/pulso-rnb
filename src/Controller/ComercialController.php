<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin\Controller;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

use Doctrine\DBAL\Connection;

class ComercialController
{
    private Connection $db;
    public function __construct(Connection $db) { $this->db = $db; }

    private function fmtKz(mixed $v): string { return number_format((float)$v, 2, ',', '.') . ' Kz'; }
    private function mesAtual(): string {
        $m = ['January'=>'Janeiro','February'=>'Fevereiro','March'=>'Março','April'=>'Abril','May'=>'Maio','June'=>'Junho','July'=>'Julho','August'=>'Agosto','September'=>'Setembro','October'=>'Outubro','November'=>'Novembro','December'=>'Dezembro'];
        return strtr(date('F Y'), $m);
    }
    private function estadoBadge(string $e): string {
        return match($e) {
            'activo','aceite','activa' => '<span class="badge bg">✓ '.ucfirst($e).'</span>',
            'prospecto','aguarda','rascunho' => '<span class="badge by">'.ucfirst($e).'</span>',
            'enviada','em_negociacao' => '<span class="badge bb">'.ucfirst(str_replace('_',' ',$e)).'</span>',
            'rejeitada','cancelado','cancelada' => '<span class="badge br">'.ucfirst($e).'</span>',
            default => '<span class="badge bd">'.ucfirst($e).'</span>',
        };
    }

    private function layout(string $titulo, string $body, int $sid, string $active): string
    {
        try {
            $nAnunc = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_anunciantes WHERE station_id=? AND estado='activo'",[$sid]);
            $nCamp  = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_campanhas WHERE station_id=? AND estado='activa'",[$sid]);
            $nProp  = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_propostas WHERE station_id=? AND estado IN('enviada','em_negociacao')",[$sid]);
        } catch(\Exception $e) { $nAnunc=$nCamp=$nProp=0; }

        $nav = [
            'dashboard'   => ['speedometer2','Dashboard'],
            'anunciantes' => ['building','Anunciantes'],
            'propostas'   => ['file-earmark-text','Propostas'],
            'contratos'   => ['file-earmark-check','Contratos'],
            'campanhas'   => ['megaphone','Campanhas'],
            'pipeline'    => ['funnel','Pipeline'],
            'equipa'      => ['people','Equipa'],
            'relatorios'  => ['graph-up-arrow','Relatórios'],
        ];
        $navHtml = '';
        foreach($nav as $k=>[$ico,$lbl]) {
            $cls = $k===$active ? 'on' : '';
            $navHtml .= "<a href='/public/comercial/{$sid}/{$k}' class='ni {$cls}'><i class='bi bi-{$ico}'></i><span>{$lbl}</span></a>";
        }
        // fix dashboard url
        $navHtml = str_replace("/public/comercial/{$sid}/dashboard", "/public/comercial/{$sid}", $navHtml);

        // Gerar navegação global RNB
        $_rnb_sid = $sid; $_rnb_atual = 'comercial';
        ob_start();
        @require dirname(__DIR__, 2) . '/public/rnb-nav.php';
        $rnbGlobalNav = ob_get_clean();

        // Gerar navegação global RNB
        $_rnb_sid = $sid; $_rnb_atual = 'comercial';
        ob_start();
        @require dirname(__DIR__, 2) . '/public/rnb-nav.php';
        $rnbGlobalNav = ob_get_clean();

        return <<<HTML
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>RNB Comercial — {$titulo}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg0:#050510;--bg1:#0f0f1f;--bg2:#1a1a2e;--bg3:#252538;--bg4:#2e2e45;
  --ac:#f59e0b;--ac2:#d97706;--blue:#3b82f6;--green:#10b981;--red:#ef4444;--pu:#8b5cf6;--cy:#06b6d4;
  --t1:#fff;--t2:#a1a1aa;--t3:#71717a;
  --br:rgba(255,255,255,.08);--br2:rgba(255,255,255,.14);
  --ff:'Inter',-apple-system,sans-serif;
  --tr:all .25s cubic-bezier(.4,0,.2,1);
}
html,body{min-height:100vh;font-family:var(--ff);background:var(--bg0);color:var(--t1);font-size:13px;-webkit-font-smoothing:antialiased}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(circle at 15% 50%,rgba(245,158,11,.05),transparent 50%),radial-gradient(circle at 85% 20%,rgba(59,130,246,.04),transparent 50%);pointer-events:none;z-index:0}
.wrap{display:grid;grid-template-columns:210px 1fr;min-height:100vh;position:relative;z-index:1}
/* SIDEBAR */
.sb{background:rgba(15,15,31,.95);border-right:1px solid var(--br);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto}
.sb::-webkit-scrollbar{width:3px}.sb::-webkit-scrollbar-thumb{background:var(--bg3)}
.sb-hd{padding:18px 16px 12px;border-bottom:1px solid var(--br)}
.sb-logo{display:flex;align-items:center;gap:9px;margin-bottom:3px}
.sb-ico{width:34px;height:34px;background:linear-gradient(135deg,var(--ac),var(--ac2));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 0 20px rgba(245,158,11,.25)}
.sb-name{font-size:14px;font-weight:900;background:linear-gradient(135deg,var(--ac),#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.sb-sub{font-size:8px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--t3);margin-left:43px}
.sb-stats{display:grid;grid-template-columns:1fr 1fr;gap:5px;padding:10px 12px;border-bottom:1px solid var(--br)}
.sb-st{background:rgba(255,255,255,.03);border:1px solid var(--br);border-radius:7px;padding:7px;text-align:center}
.sb-sv{font-size:17px;font-weight:800;color:var(--ac)}
.sb-sl{font-size:8px;color:var(--t3);text-transform:uppercase;letter-spacing:.4px;margin-top:1px}
.nav{padding:8px;flex:1}
.ni{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:9px;text-decoration:none;color:var(--t2);font-size:12px;font-weight:600;transition:var(--tr);margin-bottom:2px;white-space:nowrap}
.ni:hover{background:rgba(255,255,255,.05);color:var(--t1);text-decoration:none}
.ni.on{background:linear-gradient(135deg,rgba(245,158,11,.14),rgba(217,119,6,.08));color:var(--ac);border:1px solid rgba(245,158,11,.2);box-shadow:0 0 14px rgba(245,158,11,.1)}
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
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--ac),var(--ac2));opacity:0;transition:var(--tr)}
.card:hover::before{opacity:1}
.ct{font-size:13px;font-weight:700;color:var(--t1);margin-bottom:12px;display:flex;align-items:center;gap:7px}
.ct i{color:var(--ac)}
/* KPIs */
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.kpi{background:linear-gradient(135deg,rgba(26,26,46,.95),rgba(21,21,32,.95));border:1px solid var(--br);border-radius:14px;padding:18px;position:relative;overflow:hidden;transition:var(--tr)}
.kpi:hover{transform:translateY(-2px);border-color:var(--br2)}
.kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:14px 14px 0 0}
.kpi.gold::before{background:var(--ac)}.kpi.blue::before{background:var(--blue)}.kpi.gr::before{background:var(--green)}.kpi.pu::before{background:var(--pu)}
.kpi-ico{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:10px}
.kpi-v{font-size:22px;font-weight:900;line-height:1;margin-bottom:4px}
.kpi-l{font-size:9px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.8px}
.kpi-s{font-size:10px;color:var(--t3);margin-top:5px}
/* TABLE */
.tw{overflow-x:auto;border-radius:12px;border:1px solid var(--br)}
table{width:100%;border-collapse:collapse}
thead th{background:rgba(26,26,46,.8);padding:10px 13px;font-size:9px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--t3);text-align:left;border-bottom:1px solid var(--br);white-space:nowrap}
tbody td{padding:11px 13px;border-bottom:1px solid rgba(255,255,255,.04);font-size:12px;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:rgba(255,255,255,.02)}
/* BADGES */
.badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:700}
.bg{background:rgba(16,185,129,.1);color:var(--green);border:1px solid rgba(16,185,129,.2)}
.by{background:rgba(245,158,11,.1);color:var(--ac);border:1px solid rgba(245,158,11,.2)}
.bb{background:rgba(59,130,246,.1);color:var(--blue);border:1px solid rgba(59,130,246,.2)}
.br{background:rgba(239,68,68,.1);color:var(--red);border:1px solid rgba(239,68,68,.2)}
.bd{background:rgba(255,255,255,.06);color:var(--t2);border:1px solid var(--br)}
.bpu{background:rgba(139,92,246,.1);color:var(--pu);border:1px solid rgba(139,92,246,.2)}
/* BTNS */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font:700 12px var(--ff);cursor:pointer;transition:var(--tr);border:none;text-decoration:none;white-space:nowrap}
.btn:hover{transform:translateY(-1px);text-decoration:none}
.btn-p{background:linear-gradient(135deg,var(--ac),var(--ac2));color:#000;box-shadow:0 0 20px rgba(245,158,11,.2)}
.btn-p:hover{box-shadow:0 0 28px rgba(245,158,11,.35)}
.btn-s{background:rgba(255,255,255,.05);color:var(--t2);border:1px solid var(--br)}
.btn-s:hover{background:rgba(255,255,255,.08);color:var(--t1)}
.btn-sm{padding:4px 10px;font-size:10px}
/* MODAL */
.mbg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);backdrop-filter:blur(12px);z-index:1000;align-items:center;justify-content:center;padding:16px}
.mbg.open{display:flex}
.mbox{background:linear-gradient(135deg,rgba(26,26,46,.99),rgba(15,15,31,.99));border:1px solid var(--br2);border-radius:18px;padding:24px;width:100%;max-width:580px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.6)}
.mt{font-size:16px;font-weight:800;color:var(--t1);display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.mx{background:rgba(255,255,255,.06);border:1px solid var(--br);color:var(--t2);width:28px;height:28px;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px}
/* FORM */
.fg2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fg{margin-bottom:12px}
.fl{display:block;font-size:9px;font-weight:700;color:var(--t2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
.fi,.fs,.fta{width:100%;padding:9px 12px;background:rgba(255,255,255,.04);border:1px solid var(--br);border-radius:8px;color:var(--t1);font:13px var(--ff);outline:none;transition:var(--tr);color-scheme:dark}
.fi:focus,.fs:focus,.fta:focus{border-color:rgba(245,158,11,.4);background:rgba(255,255,255,.06)}
.fi::placeholder,.fta::placeholder{color:var(--t3)}
.fta{resize:vertical;min-height:72px}
.ff2{display:flex;gap:8px;justify-content:flex-end;margin-top:18px;padding-top:14px;border-top:1px solid var(--br)}
/* UTILS */
.es{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem;color:var(--t3);text-align:center;gap:10px}
.es i{font-size:40px;opacity:.1}
.es h3{font-size:16px;font-weight:700;color:var(--t2)}
.es p{font-size:12px;max-width:320px}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:18px}.g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.mb20{margin-bottom:20px}.mb24{margin-bottom:24px}
@media(max-width:900px){.kpis{grid-template-columns:1fr 1fr}.wrap{grid-template-columns:1fr}.sb{display:none}}
</style>
</head><body>
{$rnbGlobalNav}
<div class="wrap">
<div class="sb">
  <div class="sb-hd">
    <div class="sb-logo"><div class="sb-ico">💼</div><div class="sb-name">Comercial</div></div>
    <div class="sb-sub">Rádio New Band</div>
  </div>
  <div class="sb-stats">
    <div class="sb-st"><div class="sb-sv">{$nAnunc}</div><div class="sb-sl">Activos</div></div>
    <div class="sb-st"><div class="sb-sv">{$nCamp}</div><div class="sb-sl">Campanhas</div></div>
    <div class="sb-st" style="grid-column:span 2"><div class="sb-sv">{$nProp}</div><div class="sb-sl">Propostas em curso</div></div>
  </div>
  <nav class="nav">{$navHtml}</nav>
  <div class="sb-ft">
    <a href="/public/pulso/{$sid}" class="sb-bk"><i class="bi bi-arrow-left-circle"></i>Voltar ao PULSO</a>
  </div>
</div>
<div class="main">{$body}</div>
</div>
</body></html>
HTML;
    }

    public function dashboardAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        try {
            $kAnunc  = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_anunciantes WHERE station_id=? AND estado='activo'",[$sid]);
            $kCamp   = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_campanhas WHERE station_id=? AND estado='activa'",[$sid]);
            $kProp   = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_propostas WHERE station_id=? AND estado IN('enviada','em_negociacao')",[$sid]);
            $kRec    = (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_final),0) FROM rnb_propostas WHERE station_id=? AND estado='aceite' AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())",[$sid]);
            $ultAnunc = $this->db->fetchAllAssociative("SELECT * FROM rnb_anunciantes WHERE station_id=? ORDER BY created_at DESC LIMIT 5",[$sid]);
            $ultProp  = $this->db->fetchAllAssociative("SELECT p.*,a.nome as an FROM rnb_propostas p LEFT JOIN rnb_anunciantes a ON a.id=p.anunciante_id WHERE p.station_id=? ORDER BY p.created_at DESC LIMIT 5",[$sid]);
            $camps    = $this->db->fetchAllAssociative("SELECT c.*,a.nome as an FROM rnb_campanhas c LEFT JOIN rnb_anunciantes a ON a.id=c.anunciante_id WHERE c.station_id=? AND c.estado='activa' ORDER BY c.data_fim ASC LIMIT 5",[$sid]);
        } catch(\Exception $e) { $kAnunc=$kCamp=$kProp=$kRec=0; $ultAnunc=$ultProp=$camps=[]; }

        $ra=''; foreach($ultAnunc as $a) {
            $ini=strtoupper(substr($a['nome'],0,2));
            $ra.="<tr><td><div style='display:flex;align-items:center;gap:9px'><div style='width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,.1);color:var(--ac);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800'>{$ini}</div><span style='font-weight:700'>".htmlspecialchars($a['nome'])."</span></div></td><td>".$this->estadoBadge($a['estado'])."</td><td style='color:var(--t2)'>".htmlspecialchars($a['cidade']??'—')."</td></tr>";
        }
        if(!$ra) $ra="<tr><td colspan='3' style='text-align:center;padding:1.5rem;color:var(--t3)'>Sem anunciantes ainda. <a href='/public/comercial/{$sid}/anunciantes' style='color:var(--ac)'>Adicionar →</a></td></tr>";

        $rp=''; foreach($ultProp as $p) {
            $rp.="<tr><td><div style='font-weight:700'>".htmlspecialchars($p['titulo'])."</div><div style='font-size:10px;color:var(--t3)'>".htmlspecialchars($p['an']??'—')."</div></td><td>".$this->estadoBadge($p['estado'])."</td><td style='color:var(--ac);font-weight:700'>".$this->fmtKz($p['valor_final'])."</td></tr>";
        }
        if(!$rp) $rp="<tr><td colspan='3' style='text-align:center;padding:1.5rem;color:var(--t3)'>Sem propostas ainda.</td></tr>";

        $rc=''; foreach($camps as $c) {
            $pr=$c['spots_contratados']>0?round($c['spots_emitidos']/$c['spots_contratados']*100):0;
            $rc.="<tr><td><div style='font-weight:700'>".htmlspecialchars($c['nome'])."</div><div style='font-size:10px;color:var(--t3)'>".htmlspecialchars($c['an']??'—')."</div></td><td>{$c['data_inicio']} → {$c['data_fim']}</td><td><div style='font-size:10px;color:var(--t2);margin-bottom:3px'>{$pr}%</div><div style='height:4px;background:rgba(255,255,255,.06);border-radius:2px'><div style='height:100%;width:{$pr}%;background:var(--green);border-radius:2px'></div></div></td></tr>";
        }
        if(!$rc) $rc="<tr><td colspan='3' style='text-align:center;padding:1.5rem;color:var(--t3)'>Sem campanhas activas.</td></tr>";

        $mes = $this->mesAtual();
        $html = $this->layout('Dashboard', <<<HTML
<div class="tbar"><div><div class="pg-t">Dashboard Comercial</div><div class="pg-s">{$mes}</div></div>
<div class="tbar-acts">
  <a href="/public/comercial/{$sid}/anunciantes" class="btn btn-s"><i class="bi bi-building"></i> Anunciante</a>
  <a href="/public/comercial/{$sid}/propostas" class="btn btn-p"><i class="bi bi-plus-lg"></i> Nova Proposta</a>
</div></div>
<div class="cnt">
<div class="kpis mb24">
  <div class="kpi gold"><div class="kpi-ico" style="background:rgba(245,158,11,.1)">💰</div><div class="kpi-v" style="color:var(--ac)">{$this->fmtKz($kRec)}</div><div class="kpi-l">Receita do Mês</div><div class="kpi-s">Propostas aceites</div></div>
  <div class="kpi blue"><div class="kpi-ico" style="background:rgba(59,130,246,.1)">🏢</div><div class="kpi-v" style="color:var(--blue)">{$kAnunc}</div><div class="kpi-l">Anunciantes Activos</div><div class="kpi-s"><a href="/public/comercial/{$sid}/anunciantes" style="color:var(--blue)">Ver todos →</a></div></div>
  <div class="kpi gr"><div class="kpi-ico" style="background:rgba(16,185,129,.1)">📢</div><div class="kpi-v" style="color:var(--green)">{$kCamp}</div><div class="kpi-l">Campanhas Activas</div><div class="kpi-s"><a href="/public/comercial/{$sid}/campanhas" style="color:var(--green)">Ver campanhas →</a></div></div>
  <div class="kpi pu"><div class="kpi-ico" style="background:rgba(139,92,246,.1)">📄</div><div class="kpi-v" style="color:var(--pu)">{$kProp}</div><div class="kpi-l">Propostas Pendentes</div><div class="kpi-s"><a href="/public/comercial/{$sid}/propostas" style="color:var(--pu)">Ver propostas →</a></div></div>
</div>
<div class="g2 mb20">
  <div class="card"><div class="ct"><i class="bi bi-building"></i>Anunciantes Recentes <a href="/public/comercial/{$sid}/anunciantes" class="btn btn-s btn-sm" style="margin-left:auto">Ver todos</a></div><div class="tw"><table><thead><tr><th>Nome</th><th>Estado</th><th>Cidade</th></tr></thead><tbody>{$ra}</tbody></table></div></div>
  <div class="card"><div class="ct"><i class="bi bi-file-earmark-text"></i>Propostas Recentes <a href="/public/comercial/{$sid}/propostas" class="btn btn-s btn-sm" style="margin-left:auto">Ver todas</a></div><div class="tw"><table><thead><tr><th>Proposta</th><th>Estado</th><th>Valor</th></tr></thead><tbody>{$rp}</tbody></table></div></div>
</div>
<div class="card"><div class="ct"><i class="bi bi-megaphone"></i>Campanhas Activas <a href="/public/comercial/{$sid}/campanhas" class="btn btn-s btn-sm" style="margin-left:auto">Ver todas</a></div><div class="tw"><table><thead><tr><th>Campanha</th><th>Período</th><th>Progresso</th></tr></thead><tbody>{$rc}</tbody></table></div></div>
</div>
HTML, $sid, 'dashboard');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function anunciantesAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        $qp=$request->getQueryParams();
        $busca=trim($qp['busca']??'');
        $where="WHERE station_id=?"; $binds=[$sid];
        if($busca){$where.=" AND (nome LIKE ? OR email LIKE ? OR nif LIKE ?)"; $binds=array_merge($binds,["%{$busca}%","%{$busca}%","%{$busca}%"]);}
        $lista=$this->db->fetchAllAssociative("SELECT * FROM rnb_anunciantes {$where} ORDER BY nome",$binds);
        $tipoL=['empresa'=>'Empresa','organismo_estado'=>'Estado','marca_nacional'=>'Nacional','internacional'=>'Internacional'];
        $rows=''; foreach($lista as $a){
            $ini=strtoupper(substr($a['nome'],0,2));
            $rows.="<tr>
              <td><div style='display:flex;align-items:center;gap:9px'><div style='width:34px;height:34px;border-radius:9px;background:rgba(245,158,11,.1);color:var(--ac);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800'>{$ini}</div><div><div style='font-weight:700'>".htmlspecialchars($a['nome'])."</div><div style='font-size:10px;color:var(--t3)'>".htmlspecialchars($tipoL[$a['tipo']]??$a['tipo'])."</div></div></div></td>
              <td>".$this->estadoBadge($a['estado'])."</td>
              <td style='color:var(--t2)'>".htmlspecialchars($a['email']??'—')."</td>
              <td style='color:var(--t2)'>".htmlspecialchars($a['telefone']??'—')."</td>
              <td style='color:var(--t2)'>".htmlspecialchars($a['cidade']??'—')."</td>
              <td><div style='display:flex;gap:4px'>
                <button class='btn btn-s btn-sm' onclick='editAnunc(".json_encode($a).")'>✏</button>
                <a href='/public/comercial/{$sid}/propostas' class='btn btn-s btn-sm'>+ Proposta</a>
              </div></td>
            </tr>";
        }
        if(!$rows) $rows="<tr><td colspan='6'><div class='es'><i class='bi bi-building'></i><h3>Sem anunciantes</h3><p>Adiciona o primeiro anunciante.</p></div></td></tr>";

        $html=$this->layout('Anunciantes',<<<HTML
<div class="tbar"><div><div class="pg-t">Anunciantes</div><div class="pg-s">Clientes comerciais da RNB</div></div>
<div class="tbar-acts">
  <form method="GET" style="display:flex;gap:6px"><input type="text" name="busca" value="{$busca}" placeholder="Pesquisar..." class="fi" style="width:180px;height:34px"></form>
  <button class="btn btn-p" onclick="document.getElementById('m-anunc').classList.add('open')"><i class="bi bi-plus-lg"></i> Novo Anunciante</button>
</div></div>
<div class="cnt"><div class="tw"><table>
<thead><tr><th>Anunciante</th><th>Estado</th><th>Email</th><th>Telefone</th><th>Cidade</th><th>Acções</th></tr></thead>
<tbody>{$rows}</tbody>
</table></div></div>
<div class="mbg" id="m-anunc">
<div class="mbox">
  <div class="mt"><span id="m-anunc-t">Novo Anunciante</span><button class="mx" onclick="closeM('m-anunc')">✕</button></div>
  <form method="POST" action="/public/comercial/{$sid}/anunciantes/salvar" id="f-anunc">
    <input type="hidden" name="id" id="a-id">
    <div class="fg2">
      <div class="fg" style="grid-column:span 2"><label class="fl">Nome *</label><input type="text" name="nome" id="a-nome" class="fi" required placeholder="Ex: TPA — Televisão Pública de Angola"></div>
      <div class="fg"><label class="fl">Tipo</label><select name="tipo" id="a-tipo" class="fs"><option value="empresa">Empresa</option><option value="organismo_estado">Organismo do Estado</option><option value="marca_nacional">Marca Nacional</option><option value="internacional">Internacional</option></select></div>
      <div class="fg"><label class="fl">Estado</label><select name="estado" id="a-estado" class="fs"><option value="prospecto">Prospecto</option><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
      <div class="fg"><label class="fl">NIF</label><input type="text" name="nif" id="a-nif" class="fi" placeholder="Nº identificação"></div>
      <div class="fg"><label class="fl">Sector</label><input type="text" name="sector" id="a-sector" class="fi" placeholder="Ex: Telecomunicações"></div>
      <div class="fg"><label class="fl">Email</label><input type="email" name="email" id="a-email" class="fi" placeholder="email@empresa.ao"></div>
      <div class="fg"><label class="fl">Telefone</label><input type="text" name="telefone" id="a-tel" class="fi" placeholder="+244 923 000 000"></div>
      <div class="fg"><label class="fl">Cidade</label><input type="text" name="cidade" id="a-cidade" class="fi" placeholder="Luanda"></div>
      <div class="fg"><label class="fl">Website</label><input type="text" name="website" id="a-web" class="fi" placeholder="www.empresa.ao"></div>
      <div class="fg" style="grid-column:span 2"><label class="fl">Notas</label><textarea name="notas" id="a-notas" class="fta"></textarea></div>
    </div>
    <div class="ff2"><button type="button" class="btn btn-s" onclick="closeM('m-anunc')">Cancelar</button><button type="submit" class="btn btn-p"><i class="bi bi-check-lg"></i> Guardar</button></div>
  </form>
</div></div>
<script>
function editAnunc(a){
  document.getElementById('m-anunc-t').textContent='Editar Anunciante';
  ['id','nome','tipo','estado','nif','sector','email','tel','cidade','web','notas'].forEach(k=>{
    const el=document.getElementById('a-'+k);
    if(el) el.value=a[k==='tel'?'telefone':k==='web'?'website':k]||'';
  });
  document.getElementById('m-anunc').classList.add('open');
}
function closeM(id){ document.getElementById(id).classList.remove('open'); }
document.getElementById('m-anunc').addEventListener('click',e=>{if(e.target===document.getElementById('m-anunc'))closeM('m-anunc');});
</script>
HTML,$sid,'anunciantes');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function anuncianteSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody(); $id=(int)($b['id']??0);
        $d=['station_id'=>$sid,'nome'=>trim($b['nome']??''),'tipo'=>$b['tipo']??'empresa','estado'=>$b['estado']??'prospecto','nif'=>trim($b['nif']??''),'sector'=>trim($b['sector']??''),'email'=>trim($b['email']??''),'telefone'=>trim($b['telefone']??''),'cidade'=>trim($b['cidade']??''),'website'=>trim($b['website']??''),'notas'=>trim($b['notas']??''),'updated_at'=>date('Y-m-d H:i:s')];
        if($id>0){$this->db->update('rnb_anunciantes',$d,['id'=>$id]);}else{$d['created_at']=date('Y-m-d H:i:s');$this->db->insert('rnb_anunciantes',$d);}
        return $response->withHeader('Location',"/public/comercial/{$sid}/anunciantes")->withStatus(302);
    }

    public function anuncianteApagarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $id=(int)$params['id'];
        $this->db->update('rnb_anunciantes',['estado'=>'inactivo'],['id'=>$id,'station_id'=>$sid]);
        return $response->withHeader('Location',"/public/comercial/{$sid}/anunciantes")->withStatus(302);
    }

    public function propostasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        $lista=$this->db->fetchAllAssociative("SELECT p.*,a.nome as an,c.nome as com FROM rnb_propostas p LEFT JOIN rnb_anunciantes a ON a.id=p.anunciante_id LEFT JOIN rnb_comerciais c ON c.id=p.comercial_id WHERE p.station_id=? ORDER BY p.created_at DESC",[$sid]);
        $anunc=$this->db->fetchAllAssociative("SELECT id,nome FROM rnb_anunciantes WHERE station_id=? AND estado IN('activo','prospecto') ORDER BY nome",[$sid]);
        $coms=$this->db->fetchAllAssociative("SELECT id,nome FROM rnb_comerciais WHERE station_id=? AND ativo=1 ORDER BY nome",[$sid]);
        $oA=''; foreach($anunc as $a) $oA.="<option value='{$a['id']}'>".htmlspecialchars($a['nome'])."</option>";
        $oC='<option value="">— Sem comercial —</option>'; foreach($coms as $c) $oC.="<option value='{$c['id']}'>".htmlspecialchars($c['nome'])."</option>";
        $rows=''; foreach($lista as $p){
            $acoes='';
            if($p['estado']==='rascunho') $acoes.="<button class='btn btn-s btn-sm' onclick='mudaEstado({$p['id']},\"enviada\")'>Enviar</button>";
            if(in_array($p['estado'],['enviada','em_negociacao'])) $acoes.="<button class='btn btn-s btn-sm' style='color:var(--green)' onclick='mudaEstado({$p['id']},\"aceite\")'>✓ Aceitar</button><button class='btn btn-s btn-sm' style='color:var(--red)' onclick='mudaEstado({$p['id']},\"rejeitada\")'>✕</button>";
            $rows.="<tr><td><div style='font-weight:700'>".htmlspecialchars($p['titulo'])."</div><div style='font-size:10px;color:var(--t3)'>#".str_pad($p['id'],4,'0',STR_PAD_LEFT)." · ".htmlspecialchars($p['an']??'—')."</div></td><td>".$this->estadoBadge($p['estado'])."</td><td style='color:var(--ac);font-weight:700'>".$this->fmtKz($p['valor_final'])."</td><td style='color:var(--t2)'>{$p['data_validade']}</td><td style='color:var(--t3)'>".htmlspecialchars($p['com']??'—')."</td><td><div style='display:flex;gap:4px;flex-wrap:wrap'>{$acoes}</div></td></tr>";
        }
        if(!$rows) $rows="<tr><td colspan='6'><div class='es'><i class='bi bi-file-earmark-text'></i><h3>Sem propostas</h3><p>Cria a primeira proposta comercial.</p></div></td></tr>";

        $html=$this->layout('Propostas',<<<HTML
<div class="tbar"><div><div class="pg-t">Propostas</div><div class="pg-s">Pipeline de vendas</div></div>
<div class="tbar-acts"><button class="btn btn-p" onclick="document.getElementById('m-prop').classList.add('open')"><i class="bi bi-plus-lg"></i> Nova Proposta</button></div></div>
<div class="cnt"><div class="tw"><table>
<thead><tr><th>Proposta</th><th>Estado</th><th>Valor</th><th>Validade</th><th>Comercial</th><th>Acções</th></tr></thead>
<tbody>{$rows}</tbody></table></div></div>
<div class="mbg" id="m-prop"><div class="mbox" style="max-width:640px">
  <div class="mt">Nova Proposta <button class="mx" onclick="closeM('m-prop')">✕</button></div>
  <form method="POST" action="/public/comercial/{$sid}/propostas/salvar">
    <div class="fg2">
      <div class="fg" style="grid-column:span 2"><label class="fl">Título *</label><input type="text" name="titulo" class="fi" required placeholder="Ex: Campanha Natal 2026 — TPA"></div>
      <div class="fg"><label class="fl">Anunciante *</label><select name="anunciante_id" class="fs" required><option value="">— Seleccionar —</option>{$oA}</select></div>
      <div class="fg"><label class="fl">Comercial</label><select name="comercial_id" class="fs">{$oC}</select></div>
      <div class="fg"><label class="fl">Tipo</label><select name="tipo_publicidade" class="fs"><option value="spot">Spot (30s/60s)</option><option value="patrocinio">Patrocínio</option><option value="spot_patrocinio">Spot + Patrocínio</option><option value="live_read">Live Read</option></select></div>
      <div class="fg"><label class="fl">Duração do Spot</label><select name="duracao_spot" class="fs"><option value="15">15s</option><option value="30" selected>30s</option><option value="45">45s</option><option value="60">60s</option></select></div>
      <div class="fg"><label class="fl">Data Início Prevista</label><input type="date" name="data_inicio_prevista" class="fi"></div>
      <div class="fg"><label class="fl">Data Fim Prevista</label><input type="date" name="data_fim_prevista" class="fi"></div>
      <div class="fg"><label class="fl">Valor Total (Kz)</label><input type="number" name="valor_total" id="pv-total" class="fi" step="0.01" min="0" oninput="calcF()"></div>
      <div class="fg"><label class="fl">Desconto (%)</label><input type="number" name="desconto" id="pv-desc" class="fi" step="0.01" min="0" max="100" value="0" oninput="calcF()"></div>
      <div class="fg"><label class="fl">Valor Final (Kz)</label><input type="number" name="valor_final" id="pv-final" class="fi" step="0.01" min="0"></div>
      <div class="fg"><label class="fl">Data Validade</label><input type="date" name="data_validade" class="fi"></div>
      <div class="fg" style="grid-column:span 2"><label class="fl">Horários Pretendidos</label><input type="text" name="horario_preferencial" class="fi" placeholder="Ex: Manhã 7h-10h, Tarde 14h-17h"></div>
      <div class="fg" style="grid-column:span 2"><label class="fl">Notas para o Cliente</label><textarea name="notas_cliente" class="fta"></textarea></div>
    </div>
    <div class="ff2"><button type="button" class="btn btn-s" onclick="closeM('m-prop')">Cancelar</button><button type="submit" class="btn btn-p">Criar Proposta</button></div>
  </form>
</div></div>
<script>
function calcF(){const t=parseFloat(document.getElementById('pv-total').value)||0,d=parseFloat(document.getElementById('pv-desc').value)||0;document.getElementById('pv-final').value=(t*(1-d/100)).toFixed(2);}
function mudaEstado(id,e){if(!confirm('Confirmas?'))return;fetch('/public/comercial/{$sid}/propostas/'+id+'/estado',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'estado='+e}).then(()=>location.reload());}
function closeM(id){document.getElementById(id).classList.remove('open');}
document.getElementById('m-prop').addEventListener('click',e=>{if(e.target===document.getElementById('m-prop'))closeM('m-prop');});
</script>
HTML,$sid,'propostas');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function propostaSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $num='PROP-'.date('Y').'-'.str_pad((string)rand(1,9999),4,'0',STR_PAD_LEFT);
        $d=['station_id'=>$sid,'numero'=>$num,'anunciante_id'=>(int)($b['anunciante_id']??0),'comercial_id'=>(int)($b['comercial_id']??0)?:null,'titulo'=>trim($b['titulo']??''),'tipo_publicidade'=>$b['tipo_publicidade']??'spot','duracao_spot'=>(int)($b['duracao_spot']??30),'data_inicio_prevista'=>$b['data_inicio_prevista']?:null,'data_fim_prevista'=>$b['data_fim_prevista']?:null,'valor_total'=>(float)($b['valor_total']??0),'desconto'=>(float)($b['desconto']??0),'valor_final'=>(float)($b['valor_final']??0),'data_validade'=>$b['data_validade']?:null,'horario_preferencial'=>trim($b['horario_preferencial']??''),'notas_cliente'=>trim($b['notas_cliente']??''),'estado'=>'rascunho','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
        $this->db->insert('rnb_propostas',$d);
        return $response->withHeader('Location',"/public/comercial/{$sid}/propostas")->withStatus(302);
    }

    public function propostaEstadoAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $id=(int)$params['id']; $b=$request->getParsedBody(); $e=$b['estado']??'';
        if(in_array($e,['rascunho','enviada','em_negociacao','aceite','rejeitada','expirada'])){
            $u=['estado'=>$e,'updated_at'=>date('Y-m-d H:i:s')];
            if($e==='enviada') $u['data_envio']=date('Y-m-d H:i:s');
            $this->db->update('rnb_propostas',$u,['id'=>$id,'station_id'=>$sid]);
        }
        $response->getBody()->write('{"status":"ok"}');
        return $response->withHeader('Content-Type','application/json');
    }

    public function contratosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        $lista=$this->db->fetchAllAssociative("SELECT c.*,a.nome as an,cm.nome as com FROM rnb_contratos c LEFT JOIN rnb_anunciantes a ON a.id=c.anunciante_id LEFT JOIN rnb_comerciais cm ON cm.id=c.comercial_id WHERE c.station_id=? ORDER BY c.created_at DESC",[$sid]);
        $anunc=$this->db->fetchAllAssociative("SELECT id,nome FROM rnb_anunciantes WHERE station_id=? AND estado='activo' ORDER BY nome",[$sid]);
        $coms=$this->db->fetchAllAssociative("SELECT id,nome FROM rnb_comerciais WHERE station_id=? AND ativo=1 ORDER BY nome",[$sid]);
        $oA=''; foreach($anunc as $a) $oA.="<option value='{$a['id']}'>".htmlspecialchars($a['nome'])."</option>";
        $oC='<option value="">— Sem comercial —</option>'; foreach($coms as $c) $oC.="<option value='{$c['id']}'>".htmlspecialchars($c['nome'])."</option>";
        $rows=''; foreach($lista as $c){
            $pg=$c['valor_total']>0?round($c['valor_pago']/$c['valor_total']*100):0;
            $rows.="<tr>
              <td><div style='font-weight:700'>".htmlspecialchars($c['titulo'])."</div><div style='font-size:10px;color:var(--t3)'>".htmlspecialchars($c['an']??'—')."</div></td>
              <td>".$this->estadoBadge($c['estado'])."</td>
              <td style='font-size:11px;color:var(--t2)'>{$c['data_inicio']}<br>{$c['data_fim']}</td>
              <td style='color:var(--ac);font-weight:700'>".$this->fmtKz($c['valor_total'])."</td>
              <td><div style='font-size:10px;color:var(--t3);margin-bottom:3px'>{$pg}% pago</div><div style='height:4px;background:rgba(255,255,255,.06);border-radius:2px'><div style='height:100%;width:{$pg}%;background:var(--green);border-radius:2px'></div></div></td>
              <td>
                <div style='display:flex;gap:4px;flex-wrap:wrap'>
                  <button class='btn btn-s btn-sm' title='Gerar conta a receber no Finance Pro' onclick='gerarFatura({$c['id']})' style='color:var(--green)'><i class='bi bi-receipt'></i> Factura</button>
                  <button class='btn btn-s btn-sm' title='Sincronizar pagamento do Finance Pro' onclick='sincronizar({$c['id']})' style='color:var(--blue)'><i class='bi bi-arrow-repeat'></i></button>
                  <a href='/public/financas/{$sid}/contas-receber' class='btn btn-s btn-sm' title='Ver no Finance Pro' style='color:var(--ac)'><i class='bi bi-box-arrow-up-right'></i></a>
                </div>
              </td>
            </tr>";
        }
        if(!$rows) $rows="<tr><td colspan='5'><div class='es'><i class='bi bi-file-earmark-check'></i><h3>Sem contratos</h3><p>Os contratos aparecem após aceitar propostas.</p></div></td></tr>";

        $html=$this->layout('Contratos',<<<HTML
<div class="tbar"><div><div class="pg-t">Contratos</div><div class="pg-s">Acordos comerciais assinados</div></div>
<div class="tbar-acts"><button class="btn btn-p" onclick="document.getElementById('m-cont').classList.add('open')"><i class="bi bi-plus-lg"></i> Novo Contrato</button></div></div>
<div class="cnt"><div class="tw"><table>
<thead><tr><th>Contrato</th><th>Estado</th><th>Período</th><th>Valor</th><th>Pagamento</th><th>Finance Pro</th></tr></thead>
<tbody>{$rows}</tbody></table></div></div>
<div class="mbg" id="m-cont"><div class="mbox" style="max-width:640px">
  <div class="mt">Novo Contrato <button class="mx" onclick="closeM('m-cont')">✕</button></div>
  <form method="POST" action="/public/comercial/{$sid}/contratos/salvar">
    <div class="fg2">
      <div class="fg" style="grid-column:span 2"><label class="fl">Título *</label><input type="text" name="titulo" class="fi" required placeholder="Ex: Contrato Anual TPA 2026"></div>
      <div class="fg"><label class="fl">Anunciante *</label><select name="anunciante_id" class="fs" required><option value="">— Seleccionar —</option>{$oA}</select></div>
      <div class="fg"><label class="fl">Comercial</label><select name="comercial_id" class="fs">{$oC}</select></div>
      <div class="fg"><label class="fl">Data Início *</label><input type="date" name="data_inicio" class="fi" required></div>
      <div class="fg"><label class="fl">Data Fim *</label><input type="date" name="data_fim" class="fi" required></div>
      <div class="fg"><label class="fl">Total Spots</label><input type="number" name="total_spots_contratados" class="fi" min="0" value="0"></div>
      <div class="fg"><label class="fl">Valor Total (Kz) *</label><input type="number" name="valor_total" class="fi" step="0.01" required min="0"></div>
      <div class="fg"><label class="fl">Forma Pagamento</label><select name="forma_pagamento" class="fs"><option value="pos_emissao">Pós-emissão</option><option value="pronto">Pronto pagamento</option><option value="prestacoes">Prestações</option></select></div>
      <div class="fg"><label class="fl">Tipo</label><select name="tipo_publicidade" class="fs"><option value="spot">Spot</option><option value="patrocinio">Patrocínio</option><option value="spot_patrocinio">Spot + Patrocínio</option></select></div>
      <div class="fg" style="grid-column:span 2"><label class="fl">Notas</label><textarea name="notas" class="fta"></textarea></div>
    </div>
    <div class="ff2"><button type="button" class="btn btn-s" onclick="closeM('m-cont')">Cancelar</button><button type="submit" class="btn btn-p">Criar Contrato</button></div>
  </form>
</div></div>
<script>
function closeM(id){document.getElementById(id).classList.remove('open');}
document.getElementById('m-cont').addEventListener('click',e=>{if(e.target===document.getElementById('m-cont'))closeM('m-cont');});

function gerarFatura(id){
  const btn = event.target.closest('button');
  btn.disabled=true; btn.textContent='...';
  fetch('/public/comercial/' + {$sid} + '/contratos/'+id+'/gerar-fatura',{method:'POST'})
    .then(r=>r.json()).then(d=>{
      if(d.status==='ok') showToast('✅ '+d.message+' — '+d.valor,'success');
      else if(d.status==='exists') showToast('ℹ️ '+d.message,'info');
      else showToast('❌ '+d.message,'error');
      btn.disabled=false; btn.innerHTML="<i class='bi bi-receipt'></i> Factura";
    });
}

function sincronizar(id){
  const btn = event.target.closest('button');
  btn.disabled=true;
  fetch('/public/comercial/' + {$sid} + '/contratos/'+id+'/sincronizar',{method:'POST'})
    .then(r=>r.json()).then(d=>{
      if(d.status==='ok') { showToast('✅ Sincronizado — '+d.valor_pago+' pago','success'); setTimeout(()=>location.reload(),1500); }
      else showToast('ℹ️ '+d.message,'info');
      btn.disabled=false;
    });
}

function showToast(msg, type){
  const t=document.createElement('div');
  const colors={'success':'var(--green)','error':'var(--red)','info':'var(--blue)'};
  t.style.cssText='position:fixed;bottom:20px;right:20px;padding:12px 18px;border-radius:10px;font-size:13px;font-weight:700;color:#fff;z-index:9999;animation:fadeIn .3s ease;box-shadow:0 8px 24px rgba(0,0,0,.4);background:'+(colors[type]||'var(--ac)');
  t.textContent=msg;
  document.body.appendChild(t);
  setTimeout(()=>t.remove(),4000);
}
</script>
HTML,$sid,'contratos');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function contratoSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $num='CONT-'.date('Y').'-'.str_pad((string)rand(1,9999),4,'0',STR_PAD_LEFT);
        $d=['station_id'=>$sid,'numero'=>$num,'anunciante_id'=>(int)($b['anunciante_id']??0),'comercial_id'=>(int)($b['comercial_id']??0)?:null,'titulo'=>trim($b['titulo']??''),'tipo_publicidade'=>$b['tipo_publicidade']??'spot','data_inicio'=>$b['data_inicio'],'data_fim'=>$b['data_fim'],'total_spots_contratados'=>(int)($b['total_spots_contratados']??0),'valor_total'=>(float)($b['valor_total']??0),'forma_pagamento'=>$b['forma_pagamento']??'pos_emissao','notas'=>trim($b['notas']??''),'estado'=>'activo','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
        $this->db->insert('rnb_contratos',$d);
        $novoId = (int)$this->db->lastInsertId();
        // Gerar automaticamente conta a receber no Finance Pro
        $this->autoGerarContaReceber($sid, $novoId);
        return $response->withHeader('Location',"/public/comercial/{$sid}/contratos")->withStatus(302);
    }

    public function campanhasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        $lista=$this->db->fetchAllAssociative("SELECT c.*,a.nome as an FROM rnb_campanhas c LEFT JOIN rnb_anunciantes a ON a.id=c.anunciante_id WHERE c.station_id=? ORDER BY c.created_at DESC",[$sid]);
        $anunc=$this->db->fetchAllAssociative("SELECT id,nome FROM rnb_anunciantes WHERE station_id=? AND estado='activo' ORDER BY nome",[$sid]);
        $conts=$this->db->fetchAllAssociative("SELECT id,titulo FROM rnb_contratos WHERE station_id=? AND estado='activo' ORDER BY titulo",[$sid]);
        $oA=''; foreach($anunc as $a) $oA.="<option value='{$a['id']}'>".htmlspecialchars($a['nome'])."</option>";
        $oCt='<option value="">— Sem contrato —</option>'; foreach($conts as $c) $oCt.="<option value='{$c['id']}'>".htmlspecialchars($c['titulo'])."</option>";
        $rows=''; foreach($lista as $c){
            $pr=$c['spots_contratados']>0?round($c['spots_emitidos']/$c['spots_contratados']*100):0;
            $cor=$pr>=100?'var(--green)':($pr>=50?'var(--ac)':'var(--blue)');
            $f=max(0,(int)((strtotime($c['data_fim'])-time())/86400));
            $fc=$f<=7?'var(--red)':'var(--t3)';
            $rows.="<tr><td><div style='font-weight:700'>".htmlspecialchars($c['nome'])."</div><div style='font-size:10px;color:var(--t3)'>".htmlspecialchars($c['an']??'—')."</div></td><td>".$this->estadoBadge($c['estado'])."</td><td style='font-size:11px;color:var(--t2)'>{$c['data_inicio']}<br>{$c['data_fim']}</td><td><div style='font-size:10px;color:var(--t2);margin-bottom:3px'>{$c['spots_emitidos']}/{$c['spots_contratados']} ({$pr}%)</div><div style='height:4px;background:rgba(255,255,255,.06);border-radius:2px;width:100px'><div style='height:100%;width:{$pr}%;background:{$cor};border-radius:2px'></div></div></td><td style='color:{$fc};font-weight:700'>{$f}d</td></tr>";
        }
        if(!$rows) $rows="<tr><td colspan='5'><div class='es'><i class='bi bi-megaphone'></i><h3>Sem campanhas</h3><p>Cria a primeira campanha.</p></div></td></tr>";

        $html=$this->layout('Campanhas',<<<HTML
<div class="tbar"><div><div class="pg-t">Campanhas</div><div class="pg-s">Execução das campanhas publicitárias</div></div>
<div class="tbar-acts"><button class="btn btn-p" onclick="document.getElementById('m-camp').classList.add('open')"><i class="bi bi-plus-lg"></i> Nova Campanha</button></div></div>
<div class="cnt"><div class="tw"><table>
<thead><tr><th>Campanha</th><th>Estado</th><th>Período</th><th>Progresso</th><th>Faltam</th></tr></thead>
<tbody>{$rows}</tbody></table></div></div>
<div class="mbg" id="m-camp"><div class="mbox" style="max-width:640px">
  <div class="mt">Nova Campanha <button class="mx" onclick="closeM('m-camp')">✕</button></div>
  <form method="POST" action="/public/comercial/{$sid}/campanhas/salvar">
    <div class="fg2">
      <div class="fg" style="grid-column:span 2"><label class="fl">Nome *</label><input type="text" name="nome" class="fi" required placeholder="Ex: Campanha Natal TPA — Dez 2026"></div>
      <div class="fg"><label class="fl">Anunciante *</label><select name="anunciante_id" class="fs" required><option value="">— Seleccionar —</option>{$oA}</select></div>
      <div class="fg"><label class="fl">Contrato Associado</label><select name="contrato_id" class="fs">{$oCt}</select></div>
      <div class="fg"><label class="fl">Data Início *</label><input type="date" name="data_inicio" class="fi" required></div>
      <div class="fg"><label class="fl">Data Fim *</label><input type="date" name="data_fim" class="fi" required></div>
      <div class="fg"><label class="fl">Total Spots</label><input type="number" name="spots_contratados" class="fi" min="0" value="0"></div>
      <div class="fg"><label class="fl">Valor (Kz)</label><input type="number" name="valor_campanha" class="fi" step="0.01" min="0" value="0"></div>
      <div class="fg"><label class="fl">Horário Início</label><input type="time" name="horario_inicio" class="fi"></div>
      <div class="fg"><label class="fl">Horário Fim</label><input type="time" name="horario_fim" class="fi"></div>
      <div class="fg" style="grid-column:span 2"><label class="fl">ID Myriad (opcional)</label><input type="text" name="myriad_campaign_id" class="fi" placeholder="ID da campanha no Myriad Schedule"></div>
    </div>
    <div class="ff2"><button type="button" class="btn btn-s" onclick="closeM('m-camp')">Cancelar</button><button type="submit" class="btn btn-p">Criar Campanha</button></div>
  </form>
</div></div>
<script>function closeM(id){document.getElementById(id).classList.remove('open');}document.getElementById('m-camp').addEventListener('click',e=>{if(e.target===document.getElementById('m-camp'))closeM('m-camp');});</script>
HTML,$sid,'campanhas');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function campanhaSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $d=['station_id'=>$sid,'anunciante_id'=>(int)($b['anunciante_id']??0),'contrato_id'=>(int)($b['contrato_id']??0)?:null,'nome'=>trim($b['nome']??''),'data_inicio'=>$b['data_inicio'],'data_fim'=>$b['data_fim'],'spots_contratados'=>(int)($b['spots_contratados']??0),'valor_campanha'=>(float)($b['valor_campanha']??0),'horario_inicio'=>$b['horario_inicio']?:null,'horario_fim'=>$b['horario_fim']?:null,'myriad_campaign_id'=>trim($b['myriad_campaign_id']??'')?:null,'estado'=>'aguarda','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
        $this->db->insert('rnb_campanhas',$d);
        return $response->withHeader('Location',"/public/comercial/{$sid}/campanhas")->withStatus(302);
    }

    public function pipelineAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        $etapas=['prospecto'=>['Prospectos','binoculars','var(--t3)'],'rascunho'=>['Rascunhos','pencil-square','var(--blue)'],'enviada'=>['Enviadas','send','var(--pu)'],'em_negociacao'=>['Negociação','chat-dots','var(--ac)'],'aceite'=>['Aceites','check-circle','var(--green)']];
        $cols='';
        foreach($etapas as $estado=>[$lbl,$ico,$cor]){
            if($estado==='prospecto') $items=$this->db->fetchAllAssociative("SELECT nome as titulo,cidade as sub,0.0 as valor_final FROM rnb_anunciantes WHERE station_id=? AND estado='prospecto' ORDER BY created_at DESC",[$sid]);
            else $items=$this->db->fetchAllAssociative("SELECT p.titulo,a.nome as sub,p.valor_final FROM rnb_propostas p LEFT JOIN rnb_anunciantes a ON a.id=p.anunciante_id WHERE p.station_id=? AND p.estado=? ORDER BY p.created_at DESC",[$sid,$estado]);
            $tot=array_sum(array_column($items,'valor_final')); $n=count($items);
            $cards=''; foreach($items as $i) $cards.="<div style='background:rgba(255,255,255,.04);border:1px solid var(--br);border-radius:9px;padding:11px;margin-bottom:7px'><div style='font-size:12px;font-weight:700;color:var(--t1);margin-bottom:3px'>".htmlspecialchars($i['titulo'])."</div><div style='font-size:10px;color:var(--t3)'>".htmlspecialchars($i['sub']??'')."</div>".($i['valor_final']>0?"<div style='font-size:11px;font-weight:700;color:{$cor};margin-top:5px'>".$this->fmtKz($i['valor_final'])."</div>":'')."</div>";
            $cols.="<div style='background:rgba(26,26,46,.6);border:1px solid var(--br);border-radius:13px;padding:14px;min-width:200px;flex:1'><div style='display:flex;align-items:center;gap:7px;margin-bottom:10px;padding-bottom:8px;border-bottom:1px solid var(--br)'><i class='bi bi-{$ico}' style='color:{$cor};font-size:15px'></i><span style='font-size:12px;font-weight:700;color:var(--t1)'>{$lbl}</span><span style='margin-left:auto;background:rgba(255,255,255,.06);color:var(--t3);font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px'>{$n}</span></div>".($tot>0?"<div style='font-size:10px;color:{$cor};font-weight:700;margin-bottom:8px'>".$this->fmtKz($tot)."</div>":'')."<div style='overflow-y:auto;max-height:400px'>{$cards}".(!$cards?"<div style='text-align:center;padding:1rem;color:var(--t3);font-size:11px'>Sem itens</div>":'')."</div></div>";
        }
        $html=$this->layout('Pipeline',<<<HTML
<div class="tbar"><div><div class="pg-t">Pipeline Comercial</div><div class="pg-s">Funil de vendas em tempo real</div></div>
<div class="tbar-acts"><a href="/public/comercial/{$sid}/propostas" class="btn btn-p"><i class="bi bi-plus-lg"></i> Nova Proposta</a></div></div>
<div class="cnt"><div style="display:flex;gap:14px;overflow-x:auto;padding-bottom:14px">{$cols}</div></div>
HTML,$sid,'pipeline');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function equipaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        $lista=$this->db->fetchAllAssociative("SELECT * FROM rnb_comerciais WHERE station_id=? ORDER BY nome",[$sid]);
        $rows=''; foreach($lista as $e){
            $cl=$e['tipo_comissao']==='percentagem'?$e['comissao_valor'].'%':$this->fmtKz($e['comissao_valor']);
            $rows.="<tr><td style='font-weight:700'>".htmlspecialchars($e['nome'])."</td><td style='color:var(--t2)'>".htmlspecialchars($e['email']??'—')."</td><td style='color:var(--t2)'>".htmlspecialchars($e['telefone']??'—')."</td><td><span class='badge by'>{$cl}</span></td><td style='color:var(--green);font-weight:700'>".$this->fmtKz($e['meta_mensal'])."</td><td>".($e['ativo']?'<span class="badge bg">Activo</span>':'<span class="badge bd">Inactivo</span>')."</td></tr>";
        }
        if(!$rows) $rows="<tr><td colspan='6'><div class='es'><i class='bi bi-people'></i><h3>Sem equipa</h3><p>Adiciona os comerciais.</p></div></td></tr>";

        $html=$this->layout('Equipa',<<<HTML
<div class="tbar"><div><div class="pg-t">Equipa Comercial</div><div class="pg-s">Gestão de vendedores</div></div>
<div class="tbar-acts"><button class="btn btn-p" onclick="document.getElementById('m-com').classList.add('open')"><i class="bi bi-plus-lg"></i> Novo Comercial</button></div></div>
<div class="cnt"><div class="tw"><table>
<thead><tr><th>Nome</th><th>Email</th><th>Telefone</th><th>Comissão</th><th>Meta Mensal</th><th>Estado</th></tr></thead>
<tbody>{$rows}</tbody></table></div></div>
<div class="mbg" id="m-com"><div class="mbox">
  <div class="mt">Novo Comercial <button class="mx" onclick="closeM('m-com')">✕</button></div>
  <form method="POST" action="/public/comercial/{$sid}/equipa/salvar">
    <div class="fg2">
      <div class="fg" style="grid-column:span 2"><label class="fl">Nome *</label><input type="text" name="nome" class="fi" required></div>
      <div class="fg"><label class="fl">Email</label><input type="email" name="email" class="fi"></div>
      <div class="fg"><label class="fl">Telefone</label><input type="text" name="telefone" class="fi"></div>
      <div class="fg"><label class="fl">Tipo Comissão</label><select name="tipo_comissao" class="fs"><option value="percentagem">Percentagem (%)</option><option value="fixo">Valor Fixo (Kz)</option></select></div>
      <div class="fg"><label class="fl">Valor Comissão</label><input type="number" name="comissao_valor" class="fi" step="0.01" value="10" min="0"></div>
      <div class="fg" style="grid-column:span 2"><label class="fl">Meta Mensal (Kz)</label><input type="number" name="meta_mensal" class="fi" step="0.01" value="0" min="0"></div>
    </div>
    <div class="ff2"><button type="button" class="btn btn-s" onclick="closeM('m-com')">Cancelar</button><button type="submit" class="btn btn-p">Guardar</button></div>
  </form>
</div></div>
<script>function closeM(id){document.getElementById(id).classList.remove('open');}document.getElementById('m-com').addEventListener('click',e=>{if(e.target===document.getElementById('m-com'))closeM('m-com');});</script>
HTML,$sid,'equipa');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function equipaSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $d=['station_id'=>$sid,'nome'=>trim($b['nome']??''),'email'=>trim($b['email']??''),'telefone'=>trim($b['telefone']??''),'tipo_comissao'=>$b['tipo_comissao']??'percentagem','comissao_valor'=>(float)($b['comissao_valor']??10),'meta_mensal'=>(float)($b['meta_mensal']??0),'ativo'=>1,'created_at'=>date('Y-m-d H:i:s')];
        $this->db->insert('rnb_comerciais',$d);
        return $response->withHeader('Location',"/public/comercial/{$sid}/equipa")->withStatus(302);
    }

    public function relatoriosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        try {
            $s=['anunciantes'=>(int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_anunciantes WHERE station_id=?",[$sid]),'contratos'=>(int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_contratos WHERE station_id=?",[$sid]),'campanhas'=>(int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_campanhas WHERE station_id=?",[$sid]),'receita'=>(float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total),0) FROM rnb_contratos WHERE station_id=? AND estado='activo'",[$sid]),'spots'=>(int)$this->db->fetchOne("SELECT COALESCE(SUM(spots_emitidos),0) FROM rnb_campanhas WHERE station_id=?",[$sid])];
            $top=$this->db->fetchAllAssociative("SELECT a.nome,COUNT(c.id) as nc,COALESCE(SUM(c.valor_campanha),0) as tv FROM rnb_anunciantes a LEFT JOIN rnb_campanhas c ON c.anunciante_id=a.id WHERE a.station_id=? GROUP BY a.id,a.nome ORDER BY tv DESC LIMIT 10",[$sid]);
        } catch(\Exception $e){ $s=['anunciantes'=>0,'contratos'=>0,'campanhas'=>0,'receita'=>0,'spots'=>0]; $top=[]; }
        $tr=''; foreach($top as $i=>$a) $tr.="<tr><td style='color:var(--ac);font-weight:700'>".($i+1)."º</td><td style='font-weight:700'>".htmlspecialchars($a['nome'])."</td><td style='text-align:center;color:var(--t2)'>{$a['nc']}</td><td style='color:var(--green);font-weight:700'>".$this->fmtKz($a['tv'])."</td></tr>";
        if(!$tr) $tr="<tr><td colspan='4' style='text-align:center;padding:1.5rem;color:var(--t3)'>Sem dados</td></tr>";

        $html=$this->layout('Relatórios',<<<HTML
<div class="tbar"><div><div class="pg-t">Relatórios</div><div class="pg-s">Visão consolidada do departamento comercial</div></div></div>
<div class="cnt">
<div class="kpis mb24" style="grid-template-columns:repeat(5,1fr)">
  <div class="kpi gold"><div class="kpi-ico" style="background:rgba(245,158,11,.1)">💰</div><div class="kpi-v" style="color:var(--ac);font-size:16px">{$this->fmtKz($s['receita'])}</div><div class="kpi-l">Receita Contratos</div></div>
  <div class="kpi blue"><div class="kpi-ico" style="background:rgba(59,130,246,.1)">🏢</div><div class="kpi-v" style="color:var(--blue)">{$s['anunciantes']}</div><div class="kpi-l">Anunciantes</div></div>
  <div class="kpi gr"><div class="kpi-ico" style="background:rgba(16,185,129,.1)">📋</div><div class="kpi-v" style="color:var(--green)">{$s['contratos']}</div><div class="kpi-l">Contratos</div></div>
  <div class="kpi pu"><div class="kpi-ico" style="background:rgba(139,92,246,.1)">📢</div><div class="kpi-v" style="color:var(--pu)">{$s['campanhas']}</div><div class="kpi-l">Campanhas</div></div>
  <div class="kpi gold"><div class="kpi-ico" style="background:rgba(245,158,11,.1)">🎙</div><div class="kpi-v" style="color:var(--ac)">{$s['spots']}</div><div class="kpi-l">Spots Emitidos</div></div>
</div>
<div class="card"><div class="ct"><i class="bi bi-trophy"></i>Top Anunciantes por Valor</div><div class="tw"><table><thead><tr><th>#</th><th>Anunciante</th><th style="text-align:center">Campanhas</th><th>Valor Total</th></tr></thead><tbody>{$tr}</tbody></table></div></div>
</div>
HTML,$sid,'relatorios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    /* ─── INTEGRAÇÃO COM FINANCE PRO ──────────────────── */

    /**
     * Gera automaticamente conta a receber no Finance Pro
     * quando um contrato é criado/actualizado no Comercial
     */
    public function gerarFaturaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $id  = (int)$params['id'];

        try {
            $contrato = $this->db->fetchAssociative(
                "SELECT c.*, a.nome as anunciante_nome, a.nif, a.email as anunciante_email
                 FROM rnb_contratos c
                 LEFT JOIN rnb_anunciantes a ON a.id = c.anunciante_id
                 WHERE c.id = ? AND c.station_id = ?",
                [$id, $sid]
            );

            if (!$contrato) {
                $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Contrato não encontrado']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Verificar se já existe conta a receber para este contrato
            $jaExiste = $this->db->fetchOne(
                "SELECT COUNT(*) FROM fp_contas_movimento
                 WHERE station_id = ? AND referencia_externa = ? AND tipo = 'receber'",
                [$sid, 'CONT-' . $id]
            );

            if ($jaExiste > 0) {
                $response->getBody()->write(json_encode(['status' => 'exists', 'message' => 'Conta a receber já existe para este contrato']));
                return $response->withHeader('Content-Type', 'application/json');
            }

            // Criar conta a receber no Finance Pro
            $dataVenc = date('Y-m-d', strtotime($contrato['data_fim']));
            $this->db->insert('fp_contas_movimento', [
                'station_id'         => $sid,
                'tipo'               => 'receber',
                'descricao'          => 'Contrato Publicitário: ' . $contrato['titulo'],
                'entidade'           => $contrato['anunciante_nome'],
                'nif_entidade'       => $contrato['nif'] ?? '',
                'valor_total'        => $contrato['valor_total'],
                'valor_pago'         => $contrato['valor_pago'] ?? 0,
                'data_emissao'       => $contrato['data_inicio'],
                'data_vencimento'    => $dataVenc,
                'referencia_externa' => 'CONT-' . $id,
                'estado'             => $contrato['valor_pago'] >= $contrato['valor_total'] ? 'pago' : 'pendente',
                'notas'              => 'Gerado automaticamente pelo RNB Comercial. Contrato #' . ($contrato['numero'] ?? $id),
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);

            // Também criar lançamento no livro contabilístico
            try {
                $this->db->insert('fp_lancamentos', [
                    'station_id'      => $sid,
                    'tipo'            => 'receita',
                    'historico'       => 'Publicidade: ' . $contrato['anunciante_nome'] . ' — ' . $contrato['titulo'],
                    'valor'           => $contrato['valor_total'],
                    'data_lancamento' => $contrato['data_inicio'],
                    'estado'          => 'previsto',
                    'referencia'      => $contrato['numero'] ?? 'CONT-' . $id,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ]);
            } catch (\Exception $e) {
                // Lançamento é secundário — não falhar se fp_lancamentos tiver colunas diferentes
            }

            $response->getBody()->write(json_encode([
                'status'  => 'ok',
                'message' => 'Conta a receber criada no Finance Pro',
                'valor'   => number_format($contrato['valor_total'], 2, ',', '.') . ' Kz',
            ]));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Sincroniza estado de pagamento do Finance Pro para o Comercial
     */
    public function sincronizarFinancasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $id  = (int)$params['id'];

        try {
            // Buscar conta a receber correspondente no Finance Pro
            $conta = $this->db->fetchAssociative(
                "SELECT * FROM fp_contas_movimento
                 WHERE station_id = ? AND referencia_externa = ? AND tipo = 'receber'",
                [$sid, 'CONT-' . $id]
            );

            if (!$conta) {
                $response->getBody()->write(json_encode(['status' => 'not_found', 'message' => 'Sem conta a receber associada. Gera primeiro a factura.']));
                return $response->withHeader('Content-Type', 'application/json');
            }

            // Actualizar valor pago no contrato Comercial
            $this->db->update('rnb_contratos', [
                'valor_pago' => $conta['valor_pago'],
                'estado'     => $conta['estado'] === 'pago' ? 'concluido' : 'activo',
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id, 'station_id' => $sid]);

            $response->getBody()->write(json_encode([
                'status'      => 'ok',
                'valor_pago'  => number_format($conta['valor_pago'], 2, ',', '.') . ' Kz',
                'estado_fp'   => $conta['estado'],
            ]));
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Chamado automaticamente quando contrato é criado
     */
    private function autoGerarContaReceber(int $sid, int $contratoId): void
    {
        try {
            $contrato = $this->db->fetchAssociative(
                "SELECT c.*, a.nome as anunciante_nome, a.nif
                 FROM rnb_contratos c
                 LEFT JOIN rnb_anunciantes a ON a.id = c.anunciante_id
                 WHERE c.id = ? AND c.station_id = ?",
                [$contratoId, $sid]
            );
            if (!$contrato) return;

            $jaExiste = $this->db->fetchOne(
                "SELECT COUNT(*) FROM fp_contas_movimento WHERE station_id=? AND referencia_externa=? AND tipo='receber'",
                [$sid, 'CONT-' . $contratoId]
            );
            if ($jaExiste > 0) return;

            $this->db->insert('fp_contas_movimento', [
                'station_id'         => $sid,
                'tipo'               => 'receber',
                'descricao'          => 'Publicidade: ' . $contrato['titulo'],
                'entidade'           => $contrato['anunciante_nome'],
                'nif_entidade'       => $contrato['nif'] ?? '',
                'valor_total'        => $contrato['valor_total'],
                'valor_pago'         => 0,
                'data_emissao'       => date('Y-m-d'),
                'data_vencimento'    => $contrato['data_fim'],
                'referencia_externa' => 'CONT-' . $contratoId,
                'estado'             => 'pendente',
                'notas'              => 'Auto-gerado pelo RNB Comercial',
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            // Falha silenciosa — não bloquear criação do contrato
        }
    }

}