<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin;

/**
 * RNB Signal Layer — Cliente unificado Myriad + AzuraCast
 * Detecta automaticamente a fonte activa e retorna dados normalizados.
 */
class BridgeClient
{
    private const BRIDGE_URL   = 'http://100.65.58.5:8765';
    private const BRIDGE_TOKEN = 'rnb_bridge_2026_seguro';
    private const AZ_API_KEY   = '62beaa587a281c68:c3e116e7fe103515f089aadcb44a5a6c';
    private const AZ_BASE      = 'http://localhost';
    private const STATION_ID   = 1;
    private const TIMEOUT      = 3; // segundos

    // ── FONTE ACTIVA ──────────────────────────────────────────
    /**
     * Detecta o estado real de emissão.
     * Retorna: 'live' | 'myriad' | 'azuracast'
     *
     * 'live'      — Locutor conectado via SamCast/streamer (AzuraCast is_live=true)
     * 'myriad'    — Myriad Playout a emitir (PlayLogs activos)
     * 'azuracast' — Automação AzuraCast (fallback)
     */
    public static function detectSource(): string
    {
        // Prioridade 1: Locutor ao vivo via SamCast/streamer
        $az = self::azuracastNowPlaying();
        if (!empty($az['is_live']) && $az['is_live'] === true) {
            return 'live';
        }

        // Prioridade 2: Myriad com actividade recente (últimos 5 min)
        $np = self::myriadNowPlaying();
        if ($np && ($np['status'] ?? '') === 'on_air') {
            $inicio = strtotime($np['inicio'] ?? '');
            if ($inicio && (time() - $inicio) < 300) {
                return 'myriad';
            }
        }

        // Prioridade 3: AzuraCast automático
        return 'azuracast';
    }

    // ── NOW PLAYING UNIFICADO ─────────────────────────────────
    public static function nowPlaying(): array
    {
        $source = self::detectSource();
        $az     = self::azuracastNowPlaying();

        // ESTADO 1: LOCUTOR AO VIVO (SamCast porta 8005)
        if ($source === 'live') {
            $myr    = self::myriadNowPlaying();
            $titulo = ($myr && ($myr['status'] ?? '') === 'on_air')
                      ? ($myr['titulo']  ?? 'Emissao em Directo')
                      : ($az['titulo']   ?? 'Emissao em Directo');
            $artista= ($myr && ($myr['status'] ?? '') === 'on_air')
                      ? ($myr['artista'] ?? '')
                      : ($az['artista']  ?? '');
            return [
                'fonte'     => 'live',
                'on_air'    => true,
                'ao_vivo'   => true,
                'titulo'    => $titulo,
                'artista'   => $artista,
                'tipo'      => 'live',
                'duracao'   => 0,
                'progresso' => 0,
                'locutor'   => $az['locutor_live'] ?? '',
                'ano'       => null,
                'inicio'    => $az['inicio'] ?? '',
                'referencia'=> '',
                'e_sweeper' => false,
                'listeners' => (int)($az['listeners'] ?? 0),
            ];
        }

        // ESTADO 2: MYRIAD PLAYOUT
        if ($source === 'myriad') {
            $data = self::myriadNowPlaying();
            if ($data && ($data['status'] ?? '') === 'on_air') {
                return [
                    'fonte'     => 'myriad',
                    'on_air'    => true,
                    'ao_vivo'   => false,
                    'titulo'    => $data['titulo']    ?? 'Desconhecido',
                    'artista'   => $data['artista']   ?? '',
                    'tipo'      => $data['tipo']      ?? 'musica',
                    'duracao'   => (int)($data['duracao']   ?? 0),
                    'progresso' => (int)($data['progresso'] ?? 0),
                    'locutor'   => $data['locutor']   ?? '',
                    'ano'       => $data['ano']       ?? null,
                    'inicio'    => $data['inicio']    ?? '',
                    'referencia'=> $data['referencia'] ?? '',
                    'e_sweeper' => (bool)($data['e_sweeper'] ?? false),
                    'listeners' => (int)($az['listeners'] ?? 0),
                ];
            }
        }

        // ESTADO 3: AZURACAST AUTOMATICO (fallback)
        return [
            'fonte'     => 'azuracast',
            'on_air'    => true,
            'ao_vivo'   => false,
            'titulo'    => $az['titulo']  ?? 'Automatico',
            'artista'   => $az['artista'] ?? '',
            'tipo'      => 'musica',
            'duracao'   => (int)($az['duracao']   ?? 0),
            'progresso' => (int)($az['progresso'] ?? 0),
            'locutor'   => '',
            'ano'       => null,
            'inicio'    => $az['inicio']  ?? '',
            'referencia'=> '',
            'e_sweeper' => false,
            'listeners' => (int)($az['listeners'] ?? 0),
        ];
    }

    // ── MYRIAD ────────────────────────────────────────────────
    public static function myriadNowPlaying(): ?array
    {
        $r = self::bridgeGet('/now-playing');
        return $r ?: null;
    }

    public static function myriadHistory(int $limit = 10, string $date = ''): array
    {
        $date = $date ?: date('Y-m-d');
        $r = self::bridgeGet("/history?limit={$limit}&date={$date}");
        return $r['history'] ?? [];
    }

    public static function myriadCommercials(string $date = ''): array
    {
        $date = $date ?: date('Y-m-d');
        $r = self::bridgeGet("/commercials?date={$date}");
        return $r['commercials'] ?? [];
    }

    public static function myriadStatus(): array
    {
        $r = self::bridgeGet('/status');
        return $r ?: ['status' => 'offline', 'database' => 'error'];
    }

    // ── AZURACAST ─────────────────────────────────────────────
    public static function azuracastNowPlaying(): array
    {
        $r = self::azGet('/api/nowplaying/'.self::STATION_ID);
        if (!$r) return [];

        $np = $r['now_playing'] ?? [];
        $sh = $np['song'] ?? [];

        return [
            'titulo'    => $sh['title']  ?? 'Automático',
            'artista'   => $sh['artist'] ?? '',
            'album'     => $sh['album']  ?? '',
            'duracao'   => (int)($np['duration']  ?? 0),
            'progresso' => (int)($np['elapsed']   ?? 0),
            'inicio'    => $np['played_at'] ? date('Y-m-d\TH:i:s', $np['played_at']) : '',
            'listeners' => $r['listeners']['current'] ?? 0,
            'is_live'   => $r['live']['is_live'] ?? false,
            'locutor_live' => $r['live']['streamer_name'] ?? '',
        ];
    }

    public static function azuracastListeners(): int
    {
        $r = self::azGet('/api/nowplaying/'.self::STATION_ID);
        return (int)($r['listeners']['current'] ?? 0);
    }

    public static function azuracastHistory(int $limit = 10): array
    {
        $r = self::azGet('/api/station/'.self::STATION_ID.'/history');
        if (!$r) return [];
        return array_slice($r, 0, $limit);
    }

    // ── STATUS GERAL DO SISTEMA ───────────────────────────────
    public static function systemStatus(): array
    {
        $myriad = self::myriadStatus();
        $source = self::detectSource();
        $az     = self::azuracastNowPlaying();

        return [
            'fonte_activa'  => $source,
            'myriad_online' => ($myriad['status'] ?? '') === 'online',
            'myriad_bd'     => ($myriad['database'] ?? '') === 'ok',
            'az_listeners'  => $az['listeners'] ?? 0,
            'az_is_live'    => $az['is_live'] ?? false,
            'timestamp'     => date('Y-m-d\TH:i:s'),
        ];
    }

    // ── HTTP HELPERS ──────────────────────────────────────────
    private static function bridgeGet(string $path): ?array
    {
        $url = self::BRIDGE_URL . $path;
        $ctx = stream_context_create(['http' => [
            'timeout' => self::TIMEOUT,
            'header'  => "X-RNB-Token: ".self::BRIDGE_TOKEN."\r\n",
        ]]);
        $r = @file_get_contents($url, false, $ctx);
        return $r ? json_decode($r, true) : null;
    }

    private static function azGet(string $path): ?array
    {
        $url = self::AZ_BASE . $path;
        $ctx = stream_context_create(['http' => [
            'timeout' => self::TIMEOUT,
            'header'  => "X-API-Key: ".self::AZ_API_KEY."\r\n",
        ]]);
        $r = @file_get_contents($url, false, $ctx);
        return $r ? json_decode($r, true) : null;
    }
}
