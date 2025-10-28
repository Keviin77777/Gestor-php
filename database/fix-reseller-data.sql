-- Script para corrigir dados dos revendedores

-- Verificar usuários sem plano definido
SELECT 'Usuários sem plano:' as info;
SELECT id, email, name, current_plan_id, plan_expires_at, plan_status 
FROM users 
WHERE is_admin = FALSE AND current_plan_id IS NULL;

-- Atualizar usuários sem plano para trial
UPDATE users 
SET 
    current_plan_id = 'plan-trial',
    plan_expires_at = DATE_ADD(NOW(), INTERVAL 3 DAY),
    plan_status = 'active'
WHERE is_admin = FALSE 
AND current_plan_id IS NULL;

-- Verificar usuários com plano trial mas sem data de expiração
SELECT 'Usuários trial sem expiração:' as info;
SELECT id, email, name, current_plan_id, plan_expires_at, plan_status 
FROM users 
WHERE is_admin = FALSE 
AND current_plan_id = 'plan-trial' 
AND plan_expires_at IS NULL;

-- Corrigir usuários trial sem data de expiração
UPDATE users 
SET plan_expires_at = DATE_ADD(created_at, INTERVAL 3 DAY)
WHERE is_admin = FALSE 
AND current_plan_id = 'plan-trial' 
AND plan_expires_at IS NULL;

-- Verificar status final
SELECT 'Status final dos usuários:' as info;
SELECT 
    u.id,
    u.email,
    u.name,
    u.current_plan_id,
    u.plan_expires_at,
    u.plan_status,
    rp.name as plan_name,
    rp.is_trial,
    DATEDIFF(u.plan_expires_at, NOW()) as days_remaining
FROM users u
LEFT JOIN reseller_plans rp ON u.current_plan_id = rp.id
WHERE u.is_admin = FALSE
ORDER BY u.created_at DESC;