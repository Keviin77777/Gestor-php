<?php
/**
 * Script para verificar mensagens de boas-vindas de revendedores
 * Útil para diagnosticar problemas em produção
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
loadEnv(__DIR__ . '/../.env');

echo "=== DIAGNÓSTICO DE MENSAGENS DE BOAS-VINDAS ===\n\n";

// 1. Verificar último revendedor cadastrado
echo "1️⃣ ÚLTIMO REVENDEDOR CADASTRADO:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$lastReseller = Database::fetch("
    SELECT 
        id,
        name,
        email,
        COALESCE(phone, whatsapp) as phone,
        created_at,
        plan_expires_at
    FROM users
    WHERE role = 'reseller'
    ORDER BY created_at DESC
    LIMIT 1
");

if (!$lastReseller) {
    echo "❌ Nenhum revendedor encontrado\n\n";
    exit(1);
}

echo "Nome: {$lastReseller['name']}\n";
echo "Email: {$lastReseller['email']}\n";
echo "Telefone: " . ($lastReseller['phone'] ?: '❌ NÃO INFORMADO') . "\n";
echo "Cadastrado em: {$lastReseller['created_at']}\n";
echo "Trial expira: {$lastReseller['plan_expires_at']}\n\n";

$phone = $lastReseller['phone'];
$phoneClean = preg_replace('/[^0-9]/', '', $phone);

// 2. Verificar se tem template reseller_welcome
echo "2️⃣ VERIFICANDO TEMPLATE:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$template = Database::fetch("
    SELECT * FROM whatsapp_templates 
    WHERE reseller_id = 'admin-001' 
    AND type = 'reseller_welcome' 
    AND is_active = 1
");

if (!$template) {
    echo "❌ Template reseller_welcome NÃO ENCONTRADO para admin-001\n";
    echo "   Isso impede o envio da mensagem!\n\n";
} else {
    echo "✅ Template encontrado: {$template['name']}\n";
    echo "   ID: {$template['id']}\n";
    echo "   Ativo: " . ($template['is_active'] ? 'SIM' : 'NÃO') . "\n\n";
}

// 3. Verificar na fila
echo "3️⃣ VERIFICANDO FILA (whatsapp_message_queue):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

if ($phone) {
    $queueMessages = Database::fetchAll("
        SELECT 
            q.*,
            t.name as template_name,
            t.type as template_type
        FROM whatsapp_message_queue q
        LEFT JOIN whatsapp_templates t ON q.template_id = t.id
        WHERE q.phone LIKE ?
        ORDER BY q.created_at DESC
        LIMIT 5
    ", ['%' . $phoneClean . '%']);
    
    if (empty($queueMessages)) {
        echo "❌ Nenhuma mensagem encontrada na fila para este telefone\n";
        echo "   Isso significa que a mensagem NÃO foi adicionada à fila!\n\n";
    } else {
        echo "✅ Encontradas " . count($queueMessages) . " mensagens na fila:\n\n";
        foreach ($queueMessages as $msg) {
            echo "   📨 ID: {$msg['id']}\n";
            echo "      Template: {$msg['template_name']} ({$msg['template_type']})\n";
            echo "      Status: {$msg['status']}\n";
            echo "      Prioridade: {$msg['priority']}\n";
            echo "      Criado: {$msg['created_at']}\n";
            if ($msg['sent_at']) {
                echo "      Enviado: {$msg['sent_at']}\n";
            }
            if ($msg['error_message']) {
                echo "      ❌ Erro: {$msg['error_message']}\n";
            }
            echo "\n";
        }
    }
} else {
    echo "⚠️ Revendedor sem telefone - não é possível verificar fila\n\n";
}

// 4. Verificar no log
echo "4️⃣ VERIFICANDO LOG (whatsapp_messages_log):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$logMessages = Database::fetchAll("
    SELECT *
    FROM whatsapp_messages_log
    WHERE recipient_id = ?
    ORDER BY sent_at DESC
    LIMIT 5
", [$lastReseller['id']]);

if (empty($logMessages)) {
    echo "❌ Nenhum registro no log para este revendedor\n";
    echo "   Isso confirma que a mensagem NÃO foi processada!\n\n";
} else {
    echo "✅ Encontrados " . count($logMessages) . " registros no log:\n\n";
    foreach ($logMessages as $msg) {
        echo "   📝 Tipo: {$msg['message_type']}\n";
        echo "      Status: {$msg['status']}\n";
        echo "      Data: {$msg['sent_at']}\n\n";
    }
}

// 5. Verificar logs de erro do PHP
echo "5️⃣ VERIFICANDO LOGS DE ERRO:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$logFile = __DIR__ . '/../logs/error.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -50); // Últimas 50 linhas
    
    $relevantLines = array_filter($recentLines, function($line) {
        return stripos($line, 'welcome') !== false 
            || stripos($line, 'reseller') !== false
            || stripos($line, 'boas-vindas') !== false
            || stripos($line, 'sendWelcomeMessageToReseller') !== false;
    });
    
    if (empty($relevantLines)) {
        echo "ℹ️ Nenhum erro relacionado a boas-vindas nos últimos logs\n\n";
    } else {
        echo "⚠️ Erros encontrados:\n\n";
        foreach ($relevantLines as $line) {
            echo "   " . trim($line) . "\n";
        }
        echo "\n";
    }
} else {
    echo "ℹ️ Arquivo de log não encontrado: {$logFile}\n\n";
}

// 6. Verificar configuração do admin
echo "6️⃣ VERIFICANDO ADMIN (admin-001):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$admin = Database::fetch("
    SELECT id, name, email, role 
    FROM users 
    WHERE id = 'admin-001' OR role = 'admin' 
    LIMIT 1
");

if (!$admin) {
    echo "❌ Admin não encontrado!\n";
    echo "   Isso impede o envio de mensagens aos revendedores!\n\n";
} else {
    echo "✅ Admin encontrado:\n";
    echo "   ID: {$admin['id']}\n";
    echo "   Nome: {$admin['name']}\n";
    echo "   Email: {$admin['email']}\n\n";
}

// 7. Diagnóstico final
echo "7️⃣ DIAGNÓSTICO:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$issues = [];

if (!$lastReseller['phone']) {
    $issues[] = "❌ Revendedor cadastrado SEM telefone/WhatsApp";
}

if (!$template) {
    $issues[] = "❌ Template reseller_welcome não existe para admin-001";
}

if (!$admin) {
    $issues[] = "❌ Admin não encontrado no sistema";
}

if ($phone && empty($queueMessages)) {
    $issues[] = "❌ Mensagem NÃO foi adicionada à fila";
}

if (empty($issues)) {
    echo "✅ Tudo parece estar configurado corretamente!\n";
    echo "   Se a mensagem não foi enviada, verifique:\n";
    echo "   - Se o processador de fila está rodando (process-queue.php)\n";
    echo "   - Se o WhatsApp do admin-001 está conectado\n";
    echo "   - Se há limites de envio atingidos\n\n";
} else {
    echo "⚠️ PROBLEMAS ENCONTRADOS:\n\n";
    foreach ($issues as $issue) {
        echo "   {$issue}\n";
    }
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Diagnóstico concluído!\n";
