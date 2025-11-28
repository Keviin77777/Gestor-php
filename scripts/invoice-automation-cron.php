<?php
/**
 * Script para Automação de Faturas via Cron Job
 * Execute este script diariamente para gerar faturas automáticas
 * 
 * Configuração recomendada do cron job:
 * # Executar 3 vezes por dia (09:00, 14:00, 20:00)
 * 0 9,14,20 * * * /usr/bin/php /caminho/para/o/projeto/scripts/invoice-automation-cron.php
 * 
 * Para Windows (Task Scheduler):
 * - Programa: php.exe
 * - Argumentos: C:\caminho\para\o\projeto\scripts\invoice-automation-cron.php
 * - Executar: Diariamente às 09:00, 14:00 e 20:00
 */

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Evitar execução múltipla simultânea
$lockFile = __DIR__ . '/../logs/invoice-automation.lock';
if (file_exists($lockFile)) {
    $lockTime = filemtime($lockFile);
    // Se o lock tem mais de 1 hora, remover (processo travado)
    if (time() - $lockTime > 3600) {
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
require_once __DIR__ . '/../app/helpers/invoice-automation.php';

// Carregar configurações
loadEnv(__DIR__ . '/../.env');

// Log de início
$logFile = __DIR__ . '/../logs/invoice-automation.log';
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
    writeLog("=== INICIANDO AUTOMAÇÃO DE FATURAS ===");
    writeLog("PID: " . getmypid());
    writeLog("Memória inicial: " . memory_get_usage(true) . " bytes");
    
    // Verificar se a automação está habilitada
    $automationEnabled = getenv('INVOICE_AUTOMATION_ENABLED') !== 'false';
    if (!$automationEnabled) {
        writeLog("Automação desabilitada via configuração");
        exit(0);
    }
    
    // Buscar todos os revendedores ativos
    $db = Database::connect();
    $stmt = $db->query("SELECT id, name, email FROM resellers WHERE status = 'active'");
    $resellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    writeLog("Encontrados " . count($resellers) . " revendedores ativos");
    
    $totalReport = [
        'total_clients_checked' => 0,
        'invoices_generated' => 0,
        'clients_processed' => [],
        'errors' => [],
        'skipped_clients' => []
    ];
    
    // Executar automação para cada revendedor
    foreach ($resellers as $reseller) {
        writeLog("Processando revendedor: {$reseller['name']} (ID: {$reseller['id']})");
        
        $report = runInvoiceAutomation($reseller['id']);
        
        // Consolidar relatórios
        $totalReport['total_clients_checked'] += $report['total_clients_checked'];
        $totalReport['invoices_generated'] += $report['invoices_generated'];
        $totalReport['clients_processed'] = array_merge($totalReport['clients_processed'], $report['clients_processed']);
        $totalReport['errors'] = array_merge($totalReport['errors'], $report['errors']);
        $totalReport['skipped_clients'] = array_merge($totalReport['skipped_clients'], $report['skipped_clients']);
        
        writeLog("  -> Clientes verificados: {$report['total_clients_checked']}");
        writeLog("  -> Faturas geradas: {$report['invoices_generated']}");
    }
    
    $report = $totalReport;
    
    // Log do relatório
    writeLog("Clientes verificados: " . $report['total_clients_checked']);
    writeLog("Faturas geradas: " . $report['invoices_generated']);
    writeLog("Memória final: " . memory_get_usage(true) . " bytes");
    
    if (isset($report['error'])) {
        writeLog("ERRO: " . $report['error']);
        
        // Enviar notificação de erro (se configurado)
        $adminEmail = getenv('ADMIN_EMAIL');
        if ($adminEmail && function_exists('mail')) {
            $subject = "Erro na Automação de Faturas - " . date('Y-m-d H:i:s');
            $message = "Erro na automação de faturas:\n\n" . $report['error'];
            mail($adminEmail, $subject, $message);
        }
    } else {
        // Log detalhado dos clientes processados
        foreach ($report['clients_processed'] as $clientProcess) {
            $status = $clientProcess['result']['generated'] ? 'FATURA GERADA' : 'SEM FATURA';
            $reason = $clientProcess['result']['reason'] ?? $clientProcess['result']['message'] ?? '';
            
            writeLog("Cliente: {$clientProcess['client_name']} (ID: {$clientProcess['client_id']}) - {$status} - {$reason}");
            
            if (isset($clientProcess['result']['invoice_id'])) {
                writeLog("  -> Fatura ID: " . $clientProcess['result']['invoice_id']);
            }
        }
        
        // Notificar admin se faturas foram geradas
        if ($report['invoices_generated'] > 0) {
            $adminEmail = getenv('ADMIN_EMAIL');
            if ($adminEmail && function_exists('mail')) {
                $subject = "Faturas Geradas Automaticamente - " . date('Y-m-d');
                $message = "Foram geradas {$report['invoices_generated']} faturas automaticamente.\n\n";
                $message .= "Clientes:\n";
                
                foreach ($report['clients_processed'] as $clientProcess) {
                    if ($clientProcess['result']['generated']) {
                        $message .= "- {$clientProcess['client_name']} (Vence: {$clientProcess['renewal_date']})\n";
                    }
                }
                
                mail($adminEmail, $subject, $message);
            }
        }
    }
    
    writeLog("=== AUTOMAÇÃO CONCLUÍDA ===");
    
    // Salvar último status da execução
    $statusFile = __DIR__ . '/../logs/last-automation-status.json';
    file_put_contents($statusFile, json_encode([
        'last_run' => date('Y-m-d H:i:s'),
        'success' => !isset($report['error']),
        'clients_checked' => $report['total_clients_checked'],
        'invoices_generated' => $report['invoices_generated'],
        'error' => $report['error'] ?? null
    ]));
    
    // Se executado via linha de comando, mostrar resultado
    if (php_sapi_name() === 'cli') {
        echo "=== AUTOMAÇÃO DE FATURAS EXECUTADA ===\n";
        echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n";
        echo "Clientes verificados: " . $report['total_clients_checked'] . "\n";
        echo "Faturas geradas: " . $report['invoices_generated'] . "\n";
        
        if (isset($report['error'])) {
            echo "ERRO: " . $report['error'] . "\n";
        } elseif ($report['invoices_generated'] > 0) {
            echo "\nFaturas geradas para:\n";
            foreach ($report['clients_processed'] as $clientProcess) {
                if ($clientProcess['result']['generated']) {
                    echo "- " . $clientProcess['client_name'] . " (Vence: " . $clientProcess['renewal_date'] . ")\n";
                }
            }
        } else {
            echo "Nenhuma fatura precisou ser gerada.\n";
        }
        
        echo "\nLog completo: {$logFile}\n";
        echo "Status salvo: {$statusFile}\n";
    }
    
} catch (Exception $e) {
    $errorMsg = "ERRO CRÍTICO na automação: " . $e->getMessage();
    writeLog($errorMsg);
    writeLog("Stack trace: " . $e->getTraceAsString());
    
    // Salvar status de erro
    $statusFile = __DIR__ . '/../logs/last-automation-status.json';
    file_put_contents($statusFile, json_encode([
        'last_run' => date('Y-m-d H:i:s'),
        'success' => false,
        'error' => $e->getMessage(),
        'clients_checked' => 0,
        'invoices_generated' => 0
    ]));
    
    if (php_sapi_name() === 'cli') {
        echo $errorMsg . "\n";
    }
    
    exit(1);
}
?>