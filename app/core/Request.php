<?php
/**
 * Manipulação de requisições HTTP
 */

// Incluir funções auxiliares se não estiverem carregadas
if (!function_exists('sanitize')) {
    require_once __DIR__ . '/../helpers/functions.php';
}

class Request {
    /**
     * Obter método HTTP
     */
    public static function method(): string {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Obter URI
     */
    public static function uri(): string {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    
    /**
     * Obter todos os dados da requisição
     */
    public static function all(): array {
        $data = [];
        
        if (self::method() === 'GET') {
            $data = $_GET;
        } else {
            $input = file_get_contents('php://input');
            $json = json_decode($input, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $json;
            } else {
                $data = $_POST;
            }
        }
        
        return sanitize($data);
    }
    
    /**
     * Obter valor específico
     */
    public static function get(string $key, $default = null) {
        $data = self::all();
        return $data[$key] ?? $default;
    }
    
    /**
     * Verificar se chave existe
     */
    public static function has(string $key): bool {
        $data = self::all();
        return isset($data[$key]);
    }
    
    /**
     * Obter apenas campos específicos
     */
    public static function only(array $keys): array {
        $data = self::all();
        return array_intersect_key($data, array_flip($keys));
    }
    
    /**
     * Obter header
     */
    public static function header(string $key, $default = null) {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? $default;
    }
    
    /**
     * Obter token de autorização
     */
    public static function bearerToken(): ?string {
        $header = self::header('Authorization');
        
        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}
