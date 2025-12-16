<?php
/**
 * Router para servidor PHP embutido
 * Adiciona CORS em todas as requisições
 */

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Se for requisição OPTIONS (preflight), retornar 200 e sair
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obter o arquivo solicitado
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

// Se for um arquivo PHP, executar
if (file_exists($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
    return false; // Deixa o PHP executar o arquivo
}

// Se for um arquivo estático e existir, servir
if (file_exists($file) && is_file($file)) {
    return false;
}

// Se não encontrou, retornar 404
http_response_code(404);
echo json_encode(['error' => 'Endpoint não encontrado']);
