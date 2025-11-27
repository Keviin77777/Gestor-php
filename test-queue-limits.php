<?php
/**
 * Script de teste para adicionar mensagens na fila
 * Uso: php test-queue-limits.php [quantidade]
 */

require_once 'app/helpers/functions.php';
loadEnv('.env');
require_once 'app/core/Database.php';

$quantidade = $argv[1] ?? 10;
$resellerId = $argv[2] ?? null;

if (!$resellerId) {
    echo "❌ Uso: php test-queue-limits.php [quantidade] [reseller_id]\n";
    echo "Exemplo: php test-queue-limits.php 30 usr-123456\n";
    exit(1);
}

try {
    $db = Database::connect();
    
    echo "📝 Adicionando {$quantidade} mensagens de teste na fila...\n\n";
    
    $stmt = $db->prepare("
        INSERT INTO whatsapp_message_queue 
        (reseller_id, phone, message, status, priority, created_at)
        VALUES (?, ?, ?, 'pending', 0, NOW())
    ");
    
    for ($i = 1; $i <= $quantidade; $i++) {
        $phone = '5514997' . str_pad($i, 6, '0', STR_PAD_LEFT);
        $message = "Mensagem de teste #{$i} - " . date('H:i:s');
        
        $stmt->execute([$resellerId, $phone, $message]);
        echo "✅ Mensagem {$i}/{$quantidade} adicionada\n";
    }
    
    echo "\n🎉 {$quantidade} mensagens adicionadas com sucesso!\n";
    echo "📊 Acesse /whatsapp/queue para ver a fila\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
