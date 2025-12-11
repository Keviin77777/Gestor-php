-- Adicionar coluna payment_provider à tabela invoice_payments
-- Esta coluna armazena qual provedor foi usado (asaas, mercadopago, efibank)

ALTER TABLE invoice_payments 
ADD COLUMN payment_provider VARCHAR(50) DEFAULT 'mercadopago' AFTER payment_method;

-- Criar índice para melhor performance
CREATE INDEX idx_payment_provider ON invoice_payments(payment_provider);
