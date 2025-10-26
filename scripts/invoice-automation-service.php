<?php
/**
 * Serviço de Automação de Faturas
 * Roda continuamente em background verificando a necessidade de gerar faturas
 * 
 * Para executar como serviço:
 * Windows: php invoice-automation-service.php
 * Linux: nohup php invoice-automation-service.php > /dev/null 2>&1 &
 */

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurar para execução contínua
set_time_limit(0);
ini_set('memory_limit', '256M');

// Carregar dependências
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/invoice-automation.php';

// Carregar configurações
loadEnv(__DIR__ . '/../.env');
$config = require __DIR__ . '/../config/invoice-automation.php';

// Arquivo de controle do serviço
$serviceControlFile = __DIR__ . '/../logs/service-control.json';
$logFile = __DIR__ . '/../logs/invoice-automation-service.log';
$pidFile = __DIR__ . '/../logs/service.pid';

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
    
    $statusData = array_merge([
        'status' => $status,
        'pid' => getmypid(),
        'last_update' => date('Y-m-d H:i:s'),
        'memory_usage' => memory_get_usage(true),
        'uptime' => time() - $_SERVER['REQUEST_TIME']
    ], $data);
    
    file_put_contents($serviceControlFile, json_encode($statusData, JSON_PRETTY_PRINT));
}

// Função para verificar se é hora de executar
function isExecutionTime($executionTimes) {
    $currentTime = date('H:i:s');
    $currentMinute = date('H:i');
    
    foreach ($executionTimes as $execTime) {
        $execMinute = substr($execTime, 0, 5); // HH:MM
        if ($currentMinute === $execMinute) {
            return true;
        }
    }
    
    return false;
}

// Registrar PID
file_put_contents($pidFile, getmypid());

// Registrar handlers para parada limpa
register_shutdown_function(function() use ($pidFile, $serviceControlFile) {
    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
    
    saveServiceStatus('stopped', ['stopped_at' => date('Y-m-d H:i:s')]);
    writeServiceLog("Serviço finalizado");
});

// Handler para sinais (Linux/Unix)
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, function() {
        writeServiceLog("Recebido sinal SIGTERM, finalizando...");
        exit(0);
    });
    
    pcntl_signal(SIGINT, function() {
        writeServiceLog("Recebido sinal SIGINT, finalizando...");
        exit(0);
    });
}

try {
    writeServiceLog("=== INICIANDO SERVIÇO DE AUTOMAÇÃO DE FATURAS ===");
    writeServiceLog("PID: " . getmypid());
    writeServiceLog("Configuração carregada: " . json_encode($config));
    
    if (!$config['enabled']) {
        writeServiceLog("Automação desabilitada via configuração", 'WARNING');
        exit(0);
    }
    
    saveServiceStatus('running', ['started_at' => date('Y-m-d H:i:s')]);
    
    $lastExecution = null;
    $executionCount = 0;
    
    // Loop principal do serviço
    while (true) {
        try {
            // Verificar se deve parar
            if (shouldStopService()) {
                writeServiceLog("Comando de parada recebido");
                break;
            }
            
            // Processar sinais (Linux/Unix)
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
            
            // Verificar se é hora de executar
            if (isExecutionTime($config['execution_times'])) {
                $currentMinute = date('H:i');
                
                // Evitar execução dupla no mesmo minuto
                if ($lastExecution !== $currentMinute) {
                    writeServiceLog("Iniciando execução automática às {$currentMinute}");
                    
                    $startTime = microtime(true);
                    $report = runInvoiceAutomation();
                    $executionTime = round(microtime(true) - $startTime, 2);
                    
                    $executionCount++;
                    $lastExecution = $currentMinute;
                    
                    writeServiceLog("Execução #{$executionCount} concluída em {$executionTime}s");
                    writeServiceLog("Clientes verificados: " . $report['total_clients_checked']);
                    writeServiceLog("Faturas geradas: " . $report['invoices_generated']);
                    
                    if (isset($report['error'])) {
                        writeServiceLog("ERRO: " . $report['error'], 'ERROR');
                    }
                    
                    // Salvar status da última execução
                    saveServiceStatus('running', [
                        'last_execution' => $currentMinute,
                        'execution_count' => $executionCount,
                        'last_report' => $report,
                        'execution_time_seconds' => $executionTime
                    ]);
                    
                    // Limpeza de memória
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }
            
            // Atualizar status periodicamente (a cada 5 minutos)
            if (time() % 300 === 0) {
                saveServiceStatus('running', [
                    'execution_count' => $executionCount,
                    'last_execution' => $lastExecution
                ]);
            }
            
            // Aguardar 30 segundos antes da próxima verificação
            sleep(30);
            
        } catch (Exception $e) {
            writeServiceLog("Erro no loop principal: " . $e->getMessage(), 'ERROR');
            
            // Aguardar mais tempo em caso de erro
            sleep(60);
        }
    }
    
} catch (Exception $e) {
    writeServiceLog("ERRO CRÍTICO no serviço: " . $e->getMessage(), 'ERROR');
    saveServiceStatus('error', ['error' => $e->getMessage()]);
    exit(1);
}

writeServiceLog("Serviço finalizado normalmente");
saveServiceStatus('stopped');
?>