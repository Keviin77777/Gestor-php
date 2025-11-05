<?php
/**
 * API pública para planos de revendedores (para landing page)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
loadEnv(__DIR__ . '/../.env');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método não permitido');
    }
    
    // Buscar apenas planos ativos para exibição pública
    $plans = Database::fetchAll("
        SELECT 
            id, 
            name, 
            description, 
            price, 
            duration_days, 
            is_trial,
            is_active
        FROM reseller_plans 
        WHERE is_active = TRUE 
        ORDER BY is_trial DESC, price ASC
    ");
    
    // Formatar dados para exibição
    foreach ($plans as &$plan) {
        $plan['price'] = (float)$plan['price'];
        $plan['duration_days'] = (int)$plan['duration_days'];
        $plan['is_trial'] = (bool)$plan['is_trial'];
        $plan['is_active'] = (bool)$plan['is_active'];
        
        // Adicionar descrição padrão se não existir
        if (empty($plan['description'])) {
            if ($plan['is_trial']) {
                $plan['description'] = 'Teste todas as funcionalidades gratuitamente';
            } else if ($plan['duration_days'] <= 31) {
                $plan['description'] = 'Ideal para começar seu negócio IPTV';
            } else if ($plan['duration_days'] <= 93) {
                $plan['description'] = 'Melhor custo-benefício para crescimento';
            } else {
                $plan['description'] = 'Máximo desconto para negócios estabelecidos';
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'plans' => $plans,
        'total' => count($plans)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>