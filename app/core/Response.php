<?php
/**
 * Manipulação de respostas HTTP
 */
class Response {
    /**
     * Retornar resposta JSON
     */
    public static function json(array $data, int $status = 200): void {
        // Limpar qualquer output anterior
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Redirecionar
     */
    public static function redirect(string $url, int $status = 302): void {
        http_response_code($status);
        header("Location: $url");
        exit;
    }
    
    /**
     * Retornar erro
     */
    public static function error(string $message, int $status = 400): void {
        self::json(['error' => $message], $status);
    }
    
    /**
     * Retornar sucesso
     */
    public static function success(string $message, array $data = []): void {
        self::json(array_merge(['success' => true, 'message' => $message], $data));
    }
}
