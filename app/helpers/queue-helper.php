<?php
/**
 * Helper para gerenciar fila de mensagens WhatsApp
 */

require_once __DIR__ . '/../core/Database.php';

/**
 * Adicionar mensagem à fila
 * 
 * @param string $resellerId ID do revendedor
 * @param string $phoneNumber Número de telefone
 * @param string $message Mensagem a ser enviada
 * @param string|null $templateId ID do template (opcional)
 * @param int|null $clientId ID do cliente (opcional)
 * @param int $priority Prioridade (0=normal, 1=alta, 2=urgente)
 * @param string|null $scheduledAt Data/hora para envio agendado (opcional)
 * @return array Resultado da operação
 */
function addMessageToQueue($resellerId, $phoneNumber, $message, $templateId = null, $clientId = null, $priority = 0, $scheduledAt = null) {
    try {
        $db = Database::connect();
        
        // Formatar número de telefone
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Se não tem código do país, adicionar 55 (Brasil)
        if (strlen($phoneNumber) === 11 && substr($phoneNumber, 0, 1) !== '5') {
            $phoneNumber = '55' . $phoneNumber;
        } elseif (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 1) !== '5') {
            $phoneNumber = '55' . $phoneNumber;
        }
        
        // Inserir na fila
        $stmt = $db->prepare(
            "INSERT INTO whatsapp_message_queue 
            (reseller_id, phone, message, template_id, client_id, priority, scheduled_at, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
        );
        
        $stmt->execute([
            $resellerId,
            $phoneNumber,
            $message,
            $templateId,
            $clientId,
            $priority,
            $scheduledAt
        ]);
        
        $queueId = $db->lastInsertId();
        
        error_log("Queue Helper - Mensagem adicionada à fila: ID={$queueId}, Phone={$phoneNumber}");
        
        return [
            'success' => true,
            'queue_id' => $queueId,
            'message' => 'Mensagem adicionada à fila com sucesso'
        ];
        
    } catch (Exception $e) {
        error_log("Queue Helper - Erro ao adicionar à fila: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Adicionar múltiplas mensagens à fila de uma vez
 * 
 * @param string $resellerId ID do revendedor
 * @param array $messages Array de mensagens [['phone' => '', 'message' => '', ...], ...]
 * @return array Resultado da operação
 */
function addBulkMessagesToQueue($resellerId, $messages) {
    try {
        $db = Database::connect();
        
        $stmt = $db->prepare(
            "INSERT INTO whatsapp_message_queue 
            (reseller_id, phone, message, template_id, client_id, priority, scheduled_at, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
        );
        
        $added = 0;
        $errors = [];
        
        foreach ($messages as $msg) {
            try {
                // Formatar número de telefone
                $phoneNumber = preg_replace('/[^0-9]/', '', $msg['phone']);
                
                if (strlen($phoneNumber) === 11 && substr($phoneNumber, 0, 1) !== '5') {
                    $phoneNumber = '55' . $phoneNumber;
                } elseif (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 1) !== '5') {
                    $phoneNumber = '55' . $phoneNumber;
                }
                
                $stmt->execute([
                    $resellerId,
                    $phoneNumber,
                    $msg['message'],
                    $msg['template_id'] ?? null,
                    $msg['client_id'] ?? null,
                    $msg['priority'] ?? 0,
                    $msg['scheduled_at'] ?? null
                ]);
                
                $added++;
                
            } catch (Exception $e) {
                $errors[] = [
                    'phone' => $msg['phone'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        error_log("Queue Helper - Bulk add: {$added} mensagens adicionadas, " . count($errors) . " erros");
        
        return [
            'success' => true,
            'added' => $added,
            'errors' => $errors,
            'message' => "{$added} mensagens adicionadas à fila"
        ];
        
    } catch (Exception $e) {
        error_log("Queue Helper - Erro no bulk add: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Obter estatísticas da fila
 * 
 * @param string $resellerId ID do revendedor
 * @return array Estatísticas
 */
function getQueueStats($resellerId) {
    try {
        $db = Database::connect();
        
        $stmt = $db->prepare(
            "SELECT 
                status,
                COUNT(*) as count
            FROM whatsapp_message_queue
            WHERE reseller_id = ?
            GROUP BY status"
        );
        $stmt->execute([$resellerId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats = [
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0
        ];
        
        foreach ($results as $row) {
            $stats[$row['status']] = (int)$row['count'];
        }
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Queue Helper - Erro ao obter stats: " . $e->getMessage());
        return [
            'pending' => 0,
            'processing' => 0,
            'sent' => 0,
            'failed' => 0
        ];
    }
}

/**
 * Verificar se pode adicionar mais mensagens (respeita limites)
 * 
 * @param string $resellerId ID do revendedor
 * @return array Informações sobre limites
 */
function canAddToQueue($resellerId) {
    try {
        $db = Database::connect();
        
        // Buscar configuração de rate limit
        $stmt = $db->prepare("SELECT * FROM whatsapp_rate_limit_config WHERE reseller_id = ?");
        $stmt->execute([$resellerId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$config) {
            $config = [
                'messages_per_minute' => 20,
                'messages_per_hour' => 100,
                'enabled' => 1
            ];
        }
        
        if (!$config['enabled']) {
            return [
                'can_add' => true,
                'reason' => 'Rate limiting desabilitado'
            ];
        }
        
        // Verificar mensagens enviadas na última hora
        $stmt = $db->prepare(
            "SELECT COUNT(*) as count 
            FROM whatsapp_message_queue 
            WHERE reseller_id = ? 
            AND status = 'sent'
            AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        $stmt->execute([$resellerId]);
        $sentLastHour = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Verificar mensagens pendentes
        $stmt = $db->prepare(
            "SELECT COUNT(*) as count 
            FROM whatsapp_message_queue 
            WHERE reseller_id = ? 
            AND status = 'pending'"
        );
        $stmt->execute([$resellerId]);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $available = $config['messages_per_hour'] - $sentLastHour - $pending;
        
        return [
            'can_add' => $available > 0,
            'available_slots' => max(0, $available),
            'sent_last_hour' => $sentLastHour,
            'pending' => $pending,
            'limit_per_hour' => $config['messages_per_hour'],
            'reason' => $available > 0 ? 'OK' : 'Limite de mensagens por hora atingido'
        ];
        
    } catch (Exception $e) {
        error_log("Queue Helper - Erro ao verificar limites: " . $e->getMessage());
        return [
            'can_add' => true,
            'reason' => 'Erro ao verificar limites, permitindo por segurança'
        ];
    }
}
