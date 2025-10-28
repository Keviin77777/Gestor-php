<?php
/**
 * Script para corrigir dados dos revendedores
 */

require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Database.php';
loadEnv(__DIR__ . '/../.env');

try {
    echo "=== CORREÇÃO DE DADOS DOS REVENDEDORES ===\n\n";
    
    // 1. Verificar usuários sem plano definido
    echo "1. Verificando usuários sem plano definido...\n";
    $usersWithoutPlan = Database::fetchAll("
        SELECT id, email, name, current_plan_id, plan_expires_at, plan_status 
        FROM users 
        WHERE (is_admin = FALSE OR is_admin IS NULL) AND current_plan_id IS NULL
    ");
    
    if (count($usersWithoutPlan) > 0) {
        echo "Encontrados " . count($usersWithoutPlan) . " usuários sem plano:\n";
        foreach ($usersWithoutPlan as $user) {
            echo "- {$user['email']} ({$user['name']})\n";
        }
        
        // Atualizar usuários sem plano para trial
        echo "\nAtualizando usuários para plano trial...\n";
        Database::query("
            UPDATE users 
            SET 
                current_plan_id = 'plan-trial',
                plan_expires_at = DATE_ADD(NOW(), INTERVAL 3 DAY),
                plan_status = 'active'
            WHERE (is_admin = FALSE OR is_admin IS NULL) 
            AND current_plan_id IS NULL
        ");
        echo "Usuários atualizados com sucesso!\n";
    } else {
        echo "Todos os usuários já possuem plano definido.\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // 2. Verificar usuários com plano trial mas sem data de expiração
    echo "2. Verificando usuários trial sem data de expiração...\n";
    $trialWithoutExpiry = Database::fetchAll("
        SELECT id, email, name, current_plan_id, plan_expires_at, plan_status, created_at
        FROM users 
        WHERE (is_admin = FALSE OR is_admin IS NULL)
        AND current_plan_id = 'plan-trial' 
        AND plan_expires_at IS NULL
    ");
    
    if (count($trialWithoutExpiry) > 0) {
        echo "Encontrados " . count($trialWithoutExpiry) . " usuários trial sem expiração:\n";
        foreach ($trialWithoutExpiry as $user) {
            echo "- {$user['email']} ({$user['name']}) - Criado em: {$user['created_at']}\n";
        }
        
        // Corrigir usuários trial sem data de expiração
        echo "\nCorrigindo datas de expiração...\n";
        Database::query("
            UPDATE users 
            SET plan_expires_at = DATE_ADD(created_at, INTERVAL 3 DAY)
            WHERE (is_admin = FALSE OR is_admin IS NULL)
            AND current_plan_id = 'plan-trial' 
            AND plan_expires_at IS NULL
        ");
        echo "Usuários corrigidos com sucesso!\n";
    } else {
        echo "Todos os usuários trial já possuem data de expiração.\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // 3. Verificar status final
    echo "3. Status final dos usuários:\n";
    $finalStatus = Database::fetchAll("
        SELECT 
            u.id,
            u.email,
            u.name,
            u.current_plan_id,
            u.plan_expires_at,
            u.plan_status,
            COALESCE(rp.name, 'Sem plano') as plan_name,
            COALESCE(rp.is_trial, FALSE) as is_trial,
            CASE 
                WHEN u.plan_expires_at IS NULL THEN 0
                ELSE GREATEST(0, DATEDIFF(u.plan_expires_at, NOW()))
            END as days_remaining
        FROM users u
        LEFT JOIN reseller_plans rp ON u.current_plan_id = rp.id
        WHERE u.is_admin = FALSE OR u.is_admin IS NULL
        ORDER BY u.created_at DESC
    ");
    
    echo "Total de revendedores: " . count($finalStatus) . "\n\n";
    
    $stats = [
        'total' => count($finalStatus),
        'active' => 0,
        'trial' => 0,
        'expired' => 0,
        'suspended' => 0
    ];
    
    foreach ($finalStatus as $user) {
        echo "- {$user['email']} | {$user['plan_name']} | ";
        echo "Expira: " . ($user['plan_expires_at'] ? $user['plan_expires_at'] : 'N/A') . " | ";
        echo "Dias restantes: {$user['days_remaining']} | ";
        echo "Status: {$user['plan_status']}\n";
        
        // Contar estatísticas
        if ($user['plan_status'] === 'active') {
            $stats['active']++;
        } elseif ($user['plan_status'] === 'suspended') {
            $stats['suspended']++;
        }
        
        if ($user['days_remaining'] <= 0 && $user['plan_expires_at']) {
            $stats['expired']++;
        }
        
        if ($user['is_trial'] && $user['current_plan_id'] === 'plan-trial') {
            $stats['trial']++;
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "ESTATÍSTICAS FINAIS:\n";
    echo "Total: {$stats['total']}\n";
    echo "Ativos: {$stats['active']}\n";
    echo "Em Trial: {$stats['trial']}\n";
    echo "Vencidos: {$stats['expired']}\n";
    echo "Suspensos: {$stats['suspended']}\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "\n✅ Correção concluída com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>