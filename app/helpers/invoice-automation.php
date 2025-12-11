<?php
/**
 * Automação de Faturas
 * Funções para gerar faturas automaticamente
 */

require_once __DIR__ . '/../core/Database.php';

/**
 * Verificar se cliente precisa de fatura automática
 * @param string $clientId ID do cliente
 * @param string $renewalDate Data de renovação (Y-m-d)
 * @return bool
 */
function shouldGenerateInvoice($clientId, $renewalDate) {
    try {
        // Calcular dias até o vencimento
        $today = new DateTime();
        $renewal = new DateTime($renewalDate);
        $interval = $today->diff($renewal);
        
        // Calcular dias restantes (positivo = futuro, negativo = passado)
        $daysUntilRenewal = $interval->days;
        if ($renewal < $today) {
            $daysUntilRenewal = -$daysUntilRenewal; // Negativo se já passou
        }
        
        error_log("shouldGenerateInvoice - Cliente: $clientId, Renovação: $renewalDate, Dias: $daysUntilRenewal");
        
        // Gerar fatura se faltam 10 dias ou menos (incluindo datas passadas)
        if ($daysUntilRenewal <= 10) {
            // Verificar se já existe fatura pendente para este cliente
            $existingInvoice = Database::fetch(
                "SELECT id FROM invoices 
                 WHERE client_id = ? 
                 AND status IN ('pending', 'overdue') 
                 ORDER BY created_at DESC 
                 LIMIT 1",
                [$clientId]
            );
            
            if ($existingInvoice) {
                error_log("Fatura já existe para cliente $clientId: " . $existingInvoice['id']);
                return false;
            }
            
            error_log("Deve gerar fatura para cliente $clientId");
            return true;
        }
        
        error_log("Não deve gerar fatura para cliente $clientId (mais de 10 dias)");
        return false;
    } catch (Exception $e) {
        error_log("Erro ao verificar necessidade de fatura: " . $e->getMessage());
        return false;
    }
}

/**
 * Gerar fatura automática para cliente
 * @param array $client Dados do cliente
 * @return string|false ID da fatura gerada ou false em caso de erro
 */
function generateAutomaticInvoice($client) {
    try {
        // Validar dados do cliente
        if (!$client || !isset($client['id'], $client['value'], $client['name'])) {
            throw new Exception('Dados do cliente inválidos');
        }
        
        // Obter usuário autenticado
        require_once __DIR__ . '/../core/Auth.php';
        $user = Auth::user();
        if (!$user) {
            throw new Exception('Usuário não autenticado');
        }
        
        // Gerar ID único para a fatura
        $invoiceId = 'inv-' . uniqid();
        
        // Calcular datas
        $issueDate = date('Y-m-d');
        // Usar a data de renovação do cliente como vencimento da fatura
        $dueDate = $client['renewal_date'];
        
        // Dados da fatura
        $value = (float)$client['value'];
        $discount = 0.00;
        $finalValue = $value - $discount;
        
        // Inserir fatura no banco
        Database::query(
            "INSERT INTO invoices (
                id, reseller_id, client_id, value, discount, final_value, 
                issue_date, due_date, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())",
            [
                $invoiceId,
                $user['id'], // Usar ID do usuário autenticado
                $client['id'],
                $value,
                $discount,
                $finalValue,
                $issueDate,
                $dueDate
            ]
        );
        
        // Log da ação
        error_log("Fatura automática gerada: {$invoiceId} para cliente {$client['name']} (ID: {$client['id']})");
        
        // Enviar mensagem WhatsApp se configurado
        try {
            require_once __DIR__ . '/whatsapp-automation.php';
            $whatsappResult = sendAutomaticInvoiceMessage($invoiceId);
            if ($whatsappResult['success']) {
                error_log("WhatsApp enviado para fatura {$invoiceId}: {$whatsappResult['message_id']}");
            } else {
                error_log("Erro ao enviar WhatsApp para fatura {$invoiceId}: {$whatsappResult['error']}");
            }
        } catch (Exception $e) {
            error_log("Erro ao enviar WhatsApp para fatura {$invoiceId}: " . $e->getMessage());
        }
        
        return $invoiceId;
        
    } catch (Exception $e) {
        error_log("Erro ao gerar fatura automática: " . $e->getMessage());
        return false;
    }
}

/**
 * Processar automação de faturas para um cliente específico
 * @param array $client Dados do cliente
 * @return array Resultado da operação
 */
function processClientInvoiceAutomation($client) {
    try {
        if (!shouldGenerateInvoice($client['id'], $client['renewal_date'])) {
            return [
                'generated' => false,
                'reason' => 'Não necessário gerar fatura'
            ];
        }
        
        $invoiceId = generateAutomaticInvoice($client);
        
        if ($invoiceId) {
            return [
                'generated' => true,
                'invoice_id' => $invoiceId,
                'message' => 'Fatura gerada automaticamente'
            ];
        } else {
            return [
                'generated' => false,
                'reason' => 'Erro ao gerar fatura'
            ];
        }
        
    } catch (Exception $e) {
        error_log("Erro no processamento de automação: " . $e->getMessage());
        return [
            'generated' => false,
            'reason' => 'Erro: ' . $e->getMessage()
        ];
    }
}

/**
 * Executar automação de faturas para todos os clientes elegíveis
 * @return array Relatório da execução
 */
function runInvoiceAutomation($resellerId = null) {
    try {
        // Se não foi passado reseller_id, tentar obter do usuário autenticado
        if (!$resellerId) {
            require_once __DIR__ . '/../core/Auth.php';
            $user = Auth::user();
            if (!$user) {
                throw new Exception('Usuário não autenticado e reseller_id não fornecido');
            }
            $resellerId = $user['id'];
        }
        
        // Configurações da automação
        $daysBeforeRenewal = (int)(getenv('INVOICE_AUTOMATION_DAYS') ?: 10);
        $maxInvoicesPerRun = (int)(getenv('INVOICE_AUTOMATION_MAX_PER_RUN') ?: 50);
        
        // Buscar clientes ativos com renovação próxima
        $clients = Database::fetchAll(
            "SELECT 
                c.id, c.name, c.email, c.phone, c.value, c.renewal_date, c.plan, c.server,
                DATEDIFF(c.renewal_date, CURDATE()) as days_until_renewal,
                (SELECT COUNT(*) FROM invoices i 
                 WHERE i.client_id = c.id 
                 AND i.status IN ('pending', 'overdue') 
                 AND MONTH(i.issue_date) = MONTH(CURDATE()) 
                 AND YEAR(i.issue_date) = YEAR(CURDATE())
                ) as pending_invoices_this_month
             FROM clients c
             WHERE c.reseller_id = ? 
             AND c.status = 'active'
             AND DATEDIFF(c.renewal_date, CURDATE()) <= ?
             AND DATEDIFF(c.renewal_date, CURDATE()) >= -5
             ORDER BY c.renewal_date ASC
             LIMIT ?",
            [$resellerId, $daysBeforeRenewal, $maxInvoicesPerRun * 2]
        );
        
        $report = [
            'execution_time' => date('Y-m-d H:i:s'),
            'config' => [
                'days_before_renewal' => $daysBeforeRenewal,
                'max_invoices_per_run' => $maxInvoicesPerRun
            ],
            'total_clients_checked' => count($clients),
            'invoices_generated' => 0,
            'clients_processed' => [],
            'errors' => [],
            'skipped_clients' => []
        ];
        
        $invoicesGenerated = 0;
        
        foreach ($clients as $client) {
            // Parar se atingiu o limite de faturas por execução
            if ($invoicesGenerated >= $maxInvoicesPerRun) {
                $report['skipped_clients'][] = [
                    'client_id' => $client['id'],
                    'client_name' => $client['name'],
                    'reason' => 'Limite de faturas por execução atingido'
                ];
                continue;
            }
            
            // Verificar se cliente já tem fatura pendente
            if ($client['pending_invoices_this_month'] > 0) {
                $report['skipped_clients'][] = [
                    'client_id' => $client['id'],
                    'client_name' => $client['name'],
                    'reason' => 'Já possui fatura pendente este mês'
                ];
                continue;
            }
            
            try {
                $result = processClientInvoiceAutomation($client);
                
                $report['clients_processed'][] = [
                    'client_id' => $client['id'],
                    'client_name' => $client['name'],
                    'renewal_date' => $client['renewal_date'],
                    'days_until_renewal' => $client['days_until_renewal'],
                    'value' => $client['value'],
                    'result' => $result
                ];
                
                if ($result['generated']) {
                    $invoicesGenerated++;
                    $report['invoices_generated']++;
                }
                
            } catch (Exception $e) {
                $report['errors'][] = [
                    'client_id' => $client['id'],
                    'client_name' => $client['name'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Adicionar estatísticas finais
        $report['statistics'] = [
            'success_rate' => $report['total_clients_checked'] > 0 
                ? round(($report['invoices_generated'] / $report['total_clients_checked']) * 100, 2) 
                : 0,
            'error_count' => count($report['errors']),
            'skipped_count' => count($report['skipped_clients'])
        ];
        
        return $report;
        
    } catch (Exception $e) {
        error_log("Erro na automação de faturas: " . $e->getMessage());
        return [
            'error' => $e->getMessage(),
            'execution_time' => date('Y-m-d H:i:s'),
            'total_clients_checked' => 0,
            'invoices_generated' => 0,
            'clients_processed' => [],
            'errors' => [],
            'skipped_clients' => []
        ];
    }
}

/**
 * Verificar e gerar fatura para cliente recém-criado ou atualizado
 * @param array $client Dados do cliente
 * @return array Resultado da verificação
 */
function checkAndGenerateInvoiceForClient($client) {
    try {
        error_log("checkAndGenerateInvoiceForClient - Iniciando para cliente: " . $client['id']);
        error_log("Dados do cliente: " . json_encode($client));
        
        // Verificar se precisa gerar fatura
        if (shouldGenerateInvoice($client['id'], $client['renewal_date'])) {
            error_log("Gerando fatura automática para cliente: " . $client['id']);
            $invoiceId = generateAutomaticInvoice($client);
            
            if ($invoiceId) {
                error_log("Fatura gerada com sucesso: $invoiceId");
                return [
                    'invoice_generated' => true,
                    'invoice_id' => $invoiceId,
                    'message' => 'Fatura gerada automaticamente - cliente com renovação próxima'
                ];
            } else {
                error_log("Falha ao gerar fatura para cliente: " . $client['id']);
            }
        } else {
            error_log("Fatura não necessária para cliente: " . $client['id']);
        }
        
        return [
            'invoice_generated' => false,
            'message' => 'Fatura não necessária no momento'
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao verificar fatura para cliente: " . $e->getMessage());
        return [
            'invoice_generated' => false,
            'error' => $e->getMessage()
        ];
    }
}