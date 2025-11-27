<?php
/**
 * Script para Automação de WhatsApp via Cron Job
 * 
 * IMPORTANTE: Execute este script A CADA HORA para que os agendamentos funcionem corretamente
 * 
 * Configuração recomendada do cron job:
 * # Linux/Mac - Executar a cada hora
 * 0 * * * * /usr/bin/php /caminho/para/o/projeto/scripts/whatsapp-automation-cron.php
 * 
 * Para Windows (Task Scheduler):
 * - Programa: php.exe
 * - Argumentos: C:\caminho\para\o\projeto\scripts\whatsapp-automation-cron.php
 * - Gatilho: Diariamente às 00:00
 * - Repetir a cada: 1 hora
 * - Duração: 1 dia
 * 
 * Como funciona:
 * 1. Executa a cada hora (00:00, 01:00, 02:00... 23:00)
 * 2. Verifica se há templates agendados para o horário atual (tolerância de 5 minutos)
 * 3. Verifica clientes que precisam receber lembretes de vencimento
 * 4. Envia as mensagens necessárias
 * 5. Registra tudo no log
 */

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Evitar execução múltipla simultânea
$lockFile = __DIR__ . '/../logs/whatsapp-automation.lock';
if (file_exists($lockFile)) {
    $lockTime = filemtime($lockFile);
    // Se o lock tem mais de 2 horas, remover (processo travado)
    if (time() - $lockTime > 7200) {
        unlink($lockFile);
    } else {
        exit(0); // Já está executando
    }
}

// Criar lock
file_put_contents($lockFile, getmypid());

// Remover lock ao finalizar
register_shutdown_function(function() use ($lockFile) {
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
});

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/whatsapp-automation.php';

// Carregar configurações
loadEnv(__DIR__ . '/../.env');

// Log de início
$logFile = __DIR__ . '/../logs/whatsapp-automation.log';
$logDir = dirname($logFile);

// Criar diretório de logs se não existir
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND | LOCK_EX);
}

try {
    $currentHour = date('H:i');
    $currentDay = strtolower(date('l'));
    
    writeLog("=== INICIANDO AUTOMAÇÃO WHATSAPP ===");
    writeLog("Hora atual: $currentHour | Dia: $currentDay");
    
    // Buscar todos os resellers ativos (baseado nos clientes)
    $resellers = Database::fetchAll("SELECT DISTINCT reseller_id FROM clients WHERE reseller_id IS NOT NULL");
    
    if (empty($resellers)) {
        writeLog("⚠️  Nenhum reseller encontrado");
        exit(0);
    }
    
    writeLog("📊 Total de resellers encontrados: " . count($resellers));
    
    $totalMessagesAllResellers = 0;
    $totalErrorsAllResellers = 0;
    
    foreach ($resellers as $reseller) {
        $resellerId = $reseller['reseller_id'];
        writeLog("\n--- Processando Reseller: {$resellerId} ---");
        
        // 1. Executar agendamentos personalizados (templates configurados pelo usuário)
        writeLog("--- Verificando Templates Agendados ---");
        $scheduledReport = runScheduledTemplates($resellerId);
    
    // Escrever logs de debug
    if (!empty($scheduledReport['debug'])) {
        foreach ($scheduledReport['debug'] as $debugMsg) {
            writeLog("  [DEBUG] $debugMsg");
        }
    }
    
    if ($scheduledReport['messages_sent'] > 0) {
        writeLog("✅ Templates agendados: {$scheduledReport['messages_sent']} mensagens enviadas");
        foreach ($scheduledReport['templates_processed'] as $item) {
            writeLog("  → Template ID {$item['template_id']} enviado para cliente {$item['client_id']}");
        }
    } else {
        writeLog("ℹ️  Nenhum template agendado para este horário");
    }
    
        // 2. Executar automação de lembretes de vencimento
        writeLog("--- Verificando Lembretes de Vencimento ---");
        writeLog("ℹ️  Nota: Lembretes só são enviados se:");
        writeLog("   • auto_send_reminders = TRUE nas configurações");
        writeLog("   • Template NÃO tem agendamento ativo (is_scheduled = 0)");
        $report = runWhatsAppReminderAutomation($resellerId);
        
        if ($report['reminders_sent'] > 0) {
            writeLog("✅ Lembretes de vencimento: {$report['reminders_sent']} enviados");
            foreach ($report['clients_processed'] as $client) {
                writeLog("  → {$client['client_name']} ({$client['template_type']}) - {$client['days_until_renewal']} dias");
            }
        } else {
            writeLog("ℹ️  Nenhum lembrete de vencimento necessário");
        }
        
        // Consolidar resultados do reseller
        $totalMessages = $scheduledReport['messages_sent'] + $report['reminders_sent'];
        $totalErrors = count($scheduledReport['errors']) + count($report['errors']);
        
        $totalMessagesAllResellers += $totalMessages;
        $totalErrorsAllResellers += $totalErrors;
        
        writeLog("--- Resumo do Reseller {$resellerId} ---");
        writeLog("📊 Mensagens enviadas: {$totalMessages}");
        writeLog("📊 Erros: {$totalErrors}");
        
        // Log de erros detalhado
        if ($totalErrors > 0) {
            writeLog("--- Erros Encontrados ---");
            
            foreach ($scheduledReport['errors'] as $error) {
                if (isset($error['global'])) {
                    writeLog("❌ [Global] {$error['global']}");
                } else {
                    writeLog("❌ [Template {$error['template_id']}] Cliente {$error['client_id']}: {$error['error']}");
                }
            }
            
            foreach ($report['errors'] as $error) {
                writeLog("❌ [Lembrete] {$error['client_name']}: {$error['error']}");
            }
        }
    }
    
    writeLog("\n=== RESUMO GERAL ===");
    writeLog("📊 Total de resellers processados: " . count($resellers));
    writeLog("📊 Total de mensagens enviadas: {$totalMessagesAllResellers}");
    writeLog("📊 Total de erros: {$totalErrorsAllResellers}");
    writeLog("=== AUTOMAÇÃO FINALIZADA ===\n");
    
} catch (Exception $e) {
    writeLog("❌ ERRO CRÍTICO: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
} finally {
    // Remover lock
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}
?>
