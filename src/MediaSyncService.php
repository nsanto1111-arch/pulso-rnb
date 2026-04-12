<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin;

/**
 * RNB Media Sync Service
 * Fonte de verdade: MusicMaster (cutID + metadados)
 * Audio: Myriad (local) + AzuraCast (streaming)
 * Sincronização: MusicMaster → rnb_catalogo_musical ← AzuraCast
 */
class MediaSyncService
{
    private \PDO $pdo;
    private int  $sid;

    // Dicionários de decode MusicMaster → nomes completos
    private const GENERO = [
        'A'=>'Afrobeat','B'=>'Kuduro','C'=>'Sertanejo','D'=>'R&B',
        'E'=>'Electrónico/Dance','H'=>'Hip-Hop/Rap','K'=>'Kizomba',
        'N'=>'Reggaeton','P'=>'Pop','R'=>'Rock','S'=>'Semba',
        'Ã'=>'House','Ç'=>'Soul','Í'=>'Afropop',
    ];
    private const SUB_GENERO = [
        'A'=>'Amapiano','B'=>'Ballad','C'=>'Contemporary R&B','D'=>'Dance Pop',
        'H'=>'Hard Rock','I'=>'Soul Pop','P'=>'Pop Rock','R'=>'Rock Alternativo',
        'S'=>'Soft Rock','X'=>'Soundtrack','Y'=>'Slow Jam','Z'=>'West Coast Hip-Hop',
        'Â'=>'Pop Rap','Ç'=>'Dark Pop','Ñ'=>'Trap Pop','Õ'=>'R&B Alternativo',
    ];
    private const HUMOR = [
        'C'=>'Cool','E'=>'Energética','F'=>'Feliz','I'=>'Uplifting',
        'R'=>'Romântica','S'=>'Chill','T'=>'Triste',
    ];
    private const IDIOMA = [
        'K'=>'Kimbundo','P'=>'Português','I'=>'Inglês',
        'F'=>'Francês','E'=>'Espanhol','T'=>'Italiano',
    ];
    private const VOCAL = [
        'F'=>'Feminino','I'=>'Instrumental','M'=>'Masculino',
        'N'=>'Não-Binário','V'=>'Misto',
    ];
    private const TIPO_ARTISTA = [
        'A'=>'Artista Solo','B'=>'Banda','C'=>'Coral/Conjunto','D'=>'Dueto',
        'G'=>'Group','J'=>'DJ/Produtor','N'=>'Boy Band','P'=>'Dupla',
        'V'=>'Varios','Z'=>'Girl Band','Ç'=>'Participação',
    ];
    private const NACIONALIDADE = [
        'A'=>'Angolana','B'=>'Brasileira','D'=>'Dinamarquesa','F'=>'Francesa',
        'H'=>'Haitiana','I'=>'Italiana','N'=>'Nigeriana','R'=>'Reino Unido',
        'S'=>'Sul Africana','T'=>'Trinidiana','U'=>'Norte Americana','Â'=>'Alemanha',
    ];
    private const TIPO_MUSICA = [
        'C'=>'Cover','H'=>'Hit/Clássico','L'=>'Live','M'=>'Remix',
        'N'=>'Lançamento','O'=>'Oldie (10+ anos)','R'=>'Recente (1-5 anos)',
    ];
    private const TEMA = [
        'C'=>'Conexão Íntima','D'=>'Desejo','L'=>'Luto','O'=>'Mudança',
        'P'=>'Perda','R'=>'Relaxamento Emocional','S'=>'Saudade','T'=>'Traição',
        'V'=>'Violência','W'=>'Racismo','X'=>'Desejo Proibido','Y'=>'Provocação',
        'Z'=>'Sensualidade','Ã'=>'Atração Romântica','Ç'=>'Conexão Espiritual',
        'Í'=>'Recomeços','Ñ'=>'Amor Juvenil','Õ'=>'Separação',
    ];
    private const DECADA = [
        '7'=>'70s','8'=>'80s','9'=>'90s','0'=>'00s','1'=>'10s','2'=>'20s',
    ];
    private const TAG = [
        'C'=>'Cleaned for Radio','D'=>'Radio','L'=>'Canção Longa',
        'P'=>'Conteúdo Sensível','R'=>'Released','Y'=>'Política',
    ];

    public function __construct(int $stationId = 1)
    {
        $this->sid = $stationId;
        $this->pdo = new \PDO(
            'mysql:host=127.0.0.1;dbname=azuracast;charset=utf8mb4',
            'azuracast','CKxR234fxpJG',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    // ── IMPORTAR DO MYRIAD VIA BRIDGE ─────────────────────────
    public function syncFromMyriad(): array
    {
        $result = ['total'=>0,'novos'=>0,'actualizados'=>0,'errors'=>[]];

        // Obter biblioteca do Myriad via Bridge
        $myriad = BridgeClient::myriadHistory(500, date('Y-m-d'));

        // Obter catálogo completo do Myriad (Media table)
        $bridge = $this->bridgeGet('/media?limit=2000');
        if(!$bridge || empty($bridge['media'])) {
            $result['errors'][] = 'Bridge não acessível ou sem dados de media';
            return $result;
        }

        foreach($bridge['media'] as $m) {
            try {
                $result['total']++;
                $cutId = $m['ExternalReference'] ?? $m['MediaId'] ?? null;
                if(!$cutId) continue;

                $titulo  = $m['DisplayAs'] ?? $m['ItemTitle'] ?? '';
                $artista = $m['DisplayBy'] ?? $m['ArtistName'] ?? '';
                $duracao = (float)($m['Length'] ?? 0);

                $existe = $this->pdo->prepare(
                    "SELECT id FROM rnb_catalogo_musical WHERE cut_id=? AND station_id=?"
                );
                $existe->execute([(string)$cutId, $this->sid]);
                $eid = $existe->fetchColumn();

                if($eid) {
                    $this->pdo->prepare(
                        "UPDATE rnb_catalogo_musical
                         SET titulo=?, artista=?, duracao_seg=?, em_myriad=1,
                             myriad_media_id=?, sincronizado_em=NOW()
                         WHERE id=?"
                    )->execute([$titulo, $artista, $duracao, $m['MediaId']??null, $eid]);
                    $result['actualizados']++;
                } else {
                    $this->pdo->prepare(
                        "INSERT INTO rnb_catalogo_musical
                         (station_id,cut_id,titulo,artista,duracao_seg,
                          em_myriad,myriad_media_id,sincronizado_em,created_at)
                         VALUES (?,?,?,?,?,1,?,NOW(),NOW())"
                    )->execute([
                        $this->sid,(string)$cutId,$titulo,$artista,
                        $duracao,$m['MediaId']??null
                    ]);
                    $result['novos']++;
                }
            } catch(\Exception $e) {
                $result['errors'][] = 'Myriad: '.$e->getMessage();
            }
        }
        return $result;
    }

    // ── SINCRONIZAR COM AZURACAST ─────────────────────────────
    public function syncFromAzuraCast(): array
    {
        $result = ['total'=>0,'matched'=>0,'sem_match'=>0,'errors'=>[]];

        $stmt = $this->pdo->query(
            "SELECT id,title,artist,album,genre,length,path,isrc,unique_id
             FROM station_media ORDER BY id"
        );
        $medias = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach($medias as $m) {
            $result['total']++;
            $titulo  = $this->normalizar($m['title']);
            $artista = $this->normalizar($m['artist']);

            // Tentar match por título + artista
            $match = $this->pdo->prepare(
                "SELECT id FROM rnb_catalogo_musical
                 WHERE station_id=?
                   AND LOWER(titulo) LIKE ?
                   AND LOWER(artista) LIKE ?
                 LIMIT 1"
            );
            $match->execute([
                $this->sid,
                '%'.strtolower($titulo).'%',
                '%'.strtolower($artista).'%',
            ]);
            $cid = $match->fetchColumn();

            if($cid) {
                $this->pdo->prepare(
                    "UPDATE rnb_catalogo_musical
                     SET azuracast_id=?, em_azuracast=1, sincronizado_em=NOW()
                     WHERE id=?"
                )->execute([$m['id'], $cid]);
                $result['matched']++;
            } else {
                // Não existe no catálogo — inserir como nova entrada
                try {
                    $this->pdo->prepare(
                        "INSERT IGNORE INTO rnb_catalogo_musical
                         (station_id,cut_id,titulo,artista,duracao_seg,
                          azuracast_id,em_azuracast,em_myriad,created_at)
                         VALUES (?,?,?,?,?,?,1,0,NOW())"
                    )->execute([
                        $this->sid,
                        'AZ'.$m['id'],
                        $m['title'],
                        $m['artist'],
                        (float)$m['length'],
                        $m['id'],
                    ]);
                    $result['sem_match']++;
                } catch(\Exception $e) {}
            }
        }
        return $result;
    }

    // ── RELATÓRIO DE DIVERGÊNCIA ──────────────────────────────
    public function getDivergencias(): array
    {
        // Músicas só no AzuraCast (não estão no Myriad)
        $soAz = $this->pdo->prepare(
            "SELECT id,titulo,artista,duracao_seg
             FROM rnb_catalogo_musical
             WHERE station_id=? AND em_azuracast=1 AND em_myriad=0
             ORDER BY artista,titulo LIMIT 100"
        );
        $soAz->execute([$this->sid]);

        // Músicas só no Myriad (não estão no AzuraCast)
        $soMyriad = $this->pdo->prepare(
            "SELECT id,titulo,artista,duracao_seg
             FROM rnb_catalogo_musical
             WHERE station_id=? AND em_myriad=1 AND em_azuracast=0
             ORDER BY artista,titulo LIMIT 100"
        );
        $soMyriad->execute([$this->sid]);

        // Músicas em ambos (sincronizadas)
        $ambos = $this->pdo->prepare(
            "SELECT COUNT(*) FROM rnb_catalogo_musical
             WHERE station_id=? AND em_myriad=1 AND em_azuracast=1"
        );
        $ambos->execute([$this->sid]);

        // Totais
        $totais = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(em_myriad) AS em_myriad,
                SUM(em_azuracast) AS em_azuracast,
                SUM(em_myriad=1 AND em_azuracast=1) AS em_ambos,
                SUM(em_myriad=0 AND em_azuracast=1) AS so_azuracast,
                SUM(em_myriad=1 AND em_azuracast=0) AS so_myriad
             FROM rnb_catalogo_musical WHERE station_id=?"
        );
        $totais->execute([$this->sid]);

        return [
            'totais'      => $totais->fetch(\PDO::FETCH_ASSOC),
            'so_azuracast'=> $soAz->fetchAll(\PDO::FETCH_ASSOC),
            'so_myriad'   => $soMyriad->fetchAll(\PDO::FETCH_ASSOC),
            'em_ambos'    => (int)$ambos->fetchColumn(),
        ];
    }

    // ── IMPORTAR DO MUSICMASTER (via export TSV/CSV) ──────────
    public function importFromMusicMasterTSV(string $filepath): array
    {
        $result = ['total'=>0,'novos'=>0,'actualizados'=>0,'errors'=>[]];
        if(!file_exists($filepath)) {
            $result['errors'][] = "Ficheiro não encontrado: $filepath";
            return $result;
        }

        $lines = file($filepath, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        $headers = null;

        foreach($lines as $line) {
            $cols = str_getcsv($line, "\t");
            if(!$headers) { $headers = $cols; continue; }
            if(count($cols) < 3) continue;

            $row = array_combine(array_slice($headers,0,count($cols)), $cols);
            $result['total']++;

            try {
                $cutId = trim($row['cutID'] ?? $row['CutID'] ?? '');
                if(!$cutId) continue;

                // Parse intro (:03 → 3 segundos)
                $introRaw = trim($row['intro'] ?? '');
                $introSeg = 0;
                if(preg_match('/^:?(\d+)$/', $introRaw, $m)) $introSeg = (int)$m[1];

                // Parse duração (03:31 → 211 segundos)
                $durRaw = trim($row['Duração'] ?? $row['time'] ?? '');
                $durSeg = 0;
                if(preg_match('/^(\d+):(\d+)$/', $durRaw, $m))
                    $durSeg = (int)$m[1]*60+(int)$m[2];

                $data = [
                    'station_id'       => $this->sid,
                    'cut_id'           => $cutId,
                    'titulo'           => trim($row['titulo'] ?? $row['title'] ?? ''),
                    'artista'          => trim($row['Artista'] ?? $row['artist'] ?? ''),
                    'artista_keywords' => trim($row['Artista keywords'] ?? ''),
                    'ano'              => (int)($row['ano'] ?? $row['year'] ?? 0) ?: null,
                    'duracao_seg'      => $durSeg ?: null,
                    'intro_seg'        => $introSeg ?: null,
                    'end_type'         => trim($row['end'] ?? '') ?: null,
                    'categoria'        => trim($row['categoria'] ?? '') ?: null,
                    'genero'           => $g = trim($row['genero musical'] ?? '') ?: null,
                    'genero_nome'      => $g ? (self::GENERO[$g] ?? null) : null,
                    'sub_genero'       => $sg = trim($row['sub genero'] ?? '') ?: null,
                    'sub_genero_nome'  => $sg ? (self::SUB_GENERO[$sg] ?? null) : null,
                    'tipo_musica'      => $tm = trim($row['tipo de musica'] ?? '') ?: null,
                    'tipo_musica_nome' => $tm ? (self::TIPO_MUSICA[$tm] ?? null) : null,
                    'idioma'           => $id = trim($row['edioma'] ?? $row['idioma'] ?? '') ?: null,
                    'idioma_nome'      => $id ? (self::IDIOMA[$id] ?? null) : null,
                    'vocal'            => trim($row['vocal'] ?? '') ?: null,
                    'humor'            => $h = trim($row['humor'] ?? '') ?: null,
                    'humor_nome'       => $h ? (self::HUMOR[$h] ?? null) : null,
                    'decada'           => $d = trim($row['decada'] ?? '') ?: null,
                    'decada_nome'      => $d ? (self::DECADA[$d] ?? null) : null,
                    'tempo'            => ($t=trim($row['tempo'] ?? '')) ? (int)$t : null,
                    'energia'          => ($e=trim($row['energia'] ?? '')) ? (int)$e : null,
                    'tipo_artista'     => $ta = trim($row['tipo de artista'] ?? '') ?: null,
                    'tipo_artista_nome'=> $ta ? (self::TIPO_ARTISTA[$ta] ?? null) : null,
                    'nacionalidade'    => $n = trim($row['Nacionalidade'] ?? '') ?: null,
                    'nacionalidade_nome'=> $n ? (self::NACIONALIDADE[$n] ?? null) : null,
                    'tema'             => $te = trim($row['tema'] ?? '') ?: null,
                    'tema_nome'        => $te ? (self::TEMA[$te] ?? null) : null,
                    'tag'              => trim($row['tag'] ?? '') ?: null,
                    'em_myriad'        => 1,
                    'sincronizado_em'  => date('Y-m-d H:i:s'),
                ];

                $existe = $this->pdo->prepare(
                    "SELECT id FROM rnb_catalogo_musical WHERE cut_id=? AND station_id=?"
                );
                $existe->execute([$cutId, $this->sid]);
                $eid = $existe->fetchColumn();

                if($eid) {
                    $sets = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
                    $vals = array_values($data);
                    $vals[] = $eid;
                    $this->pdo->prepare("UPDATE rnb_catalogo_musical SET $sets WHERE id=?")
                        ->execute($vals);
                    $result['actualizados']++;
                } else {
                    $keys = implode(',', array_keys($data));
                    $ph   = implode(',', array_fill(0, count($data), '?'));
                    $this->pdo->prepare("INSERT INTO rnb_catalogo_musical ($keys) VALUES ($ph)")
                        ->execute(array_values($data));
                    $result['novos']++;
                }
            } catch(\Exception $e) {
                $result['errors'][] = ($row['cutID']??'?').': '.$e->getMessage();
            }
        }
        return $result;
    }

    // ── ANÁLISE DO CATÁLOGO ───────────────────────────────────
    public function getAnalise(): array
    {
        $total = (int)$this->pdo->prepare(
            "SELECT COUNT(*) FROM rnb_catalogo_musical WHERE station_id=?"
        )->execute([$this->sid]) ? $this->pdo->query(
            "SELECT COUNT(*) FROM rnb_catalogo_musical WHERE station_id={$this->sid}"
        )->fetchColumn() : 0;

        // Por género
        $porGenero = $this->pdo->prepare(
            "SELECT COALESCE(genero_nome,'Desconhecido') AS genero,
                    COUNT(*) AS total
             FROM rnb_catalogo_musical WHERE station_id=?
             GROUP BY genero_nome ORDER BY total DESC"
        );
        $porGenero->execute([$this->sid]);

        // Por idioma
        $porIdioma = $this->pdo->prepare(
            "SELECT COALESCE(idioma_nome,'Desconhecido') AS idioma,
                    COUNT(*) AS total
             FROM rnb_catalogo_musical WHERE station_id=?
             GROUP BY idioma_nome ORDER BY total DESC"
        );
        $porIdioma->execute([$this->sid]);

        // Por nacionalidade
        $porNac = $this->pdo->prepare(
            "SELECT COALESCE(nacionalidade_nome,'Desconhecida') AS nac,
                    COUNT(*) AS total
             FROM rnb_catalogo_musical WHERE station_id=?
             GROUP BY nacionalidade_nome ORDER BY total DESC"
        );
        $porNac->execute([$this->sid]);

        // Por humor
        $porHumor = $this->pdo->prepare(
            "SELECT COALESCE(humor_nome,'Desconhecido') AS humor,
                    COUNT(*) AS total
             FROM rnb_catalogo_musical WHERE station_id=?
             GROUP BY humor_nome ORDER BY total DESC"
        );
        $porHumor->execute([$this->sid]);

        // Nacional vs Internacional
        $nacional = $this->pdo->prepare(
            "SELECT COUNT(*) FROM rnb_catalogo_musical
             WHERE station_id=? AND nacionalidade='A'"
        );
        $nacional->execute([$this->sid]);

        return [
            'total'          => (int)$total,
            'por_genero'     => $porGenero->fetchAll(\PDO::FETCH_ASSOC),
            'por_idioma'     => $porIdioma->fetchAll(\PDO::FETCH_ASSOC),
            'por_nac'        => $porNac->fetchAll(\PDO::FETCH_ASSOC),
            'por_humor'      => $porHumor->fetchAll(\PDO::FETCH_ASSOC),
            'total_angola'   => (int)$nacional->fetchColumn(),
        ];
    }

    // ── PESQUISA INTELIGENTE ──────────────────────────────────
    public function pesquisar(array $filtros = [], int $limit = 50): array
    {
        $where = ['station_id=?'];
        $params = [$this->sid];

        if(!empty($filtros['genero'])) {
            $where[] = 'genero=?';
            $params[] = $filtros['genero'];
        }
        if(!empty($filtros['humor'])) {
            $where[] = 'humor=?';
            $params[] = $filtros['humor'];
        }
        if(!empty($filtros['idioma'])) {
            $where[] = 'idioma=?';
            $params[] = $filtros['idioma'];
        }
        if(!empty($filtros['energia_min'])) {
            $where[] = 'energia>=?';
            $params[] = (int)$filtros['energia_min'];
        }
        if(!empty($filtros['energia_max'])) {
            $where[] = 'energia<=?';
            $params[] = (int)$filtros['energia_max'];
        }
        if(!empty($filtros['vocal'])) {
            $where[] = 'vocal=?';
            $params[] = $filtros['vocal'];
        }
        if(!empty($filtros['nacionalidade'])) {
            $where[] = 'nacionalidade=?';
            $params[] = $filtros['nacionalidade'];
        }
        if(!empty($filtros['artista'])) {
            $where[] = 'artista LIKE ?';
            $params[] = '%'.$filtros['artista'].'%';
        }
        if(!empty($filtros['titulo'])) {
            $where[] = 'titulo LIKE ?';
            $params[] = '%'.$filtros['titulo'].'%';
        }

        $sql = "SELECT * FROM rnb_catalogo_musical
                WHERE ".implode(' AND ',$where)."
                ORDER BY artista,titulo LIMIT $limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // ── HELPERS ───────────────────────────────────────────────
    private function normalizar(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    private function bridgeGet(string $path): ?array
    {
        $ctx = stream_context_create(['http'=>[
            'timeout'=>5,
            'header' =>"X-RNB-Token: rnb_bridge_2026_seguro\r\n",
        ]]);
        $r = @file_get_contents('http://100.65.58.5:8765'.$path, false, $ctx);
        return $r ? json_decode($r, true) : null;
    }
}
