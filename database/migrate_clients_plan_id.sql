-- Script para migrar clientes para usar plan_id em vez de apenas o nome do plano
-- Execute este script para corrigir a associação de clientes com planos

USE ultragestor_php;

-- Atualizar clientes que têm plan_id NULL para usar o ID correto baseado no nome do plano
UPDATE clients c
SET c.plan_id = (
    SELECT p.id 
    FROM subscription_plans p 
    WHERE p.name = c.plan 
    AND p.reseller_id = c.reseller_id 
    ORDER BY p.created_at ASC 
    LIMIT 1
)
WHERE c.plan_id IS NULL 
AND c.plan IS NOT NULL 
AND c.plan != 'Personalizado'
AND EXISTS (
    SELECT 1 
    FROM subscription_plans p 
    WHERE p.name = c.plan 
    AND p.reseller_id = c.reseller_id
);

-- Verificar o resultado
SELECT 
    c.id,
    c.name as client_name,
    c.plan as plan_name,
    c.plan_id,
    p.name as plan_real_name,
    s.name as server_name
FROM clients c
LEFT JOIN subscription_plans p ON c.plan_id = p.id
LEFT JOIN servers s ON p.server_id = s.id
WHERE c.reseller_id = 'admin-001'
ORDER BY c.created_at DESC;