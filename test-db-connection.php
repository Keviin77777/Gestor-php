<?php
try {
    $pdo = new PDO('mysql:host=157.173.104.203;port=3306;dbname=ultragestor_php', 'ultragestor_php', 'ksZfGDNh3WidYYNh');
    echo "✅ Conexão com banco de dados OK!\n";
    echo "Status: Conectado\n";
} catch(Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
