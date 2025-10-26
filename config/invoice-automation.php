<?php
/**
 * Configurações da Automação de Faturas
 * Centralize todas as configurações relacionadas à automação
 */

return [
    // Configurações gerais
    'enabled' => getenv('INVOICE_AUTOMATION_ENABLED') !== 'false',
    'days_before_renewal' => (int)(getenv('INVOICE_AUTOMATION_DAYS') ?: 10),
    'max_invoices_per_run' => (int)(getenv('INVOICE_AUTOMATION_MAX_PER_RUN') ?: 50),
    'days_to_pay' => (int)(getenv('INVOICE_DAYS_TO_PAY') ?: 5),
    
    // Configurações de execução
    'execution_times' => [
        '09:00:00', // Manhã
        '14:00:00', // Tarde
        '20:00:00'  // Noite
    ],
    
    // Configurações de notificação
    'notifications' => [
        'admin_email' => getenv('ADMIN_EMAIL'),
        'send_on_generation' => getenv('NOTIFY_ON_INVOICE_GENERATION') !== 'false',
        'send_on_error' => getenv('NOTIFY_ON_ERROR') !== 'false',
    ],
    
    // Configurações de log
    'logging' => [
        'enabled' => true,
        'level' => getenv('LOG_LEVEL') ?: 'info', // debug, info, warning, error
        'max_log_files' => 30, // Manter logs por 30 dias
        'log_rotation' => true
    ],
    
    // Configurações de segurança
    'security' => [
        'max_execution_time' => 300, // 5 minutos
        'memory_limit' => '128M',
        'lock_timeout' => 3600, // 1 hora
    ],
    
    // Configurações de fatura
    'invoice_defaults' => [
        'reseller_id' => 'admin-001',
        'status' => 'pending',
        'discount' => 0.00,
        'currency' => 'BRL'
    ],
    
    // Configurações de retry
    'retry' => [
        'max_attempts' => 3,
        'delay_seconds' => 60,
        'exponential_backoff' => true
    ]
];