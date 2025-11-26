<?php
/**
 * Teste simples de DELETE
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? 'não fornecido';

echo json_encode([
    'success' => true,
    'message' => 'Teste de DELETE',
    'method' => $method,
    'id' => $id,
    'get' => $_GET,
    'server' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
        'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? ''
    ]
]);
