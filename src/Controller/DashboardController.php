<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin\Controller;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;

class DashboardController
{
    private Connection $db;
    public function __construct(Connection $db) { $this->db = $db; }

    public function indexAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $now = date('Y-m-d H:i:s');
        $mes = date('Y-m');
        $hoje = date('Y-m-d');

        // ── AUDIÊNCIA ──────────────────────────────────────────
        try {
            $ouvintesAtivos    = (int)$this->db->fetchOne("SELECT COUNT(*) FROM pulso_ouvintes WHERE station_id=? AND ativo=1", [$sid]);
            $participacoesHoje = (int)$this->db->fetchOne("SELECT COUNT(*) FROM pulso_participacoes WHERE station_id=? AND DATE(created_at)=?", [$sid, $hoje]);
            $novosMes          = (int)$this->db->fetchOne("SELECT COUNT(*) FROM pulso_ouvintes WHERE station_id=? AND DATE_FORMAT(created_at,'%Y-%m')=?", [$sid, $mes]);
            $topMusica         = $this->db->fetchAssociative("SELECT musica, COUNT(*) as c FROM pulso_participacoes WHERE station_id=? AND musica IS NOT NULL AND musica!='' GROUP BY musica ORDER BY c DESC LIMIT 1", [$sid]);
        } catch(\Exception $e) { $ouvintesAtivos=$participacoesHoje=$novosMes=0; $topMusica=null; }

        // ── FINANÇAS ───────────────────────────────────────────
        try {
            $receitaMes     = (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total),0) FROM fp_lancamentos WHERE station_id=? AND tipo='receita' AND DATE_FORMAT(data_lancamento,'%Y-%m')=? AND estado!='cancelado'", [$sid, $mes]);
            $despesaMes     = (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total),0) FROM fp_lancamentos WHERE station_id=? AND tipo='despesa' AND DATE_FORMAT(data_lancamento,'%Y-%m')=? AND estado!='cancelado'", [$sid, $mes]);
            $aReceber       = (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total-valor_pago),0) FROM fp_contas_movimento WHERE station_id=? AND tipo='receber' AND estado='pendente'", [$sid]);
            $aPagar         = (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total-valor_pago),0) FROM fp_contas_movimento WHERE station_id=? AND tipo='pagar' AND estado='pendente'", [$sid]);
            $vencemHoje     = $this->db->fetchAllAssociative("SELECT * FROM fp_contas_movimento WHERE station_id=? AND estado='pendente' AND data_vencimento<=? ORDER BY data_vencimento ASC LIMIT 5", [$sid, $hoje]);
            $saldoBancario  = (float)$this->db->fetchOne("SELECT COALESCE(SUM(saldo_atual),0) FROM fp_contas_bancarias WHERE station_id=?", [$sid]);
        } catch(\Exception $e) { $receitaMes=$despesaMes=$aReceber=$aPagar=$saldoBancario=0; $vencemHoje=[]; }

        // ── COMERCIAL ──────────────────────────────────────────
        try {
            $anunciantesAtivos  = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_anunciantes WHERE station_id=? AND estado='activo'", [$sid]);
            $campanhasAtivas    = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_campanhas WHERE station_id=? AND estado='activa'", [$sid]);
            $propostasPendentes = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_propostas WHERE station_id=? AND estado IN('enviada','em_negociacao')", [$sid]);
            $receitaComercial   = (float)$this->db->fetchOne("SELECT COALESCE(SUM(valor_total),0) FROM rnb_contratos WHERE station_id=? AND estado='activo'", [$sid]);
            $campanhasTop       = $this->db->fetchAllAssociative("SELECT c.nome, a.nome as an, c.spots_emitidos, c.spots_contratados, c.data_fim FROM rnb_campanhas c LEFT JOIN rnb_anunciantes a ON a.id=c.anunciante_id WHERE c.station_id=? AND c.estado='activa' ORDER BY c.data_fim ASC LIMIT 4", [$sid]);
            $campsFimSemana     = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_campanhas WHERE station_id=? AND estado='activa' AND data_fim<=DATE_ADD(?,INTERVAL 7 DAY)", [$sid, $hoje]);
        } catch(\Exception $e) { $anunciantesAtivos=$campanhasAtivas=$propostasPendentes=$receitaComercial=$campsFimSemana=0; $campanhasTop=[]; }

        // ── PROGRAMAÇÃO ACTUAL ─────────────────────────────────
        try {
            $agora = date('H:i:s');
            $diaSemana = strtolower(['domingo','segunda','terca','quarta','quinta','sexta','sabado'][date('w')]);
            $progAtual = $this->db->fetchAssociative(
                "SELECT * FROM plugin_prog_programas WHERE station_id=? AND JSON_CONTAINS(dias_semana, JSON_QUOTE(?)) AND horario_inicio<=? AND horario_fim>=? AND ativo=1 LIMIT 1",
                [$sid, $diaSemana, $agora, $agora]
            );
            $proxProg = $this->db->fetchAssociative(
                "SELECT * FROM plugin_prog_programas WHERE station_id=? AND JSON_CONTAINS(dias_semana, JSON_QUOTE(?)) AND horario_inicio>? AND ativo=1 ORDER BY horario_inicio ASC LIMIT 1",
                [$sid, $diaSemana, $agora]
            );
        } catch(\Exception $e) { $progAtual=null; $proxProg=null; }

        // ── NOW PLAYING via API AzuraCast ──────────────────────
        $nowPlaying = null;
        $listeners  = 0;
        try {
            $apiUrl = "http://localhost/api/station/{$sid}/nowplaying";
            $json   = @file_get_contents($apiUrl);
            if ($json) {
                $np = json_decode($json, true);
                $nowPlaying = $np['now_playing']['song'] ?? null;
                $listeners  = $np['listeners']['current'] ?? 0;
            }
        } catch(\Exception $e) {}

        // ── ALERTAS ────────────────────────────────────────────
        $alertas = [];
        if ($campsFimSemana > 0)
            $alertas[] = ['tipo'=>'gold','ico'=>'megaphone','msg'=>"{$campsFimSemana} campanha(s) terminam nos próximos 7 dias"];
        if ($propostasPendentes > 0)
            $alertas[] = ['tipo'=>'blue','ico'=>'file-earmark-text','msg'=>"{$propostasPendentes} proposta(s) aguardam resposta"];
        if (count($vencemHoje) > 0)
            $alertas[] = ['tipo'=>'red','ico'=>'exclamation-triangle','msg'=>count($vencemHoje)." conta(s) vencidas ou a vencer hoje"];
        if ($receitaMes > $despesaMes * 1.5)
            $alertas[] = ['tipo'=>'green','ico'=>'graph-up-arrow','msg'=>"Mês positivo — receita acima das despesas"];

        // ── FORMATOS ───────────────────────────────────────────
        $fmtKz  = fn($v) => number_format((float)$v, 0, ',', '.') . ' Kz';
        $fmtNum = fn($v) => number_format((float)$v, 0, ',', '.');
        $lucroMes = $receitaMes - $despesaMes;
        $mesLabel = strtr(date('F Y'), ['January'=>'Janeiro','February'=>'Fevereiro','March'=>'Março','April'=>'Abril','May'=>'Maio','June'=>'Junho','July'=>'Julho','August'=>'Agosto','September'=>'Setembro','October'=>'Outubro','November'=>'Novembro','December'=>'Dezembro']);

        // ── RENDER ─────────────────────────────────────────────
        ob_start();
        ?><!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="refresh" content="60">
<title>RNB OS — Dashboard Executivo</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
    --bg-0:#050510;--bg-1:#0f0f1f;--bg-2:#1a1a2e;--bg-3:#252538;--bg-4:#2e2e45;
    --accent:#00e5ff;--accent2:#7c3aed;
    --green:#10b981;--red:#ef4444;--gold:#fbbf24;--purple:#a78bfa;--blue:#3b82f6;--pink:#f472b6;
    --text-1:#ffffff;--text-2:#a1a1aa;--text-3:#71717a;
    --border:rgba(255,255,255,.08);--border2:rgba(255,255,255,.14);
    --ff:'Inter',-apple-system,sans-serif;
    --tr:all .3s cubic-bezier(.4,0,.2,1);
}
html,body{min-height:100vh;font-family:var(--ff);background:var(--bg-0);color:var(--text-1);font-size:13px;-webkit-font-smoothing:antialiased;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
    background:radial-gradient(circle at 10% 30%,rgba(124,58,237,.07),transparent 50%),
               radial-gradient(circle at 90% 70%,rgba(0,229,255,.05),transparent 50%),
               radial-gradient(circle at 50% 100%,rgba(16,185,129,.03),transparent 40%)}

/* ── HEADER ── */
.hdr{
    position:sticky;top:0;z-index:200;
    background:rgba(5,5,16,.9);backdrop-filter:blur(20px);
    border-bottom:1px solid var(--border);
    padding:0 28px;height:60px;
    display:flex;align-items:center;gap:20px;
}
.hdr-logo{display:flex;align-items:center;gap:10px;padding-right:20px;border-right:1px solid var(--border)}
.hdr-logo-ico{width:36px;height:36px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 0 24px rgba(0,229,255,.2)}
.hdr-logo-ico svg{width:26px;height:26px}
.hdr-logo-text{line-height:1}
.hdr-logo-nome{font-size:15px;font-weight:900;background:linear-gradient(135deg,var(--accent),#fff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hdr-logo-sub{font-size:8px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--text-3)}

/* NP STRIP */
.np-strip{
    flex:1;display:flex;align-items:center;gap:12px;
    background:rgba(26,26,46,.7);border:1px solid var(--border);
    border-radius:10px;padding:0 14px;height:38px;
    position:relative;overflow:hidden;
}
.np-strip::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,var(--green),var(--accent),transparent);opacity:.5}
.np-dot{width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 8px var(--green);animation:pulse 1.4s infinite;flex-shrink:0}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.np-song{font-size:13px;font-weight:700;flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.np-art{color:var(--text-2);font-weight:400}
.np-list{display:flex;align-items:center;gap:5px;font-size:12px;color:var(--text-2);flex-shrink:0}
.np-list b{color:var(--accent);font-size:14px;font-weight:800}

.hdr-r{display:flex;align-items:center;gap:12px;flex-shrink:0}
.hdr-clock{font-size:18px;font-weight:800;font-variant-numeric:tabular-nums;letter-spacing:-.5px}
.hdr-date{font-size:10px;color:var(--text-3);text-align:right}

/* MÓDULOS QUICK ACCESS */
.hdr-mods{display:flex;gap:6px}
.hm{display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;background:rgba(255,255,255,.04);border:1px solid var(--border);text-decoration:none;color:var(--text-2);font-size:11px;font-weight:600;transition:var(--tr)}
.hm:hover{background:rgba(255,255,255,.08);color:var(--text-1);text-decoration:none}
.hm i{font-size:14px}
.hm.ac{background:linear-gradient(135deg,rgba(0,229,255,.08),rgba(124,58,237,.08));border-color:rgba(0,229,255,.2);color:var(--accent)}

/* ── PROG ACTUAL ── */
.prog-bar{
    background:linear-gradient(90deg,rgba(16,185,129,.07),rgba(16,185,129,.02));
    border-bottom:1px solid rgba(16,185,129,.12);
    padding:8px 28px;
    display:flex;align-items:center;gap:16px;
    font-size:12px;position:relative;z-index:1;
}
.pb-live{display:flex;align-items:center;gap:6px;color:var(--green);font-weight:700;font-size:11px}
.pb-prog{font-weight:700;color:var(--text-1);font-size:13px}
.pb-hor{color:var(--text-3)}
.pb-prox{margin-left:auto;font-size:11px;color:var(--text-3)}
.pb-prox span{color:var(--gold);font-weight:600}

/* ── LAYOUT ── */
.page{padding:22px 28px;position:relative;z-index:1}

/* ── ALERTAS ── */
.alertas{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px}
.alerta{display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:10px;font-size:11px;font-weight:600;border:1px solid}
.alerta.gold{background:rgba(251,191,36,.06);border-color:rgba(251,191,36,.2);color:var(--gold)}
.alerta.blue{background:rgba(59,130,246,.06);border-color:rgba(59,130,246,.2);color:var(--blue)}
.alerta.red{background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.2);color:var(--red)}
.alerta.green{background:rgba(16,185,129,.06);border-color:rgba(16,185,129,.2);color:var(--green)}

/* ── KPI PRINCIPAL ── */
.kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px}
.kpi{
    background:linear-gradient(135deg,rgba(26,26,46,.95),rgba(21,21,32,.95));
    border:1px solid var(--border);border-radius:16px;
    padding:18px;position:relative;overflow:hidden;
    transition:var(--tr);cursor:default;
}
.kpi:hover{border-color:var(--border2);transform:translateY(-2px)}
.kpi::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;border-radius:16px 16px 0 0}
.kpi.cy::after{background:linear-gradient(90deg,var(--accent),var(--accent2))}
.kpi.gr::after{background:var(--green)}.kpi.gd::after{background:var(--gold)}.kpi.pu::after{background:var(--purple)}.kpi.bl::after{background:var(--blue)}.kpi.pk::after{background:var(--pink)}.kpi.rd::after{background:var(--red)}
.kpi-hd{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px}
.kpi-mod{font-size:8px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--text-3)}
.kpi-ico{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px}
.kpi-v{font-size:26px;font-weight:900;line-height:1;margin-bottom:4px}
.kpi-l{font-size:10px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.6px}
.kpi-s{font-size:11px;color:var(--text-3);margin-top:6px;display:flex;align-items:center;gap:4px}
.kpi-s.up{color:var(--green)}.kpi-s.dn{color:var(--red)}

/* ── GRID PRINCIPAL ── */
.grid-main{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px;margin-bottom:18px}
.grid-wide{display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-bottom:18px}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:18px}

/* ── CARDS ── */
.card{
    background:linear-gradient(135deg,rgba(26,26,46,.95),rgba(21,21,32,.95));
    border:1px solid var(--border);border-radius:16px;
    padding:18px;position:relative;overflow:hidden;
}
.card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;opacity:0;transition:var(--tr)}
.card:hover::before{opacity:1}
.card.cy::before{background:linear-gradient(90deg,var(--accent),var(--accent2))}
.card.gr::before{background:linear-gradient(90deg,var(--green),var(--accent))}
.card.gd::before{background:linear-gradient(90deg,var(--gold),var(--accent2))}
.card.bl::before{background:linear-gradient(90deg,var(--blue),var(--purple))}
.ct{font-size:12px;font-weight:700;color:var(--text-1);margin-bottom:14px;display:flex;align-items:center;gap:7px}
.ct i{font-size:14px}
.ct a{margin-left:auto;font-size:10px;font-weight:600;color:var(--text-3);text-decoration:none;padding:3px 8px;border-radius:5px;border:1px solid var(--border);transition:var(--tr)}
.ct a:hover{color:var(--text-2);background:rgba(255,255,255,.04)}

/* FINANÇAS CARD */
.fin-row{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px}
.fin-blk{}
.fin-lbl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text-3);margin-bottom:4px}
.fin-val{font-size:20px;font-weight:900;line-height:1}
.fin-saldo{text-align:right}
.lucro-bar{height:4px;background:rgba(255,255,255,.06);border-radius:2px;overflow:hidden;margin-bottom:12px}
.lucro-fill{height:100%;border-radius:2px;transition:width .6s ease}
.fin-items{display:flex;flex-direction:column;gap:5px}
.fi-item{display:flex;align-items:center;justify-content:space-between;padding:7px 10px;background:rgba(255,255,255,.03);border-radius:8px;border:1px solid var(--border)}
.fi-item-l{font-size:11px;font-weight:600;color:var(--text-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px}
.fi-item-v{font-size:11px;font-weight:700;flex-shrink:0}
.fi-item-d{font-size:9px;color:var(--red);margin-left:4px}

/* CAMPANHAS */
.camp-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.camp-item:last-child{border-bottom:none;padding-bottom:0}
.camp-av{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#000;flex-shrink:0}
.camp-info{flex:1;min-width:0}
.camp-nome{font-size:11px;font-weight:700;color:var(--text-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.camp-an{font-size:9px;color:var(--text-3)}
.camp-prog{flex-shrink:0;text-align:right}
.camp-pct{font-size:11px;font-weight:700}
.camp-bar{width:60px;height:3px;background:rgba(255,255,255,.06);border-radius:2px;overflow:hidden;margin-top:3px}
.camp-fill{height:100%;border-radius:2px}
.camp-dias{font-size:9px;margin-top:2px}

/* AUDIÊNCIA */
.aud-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.aud-kpi{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;padding:10px;text-align:center}
.aud-v{font-size:22px;font-weight:900;line-height:1}
.aud-l{font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px;margin-top:3px}
.top-mus{margin-top:12px;padding:9px 12px;background:rgba(0,229,255,.04);border:1px solid rgba(0,229,255,.1);border-radius:9px}
.top-mus-l{font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--accent);margin-bottom:3px}
.top-mus-v{font-size:12px;font-weight:700;color:var(--text-1)}

/* PROPOSTAS PIPELINE */
.pipe-item{display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.pipe-item:last-child{border-bottom:none}
.pipe-est{font-size:8px;font-weight:700;padding:2px 7px;border-radius:10px}
.pe-env{background:rgba(59,130,246,.1);color:var(--blue)}
.pe-neg{background:rgba(251,191,36,.1);color:var(--gold)}
.pe-acl{background:rgba(16,185,129,.1);color:var(--green)}
.pipe-t{font-size:11px;font-weight:600;color:var(--text-1);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pipe-v{font-size:11px;font-weight:700;color:var(--gold);flex-shrink:0}

/* VENCIMENTOS */
.venc-item{display:flex;align-items:center;gap:8px;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.venc-item:last-child{border-bottom:none}
.venc-ico{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
.venc-t{font-size:11px;font-weight:600;color:var(--text-1);flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.venc-d{font-size:10px;font-weight:700;flex-shrink:0}

/* FOOTER */
.dash-footer{text-align:center;padding:16px;font-size:10px;color:var(--text-3);border-top:1px solid var(--border);margin-top:4px}
.dash-footer span{color:var(--accent)}

@media(max-width:1200px){.kpi-row{grid-template-columns:repeat(2,1fr)}.grid-main{grid-template-columns:1fr 1fr}.grid-wide{grid-template-columns:1fr}.grid-3{grid-template-columns:1fr 1fr}}
@media(max-width:768px){.kpi-row{grid-template-columns:1fr 1fr}.grid-main,.grid-wide,.grid-3{grid-template-columns:1fr}.hdr-mods{display:none}}
</style>
</head>
<body>
<?php $_rnb_sid=$sid; $_rnb_atual='dashboard'; require dirname(__DIR__,2).'/public/rnb-nav.php'; ?>
<!-- HEADER -->
<div class="hdr">
    <div class="hdr-logo">
        <div class="hdr-logo-ico">
            <svg viewBox="0 0 60 40" xmlns="http://www.w3.org/2000/svg">
                <rect x="2" y="6" width="2.5" height="28" rx="1.2" fill="#0099ff"/>
                <rect x="5.5" y="6" width="2.5" height="6" rx="1.2" fill="#00aaff"/>
                <rect x="9" y="5" width="2.5" height="8" rx="1.2" fill="#00ccff"/>
                <rect x="12.5" y="6" width="2.5" height="7" rx="1.2" fill="#33aaff"/>
                <rect x="15.5" y="8" width="2.5" height="5" rx="1.2" fill="#5599ff"/>
                <rect x="9" y="16" width="2.5" height="7" rx="1.2" fill="#7755ff"/>
                <rect x="12.5" y="18" width="2.5" height="8" rx="1.2" fill="#9933ff"/>
                <rect x="21" y="6" width="2.5" height="28" rx="1.2" fill="#aa22ff"/>
                <rect x="33" y="6" width="2.5" height="28" rx="1.2" fill="#ff5566"/>
                <rect x="39" y="6" width="2.5" height="28" rx="1.2" fill="#ff8800"/>
                <rect x="42.5" y="6" width="2.5" height="6" rx="1.2" fill="#ffaa00"/>
                <rect x="46" y="5" width="2.5" height="8" rx="1.2" fill="#ffcc00"/>
                <rect x="49.5" y="6" width="2.5" height="7" rx="1.2" fill="#ffdd22"/>
            </svg>
        </div>
        <div class="hdr-logo-text">
            <div class="hdr-logo-nome">RNB OS</div>
            <div class="hdr-logo-sub">Dashboard Executivo</div>
        </div>
    </div>

    <!-- NOW PLAYING -->
    <div class="np-strip">
        <div class="np-dot"></div>
        <span class="np-song">
            <?php if($nowPlaying): ?>
                <b><?= htmlspecialchars($nowPlaying['title']??'') ?></b>
                <span class="np-art"> — <?= htmlspecialchars($nowPlaying['artist']??'') ?></span>
            <?php else: ?>
                <span style="color:var(--text-3)">A carregar stream...</span>
            <?php endif ?>
        </span>
        <div class="np-list"><i class="bi bi-headphones"></i><b><?= $listeners ?></b>&nbsp;ao vivo</div>
    </div>

    <!-- MÓDULOS -->
    <div class="hdr-mods">
        <a href="/public/pulso/<?= $sid ?>" class="hm"><i class="bi bi-people-fill"></i>Audiência</a>
        <a href="/public/comercial/<?= $sid ?>" class="hm"><i class="bi bi-building"></i>Comercial</a>
        <a href="/public/financas/<?= $sid ?>" class="hm"><i class="bi bi-wallet2"></i>Finanças</a>
        <a href="/public/news/<?= $sid ?>" class="hm"><i class="bi bi-newspaper"></i>News</a>
        <a href="/public/rh/<?= $sid ?>" class="hm"><i class="bi bi-people-fill"></i>RH</a>
        <a href="/pulso/locutor" class="hm"><i class="bi bi-mic-fill"></i>Studio</a>
        <a href="/public/programacao/<?= $sid ?>" class="hm"><i class="bi bi-calendar3"></i>Programação</a>
        <a href="/public/dashboard/<?= $sid ?>" class="hm ac"><i class="bi bi-speedometer2"></i>Dashboard</a>
    </div>

    <!-- RELÓGIO -->
    <div class="hdr-r">
        <div>
            <div class="hdr-clock" id="clock">--:--:--</div>
            <div class="hdr-date"><?= date('d/m/Y') ?></div>
        </div>
    </div>
</div>

<!-- PROGRAMA ACTUAL -->
<?php if($progAtual): ?>
<div class="prog-bar">
    <div class="pb-live"><div class="np-dot"></div> AO VIVO</div>
    <div class="pb-prog"><?= htmlspecialchars($progAtual['programa'] ?? $progAtual['nome'] ?? 'Programa') ?></div>
    <div class="pb-hor"><?= substr($progAtual['horario_inicio'],0,5) ?> → <?= substr($progAtual['horario_fim'],0,5) ?></div>
    <?php if($proxProg): ?>
    <div class="pb-prox">A seguir: <span><?= htmlspecialchars($proxProg['programa'] ?? $proxProg['nome'] ?? '') ?></span> às <?= substr($proxProg['horario_inicio'],0,5) ?></div>
    <?php endif ?>
</div>
<?php endif ?>

<div class="page">

    <!-- ALERTAS -->
    <?php if($alertas): ?>
    <div class="alertas">
        <?php foreach($alertas as $al): ?>
        <div class="alerta <?= $al['tipo'] ?>">
            <i class="bi bi-<?= $al['ico'] ?>"></i>
            <?= htmlspecialchars($al['msg']) ?>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- KPIs PRINCIPAIS -->
    <div class="kpi-row">
        <!-- Receita do mês -->
        <div class="kpi gd">
            <div class="kpi-hd">
                <div class="kpi-mod">Finanças</div>
                <div class="kpi-ico" style="background:rgba(251,191,36,.1)">💰</div>
            </div>
            <div class="kpi-v" style="color:var(--gold)"><?= $fmtKz($receitaMes) ?></div>
            <div class="kpi-l">Receita — <?= $mesLabel ?></div>
            <div class="kpi-s <?= $lucroMes >= 0 ? 'up' : 'dn' ?>">
                <i class="bi bi-<?= $lucroMes >= 0 ? 'arrow-up' : 'arrow-down' ?>-circle"></i>
                <?= $lucroMes >= 0 ? 'Lucro' : 'Prejuízo' ?>: <?= $fmtKz(abs($lucroMes)) ?>
            </div>
        </div>

        <!-- Ouvintes -->
        <div class="kpi cy">
            <div class="kpi-hd">
                <div class="kpi-mod">Audiência</div>
                <div class="kpi-ico" style="background:rgba(0,229,255,.1)">🎧</div>
            </div>
            <div class="kpi-v" style="color:var(--accent)"><?= $fmtNum($ouvintesAtivos) ?></div>
            <div class="kpi-l">Ouvintes Registados</div>
            <div class="kpi-s"><i class="bi bi-person-plus"></i><?= $fmtNum($novosMes) ?> novos este mês</div>
        </div>

        <!-- Campanhas -->
        <div class="kpi gr">
            <div class="kpi-hd">
                <div class="kpi-mod">Comercial</div>
                <div class="kpi-ico" style="background:rgba(16,185,129,.1)">📢</div>
            </div>
            <div class="kpi-v" style="color:var(--green)"><?= $campanhasAtivas ?></div>
            <div class="kpi-l">Campanhas Activas</div>
            <div class="kpi-s"><?= $fmtKz($receitaComercial) ?> em contratos activos</div>
        </div>

        <!-- Saldo bancário -->
        <div class="kpi bl">
            <div class="kpi-hd">
                <div class="kpi-mod">Finanças</div>
                <div class="kpi-ico" style="background:rgba(59,130,246,.1)">🏦</div>
            </div>
            <div class="kpi-v" style="color:var(--blue)"><?= $fmtKz($saldoBancario) ?></div>
            <div class="kpi-l">Saldo Bancário Total</div>
            <div class="kpi-s">
                <i class="bi bi-arrow-up-circle" style="color:var(--green)"></i><?= $fmtKz($aReceber) ?> a receber
            </div>
        </div>
    </div>

    <!-- LINHA 2: FINANÇAS + CAMPANHAS + AUDIÊNCIA -->
    <div class="grid-main">

        <!-- FINANÇAS -->
        <div class="card gd">
            <div class="ct" style="color:var(--gold)">
                <i class="bi bi-wallet2"></i>Finanças do Mês
                <a href="/public/financas/<?= $sid ?>">Abrir →</a>
            </div>
            <div class="fin-row">
                <div class="fin-blk">
                    <div class="fin-lbl">Receita</div>
                    <div class="fin-val" style="color:var(--green)"><?= $fmtKz($receitaMes) ?></div>
                </div>
                <div class="fin-blk">
                    <div class="fin-lbl">Despesa</div>
                    <div class="fin-val" style="color:var(--red)"><?= $fmtKz($despesaMes) ?></div>
                </div>
                <div class="fin-saldo">
                    <div class="fin-lbl">Resultado</div>
                    <div class="fin-val" style="color:<?= $lucroMes >= 0 ? 'var(--green)' : 'var(--red)' ?>"><?= ($lucroMes >= 0 ? '+' : '') . $fmtKz($lucroMes) ?></div>
                </div>
            </div>
            <?php $pctLucro = $receitaMes > 0 ? min(100, max(0, ($lucroMes/$receitaMes)*100)) : 0 ?>
            <div class="lucro-bar">
                <div class="lucro-fill" style="width:<?= $pctLucro ?>%;background:<?= $lucroMes >= 0 ? 'var(--green)' : 'var(--red)' ?>"></div>
            </div>

            <!-- Vencimentos -->
            <?php if($vencemHoje): ?>
            <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--red);margin-bottom:7px">
                <i class="bi bi-exclamation-triangle"></i> Vencidos / A vencer
            </div>
            <div class="fin-items">
                <?php foreach(array_slice($vencemHoje,0,3) as $v):
                    $atr = (strtotime($v['data_vencimento']) < strtotime($hoje));
                ?>
                <div class="fi-item">
                    <span class="fi-item-l"><?= htmlspecialchars($v['descricao']??$v['entidade']??'') ?></span>
                    <span class="fi-item-v" style="color:<?= $v['tipo']==='receber' ? 'var(--green)' : 'var(--red)' ?>">
                        <?= $fmtKz($v['valor_total']-$v['valor_pago']) ?>
                    </span>
                    <?php if($atr): ?><span class="fi-item-d">ATRASADO</span><?php endif ?>
                </div>
                <?php endforeach ?>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:1rem;color:var(--text-3);font-size:11px">
                <i class="bi bi-check-circle" style="color:var(--green)"></i> Sem vencimentos em atraso
            </div>
            <?php endif ?>

            <div style="display:flex;gap:8px;margin-top:12px">
                <div style="flex:1;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:8px;padding:8px;text-align:center">
                    <div style="font-size:12px;font-weight:800;color:var(--green)"><?= $fmtKz($aReceber) ?></div>
                    <div style="font-size:8px;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px;margin-top:2px">A Receber</div>
                </div>
                <div style="flex:1;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:8px;padding:8px;text-align:center">
                    <div style="font-size:12px;font-weight:800;color:var(--red)"><?= $fmtKz($aPagar) ?></div>
                    <div style="font-size:8px;color:var(--text-3);text-transform:uppercase;letter-spacing:.5px;margin-top:2px">A Pagar</div>
                </div>
            </div>
        </div>

        <!-- CAMPANHAS -->
        <div class="card gr">
            <div class="ct" style="color:var(--green)">
                <i class="bi bi-megaphone-fill"></i>Campanhas Activas
                <a href="/public/comercial/<?= $sid ?>/campanhas">Ver todas →</a>
            </div>
            <?php if($campanhasTop): ?>
            <?php $cores = ['#fbbf24','#10b981','#00e5ff','#a78bfa','#f472b6']; ?>
            <?php foreach($campanhasTop as $i=>$c):
                $pr = $c['spots_contratados']>0 ? round($c['spots_emitidos']/$c['spots_contratados']*100) : 0;
                $dias = max(0,(int)((strtotime($c['data_fim'])-time())/86400));
                $cor = $cores[$i%count($cores)];
                $prCor = $pr>=100?'var(--green)':($pr>=50?'var(--gold)':'var(--blue)');
            ?>
            <div class="camp-item">
                <div class="camp-av" style="background:<?= $cor ?>"><?= strtoupper(substr($c['nome'],0,1)) ?></div>
                <div class="camp-info">
                    <div class="camp-nome"><?= htmlspecialchars($c['nome']) ?></div>
                    <div class="camp-an"><?= htmlspecialchars($c['an']??'') ?></div>
                </div>
                <div class="camp-prog">
                    <div class="camp-pct" style="color:<?= $prCor ?>"><?= $pr ?>%</div>
                    <div class="camp-bar"><div class="camp-fill" style="width:<?= $pr ?>%;background:<?= $prCor ?>"></div></div>
                    <div class="camp-dias" style="color:<?= $dias<=7?'var(--red)':'var(--text-3)' ?>"><?= $dias ?>d</div>
                </div>
            </div>
            <?php endforeach ?>
            <?php else: ?>
            <div style="text-align:center;padding:2rem;color:var(--text-3)">
                <i class="bi bi-megaphone" style="font-size:28px;opacity:.15;display:block;margin-bottom:8px"></i>
                Sem campanhas activas
            </div>
            <?php endif ?>
        </div>

        <!-- AUDIÊNCIA -->
        <div class="card cy">
            <div class="ct" style="color:var(--accent)">
                <i class="bi bi-people-fill"></i>Audiência
                <a href="/public/pulso/<?= $sid ?>">Ver PULSO →</a>
            </div>
            <div class="aud-grid">
                <div class="aud-kpi">
                    <div class="aud-v" style="color:var(--accent)"><?= $fmtNum($listeners) ?></div>
                    <div class="aud-l">Ao Vivo</div>
                </div>
                <div class="aud-kpi">
                    <div class="aud-v" style="color:var(--text-1)"><?= $fmtNum($ouvintesAtivos) ?></div>
                    <div class="aud-l">Registados</div>
                </div>
                <div class="aud-kpi">
                    <div class="aud-v" style="color:var(--green)"><?= $fmtNum($novosMes) ?></div>
                    <div class="aud-l">Novos este mês</div>
                </div>
                <div class="aud-kpi">
                    <div class="aud-v" style="color:var(--gold)"><?= $fmtNum($participacoesHoje) ?></div>
                    <div class="aud-l">Participações hoje</div>
                </div>
            </div>
            <?php if($topMusica): ?>
            <div class="top-mus">
                <div class="top-mus-l"><i class="bi bi-music-note"></i> Mais pedida</div>
                <div class="top-mus-v"><?= htmlspecialchars($topMusica['musica']) ?></div>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- LINHA 3: PIPELINE + COMERCIAL STATS -->
    <div class="grid-wide">
        <!-- PIPELINE COMERCIAL -->
        <div class="card bl">
            <div class="ct" style="color:var(--blue)">
                <i class="bi bi-funnel-fill"></i>Pipeline Comercial
                <a href="/public/comercial/<?= $sid ?>/pipeline">Ver pipeline →</a>
            </div>
            <?php
            try {
                $pipeline = $this->db->fetchAllAssociative(
                    "SELECT p.titulo, p.estado, p.valor_final, a.nome as an
                     FROM rnb_propostas p
                     LEFT JOIN rnb_anunciantes a ON a.id=p.anunciante_id
                     WHERE p.station_id=? AND p.estado IN('enviada','em_negociacao','aceite')
                     ORDER BY p.created_at DESC LIMIT 6",
                    [$sid]
                );
            } catch(\Exception $e) { $pipeline = []; }
            ?>
            <?php if($pipeline): ?>
            <?php foreach($pipeline as $p):
                $estCls = ['enviada'=>'pe-env','em_negociacao'=>'pe-neg','aceite'=>'pe-acl'][$p['estado']]??'pe-env';
                $estLbl = ['enviada'=>'Enviada','em_negociacao'=>'Negociação','aceite'=>'Aceite'][$p['estado']]??$p['estado'];
            ?>
            <div class="pipe-item">
                <span class="pipe-est <?= $estCls ?>"><?= $estLbl ?></span>
                <span class="pipe-t"><?= htmlspecialchars($p['titulo']) ?> <span style="color:var(--text-3);font-size:10px">· <?= htmlspecialchars($p['an']??'') ?></span></span>
                <span class="pipe-v"><?= $fmtKz($p['valor_final']) ?></span>
            </div>
            <?php endforeach ?>
            <?php else: ?>
            <div style="text-align:center;padding:2rem;color:var(--text-3)">
                <i class="bi bi-funnel" style="font-size:28px;opacity:.15;display:block;margin-bottom:8px"></i>
                Sem propostas em curso
            </div>
            <?php endif ?>
        </div>

        <!-- STATS COMERCIAL -->
        <div class="card gd">
            <div class="ct" style="color:var(--gold)">
                <i class="bi bi-bar-chart-fill"></i>Comercial
                <a href="/public/comercial/<?= $sid ?>">Abrir →</a>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px">
                <?php
                $statsC = [
                    ['Anunciantes Activos', $anunciantesAtivos, 'building', 'var(--blue)'],
                    ['Campanhas Activas',   $campanhasAtivas,   'megaphone','var(--green)'],
                    ['Propostas Pendentes', $propostasPendentes,'file-earmark-text','var(--gold)'],
                    ['Terminam em 7 dias',  $campsFimSemana,    'clock-history',   'var(--red)'],
                ];
                foreach($statsC as [$lbl,$val,$ico,$cor]): ?>
                <div style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:9px">
                    <i class="bi bi-<?= $ico ?>" style="color:<?= $cor ?>;font-size:16px;flex-shrink:0"></i>
                    <span style="flex:1;font-size:12px;color:var(--text-2)"><?= $lbl ?></span>
                    <span style="font-size:16px;font-weight:800;color:<?= $cor ?>"><?= $val ?></span>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>

</div><!-- /page -->

<div class="dash-footer">
    RNB OS — Dashboard Executivo · Actualização automática a cada 60s · <span><?= date('H:i:s') ?></span>
</div>

<script>
// Relógio
function tick(){
    const n=new Date();
    document.getElementById('clock').textContent=
        [n.getHours(),n.getMinutes(),n.getSeconds()].map(v=>String(v).padStart(2,'0')).join(':');
}
tick(); setInterval(tick,1000);
</script>
</body>
</html><?php
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
}
