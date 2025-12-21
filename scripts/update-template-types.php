<?php
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
loadEnv(__DIR__ . '/../.env');

$sql = file_get_contents(__DIR__ . '/../database/update-template-types-reseller.sql');
Database::query($sql);
echo "✅ Tipos de templates atualizados com sucesso!\n";
