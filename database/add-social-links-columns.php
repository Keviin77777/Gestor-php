<?php
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/functions.php';

try {
    // Carregar variáveis de ambiente
    loadEnv(__DIR__ . '/../.env');
    
    $db = Database::connect();
    
    echo "Adicionando colunas de links sociais...\n";
    
    // Verificar se as colunas já existem
    $checkTelegram = $db->query("SHOW COLUMNS FROM users LIKE 'telegram_link'")->fetch();
    $checkWhatsApp = $db->query("SHOW COLUMNS FROM users LIKE 'whatsapp_number'")->fetch();
    
    if (!$checkTelegram) {
        $db->exec("ALTER TABLE users ADD COLUMN telegram_link VARCHAR(500) DEFAULT 'https://t.me/+jim14-gGOBFhNWMx'");
        echo "✓ Coluna telegram_link adicionada\n";
    } else {
        echo "✓ Coluna telegram_link já existe\n";
    }
    
    if (!$checkWhatsApp) {
        $db->exec("ALTER TABLE users ADD COLUMN whatsapp_number VARCHAR(20) DEFAULT '14997349352'");
        echo "✓ Coluna whatsapp_number adicionada\n";
    } else {
        echo "✓ Coluna whatsapp_number já existe\n";
    }
    
    echo "\n✅ Migração concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
