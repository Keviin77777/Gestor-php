<?php
/**
 * Script para limpar mensagens enviadas hoje (para testes)
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

echo "=== LIMPAR MENSAGENS DE HOJE ===\n\n";

// Buscar mensagens de hoje
$messages = Database::fetchAll(
    "SELECT id, client_id, template_id, sent_at 
     FROM whatsapp_messages 
     WHERE DATE(sent_at) = CURDATE()"
);

echo "Mensagens enviadas hoje: " . count($messages) . "\n\n";

if (count($messages) === 0) {
    echo "✅ Nenhuma mensagem para limpar\n";
    exit(0);
}

foreach ($messages as $msg) {
    echo "  • ID: {$msg['id']} | Cliente: {$msg['client_id']} | Template: {$msg['template_id']} | Enviado: {$msg['sent_at']}\n";
}

echo "\n⚠️  Deseja deletar estas mensagens? (s/n): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
fclose($handle);

if (trim(strtolower($line)) !== 's') {
    echo "\n❌ Operação cancelada\n";
    exit(0);
}

// Deletar mensagens
Database::query("DELETE FROM whatsapp_messages WHERE DATE(sent_at) = CURDATE()");

echo "\n✅ Mensagens deletadas com sucesso!\n";
echo "\nAgora você pode testar o envio novamente.\n";
