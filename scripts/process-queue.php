<?php
/**
 * Processador de fila de mensagens WhatsApp
 * Respeita os limites configurados por revendedor
 * 
 * Uso: php scripts/process-queue.php
 * Ou configure no cron: * * * * * php /caminho/scripts/process-queue.php
 */

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/whatsapp-helper.php';

$logFile = __DIR__ . '/../logs/queue-processor.log';
$logDir = dirname($logFile);

if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
    echo "[{$timestamp}] {$message}\n";
}

try {
    $db = Database::connect();
    logMessage("=== Iniciando processamento da fila ===");
    
    // Buscar revendedores com mensagens pendentes
    $stmt = $db->query("
        SELECT DISTINCT reseller_id 
        FROM whatsapp_message_queue 
        WHERE status = 'pending'
        AND (scheduled_at IS NULL OR scheduled_at <= NOW())
    ");
    $resellers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($resellers)) {
        logMessage("Nenhuma mensagem pendente na fila");
        exit(0);
    }
    
    logMessage("Encontrados " . count($resellers) . " revendedores com mensagens pendentes");
    
    foreach ($resellers as $resellerId) {
        processResellerQueue($db, $resellerId);
    }
    
    logMessage("=== Processamento concluído ===");
    
} catch (Exception $e) {
    logMessage("ERRO: " . $e->getMessage());
    exit(1);
}

/**
 * Processar fila de um revendedor específico
 */
function processResellerQueue($db, $resellerId) {
    logMessage("Processando fila do revendedor: {$resellerId}");
    
    // Buscar configuração de rate limit
    $stmt = $db->prepare("
        SELECT * FROM whatsapp_rate_limit_config 
        WHERE reseller_id = ?
    ");
    $stmt->execute([$resellerId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        // Criar configuração padrão
        $stmt = $db->prepare("
            INSERT INTO whatsapp_rate_limit_config 
            (reseller_id, messages_per_minute, messages_per_hour, delay_between_messages)
            VALUES (?, 20, 100, 3)
        ");
        $stmt->execute([$resellerId]);
        
        $config = [
            'messages_per_minute' => 20,
            'messages_per_hour' => 100,
            'delay_between_messages' => 3
        ];
    }
    
    logMessage("Limites: {$config['messages_per_minute']}/min, {$config['messages_per_hour']}/hora, delay {$config['delay_between_messages']}s");
    
    // Verificar quantas mensagens foram enviadas na última hora
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM whatsapp_message_queue 
        WHERE reseller_id = ? 
        AND status = 'sent'
        AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$resellerId]);
    $sentLastHour = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($sentLastHour >= $config['messages_per_hour']) {
        logMessage("Limite de {$config['messages_per_hour']} mensagens/hora atingido ({$sentLastHour} enviadas)");
        return;
    }
    
    // Verificar quantas mensagens foram enviadas no último minuto
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM whatsapp_message_queue 
        WHERE reseller_id = ? 
        AND status = 'sent'
        AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $stmt->execute([$resellerId]);
    $sentLastMinute = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($sentLastMinute >= $config['messages_per_minute']) {
        logMessage("Limite de {$config['messages_per_minute']} mensagens/minuto atingido ({$sentLastMinute} enviadas)");
        return;
    }
    
    // Calcular quantas mensagens podemos enviar agora
    $canSendHour = $config['messages_per_hour'] - $sentLastHour;
    $canSendMinute = $config['messages_per_minute'] - $sentLastMinute;
    $canSend = min($canSendHour, $canSendMinute, 10); // Máximo 10 por execução
    
    logMessage("Pode enviar: {$canSend} mensagens (hora: {$canSendHour}, minuto: {$canSendMinute})");
    
    if ($canSend <= 0) {
        logMessage("Nenhuma mensagem pode ser enviada no momento");
        return;
    }
    
    // Buscar mensagens pendentes
    $stmt = $db->prepare("
        SELECT * FROM whatsapp_message_queue 
        WHERE reseller_id = ? 
        AND status = 'pending'
        AND (scheduled_at IS NULL OR scheduled_at <= NOW())
        AND attempts < max_attempts
        ORDER BY priority DESC, created_at ASC
        LIMIT ?
    ");
    $stmt->execute([$resellerId, $canSend]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($messages)) {
        logMessage("Nenhuma mensagem pendente encontrada");
        return;
    }
    
    logMessage("Enviando " . count($messages) . " mensagens...");
    
    $sent = 0;
    $failed = 0;
    
    foreach ($messages as $msg) {
        // Marcar como processando
        $updateStmt = $db->prepare("
            UPDATE whatsapp_message_queue 
            SET status = 'processing', updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$msg['id']]);
        
        // Tentar enviar
        try {
            logMessage("Enviando mensagem #{$msg['id']} para {$msg['phone']}");
            
            $result = sendWhatsAppMessage(
                $resellerId,
                $msg['phone'],
                $msg['message'],
                $msg['template_id'],
                $msg['client_id']
            );
            
            if ($result['success']) {
                // Sucesso
                $updateStmt = $db->prepare("
                    UPDATE whatsapp_message_queue 
                    SET status = 'sent', sent_at = NOW(), updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$msg['id']]);
                
                logMessage("✅ Mensagem #{$msg['id']} enviada com sucesso");
                $sent++;
                
                // Aguardar delay configurado
                if ($config['delay_between_messages'] > 0) {
                    logMessage("Aguardando {$config['delay_between_messages']}s...");
                    sleep($config['delay_between_messages']);
                }
                
            } else {
                // Falha
                $attempts = $msg['attempts'] + 1;
                $status = $attempts >= $msg['max_attempts'] ? 'failed' : 'pending';
                
                $updateStmt = $db->prepare("
                    UPDATE whatsapp_message_queue 
                    SET status = ?, attempts = ?, error_message = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $status,
                    $attempts,
                    $result['error'] ?? 'Erro desconhecido',
                    $msg['id']
                ]);
                
                logMessage("❌ Falha ao enviar mensagem #{$msg['id']}: " . ($result['error'] ?? 'Erro desconhecido'));
                $failed++;
            }
            
        } catch (Exception $e) {
            // Erro ao enviar
            $attempts = $msg['attempts'] + 1;
            $status = $attempts >= $msg['max_attempts'] ? 'failed' : 'pending';
            
            $updateStmt = $db->prepare("
                UPDATE whatsapp_message_queue 
                SET status = ?, attempts = ?, error_message = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([
                $status,
                $attempts,
                $e->getMessage(),
                $msg['id']
            ]);
            
            logMessage("❌ Erro ao enviar mensagem #{$msg['id']}: " . $e->getMessage());
            $failed++;
        }
    }
    
    logMessage("Resumo: {$sent} enviadas, {$failed} falharam");
}
