<?php
echo "<h1>Teste Simples</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";

echo "<hr>";
echo "<h2>Teste de includes:</h2>";

$helpersPath = __DIR__ . '/../app/helpers/functions.php';
echo "<p>Helpers path: $helpersPath</p>";
echo "<p>Helpers exists: " . (file_exists($helpersPath) ? 'YES' : 'NO') . "</p>";

if (file_exists($helpersPath)) {
    require_once $helpersPath;
    echo "<p>Helpers loaded: YES</p>";
    
    $envPath = __DIR__ . '/../.env';
    echo "<p>ENV path: $envPath</p>";
    echo "<p>ENV exists: " . (file_exists($envPath) ? 'YES' : 'NO') . "</p>";
    
    if (file_exists($envPath)) {
        loadEnv($envPath);
        echo "<p>ENV loaded: YES</p>";
        echo "<p>DB_NAME: " . env('DB_NAME', 'NOT SET') . "</p>";
    }
}

echo "<hr>";
echo "<h2>Teste de Database:</h2>";

$dbPath = __DIR__ . '/../app/core/Database.php';
if (file_exists($dbPath)) {
    require_once $dbPath;
    echo "<p>Database class loaded</p>";
    
    try {
        $pdo = Database::connect();
        echo "<p style='color: green;'>✓ Database connected!</p>";
        
        $result = Database::fetch("SELECT COUNT(*) as total FROM users");
        echo "<p>Total users: " . $result['total'] . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
    }
}
