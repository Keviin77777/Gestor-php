<?php
/**
 * Script para verificar qual API WhatsApp está sendo usada
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = Database::connect();
    
    echo "=== Verificando APIs WhatsApp ===\n\n";
    
    // Verificar API Nativa
    $nativeApiUrl = env('WHATSAPP_NATIVE_API_URL', 'http://localhost:3000');
    echo "1. API Nativa ($nativeApiUrl):\n";
    
    $ch = curl_init($nativeApiUrl . '/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "   ✅ ONLINE\n";
        echo "   Status: " . ($data['status'] ?? 'N/A') . "\n";
        echo "   Provider: " . ($data['provider'] ?? 'N/A') . "\n";
        echo "   Instâncias: " . ($data['instances']['total'] ?? 0) . " total, " . ($data['instances']['connected'] ?? 0) . " conectadas\n";
        $nativeOnline = true;
    } else {
        echo "   ❌ OFFLINE\n";
        $nativeOnline = false;
    }
    
    echo "\n";
    
    // Verificar Evolution API
    $evolutionApiUrl = env('EVOLUTION_API_URL', 'http://localhost:8081');
    echo "2. Evolution API ($evolutionApiUrl):\n";
    
    $ch = curl_init($evolutionApiUrl . '/manager/fetchInstances');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . env('EVOLUTION_API_KEY', '')]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $instances = json_decode($response, true);
        echo "   ✅ ONLINE\n";
        echo "   Instâncias: " . count($instances) . "\n";
        $evolutionOnline = true;
    } else {
        echo "   ❌ OFFLINE\n";
        $evolutionOnline = false;
    }
    
    echo "\n";
    
    // Verificar sessões no banco
    echo "3. Sessões no banco de dados:\n";
    $sessions = $db->query("SELECT * FROM whatsapp_sessions WHERE status = 'connected'")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sessions)) {
        echo "   ⚠️  Nenhuma sessão conectada\n";
    } else {
        foreach ($sessions as $session) {
            echo "   - Reseller: {$session['reseller_id']}\n";
            echo "     Instance: {$session['instance_name']}\n";
            echo "     Provider: " . ($session['provider'] ?? 'NÃO DEFINIDO') . "\n";
            echo "     Status: {$session['status']}\n";
            echo "     Telefone: " . ($session['phone_number'] ?? 'N/A') . "\n";
            
            // Sugerir provider correto
            if ($nativeOnline && ($session['provider'] === 'native' || strpos($session['instance_name'], 'reseller_') === 0)) {
                echo "     ✅ Provider correto (native)\n";
            } elseif ($evolutionOnline && $session['provider'] === 'evolution') {
                echo "     ✅ Provider correto (evolution)\n";
            } else {
                echo "     ⚠️  Provider pode estar incorreto\n";
                
                // Tentar detectar o correto
                if ($nativeOnline && strpos($session['instance_name'], 'reseller_') === 0) {
                    echo "     💡 Sugestão: Atualizar para 'native'\n";
                    
                    $stmt = $db->prepare("UPDATE whatsapp_sessions SET provider = 'native' WHERE id = ?");
                    $stmt->execute([$session['id']]);
                    echo "     ✓ Provider atualizado para 'native'\n";
                }
            }
            echo "\n";
        }
    }
    
    echo "\n=== Configuração recomendada no .env ===\n";
    if ($nativeOnline) {
        echo "WHATSAPP_DEFAULT_PROVIDER=native\n";
    } elseif ($evolutionOnline) {
        echo "WHATSAPP_DEFAULT_PROVIDER=evolution\n";
    } else {
        echo "⚠️  Nenhuma API está online!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
