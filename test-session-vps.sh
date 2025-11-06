#!/bin/bash
# Teste de sessão PHP na VPS

cd /www/wwwroot/ultragestor.site/Gestor/public

# Criar arquivo de teste de sessão
cat > test_session.php << 'EOF'
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE DE SESSAO PHP ===\n";
echo "session.save_path: " . session_save_path() . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "\n";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";

// Simular usuário na sessão
$_SESSION['user'] = [
    'id' => 'test-user-123',
    'email' => 'test@test.com',
    'name' => 'Test User',
    'role' => 'reseller'
];

echo "\nSessão iniciada - ID: " . session_id() . "\n";
echo "Dados na sessão: " . json_encode($_SESSION) . "\n";

// Testar Auth::user()
require_once '../app/core/Auth.php';
$user = Auth::user();
if ($user) {
    echo "\n✓ Auth::user() retornou dados: " . json_encode($user) . "\n";
} else {
    echo "\n✗ Auth::user() retornou null\n";
}
EOF

php test_session.php
rm -f test_session.php

