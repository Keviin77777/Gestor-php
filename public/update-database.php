<?php
/**
 * Script para atualizar a estrutura do banco de dados
 * Execute este arquivo no navegador: http://localhost/update-database.php
 */

// Carregar funções auxiliares
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Carregar Database
require_once __DIR__ . '/../app/core/Database.php';

echo "<h2>Atualizando estrutura do banco de dados...</h2>";

try {
    // Conectar ao banco
    $pdo = Database::connect();
    echo "<p>✅ Conexão com banco de dados estabelecida</p>";
    
    // 1. Alterar o tipo do campo id para AUTO_INCREMENT
    echo "<p>🔄 Alterando campo id para AUTO_INCREMENT...</p>";
    $pdo->exec("ALTER TABLE clients MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY");
    echo "<p>✅ Campo id alterado com sucesso</p>";
    
    // 2. Adicionar novos campos
    echo "<p>🔄 Adicionando novos campos...</p>";
    
    $fields = [
        "iptv_password VARCHAR(100) AFTER password",
        "plan VARCHAR(100) DEFAULT 'Personalizado' AFTER iptv_password", 
        "server VARCHAR(100) AFTER value",
        "mac VARCHAR(17) AFTER server",
        "notifications ENUM('sim', 'nao') DEFAULT 'sim' AFTER mac",
        "screens INT DEFAULT 1 AFTER notifications"
    ];
    
    foreach ($fields as $field) {
        try {
            $pdo->exec("ALTER TABLE clients ADD COLUMN $field");
            echo "<p>✅ Campo adicionado: $field</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<p>⚠️ Campo já existe: $field</p>";
            } else {
                throw $e;
            }
        }
    }
    
    // 3. Tornar start_date opcional
    echo "<p>🔄 Tornando start_date opcional...</p>";
    $pdo->exec("ALTER TABLE clients MODIFY COLUMN start_date DATE");
    echo "<p>✅ Campo start_date alterado</p>";
    
    // 4. Limpar dados de exemplo antigos se existirem
    echo "<p>🔄 Limpando dados antigos...</p>";
    $pdo->exec("DELETE FROM clients WHERE reseller_id = 'admin-001' AND id IN ('client-001', 'client-002', 'client-003', 'client-004', 'client-005')");
    echo "<p>✅ Dados antigos removidos</p>";
    
    // 5. Inserir dados de exemplo atualizados
    echo "<p>🔄 Inserindo dados de exemplo...</p>";
    
    $sampleData = [
        ['admin-001', 'João Silva Santos', 'joao@email.com', '(11) 99999-1234', 'joao123', 'senha123', 'iptv123', 'Premium', '2024-10-01', '2025-10-28', 'active', 35.00, 'Servidor Principal', 'AA:BB:CC:DD:EE:FF', 'sim', 2, 'Cliente VIP'],
        ['admin-001', 'Maria Oliveira Costa', 'maria@email.com', '(11) 98888-5678', 'maria456', 'senha456', 'iptv456', 'Básico', '2024-09-15', '2025-10-30', 'active', 25.00, 'Servidor Backup', 'BB:CC:DD:EE:FF:AA', 'sim', 1, ''],
        ['admin-001', 'Pedro Souza Lima', 'pedro@email.com', '(11) 97777-9012', 'pedro789', 'senha789', 'iptv789', 'VIP', '2024-08-20', '2025-11-05', 'active', 50.00, 'Servidor Premium', 'CC:DD:EE:FF:AA:BB', 'sim', 3, 'Pagamento em dia'],
        ['admin-001', 'Ana Carolina Ferreira', 'ana@email.com', '(11) 96666-3456', 'ana321', 'senha321', 'iptv321', 'Premium', '2024-07-10', '2025-10-25', 'inactive', 35.00, 'Servidor Principal', 'DD:EE:FF:AA:BB:CC', 'nao', 1, 'Vencido há 2 dias'],
        ['admin-001', 'Carlos Eduardo Rocha', 'carlos@email.com', '(11) 95555-7890', 'carlos654', 'senha654', 'iptv654', 'Básico', '2024-06-05', '2025-11-10', 'suspended', 25.00, 'Servidor Backup', 'EE:FF:AA:BB:CC:DD', 'nao', 1, 'Suspenso por falta de pagamento']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO clients (reseller_id, name, email, phone, username, password, iptv_password, plan, start_date, renewal_date, status, value, server, mac, notifications, screens, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($sampleData as $data) {
        $stmt->execute($data);
    }
    
    echo "<p>✅ Dados de exemplo inseridos</p>";
    
    // 6. Verificar estrutura final
    echo "<p>🔄 Verificando estrutura final...</p>";
    $result = $pdo->query("DESCRIBE clients");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Estrutura da tabela clients:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 7. Verificar dados inseridos
    echo "<h3>Dados inseridos:</h3>";
    $result = $pdo->query("SELECT id, name, email, plan, value, status FROM clients WHERE reseller_id = 'admin-001' ORDER BY id");
    $clients = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Plano</th><th>Valor</th><th>Status</th></tr>";
    foreach ($clients as $client) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($client['id']) . "</td>";
        echo "<td>" . htmlspecialchars($client['name']) . "</td>";
        echo "<td>" . htmlspecialchars($client['email']) . "</td>";
        echo "<td>" . htmlspecialchars($client['plan']) . "</td>";
        echo "<td>R$ " . number_format($client['value'], 2, ',', '.') . "</td>";
        echo "<td>" . htmlspecialchars($client['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 style='color: green;'>✅ Banco de dados atualizado com sucesso!</h2>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ul>";
    echo "<li>Teste criar um novo cliente</li>";
    echo "<li>Teste editar um cliente existente</li>";
    echo "<li>Teste excluir um cliente</li>";
    echo "<li>Recarregue a página para verificar se os dados persistem</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erro ao atualizar banco de dados:</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Dica:</strong> Verifique se o MySQL está rodando e se as credenciais estão corretas no arquivo .env</p>";
}
?>
