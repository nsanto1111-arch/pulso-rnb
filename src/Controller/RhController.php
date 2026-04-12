<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin\Controller;

use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;
use Plugin\ProgramacaoPlugin\RhAuth;
use Psr\Http\Message\ResponseInterface;

class RhController
{
    private Connection $db;
    public function __construct(Connection $db) { $this->db = $db; }

    // ─── AUTH GUARD ───────────────────────────────────────────────────────────
    private function rhUser(): ?array
    {
        static $cache = false;
        if($cache === false) $cache = RhAuth::getUser($this->db);
        return $cache ?: null;
    }

    private function requireAuth(Response $response, string $sid): ?ResponseInterface
    {
        if(!$this->rhUser()) {
            $next = urlencode($_SERVER['REQUEST_URI'] ?? '');
            return $response->withHeader('Location',"/public/rh/login?sid={$sid}&next={$next}")->withStatus(302);
        }
        return null;
    }

    private function requirePerm(Response $response, string $perm, string $sid): ?ResponseInterface
    {
        $guard = $this->requireAuth($response, $sid);
        if($guard) return $guard;
        if(!RhAuth::can($perm, $this->rhUser())) {
            $response->getBody()->write(RhAuth::denyHtml());
            return $response->withStatus(403)->withHeader('Content-Type','text/html');
        }
        return null;
    }

    // ─── LOGIN ────────────────────────────────────────────────────────────────
    public function loginAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $qp    = $request->getQueryParams();
        $sid   = (int)($qp['sid'] ?? 1);
        $next  = $qp['next'] ?? "/public/rh/{$sid}";
        $erro  = '';

        if($this->rhUser()) {
            return $response->withHeader('Location', $next)->withStatus(302);
        }

        ob_start(); ?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>RNB RH — Entrar</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,sans-serif;background:#F7F9FB;display:flex;align-items:center;justify-content:center;min-height:100vh;color:#0B1220}
.card{background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:36px;width:100%;max-width:380px;box-shadow:0 4px 16px rgba(0,0,0,.08)}
.logo{text-align:center;margin-bottom:28px}
.logo-mark{width:48px;height:48px;background:#2563EB;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#fff;margin:0 auto 12px}
.logo-name{font-size:18px;font-weight:700;color:#0B1220}
.logo-sub{font-size:13px;color:#6B7280;margin-top:3px}
.f-group{margin-bottom:16px}
.f-label{display:block;font-size:12px;font-weight:600;color:#0B1220;margin-bottom:5px}
.f-input{width:100%;padding:9px 12px;border:1px solid #E5E7EB;border-radius:6px;font:14px Inter,sans-serif;color:#0B1220;outline:none;transition:border-color .12s}
.f-input:focus{border-color:#2563EB;box-shadow:0 0 0 3px rgba(37,99,235,.08)}
.btn-login{width:100%;padding:10px;background:#2563EB;color:#fff;border:none;border-radius:6px;font:600 14px Inter,sans-serif;cursor:pointer;transition:background .12s;margin-top:4px}
.btn-login:hover{background:#1D4ED8}
.erro{background:#FEF2F2;border:1px solid #FECACA;color:#DC2626;border-radius:6px;padding:10px 14px;font-size:13px;margin-bottom:16px}
.footer{text-align:center;margin-top:20px;font-size:12px;color:#9CA3AF}
</style>
</head>
<body>
<div class="card">
    <div class="logo">
        <div class="logo-mark">RH</div>
        <div class="logo-name">RNB RH</div>
        <div class="logo-sub">Sistema de Recursos Humanos</div>
    </div>
    <?php if($erro): ?>
    <div class="erro"><?= htmlspecialchars($erro) ?></div>
    <?php endif ?>
    <form method="POST" action="/public/rh/login?sid=<?= $sid ?>&next=<?= urlencode($next) ?>">
        <div class="f-group">
            <label class="f-label">Utilizador</label>
            <input type="text" name="username" class="f-input" required autofocus autocomplete="username" placeholder="O teu utilizador">
        </div>
        <div class="f-group">
            <label class="f-label">Password</label>
            <input type="password" name="password" class="f-input" required autocomplete="current-password" placeholder="A tua password">
        </div>
        <button type="submit" class="btn-login">Entrar</button>
    </form>
    <div class="footer">Rádio New Band Angola &middot; RNB OS</div>
</div>
</body>
</html>
        <?php
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function loginPostAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $qp   = $request->getQueryParams();
        $sid  = (int)($qp['sid'] ?? 1);
        $next = $qp['next'] ?? "/public/rh/{$sid}";
        $b    = $request->getParsedBody();
        $user = trim($b['username'] ?? '');
        $pass = $b['password'] ?? '';

        // Rate limiting simples — penalizar tentativas falhadas com sleep

        try {
            $userRec = $this->db->fetchAssociative(
                "SELECT * FROM rnb_rh_users WHERE username=? AND station_id=? AND activo=1",
                [$user, $sid]
            );
        } catch(\Exception $e) { $userRec = null; }

        if($userRec && password_verify($pass, $userRec['password_hash'])) {
            // Gerar token e definir cookie
            $token = RhAuth::login($userRec, $this->db);
            RhAuth::setCookie($token);

            // Actualizar ultimo_acesso
            try {
                $this->db->update('rnb_rh_users',
                    ['ultimo_acesso' => date('Y-m-d H:i:s')],
                    ['id' => $userRec['id']]
                );
                RhAuth::log($this->db, ['id'=>$userRec['id'],'username'=>$userRec['username'],'station'=>$sid], 'login');
            } catch(\Exception $e) {}

            // Sanitizar redirect
            $parsed = parse_url($next, PHP_URL_PATH);
            $safe = ($parsed && str_starts_with($parsed, '/')) ? $parsed : "/public/rh/{$sid}";

            return $response->withHeader('Location', $safe)->withStatus(302);
        }

        // Falha — penalizar
        sleep(1);
        return $response->withHeader('Location',"/public/rh/login?sid={$sid}&err=invalid&next=".urlencode($next))->withStatus(302);
    }

    public function logoutAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)($request->getQueryParams()['sid'] ?? 1);
        try {
            $u = $this->rhUser();
            if($u) RhAuth::log($this->db, $u, 'logout');
            RhAuth::logout($this->db);
        } catch(\Exception $e) { RhAuth::logout($this->db); }
        return $response->withHeader('Location',"/public/rh/login?sid={$sid}")->withStatus(302);
    }

    private function kz(float $v): string {
        return number_format($v,2,',','.').' Kz';
    }

    private function deptLabel(string $d): string {
        return ['locutor'=>'Locutor','jornalismo'=>'Jornalismo','tecnico'=>'Técnico',
                'comercial'=>'Comercial','financeiro'=>'Financeiro','administrativo'=>'Administrativo',
                'direcao'=>'Direcção','producao'=>'Produção'][$d] ?? ucfirst($d);
    }

    private function mesNome(int $m): string {
        return [1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
                7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'][$m] ?? (string)$m;
    }

    private function css(): string { return <<<'CSS'
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#F9FAFB;--bg2:#FFFFFF;--bg3:#F3F4F6;
  --bd:#E5E7EB;--bd2:#D1D5DB;
  --tx:#111827;--tx2:#6B7280;--tx3:#9CA3AF;
  --pr:#2563EB;--pr2:#1D4ED8;--pr-t:#EFF6FF;--pr-b:#BFDBFE;
  --green:#059669;--green-t:#ECFDF5;--green-b:#A7F3D0;
  --red:#DC2626;--red-t:#FEF2F2;--red-b:#FECACA;
  --gold:#D97706;--gold-t:#FFFBEB;--gold-b:#FDE68A;
  --ff:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  --sb:220px;--top:52px;
}
html,body{height:100%;-webkit-font-smoothing:antialiased}
body{font-family:var(--ff);font-size:14px;background:var(--bg);color:var(--tx);line-height:1.5}
a{color:var(--pr);text-decoration:none}
a:hover{text-decoration:underline}

/* SHELL */
.shell{display:grid;grid-template-columns:var(--sb) 1fr;grid-template-rows:var(--top) 1fr;height:100vh;overflow:hidden;margin-left:46px}

/* TOPBAR */
.topbar{
  grid-column:1/-1;
  background:var(--bg2);border-bottom:1px solid var(--bd);
  display:flex;align-items:center;padding:0 20px;gap:16px;
}
.topbar-brand{font-size:15px;font-weight:700;color:var(--tx);padding-right:20px;border-right:1px solid var(--bd);white-space:nowrap}
.topbar-brand span{color:var(--pr)}
.topbar-spacer{flex:1}
.topbar-user{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--tx2)}
.topbar-avatar{width:28px;height:28px;border-radius:50%;background:var(--pr-t);border:1px solid var(--pr-b);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--pr)}

/* SIDEBAR */
.sidebar{
  background:var(--bg2);border-right:1px solid var(--bd);
  display:flex;flex-direction:column;overflow:hidden;
  grid-row:2;padding:12px 0;
}
.nav-item{
  display:flex;align-items:center;gap:10px;
  padding:8px 16px;
  font-size:14px;color:var(--tx2);
  text-decoration:none;transition:all .1s;
  border-left:2px solid transparent;
}
.nav-item:hover{background:var(--bg3);color:var(--tx);text-decoration:none}
.nav-item.active{color:var(--pr);background:var(--pr-t);border-left-color:var(--pr);font-weight:500}
.nav-item i{font-size:15px;width:18px;text-align:center;flex-shrink:0}
.nav-sep{height:1px;background:var(--bd);margin:8px 16px}
.nav-back{
  display:flex;align-items:center;gap:8px;padding:8px 16px;
  font-size:13px;color:var(--tx3);margin-top:auto;
  text-decoration:none;border-top:1px solid var(--bd);
}
.nav-back:hover{color:var(--tx2);text-decoration:none}

/* MAIN */
.main{display:flex;flex-direction:column;overflow:hidden;background:var(--bg);grid-row:2}
.page-header{
  padding:16px 24px;border-bottom:1px solid var(--bd);
  background:var(--bg2);display:flex;align-items:center;
  justify-content:space-between;gap:16px;flex-shrink:0;
}
.page-title{font-size:18px;font-weight:600;color:var(--tx)}
.page-sub{font-size:13px;color:var(--tx2);margin-top:2px}
.page-actions{display:flex;align-items:center;gap:8px}
.page-body{flex:1;overflow-y:auto;padding:24px}

/* BUTTONS */
.btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:7px 14px;border-radius:6px;
  font:500 13px var(--ff);cursor:pointer;border:none;
  text-decoration:none;white-space:nowrap;transition:all .1s;
}
.btn:hover{text-decoration:none}
.btn-primary{background:var(--pr);color:#fff}
.btn-primary:hover{background:var(--pr2);color:#fff}
.btn-default{background:var(--bg2);color:var(--tx);border:1px solid var(--bd)}
.btn-default:hover{background:var(--bg3);border-color:var(--bd2)}
.btn-danger{background:var(--red-t);color:var(--red);border:1px solid var(--red-b)}
.btn-danger:hover{background:var(--red-b)}
.btn-success{background:var(--green-t);color:var(--green);border:1px solid var(--green-b)}
.btn-success:hover{background:var(--green-b)}
.btn-sm{padding:4px 10px;font-size:12px}
.btn-link{background:none;color:var(--pr);padding:0;font-size:13px;font-weight:400}
.btn-link:hover{text-decoration:underline}

/* SEARCH & FILTERS */
.toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.search-input{
  padding:7px 12px 7px 32px;border:1px solid var(--bd);border-radius:6px;
  font:14px var(--ff);color:var(--tx);background:var(--bg2);outline:none;
  width:240px;transition:border-color .1s;position:relative;
}
.search-input:focus{border-color:var(--pr)}
.search-wrap{position:relative}
.search-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--tx3);font-size:13px;pointer-events:none}
.filter-select{
  padding:7px 28px 7px 10px;border:1px solid var(--bd);border-radius:6px;
  font:14px var(--ff);color:var(--tx);background:var(--bg2);outline:none;
  appearance:none;cursor:pointer;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 8px center;
}
.filter-select:focus{border-color:var(--pr)}
.filter-sep{width:1px;height:20px;background:var(--bd)}

/* TABLE */
.table-wrap{background:var(--bg2);border:1px solid var(--bd);border-radius:8px;overflow:hidden}
table{width:100%;border-collapse:collapse;font-size:14px}
thead{background:var(--bg3)}
thead th{
  padding:10px 16px;text-align:left;
  font-size:12px;font-weight:600;color:var(--tx2);
  text-transform:uppercase;letter-spacing:.4px;
  border-bottom:1px solid var(--bd);white-space:nowrap;
}
tbody td{padding:12px 16px;border-bottom:1px solid var(--bd);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:var(--bg3)}
.td-name{font-weight:500;color:var(--tx)}
.td-sub{font-size:12px;color:var(--tx2)}

/* BADGES */
.badge{
  display:inline-flex;align-items:center;
  padding:2px 8px;border-radius:100px;
  font-size:12px;font-weight:500;
}
.badge-green{background:var(--green-t);color:var(--green)}
.badge-red{background:var(--red-t);color:var(--red)}
.badge-gold{background:var(--gold-t);color:var(--gold)}
.badge-blue{background:var(--pr-t);color:var(--pr)}
.badge-gray{background:var(--bg3);color:var(--tx2)}

/* MODAL */
.modal-overlay{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,.4);z-index:1000;
  align-items:flex-start;justify-content:center;padding-top:60px;
}
.modal-overlay.open{display:flex}
.modal{
  background:var(--bg2);border:1px solid var(--bd);
  border-radius:8px;padding:0;width:100%;max-width:560px;
  max-height:calc(100vh - 120px);overflow-y:auto;
  box-shadow:0 8px 24px rgba(0,0,0,.12);
}
.modal-head{
  padding:16px 20px;border-bottom:1px solid var(--bd);
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;background:var(--bg2);z-index:1;
}
.modal-title{font-size:15px;font-weight:600;color:var(--tx)}
.modal-close{
  width:26px;height:26px;border-radius:5px;border:1px solid var(--bd);
  background:var(--bg2);color:var(--tx2);cursor:pointer;font-size:14px;
  display:flex;align-items:center;justify-content:center;
}
.modal-close:hover{background:var(--bg3)}
.modal-body{padding:20px}
.modal-footer{
  padding:14px 20px;border-top:1px solid var(--bd);
  display:flex;justify-content:flex-end;gap:8px;
  position:sticky;bottom:0;background:var(--bg2);
}

/* FORM */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.form-group{margin-bottom:14px}
.form-group.full{grid-column:span 2}
.form-label{display:block;font-size:13px;font-weight:500;color:var(--tx);margin-bottom:5px}
.form-hint{font-size:12px;color:var(--tx3);margin-top:3px}
.form-input,.form-select,.form-textarea{
  width:100%;padding:8px 10px;
  border:1px solid var(--bd);border-radius:6px;
  font:14px var(--ff);color:var(--tx);
  background:var(--bg2);outline:none;transition:border-color .1s;
}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--pr);box-shadow:0 0 0 3px var(--pr-t)}
.form-input::placeholder,.form-textarea::placeholder{color:var(--tx3)}
.form-textarea{resize:vertical;min-height:80px;line-height:1.5}
.form-select{
  appearance:none;cursor:pointer;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 10px center;
  padding-right:28px;
}

/* SECTION */
.section{margin-bottom:28px}
.section-title{font-size:13px;font-weight:600;color:var(--tx2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid var(--bd)}

/* INLINE INFO */
.info-row{display:flex;align-items:baseline;gap:8px;padding:10px 0;border-bottom:1px solid var(--bd)}
.info-row:last-child{border-bottom:none}
.info-label{font-size:13px;color:var(--tx2);min-width:160px;flex-shrink:0}
.info-value{font-size:14px;color:var(--tx);flex:1}

/* EMPTY */
.empty{text-align:center;padding:48px 24px;color:var(--tx2)}
.empty p{font-size:14px;margin-top:8px;color:var(--tx3)}

/* TOAST */
.toast{position:fixed;bottom:24px;right:24px;padding:10px 16px;background:var(--tx);color:#fff;border-radius:6px;font-size:13px;font-weight:500;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.15)}

/* PAGINATION */
.pager{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--bd);font-size:13px;color:var(--tx2)}

@media(max-width:900px){.shell{grid-template-columns:1fr}.sidebar{display:none}}
</style>
CSS; }

    private function layout(string $titulo, string $corpo, int $sid, string $pag): string
    {
        $_rnb_sid=$sid; $_rnb_atual='rh';
        ob_start(); @require dirname(__DIR__,2).'/public/rnb-nav.php'; $rnbNav=ob_get_clean();

        try {
            $nFunc=(int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]);
        } catch(\Exception $e) { $nFunc=0; }

        $navItems = [
            'index'           => ['bi-people','Funcionários'],
            'escalas'         => ['bi-calendar3','Escalas'],
            'ferias'          => ['bi-umbrella','Férias'],
            'folha-pagamento' => ['bi-receipt','Salários'],
            'contratos'       => ['bi-file-earmark-text','Contratos'],
            'performance'     => ['bi-graph-up','Performance'],
            'alertas'         => ['bi-bell','Alertas'],
            'relatorios'      => ['bi-bar-chart-line','Relatórios'],
        ];

        // Info do utilizador autenticado
        $rhUser = $this->rhUser() ?? ['nome'=>'Visitante','role'=>'—'];
        $rhUserNome = htmlspecialchars($rhUser['nome'] ?? 'Utilizador');
        $rhUserRole = ucfirst($rhUser['role'] ?? '—');
        $logoutUrl  = "/public/rh/logout?sid={$sid}";
        $rhUserIni  = strtoupper(substr($rhUser['nome'] ?? 'U', 0, 1));

        $navHtml = '';
        foreach($navItems as $k=>[$ico,$lbl]) {
            $url = $k==='index' ? "/public/rh/{$sid}" : "/public/rh/{$sid}/{$k}";
            $cls = $k===$pag ? ' active' : '';
            $navHtml .= "<a href='{$url}' class='nav-item{$cls}'><i class='bi {$ico}'></i>{$lbl}</a>";
        }

        $css = $this->css();

        return <<<HTML
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>RNB RH — {$titulo}</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
{$css}
</head>
<body>
{$rnbNav}
<div class="shell">
  <header class="topbar">
    <div class="topbar-brand">RNB <span>RH</span></div>
    <div class="topbar-spacer"></div>
    <div class="topbar-user">
      <div class="topbar-avatar">{$rhUserIni}</div>
      <span>{$rhUserNome}</span>
    </div>
  </header>

  <aside class="sidebar">
    {$navHtml}
    <div class="nav-sep"></div>
    <a href="/public/dashboard/{$sid}" class="nav-back"><i class="bi bi-arrow-left"></i> Dashboard</a>
    <a href="{$logoutUrl}" class="nav-back" style="color:#DC2626"><i class="bi bi-box-arrow-right"></i> Terminar Sessão</a>
  </aside>

  <main class="main">{$corpo}</main>
</div>

<script>
function openModal(id){document.getElementById(id).classList.add('open')}
function closeModal(id){document.getElementById(id).classList.remove('open')}
document.querySelectorAll('.modal-overlay').forEach(function(m){
    m.addEventListener('click',function(e){if(e.target===m)m.classList.remove('open')});
});
function showToast(msg){
    var t=document.createElement('div');t.className='toast';t.textContent=msg;
    document.body.appendChild(t);setTimeout(function(){t.remove()},3000);
}
</script>
</body>
</html>
HTML;
    }

    /* ── FUNCIONÁRIOS ─────────────────────────────────────── */
    public function indexAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid  = (int)$params['station_id'];
        if($guard = $this->requirePerm($response,'rh.view',(string)$sid)) return $guard;
        $qp   = $request->getQueryParams();
        $dept = $qp['dept'] ?? '';
        $est  = $qp['estado'] ?? '';
        $q    = trim($qp['q'] ?? '');

        $where = "WHERE station_id=?"; $binds = [$sid];
        if($dept){ $where.=" AND departamento=?"; $binds[]=$dept; }
        if($est) { $where.=" AND estado=?";       $binds[]=$est;  }
        if($q)   { $where.=" AND (nome LIKE ? OR cargo LIKE ?)"; $binds[]="%{$q}%"; $binds[]="%{$q}%"; }

        try {
            $lista = $this->db->fetchAllAssociative("SELECT * FROM rnb_funcionarios {$where} ORDER BY nome",$binds);
            $total = (int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_funcionarios WHERE station_id=?",[$sid]);
        } catch(\Exception $e) { $lista=[]; $total=0; }

        $nLista = count($lista);
        $pluralFunc = $total === 1 ? 'funcionário' : 'funcionários';
        // Pré-calcular selected dos filtros
        $dLocutor = $dept==='locutor' ? 'selected' : '';
        $dJornalismo = $dept==='jornalismo' ? 'selected' : '';
        $dTecnico = $dept==='tecnico' ? 'selected' : '';
        $dComercial = $dept==='comercial' ? 'selected' : '';
        $dFinanceiro = $dept==='financeiro' ? 'selected' : '';
        $dAdministrativo = $dept==='administrativo' ? 'selected' : '';
        $dDirecao = $dept==='direcao' ? 'selected' : '';
        $dProducao = $dept==='producao' ? 'selected' : '';
        $eActivo = $est==='activo' ? 'selected' : '';
        $eFerias = $est==='ferias' ? 'selected' : '';
        $eBaixa = $est==='baixa' ? 'selected' : '';
        $eInactivo = $est==='inactivo' ? 'selected' : '';
        $linkLimpar = ($dept||$est||$q) ? "<a href='/public/rh/{$sid}' class='btn btn-sm btn-default'>Limpar</a>" : '';

        $rows = '';
        foreach($lista as $f) {
            $nome   = htmlspecialchars($f['nome']);
            $cargo  = htmlspecialchars($f['cargo']);
            $dept_  = $this->deptLabel($f['departamento']);
            $email  = htmlspecialchars($f['email'] ?? '');
            $since  = $f['data_admissao'] ? date('d/m/Y',strtotime($f['data_admissao'])) : '—';
            $salStr = $this->kz((float)$f['salario_base']);
            $eData  = htmlspecialchars(json_encode($f, JSON_HEX_APOS|JSON_HEX_QUOT), ENT_QUOTES);
            $emailHtml = $email ? "<div class='td-sub'>{$email}</div>" : '';

            $eCls = match($f['estado']) {
                'activo'  => 'badge-green','ferias'=>'badge-gold',
                'baixa'   => 'badge-blue','suspenso'=>'badge-red',
                'inactivo'=> 'badge-gray', default=>'badge-gray'
            };
            $eLbl = ['activo'=>'Activo','ferias'=>'Férias','baixa'=>'Baixa',
                     'suspenso'=>'Suspenso','inactivo'=>'Inactivo'][$f['estado']] ?? $f['estado'];

            $rows .= "<tr>
                <td>
                    <div class='td-name'>{$nome}</div>
                    {$emailHtml}
                </td>
                <td style='color:var(--tx)'>{$cargo}</td>
                <td style='color:var(--tx2)'>{$dept_}</td>
                <td><span class='badge {$eCls}'>{$eLbl}</span></td>
                <td style='color:var(--tx2)'>{$since}</td>
                <td style='color:var(--tx)'>{$salStr}</td>
                <td>
                    <button class='btn btn-sm btn-default' onclick='editFunc({$eData})'>Editar</button>
                    <a href='/public/rh/{$sid}/funcionario/{$f["id"]}' class='btn btn-sm btn-default'>Perfil</a>
                </td>
            </tr>";
        }
        if(!$rows) $rows = "<tr><td colspan='7'><div class='empty'><i class='bi bi-people' style='font-size:32px;color:var(--tx3)'></i><p>Nenhum funcionário encontrado.</p></div></td></tr>";

        $corpo = <<<HTML
<div class="page-header">
    <div>
        <div class="page-title">Funcionários</div>
        <div class="page-sub">Total: {$total} {$pluralFunc}</div>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('m-func')">
            <i class="bi bi-plus"></i> Novo Funcionário
        </button>
    </div>
</div>
<div class="page-body">
    <div class="toolbar">
        <form method="GET" style="display:contents">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" name="q" class="search-input" placeholder="Pesquisar..." value="{$q}" onchange="this.form.submit()">
            </div>
            <select name="dept" class="filter-select" onchange="this.form.submit()">
                <option value="">Todos os departamentos</option>
                <option value="locutor" {$dLocutor}>Locutor</option>
                <option value="jornalismo" {$dJornalismo}>Jornalismo</option>
                <option value="tecnico" {$dTecnico}>Técnico</option>
                <option value="comercial" {$dComercial}>Comercial</option>
                <option value="financeiro" {$dFinanceiro}>Financeiro</option>
                <option value="administrativo" {$dAdministrativo}>Administrativo</option>
                <option value="direcao" {$dDirecao}>Direcção</option>
                <option value="producao" {$dProducao}>Produção</option>
            </select>
            <select name="estado" class="filter-select" onchange="this.form.submit()">
                <option value="">Todos os estados</option>
                <option value="activo" {$eActivo}>Activo</option>
                <option value="ferias" {$eFerias}>Férias</option>
                <option value="baixa" {$eBaixa}>Baixa</option>
                <option value="inactivo" {$eInactivo}>Inactivo</option>
            </select>
            {$linkLimpar}
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nome</th><th>Cargo</th><th>Departamento</th>
                    <th>Estado</th><th>Admissão</th><th>Salário Base</th><th></th>
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="m-func">
<div class="modal">
    <div class="modal-head">
        <div class="modal-title" id="m-func-title">Novo Funcionário</div>
        <button class="modal-close" onclick="closeModal('m-func')">✕</button>
    </div>
    <div class="modal-body">
        <form method="POST" action="/public/rh/{$sid}/funcionarios/salvar" id="form-func">
            <input type="hidden" name="id" id="func-id">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Nome *</label><input type="text" name="nome" id="func-nome" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Cargo *</label><input type="text" name="cargo" id="func-cargo" class="form-input" required placeholder="Ex: Locutor Principal"></div>
                <div class="form-group"><label class="form-label">Departamento</label>
                    <select name="departamento" id="func-dept" class="form-select">
                        <option value="locutor">Locutor</option><option value="jornalismo">Jornalismo</option>
                        <option value="tecnico">Técnico</option><option value="comercial">Comercial</option>
                        <option value="financeiro">Financeiro</option><option value="administrativo">Administrativo</option>
                        <option value="direcao">Direcção</option><option value="producao">Produção</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Estado</label>
                    <select name="estado" id="func-estado" class="form-select">
                        <option value="activo">Activo</option><option value="ferias">Férias</option>
                        <option value="baixa">Baixa</option><option value="inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Tipo de Contrato</label>
                    <select name="tipo_contrato" id="func-contrato" class="form-select">
                        <option value="efectivo">Efectivo</option><option value="temporario">Temporário</option>
                        <option value="estagiario">Estagiário</option><option value="freelance">Freelance</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Data de Admissão</label><input type="date" name="data_admissao" id="func-admissao" class="form-input"></div>
                <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="func-email" class="form-input"></div>
                <div class="form-group"><label class="form-label">Telefone</label><input type="tel" name="telefone" id="func-tel" class="form-input"></div>
                <div class="form-group"><label class="form-label">Salário Base (Kz)</label><input type="number" name="salario_base" id="func-sal" class="form-input" step="0.01" min="0" value="0"></div>
                <div class="form-group"><label class="form-label">Sub. Alimentação (Kz)</label><input type="number" name="subsidio_alimentacao" id="func-sa" class="form-input" step="0.01" min="0" value="0"></div>
                <div class="form-group"><label class="form-label">Sub. Transporte (Kz)</label><input type="number" name="subsidio_transporte" id="func-st" class="form-input" step="0.01" min="0" value="0"></div>
                <div class="form-group"><label class="form-label">Banco</label><input type="text" name="banco" id="func-banco" class="form-input"></div>
                <div class="form-group full"><label class="form-label">IBAN</label><input type="text" name="iban" id="func-iban" class="form-input"></div>
                <div class="form-group full"><label class="form-label">NIF</label><input type="text" name="nif" id="func-nif" class="form-input"></div>
            </div>
        </form>
    </div>
    <div class="modal-footer">
        <button class="btn btn-default" onclick="closeModal('m-func')">Cancelar</button>
        <button class="btn btn-primary" onclick="document.getElementById('form-func').submit()">Guardar</button>
    </div>
</div>
</div>

<script>
function editFunc(f){
    document.getElementById('m-func-title').textContent='Editar Funcionário';
    var m={id:'id',nome:'nome',cargo:'cargo',departamento:'dept',estado:'estado',tipo_contrato:'contrato',data_admissao:'admissao',email:'email',telefone:'tel',salario_base:'sal',subsidio_alimentacao:'sa',subsidio_transporte:'st',banco:'banco',iban:'iban',nif:'nif'};
    for(var k in m){var el=document.getElementById('func-'+m[k]);if(el&&f[k]!==undefined)el.value=f[k]||'';}
    openModal('m-func');
}
</script>
HTML;
        $html = $this->layout('Funcionários', $corpo, $sid, 'index');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function funcionariosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        return $this->indexAction($request, $response, $params);
    }

    public function funcionarioSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody(); $id=(int)($b['id']??0);
        $d=['station_id'=>$sid,'nome'=>trim($b['nome']??''),'cargo'=>trim($b['cargo']??''),
            'departamento'=>$b['departamento']??'locutor','tipo_contrato'=>$b['tipo_contrato']??'efectivo',
            'estado'=>$b['estado']??'activo','data_admissao'=>$b['data_admissao']?:null,
            'email'=>trim($b['email']??''),'telefone'=>trim($b['telefone']??''),
            'salario_base'=>(float)($b['salario_base']??0),'subsidio_alimentacao'=>(float)($b['subsidio_alimentacao']??0),
            'subsidio_transporte'=>(float)($b['subsidio_transporte']??0),'outros_subsidios'=>0,
            'banco'=>trim($b['banco']??''),'iban'=>trim($b['iban']??''),'nif'=>trim($b['nif']??''),
            'updated_at'=>date('Y-m-d H:i:s')];
        if($id>0){$this->db->update('rnb_funcionarios',$d,['id'=>$id]);}
        else{$d['created_at']=date('Y-m-d H:i:s');$this->db->insert('rnb_funcionarios',$d);}
        return $response->withHeader('Location',"/public/rh/{$sid}")->withStatus(302);
    }

    /* ── ESCALAS ──────────────────────────────────────────── */
    public function escalasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid  = (int)$params['station_id'];
        $qp   = $request->getQueryParams();
        $data = $qp['data'] ?? date('Y-m-d');
        $hoje = date('Y-m-d');

        try {
            $escalas = $this->db->fetchAllAssociative(
                "SELECT e.*,f.nome,f.cargo,f.departamento FROM rnb_rh_escalas e
                 JOIN rnb_funcionarios f ON f.id=e.funcionario_id
                 WHERE e.station_id=? AND e.data=? ORDER BY e.hora_entrada",
                [$sid,$data]
            );
            $funcs = $this->db->fetchAllAssociative("SELECT id,nome FROM rnb_funcionarios WHERE station_id=? AND estado='activo' ORDER BY nome",[$sid]);
        } catch(\Exception $e) { $escalas=[]; $funcs=[]; }

        $oF = '<option value="">— Seleccionar —</option>';
        foreach($funcs as $f) $oF .= "<option value='{$f['id']}'>" . htmlspecialchars($f['nome']) . "</option>";

        $rows = '';
        foreach($escalas as $e) {
            $nome = htmlspecialchars($e['nome']);
            $dept = $this->deptLabel($e['departamento']);
            $prog = htmlspecialchars($e['programa'] ?? '—');
            $hI   = substr($e['hora_entrada']??'',0,5) ?: '—';
            $hF   = substr($e['hora_saida']??'',0,5) ?: '—';
            $tipo = ['normal'=>'Normal','extra'=>'Extra','folga'=>'Folga'][$e['tipo']] ?? $e['tipo'];
            $tCls = $e['tipo']==='extra'?'badge-gold':($e['tipo']==='folga'?'badge-gray':'badge-blue');
            $rows .= "<tr>
                <td style='font-family:monospace;font-size:13px;color:var(--tx)'>{$hI} – {$hF}</td>
                <td style='color:var(--tx)'>{$prog}</td>
                <td class='td-name'>{$nome}</td>
                <td style='color:var(--tx2)'>{$dept}</td>
                <td><span class='badge {$tCls}'>{$tipo}</span></td>
            </tr>";
        }
        if(!$rows) $rows = "<tr><td colspan='5'><div class='empty'><p>Sem escalas para este dia.</p></div></td></tr>";

        $dataDisp = date('d/m/Y',strtotime($data));
        $nEsc = count($escalas);
        $dAnt = date('Y-m-d',strtotime($data.' -1 day'));
        $dPrx = date('Y-m-d',strtotime($data.' +1 day'));
        $linkHoje = $data !== $hoje ? "<a href='?data={$hoje}' class='btn btn-default btn-sm'>Hoje</a>" : '';

        $corpo = <<<HTML
<div class="page-header">
    <div>
        <div class="page-title">Escalas</div>
        <div class="page-sub">{$dataDisp} · {$nEsc} turnos</div>
    </div>
    <div class="page-actions">
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <a href="?data={$dAnt}" class="btn btn-default btn-sm"><i class="bi bi-chevron-left"></i></a>
            <input type="date" name="data" value="{$data}" class="form-input" style="width:150px" onchange="this.form.submit()">
            <a href="?data={$dPrx}" class="btn btn-default btn-sm"><i class="bi bi-chevron-right"></i></a>
            {$linkHoje}
        </form>
        <button class="btn btn-primary" onclick="openModal('m-esc')"><i class="bi bi-plus"></i> Adicionar</button>
    </div>
</div>
<div class="page-body">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Hora</th><th>Programa</th><th>Funcionário</th><th>Departamento</th><th>Tipo</th></tr></thead>
            <tbody>{$rows}</tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="m-esc">
<div class="modal">
    <div class="modal-head"><div class="modal-title">Adicionar Escala</div><button class="modal-close" onclick="closeModal('m-esc')">✕</button></div>
    <div class="modal-body">
        <form method="POST" action="/public/rh/{$sid}/escalas/salvar" id="form-esc">
            <div class="form-row">
                <div class="form-group full"><label class="form-label">Funcionário *</label><select name="funcionario_id" class="form-select" required>{$oF}</select></div>
                <div class="form-group"><label class="form-label">Data *</label><input type="date" name="data" class="form-input" required value="{$data}"></div>
                <div class="form-group"><label class="form-label">Tipo</label><select name="tipo" class="form-select"><option value="normal">Normal</option><option value="extra">Extra</option><option value="folga">Folga</option></select></div>
                <div class="form-group"><label class="form-label">Hora Entrada</label><input type="time" name="hora_entrada" class="form-input" value="08:00"></div>
                <div class="form-group"><label class="form-label">Hora Saída</label><input type="time" name="hora_saida" class="form-input" value="17:00"></div>
                <div class="form-group full"><label class="form-label">Programa</label><input type="text" name="programa" class="form-input" placeholder="Ex: Manhã RNB"></div>
            </div>
        </form>
    </div>
    <div class="modal-footer"><button class="btn btn-default" onclick="closeModal('m-esc')">Cancelar</button><button class="btn btn-primary" onclick="document.getElementById('form-esc').submit()">Guardar</button></div>
</div>
</div>
HTML;
        $html = $this->layout('Escalas', $corpo, $sid, 'escalas');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function escalaSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $this->db->insert('rnb_rh_escalas',['station_id'=>$sid,'funcionario_id'=>(int)($b['funcionario_id']??0),'data'=>$b['data'],'hora_entrada'=>$b['hora_entrada']?:null,'hora_saida'=>$b['hora_saida']?:null,'tipo'=>$b['tipo']??'normal','programa'=>trim($b['programa']??''),'created_at'=>date('Y-m-d H:i:s')]);
        return $response->withHeader('Location',"/public/rh/{$sid}/escalas?data={$b['data']}")->withStatus(302);
    }

    /* ── FÉRIAS ───────────────────────────────────────────── */
    public function feriasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $qp  = $request->getQueryParams();
        $est = $qp['estado'] ?? '';

        $where = "WHERE f.station_id=?"; $binds = [$sid];
        if($est){ $where.=" AND f.estado=?"; $binds[]=$est; }

        try {
            $lista = $this->db->fetchAllAssociative("SELECT f.*,fn.nome,fn.cargo,fn.departamento FROM rnb_rh_ferias f JOIN rnb_funcionarios fn ON fn.id=f.funcionario_id {$where} ORDER BY f.estado='pendente' DESC,f.data_inicio DESC",$binds);
            $funcs = $this->db->fetchAllAssociative("SELECT id,nome FROM rnb_funcionarios WHERE station_id=? AND estado='activo' ORDER BY nome",[$sid]);
        } catch(\Exception $e) { $lista=[]; $funcs=[]; }

        $oF = '<option value="">— Seleccionar —</option>';
        foreach($funcs as $f) $oF .= "<option value='{$f['id']}'>" . htmlspecialchars($f['nome']) . "</option>";

        $nPend = count(array_filter($lista, fn($f) => $f['estado']==='pendente'));
        $fSelTodos = !$est ? 'selected' : '';
        $fSelPend  = $est === 'pendente'  ? 'selected' : '';
        $fSelAprov = $est === 'aprovado'  ? 'selected' : '';
        $fSelRej   = $est === 'rejeitado' ? 'selected' : '';

        $rows = '';
        foreach($lista as $f) {
            $nome  = htmlspecialchars($f['nome']);
            $cargo = htmlspecialchars($f['cargo']);
            $tipoL = ['ferias'=>'Férias','licenca'=>'Licença','baixa_medica'=>'Baixa Médica','ausencia_justificada'=>'Aus. Justif.','ausencia_injustificada'=>'Aus. Injust.'][$f['tipo']] ?? $f['tipo'];
            $dias  = max(0,(int)((strtotime($f['data_fim'])-strtotime($f['data_inicio']))/86400)+1);
            $eCls  = match($f['estado']){'aprovado'=>'badge-green','pendente'=>'badge-gold','rejeitado'=>'badge-red',default=>'badge-gray'};
            $eLbl  = ['aprovado'=>'Aprovado','pendente'=>'Pendente','rejeitado'=>'Rejeitado','cancelado'=>'Cancelado'][$f['estado']] ?? $f['estado'];

            $acoes = '';
            if($f['estado']==='pendente') {
                $fId = (int)$f['id'];
                $acoes = "<div style='display:flex;gap:6px'>
                    <button class='btn btn-sm btn-success' onclick='aprovar({$fId},\"aprovado\")'>Aprovar</button>
                    <button class='btn btn-sm btn-danger' onclick='aprovar({$fId},\"rejeitado\")'>Rejeitar</button>
                </div>";
            }

            $rows .= "<tr>
                <td><div class='td-name'>{$nome}</div><div class='td-sub'>{$cargo}</div></td>
                <td style='color:var(--tx2)'>{$tipoL}</td>
                <td style='color:var(--tx)'>{$f['data_inicio']}</td>
                <td style='color:var(--tx)'>{$f['data_fim']}</td>
                <td style='text-align:center;color:var(--tx)'>{$dias}</td>
                <td><span class='badge {$eCls}'>{$eLbl}</span></td>
                <td>{$acoes}</td>
            </tr>";
        }
        if(!$rows) $rows = "<tr><td colspan='7'><div class='empty'><p>Nenhum registo encontrado.</p></div></td></tr>";

        $nTotal = count($lista);
        $subStr = $nPend > 0 ? "{$nTotal} registos · <strong>{$nPend} pendentes</strong>" : "{$nTotal} registos";

        $corpo = <<<HTML
<div class="page-header">
    <div>
        <div class="page-title">Férias e Ausências</div>
        <div class="page-sub">{$subStr}</div>
    </div>
    <div class="page-actions">
        <form method="GET" style="display:flex;gap:8px">
            <select name="estado" class="filter-select" onchange="this.form.submit()">
                <option value="" {$fSelTodos}>Todos os estados</option>
                <option value="pendente" {$fSelPend}>Pendentes</option>
                <option value="aprovado" {$fSelAprov}>Aprovados</option>
                <option value="rejeitado" {$fSelRej}>Rejeitados</option>
            </select>
        </form>
        <button class="btn btn-primary" onclick="openModal('m-fer')"><i class="bi bi-plus"></i> Novo Pedido</button>
    </div>
</div>
<div class="page-body">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Funcionário</th><th>Tipo</th><th>Início</th><th>Fim</th><th style="text-align:center">Dias</th><th>Estado</th><th></th></tr></thead>
            <tbody>{$rows}</tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="m-fer">
<div class="modal">
    <div class="modal-head"><div class="modal-title">Novo Pedido</div><button class="modal-close" onclick="closeModal('m-fer')">✕</button></div>
    <div class="modal-body">
        <form method="POST" action="/public/rh/{$sid}/ferias/salvar" id="form-fer">
            <div class="form-row">
                <div class="form-group full"><label class="form-label">Funcionário *</label><select name="funcionario_id" class="form-select" required>{$oF}</select></div>
                <div class="form-group"><label class="form-label">Tipo</label>
                    <select name="tipo" class="form-select">
                        <option value="ferias">Férias</option><option value="licenca">Licença</option>
                        <option value="baixa_medica">Baixa Médica</option><option value="ausencia_justificada">Aus. Justificada</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Estado</label>
                    <select name="estado" class="form-select"><option value="pendente">Pendente</option><option value="aprovado">Aprovado</option></select>
                </div>
                <div class="form-group"><label class="form-label">Data Início *</label><input type="date" name="data_inicio" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Data Fim *</label><input type="date" name="data_fim" class="form-input" required></div>
                <div class="form-group full"><label class="form-label">Notas</label><textarea name="notas" class="form-textarea"></textarea></div>
            </div>
        </form>
    </div>
    <div class="modal-footer"><button class="btn btn-default" onclick="closeModal('m-fer')">Cancelar</button><button class="btn btn-primary" onclick="document.getElementById('form-fer').submit()">Guardar</button></div>
</div>
</div>
<script>
function aprovar(id,estado){
    if(!confirm('Confirmas esta acção?'))return;
    fetch('/public/rh/{$sid}/ferias/'+id+'/aprovar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'estado='+estado})
        .then(function(){location.reload()});
}
</script>
HTML;
        $html = $this->layout('Férias e Ausências', $corpo, $sid, 'ferias');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function feriasSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $ini=strtotime($b['data_inicio']??'now'); $fim=strtotime($b['data_fim']??'now');
        $this->db->insert('rnb_rh_ferias',['station_id'=>$sid,'funcionario_id'=>(int)($b['funcionario_id']??0),'data_inicio'=>$b['data_inicio'],'data_fim'=>$b['data_fim'],'dias_uteis'=>max(0,(int)(($fim-$ini)/86400)+1),'tipo'=>$b['tipo']??'ferias','estado'=>$b['estado']??'pendente','notas'=>trim($b['notas']??''),'created_at'=>date('Y-m-d H:i:s')]);
        return $response->withHeader('Location',"/public/rh/{$sid}/ferias")->withStatus(302);
    }

    public function feriasAprovarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $id=(int)$params['id']; $b=$request->getParsedBody(); $e=$b['estado']??'aprovado';
        if(in_array($e,['aprovado','rejeitado','cancelado'])) $this->db->update('rnb_rh_ferias',['estado'=>$e,'aprovado_por'=>'Admin'],['id'=>$id,'station_id'=>$sid]);
        $response->getBody()->write('{"status":"ok"}');
        return $response->withHeader('Content-Type','application/json');
    }

    /* ── SALÁRIOS ─────────────────────────────────────────── */
    public function folhaAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        if($guard = $this->requirePerm($response,'rh.salary.view',(string)$sid)) return $guard;
        $qp  = $request->getQueryParams();
        $mes = (int)($qp['mes'] ?? date('m'));
        $ano = (int)($qp['ano'] ?? date('Y'));

        try {
            $funcs  = $this->db->fetchAllAssociative("SELECT * FROM rnb_funcionarios WHERE station_id=? AND estado='activo' ORDER BY nome",[$sid]);
            $folhas = $this->db->fetchAllAssociative("SELECT * FROM rnb_rh_folha_pagamento WHERE station_id=? AND mes=? AND ano=?",[$sid,$mes,$ano]);
            $fMap   = [];
            foreach($folhas as $f) $fMap[$f['funcionario_id']] = $f;
        } catch(\Exception $e) { $funcs=[]; $fMap=[]; }

        $opsMes = '';
        for($m=1;$m<=12;$m++) {
            $s = $m===$mes?'selected':'';
            $opsMes .= "<option value='{$m}' {$s}>".$this->mesNome($m)."</option>";
        }
        $opsAno = '';
        for($y=date('Y');$y>=2024;$y--) {
            $s = $y===$ano?'selected':'';
            $opsAno .= "<option value='{$y}' {$s}>{$y}</option>";
        }

        $rows = '';
        $totB = 0.0; $totL = 0.0;
        foreach($funcs as $f) {
            $fp    = $fMap[$f['id']] ?? null;
            $nome  = htmlspecialchars($f['nome']);
            $cargo = htmlspecialchars($f['cargo']);
            $base  = (float)$f['salario_base'];
            $subs  = (float)$f['subsidio_alimentacao']+(float)$f['subsidio_transporte']+(float)$f['outros_subsidios'];
            $bruto = $fp ? (float)$fp['total_bruto'] : $base+$subs;
            $liq   = $fp ? (float)$fp['total_liquido'] : 0.0;
            $desc  = $fp ? (float)$fp['irt']+(float)$fp['seguranca_social'] : 0.0;
            $totB += $bruto; $totL += $liq;

            $baseStr = number_format($base,2,',','.').' Kz';
            $subsStr = number_format($subs,2,',','.').' Kz';
            $brutoStr = number_format($bruto,2,',','.').' Kz';
            $eCls = 'badge-gray'; $eLbl = 'Por processar';
            if($fp){ $eCls=match($fp['estado']){'processado'=>'badge-blue','pago'=>'badge-green',default=>'badge-gray'}; $eLbl=match($fp['estado']){'processado'=>'Processado','pago'=>'Pago',default=>'Rascunho'}; }

            $acoes = $fp
                ? ($fp['estado']==='processado' ? "<button class='btn btn-sm btn-success' onclick='pagar({$fp['id']})'>Marcar pago</button>" : '')
                : "<button class='btn btn-sm btn-default' onclick='processar({$f['id']})'>Processar</button>";

            $rows .= "<tr>
                <td><div class='td-name'>{$nome}</div><div class='td-sub'>{$cargo}</div></td>
                <td style='color:var(--tx)'>{$baseStr}</td>
                <td style='color:var(--tx2)'>{$subsStr}</td>
                <td style='color:var(--tx)'>{$brutoStr}</td>
                <td style='color:var(--red)'>" . ($desc>0 ? $this->kz($desc) : '—') . "</td>
                <td style='color:var(--green);font-weight:500'>" . ($liq>0 ? $this->kz($liq) : '—') . "</td>
                <td><span class='badge {$eCls}'>{$eLbl}</span></td>
                <td>{$acoes}</td>
            </tr>";
        }

        if(!$rows) $rows = "<tr><td colspan='8'><div class='empty'><p>Sem funcionários activos.</p></div></td></tr>";

        $nF = count($funcs);
        $mesLabel = $this->mesNome($mes).' '.$ano;
        $totBStr = number_format($totB,2,',','.').' Kz';
        $totLStr = number_format($totL,2,',','.').' Kz';

        $corpo = <<<HTML
<div class="page-header">
    <div>
        <div class="page-title">Folha de Salários</div>
        <div class="page-sub">{$mesLabel} · {$nF} funcionários</div>
    </div>
    <div class="page-actions">
        <form method="GET" style="display:flex;gap:8px">
            <select name="mes" class="filter-select" onchange="this.form.submit()">{$opsMes}</select>
            <select name="ano" class="filter-select" onchange="this.form.submit()">{$opsAno}</select>
        </form>
        <button class="btn btn-primary" onclick="processarTodos()">Processar Todos</button>
    </div>
</div>
<div class="page-body">
    <div style="display:flex;gap:32px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--bd)">
        <div><div style="font-size:12px;color:var(--tx2);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">Total Bruto</div><div style="font-size:20px;font-weight:600;color:var(--tx)">{$totBStr}</div></div>
        <div><div style="font-size:12px;color:var(--tx2);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">Total Líquido</div><div style="font-size:20px;font-weight:600;color:var(--green)">{$totLStr}</div></div>
        <div><div style="font-size:12px;color:var(--tx2);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">Funcionários</div><div style="font-size:20px;font-weight:600;color:var(--tx)">{$nF}</div></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Funcionário</th><th>Base</th><th>Subsídios</th><th>Bruto</th><th>Descontos</th><th>Líquido</th><th>Estado</th><th></th></tr></thead>
            <tbody>{$rows}</tbody>
        </table>
    </div>
</div>
<script>
var RH_SID={$sid},RH_MES={$mes},RH_ANO={$ano};
function processar(id){
    fetch('/public/rh/'+RH_SID+'/folha-pagamento/processar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'funcionario_id='+id+'&mes='+RH_MES+'&ano='+RH_ANO})
        .then(function(){location.reload()});
}
function pagar(id){
    fetch('/public/rh/'+RH_SID+'/folha-pagamento/'+id+'/pagar',{method:'POST'}).then(function(){location.reload()});
}
function processarTodos(){
    if(!confirm('Processar folha para todos?'))return;
    var btns=[...document.querySelectorAll('button[onclick*="processar("]')];
    if(!btns.length){showToast('Todos já processados');return;}
    Promise.all(btns.map(function(b){
        var id=b.getAttribute('onclick').match(/\d+/)[0];
        return fetch('/public/rh/'+RH_SID+'/folha-pagamento/processar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'funcionario_id='+id+'&mes='+RH_MES+'&ano='+RH_ANO});
    })).then(function(){location.reload()});
}
</script>
HTML;
        $html = $this->layout('Folha de Salários', $corpo, $sid, 'folha-pagamento');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function folhaProcessarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $funcId=(int)($b['funcionario_id']??0); $mes=(int)($b['mes']??date('m')); $ano=(int)($b['ano']??date('Y'));
        $f=$this->db->fetchAssociative("SELECT * FROM rnb_funcionarios WHERE id=? AND station_id=?",[$funcId,$sid]);
        if(!$f){$response->getBody()->write('{"error":"not found"}');return $response->withHeader('Content-Type','application/json');}
        $base=(float)$f['salario_base']; $subs=(float)$f['subsidio_alimentacao']+(float)$f['subsidio_transporte']+(float)$f['outros_subsidios'];
        $bruto=$base+$subs;
        $irt=$base>70000?($base-70000)*0.10:($base>35000?($base-35000)*0.07:0);
        $ss=$base*0.03; $liq=$bruto-$irt-$ss;
        try {
            $ex=$this->db->fetchOne("SELECT id FROM rnb_rh_folha_pagamento WHERE funcionario_id=? AND mes=? AND ano=?",[$funcId,$mes,$ano]);
            $d=['total_bruto'=>$bruto,'irt'=>$irt,'seguranca_social'=>$ss,'total_liquido'=>$liq,'salario_base'=>$base,'subsidios'=>$subs,'estado'=>'processado'];
            if($ex) $this->db->update('rnb_rh_folha_pagamento',$d,['funcionario_id'=>$funcId,'mes'=>$mes,'ano'=>$ano]);
            else { $d=array_merge($d,['station_id'=>$sid,'funcionario_id'=>$funcId,'mes'=>$mes,'ano'=>$ano,'created_at'=>date('Y-m-d H:i:s')]); $this->db->insert('rnb_rh_folha_pagamento',$d); }
            try {
                $ref='SAL-'.$funcId.'-'.$ano.'-'.$mes;
                if(!$this->db->fetchOne("SELECT id FROM fp_contas_movimento WHERE referencia_externa=? AND station_id=?",[$ref,$sid])) {
                    $this->db->insert('fp_contas_movimento',['station_id'=>$sid,'tipo'=>'pagar','descricao'=>'Salário: '.$f['nome'].' — '.str_pad((string)$mes,2,'0',STR_PAD_LEFT).'/'.$ano,'entidade'=>$f['nome'],'valor_total'=>$liq,'valor_pago'=>0,'data_emissao'=>date('Y-m-d'),'data_vencimento'=>date('Y-m-t'),'referencia_externa'=>$ref,'estado'=>'pendente','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
                }
            } catch(\Exception $e) {}
        } catch(\Exception $e) {}
        $response->getBody()->write(json_encode(['status'=>'ok','liquido'=>round($liq,2)]));
        return $response->withHeader('Content-Type','application/json');
    }

    public function folhaPagarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $id=(int)$params['id'];
        $this->db->update('rnb_rh_folha_pagamento',['estado'=>'pago','data_pagamento'=>date('Y-m-d')],['id'=>$id,'station_id'=>$sid]);
        $response->getBody()->write('{"status":"ok"}');
        return $response->withHeader('Content-Type','application/json');
    }

    /* ── CONTRATOS ────────────────────────────────────────── */
    public function contratosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid  = (int)$params['station_id'];
        if($guard = $this->requirePerm($response,'contratos.view',(string)$sid)) return $guard;
        $hoje = date('Y-m-d');
        try {
            $lista = $this->db->fetchAllAssociative("SELECT c.*,f.nome,f.cargo FROM rnb_rh_contratos c JOIN rnb_funcionarios f ON f.id=c.funcionario_id WHERE c.station_id=? ORDER BY c.estado='activo' DESC,c.data_fim ASC",[$sid]);
            $funcs = $this->db->fetchAllAssociative("SELECT id,nome FROM rnb_funcionarios WHERE station_id=? AND estado='activo' ORDER BY nome",[$sid]);
        } catch(\Exception $e) { $lista=[]; $funcs=[]; }

        $oF = '<option value="">— Seleccionar —</option>';
        foreach($funcs as $f) $oF .= "<option value='{$f['id']}'>" . htmlspecialchars($f['nome']) . "</option>";

        $rows = '';
        $nExp = 0;
        foreach($lista as $c) {
            $nome  = htmlspecialchars($c['nome']);
            $cargo = htmlspecialchars($c['cargo']);
            $tipo  = ucfirst(str_replace('_',' ',$c['tipo']));
            $dias  = $c['data_fim'] ? max(0,(int)((strtotime($c['data_fim'])-time())/86400)) : null;
            $isExp = $dias !== null && $dias <= 30 && $c['estado']==='activo';
            if($isExp) $nExp++;

            $salContStr = number_format((float)$c['salario_contratado'],2,',','.').' Kz';
            $eCls = match($c['estado']){'activo'=>'badge-green','expirado'=>'badge-red','cancelado'=>'badge-gray',default=>'badge-gold'};
            $eLbl = ['activo'=>'Activo','expirado'=>'Expirado','cancelado'=>'Cancelado','renovacao_pendente'=>'Renovação'][$c['estado']] ?? $c['estado'];

            $trStyle = $isExp ? "style='background:#FFFBEB'" : '';
            $fimStr = $c['data_fim']
                ? ($isExp ? "<span style='color:var(--gold)'>{$c['data_fim']} <small>({$dias}d)</small></span>" : $c['data_fim'])
                : '<span style="color:var(--tx3)">Indeterminado</span>';

            $rows .= "<tr{$trStyle}>
                <td><div class='td-name'>{$nome}</div><div class='td-sub'>{$cargo}</div></td>
                <td style='color:var(--tx2)'>{$tipo}</td>
                <td style='color:var(--tx)'>{$c['data_inicio']}</td>
                <td>{$fimStr}</td>
                <td style='color:var(--green);font-weight:500'>{$salContStr}</td>
                <td><span class='badge {$eCls}'>{$eLbl}</span></td>
            </tr>";
        }
        if(!$rows) $rows = "<tr><td colspan='6'><div class='empty'><p>Nenhum contrato registado.</p></div></td></tr>";

        $nTotal = count($lista);
        $subStr = $nExp > 0 ? "{$nTotal} contratos · <strong style='color:var(--gold)'>{$nExp} a expirar em 30 dias</strong>" : "{$nTotal} contratos";

        $corpo = <<<HTML
<div class="page-header">
    <div><div class="page-title">Contratos</div><div class="page-sub">{$subStr}</div></div>
    <div class="page-actions"><button class="btn btn-primary" onclick="openModal('m-cont')"><i class="bi bi-plus"></i> Novo Contrato</button></div>
</div>
<div class="page-body">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Funcionário</th><th>Tipo</th><th>Início</th><th>Fim</th><th>Salário</th><th>Estado</th></tr></thead>
            <tbody>{$rows}</tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="m-cont">
<div class="modal">
    <div class="modal-head"><div class="modal-title">Novo Contrato</div><button class="modal-close" onclick="closeModal('m-cont')">✕</button></div>
    <div class="modal-body">
        <form method="POST" action="/public/rh/{$sid}/contratos/salvar" id="form-cont">
            <div class="form-row">
                <div class="form-group full"><label class="form-label">Funcionário *</label><select name="funcionario_id" class="form-select" required>{$oF}</select></div>
                <div class="form-group"><label class="form-label">Tipo *</label><select name="tipo" class="form-select"><option value="efectivo">Efectivo</option><option value="temporario">Temporário</option><option value="prestacao_servicos">Prestação de Serviços</option><option value="estagiario">Estagiário</option><option value="freelance">Freelance</option></select></div>
                <div class="form-group"><label class="form-label">Estado</label><select name="estado" class="form-select"><option value="activo">Activo</option><option value="renovacao_pendente">Renovação Pendente</option></select></div>
                <div class="form-group"><label class="form-label">Data Início *</label><input type="date" name="data_inicio" class="form-input" required></div>
                <div class="form-group"><label class="form-label">Data Fim <span style="color:var(--tx3);font-weight:400">(vazio = indeterminado)</span></label><input type="date" name="data_fim" class="form-input"></div>
                <div class="form-group"><label class="form-label">Salário Contratado (Kz)</label><input type="number" name="salario_contratado" class="form-input" step="0.01" min="0" value="0"></div>
                <div class="form-group"><label class="form-label">Alertar (dias antes)</label><input type="number" name="alerta_renovacao_dias" class="form-input" min="0" value="30"></div>
                <div class="form-group full"><label class="form-label">Notas</label><textarea name="notas" class="form-textarea"></textarea></div>
            </div>
        </form>
    </div>
    <div class="modal-footer"><button class="btn btn-default" onclick="closeModal('m-cont')">Cancelar</button><button class="btn btn-primary" onclick="document.getElementById('form-cont').submit()">Guardar</button></div>
</div>
</div>
HTML;
        $html = $this->layout('Contratos', $corpo, $sid, 'contratos');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function contratoSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $this->db->insert('rnb_rh_contratos',['station_id'=>$sid,'funcionario_id'=>(int)($b['funcionario_id']??0),'tipo'=>$b['tipo']??'efectivo','data_inicio'=>$b['data_inicio'],'data_fim'=>$b['data_fim']?:null,'salario_contratado'=>(float)($b['salario_contratado']??0),'notas'=>trim($b['notas']??''),'alerta_renovacao_dias'=>(int)($b['alerta_renovacao_dias']??30),'estado'=>$b['estado']??'activo','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
        return $response->withHeader('Location',"/public/rh/{$sid}/contratos")->withStatus(302);
    }

    /* ── PERFORMANCE ──────────────────────────────────────── */
    public function performanceAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $qp  = $request->getQueryParams();
        $per = $qp['periodo'] ?? date('Y-m');

        try {
            $perf  = $this->db->fetchAllAssociative("SELECT p.*,f.nome,f.cargo FROM rnb_rh_performance p JOIN rnb_funcionarios f ON f.id=p.funcionario_id WHERE p.station_id=? AND p.periodo=? ORDER BY p.performance_score DESC",[$sid,$per]);
            $funcs = $this->db->fetchAllAssociative("SELECT id,nome FROM rnb_funcionarios WHERE station_id=? AND estado='activo' ORDER BY nome",[$sid]);
        } catch(\Exception $e) { $perf=[]; $funcs=[]; }

        $oF = '<option value="">— Seleccionar —</option>';
        foreach($funcs as $f) $oF .= "<option value='{$f['id']}'>" . htmlspecialchars($f['nome']) . "</option>";

        $opsPeriodo = '';
        for($i=0;$i<12;$i++) {
            $p  = date('Y-m',strtotime("-{$i} months"));
            [$pA,$pM] = explode('-',$p);
            $lbl = $this->mesNome((int)$pM).' '.$pA;
            $sel = $p===$per?'selected':'';
            $opsPeriodo .= "<option value='{$p}' {$sel}>{$lbl}</option>";
        }

        $rows = '';
        foreach($perf as $i=>$p) {
            $nome  = htmlspecialchars($p['nome']);
            $prog  = htmlspecialchars($p['programa_nome']??'—');
            $score = number_format((float)$p['performance_score'],1);
            $aud   = number_format((int)$p['audiencia_media'],0,',','.');
            $part  = (int)$p['participacoes'];
            $data  = $p['data'] ? date('d/m/Y',strtotime($p['data'])) : '—';
            $sCls  = (float)$p['performance_score']>=80?'badge-green':((float)$p['performance_score']>=60?'badge-gold':'badge-red');
            $pos   = $i+1;

            $rows .= "<tr>
                <td style='color:var(--tx2);font-size:13px'>{$pos}</td>
                <td class='td-name'>{$nome}</td>
                <td style='color:var(--tx2)'>{$prog}</td>
                <td style='color:var(--tx)'>{$data}</td>
                <td style='color:var(--tx)'>{$aud}</td>
                <td style='text-align:center'>{$part}</td>
                <td><span class='badge {$sCls}'>{$score}</span></td>
            </tr>";
        }
        if(!$rows) $rows = "<tr><td colspan='7'><div class='empty'><p>Sem dados de performance para este período.</p></div></td></tr>";

        $nAval  = count($perf);
        $topN   = $perf[0]['nome'] ?? '—';
        $media  = $nAval > 0 ? number_format(array_sum(array_column($perf,'performance_score'))/$nAval,1) : '—';

        [$perAno,$perMes] = explode('-',$per);
        $mesLabel = $this->mesNome((int)$perMes).' '.$perAno;
        $hoje = date('Y-m-d');

        $corpo = <<<HTML
<div class="page-header">
    <div><div class="page-title">Performance</div><div class="page-sub">{$mesLabel} · {$nAval} avaliações · Score médio: {$media}</div></div>
    <div class="page-actions">
        <form method="GET" style="display:flex;gap:8px">
            <select name="periodo" class="filter-select" onchange="this.form.submit()" style="width:160px">{$opsPeriodo}</select>
        </form>
        <button class="btn btn-primary" onclick="openModal('m-perf')"><i class="bi bi-plus"></i> Registar</button>
    </div>
</div>
<div class="page-body">
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Funcionário</th><th>Programa</th><th>Data</th><th>Audiência</th><th style="text-align:center">Partic.</th><th>Score</th></tr></thead>
            <tbody>{$rows}</tbody>
        </table>
    </div>
</div>

<div class="modal-overlay" id="m-perf">
<div class="modal">
    <div class="modal-head"><div class="modal-title">Registar Performance</div><button class="modal-close" onclick="closeModal('m-perf')">✕</button></div>
    <div class="modal-body">
        <form method="POST" action="/public/rh/{$sid}/performance/calcular" id="form-perf">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Funcionário *</label><select name="funcionario_id" class="form-select" required>{$oF}</select></div>
                <div class="form-group"><label class="form-label">Data *</label><input type="date" name="data" class="form-input" required value="{$hoje}"></div>
                <div class="form-group full"><label class="form-label">Programa</label><input type="text" name="programa_nome" class="form-input" placeholder="Ex: Manhã RNB"></div>
                <div class="form-group"><label class="form-label">Audiência Média<div class="form-hint">Número de ouvintes</div></label><input type="number" name="audiencia_media" class="form-input" min="0" value="0"></div>
                <div class="form-group"><label class="form-label">Participações</label><input type="number" name="participacoes" class="form-input" min="0" value="0"></div>
                <div class="form-group full"><label class="form-label">Engagement (0–100)<div class="form-hint">Interacção com o programa</div></label><input type="number" name="engagement_score" class="form-input" min="0" max="100" step="0.1" value="50"></div>
                <div class="form-group full"><label class="form-label">Notas</label><textarea name="notas" class="form-textarea"></textarea></div>
            </div>
        </form>
    </div>
    <div class="modal-footer"><button class="btn btn-default" onclick="closeModal('m-perf')">Cancelar</button><button class="btn btn-primary" onclick="document.getElementById('form-perf').submit()">Guardar</button></div>
</div>
</div>
HTML;
        $html = $this->layout('Performance', $corpo, $sid, 'performance');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function performanceCalcularAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody();
        $aud=(float)($b['audiencia_media']??0); $eng=(float)($b['engagement_score']??0); $part=(int)($b['participacoes']??0);
        $data=$b['data']??date('Y-m-d'); $per=substr($data,0,7);
        $score=min(100,($aud/1000*50)+($eng*0.3)+($part*0.2));
        $this->db->insert('rnb_rh_performance',['station_id'=>$sid,'funcionario_id'=>(int)($b['funcionario_id']??0),'programa_nome'=>trim($b['programa_nome']??''),'data'=>$data,'periodo'=>$per,'audiencia_media'=>(int)$aud,'participacoes'=>$part,'engagement_score'=>$eng,'performance_score'=>round($score,2),'notas'=>trim($b['notas']??''),'created_at'=>date('Y-m-d H:i:s')]);
        return $response->withHeader('Location',"/public/rh/{$sid}/performance?periodo={$per}")->withStatus(302);
    }

    /* ── ALERTAS ──────────────────────────────────────────── */
    public function alertasAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        try {
            $alertas = $this->db->fetchAllAssociative("SELECT a.*,f.nome as func_nome FROM rnb_rh_alertas a LEFT JOIN rnb_funcionarios f ON f.id=a.funcionario_id WHERE a.station_id=? AND a.resolvido=0 ORDER BY a.severidade='critico' DESC,a.created_at DESC",[$sid]);
        } catch(\Exception $e) { $alertas=[]; }

        $nCrit = count(array_filter($alertas,fn($a)=>$a['severidade']==='critico'));
        $rows  = '';
        foreach($alertas as $a) {
            $eCls = match($a['severidade']){'critico'=>'badge-red','aviso'=>'badge-gold',default=>'badge-blue'};
            $eLbl = ['critico'=>'Crítico','aviso'=>'Aviso','info'=>'Info'][$a['severidade']] ?? $a['severidade'];
            $tit  = htmlspecialchars($a['titulo']);
            $msg  = htmlspecialchars($a['mensagem']??'');
            $func = $a['func_nome'] ? htmlspecialchars($a['func_nome']) : '';
            $msgHtml = $msg ? "<div class='td-sub'>{$msg}</div>" : '';
            $data = date('d/m/Y H:i',strtotime($a['created_at']));
            $id   = (int)$a['id'];

            $rows .= "<tr>
                <td><span class='badge {$eCls}'>{$eLbl}</span></td>
                <td><div class='td-name'>{$tit}</div>{$msgHtml}</td>
                <td style='color:var(--tx2)'>{$func}</td>
                <td style='color:var(--tx3);font-size:13px'>{$data}</td>
                <td><button class='btn btn-sm btn-default' onclick='resolver({$id})'>Resolver</button></td>
            </tr>";
        }
        if(!$rows) $rows = "<tr><td colspan='5'><div class='empty'><p>Sem alertas activos. Sistema operacional.</p></div></td></tr>";

        $nTotal = count($alertas);
        $subStr = $nCrit > 0 ? "<strong style='color:var(--red)'>{$nCrit} críticos</strong> · {$nTotal} total" : "{$nTotal} alertas activos";

        $corpo = <<<HTML
<div class="page-header">
    <div><div class="page-title">Alertas</div><div class="page-sub">{$subStr}</div></div>
    <div class="page-actions"><button class="btn btn-default" onclick="gerar()"><i class="bi bi-arrow-repeat"></i> Gerar Alertas</button></div>
</div>
<div class="page-body">
    <div class="table-wrap">
        <table>
            <thead><tr><th>Severidade</th><th>Alerta</th><th>Funcionário</th><th>Data</th><th></th></tr></thead>
            <tbody>{$rows}</tbody>
        </table>
    </div>
</div>
<script>
function resolver(id){
    fetch('/public/rh/{$sid}/alertas/gerar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'resolver_id='+id})
        .then(function(){location.reload()});
}
function gerar(){
    fetch('/public/rh/{$sid}/alertas/gerar',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'gerar=1'})
        .then(function(r){return r.json()}).then(function(d){showToast(d.gerados+' alertas gerados');location.reload()});
}
</script>
HTML;
        $html = $this->layout('Alertas', $corpo, $sid, 'alertas');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    public function alertasGerarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid=(int)$params['station_id']; $b=$request->getParsedBody(); $hoje=date('Y-m-d'); $gerados=0;
        if(!empty($b['resolver_id'])){
            $this->db->update('rnb_rh_alertas',['resolvido'=>1,'lido'=>1],['id'=>(int)$b['resolver_id'],'station_id'=>$sid]);
            $response->getBody()->write('{"status":"ok"}');
            return $response->withHeader('Content-Type','application/json');
        }
        try {
            $conts=$this->db->fetchAllAssociative("SELECT c.*,f.nome FROM rnb_rh_contratos c JOIN rnb_funcionarios f ON f.id=c.funcionario_id WHERE c.station_id=? AND c.estado='activo' AND c.data_fim IS NOT NULL AND c.data_fim BETWEEN ? AND DATE_ADD(?,INTERVAL 30 DAY)",[$sid,$hoje,$hoje]);
            foreach($conts as $c){
                $dias=max(0,(int)((strtotime($c['data_fim'])-time())/86400));
                if(!$this->db->fetchOne("SELECT COUNT(*) FROM rnb_rh_alertas WHERE station_id=? AND tipo='contrato_a_expirar' AND funcionario_id=? AND resolvido=0",[$sid,$c['funcionario_id']])){
                    $this->db->insert('rnb_rh_alertas',['station_id'=>$sid,'tipo'=>'contrato_a_expirar','funcionario_id'=>$c['funcionario_id'],'titulo'=>'Contrato a expirar: '.$c['nome'],'mensagem'=>'Expira em '.$dias.' dias ('.$c['data_fim'].').','severidade'=>$dias<=7?'critico':'aviso','created_at'=>date('Y-m-d H:i:s')]);
                    $gerados++;
                }
            }
        } catch(\Exception $e){}
        try {
            $confs=$this->db->fetchAllAssociative("SELECT e.*,f.nome FROM rnb_rh_escalas e JOIN rnb_funcionarios f ON f.id=e.funcionario_id JOIN rnb_rh_ferias fe ON fe.funcionario_id=e.funcionario_id WHERE e.station_id=? AND e.data>=? AND fe.estado='aprovado' AND e.data BETWEEN fe.data_inicio AND fe.data_fim",[$sid,$hoje]);
            foreach($confs as $c){
                if(!$this->db->fetchOne("SELECT COUNT(*) FROM rnb_rh_alertas WHERE station_id=? AND tipo='funcionario_ferias_escalado' AND funcionario_id=? AND resolvido=0",[$sid,$c['funcionario_id']])){
                    $this->db->insert('rnb_rh_alertas',['station_id'=>$sid,'tipo'=>'funcionario_ferias_escalado','funcionario_id'=>$c['funcionario_id'],'titulo'=>'Conflito: '.$c['nome'].' de férias e escalado','mensagem'=>'Escalado para '.$c['data'].' mas tem férias aprovadas.','severidade'=>'critico','created_at'=>date('Y-m-d H:i:s')]);
                    $gerados++;
                }
            }
        } catch(\Exception $e){}
        $response->getBody()->write(json_encode(['status'=>'ok','gerados'=>$gerados]));
        return $response->withHeader('Content-Type','application/json');
    }

    /* ── RELATÓRIOS ───────────────────────────────────────── */
    public function relatoriosAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        if($guard = $this->requirePerm($response,'relatorios.view',(string)$sid)) return $guard;
        try {
            $s=[
                'total'   =>(int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_funcionarios WHERE station_id=?",[$sid]),
                'activos' =>(int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]),
                'massa'   =>(float)$this->db->fetchOne("SELECT COALESCE(SUM(salario_base+subsidio_alimentacao+subsidio_transporte+outros_subsidios),0) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]),
                'media'   =>(float)$this->db->fetchOne("SELECT COALESCE(AVG(salario_base),0) FROM rnb_funcionarios WHERE station_id=? AND estado='activo'",[$sid]),
                'pago'    =>(float)$this->db->fetchOne("SELECT COALESCE(SUM(total_liquido),0) FROM rnb_rh_folha_pagamento WHERE station_id=? AND mes=? AND ano=? AND estado='pago'",[$sid,(int)date('m'),(int)date('Y')]),
                'ausencias'=>(int)$this->db->fetchOne("SELECT COUNT(*) FROM rnb_rh_ferias WHERE station_id=? AND YEAR(created_at)=?",[$sid,(int)date('Y')]),
            ];
            $depts=$this->db->fetchAllAssociative("SELECT departamento,COUNT(*) as n,SUM(salario_base) as massa FROM rnb_funcionarios WHERE station_id=? AND estado='activo' GROUP BY departamento ORDER BY n DESC",[$sid]);
        } catch(\Exception $e){ $s=['total'=>0,'activos'=>0,'massa'=>0.0,'media'=>0.0,'pago'=>0.0,'ausencias'=>0]; $depts=[]; }

        $rows = '';
        $tot  = max(1,$s['activos']);
        foreach($depts as $d) {
            $lbl  = $this->deptLabel($d['departamento']);
            $n    = (int)$d['n'];
            $m    = (float)$d['massa'];
            $pct  = round($n/$tot*100);
            $mStr = number_format((float)$d['massa'],2,',','.').' Kz';
            $rows .= "<tr>
                <td style='color:var(--tx)'>{$lbl}</td>
                <td style='color:var(--tx)'>{$n}</td>
                <td style='color:var(--tx)'>{$mStr}</td>
                <td>
                    <div style='display:flex;align-items:center;gap:8px'>
                        <div style='flex:1;height:6px;background:var(--bg3);border-radius:3px;overflow:hidden'>
                            <div style='height:100%;width:{$pct}%;background:var(--pr);border-radius:3px'></div>
                        </div>
                        <span style='font-size:12px;color:var(--tx2);min-width:30px;text-align:right'>{$pct}%</span>
                    </div>
                </td>
            </tr>";
        }
        if(!$rows) $rows = "<tr><td colspan='4'><div class='empty'><p>Sem dados.</p></div></td></tr>";

        $mesNm   = $this->mesNome((int)date('m'));
        $sMassa  = $this->kz($s['massa']);
        $sMedia  = $this->kz($s['media']);
        $sPago   = $this->kz($s['pago']);
        $sAtivos = $s['activos'];
        $sTotal  = $s['total'];
        $sAus    = $s['ausencias'];

        $corpo = <<<HTML
<div class="page-header">
    <div><div class="page-title">Relatórios</div><div class="page-sub">Visão consolidada da equipa</div></div>
</div>
<div class="page-body">
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:28px">
        <div style="border:1px solid var(--bd);border-radius:8px;padding:16px;background:var(--bg2)">
            <div style="font-size:12px;color:var(--tx2);margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px">Funcionários activos</div>
            <div style="font-size:24px;font-weight:600;color:var(--tx)">{$s['activos']}</div>
            <div style="font-size:12px;color:var(--tx3);margin-top:4px">{$s['total']} no total</div>
        </div>
        <div style="border:1px solid var(--bd);border-radius:8px;padding:16px;background:var(--bg2)">
            <div style="font-size:12px;color:var(--tx2);margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px">Massa salarial</div>
            <div style="font-size:24px;font-weight:600;color:var(--tx)">{$sMassa}</div>
            <div style="font-size:12px;color:var(--tx3);margin-top:4px">Média: {$sMedia}</div>
        </div>
        <div style="border:1px solid var(--bd);border-radius:8px;padding:16px;background:var(--bg2)">
            <div style="font-size:12px;color:var(--tx2);margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px">Pago em {$mesNm}</div>
            <div style="font-size:24px;font-weight:600;color:var(--green)">{$sPago}</div>
        </div>
        <div style="border:1px solid var(--bd);border-radius:8px;padding:16px;background:var(--bg2)">
            <div style="font-size:12px;color:var(--tx2);margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px">Ausências este ano</div>
            <div style="font-size:24px;font-weight:600;color:var(--tx)">{$s['ausencias']}</div>
        </div>
    </div>

    <div style="font-size:14px;font-weight:600;color:var(--tx);margin-bottom:12px">Distribuição por Departamento</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Departamento</th><th>Pessoas</th><th>Massa Salarial</th><th>Peso</th></tr></thead>
            <tbody>{$rows}</tbody>
        </table>
    </div>
</div>
HTML;
        $html = $this->layout('Relatórios', $corpo, $sid, 'relatorios');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

    /* ─── PERFIL DO FUNCIONÁRIO ─────────────────────────────────────────────── */
    public function perfilAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $fid = (int)$params['id'];
        $tab = $request->getQueryParams()['tab'] ?? 'overview';

        // Verificar autenticação e acesso ao funcionário
        if($guard = $this->requireAuth($response,(string)$sid)) return $guard;
        if(!RhAuth::canViewFuncionario($fid, $this->rhUser())) {
            $response->getBody()->write(RhAuth::denyHtml('Não tem permissão para ver o perfil deste funcionário.'));
            return $response->withStatus(403)->withHeader('Content-Type','text/html');
        }

        try {
            $func = $this->db->fetchAssociative(
                "SELECT f.*, m.nome AS manager_nome
                 FROM rnb_funcionarios f
                 LEFT JOIN rnb_funcionarios m ON m.id = f.manager_id
                 WHERE f.id = ? AND f.station_id = ?",
                [$fid, $sid]
            );
        } catch(\Exception $e) { $func = null; }

        if(!$func) {
            $response->getBody()->write('<p style="padding:2rem;font-family:Inter,sans-serif">Funcionário não encontrado.</p>');
            return $response->withHeader('Content-Type','text/html');
        }

        try {
            $skills  = $this->db->fetchAllAssociative("SELECT * FROM rnb_rh_skills WHERE funcionario_id=? ORDER BY nivel DESC,nome ASC",[$fid]);
            $docs    = $this->db->fetchAllAssociative("SELECT * FROM rnb_rh_documentos WHERE funcionario_id=? AND station_id=? ORDER BY uploaded_at DESC",[$fid,$sid]);
            $escalas = $this->db->fetchAllAssociative("SELECT * FROM rnb_rh_escalas WHERE funcionario_id=? AND station_id=? ORDER BY data DESC LIMIT 20",[$fid,$sid]);
            $ferias  = $this->db->fetchAllAssociative("SELECT * FROM rnb_rh_ferias WHERE funcionario_id=? AND station_id=? ORDER BY data_inicio DESC",[$fid,$sid]);
            $perf    = $this->db->fetchAllAssociative("SELECT * FROM rnb_rh_performance WHERE funcionario_id=? AND station_id=? ORDER BY data DESC LIMIT 12",[$fid,$sid]);
            $folhas  = $this->db->fetchAllAssociative("SELECT * FROM rnb_rh_folha_pagamento WHERE funcionario_id=? AND station_id=? ORDER BY ano DESC,mes DESC LIMIT 6",[$fid,$sid]);
            $contrato= $this->db->fetchAssociative("SELECT * FROM rnb_rh_contratos WHERE funcionario_id=? AND estado='activo' AND station_id=? ORDER BY created_at DESC LIMIT 1",[$fid,$sid]);
            $managers= $this->db->fetchAllAssociative("SELECT id,nome FROM rnb_funcionarios WHERE station_id=? AND id!=? AND estado='activo' ORDER BY nome",[$sid,$fid]);

            $audMedia  = (float)($this->db->fetchOne("SELECT COALESCE(AVG(audiencia_media),0) FROM rnb_rh_performance WHERE funcionario_id=? AND station_id=?",[$fid,$sid]) ?? 0);
            $turnosMes = (int)($this->db->fetchOne("SELECT COUNT(*) FROM rnb_rh_escalas WHERE funcionario_id=? AND station_id=? AND DATE_FORMAT(data,'%Y-%m')=?",[$fid,$sid,date('Y-m')]) ?? 0);
            $diasFer   = (int)($this->db->fetchOne("SELECT COALESCE(SUM(dias_uteis),0) FROM rnb_rh_ferias WHERE funcionario_id=? AND station_id=? AND estado='aprovado' AND YEAR(data_inicio)=?",[$fid,$sid,date('Y')]) ?? 0);
            $agora_    = date('H:i:s'); $hoje_ = date('Y-m-d');
            $noAr      = (int)($this->db->fetchOne("SELECT COUNT(*) FROM rnb_rh_escalas WHERE funcionario_id=? AND station_id=? AND data=? AND hora_entrada<=? AND hora_saida>=?",[$fid,$sid,$hoje_,$agora_,$agora_]) ?? 0);
        } catch(\Exception $e) {
            $skills=$docs=$escalas=$ferias=$perf=$folhas=$managers=[];
            $contrato=null; $audMedia=$turnosMes=$diasFer=$noAr=0;
        }

        $nome    = htmlspecialchars($func['nome']);
        $cargo   = htmlspecialchars($func['cargo']);
        $dept    = $this->deptLabel($func['departamento']);
        $email   = htmlspecialchars($func['email'] ?? '');
        $tel     = htmlspecialchars($func['telefone'] ?? '');
        $since   = $func['data_admissao'] ? date('d/m/Y',strtotime($func['data_admissao'])) : '—';
        $salBase = number_format((float)$func['salario_base'],2,',','.').' Kz';
        $wtype   = ['studio'=>'Estúdio','remoto'=>'Remoto','campo'=>'Campo'][$func['work_type'] ?? 'studio'] ?? 'Estúdio';
        $mgr     = htmlspecialchars($func['manager_nome'] ?? '—');
        $ini     = strtoupper(substr($func['nome'],0,1).(strpos($func['nome'],' ')!==false?substr($func['nome'],strpos($func['nome'],' ')+1,1):''));
        $audStr  = number_format((int)$audMedia,0,',','.');
        $mesAtual= $this->mesNome((int)date('m'));

        $eMap = [
            'activo'  => ['Activo',  '#027A48','#ECFDF3','#D1FADF'],
            'ferias'  => ['Férias',  '#92400E','#FFFBEB','#FDE68A'],
            'baixa'   => ['Baixa',   '#1D4ED8','#EFF6FF','#BFDBFE'],
            'suspenso'=> ['Suspenso','#991B1B','#FEF2F2','#FECACA'],
            'inactivo'=> ['Inactivo','#374151','#F9FAFB','#E5E7EB'],
        ];
        [$eLbl,$eCor,$eBg,$eBd] = $eMap[$func['estado']] ?? ['—','#374151','#F9FAFB','#E5E7EB'];

        $tabs = [
            'overview'    => 'Visão Geral',
            'performance' => 'Performance',
            'escalas'     => 'Escalas',
            'ferias'      => 'Férias',
            'documentos'  => 'Documentos',
            'skills'      => 'Competências',
            'financeiro'  => 'Financeiro',
        ];

        ob_start();
?>
<style>
:root{
  --c-bg:#F7F9FB;--c-white:#FFFFFF;--c-border:#E5E7EB;--c-border2:#D1D5DB;
  --c-text:#0B1220;--c-muted:#6B7280;--c-subtle:#9CA3AF;
  --c-primary:#2563EB;--c-primary-h:#1D4ED8;--c-primary-t:#EFF6FF;
  --c-green:#027A48;--c-green-t:#ECFDF3;
  --c-red:#DC2626;--c-red-t:#FEF2F2;
  --c-gold:#92400E;--c-gold-t:#FFFBEB;
  --ff:Inter,-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
  --r:8px;--r-sm:6px;
  --sh:0 1px 3px rgba(0,0,0,.06),0 1px 2px rgba(0,0,0,.04);
  --sh-md:0 4px 12px rgba(0,0,0,.08),0 2px 4px rgba(0,0,0,.04);
}
.pf *{box-sizing:border-box}
.shell{margin-left:38px}
.pf{font-family:var(--ff);font-size:14px;color:var(--c-text);line-height:1.5;-webkit-font-smoothing:antialiased}

/* HERO */
.pf-hero{background:var(--c-white);border:1px solid var(--c-border);border-radius:var(--r);padding:24px 28px;display:flex;align-items:flex-start;justify-content:space-between;gap:24px;margin-bottom:16px;box-shadow:var(--sh)}
.pf-hero-l{display:flex;align-items:center;gap:18px}
.pf-avatar{width:72px;height:72px;border-radius:50%;background:var(--c-primary-t);border:2px solid #BFDBFE;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:700;color:var(--c-primary);flex-shrink:0;letter-spacing:-.5px}
.pf-hero-name{font-size:20px;font-weight:700;color:var(--c-text);letter-spacing:-.3px;margin-bottom:3px}
.pf-hero-sub{font-size:14px;color:var(--c-muted);margin-bottom:10px}
.pf-hero-badges{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.pf-hero-r{display:flex;align-items:center;gap:8px;flex-shrink:0}

/* BADGE */
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:100px;font-size:12px;font-weight:600;border:1px solid}
.badge-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0}
.badge-live{animation:livepulse 1.8s ease-in-out infinite}
@keyframes livepulse{0%,100%{opacity:1}50%{opacity:.35}}

/* KPIs */
.pf-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.pf-kpi{background:var(--c-white);border:1px solid var(--c-border);border-radius:var(--r);padding:18px 20px;box-shadow:var(--sh);position:relative;overflow:hidden}
.pf-kpi::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--kpi-color,var(--c-primary));border-radius:var(--r) var(--r) 0 0}
.pf-kpi-label{font-size:11px;font-weight:600;color:var(--c-subtle);text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px}
.pf-kpi-val{font-size:22px;font-weight:700;color:var(--c-text);line-height:1;margin-bottom:4px}
.pf-kpi-sub{font-size:12px;color:var(--c-subtle)}

/* LAYOUT */
.pf-layout{display:grid;grid-template-columns:260px 1fr;gap:16px;align-items:start}

/* SIDEBAR INFO */
.pf-info{background:var(--c-white);border:1px solid var(--c-border);border-radius:var(--r);overflow:hidden;box-shadow:var(--sh);margin-bottom:12px}
.pf-info-head{padding:14px 16px;border-bottom:1px solid var(--c-border)}
.pf-info-section{font-size:11px;font-weight:700;color:var(--c-subtle);text-transform:uppercase;letter-spacing:.6px}
.pf-info-row{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;padding:10px 16px;border-bottom:1px solid #F3F4F6}
.pf-info-row:last-child{border-bottom:none}
.pf-info-label{font-size:12px;color:var(--c-muted);flex-shrink:0;padding-top:1px}
.pf-info-val{font-size:13px;color:var(--c-text);font-weight:500;text-align:right}

/* CONTRATO */
.pf-contract{background:var(--c-white);border:1px solid var(--c-border);border-radius:var(--r);overflow:hidden;box-shadow:var(--sh)}

/* TABS */
.pf-tabs-wrap{background:var(--c-white);border:1px solid var(--c-border);border-radius:var(--r);overflow:hidden;box-shadow:var(--sh)}
.pf-tabs-nav{display:flex;border-bottom:1px solid var(--c-border);overflow-x:auto;padding:0 4px;background:var(--c-white)}
.pf-tabs-nav::-webkit-scrollbar{display:none}
.pf-tab-btn{padding:13px 16px;font-size:13px;font-weight:500;color:var(--c-muted);border:none;background:none;border-bottom:2px solid transparent;cursor:pointer;white-space:nowrap;transition:all .15s;font-family:var(--ff);margin-bottom:-1px}
.pf-tab-btn:hover{color:var(--c-text)}
.pf-tab-btn.active{color:var(--c-primary);border-bottom-color:var(--c-primary);font-weight:600}
.pf-tab-content{display:none}.pf-tab-content.active{display:block}

/* TABLE */
.pf-table{width:100%;border-collapse:collapse;font-size:13px}
.pf-table th{padding:9px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--c-subtle);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--c-border);background:#FAFAFA;white-space:nowrap}
.pf-table td{padding:12px 16px;border-bottom:1px solid #F3F4F6;color:var(--c-text);vertical-align:middle}
.pf-table tr:last-child td{border-bottom:none}
.pf-table tbody tr:hover td{background:#FAFAFA}
.td-muted{color:var(--c-muted) !important}
.td-mono{font-family:'SFMono-Regular',Consolas,monospace;font-size:12px}

/* STATUS BADGES */
.st{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:600;border:1px solid}
.st-green{background:var(--c-green-t);color:var(--c-green);border-color:#A7F3D0}
.st-blue{background:var(--c-primary-t);color:var(--c-primary);border-color:#BFDBFE}
.st-gold{background:var(--c-gold-t);color:var(--c-gold);border-color:#FDE68A}
.st-red{background:var(--c-red-t);color:var(--c-red);border-color:#FECACA}
.st-gray{background:#F9FAFB;color:#374151;border-color:#E5E7EB}

/* SKILL */
.sk-level{display:inline-flex;gap:3px;align-items:center}
.sk-pip{width:10px;height:10px;border-radius:2px}
.sk-filled{background:var(--c-primary)}
.sk-empty{background:var(--c-border)}

/* DOC ITEM */
.doc-item{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid #F3F4F6;transition:background .1s}
.doc-item:hover{background:#FAFAFA}
.doc-item:last-child{border-bottom:none}
.doc-icon{width:36px;height:36px;border-radius:var(--r-sm);background:var(--c-primary-t);border:1px solid #BFDBFE;display:flex;align-items:center;justify-content:center;flex-shrink:0}

/* ACTIVITY */
.act-item{display:flex;align-items:flex-start;gap:12px;padding:11px 16px;border-bottom:1px solid #F3F4F6}
.act-item:last-child{border-bottom:none}
.act-ico{width:30px;height:30px;border-radius:var(--r-sm);background:#F3F4F6;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.act-body{flex:1;min-width:0}
.act-title{font-size:13px;color:var(--c-text);font-weight:500}
.act-sub{font-size:12px;color:var(--c-subtle);margin-top:2px}
.act-date{font-size:12px;color:var(--c-subtle);flex-shrink:0;padding-top:2px}

/* BUTTONS */
.btn-ent{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:var(--r-sm);font:500 13px var(--ff);cursor:pointer;border:1px solid;text-decoration:none;transition:all .12s;white-space:nowrap}
.btn-ent:hover{text-decoration:none;transform:translateY(-1px)}
.btn-primary{background:var(--c-primary);border-color:var(--c-primary);color:#fff}
.btn-primary:hover{background:var(--c-primary-h);border-color:var(--c-primary-h);color:#fff}
.btn-secondary{background:var(--c-white);border-color:var(--c-border2);color:var(--c-text)}
.btn-secondary:hover{background:#F9FAFB;border-color:#9CA3AF}
.btn-ghost-sm{display:inline-flex;align-items:center;gap:4px;padding:5px 9px;border-radius:5px;font:500 12px var(--ff);cursor:pointer;border:1px solid var(--c-border);background:var(--c-white);color:var(--c-muted);transition:all .1s;text-decoration:none}
.btn-ghost-sm:hover{color:var(--c-text);border-color:var(--c-border2);background:#F9FAFB;text-decoration:none}
.btn-danger-sm{border-color:#FECACA;color:var(--c-red);background:var(--c-red-t)}
.btn-danger-sm:hover{background:#FEE2E2}

/* EMPTY */
.pf-empty{padding:36px 16px;text-align:center;color:var(--c-subtle);font-size:13px}

/* SECTION HEAD INSIDE TAB */
.tab-head{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--c-border)}
.tab-head-title{font-size:13px;font-weight:600;color:var(--c-text)}
.tab-head-sub{font-size:12px;color:var(--c-subtle)}

/* SKILL GROUPS */
.sk-group{padding:14px 16px;border-bottom:1px solid #F3F4F6}
.sk-group:last-child{border-bottom:none}
.sk-group-title{font-size:11px;font-weight:700;color:var(--c-subtle);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px}
.sk-row{display:flex;align-items:center;gap:12px;padding:6px 0}
.sk-name{flex:1;font-size:13px;font-weight:500;color:var(--c-text)}
.sk-remove{background:none;border:none;color:var(--c-border2);cursor:pointer;font-size:18px;line-height:1;padding:0 2px;transition:color .1s}
.sk-remove:hover{color:var(--c-red)}

/* MODAL */
.m-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.35);backdrop-filter:blur(2px);z-index:2000;align-items:flex-start;justify-content:center;padding:60px 16px}
.m-overlay.open{display:flex}
.m-box{background:var(--c-white);border:1px solid var(--c-border);border-radius:10px;width:100%;max-width:480px;max-height:calc(100vh - 120px);overflow-y:auto;box-shadow:var(--sh-md)}
.m-head{padding:18px 20px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--c-white);z-index:1}
.m-title{font-size:15px;font-weight:700;color:var(--c-text)}
.m-close{width:28px;height:28px;border-radius:var(--r-sm);border:1px solid var(--c-border);background:none;color:var(--c-muted);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px;transition:all .1s}
.m-close:hover{background:#F3F4F6;color:var(--c-text)}
.m-body{padding:20px}
.m-footer{padding:14px 20px;border-top:1px solid var(--c-border);display:flex;justify-content:flex-end;gap:8px;position:sticky;bottom:0;background:var(--c-white)}
.f-group{margin-bottom:14px}
.f-label{display:block;font-size:12px;font-weight:600;color:var(--c-text);margin-bottom:5px}
.f-hint{font-size:11px;color:var(--c-subtle);margin-top:3px}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.f-input,.f-select,.f-textarea{width:100%;padding:8px 10px;border:1px solid var(--c-border);border-radius:var(--r-sm);font:14px var(--ff);color:var(--c-text);background:var(--c-white);outline:none;transition:border-color .12s,box-shadow .12s}
.f-input:focus,.f-select:focus,.f-textarea:focus{border-color:var(--c-primary);box-shadow:0 0 0 3px rgba(37,99,235,.08)}
.f-input::placeholder,.f-textarea::placeholder{color:var(--c-subtle)}
.f-textarea{resize:vertical;min-height:72px;line-height:1.5}
.f-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;padding-right:28px}

@media(max-width:960px){.pf-layout{grid-template-columns:1fr}.pf-kpis{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.pf-kpis{grid-template-columns:1fr}.pf-hero-l{flex-direction:column;align-items:flex-start}}
</style>

<div class="page-header" style="padding:16px 24px;border-bottom:1px solid #E5E7EB;background:#fff;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-shrink:0">
    <div>
        <div style="font-size:18px;font-weight:700;color:#0B1220">Perfil do Funcionário</div>
        <div style="font-size:13px;color:#6B7280;margin-top:2px"><?= $nome ?> &middot; <?= $dept ?></div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="/public/rh/<?= $sid ?>" class="btn-ent btn-secondary"><i class="bi bi-arrow-left"></i> Funcionários</a>
    </div>
</div>

<div class="page-body pf" style="background:#F7F9FB;padding:20px 24px">

    <!-- HERO -->
    <div class="pf-hero">
        <div class="pf-hero-l">
            <div class="pf-avatar"><?= $ini ?></div>
            <div>
                <div class="pf-hero-name"><?= $nome ?></div>
                <div class="pf-hero-sub"><?= $cargo ?> &middot; <?= $dept ?></div>
                <div class="pf-hero-badges">
                    <span class="badge" style="background:<?= $eBg ?>;color:<?= $eCor ?>;border-color:<?= $eBd ?>">
                        <span class="badge-dot" style="background:<?= $eCor ?>"></span>
                        <?= $eLbl ?>
                    </span>
                    <?php if($noAr): ?>
                    <span class="badge" style="background:#FEF2F2;color:#DC2626;border-color:#FECACA">
                        <span class="badge-dot badge-live" style="background:#DC2626"></span>
                        No Ar
                    </span>
                    <?php endif ?>
                    <span style="font-size:12px;color:#9CA3AF">Desde <?= $since ?></span>
                </div>
            </div>
        </div>
        <div class="pf-hero-r">
            <button class="btn-ent btn-secondary" onclick="openM('m-doc')">
                <i class="bi bi-upload" style="font-size:13px"></i> Documento
            </button>
            <button class="btn-ent btn-secondary" onclick="openM('m-skill')">
                <i class="bi bi-plus" style="font-size:13px"></i> Competência
            </button>
        </div>
    </div>

    <!-- KPIs -->
    <div class="pf-kpis">
        <div class="pf-kpi" style="--kpi-color:#2563EB">
            <div class="pf-kpi-label">Salário Base</div>
            <div class="pf-kpi-val" style="font-size:18px"><?= $salBase ?></div>
            <div class="pf-kpi-sub"><?= $wtype ?></div>
        </div>
        <div class="pf-kpi" style="--kpi-color:#059669">
            <div class="pf-kpi-label">Dias de Férias <?= date('Y') ?></div>
            <div class="pf-kpi-val"><?= $diasFer ?></div>
            <div class="pf-kpi-sub">dias aprovados este ano</div>
        </div>
        <div class="pf-kpi" style="--kpi-color:#7C3AED">
            <div class="pf-kpi-label">Turnos em <?= $mesAtual ?></div>
            <div class="pf-kpi-val"><?= $turnosMes ?></div>
            <div class="pf-kpi-sub">escalas este mês</div>
        </div>
        <div class="pf-kpi" style="--kpi-color:#D97706">
            <div class="pf-kpi-label">Audiência Média</div>
            <div class="pf-kpi-val"><?= $audStr ?></div>
            <div class="pf-kpi-sub">ouvintes por programa</div>
        </div>
    </div>

    <!-- GRID -->
    <div class="pf-layout">

        <!-- SIDEBAR -->
        <div>
            <div class="pf-info">
                <div class="pf-info-head"><span class="pf-info-section">Informação Pessoal</span></div>
                <?php
                $rows = [
                    ['Departamento',   $dept],
                    ['Tipo Trabalho',  $wtype],
                    ['Admissão',       $since],
                    ['Gestor',         $mgr],
                    ['Email',          $email ?: '—'],
                    ['Telefone',       $tel ?: '—'],
                ];
                foreach($rows as [$l,$v]):
                ?>
                <div class="pf-info-row">
                    <span class="pf-info-label"><?= $l ?></span>
                    <span class="pf-info-val"><?= $v ?></span>
                </div>
                <?php endforeach ?>
            </div>

            <?php if($contrato): ?>
            <div class="pf-contract">
                <div class="pf-info-head"><span class="pf-info-section">Contrato Activo</span></div>
                <div class="pf-info-row">
                    <span class="pf-info-label">Tipo</span>
                    <span class="pf-info-val"><?= ucfirst(str_replace('_',' ',$contrato['tipo'])) ?></span>
                </div>
                <div class="pf-info-row">
                    <span class="pf-info-label">Início</span>
                    <span class="pf-info-val"><?= $contrato['data_inicio'] ?></span>
                </div>
                <div class="pf-info-row">
                    <span class="pf-info-label">Fim</span>
                    <span class="pf-info-val" style="color:<?= $contrato['data_fim'] ? 'var(--c-text)' : '#027A48' ?>">
                        <?= $contrato['data_fim'] ?? 'Indeterminado' ?>
                    </span>
                </div>
                <div class="pf-info-row">
                    <span class="pf-info-label">Valor</span>
                    <span class="pf-info-val"><?= number_format((float)$contrato['salario_contratado'],2,',','.') ?> Kz</span>
                </div>
            </div>
            <?php endif ?>
        </div>

        <!-- TABS -->
        <div class="pf-tabs-wrap">
            <div class="pf-tabs-nav">
                <?php foreach($tabs as $tk => $tl): ?>
                <button class="pf-tab-btn <?= $tab===$tk?'active':'' ?>" onclick="switchTab('<?= $tk ?>',this)"><?= $tl ?></button>
                <?php endforeach ?>
            </div>

            <!-- OVERVIEW -->
            <div id="tc-overview" class="pf-tab-content <?= $tab==='overview'?'active':'' ?>">
                <?php
                $events = [];
                foreach(array_slice($escalas,0,4) as $e) {
                    $events[] = ['calendar3','Escala — '.htmlspecialchars($e['programa']??'—'),substr($e['hora_entrada']??'',0,5).' – '.substr($e['hora_saida']??'',0,5),date('d/m/Y',strtotime($e['data']))];
                }
                foreach(array_slice($ferias,0,2) as $f) {
                    $events[] = ['umbrella','Férias — '.htmlspecialchars(['aprovado'=>'Aprovadas','pendente'=>'Pendentes'][$f['estado']]??$f['estado']),$f['data_inicio'].' a '.$f['data_fim'],date('d/m/Y',strtotime($f['data_inicio']))];
                }
                foreach(array_slice($docs,0,2) as $d) {
                    $events[] = ['file-earmark','Documento — '.htmlspecialchars($d['nome']),htmlspecialchars(['bi'=>'BI','nif'=>'NIF','contrato'=>'Contrato','certificado'=>'Certificado','outro'=>'Outro'][$d['tipo']]??$d['tipo']),date('d/m/Y',strtotime($d['uploaded_at']))];
                }
                usort($events, fn($a,$b) => strcmp($b[3],$a[3]));
                if($events):
                    foreach($events as $ev):
                ?>
                <div class="act-item">
                    <div class="act-ico">
                        <i class="bi bi-<?= $ev[0] ?>" style="font-size:13px;color:#6B7280"></i>
                    </div>
                    <div class="act-body">
                        <div class="act-title"><?= $ev[1] ?></div>
                        <div class="act-sub"><?= $ev[2] ?></div>
                    </div>
                    <div class="act-date"><?= $ev[3] ?></div>
                </div>
                <?php endforeach; else: ?>
                <div class="pf-empty">Sem actividade registada.</div>
                <?php endif ?>
            </div>

            <!-- PERFORMANCE -->
            <div id="tc-performance" class="pf-tab-content <?= $tab==='performance'?'active':'' ?>">
                <?php if($perf): ?>
                <div class="tab-head">
                    <div>
                        <div class="tab-head-title">Performance Real</div>
                        <div class="tab-head-sub">Dados sincronizados automaticamente do Myriad</div>
                    </div>
                    <a href="/api/rnb/sync/performance?date=<?= date('Y-m-d') ?>" 
                       class="btn-ghost-sm" target="_blank"
                       onclick="setTimeout(()=>location.reload(),2000)">
                       <i class="bi bi-arrow-repeat"></i> Sincronizar
                    </a>
                </div>
                <table class="pf-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Programa</th>
                            <th>Período</th>
                            <th>Músicas</th>
                            <th>Audiência</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach($perf as $p):
                        $sc   = (float)$p['performance_score'];
                        $sCls = $sc>=80?'st-green':($sc>=60?'st-gold':'st-red');
                        $per  = ['manha'=>'Manhã','almoco'=>'Almoço','tarde'=>'Tarde',
                                 'noite'=>'Noite','madrugada'=>'Madrugada'][$p['periodo']??''] ?? $p['periodo'];
                    ?>
                    <tr>
                        <td class="td-muted"><?= date('d/m/Y',strtotime($p['data'])) ?></td>
                        <td style="font-weight:500"><?= htmlspecialchars($p['programa_nome']??'—') ?></td>
                        <td class="td-muted"><?= $per ?></td>
                        <td style="text-align:center"><?= (int)$p['participacoes'] ?></td>
                        <td><?= number_format((int)$p['audiencia_media'],0,',','.') ?></td>
                        <td><span class="st <?= $sCls ?>"><?= number_format($sc,1) ?></span></td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="pf-empty" style="padding:24px">
                    <div style="margin-bottom:12px">Sem dados de performance.</div>
                    <a href="/api/rnb/sync/performance?date=<?= date('Y-m-d') ?>" 
                       class="btn-ent btn-secondary" target="_blank"
                       onclick="setTimeout(()=>location.reload(),2000)">
                       <i class="bi bi-arrow-repeat"></i> Sincronizar agora
                    </a>
                </div>
                <?php endif ?>
            </div>

            <!-- ESCALAS -->
            <div id="tc-escalas" class="pf-tab-content <?= $tab==='escalas'?'active':'' ?>">
                <?php if($escalas): ?>
                <table class="pf-table">
                    <thead><tr><th>Data</th><th>Programa</th><th>Entrada</th><th>Saída</th><th>Tipo</th></tr></thead>
                    <tbody>
                    <?php
                    $hoje__ = date('Y-m-d'); $agora__ = date('H:i:s');
                    foreach($escalas as $e):
                        $isNow = $e['data']===$hoje__ && ($e['hora_entrada']??'99')<=$agora__ && ($e['hora_saida']??'00')>=$agora__;
                        $tCls  = $e['tipo']==='extra'?'st-gold':($e['tipo']==='folga'?'st-gray':'st-blue');
                        $tLbl  = ['normal'=>'Normal','extra'=>'Extra','folga'=>'Folga'][$e['tipo']] ?? $e['tipo'];
                    ?>
                    <tr <?= $isNow ? 'style="background:#EFF6FF"' : '' ?>>
                        <td style="font-weight:<?= $isNow?'600':'400' ?>;color:<?= $isNow?'var(--c-primary)':'var(--c-muted)' ?>"><?= date('d/m/Y',strtotime($e['data'])) ?></td>
                        <td><?= htmlspecialchars($e['programa']??'—') ?></td>
                        <td class="td-mono"><?= substr($e['hora_entrada']??'',0,5) ?></td>
                        <td class="td-mono"><?= substr($e['hora_saida']??'',0,5) ?></td>
                        <td><span class="st <?= $tCls ?>"><?= $tLbl ?></span></td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="pf-empty">Sem escalas registadas.</div>
                <?php endif ?>
            </div>

            <!-- FERIAS -->
            <div id="tc-ferias" class="pf-tab-content <?= $tab==='ferias'?'active':'' ?>">
                <div class="tab-head">
                    <div>
                        <div class="tab-head-title">Férias e Ausências</div>
                        <div class="tab-head-sub"><?= $diasFer ?> dias usados em <?= date('Y') ?></div>
                    </div>
                    <a href="/public/rh/<?= $sid ?>/ferias" class="btn-ghost-sm">Gerir</a>
                </div>
                <?php if($ferias): ?>
                <table class="pf-table">
                    <thead><tr><th>Tipo</th><th>Início</th><th>Fim</th><th>Dias</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach($ferias as $f):
                        $fT   = ['ferias'=>'Férias','licenca'=>'Licença','baixa_medica'=>'Baixa Médica','ausencia_justificada'=>'Aus. Just.'][$f['tipo']] ?? $f['tipo'];
                        $fCls = ['aprovado'=>'st-green','pendente'=>'st-gold','rejeitado'=>'st-red','cancelado'=>'st-gray'][$f['estado']] ?? 'st-gray';
                        $fLbl = ['aprovado'=>'Aprovado','pendente'=>'Pendente','rejeitado'=>'Rejeitado','cancelado'=>'Cancelado'][$f['estado']] ?? $f['estado'];
                        $fD   = max(0,(int)((strtotime($f['data_fim'])-strtotime($f['data_inicio']))/86400)+1);
                    ?>
                    <tr>
                        <td style="font-weight:500"><?= $fT ?></td>
                        <td class="td-muted"><?= $f['data_inicio'] ?></td>
                        <td class="td-muted"><?= $f['data_fim'] ?></td>
                        <td style="text-align:center;font-weight:600"><?= $fD ?></td>
                        <td><span class="st <?= $fCls ?>"><?= $fLbl ?></span></td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="pf-empty">Sem registos de férias.</div>
                <?php endif ?>
            </div>

            <!-- DOCUMENTOS -->
            <div id="tc-documentos" class="pf-tab-content <?= $tab==='documentos'?'active':'' ?>">
                <div class="tab-head">
                    <div>
                        <div class="tab-head-title">Documentos</div>
                        <div class="tab-head-sub"><?= count($docs) ?> ficheiro(s)</div>
                    </div>
                    <button class="btn-ghost-sm" onclick="openM('m-doc')">
                        <i class="bi bi-upload" style="font-size:12px"></i> Carregar
                    </button>
                </div>
                <?php if($docs): ?>
                <?php foreach($docs as $d):
                    $dn  = htmlspecialchars($d['nome']);
                    $dt  = ['bi'=>'BI','nif'=>'NIF','contrato'=>'Contrato','certificado'=>'Certificado','outro'=>'Outro'][$d['tipo']] ?? $d['tipo'];
                    $dd  = date('d/m/Y',strtotime($d['uploaded_at']));
                    $dkb = $d['tamanho_kb'] ? ' · '.$d['tamanho_kb'].' KB' : '';
                    $did = (int)$d['id'];
                ?>
                <div class="doc-item">
                    <div class="doc-icon">
                        <i class="bi bi-file-earmark-pdf" style="color:#2563EB;font-size:15px"></i>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $dn ?></div>
                        <div style="font-size:11px;color:#9CA3AF;margin-top:2px"><?= $dt ?> &middot; <?= $dd ?><?= $dkb ?></div>
                    </div>
                    <div style="display:flex;gap:6px;flex-shrink:0">
                        <a href="/public/rh/<?= $sid ?>/documentos/<?= $did ?>/download" class="btn-ghost-sm" target="_blank">
                            <i class="bi bi-download" style="font-size:12px"></i>
                        </a>
                        <button class="btn-ghost-sm btn-danger-sm" onclick="delDoc(<?= $did ?>)">
                            <i class="bi bi-trash" style="font-size:12px"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach ?>
                <?php else: ?>
                <div class="pf-empty">Sem documentos carregados.</div>
                <?php endif ?>
            </div>

            <!-- SKILLS -->
            <div id="tc-skills" class="pf-tab-content <?= $tab==='skills'?'active':'' ?>">
                <div class="tab-head">
                    <div>
                        <div class="tab-head-title">Competências</div>
                        <div class="tab-head-sub"><?= count($skills) ?> registadas</div>
                    </div>
                    <button class="btn-ghost-sm" onclick="openM('m-skill')">
                        <i class="bi bi-plus" style="font-size:12px"></i> Adicionar
                    </button>
                </div>
                <?php
                $catL  = ['locutor'=>'Locutor','tecnico'=>'Técnico','jornalismo'=>'Jornalismo','geral'=>'Geral'];
                $byCat = [];
                foreach($skills as $s) $byCat[$s['categoria']][] = $s;
                if($byCat): foreach($byCat as $cat => $sl):
                ?>
                <div class="sk-group">
                    <div class="sk-group-title"><?= $catL[$cat]??$cat ?></div>
                    <?php foreach($sl as $s):
                        $sid_ = (int)$s['id'];
                        $n    = (int)$s['nivel'];
                    ?>
                    <div class="sk-row">
                        <div class="sk-name"><?= htmlspecialchars($s['nome']) ?></div>
                        <div class="sk-level">
                            <?php for($i=1;$i<=5;$i++): ?>
                            <span class="sk-pip <?= $i<=$n?'sk-filled':'sk-empty' ?>"></span>
                            <?php endfor ?>
                        </div>
                        <span style="font-size:11px;color:#9CA3AF;min-width:28px;text-align:right"><?= $n ?>/5</span>
                        <button class="sk-remove" onclick="delSkill(<?= $sid_ ?>)" title="Remover">&times;</button>
                    </div>
                    <?php endforeach ?>
                </div>
                <?php endforeach; else: ?>
                <div class="pf-empty">Sem competências registadas.</div>
                <?php endif ?>
            </div>

            <!-- FINANCEIRO -->
            <div id="tc-financeiro" class="pf-tab-content <?= $tab==='financeiro'?'active':'' ?>">
                <div class="tab-head">
                    <div class="tab-head-title">Histórico Salarial</div>
                    <a href="/public/rh/<?= $sid ?>/folha-pagamento" class="btn-ghost-sm">Ver Folha</a>
                </div>
                <?php if($folhas): ?>
                <table class="pf-table">
                    <thead><tr><th>Período</th><th>Base</th><th>Descontos</th><th>Líquido</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach($folhas as $f):
                        $fCls = ['processado'=>'st-blue','pago'=>'st-green','rascunho'=>'st-gray'][$f['estado']] ?? 'st-gray';
                        $fLbl = ['processado'=>'Processado','pago'=>'Pago','rascunho'=>'Rascunho'][$f['estado']] ?? $f['estado'];
                    ?>
                    <tr>
                        <td style="font-weight:500"><?= $this->mesNome((int)$f['mes']) ?> <?= $f['ano'] ?></td>
                        <td class="td-muted"><?= number_format((float)$f['salario_base'],2,',','.') ?> Kz</td>
                        <td style="color:#DC2626"><?= number_format((float)$f['irt']+(float)$f['seguranca_social'],2,',','.') ?> Kz</td>
                        <td style="color:#027A48;font-weight:600"><?= number_format((float)$f['total_liquido'],2,',','.') ?> Kz</td>
                        <td><span class="st <?= $fCls ?>"><?= $fLbl ?></span></td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="pf-empty">Sem histórico salarial.</div>
                <?php endif ?>
            </div>

        </div><!-- /tabs-wrap -->
    </div><!-- /grid -->
</div><!-- /page-body -->

<!-- MODAL: Competência -->
<div class="m-overlay" id="m-skill">
<div class="m-box">
    <div class="m-head"><div class="m-title">Nova Competência</div><button class="m-close" onclick="closeM('m-skill')">&#x2715;</button></div>
    <div class="m-body">
        <form method="POST" action="/public/rh/<?= $sid ?>/skills/salvar" id="fs">
            <input type="hidden" name="funcionario_id" value="<?= $fid ?>">
            <div class="f-group">
                <label class="f-label">Competência *</label>
                <input type="text" name="nome" class="f-input" required placeholder="Ex: locução, streaming, edição...">
            </div>
            <div class="f-row">
                <div class="f-group" style="margin-bottom:0">
                    <label class="f-label">Categoria</label>
                    <select name="categoria" class="f-select">
                        <option value="geral">Geral</option>
                        <option value="locutor">Locutor</option>
                        <option value="tecnico">Técnico</option>
                        <option value="jornalismo">Jornalismo</option>
                    </select>
                </div>
                <div class="f-group" style="margin-bottom:0">
                    <label class="f-label">Nível</label>
                    <select name="nivel" class="f-select">
                        <option value="1">1 — Básico</option>
                        <option value="2">2 — Elementar</option>
                        <option value="3" selected>3 — Intermédio</option>
                        <option value="4">4 — Avançado</option>
                        <option value="5">5 — Especialista</option>
                    </select>
                </div>
            </div>
        </form>
    </div>
    <div class="m-footer">
        <button class="btn-ent btn-secondary" onclick="closeM('m-skill')">Cancelar</button>
        <button class="btn-ent btn-primary" onclick="document.getElementById('fs').submit()">Guardar</button>
    </div>
</div>
</div>

<!-- MODAL: Documento -->
<div class="m-overlay" id="m-doc">
<div class="m-box">
    <div class="m-head"><div class="m-title">Carregar Documento</div><button class="m-close" onclick="closeM('m-doc')">&#x2715;</button></div>
    <div class="m-body">
        <form method="POST" action="/public/rh/<?= $sid ?>/documentos/upload" enctype="multipart/form-data" id="fd">
            <input type="hidden" name="funcionario_id" value="<?= $fid ?>">
            <div class="f-group">
                <label class="f-label">Tipo</label>
                <select name="tipo" class="f-select">
                    <option value="bi">BI / Identificação</option>
                    <option value="nif">NIF</option>
                    <option value="contrato">Contrato</option>
                    <option value="certificado">Certificado</option>
                    <option value="outro">Outro</option>
                </select>
            </div>
            <div class="f-group">
                <label class="f-label">Nome do documento *</label>
                <input type="text" name="nome" class="f-input" required placeholder="Ex: BI actualizado 2026">
            </div>
            <div class="f-group">
                <label class="f-label">Ficheiro *</label>
                <input type="file" name="ficheiro" class="f-input" accept=".pdf,.jpg,.jpeg,.png" required style="padding:6px">
                <div class="f-hint">PDF, JPG ou PNG &middot; máximo 5 MB</div>
            </div>
        </form>
    </div>
    <div class="m-footer">
        <button class="btn-ent btn-secondary" onclick="closeM('m-doc')">Cancelar</button>
        <button class="btn-ent btn-primary" onclick="document.getElementById('fd').submit()">Carregar</button>
    </div>
</div>
</div>

<script>
function switchTab(id, btn) {
    document.querySelectorAll('.pf-tab-content').forEach(function(el){ el.classList.remove('active'); });
    document.querySelectorAll('.pf-tab-btn').forEach(function(b){ b.classList.remove('active'); });
    var tc = document.getElementById('tc-'+id);
    if(tc) tc.classList.add('active');
    if(btn) btn.classList.add('active');
}
function openM(id){ document.getElementById(id).classList.add('open'); }
function closeM(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.m-overlay').forEach(function(m){
    m.addEventListener('click', function(e){ if(e.target===m) m.classList.remove('open'); });
});
function delSkill(id){
    if(!confirm('Remover esta competência?'))return;
    fetch('/public/rh/<?= $sid ?>/skills/'+id+'/apagar',{method:'POST'}).then(function(){location.reload()});
}
function delDoc(id){
    if(!confirm('Apagar este documento permanentemente?'))return;
    fetch('/public/rh/<?= $sid ?>/documentos/'+id+'/apagar',{method:'POST'}).then(function(){location.reload()});
}
</script>
<?php
        $corpo = ob_get_clean();
        $html  = $this->layout('Perfil — '.$nome, $corpo, $sid, 'index');
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type','text/html');
    }

        /* ─── SKILL SALVAR ───────────────────────────────────── */
    public function skillSalvarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $b   = $request->getParsedBody();
        $fid = (int)($b['funcionario_id'] ?? 0);
        $this->db->insert('rnb_rh_skills', [
            'station_id'     => $sid,
            'funcionario_id' => $fid,
            'nome'           => trim($b['nome'] ?? ''),
            'nivel'          => max(1,min(5,(int)($b['nivel'] ?? 3))),
            'categoria'      => $b['categoria'] ?? 'geral',
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        return $response->withHeader('Location',"/public/rh/{$sid}/funcionario/{$fid}")->withStatus(302);
    }

    /* ─── SKILL APAGAR ───────────────────────────────────── */
    public function skillApagarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $id  = (int)$params['id'];
        $this->db->delete('rnb_rh_skills', ['id'=>$id,'station_id'=>$sid]);
        $response->getBody()->write('{"status":"ok"}');
        return $response->withHeader('Content-Type','application/json');
    }

    /* ─── DOCUMENTO UPLOAD ───────────────────────────────────────────────────── */
    public function documentoUploadAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid  = (int)$params['station_id'];
        if($guard = $this->requirePerm($response,'rh.documents.upload',(string)$sid)) return $guard;
        $b    = $request->getParsedBody();
        $fid  = (int)($b['funcionario_id'] ?? 0);

        if(!$fid || !$sid) {
            return $response->withHeader('Location', "/public/rh/{$sid}")->withStatus(302);
        }

        $uploadDir = '/var/azuracast/uploads/rh_docs/';
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0750, true);
        }

        $files = $request->getUploadedFiles();
        $file  = $files['ficheiro'] ?? null;

        if(!$file || $file->getError() !== UPLOAD_ERR_OK) {
            return $response->withHeader('Location', "/public/rh/{$sid}/funcionario/{$fid}")->withStatus(302);
        }

        // Validar tamanho (5 MB max)
        if($file->getSize() > 5 * 1024 * 1024) {
            return $response->withHeader('Location', "/public/rh/{$sid}/funcionario/{$fid}")->withStatus(302);
        }

        // Validar MIME via finfo (não confiar na extensão)
        $tmpStream  = $file->getStream();
        $tmpContent = $tmpStream->getContents();
        $tmpFile    = tempnam(sys_get_temp_dir(), 'rh_upload_');
        file_put_contents($tmpFile, $tmpContent);

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $tmpFile);
        finfo_close($finfo);

        $allowed = [
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
        ];

        if(!isset($allowed[$mime])) {
            unlink($tmpFile);
            return $response->withHeader('Location', "/public/rh/{$sid}/funcionario/{$fid}")->withStatus(302);
        }

        $ext      = $allowed[$mime];
        $filename = uniqid('doc_', true) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        // Mover o ficheiro temporário para o destino seguro
        if(!rename($tmpFile, $destPath)) {
            @unlink($tmpFile);
            return $response->withHeader('Location', "/public/rh/{$sid}/funcionario/{$fid}")->withStatus(302);
        }

        chmod($destPath, 0640);
        $kb = (int)(filesize($destPath) / 1024);

        $this->db->insert('rnb_rh_documentos', [
            'station_id'     => $sid,
            'funcionario_id' => $fid,
            'tipo'           => in_array($b['tipo'] ?? '', ['bi','nif','contrato','certificado','outro']) ? $b['tipo'] : 'outro',
            'nome'           => substr(strip_tags(trim($b['nome'] ?? $file->getClientFilename())), 0, 200),
            'ficheiro_path'  => $filename,
            'tamanho_kb'     => $kb,
            'uploaded_at'    => date('Y-m-d H:i:s'),
        ]);

        return $response->withHeader('Location', "/public/rh/{$sid}/funcionario/{$fid}")->withStatus(302);
    }

        /* ─── DOCUMENTO DOWNLOAD ─────────────────────────────────────────────────── */
    public function documentoDownloadAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $id  = (int)$params['id'];

        $doc = $this->db->fetchAssociative(
            'SELECT * FROM rnb_rh_documentos WHERE id=? AND station_id=?',
            [$id, $sid]
        );

        if(!$doc) {
            $response->getBody()->write('Acesso negado.');
            return $response->withStatus(403)->withHeader('Content-Type', 'text/plain');
        }

        // Prevenir path traversal — o ficheiro_path não pode ter / \ ou ..
        $fname = basename($doc['ficheiro_path']);
        if($fname !== $doc['ficheiro_path'] || strpos($fname, '..') !== false) {
            $response->getBody()->write('Ficheiro inválido.');
            return $response->withStatus(400)->withHeader('Content-Type', 'text/plain');
        }

        $path = '/var/azuracast/uploads/rh_docs/' . $fname;

        if(!file_exists($path) || !is_file($path)) {
            $response->getBody()->write('Ficheiro não encontrado.');
            return $response->withStatus(404)->withHeader('Content-Type', 'text/plain');
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = finfo_file($finfo, $path) ?: 'application/octet-stream';
        finfo_close($finfo);

        // Apenas servir tipos permitidos
        $mimeAllowed = ['application/pdf', 'image/jpeg', 'image/png'];
        if(!in_array($mime, $mimeAllowed)) {
            $response->getBody()->write('Tipo de ficheiro não permitido.');
            return $response->withStatus(400)->withHeader('Content-Type', 'text/plain');
        }

        $nomeSeguro = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $doc['nome']);
        $size       = filesize($path);

        $response->getBody()->write(file_get_contents($path));

        return $response
            ->withHeader('Content-Type', $mime)
            ->withHeader('Content-Disposition', 'inline; filename="' . $nomeSeguro . '"')
            ->withHeader('Content-Length', (string)$size)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'private, no-cache');
    }

        /* ─── DOCUMENTO APAGAR ──────────────────────────────────────────────────── */
    public function documentoApagarAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $sid = (int)$params['station_id'];
        $id  = (int)$params['id'];

        $doc = $this->db->fetchAssociative(
            'SELECT * FROM rnb_rh_documentos WHERE id=? AND station_id=?',
            [$id, $sid]
        );

        if($doc) {
            $fname = basename($doc['ficheiro_path']);
            if($fname && strpos($fname, '..') === false) {
                $path = '/var/azuracast/uploads/rh_docs/' . $fname;
                if(file_exists($path) && is_file($path)) {
                    unlink($path);
                }
            }
            $this->db->delete('rnb_rh_documentos', ['id' => $id, 'station_id' => $sid]);
        }

        $response->getBody()->write('{"status":"ok"}');
        return $response->withHeader('Content-Type', 'application/json');
    }
}