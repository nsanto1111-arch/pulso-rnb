<?php
$historico = $historico ?? [];
$stats     = $dados['stats'] ?? [];
$tipoLabel = [
    'pedido_musical' => ['label'=>'🎵 Pedido Musical', 'cor'=>'#8b5cf6'],
    'participacao'   => ['label'=>'🎁 Participação',   'cor'=>'#10b981'],
    'informacao'     => ['label'=>'ℹ️ Informação',     'cor'=>'#3b82f6'],
    'reclamacao'     => ['label'=>'⚠️ Reclamação',     'cor'=>'#ef4444'],
    'sugestao'       => ['label'=>'💡 Sugestão',       'cor'=>'#f59e0b'],
    'outro'          => ['label'=>'📋 Outro',          'cor'=>'#71717a'],
];
$canalIcon = ['telefone'=>'📞','whatsapp'=>'💬','presencial'=>'🏢','outro'=>'📋'];
?>
<style>
.ah-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.ah-filtros{display:flex;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;align-items:center}
.ah-input{padding:.5rem .875rem;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:13px;outline:none;color-scheme:dark}
.ah-select{padding:.5rem .875rem;background:rgba(20,20,40,0.9);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#fff;font-size:13px;outline:none}
.ah-btn{padding:.5rem 1rem;background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);border-radius:8px;color:#00e5ff;font-size:13px;font-weight:600;cursor:pointer}
.ah-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden}
.ah-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;justify-content:space-between}
.ah-table{width:100%;border-collapse:collapse;font-size:13px}
.ah-table th{padding:.75rem 1.25rem;text-align:left;font-size:11px;font-weight:700;color:#71717a;text-transform:uppercase;letter-spacing:1px;border-bottom:1px solid rgba(255,255,255,0.06)}
.ah-table td{padding:.875rem 1.25rem;border-bottom:1px solid rgba(255,255,255,0.04);vertical-align:middle}
.ah-table tr:last-child td{border-bottom:none}
.ah-table tr:hover td{background:rgba(255,255,255,0.02)}
.ah-badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700}
.ah-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#00e5ff,#8b5cf6);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#000;flex-shrink:0}
.ah-empty{text-align:center;padding:3rem;color:#52525b}
</style>

<div class="ah-header">
    <div>
        <div style="font-size:22px;font-weight:800;color:#fff">📋 Histórico de Atendimentos</div>
        <div style="font-size:13px;color:#71717a;margin-top:3px"><?= count($historico) ?> registos encontrados</div>
    </div>
    <a href="/public/pulso/<?= $stationId ?>/atendimento"
       style="display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.125rem;background:rgba(0,229,255,0.1);border:1px solid rgba(0,229,255,0.3);border-radius:8px;color:#00e5ff;text-decoration:none;font-size:13px;font-weight:600">
        <i class="bi bi-plus-lg"></i> Novo Atendimento
    </a>
</div>

<!-- FILTROS -->
<div class="ah-filtros">
    <input type="date" class="ah-input" id="filtroData" value="<?= htmlspecialchars($_GET['data'] ?? date('Y-m-d')) ?>" style="color-scheme:dark">
    <select class="ah-select" id="filtroTipo">
        <option value="">Todos os tipos</option>
        <?php foreach($tipoLabel as $k => $v): ?>
        <option value="<?= $k ?>" <?= ($_GET['tipo']??'')===$k?'selected':'' ?>><?= $v['label'] ?></option>
        <?php endforeach; ?>
    </select>
    <button class="ah-btn" onclick="filtrar()"><i class="bi bi-search"></i> Filtrar</button>
    <a href="?data=<?= date('Y-m-d') ?>" style="font-size:12px;color:#71717a;text-decoration:none;padding:.5rem .875rem;border:1px solid rgba(255,255,255,0.08);border-radius:8px">Hoje</a>
</div>

<!-- TABELA -->
<div class="ah-card">
    <div class="ah-card-head">
        <span>📞 Atendimentos</span>
        <span style="font-size:12px;color:#71717a"><?= count($historico) ?></span>
    </div>
    <?php if (!empty($historico)): ?>
    <div style="overflow-x:auto">
    <table class="ah-table">
        <thead>
            <tr>
                <th>Ouvinte</th>
                <th>Tipo</th>
                <th>Canal</th>
                <th>Detalhe</th>
                <th>Atendente</th>
                <th>Duração</th>
                <th>Hora</th>
                <th>Resultado</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($historico as $h):
            $tipo = $tipoLabel[$h['tipo'] ?? 'outro'] ?? $tipoLabel['outro'];
            $canal = $canalIcon[$h['canal'] ?? 'telefone'] ?? '📞';
            $duracao = $h['duracao_segundos'] > 0
                ? floor($h['duracao_segundos']/60) . ':' . str_pad($h['duracao_segundos']%60, 2, '0', STR_PAD_LEFT)
                : '—';
            $ini = mb_strtoupper(mb_substr($h['ouvinte_nome'] ?? '?', 0, 1));
            $hora = date('H:i', strtotime($h['data_atendimento']));
            $resColor = $h['resultado'] === 'atendido' ? '#10b981' : '#ef4444';
        ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:.625rem">
                    <div class="ah-avatar"><?= $ini ?></div>
                    <div>
                        <a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $h['ouvinte_id'] ?>/ficha"
                           style="font-weight:600;color:#fff;text-decoration:none;font-size:13px">
                            <?= htmlspecialchars($h['ouvinte_nome'] ?? 'Desconhecido') ?>
                        </a>
                        <div style="font-size:11px;color:#71717a"><?= htmlspecialchars($h['telefone'] ?? '') ?></div>
                    </div>
                </div>
            </td>
            <td>
                <span class="ah-badge" style="background:<?= $tipo['cor'] ?>15;color:<?= $tipo['cor'] ?>;border:1px solid <?= $tipo['cor'] ?>25">
                    <?= $tipo['label'] ?>
                </span>
            </td>
            <td style="font-size:16px"><?= $canal ?></td>
            <td style="color:#a1a1aa;font-size:12px;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= htmlspecialchars($h['musica_pedida'] ?: ($h['descricao'] ?? '—')) ?>
            </td>
            <td style="color:#71717a;font-size:12px"><?= htmlspecialchars($h['atendente'] ?? '—') ?></td>
            <td style="color:#00e5ff;font-family:monospace;font-size:12px"><?= $duracao ?></td>
            <td style="color:#71717a;font-size:12px"><?= $hora ?></td>
            <td>
                <span style="font-size:11px;font-weight:700;color:<?= $resColor ?>"><?= ucfirst($h['resultado'] ?? '') ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
    <div class="ah-empty">
        <div style="font-size:48px;margin-bottom:1rem;opacity:.3">📞</div>
        <div style="font-size:15px;font-weight:600;color:#a1a1aa;margin-bottom:.5rem">Nenhum atendimento encontrado</div>
        <div style="font-size:13px">Tenta ajustar os filtros</div>
    </div>
    <?php endif; ?>
</div>

<script>
function filtrar() {
    const data = document.getElementById('filtroData').value;
    const tipo = document.getElementById('filtroTipo').value;
    let url = `?data=${data}`;
    if (tipo) url += `&tipo=${tipo}`;
    window.location = url;
}
document.getElementById('filtroData').addEventListener('change', filtrar);
</script>
