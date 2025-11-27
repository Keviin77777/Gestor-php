<?php
/**
 * Monitor de fila em tempo real
 * Mostra estatísticas e últimas mensagens enviadas
 * 
 * Uso: php monitor-queue.php [reseller_id]
 */

require_once 'app/helpers/functions.php';
loadEnv('.env');
require_once 'app/core/Database.php';

$resellerId = $argv[1] ?? null;

if (!$resellerId) {
    echo "❌ Uso: php monitor-queue.php [reseller_id]\n";
    echo "Exemplo: php monitor-queue.php usr-123456\n";
    exit(1);
}

function clearScreen() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        system('cls');
    } else {
        system('clear');
    }
}

try {
    $db = Database::connect();
    
    while (true) {
        clearScreen();
        
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║          MONITOR DE FILA - WHATSAPP                        ║\n";
        echo "║          Revendedor: " . str_pad($resellerId, 38) . "║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";
        
        // Configuração atual
        $stmt = $db->prepare("SELECT * FROM whatsapp_rate_limit_config WHERE reseller_id = ?");
        $stmt->execute([$resellerId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config) {
            echo "⚙️  CONFIGURAÇÃO:\n";
            echo "   • Mensagens/minuto: {$config['messages_per_minute']}\n";
            echo "   • Mensagens/hora: {$config['messages_per_hour']}\n";
            echo "   • Delay: {$config['delay_between_messages']}s\n\n";
        }
        
        // Estatísticas gerais
        $stmt = $db->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM whatsapp_message_queue
            WHERE reseller_id = ?
            GROUP BY status
        ");
        $stmt->execute([$resellerId]);
        $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        echo "📊 ESTATÍSTICAS:\n";
        echo "   • Pendentes: " . ($stats['pending'] ?? 0) . "\n";
        echo "   • Processando: " . ($stats['processing'] ?? 0) . "\n";
        echo "   • Enviadas: " . ($stats['sent'] ?? 0) . "\n";
        echo "   • Falhas: " . ($stats['failed'] ?? 0) . "\n\n";
        
        // Mensagens enviadas no último minuto
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM whatsapp_message_queue 
            WHERE reseller_id = ? 
            AND status = 'sent'
            AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ");
        $stmt->execute([$resellerId]);
        $lastMinute = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Mensagens enviadas na última hora
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM whatsapp_message_queue 
            WHERE reseller_id = ? 
            AND status = 'sent'
            AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$resellerId]);
        $lastHour = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "📈 TAXA DE ENVIO:\n";
        echo "   • Último minuto: {$lastMinute}/" . ($config['messages_per_minute'] ?? 20) . "\n";
        echo "   • Última hora: {$lastHour}/" . ($config['messages_per_hour'] ?? 100) . "\n\n";
        
        // Últimas 5 mensagens enviadas
        $stmt = $db->prepare("
            SELECT id, phone, sent_at, status
            FROM whatsapp_message_queue 
            WHERE reseller_id = ? 
            AND status = 'sent'
            ORDER BY sent_at DESC
            LIMIT 5
        ");
        $stmt->execute([$resellerId]);
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📝 ÚLTIMAS MENSAGENS ENVIADAS:\n";
        if (empty($recent)) {
            echo "   Nenhuma mensagem enviada ainda\n";
        } else {
            foreach ($recent as $msg) {
                $time = date('H:i:s', strtotime($msg['sent_at']));
                echo "   • #{$msg['id']} → {$msg['phone']} às {$time}\n";
            }
        }
        
        echo "\n" . str_repeat("─", 60) . "\n";
        echo "Atualizado em: " . date('H:i:s') . " | Pressione Ctrl+C para sair\n";
        
        sleep(2); // Atualizar a cada 2 segundos
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
