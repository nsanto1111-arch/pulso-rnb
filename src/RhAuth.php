<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin;

class RhAuth
{
    private const COOKIE   = 'rnb_rh_token';
    private const DURATION = 28800;

    private const PERMISSIONS = [
        'admin'    => ['*'],
        'director' => ['rh.view','rh.edit','rh.salary.view','rh.documents.view','rh.performance.view','escalas.view','ferias.view','ferias.approve','contratos.view','financeiro.view','relatorios.view'],
        'rh'       => ['rh.view','rh.edit','rh.salary.view','rh.documents.view','rh.documents.upload','escalas.view','escalas.edit','ferias.view','ferias.edit','ferias.approve','contratos.view','contratos.edit','relatorios.view'],
        'locutor'  => ['rh.view','escalas.view','rh.self.view'],
        'comercial'=> ['rh.view','escalas.view','rh.self.view'],
        'tecnico'  => ['rh.view','escalas.view','rh.self.view'],
    ];

    public static function login(array $user, \Doctrine\DBAL\Connection $db): string
    {
        $token = bin2hex(random_bytes(32));
        try {
            $db->executeStatement("DELETE FROM rnb_rh_tokens WHERE user_id=? OR expires_at < NOW()", [$user['id']]);
            $db->insert('rnb_rh_tokens', [
                'token'      => $token,
                'user_id'    => (int)$user['id'],
                'station_id' => (int)$user['station_id'],
                'expires_at' => date('Y-m-d H:i:s', time() + self::DURATION),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch(\Exception $e) {}
        return $token;
    }

    public static function setCookie(string $token): void
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
                   || ($_SERVER['SERVER_PORT'] ?? '') === '443';

        setcookie(self::COOKIE, $token, [
            'expires'  => time() + self::DURATION,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function getUser(\Doctrine\DBAL\Connection $db): ?array
    {
        $token = $_COOKIE[self::COOKIE] ?? '';
        if(!$token || strlen($token) !== 64 || !ctype_xdigit($token)) return null;
        try {
            $row = $db->fetchAssociative(
                "SELECT t.*, u.username, u.nome, u.role, u.funcionario_id
                 FROM rnb_rh_tokens t
                 JOIN rnb_rh_users u ON u.id = t.user_id
                 WHERE t.token = ? AND t.expires_at > NOW() AND u.activo = 1",
                [$token]
            );
        } catch(\Exception $e) { return null; }
        if(!$row) return null;
        return [
            'id'      => (int)$row['user_id'],
            'username'=> $row['username'],
            'role'    => $row['role'],
            'nome'    => $row['nome'],
            'station' => (int)$row['station_id'],
            'func_id' => $row['funcionario_id'] ? (int)$row['funcionario_id'] : null,
        ];
    }

    public static function logout(\Doctrine\DBAL\Connection $db): void
    {
        $token = $_COOKIE[self::COOKIE] ?? '';
        if($token) {
            try { $db->executeStatement("DELETE FROM rnb_rh_tokens WHERE token=?", [$token]); }
            catch(\Exception $e) {}
        }
        setcookie(self::COOKIE, '', time() - 3600, '/');
    }

    public static function can(string $perm, ?array $user): bool
    {
        if(!$user) return false;
        $perms = self::PERMISSIONS[$user['role']] ?? [];
        if(in_array('*', $perms)) return true;
        return in_array($perm, $perms);
    }

    public static function canViewFuncionario(int $funcId, ?array $user): bool
    {
        if(!$user) return false;
        if(in_array($user['role'], ['admin','director','rh'])) return true;
        return $user['func_id'] && (int)$user['func_id'] === $funcId;
    }

    public static function denyHtml(string $msg = 'Não tem permissão para aceder a esta página.'): string
    {
        return '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>Acesso Negado</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
        <style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:Inter,sans-serif;background:#F7F9FB;display:flex;align-items:center;justify-content:center;min-height:100vh}
        .box{background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:40px;max-width:400px;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,.08)}
        h1{font-size:18px;font-weight:700;color:#0B1220;margin-bottom:8px}p{font-size:14px;color:#6B7280;margin-bottom:24px}
        a{display:inline-flex;padding:8px 20px;background:#2563EB;color:#fff;border-radius:6px;font:600 13px Inter,sans-serif;text-decoration:none}
        a:hover{background:#1D4ED8}</style></head>
        <body><div class="box"><h1>Acesso Negado</h1><p>' . htmlspecialchars($msg) . '</p><a href="javascript:history.back()">Voltar</a></div></body></html>';
    }

    public static function log(\Doctrine\DBAL\Connection $db, array $user, string $accao, string $recurso = ''): void
    {
        try {
            $db->insert('rnb_rh_access_log', [
                'station_id' => $user['station'] ?? 1,
                'user_id'    => $user['id'],
                'username'   => $user['username'],
                'accao'      => $accao,
                'recurso'    => substr($recurso, 0, 200),
                'ip'         => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch(\Exception $e) {}
    }
}
