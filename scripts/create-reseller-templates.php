<?php
/**
 * Script para criar templates padrão para revendedores
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
loadEnv(__DIR__ . '/../.env');

$templates = [
    // Templates de renovação - antes do vencimento
    [
        'name' => 'Renovação 7 dias - Revendedor',
        'type' => 'reseller_renewal_7d',
        'title' => 'Lembrete 7 dias',
        'message' => "🔔 *Lembrete de Renovação - UltraGestor*\n\nOlá *{{revendedor_nome}}*!\n\nSeu plano *{{revendedor_plano}}* vence em *7 dias*.\n\n📅 Vencimento: {{revendedor_vencimento}}\n💰 Valor: {{revendedor_valor}}\n\nRenove agora para não perder o acesso ao sistema!\n\n🔗 {{link_renovacao}}",
        'variables' => '["revendedor_nome", "revendedor_plano", "revendedor_vencimento", "revendedor_valor", "link_renovacao"]',
        'is_active' => 1,
        'is_default' => 1,
        'category' => 'reseller'
    ],
    [
        'name' => 'Renovação 3 dias - Revendedor',
        'type' => 'reseller_renewal_3d',
        'title' => 'Lembrete 3 dias',
        'message' => "⚠️ *Lembrete de Renovação - UltraGestor*\n\nOlá *{{revendedor_nome}}*!\n\nSeu plano *{{revendedor_plano}}* vence em *3 dias*.\n\n📅 Vencimento: {{revendedor_vencimento}}\n💰 Valor: {{revendedor_valor}}\n\n⚡ Renove agora para não perder o acesso!\n\n🔗 {{link_renovacao}}",
        'variables' => '["revendedor_nome", "revendedor_plano", "revendedor_vencimento", "revendedor_valor", "link_renovacao"]',
        'is_active' => 1,
        'is_default' => 1,
        'category' => 'reseller'
    ],
    [
        'name' => 'Renovação 1 dia - Revendedor',
        'type' => 'reseller_renewal_1d',
        'title' => 'Lembrete 1 dia',
        'message' => "🚨 *URGENTE - Renovação UltraGestor*\n\nOlá *{{revendedor_nome}}*!\n\nSeu plano *{{revendedor_plano}}* vence *AMANHÃ*!\n\n📅 Vencimento: {{revendedor_vencimento}}\n💰 Valor: {{revendedor_valor}}\n\n⚡ Renove AGORA para não perder o acesso!\n\n🔗 {{link_renovacao}}",
        'variables' => '["revendedor_nome", "revendedor_plano", "revendedor_vencimento", "revendedor_valor", "link_renovacao"]',
        'is_active' => 1,
        'is_default' => 1,
        'category' => 'reseller'
    ],
    [
        'name' => 'Vence Hoje - Revendedor',
        'type' => 'reseller_expires_today',
        'title' => 'Vence Hoje',
        'message' => "🔴 *ÚLTIMO DIA - UltraGestor*\n\nOlá *{{revendedor_nome}}*!\n\nSeu plano *{{revendedor_plano}}* vence *HOJE*!\n\n📅 Vencimento: {{revendedor_vencimento}}\n💰 Valor: {{revendedor_valor}}\n\n⚡ Renove AGORA para não perder o acesso!\n\n🔗 {{link_renovacao}}",
        'variables' => '["revendedor_nome", "revendedor_plano", "revendedor_vencimento", "revendedor_valor", "link_renovacao"]',
        'is_active' => 1,
        'is_default' => 1,
        'category' => 'reseller'
    ],
    // Templates de cobrança - após vencimento
    [
        'name' => 'Vencido 1 dia - Revendedor',
        'type' => 'reseller_expired_1d',
        'title' => 'Vencido 1 dia',
        'message' => "❌ *Plano Vencido - UltraGestor*\n\nOlá *{{revendedor_nome}}*,\n\nSeu plano *{{revendedor_plano}}* venceu *ontem*.\n\n📅 Venceu em: {{revendedor_vencimento}}\n💰 Valor para renovação: {{revendedor_valor}}\n\n⚡ Renove agora para recuperar o acesso!\n\n🔗 {{link_renovacao}}",
        'variables' => '["revendedor_nome", "revendedor_plano", "revendedor_vencimento", "revendedor_valor", "link_renovacao"]',
        'is_active' => 1,
        'is_default' => 1,
        'category' => 'reseller'
    ],
    [
        'name' => 'Vencido 3 dias - Revendedor',
        'type' => 'reseller_expired_3d',
        'title' => 'Vencido 3 dias',
        'message' => "⚠️ *Plano Vencido - UltraGestor*\n\nOlá *{{revendedor_nome}}*,\n\nSeu plano *{{revendedor_plano}}* venceu há *{{revendedor_dias}} dias*.\n\n📅 Venceu em: {{revendedor_vencimento}}\n💰 Valor para renovação: {{revendedor_valor}}\n\n⚡ Renove agora para recuperar o acesso!\n\n🔗 {{link_renovacao}}",
        'variables' => '["revendedor_nome", "revendedor_plano", "revendedor_dias", "revendedor_vencimento", "revendedor_valor", "link_renovacao"]',
        'is_active' => 1,
        'is_default' => 1,
        'category' => 'reseller'
    ],
    [
        'name' => 'Vencido 7 dias - Revendedor',
        'type' => 'reseller_expired_7d',
        'title' => 'Vencido 7 dias',
        'message' => "🚨 *Plano Vencido - UltraGestor*\n\nOlá *{{revendedor_nome}}*,\n\nSeu plano *{{revendedor_plano}}* venceu há *{{revendedor_dias}} dias*.\n\n📅 Venceu em: {{revendedor_vencimento}}\n💰 Valor para renovação: {{revendedor_valor}}\n\n⚡ Última chance! Renove agora!\n\n🔗 {{link_renovacao}}",
        'variables' => '["revendedor_nome", "revendedor_plano", "revendedor_dias", "revendedor_vencimento", "revendedor_valor", "link_renovacao"]',
        'is_active' => 1,
        'is_default' => 1,
        'category' => 'reseller'
    ],
    // Templates genéricos (manter compatibilidade)
    [
        'name' => 'Lembrete Renovação Revendedor',
        'type' => 'reseller_reminder',
        'title' => 'Lembrete de Renovação',
        'message' => "🔔 *Lembrete de Renovação - UltraGestor*\n\nOlá *{{revendedor_nome}}*!\n\nSeu plano *{{revendedor_plano}}* vence em *{{revendedor_dias}} dia(s)*.\n\n📅 Vencimento: {{revendedor_vencimento}}\n💰 Valor: {{revendedor_valor}}\n\nRenove agora para não perder o acesso ao sistema!\n\n🔗 {{link_renovacao}}",
        'variables' => '["revendedor_nome", "revendedor_plano", "revendedor_dias", "revendedor_vencimento", "revendedor_valor", "link_renovacao"]',
        'is_active' => 1,
        'is_default' => 0,
        'category' => 'reseller'
    ],
    [
        'name' => 'Plano Vencido Revendedor',
        'type' => 'reseller_expired',
        'title' => 'Plano Vencido',
        'message' => "⚠️ *Plano Vencido - UltraGestor*\n\nOlá *{{revendedor_nome}}*,\n\nSeu plano *{{revendedor_plano}}* venceu há *{{revendedor_dias}} dia(s)*.\n\n📅 Venceu em: {{revendedor_vencimento}}\n💰 Valor para renovação: {{revendedor_valor}}\n\n⚡ Renove agora para recuperar o acesso!\n\n🔗 {{link_renovacao}}",
        'variables' => '["revendedor_nome", "revendedor_plano", "revendedor_dias", "revendedor_vencimento", "revendedor_valor", "link_renovacao"]',
        'is_active' => 1,
        'is_default' => 0,
        'category' => 'reseller'
    ],
    [
        'name' => 'Boas-vindas Revendedor',
        'type' => 'reseller_welcome',
        'title' => 'Boas-vindas',
        'message' => "🎉 *Bem-vindo ao UltraGestor!*\n\nOlá *{{revendedor_nome}}*!\n\nSua conta foi criada com sucesso!\n\n✅ Plano: {{revendedor_plano}}\n📅 Válido até: {{revendedor_vencimento}}\n\n🚀 Acesse agora e comece a gerenciar seus clientes:\n{{link_renovacao}}\n\n📧 Email: {{revendedor_email}}\n\nQualquer dúvida, estamos à disposição!",
        'variables' => '["revendedor_nome", "revendedor_plano", "revendedor_vencimento", "link_renovacao", "revendedor_email"]',
        'is_active' => 1,
        'is_default' => 0,
        'category' => 'reseller'
    ],
    [
        'name' => 'Trial Acabando Cliente',
        'type' => 'trial_ending',
        'title' => 'Trial Acabando',
        'message' => "⏰ *Seu período de teste está acabando!*\n\nOlá *{{cliente_nome}}*!\n\nSeu período de teste de 3 dias termina em *{{cliente_dias}} dia(s)*.\n\n📅 Término: {{cliente_vencimento}}\n\n💡 Gostou do serviço? Entre em contato para assinar!\n\n📱 WhatsApp: {{contato_revendedor}}\n\nNão perca o acesso!",
        'variables' => '["cliente_nome", "cliente_dias", "cliente_vencimento", "contato_revendedor"]',
        'is_active' => 1,
        'is_default' => 1,
        'category' => 'client_trial'
    ],
    [
        'name' => 'Trial Expirado Cliente',
        'type' => 'trial_expired',
        'title' => 'Trial Expirado',
        'message' => "❌ *Período de teste encerrado*\n\nOlá *{{cliente_nome}}*,\n\nSeu período de teste de 3 dias foi encerrado.\n\n📅 Encerrado em: {{cliente_vencimento}}\n\n💰 Quer continuar aproveitando? Entre em contato para assinar!\n\n📱 WhatsApp: {{contato_revendedor}}\n\nEstamos aguardando você! 😊",
        'variables' => '["cliente_nome", "cliente_vencimento", "contato_revendedor"]',
        'is_active' => 1,
        'is_default' => 1,
        'category' => 'client_trial'
    ]
];

try {
    echo "Criando templates para revendedores e trial...\n\n";
    
    // Buscar ID do admin
    $admin = Database::fetch("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if (!$admin) {
        throw new Exception("Nenhum usuário admin encontrado!");
    }
    $adminId = $admin['id'];
    echo "Admin ID: $adminId\n\n";
    
    foreach ($templates as $template) {
        // Verificar se já existe
        $existing = Database::fetch(
            "SELECT id FROM whatsapp_templates WHERE type = ?",
            [$template['type']]
        );
        
        if ($existing) {
            echo "⚠ Template '{$template['name']}' já existe. Pulando...\n";
            continue;
        }
        
        // Inserir template
        $templateId = 'tpl-' . uniqid();
        Database::query("
            INSERT INTO whatsapp_templates 
            (id, reseller_id, name, type, title, message, variables, is_active, is_default, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ", [
            $templateId,
            $adminId,
            $template['name'],
            $template['type'],
            $template['title'],
            $template['message'],
            $template['variables'],
            $template['is_active'],
            $template['is_default']
        ]);
        
        echo "✓ Template '{$template['name']}' criado com sucesso!\n";
    }
    
    echo "\n✅ Todos os templates foram processados!\n";
    
} catch (Exception $e) {
    echo "\n✗ Erro: " . $e->getMessage() . "\n";
}
