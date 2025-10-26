<?php
/**
 * Script para corrigir charset do banco de dados
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';

// Configurações do banco (temporário para correção)
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_PORT'] = '3306';
$_ENV['DB_NAME'] = 'ultragestor_php';
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = '';

try {
    echo "Iniciando correção de charset...\n\n";
    
    // Conectar ao banco
    $pdo = Database::connect();
    
    // Ler o arquivo SQL
    $sql = file_get_contents(__DIR__ . '/fix-charset.sql');
    
    // Separar as queries
    $queries = array_filter(
        array_map('trim', explode(';', $sql)),
        function($query) {
            return !empty($query) && !str_starts_with($query, '--');
        }
    );
    
    // Executar cada query
    foreach ($queries as $query) {
        if (empty($query)) continue;
        
        echo "Executando: " . substr($query, 0, 80) . "...\n";
        
        try {
            $pdo->exec($query);
            echo "✓ Sucesso\n\n";
        } catch (PDOException $e) {
            echo "✗ Erro: " . $e->getMessage() . "\n\n";
        }
    }
    
    echo "\n✓ Correção de charset concluída!\n";
    echo "\nAgora atualize a página do navegador para ver as correções.\n";
    
} catch (Exception $e) {
    echo "✗ Erro fatal: " . $e->getMessage() . "\n";
    exit(1);
}
