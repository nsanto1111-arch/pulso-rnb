<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin\Controller;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

class RhController
{
    private Connection $db;
    public function __construct(Connection $db) { $this->db = $db; }

    private function fmtKz(float $v): string { return number_format($v,0,',','.').' Kz'; }

    private function deptLabel(string $d): string {
        return ['locutor'=>'Locutor','jornalismo'=>'Jornalismo','tecnico'=>'Técnico',
                'comercial'=>'Comercial','financeiro'=>'Financeiro','administrativo'=>'Administrativo',
                'direcao'=>'Direcção','producao'=>'Produção'][$d] ?? ucfirst($d);
    }

    private function deptCor(string $d): string {
        return ['locutor'=>'#a78bfa','jornalismo'=>'#f59e0b','tecnico'=>'#06b6d4',
                'comercial'=>'#f59e0b','financeiro'=>'#10b981','administrativo'=>'#3b82f6',
                'direcao'=>'#00e5ff','producao'=>'#f472b6'][$d] ?? '#71717a';
    }

    private function estadoBadge(string $e): string {
        return match($e) {
            'activo'    => '<span class="badge bg">Activo</span>',
            'ferias'    => '<span class="badge bb">Férias</span>',
            'baixa'     => '<span class="badge by">Baixa</span>',
            'suspenso'  => '<span class="badge br">Suspenso</span>',
            'inactivo'  => '<span class="badge bd">Inactivo</span>',
            'processado'=> '<span class="badge bb">Processado</span>',
            'pago'      => '<span class="badge bg">Pago</span>',
            'rascunho'  => '<span class="badge bd">Rascunho</span>',
            'aprovado'  => '<span class="badge bg">Aprovado</span>',
            'pendente'  => '<span class="badge by">Pendente</span>',
            'rejeitado' => '<span class="badge br">Rejeitado</span>',
            default     => '<span class="badge bd">'.ucfirst($e).'</span>',
        };
    }

    private function layout(string $titulo, string $body, int $sid, string $active): string
    {
        $_rnb_sid = $sid; $_rnb_atual = 'rh';
        ob_start(); @require dirname(__DIR__,2).'/public/rnb-nav.php'; $rnbNav = ob_get_clean();

        try {
            $nFunc  = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]);
            $nFerias= (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_rh_ferias WHERE station_id=? AND estado='pendente'",[$sid]);
            $massaS = (float)$this->db->fetchOne("SELECT COALESCE(SUM(salario_base+subsidio_alimentacao+subsidio_transporte+outros_subsidios),0) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]);
        } catch(\Exception $e) { $nFunc=$nFerias=0; $massaS=0; }

        $nav = [
            'index'          => ['speedometer2',   'Dashboard'],
            'funcionarios'   => ['people-fill',    'Equipa'],
            'folha-pagamento'=> ['receipt',        'Folha Salários'],
            'ferias'         => ['umbrella',       'Férias / Faltas'],
            'escalas'        => ['calendar3',      'Escalas'],
            'relatorios'     => ['graph-up-arrow', 'Relatórios'],
        ];
        $navHtml = '';
        foreach($nav as $k=>[$ico,$lbl]) {
            $cls = $k===$active?'on':'';
            $url = $k==='index' ? "/public/rh/{$sid}" : "/public/rh/{$sid}/{$k}";
            $navHtml .= "<a href='{$url}' class='ni {$cls}'><i class='bi bi-{$ico}'></i><span>{$lbl}</span></a>";
        }

        return <<<HTML
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>RNB RH — {$titulo}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg0:#050510;--bg1:#0f0f1f;--bg2:#1a1a2e;--bg3:#252538;
    --ac:#38bdf8;--ac2:#0284c7;
    --green:#10b981;--red:#ef4444;--gold:#f59e0b;--pu:#8b5cf6;--cy:#00e5ff;--pink:#f472b6;
    --t1:#fff;--t2:#a1a1aa;--t3:#71717a;
    --br:rgba(255,255,255,.08);--br2:rgba(255,255,255,.14);
    --ff:'Inter',-apple-system,sans-serif;--tr:all .25s cubic-bezier(.4,0,.2,1);
}
html,body{min-height:100vh;font-family:var(--ff);background:var(--bg0);color:var(--t1);font-size:13px;-webkit-font-smoothing:antialiased}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(circle at 15% 40%,rgba(56,189,248,.05),transparent 50%),radial-gradient(circle at 85% 70%,rgba(139,92,246,.04),transparent 50%);pointer-events:none;z-index:0}
.wrap{display:grid;grid-template-columns:210px 1fr;min-height:100vh;position:relative;z-index:1}
.sb{background:rgba(15,15,31,.95);border-right:1px solid var(--br);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto}
.sb::-webkit-scrollbar{width:3px}.sb::-webkit-scrollbar-thumb{background:var(--bg3)}
.sb-hd{padding:18px 16px 12px;border-bottom:1px solid var(--br)}
.sb-logo{display:flex;align-items:center;gap:9px;margin-bottom:3px}
.sb-ico{width:34px;height:34px;background:linear-gradient(135deg,var(--ac),var(--ac2));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 0 20px rgba(56,189,248,.25)}
.sb-name{font-size:14px;font-weight:900;background:linear-gradient(135deg,var(--ac),#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.sb-sub{font-size:8px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--t3);margin-left:43px}
.sb-stats{display:grid;grid-template-columns:1fr 1fr;gap:5px;padding:10px 12px;border-bottom:1px solid var(--br)}
.sb-st{background:rgba(255,255,255,.03);border:1px solid var(--br);border-radius:7px;padding:7px;text-align:center}
.sb-sv{font-size:17px;font-weight:800;color:var(--ac)}
.sb-sl{font-size:8px;color:var(--t3);text-transform:uppercase;letter-spacing:.4px;margin-top:1px}
.nav{padding:8px;flex:1}
.ni{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:9px;text-decoration:none;color:var(--t2);font-size:12px;font-weight:600;transition:var(--tr);margin-bottom:2px;white-space:nowrap}
.ni:hover{background:rgba(255,255,255,.05);color:var(--t1);text-decoration:none}
.ni.on{background:linear-gradient(135deg,rgba(56,189,248,.14),rgba(2,132,199,.08));color:var(--ac);border:1px solid rgba(56,189,248,.2)}
.ni i{font-size:15px;flex-shrink:0}
.sb-ft{padding:10px 12px;border-top:1px solid var(--br)}
.sb-bk{display:flex;align-items:center;gap:7px;padding:7px 11px;border-radius:8px;text-decoration:none;color:var(--t3);font-size:11px;font-weight:600;transition:var(--tr)}
.sb-bk:hover{background:rgba(255,255,255,.04);color:var(--t2);text-decoration:none}
.main{display:flex;flex-direction:column;min-height:100vh}
.tbar{padding:14px 24px;border-bottom:1px solid var(--br);background:rgba(15,15,31,.7);backdrop-filter:blur(20px);position:sticky;top:0;z-index:100;display:flex;align-items:center;justify-content:space-between;gap:16px}
.pg-t{font-size:20px;font-weight:900}.pg-s{font-size:11px;color:var(--t3);margin-top:2px}
.tbar-acts{display:flex;gap:7px;align-items:center}
.cnt{padding:22px 24px;flex:1}
.card{background:linear-gradient(135deg,rgba(26,26,46,.95),rgba(21,21,32,.95));border:1px solid var(--br);border-radius:14px;padding:18px;position:relative;overflow:hidden;transition:var(--tr)}
.card:hover{border-color:var(--br2)}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;opacity:0;transition:var(--tr)}
.card.cy::before{background:linear-gradient(90deg,var(--ac),var(--ac2))}.card:hover::before{opacity:1}
.ct{font-size:12px;font-weight:700;color:var(--t1);margin-bottom:12px;display:flex;align-items:center;gap:7px}
.ct i{color:var(--ac)}.ct a{margin-left:auto;font-size:10px;font-weight:600;color:var(--t3);text-decoration:none;padding:3px 8px;border-radius:5px;border:1px solid var(--br)}
.ct a:hover{color:var(--t2);background:rgba(255,255,255,.04);text-decoration:none}
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.kpi{background:linear-gradient(135deg,rgba(26,26,46,.95),rgba(21,21,32,.95));border:1px solid var(--br);border-radius:14px;padding:18px;position:relative;overflow:hidden;transition:var(--tr)}
.kpi:hover{transform:translateY(-2px);border-color:var(--br2)}
.kpi::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:14px 14px 0 0}
.kpi.cy::after{background:var(--ac)}.kpi.gr::after{background:var(--green)}.kpi.gd::after{background:var(--gold)}.kpi.pu::after{background:var(--pu)}.kpi.pk::after{background:var(--pink)}
.kpi-ico{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:10px}
.kpi-v{font-size:22px;font-weight:900;line-height:1;margin-bottom:4px}.kpi-l{font-size:9px;font-weight:700;color:var(--t3);text-transform:uppercase;letter-spacing:.6px}
.kpi-s{font-size:10px;color:var(--t3);margin-top:5px}
.tw{overflow-x:auto;border-radius:12px;border:1px solid var(--br)}
table{width:100%;border-collapse:collapse}
thead th{background:rgba(26,26,46,.8);padding:10px 13px;font-size:9px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--t3);text-align:left;border-bottom:1px solid var(--br);white-space:nowrap}
tbody td{padding:11px 13px;border-bottom:1px solid rgba(255,255,255,.04);font-size:12px;vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover td{background:rgba(255,255,255,.02)}
.badge{display:inline-flex;align-items:center;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:700}
.bg{background:rgba(16,185,129,.1);color:var(--green);border:1px solid rgba(16,185,129,.2)}
.by{background:rgba(245,158,11,.1);color:var(--gold);border:1px solid rgba(245,158,11,.2)}
.bb{background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2)}
.br{background:rgba(239,68,68,.1);color:var(--red);border:1px solid rgba(239,68,68,.2)}
.bd{background:rgba(255,255,255,.06);color:var(--t2);border:1px solid var(--br)}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font:700 12px var(--ff);cursor:pointer;transition:var(--tr);border:none;text-decoration:none;white-space:nowrap}
.btn:hover{transform:translateY(-1px);text-decoration:none}
.btn-p{background:linear-gradient(135deg,var(--ac),var(--ac2));color:#000;box-shadow:0 0 20px rgba(56,189,248,.2)}
.btn-p:hover{box-shadow:0 0 28px rgba(56,189,248,.35)}
.btn-s{background:rgba(255,255,255,.05);color:var(--t2);border:1px solid var(--br)}
.btn-s:hover{background:rgba(255,255,255,.08);color:var(--t1)}
.btn-sm{padding:4px 10px;font-size:10px}
.mbg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);backdrop-filter:blur(14px);z-index:1000;align-items:center;justify-content:center;padding:16px}
.mbg.open{display:flex}
.mbox{background:linear-gradient(135deg,rgba(26,26,46,.99),rgba(15,15,31,.99));border:1px solid var(--br2);border-radius:18px;padding:24px;width:100%;max-width:640px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.6)}
.mt{font-size:16px;font-weight:800;display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
.mx{background:rgba(255,255,255,.06);border:1px solid var(--br);color:var(--t2);width:28px;height:28px;border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px}
.fg2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fg{margin-bottom:12px}
.fl{display:block;font-size:9px;font-weight:700;color:var(--t2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
.fi,.fs,.fta{width:100%;padding:9px 12px;background:rgba(255,255,255,.04);border:1px solid var(--br);border-radius:8px;color:var(--t1);font:13px var(--ff);outline:none;transition:var(--tr);color-scheme:dark}
.fi:focus,.fs:focus,.fta:focus{border-color:rgba(56,189,248,.4);background:rgba(255,255,255,.06)}
.fi::placeholder,.fta::placeholder{color:var(--t3)}
.fta{resize:vertical;min-height:72px}
.ff2{display:flex;gap:8px;justify-content:flex-end;margin-top:18px;padding-top:14px;border-top:1px solid var(--br)}
.g2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
.g3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.mb20{margin-bottom:20px}
.es{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:3rem;color:var(--t3);text-align:center;gap:10px}
.es i{font-size:40px;opacity:.1}.es h3{font-size:16px;font-weight:700;color:var(--t2)}
/* FUNC CARD */
.fc{background:linear-gradient(135deg,rgba(26,26,46,.9),rgba(21,21,32,.9));border:1px solid var(--br);border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px;transition:var(--tr)}
.fc:hover{border-color:var(--br2);transform:translateY(-2px)}
.fc-av{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:900;flex-shrink:0}
.fc-nome{font-size:13px;font-weight:800;color:var(--t1)}
.fc-cargo{font-size:11px;color:var(--t2);margin-top:1px}
.fc-dept{font-size:9px;font-weight:700;padding:2px 7px;border-radius:10px;margin-top:4px;display:inline-block}
.fc-sal{font-size:12px;font-weight:700;color:var(--green);margin-left:auto;text-align:right;flex-shrink:0}
.fc-sal-l{font-size:9px;color:var(--t3)}
/* ESCALA */
.esc-slot{display:flex;align-items:center;gap:10px;padding:9px 12px;background:rgba(255,255,255,.03);border:1px solid var(--br);border-radius:9px;margin-bottom:5px}
.esc-hora{font:700 12px 'JetBrains Mono',monospace;color:var(--ac);flex-shrink:0;min-width:100px}
.esc-nome{font-size:12px;font-weight:700;color:var(--t1);flex:1}
.esc-prog{font-size:10px;color:var(--gold)}
@media(max-width:900px){.kpis{grid-template-columns:1fr 1fr}.wrap{grid-template-columns:1fr}.sb{display:none}.g2,.g3{grid-template-columns:1fr}}
</style>
</head><body>
{$rnbNav}
<div class="wrap">
<div class="sb">
  <div class="sb-hd">
    <div class="sb-logo"><div class="sb-ico"><i class="bi bi-people-fill" style="color:#fff;font-size:15px"></i></div><div class="sb-name">RNB RH</div></div>
    <div class="sb-sub">Rádio New Band</div>
  </div>
  <div class="sb-stats">
    <div class="sb-st"><div class="sb-sv">{$nFunc}</div><div class="sb-sl">Activos</div></div>
    <div class="sb-st"><div class="sb-sv" style="color:var(--gold)">{$nFerias}</div><div class="sb-sl">Férias pend.</div></div>
    <div class="sb-st" style="grid-column:span 2"><div class="sb-sv" style="font-size:13px">{$this->fmtKz($massaS)}</div><div class="sb-sl">Massa salarial</div></div>
  </div>
  <nav class="nav">{$navHtml}</nav>
  <div class="sb-ft"><a href="/public/dashboard/{$sid}" class="sb-bk"><i class="bi bi-arrow-left-circle"></i>Dashboard</a></div>
</div>
<div class="main">{$body}</div>
</div>
</body></html>
HTML;
    }

    /* ─── DASHBOARD ──────────────────────────────────────── */
    public function indexAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        try {
            $funcs      = $this->db->fetchAllAssociative("SELECT * FROM rnb_funcionarios WHERE station_id=? AND estado='activo' ORDER BY departamento,nome",[$sid]);
            $massaS     = (float)$this->db->fetchOne("SELECT COALESCE(SUM(salario_base+subsidio_alimentacao+subsidio_transporte+outros_subsidios),0) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]);
            $nFunc      = count($funcs);
            $nFerias    = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_rh_ferias WHERE station_id=? AND estado='pendente'",[$sid]);
            $folhasMes  = $this->db->fetchAllAssociative("SELECT f.*,fn.nome,fn.cargo,fn.departamento FROM rnb_rh_folha_pagamento f JOIN rnb_funcionarios fn ON fn.id=f.funcionario_id WHERE f.station_id=? AND f.mes=? AND f.ano=? ORDER BY fn.nome",[$sid,(int)date('m'),(int)date('Y')]);
            $feriasP    = $this->db->fetchAllAssociative("SELECT f.*,fn.nome,fn.cargo FROM rnb_rh_ferias f JOIN rnb_funcionarios fn ON fn.id=f.funcionario_id WHERE f.station_id=? AND f.estado='pendente' ORDER BY f.data_inicio",[$sid]);
            $escalasHoje= $this->db->fetchAllAssociative("SELECT e.*,fn.nome,fn.cargo FROM rnb_rh_escalas e JOIN rnb_funcionarios fn ON fn.id=e.funcionario_id WHERE e.station_id=? AND e.data=? ORDER BY e.hora_entrada",[$sid,date('Y-m-d')]);
        } catch(\Exception $e) { $funcs=$folhasMes=$feriasP=$escalasHoje=[]; $massaS=$nFunc=$nFerias=0; }

        // Por departamento
        $porDept = [];
        foreach($funcs as $f) $porDept[$f['departamento']][] = $f;

        $deptCards = '';
        foreach($porDept as $dept => $lista) {
            $cor = $this->deptCor($dept);
            $deptCards .= "<div style='background:rgba(255,255,255,.03);border:1px solid var(--br);border-radius:10px;padding:12px;margin-bottom:8px'>
                <div style='display:flex;align-items:center;gap:8px;margin-bottom:8px'>
                    <div style='width:8px;height:8px;border-radius:50%;background:{$cor}'></div>
                    <span style='font-size:11px;font-weight:700;color:{$cor}'>".$this->deptLabel($dept)."</span>
                    <span style='margin-left:auto;font-size:10px;color:var(--t3)'>".count($lista)." pessoas</span>
                </div>";
            foreach($lista as $f) {
                $ini = strtoupper(substr($f['nome'],0,2));
                $deptCards .= "<div style='display:flex;align-items:center;gap:8px;padding:5px 0'>
                    <div style='width:26px;height:26px;border-radius:7px;background:{$cor}18;color:{$cor};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800'>{$ini}</div>
                    <div style='flex:1'><div style='font-size:11px;font-weight:700;color:var(--t1)'>".htmlspecialchars($f['nome'])."</div><div style='font-size:9px;color:var(--t3)'>".htmlspecialchars($f['cargo'])."</div></div>
                    <span style='font-size:10px;font-weight:700;color:var(--green)'>".$this->fmtKz($f['salario_base'])."</span>
                </div>";
            }
            $deptCards .= "</div>";
        }
        if(!$deptCards) $deptCards = "<div class='es'><i class='bi bi-people'></i><h3>Sem funcionários</h3><p>Adiciona a equipa.</p></div>";

        // Férias pendentes
        $feriaRows = '';
        foreach($feriasP as $f) {
            $feriaRows .= "<tr><td style='font-weight:700'>".htmlspecialchars($f['nome'])."</td><td style='color:var(--t2)'>".htmlspecialchars($f['cargo'])."</td><td style='color:var(--t2)'>{$f['data_inicio']} → {$f['data_fim']}</td><td>".$this->estadoBadge($f['estado'])."</td><td><div style='display:flex;gap:4px'><button class='btn btn-s btn-sm' style='color:var(--green)' onclick='aprovarFerias({$f['id']},\"aprovado\")'>✓</button><button class='btn btn-s btn-sm' style='color:var(--red)' onclick='aprovarFerias({$f['id']},\"rejeitado\")'>✕</button></div></td></tr>";
        }
        if(!$feriaRows) $feriaRows = "<tr><td colspan='5' style='text-align:center;padding:1.5rem;color:var(--t3)'>Sem pedidos pendentes</td></tr>";

        // Escalas hoje
        $escRows = '';
        foreach($escalasHoje as $e) {
            $escRows .= "<div class='esc-slot'><div class='esc-hora'>".substr($e['hora_entrada']??'--:--',0,5)." → ".substr($e['hora_saida']??'--:--',0,5)."</div><div style='flex:1'><div class='esc-nome'>".htmlspecialchars($e['nome'])."</div>".($e['programa']?"<div class='esc-prog'>".htmlspecialchars($e['programa'])."</div>":'')."</div>".$this->estadoBadge($e['tipo'])."</div>";
        }
        if(!$escRows) $escRows = "<div class='es' style='padding:1.5rem'><i class='bi bi-calendar3'></i><p>Sem escalas hoje</p></div>";

        $mes = strtr(date('F Y'),['January'=>'Janeiro','February'=>'Fevereiro','March'=>'Março','April'=>'Abril','May'=>'Maio','June'=>'Junho','July'=>'Julho','August'=>'Agosto','September'=>'Setembro','October'=>'Outubro','November'=>'Novembro','December'=>'Dezembro']);

        $nEscalasHoje = count($escalasHoje);
        $nEscalasHoje = count($escalasHoje);
        $html = $this->layout('Dashboard',<<<HTML
<div class="tbar">
    <div><div class="pg-t"><i class="bi bi-people-fill" style="color:var(--ac)"></i> Recursos Humanos</div><div class="pg-s">{$mes}</div></div>
    <div class="tbar-acts">
        <a href="/public/rh/{$sid}/funcionarios" class="btn btn-s"><i class="bi bi-person-plus"></i> Novo Funcionário</a>
        <a href="/public/rh/{$sid}/folha-pagamento" class="btn btn-p"><i class="bi bi-receipt"></i> Folha de Salários</a>
    </div>
</div>
<div class="cnt">
    <div class="kpis mb20">
        <div class="kpi cy"><div class="kpi-ico" style="background:rgba(56,189,248,.1)">👥</div><div class="kpi-v" style="color:var(--ac)">{$nFunc}</div><div class="kpi-l">Funcionários Activos</div></div>
        <div class="kpi gr"><div class="kpi-ico" style="background:rgba(16,185,129,.1)">💰</div><div class="kpi-v" style="color:var(--green);font-size:16px">{$this->fmtKz($massaS)}</div><div class="kpi-l">Massa Salarial</div></div>
        <div class="kpi gd"><div class="kpi-ico" style="background:rgba(245,158,11,.1)">🏖</div><div class="kpi-v" style="color:var(--gold)">{$nFerias}</div><div class="kpi-l">Pedidos Férias Pend.</div></div>
        <div class="kpi pu"><div class="kpi-ico" style="background:rgba(139,92,246,.1)">📅</div><div class="kpi-v" style="color:var(--pu)>{$nEscalasHoje}</div><div class="kpi-l">Em Escala Hoje</div></div>
    </div>
    <div class="g2 mb20">
        <div class="card cy">
            <div class="ct"><i class="bi bi-diagram-3"></i>Equipa por Departamento <a href="/public/rh/{$sid}/funcionarios">Ver todos →</a></div>
            {$deptCards}
        </div>
        <div style="display:flex;flex-direction:column;gap:18px">
            <div class="card cy">
                <div class="ct"><i class="bi bi-calendar3"></i>Escala de Hoje <a href="/public/rh/{$sid}/escalas">Ver →</a></div>
                {$escRows}
            </div>
        </div>
    </div>
    <div class="card cy">
        <div class="ct"><i class="bi bi-umbrella"></i>Pedidos de Férias Pendentes <a href="/public/rh/{$sid}/ferias">Ver todos →</a></div>
        <div class="tw"><table><thead><tr><th>Funcionário</th><th>Cargo</th><th>Período</th><th>Estado</th><th>Acção</th></tr></thead><tbody>{$feriaRows}</tbody></table></div>
    </div>
</div>
<script>
function aprovarFerias(id,estado){
    if(!confirm('Confirmas?')) return;
    fetch('/public/rh/{$sid}/ferias/'+id+'/aprovar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'estado='+estado}).then(()=>location.reload());
}
</script>
HTML,$sid,'index');
        $response->getBody()->write($html); return $response->withHeader('Content-Type','text/html');
    }

    /* ─── FUNCIONÁRIOS ───────────────────────────────────── */
    public function funcionariosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $qp  = $request->getQueryParams();
        $dept = $qp['dept'] ?? '';
        $where = "WHERE station_id=?"; $binds = [$sid];
        if($dept){ $where .= " AND departamento=?"; $binds[] = $dept; }
        $lista = $this->db->fetchAllAssociative("SELECT * FROM rnb_funcionarios {$where} ORDER BY departamento,nome",$binds);

        $depts = $this->db->fetchAllAssociative("SELECT departamento,COUNT(*) as n FROM rnb_funcionarios WHERE station_id=? GROUP BY departamento ORDER BY n DESC",[$sid]);

        $cards = '';
        $cores = ['#fbbf24','#10b981','#00e5ff','#a78bfa','#f472b6','#38bdf8','#fb923c','#34d399'];
        foreach($lista as $i=>$f) {
            $ini = strtoupper(substr($f['nome'],0,2));
            $cor = $cores[$i%count($cores)];
            $deptCor = $this->deptCor($f['departamento']);
            $totalBruto = $f['salario_base']+$f['subsidio_alimentacao']+$f['subsidio_transporte']+$f['outros_subsidios'];
            $cards .= "<div class='fc'>
                <div class='fc-av' style='background:{$cor}18;color:{$cor}'>{$ini}</div>
                <div style='flex:1;min-width:0'>
                    <div class='fc-nome'>".htmlspecialchars($f['nome'])."</div>
                    <div class='fc-cargo'>".htmlspecialchars($f['cargo'])."</div>
                    <span class='fc-dept' style='background:{$deptCor}12;color:{$deptCor};border:1px solid {$deptCor}20'>".$this->deptLabel($f['departamento'])."</span>
                    <div style='display:flex;align-items:center;gap:6px;margin-top:5px;flex-wrap:wrap'>
                        ".$this->estadoBadge($f['estado'])."
                        ".($f['tipo_contrato']?'<span class="badge bd">'.ucfirst(str_replace('_',' ',$f['tipo_contrato'])).'</span>':'')."
                        ".($f['data_admissao']?"<span style='font-size:9px;color:var(--t3)'>Desde ".date('m/Y',strtotime($f['data_admissao']))."</span>":'')."
                    </div>
                </div>
                <div class='fc-sal'>
                    <div>".$this->fmtKz($f['salario_base'])."</div>
                    <div class='fc-sal-l'>base</div>
                    <div style='font-size:10px;color:var(--t3);margin-top:2px'>".$this->fmtKz($totalBruto)." total</div>
                    <div style='display:flex;gap:3px;margin-top:6px'>
                        <button class='btn btn-s btn-sm' onclick='editFuncionario(".json_encode($f).")'>✏</button>
                        <a href='/public/rh/{$sid}/folha-pagamento?func={$f['id']}' class='btn btn-s btn-sm'>📋</a>
                    </div>
                </div>
            </div>";
        }
        if(!$cards) $cards = "<div class='es'><i class='bi bi-people'></i><h3>Sem funcionários</h3><p>Adiciona o primeiro membro da equipa.</p></div>";

        $filtros = "<div style='display:flex;gap:6px;flex-wrap:wrap;margin-bottom:18px'><a href='/public/rh/{$sid}/funcionarios' class='btn btn-sm ".(!$dept?'btn-p':'btn-s')."'>Todos</a>";
        foreach($depts as $d) {
            $cls = $dept===$d['departamento']?'btn-p':'btn-s';
            $filtros .= "<a href='?dept={$d['departamento']}' class='btn btn-sm {$cls}'>".$this->deptLabel($d['departamento'])." ({$d['n']})</a>";
        }
        $filtros .= "</div>";

        $html = $this->layout('Equipa',<<<HTML
<div class="tbar">
    <div><div class="pg-t">Equipa</div><div class="pg-s">".count($lista)." funcionários</div></div>
    <div class="tbar-acts">
        <button class="btn btn-p" onclick="document.getElementById('m-func').classList.add('open')"><i class="bi bi-person-plus"></i> Novo Funcionário</button>
    </div>
</div>
<div class="cnt">
    {$filtros}
    <div style="display:flex;flex-direction:column;gap:10px">{$cards}</div>
</div>

<div class="mbg" id="m-func">
<div class="mbox">
    <div class="mt"><span id="m-func-t">Novo Funcionário</span><button class="mx" onclick="closeM('m-func')">✕</button></div>
    <form method="POST" action="/public/rh/{$sid}/funcionarios/salvar" id="f-func">
        <input type="hidden" name="id" id="func-id">
        <div class="fg2">
            <div class="fg"><label class="fl">Nome *</label><input type="text" name="nome" id="func-nome" class="fi" required></div>
            <div class="fg"><label class="fl">Cargo *</label><input type="text" name="cargo" id="func-cargo" class="fi" required placeholder="Ex: Locutor Principal"></div>
            <div class="fg"><label class="fl">Departamento</label>
                <select name="departamento" id="func-dept" class="fs">
                    <option value="locutor">Locutor</option><option value="jornalismo">Jornalismo</option>
                    <option value="tecnico">Técnico</option><option value="comercial">Comercial</option>
                    <option value="financeiro">Financeiro</option><option value="administrativo">Administrativo</option>
                    <option value="direcao">Direcção</option><option value="producao">Produção</option>
                </select>
            </div>
            <div class="fg"><label class="fl">Tipo Contrato</label>
                <select name="tipo_contrato" id="func-contrato" class="fs">
                    <option value="efectivo">Efectivo</option><option value="temporario">Temporário</option>
                    <option value="estagiario">Estagiário</option><option value="freelance">Freelance</option>
                </select>
            </div>
            <div class="fg"><label class="fl">Data Admissão</label><input type="date" name="data_admissao" id="func-admissao" class="fi"></div>
            <div class="fg"><label class="fl">Data Nascimento</label><input type="date" name="data_nascimento" id="func-nasc" class="fi"></div>
            <div class="fg"><label class="fl">Email</label><input type="email" name="email" id="func-email" class="fi"></div>
            <div class="fg"><label class="fl">Telefone</label><input type="text" name="telefone" id="func-tel" class="fi"></div>
            <div class="fg"><label class="fl">Salário Base (Kz)</label><input type="number" name="salario_base" id="func-sal" class="fi" step="0.01" min="0" value="0" oninput="calcTotal()"></div>
            <div class="fg"><label class="fl">Sub. Alimentação (Kz)</label><input type="number" name="subsidio_alimentacao" id="func-sa" class="fi" step="0.01" min="0" value="0" oninput="calcTotal()"></div>
            <div class="fg"><label class="fl">Sub. Transporte (Kz)</label><input type="number" name="subsidio_transporte" id="func-st" class="fi" step="0.01" min="0" value="0" oninput="calcTotal()"></div>
            <div class="fg"><label class="fl">Outros Subsídios (Kz)</label><input type="number" name="outros_subsidios" id="func-so" class="fi" step="0.01" min="0" value="0" oninput="calcTotal()"></div>
            <div class="fg"><label class="fl">Total Bruto</label><input type="text" id="func-total" class="fi" readonly style="color:var(--green);font-weight:700"></div>
            <div class="fg"><label class="fl">Banco</label><input type="text" name="banco" id="func-banco" class="fi" placeholder="Ex: BFA, BPC..."></div>
            <div class="fg" style="grid-column:span 2"><label class="fl">IBAN</label><input type="text" name="iban" id="func-iban" class="fi"></div>
            <div class="fg" style="grid-column:span 2"><label class="fl">NIF</label><input type="text" name="nif" id="func-nif" class="fi"></div>
        </div>
        <div class="ff2">
            <button type="button" class="btn btn-s" onclick="closeM('m-func')">Cancelar</button>
            <button type="submit" class="btn btn-p">Guardar</button>
        </div>
    </form>
</div>
</div>

<script>
function closeM(id){ document.getElementById(id).classList.remove('open'); }
document.getElementById('m-func').addEventListener('click',e=>{ if(e.target===document.getElementById('m-func')) closeM('m-func'); });
function calcTotal(){
    const s=parseFloat(document.getElementById('func-sal').value)||0;
    const a=parseFloat(document.getElementById('func-sa').value)||0;
    const t=parseFloat(document.getElementById('func-st').value)||0;
    const o=parseFloat(document.getElementById('func-so').value)||0;
    document.getElementById('func-total').value=(s+a+t+o).toLocaleString('pt-AO',{minimumFractionDigits:2})+' Kz';
}
function editFuncionario(f){
    document.getElementById('m-func-t').textContent='Editar Funcionário';
    const m={'id':'id','nome':'nome','cargo':'cargo','departamento':'dept','tipo_contrato':'contrato','data_admissao':'admissao','data_nascimento':'nasc','email':'email','telefone':'tel','salario_base':'sal','subsidio_alimentacao':'sa','subsidio_transporte':'st','outros_subsidios':'so','banco':'banco','iban':'iban','nif':'nif'};
    for(const[k,v] of Object.entries(m)){ const el=document.getElementById('func-'+v); if(el&&f[k]!==undefined) el.value=f[k]||''; }
    calcTotal();
    document.getElementById('m-func').classList.add('open');
}
</script>
HTML,$sid,'funcionarios');
        $response->getBody()->write($html); return $response->withHeader('Content-Type','text/html');
    }

    /* ─── SALVAR FUNCIONÁRIO ─────────────────────────────── */
    public function funcionarioSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody(); $id=(int)($b['id']??0);
        $d=['station_id'=>$sid,'nome'=>trim($b['nome']??''),'cargo'=>trim($b['cargo']??''),'departamento'=>$b['departamento']??'administrativo','tipo_contrato'=>$b['tipo_contrato']??'efectivo','data_admissao'=>$b['data_admissao']?:null,'data_nascimento'=>$b['data_nascimento']?:null,'email'=>trim($b['email']??''),'telefone'=>trim($b['telefone']??''),'salario_base'=>(float)($b['salario_base']??0),'subsidio_alimentacao'=>(float)($b['subsidio_alimentacao']??0),'subsidio_transporte'=>(float)($b['subsidio_transporte']??0),'outros_subsidios'=>(float)($b['outros_subsidios']??0),'banco'=>trim($b['banco']??''),'iban'=>trim($b['iban']??''),'nif'=>trim($b['nif']??''),'updated_at'=>date('Y-m-d H:i:s')];
        if($id>0){$this->db->update('rnb_funcionarios',$d,['id'=>$id]);}else{$d['created_at']=date('Y-m-d H:i:s');$this->db->insert('rnb_funcionarios',$d);}
        return $response->withHeader('Location',"/public/rh/{$sid}/funcionarios")->withStatus(302);
    }

    /* ─── FOLHA DE PAGAMENTO ─────────────────────────────── */
    public function folhaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        $qp=$request->getQueryParams();
        $mes=(int)($qp['mes']??date('m'));
        $ano=(int)($qp['ano']??date('Y'));

        $funcs = $this->db->fetchAllAssociative("SELECT * FROM rnb_funcionarios WHERE station_id=? AND estado='activo' ORDER BY nome",[$sid]);
        $folhas = $this->db->fetchAllAssociative("SELECT * FROM rnb_rh_folha_pagamento WHERE station_id=? AND mes=? AND ano=?",[$sid,$mes,$ano]);
        $folhaMap = []; foreach($folhas as $f) $folhaMap[$f['funcionario_id']] = $f;

        $meses=['01'=>'Janeiro','02'=>'Fevereiro','03'=>'Março','04'=>'Abril','05'=>'Maio','06'=>'Junho','07'=>'Julho','08'=>'Agosto','09'=>'Setembro','10'=>'Outubro','11'=>'Novembro','12'=>'Dezembro'];
        $mesLabel = $meses[str_pad((string)$mes,2,'0',STR_PAD_LEFT)]??$mes;

        $rows=''; $totalBruto=0; $totalLiquido=0;
        foreach($funcs as $f) {
            $fp = $folhaMap[$f['id']] ?? null;
            $bruto = $fp ? $fp['total_bruto'] : ($f['salario_base']+$f['subsidio_alimentacao']+$f['subsidio_transporte']+$f['outros_subsidios']);
            $liq   = $fp ? $fp['total_liquido'] : 0;
            $totalBruto += $bruto; $totalLiquido += $liq;
            $rows .= "<tr>
                <td><div style='font-weight:700'>".htmlspecialchars($f['nome'])."</div><div style='font-size:9px;color:var(--t3)'>".$this->deptLabel($f['departamento'])."</div></td>
                <td style='color:var(--t2)'>".$this->fmtKz($f['salario_base'])."</td>
                <td style='color:var(--t2)'>".$this->fmtKz($f['subsidio_alimentacao']+$f['subsidio_transporte']+$f['outros_subsidios'])."</td>
                <td style='font-weight:700'>".$this->fmtKz($bruto)."</td>
                <td style='color:var(--red)'>".($fp?$this->fmtKz($fp['irt']+$fp['seguranca_social']):'—')."</td>
                <td style='color:var(--green);font-weight:700'>".($fp?$this->fmtKz($liq):'—')."</td>
                <td>".($fp?$this->estadoBadge($fp['estado']):'<span class="badge bd">Por processar</span>')."</td>
                <td>".($fp?($fp['estado']==='processado'?"<button class='btn btn-s btn-sm' style='color:var(--green)' onclick='pagarFolha({$fp['id']})'>💳 Pagar</button>":""):"<button class='btn btn-s btn-sm' onclick='processar({$f['id']})'>⚙ Processar</button>")."</td>
            </tr>";
        }
        if(!$rows) $rows="<tr><td colspan='8'><div class='es'><i class='bi bi-receipt'></i><h3>Sem funcionários activos</h3></div></td></tr>";

        // Selector mês/ano
        $opsMes=''; foreach($meses as $k=>$v) $opsMes.="<option value='{$k}' ".((int)$k===$mes?'selected':'').">{$v}</option>";
        $opsAno=''; for($y=date('Y');$y>=2024;$y--) $opsAno.="<option value='{$y}' ".($y===$ano?'selected':'').">{$y}</option>";

        $html=$this->layout('Folha de Salários',<<<HTML
<div class="tbar">
    <div><div class="pg-t">Folha de Salários</div><div class="pg-s">{$mesLabel} {$ano}</div></div>
    <div class="tbar-acts">
        <form method="GET" style="display:flex;gap:6px">
            <select name="mes" class="fs" style="height:34px" onchange="this.form.submit()">{$opsMes}</select>
            <select name="ano" class="fs" style="height:34px" onchange="this.form.submit()">{$opsAno}</select>
        </form>
        <button class="btn btn-p" onclick="processarTodos()"><i class="bi bi-gear"></i> Processar Todos</button>
    </div>
</div>
<div class="cnt">
    <div class="g3 mb20">
        <div class="kpi gd"><div class="kpi-ico" style="background:rgba(245,158,11,.1)">💰</div><div class="kpi-v" style="color:var(--gold);font-size:16px">{$this->fmtKz($totalBruto)}</div><div class="kpi-l">Total Bruto</div></div>
        <div class="kpi gr"><div class="kpi-ico" style="background:rgba(16,185,129,.1)">💳</div><div class="kpi-v" style="color:var(--green);font-size:16px">{$this->fmtKz($totalLiquido)}</div><div class="kpi-l">Total Líquido</div></div>
        <div class="kpi cy"><div class="kpi-ico" style="background:rgba(56,189,248,.1)">👥</div><div class="kpi-v" style="color:var(--ac)">".count($funcs)."</div><div class="kpi-l">Funcionários</div></div>
    </div>
    <div class="tw">
        <table>
            <thead><tr><th>Funcionário</th><th>Salário Base</th><th>Subsídios</th><th>Total Bruto</th><th>Descontos</th><th>Líquido</th><th>Estado</th><th>Acção</th></tr></thead>
            <tbody>{$rows}</tbody>
        </table>
    </div>
</div>
<script>
function processar(funcId){
    fetch('/public/rh/{$sid}/folha-pagamento/processar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'funcionario_id='+funcId+'&mes={$mes}&ano={$ano}'}).then(()=>location.reload());
}
function processarTodos(){
    if(!confirm('Processar folha de salários para todos os funcionários?')) return;
    const funcs=[...document.querySelectorAll('button[onclick*="processar("]')];
    if(!funcs.length){ alert('Todos já processados!'); return; }
    Promise.all(funcs.map(b=>{ const id=b.getAttribute('onclick').match(/\d+/)[0]; return fetch('/public/rh/{$sid}/folha-pagamento/processar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'funcionario_id='+id+'&mes={$mes}&ano={$ano}'}); })).then(()=>location.reload());
}
function pagarFolha(id){
    fetch('/public/rh/{$sid}/folha-pagamento/'+id+'/pagar',{method:'POST'}).then(()=>location.reload());
}
</script>
HTML,$sid,'folha-pagamento');
        $response->getBody()->write($html); return $response->withHeader('Content-Type','text/html');
    }

    /* ─── PROCESSAR FOLHA ────────────────────────────────── */
    public function folhaProcessarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $funcId=(int)($b['funcionario_id']??0);
        $mes=(int)($b['mes']??date('m'));
        $ano=(int)($b['ano']??date('Y'));

        $f=$this->db->fetchAssociative("SELECT * FROM rnb_funcionarios WHERE id=? AND station_id=?",[$funcId,$sid]);
        if(!$f){ $response->getBody()->write('{"status":"error"}'); return $response->withHeader('Content-Type','application/json'); }

        $bruto=$f['salario_base']+$f['subsidio_alimentacao']+$f['subsidio_transporte']+$f['outros_subsidios'];
        // IRT simplificado Angola
        $irt=0;
        if($f['salario_base']>70000) $irt=($f['salario_base']-70000)*0.10;
        elseif($f['salario_base']>35000) $irt=($f['salario_base']-35000)*0.07;
        $ss=$f['salario_base']*0.03; // Segurança social 3%
        $liq=$bruto-$irt-$ss;

        try {
            $existe=$this->db->fetchOne("SELECT id FROM rnb_rh_folha_pagamento WHERE funcionario_id=? AND mes=? AND ano=?",[$funcId,$mes,$ano]);
            $data=['total_bruto'=>$bruto,'irt'=>$irt,'seguranca_social'=>$ss,'total_liquido'=>$liq,'salario_base'=>$f['salario_base'],'subsidios'=>$f['subsidio_alimentacao']+$f['subsidio_transporte']+$f['outros_subsidios'],'estado'=>'processado'];
            if($existe) $this->db->update('rnb_rh_folha_pagamento',$data,['funcionario_id'=>$funcId,'mes'=>$mes,'ano'=>$ano]);
            else { $data=array_merge($data,['station_id'=>$sid,'funcionario_id'=>$funcId,'mes'=>$mes,'ano'=>$ano,'created_at'=>date('Y-m-d H:i:s')]); $this->db->insert('rnb_rh_folha_pagamento',$data); }
            // Integrar com Finance Pro
            $this->db->insert('fp_contas_movimento',['station_id'=>$sid,'tipo'=>'pagar','descricao'=>'Salário: '.($f['nome']).' — '.str_pad($mes,2,'0',STR_PAD_LEFT).'/'.$ano,'entidade'=>$f['nome'],'valor_total'=>$liq,'valor_pago'=>0,'data_emissao'=>date('Y-m-d'),'data_vencimento'=>date('Y-m-'.date('t')),'referencia_externa'=>'SAL-'.$funcId.'-'.$ano.'-'.$mes,'estado'=>'pendente','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
        } catch(\Exception $e) {}
        $response->getBody()->write('{"status":"ok","liquido":'.$liq.'}'); return $response->withHeader('Content-Type','application/json');
    }

    /* ─── PAGAR FOLHA ────────────────────────────────────── */
    public function folhaPagarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $id=(int)$params['id'];
        $this->db->update('rnb_rh_folha_pagamento',['estado'=>'pago','data_pagamento'=>date('Y-m-d')],['id'=>$id,'station_id'=>$sid]);
        try { $this->db->update('fp_contas_movimento',['estado'=>'pago','valor_pago'=>$this->db->fetchOne("SELECT total_liquido FROM rnb_rh_folha_pagamento WHERE id=?",[$id]),'updated_at'=>date('Y-m-d H:i:s')],['referencia_externa'=>'SAL-'.(string)$id]); } catch(\Exception $e){}
        $response->getBody()->write('{"status":"ok"}'); return $response->withHeader('Content-Type','application/json');
    }

    /* ─── FÉRIAS ─────────────────────────────────────────── */
    public function feriasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        $lista=$this->db->fetchAllAssociative("SELECT f.*,fn.nome,fn.cargo,fn.departamento FROM rnb_rh_ferias f JOIN rnb_funcionarios fn ON fn.id=f.funcionario_id WHERE f.station_id=? ORDER BY f.estado='pendente' DESC,f.data_inicio ASC",[$sid]);
        $funcs=$this->db->fetchAllAssociative("SELECT id,nome FROM rnb_funcionarios WHERE station_id=? AND estado='activo' ORDER BY nome",[$sid]);
        $oF=''; foreach($funcs as $f) $oF.="<option value='{$f['id']}'>".htmlspecialchars($f['nome'])."</option>";

        $rows=''; foreach($lista as $f) {
            $dias=max(0,(int)((strtotime($f['data_fim'])-strtotime($f['data_inicio']))/86400)+1);
            $rows.="<tr><td style='font-weight:700'>".htmlspecialchars($f['nome'])."</td><td style='color:var(--t2)'>".$this->deptLabel($f['departamento'])."</td><td style='color:var(--t2)'>{$f['data_inicio']}</td><td style='color:var(--t2)'>{$f['data_fim']}</td><td style='text-align:center'>{$dias}</td><td>".$this->estadoBadge($f['estado'])."</td><td>".($f['estado']==='pendente'?"<div style='display:flex;gap:3px'><button class='btn btn-s btn-sm' style='color:var(--green)' onclick='aprovar({$f['id']},\"aprovado\")'>✓</button><button class='btn btn-s btn-sm' style='color:var(--red)' onclick='aprovar({$f['id']},\"rejeitado\")'>✕</button></div>":'')."</td></tr>";
        }
        if(!$rows) $rows="<tr><td colspan='7'><div class='es'><i class='bi bi-umbrella'></i><h3>Sem pedidos</h3></div></td></tr>";

        $html=$this->layout('Férias e Faltas',<<<HTML
<div class="tbar">
    <div><div class="pg-t">Férias e Ausências</div></div>
    <div class="tbar-acts"><button class="btn btn-p" onclick="document.getElementById('m-ferias').classList.add('open')"><i class="bi bi-plus-lg"></i> Novo Pedido</button></div>
</div>
<div class="cnt">
    <div class="tw"><table>
        <thead><tr><th>Funcionário</th><th>Departamento</th><th>Início</th><th>Fim</th><th style="text-align:center">Dias</th><th>Estado</th><th>Acção</th></tr></thead>
        <tbody>{$rows}</tbody>
    </table></div>
</div>
<div class="mbg" id="m-ferias"><div class="mbox">
    <div class="mt">Novo Pedido <button class="mx" onclick="closeM('m-ferias')">✕</button></div>
    <form method="POST" action="/public/rh/{$sid}/ferias/salvar">
        <div class="fg2">
            <div class="fg" style="grid-column:span 2"><label class="fl">Funcionário *</label><select name="funcionario_id" class="fs" required><option value="">— Seleccionar —</option>{$oF}</select></div>
            <div class="fg"><label class="fl">Tipo</label><select name="tipo" class="fs"><option value="ferias">Férias</option><option value="licenca">Licença</option><option value="baixa_medica">Baixa Médica</option><option value="ausencia_justificada">Ausência Justificada</option></select></div>
            <div class="fg"><label class="fl">Estado</label><select name="estado" class="fs"><option value="pendente">Pendente</option><option value="aprovado">Aprovado</option></select></div>
            <div class="fg"><label class="fl">Data Início *</label><input type="date" name="data_inicio" class="fi" required></div>
            <div class="fg"><label class="fl">Data Fim *</label><input type="date" name="data_fim" class="fi" required></div>
            <div class="fg" style="grid-column:span 2"><label class="fl">Notas</label><textarea name="notas" class="fta" style="min-height:60px"></textarea></div>
        </div>
        <div class="ff2"><button type="button" class="btn btn-s" onclick="closeM('m-ferias')">Cancelar</button><button type="submit" class="btn btn-p">Guardar</button></div>
    </form>
</div></div>
<script>
function closeM(id){document.getElementById(id).classList.remove('open');}
document.getElementById('m-ferias').addEventListener('click',e=>{if(e.target===document.getElementById('m-ferias'))closeM('m-ferias');});
function aprovar(id,estado){
    if(!confirm('Confirmas?'))return;
    fetch('/public/rh/{$sid}/ferias/'+id+'/aprovar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'estado='+estado}).then(()=>location.reload());
}
</script>
HTML,$sid,'ferias');
        $response->getBody()->write($html); return $response->withHeader('Content-Type','text/html');
    }

    /* ─── SALVAR FÉRIAS ──────────────────────────────────── */
    public function feriasSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $ini=strtotime($b['data_inicio']??'now'); $fim=strtotime($b['data_fim']??'now');
        $dias=max(0,(int)(($fim-$ini)/86400)+1);
        $this->db->insert('rnb_rh_ferias',['station_id'=>$sid,'funcionario_id'=>(int)($b['funcionario_id']??0),'data_inicio'=>$b['data_inicio'],'data_fim'=>$b['data_fim'],'dias_uteis'=>$dias,'tipo'=>$b['tipo']??'ferias','estado'=>$b['estado']??'pendente','notas'=>trim($b['notas']??''),'created_at'=>date('Y-m-d H:i:s')]);
        return $response->withHeader('Location',"/public/rh/{$sid}/ferias")->withStatus(302);
    }

    /* ─── APROVAR FÉRIAS ─────────────────────────────────── */
    public function feriasAprovarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $id=(int)$params['id']; $b=$request->getParsedBody();
        $e=$b['estado']??'aprovado';
        if(in_array($e,['aprovado','rejeitado','cancelado'])) $this->db->update('rnb_rh_ferias',['estado'=>$e,'aprovado_por'=>'Admin'],['id'=>$id,'station_id'=>$sid]);
        $response->getBody()->write('{"status":"ok"}'); return $response->withHeader('Content-Type','application/json');
    }

    /* ─── ESCALAS ────────────────────────────────────────── */
    public function escalasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        $qp=$request->getQueryParams();
        $semana=$qp['semana']??date('Y-m-d');
        $inicio=date('Y-m-d',strtotime('monday this week',strtotime($semana)));
        $fim=date('Y-m-d',strtotime('sunday this week',strtotime($semana)));

        $escalas=$this->db->fetchAllAssociative("SELECT e.*,fn.nome,fn.departamento FROM rnb_rh_escalas e JOIN rnb_funcionarios fn ON fn.id=e.funcionario_id WHERE e.station_id=? AND e.data BETWEEN ? AND ? ORDER BY e.data,e.hora_entrada",[$sid,$inicio,$fim]);
        $funcs=$this->db->fetchAllAssociative("SELECT id,nome FROM rnb_funcionarios WHERE station_id=? AND estado='activo' ORDER BY nome",[$sid]);

        $dias=['Monday'=>'Segunda','Tuesday'=>'Terça','Wednesday'=>'Quarta','Thursday'=>'Quinta','Friday'=>'Sexta','Saturday'=>'Sábado','Sunday'=>'Domingo'];
        $porDia=[];
        foreach($escalas as $e) { $porDia[$e['data']][] = $e; }

        $grelha='';
        for($d=0;$d<7;$d++){
            $data=date('Y-m-d',strtotime($inicio.'+'.($d).' days'));
            $nomeDia=$dias[date('l',strtotime($data))]??date('l',strtotime($data));
            $isHoje=$data===date('Y-m-d');
            $slots=$porDia[$data]??[];
            $slotHtml='';
            foreach($slots as $s) {
                $cor=$this->deptCor($s['departamento']);
                $slotHtml.="<div style='display:flex;align-items:center;gap:7px;padding:6px 9px;background:{$cor}0d;border:1px solid {$cor}20;border-radius:7px;margin-bottom:4px'>
                    <div style='width:4px;height:28px;background:{$cor};border-radius:2px;flex-shrink:0'></div>
                    <div><div style='font-size:11px;font-weight:700;color:var(--t1)'>".htmlspecialchars($s['nome'])."</div><div style='font-size:9px;color:var(--t3)'>".substr($s['hora_entrada']??'',0,5)." → ".substr($s['hora_saida']??'',0,5).($s['programa']?" · ".htmlspecialchars($s['programa']):'')."</div></div>
                </div>";
            }
            $grelha.="<div style='background:rgba(255,255,255,.03);border:1px solid ".($isHoje?'rgba(56,189,248,.3)':'var(--br)')."'.;border-radius:12px;padding:12px;".($isHoje?'background:rgba(56,189,248,.04)':'')."'>
                <div style='display:flex;align-items:center;justify-content:space-between;margin-bottom:10px'>
                    <div>
                        <div style='font-size:11px;font-weight:800;color:".($isHoje?'var(--ac)':'var(--t1)')."'>{$nomeDia}</div>
                        <div style='font-size:9px;color:var(--t3)'>".date('d/m',strtotime($data))."</div>
                    </div>
                    ".($isHoje?'<span class="badge bb">Hoje</span>':'')."
                </div>
                ".($slotHtml?:'<div style="text-align:center;padding:.5rem;color:var(--t3);font-size:10px">Folga</div>')."
            </div>";
        }

        $oF=''; foreach($funcs as $f) $oF.="<option value='{$f['id']}'>".htmlspecialchars($f['nome'])."</option>";
        $semAnt=date('Y-m-d',strtotime($inicio.'-7 days'));
        $semProx=date('Y-m-d',strtotime($inicio.'+7 days'));

        $html=$this->layout('Escalas',<<<HTML
<div class="tbar">
    <div><div class="pg-t">Escalas de Trabalho</div><div class="pg-s">Semana de {$inicio} a {$fim}</div></div>
    <div class="tbar-acts">
        <a href="?semana={$semAnt}" class="btn btn-s"><i class="bi bi-chevron-left"></i></a>
        <a href="?semana={$semProx}" class="btn btn-s"><i class="bi bi-chevron-right"></i></a>
        <button class="btn btn-p" onclick="document.getElementById('m-escala').classList.add('open')"><i class="bi bi-plus-lg"></i> Adicionar</button>
    </div>
</div>
<div class="cnt">
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:10px">{$grelha}</div>
</div>
<div class="mbg" id="m-escala"><div class="mbox">
    <div class="mt">Adicionar à Escala <button class="mx" onclick="closeM('m-escala')">✕</button></div>
    <form method="POST" action="/public/rh/{$sid}/escalas/salvar">
        <div class="fg2">
            <div class="fg" style="grid-column:span 2"><label class="fl">Funcionário *</label><select name="funcionario_id" class="fs" required><option value="">— Seleccionar —</option>{$oF}</select></div>
            <div class="fg"><label class="fl">Data *</label><input type="date" name="data" class="fi" required value="{$inicio}"></div>
            <div class="fg"><label class="fl">Tipo</label><select name="tipo" class="fs"><option value="normal">Normal</option><option value="extra">Extra</option><option value="folga">Folga</option></select></div>
            <div class="fg"><label class="fl">Hora Entrada</label><input type="time" name="hora_entrada" class="fi" value="08:00"></div>
            <div class="fg"><label class="fl">Hora Saída</label><input type="time" name="hora_saida" class="fi" value="17:00"></div>
            <div class="fg" style="grid-column:span 2"><label class="fl">Programa</label><input type="text" name="programa" class="fi" placeholder="Ex: Manhã RNB"></div>
        </div>
        <div class="ff2"><button type="button" class="btn btn-s" onclick="closeM('m-escala')">Cancelar</button><button type="submit" class="btn btn-p">Guardar</button></div>
    </form>
</div></div>
<script>
function closeM(id){document.getElementById(id).classList.remove('open');}
document.getElementById('m-escala').addEventListener('click',e=>{if(e.target===document.getElementById('m-escala'))closeM('m-escala');});
</script>
HTML,$sid,'escalas');
        $response->getBody()->write($html); return $response->withHeader('Content-Type','text/html');
    }

    /* ─── SALVAR ESCALA ──────────────────────────────────── */
    public function escalaSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $this->db->insert('rnb_rh_escalas',['station_id'=>$sid,'funcionario_id'=>(int)($b['funcionario_id']??0),'data'=>$b['data'],'hora_entrada'=>$b['hora_entrada']?:null,'hora_saida'=>$b['hora_saida']?:null,'tipo'=>$b['tipo']??'normal','programa'=>trim($b['programa']??''),'created_at'=>date('Y-m-d H:i:s')]);
        return $response->withHeader('Location',"/public/rh/{$sid}/escalas")->withStatus(302);
    }

    /* ─── RELATÓRIOS ─────────────────────────────────────── */
    public function relatoriosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id'];
        try {
            $s=[
                'total'       => (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_funcionarios WHERE station_id=?",[$sid]),
                'activos'     => (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]),
                'massa_bruta' => (float)$this->db->fetchOne("SELECT COALESCE(SUM(salario_base+subsidio_alimentacao+subsidio_transporte+outros_subsidios),0) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]),
                'media_sal'   => (float)$this->db->fetchOne("SELECT COALESCE(AVG(salario_base),0) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]),
                'pago_mes'    => (float)$this->db->fetchOne("SELECT COALESCE(SUM(total_liquido),0) FROM rnb_rh_folha_pagamento WHERE station_id=? AND mes=? AND ano=? AND estado='pago'",[$sid,(int)date('m'),(int)date('Y')]),
                'ferias_ano'  => (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_rh_ferias WHERE station_id=? AND YEAR(created_at)=?",[$sid,(int)date('Y')]),
            ];
            $porDept=$this->db->fetchAllAssociative("SELECT departamento,COUNT(*) as n,SUM(salario_base) as massa FROM rnb_funcionarios WHERE station_id=? AND estado='activo' GROUP BY departamento ORDER BY massa DESC",[$sid]);
        } catch(\Exception $e){ $s=['total'=>0,'activos'=>0,'massa_bruta'=>0,'media_sal'=>0,'pago_mes'=>0,'ferias_ano'=>0]; $porDept=[]; }

        $dRows=''; foreach($porDept as $i=>$d) {
            $cor=$this->deptCor($d['departamento']);
            $pct=$s['massa_bruta']>0?round($d['massa']/$s['massa_bruta']*100):0;
            $dRows.="<tr>
                <td><div style='display:flex;align-items:center;gap:7px'><div style='width:8px;height:8px;border-radius:50%;background:{$cor}'></div><span style='font-weight:700;color:{$cor}'>".$this->deptLabel($d['departamento'])."</span></div></td>
                <td style='text-align:center'>{$d['n']}</td>
                <td style='color:var(--green);font-weight:700'>".$this->fmtKz($d['massa'])."</td>
                <td><div style='display:flex;align-items:center;gap:8px'><div style='flex:1;height:4px;background:rgba(255,255,255,.06);border-radius:2px'><div style='height:100%;width:{$pct}%;background:{$cor};border-radius:2px'></div></div><span style='font-size:10px;color:var(--t3)'>{$pct}%</span></div></td>
            </tr>";
        }

        $html=$this->layout('Relatórios',<<<HTML
<div class="tbar"><div><div class="pg-t">Relatórios RH</div><div class="pg-s">Visão consolidada</div></div></div>
<div class="cnt">
    <div class="kpis mb20" style="grid-template-columns:repeat(3,1fr)">
        <div class="kpi cy"><div class="kpi-ico" style="background:rgba(56,189,248,.1)">👥</div><div class="kpi-v" style="color:var(--ac)">{$s['activos']}</div><div class="kpi-l">Funcionários Activos</div><div class="kpi-s">de {$s['total']} totais</div></div>
        <div class="kpi gr"><div class="kpi-ico" style="background:rgba(16,185,129,.1)">💰</div><div class="kpi-v" style="color:var(--green);font-size:15px">{$this->fmtKz($s['massa_bruta'])}</div><div class="kpi-l">Massa Salarial Bruta</div><div class="kpi-s">Média: {$this->fmtKz($s['media_sal'])}</div></div>
        <div class="kpi gd"><div class="kpi-ico" style="background:rgba(245,158,11,.1)">💳</div><div class="kpi-v" style="color:var(--gold);font-size:15px">{$this->fmtKz($s['pago_mes'])}</div><div class="kpi-l">Pago Este Mês</div><div class="kpi-s">{$s['ferias_ano']} ausências este ano</div></div>
    </div>
    <div class="card cy">
        <div class="ct"><i class="bi bi-diagram-3"></i>Distribuição por Departamento</div>
        <div class="tw"><table><thead><tr><th>Departamento</th><th style="text-align:center">Pessoas</th><th>Massa Salarial</th><th>Peso</th></tr></thead><tbody>{$dRows}</tbody></table></div>
    </div>
</div>
HTML,$sid,'relatorios');
        $response->getBody()->write($html); return $response->withHeader('Content-Type','text/html');
    }
}
