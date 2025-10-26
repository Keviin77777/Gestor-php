<?php
/**
 * Script para instalar a tabela de servidores
 * Execute este arquivo via navegador: http://localhost:8000/install-servers-table.php
 */

// Carregar configurações
require_once __DIR__ . '/../app/helpers/functions.php';
loadEnv(__DIR__ . '/../.env');

// Configurações do banco
$host = env('DB_HOST', 'localhost');
$dbname = env('DB_NAME', 'ultragestor_php');
$username = env('DB_USER', 'root');
$password = env('DB_PASS', '');

try {
    // Conectar ao banco
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<h1>Instalação da Tabela de Servidores</h1>";
    echo "<p>Conectado ao banco de dados: <strong>$dbname</strong></p>";

    // SQL para criar a tabela
    $sql = "
    CREATE TABLE IF NOT EXISTS servers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(36) NOT NULL,
        name VARCHAR(255) NOT NULL,
        billing_type ENUM('fixed', 'per_active') NOT NULL DEFAULT 'fixed',
        cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        panel_type VARCHAR(50) NULL,
        panel_url VARCHAR(255) NULL,
        reseller_user VARCHAR(100) NULL,
        sigma_token VARCHAR(500) NULL,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    // Executar SQL
    $pdo->exec($sql);

    echo "<p style='color: green; font-weight: bold;'>✅ Tabela 'servers' criada com sucesso!</p>";

    // Verificar a tabela
    $stmt = $pdo->query("DESCRIBE servers");
    $columns = $stmt->fetchAll();

    echo "<h2>Estrutura da Tabela:</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";

    echo "<hr>";
    echo "<p><strong>Próximos passos:</strong></p>";
    echo "<ol>";
    echo "<li>Acesse a página de servidores: <a href='/servidores'>http://localhost:8000/servidores</a></li>";
    echo "<li>Teste adicionar um novo servidor</li>";
    echo "<li>Após confirmar que está funcionando, você pode deletar este arquivo (install-servers-table.php)</li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p><strong>Verifique:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL está rodando</li>";
    echo "<li>Banco de dados 'ultragestor_php' existe</li>";
    echo "<li>Credenciais no arquivo .env estão corretas</li>";
    echo "</ul>";
}
?>

<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        max-width: 900px;
        margin: 50px auto;
        padding: 20px;
        background: #f5f5f5;
    }
    h1 {
        color: #6366f1;
    }
    table {
        width: 100%;
        background: white;
        margin: 20px 0;
    }
    th {
        background: #6366f1;
        color: white;
        padding: 10px;
    }
    td {
        padding: 8px;
    }
    tr:nth-child(even) {
        background: #f9f9f9;
    }
</style>
