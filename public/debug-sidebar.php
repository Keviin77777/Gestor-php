<?php
// Debug da sidebar
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== DEBUG SIDEBAR ===\n\n";

$currentUser = Auth::user();
echo "1. Auth::user():\n";
var_dump($currentUser);

if ($currentUser) {
    $userFromDB = Database::fetch(
        "SELECT * FROM users WHERE id = ? OR email = ?",
        [$currentUser['id'] ?? '', $currentUser['email'] ?? '']
    );
    
    echo "\n2. Database::fetch():\n";
    var_dump($userFromDB);
    
    if ($userFromDB) {
        echo "\n3. Verificação de admin:\n";
        echo "   - role no banco: '" . ($userFromDB['role'] ?? 'NULL') . "'\n";
        echo "   - is_admin no banco: " . var_export($userFromDB['is_admin'] ?? null, true) . "\n";
        
        $role = strtolower(trim($userFromDB['role'] ?? ''));
        echo "   - role (lowercase): '" . $role . "'\n";
        
        $isAdmin = false;
        if ($role === 'admin') {
            $isAdmin = true;
            echo "   - Verificação por role: TRUE\n";
        } else {
            echo "   - Verificação por role: FALSE\n";
        }
        
        if (!$isAdmin && isset($userFromDB['is_admin'])) {
            $isAdminValue = $userFromDB['is_admin'];
            if ($isAdminValue === 1 || $isAdminValue === true || $isAdminValue === '1' || $isAdminValue === 1.0) {
                $isAdmin = true;
                echo "   - Verificação por is_admin: TRUE\n";
            } else {
                echo "   - Verificação por is_admin: FALSE (valor: " . var_export($isAdminValue, true) . ")\n";
            }
        }
        
        echo "\n4. RESULTADO FINAL:\n";
        echo "   - isAdmin: " . ($isAdmin ? 'TRUE' : 'FALSE') . "\n";
        
        echo "\n5. Dados da sessão:\n";
        var_dump($_SESSION['user'] ?? 'NÃO DEFINIDO');
    }
} else {
    echo "\nERRO: Nenhum usuário autenticado!\n";
}


