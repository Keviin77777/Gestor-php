<?php
/**
 * Script para processar mensagens WhatsApp pendentes
 * Executa quando o WhatsApp é conectado ou periodicamente
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/whatsapp-helper.php';

loadEnv(__DIR__ . '/../.env');

date_default_timezone_set('America/Sao_Paulo');

function processPendingMessages($resellerId = null) {
    $report = [
        'processed' => 0,
        'sent' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    try {
        // Buscar mensagens pendentes
        $query = "SELECT * FROM whatsapp_messages 
                  WHERE status = 'pending' 
                  AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $params = [];
        if ($resellerId) {
            $query .= " AND reseller_id = ?";
            $params[] = $resellerId;
        }
        
        $query .= " ORDER BY created_at ASC LIMIT 50"; // Limitar para evitar spam
        
        $pendingMessages = Database::fetchAll($query, $params);
        
        if (empty($pendingMessages)) {
            return $report;
        }
        
        echo "📨 Encontradas " . count($pendingMessages) . " mensagens pendentes\n";
        
        foreach ($pendingMessages as $message) {
            $report['processed']++;
            
            // Verificar se a sessão está ativa
            $session = Database::fetch(
                "SELECT * FROM whatsapp_sessions WHERE id = ? AND status = 'connected'",
                [$message['session_id']]
            );
            
            if (!$session) {
                echo "⚠️  Sessão {$message['session_id']} não está conectada - pulando\n";
                continue;
            }
            
            // Tentar enviar a mensagem
            echo "📤 Enviando mensagem para {$message['phone_number']}...\n";
            
            $result = sendWhatsAppMessage(
                $message['reseller_id'],
                $message['phone_number'],
                $message['message'],
                $message['template_id'],
                $message['client_id']
            );
            
            if ($result['success']) {
                // Atualizar status para 'sent'
                Database::query(
                    "UPDATE whatsapp_messages 
                     SET status = 'sent', 
                         sent_at = NOW(),
                         evolution_message_id = ?,
                         updated_at = NOW()
                     WHERE id = ?",
                    [$result['message_id'] ?? null, $message['id']]
                );
                
                $report['sent']++;
                echo "✅ Mensagem enviada com sucesso\n";
                
                // Delay de 2 segundos entre mensagens para evitar bloqueio
                sleep(2);
                
            } else {
                // Atualizar status para 'failed'
                Database::query(
                    "UPDATE whatsapp_messages 
                     SET status = 'failed',
                         error_message = ?,
                         updated_at = NOW()
                     WHERE id = ?",
                    [$result['error'] ?? 'Erro desconhecido', $message['id']]
                );
                
                $report['failed']++;
                $report['errors'][] = [
                    'phone' => $message['phone_number'],
                    'error' => $result['error'] ?? 'Erro desconhecido'
                ];
                echo "❌ Falha ao enviar: " . ($result['error'] ?? 'Erro desconhecido') . "\n";
            }
        }
        
    } catch (Exception $e) {
        $report['errors'][] = ['global' => $e->getMessage()];
        echo "❌ Erro: " . $e->getMessage() . "\n";
    }
    
    return $report;
}

// Se executado diretamente
if (php_sapi_name() === 'cli') {
    echo "=== PROCESSANDO MENSAGENS PENDENTES ===\n\n";
    
    $resellerId = $argv[1] ?? null;
    
    if ($resellerId) {
        echo "Processando mensagens do reseller: {$resellerId}\n\n";
    } else {
        echo "Processando mensagens de todos os resellers\n\n";
    }
    
    $report = processPendingMessages($resellerId);
    
    echo "\n=== RESUMO ===\n";
    echo "📊 Mensagens processadas: {$report['processed']}\n";
    echo "✅ Enviadas com sucesso: {$report['sent']}\n";
    echo "❌ Falharam: {$report['failed']}\n";
    
    if (!empty($report['errors'])) {
        echo "\n=== ERROS ===\n";
        foreach ($report['errors'] as $error) {
            if (isset($error['global'])) {
                echo "❌ {$error['global']}\n";
            } else {
                echo "❌ {$error['phone']}: {$error['error']}\n";
            }
        }
    }
    
    echo "\n";
}
