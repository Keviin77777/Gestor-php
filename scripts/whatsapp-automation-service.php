<?php
/**
 * Serviço de Automação de WhatsApp
 * Roda continuamente em background verificando a necessidade de enviar mensagens
 * 
 * Para executar como serviço:
 * Windows: php whatsapp-automation-service.php
 * Linux: nohup php whatsapp-automation-service.php > /dev/null 2>&1 &
 */

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurar para execução contínua
set_time_limit(0);
ini_set('memory_limit', '256M');

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/whatsapp-automation.php';

// Carregar configurações
loadEnv(__DIR__ . '/../.env');

// Arquivo de controle do serviço
$serviceControlFile = __DIR__ . '/../logs/whatsapp-service-control.json';
$logFile = __DIR__ . '/../logs/whatsapp-automation-service.log';
$pidFile = __DIR__ . '/../logs/whatsapp-service.pid';

// Criar diretório de logs se não existir
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Função para escrever log
function writeServiceLog($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $pid = getmypid();
    file_put_contents($logFile, "[{$timestamp}] [{$level}] [PID:{$pid}] {$message}\n", FILE_APPEND | LOCK_EX);
}

// Função para verificar se deve parar o serviço
function shouldStopService() {
    global $serviceControlFile;
    
    if (file_exists($serviceControlFile)) {
        $control = json_decode(file_get_contents($serviceControlFile), true);
        return isset($control['stop']) && $control['stop'] === true;
    }
    
    return false;
}

// Função para salvar status do serviço
function saveServiceStatus($status, $data = []) {
    global $serviceControlFile;
    
    $statusData = [
        'status' => $status,
        'last_update' => date('Y-m-d H:i:s'),
        'pid' => getmypid(),
        'data' => $data
    ];
    
    file_put_contents($serviceControlFile, json_encode($statusData, JSON_PRETTY_PRINT), LOCK_EX);
}

// Função para salvar PID
function savePid() {
    global $pidFile;
    file_put_contents($pidFile, getmypid());
}

// Função para remover PID
function removePid() {
    global $pidFile;
    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
}

// Função principal de automação
function runAutomation() {
    try {
        writeServiceLog("Executando automação de lembretes...");
        
        $report = runWhatsAppReminderAutomation();
        
        writeServiceLog("Lembretes enviados: " . $report['reminders_sent']);
        writeServiceLog("Clientes processados: " . count($report['clients_processed']));
        writeServiceLog("Erros: " . count($report['errors']));
        
        // Log detalhado dos clientes processados
        foreach ($report['clients_processed'] as $client) {
            writeServiceLog("Cliente: {$client['client_name']} - Template: {$client['template_type']} - Dias: {$client['days_until_renewal']}");
        }
        
        // Log dos erros
        foreach ($report['errors'] as $error) {
            writeServiceLog("ERRO - Cliente: {$error['client_name']} - Erro: {$error['error']}", 'ERROR');
        }
        
        return $report;
        
    } catch (Exception $e) {
        writeServiceLog("ERRO na automação: " . $e->getMessage(), 'ERROR');
        writeServiceLog("Stack trace: " . $e->getTraceAsString(), 'ERROR');
        return null;
    }
}

// Função para verificar se está no horário de execução
function shouldRunNow() {
    $currentHour = (int)date('H');
    $currentMinute = (int)date('i');
    
    // Executar às 09:00, 14:00 e 20:00
    $executionTimes = [9, 14, 20];
    
    foreach ($executionTimes as $hour) {
        if ($currentHour === $hour && $currentMinute < 5) {
            return true;
        }
    }
    
    return false;
}

// Função para verificar se já executou hoje
function hasRunToday() {
    global $serviceControlFile;
    
    if (file_exists($serviceControlFile)) {
        $control = json_decode(file_get_contents($serviceControlFile), true);
        if (isset($control['last_run_date'])) {
            return $control['last_run_date'] === date('Y-m-d');
        }
    }
    
    return false;
}

// Função para marcar como executado hoje
function markAsRunToday() {
    global $serviceControlFile;
    
    $control = [];
    if (file_exists($serviceControlFile)) {
        $control = json_decode(file_get_contents($serviceControlFile), true) ?: [];
    }
    
    $control['last_run_date'] = date('Y-m-d');
    $control['last_run_time'] = date('H:i:s');
    
    file_put_contents($serviceControlFile, json_encode($control, JSON_PRETTY_PRINT), LOCK_EX);
}

// Inicializar serviço
writeServiceLog("=== INICIANDO SERVIÇO DE AUTOMAÇÃO WHATSAPP ===");
writeServiceLog("PID: " . getmypid());

// Salvar PID
savePid();

// Registrar função de limpeza ao finalizar
register_shutdown_function(function() {
    removePid();
    writeServiceLog("Serviço finalizado");
});

// Loop principal
$lastCheck = 0;
$checkInterval = 60; // Verificar a cada minuto

while (true) {
    try {
        // Verificar se deve parar
        if (shouldStopService()) {
            writeServiceLog("Comando de parada recebido, finalizando serviço...");
            break;
        }
        
        $currentTime = time();
        
        // Verificar se passou o intervalo de verificação
        if ($currentTime - $lastCheck >= $checkInterval) {
            $lastCheck = $currentTime;
            
            // Verificar se deve executar agora
            if (shouldRunNow() && !hasRunToday()) {
                writeServiceLog("Executando automação programada...");
                
                $report = runAutomation();
                
                if ($report) {
                    markAsRunToday();
                    saveServiceStatus('running', [
                        'last_automation' => date('Y-m-d H:i:s'),
                        'reminders_sent' => $report['reminders_sent'],
                        'clients_processed' => count($report['clients_processed']),
                        'errors' => count($report['errors'])
                    ]);
                } else {
                    saveServiceStatus('error', ['last_error' => date('Y-m-d H:i:s')]);
                }
            } else {
                // Salvar status de espera
                saveServiceStatus('waiting', [
                    'next_check' => date('Y-m-d H:i:s', $currentTime + $checkInterval),
                    'should_run' => shouldRunNow(),
                    'has_run_today' => hasRunToday()
                ]);
            }
        }
        
        // Dormir por 10 segundos antes da próxima verificação
        sleep(10);
        
    } catch (Exception $e) {
        writeServiceLog("ERRO no loop principal: " . $e->getMessage(), 'ERROR');
        saveServiceStatus('error', ['last_error' => date('Y-m-d H:i:s')]);
        sleep(30); // Aguardar 30 segundos antes de tentar novamente
    }
}

writeServiceLog("=== SERVIÇO FINALIZADO ===");
?>
