<?php
/**
 * Script para adicionar colunas necessárias na tabela users
 * Execute este script uma vez para garantir que todas as colunas existem
 */

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/functions.php';

// Carregar variáveis de ambiente
loadEnv(__DIR__ . '/../.env');

try {
    echo "=== Script de Migração de Colunas do Perfil ===\n\n";
    
    // Conectar ao banco
    Database::connect();
    echo "✓ Conexão com banco de dados estabelecida\n";
    
    $dbName = env('DB_NAME', 'ultragestor_php');
    echo "✓ Banco de dados: {$dbName}\n\n";
    
    // Função auxiliar para verificar se coluna existe
    function columnExists($dbName, $tableName, $columnName) {
        $result = Database::fetch("
            SELECT COUNT(*) as col_count 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?
        ", [$dbName, $tableName, $columnName]);
        
        return $result && (int)($result['col_count'] ?? 0) > 0;
    }
    
    // Função auxiliar para adicionar coluna
    function addColumn($tableName, $columnName, $definition) {
        try {
            Database::query("ALTER TABLE {$tableName} ADD COLUMN {$columnName} {$definition}");
            echo "✓ Coluna '{$columnName}' adicionada com sucesso\n";
            return true;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "- Coluna '{$columnName}' já existe\n";
                return false;
            }
            throw $e;
        }
    }
    
    $tableName = 'users';
    $columnsAdded = 0;
    
    // 1. Verificar e adicionar coluna phone
    echo "Verificando coluna 'phone'...\n";
    if (!columnExists($dbName, $tableName, 'phone')) {
        // Verificar se whatsapp existe para decidir onde adicionar
        if (columnExists($dbName, $tableName, 'whatsapp')) {
            addColumn($tableName, 'phone', "VARCHAR(20) NULL AFTER whatsapp");
        } else {
            addColumn($tableName, 'phone', "VARCHAR(20) NULL");
        }
        $columnsAdded++;
    } else {
        echo "- Coluna 'phone' já existe\n";
    }
    
    // 2. Verificar e adicionar coluna company
    echo "\nVerificando coluna 'company'...\n";
    if (!columnExists($dbName, $tableName, 'company')) {
        if (columnExists($dbName, $tableName, 'phone')) {
            addColumn($tableName, 'company', "VARCHAR(255) NULL AFTER phone");
        } else {
            addColumn($tableName, 'company', "VARCHAR(255) NULL");
        }
        $columnsAdded++;
    } else {
        echo "- Coluna 'company' já existe\n";
    }
    
    // 3. Verificar e adicionar coluna is_admin
    echo "\nVerificando coluna 'is_admin'...\n";
    if (!columnExists($dbName, $tableName, 'is_admin')) {
        if (columnExists($dbName, $tableName, 'role')) {
            addColumn($tableName, 'is_admin', "BOOLEAN DEFAULT FALSE AFTER role");
        } else {
            addColumn($tableName, 'is_admin', "BOOLEAN DEFAULT FALSE");
        }
        $columnsAdded++;
        
        // Atualizar is_admin baseado no role existente
        echo "Atualizando valores de is_admin baseado no role...\n";
        Database::query("
            UPDATE users 
            SET is_admin = CASE 
                WHEN role = 'admin' THEN TRUE 
                ELSE FALSE 
            END
            WHERE is_admin IS NULL OR (role = 'admin' AND is_admin = FALSE)
        ");
        echo "✓ Valores de is_admin atualizados\n";
    } else {
        echo "- Coluna 'is_admin' já existe\n";
    }
    
    // 4. Verificar e adicionar coluna current_plan_id
    echo "\nVerificando coluna 'current_plan_id'...\n";
    if (!columnExists($dbName, $tableName, 'current_plan_id')) {
        if (columnExists($dbName, $tableName, 'is_admin')) {
            addColumn($tableName, 'current_plan_id', "VARCHAR(50) DEFAULT NULL AFTER is_admin");
        } else {
            addColumn($tableName, 'current_plan_id', "VARCHAR(50) DEFAULT NULL");
        }
        $columnsAdded++;
    } else {
        echo "- Coluna 'current_plan_id' já existe\n";
    }
    
    // 5. Verificar e adicionar coluna plan_expires_at
    echo "\nVerificando coluna 'plan_expires_at'...\n";
    if (!columnExists($dbName, $tableName, 'plan_expires_at')) {
        if (columnExists($dbName, $tableName, 'current_plan_id')) {
            addColumn($tableName, 'plan_expires_at', "DATETIME NULL AFTER current_plan_id");
        } else {
            addColumn($tableName, 'plan_expires_at', "DATETIME NULL");
        }
        $columnsAdded++;
    } else {
        echo "- Coluna 'plan_expires_at' já existe\n";
    }
    
    // 6. Verificar se tabela reseller_plans existe
    echo "\nVerificando tabela 'reseller_plans'...\n";
    $tableExists = Database::fetch("
        SELECT COUNT(*) as count 
        FROM INFORMATION_SCHEMA.TABLES 
        WHERE TABLE_SCHEMA = ? 
        AND TABLE_NAME = 'reseller_plans'
    ", [$dbName]);
    
    if ($tableExists && (int)($tableExists['count'] ?? 0) > 0) {
        echo "✓ Tabela 'reseller_plans' existe\n";
    } else {
        echo "⚠ Tabela 'reseller_plans' não existe. Criando...\n";
        Database::query("
            CREATE TABLE IF NOT EXISTS reseller_plans (
                id VARCHAR(50) PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT NULL,
                price DECIMAL(10,2) NOT NULL,
                duration_days INT NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                is_trial BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_active (is_active),
                INDEX idx_trial (is_trial)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Inserir planos padrão
        Database::query("
            INSERT IGNORE INTO reseller_plans (id, name, description, price, duration_days, is_active, is_trial) VALUES
            ('plan-trial', 'Trial 3 Dias', 'Período de teste gratuito de 3 dias', 0.00, 3, TRUE, TRUE),
            ('plan-monthly', 'Mensal', 'Plano mensal para revendedores', 39.90, 30, TRUE, FALSE),
            ('plan-quarterly', 'Trimestral', 'Plano trimestral para revendedores', 120.00, 90, TRUE, FALSE),
            ('plan-biannual', 'Semestral', 'Plano semestral para revendedores', 180.00, 180, TRUE, FALSE),
            ('plan-annual', 'Anual', 'Plano anual para revendedores', 299.00, 365, TRUE, FALSE)
        ");
        
        echo "✓ Tabela 'reseller_plans' criada com planos padrão\n";
    }
    
    // Resumo final
    echo "\n=== Resumo ===\n";
    echo "Colunas adicionadas: {$columnsAdded}\n";
    echo "Status: ✓ Migração concluída com sucesso!\n\n";
    
    // Verificar usuários sem plano
    echo "Verificando usuários sem plano...\n";
    $usersWithoutPlan = Database::fetchAll("
        SELECT id, email, name, role, is_admin 
        FROM users 
        WHERE (is_admin = FALSE OR is_admin IS NULL OR is_admin = 0)
        AND (current_plan_id IS NULL OR current_plan_id = '')
    ");
    
    if (count($usersWithoutPlan) > 0) {
        echo "⚠ Encontrados " . count($usersWithoutPlan) . " usuários sem plano atribuído\n";
        echo "Atribuindo plano trial aos usuários sem plano...\n";
        
        Database::query("
            UPDATE users 
            SET 
                current_plan_id = 'plan-trial',
                plan_expires_at = DATE_ADD(NOW(), INTERVAL 3 DAY)
            WHERE (is_admin = FALSE OR is_admin IS NULL OR is_admin = 0)
            AND (current_plan_id IS NULL OR current_plan_id = '')
        ");
        
        echo "✓ Plano trial atribuído aos usuários sem plano\n";
    } else {
        echo "✓ Todos os usuários têm plano atribuído\n";
    }
    
    echo "\n=== Script concluído com sucesso! ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>

