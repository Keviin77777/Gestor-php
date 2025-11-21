<?php
/**
 * Script para verificar se o menu de administração está sendo renderizado para admin
 * Acesse: https://ultragestor.site/debug-sidebar-render-admin.php
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Auth.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Renderização Sidebar Admin</title>
    <style>
        body {
            font-family: monospace;
            background: #1e293b;
            color: #f1f5f9;
            padding: 2rem;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #0f172a;
            padding: 2rem;
            border-radius: 12px;
        }
        h1 { color: #6366f1; margin-bottom: 2rem; }
        h2 { color: #818cf8; margin-top: 2rem; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        pre {
            background: #334155;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            color: #cbd5e1;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 500px;
            overflow-y: auto;
        }
        .section {
            background: #1e293b;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #6366f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug - Renderização do Menu Admin</h1>
        
        <?php
        // Simular o contexto do sidebar
        $currentUser = Auth::user();
        $isAdmin = false;
        $userPlan = null;
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        $currentPath = parse_url($currentPath, PHP_URL_PATH);
        
        if ($currentUser) {
            $userFromDB = Database::fetch(
                "SELECT * FROM users WHERE id = ? OR email = ?",
                [$currentUser['id'] ?? '', $currentUser['email'] ?? '']
            );
            
            if ($userFromDB) {
                $currentUser = array_merge($currentUser, $userFromDB);
                
                $isAdmin = false;
                $role = strtolower(trim($userFromDB['role'] ?? ''));
                if ($role === 'admin') {
                    $isAdmin = true;
                }
                
                if (!$isAdmin && isset($userFromDB['is_admin'])) {
                    $isAdminValue = $userFromDB['is_admin'];
                    if ($isAdminValue === 1 || $isAdminValue === true || $isAdminValue === '1' || $isAdminValue === 1.0) {
                        $isAdmin = true;
                    }
                }
            }
        }
        
        echo '<div class="section">';
        echo '<h2>1️⃣ Variável $isAdmin</h2>';
        echo '<p class="' . ($isAdmin ? 'success' : 'error') . '">$isAdmin = ' . ($isAdmin ? 'TRUE' : 'FALSE') . '</p>';
        echo '</div>';
        
        // Capturar a renderização da sidebar
        ob_start();
        include __DIR__ . '/../app/views/components/sidebar.php';
        $sidebarHtml = ob_get_clean();
        
        // Verificar se contém o menu de administração
        $hasAdminMenu = strpos($sidebarHtml, 'Administração') !== false;
        $hasAdminSubmenu = strpos($sidebarHtml, 'admin-submenu') !== false;
        $hasResellersLink = strpos($sidebarHtml, '/admin/resellers') !== false;
        $hasIfAdmin = strpos($sidebarHtml, 'if ($isAdmin)') !== false || strpos($sidebarHtml, 'if($isAdmin)') !== false;
        
        echo '<div class="section">';
        echo '<h2>2️⃣ Verificação de Renderização</h2>';
        
        if ($hasAdminMenu) {
            echo '<p class="success">✅ Menu "Administração" ESTÁ sendo renderizado no HTML</p>';
        } else {
            echo '<p class="error">❌ Menu "Administração" NÃO está sendo renderizado</p>';
        }
        
        if ($hasAdminSubmenu) {
            echo '<p class="success">✅ Submenu "admin-submenu" ESTÁ presente</p>';
        } else {
            echo '<p class="error">❌ Submenu "admin-submenu" NÃO está presente</p>';
        }
        
        if ($hasResellersLink) {
            echo '<p class="success">✅ Link "/admin/resellers" ESTÁ presente</p>';
        } else {
            echo '<p class="error">❌ Link "/admin/resellers" NÃO está presente</p>';
        }
        
        echo '</div>';
        
        // Mostrar parte do HTML renderizado (apenas a parte do menu admin)
        echo '<div class="section">';
        echo '<h2>3️⃣ HTML Renderizado (parte do menu admin)</h2>';
        
        // Extrair apenas a parte do menu admin
        if (preg_match('/(<!-- Menu para Admin.*?<\/div>\s*<\/div>\s*<\?php endif; \?>)/s', $sidebarHtml, $matches)) {
            echo '<pre>' . htmlspecialchars($matches[1]) . '</pre>';
        } else {
            echo '<p class="error">❌ Não foi possível encontrar o bloco do menu admin no HTML</p>';
            // Mostrar um trecho do HTML para debug
            $pos = strpos($sidebarHtml, 'Dashboard');
            if ($pos !== false) {
                $snippet = substr($sidebarHtml, $pos, 2000);
                echo '<pre>' . htmlspecialchars($snippet) . '</pre>';
            }
        }
        
        echo '</div>';
        
        // Verificar se há CSS escondendo
        echo '<div class="section">';
        echo '<h2>4️⃣ Verificação de CSS</h2>';
        echo '<p>Se o menu está no HTML mas não aparece, pode ser CSS escondendo.</p>';
        echo '<p>Abra o DevTools (F12) e inspecione o elemento com id="admin-submenu"</p>';
        echo '</div>';
        ?>
        
        <div class="section" style="border-left-color: #f59e0b;">
            <h2>⚠️ REMOVER APÓS DIAGNÓSTICO</h2>
            <p class="warning">Este arquivo expõe código do sistema!</p>
            <pre>rm public/debug-sidebar-render-admin.php</pre>
        </div>
    </div>
</body>
</html>

