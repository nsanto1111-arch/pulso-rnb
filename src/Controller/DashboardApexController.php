<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin\Controller;

use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;


require_once __DIR__ . '/../../includes/rnb-apex-shell.php';

class DashboardApexController
{
    private Connection $db;
    public function __construct(Connection $db) { $this->db = $db; }

    public function indexAction(ServerRequest $request, ResponseInterface $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $np  = $this->_getNowPlaying($sid);
        $sch = $this->_getSchedule($sid);
        $team= $this->_getTeam($sid);
        $user= $this->_getUser();

        ob_start();
        $this->_renderHero($np, $sid);
        $this->_renderGrid($sch, $team, $sid);
        $corpo = ob_get_clean();

        $html = rnb_apex_layout('Dashboard', $corpo, $sid, 'dashboard', $user);
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html; charset=utf-8');
    }

    private function _renderHero(array $np, int $sid): void
    {
        $t      = APEX_TOKENS;
        $title  = htmlspecialchars($np['title']  ?? 'Aguardar sinal');
        $artist = htmlspecialchars($np['artist'] ?? '—');
        $album  = htmlspecialchars($np['album']  ?? '');
        $next   = htmlspecialchars($np['next']   ?? '—');
        $src    = htmlspecialchars($np['src']    ?? 'AzuraCast');
        $dur    = (int)($np['duration'] ?? 0);
        $el     = (int)($np['elapsed']  ?? 0);
        $pct    = $dur > 0 ? min(100, round($el/$dur*100)) : 0;
        $rem    = max(0, $dur - $el);
        $fmt    = fn($s) => sprintf('%02d:%02d', floor($s/60), $s%60);
        $wv     = rnb_apex_waveform();
        ?>
<div class="apex-hero">
  <div style="position:absolute;left:0;right:0;height:70px;background:linear-gradient(to bottom,transparent,rgba(255,31,75,0.025),transparent);animation:apex-hero-scan 9s ease-in-out 2s infinite;pointer-events:none"></div>
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
    <div class="apex-onair">
      <div class="apex-onair-scan"></div>
      <span class="apex-onair-dot"></span>
      <span class="apex-onair-label">No Ar</span>
    </div>
    <span class="apex-hero-src"><?= $src ?></span>
    <div style="flex:1"></div>
    <?= $wv ?>
  </div>
  <div class="apex-track-title"><?= $title ?></div>
  <div class="apex-track-artist"><?= $artist ?><?php if($album): ?><span class="apex-track-album"><?= $album ?></span><?php endif ?></div>
  <div class="apex-track-meta">
    <span class="apex-track-tag"><?= $src ?></span>
    <?php if(!empty($np['bpm'])): ?><span class="apex-track-tag"><?= (int)$np['bpm'] ?> BPM</span><?php endif ?>
  </div>
  <div class="apex-progress">
    <div class="apex-progress-fill" id="dp-fill" style="width:<?= $pct ?>%"></div>
    <div class="apex-progress-cursor" id="dp-cursor" style="left:<?= $pct ?>%"></div>
  </div>
  <div class="apex-progress-times">
    <span id="dp-el"><?= $fmt($el) ?></span>
    <span class="apex-progress-next">A seguir: <span><?= $next ?></span></span>
    <span id="dp-rem">−<?= $fmt($rem) ?></span>
  </div>
</div>
<script>
(function(){
  var el=<?= $el ?>,dur=<?= max(1,$dur) ?>;
  var fill=document.getElementById('dp-fill'),cursor=document.getElementById('dp-cursor');
  var elEl=document.getElementById('dp-el'),remEl=document.getElementById('dp-rem');
  var pad=function(n){return String(Math.floor(n)).padStart(2,'0');};
  var fmt=function(s){return pad(s/60)+':'+pad(s%60);};
  setInterval(function(){
    el=Math.min(el+1,dur);
    var pct=Math.round(el/dur*100);
    if(fill)fill.style.width=pct+'%';
    if(cursor)cursor.style.left=pct+'%';
    if(elEl)elEl.textContent=fmt(el);
    if(remEl)remEl.textContent='−'+fmt(dur-el);
  },1000);
})();
</script>
        <?php
    }

    private function _renderGrid(array $sch, array $team, int $sid): void
    {
        $t   = APEX_TOKENS;
        $now = date('H:i:s');
        ?>
<div class="apex-grid2">
  <div>
    <?= rnb_apex_panel_header('Grelha · Hoje', date('D, d M'), 'Programação →', "/public/programacao/{$sid}") ?>
    <?php foreach($sch as $s):
        $h    = substr($s['hora_inicio']??'00:00',0,5);
        $prog = htmlspecialchars($s['programa']??'—');
        $pres = htmlspecialchars($s['apresentador']??'Automático');
        $st   = $this->_slotStatus($s['hora_inicio']??'',$s['hora_fim']??'');
        $live = $st==='live';$done=$st==='done';
        $lc   = $live?' live':'';
        $tc   = $live?$t['live']:($done?$t['ink4']:$t['ink3']);
        $pw   = $live?'600':'300';
        $pc   = $live?$t['ink']:($done?$t['ink4']:$t['ink2']);
        $pf   = $live?"'Syne',sans-serif":"'DM Sans',sans-serif";
    ?>
    <div class="apex-sched-row<?= $lc ?>" data-hover>
      <span class="apex-sched-time" style="color:<?= $tc ?>"><?= $h ?></span>
      <div style="flex:1;min-width:0">
        <div class="apex-sched-prog" style="font-family:<?= $pf ?>;font-weight:<?= $pw ?>;color:<?= $pc ?>"><?= $prog ?></div>
        <div class="apex-sched-pres"><?= $pres ?></div>
      </div>
      <?= rnb_apex_pill($st) ?>
    </div>
    <?php endforeach ?>
  </div>

  <div>
    <?= rnb_apex_panel_header('Equipa', count($team).' membros', 'RH →', "/public/rh/{$sid}") ?>
    <?php foreach($team as $m):
        $nome  = htmlspecialchars($m['nome']??'—');
        $dept  = htmlspecialchars($m['departamento']??$m['dept']??'—');
        $est   = $m['estado']??'activo';
        $sc    = (int)round((float)($m['score']??0));
        $words = array_filter(explode(' ',$m['nome']??''));
        $ini   = strtoupper(implode('',array_map(fn($w)=>$w[0],array_slice($words,0,2))));
        $href  = "/public/rh/{$sid}/funcionario/{$m['id']}";
    ?>
    <a href="<?= $href ?>" class="apex-sched-row" data-hover style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid <?= $t['rule'] ?>;text-decoration:none;border-left:2px solid transparent">
      <div class="apex-team-avatar"><?= htmlspecialchars(substr($ini,0,2)) ?></div>
      <div style="flex:1;min-width:0">
        <div class="apex-team-name"><?= $nome ?></div>
        <div class="apex-team-dept"><?= $dept ?></div>
      </div>
      <?= rnb_apex_pill($est) ?>
      <?= rnb_apex_score($sc) ?>
    </a>
    <?php endforeach ?>
  </div>
</div>
        <?php
    }

    private function _getNowPlaying(int $sid): array
    {
        try {
            $ctx  = stream_context_create(['http'=>['timeout'=>2]]);
            $json = @file_get_contents("http://localhost/api/station/{$sid}/now-playing",false,$ctx);
            if($json){
                $d  = json_decode($json,true);
                $sp = $d['now_playing']['song']??[];
                $nxt= $d['playing_next']['song']??[];
                return [
                    'title'    => $sp['title']  ?? 'Sem sinal',
                    'artist'   => $sp['artist'] ?? '—',
                    'album'    => $sp['album']  ?? '',
                    'src'      => 'AzuraCast',
                    'duration' => (int)($d['now_playing']['duration']??0),
                    'elapsed'  => (int)($d['now_playing']['elapsed'] ??0),
                    'next'     => trim(($nxt['artist']??'').' — '.($nxt['title']??'')),
                ];
            }
        } catch(\Throwable $e){}
        return ['title'=>'Aguardar sinal','artist'=>'—','src'=>'AzuraCast','duration'=>0,'elapsed'=>0,'next'=>'—'];
    }

    private function _getSchedule(int $sid): array
    {
        try {
            $rows = $this->db->fetchAllAssociative(
                "SELECT p.hora_inicio, p.hora_fim, p.nome AS programa,
                        COALESCE(f.nome,'Automático') AS apresentador, p.estado
                 FROM rnb_programacao p
                 LEFT JOIN rnb_funcionarios f ON f.id=p.locutor_id
                 WHERE p.station_id=? AND p.dia_semana=DAYOFWEEK(NOW())
                 ORDER BY p.hora_inicio LIMIT 7", [$sid]
            );
            if($rows) return $rows;
        } catch(\Throwable $e){}
        return [
            ['hora_inicio'=>'06:00:00','hora_fim'=>'10:00:00','programa'=>'Madrugada Musical', 'apresentador'=>'Automático','estado'=>''],
            ['hora_inicio'=>'10:00:00','hora_fim'=>'13:00:00','programa'=>'Manhã com Sabor',   'apresentador'=>'Maria João','estado'=>''],
            ['hora_inicio'=>'13:00:00','hora_fim'=>'17:00:00','programa'=>'Tarde Angolana',     'apresentador'=>'Carlos Mendes','estado'=>''],
            ['hora_inicio'=>'17:00:00','hora_fim'=>'19:00:00','programa'=>'Noticiário Nacional','apresentador'=>'Ana Silva','estado'=>''],
            ['hora_inicio'=>'19:00:00','hora_fim'=>'22:00:00','programa'=>'Noite RNB',          'apresentador'=>'Rui Ferreira','estado'=>''],
            ['hora_inicio'=>'22:00:00','hora_fim'=>'06:00:00','programa'=>'Jazz & Alma',        'apresentador'=>'Automático','estado'=>''],
        ];
    }

    private function _getTeam(int $sid): array
    {
        try {
            $rows = $this->db->fetchAllAssociative(
                "SELECT f.id, f.nome, f.cargo, f.departamento, f.estado,
                        COALESCE(AVG(p.performance_score),0) AS score
                 FROM rnb_funcionarios f
                 LEFT JOIN rnb_rh_performance p ON p.funcionario_id=f.id
                   AND p.created_at>=DATE_SUB(NOW(),INTERVAL 30 DAY)
                 WHERE f.station_id=? AND f.estado IN ('activo','live','ferias')
                 GROUP BY f.id ORDER BY f.estado='live' DESC, score DESC LIMIT 6",
                [$sid]
            );
            if($rows) return $rows;
        } catch(\Throwable $e){}
        return [
            ['id'=>1,'nome'=>'Carlos Mendes',   'departamento'=>'Studio', 'estado'=>'live',   'score'=>88],
            ['id'=>2,'nome'=>'Maria João',       'departamento'=>'Studio', 'estado'=>'activo', 'score'=>74],
            ['id'=>3,'nome'=>'Ana Silva',        'departamento'=>'News',   'estado'=>'activo', 'score'=>82],
            ['id'=>4,'nome'=>'Vanessa Ndombele', 'departamento'=>'News',   'estado'=>'ferias', 'score'=>91],
            ['id'=>5,'nome'=>'Rui Ferreira',     'departamento'=>'Técnico','estado'=>'activo', 'score'=>79],
        ];
    }

    private function _getUser(): array
    {
        // Integrar com RhAuth quando disponível
        if(class_exists('Plugin\ProgramacaoPlugin\RhAuth')) {
            try {
                $u = \Plugin\ProgramacaoPlugin\RhAuth::getUser($this->db);
                if($u) return $u;
            } catch(\Throwable $e){}
        }
        return ['nome'=>'Newton Santos','role'=>'admin'];
    }

    private function _slotStatus(string $ini, string $fim): string
    {
        $now = date('H:i:s');
        if($ini && $fim){
            if($now>=$ini && $now<$fim) return 'live';
            if($now>=$fim) return 'done';
        }
        return 'next';
    }
}
