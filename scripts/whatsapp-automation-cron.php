<?php
/**
 * Script para Automação de WhatsApp via Cron Job
 * Execute este script diariamente para enviar lembretes automáticos
 * 
 * Configuração recomendada do cron job:
 * # Executar 2 vezes por dia (09:00 e 18:00)
 * 0 9,18 * * * /usr/bin/php /caminho/para/o/projeto/scripts/whatsapp-automation-cron.php
 * 
 * Para Windows (Task Scheduler):
 * - Programa: php.exe
 * - Argumentos: C:\caminho\para\o\projeto\scripts\whatsapp-automation-cron.php
 * - Executar: Diariamente às 09:00 e 18:00
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
    writeLog("=== INICIANDO AUTOMAÇÃO WHATSAPP ===");
    
    // Executar agendamentos personalizados
    $scheduledReport = runScheduledTemplates();
    writeLog("Agendamentos personalizados: {$scheduledReport['messages_sent']} mensagens enviadas");
    
    // Executar automação de lembretes
    $report = runWhatsAppReminderAutomation();
    writeLog("Lembretes enviados: " . $report['reminders_sent']);
    writeLog("Clientes processados: " . count($report['clients_processed']));
    writeLog("Erros: " . count($report['errors']));
    
    // Log detalhado dos clientes processados
    foreach ($report['clients_processed'] as $client) {
        writeLog("Cliente: {$client['client_name']} - Template: {$client['template_type']} - Dias: {$client['days_until_renewal']}");
    }
    
    // Log dos erros
    foreach ($report['errors'] as $error) {
        writeLog("ERRO - Cliente: {$error['client_name']} - Erro: {$error['error']}");
    }
    
    $totalMessages = $scheduledReport['messages_sent'] + $report['reminders_sent'];
    writeLog("=== AUTOMAÇÃO WHATSAPP FINALIZADA - Total: {$totalMessages} mensagens ===");
    
} catch (Exception $e) {
    writeLog("ERRO CRÍTICO: " . $e->getMessage());
    writeLog("Stack trace: " . $e->getTraceAsString());
} finally {
    // Remover lock
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}
?>
