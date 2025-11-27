<?php
/**
 * Script para verificar erros de PHP no arquivo parear.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Verificando arquivo parear.php ===\n\n";

$file = __DIR__ . '/app/views/whatsapp/parear.php';

if (!file_exists($file)) {
    echo "❌ Arquivo não encontrado: $file\n";
    exit(1);
}

echo "✓ Arquivo encontrado\n";
echo "✓ Tamanho: " . filesize($file) . " bytes\n\n";

// Verificar sintaxe PHP
$output = [];
$return = 0;
exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);

echo "=== Verificação de Sintaxe ===\n";
echo implode("\n", $output) . "\n\n";

if ($return === 0) {
    echo "✅ Sintaxe PHP está correta!\n\n";
} else {
    echo "❌ Erro de sintaxe encontrado!\n\n";
    exit(1);
}

// Tentar incluir o arquivo
echo "=== Tentando processar o arquivo ===\n";
ob_start();
try {
    include $file;
    $content = ob_get_clean();
    
    if (strpos($content, '<div class="rate-limit-section">') !== false) {
        echo "✅ Seção de rate limiting encontrada no HTML gerado\n";
    } else {
        echo "⚠️  Seção de rate limiting NÃO encontrada no HTML\n";
    }
    
    if (strpos($content, 'Configuração de Limites de Envio') !== false) {
        echo "✅ Título encontrado no HTML\n";
    } else {
        echo "⚠️  Título NÃO encontrado no HTML\n";
    }
    
    echo "\n✅ Arquivo processado sem erros!\n";
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Erro ao processar: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Verificação concluída ===\n";
