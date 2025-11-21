<?php
/**
 * Script para verificar se o menu de administração está sendo renderizado
 * Acesse: http://seu-dominio.com/debug-sidebar-render.php
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
    <title>Debug - Renderização Sidebar</title>
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
        }
        .section {
            background: #1e293b;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #6366f1;
        }
        .code-block {
            background: #0f172a;
            border: 1px solid #334155;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug - Renderização do Menu Admin</h1>
        
        <?php
        // Capturar a renderização da sidebar
        ob_start();
        include __DIR__ . '/../app/views/components/sidebar.php';
        $sidebarHtml = ob_get_clean();
        
        // Verificar se contém o menu de administração
        $hasAdminMenu = strpos($sidebarHtml, 'Administração') !== false;
        $hasAdminSubmenu = strpos($sidebarHtml, 'admin-submenu') !== false;
        $hasResellersLink = strpos($sidebarHtml, '/admin/resellers') !== false;
        
        echo '<div class="section">';
        echo '<h2>1️⃣ Verificação de Renderização</h2>';
        
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
        
        // Mostrar o HTML renderizado
        echo '<div class="section">';
        echo '<h2>2️⃣ HTML Renderizado da Sidebar</h2>';
        echo '<div class="code-block">';
        echo '<pre>' . htmlspecialchars($sidebarHtml) . '</pre>';
        echo '</div>';
        echo '</div>';
        
        // Verificar variável $isAdmin
        echo '<div class="section">';
        echo '<h2>3️⃣ Variável $isAdmin no Sidebar</h2>';
        
        // Extrair o valor de $isAdmin do código
        if (preg_match('/\$isAdmin\s*=\s*(true|false|1|0)/', $sidebarHtml, $matches)) {
            echo '<p>Valor encontrado no código: <strong>' . $matches[1] . '</strong></p>';
        }
        
        // Verificar se o bloco PHP do admin está presente
        if (strpos($sidebarHtml, '<?php if ($isAdmin): ?>') !== false || 
            strpos($sidebarHtml, 'if ($isAdmin)') !== false) {
            echo '<p class="success">✅ Condição "if ($isAdmin)" encontrada no código</p>';
        } else {
            echo '<p class="warning">⚠️ Condição "if ($isAdmin)" NÃO encontrada (pode estar processada)</p>';
        }
        
        echo '</div>';
        
        // Verificar arquivos CSS
        echo '<div class="section">';
        echo '<h2>4️⃣ Verificação de Arquivos CSS</h2>';
        
        $cssFiles = [
            'public/assets/css/dashboard.css',
            'public/assets/css/admin-responsive.css',
        ];
        
        foreach ($cssFiles as $file) {
            $fullPath = __DIR__ . '/../' . str_replace('public/', '', $file);
            if (file_exists($fullPath)) {
                $size = filesize($fullPath);
                $modified = date('Y-m-d H:i:s', filemtime($fullPath));
                echo '<p class="success">✅ ' . $file . '</p>';
                echo '<p style="margin-left: 2rem;">Tamanho: ' . number_format($size) . ' bytes | Modificado: ' . $modified . '</p>';
            } else {
                echo '<p class="error">❌ ' . $file . ' NÃO encontrado</p>';
            }
        }
        
        echo '</div>';
        
        // Verificar se há CSS escondendo o menu
        echo '<div class="section">';
        echo '<h2>5️⃣ Buscar CSS que pode estar escondendo o menu</h2>';
        
        $dashboardCss = file_get_contents(__DIR__ . '/assets/css/dashboard.css');
        
        // Procurar por regras que podem esconder elementos
        $hidingRules = [
            'display:\s*none',
            'visibility:\s*hidden',
            'opacity:\s*0',
            'height:\s*0',
            'max-height:\s*0'
        ];
        
        $foundIssues = [];
        foreach ($hidingRules as $rule) {
            if (preg_match_all('/' . $rule . '/i', $dashboardCss, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $position = $match[1];
                    $context = substr($dashboardCss, max(0, $position - 100), 200);
                    $foundIssues[] = [
                        'rule' => $match[0],
                        'context' => $context
                    ];
                }
            }
        }
        
        if (count($foundIssues) > 0) {
            echo '<p class="warning">⚠️ Encontradas ' . count($foundIssues) . ' regras CSS que podem esconder elementos</p>';
            echo '<p style="font-size: 0.875rem;">Mostrando primeiras 5:</p>';
            foreach (array_slice($foundIssues, 0, 5) as $issue) {
                echo '<div class="code-block">';
                echo '<pre>' . htmlspecialchars($issue['context']) . '</pre>';
                echo '</div>';
            }
        } else {
            echo '<p class="success">✅ Nenhuma regra CSS suspeita encontrada</p>';
        }
        
        echo '</div>';
        
        // Recomendações finais
        echo '<div class="section">';
        echo '<h2>6️⃣ Recomendações</h2>';
        
        if ($hasAdminMenu) {
            echo '<p class="success">✅ O menu ESTÁ sendo renderizado no servidor!</p>';
            echo '<p class="warning">⚠️ Se você não vê o menu no navegador, o problema é:</p>';
            echo '<ul>';
            echo '<li><strong>Cache do navegador:</strong> Pressione Ctrl+Shift+R para recarregar sem cache</li>';
            echo '<li><strong>CSS escondendo:</strong> Inspecione o elemento no navegador (F12) e veja se há "display: none"</li>';
            echo '<li><strong>JavaScript removendo:</strong> Verifique o console do navegador (F12) por erros</li>';
            echo '</ul>';
            
            echo '<h3>🔧 Teste Rápido:</h3>';
            echo '<p>Abra o console do navegador (F12) e execute:</p>';
            echo '<pre>document.querySelector("#admin-submenu").style.display = "block";</pre>';
            echo '<p>Se o menu aparecer, o problema é CSS. Se não aparecer, o elemento não existe no DOM.</p>';
        } else {
            echo '<p class="error">❌ O menu NÃO está sendo renderizado!</p>';
            echo '<p>Isso significa que a variável $isAdmin está FALSE no sidebar.php</p>';
            echo '<p>Verifique o arquivo: app/views/components/sidebar.php</p>';
        }
        
        echo '</div>';
        ?>
        
        <div class="section" style="border-left-color: #f59e0b;">
            <h2>⚠️ REMOVER APÓS DIAGNÓSTICO</h2>
            <p class="warning">Este arquivo expõe código do sistema!</p>
            <pre>rm public/debug-sidebar-render.php</pre>
        </div>
    </div>
</body>
</html>
