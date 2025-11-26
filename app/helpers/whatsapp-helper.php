<?php
/**
 * Helper para WhatsApp com Evolution API
 */

require_once __DIR__ . '/../core/Database.php';

/**
 * Detectar qual API está sendo usada
 */
function getActiveWhatsAppProvider($resellerId) {
    // Verificar qual API está conectada
    $session = Database::fetch(
        "SELECT * FROM whatsapp_sessions WHERE reseller_id = ? AND status = 'connected' ORDER BY connected_at DESC LIMIT 1",
        [$resellerId]
    );
    
    if (!$session) {
        return null;
    }
    
    // Verificar se o instance_name indica API nativa (formato: reseller_xxx)
    if (strpos($session['instance_name'], 'reseller_') === 0 || strpos($session['instance_name'], 'ultragestor-') === 0) {
        // Verificar se a API nativa está configurada
        $nativeApiUrl = env('WHATSAPP_NATIVE_API_URL', 'http://localhost:3000');
        
        // Tentar fazer um ping na API nativa
        $ch = curl_init($nativeApiUrl . '/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return 'native';
        }
    }
    
    // Fallback para Evolution API
    return 'evolution';
}

/**
 * Enviar mensagem via WhatsApp
 */
function sendWhatsAppMessage($resellerId, $phoneNumber, $message, $templateId = null, $clientId = null, $invoiceId = null) {
    try {
        // Buscar sessão ativa do reseller
        $session = Database::fetch(
            "SELECT * FROM whatsapp_sessions WHERE reseller_id = ? AND status = 'connected' ORDER BY connected_at DESC LIMIT 1",
            [$resellerId]
        );
        
        if (!$session) {
            throw new Exception('Nenhuma sessão WhatsApp ativa encontrada');
        }
        
        // Buscar configurações
        $settings = Database::fetch(
            "SELECT * FROM whatsapp_settings WHERE reseller_id = ?",
            [$resellerId]
        );
        
        if (!$settings) {
            throw new Exception('Configurações WhatsApp não encontradas');
        }
        
        // Verificar horário comercial se configurado
        if (isset($settings['send_only_business_hours']) && $settings['send_only_business_hours']) {
            $currentTime = date('H:i:s');
            if ($currentTime < $settings['business_hours_start'] || $currentTime > $settings['business_hours_end']) {
                throw new Exception('Fora do horário comercial configurado');
            }
        }
        
        // Formatar número de telefone
        $formattedPhone = formatPhoneNumber($phoneNumber);
        
        // Criar registro da mensagem
        $messageId = 'msg-' . uniqid();
        Database::query(
            "INSERT INTO whatsapp_messages (id, reseller_id, session_id, template_id, client_id, invoice_id, phone_number, message, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
            [$messageId, $resellerId, $session['id'], $templateId, $clientId, $invoiceId, $formattedPhone, $message]
        );
        
        // Detectar qual API usar
        $provider = getActiveWhatsAppProvider($resellerId);
        
        // Enviar mensagem via API apropriada
        if ($provider === 'native') {
            // Usar API Nativa
            require_once __DIR__ . '/whatsapp-native-api.php';
            $nativeApi = new WhatsAppNativeAPI();
            $result = $nativeApi->sendMessage($resellerId, $formattedPhone, $message);
        } else {
            // Usar Evolution API
            $result = sendMessageToEvolution($settings['evolution_api_url'], $settings['evolution_api_key'], $session['instance_name'], $formattedPhone, $message);
        }
        
        if ($result['success']) {
            // Atualizar status da mensagem
            Database::query(
                "UPDATE whatsapp_messages SET 
                 status = 'sent', 
                 evolution_message_id = ?, 
                 sent_at = CURRENT_TIMESTAMP 
                 WHERE id = ?",
                [$result['message_id'], $messageId]
            );
            
            return [
                'success' => true,
                'message_id' => $messageId,
                'evolution_message_id' => $result['message_id']
            ];
        } else {
            // Atualizar status para erro
            Database::query(
                "UPDATE whatsapp_messages SET 
                 status = 'failed', 
                 error_message = ? 
                 WHERE id = ?",
                [$result['error'], $messageId]
            );
            
            throw new Exception($result['error']);
        }
        
    } catch (Exception $e) {
        error_log("Erro ao enviar mensagem WhatsApp: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Enviar mensagem para Evolution API
 */
function sendMessageToEvolution($apiUrl, $apiKey, $instanceName, $phoneNumber, $message) {
    $headers = [
        'Content-Type: application/json',
        'apikey: ' . $apiKey  // Evolution API usa 'apikey' header, não 'Authorization'
    ];
    
    // Log para debug
    error_log("Evolution API - URL: " . $apiUrl);
    error_log("Evolution API - Instance: " . $instanceName);
    error_log("Evolution API - Phone: " . $phoneNumber);
    error_log("Evolution API - API Key: " . substr($apiKey, 0, 10) . "...");
    
    // Formato correto para Evolution API
    $payload = [
        'number' => $phoneNumber,
        'textMessage' => [
            'text' => $message
        ]
    ];
    
    // Log do payload
    error_log("Evolution API - Payload: " . json_encode($payload));
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($apiUrl, '/') . '/message/sendText/' . $instanceName,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log da resposta
    error_log("Evolution API - HTTP Code: " . $httpCode);
    error_log("Evolution API - Response: " . $response);
    
    if ($error) {
        error_log("Evolution API - cURL Error: " . $error);
        return ['success' => false, 'error' => 'Erro de conexão: ' . $error];
    }
    
    if ($httpCode !== 200 && $httpCode !== 201) {
        $responseData = json_decode($response, true);
        $errorMsg = $responseData['message'] ?? $responseData['error'] ?? 'Erro HTTP: ' . $httpCode;
        error_log("Evolution API - Error Response: " . $errorMsg);
        return ['success' => false, 'error' => $errorMsg];
    }
    
    $responseData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Resposta inválida da API'];
    }
    
    return [
        'success' => true,
        'message_id' => $responseData['key']['id'] ?? null,
        'data' => $responseData
    ];
}

/**
 * Processar template com variáveis
 */
function processTemplate($templateMessage, $variables) {
    $processedMessage = $templateMessage;
    
    foreach ($variables as $key => $value) {
        $processedMessage = str_replace('{{' . $key . '}}', $value, $processedMessage);
    }
    
    return $processedMessage;
}

/**
 * Enviar mensagem usando template
 */
function sendTemplateMessage($resellerId, $phoneNumber, $templateType, $variables, $clientId = null, $invoiceId = null) {
    try {
        // Buscar template
        $template = Database::fetch(
            "SELECT * FROM whatsapp_templates WHERE reseller_id = ? AND type = ? AND is_active = 1 ORDER BY is_default DESC, created_at DESC LIMIT 1",
            [$resellerId, $templateType]
        );
        
        if (!$template) {
            throw new Exception("Template '$templateType' não encontrado");
        }
        
        // Processar template com variáveis
        $message = processTemplate($template['message'], $variables);
        
        // Enviar mensagem
        return sendWhatsAppMessage($resellerId, $phoneNumber, $message, $template['id'], $clientId, $invoiceId);
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Formatar número de telefone para WhatsApp
 */
function formatPhoneNumber($phone) {
    // Remover caracteres não numéricos
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Se não tem código do país, adicionar 55 (Brasil)
    if (strlen($phone) === 11 && substr($phone, 0, 1) !== '5') {
        $phone = '55' . $phone;
    } elseif (strlen($phone) === 10 && substr($phone, 0, 1) !== '5') {
        $phone = '55' . $phone;
    }
    
    // Adicionar 9 no celular se necessário (padrão brasileiro)
    if (strlen($phone) === 12 && substr($phone, 2, 1) !== '9') {
        $ddd = substr($phone, 2, 2);
        $numero = substr($phone, 4);
        if (strlen($numero) === 8 && in_array($ddd, ['11', '12', '13', '14', '15', '16', '17', '18', '19', '21', '22', '24', '27', '28'])) {
            $phone = '55' . $ddd . '9' . $numero;
        }
    }
    
    return $phone;
}

/**
 * Enviar mensagem de boas-vindas para novo cliente
 */
function sendWelcomeMessage($clientId) {
    try {
        // Buscar dados do cliente
        $client = Database::fetch(
            "SELECT * FROM clients WHERE id = ?",
            [$clientId]
        );
        
        if (!$client || !$client['phone']) {
            return ['success' => false, 'error' => 'Cliente não encontrado ou sem telefone'];
        }
        
        // Verificar se deve enviar mensagem de boas-vindas
        $settings = Database::fetch(
            "SELECT auto_send_welcome FROM whatsapp_settings WHERE reseller_id = ?",
            [$client['reseller_id']]
        );
        
        if (!$settings || !$settings['auto_send_welcome']) {
            return ['success' => false, 'error' => 'Envio automático de boas-vindas desabilitado'];
        }
        
        // Preparar variáveis do template
        $variables = [
            'cliente_nome' => $client['name'],
            'cliente_usuario' => $client['username'] ?: 'Não informado',
            'cliente_senha' => $client['iptv_password'] ?: $client['password'] ?: 'Não informada',
            'cliente_servidor' => $client['server'] ?: 'Principal',
            'cliente_plano' => $client['plan'] ?: 'Personalizado',
            'cliente_vencimento' => date('d/m/Y', strtotime($client['renewal_date'])),
            'cliente_valor' => 'R$ ' . number_format($client['value'], 2, ',', '.')
        ];
        
        return sendTemplateMessage($client['reseller_id'], $client['phone'], 'welcome', $variables, $clientId);
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Enviar mensagem de fatura gerada
 */
function sendInvoiceGeneratedMessage($invoiceId) {
    try {
        // Buscar dados da fatura e cliente
        $invoice = Database::fetch(
            "SELECT i.*, c.name as client_name, c.phone as client_phone, c.plan as client_plan
             FROM invoices i 
             JOIN clients c ON i.client_id = c.id 
             WHERE i.id = ?",
            [$invoiceId]
        );
        
        if (!$invoice || !$invoice['client_phone']) {
            return ['success' => false, 'error' => 'Fatura não encontrada ou cliente sem telefone'];
        }
        
        // Verificar se deve enviar mensagem de fatura
        $settings = Database::fetch(
            "SELECT auto_send_invoice FROM whatsapp_settings WHERE reseller_id = ?",
            [$invoice['reseller_id']]
        );
        
        if (!$settings || !$settings['auto_send_invoice']) {
            return ['success' => false, 'error' => 'Envio automático de fatura desabilitado'];
        }
        
        // Preparar variáveis do template
        $variables = [
            'cliente_nome' => $invoice['client_name'],
            'fatura_valor' => 'R$ ' . number_format($invoice['final_value'], 2, ',', '.'),
            'fatura_vencimento' => date('d/m/Y', strtotime($invoice['due_date'])),
            'fatura_periodo' => date('m/Y', strtotime($invoice['issue_date']))
        ];
        
        return sendTemplateMessage($invoice['reseller_id'], $invoice['client_phone'], 'invoice_generated', $variables, $invoice['client_id'], $invoiceId);
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Enviar mensagem de renovação confirmada
 */
function sendRenewalMessage($clientId, $invoiceId) {
    try {
        // Buscar dados do cliente e fatura
        $data = Database::fetch(
            "SELECT c.*, i.final_value 
             FROM clients c 
             LEFT JOIN invoices i ON i.id = ? 
             WHERE c.id = ?",
            [$invoiceId, $clientId]
        );
        
        if (!$data || !$data['phone']) {
            return ['success' => false, 'error' => 'Cliente não encontrado ou sem telefone'];
        }
        
        // Verificar se deve enviar mensagem de renovação
        $settings = Database::fetch(
            "SELECT auto_send_renewal FROM whatsapp_settings WHERE reseller_id = ?",
            [$data['reseller_id']]
        );
        
        if (!$settings || !$settings['auto_send_renewal']) {
            return ['success' => false, 'error' => 'Envio automático de renovação desabilitado'];
        }
        
        // Preparar variáveis do template
        $variables = [
            'cliente_nome' => $data['name'],
            'cliente_vencimento' => date('d/m/Y', strtotime($data['renewal_date'])),
            'fatura_valor' => 'R$ ' . number_format($data['final_value'] ?: $data['value'], 2, ',', '.')
        ];
        
        return sendTemplateMessage($data['reseller_id'], $data['phone'], 'renewed', $variables, $clientId, $invoiceId);
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>