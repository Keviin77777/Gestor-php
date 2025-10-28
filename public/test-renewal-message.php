<?php
/**
 * Script de teste para mensagem de renovação
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/helpers/auth-helper.php';
require_once __DIR__ . '/../app/helpers/whatsapp-automation.php';
loadEnv(__DIR__ . '/../.env');

// Obter parâmetros
$clientId = $_GET['client_id'] ?? null;
$invoiceId = $_GET['invoice_id'] ?? null;

echo json_encode([
    'test_mode' => true,
    'client_id' => $clientId,
    'invoice_id' => $invoiceId
], JSON_PRETTY_PRINT);

echo "\n\n=== DIAGNÓSTICO DE RENOVAÇÃO ===\n\n";

try {
    // 1. Verificar template
    echo "1. Verificando template 'renewed'...\n";
    $template = Database::fetch(
        "SELECT * FROM whatsapp_templates WHERE type = 'renewed' AND is_active = 1 ORDER BY is_default DESC LIMIT 1"
    );
    
    if ($template) {
        echo "   ✅ Template encontrado: {$template['name']}\n";
        echo "   - ID: {$template['id']}\n";
        echo "   - Reseller: {$template['reseller_id']}\n";
        echo "   - Ativo: " . ($template['is_active'] ? 'Sim' : 'Não') . "\n";
        echo "   - Padrão: " . ($template['is_default'] ? 'Sim' : 'Não') . "\n";
        echo "   - Mensagem: " . substr($template['message'], 0, 100) . "...\n";
    } else {
        echo "   ❌ Template 'renewed' não encontrado!\n";
    }
    
    echo "\n2. Verificando configurações do WhatsApp...\n";
    $settings = Database::fetch(
        "SELECT * FROM whatsapp_settings WHERE reseller_id = 'admin-001'"
    );
    
    if ($settings) {
        echo "   ✅ Configurações encontradas\n";
        echo "   - Auto send renewal: " . ($settings['auto_send_renewal'] ? 'ATIVADO ✅' : 'DESATIVADO ❌') . "\n";
        echo "   - Auto send welcome: " . ($settings['auto_send_welcome'] ? 'Sim' : 'Não') . "\n";
        echo "   - Auto send invoice: " . ($settings['auto_send_invoice'] ? 'Sim' : 'Não') . "\n";
        echo "   - Evolution API URL: {$settings['evolution_api_url']}\n";
        echo "   - API Key: " . (empty($settings['evolution_api_key']) ? 'NÃO CONFIGURADA ❌' : substr($settings['evolution_api_key'], 0, 10) . '... ✅') . "\n";
    } else {
        echo "   ❌ Configurações não encontradas!\n";
    }
    
    echo "\n3. Verificando sessão WhatsApp...\n";
    $session = Database::fetch(
        "SELECT * FROM whatsapp_sessions WHERE reseller_id = 'admin-001' ORDER BY created_at DESC LIMIT 1"
    );
    
    if ($session) {
        echo "   ✅ Sessão encontrada\n";
        echo "   - Instance: {$session['instance_name']}\n";
        echo "   - Status: {$session['status']}\n";
        echo "   - QR Code: " . ($session['qr_code'] ? 'Disponível' : 'N/A') . "\n";
        
        if ($session['status'] !== 'connected') {
            echo "   ⚠️ ATENÇÃO: Sessão NÃO está conectada! Status: {$session['status']}\n";
        } else {
            echo "   - Conectado em: {$session['connected_at']}\n";
        }
    } else {
        echo "   ❌ Nenhuma sessão encontrada!\n";
    }
    
    // 4. Se foi fornecido um cliente, testar o envio
    if ($clientId && $invoiceId) {
        echo "\n4. Testando envio de mensagem de renovação...\n";
        echo "   Cliente ID: $clientId\n";
        echo "   Fatura ID: $invoiceId\n";
        
        // Buscar dados do cliente
        $client = Database::fetch(
            "SELECT * FROM clients WHERE id = ?",
            [$clientId]
        );
        
        if ($client) {
            echo "   - Nome: {$client['name']}\n";
            echo "   - Telefone: " . ($client['phone'] ?: 'NÃO CADASTRADO ❌') . "\n";
            echo "   - Status: {$client['status']}\n";
            echo "   - Vencimento: {$client['renewal_date']}\n";
            
            if ($client['phone']) {
                echo "\n   Enviando mensagem...\n";
                $result = sendAutomaticRenewalMessage($clientId, $invoiceId);
                
                if ($result['success']) {
                    echo "   ✅ MENSAGEM ENVIADA COM SUCESSO!\n";
                    echo "   - Message ID: {$result['message_id']}\n";
                } else {
                    echo "   ❌ ERRO ao enviar mensagem:\n";
                    echo "   - Erro: {$result['error']}\n";
                }
            } else {
                echo "   ❌ Cliente não possui telefone cadastrado!\n";
            }
        } else {
            echo "   ❌ Cliente não encontrado!\n";
        }
    }
    
    echo "\n5. Verificando últimas mensagens enviadas...\n";
    $messages = Database::fetchAll(
        "SELECT * FROM whatsapp_messages ORDER BY created_at DESC LIMIT 5"
    );
    
    if ($messages) {
        echo "   Últimas " . count($messages) . " mensagens:\n";
        foreach ($messages as $msg) {
            echo "   - {$msg['id']}: {$msg['status']} para {$msg['phone_number']}\n";
            if ($msg['error_message']) {
                echo "     Erro: {$msg['error_message']}\n";
            }
        }
    } else {
        echo "   ℹ️ Nenhuma mensagem registrada no banco\n";
    }
    
    echo "\n=== FIM DO DIAGNÓSTICO ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
