<?php
/**
 * Script para ATIVAR lembretes automáticos
 * Após executar, o cron enviará mensagens automaticamente para clientes com vencimento próximo
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

echo "=== ATIVAR LEMBRETES AUTOMÁTICOS ===\n\n";

// Ativar lembretes automáticos
// Obter reseller_id do usuário autenticado ou usar padrão
session_start();
$resellerId = $_SESSION['user_id'] ?? 'admin-001';

Database::query(
    "UPDATE whatsapp_settings SET auto_send_reminders = TRUE WHERE reseller_id = ?",
    [$resellerId]
);

echo "✅ Lembretes automáticos ATIVADOS!\n\n";
echo "⚠️  ATENÇÃO: O cron agora enviará mensagens automaticamente para:\n";
echo "  • Clientes que vencem em 7 dias\n";
echo "  • Clientes que vencem em 3 dias\n";
echo "  • Clientes que vencem hoje\n";
echo "  • Clientes vencidos há 1 dia\n";
echo "  • Clientes vencidos há 3 dias\n\n";

echo "Isso acontece INDEPENDENTE do agendamento configurado nos templates.\n\n";

echo "Para desativar, execute:\n";
echo "php scripts/disable-auto-reminders.php\n\n";

// Verificar configuração
$settings = Database::fetch(
    "SELECT auto_send_reminders FROM whatsapp_settings WHERE reseller_id = ?",
    [$resellerId]
);

echo "Status atual: " . ($settings['auto_send_reminders'] ? 'ATIVADO' : 'DESATIVADO') . "\n";
