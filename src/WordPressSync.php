<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin;

/**
 * RNB WordPress Sync Service
 * Sincroniza plugin_prog_* ↔ WordPress (shows + members)
 * Sentido: WordPress → BD (fonte de verdade é o WP)
 */
class WordPressSync
{
    private \PDO    $pdo;
    private int     $sid;
    private string  $wpBase = 'https://radionewband.ao/wp-json/wp/v2';

    public function __construct(int $stationId = 1)
    {
        $this->sid = $stationId;
        $this->pdo = new \PDO(
            'mysql:host=127.0.0.1;dbname=azuracast;charset=utf8mb4',
            'azuracast', 'CKxR234fxpJG',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    // ── SYNC MEMBERS → LOCUTORES ──────────────────────────────
    public function syncLocutores(): array
    {
        $result = ['synced'=>0,'created'=>0,'errors'=>[]];

        $members = $this->wpGet('/members?per_page=50&_fields=id,title,slug,content,excerpt,featured_media,link');
        if(!$members) { $result['errors'][] = 'WordPress members não acessível'; return $result; }

        foreach($members as $m) {
            try {
                $wpId   = (int)$m['id'];
                $nome   = $m['title']['rendered'] ?? '';
                $bio    = strip_tags($m['content']['rendered'] ?? '');
                $foto   = $m['featured_media'] ? $this->getMediaUrl((int)$m['featured_media']) : null;

                $existe = $this->pdo->prepare(
                    "SELECT id FROM plugin_prog_locutores WHERE station_id=? AND wp_post_id=?"
                );
                $existe->execute([$this->sid, $wpId]);
                $lid = $existe->fetchColumn();

                if($lid) {
                    $this->pdo->prepare(
                        "UPDATE plugin_prog_locutores
                         SET nome=?, bio=?, foto=COALESCE(?,foto), updated_at=NOW()
                         WHERE id=?"
                    )->execute([$nome, $bio, $foto, $lid]);
                    $result['synced']++;
                } else {
                    $this->pdo->prepare(
                        "INSERT INTO plugin_prog_locutores
                         (station_id,nome,bio,foto,ativo,wp_post_id,created_at,updated_at)
                         VALUES (?,?,?,?,1,?,NOW(),NOW())"
                    )->execute([$this->sid,$nome,$bio,$foto,$wpId]);
                    $result['created']++;
                }
            } catch(\Exception $e) {
                $result['errors'][] = "Locutor {$m['id']}: ".$e->getMessage();
            }
        }
        return $result;
    }

    // ── SYNC SHOWS → PROGRAMAS ────────────────────────────────
    public function syncProgramas(): array
    {
        $result = ['synced'=>0,'created'=>0,'errors'=>[]];

        $shows = $this->wpGet('/shows?per_page=50&_fields=id,title,slug,content,excerpt,featured_media,link,status');
        if(!$shows) { $result['errors'][] = 'WordPress shows não acessível'; return $result; }

        foreach($shows as $s) {
            try {
                $wpId    = (int)$s['id'];
                $nome    = $s['title']['rendered'] ?? '';
                $desc    = strip_tags($s['content']['rendered'] ?? '');
                $descCurta = strip_tags($s['excerpt']['rendered'] ?? '');
                $banner  = $s['featured_media'] ? $this->getMediaUrl((int)$s['featured_media']) : null;
                $ativo   = ($s['status'] ?? '') === 'publish' ? 1 : 0;
                $slug    = $s['slug'] ?? '';

                $existe = $this->pdo->prepare(
                    "SELECT id FROM plugin_prog_programas WHERE station_id=? AND wp_post_id=?"
                );
                $existe->execute([$this->sid, $wpId]);
                $pid = $existe->fetchColumn();

                if($pid) {
                    $this->pdo->prepare(
                        "UPDATE plugin_prog_programas
                         SET nome=?, descricao=?,
                             banner=CASE WHEN ? IS NOT NULL AND ? != '' THEN ? ELSE banner END,
                             ativo=?, updated_at=NOW()
                         WHERE id=?"
                    )->execute([$nome,$desc,$banner,$banner,$banner,$ativo,$pid]);
                    $result['synced']++;
                } else {
                    // Novo programa — sem horário ainda (definir no módulo de programação)
                    $this->pdo->prepare(
                        "INSERT INTO plugin_prog_programas
                         (station_id,nome,descricao,banner,hora_inicio,hora_fim,
                          dias_semana,ativo,wp_post_id,created_at,updated_at)
                         VALUES (?,?,?,?,'00:00','23:59','[]',?,?,NOW(),NOW())"
                    )->execute([$this->sid,$nome,$desc,$banner,$ativo,$wpId]);
                    $result['created']++;
                }
            } catch(\Exception $e) {
                $result['errors'][] = "Programa {$s['id']}: ".$e->getMessage();
            }
        }
        return $result;
    }

    // ── SYNC COMPLETO ─────────────────────────────────────────
    public function syncAll(): array
    {
        $locs  = $this->syncLocutores();
        $progs = $this->syncProgramas();
        return [
            'timestamp'  => date('Y-m-d H:i:s'),
            'locutores'  => $locs,
            'programas'  => $progs,
            'total_sync' => $locs['synced'] + $progs['synced'],
            'total_new'  => $locs['created'] + $progs['created'],
            'errors'     => array_merge($locs['errors'], $progs['errors']),
        ];
    }

    // ── HELPERS ───────────────────────────────────────────────
    private function wpGet(string $path): ?array
    {
        $ctx = stream_context_create(['http'=>['timeout'=>10,'ignore_errors'=>true]]);
        $r   = @file_get_contents($this->wpBase.$path, false, $ctx);
        if(!$r) return null;
        $d = json_decode($r, true);
        return is_array($d) ? $d : null;
    }

    private function getMediaUrl(int $mediaId): ?string
    {
        if(!$mediaId) return null;
        $r = $this->wpGet("/media/{$mediaId}?_fields=source_url");
        return $r['source_url'] ?? null;
    }
}
