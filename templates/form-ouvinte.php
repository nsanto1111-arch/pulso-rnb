<?php
$isEdit = !empty($dados['id']);
$id     = $dados['id'] ?? '';
$action = $isEdit
    ? "/public/pulso/{$stationId}/ouvintes/{$id}/editar"
    : "/public/pulso/{$stationId}/ouvintes/salvar";

$generosSalvos = [];
if (!empty($dados['generos_musicais'])) {
    $decoded = json_decode($dados['generos_musicais'], true);
    $generosSalvos = is_array($decoded) ? $decoded : [];
}

$provincias = ['Luanda','Benguela','Huambo','Bié','Moxico','Lunda Norte','Lunda Sul',
    'Cuanza Norte','Cuanza Sul','Malanje','Uíge','Zaire','Cabinda',
    'Cuando Cubango','Cunene','Huíla','Namibe','Bengo'];

$paisActual = $dados['pais'] ?? 'Angola';

function fv(array $d, string $k): string {
    return htmlspecialchars($d[$k] ?? '');
}
?>
<style>
.ef{font-family:'Inter',sans-serif}
.ef-back{display:inline-flex;align-items:center;gap:.5rem;color:#71717a;text-decoration:none;font-size:13px;font-weight:600;margin-bottom:1.25rem;transition:color .2s}
.ef-back:hover{color:#fff;text-decoration:none}

.ef-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.ef-title{font-size:22px;font-weight:800;color:#fff}
.ef-subtitle{font-size:13px;color:#71717a;margin-top:3px}

.ef-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.25rem}
.ef-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem}
.ef-full{grid-column:1/-1}

.ef-card{background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.07);border-radius:14px;overflow:hidden;margin-bottom:1.25rem}
.ef-card-head{padding:1rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.06);font-size:14px;font-weight:700;color:#fff;display:flex;align-items:center;gap:.625rem}
.ef-card-head i{color:#00e5ff;font-size:16px}
.ef-card-body{padding:1.5rem}

.ef-group{display:flex;flex-direction:column;gap:.5rem}
.ef-label{font-size:12px;font-weight:700;color:#71717a;text-transform:uppercase;letter-spacing:.5px}
.ef-label span{color:#ef4444;margin-left:2px}
.ef-input,.ef-select,.ef-textarea{
    width:100%;padding:.75rem 1rem;
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:10px;color:#fff;font-size:14px;
    font-family:inherit;transition:border-color .2s,background .2s;outline:none;
}
.ef-input:focus,.ef-select:focus,.ef-textarea:focus{
    border-color:rgba(0,229,255,0.4);
    background:rgba(0,229,255,0.04);
}
.ef-input::placeholder{color:#52525b}
.ef-select option{background:#1a1a2e;color:#fff}
.ef-textarea{resize:vertical;min-height:100px;line-height:1.6}
.ef-hint{font-size:11px;color:#52525b;margin-top:.25rem}

/* GÉNEROS */
.ef-generos{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.5rem;margin-top:.5rem}
.ef-genero{display:flex;align-items:center;gap:.625rem;padding:.625rem .875rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:8px;cursor:pointer;transition:all .2s}
.ef-genero:hover{border-color:rgba(139,92,246,0.3);background:rgba(139,92,246,0.06)}
.ef-genero input[type=checkbox]{width:16px;height:16px;accent-color:#8b5cf6;cursor:pointer;flex-shrink:0}
.ef-genero label{font-size:12px;color:#a1a1aa;cursor:pointer;font-weight:500}
.ef-genero.checked{border-color:rgba(139,92,246,0.5);background:rgba(139,92,246,0.1)}
.ef-genero.checked label{color:#a78bfa}

/* RADIO GÉNERO */
.ef-radios{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:.5rem}
.ef-radio{display:flex;align-items:center;gap:.5rem;padding:.625rem 1rem;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);border-radius:8px;cursor:pointer;transition:all .2s}
.ef-radio:hover{border-color:rgba(0,229,255,0.3)}
.ef-radio input{accent-color:#00e5ff;cursor:pointer}
.ef-radio label{font-size:13px;color:#a1a1aa;cursor:pointer;font-weight:500}
.ef-radio.checked{border-color:rgba(0,229,255,0.4);background:rgba(0,229,255,0.06)}
.ef-radio.checked label{color:#00e5ff}

/* ACTIONS */
.ef-actions{display:flex;gap:.875rem;margin-top:1.75rem;padding-top:1.5rem;border-top:1px solid rgba(255,255,255,0.07)}
.ef-btn-save{display:inline-flex;align-items:center;gap:.625rem;padding:.875rem 2rem;background:linear-gradient(135deg,#00e5ff,#0891b2);color:#000;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s}
.ef-btn-save:hover{opacity:.9;transform:translateY(-1px)}
.ef-btn-cancel{display:inline-flex;align-items:center;gap:.625rem;padding:.875rem 1.5rem;background:rgba(255,255,255,0.04);color:#a1a1aa;border:1px solid rgba(255,255,255,0.1);border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;transition:all .2s}
.ef-btn-cancel:hover{background:rgba(255,255,255,0.07);color:#fff;text-decoration:none}

/* COMPLETUDE */
.ef-comp{background:rgba(0,229,255,0.04);border:1px solid rgba(0,229,255,0.12);border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:1rem}
.ef-comp-bar{flex:1;height:6px;background:rgba(255,255,255,0.08);border-radius:3px;overflow:hidden}
.ef-comp-fill{height:100%;border-radius:3px;background:#00e5ff;transition:width .4s}
.ef-comp-txt{font-size:12px;color:#00e5ff;font-weight:700;white-space:nowrap}

@media(max-width:768px){
    .ef-grid,.ef-grid-3{grid-template-columns:1fr}
    .ef-full{grid-column:1}
}
</style>

<div class="ef">

<!-- VOLTAR -->
<?php if ($isEdit): ?>
<a href="/public/pulso/<?= $stationId ?>/ouvintes/<?= $id ?>/ficha" class="ef-back">
    <i class="bi bi-arrow-left"></i> Voltar à Ficha
</a>
<?php else: ?>
<a href="/public/pulso/<?= $stationId ?>/ouvintes" class="ef-back">
    <i class="bi bi-arrow-left"></i> Voltar aos Ouvintes
</a>
<?php endif; ?>

<!-- HEADER -->
<div class="ef-header">
    <div>
        <div class="ef-title"><?= $isEdit ? '✏️ Editar Ouvinte' : '➕ Novo Ouvinte' ?></div>
        <div class="ef-subtitle">
            <?= $isEdit ? 'Actualizar informações de ' . htmlspecialchars($dados['nome'] ?? '') : 'Adicionar novo ouvinte ao PULSO' ?>
        </div>
    </div>
</div>

<!-- BARRA DE COMPLETUDE (só em edição) -->
<?php if ($isEdit):
    $score = (
        (!empty($dados['provincia'])       ? 1 : 0) +
        (!empty($dados['genero'])          ? 1 : 0) +
        (!empty($dados['data_nascimento']) ? 1 : 0) +
        (!empty($dados['generos_musicais'])? 1 : 0) +
        (!empty($dados['como_conheceu'])   ? 1 : 0)
    );
    $pct = round($score / 5 * 100);
    $corComp = $pct >= 80 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#ef4444');
?>
<div class="ef-comp">
    <div style="font-size:13px;color:#a1a1aa;font-weight:600;white-space:nowrap">Perfil <?= $pct ?>% completo</div>
    <div class="ef-comp-bar">
        <div class="ef-comp-fill" style="width:<?= $pct ?>%;background:<?= $corComp ?>"></div>
    </div>
    <div class="ef-comp-txt"><?= $score ?>/5</div>
</div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>">

    <!-- DADOS PESSOAIS -->
    <div class="ef-card">
        <div class="ef-card-head"><i class="bi bi-person-circle"></i> Dados Pessoais</div>
        <div class="ef-card-body">
            <div class="ef-grid" style="margin-bottom:1rem">
                <div class="ef-group">
                    <div class="ef-label">Nome Completo <span>*</span></div>
                    <input type="text" name="nome" required class="ef-input"
                           value="<?= fv($dados,'nome') ?>" placeholder="Ex: Maria da Silva">
                </div>
                <div class="ef-group">
                    <div class="ef-label">Telefone <span>*</span></div>
                    <input type="tel" name="telefone" required class="ef-input"
                           value="<?= fv($dados,'telefone') ?>" placeholder="+244 923 456 789">
                    <div class="ef-hint">Inclua o código do país: +244 (Angola), +351 (Portugal)...</div>
                </div>
            </div>
            <div class="ef-grid" style="margin-bottom:1rem">
                <div class="ef-group">
                    <div class="ef-label">Email</div>
                    <input type="email" name="email" class="ef-input"
                           value="<?= fv($dados,'email') ?>" placeholder="exemplo@email.com">
                </div>
                <div class="ef-group">
                    <div class="ef-label">Data de Nascimento</div>
                    <input type="date" name="data_nascimento" class="ef-input"
                           value="<?= fv($dados,'data_nascimento') ?>"
                           max="<?= date('Y-m-d', strtotime('-10 years')) ?>">
                </div>
            </div>
            <div class="ef-group">
                <div class="ef-label">Género</div>
                <div class="ef-radios" id="radios-genero">
                    <?php foreach(['masculino'=>'👨 Masculino','feminino'=>'👩 Feminino','outro'=>'⚧ Outro','prefiro_nao_dizer'=>'🤐 Prefiro não dizer'] as $val => $lbl): ?>
                    <div class="ef-radio <?= ($dados['genero'] ?? '') === $val ? 'checked' : '' ?>">
                        <input type="radio" name="genero" value="<?= $val ?>" id="g-<?= $val ?>"
                               <?= ($dados['genero'] ?? '') === $val ? 'checked' : '' ?>>
                        <label for="g-<?= $val ?>"><?= $lbl ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- LOCALIZAÇÃO -->
    <div class="ef-card">
        <div class="ef-card-head"><i class="bi bi-geo-alt"></i> Localização</div>
        <div class="ef-card-body">
            <div class="ef-grid" style="margin-bottom:1rem">
                <div class="ef-group">
                    <div class="ef-label">País <span>*</span></div>
                    <select name="pais" class="ef-select" id="sel-pais" onchange="toggleProvincia()">
                        <?php foreach(['Angola','Portugal','Brasil','França','Moçambique','Cabo Verde','São Tomé e Príncipe','Guiné-Bissau','Outro'] as $p): ?>
                        <option value="<?= $p ?>" <?= $paisActual === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ef-group" id="wrap-provincia">
                    <div class="ef-label">Província</div>
                    <?php if ($paisActual === 'Angola'): ?>
                    <select name="provincia" class="ef-select">
                        <option value="">Selecione...</option>
                        <?php foreach($provincias as $prov): ?>
                        <option value="<?= $prov ?>" <?= ($dados['provincia'] ?? '') === $prov ? 'selected' : '' ?>><?= $prov ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php else: ?>
                    <input type="text" name="provincia" class="ef-input"
                           value="<?= fv($dados,'provincia') ?>" placeholder="Estado / Região">
                    <?php endif; ?>
                </div>
            </div>
            <div class="ef-grid">
                <div class="ef-group">
                    <div class="ef-label">Município / Cidade</div>
                    <input type="text" name="municipio" class="ef-input"
                           value="<?= fv($dados,'municipio') ?>" placeholder="Ex: Viana, Porto, São Paulo">
                </div>
                <div class="ef-group">
                    <div class="ef-label">Bairro</div>
                    <input type="text" name="bairro" class="ef-input"
                           value="<?= fv($dados,'bairro') ?>" placeholder="Ex: Cazenga, Boavista">
                </div>
            </div>
        </div>
    </div>

    <!-- PREFERÊNCIAS -->
    <div class="ef-card">
        <div class="ef-card-head"><i class="bi bi-music-note-beamed"></i> Preferências da Rádio</div>
        <div class="ef-card-body">
            <div class="ef-grid" style="margin-bottom:1.25rem">
                <div class="ef-group">
                    <div class="ef-label">Programa Favorito</div>
                    <input type="text" name="programa_favorito" class="ef-input"
                           value="<?= fv($dados,'programa_favorito') ?>" placeholder="Ex: Morning Show, Drive Time">
                </div>
                <div class="ef-group">
                    <div class="ef-label">Locutor Favorito</div>
                    <input type="text" name="locutor_favorito" class="ef-input"
                           value="<?= fv($dados,'locutor_favorito') ?>" placeholder="Ex: DJ Celso, Newton">
                </div>
            </div>
            <div class="ef-grid" style="margin-bottom:1.25rem">
                <div class="ef-group">
                    <div class="ef-label">Como Conheceu a Rádio?</div>
                    <select name="como_conheceu" class="ef-select">
                        <option value="">Selecione...</option>
                        <?php foreach(['Redes Sociais','Amigos/Família','Google/Pesquisa','Rádio FM','Publicidade','Evento','Outro'] as $op): ?>
                        <option value="<?= $op ?>" <?= ($dados['como_conheceu'] ?? '') === $op ? 'selected' : '' ?>><?= $op ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ef-group">
                    <div class="ef-label">Horário Preferido de Escuta</div>
                    <select name="horario_preferido" class="ef-select">
                        <option value="">Selecione...</option>
                        <?php foreach(['Madrugada (00h-06h)','Manhã (06h-12h)','Tarde (12h-18h)','Noite (18h-00h)','Qualquer hora'] as $op): ?>
                        <option value="<?= $op ?>" <?= ($dados['horario_preferido'] ?? '') === $op ? 'selected' : '' ?>><?= $op ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="ef-hint">Usado para personalizar promoções e contactos</div>
                </div>
            </div>
                </select>
            </div>
            <div class="ef-group">
                <div class="ef-label">Géneros Musicais Favoritos <span style="color:#71717a;font-weight:400;text-transform:none;letter-spacing:0">(máx. 5)</span></div>
                <?php
                $generos = ['Kizomba','Semba','Kuduro','Afrobeat','Afrobeats','Afro House','Amapiano',
                    'Zouk','Kompa','Hip-Hop','Rap','R&B','Neo Soul','Pop','Rock','Eletrónica',
                    'House','Deep House','Techno','Reggae','Dancehall','Samba','Pagode',
                    'Bossa Nova','MPB','Funk','Jazz','Soul','Gospel','Lo-fi','Clássica'];
                ?>
                <div class="ef-generos" id="generos-wrap">
                    <?php foreach($generos as $g):
                        $checked = in_array($g, $generosSalvos);
                        $gid = 'gm_'.preg_replace('/[^a-z0-9]/i','_',strtolower($g));
                    ?>
                    <div class="ef-genero <?= $checked ? 'checked' : '' ?>" onclick="toggleGenero(this)">
                        <input type="checkbox" name="generos_musicais[]" value="<?= htmlspecialchars($g) ?>"
                               id="<?= $gid ?>" <?= $checked ? 'checked' : '' ?>>
                        <label for="<?= $gid ?>"><?= $g ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="ef-hint" id="generos-hint" style="margin-top:.5rem">
                    <span id="generos-count"><?= count($generosSalvos) ?></span>/5 géneros seleccionados
                </div>
            </div>
        </div>
    </div>

    <!-- NOTAS INTERNAS -->
    <div class="ef-card">
        <div class="ef-card-head"><i class="bi bi-pencil-square"></i> Notas Internas da Equipa</div>
        <div class="ef-card-body">
            <div class="ef-group">
                <div class="ef-label">Observações</div>
                <textarea name="notas" class="ef-textarea"
                    placeholder="Notas internas sobre este ouvinte — não visíveis ao público..."><?= fv($dados,'notas') ?></textarea>
                <div class="ef-hint">Visível apenas para a equipa da rádio</div>
            </div>
        </div>
    </div>

    <!-- ACÇÕES -->
    <div class="ef-actions">
        <button type="submit" class="ef-btn-save">
            <i class="bi bi-check-circle-fill"></i>
            <?= $isEdit ? 'Guardar Alterações' : 'Criar Ouvinte' ?>
        </button>
        <a href="<?= $isEdit ? "/public/pulso/{$stationId}/ouvintes/{$id}/ficha" : "/public/pulso/{$stationId}/ouvintes" ?>"
           class="ef-btn-cancel">
            <i class="bi bi-x-circle"></i> Cancelar
        </a>
    </div>

</form>
</div>

<script>
// Toggle visual dos géneros
function toggleGenero(el) {
    const cb = el.querySelector('input[type=checkbox]');
    const total = document.querySelectorAll('#generos-wrap input:checked').length;

    if (!cb.checked && total >= 5) {
        document.getElementById('generos-hint').style.color = '#ef4444';
        setTimeout(() => document.getElementById('generos-hint').style.color = '', 1500);
        return;
    }

    cb.checked = !cb.checked;
    el.classList.toggle('checked', cb.checked);
    document.getElementById('generos-count').textContent =
        document.querySelectorAll('#generos-wrap input:checked').length;
    document.getElementById('generos-hint').style.color = '';
}

// Toggle visual dos radio buttons de género
document.querySelectorAll('#radios-genero .ef-radio').forEach(function(el) {
    el.addEventListener('click', function() {
        document.querySelectorAll('#radios-genero .ef-radio').forEach(r => r.classList.remove('checked'));
        el.classList.add('checked');
        el.querySelector('input').checked = true;
    });
});

// Toggle província Angola vs outro país
function toggleProvincia() {
    const pais = document.getElementById('sel-pais').value;
    const wrap = document.getElementById('wrap-provincia');
    const isAngola = pais === 'Angola';
    const current = wrap.querySelector('input,select').value;

    if (isAngola) {
        wrap.innerHTML = '<div class="ef-label">Província</div><select name="provincia" class="ef-select"><option value="">Selecione...</option><?php foreach($provincias as $prov): ?><option value="<?= $prov ?>"><?= $prov ?></option><?php endforeach; ?></select>';
    } else {
        wrap.innerHTML = '<div class="ef-label">Província / Estado</div><input type="text" name="provincia" class="ef-input" value="' + current + '" placeholder="Estado / Região">';
    }
}
</script>
