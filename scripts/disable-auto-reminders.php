<?php
/**
 * Script para DESATIVAR lembretes automáticos
 * Após executar, apenas templates com agendamento ativo serão enviados
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

echo "=== DESATIVAR LEMBRETES AUTOMÁTICOS ===\n\n";

// Desativar lembretes automáticos
// Obter reseller_id do usuário autenticado ou usar padrão
session_start();
$resellerId = $_SESSION['user_id'] ?? 'admin-001';

Database::query(
    "UPDATE whatsapp_settings SET auto_send_reminders = FALSE WHERE reseller_id = ?",
    [$resellerId]
);

echo "✅ Lembretes automáticos DESATIVADOS!\n\n";
echo "Agora o cron só enviará mensagens de templates que tiverem:\n";
echo "  • is_scheduled = 1 (agendamento ativo)\n";
echo "  • Dia e horário configurados\n\n";

echo "Para reativar, execute:\n";
echo "php scripts/enable-auto-reminders.php\n\n";

// Verificar configuração
$settings = Database::fetch(
    "SELECT auto_send_reminders FROM whatsapp_settings WHERE reseller_id = ?",
    [$resellerId]
);

echo "Status atual: " . ($settings['auto_send_reminders'] ? 'ATIVADO' : 'DESATIVADO') . "\n";
