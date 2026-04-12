<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin\Controller;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Slim\Http\Response;
use Doctrine\DBAL\Connection;
use Plugin\ProgramacaoPlugin\Security;

class VerificadorController
{
    private Connection $db;
    public function __construct(Connection $db) { $this->db = $db; }

    public function indexAction(ServerRequest $req, Response $res): ResponseInterface
    {
        Security::setHeaders();
        $qp     = $req->getQueryParams();
        $codigo = strtoupper(trim($qp['codigo'] ?? ''));
        $result = null;
        if($codigo) $result = $this->verificar($codigo, Security::getIp());
        $res->getBody()->write($this->render($codigo, $result));
        return $res->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    public function apiAction(ServerRequest $req, Response $res): ResponseInterface
    {
        Security::setHeaders();
        $sec = new Security();
        $ip  = Security::getIp();
        if(!$sec->checkRateLimit('api', $ip)) {
            $res->getBody()->write(json_encode(['status'=>'blocked','msg'=>'Demasiadas tentativas']));
            return $res->withHeader('Content-Type','application/json')->withStatus(429);
        }
        $b      = $req->getParsedBody() ?? [];
        $codigo = strtoupper(trim($b['codigo'] ?? $req->getQueryParams()['codigo'] ?? ''));
        $r      = $this->verificar($codigo, $ip);
        $res->getBody()->write(json_encode($r, JSON_UNESCAPED_UNICODE));
        return $res->withHeader('Content-Type', 'application/json');
    }

    private function verificar(string $codigo, string $ip): array
    {
        $codigo = preg_replace('/[^A-Z0-9\-]/', '', $codigo);
        if(!preg_match('/^RNB-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $codigo)) {
            $this->log($codigo, $ip, 'invalido');
            return ['status'=>'invalido','codigo'=>$codigo,'msg'=>'Formato invalido. Use: RNB-XXXX-XXXX'];
        }
        try {
            $r = $this->db->fetchAssociative(
                "SELECT * FROM rnb_prova_codigos WHERE codigo=?", [$codigo]
            );
        } catch(\Exception $e) { $r = null; }

        if(!$r) {
            $this->log($codigo, $ip, 'invalido');
            return ['status'=>'invalido','codigo'=>$codigo,'msg'=>'Este codigo nao foi encontrado no sistema oficial da Radio New Band.'];
        }
        if(!$r['ativo']) {
            $this->log($codigo, $ip, 'invalido');
            return ['status'=>'invalido','codigo'=>$codigo,'msg'=>'Este comprovante foi desactivado.'];
        }

        $hashExp = hash('sha256',
            $r['anunciante_id'].'|'.
            $r['titulo_spot'].'|'.
            $r['data_emissao'].'|'.
            $r['hora_emissao'].'|'.
            $codigo
        );

        if(!hash_equals($hashExp, $r['hash'])) {
            $this->log($codigo, $ip, 'inconsistente');
            return ['status'=>'inconsistente','codigo'=>$codigo,'msg'=>'Comprovante existe mas apresenta inconsistencias. Contacte a radio.'];
        }

        try {
            $this->db->executeStatement(
                "UPDATE rnb_prova_codigos SET verificacoes=verificacoes+1, ultima_verificacao=NOW() WHERE codigo=?",
                [$codigo]
            );
        } catch(\Exception $e) {}

        $this->log($codigo, $ip, 'valido');

        return [
            'status'      => 'valido',
            'codigo'      => $codigo,
            'titulo_spot'=> $r['titulo_spot'],
            'anunciante' => $r['nome_anunciante'],
            'data'       => date('d/m/Y', strtotime($r['data_emissao'])),
            'hora'       => substr($r['hora_emissao'],0,5),
            'programa'   => $r['programa'] ?? '--',
            'duracao'    => $r['duracao_seg'].'s',
            'verificacoes'=> (int)$r['verificacoes']+1,
            'msg'        => 'Comprovante valido e autentico.',
        ];
    }

    private function log(string $codigo, string $ip, string $result): void
    {
        try {
            $this->db->insert('rnb_verificacao_log', [
                'codigo'     => $codigo,
                'ip'         => $ip,
                'user_agent'=> substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
                'resultado'  => $result,
                'created_at'=> date('Y-m-d H:i:s'),
            ]);
        } catch(\Exception $e) {}
    }

    private function render(string $codigo, ?array $r): string
    {
        $isV = ($r['status'] ?? '') === 'valido';
        $isI = ($r['status'] ?? '') === 'invalido';
        $isC = ($r['status'] ?? '') === 'inconsistente';
        ob_start(); ?>
<!DOCTYPE html><html lang="pt"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verificar Comprovante — Radio New Band Angola</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root{--ink:#08090C;--ink2:#0F1117;--ink3:#161A22;--ink4:#1D2129;--wire:#272D38;--wire2:#353C4A;--smoke:#60697A;--fog:#B8C0CE;--w:#EEF0F5;--gold:#D4A84B;--gl:#E8C870;--gd:#9A7620;--gg:rgba(212,168,75,.15);--jade:#27C47A;--jl:#3DDA8E;--jt:rgba(39,196,122,.1);--ember:#E05A38;--et:rgba(224,90,56,.1);--amber:#E09A2A;--at:rgba(224,154,42,.1)}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:"Plus Jakarta Sans",sans-serif;background:var(--ink);color:var(--w);min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased;position:relative;overflow-x:hidden}
body::before{content:"";position:fixed;inset:0;background-image:linear-gradient(rgba(212,168,75,.025) 1px,transparent 1px),linear-gradient(90deg,rgba(212,168,75,.025) 1px,transparent 1px);background-size:56px 56px;pointer-events:none}
body::after{content:"";position:fixed;top:-20%;left:50%;transform:translateX(-50%);width:700px;height:500px;background:radial-gradient(ellipse,rgba(212,168,75,.07),transparent 70%);pointer-events:none}
.hdr{padding:18px 32px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--wire);background:rgba(8,9,12,.82);backdrop-filter:blur(12px);position:relative;z-index:10}
.hdr::after{content:"";position:absolute;bottom:-1px;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--gold),transparent);opacity:.2}
.logo{display:flex;align-items:center;gap:11px}
.lm{width:38px;height:38px;border-radius:9px;background:linear-gradient(135deg,var(--gd),var(--gold));display:flex;align-items:center;justify-content:center;font-family:"Playfair Display",serif;font-size:13px;font-weight:700;color:var(--ink);box-shadow:0 0 20px var(--gg)}
.lt{font-family:"Playfair Display",serif;font-size:17px;font-weight:600;color:var(--w)}.lt em{color:var(--gold);font-style:normal}
.hbdg{font-size:10px;font-weight:700;color:var(--smoke);border:1px solid var(--wire2);padding:4px 12px;border-radius:100px;letter-spacing:1px;text-transform:uppercase;display:flex;align-items:center;gap:5px}
main{flex:1;display:flex;align-items:center;justify-content:center;padding:48px 24px;position:relative;z-index:1}
.cx{width:100%;max-width:560px}
.eye{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:14px;font-size:10px;font-weight:800;color:var(--gold);text-transform:uppercase;letter-spacing:2.5px}
.eye::before,.eye::after{content:"";flex:1;max-width:40px;height:1px;background:var(--gold);opacity:.4}
h1{font-family:"Playfair Display",serif;font-size:36px;font-weight:700;color:var(--w);text-align:center;letter-spacing:-.5px;line-height:1.15;margin-bottom:10px}
.sub{font-size:14px;color:var(--smoke);text-align:center;margin-bottom:36px;font-weight:400;line-height:1.5}
.card{background:var(--ink2);border:1px solid var(--wire);border-radius:16px;padding:32px;box-shadow:0 24px 48px rgba(0,0,0,.4),0 0 0 1px rgba(255,255,255,.02)}
.lbl{display:block;font-size:10px;font-weight:800;color:var(--smoke);text-transform:uppercase;letter-spacing:1.8px;margin-bottom:8px}
.iw{position:relative;margin-bottom:14px}
.ci{width:100%;padding:16px 52px 16px 20px;background:var(--ink3);border:2px solid var(--wire2);border-radius:10px;font-family:"JetBrains Mono",monospace;font-size:20px;font-weight:500;color:var(--w);text-align:center;text-transform:uppercase;letter-spacing:4px;outline:none;transition:border-color .2s,box-shadow .2s,background .2s}
.ci::placeholder{color:var(--wire2);font-size:16px;letter-spacing:2px}
.ci:focus{border-color:var(--gold);background:var(--ink4);box-shadow:0 0 0 4px var(--gg),0 0 32px var(--gg)}
.ii{position:absolute;right:16px;top:50%;transform:translateY(-50%);font-size:18px;color:var(--smoke);pointer-events:none}
.vbtn{width:100%;padding:15px;border:none;border-radius:10px;background:linear-gradient(135deg,var(--gd),var(--gold) 60%,var(--gl) 100%);color:var(--ink);font:800 14px "Plus Jakarta Sans",sans-serif;cursor:pointer;letter-spacing:.3px;box-shadow:0 4px 20px var(--gg);transition:all .2s;position:relative;overflow:hidden}
.vbtn::before{content:"";position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.12),transparent)}
.vbtn:hover{transform:translateY(-2px);box-shadow:0 8px 32px var(--gg)}
.vbtn:active{transform:none}
.hint{text-align:center;margin-top:14px;font-size:11px;color:var(--smoke);display:flex;align-items:center;justify-content:center;gap:5px}
.hint i{color:var(--jade)}
.result{margin-top:24px;border-radius:12px;overflow:hidden;animation:su .35s cubic-bezier(.16,1,.3,1) both}
@keyframes su{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.rh{padding:20px 22px;display:flex;align-items:center;gap:14px}
.ri{width:46px;height:46px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:20px}
.rt{font-family:"Playfair Display",serif;font-size:17px;font-weight:700}
.rm{font-size:13px;margin-top:3px;opacity:.8}
.rb{padding:0 22px 20px}
.rg{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px}
.rit{padding:11px 13px;border-radius:8px;background:rgba(255,255,255,.04)}
.rl{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;opacity:.5;margin-bottom:4px}
.rv{font-size:13px;font-weight:600;color:var(--w)}
.rc{font-family:"JetBrains Mono",monospace;font-size:13px;font-weight:500;letter-spacing:2px}
.rdiv{height:1px;margin:0 22px;opacity:.2}
.rvalid{background:rgba(39,196,122,.06);border:1px solid rgba(39,196,122,.2)}
.rvalid .ri{background:rgba(39,196,122,.12);color:var(--jl)}
.rvalid .rt{color:var(--jl)}
.rvalid .rdiv{background:var(--jade)}
.rinv{background:rgba(224,90,56,.06);border:1px solid rgba(224,90,56,.2)}
.rinv .ri{background:var(--et);color:#F08060}
.rinv .rt{color:#F08060}
.rinc{background:rgba(224,154,42,.06);border:1px solid rgba(224,154,42,.2)}
.rinc .ri{background:var(--at);color:var(--amber)}
.rinc .rt{color:var(--amber)}
.stamp{display:flex;align-items:center;justify-content:center;gap:7px;margin:14px 0 0;padding:10px;border-radius:8px;background:rgba(39,196,122,.06);border:1px dashed rgba(39,196,122,.22);font-size:10px;font-weight:700;color:var(--jade);text-transform:uppercase;letter-spacing:1.5px}
footer{padding:18px 32px;text-align:center;border-top:1px solid var(--wire);font-size:11px;color:var(--smoke);background:rgba(8,9,12,.6);backdrop-filter:blur(8px);position:relative;z-index:1}
footer span{color:var(--gold)}
@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:600px){h1{font-size:26px}.card{padding:22px 16px}.rg{grid-template-columns:1fr}.hdr{padding:14px 16px}.hbdg span{display:none}}
</style></head><body>
<header class="hdr">
  <div class="logo">
    <div class="lm">RNB</div>
    <div class="lt">Radio <em>New Band</em></div>
  </div>
  <div class="hbdg"><i class="bi bi-shield-check"></i> <span>Verificacao Oficial</span></div>
</header>
<main>
<div class="cx">
  <div class="eye"><span>Sistema Antifraude</span></div>
  <h1>Verifique o seu<br>Comprovante</h1>
  <p class="sub">Confirme a autenticidade de qualquer spot emitido<br>pela Radio New Band Angola com um clique.</p>
  <div class="card">
    <form method="GET" action="/verificar" onsubmit="onSub(event)">
      <label class="lbl"><i class="bi bi-key-fill"></i> Codigo do Comprovante</label>
      <div class="iw">
        <input class="ci" type="text" name="codigo" id="codigo" maxlength="14"
               placeholder="RNB-XXXX-XXXX"
               value="<?= htmlspecialchars($codigo) ?>"
               autocomplete="off" spellcheck="false"
               oninput="fmt(this)">
        <i class="bi bi-upc-scan ii"></i>
      </div>
      <button type="submit" class="vbtn" id="vbtn">
        <i class="bi bi-shield-lock-fill"></i>&ensp;Verificar Autenticidade
      </button>
    </form>
    <div class="hint"><i class="bi bi-shield-check-fill"></i> Ligacao segura &middot; Verificacao instantanea &middot; Sem guardar dados</div>
<?php if($r !== null): ?>
    <div class="result <?= $isV?'rvalid':($isC?'rinc':'rinv') ?>">
      <div class="rh">
        <div class="ri">
          <?= $isV?'<i class="bi bi-patch-check-fill"></i>':($isC?'<i class="bi bi-exclamation-triangle-fill"></i>':'<i class="bi bi-x-circle-fill"></i>') ?>
        </div>
        <div>
          <div class="rt"><?= $isV?'Comprovante Valido':($isC?'Inconsistencia Detectada':'Nao Encontrado') ?></div>
          <div class="rm"><?= htmlspecialchars($r['msg']) ?></div>
        </div>
      </div>
      <?php if($isV): ?>
      <div class="rdiv"></div>
      <div class="rb">
        <div class="rg">
          <div class="rit"><div class="rl">Codigo</div><div class="rv rc"><?= htmlspecialchars($r['codigo']) ?></div></div>
          <div class="rit"><div class="rl">Anunciante</div><div class="rv"><?= htmlspecialchars($r['anunciante']) ?></div></div>
          <div class="rit"><div class="rl">Spot Emitido</div><div class="rv"><?= htmlspecialchars($r['titulo_spot']) ?></div></div>
          <div class="rit"><div class="rl">Data de Emissao</div><div class="rv"><?= htmlspecialchars($r['data']) ?></div></div>
          <div class="rit"><div class="rl">Hora</div><div class="rv rc"><?= htmlspecialchars($r['hora']) ?></div></div>
          <div class="rit"><div class="rl">Programa</div><div class="rv"><?= htmlspecialchars($r['programa']) ?></div></div>
          <div class="rit"><div class="rl">Duracao</div><div class="rv"><?= htmlspecialchars($r['duracao']) ?></div></div>
          <div class="rit"><div class="rl">Verificacoes</div><div class="rv"><?= number_format((int)$r['verificacoes']) ?>x</div></div>
        </div>
        <div class="stamp"><i class="bi bi-patch-check-fill"></i> Autenticado pelo Sistema Oficial da Radio New Band Angola</div>
      </div>
      <?php endif ?>
    </div>
<?php endif ?>
  </div>
</div>
</main>
<footer><span>Radio New Band Angola</span> &middot; Sistema Oficial de Verificacao &middot; <a href="https://radionewband.ao" style="color:var(--smoke)">radionewband.ao</a> &middot; <?= date('Y') ?></footer>
<script>
function fmt(el){
  let v=el.value.toUpperCase().replace(/[^A-Z0-9]/g,"");
  if(v.startsWith("RNB"))v=v.slice(3);
  let p=v.replace(/-/g,"");
  let r="RNB-";
  if(p.length>4)r+=p.slice(0,4)+"-"+p.slice(4,8);
  else r+=p.slice(0,4);
  el.value=r.slice(0,14);
}
function onSub(e){
  const b=document.getElementById("vbtn");
  b.innerHTML='<i class="bi bi-arrow-repeat" style="animation:spin .7s linear infinite"></i>&ensp;A verificar...';
  b.style.opacity="0.8";
}
document.addEventListener("DOMContentLoaded",()=>{const i=document.getElementById("codigo");if(i&&i.value)fmt(i);});
</script>
</body></html>
        <?php
        return ob_get_clean();
    }
}
