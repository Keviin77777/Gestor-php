<?php
/**
 * Controle do Serviço de Automação WhatsApp
 * Script para iniciar, parar e verificar status do serviço
 */

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';

// Arquivos de controle
$serviceControlFile = __DIR__ . '/../logs/whatsapp-service-control.json';
$pidFile = __DIR__ . '/../logs/whatsapp-service.pid';
$logFile = __DIR__ . '/../logs/whatsapp-automation-service.log';

// Função para mostrar ajuda
function showHelp() {
    echo "Controle do Serviço de Automação WhatsApp\n";
    echo "==========================================\n\n";
    echo "Uso: php whatsapp-service-control.php [comando]\n\n";
    echo "Comandos disponíveis:\n";
    echo "  start     - Iniciar o serviço\n";
    echo "  stop      - Parar o serviço\n";
    echo "  restart   - Reiniciar o serviço\n";
    echo "  status    - Verificar status do serviço\n";
    echo "  logs      - Mostrar logs recentes\n";
    echo "  run       - Executar automação manualmente\n";
    echo "  help      - Mostrar esta ajuda\n\n";
}

// Função para verificar se o serviço está rodando
function isServiceRunning() {
    global $pidFile;
    
    if (!file_exists($pidFile)) {
        return false;
    }
    
    $pid = trim(file_get_contents($pidFile));
    
    if (empty($pid)) {
        return false;
    }
    
    // Verificar se o processo ainda existe
    if (PHP_OS_FAMILY === 'Windows') {
        $result = shell_exec("tasklist /FI \"PID eq $pid\" 2>NUL");
        return strpos($result, $pid) !== false;
    } else {
        $result = shell_exec("ps -p $pid 2>/dev/null");
        return !empty($result);
    }
}

// Função para iniciar o serviço
function startService() {
    global $serviceControlFile;
    
    if (isServiceRunning()) {
        echo "❌ Serviço já está rodando!\n";
        return false;
    }
    
    // Remover arquivo de controle de parada se existir
    if (file_exists($serviceControlFile)) {
        $control = json_decode(file_get_contents($serviceControlFile), true);
        if (isset($control['stop'])) {
            unset($control['stop']);
            file_put_contents($serviceControlFile, json_encode($control, JSON_PRETTY_PRINT));
        }
    }
    
    // Iniciar serviço em background
    $serviceScript = __DIR__ . '/whatsapp-automation-service.php';
    
    if (PHP_OS_FAMILY === 'Windows') {
        // Windows
        $command = "start /B php \"$serviceScript\"";
        pclose(popen($command, 'r'));
    } else {
        // Linux/Unix
        $command = "nohup php \"$serviceScript\" > /dev/null 2>&1 &";
        exec($command);
    }
    
    // Aguardar um pouco e verificar se iniciou
    sleep(2);
    
    if (isServiceRunning()) {
        echo "✅ Serviço iniciado com sucesso!\n";
        return true;
    } else {
        echo "❌ Falha ao iniciar o serviço!\n";
        return false;
    }
}

// Função para parar o serviço
function stopService() {
    global $serviceControlFile;
    
    if (!isServiceRunning()) {
        echo "❌ Serviço não está rodando!\n";
        return false;
    }
    
    // Marcar para parar
    $control = [];
    if (file_exists($serviceControlFile)) {
        $control = json_decode(file_get_contents($serviceControlFile), true) ?: [];
    }
    
    $control['stop'] = true;
    $control['stop_requested_at'] = date('Y-m-d H:i:s');
    
    file_put_contents($serviceControlFile, json_encode($control, JSON_PRETTY_PRINT));
    
    echo "⏳ Parando serviço...\n";
    
    // Aguardar até 30 segundos para o serviço parar
    $maxWait = 30;
    $waited = 0;
    
    while (isServiceRunning() && $waited < $maxWait) {
        sleep(1);
        $waited++;
        echo ".";
    }
    
    echo "\n";
    
    if (!isServiceRunning()) {
        echo "✅ Serviço parado com sucesso!\n";
        return true;
    } else {
        echo "❌ Falha ao parar o serviço! Tentando forçar...\n";
        
        // Tentar parar forçadamente
        global $pidFile;
        if (file_exists($pidFile)) {
            $pid = trim(file_get_contents($pidFile));
            if (PHP_OS_FAMILY === 'Windows') {
                exec("taskkill /F /PID $pid 2>NUL");
            } else {
                exec("kill -9 $pid 2>/dev/null");
            }
            unlink($pidFile);
        }
        
        if (!isServiceRunning()) {
            echo "✅ Serviço parado forçadamente!\n";
            return true;
        } else {
            echo "❌ Não foi possível parar o serviço!\n";
            return false;
        }
    }
}

// Função para reiniciar o serviço
function restartService() {
    echo "🔄 Reiniciando serviço...\n";
    
    if (isServiceRunning()) {
        stopService();
        sleep(2);
    }
    
    startService();
}

// Função para verificar status
function showStatus() {
    global $serviceControlFile, $logFile;
    
    echo "Status do Serviço de Automação WhatsApp\n";
    echo "=======================================\n\n";
    
    if (isServiceRunning()) {
        echo "Status: ✅ RODANDO\n";
        
        if (file_exists($serviceControlFile)) {
            $control = json_decode(file_get_contents($serviceControlFile), true);
            if ($control) {
                echo "Última atualização: " . ($control['last_update'] ?? 'N/A') . "\n";
                echo "PID: " . ($control['pid'] ?? 'N/A') . "\n";
                echo "Status interno: " . ($control['status'] ?? 'N/A') . "\n";
                
                if (isset($control['data'])) {
                    $data = $control['data'];
                    if (isset($data['last_automation'])) {
                        echo "Última automação: " . $data['last_automation'] . "\n";
                    }
                    if (isset($data['reminders_sent'])) {
                        echo "Lembretes enviados: " . $data['reminders_sent'] . "\n";
                    }
                    if (isset($data['clients_processed'])) {
                        echo "Clientes processados: " . $data['clients_processed'] . "\n";
                    }
                    if (isset($data['errors'])) {
                        echo "Erros: " . $data['errors'] . "\n";
                    }
                }
            }
        }
    } else {
        echo "Status: ❌ PARADO\n";
    }
    
    echo "\n";
}

// Função para mostrar logs
function showLogs($lines = 20) {
    global $logFile;
    
    if (!file_exists($logFile)) {
        echo "❌ Arquivo de log não encontrado!\n";
        return;
    }
    
    echo "Logs recentes (últimas $lines linhas):\n";
    echo "=====================================\n\n";
    
    $logContent = file_get_contents($logFile);
    $logLines = explode("\n", $logContent);
    $logLines = array_filter($logLines);
    $recentLines = array_slice($logLines, -$lines);
    
    foreach ($recentLines as $line) {
        echo $line . "\n";
    }
}

// Função para executar automação manualmente
function runAutomation() {
    echo "🚀 Executando automação manualmente...\n\n";
    
    // Carregar dependências
    require_once __DIR__ . '/../app/core/Database.php';
    require_once __DIR__ . '/../app/helpers/whatsapp-automation.php';
    
    loadEnv(__DIR__ . '/../.env');
    
    try {
        // 1. Executar templates agendados
        echo "📅 Verificando templates agendados...\n";
        
        // Debug: Mostrar informações de debug
        $currentDay = strtolower(date('l'));
        $currentTime = date('H:i:s');
        echo "Dia atual: $currentDay\n";
        echo "Hora atual: $currentTime\n\n";
        
        // Debug: Buscar todos os templates
        // Obter reseller_id do usuário autenticado ou usar padrão
        $resellerId = 'admin-001'; // Padrão para scripts
        if (isset($_SESSION['user_id'])) {
            $resellerId = $_SESSION['user_id'];
        }
        
        $allTemplates = Database::fetchAll(
            "SELECT id, name, type, is_scheduled, scheduled_days, scheduled_time, is_active 
             FROM whatsapp_templates 
             WHERE reseller_id = ?",
            [$resellerId]
        );
        
        echo "Total de templates no banco: " . count($allTemplates) . "\n";
        foreach ($allTemplates as $tpl) {
            echo "- {$tpl['name']} (Tipo: {$tpl['type']})\n";
            echo "  Agendado: " . ($tpl['is_scheduled'] ? 'SIM' : 'NÃO') . "\n";
            echo "  Ativo: " . ($tpl['is_active'] ? 'SIM' : 'NÃO') . "\n";
            echo "  Dias: " . ($tpl['scheduled_days'] ?: 'NULL') . "\n";
            echo "  Horário: " . ($tpl['scheduled_time'] ?: 'NULL') . "\n\n";
        }
        
        $scheduledReport = runScheduledTemplates();
        
        // Mostrar debug
        if (!empty($scheduledReport['debug'])) {
            echo "\n🔍 Debug:\n";
            foreach ($scheduledReport['debug'] as $debugMsg) {
                echo "  $debugMsg\n";
            }
            echo "\n";
        }
        
        echo "Templates agendados processados: " . count($scheduledReport['templates_processed']) . "\n";
        echo "Mensagens enviadas (agendadas): " . $scheduledReport['messages_sent'] . "\n\n";
        
        // 2. Executar automação de lembretes
        echo "⏰ Verificando lembretes de vencimento...\n";
        $reminderReport = runWhatsAppReminderAutomation();
        
        echo "Lembretes enviados: " . $reminderReport['reminders_sent'] . "\n";
        echo "Clientes processados: " . count($reminderReport['clients_processed']) . "\n\n";
        
        // Consolidar relatório
        $totalSent = $scheduledReport['messages_sent'] + $reminderReport['reminders_sent'];
        $totalErrors = count($scheduledReport['errors']) + count($reminderReport['errors']);
        
        echo "✅ Automação executada com sucesso!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Total de mensagens enviadas: $totalSent\n";
        echo "Total de erros: $totalErrors\n\n";
        
        if (!empty($scheduledReport['templates_processed'])) {
            echo "📋 Templates agendados processados:\n";
            foreach ($scheduledReport['templates_processed'] as $item) {
                echo "- Template ID: {$item['template_id']} → Cliente ID: {$item['client_id']} ({$item['status']})\n";
            }
            echo "\n";
        }
        
        if (!empty($reminderReport['clients_processed'])) {
            echo "📨 Lembretes enviados:\n";
            foreach ($reminderReport['clients_processed'] as $client) {
                echo "- {$client['client_name']} ({$client['template_type']}) - {$client['days_until_renewal']} dias\n";
            }
            echo "\n";
        }
        
        if (!empty($scheduledReport['errors']) || !empty($reminderReport['errors'])) {
            echo "❌ Erros encontrados:\n";
            foreach ($scheduledReport['errors'] as $error) {
                if (isset($error['global'])) {
                    echo "- [Global] {$error['global']}\n";
                } else {
                    echo "- Template {$error['template_id']}: {$error['error']}\n";
                }
            }
            foreach ($reminderReport['errors'] as $error) {
                echo "- {$error['client_name']}: {$error['error']}\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Erro ao executar automação: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
}

// Processar comando
$command = $argv[1] ?? 'help';

switch ($command) {
    case 'start':
        startService();
        break;
        
    case 'stop':
        stopService();
        break;
        
    case 'restart':
        restartService();
        break;
        
    case 'status':
        showStatus();
        break;
        
    case 'logs':
        $lines = $argv[2] ?? 20;
        showLogs((int)$lines);
        break;
        
    case 'run':
        runAutomation();
        break;
        
    case 'help':
    default:
        showHelp();
        break;
}
?>
