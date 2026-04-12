<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin;

class WeeklyReport
{
    private \PDO $pdo;
    private int  $sid;

    public function __construct(int $stationId = 1)
    {
        $this->sid = $stationId;
        $this->pdo = new \PDO(
            'mysql:host=127.0.0.1;dbname=azuracast;charset=utf8mb4',
            'azuracast','CKxR234fxpJG',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    public function generate(string $dataIni = '', string $dataFim = ''): array
    {
        $dataFim = $dataFim ?: date('Y-m-d');
        $dataIni = $dataIni ?: date('Y-m-d', strtotime('-7 days'));
        $semana  = 'Semana '.date('W').'/'.date('Y');

        // Audiência
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(AVG(listeners_total),0) as media,
                    COALESCE(MAX(listeners_total),0) as pico
             FROM plugin_pulso_stream_stats
             WHERE station_id=? AND DATE(created_at) BETWEEN ? AND ?"
        );
        $stmt->execute([$this->sid,$dataIni,$dataFim]);
        $audStats = $stmt->fetch(\PDO::FETCH_ASSOC);

        $stmtOuv = $this->pdo->prepare(
            "SELECT COUNT(*) FROM plugin_pulso_ouvintes
             WHERE station_id=? AND DATE(data_registo) BETWEEN ? AND ?"
        );
        $stmtOuv->execute([$this->sid,$dataIni,$dataFim]);
        $novosOuv = (int)$stmtOuv->fetchColumn();

        // Top músicas por toques (Myriad)
        $sync = new SyncService($this->sid);
        $topMusicas = $sync->getTopMusicas($dataIni, $dataFim, 10);

        // Top músicas por audiência (PULSO)
        $stmt = $this->pdo->prepare(
            "SELECT song_title AS titulo, song_artist AS artista,
                    ROUND(AVG(listeners_total),1) AS media_aud,
                    COUNT(*) AS amostras
             FROM plugin_pulso_stream_stats
             WHERE station_id=? AND DATE(created_at) BETWEEN ? AND ?
               AND song_title != '' AND listeners_total > 0
             GROUP BY song_title, song_artist
             ORDER BY media_aud DESC LIMIT 10"
        );
        $stmt->execute([$this->sid,$dataIni,$dataFim]);
        $topMusicasAud = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Ranking programas
        $stmt = $this->pdo->prepare(
            "SELECT pr.nome AS programa, lo.nome AS locutor,
                    pr.hora_inicio, pr.hora_fim,
                    ROUND(AVG(s.listeners_total),1) AS media_aud,
                    MAX(s.listeners_total) AS pico_aud
             FROM plugin_prog_programas pr
             LEFT JOIN plugin_prog_programa_locutor pl ON pl.programa_id=pr.id AND pl.is_principal=1
             LEFT JOIN plugin_prog_locutores lo ON lo.id=pl.locutor_id
             JOIN plugin_pulso_stream_stats s ON s.station_id=pr.station_id
               AND HOUR(s.created_at) >= HOUR(pr.hora_inicio)
               AND HOUR(s.created_at) < HOUR(pr.hora_fim)
               AND DATE(s.created_at) BETWEEN ? AND ?
             WHERE pr.station_id=? AND pr.ativo=1
             GROUP BY pr.id ORDER BY media_aud DESC"
        );
        $stmt->execute([$dataIni,$dataFim,$this->sid]);
        $progRanking = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Performance locutores
        $stmt = $this->pdo->prepare(
            "SELECT f.nome,
                    ROUND(AVG(p.performance_score),1) AS score_medio,
                    SUM(p.participacoes) AS total_musicas,
                    ROUND(AVG(p.audiencia_media),0) AS aud_media
             FROM rnb_rh_performance p
             JOIN rnb_funcionarios f ON f.id=p.funcionario_id
             WHERE p.station_id=? AND p.data BETWEEN ? AND ?
             GROUP BY p.funcionario_id ORDER BY score_medio DESC"
        );
        $stmt->execute([$this->sid,$dataIni,$dataFim]);
        $perfLocutores = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Spots
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total, COALESCE(SUM(duracao_seg),0) AS duracao_total
             FROM rnb_prova_emissao
             WHERE station_id=? AND DATE(data_emissao) BETWEEN ? AND ?"
        );
        $stmt->execute([$this->sid,$dataIni,$dataFim]);
        $spotsStats = $stmt->fetch(\PDO::FETCH_ASSOC);

        $data = [
            'semana'       => $semana,
            'periodo'      => ['inicio'=>$dataIni,'fim'=>$dataFim],
            'audiencia'    => [
                'media'    => round((float)($audStats['media']??0),1),
                'pico'     => (int)($audStats['pico']??0),
                'novos_ouv'=> (int)$novosOuv,
            ],
            'top_musicas'  => $topMusicas,
            'top_aud'      => $topMusicasAud,
            'prog_ranking' => $progRanking,
            'locutores'    => $perfLocutores,
            'spots'        => $spotsStats,
        ];

        $this->saveReport($data);
        return $data;
    }

    public function generateHtml(array $data): string
    {
        $s   = $data['semana'];
        $ini = $data['periodo']['inicio'];
        $fim = $data['periodo']['fim'];
        $aud = $data['audiencia'];
        ob_start();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<title>Relatório Semanal RNB — <?= $s ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Inter,sans-serif;background:#0A0A14;color:#E2E8F0;padding:32px;font-size:14px}
.header{background:linear-gradient(135deg,#1a0533,#0f1729);border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:28px 32px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:flex-start}
.logo{font-size:28px;font-weight:900;color:#fff;letter-spacing:-1px}.logo span{color:#00E5B8}
.kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}
.kpi{background:#111827;border:1px solid #1F2937;border-radius:10px;padding:18px;border-top:3px solid}
.kpi-val{font-size:26px;font-weight:900;color:#fff;margin-bottom:4px}
.kpi-lbl{font-size:11px;color:#64748B;text-transform:uppercase;letter-spacing:.6px}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
.card{background:#111827;border:1px solid #1F2937;border-radius:10px;overflow:hidden}
.card-head{padding:14px 18px;border-bottom:1px solid #1F2937;font-size:13px;font-weight:700;color:#fff;display:flex;justify-content:space-between}
.card-sub{font-size:11px;color:#64748B;font-weight:400}
.row{display:flex;align-items:center;gap:10px;padding:10px 18px;border-bottom:1px solid #1a2030}
.row:last-child{border-bottom:none}
.rank{width:22px;height:22px;border-radius:6px;background:#1F2937;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#94A3B8;flex-shrink:0}
.rank.gold{background:#92400E22;color:#F59E0B}.rank.silver{background:#374151;color:#9CA3AF}.rank.bronze{background:#431407;color:#EA580C}
.row-title{flex:1;min-width:0}
.row-name{font-size:13px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.row-sub{font-size:11px;color:#64748B;margin-top:1px}
.footer{text-align:center;padding:20px;font-size:11px;color:#374151;border-top:1px solid #1F2937;margin-top:24px}
</style>
</head>
<body>
<div class="header">
  <div>
    <div class="logo">RNB <span>OS</span></div>
    <div style="font-size:12px;color:#64748B;margin-top:4px">Sistema de Gestão de Rádio</div>
  </div>
  <div style="text-align:right">
    <div style="font-size:18px;font-weight:700;color:#fff">Relatório Semanal — <?= $s ?></div>
    <div style="font-size:12px;color:#94A3B8;margin-top:4px"><?= $ini ?> a <?= $fim ?></div>
    <div style="font-size:11px;color:#64748B;margin-top:4px">Gerado automaticamente pelo RNB OS em <?= date('d/m/Y H:i') ?></div>
  </div>
</div>

<div class="kpis">
  <div class="kpi" style="border-top-color:#00E5B8">
    <div class="kpi-val"><?= number_format($aud['media'],1,',','.') ?></div>
    <div class="kpi-lbl">Ouvintes Médios</div>
  </div>
  <div class="kpi" style="border-top-color:#8860FF">
    <div class="kpi-val"><?= number_format($aud['pico'],0,',','.') ?></div>
    <div class="kpi-lbl">Pico de Audiência</div>
  </div>
  <div class="kpi" style="border-top-color:#F59E0B">
    <div class="kpi-val">+<?= $aud['novos_ouv'] ?></div>
    <div class="kpi-lbl">Novos Ouvintes</div>
  </div>
  <div class="kpi" style="border-top-color:#10B981">
    <div class="kpi-val"><?= (int)($data['spots']['total']??0) ?></div>
    <div class="kpi-lbl">Spots Emitidos</div>
  </div>
</div>

<div class="grid">
  <div class="card">
    <div class="card-head">Top Músicas — Mais Tocadas <span class="card-sub">Emissões Myriad</span></div>
    <?php foreach(array_slice($data['top_musicas'],0,8) as $i=>$m): ?>
    <div class="row">
      <div class="rank <?= $i===0?'gold':($i===1?'silver':($i===2?'bronze':'')) ?>"><?= $i+1 ?></div>
      <div class="row-title">
        <div class="row-name"><?= htmlspecialchars($m['titulo']??'—') ?></div>
        <div class="row-sub"><?= htmlspecialchars($m['artista']??'') ?> <?= $m['ano']?'('.$m['ano'].')':'' ?></div>
      </div>
      <div style="font-size:12px;font-weight:700;color:#00E5B8;flex-shrink:0"><?= $m['toques'] ?>×</div>
    </div>
    <?php endforeach ?>
    <?php if(empty($data['top_musicas'])): ?>
    <div style="padding:20px;text-align:center;color:#374151;font-size:12px">Sem dados do Myriad esta semana</div>
    <?php endif ?>
  </div>

  <div class="card">
    <div class="card-head">Programas — Ranking <span class="card-sub">Por audiência real</span></div>
    <?php foreach(array_slice($data['prog_ranking'],0,8) as $i=>$p): ?>
    <div class="row">
      <div class="rank <?= $i===0?'gold':($i===1?'silver':($i===2?'bronze':'')) ?>"><?= $i+1 ?></div>
      <div class="row-title">
        <div class="row-name"><?= htmlspecialchars($p['programa']??'—') ?></div>
        <div class="row-sub"><?= htmlspecialchars($p['locutor']??'Automático') ?> · <?= substr($p['hora_inicio']??'',0,5) ?>–<?= substr($p['hora_fim']??'',0,5) ?></div>
      </div>
      <div style="font-size:12px;font-weight:700;color:#8860FF;flex-shrink:0"><?= $p['media_aud'] ?> ouv</div>
    </div>
    <?php endforeach ?>
    <?php if(empty($data['prog_ranking'])): ?>
    <div style="padding:20px;text-align:center;color:#374151;font-size:12px">Sem dados de programas esta semana</div>
    <?php endif ?>
  </div>
</div>

<?php if(!empty($data['top_aud'])): ?>
<div class="card" style="margin-bottom:16px">
  <div class="card-head">Top Músicas por Audiência <span class="card-sub">Músicas que retêm mais ouvintes</span></div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:0">
  <?php foreach(array_slice($data['top_aud'],0,6) as $i=>$m): ?>
  <div class="row">
    <div class="rank <?= $i===0?'gold':($i===1?'silver':($i===2?'bronze':'')) ?>"><?= $i+1 ?></div>
    <div class="row-title">
      <div class="row-name"><?= htmlspecialchars($m['titulo']??'—') ?></div>
      <div class="row-sub"><?= htmlspecialchars($m['artista']??'') ?></div>
    </div>
    <div style="font-size:12px;font-weight:700;color:#F59E0B;flex-shrink:0"><?= $m['media_aud'] ?> ouv</div>
  </div>
  <?php endforeach ?>
  </div>
</div>
<?php endif ?>

<?php if(!empty($data['locutores'])): ?>
<div class="card" style="margin-bottom:16px">
  <div class="card-head">Performance dos Locutores <span class="card-sub">Score automático — dados Myriad</span></div>
  <?php foreach($data['locutores'] as $l): ?>
  <div class="row">
    <div class="row-title">
      <div class="row-name"><?= htmlspecialchars($l['nome']??'—') ?></div>
      <div class="row-sub"><?= number_format((float)($l['total_musicas']??0)) ?> músicas apresentadas esta semana</div>
    </div>
    <div style="text-align:right;flex-shrink:0">
      <div style="font-size:20px;font-weight:900;color:<?= (float)($l['score_medio']??0)>=70?'#10B981':'#F59E0B' ?>"><?= $l['score_medio'] ?></div>
      <div style="font-size:10px;color:#64748B">score</div>
    </div>
  </div>
  <?php endforeach ?>
</div>
<?php endif ?>

<div class="footer">
  RNB OS &middot; Rádio New Band Angola &middot; Relatório gerado automaticamente em <?= date('d/m/Y H:i') ?>
</div>
</body>
</html>
<?php
        return ob_get_clean();
    }

    private function saveReport(array $data): void
    {
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS rnb_relatorios_semanais (
                id INT AUTO_INCREMENT PRIMARY KEY,
                station_id INT NOT NULL DEFAULT 1,
                semana VARCHAR(20),
                data_inicio DATE,
                data_fim DATE,
                dados JSON,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_station (station_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $this->pdo->prepare(
                "INSERT INTO rnb_relatorios_semanais
                 (station_id,semana,data_inicio,data_fim,dados,created_at)
                 VALUES (?,?,?,?,?,NOW())"
            )->execute([
                $this->sid,
                $data['semana'],
                $data['periodo']['inicio'],
                $data['periodo']['fim'],
                json_encode($data, JSON_UNESCAPED_UNICODE),
            ]);
        } catch(\Exception $e) {}
    }
}
