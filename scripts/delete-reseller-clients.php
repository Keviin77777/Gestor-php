<?php
/**
 * Script para deletar todos os clientes de um revendedor específico
 * 
 * USO:
 * php scripts/delete-reseller-clients.php <email_do_revendedor>
 * 
 * Exemplo:
 * php scripts/delete-reseller-clients.php lustosa.iptv77@gmail.com
 */

require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');
require_once __DIR__ . '/../app/core/Database.php';

// Cores
class Color {
    public static $RESET = "\033[0m";
    public static $RED = "\033[31m";
    public static $GREEN = "\033[32m";
    public static $YELLOW = "\033[33m";
    public static $BLUE = "\033[34m";
    public static $CYAN = "\033[36m";
    public static $BOLD = "\033[1m";
}

function printSuccess($text) {
    echo Color::$GREEN . "✅ " . $text . Color::$RESET . "\n";
}

function printError($text) {
    echo Color::$RED . "❌ " . $text . Color::$RESET . "\n";
}

function printWarning($text) {
    echo Color::$YELLOW . "⚠️  " . $text . Color::$RESET . "\n";
}

function printInfo($text) {
    echo Color::$BLUE . "ℹ️  " . $text . Color::$RESET . "\n";
}

// Verificar argumentos
if ($argc < 2) {
    printError("Uso: php scripts/delete-reseller-clients.php <email_do_revendedor>");
    printInfo("Exemplo: php scripts/delete-reseller-clients.php lustosa.iptv77@gmail.com");
    exit(1);
}

$resellerEmail = $argv[1];

echo "\n" . Color::$BOLD . Color::$CYAN . "═══════════════════════════════════════════════════════════════" . Color::$RESET . "\n";
echo Color::$BOLD . Color::$CYAN . "  DELETAR CLIENTES DE REVENDEDOR" . Color::$RESET . "\n";
echo Color::$BOLD . Color::$CYAN . "═══════════════════════════════════════════════════════════════" . Color::$RESET . "\n\n";

try {
    // Buscar revendedor
    printInfo("Buscando revendedor: {$resellerEmail}");
    
    $reseller = Database::fetch(
        "SELECT id, name, email FROM users WHERE email = ?",
        [$resellerEmail]
    );
    
    if (!$reseller) {
        printError("Revendedor não encontrado com email: {$resellerEmail}");
        exit(1);
    }
    
    printSuccess("Revendedor encontrado!");
    printInfo("ID: {$reseller['id']}");
    printInfo("Nome: {$reseller['name']}");
    printInfo("Email: {$reseller['email']}");
    
    echo "\n";
    
    // Contar clientes
    $clientCount = Database::fetch(
        "SELECT COUNT(*) as total FROM clients WHERE reseller_id = ?",
        [$reseller['id']]
    );
    
    $total = $clientCount['total'];
    
    if ($total == 0) {
        printInfo("Nenhum cliente encontrado para este revendedor.");
        exit(0);
    }
    
    printWarning("Total de clientes a serem deletados: {$total}");
    
    // Listar alguns clientes
    echo "\n" . Color::$BOLD . "Primeiros 10 clientes:" . Color::$RESET . "\n";
    echo "─────────────────────────────────────────────────────────────\n";
    
    $sampleClients = Database::fetchAll(
        "SELECT id, name, email, status, renewal_date 
         FROM clients 
         WHERE reseller_id = ? 
         LIMIT 10",
        [$reseller['id']]
    );
    
    foreach ($sampleClients as $client) {
        echo "  • {$client['name']} ({$client['id']}) - Status: {$client['status']}\n";
    }
    
    if ($total > 10) {
        echo "  ... e mais " . ($total - 10) . " clientes\n";
    }
    
    echo "\n";
    
    // Confirmação
    printWarning("ATENÇÃO: Esta ação NÃO PODE SER DESFEITA!");
    printWarning("Todos os {$total} clientes serão PERMANENTEMENTE deletados.");
    echo "\n";
    
    echo Color::$YELLOW . "Digite 'CONFIRMAR' para prosseguir ou qualquer outra coisa para cancelar: " . Color::$RESET;
    $confirmation = trim(fgets(STDIN));
    
    if ($confirmation !== 'CONFIRMAR') {
        printInfo("Operação cancelada pelo usuário.");
        exit(0);
    }
    
    echo "\n";
    printInfo("Iniciando deleção...");
    
    // Deletar faturas relacionadas primeiro (integridade referencial)
    printInfo("Deletando faturas dos clientes...");
    $invoicesDeleted = Database::query(
        "DELETE FROM invoices WHERE client_id IN (SELECT id FROM clients WHERE reseller_id = ?)",
        [$reseller['id']]
    );
    printSuccess("Faturas deletadas");
    
    // Deletar pagamentos de faturas
    printInfo("Deletando pagamentos de faturas...");
    Database::query(
        "DELETE FROM invoice_payments 
         WHERE invoice_id IN (
             SELECT id FROM invoices WHERE client_id IN (
                 SELECT id FROM clients WHERE reseller_id = ?
             )
         )",
        [$reseller['id']]
    );
    printSuccess("Pagamentos deletados");
    
    // Deletar clientes
    printInfo("Deletando clientes...");
    $clientsDeleted = Database::query(
        "DELETE FROM clients WHERE reseller_id = ?",
        [$reseller['id']]
    );
    
    printSuccess("Clientes deletados com sucesso!");
    
    echo "\n" . Color::$BOLD . Color::$GREEN . "═══════════════════════════════════════════════════════════════" . Color::$RESET . "\n";
    echo Color::$BOLD . Color::$GREEN . "  DELEÇÃO CONCLUÍDA COM SUCESSO!" . Color::$RESET . "\n";
    echo Color::$BOLD . Color::$GREEN . "═══════════════════════════════════════════════════════════════" . Color::$RESET . "\n\n";
    
    printInfo("Revendedor: {$reseller['name']} ({$reseller['email']})");
    printInfo("Total de clientes deletados: {$total}");
    
    echo "\n";
    printSuccess("Operação concluída!");
    
} catch (Exception $e) {
    printError("ERRO: " . $e->getMessage());
    echo "\n" . Color::$RED . "Stack trace:" . Color::$RESET . "\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
