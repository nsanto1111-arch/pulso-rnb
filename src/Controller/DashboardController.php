<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin\Controller;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Plugin\ProgramacaoPlugin\BridgeClient;
use Plugin\ProgramacaoPlugin\SyncService;
use Psr\Http\Message\ResponseInterface;

class DashboardController
{
    private Connection $db;
    public function __construct(Connection $db) { $this->db = $db; }

    private function kz(float $v, bool $short=false): string {
        if($short && $v >= 1000000) return number_format($v/1000000,1,',','.').'M Kz';
        if($short && $v >= 1000) return number_format($v/1000,0,',','.').'K Kz';
        return number_format($v,0,',','.').' Kz';
    }
    private function pct(float $v, float $t): int { return $t>0?min(100,max(0,(int)($v/$t*100))):0; }
    private function mesNome(): string {
        return strtr(date('F'),['January'=>'Janeiro','February'=>'Fevereiro','March'=>'Março','April'=>'Abril','May'=>'Maio','June'=>'Junho','July'=>'Julho','August'=>'Agosto','September'=>'Setembro','October'=>'Outubro','November'=>'Novembro','December'=>'Dezembro']);
    }

    public function indexAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $hoje = date('Y-m-d');
        $mes  = date('Y-m');
        $ano  = date('Y');
        $agora = date('H:i:s');
        $diaSemana = strtolower(['domingo','segunda','terca','quarta','quinta','sexta','sabado'][date('w')]);

        // ── NOW PLAYING ────────────────────────────────────────
        // RNB Signal Layer — fonte automática (Myriad ou AzuraCast)
        $np         = BridgeClient::nowPlaying();
        $npTitulo   = $np['titulo']    ?? 'A carregar...';
        $npArtista  = $np['artista']   ?? '';
        $npElapsed  = $np['progresso'] ?? 0;
        $npDuration = $np['duracao']   ?? 0;
        $npFonte    = $np['fonte']     ?? 'azuracast';
        $npLocutor  = $np['locutor']   ?? '';
        $npTipo     = $np['tipo']      ?? 'musica';
        $npListeners= BridgeClient::azuracastListeners();
        $sysStatus  = BridgeClient::systemStatus();

        // Intelligence — dados reais de audiência + programação
        try {
            $sync         = new SyncService($sid);
            $intel        = $sync->getIntelligenceProgramacao(
                                date('Y-m-d', strtotime('-30 days')),
                                date('Y-m-d')
                            );
            $progNoAr     = $sync->getProgramaNoAr();
            $topMusicasAud= array_slice($intel['top_musicas_aud'] ?? [], 0, 5);
            $progRanking  = array_slice($intel['programas_ranking'] ?? [], 0, 5);
            $audPorHora   = $intel['audiencia_por_hora'] ?? [];
            $melhorHora   = !empty($audPorHora)
                ? array_reduce($audPorHora, fn($c,$i) => (!$c||$i['media_listeners']>$c['media_listeners'])?$i:$c, null)
                : null;
        } catch(\Exception $e) {
            $intel=[]; $progNoAr=[]; $topMusicasAud=[]; $progRanking=[]; $audPorHora=[]; $melhorHora=null;
        }
        $listeners  = $np['listeners']['current']          ?? 0;
        $npNext     = $np['playing_next']['song']['title'] ?? '';
        $npPct      = $npDuration > 0 ? $this->pct($npElapsed, $npDuration) : 0;

        // ── PROGRAMA ACTUAL ────────────────────────────────────
        try {
            $progAtual = $this->db->fetchAssociative(
                "SELECT * FROM plugin_prog_programas WHERE station_id=? AND JSON_CONTAINS(dias_semana,JSON_QUOTE(?)) AND hora_inicio<=? AND hora_fim>=? AND ativo=1 LIMIT 1",
                [$sid, $diaSemana, $agora, $agora]
            );
            $proxProg = $this->db->fetchAssociative(
                "SELECT * FROM plugin_prog_programas WHERE station_id=? AND JSON_CONTAINS(dias_semana,JSON_QUOTE(?)) AND hora_inicio>? AND ativo=1 ORDER BY hora_inicio ASC LIMIT 1",
                [$sid, $diaSemana, $agora]
            );
            $totalProgs = (int)$this->db->fetchOne("SELECT COUNT(*) FROM plugin_prog_programas WHERE station_id=? AND ativo=1", [$sid]);
        } catch(\Exception $e) { $progAtual=null; $proxProg=null; $totalProgs=0; }

        // ── FINANÇAS ───────────────────────────────────────────
        try {
            $receitaMes  = (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total),0) FROM fp_lancamentos WHERE station_id=? AND tipo='receita' AND DATE_FORMAT(data_lancamento,'%Y-%m')=? AND estado!='cancelado'",[$sid,$mes]);
            $despesaMes  = (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total),0) FROM fp_lancamentos WHERE station_id=? AND tipo='despesa' AND DATE_FORMAT(data_lancamento,'%Y-%m')=? AND estado!='cancelado'",[$sid,$mes]);
            $saldoBanc   = (float)$this->db->fetchOne("SELECT COALESCE(SUM(saldo_atual),0) FROM fp_contas_bancarias WHERE station_id=?",[$sid]);
            $aReceber    = (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total-valor_pago),0) FROM fp_contas_movimento WHERE station_id=? AND tipo='receber' AND estado='pendente'",[$sid]);
            $aPagar      = (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total-valor_pago),0) FROM fp_contas_movimento WHERE station_id=? AND tipo='pagar' AND estado='pendente'",[$sid]);
            $vencidos    = $this->db->fetchAllAssociative("SELECT * FROM fp_contas_movimento WHERE station_id=? AND estado='pendente' AND data_vencimento<=? ORDER BY data_vencimento ASC LIMIT 4",[$sid,$hoje]);
            $lucroMes    = $receitaMes - $despesaMes;
            $contas      = $this->db->fetchAllAssociative("SELECT nome,saldo_atual FROM fp_contas_bancarias WHERE station_id=? ORDER BY saldo_atual DESC",[$sid]);
        } catch(\Exception $e) { $receitaMes=$despesaMes=$saldoBanc=$aReceber=$aPagar=$lucroMes=0; $vencidos=$contas=[]; }

        // ── AUDIÊNCIA ──────────────────────────────────────────
        try {
            $totalOuvintes  = (int)$this->db->fetchOne("SELECT COUNT(*) FROM pulso_ouvintes WHERE station_id=?",[$sid]);
            $ouvAtivos      = (int)$this->db->fetchOne("SELECT COUNT(*) FROM pulso_ouvintes WHERE station_id=? AND ativo=1",[$sid]);
            $novosMes       = (int)$this->db->fetchOne("SELECT COUNT(*) FROM pulso_ouvintes WHERE station_id=? AND DATE_FORMAT(created_at,'%Y-%m')=?",[$sid,$mes]);
            $partHoje       = (int)$this->db->fetchOne("SELECT COUNT(*) FROM pulso_participacoes WHERE station_id=? AND DATE(created_at)=?",[$sid,$hoje]);
            $partMes        = (int)$this->db->fetchOne("SELECT COUNT(*) FROM pulso_participacoes WHERE station_id=? AND DATE_FORMAT(created_at,'%Y-%m')=?",[$sid,$mes]);
            $topMusica      = $this->db->fetchAssociative("SELECT musica,COUNT(*) as n FROM pulso_participacoes WHERE station_id=? AND musica IS NOT NULL AND musica!='' GROUP BY musica ORDER BY n DESC LIMIT 1",[$sid]);
        } catch(\Exception $e) { $totalOuvintes=$ouvAtivos=$novosMes=$partHoje=$partMes=0; $topMusica=null; }

        // ── COMERCIAL ──────────────────────────────────────────
        try {
            $nAnunc      = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_anunciantes WHERE station_id=? AND estado='activo'",[$sid]);
            $nContratos  = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_contratos WHERE station_id=? AND estado='activo'",[$sid]);
            $nCampanhas  = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_campanhas WHERE station_id=? AND estado='activa'",[$sid]);
            $nPropostas  = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_propostas WHERE station_id=? AND estado IN('enviada','em_negociacao')",[$sid]);
            $recComercial= (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total),0) FROM rnb_contratos WHERE station_id=? AND estado='activo'",[$sid]);
            $campAtivas  = $this->db->fetchAllAssociative("SELECT c.*,a.nome as an FROM rnb_campanhas c LEFT JOIN rnb_anunciantes a ON a.id=c.anunciante_id WHERE c.station_id=? AND c.estado='activa' ORDER BY c.data_fim ASC LIMIT 3",[$sid]);
            $campFim7    = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_campanhas WHERE station_id=? AND estado='activa' AND data_fim<=DATE_ADD(?,INTERVAL 7 DAY)",[$sid,$hoje]);
        } catch(\Exception $e) { $nAnunc=$nContratos=$nCampanhas=$nPropostas=$campFim7=0; $recComercial=0; $campAtivas=[]; }

        // ── NOTÍCIAS ───────────────────────────────────────────
        try {
            $nNotActivas = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_noticias WHERE station_id=? AND ativo=1",[$sid]);
            $nNotUrgent  = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_noticias WHERE station_id=? AND ativo=1 AND prioridade='urgente'",[$sid]);
            $ultimasNot  = $this->db->fetchAllAssociative("SELECT titulo,categoria,prioridade,created_at FROM rnb_noticias WHERE station_id=? AND ativo=1 ORDER BY FIELD(prioridade,'urgente','alta','normal','baixa'),created_at DESC LIMIT 4",[$sid]);
        } catch(\Exception $e) { $nNotActivas=$nNotUrgent=0; $ultimasNot=[]; }

        // ── RH ─────────────────────────────────────────────────
        try {
            $nFunc      = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]);
            $massaS     = (float)$this->db->fetchOne("SELECT COALESCE(SUM(salario_base+subsidio_alimentacao+subsidio_transporte+outros_subsidios),0) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]);
            $nFerias    = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_rh_ferias WHERE station_id=? AND estado='pendente'",[$sid]);
            $escHoje    = $this->db->fetchAllAssociative("SELECT e.*,f.nome,f.departamento FROM rnb_rh_escalas e JOIN rnb_funcionarios f ON f.id=e.funcionario_id WHERE e.station_id=? AND e.data=? ORDER BY e.hora_entrada",[$sid,$hoje]);
        } catch(\Exception $e) { $nFunc=$nFerias=0; $massaS=0; $escHoje=[]; }

        // ── ALERTAS ────────────────────────────────────────────
        $alertas = [];
        if($nNotUrgent>0) $alertas[]=['red','exclamation-octagon-fill',"{$nNotUrgent} notícia(s) urgente(s) na Newsroom",'/public/news/'.$sid];
        if(count($vencidos)>0) $alertas[]=['amber','clock-history',count($vencidos)." conta(s) vencida(s) ou a vencer hoje",'/public/financas/'.$sid.'/contas-pagar'];
        if($campFim7>0) $alertas[]=['blue','megaphone',"{$campFim7} campanha(s) terminam em 7 dias",'/public/comercial/'.$sid.'/campanhas'];
        if($nFerias>0) $alertas[]=['purple','umbrella',"{$nFerias} pedido(s) de férias aguardam aprovação",'/public/rh/'.$sid.'/ferias'];
        if($lucroMes>0 && $receitaMes>0) $alertas[]=['green','graph-up-arrow',"Resultado positivo em ".$this->mesNome()." · +".$this->kz($lucroMes,true),'/public/financas/'.$sid];

        // ── RENDER ─────────────────────────────────────────────
        $_rnb_sid=$sid; $_rnb_atual='dashboard';
        ob_start(); @require dirname(__DIR__,2).'/public/rnb-nav.php'; $rnbNav=ob_get_clean();

        $remMin = $npDuration>0 ? max(0,(int)(($npDuration-$npElapsed)/60)) : 0;
        $remSec = $npDuration>0 ? max(0,($npDuration-$npElapsed)%60) : 0;
        $npRemStr = $npDuration>0 ? '-'.sprintf('%02d:%02d',$remMin,$remSec) : '--:--';

        ob_start(); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="refresh" content="60">
<title>RNB OS — Centro de Controlo</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --void:#020208;
  --d1:#080814;--d2:#0e0e1c;--d3:#161626;--d4:#1e1e30;--d5:#262638;--d6:#2e2e42;
  --cy:#00d4f5;--cy2:#0099cc;--cy3:rgba(0,212,245,.08);
  --green:#0fba81;--green2:#089662;--green3:rgba(15,186,129,.08);
  --red:#f03e3e;--red2:#c43333;--red3:rgba(240,62,62,.08);
  --gold:#f0a500;--gold2:#c47f00;--gold3:rgba(240,165,0,.08);
  --purple:#8b5cf6;--purple3:rgba(139,92,246,.08);
  --blue:#3d7eff;--blue3:rgba(61,126,255,.08);
  --pink:#e879a0;--pink3:rgba(232,121,160,.08);
  --t1:#f0f0ff;--t2:#8888aa;--t3:#4a4a66;
  --br:rgba(255,255,255,.06);--br2:rgba(255,255,255,.1);--br3:rgba(255,255,255,.15);
  --ff:'Inter',-apple-system,sans-serif;
  --r8:8px;--r12:12px;--r16:16px;--r20:20px;
}
html,body{min-height:100vh;font-family:var(--ff);background:var(--void);color:var(--t1);font-size:13px;-webkit-font-smoothing:antialiased;line-height:1.5}

/* ── BACKGROUND ── */
.bg-noise{position:fixed;inset:0;z-index:0;pointer-events:none;
  background:
    radial-gradient(ellipse 80% 60% at 10% 20%,rgba(0,212,245,.04) 0%,transparent 60%),
    radial-gradient(ellipse 60% 80% at 90% 80%,rgba(139,92,246,.04) 0%,transparent 60%),
    radial-gradient(ellipse 100% 40% at 50% 0%,rgba(15,186,129,.025) 0%,transparent 50%);
}
.bg-grid{position:fixed;inset:0;z-index:0;pointer-events:none;opacity:.018;
  background-image:linear-gradient(rgba(255,255,255,.5) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.5) 1px,transparent 1px);
  background-size:40px 40px;
}

/* ── LAYOUT ── */
.shell{position:relative;z-index:1;display:flex;flex-direction:column;min-height:100vh}

/* ── TOPBAR ── */
.topbar{
  height:52px;display:flex;align-items:center;gap:0;
  background:rgba(8,8,20,.92);backdrop-filter:blur(24px);
  border-bottom:1px solid var(--br);position:sticky;top:0;z-index:200;
  padding:0 20px;flex-shrink:0;
}
/* Logo */
.tb-logo{display:flex;align-items:center;gap:10px;padding-right:18px;border-right:1px solid var(--br);flex-shrink:0}
.tb-logo-mark{
  width:32px;height:32px;border-radius:9px;flex-shrink:0;
  background:linear-gradient(135deg,var(--cy),var(--purple));
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 0 20px rgba(0,212,245,.2);
}
.tb-logo-mark svg{width:22px;height:22px}
.tb-logo-name{line-height:1}
.tb-logo-rnb{font-size:14px;font-weight:800;letter-spacing:-.3px;
  background:linear-gradient(135deg,var(--cy) 0%,#fff 100%);
  -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.tb-logo-sub{font-size:8px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--t3);margin-top:1px}
/* NP strip */
.tb-np{
  flex:1;margin:0 16px;height:32px;
  background:var(--d2);border:1px solid var(--br);border-radius:100px;
  display:flex;align-items:center;gap:10px;padding:0 14px;
  position:relative;overflow:hidden;min-width:0;
}
.tb-np-prog{position:absolute;left:0;top:0;bottom:0;background:linear-gradient(90deg,rgba(0,212,245,.07),transparent);border-radius:100px;pointer-events:none;transition:width 1s linear}
.tb-np-dot{width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 8px var(--green);animation:dot 1.6s ease-in-out infinite;flex-shrink:0}
@keyframes dot{0%,100%{opacity:1;box-shadow:0 0 8px var(--green)}50%{opacity:.4;box-shadow:none}}
.tb-np-song{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex:1;color:var(--t1)}
.tb-np-art{font-weight:400;color:var(--t2)}
.tb-np-timer{font-size:12px;font-weight:700;color:var(--green);font-variant-numeric:tabular-nums;flex-shrink:0;letter-spacing:.5px}
.tb-np-next{font-size:10px;color:var(--t3);flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px}
.tb-np-list{display:flex;align-items:center;gap:5px;flex-shrink:0;font-size:12px;border-left:1px solid var(--br);padding-left:10px;color:var(--t2)}
.tb-np-list b{color:var(--cy);font-size:14px;font-weight:800}
/* Modulos nav */
.tb-mods{display:flex;gap:2px;padding-left:14px;border-left:1px solid var(--br);flex-shrink:0}
.tb-mod{display:flex;align-items:center;gap:5px;padding:5px 10px;border-radius:7px;text-decoration:none;color:var(--t3);font-size:11px;font-weight:600;transition:all .2s;white-space:nowrap}
.tb-mod:hover{background:rgba(255,255,255,.05);color:var(--t2);text-decoration:none}
.tb-mod.here{background:rgba(0,212,245,.08);color:var(--cy);border:1px solid rgba(0,212,245,.15)}
.tb-mod i{font-size:13px}
/* Relógio */
.tb-clock{padding-left:14px;border-left:1px solid var(--br);flex-shrink:0;text-align:right}
.tb-clock-h{font-size:16px;font-weight:800;font-variant-numeric:tabular-nums;letter-spacing:-.5px;color:var(--t1)}
.tb-clock-d{font-size:9px;color:var(--t3);margin-top:1px}

/* ── PROG BAR ── */
.progbar{
  display:none;align-items:center;gap:14px;
  padding:7px 20px;border-bottom:1px solid rgba(15,186,129,.12);
  background:rgba(15,186,129,.03);font-size:12px;flex-shrink:0;
}
.progbar.show{display:flex}
.pb-live{display:flex;align-items:center;gap:5px;color:var(--green);font-weight:700;font-size:10px;letter-spacing:.5px}
.pb-prog{font-weight:700;font-size:13px;color:var(--t1)}
.pb-hor{color:var(--t3)}
.pb-countdown{font-variant-numeric:tabular-nums;font-weight:700;color:var(--gold);font-size:12px;margin-left:4px}
.pb-prox{margin-left:auto;color:var(--t3);font-size:11px}
.pb-prox strong{color:var(--t2);font-weight:600}

/* ── ALERTAS ── */
.alertas-bar{padding:8px 20px;display:flex;gap:7px;flex-wrap:wrap;border-bottom:1px solid var(--br);background:rgba(0,0,0,.2);flex-shrink:0}
.alerta{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:100px;font-size:11px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;border:1px solid transparent}
.alerta:hover{filter:brightness(1.15);text-decoration:none}
.alerta.red{background:var(--red3);color:var(--red);border-color:rgba(240,62,62,.2)}
.alerta.amber{background:var(--gold3);color:var(--gold);border-color:rgba(240,165,0,.2)}
.alerta.blue{background:var(--blue3);color:var(--blue);border-color:rgba(61,126,255,.2)}
.alerta.purple{background:var(--purple3);color:var(--purple);border-color:rgba(139,92,246,.2)}
.alerta.green{background:var(--green3);color:var(--green);border-color:rgba(15,186,129,.2)}

/* ── MAIN CONTENT ── */
.content{flex:1;padding:18px 20px 24px;display:flex;flex-direction:column;gap:16px}

/* ── KPI ROW ── */
.kpi-row{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}
.kpi{
  background:var(--d2);border:1px solid var(--br);border-radius:var(--r16);
  padding:16px;position:relative;overflow:hidden;cursor:default;transition:all .25s;
  text-decoration:none;display:block;
}
.kpi:hover{border-color:var(--br2);transform:translateY(-2px);text-decoration:none}
.kpi-accent{position:absolute;top:0;left:0;right:0;height:2px;border-radius:var(--r16) var(--r16) 0 0}
.kpi-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px}
.kpi-ico{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.kpi-trend{font-size:10px;font-weight:700;padding:2px 7px;border-radius:100px;display:flex;align-items:center;gap:3px}
.kpi-trend.up{background:var(--green3);color:var(--green)}
.kpi-trend.dn{background:var(--red3);color:var(--red)}
.kpi-trend.neu{background:rgba(255,255,255,.04);color:var(--t3)}
.kpi-val{font-size:24px;font-weight:900;line-height:1;margin-bottom:3px;color:var(--t1)}
.kpi-val.sm{font-size:17px}
.kpi-lbl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--t3)}
.kpi-sub{font-size:10px;color:var(--t3);margin-top:6px;display:flex;align-items:center;gap:4px}

/* ── GRID ── */
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.grid-2-1{display:grid;grid-template-columns:2fr 1fr;gap:14px}
.grid-1-2{display:grid;grid-template-columns:1fr 2fr;gap:14px}

/* ── CARDS ── */
.card{
  background:var(--d2);border:1px solid var(--br);border-radius:var(--r16);
  overflow:hidden;position:relative;transition:border-color .25s;
}
.card:hover{border-color:var(--br2)}
.card-hd{
  padding:14px 16px 12px;border-bottom:1px solid var(--br);
  display:flex;align-items:center;justify-content:space-between;gap:10px;
}
.card-hd-l{display:flex;align-items:center;gap:8px}
.card-hd-ico{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.card-title{font-size:12px;font-weight:700;color:var(--t1)}
.card-sub{font-size:10px;color:var(--t3);margin-top:1px}
.card-link{font-size:10px;font-weight:600;color:var(--t3);text-decoration:none;padding:3px 8px;border-radius:6px;border:1px solid var(--br);transition:all .2s;display:inline-flex;align-items:center;gap:4px;flex-shrink:0}
.card-link:hover{color:var(--t2);background:rgba(255,255,255,.04);border-color:var(--br2);text-decoration:none}
.card-body{padding:14px 16px}
.card-body.p0{padding:0}

/* ── FINANCAS CARD ── */
.fin-main{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1px;background:var(--br)}
.fin-col{background:var(--d2);padding:14px 16px}
.fin-col-lbl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--t3);margin-bottom:6px}
.fin-col-val{font-size:20px;font-weight:900;line-height:1}
.fin-col-sub{font-size:10px;color:var(--t3);margin-top:5px}
.fin-bar{height:3px;background:var(--d4);margin:0 16px 0;border-radius:2px;overflow:hidden}
.fin-bar-fill{height:100%;border-radius:2px;transition:width .6s ease}
.fin-contas{display:flex;flex-direction:column;gap:1px;background:var(--br)}
.fin-conta{background:var(--d2);padding:10px 16px;display:flex;align-items:center;gap:10px}
.fin-conta-nome{font-size:11px;font-weight:600;color:var(--t2);flex:1}
.fin-conta-val{font-size:12px;font-weight:700}
.fin-venc{padding:12px 16px;border-top:1px solid var(--br)}
.fin-venc-t{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--red);margin-bottom:8px;display:flex;align-items:center;gap:5px}
.fin-venc-item{display:flex;align-items:center;gap:8px;padding:7px 10px;background:var(--d3);border-radius:8px;margin-bottom:4px}
.fin-venc-desc{font-size:11px;font-weight:600;color:var(--t1);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.fin-venc-val{font-size:11px;font-weight:700;flex-shrink:0}
.fin-venc-tipo{font-size:8px;font-weight:700;padding:2px 6px;border-radius:4px;flex-shrink:0}

/* ── AUDIÊNCIA ── */
.aud-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.aud-item{background:var(--d3);border-radius:10px;padding:12px;text-align:center}
.aud-val{font-size:22px;font-weight:900;line-height:1}
.aud-lbl{font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:var(--t3);margin-top:3px}
.aud-top{margin-top:10px;padding:10px 12px;background:var(--cy3);border:1px solid rgba(0,212,245,.12);border-radius:9px}
.aud-top-l{font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--cy);margin-bottom:3px;display:flex;align-items:center;gap:4px}
.aud-top-v{font-size:12px;font-weight:700;color:var(--t1)}

/* ── PROGRAMAS ── */
.prog-item{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--br)}
.prog-item:last-child{border-bottom:none}
.prog-dot{width:3px;height:36px;border-radius:2px;flex-shrink:0}
.prog-nome{font-size:12px;font-weight:700;color:var(--t1)}
.prog-hora{font-size:10px;font-weight:600;font-variant-numeric:tabular-nums;color:var(--t3);flex-shrink:0}
.prog-dias{display:flex;gap:3px;margin-top:3px;flex-wrap:wrap}
.prog-dia{font-size:8px;font-weight:700;padding:1px 5px;border-radius:3px;background:var(--d4);color:var(--t3);text-transform:uppercase;letter-spacing:.3px}
.prog-dia.on{background:rgba(0,212,245,.1);color:var(--cy)}
.prog-atual{background:rgba(15,186,129,.05);border-color:rgba(15,186,129,.15)}

/* ── NOTÍCIAS ── */
.not-item{display:flex;align-items:flex-start;gap:10px;padding:10px 16px;border-bottom:1px solid var(--br);transition:background .2s}
.not-item:last-child{border-bottom:none}
.not-item:hover{background:rgba(255,255,255,.02)}
.not-dot{width:8px;height:8px;border-radius:50%;margin-top:4px;flex-shrink:0}
.not-titulo{font-size:12px;font-weight:700;color:var(--t1);flex:1;min-width:0;line-height:1.4}
.not-cat{font-size:9px;font-weight:700;padding:2px 6px;border-radius:4px;flex-shrink:0;margin-top:1px}
.not-urg{background:rgba(240,62,62,.1);color:var(--red)}
.not-alta{background:rgba(240,165,0,.1);color:var(--gold)}
.not-norm{background:rgba(255,255,255,.06);color:var(--t3)}

/* ── CAMPANHAS ── */
.camp-item{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--br)}
.camp-item:last-child{border-bottom:none}
.camp-av{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#000;flex-shrink:0}
.camp-info{flex:1;min-width:0}
.camp-nome{font-size:12px;font-weight:700;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.camp-an{font-size:10px;color:var(--t3)}
.camp-r{flex-shrink:0;text-align:right}
.camp-pct{font-size:11px;font-weight:700}
.camp-bar{width:56px;height:3px;background:var(--d4);border-radius:2px;overflow:hidden;margin-top:3px}
.camp-bar-f{height:100%;border-radius:2px}
.camp-d{font-size:9px;margin-top:2px}

/* ── RH ESCALAS ── */
.esc-item{display:flex;align-items:center;gap:10px;padding:9px 16px;border-bottom:1px solid var(--br)}
.esc-item:last-child{border-bottom:none}
.esc-hora{font-size:11px;font-weight:700;font-variant-numeric:tabular-nums;color:var(--cy);min-width:90px;flex-shrink:0}
.esc-nome{font-size:12px;font-weight:700;color:var(--t1);flex:1}
.esc-dept{font-size:9px;color:var(--t3)}

/* ── PIPELINE ── */
.pipe-item{display:flex;align-items:center;gap:8px;padding:9px 16px;border-bottom:1px solid var(--br)}
.pipe-item:last-child{border-bottom:none}
.pipe-badge{font-size:9px;font-weight:700;padding:2px 7px;border-radius:100px;flex-shrink:0}
.pipe-env{background:var(--blue3);color:var(--blue)}
.pipe-neg{background:var(--gold3);color:var(--gold)}
.pipe-ace{background:var(--green3);color:var(--green)}
.pipe-titulo{font-size:12px;font-weight:600;color:var(--t1);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pipe-val{font-size:11px;font-weight:700;color:var(--gold);flex-shrink:0}

/* ── EMPTY ── */
.empty{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:28px 16px;color:var(--t3);text-align:center;gap:7px}
.empty i{font-size:28px;opacity:.12}
.empty span{font-size:11px}

/* ── FOOTER ── */
.dash-footer{text-align:center;padding:10px;font-size:10px;color:var(--t3);border-top:1px solid var(--br);flex-shrink:0}
.dash-footer span{color:var(--cy)}

/* ── MISC ── */
.badge{display:inline-flex;align-items:center;padding:2px 7px;border-radius:100px;font-size:10px;font-weight:700}
.tag-dias{display:flex;gap:2px;margin-top:3px}

@media(max-width:1400px){.kpi-row{grid-template-columns:repeat(5,1fr)}.grid-3{grid-template-columns:1fr 1fr}}
@media(max-width:1100px){.kpi-row{grid-template-columns:repeat(3,1fr)}.grid-3,.grid-2-1,.grid-1-2{grid-template-columns:1fr}}
@media(max-width:700px){.kpi-row{grid-template-columns:1fr 1fr}.tb-mods{display:none}}
</style>
</head>
<body>
<div class="bg-noise"></div>
<div class="bg-grid"></div>
<?= $rnbNav ?>

<div class="shell">

<!-- TOPBAR -->
<div class="topbar">
  <!-- Logo -->
  <div class="tb-logo">
    <div class="tb-logo-mark">
      <svg viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg">
        <rect x="2" y="6" width="2.5" height="28" rx="1.2" fill="#0099ff"/>
        <rect x="5.5" y="6" width="2.5" height="6" rx="1.2" fill="#00aaff"/>
        <rect x="9" y="5" width="2.5" height="8" rx="1.2" fill="#00ccff"/>
        <rect x="12.5" y="6" width="2.5" height="7" rx="1.2" fill="#33aaff"/>
        <rect x="9" y="16" width="2.5" height="7" rx="1.2" fill="#7755ff"/>
        <rect x="12.5" y="18" width="2.5" height="8" rx="1.2" fill="#9933ff"/>
        <rect x="21" y="6" width="2.5" height="28" rx="1.2" fill="#aa22ff"/>
        <rect x="33" y="6" width="2.5" height="28" rx="1.2" fill="#ff5566"/>
        <rect x="39" y="6" width="2.5" height="28" rx="1.2" fill="#ff8800"/>
        <rect x="42.5" y="6" width="2.5" height="6" rx="1.2" fill="#ffaa00"/>
        <rect x="46" y="5" width="2.5" height="8" rx="1.2" fill="#ffcc00"/>
        <rect x="49.5" y="6" width="2.5" height="7" rx="1.2" fill="#ffdd22"/>
        <rect x="42.5" y="18" width="2.5" height="6" rx="1.2" fill="#66aaff"/>
        <rect x="46" y="17" width="2.5" height="8" rx="1.2" fill="#4488ff"/>
        <rect x="49.5" y="18" width="2.5" height="7" rx="1.2" fill="#2266ff"/>
      </svg>
    </div>
    <div class="tb-logo-name">
      <div class="tb-logo-rnb">RNB OS</div>
      <div class="tb-logo-sub">Centro de Controlo</div>
    </div>
  </div>

  <!-- Now Playing — RNB Signal Layer -->
  <div class="tb-np" id="np-strip">
    <div class="tb-np-prog" id="np-prog-bar" style="width:<?= $npPct ?>%"></div>

    <?php
    // Badge de fonte — 3 estados
    $fonte_ = $npFonte ?? 'azuracast';
    if($fonte_ === 'live') {
        echo '<span style="flex-shrink:0;display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:100px;font-size:10px;font-weight:800;background:#DC262622;color:#DC2626;border:1px solid #DC262644;letter-spacing:.5px;animation:livepulse 1.5s infinite">&#9679; AO VIVO</span>';
    } elseif($fonte_ === 'myriad') {
        echo '<span style="flex-shrink:0;display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border-radius:100px;font-size:10px;font-weight:700;background:#7C3AED22;color:#7C3AED;border:1px solid #7C3AED44;letter-spacing:.3px">MYRIAD</span>';
    } else {
        echo '<span style="flex-shrink:0;display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border-radius:100px;font-size:10px;font-weight:700;background:#2563EB22;color:#2563EB;border:1px solid #2563EB44;letter-spacing:.3px">AUTO</span>';
    }
    ?>

    <div class="tb-np-dot"></div>

    <div class="tb-np-song">
      <?php if($npTitulo && $npTitulo !== 'A carregar...'): ?>
        <b><?= htmlspecialchars($npTitulo) ?></b>
        <?php if($npArtista): ?>
          <span class="tb-np-art">— <?= htmlspecialchars($npArtista) ?></span>
        <?php endif ?>
        <?php if(!empty($npLocutor)): ?>
          <span style="opacity:.5;font-size:10px;margin-left:6px">
            <i class="bi bi-person-fill"></i> <?= htmlspecialchars($npLocutor) ?>
          </span>
        <?php endif ?>
      <?php else: ?>
        <span style="color:var(--t3)">A carregar...</span>
      <?php endif ?>
    </div>

    <?php if($npDuration > 0): ?>
    <div class="tb-np-timer" id="np-timer"><?= $npRemStr ?></div>
    <?php endif ?>

    <?php if($npNext): ?>
    <div class="tb-np-next">
      <i class="bi bi-skip-forward" style="font-size:10px;opacity:.5"></i>
      <?= htmlspecialchars($npNext) ?>
    </div>
    <?php endif ?>

    <div class="tb-np-list">
      <i class="bi bi-headphones" style="font-size:11px"></i>
      <b id="np-listeners"><?= $npListeners ?? $listeners ?></b>
    </div>
  </div>

  <!-- Módulos -->
  <div class="tb-mods">
    <a href="/public/pulso/<?= $sid ?>" class="tb-mod"><i class="bi bi-people-fill"></i>Audiência</a>
    <a href="/public/comercial/<?= $sid ?>" class="tb-mod"><i class="bi bi-building"></i>Comercial</a>
    <a href="/public/financas/<?= $sid ?>" class="tb-mod"><i class="bi bi-wallet2"></i>Finanças</a>
    <a href="/public/news/<?= $sid ?>" class="tb-mod"><i class="bi bi-newspaper"></i>News</a>
    <a href="/public/rh/<?= $sid ?>" class="tb-mod"><i class="bi bi-people"></i>RH</a>
    <a href="/pulso/locutor" class="tb-mod"><i class="bi bi-mic-fill"></i>Studio</a>
    <a href="/public/dashboard/<?= $sid ?>" class="tb-mod here"><i class="bi bi-speedometer2"></i>Dashboard</a>
  </div>

  <!-- Relógio -->
  <div class="tb-clock">
    <div class="tb-clock-h" id="clock">--:--:--</div>
    <div class="tb-clock-d"><?= date('d/m/Y') ?> · <?= $this->mesNome() ?></div>
  </div>
</div>

<!-- PROGRAMA ACTUAL -->
<?php if($progAtual): ?>
<?php
  $hI = strtotime($hoje.' '.$progAtual['hora_fim']);
  $now = time();
  $remProg = max(0, $hI - $now);
  $remH = floor($remProg/3600);
  $remM = floor(($remProg%3600)/60);
  $remS = $remProg%60;
?>
<div class="progbar show">
  <div class="pb-live"><div class="tb-np-dot" style="width:5px;height:5px"></div>AO VIVO</div>
  <div class="pb-prog"><?= htmlspecialchars($progAtual['nome']) ?></div>
  <div class="pb-hor"><?= substr($progAtual['hora_inicio'],0,5) ?> → <?= substr($progAtual['hora_fim'],0,5) ?></div>
  <div class="pb-countdown" id="prog-cd"><?= $remH>0?sprintf('%02d:',$remH):'' ?><?= sprintf('%02d:%02d',$remM,$remS) ?></div>
  <?php if($proxProg): ?>
  <div class="pb-prox">A seguir: <strong><?= htmlspecialchars($proxProg['nome']) ?></strong> às <?= substr($proxProg['hora_inicio'],0,5) ?></div>
  <?php endif ?>
</div>
<?php endif ?>

<!-- ALERTAS -->
<?php if($alertas): ?>
<div class="alertas-bar">
  <?php foreach($alertas as [$cor,$ico,$msg,$url]): ?>
  <a href="<?= $url ?>" class="alerta <?= $cor ?>"><i class="bi bi-<?= $ico ?>"></i><?= htmlspecialchars($msg) ?></a>
  <?php endforeach ?>
</div>
<?php endif ?>

<!-- CONTEÚDO PRINCIPAL -->
<div class="content">

  <!-- KPI ROW -->
  <div class="kpi-row">

    <!-- Receita -->
    <a href="/public/financas/<?= $sid ?>" class="kpi">
      <div class="kpi-accent" style="background:linear-gradient(90deg,var(--gold),var(--gold2))"></div>
      <div class="kpi-top">
        <div class="kpi-ico" style="background:var(--gold3)"><i class="bi bi-currency-exchange" style="color:var(--gold)"></i></div>
        <div class="kpi-trend <?= $lucroMes>=0?'up':'dn' ?>"><i class="bi bi-arrow-<?= $lucroMes>=0?'up':'down' ?>"></i><?= $lucroMes>=0?'+':'-' ?><?= $this->kz(abs($lucroMes),true) ?></div>
      </div>
      <div class="kpi-val sm"><?= $this->kz($receitaMes,true) ?></div>
      <div class="kpi-lbl">Receita — <?= $this->mesNome() ?></div>
      <div class="kpi-sub"><i class="bi bi-arrow-down-circle" style="color:var(--red)"></i><?= $this->kz($despesaMes,true) ?> despesas</div>
    </a>

    <!-- Saldo -->
    <a href="/public/financas/<?= $sid ?>/conta-corrente" class="kpi">
      <div class="kpi-accent" style="background:linear-gradient(90deg,var(--cy),var(--cy2))"></div>
      <div class="kpi-top">
        <div class="kpi-ico" style="background:var(--cy3)"><i class="bi bi-bank" style="color:var(--cy)"></i></div>
        <div class="kpi-trend neu"><?= count($contas) ?> conta<?= count($contas)!=1?'s':'' ?></div>
      </div>
      <div class="kpi-val sm"><?= $this->kz($saldoBanc,true) ?></div>
      <div class="kpi-lbl">Saldo Bancário</div>
      <div class="kpi-sub" style="gap:8px">
        <span style="color:var(--green)">↑ <?= $this->kz($aReceber,true) ?></span>
        <span style="color:var(--red)">↓ <?= $this->kz($aPagar,true) ?></span>
      </div>
    </a>

    <!-- Ouvintes -->
    <a href="/public/pulso/<?= $sid ?>" class="kpi">
      <div class="kpi-accent" style="background:linear-gradient(90deg,var(--purple),var(--pink))"></div>
      <div class="kpi-top">
        <div class="kpi-ico" style="background:var(--purple3)"><i class="bi bi-people-fill" style="color:var(--purple)"></i></div>
        <?php if($listeners>0): ?>
        <div class="kpi-trend up"><i class="bi bi-broadcast"></i><?= $listeners ?> ao vivo</div>
        <?php else: ?>
        <div class="kpi-trend neu">—</div>
        <?php endif ?>
      </div>
      <div class="kpi-val"><?= number_format($ouvAtivos,0,',','.') ?></div>
      <div class="kpi-lbl">Ouvintes Registados</div>
      <div class="kpi-sub"><i class="bi bi-person-plus" style="color:var(--purple)"></i>+<?= $novosMes ?> este mês</div>
    </a>

    <!-- Comercial -->
    <a href="/public/comercial/<?= $sid ?>" class="kpi">
      <div class="kpi-accent" style="background:linear-gradient(90deg,var(--green),var(--green2))"></div>
      <div class="kpi-top">
        <div class="kpi-ico" style="background:var(--green3)"><i class="bi bi-megaphone-fill" style="color:var(--green)"></i></div>
        <?php if($campFim7>0): ?>
        <div class="kpi-trend dn"><i class="bi bi-clock"></i><?= $campFim7 ?> a terminar</div>
        <?php elseif($nCampanhas>0): ?>
        <div class="kpi-trend up"><i class="bi bi-check-circle"></i>Em dia</div>
        <?php else: ?><div class="kpi-trend neu">—</div><?php endif ?>
      </div>
      <div class="kpi-val"><?= $nCampanhas ?></div>
      <div class="kpi-lbl">Campanhas Activas</div>
      <div class="kpi-sub"><i class="bi bi-building" style="color:var(--green)"></i><?= $nAnunc ?> anunciantes · <?= $nPropostas ?> propostas</div>
    </a>

    <!-- Equipa -->
    <a href="/public/rh/<?= $sid ?>" class="kpi">
      <div class="kpi-accent" style="background:linear-gradient(90deg,var(--blue),var(--purple))"></div>
      <div class="kpi-top">
        <div class="kpi-ico" style="background:var(--blue3)"><i class="bi bi-person-badge-fill" style="color:var(--blue)"></i></div>
        <?php if($nFerias>0): ?>
        <div class="kpi-trend amber" style="background:var(--gold3);color:var(--gold)"><i class="bi bi-umbrella"></i><?= $nFerias ?> pend.</div>
        <?php else: ?><div class="kpi-trend neu"><?= count($escHoje) ?> hoje</div><?php endif ?>
      </div>
      <div class="kpi-val"><?= $nFunc ?></div>
      <div class="kpi-lbl">Funcionários Activos</div>
      <div class="kpi-sub"><i class="bi bi-cash-stack" style="color:var(--blue)"></i><?= $this->kz($massaS,true) ?> massa salarial</div>
    </a>

  </div>

  <!-- LINHA 2: FINANÇAS COMPLETO -->
  <div class="card">
    <div class="card-hd">
      <div class="card-hd-l">
        <div class="card-hd-ico" style="background:var(--gold3)"><i class="bi bi-wallet2" style="color:var(--gold);font-size:13px"></i></div>
        <div><div class="card-title">Finanças — <?= $this->mesNome().' '.$ano ?></div><div class="card-sub">Fluxo de caixa · Contas bancárias · Vencimentos</div></div>
      </div>
      <a href="/public/financas/<?= $sid ?>" class="card-link"><i class="bi bi-box-arrow-up-right"></i>Abrir Finance Pro</a>
    </div>
    <!-- Métricas principais -->
    <div class="fin-main">
      <div class="fin-col">
        <div class="fin-col-lbl">Receita do Mês</div>
        <div class="fin-col-val" style="color:var(--green)"><?= $this->kz($receitaMes) ?></div>
        <div class="fin-col-sub"><?= $this->kz($aReceber) ?> a receber</div>
      </div>
      <div class="fin-col" style="border-left:1px solid var(--br)">
        <div class="fin-col-lbl">Despesas do Mês</div>
        <div class="fin-col-val" style="color:var(--red)"><?= $this->kz($despesaMes) ?></div>
        <div class="fin-col-sub"><?= $this->kz($aPagar) ?> a pagar</div>
      </div>
      <div class="fin-col" style="border-left:1px solid var(--br)">
        <div class="fin-col-lbl">Resultado</div>
        <div class="fin-col-val" style="color:<?= $lucroMes>=0?'var(--green)':'var(--red)' ?>"><?= ($lucroMes>=0?'+':'').$this->kz($lucroMes) ?></div>
        <div class="fin-col-sub">margem <?= $receitaMes>0?round($lucroMes/$receitaMes*100):0 ?>%</div>
      </div>
    </div>
    <?php
      $maxFin = max(1, $receitaMes, $despesaMes);
      $pctRec = $this->pct($receitaMes, $maxFin);
      $pctDesp = $this->pct($despesaMes, $maxFin);
    ?>
    <div style="padding:10px 16px;background:var(--d3);border-top:1px solid var(--br);display:flex;flex-direction:column;gap:5px">
      <div style="display:flex;align-items:center;gap:8px;font-size:10px;color:var(--green)">
        <span style="min-width:60px;font-weight:700">Receita</span>
        <div style="flex:1;height:4px;background:var(--d4);border-radius:2px;overflow:hidden"><div style="height:100%;width:<?= $pctRec ?>%;background:var(--green);border-radius:2px"></div></div>
        <span style="min-width:50px;text-align:right;font-weight:700"><?= $pctRec ?>%</span>
      </div>
      <div style="display:flex;align-items:center;gap:8px;font-size:10px;color:var(--red)">
        <span style="min-width:60px;font-weight:700">Despesas</span>
        <div style="flex:1;height:4px;background:var(--d4);border-radius:2px;overflow:hidden"><div style="height:100%;width:<?= $pctDesp ?>%;background:var(--red);border-radius:2px"></div></div>
        <span style="min-width:50px;text-align:right;font-weight:700"><?= $pctDesp ?>%</span>
      </div>
    </div>
    <!-- Contas bancárias -->
    <?php if($contas): ?>
    <div class="fin-contas" style="border-top:1px solid var(--br)">
      <?php foreach($contas as $c): ?>
      <div class="fin-conta">
        <i class="bi bi-bank" style="color:var(--t3);font-size:12px"></i>
        <span class="fin-conta-nome"><?= htmlspecialchars($c['nome']) ?></span>
        <span class="fin-conta-val" style="color:<?= $c['saldo_atual']>=0?'var(--green)':'var(--red)' ?>"><?= $this->kz($c['saldo_atual']) ?></span>
      </div>
      <?php endforeach ?>
    </div>
    <?php endif ?>
    <!-- Vencimentos -->
    <?php if($vencidos): ?>
    <div class="fin-venc">
      <div class="fin-venc-t"><i class="bi bi-exclamation-triangle-fill"></i>Vencidos / A vencer hoje</div>
      <?php foreach($vencidos as $v):
        $atr = strtotime($v['data_vencimento']) < strtotime($hoje);
        $isPagar = $v['tipo']==='pagar';
        $valPend = $v['valor_total']-$v['valor_pago'];
      ?>
      <div class="fin-venc-item">
        <div class="fin-venc-desc"><?= htmlspecialchars($v['descricao']??$v['entidade']??'—') ?></div>
        <span class="fin-venc-tipo" style="background:<?= $isPagar?'var(--red3)':'var(--green3)' ?>;color:<?= $isPagar?'var(--red)':'var(--green)' ?>"><?= $isPagar?'PAGAR':'RECEBER' ?></span>
        <div class="fin-venc-val" style="color:<?= $isPagar?'var(--red)':'var(--green)' ?>"><?= $this->kz($valPend) ?></div>
        <?php if($atr): ?><div style="font-size:8px;font-weight:800;color:var(--red);background:var(--red3);padding:2px 5px;border-radius:3px">ATRASO</div><?php endif ?>
      </div>
      <?php endforeach ?>
    </div>
    <?php endif ?>
  </div>

  <!-- INTELLIGENCE ROW -->
  <div class="grid-3" style="margin-bottom:14px">

    <!-- TOP MÚSICAS POR AUDIÊNCIA -->
    <div class="card">
      <div class="card-hd">
        <div class="card-hd-l">
          <div class="card-hd-ico" style="background:var(--gold3)"><i class="bi bi-bar-chart-fill" style="color:var(--gold);font-size:13px"></i></div>
          <div><div class="card-title">Top Músicas</div><div class="card-sub">Por audiência real · 30 dias</div></div>
        </div>
        <a href="/api/rnb/intelligence" class="card-link" target="_blank">API →</a>
      </div>
      <div class="card-body p0">
        <?php if($topMusicasAud): ?>
        <?php foreach($topMusicasAud as $i=>$m): ?>
        <div class="pipe-item">
          <div class="pipe-badge pipe-env" style="min-width:18px;text-align:center"><?= $i+1 ?></div>
          <div style="flex:1;min-width:0">
            <div class="pipe-titulo"><?= htmlspecialchars($m['titulo']??'—') ?></div>
            <div style="font-size:10px;color:var(--t3)"><?= htmlspecialchars($m['artista']??'') ?></div>
          </div>
          <div style="font-size:11px;font-weight:700;color:var(--cy);flex-shrink:0">
            <?= number_format((float)($m['media_aud']??0),1) ?> ouv
          </div>
        </div>
        <?php endforeach ?>
        <?php else: ?>
        <div class="empty"><i class="bi bi-bar-chart"></i><span>Sem dados ainda</span></div>
        <?php endif ?>
      </div>
    </div>

    <!-- RANKING PROGRAMAS -->
    <div class="card">
      <div class="card-hd">
        <div class="card-hd-l">
          <div class="card-hd-ico" style="background:var(--purple3)"><i class="bi bi-trophy-fill" style="color:var(--purple);font-size:13px"></i></div>
          <div><div class="card-title">Programas</div><div class="card-sub">Ranking por audiência</div></div>
        </div>
        <a href="/public/programacao/<?= $sid ?>" class="card-link">Grade →</a>
      </div>
      <div class="card-body p0">
        <?php if($progRanking): ?>
        <?php foreach($progRanking as $i=>$prog): ?>
        <div class="pipe-item">
          <div class="pipe-badge" style="background:var(--purple3);color:var(--purple);min-width:18px;text-align:center"><?= $i+1 ?></div>
          <div style="flex:1;min-width:0">
            <div class="pipe-titulo"><?= htmlspecialchars($prog['programa']??'—') ?></div>
            <div style="font-size:10px;color:var(--t3)"><?= htmlspecialchars($prog['locutor']??'Automático') ?> · <?= substr($prog['hora_inicio']??'',0,5) ?>-<?= substr($prog['hora_fim']??'',0,5) ?></div>
          </div>
          <div style="font-size:11px;font-weight:700;color:var(--purple);flex-shrink:0">
            <?= number_format((float)($prog['media_aud']??0),1) ?> ouv
          </div>
        </div>
        <?php endforeach ?>
        <?php else: ?>
        <div class="empty"><i class="bi bi-trophy"></i><span>Sem dados ainda</span></div>
        <?php endif ?>
      </div>
    </div>

    <!-- MELHOR HORA + PROGRAMA NO AR -->
    <div class="card">
      <div class="card-hd">
        <div class="card-hd-l">
          <div class="card-hd-ico" style="background:var(--cy3,rgba(0,229,184,.1))"><i class="bi bi-clock-fill" style="color:var(--cy);font-size:13px"></i></div>
          <div><div class="card-title">Inteligência</div><div class="card-sub">Padrões da audiência</div></div>
        </div>
      </div>
      <div class="card-body">
        <?php if($melhorHora): ?>
        <div style="margin-bottom:12px;padding:10px 12px;background:rgba(0,229,184,.07);border-radius:8px;border:1px solid rgba(0,229,184,.15)">
          <div style="font-size:10px;color:var(--t3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px">Melhor Hora do Dia</div>
          <div style="font-size:22px;font-weight:900;color:var(--cy)"><?= str_pad((string)(int)$melhorHora["hora"],2,"0",STR_PAD_LEFT) ?>:00</div>
          <div style="font-size:11px;color:var(--t2);margin-top:2px"><?= number_format((float)$melhorHora['media_listeners'],1) ?> ouvintes médios</div>
        </div>
        <?php endif ?>
        <?php if(!empty($progNoAr['programa'])): $pna=$progNoAr['programa']; ?>
        <div style="padding:10px 12px;background:rgba(120,80,255,.07);border-radius:8px;border:1px solid rgba(120,80,255,.15)">
          <div style="font-size:10px;color:var(--t3);text-transform:uppercase;letter-spacing:.6px;margin-bottom:4px">Programa Agora</div>
          <div style="font-size:14px;font-weight:700;color:var(--t1)"><?= htmlspecialchars($pna['nome']??'—') ?></div>
          <?php if(!empty($pna['locutor_nome'])): ?>
          <div style="font-size:11px;color:var(--t3);margin-top:2px"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($pna['locutor_nome']) ?></div>
          <?php endif ?>
          <div style="font-size:10px;color:var(--t3);margin-top:2px"><?= substr($pna['hora_inicio']??'',0,5) ?> – <?= substr($pna['hora_fim']??'',0,5) ?></div>
        </div>
        <?php endif ?>
        <?php if(!$melhorHora && empty($progNoAr['programa'])): ?>
        <div class="empty"><i class="bi bi-graph-up"></i><span>A acumular dados...</span></div>
        <?php endif ?>
      </div>
    </div>

  </div>

  <!-- LINHA 3: 3 COLUNAS -->
  <div class="grid-3">

    <!-- AUDIÊNCIA -->
    <div class="card">
      <div class="card-hd">
        <div class="card-hd-l">
          <div class="card-hd-ico" style="background:var(--purple3)"><i class="bi bi-people-fill" style="color:var(--purple);font-size:13px"></i></div>
          <div><div class="card-title">Audiência</div><div class="card-sub">Ouvintes em tempo real</div></div>
        </div>
        <a href="/public/pulso/<?= $sid ?>" class="card-link">PULSO →</a>
      </div>
      <div class="card-body">
        <div class="aud-grid">
          <div class="aud-item">
            <div class="aud-val" style="color:var(--cy)"><?= $listeners ?></div>
            <div class="aud-lbl">Ao vivo</div>
          </div>
          <div class="aud-item">
            <div class="aud-val" style="color:var(--purple)"><?= number_format($ouvAtivos,0,',','.') ?></div>
            <div class="aud-lbl">Registados</div>
          </div>
          <div class="aud-item">
            <div class="aud-val" style="color:var(--green)">+<?= $novosMes ?></div>
            <div class="aud-lbl">Novos mês</div>
          </div>
          <div class="aud-item">
            <div class="aud-val" style="color:var(--gold)"><?= $partHoje ?></div>
            <div class="aud-lbl">Partic. hoje</div>
          </div>
        </div>
        <?php if($topMusica): ?>
        <div class="aud-top">
          <div class="aud-top-l"><i class="bi bi-music-note-beamed" style="font-size:10px"></i>Mais pedida</div>
          <div class="aud-top-v"><?= htmlspecialchars($topMusica['musica']) ?></div>
        </div>
        <?php endif ?>
      </div>
    </div>

    <!-- NOTÍCIAS -->
    <div class="card">
      <div class="card-hd">
        <div class="card-hd-l">
          <div class="card-hd-ico" style="background:var(--red3)"><i class="bi bi-newspaper" style="color:var(--red);font-size:13px"></i></div>
          <div><div class="card-title">Newsroom</div><div class="card-sub"><?= $nNotActivas ?> activas<?= $nNotUrgent>0?' · '.$nNotUrgent.' urgente'.($nNotUrgent>1?'s':''):'' ?></div></div>
        </div>
        <a href="/public/news/<?= $sid ?>" class="card-link">News →</a>
      </div>
      <div class="card-body p0">
        <?php if($ultimasNot): ?>
          <?php foreach($ultimasNot as $n):
            $pCor = ['urgente'=>'var(--red)','alta'=>'var(--gold)','normal'=>'var(--t3)','baixa'=>'var(--t3)'][$n['prioridade']]??'var(--t3)';
            $pCls = ['urgente'=>'not-urg','alta'=>'not-alta','normal'=>'not-norm'][$n['prioridade']]??'not-norm';
          ?>
          <div class="not-item">
            <div class="not-dot" style="background:<?= $pCor ?>"></div>
            <div class="not-titulo"><?= htmlspecialchars($n['titulo']) ?></div>
            <div class="not-cat <?= $pCls ?>"><?= ucfirst($n['prioridade']) ?></div>
          </div>
          <?php endforeach ?>
        <?php else: ?>
          <div class="empty"><i class="bi bi-newspaper"></i><span>Sem notícias activas</span></div>
        <?php endif ?>
      </div>
    </div>

    <!-- ESCALAS HOJE -->
    <div class="card">
      <div class="card-hd">
        <div class="card-hd-l">
          <div class="card-hd-ico" style="background:var(--blue3)"><i class="bi bi-calendar3" style="color:var(--blue);font-size:13px"></i></div>
          <div><div class="card-title">Equipa Hoje</div><div class="card-sub"><?= count($escHoje) ?> em escala · <?= $nFunc ?> activos</div></div>
        </div>
        <a href="/public/rh/<?= $sid ?>/escalas" class="card-link">RH →</a>
      </div>
      <div class="card-body p0">
        <?php if($escHoje): ?>
          <?php
          $deptCores = ['locutor'=>'#a78bfa','jornalismo'=>'#f59e0b','tecnico'=>'#06b6d4','comercial'=>'#f59e0b','financeiro'=>'#10b981','administrativo'=>'#3b82f6','direcao'=>'#00e5ff','producao'=>'#f472b6'];
          foreach($escHoje as $e):
            $cor = $deptCores[$e['departamento']] ?? '#71717a';
          ?>
          <div class="esc-item">
            <div class="esc-hora"><?= substr($e['hora_entrada']??'',0,5) ?> – <?= substr($e['hora_saida']??'',0,5) ?></div>
            <div>
              <div class="esc-nome"><?= htmlspecialchars($e['nome']) ?></div>
              <div class="esc-dept" style="color:<?= $cor ?>"><?= htmlspecialchars($e['programa']??'') ?></div>
            </div>
          </div>
          <?php endforeach ?>
        <?php else: ?>
          <div class="empty"><i class="bi bi-calendar-x"></i><span>Sem escalas para hoje</span></div>
        <?php endif ?>
      </div>
    </div>

  </div>

  <!-- LINHA 4: PROGRAMAÇÃO + CAMPANHAS + PIPELINE -->
  <div class="grid-3">

    <!-- PROGRAMAÇÃO DO DIA -->
    <div class="card">
      <div class="card-hd">
        <div class="card-hd-l">
          <div class="card-hd-ico" style="background:var(--cy3)"><i class="bi bi-broadcast" style="color:var(--cy);font-size:13px"></i></div>
          <div><div class="card-title">Grelha — <?= ucfirst($diaSemana) ?></div><div class="card-sub"><?= $totalProgs ?> programas activos</div></div>
        </div>
        <a href="/public/programacao/<?= $sid ?>" class="card-link">Grade →</a>
      </div>
      <div class="card-body p0">
        <?php
        try {
          $progsHoje = $this->db->fetchAllAssociative(
            "SELECT * FROM plugin_prog_programas WHERE station_id=? AND JSON_CONTAINS(dias_semana,JSON_QUOTE(?)) AND ativo=1 ORDER BY hora_inicio ASC LIMIT 8",
            [$sid, $diaSemana]
          );
        } catch(\Exception $e) { $progsHoje=[]; }
        $cores = ['#00d4f5','#0fba81','#f0a500','#8b5cf6','#f03e3e','#e879a0','#3d7eff','#f59e0b'];
        if($progsHoje): foreach($progsHoje as $i=>$p):
          $isAtual = $progAtual && $progAtual['id']==$p['id'];
          $cor = $cores[$i%count($cores)];
        ?>
        <div class="prog-item<?= $isAtual?' prog-atual':'' ?>">
          <div class="prog-dot" style="background:<?= $cor ?>"></div>
          <div style="flex:1;min-width:0">
            <div class="prog-nome"><?= htmlspecialchars($p['nome']) ?></div>
            <?php if($isAtual): ?><div style="font-size:9px;color:var(--green);font-weight:700;margin-top:1px">● AO VIVO</div><?php endif ?>
          </div>
          <div class="prog-hora"><?= substr($p['hora_inicio'],0,5) ?><br><span style="opacity:.4;font-size:9px">↓</span><br><?= substr($p['hora_fim'],0,5) ?></div>
        </div>
        <?php endforeach; else: ?>
        <div class="empty"><i class="bi bi-calendar-x"></i><span>Sem programas hoje</span></div>
        <?php endif ?>
      </div>
    </div>

    <!-- CAMPANHAS -->
    <div class="card">
      <div class="card-hd">
        <div class="card-hd-l">
          <div class="card-hd-ico" style="background:var(--green3)"><i class="bi bi-megaphone-fill" style="color:var(--green);font-size:13px"></i></div>
          <div><div class="card-title">Campanhas Activas</div><div class="card-sub"><?= $nAnunc ?> anunciantes · <?= $nContratos ?> contratos</div></div>
        </div>
        <a href="/public/comercial/<?= $sid ?>/campanhas" class="card-link">Comercial →</a>
      </div>
      <div class="card-body p0">
        <?php if($campAtivas): ?>
          <?php foreach($campAtivas as $i=>$c):
            $pr = $c['spots_contratados']>0 ? $this->pct($c['spots_emitidos'],$c['spots_contratados']) : 0;
            $dias = max(0,(int)((strtotime($c['data_fim'])-time())/86400));
            $prCor = $pr>=100?'var(--green)':($pr>=50?'var(--gold)':'var(--cy)');
            $diasCor = $dias<=7?'var(--red)':'var(--t3)';
            $cor = $cores[$i%count($cores)];
          ?>
          <div class="camp-item">
            <div class="camp-av" style="background:<?= $cor ?>"><?= strtoupper(substr($c['nome'],0,1)) ?></div>
            <div class="camp-info">
              <div class="camp-nome"><?= htmlspecialchars($c['nome']) ?></div>
              <div class="camp-an"><?= htmlspecialchars($c['an']??'') ?></div>
            </div>
            <div class="camp-r">
              <div class="camp-pct" style="color:<?= $prCor ?>"><?= $pr ?>%</div>
              <div class="camp-bar"><div class="camp-bar-f" style="width:<?= $pr ?>%;background:<?= $prCor ?>"></div></div>
              <div class="camp-d" style="color:<?= $diasCor ?>"><?= $dias ?>d</div>
            </div>
          </div>
          <?php endforeach ?>
        <?php else: ?>
          <div class="empty"><i class="bi bi-megaphone"></i><span>Sem campanhas activas</span></div>
        <?php endif ?>
      </div>
    </div>

    <!-- PIPELINE -->
    <div class="card">
      <div class="card-hd">
        <div class="card-hd-l">
          <div class="card-hd-ico" style="background:var(--gold3)"><i class="bi bi-funnel-fill" style="color:var(--gold);font-size:13px"></i></div>
          <div><div class="card-title">Pipeline Comercial</div><div class="card-sub"><?= $nPropostas ?> em negociação</div></div>
        </div>
        <a href="/public/comercial/<?= $sid ?>/pipeline" class="card-link">Pipeline →</a>
      </div>
      <div class="card-body p0">
        <?php
        try {
          $pipeline = $this->db->fetchAllAssociative(
            "SELECT p.titulo,p.estado,p.valor_final,a.nome as an FROM rnb_propostas p LEFT JOIN rnb_anunciantes a ON a.id=p.anunciante_id WHERE p.station_id=? AND p.estado IN('enviada','em_negociacao','aceite') ORDER BY p.created_at DESC LIMIT 6",
            [$sid]
          );
        } catch(\Exception $e) { $pipeline=[]; }
        if($pipeline): foreach($pipeline as $p):
          $bCls = ['enviada'=>'pipe-env','em_negociacao'=>'pipe-neg','aceite'=>'pipe-ace'][$p['estado']]??'pipe-env';
          $bLbl = ['enviada'=>'Enviada','em_negociacao'=>'Negociação','aceite'=>'Aceite'][$p['estado']]??$p['estado'];
        ?>
        <div class="pipe-item">
          <span class="pipe-badge <?= $bCls ?>"><?= $bLbl ?></span>
          <span class="pipe-titulo"><?= htmlspecialchars($p['titulo']) ?></span>
          <span class="pipe-val"><?= $this->kz($p['valor_final'],true) ?></span>
        </div>
        <?php endforeach; else: ?>
        <div class="empty"><i class="bi bi-funnel"></i><span>Sem propostas em curso</span></div>
        <?php endif ?>
      </div>
    </div>

  </div>

</div><!-- /content -->

<div class="dash-footer">
  RNB OS · Centro de Controlo · Actualização automática a cada 60s · <span><?= date('H:i:s') ?></span>
</div>

</div><!-- /shell -->

<script>
// Relógio
(function tick(){
    const n=new Date();
    const el=document.getElementById('clock');
    if(el) el.textContent=[n.getHours(),n.getMinutes(),n.getSeconds()].map(v=>String(v).padStart(2,'0')).join(':');
    setTimeout(tick,1000);
})();

// Countdown programa
<?php if($progAtual): ?>
let progRem = <?= $remProg ?>;
(function progTick(){
    if(progRem<=0){document.querySelector('.progbar')?.classList.remove('show');return;}
    progRem--;
    const h=Math.floor(progRem/3600),m=Math.floor((progRem%3600)/60),s=progRem%60;
    const el=document.getElementById('prog-cd');
    if(el) el.textContent=(h>0?String(h).padStart(2,'0')+':':'')+(String(m).padStart(2,'0')+':'+String(s).padStart(2,'0'));
    setTimeout(progTick,1000);
})();
<?php endif ?>

// Timer NP
<?php if($npDuration>0): ?>
let npRem = <?= max(0,$npDuration-$npElapsed) ?>;
let npFonteActual = '<?= $npFonte ?? "azuracast" ?>';
let npDur = <?= $npDuration ?>;
(function npTick(){
    if(npRem<=0){npRem=0;}
    else npRem--;
    const m=Math.floor(npRem/60),s=npRem%60;
    const te=document.getElementById('np-timer');
    const pb=document.getElementById('np-prog-bar');
    if(te) te.textContent='-'+String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');
    if(pb && npDur>0){
        const pct=Math.min(100,Math.round((npDur-npRem)/npDur*100));
        pb.style.width=pct+'%';
        te.style.color=npRem<=10?'var(--red)':npRem<=30?'var(--gold)':'var(--green)';
    }
    setTimeout(npTick,1000);
})();
<?php endif ?>
</script>
</body>
</html>
<?php
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }
}
