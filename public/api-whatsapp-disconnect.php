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

$method = $_SERVER['REQUEST_METHOD'];
$resellerId = 'admin-001'; // Por enquanto fixo

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
    
    // Buscar configurações
    $settings = Database::fetch(
        "SELECT * FROM whatsapp_settings WHERE reseller_id = ?",
        [$resellerId]
    );
    
    if ($settings) {
        // Tentar desconectar da Evolution API
        $disconnectResult = disconnectEvolutionInstance(
            $settings['evolution_api_url'], 
            $settings['evolution_api_key'], 
            $session['instance_name']
        );
        
        if (!$disconnectResult['success']) {
            error_log("Erro ao desconectar da Evolution API: " . $disconnectResult['error']);
        }
    }
    
    // Atualizar status no banco
    try {
        Database::query(
            "UPDATE whatsapp_sessions SET 
             status = 'disconnected', 
             qr_code = NULL,
             profile_name = NULL,
             profile_picture = NULL,
             phone_number = NULL,
             updated_at = CURRENT_TIMESTAMP
             WHERE id = ?",
            [$session['id']]
        );
    } catch (Exception $dbError) {
        error_log("Erro ao atualizar sessão: " . $dbError->getMessage());
        throw new Exception("Erro ao atualizar status da sessão");
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