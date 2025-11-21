<?php
/**
 * Script para verificar configuração EFI Bank
 */

require_once __DIR__ . '/app/helpers/functions.php';
loadEnv(__DIR__ . '/.env');

require_once __DIR__ . '/app/core/Database.php';

try {
    $db = Database::connect();
    
    // Buscar configuração
    $stmt = $db->prepare("
        SELECT method_name, enabled, config_value 
        FROM payment_methods 
        WHERE method_name = 'efibank'
    ");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        echo "❌ EFI Bank não está configurado no banco de dados\n";
        exit(1);
    }
    
    echo "✅ EFI Bank encontrado no banco\n";
    echo "Status: " . ($result['enabled'] ? 'ATIVO' : 'INATIVO') . "\n\n";
    
    $config = json_decode($result['config_value'], true);
    
    echo "=== CONFIGURAÇÃO ===\n";
    echo "Client ID: " . (empty($config['client_id']) ? '❌ VAZIO' : '✅ ' . substr($config['client_id'], 0, 20) . '...') . "\n";
    echo "Client Secret: " . (empty($config['client_secret']) ? '❌ VAZIO' : '✅ ' . substr($config['client_secret'], 0, 20) . '...') . "\n";
    echo "Certificado: " . (empty($config['certificate']) ? '❌ VAZIO' : '✅ ' . $config['certificate']) . "\n";
    echo "Chave PIX: " . (empty($config['pix_key']) ? '❌ VAZIO' : '✅ ' . $config['pix_key']) . "\n";
    echo "Sandbox: " . ($config['sandbox'] ? '✅ SIM (Homologação)' : '❌ NÃO (Produção)') . "\n\n";
    
    // Verificar se certificado existe
    if (!empty($config['certificate'])) {
        if (file_exists($config['certificate'])) {
            echo "✅ Arquivo de certificado existe\n";
            echo "Caminho: " . $config['certificate'] . "\n";
            echo "Tamanho: " . filesize($config['certificate']) . " bytes\n";
            
            // Verificar permissões
            if (is_readable($config['certificate'])) {
                echo "✅ Certificado é legível\n";
            } else {
                echo "❌ Certificado NÃO é legível (problema de permissão)\n";
            }
        } else {
            echo "❌ Arquivo de certificado NÃO existe: " . $config['certificate'] . "\n";
        }
    }
    
    echo "\n=== DIAGNÓSTICO ===\n";
    
    // Verificar se está em sandbox mas usando credenciais de produção
    if ($config['sandbox']) {
        echo "⚠️  MODO SANDBOX (Homologação)\n";
        echo "   - Use credenciais de HOMOLOGAÇÃO\n";
        echo "   - Certificado de HOMOLOGAÇÃO\n";
        echo "   - Chave PIX de HOMOLOGAÇÃO\n";
    } else {
        echo "🔴 MODO PRODUÇÃO\n";
        echo "   - Use credenciais de PRODUÇÃO\n";
        echo "   - Certificado de PRODUÇÃO\n";
        echo "   - Chave PIX de PRODUÇÃO\n";
    }
    
    echo "\n=== POSSÍVEIS PROBLEMAS ===\n";
    echo "1. Credenciais de homologação sendo usadas em produção (ou vice-versa)\n";
    echo "2. Certificado não corresponde às credenciais\n";
    echo "3. Chave PIX não está cadastrada na conta EFI Bank\n";
    echo "4. Conta EFI Bank não tem permissão para criar cobranças PIX\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
