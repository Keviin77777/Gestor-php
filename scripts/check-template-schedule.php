<?php
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
loadEnv(__DIR__ . '/../.env');

$template = Database::fetch(
    "SELECT id, name, type, is_scheduled, scheduled_days, scheduled_time 
     FROM whatsapp_templates 
     WHERE type = ?",
    ['expires_3d']
);

echo "Template: {$template['name']}\n";
echo "Type: {$template['type']}\n";
echo "Is Scheduled: " . ($template['is_scheduled'] ? 'YES' : 'NO') . "\n";
echo "Scheduled Days (raw): " . var_export($template['scheduled_days'], true) . "\n";
echo "Scheduled Time: {$template['scheduled_time']}\n\n";

$days = json_decode($template['scheduled_days'], true);
echo "Scheduled Days (decoded): " . var_export($days, true) . "\n";
echo "Is array: " . (is_array($days) ? 'YES' : 'NO') . "\n";
echo "Is empty: " . (empty($days) ? 'YES' : 'NO') . "\n";
