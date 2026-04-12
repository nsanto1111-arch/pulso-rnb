<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin;

use Doctrine\DBAL\Connection;

/**
 * RNB Portal do Anunciante — Sistema de Autenticação
 * Token em BD + Cookie (sem $_SESSION)
 */
class PortalAuth
{
    private const COOKIE = 'RNB_PORTAL';
    private const DURATION = 28800; // 8 horas

    // ─── LOGIN ────────────────────────────────────────────────────────────────
    public static function login(array $user, Connection $db): string
    {        
        $token = bin2hex(random_bytes(32));        
        $expires = date('Y-m-d H:i:s', time() + self::DURATION);
        // Limpar tokens antigos do utilizador
        try {
            $db->executeStatement("DELETE FROM rnb_portal_tokens WHERE user_id=?", [$user['id']]);        } catch (\Exception $e) {        }

        // Criar novo token
        try {            
            $db->insert('rnb_portal_tokens', [
                'token' => $token,
                'user_id' => $user['id'],
                'anunciante_id' => $user['anunciante_id'],
                'station_id' => $user['station_id'] ?? 1,
                'expires_at' => $expires,
            ]);        } catch (\Exception $e) {            throw $e;
        }

        // Actualizar último acesso
        $db->executeStatement(
            "UPDATE rnb_portal_users SET ultimo_acesso=NOW() WHERE id=?",
            [$user['id']]
        );
        // Definir cookie        self::setCookie($token);
        // Log de acesso
        self::log($db, $user, 'login');
        return $token;
    }

    // ─── DEFINIR COOKIE ───────────────────────────────────────────────────────
    public static function setCookie(string $token): void
    {
        setcookie(
            self::COOKIE,
            $token,
            time() + self::DURATION,
            '/',
            '',
            true,  // secure
            true   // httponly
        );
    }

    // ─── OBTER UTILIZADOR ATUAL ───────────────────────────────────────────────
    public static function getUser(Connection $db): ?array
    {
        $token = $_COOKIE[self::COOKIE] ?? null;
        if (!$token) return null;

        try {
            $stmt = $db->prepare(
                "SELECT u.*, t.anunciante_id, t.expires_at
                 FROM rnb_portal_tokens t
                 JOIN rnb_portal_users u ON u.id = t.user_id
                 WHERE t.token = ? AND t.expires_at > NOW() AND u.activo = 1"
            );
            $stmt->bindValue(1, $token);
            $result = $stmt->executeQuery();
            $user = $result->fetchAssociative();

            if (!$user) {
                self::clearCookie();
                return null;
            }

            return $user;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ─── LOGOUT ───────────────────────────────────────────────────────────────
    public static function logout(Connection $db): void
    {
        $token = $_COOKIE[self::COOKIE] ?? null;
        if ($token) {
            try {
                $db->executeStatement("DELETE FROM rnb_portal_tokens WHERE token=?", [$token]);
            } catch (\Exception $e) {}
        }
        self::clearCookie();
    }

    // ─── LIMPAR COOKIE ────────────────────────────────────────────────────────
    private static function clearCookie(): void
    {
        setcookie(self::COOKIE, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    // ─── VERIFICAR AUTENTICAÇÃO ───────────────────────────────────────────────
    public static function isLoggedIn(Connection $db): bool
    {
        return self::getUser($db) !== null;
    }

    // ─── MENSAGEM DE ACESSO NEGADO ────────────────────────────────────────────
    public static function denyHtml(string $msg = 'Acesso negado. Por favor faça login.'): string
    {
        return <<<HTML
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Acesso Negado</title>
<style>
body{font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0f172a}
.box{background:#1e293b;padding:2rem;border-radius:8px;text-align:center;max-width:400px}
h1{color:#ef4444;margin:0 0 1rem}
p{color:#94a3b8;margin:0 0 1.5rem}
a{display:inline-block;background:#3b82f6;color:#fff;padding:0.75rem 1.5rem;border-radius:6px;text-decoration:none;font-weight:600}
a:hover{background:#2563eb}
</style></head><body>
<div class="box"><h1>⛔ Acesso Negado</h1><p>{$msg}</p><a href="/public/portal/login">Fazer Login</a></div>
</body></html>
HTML;
    }

    // ─── LOG DE ACESSO ────────────────────────────────────────────────────────
    public static function log(Connection $db, array $user, string $accao, string $recurso = ''): void
    {
        try {
            $db->insert('rnb_portal_access_log', [
                'station_id' => $user['station_id'] ?? 1,
                'user_id' => $user['id'],
                'anunciante_id' => $user['anunciante_id'],
                'username' => $user['username'],
                'accao' => $accao,
                'recurso' => substr($recurso, 0, 200),
                'ip' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        } catch (\Exception $e) {}
    }
}
