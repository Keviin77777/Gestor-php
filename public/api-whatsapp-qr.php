<?php
/**
 * API para buscar QR Code do WhatsApp
 */

// Aumentar timeout para operações longas
set_time_limit(60);
ini_set('max_execution_time', '60');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
    if ($method !== 'GET') {
        throw new Exception('Método não permitido');
    }
    
    // Buscar sessão ativa em processo de conexão
    $session = Database::fetch(
        "SELECT * FROM whatsapp_sessions WHERE reseller_id = ? AND status IN ('connecting', 'qr_code') ORDER BY created_at DESC LIMIT 1",
        [$resellerId]
    );
    
    error_log("QR API - Reseller: $resellerId, Session found: " . ($session ? 'YES' : 'NO'));
    if ($session) {
        error_log("QR API - Session status: " . $session['status'] . ", Instance: " . $session['instance_name']);
    }
    
    if (!$session) {
        // Não é erro, apenas não há sessão em processo de conexão
        error_log("QR API - Nenhuma sessão em processo de conexão");
        echo json_encode([
            'success' => true,
            'connected' => false,
            'qr_code' => null,
            'message' => 'Nenhuma sessão em processo de conexão'
        ]);
        exit();
    }
    
    // Detectar qual API está sendo usada baseado no provider da sessão
    $provider = $session['provider'] ?? 'evolution';
    
    error_log("QR API - Provider detectado: $provider");
    
    if ($provider === 'native') {
        // Usar API Premium (Nativa)
        require_once __DIR__ . '/../app/helpers/whatsapp-native-api.php';
        $nativeApi = new WhatsAppNativeAPI();
        $qrResult = $nativeApi->getQRCode($resellerId);
    } else {
        // Usar Evolution API (Básica)
        $settings = Database::fetch(
            "SELECT * FROM whatsapp_settings WHERE reseller_id = ?",
            [$resellerId]
        );
        
        if (!$settings) {
            // Usar configurações padrão do .env
            $settings = [
                'evolution_api_url' => getenv('EVOLUTION_API_URL') ?: getenv('WHATSAPP_API_URL') ?: 'http://localhost:8081',
                'evolution_api_key' => getenv('EVOLUTION_API_KEY') ?: getenv('WHATSAPP_API_KEY') ?: ''
            ];
        }
        
        // Buscar QR Code na Evolution API
        $qrResult = fetchQRCodeFromEvolution(
            $settings['evolution_api_url'], 
            $settings['evolution_api_key'], 
            $session['instance_name']
        );
    }
    
    if ($qrResult['success']) {
        // Atualizar QR Code no banco se houver um novo
        if (isset($qrResult['qr_code']) && $qrResult['qr_code']) {
            $currentQrCode = $session['qr_code'] ?? null;
            if ($qrResult['qr_code'] !== $currentQrCode) {
                try {
                    Database::query(
                        "UPDATE whatsapp_sessions SET qr_code = ?, status = 'qr_code', updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                        [$qrResult['qr_code'], $session['id']]
                    );
                } catch (Exception $e) {
                    error_log("Erro ao atualizar QR Code: " . $e->getMessage());
                    // Não falhar se não conseguir atualizar, apenas logar
                }
            }
        }
        
        // Verificar se já está conectado
        if ($qrResult['connected']) {
            try {
                Database::query(
                    "UPDATE whatsapp_sessions SET 
                     status = 'connected', 
                     updated_at = CURRENT_TIMESTAMP,
                     profile_name = ?,
                     phone_number = ?,
                     profile_picture = ?
                     WHERE id = ?",
                    [
                        $qrResult['profile_name'] ?? null,
                        $qrResult['phone_number'] ?? null,
                        $qrResult['profile_picture'] ?? null,
                        $session['id']
                    ]
                );
            } catch (Exception $e) {
                error_log("Erro ao atualizar sessão conectada: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true,
                'connected' => true,
                'profile_name' => $qrResult['profile_name'],
                'phone_number' => $qrResult['phone_number']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'connected' => false,
                'qr_code' => $qrResult['qr_code']
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => $qrResult['error']
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Buscar QR Code na Evolution API
 */
function fetchQRCodeFromEvolution($apiUrl, $apiKey, $instanceName) {
    $headers = [
        'Content-Type: application/json'
    ];
    
    if ($apiKey) {
        $headers[] = 'apikey: ' . $apiKey;
    }
    
    // Primeiro, verificar o status da conexão
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/connectionState/' . $instanceName,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    error_log("fetchQRCode - connectionState HTTP: $httpCode, Response: " . substr($response, 0, 200));
    
    if ($error) {
        error_log("fetchQRCode - connectionState Error: $error");
        // Não falhar, apenas retornar que ainda não está pronto
        return ['success' => true, 'connected' => false, 'qr_code' => null, 'message' => 'Aguardando instância...'];
    }
    
    if ($httpCode !== 200) {
        error_log("fetchQRCode - connectionState Failed: HTTP $httpCode");
        // Se a instância não existe ainda (404), retornar sucesso mas sem QR
        if ($httpCode === 404) {
            error_log("fetchQRCode - Instância ainda não existe (404), aguardando criação...");
            return ['success' => true, 'connected' => false, 'qr_code' => null, 'message' => 'Criando instância...'];
        }
        // Outros erros também não devem falhar, apenas aguardar
        return ['success' => true, 'connected' => false, 'qr_code' => null, 'message' => 'Aguardando...'];
    }
    
    $statusData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("fetchQRCode - JSON decode error");
        return ['success' => true, 'connected' => false, 'qr_code' => null, 'message' => 'Aguardando...'];
    }
    
    // Verificar se já está conectado
    if (isset($statusData['instance']['state']) && $statusData['instance']['state'] === 'open') {
        error_log("fetchQRCode - Instância já conectada!");
        return [
            'success' => true,
            'connected' => true,
            'profile_name' => $statusData['instance']['profileName'] ?? null,
            'phone_number' => $statusData['instance']['owner'] ?? null
        ];
    }
    
    // Se state não existe, a instância ainda está inicializando
    if (!isset($statusData['instance']['state'])) {
        error_log("fetchQRCode - Instância ainda inicializando (sem state)");
        return ['success' => true, 'connected' => false, 'qr_code' => null, 'message' => 'Inicializando...'];
    }
    
    $currentState = $statusData['instance']['state'] ?? 'unknown';
    error_log("fetchQRCode - State: $currentState, buscando QR Code...");
    
    // Se não está conectado, buscar QR Code
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/connect/' . $instanceName,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15, // Timeout menor para não travar
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    error_log("fetchQRCode - /instance/connect HTTP: $httpCode, Response: " . substr($response, 0, 300));
    
    if ($error) {
        error_log("fetchQRCode - /instance/connect Error: $error");
        // Não falhar, apenas retornar que ainda não está pronto
        return ['success' => true, 'connected' => false, 'qr_code' => null, 'message' => 'Aguardando QR Code...'];
    }
    
    if ($httpCode !== 200) {
        error_log("fetchQRCode - /instance/connect Failed: HTTP $httpCode");
        // Não falhar, apenas retornar que ainda não está pronto
        return ['success' => true, 'connected' => false, 'qr_code' => null, 'message' => 'Aguardando QR Code...'];
    }
    
    $qrData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("fetchQRCode - JSON decode error");
        return ['success' => false, 'error' => 'Resposta inválida'];
    }
    
    // Extrair QR Code da resposta
    $qrCode = null;
    if (isset($qrData['qrcode'])) {
        if (isset($qrData['qrcode']['base64'])) {
            $qrCode = $qrData['qrcode']['base64'];
            error_log("fetchQRCode - QR Code base64 encontrado!");
        }
    } elseif (isset($qrData['base64'])) {
        $qrCode = $qrData['base64'];
        error_log("fetchQRCode - QR Code base64 direto encontrado!");
    }
    
    if (!$qrCode) {
        error_log("fetchQRCode - QR Code NÃO encontrado via /connect. Keys: " . implode(', ', array_keys($qrData)));
        
        // Tentar endpoint alternativo: /instance/qrcode
        error_log("fetchQRCode - Tentando endpoint /instance/qrcode...");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => rtrim($apiUrl, '/') . '/instance/qrcode/' . $instanceName,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $qrResponse = curl_exec($ch);
        $qrHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("fetchQRCode - /qrcode HTTP: $qrHttpCode, Response: " . substr($qrResponse, 0, 200));
        
        if ($qrHttpCode === 200) {
            $qrCodeData = json_decode($qrResponse, true);
            if (isset($qrCodeData['base64'])) {
                $qrCode = $qrCodeData['base64'];
                error_log("fetchQRCode - QR Code encontrado via /qrcode!");
            } elseif (isset($qrCodeData['qrcode']['base64'])) {
                $qrCode = $qrCodeData['qrcode']['base64'];
                error_log("fetchQRCode - QR Code encontrado via /qrcode (nested)!");
            }
        }
    }
    
    error_log("QR Code API - QR Code encontrado: " . ($qrCode ? 'Sim' : 'Não'));
    
    return [
        'success' => true,
        'connected' => false,
        'qr_code' => $qrCode
    ];
}
?>