<?php
/**
 * Helper para WhatsApp com Evolution API
 */

require_once __DIR__ . '/../core/Database.php';

/**
 * Detectar qual API está sendo usada
 */
function getActiveWhatsAppProvider($resellerId) {
    // Verificar qual API está conectada na tabela whatsapp_sessions
    $session = Database::fetch(
        "SELECT * FROM whatsapp_sessions WHERE reseller_id = ? AND status = 'connected' ORDER BY connected_at DESC LIMIT 1",
        [$resellerId]
    );
    
    if (!$session) {
        error_log("WhatsApp Helper - Nenhuma sessão conectada encontrada para reseller: " . $resellerId);
        throw new Exception('Nenhuma sessão WhatsApp conectada. Conecte o WhatsApp primeiro.');
    }
    
    // Se a sessão já tem provider definido, usar ele
    $sessionProvider = $session['provider'] ?? null;
    
    if ($sessionProvider === 'native') {
        // Verificar se a API Premium ainda está online
        $nativeApiUrl = env('WHATSAPP_NATIVE_API_URL', 'http://localhost:3000');
        $ch = curl_init($nativeApiUrl . '/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $healthData = json_decode($response, true);
            if ($healthData && isset($healthData['instances']['connected']) && $healthData['instances']['connected'] > 0) {
                error_log("WhatsApp Helper - Usando API Premium (native) - Sessão: " . $session['instance_name']);
                return 'native';
            }
        }
        
        // API Premium offline, mas sessão diz que é native
        error_log("WhatsApp Helper - AVISO: Sessão configurada como 'native' mas API Premium está offline!");
        throw new Exception('API Premium WhatsApp está offline. Reconecte o WhatsApp.');
    }
    
    if ($sessionProvider === 'evolution') {
        error_log("WhatsApp Helper - Usando Evolution API - Sessão: " . $session['instance_name']);
        return 'evolution';
    }
    
    // Se não tem provider definido, tentar detectar
    error_log("WhatsApp Helper - Provider não definido na sessão, tentando detectar...");
    
    // Verificar API Premium primeiro
    $nativeApiUrl = env('WHATSAPP_NATIVE_API_URL', 'http://localhost:3000');
    $ch = curl_init($nativeApiUrl . '/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $healthData = json_decode($response, true);
        if ($healthData && isset($healthData['instances']['connected']) && $healthData['instances']['connected'] > 0) {
            // Atualizar provider na sessão
            Database::query(
                "UPDATE whatsapp_sessions SET provider = 'native' WHERE id = ?",
                [$session['id']]
            );
            error_log("WhatsApp Helper - Detectado e atualizado para 'native' (API Premium)");
            return 'native';
        }
    }
    
    // Fallback para Evolution
    Database::query(
        "UPDATE whatsapp_sessions SET provider = 'evolution' WHERE id = ?",
        [$session['id']]
    );
    error_log("WhatsApp Helper - Detectado e atualizado para 'evolution'");
    return 'evolution';
}

/**
 * Enviar mensagem via WhatsApp
 */
function sendWhatsAppMessage($resellerId, $phoneNumber, $message, $templateId = null, $clientId = null, $invoiceId = null) {
    try {
        // Formatar número de telefone
        $formattedPhone = formatPhoneNumber($phoneNumber);
        
        // Detectar qual API usar
        $provider = getActiveWhatsAppProvider($resellerId);
        error_log("WhatsApp Helper - Provider detectado: " . $provider);
        
        // Buscar sessão ativa do reseller
        $session = Database::fetch(
            "SELECT * FROM whatsapp_sessions WHERE reseller_id = ? AND status = 'connected' ORDER BY connected_at DESC LIMIT 1",
            [$resellerId]
        );
        
        if (!$session) {
            error_log("WhatsApp Helper - Nenhuma sessão encontrada para reseller: " . $resellerId);
            throw new Exception('Nenhuma sessão WhatsApp ativa encontrada. Conecte o WhatsApp primeiro.');
        }
        
        // Buscar configurações
        $settings = Database::fetch(
            "SELECT * FROM whatsapp_settings WHERE reseller_id = ?",
            [$resellerId]
        );
        
        if (!$settings) {
            error_log("WhatsApp Helper - Criando configurações padrão para reseller: " . $resellerId);
            // Criar configurações padrão
            $settingsId = 'ws-' . uniqid();
            Database::query(
                "INSERT INTO whatsapp_settings (id, reseller_id, evolution_api_url, evolution_api_key, reminder_days) 
                 VALUES (?, ?, ?, ?, JSON_ARRAY(3, 7))",
                [
                    $settingsId, 
                    $resellerId, 
                    env('EVOLUTION_API_URL', 'http://localhost:8081'),
                    env('EVOLUTION_API_KEY', '')
                ]
            );
            
            $settings = Database::fetch(
                "SELECT * FROM whatsapp_settings WHERE id = ?",
                [$settingsId]
            );
        }
        
        // Verificar horário comercial se configurado
        if (isset($settings['send_only_business_hours']) && $settings['send_only_business_hours']) {
            $currentTime = date('H:i:s');
            if ($currentTime < $settings['business_hours_start'] || $currentTime > $settings['business_hours_end']) {
                throw new Exception('Fora do horário comercial configurado');
            }
        }
        
        // Criar registro da mensagem
        $messageId = 'msg-' . uniqid();
        Database::query(
            "INSERT INTO whatsapp_messages (id, reseller_id, session_id, template_id, client_id, invoice_id, phone_number, message, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
            [$messageId, $resellerId, $session['id'], $templateId, $clientId, $invoiceId, $formattedPhone, $message]
        );
        
        // Enviar mensagem via API apropriada
        if ($provider === 'native') {
            // Usar API Nativa
            error_log("WhatsApp Helper - Enviando via API Nativa");
            require_once __DIR__ . '/whatsapp-native-api.php';
            $nativeApi = new WhatsAppNativeAPI();
            $result = $nativeApi->sendMessage($resellerId, $formattedPhone, $message, $templateId, $clientId, $invoiceId);
        } else {
            // Usar Evolution API
            error_log("WhatsApp Helper - Enviando via Evolution API");
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
                [$result['message_id'] ?? $result['whatsapp_message_id'] ?? null, $messageId]
            );
            
            return [
                'success' => true,
                'message_id' => $messageId,
                'evolution_message_id' => $result['message_id'] ?? $result['whatsapp_message_id'] ?? null
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
        // Substituir tanto {{var}} quanto {var}
        $processedMessage = str_replace('{{' . $key . '}}', $value, $processedMessage);
        $processedMessage = str_replace('{' . $key . '}', $value, $processedMessage);
    }
    
    return $processedMessage;
}

/**
 * Enviar mensagem usando template (via fila)
 */
function sendTemplateMessage($resellerId, $phoneNumber, $templateType, $variables, $clientId = null, $invoiceId = null, $useQueue = true) {
    try {
        // Buscar template
        $template = Database::fetch(
            "SELECT * FROM whatsapp_templates WHERE reseller_id = ? AND type = ? AND is_active = 1 ORDER BY is_default DESC, created_at DESC LIMIT 1",
            [$resellerId, $templateType]
        );
        
        if (!$template) {
            throw new Exception("Template '$templateType' não encontrado");
        }
        
        // Se as variáveis não incluem payment_link ou está vazio, precisamos buscar do cliente
        if ((!isset($variables['payment_link']) || empty($variables['payment_link'])) && $clientId) {
            // Buscar dados do cliente para gerar payment_link
            require_once __DIR__ . '/whatsapp-automation.php';
            $client = Database::fetch(
                "SELECT * FROM clients WHERE id = ?",
                [$clientId]
            );
            
            if ($client) {
                // Usar prepareTemplateVariables para gerar todas as variáveis incluindo payment_link
                $allVariables = prepareTemplateVariables($template, $client);
                // Mesclar com as variáveis passadas (as passadas têm prioridade, exceto payment_link vazio)
                foreach ($allVariables as $key => $value) {
                    if (!isset($variables[$key]) || (empty($variables[$key]) && !empty($value))) {
                        $variables[$key] = $value;
                    }
                }
            }
        }
        
        // Processar template com variáveis
        $message = processTemplate($template['message'], $variables);
        
        // Se useQueue = true, adicionar à fila ao invés de enviar direto
        if ($useQueue) {
            require_once __DIR__ . '/queue-helper.php';
            
            // Determinar prioridade baseado no tipo de template
            $priority = 0; // Normal
            if (in_array($templateType, ['expires_today', 'expired_1d'])) {
                $priority = 1; // Alta prioridade para vencimentos urgentes
            } elseif ($templateType === 'welcome') {
                $priority = 2; // Prioridade máxima para boas-vindas
            }
            
            // Verificar se o template tem agendamento ativo
            $scheduledAt = null;
            if ($template['is_scheduled'] && $template['scheduled_time']) {
                // Calcular próximo horário de envio baseado no agendamento
                $currentDay = strtolower(date('l')); // monday, tuesday, etc.
                $scheduledDays = json_decode($template['scheduled_days'], true) ?: [];
                
                // Se hoje está nos dias agendados
                if (in_array($currentDay, $scheduledDays)) {
                    $scheduledTime = $template['scheduled_time'];
                    $scheduledDateTime = date('Y-m-d') . ' ' . $scheduledTime;
                    
                    // Verificar se o horário agendado já passou
                    $scheduledTimestamp = strtotime($scheduledDateTime);
                    $currentTimestamp = time();
                    
                    if ($scheduledTimestamp > $currentTimestamp) {
                        // Horário ainda não passou, agendar para hoje
                        $scheduledAt = $scheduledDateTime;
                        error_log("Template '{$templateType}' agendado para HOJE: {$scheduledAt}");
                    } else {
                        // Horário já passou, enviar imediatamente (agendar para daqui 1 minuto)
                        $scheduledAt = date('Y-m-d H:i:s', strtotime('+1 minute'));
                        error_log("Template '{$templateType}' - horário passou, enviando IMEDIATAMENTE: {$scheduledAt}");
                    }
                } else {
                    // Hoje não está nos dias agendados, encontrar o próximo dia
                    $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    $currentDayIndex = array_search($currentDay, $daysOfWeek);
                    
                    for ($i = 1; $i <= 7; $i++) {
                        $nextDayIndex = ($currentDayIndex + $i) % 7;
                        $nextDay = $daysOfWeek[$nextDayIndex];
                        
                        if (in_array($nextDay, $scheduledDays)) {
                            $scheduledAt = date('Y-m-d', strtotime("+{$i} days")) . ' ' . $template['scheduled_time'];
                            break;
                        }
                    }
                    
                    error_log("Template '{$templateType}' agendado para próximo dia: {$scheduledAt}");
                }
            }
            
            // Verificar se já existe mensagem deste template para este cliente HOJE
            // Isso evita duplicações - só permite 1 mensagem por cliente/template/dia
            if ($clientId && $template['id']) {
                // 1. Verificar na FILA (pending ou já enviada hoje)
                $existingInQueue = Database::fetch(
                    "SELECT id, status, scheduled_at FROM whatsapp_message_queue 
                     WHERE client_id = ? 
                     AND template_id = ? 
                     AND DATE(created_at) = CURDATE()
                     ORDER BY created_at DESC 
                     LIMIT 1",
                    [$clientId, $template['id']]
                );
                
                if ($existingInQueue) {
                    // Se está pendente, atualizar conteúdo
                    if ($existingInQueue['status'] === 'pending') {
                        Database::query(
                            "UPDATE whatsapp_message_queue 
                             SET scheduled_at = COALESCE(?, scheduled_at), message = ?, updated_at = NOW() 
                             WHERE id = ?",
                            [$scheduledAt, $message, $existingInQueue['id']]
                        );
                        
                        error_log("Queue Helper - Mensagem ATUALIZADA na fila: ID={$existingInQueue['id']}");
                        
                        return [
                            'success' => true,
                            'queued' => true,
                            'queue_id' => $existingInQueue['id'],
                            'updated' => true,
                            'message' => 'Mensagem atualizada na fila'
                        ];
                    } else {
                        // Já foi enviada/processada hoje - não duplicar
                        error_log("Queue Helper - Mensagem já enviada HOJE para cliente {$clientId}, template {$template['id']} - IGNORANDO duplicação");
                        
                        return [
                            'success' => true,
                            'queued' => false,
                            'skipped' => true,
                            'reason' => 'already_sent_today',
                            'message' => 'Mensagem já enviada hoje para este cliente'
                        ];
                    }
                }
                
                // 2. Verificar também na tabela de mensagens enviadas (whatsapp_messages)
                $alreadySentToday = Database::fetch(
                    "SELECT id FROM whatsapp_messages 
                     WHERE client_id = ? 
                     AND template_id = ? 
                     AND DATE(created_at) = CURDATE()
                     LIMIT 1",
                    [$clientId, $template['id']]
                );
                
                if ($alreadySentToday) {
                    error_log("Queue Helper - Mensagem já ENVIADA hoje (whatsapp_messages) para cliente {$clientId} - IGNORANDO");
                    
                    return [
                        'success' => true,
                        'queued' => false,
                        'skipped' => true,
                        'reason' => 'already_sent_today',
                        'message' => 'Mensagem já enviada hoje para este cliente'
                    ];
                }
            }
            
            // Se não existe, adicionar nova mensagem
            $result = addMessageToQueue(
                $resellerId,
                $phoneNumber,
                $message,
                $template['id'],
                $clientId,
                $priority,
                $scheduledAt
            );
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'queued' => true,
                    'queue_id' => $result['queue_id'],
                    'message' => 'Mensagem adicionada à fila'
                ];
            } else {
                throw new Exception($result['error']);
            }
        } else {
            // Enviar direto (modo legado)
            return sendWhatsAppMessage($resellerId, $phoneNumber, $message, $template['id'], $clientId, $invoiceId);
        }
        
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
    if (strlen($phone) === 11 && substr($phone, 0, 2) !== '55') {
        $phone = '55' . $phone;
    } elseif (strlen($phone) === 10 && substr($phone, 0, 2) !== '55') {
        // Número com 10 dígitos (sem o 9), adicionar código do país
        $phone = '55' . $phone;
    }
    
    // Adicionar 9 no celular APENAS se tiver 12 dígitos (55 + DDD + 8 dígitos)
    // E o terceiro dígito após o código do país NÃO for 9
    if (strlen($phone) === 12) {
        $ddd = substr($phone, 2, 2);
        $primeiroDigito = substr($phone, 4, 1);
        
        // Se o primeiro dígito do número não é 9, adicionar
        if ($primeiroDigito !== '9') {
            $numero = substr($phone, 4);
            // Verificar se é DDD válido e número tem 8 dígitos
            if (strlen($numero) === 8) {
                $phone = '55' . $ddd . '9' . $numero;
            }
        }
    }
    
    return $phone;
}

/**
 * Enviar mensagem de boas-vindas para novo cliente
 */
function sendWelcomeMessage($clientId, $useQueue = true) {
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
        
        return sendTemplateMessage($client['reseller_id'], $client['phone'], 'welcome', $variables, $clientId, null, $useQueue);
        
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
function sendInvoiceGeneratedMessage($invoiceId, $useQueue = true) {
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
        
        return sendTemplateMessage($invoice['reseller_id'], $invoice['client_phone'], 'invoice_generated', $variables, $invoice['client_id'], $invoiceId, $useQueue);
        
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
function sendRenewalMessage($clientId, $invoiceId, $useQueue = true) {
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
        
        return sendTemplateMessage($data['reseller_id'], $data['phone'], 'renewed', $variables, $clientId, $invoiceId, $useQueue);
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Adicionar mensagem à fila (atalho)
 */
function queueWhatsAppMessage($resellerId, $phoneNumber, $message, $templateId = null, $clientId = null, $priority = 0) {
    require_once __DIR__ . '/queue-helper.php';
    return addMessageToQueue($resellerId, $phoneNumber, $message, $templateId, $clientId, $priority);
}

/**
 * Adicionar múltiplas mensagens à fila (atalho)
 */
function queueBulkWhatsAppMessages($resellerId, $messages) {
    require_once __DIR__ . '/queue-helper.php';
    return addBulkMessagesToQueue($resellerId, $messages);
}
?>