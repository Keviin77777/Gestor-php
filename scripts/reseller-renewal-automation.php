<?php
/**
 * Script de Automação de Renovação para Revendedores
 * Envia lembretes e cobranças via WhatsApp para revendedores com planos próximos ao vencimento
 * Usa sistema de fila e templates do banco de dados (mesmo fluxo dos clientes)
 * 
 * Executar via cron: php scripts/reseller-renewal-automation.php
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/queue-helper.php';

loadEnv(__DIR__ . '/../.env');

$logFile = __DIR__ . '/../logs/reseller-automation-' . date('Y-m-d') . '.log';

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    echo $logMessage;
    
    $logDir = dirname($logFile);
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Processar template com variáveis do revendedor
 */
function processResellerTemplate($templateMessage, $reseller) {
    $variables = [
        'revendedor_nome' => $reseller['name'],
        'revendedor_email' => $reseller['email'],
        'revendedor_plano' => $reseller['plan_name'] ?: 'Sem plano',
        'revendedor_vencimento' => date('d/m/Y', strtotime($reseller['plan_expires_at'])),
        'revendedor_valor' => 'R$ ' . number_format($reseller['plan_price'], 2, ',', '.'),
        'revendedor_dias' => abs($reseller['days_until_expiry']),
        'link_renovacao' => env('APP_URL', 'http://localhost:8000') . '/renew-access'
    ];
    
    $processedMessage = $templateMessage;
    foreach ($variables as $key => $value) {
        $processedMessage = str_replace('{{' . $key . '}}', $value, $processedMessage);
    }
    
    return $processedMessage;
}

/**
 * Determinar tipo de template baseado nos dias até vencimento
 */
function getTemplateType($daysUntilExpiry) {
    if ($daysUntilExpiry >= 7) {
        return 'reseller_renewal_7d';
    } elseif ($daysUntilExpiry >= 3) {
        return 'reseller_renewal_3d';
    } elseif ($daysUntilExpiry >= 1) {
        return 'reseller_renewal_1d';
    } elseif ($daysUntilExpiry === 0) {
        return 'reseller_expires_today';
    } elseif ($daysUntilExpiry === -1) {
        return 'reseller_expired_1d';
    } elseif ($daysUntilExpiry >= -3) {
        return 'reseller_expired_3d';
    } else {
        return 'reseller_expired_7d';
    }
}

function processResellers() {
    logMessage("=== Iniciando processamento de revendedores ===");
    
    // Admin que vai enviar as mensagens (deve ter WhatsApp conectado)
    $adminResellerId = 'admin-001';
    
    // Buscar revendedores com planos próximos ao vencimento
    $resellers = Database::fetchAll("
        SELECT 
            u.id,
            u.name,
            u.email,
            COALESCE(u.phone, u.whatsapp) as phone,
            u.plan_expires_at,
            rp.name as plan_name,
            rp.price as plan_price,
            DATEDIFF(DATE(u.plan_expires_at), CURDATE()) as days_until_expiry
        FROM users u
        LEFT JOIN reseller_plans rp ON u.current_plan_id = rp.id
        WHERE u.role != 'admin' 
        AND u.plan_expires_at IS NOT NULL
        AND DATEDIFF(DATE(u.plan_expires_at), CURDATE()) BETWEEN -7 AND 7
        ORDER BY u.plan_expires_at ASC
    ");
    
    logMessage("Encontrados " . count($resellers) . " revendedores para processar");
    
    $queued = 0;
    $skipped = 0;
    $errors = 0;
    
    foreach ($resellers as $reseller) {
        $daysUntilExpiry = (int)$reseller['days_until_expiry'];
        $phone = $reseller['phone'];
        
        if (empty($phone)) {
            logMessage("⚠️ Revendedor {$reseller['name']} sem telefone cadastrado");
            $skipped++;
            continue;
        }
        
        // Determinar tipo de template
        $templateType = getTemplateType($daysUntilExpiry);
        
        // Buscar template do banco (admin-001 é quem tem os templates de revendedores)
        $template = Database::fetch("
            SELECT * FROM whatsapp_templates 
            WHERE reseller_id = ? 
            AND type = ? 
            AND is_active = 1 
            ORDER BY is_default DESC, created_at DESC 
            LIMIT 1
        ", [$adminResellerId, $templateType]);
        
        if (!$template) {
            logMessage("⚠️ Template '$templateType' não encontrado para {$reseller['name']}");
            $skipped++;
            continue;
        }
        
        // Verificar se já existe na fila para hoje
        $existingInQueue = Database::fetch("
            SELECT id FROM whatsapp_message_queue 
            WHERE phone = ? 
            AND template_id = ? 
            AND DATE(created_at) = CURDATE()
            AND status IN ('pending', 'processing')
        ", [$phone, $template['id']]);
        
        if ($existingInQueue) {
            logMessage("ℹ️ Já existe na fila para {$reseller['name']} hoje");
            $skipped++;
            continue;
        }
        
        // Processar mensagem com variáveis
        $message = processResellerTemplate($template['message'], $reseller);
        
        // Verificar agendamento do template
        $scheduledAt = null;
        if ($template['is_scheduled'] && $template['scheduled_time']) {
            $currentDay = strtolower(date('l'));
            $scheduledDays = json_decode($template['scheduled_days'], true) ?: [];
            
            if (in_array($currentDay, $scheduledDays)) {
                $scheduledTime = $template['scheduled_time'];
                $scheduledDateTime = date('Y-m-d') . ' ' . $scheduledTime;
                $scheduledTimestamp = strtotime($scheduledDateTime);
                $currentTimestamp = time();
                
                if ($scheduledTimestamp > $currentTimestamp) {
                    $scheduledAt = $scheduledDateTime;
                    logMessage("📅 Agendado para hoje às {$scheduledTime}");
                } else {
                    $scheduledAt = date('Y-m-d H:i:s', strtotime('+1 minute'));
                    logMessage("⚡ Enviando imediatamente");
                }
            } else {
                // Encontrar próximo dia agendado
                $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                $currentDayIndex = array_search($currentDay, $daysOfWeek);
                
                for ($i = 1; $i <= 7; $i++) {
                    $nextDayIndex = ($currentDayIndex + $i) % 7;
                    $nextDay = $daysOfWeek[$nextDayIndex];
                    
                    if (in_array($nextDay, $scheduledDays)) {
                        $scheduledAt = date('Y-m-d', strtotime("+{$i} days")) . ' ' . $template['scheduled_time'];
                        logMessage("📅 Agendado para próximo dia: {$scheduledAt}");
                        break;
                    }
                }
            }
        }
        
        // Determinar prioridade
        $priority = 0; // Normal
        if (in_array($templateType, ['reseller_expires_today', 'reseller_expired_1d'])) {
            $priority = 2; // Alta prioridade para vencimentos urgentes
        } elseif ($daysUntilExpiry <= 3 && $daysUntilExpiry >= 0) {
            $priority = 1; // Média prioridade
        }
        
        // Adicionar à fila
        $result = addMessageToQueue(
            $adminResellerId, // Admin envia as mensagens
            $phone,
            $message,
            $template['id'],
            null, // Não é cliente, é revendedor
            $priority,
            $scheduledAt
        );
        
        if ($result['success']) {
            $queued++;
            logMessage("✅ Adicionado à fila: {$reseller['name']} ({$phone}) - {$templateType} - {$daysUntilExpiry} dias");
            
            // Registrar no log de mensagens de revendedores
            try {
                Database::query("
                    INSERT INTO whatsapp_messages_log 
                    (recipient_id, recipient_phone, message, message_type, sent_at, status)
                    VALUES (?, ?, ?, ?, NOW(), 'queued')
                ", [
                    $reseller['id'],
                    $phone,
                    $message,
                    'reseller_renewal'
                ]);
            } catch (Exception $e) {
                logMessage("⚠️ Erro ao registrar log: " . $e->getMessage());
            }
        } else {
            $errors++;
            logMessage("❌ Erro ao adicionar à fila: {$reseller['name']} - " . $result['error']);
        }
    }
    
    logMessage("=== Processamento concluído ===");
    logMessage("✅ Adicionados à fila: $queued");
    logMessage("⏭️ Ignorados: $skipped");
    logMessage("❌ Erros: $errors");
}

// Executar
try {
    processResellers();
} catch (Exception $e) {
    logMessage("ERRO FATAL: " . $e->getMessage());
    logMessage($e->getTraceAsString());
}
