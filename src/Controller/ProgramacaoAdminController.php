<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin\Controller;
use App\Http\Response;
use App\Http\ServerRequest;
use Plugin\ProgramacaoPlugin\Service\ProgramacaoService;
use Psr\Http\Message\ResponseInterface;

class ProgramacaoAdminController {
    private ProgramacaoService $service;
    public function __construct(ProgramacaoService $service) { $this->service = $service; }

    public function playerAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $res->getBody()->write($this->renderPlayerDemo($s)); return $res->withHeader('Content-Type','text/html'); }
    public function indexAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $st=$this->service->getEstatisticas($s); $res->getBody()->write($this->renderPage('Dashboard',$this->renderDashboard($s,$st),$s,'dashboard')); return $res->withHeader('Content-Type','text/html'); }
    public function programasAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $pr=$this->service->getProgramas($s); $res->getBody()->write($this->renderPage('Programas',$this->renderProgramasList($s,$pr),$s,'programas')); return $res->withHeader('Content-Type','text/html'); }
    public function programaFormAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $id=$p['id']??null; $pr=$id?$this->service->getPrograma((int)$id):null; $lc=$this->service->getLocutores($s); $res->getBody()->write($this->renderPage($id?'Editar Programa':'Novo Programa',$this->renderProgramaForm($s,$pr,$lc),$s,'programas')); return $res->withHeader('Content-Type','text/html'); }
    public function programaSaveAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $id=$p['id']??null; $post=$r->getParsedBody(); $data=['station_id'=>$s,'nome'=>$post['nome']??'','descricao'=>$post['descricao']??'','banner'=>$post['banner']??null,'hora_inicio'=>$post['hora_inicio']??'00:00','hora_fim'=>$post['hora_fim']??'00:00','dias_semana'=>json_encode($post['dias_semana']??[]),'ativo'=>isset($post['ativo'])?1:0]; if($id){$data['id']=(int)$id;} $pid=$this->service->savePrograma($data); foreach($this->service->getLocutoresDoPrograma($pid) as $l){$this->service->desvincularLocutorPrograma($pid,(int)$l['id']);} $locs=$post['locutores']??[]; $lp=(int)($post['locutor_principal']??0); foreach($locs as $li){$this->service->vincularLocutorPrograma($pid,(int)$li,((int)$li===$lp));} return $res->withHeader('Location','/public/programacao/'.$s.'/programas')->withStatus(302); }
    public function programaDeleteAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $this->service->deletePrograma((int)$p['id']); return $res->withHeader('Location','/public/programacao/'.$s.'/programas')->withStatus(302); }
    public function locutoresAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $lc=$this->service->getLocutores($s); $res->getBody()->write($this->renderPage('Locutores',$this->renderLocutoresList($s,$lc),$s,'locutores')); return $res->withHeader('Content-Type','text/html'); }
    public function locutorFormAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $id=$p['id']??null; $lc=$id?$this->service->getLocutor((int)$id):null; $res->getBody()->write($this->renderPage($id?'Editar Locutor':'Novo Locutor',$this->renderLocutorForm($s,$lc),$s,'locutores')); return $res->withHeader('Content-Type','text/html'); }
    public function locutorSaveAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $id=$p['id']??null; $post=$r->getParsedBody(); $data=['station_id'=>$s,'nome'=>$post['nome']??'','bio'=>$post['bio']??'','foto'=>$post['foto']??null,'email'=>$post['email']??null,'instagram'=>$post['instagram']??null,'twitter'=>$post['twitter']??null,'facebook'=>$post['facebook']??null,'ativo'=>isset($post['ativo'])?1:0]; if($id){$data['id']=(int)$id;} $this->service->saveLocutor($data); return $res->withHeader('Location','/public/programacao/'.$s.'/locutores')->withStatus(302); }
    public function locutorDeleteAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $this->service->deleteLocutor((int)$p['id']); return $res->withHeader('Location','/public/programacao/'.$s.'/locutores')->withStatus(302); }
    public function carrosselAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $m=$this->service->getMensagensCarrosselTodas($s); $res->getBody()->write($this->renderPage('Carrossel',$this->renderCarrosselList($s,$m),$s,'carrossel')); return $res->withHeader('Content-Type','text/html'); }
    public function carrosselFormAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $id=$p['id']??null; $m=$id?$this->service->getMensagemCarrossel((int)$id):null; $res->getBody()->write($this->renderPage($id?'Editar Mensagem':'Nova Mensagem',$this->renderCarrosselForm($s,$m),$s,'carrossel')); return $res->withHeader('Content-Type','text/html'); }
    public function carrosselSaveAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $id=$p['id']??null; $post=$r->getParsedBody(); $data=['station_id'=>$s,'tipo'=>$post['tipo']??'institucional','linha1'=>$post['linha1']??'','linha2'=>$post['linha2']??'','dias_semana'=>json_encode($post['dias_semana']??[]),'hora_inicio'=>$post['hora_inicio']??'00:00','hora_fim'=>$post['hora_fim']??'23:59','prioridade'=>(int)($post['prioridade']??1),'data_inicio'=>!empty($post['data_inicio'])?$post['data_inicio']:null,'data_fim'=>!empty($post['data_fim'])?$post['data_fim']:null,'ativo'=>isset($post['ativo'])?1:0]; if($id){$data['id']=(int)$id;} $this->service->saveMensagemCarrossel($data); return $res->withHeader('Location','/public/programacao/'.$s.'/carrossel')->withStatus(302); }
    public function carrosselDeleteAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $this->service->deleteMensagemCarrossel((int)$p['id']); return $res->withHeader('Location','/public/programacao/'.$s.'/carrossel')->withStatus(302); }
    public function estatisticasAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $st=$this->service->getEstatisticasCarrossel($s); $res->getBody()->write($this->renderPage('Estatísticas',$this->renderEstatisticas($s,$st),$s,'estatisticas')); return $res->withHeader('Content-Type','text/html'); }
    public function carrosselImportAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $files=$r->getUploadedFiles(); if(isset($files['import_file'])&&$files['import_file']->getError()===UPLOAD_ERR_OK){$c=(string)$files['import_file']->getStream();$ms=json_decode($c,true);if(is_array($ms)){foreach($ms as $m){$d=['station_id'=>$s,'tipo'=>$m['tipo']??'institucional','linha1'=>$m['linha1']??'','linha2'=>$m['linha2']??'','dias_semana'=>is_array($m['dias_semana'])?json_encode($m['dias_semana']):($m['dias_semana']??'[]'),'hora_inicio'=>$m['hora_inicio']??'00:00:00','hora_fim'=>$m['hora_fim']??'23:59:59','prioridade'=>(int)($m['prioridade']??1),'data_inicio'=>$m['data_inicio']?:null,'data_fim'=>$m['data_fim']?:null,'ativo'=>(int)($m['ativo']??1)];$this->service->saveMensagemCarrossel($d);}}} return $res->withHeader('Location','/public/programacao/'.$s.'/carrossel')->withStatus(302); }
    public function carrosselExportAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $ms=$this->service->getMensagensCarrosselTodas($s); $ex=[]; foreach($ms as $m){$ex[]=['tipo'=>$m['tipo'],'linha1'=>$m['linha1'],'linha2'=>$m['linha2'],'dias_semana'=>$m['dias_semana'],'hora_inicio'=>$m['hora_inicio'],'hora_fim'=>$m['hora_fim'],'prioridade'=>$m['prioridade'],'data_inicio'=>$m['data_inicio'],'data_fim'=>$m['data_fim'],'ativo'=>$m['ativo']];} $json=json_encode($ex,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE); return $res->withHeader('Content-Type','application/json')->withHeader('Content-Disposition','attachment; filename="carrossel_backup_'.date('Y-m-d').'.json"')->write($json); }
    public function carrosselDuplicateAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $orig=$this->service->getMensagemCarrossel((int)$p['id']); if($orig){$nova=$orig;unset($nova['id']);$nova['linha1']=$orig['linha1'].' (cópia)';$nova['created_at']=$nova['updated_at']=date('Y-m-d H:i:s');$this->service->saveMensagemCarrossel($nova);} return $res->withHeader('Location','/public/programacao/'.$s.'/carrossel')->withStatus(302); }
    public function carrosselToggleAction(ServerRequest $r, Response $res, array $p): ResponseInterface { $s=(int)$p['station_id']; $this->service->toggleMensagemCarrossel((int)$p['id']); return $res->withHeader('Location','/public/programacao/'.$s.'/carrossel')->withStatus(302); }

    private function getStyles(): string { return '<style>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap");
:root{--bg-base:#05050f;--bg-surface:#0d0d1a;--bg-elevated:#13131f;--bg-overlay:#1a1a2a;--bg-hover:#202035;--border:rgba(255,255,255,0.06);--border-md:rgba(255,255,255,0.10);--border-lg:rgba(255,255,255,0.15);--accent:#e11d48;--accent-soft:rgba(225,29,72,0.12);--accent-glow:rgba(225,29,72,0.25);--accent-hover:#be123c;--teal:#0d9488;--teal-soft:rgba(13,148,136,0.12);--blue:#2563eb;--blue-soft:rgba(37,99,235,0.12);--amber:#d97706;--amber-soft:rgba(217,119,6,0.12);--violet:#7c3aed;--violet-soft:rgba(124,58,237,0.12);--emerald:#059669;--emerald-soft:rgba(5,150,105,0.12);--text-primary:#f1f1f5;--text-secondary:#8b8ba7;--text-muted:#52526b;--text-disabled:#3a3a52;--radius-sm:6px;--radius-md:10px;--radius-lg:14px;--radius-xl:20px;--radius-full:9999px;--shadow-sm:0 1px 3px rgba(0,0,0,0.4);--shadow-md:0 4px 16px rgba(0,0,0,0.5);--shadow-lg:0 12px 40px rgba(0,0,0,0.6);--sidebar-w:240px;--header-h:60px;--transition:0.18s cubic-bezier(0.4,0,0.2,1)}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:14px;-webkit-font-smoothing:antialiased}
body{font-family:"Inter",system-ui,sans-serif;background:var(--bg-base);color:var(--text-primary);min-height:100vh;overflow-x:hidden}
::-webkit-scrollbar{width:5px;height:5px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:var(--bg-overlay);border-radius:3px}
.sidebar{position:fixed;inset:0 auto 0 0;width:var(--sidebar-w);background:var(--bg-surface);border-right:1px solid var(--border);display:flex;flex-direction:column;z-index:200}
.sidebar-brand{display:flex;align-items:center;gap:10px;padding:0 20px;height:var(--header-h);border-bottom:1px solid var(--border);flex-shrink:0}
.sidebar-brand-icon{width:32px;height:32px;background:var(--accent);border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:15px;color:#fff;box-shadow:0 0 20px var(--accent-glow);flex-shrink:0}
.sidebar-brand-name{font-size:13px;font-weight:700;color:var(--text-primary);letter-spacing:.3px;line-height:1.2}
.sidebar-brand-sub{font-size:11px;color:var(--text-muted);font-weight:400;letter-spacing:.5px}
.sidebar-nav{flex:1;overflow-y:auto;padding:16px 12px;display:flex;flex-direction:column;gap:2px}
.nav-section-label{font-size:10px;font-weight:700;letter-spacing:1.2px;text-transform:uppercase;color:var(--text-disabled);padding:12px 8px 6px}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:var(--radius-md);text-decoration:none;color:var(--text-secondary);font-size:13px;font-weight:500;transition:all var(--transition);position:relative;white-space:nowrap}
.nav-item i{font-size:16px;flex-shrink:0;width:18px;text-align:center}
.nav-item:hover{background:var(--bg-overlay);color:var(--text-primary)}
.nav-item.active{background:var(--accent-soft);color:var(--accent);font-weight:600}
.nav-item.active::before{content:"";position:absolute;left:0;top:20%;bottom:20%;width:3px;border-radius:0 2px 2px 0;background:var(--accent)}
.sidebar-footer{padding:12px;border-top:1px solid var(--border);flex-shrink:0}
.page-wrapper{margin-left:var(--sidebar-w);min-height:100vh;display:flex;flex-direction:column}
.page-topbar{height:var(--header-h);background:var(--bg-surface);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:100;flex-shrink:0}
.topbar-breadcrumb{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-secondary)}
.topbar-breadcrumb .sep{color:var(--text-disabled)}
.topbar-breadcrumb .current{color:var(--text-primary);font-weight:600}
.page-content{flex:1;padding:28px;max-width:1400px;width:100%}
.page-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:28px}
.page-title{font-size:22px;font-weight:700;color:var(--text-primary);letter-spacing:-.3px;line-height:1.3}
.page-subtitle{font-size:13px;color:var(--text-secondary);margin-top:4px}
.page-header-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
.stat-card{background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-lg);padding:20px;display:flex;flex-direction:column;gap:12px;transition:border-color var(--transition);position:relative;overflow:hidden}
.stat-card::after{content:"";position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.02) 0%,transparent 60%);pointer-events:none}
.stat-card:hover{border-color:var(--border-md)}
.stat-card-top{display:flex;align-items:center;justify-content:space-between}
.stat-icon{width:38px;height:38px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:17px}
.stat-icon.red{background:var(--accent-soft);color:var(--accent)}
.stat-icon.teal{background:var(--teal-soft);color:var(--teal)}
.stat-icon.blue{background:var(--blue-soft);color:var(--blue)}
.stat-icon.violet{background:var(--violet-soft);color:var(--violet)}
.stat-value{font-size:30px;font-weight:800;color:var(--text-primary);letter-spacing:-1px;line-height:1}
.stat-label{font-size:12px;font-weight:500;color:var(--text-secondary);margin-top:-6px}
.card{background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.card-header{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border)}
.card-title{font-size:14px;font-weight:600;color:var(--text-primary);display:flex;align-items:center;gap:8px}
.card-title i{color:var(--accent)}
.card-body{padding:22px}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
thead th{padding:12px 18px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);background:var(--bg-surface);border-bottom:1px solid var(--border);white-space:nowrap}
tbody td{padding:14px 18px;font-size:13px;color:var(--text-primary);border-bottom:1px solid var(--border);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr{transition:background var(--transition)}
tbody tr:hover td{background:var(--bg-overlay)}
.badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:var(--radius-full);font-size:11px;font-weight:600;white-space:nowrap}
.badge-active{background:var(--emerald-soft);color:var(--emerald)}
.badge-inactive{background:rgba(255,255,255,.05);color:var(--text-muted)}
.badge-day{background:var(--blue-soft);color:var(--blue);font-size:10px;padding:2px 7px}
.badge-red{background:var(--accent-soft);color:var(--accent)}
.badge-amber{background:var(--amber-soft);color:var(--amber)}
.badge-violet{background:var(--violet-soft);color:var(--violet)}
.badge-teal{background:var(--teal-soft);color:var(--teal)}
.badge-blue{background:var(--blue-soft);color:var(--blue)}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;border-radius:var(--radius-md);font-size:13px;font-weight:600;font-family:inherit;text-decoration:none;border:none;cursor:pointer;transition:all var(--transition);white-space:nowrap;line-height:1}
.btn-primary{background:var(--accent);color:#fff;box-shadow:0 2px 12px var(--accent-glow)}
.btn-primary:hover{background:var(--accent-hover);transform:translateY(-1px);box-shadow:0 4px 18px var(--accent-glow)}
.btn-ghost{background:transparent;color:var(--text-secondary);border:1px solid var(--border-md)}
.btn-ghost:hover{background:var(--bg-overlay);color:var(--text-primary);border-color:var(--border-lg)}
.btn-danger{background:var(--accent-soft);color:var(--accent);border:1px solid rgba(225,29,72,.2)}
.btn-danger:hover{background:rgba(225,29,72,.2)}
.btn-sm{padding:6px 12px;font-size:12px}
.btn-icon{width:34px;height:34px;padding:0;justify-content:center;border-radius:var(--radius-md)}
.action-group{display:flex;align-items:center;gap:6px}
.form-section{margin-bottom:28px}
.form-section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border)}
.form-grid{display:grid;gap:16px}
.form-grid-2{grid-template-columns:1fr 1fr}
.form-grid-3{grid-template-columns:1fr 1fr 1fr}
.form-grid-4{grid-template-columns:1fr 1fr 1fr 1fr}
.form-group{display:flex;flex-direction:column;gap:6px}
.form-label{font-size:12px;font-weight:600;color:var(--text-secondary);letter-spacing:.3px}
.form-label .req{color:var(--accent);margin-left:2px}
.form-control{width:100%;padding:10px 14px;background:var(--bg-overlay);border:1px solid var(--border-md);border-radius:var(--radius-md);color:var(--text-primary);font-size:13px;font-family:inherit;transition:all var(--transition);outline:none}
.form-control:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-soft);background:var(--bg-hover)}
.form-control::placeholder{color:var(--text-disabled)}
select.form-control{cursor:pointer}
select.form-control option{background:var(--bg-elevated)}
textarea.form-control{min-height:100px;resize:vertical;line-height:1.6}
.form-hint{font-size:11px;color:var(--text-muted);margin-top:2px}
.days-selector{display:flex;gap:8px;flex-wrap:wrap}
.day-chip{position:relative}
.day-chip input{position:absolute;opacity:0;pointer-events:none}
.day-chip label{display:flex;align-items:center;justify-content:center;min-width:54px;padding:8px 12px;background:var(--bg-overlay);border:1px solid var(--border-md);border-radius:var(--radius-md);color:var(--text-secondary);font-size:12px;font-weight:600;cursor:pointer;transition:all var(--transition);user-select:none}
.day-chip input:checked+label{background:var(--accent-soft);border-color:var(--accent);color:var(--accent)}
.day-chip label:hover{border-color:var(--border-lg);color:var(--text-primary)}
.toggle-group{display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--bg-overlay);border:1px solid var(--border);border-radius:var(--radius-md)}
.toggle-label{flex:1;font-size:13px;font-weight:500;color:var(--text-primary)}
.toggle-label small{display:block;font-size:11px;color:var(--text-muted);font-weight:400;margin-top:2px}
.toggle{position:relative;width:40px;height:22px;flex-shrink:0}
.toggle input{opacity:0;width:0;height:0;position:absolute}
.toggle-track{position:absolute;inset:0;background:var(--bg-hover);border:1px solid var(--border-md);border-radius:11px;cursor:pointer;transition:all var(--transition)}
.toggle-track::after{content:"";position:absolute;top:2px;left:2px;width:16px;height:16px;background:var(--text-muted);border-radius:50%;transition:all var(--transition)}
.toggle input:checked+.toggle-track{background:var(--accent);border-color:var(--accent)}
.toggle input:checked+.toggle-track::after{transform:translateX(18px);background:#fff}
.locutor-list{display:flex;flex-direction:column;gap:8px}
.locutor-row{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--bg-overlay);border:1px solid var(--border);border-radius:var(--radius-md);transition:all var(--transition)}
.locutor-row:hover{border-color:var(--border-md);background:var(--bg-hover)}
.locutor-row-left{display:flex;align-items:center;gap:10px}
.check-custom{position:relative;width:18px;height:18px;flex-shrink:0}
.check-custom input{opacity:0;position:absolute;width:100%;height:100%;cursor:pointer;margin:0}
.check-box{width:18px;height:18px;border:1.5px solid var(--border-md);border-radius:5px;background:var(--bg-overlay);display:flex;align-items:center;justify-content:center;transition:all var(--transition);pointer-events:none}
.check-custom input:checked~.check-box{background:var(--accent);border-color:var(--accent)}
.check-custom input:checked~.check-box::after{content:"✓";color:#fff;font-size:11px;font-weight:700}
.radio-custom{position:relative;display:flex;align-items:center;gap:6px;cursor:pointer}
.radio-custom input{opacity:0;position:absolute}
.radio-dot{width:16px;height:16px;border:1.5px solid var(--border-md);border-radius:50%;background:var(--bg-overlay);display:flex;align-items:center;justify-content:center;transition:all var(--transition)}
.radio-custom input:checked~.radio-dot{border-color:var(--teal);background:var(--teal-soft)}
.radio-custom input:checked~.radio-dot::after{content:"";width:6px;height:6px;background:var(--teal);border-radius:50%}
.radio-label{font-size:11px;color:var(--text-muted);font-weight:500}
.live-card{background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;position:relative;overflow:hidden}
.live-card::before{content:"";position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),#f43f5e,transparent)}
.live-badge{display:inline-flex;align-items:center;gap:6px;background:var(--accent-soft);border:1px solid rgba(225,29,72,.2);padding:4px 12px;border-radius:var(--radius-full);font-size:11px;font-weight:700;color:var(--accent);letter-spacing:1px;text-transform:uppercase;margin-bottom:16px}
.live-dot{width:7px;height:7px;background:var(--accent);border-radius:50%;animation:livepulse 1.5s infinite}
@keyframes livepulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.4)}}
.live-title{font-size:20px;font-weight:700;color:var(--text-primary);letter-spacing:-.3px;margin-bottom:6px}
.live-meta{font-size:13px;color:var(--text-secondary)}
.quick-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.quick-card{display:flex;align-items:center;gap:14px;padding:16px;background:var(--bg-overlay);border:1px solid var(--border);border-radius:var(--radius-md);text-decoration:none;color:var(--text-primary);transition:all var(--transition)}
.quick-card:hover{background:var(--bg-hover);border-color:var(--border-md);transform:translateY(-2px);box-shadow:var(--shadow-md)}
.quick-icon{width:42px;height:42px;flex-shrink:0;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:center;font-size:18px}
.quick-text h4{font-size:13px;font-weight:600;margin-bottom:2px}
.quick-text p{font-size:11px;color:var(--text-muted)}
.form-actions{display:flex;align-items:center;gap:10px;padding-top:24px;border-top:1px solid var(--border);margin-top:28px}
.empty-state{display:flex;flex-direction:column;align-items:center;padding:56px 24px;text-align:center}
.empty-icon{width:60px;height:60px;background:var(--bg-overlay);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;font-size:26px;color:var(--text-disabled);margin-bottom:16px}
.empty-title{font-size:15px;font-weight:600;color:var(--text-primary);margin-bottom:6px}
.empty-desc{font-size:13px;color:var(--text-secondary);margin-bottom:20px;max-width:320px;line-height:1.6}
.avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;border:1.5px solid var(--border-md);flex-shrink:0}
.avatar-fallback{width:36px;height:36px;border-radius:50%;background:var(--bg-overlay);border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:15px;color:var(--text-muted);flex-shrink:0}
.cell-user{display:flex;align-items:center;gap:12px}
.cell-user-name{font-size:13px;font-weight:600;color:var(--text-primary)}
.cell-user-sub{font-size:11px;color:var(--text-muted);margin-top:2px}
.progress-row{margin-bottom:14px}
.progress-top{display:flex;justify-content:space-between;font-size:12px;margin-bottom:6px;color:var(--text-primary)}
.progress-top span:last-child{color:var(--text-secondary)}
.progress-track{height:6px;background:var(--bg-overlay);border-radius:3px;overflow:hidden}
.progress-fill{height:100%;border-radius:3px;transition:width .6s ease}
.preview-card{background:linear-gradient(135deg,#0d0d1a,#13131f);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px}
.preview-inner{text-align:center;padding:16px}
.preview-l1{font-size:15px;font-weight:700;color:var(--text-primary);letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px}
.preview-l2{font-size:13px;color:var(--text-secondary)}
.preview-dots{display:flex;justify-content:center;gap:6px;margin-top:16px}
.preview-dot{width:7px;height:7px;border-radius:50%;background:var(--bg-hover)}
.preview-dot.active{background:var(--accent)}
.stats-detail-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px}
.stat-detail-card{background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;text-align:center}
.stat-detail-value{font-size:28px;font-weight:800;color:var(--text-primary);letter-spacing:-1px}
.stat-detail-label{font-size:12px;color:var(--text-secondary);margin-top:5px}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.tipo-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;display:inline-block}
@media(max-width:1200px){.stats-row{grid-template-columns:repeat(2,1fr)}.form-grid-4{grid-template-columns:1fr 1fr}}
@media(max-width:900px){.two-col{grid-template-columns:1fr}.form-grid-2,.form-grid-3{grid-template-columns:1fr}}
@media(max-width:600px){.page-content{padding:16px}.stats-row{grid-template-columns:1fr}.stats-detail-grid{grid-template-columns:1fr}.quick-grid{grid-template-columns:1fr}}
</style>'; }

    private function renderPage(string $title, string $content, int $stationId, string $active = ''): string {
        $nav = [
            ['url'=>"/public/programacao/{$stationId}",'icon'=>'grid','label'=>'Dashboard','key'=>'dashboard'],
            ['url'=>"/public/programacao/{$stationId}/programas",'icon'=>'calendar-week','label'=>'Programas','key'=>'programas'],
            ['url'=>"/public/programacao/{$stationId}/locutores",'icon'=>'person-badge','label'=>'Locutores','key'=>'locutores'],
            ['url'=>"/public/programacao/{$stationId}/carrossel",'icon'=>'collection-play','label'=>'Carrossel','key'=>'carrossel'],
            ['url'=>"/public/programacao/{$stationId}/player",'icon'=>'play-circle','label'=>'Player Demo','key'=>'player'],
        ];
        $navHtml='';
        foreach($nav as $item){
            $cls=$item['key']===$active?' active':'';
            $navHtml.='<a href="'.$item['url'].'" class="nav-item'.$cls.'"><i class="bi bi-'.$item['icon'].'"></i><span>'.$item['label'].'</span></a>';
        }
        return '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>'.htmlspecialchars($title).' &middot; Radio New Band</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">'.$this->getStyles().'</head><body><aside class="sidebar"><div class="sidebar-brand"><div class="sidebar-brand-icon"><i class="bi bi-broadcast-pin"></i></div><div><div class="sidebar-brand-name">Radio New Band</div><div class="sidebar-brand-sub">Gestão &middot; Programação</div></div></div><nav class="sidebar-nav"><div class="nav-section-label">Principal</div>'.$navHtml.'<div class="nav-section-label" style="margin-top:8px">Sistema</div><a href="/" class="nav-item"><i class="bi bi-box-arrow-left"></i><span>AzuraCast</span></a></nav><div class="sidebar-footer"><div style="display:flex;align-items:center;gap:10px;padding:10px 8px"><div style="width:32px;height:32px;border-radius:50%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:14px;flex-shrink:0"><i class="bi bi-person"></i></div><div><div style="font-size:12px;font-weight:600;color:var(--text-primary)">Admin</div><div style="font-size:11px;color:var(--text-muted)">Administrador</div></div></div></div></aside><div class="page-wrapper"><header class="page-topbar"><div class="topbar-breadcrumb"><i class="bi bi-broadcast-pin" style="color:var(--accent)"></i><span>Radio New Band</span><span class="sep">/</span><span class="current">'.htmlspecialchars($title).'</span></div><div style="font-size:12px;color:var(--text-muted);font-family:\'JetBrains Mono\',monospace" id="clock"></div></header><main class="page-content">'.$content.'</main></div><script>(function(){function t(){var e=document.getElementById("clock");if(e)e.textContent=new Date().toLocaleTimeString("pt-PT")}t();setInterval(t,1000)})();</script>
<script src="/static/modal-ver.js"></script>
<script src="/static/auto-refresh-programas.js"></script></body></html>';
    }

    private function renderPlayerDemo(int $stationId): string {
        return '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Player Demo</title><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">'.$this->getStyles().'<style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;margin-left:0}.player-card{background:var(--bg-elevated);border:1px solid var(--border);border-radius:var(--radius-xl);padding:36px;max-width:360px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.7)}.art{width:100%;aspect-ratio:1;border-radius:var(--radius-lg);object-fit:cover;margin-bottom:24px}.info{text-align:center;min-height:72px;margin-bottom:20px}.info-label{font-size:11px;color:var(--accent);text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;font-weight:600}.info-text{font-size:18px;color:var(--text-primary);font-weight:700}.dots{display:flex;justify-content:center;gap:7px;margin-bottom:24px}.dot{width:7px;height:7px;border-radius:50%;background:var(--bg-hover);transition:.3s}.dot.active{background:var(--accent)}.controls{display:flex;justify-content:center}.play-btn{width:62px;height:62px;border-radius:50%;background:var(--accent);border:none;color:#fff;font-size:26px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 24px var(--accent-glow);transition:all var(--transition)}.play-btn:hover{transform:scale(1.08)}</style></head><body><div class="player-card"><img id="art" class="art" src="https://rnb.radionewband.ao/static/img/generic_song.jpg"><div class="info"><div class="info-label" id="label">A carregar...</div><div class="info-text" id="text">Radio New Band</div></div><div class="dots"><div class="dot active"></div><div class="dot"></div><div class="dot"></div></div><div class="controls"><button class="play-btn" onclick="togglePlay()"><i class="bi bi-play-fill" id="icon"></i></button></div><audio id="audio"><source src="https://rnb.radionewband.ao/listen/rnb/radio.mp3" type="audio/mpeg"></audio></div><script>let estados=[],idx=0,playing=false;async function load(){try{const r=await fetch("/api/station/'.$stationId.'/programacao/widget");const d=await r.json();if(d.success){estados=d.data.estados;if(d.data.musica?.art)document.getElementById("art").src=d.data.musica.art;show()}}catch(e){}}function show(){if(!estados.length)return;const e=estados[idx%estados.length];document.getElementById("label").textContent=e.linha1;document.getElementById("text").textContent=e.linha2;document.querySelectorAll(".dot").forEach((d,i)=>d.classList.toggle("active",i===idx%3))}function next(){idx++;show()}function togglePlay(){const a=document.getElementById("audio"),i=document.getElementById("icon");if(playing){a.pause();i.className="bi bi-play-fill"}else{a.play();i.className="bi bi-pause-fill"}playing=!playing}load();setInterval(next,8000);setInterval(load,30000)</script>
<div id="modal-ver" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);align-items:center;justify-content:center">
  <div id="modal-box" style="background:var(--bg-elevated);border:1px solid var(--border-md);border-radius:16px;max-width:560px;width:92%;max-height:85vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,0.6)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border)">
      <div style="display:flex;align-items:center;gap:12px">
        <div id="modal-icon" style="width:38px;height:38px;border-radius:10px;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:17px;flex-shrink:0"></div>
        <div>
          <div id="modal-title" style="font-size:15px;font-weight:700;color:var(--text-primary)"></div>
          <div id="modal-sub" style="font-size:12px;color:var(--text-muted);margin-top:2px"></div>
        </div>
      </div>
      <button onclick="fecharModal()" style="background:none;border:none;color:var(--text-muted);font-size:22px;cursor:pointer;line-height:1;padding:4px 8px;border-radius:6px">&times;</button>
    </div>
    <div id="modal-body" style="padding:24px"></div>
    <div id="modal-footer" style="padding:16px 24px;border-top:1px solid var(--border);display:flex;gap:8px;justify-content:flex-end"></div>
  </div>
</div>
<script src="/static/auto-refresh-programas.js"></script></body></html>';
    }

    private function renderDashboard(int $stationId, array $stats): string {
        $noAr=$stats['programa_no_ar'];
        $live=$noAr
            ?'<div class="live-badge"><span class="live-dot"></span>No ar agora</div><div class="live-title">'.htmlspecialchars($noAr['nome']).'</div><div class="live-meta"><i class="bi bi-clock"></i> '.substr($noAr['hora_inicio'],0,5).' &mdash; '.substr($noAr['hora_fim'],0,5).'</div>'
            :'<div class="live-badge"><span class="live-dot"></span>No ar agora</div><div class="live-title">Programação Musical</div><div class="live-meta">Sem programa específico agendado</div>';
        return '
<div class="page-header"><div class="page-header-left"><h1 class="page-title">Dashboard</h1><p class="page-subtitle">Visão geral da sua rádio</p></div></div>
<div class="stats-row">
<div class="stat-card"><div class="stat-card-top"><div class="stat-icon red"><i class="bi bi-calendar-week"></i></div></div><div class="stat-value">'.$stats['total_programas'].'</div><div class="stat-label">Total de Programas</div></div>
<div class="stat-card"><div class="stat-card-top"><div class="stat-icon teal"><i class="bi bi-check-circle"></i></div></div><div class="stat-value">'.$stats['programas_ativos'].'</div><div class="stat-label">Programas Activos</div></div>
<div class="stat-card"><div class="stat-card-top"><div class="stat-icon blue"><i class="bi bi-person-badge"></i></div></div><div class="stat-value">'.$stats['total_locutores'].'</div><div class="stat-label">Total de Locutores</div></div>
<div class="stat-card"><div class="stat-card-top"><div class="stat-icon violet"><i class="bi bi-mic"></i></div></div><div class="stat-value">'.$stats['locutores_ativos'].'</div><div class="stat-label">Locutores Activos</div></div>
</div>
<div class="two-col">
<div class="live-card">'.$live.'</div>
<div class="card"><div class="card-header"><div class="card-title"><i class="bi bi-lightning-charge"></i> Acções Rápidas</div></div><div class="card-body"><div class="quick-grid">
<a href="/public/programacao/'.$stationId.'/programas/novo" class="quick-card"><div class="quick-icon" style="background:var(--accent-soft);color:var(--accent)"><i class="bi bi-plus-lg"></i></div><div class="quick-text"><h4>Novo Programa</h4><p>Criar programa</p></div></a>
<a href="/public/programacao/'.$stationId.'/locutores/novo" class="quick-card"><div class="quick-icon" style="background:var(--blue-soft);color:var(--blue)"><i class="bi bi-person-plus"></i></div><div class="quick-text"><h4>Novo Locutor</h4><p>Adicionar locutor</p></div></a>
<a href="/public/programacao/'.$stationId.'/carrossel/novo" class="quick-card"><div class="quick-icon" style="background:var(--violet-soft);color:var(--violet)"><i class="bi bi-chat-square-text"></i></div><div class="quick-text"><h4>Nova Mensagem</h4><p>Carrossel</p></div></a>
<a href="/public/programacao/'.$stationId.'/player" class="quick-card"><div class="quick-icon" style="background:var(--teal-soft);color:var(--teal)"><i class="bi bi-play-circle"></i></div><div class="quick-text"><h4>Player Demo</h4><p>Visualizar player</p></div></a>
</div></div></div>
</div>';
    }

    private function renderProgramasList(int $stationId, array $programas): string {
        $da=['segunda'=>'Seg','terca'=>'Ter','quarta'=>'Qua','quinta'=>'Qui','sexta'=>'Sex','sabado'=>'Sáb','domingo'=>'Dom'];
        if(empty($programas)){
            $body='<tr><td colspan="5"><div class="empty-state"><div class="empty-icon"><i class="bi bi-calendar-x"></i></div><div class="empty-title">Nenhum programa criado</div><div class="empty-desc">Crie o primeiro programa da sua rádio para começar a gerir a grelha semanal.</div><a href="/public/programacao/'.$stationId.'/programas/novo" class="btn btn-primary"><i class="bi bi-plus"></i> Criar Programa</a></div></td></tr>';
        } else {
            $body='';
            foreach($programas as $p){
                $dias=json_decode($p['dias_semana']??'[]',true)?:[];
                $dh=''; foreach($dias as $d){$dh.='<span class="badge badge-day">'.($da[$d]??$d).'</span> ';}
                $st=$p['ativo']?'<span class="badge badge-active"><i class="bi bi-circle-fill" style="font-size:6px"></i>Activo</span>':'<span class="badge badge-inactive">Inactivo</span>';
                $body.='<tr><td><span style="font-weight:600">'.htmlspecialchars($p['nome']).'</span></td><td><span style="font-family:\'JetBrains Mono\',monospace;font-size:12px;color:var(--text-secondary)">'.substr($p['hora_inicio'],0,5).' &ndash; '.substr($p['hora_fim'],0,5).'</span></td><td><div style="display:flex;gap:4px;flex-wrap:wrap">'.$dh.'</div></td><td>'.$st.'</td><td><div class="action-group"><button onclick="verPrograma('.$p['id'].')" class="btn btn-ghost btn-icon btn-sm" title="Ver"><i class="bi bi-eye"></i></button><a href="/public/programacao/'.$stationId.'/programas/'.$p['id'].'/editar" class="btn btn-ghost btn-icon btn-sm"><i class="bi bi-pencil"></i></a><form method="POST" action="/public/programacao/'.$stationId.'/programas/'.$p['id'].'/excluir" style="display:inline" onsubmit="return confirm(\'Eliminar?\')"><button type="submit" class="btn btn-danger btn-icon btn-sm"><i class="bi bi-trash"></i></button></form></div></td></tr>';
            }
        }
        return '<div class="page-header"><div class="page-header-left"><h1 class="page-title">Programas</h1><p class="page-subtitle">'.count($programas).' programa(s) registado(s)</p></div><div class="page-header-actions"><a href="/public/programacao/'.$stationId.'/programas/novo" class="btn btn-primary"><i class="bi bi-plus"></i> Novo Programa</a></div></div><div class="card"><div class="table-wrap"><table><thead><tr><th>Nome</th><th>Horário</th><th>Dias</th><th>Estado</th><th>Acções</th></tr></thead><tbody>'.$body.'</tbody></table></div></div>';
    }

    private function renderProgramaForm(int $stationId, ?array $programa, array $locutores): string {
        $nome=htmlspecialchars($programa['nome']??'');
        $desc=htmlspecialchars($programa['descricao']??'');
        $banner=htmlspecialchars($programa['banner']??'');
        $hi=substr($programa['hora_inicio']??'00:00',0,5);
        $hf=substr($programa['hora_fim']??'00:00',0,5);
        $dias=json_decode($programa['dias_semana']??'[]',true)?:[];
        $ativo=($programa['ativo']??1)?'checked':'';
        $action=$programa?'/public/programacao/'.$stationId.'/programas/'.$programa['id'].'/editar':'/public/programacao/'.$stationId.'/programas/novo';
        $dc=['segunda'=>'Segunda','terca'=>'Terça','quarta'=>'Quarta','quinta'=>'Quinta','sexta'=>'Sexta','sabado'=>'Sábado','domingo'=>'Domingo'];
        $dh=''; foreach($dc as $k=>$l){$ck=in_array($k,$dias)?'checked':'';$dh.='<div class="day-chip"><input type="checkbox" name="dias_semana[]" value="'.$k.'" id="d_'.$k.'" '.$ck.'><label for="d_'.$k.'">'.$l.'</label></div>';}
        $lv=[];$lp=0;
        if($programa&&isset($programa['id'])){foreach($this->service->getLocutoresDoPrograma((int)$programa['id']) as $v){$lv[]=(int)$v['id'];if($v['is_principal'])$lp=(int)$v['id'];}}
        $lh='';
        if(empty($locutores)){$lh='<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px">Nenhum locutor. <a href="/public/programacao/'.$stationId.'/locutores/novo" style="color:var(--accent)">Criar</a></div>';}
        else{foreach($locutores as $loc){$ck=in_array((int)$loc['id'],$lv)?'checked':'';$pr=((int)$loc['id']===$lp)?'checked':'';$foto=$loc['foto']?'<img src="'.htmlspecialchars($loc['foto']).'" class="avatar">':'<div class="avatar-fallback"><i class="bi bi-person"></i></div>';$lh.='<div class="locutor-row"><div class="locutor-row-left"><label class="check-custom"><input type="checkbox" name="locutores[]" value="'.$loc['id'].'" '.$ck.'><div class="check-box"></div></label>'.$foto.'<span style="font-size:13px;font-weight:500">'.htmlspecialchars($loc['nome']).'</span></div><label class="radio-custom"><input type="radio" name="locutor_principal" value="'.$loc['id'].'" '.$pr.'><div class="radio-dot"></div><span class="radio-label">Principal</span></label></div>';}}
        return '<div class="page-header"><div class="page-header-left"><h1 class="page-title">'.($programa?'Editar Programa':'Novo Programa').'</h1></div></div><div class="card"><div class="card-header"><div class="card-title"><i class="bi bi-calendar-week"></i> Dados do Programa</div></div><div class="card-body"><form method="POST" action="'.$action.'"><div class="form-section"><div class="form-section-title">Informação Geral</div><div class="form-grid form-grid-2"><div class="form-group"><label class="form-label">Nome <span class="req">*</span></label><input type="text" name="nome" class="form-control" value="'.$nome.'" placeholder="Ex: Manhã Total" required></div><div class="form-group"><label class="form-label">URL do Banner</label><input type="url" name="banner" class="form-control" value="'.$banner.'" placeholder="https://..."></div></div><div class="form-group"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" placeholder="Descreva o programa...">'.$desc.'</textarea></div></div><div class="form-section"><div class="form-section-title">Horário e Dias</div><div class="form-grid form-grid-2" style="max-width:400px;margin-bottom:16px"><div class="form-group"><label class="form-label">Hora Início</label><input type="time" name="hora_inicio" class="form-control" value="'.$hi.'"></div><div class="form-group"><label class="form-label">Hora Fim</label><input type="time" name="hora_fim" class="form-control" value="'.$hf.'"></div></div><div class="form-group"><label class="form-label">Dias da Semana</label><div class="days-selector">'.$dh.'</div></div></div><div class="form-section"><div class="form-section-title">Locutores</div><div class="locutor-list" style="max-height:280px;overflow-y:auto">'.$lh.'</div></div><div class="form-section" style="margin-bottom:0"><div class="form-section-title">Configurações</div><div class="toggle-group" style="max-width:400px"><div class="toggle-label">Programa Activo<small>Aparece na grelha e no player público</small></div><label class="toggle"><input type="checkbox" name="ativo" '.$ativo.'><div class="toggle-track"></div></label></div></div><div class="form-actions"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button><a href="/public/programacao/'.$stationId.'/programas" class="btn btn-ghost">Cancelar</a></div></form></div></div>';
    }

    private function renderLocutoresList(int $stationId, array $locutores): string {
        if(empty($locutores)){
            $body='<tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="bi bi-people"></i></div><div class="empty-title">Nenhum locutor registado</div><div class="empty-desc">Adicione os locutores da sua rádio.</div><a href="/public/programacao/'.$stationId.'/locutores/novo" class="btn btn-primary"><i class="bi bi-plus"></i> Criar Locutor</a></div></td></tr>';
        } else {
            $body='';
            foreach($locutores as $l){
                $foto=$l['foto']?'<img src="'.htmlspecialchars($l['foto']).'" class="avatar">':'<div class="avatar-fallback"><i class="bi bi-person"></i></div>';
                $st=$l['ativo']?'<span class="badge badge-active"><i class="bi bi-circle-fill" style="font-size:6px"></i>Activo</span>':'<span class="badge badge-inactive">Inactivo</span>';
                $body.='<tr><td><div class="cell-user">'.$foto.'<div><div class="cell-user-name">'.htmlspecialchars($l['nome']).'</div>'.(!empty($l['email'])?'<div class="cell-user-sub">'.htmlspecialchars($l['email']).'</div>':'').'</div></div></td><td>'.($l['total_programas']??0).'</td><td>'.$st.'</td><td><div class="action-group"><button onclick="verLocutor('.$l['id'].')" class="btn btn-ghost btn-icon btn-sm" title="Ver"><i class="bi bi-eye"></i></button><a href="/public/programacao/'.$stationId.'/locutores/'.$l['id'].'/editar" class="btn btn-ghost btn-icon btn-sm"><i class="bi bi-pencil"></i></a><form method="POST" action="/public/programacao/'.$stationId.'/locutores/'.$l['id'].'/excluir" style="display:inline" onsubmit="return confirm(\'Eliminar?\')"><button type="submit" class="btn btn-danger btn-icon btn-sm"><i class="bi bi-trash"></i></button></form></div></td></tr>';
            }
        }
        return '<div class="page-header"><div class="page-header-left"><h1 class="page-title">Locutores</h1><p class="page-subtitle">'.count($locutores).' locutor(es) registado(s)</p></div><div class="page-header-actions"><a href="/public/programacao/'.$stationId.'/locutores/novo" class="btn btn-primary"><i class="bi bi-plus"></i> Novo Locutor</a></div></div><div class="card"><div class="table-wrap"><table><thead><tr><th>Locutor</th><th>Programas</th><th>Estado</th><th>Acções</th></tr></thead><tbody>'.$body.'</tbody></table></div></div>';
    }

    private function renderLocutorForm(int $stationId, ?array $locutor): string {
        $nome=htmlspecialchars($locutor['nome']??'');
        $bio=htmlspecialchars($locutor['bio']??'');
        $foto=htmlspecialchars($locutor['foto']??'');
        $email=htmlspecialchars($locutor['email']??'');
        $ig=htmlspecialchars($locutor['instagram']??'');
        $tw=htmlspecialchars($locutor['twitter']??'');
        $fb=htmlspecialchars($locutor['facebook']??'');
        $ativo=($locutor['ativo']??1)?'checked':'';
        $action=$locutor?'/public/programacao/'.$stationId.'/locutores/'.$locutor['id'].'/editar':'/public/programacao/'.$stationId.'/locutores/novo';
        $pv=$foto?'<img src="'.$foto.'" id="fp" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--border-md)">':'<div id="fp" style="width:80px;height:80px;border-radius:50%;background:var(--bg-overlay);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:32px;color:var(--text-disabled)"><i class=\"bi bi-person\"></i></div>';
        return '<div class="page-header"><div class="page-header-left"><h1 class="page-title">'.($locutor?'Editar Locutor':'Novo Locutor').'</h1></div></div><div class="card"><div class="card-header"><div class="card-title"><i class="bi bi-person-badge"></i> Perfil do Locutor</div></div><div class="card-body"><form method="POST" action="'.$action.'"><div class="form-section"><div class="form-section-title">Foto de Perfil</div><div style="display:flex;align-items:center;gap:20px;margin-bottom:4px">'.$pv.'<div style="flex:1"><div class="form-group"><label class="form-label">URL da Foto</label><input type="url" name="foto" id="fu" class="form-control" value="'.$foto.'" placeholder="https://..."><span class="form-hint">Link directo para a imagem</span></div></div></div></div><div class="form-section"><div class="form-section-title">Informação Pessoal</div><div class="form-grid form-grid-2"><div class="form-group"><label class="form-label">Nome Completo <span class="req">*</span></label><input type="text" name="nome" class="form-control" value="'.$nome.'" required></div><div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="'.$email.'" placeholder="email@exemplo.com"></div></div><div class="form-group"><label class="form-label">Biografia</label><textarea name="bio" class="form-control" placeholder="Sobre o locutor...">'.$bio.'</textarea></div></div><div class="form-section"><div class="form-section-title">Redes Sociais</div><div class="form-grid form-grid-3"><div class="form-group"><label class="form-label"><i class="bi bi-instagram" style="color:#e1306c"></i> Instagram</label><input type="text" name="instagram" class="form-control" value="'.$ig.'" placeholder="@usuario"></div><div class="form-group"><label class="form-label"><i class="bi bi-twitter-x"></i> Twitter</label><input type="text" name="twitter" class="form-control" value="'.$tw.'" placeholder="@usuario"></div><div class="form-group"><label class="form-label"><i class="bi bi-facebook" style="color:#1877f2"></i> Facebook</label><input type="text" name="facebook" class="form-control" value="'.$fb.'" placeholder="usuario"></div></div></div><div class="form-section" style="margin-bottom:0"><div class="form-section-title">Configurações</div><div class="toggle-group" style="max-width:400px"><div class="toggle-label">Locutor Activo<small>Disponível para associação a programas</small></div><label class="toggle"><input type="checkbox" name="ativo" '.$ativo.'><div class="toggle-track"></div></label></div></div><div class="form-actions"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button><a href="/public/programacao/'.$stationId.'/locutores" class="btn btn-ghost">Cancelar</a></div></form></div></div><script>document.getElementById("fu").addEventListener("input",function(){var p=document.getElementById("fp");if(this.value){p.outerHTML="<img id=\"fp\" src=\""+this.value+"\" style=\"width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--border-md)\">";}});</script>';
    }

    private function renderCarrosselList(int $stationId, array $mensagens): string {
        $tl=['saudacao'=>'Saudação','promocao'=>'Promoção','programa'=>'Programa','institucional'=>'Institucional','especial'=>'Especial'];
        $tb=['saudacao'=>'badge-teal','promocao'=>'badge-amber','programa'=>'badge-violet','institucional'=>'badge-blue','especial'=>'badge-red'];
        $da=['segunda'=>'Seg','terca'=>'Ter','quarta'=>'Qua','quinta'=>'Qui','sexta'=>'Sex','sabado'=>'Sáb','domingo'=>'Dom'];
        if(empty($mensagens)){
            $body='<tr><td colspan="7"><div class="empty-state"><div class="empty-icon"><i class="bi bi-collection-play"></i></div><div class="empty-title">Carrossel vazio</div><div class="empty-desc">Adicione mensagens ao carrossel do player.</div><a href="/public/programacao/'.$stationId.'/carrossel/novo" class="btn btn-primary"><i class="bi bi-plus"></i> Criar Mensagem</a></div></td></tr>';
        } else {
            $body='';
            foreach($mensagens as $m){
                $dias=json_decode($m['dias_semana']??'[]',true)?:[];
                $dh=''; foreach($dias as $d){$dh.='<span class="badge badge-day">'.($da[$d]??$d).'</span> ';}
                $bc=$tb[$m['tipo']]??'badge-inactive';
                $lb=$tl[$m['tipo']]??$m['tipo'];
                $st=$m['ativo']?'<span class="badge badge-active"><i class="bi bi-circle-fill" style="font-size:6px"></i>Activo</span>':'<span class="badge badge-inactive">Inactivo</span>';
                $body.='<tr><td><span class="badge '.$bc.'">'.$lb.'</span></td><td><div style="font-weight:600;font-size:13px">'.htmlspecialchars($m['linha1']).'</div><div style="font-size:11px;color:var(--text-muted);margin-top:3px">'.htmlspecialchars($m['linha2']).'</div></td><td><div style="display:flex;gap:3px;flex-wrap:wrap">'.$dh.'</div></td><td><span style="font-family:\'JetBrains Mono\',monospace;font-size:11px;color:var(--text-secondary)">'.substr($m['hora_inicio'],0,5).'&ndash;'.substr($m['hora_fim'],0,5).'</span></td><td><span style="font-weight:600">'.$m['prioridade'].'</span></td><td>'.$st.'</td><td><div class="action-group"><a href="/public/programacao/'.$stationId.'/carrossel/'.$m['id'].'/toggle" class="btn btn-ghost btn-icon btn-sm" title="Toggle"><i class="bi bi-power"></i></a><a href="/public/programacao/'.$stationId.'/carrossel/'.$m['id'].'/clonar" class="btn btn-ghost btn-icon btn-sm" title="Duplicar"><i class="bi bi-copy"></i></a><button onclick="verCarrossel('.$m['id'].')" class="btn btn-ghost btn-icon btn-sm" title="Ver"><i class="bi bi-eye"></i></button><a href="/public/programacao/'.$stationId.'/carrossel/'.$m['id'].'/editar" class="btn btn-ghost btn-icon btn-sm"><i class="bi bi-pencil"></i></a><form method="POST" action="/public/programacao/'.$stationId.'/carrossel/'.$m['id'].'/excluir" style="display:inline" onsubmit="return confirm(\'Eliminar?\')"><button type="submit" class="btn btn-danger btn-icon btn-sm"><i class="bi bi-trash"></i></button></form></div></td></tr>';
            }
        }
        return '<div class="page-header"><div class="page-header-left"><h1 class="page-title">Carrossel</h1><p class="page-subtitle">'.count($mensagens).' mensagem(ns)</p></div><div class="page-header-actions"><form method="POST" action="/public/programacao/'.$stationId.'/carrossel/importar" enctype="multipart/form-data" style="display:inline"><input type="file" name="import_file" accept=".json" id="if" style="display:none" onchange="this.form.submit()"><label for="if" class="btn btn-ghost" style="cursor:pointer"><i class="bi bi-upload"></i> Importar</label></form><a href="/public/programacao/'.$stationId.'/carrossel/exportar" class="btn btn-ghost"><i class="bi bi-download"></i> Exportar</a><a href="/public/programacao/'.$stationId.'/carrossel/novo" class="btn btn-primary"><i class="bi bi-plus"></i> Nova Mensagem</a></div></div><div class="card"><div class="table-wrap"><table><thead><tr><th>Tipo</th><th>Mensagem</th><th>Dias</th><th>Horário</th><th>Peso</th><th>Estado</th><th>Acções</th></tr></thead><tbody>'.$body.'</tbody></table></div></div>';
    }

    private function renderCarrosselForm(int $stationId, ?array $mensagem): string {
        $tipo=$mensagem['tipo']??'institucional';
        $l1=htmlspecialchars($mensagem['linha1']??'');
        $l2=htmlspecialchars($mensagem['linha2']??'');
        $hi=substr($mensagem['hora_inicio']??'00:00',0,5);
        $hf=substr($mensagem['hora_fim']??'23:59',0,5);
        $pr=$mensagem['prioridade']??1;
        $di=$mensagem['data_inicio']??'';
        $df=$mensagem['data_fim']??'';
        $dias=json_decode($mensagem['dias_semana']??'[]',true)?:[];
        $ativo=($mensagem['ativo']??1)?'checked':'';
        $action=$mensagem?'/public/programacao/'.$stationId.'/carrossel/'.$mensagem['id'].'/editar':'/public/programacao/'.$stationId.'/carrossel/novo';
        $tipos=['saudacao'=>'Saudação','promocao'=>'Promoção','programa'=>'Programa','institucional'=>'Institucional','especial'=>'Especial'];
        $th=''; foreach($tipos as $k=>$v){$sel=$tipo===$k?'selected':'';$th.='<option value="'.$k.'" '.$sel.'>'.$v.'</option>';}
        $dc=['segunda'=>'Segunda','terca'=>'Terça','quarta'=>'Quarta','quinta'=>'Quinta','sexta'=>'Sexta','sabado'=>'Sábado','domingo'=>'Domingo'];
        $dh=''; foreach($dc as $k=>$l){$ck=in_array($k,$dias)?'checked':'';$dh.='<div class="day-chip"><input type="checkbox" name="dias_semana[]" value="'.$k.'" id="d_'.$k.'" '.$ck.'><label for="d_'.$k.'">'.$l.'</label></div>';}
        return '<div class="page-header"><div class="page-header-left"><h1 class="page-title">'.($mensagem?'Editar Mensagem':'Nova Mensagem').'</h1><p class="page-subtitle">Carrossel do player público</p></div></div><div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start"><div class="card"><div class="card-header"><div class="card-title"><i class="bi bi-collection-play"></i> Conteúdo</div></div><div class="card-body"><form method="POST" action="'.$action.'" id="mf"><div class="form-section"><div class="form-section-title">Conteúdo</div><div class="form-grid form-grid-2"><div class="form-group"><label class="form-label">Tipo</label><select name="tipo" class="form-control">'.$th.'</select></div><div class="form-group"><label class="form-label">Prioridade (1–5)</label><input type="number" name="prioridade" class="form-control" value="'.$pr.'" min="1" max="5"><span class="form-hint">Maior = aparece mais vezes</span></div></div><div class="form-group"><label class="form-label">Linha 1 — Título <span class="req">*</span></label><input type="text" name="linha1" id="l1" class="form-control" value="'.$l1.'" required></div><div class="form-group"><label class="form-label">Linha 2 — Subtítulo <span class="req">*</span></label><input type="text" name="linha2" id="l2" class="form-control" value="'.$l2.'" required></div></div><div class="form-section"><div class="form-section-title">Programação</div><div class="form-group" style="margin-bottom:16px"><label class="form-label">Dias da Semana</label><div class="days-selector">'.$dh.'</div></div><div class="form-grid form-grid-4"><div class="form-group"><label class="form-label">Hora Início</label><input type="time" name="hora_inicio" class="form-control" value="'.$hi.'"></div><div class="form-group"><label class="form-label">Hora Fim</label><input type="time" name="hora_fim" class="form-control" value="'.$hf.'"></div><div class="form-group"><label class="form-label">Data Início</label><input type="date" name="data_inicio" class="form-control" value="'.$di.'"></div><div class="form-group"><label class="form-label">Data Fim</label><input type="date" name="data_fim" class="form-control" value="'.$df.'"></div></div></div><div class="form-section" style="margin-bottom:0"><div class="form-section-title">Configurações</div><div class="toggle-group" style="max-width:400px"><div class="toggle-label">Mensagem Activa<small>Aparece no carrossel do player</small></div><label class="toggle"><input type="checkbox" name="ativo" '.$ativo.'><div class="toggle-track"></div></label></div></div><div class="form-actions"><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button><a href="/public/programacao/'.$stationId.'/carrossel" class="btn btn-ghost">Cancelar</a></div></form></div></div><div><div class="card"><div class="card-header"><div class="card-title"><i class="bi bi-eye"></i> Pré-visualização</div></div><div class="card-body"><p style="font-size:12px;color:var(--text-muted);margin-bottom:16px">Como aparece no player</p><div class="preview-card"><div class="preview-inner"><div class="preview-l1" id="pl1">'.($l1?:' Título').'</div><div class="preview-l2" id="pl2">'.($l2?:'Subtítulo').'</div></div><div class="preview-dots"><div class="preview-dot active"></div><div class="preview-dot"></div><div class="preview-dot"></div></div></div></div></div></div></div><script>var a=document.getElementById("l1"),b=document.getElementById("l2"),c=document.getElementById("pl1"),d=document.getElementById("pl2");a.addEventListener("input",function(){c.textContent=this.value||"Título"});b.addEventListener("input",function(){d.textContent=this.value||"Subtítulo"});</script>';
    }

    private function renderEstatisticas(int $stationId, array $stats): string {
        $tc=['saudacao'=>'#0d9488','promocao'=>'#d97706','programa'=>'#7c3aed','institucional'=>'#2563eb','especial'=>'#e11d48','musica'=>'#e11d48','desconhecido'=>'#3a3a52'];
        $tr=''; $pos=1;
        foreach($stats['top_mensagens'] as $msg){
            $c=$tc[$msg['tipo']]??'#3a3a52';
            $tr.='<tr><td><span style="font-weight:700;color:var(--accent)">'.$pos++.'</span></td><td><span class="tipo-dot" style="background:'.$c.'"></span> '.ucfirst($msg['tipo']).'</td><td><div style="font-weight:600">'.htmlspecialchars($msg['linha1']).'</div><div style="font-size:11px;color:var(--text-muted);margin-top:2px">'.htmlspecialchars($msg['linha2']).'</div></td><td><span class="badge badge-red">'.number_format($msg['total']).'</span></td></tr>';
        }
        if(empty($stats['top_mensagens'])){$tr='<tr><td colspan="4"><div class="empty-state"><div class="empty-icon"><i class="bi bi-graph-up"></i></div><div class="empty-title">Sem dados</div></div></td></tr>';}
        $ph='';
        foreach($stats['por_tipo'] as $t){$c=$tc[$t['tipo']]??'#3a3a52';$pct=$stats['total_exibicoes']>0?round(($t['total']/$stats['total_exibicoes'])*100):0;$ph.='<div class="progress-row"><div class="progress-top"><span>'.ucfirst($t['tipo']).'</span><span>'.number_format($t['total']).' ('.$pct.'%)</span></div><div class="progress-track"><div class="progress-fill" style="width:'.$pct.'%;background:'.$c.'"></div></div></div>';}
        $mx=1; foreach($stats['ultimos_7_dias'] as $d){if($d['total']>$mx)$mx=$d['total'];}
        $dh='';
        foreach($stats['ultimos_7_dias'] as $d){$pct=round(($d['total']/$mx)*100);$dh.='<div class="progress-row"><div class="progress-top"><span>'.date('d/m',strtotime($d['dia'])).'</span><span>'.number_format($d['total']).'</span></div><div class="progress-track"><div class="progress-fill" style="width:'.$pct.'%;background:var(--blue)"></div></div></div>';}
        if(empty($stats['ultimos_7_dias'])){$dh='<div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px">Sem dados.</div>';}
        return '<div class="page-header"><div class="page-header-left"><h1 class="page-title">Estatísticas</h1><p class="page-subtitle">Desempenho do carrossel</p></div></div><div class="stats-detail-grid"><div class="stat-detail-card"><div class="stat-detail-value" style="color:var(--accent)">'.number_format($stats['total_exibicoes']).'</div><div class="stat-detail-label">Total de Exibições</div></div><div class="stat-detail-card"><div class="stat-detail-value" style="color:var(--teal)">'.number_format($stats['exibicoes_hoje']).'</div><div class="stat-detail-label">Exibições Hoje</div></div><div class="stat-detail-card"><div class="stat-detail-value" style="color:var(--blue)">'.count($stats['por_tipo']).'</div><div class="stat-detail-label">Tipos Activos</div></div></div><div class="card" style="margin-bottom:20px"><div class="card-header"><div class="card-title"><i class="bi bi-trophy"></i> Top 5 Mensagens</div></div><div class="table-wrap"><table><thead><tr><th>#</th><th>Tipo</th><th>Mensagem</th><th>Exibições</th></tr></thead><tbody>'.$tr.'</tbody></table></div></div><div class="two-col"><div class="card"><div class="card-header"><div class="card-title"><i class="bi bi-pie-chart"></i> Por Tipo</div></div><div class="card-body">'.($ph?:'<div style="color:var(--text-muted);text-align:center;padding:20px">Sem dados.</div>').'</div></div><div class="card"><div class="card-header"><div class="card-title"><i class="bi bi-calendar-week"></i> Últimos 7 Dias</div></div><div class="card-body">'.$dh.'</div></div></div>';
    }
}
