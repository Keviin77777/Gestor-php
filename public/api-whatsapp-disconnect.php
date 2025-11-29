<?php
/**
 * API para desconectar WhatsApp
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

require_once __DIR__ . '/../app/helpers/auth-helper.php';

$method = $_SERVER['REQUEST_METHOD'];
$user = getAuthenticatedUser();
$resellerId = $user['id'];

try {
    if ($method !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Buscar sessão ativa
    $session = Database::fetch(
        "SELECT * FROM whatsapp_sessions WHERE reseller_id = ? AND status IN ('connected', 'connecting', 'qr_code') ORDER BY created_at DESC LIMIT 1",
        [$resellerId]
    );
    
    if (!$session) {
        echo json_encode([
            'success' => true,
            'message' => 'Nenhuma sessão ativa encontrada'
        ]);
        exit();
    }
    
    $provider = $session['provider'] ?? 'evolution';
    error_log("Desconectando sessão {$session['id']} - Provider: {$provider}");
    
    // Desconectar da API apropriada
    if ($provider === 'native') {
        // API Premium (porta 3000)
        $nativeApiUrl = env('WHATSAPP_NATIVE_API_URL', 'http://localhost:3000');
        $instanceName = $session['instance_name'];
        
        error_log("Desconectando da API Premium: {$nativeApiUrl}/instance/{$instanceName}/logout");
        
        $ch = curl_init($nativeApiUrl . '/instance/' . $instanceName . '/logout');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("API Premium logout - HTTP Code: {$httpCode}, Response: " . $response);
    } else {
        // Evolution API
        $settings = Database::fetch(
            "SELECT * FROM whatsapp_settings WHERE reseller_id = ?",
            [$resellerId]
        );
        
        if ($settings) {
            $disconnectResult = disconnectEvolutionInstance(
                $settings['evolution_api_url'], 
                $settings['evolution_api_key'], 
                $session['instance_name']
            );
            
            if (!$disconnectResult['success']) {
                error_log("Erro ao desconectar da Evolution API: " . $disconnectResult['error']);
            }
        }
    }
    
    // DELETAR a sessão do banco (não apenas atualizar status)
    try {
        Database::query(
            "DELETE FROM whatsapp_sessions WHERE id = ?",
            [$session['id']]
        );
        error_log("Sessão {$session['id']} deletada do banco de dados");
    } catch (Exception $dbError) {
        error_log("Erro ao deletar sessão: " . $dbError->getMessage());
        throw new Exception("Erro ao remover sessão");
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'WhatsApp desconectado com sucesso'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Desconectar instância na Evolution API
 */
function disconnectEvolutionInstance($apiUrl, $apiKey, $instanceName) {
    $headers = [
        'Content-Type: application/json'
    ];
    
    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }
    
    // Primeiro, tentar fazer logout
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/logout/' . $instanceName,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Erro de conexão: ' . $error];
    }
    
    // Se logout não funcionou, tentar deletar a instância
    if ($httpCode !== 200) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/delete/' . $instanceName,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => 'Erro de conexão: ' . $error];
        }
    }
    
    return ['success' => true, 'message' => 'Instância desconectada'];
}
?>