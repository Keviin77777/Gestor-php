<?php
/**
 * Helper para autenticação em APIs públicas
 */

require_once __DIR__ . '/../core/Auth.php';

/**
 * Obter usuário autenticado ou retornar erro
 */
function getAuthenticatedUser() {
    $user = Auth::user();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Não autorizado']);
        exit;
    }
    return $user;
}

/**
 * Verificar se usuário é admin
 */
function isAdmin($user = null) {
    if (!$user) {
        $user = getAuthenticatedUser();
    }
    return $user['role'] === 'admin';
}