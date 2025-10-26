<?php
/**
 * Sistema de roteamento
 */
class Router {
    private array $routes = [];
    
    /**
     * Registrar rota GET
     */
    public function get(string $path, callable $handler): void {
        $this->addRoute('GET', $path, $handler);
    }
    
    /**
     * Registrar rota POST
     */
    public function post(string $path, callable $handler): void {
        $this->addRoute('POST', $path, $handler);
    }
    
    /**
     * Registrar rota PUT
     */
    public function put(string $path, callable $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }
    
    /**
     * Registrar rota DELETE
     */
    public function delete(string $path, callable $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }
    
    /**
     * Adicionar rota
     */
    private function addRoute(string $method, string $path, callable $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    /**
     * Despachar requisição
     */
    public function dispatch(string $method, string $uri): void {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri)) {
                call_user_func($route['handler']);
                return;
            }
        }
        
        // Rota não encontrada
        Response::json(['error' => 'Rota não encontrada'], 404);
    }
    
    /**
     * Verificar se path corresponde à URI
     */
    private function matchPath(string $pattern, string $uri): bool {
        // Converter padrão para regex
        $pattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $uri) === 1;
    }
}
