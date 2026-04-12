<?php
declare(strict_types=1);
namespace Plugin\ProgramacaoPlugin;

/**
 * RNB Security Layer
 * Rate limiting, brute force, CSRF, XSS, headers
 */
class Security
{
    private \PDO $pdo;

    // Limites por módulo
    private const LIMITS = [
        'portal' => ['tentativas' => 5,  'janela_min' => 15, 'bloqueio_min' => 60],
        'rh'     => ['tentativas' => 5,  'janela_min' => 10, 'bloqueio_min' => 30],
        'api'    => ['tentativas' => 20, 'janela_min' => 1,  'bloqueio_min' => 10],
    ];

    public function __construct()
    {
        $this->pdo = new \PDO(
            'mysql:host=127.0.0.1;dbname=azuracast;charset=utf8mb4',
            'azuracast', 'CKxR234fxpJG',
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
        );
    }

    // ── HEADERS DE SEGURANÇA ──────────────────────────────────
    public static function setHeaders(): void
    {
        // Prevenir XSS
        header("X-XSS-Protection: 1; mode=block");
        // Prevenir clickjacking
        header("X-Frame-Options: SAMEORIGIN");
        // Prevenir MIME sniffing
        header("X-Content-Type-Options: nosniff");
        // HTTPS obrigatório
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self' https://fonts.googleapis.com https://fonts.gstatic.com https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; img-src 'self' data: https:;");
        // Referrer
        header("Referrer-Policy: strict-origin-when-cross-origin");
        // Permissions
        header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
        // Remover headers que revelam stack
        header_remove('X-Powered-By');
        header_remove('Server');
    }

    // ── IP DETECTION ──────────────────────────────────────────
    public static function getIp(): string
    {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP']    // Cloudflare
           ?? $_SERVER['HTTP_X_FORWARDED_FOR']
           ?? $_SERVER['HTTP_X_REAL_IP']
           ?? $_SERVER['REMOTE_ADDR']
           ?? '0.0.0.0';
        // Pegar só o primeiro IP se houver lista
        $ip = trim(explode(',', $ip)[0]);
        // Validar
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    // ── RATE LIMITING ─────────────────────────────────────────
    public function checkRateLimit(string $modulo, string $ip): bool
    {
        $cfg = self::LIMITS[$modulo] ?? self::LIMITS['portal'];

        // Verificar se está bloqueado
        $bloq = $this->pdo->prepare(
            "SELECT bloqueado_ate, tentativas FROM rnb_security_bloqueios
             WHERE ip=? AND modulo=? AND bloqueado_ate>NOW()"
        );
        $bloq->execute([$ip, $modulo]);
        $b = $bloq->fetch(\PDO::FETCH_ASSOC);

        if($b) {
            $this->log('ip_blocked', $modulo, $ip, "IP bloqueado até {$b['bloqueado_ate']} ({$b['tentativas']} tentativas)");
            return false; // bloqueado
        }

        // Contar tentativas falhadas recentes
        $count = $this->pdo->prepare(
            "SELECT COUNT(*) FROM rnb_security_log
             WHERE ip=? AND modulo=? AND tipo='login_fail'
             AND created_at > NOW() - INTERVAL ? MINUTE"
        );
        $count->execute([$ip, $modulo, $cfg['janela_min']]);
        $nFails = (int)$count->fetchColumn();

        if($nFails >= $cfg['tentativas']) {
            // Bloquear IP
            $this->pdo->prepare(
                "INSERT INTO rnb_security_bloqueios (ip, modulo, motivo, tentativas, bloqueado_ate)
                 VALUES (?,?,?,?,NOW() + INTERVAL ? MINUTE)
                 ON DUPLICATE KEY UPDATE tentativas=tentativas+1,
                 bloqueado_ate=NOW() + INTERVAL ? MINUTE"
            )->execute([
                $ip, $modulo,
                "Brute force: $nFails tentativas em {$cfg['janela_min']} minutos",
                $nFails,
                $cfg['bloqueio_min'],
                $cfg['bloqueio_min'],
            ]);
            $this->log('brute_force', $modulo, $ip, "Bloqueado por $nFails tentativas falhadas");
            return false;
        }

        return true; // permitido
    }

    // ── LOGGING ───────────────────────────────────────────────
    public function log(string $tipo, string $modulo, string $ip, string $detalhe = '', string $ref = ''): void
    {
        try {
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);
            $this->pdo->prepare(
                "INSERT INTO rnb_security_log (tipo, modulo, ip, user_agent, referencia, detalhe)
                 VALUES (?,?,?,?,?,?)"
            )->execute([$tipo, $modulo, $ip, $ua, $ref, $detalhe]);
        } catch(\Exception $e) {}
    }

    // ── CSRF ──────────────────────────────────────────────────
    public function generateCsrf(string $modulo): string
    {
        $token      = bin2hex(random_bytes(32));
        $sessionKey = bin2hex(random_bytes(16));

        // Guardar cookie de sessão CSRF
        setcookie("rnb_csrf_$modulo", $sessionKey, [
            'expires'  => time()+3600,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        $this->pdo->prepare(
            "INSERT INTO rnb_csrf_tokens (token, session_key, modulo, expires_at)
             VALUES (?,?,?, NOW() + INTERVAL 1 HOUR)"
        )->execute([$token, $sessionKey, $modulo]);

        // Limpar tokens expirados
        $this->pdo->exec("DELETE FROM rnb_csrf_tokens WHERE expires_at < NOW()");

        return $token;
    }

    public function verifyCsrf(string $token, string $modulo): bool
    {
        $sessionKey = $_COOKIE["rnb_csrf_$modulo"] ?? '';
        if(!$token || !$sessionKey) return false;

        $r = $this->pdo->prepare(
            "SELECT id FROM rnb_csrf_tokens
             WHERE token=? AND session_key=? AND modulo=?
             AND expires_at>NOW() AND usado=0"
        );
        $r->execute([$token, $sessionKey, $modulo]);
        $id = $r->fetchColumn();

        if($id) {
            // Marcar como usado (single use)
            $this->pdo->prepare("UPDATE rnb_csrf_tokens SET usado=1 WHERE id=?")->execute([$id]);
            return true;
        }
        return false;
    }

    // ── SANITIZAÇÃO ───────────────────────────────────────────
    public static function clean(string $input): string
    {
        $input = trim($input);
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES|ENT_HTML5, 'UTF-8');
        return $input;
    }

    public static function cleanCode(string $input): string
    {
        // Só letras maiúsculas e números para códigos de acesso
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($input))));
    }

    // ── VALIDAÇÃO DE TOKEN DE SESSÃO ──────────────────────────
    public static function validateToken(string $token): bool
    {
        // Token deve ter exactamente 64 chars hex
        return (bool)preg_match('/^[a-f0-9]{64}$/', $token);
    }

    // ── RELATÓRIO DE SEGURANÇA ────────────────────────────────
    public function getReport(): array
    {
        $bloqueios = $this->pdo->query(
            "SELECT ip, modulo, tentativas, bloqueado_ate
             FROM rnb_security_bloqueios
             WHERE bloqueado_ate > NOW()
             ORDER BY criado_em DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $falhas24h = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM rnb_security_log
             WHERE tipo='login_fail' AND created_at > NOW() - INTERVAL 24 HOUR"
        )->fetchColumn();

        $bruteForce = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM rnb_security_log
             WHERE tipo='brute_force' AND created_at > NOW() - INTERVAL 24 HOUR"
        )->fetchColumn();

        $topIps = $this->pdo->query(
            "SELECT ip, COUNT(*) AS t FROM rnb_security_log
             WHERE tipo='login_fail' AND created_at > NOW() - INTERVAL 24 HOUR
             GROUP BY ip ORDER BY t DESC LIMIT 5"
        )->fetchAll(\PDO::FETCH_ASSOC);

        return [
            'bloqueios_activos' => $bloqueios,
            'falhas_24h'        => $falhas24h,
            'brute_force_24h'   => $bruteForce,
            'top_ips_suspeitos'  => $topIps,
        ];
    }
}
