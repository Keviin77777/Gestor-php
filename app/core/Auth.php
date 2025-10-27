<?php
/**
 * Sistema de autenticação JWT
 */

// Incluir dependências se não estiverem carregadas
if (!class_exists('Request')) {
    require_once __DIR__ . '/Request.php';
}

class Auth {
    /**
     * Gerar token JWT
     */
    public static function generateToken(array $payload): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload['iat'] = time();
        $payload['exp'] = time() + (7 * 24 * 60 * 60); // 7 dias
        
        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode(json_encode($payload));
        
        $signature = hash_hmac(
            'sha256',
            $base64UrlHeader . "." . $base64UrlPayload,
            env('JWT_SECRET'),
            true
        );
        
        $base64UrlSignature = self::base64UrlEncode($signature);
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Validar token JWT
     */
    public static function validateToken(string $token): array|false {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            error_log("JWT: Token inválido - não tem 3 partes");
            return false;
        }
        
        list($base64UrlHeader, $base64UrlPayload, $base64UrlSignature) = $parts;
        
        $signature = self::base64UrlDecode($base64UrlSignature);
        $expectedSignature = hash_hmac(
            'sha256',
            $base64UrlHeader . "." . $base64UrlPayload,
            env('JWT_SECRET'),
            true
        );
        
        if (!hash_equals($signature, $expectedSignature)) {
            error_log("JWT: Assinatura inválida");
            return false;
        }
        
        $payload = json_decode(self::base64UrlDecode($base64UrlPayload), true);
        
        if (!isset($payload['exp'])) {
            error_log("JWT: Token sem expiração");
            return false;
        }
        
        if ($payload['exp'] < time()) {
            error_log("JWT: Token expirado");
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Verificar se usuário está autenticado
     */
    public static function check(): bool {
        $token = Request::bearerToken();
        
        if (!$token) {
            return false;
        }
        
        $payload = self::validateToken($token);
        return $payload !== false;
    }
    
    /**
     * Obter usuário autenticado
     */
    public static function user(): ?array {
        // Iniciar sessão se não estiver iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Primeiro tentar sessão PHP
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        
        // Fallback para JWT se existir
        $token = Request::bearerToken();
        
        if (!$token) {
            return null;
        }
        
        $payload = self::validateToken($token);
        
        if (!$payload) {
            return null;
        }
        
        // Salvar na sessão para próximas requisições
        $_SESSION['user'] = $payload;
        
        return $payload;
    }
    
    /**
     * Requerer autenticação
     */
    public static function requireAuth(): array {
        $user = self::user();
        
        if (!$user) {
            Response::error('Não autorizado', 401);
        }
        
        return $user;
    }
    
    /**
     * Verificar permissão
     */
    public static function checkPermission(string $role): bool {
        $user = self::user();
        
        if (!$user) {
            return false;
        }
        
        return isset($user['role']) && $user['role'] === $role;
    }
    
    /**
     * Hash de senha
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    /**
     * Verificar senha
     */
    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }
    
    /**
     * Login do usuário (salvar na sessão)
     */
    public static function login(array $user): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user'] = $user;
    }
    
    /**
     * Logout
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['user']);
        session_destroy();
    }
    
    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
