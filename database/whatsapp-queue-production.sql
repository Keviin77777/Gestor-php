-- ============================================
-- SCRIPT DE ATUALIZAÇÃO PARA SISTEMA DE FILA WHATSAPP
-- Execute este script na VPS de produção
-- ============================================

-- 1. Adicionar campo provider à tabela whatsapp_sessions
ALTER TABLE whatsapp_sessions 
ADD COLUMN IF NOT EXISTS provider ENUM('evolution', 'native') DEFAULT 'evolution' AFTER status;

-- 2. Atualizar sessões existentes baseado no instance_name
UPDATE whatsapp_sessions 
SET provider = 'native' 
WHERE (instance_name LIKE 'reseller_%' OR instance_name LIKE 'ultragestor-%')
AND (provider IS NULL OR provider = '');

UPDATE whatsapp_sessions 
SET provider = 'evolution' 
WHERE provider IS NULL OR provider = '';

-- 3. Verificar se as tabelas de fila já existem
-- Se não existirem, serão criadas

-- Tabela de fila de mensagens
CREATE TABLE IF NOT EXISTS whatsapp_message_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    template_id VARCHAR(50),
    client_id INT,
    status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
    priority INT DEFAULT 0 COMMENT '0=normal, 1=alta, 2=urgente',
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reseller (reseller_id),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configuração de rate limiting
CREATE TABLE IF NOT EXISTS whatsapp_rate_limit_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reseller_id VARCHAR(36) NOT NULL UNIQUE,
    messages_per_minute INT DEFAULT 20,
    messages_per_hour INT DEFAULT 100,
    delay_between_messages INT DEFAULT 3 COMMENT 'Segundos',
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reseller (reseller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- VERIFICAÇÃO
-- ============================================

-- Verificar se as tabelas foram criadas
SELECT 
    'whatsapp_sessions' as tabela,
    COUNT(*) as registros,
    'Campo provider adicionado' as status
FROM whatsapp_sessions
UNION ALL
SELECT 
    'whatsapp_message_queue' as tabela,
    COUNT(*) as registros,
    'Tabela de fila criada' as status
FROM whatsapp_message_queue
UNION ALL
SELECT 
    'whatsapp_rate_limit_config' as tabela,
    COUNT(*) as registros,
    'Tabela de config criada' as status
FROM whatsapp_rate_limit_config;

-- ============================================
-- CONCLUÍDO
-- ============================================
-- Execute este script e verifique se não há erros
-- Depois configure os crons conforme documentação
-- ============================================
