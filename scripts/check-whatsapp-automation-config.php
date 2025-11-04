<?php
/**
 * Script para verificar e configurar automação do WhatsApp
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

loadEnv(__DIR__ . '/../.env');

echo "=== CONFIGURAÇÃO DE AUTOMAÇÃO WHATSAPP ===\n\n";

// Buscar configurações
// Obter reseller_id do usuário autenticado ou usar padrão
session_start();
$resellerId = $_SESSION['user_id'] ?? 'admin-001';

$settings = Database::fetch(
    "SELECT * FROM whatsapp_settings WHERE reseller_id = ?",
    [$resellerId]
);

if (!$settings) {
    echo "❌ Nenhuma configuração encontrada para $resellerId\n";
    echo "\nCriando configuração padrão...\n";
    
    $settingsId = 'ws-' . uniqid();
    Database::query(
        "INSERT INTO whatsapp_settings (
            id, reseller_id, evolution_api_url, evolution_api_key,
            auto_send_welcome, auto_send_invoice, auto_send_renewal, auto_send_reminders
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [
            $settingsId,
            $resellerId,
            'http://localhost:8081',
            'gestplay-whatsapp-2024',
            false, // auto_send_welcome
            false, // auto_send_invoice
            false, // auto_send_renewal
            false  // auto_send_reminders - DESATIVADO por padrão
        ]
    );
    
    echo "✅ Configuração criada com automação DESATIVADA\n";
    exit(0);
}

echo "📋 Configurações atuais:\n\n";
echo "Reseller ID: {$settings['reseller_id']}\n";
echo "Evolution API URL: {$settings['evolution_api_url']}\n";
echo "Evolution API Key: " . ($settings['evolution_api_key'] ? '***' : 'Não configurada') . "\n\n";

echo "🤖 Automações:\n";
echo "  • Boas-vindas (auto_send_welcome): " . ($settings['auto_send_welcome'] ? '✅ ATIVADA' : '❌ DESATIVADA') . "\n";
echo "  • Fatura gerada (auto_send_invoice): " . ($settings['auto_send_invoice'] ? '✅ ATIVADA' : '❌ DESATIVADA') . "\n";
echo "  • Renovação confirmada (auto_send_renewal): " . ($settings['auto_send_renewal'] ? '✅ ATIVADA' : '❌ DESATIVADA') . "\n";
echo "  • Lembretes de vencimento (auto_send_reminders): " . ($settings['auto_send_reminders'] ? '✅ ATIVADA' : '❌ DESATIVADA') . "\n\n";

// Buscar templates
$templates = Database::fetchAll(
    "SELECT id, name, type, is_active, is_scheduled, scheduled_days, scheduled_time 
     FROM whatsapp_templates 
     WHERE reseller_id = ?
     ORDER BY type",
    [$resellerId]
);

echo "📝 Templates cadastrados: " . count($templates) . "\n\n";

foreach ($templates as $template) {
    $status = $template['is_active'] ? '✅' : '❌';
    $scheduled = $template['is_scheduled'] ? '📅 AGENDADO' : '🔄 AUTOMÁTICO';
    
    echo "{$status} {$template['name']} ({$template['type']})\n";
    echo "   Modo: {$scheduled}\n";
    
    if ($template['is_scheduled']) {
        $days = json_decode($template['scheduled_days'], true);
        echo "   Dias: " . implode(', ', $days ?: []) . "\n";
        echo "   Horário: {$template['scheduled_time']}\n";
    }
    echo "\n";
}

echo "\n=== RESUMO ===\n\n";

if ($settings['auto_send_reminders']) {
    echo "⚠️  ATENÇÃO: Lembretes automáticos estão ATIVADOS!\n";
    echo "   O cron enviará mensagens automaticamente para clientes com vencimento próximo.\n\n";
    echo "Para DESATIVAR, execute:\n";
    echo "UPDATE whatsapp_settings SET auto_send_reminders = FALSE WHERE reseller_id = 'admin-001';\n";
} else {
    echo "✅ Lembretes automáticos estão DESATIVADOS\n";
    echo "   Apenas templates com agendamento ativo serão enviados.\n\n";
    echo "Para ATIVAR, execute:\n";
    echo "UPDATE whatsapp_settings SET auto_send_reminders = TRUE WHERE reseller_id = 'admin-001';\n";
}

echo "\n";
