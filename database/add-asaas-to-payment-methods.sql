-- Adicionar suporte ao Asaas na tabela payment_methods

-- Inserir registro padrão para Asaas (desabilitado)
INSERT INTO payment_methods (method_name, config_value, enabled) 
VALUES ('asaas', '{"api_key":"","sandbox":false}', 0)
ON DUPLICATE KEY UPDATE method_name = method_name;
