<?php
/**
 * Automação de WhatsApp
 * Sistema de envio automático de mensagens baseado em templates
 */

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/whatsapp-helper.php';

/**
 * Executar agendamentos personalizados de templates
 * @param string $resellerId
 * @return array Relatório da execução
 */
function runScheduledTemplates($resellerId = 'admin-001') {
    $report = [
        'execution_time' => date('Y-m-d H:i:s'),
        'messages_sent' => 0,
        'templates_processed' => [],
        'errors' => [],
        'debug' => []
    ];

    try {
        // Buscar templates com agendamento ativo
        $scheduledTemplates = Database::fetchAll(
            "SELECT * FROM whatsapp_templates 
             WHERE reseller_id = ? 
             AND is_scheduled = 1 
             AND is_active = 1
             AND scheduled_days IS NOT NULL 
             AND scheduled_time IS NOT NULL",
            [$resellerId]
        );

        $report['debug'][] = "Templates com agendamento ativo encontrados: " . count($scheduledTemplates);

        $currentDay = strtolower(date('l')); // monday, tuesday, etc.
        $currentTime = date('H:i:s');
        
        $report['debug'][] = "Dia atual: $currentDay";
        $report['debug'][] = "Hora atual: $currentTime";

        foreach ($scheduledTemplates as $template) {
            $scheduledDays = json_decode($template['scheduled_days'], true);
            $scheduledTime = $template['scheduled_time'];

            $report['debug'][] = "Verificando template: {$template['name']} (ID: {$template['id']})";
            $report['debug'][] = "  Dias agendados: " . implode(', ', $scheduledDays ?: []);
            $report['debug'][] = "  Horário agendado: $scheduledTime";

            // Verificar se hoje é um dia agendado
            if (!in_array($currentDay, $scheduledDays)) {
                $report['debug'][] = "  ❌ Hoje ($currentDay) não está nos dias agendados";
                continue;
            }
            
            $report['debug'][] = "  ✅ Hoje está nos dias agendados";

            // Verificar se é o horário correto (com tolerância de 5 minutos)
            $templateTime = new DateTime($scheduledTime);
            $currentDateTime = new DateTime($currentTime);
            $timeDiff = abs($templateTime->getTimestamp() - $currentDateTime->getTimestamp());

            $report['debug'][] = "  Diferença de tempo: " . round($timeDiff / 60, 2) . " minutos";

            if ($timeDiff > 300) { // 5 minutos de tolerância
                $report['debug'][] = "  ❌ Fora do horário (tolerância: 5 minutos)";
                continue;
            }
            
            $report['debug'][] = "  ✅ Dentro do horário";

            // Verificar se já foi enviado hoje
            $alreadySent = Database::fetch(
                "SELECT id FROM whatsapp_messages 
                 WHERE template_id = ? 
                 AND DATE(sent_at) = CURDATE()",
                [$template['id']]
            );

            if ($alreadySent) {
                $report['debug'][] = "  ❌ Já foi enviado hoje (ID: {$alreadySent['id']})";
                continue;
            }
            
            $report['debug'][] = "  ✅ Ainda não foi enviado hoje";

            // Buscar clientes que atendem aos critérios do template
            $clients = getClientsForTemplate($template, $resellerId);
            
            $report['debug'][] = "  Clientes encontrados: " . count($clients);
            
            if (empty($clients)) {
                $report['debug'][] = "  ⚠️ Nenhum cliente atende aos critérios deste template";
                continue;
            }

            foreach ($clients as $client) {
                $report['debug'][] = "  Processando cliente: {$client['name']} (ID: {$client['id']})";
                $variables = prepareTemplateVariables($template, $client);
                
                $result = sendTemplateMessage(
                    $resellerId, 
                    $client['phone'], 
                    $template['type'], 
                    $variables, 
                    $client['id']
                );

                if ($result['success']) {
                    $report['messages_sent']++;
                    $report['templates_processed'][] = [
                        'template_id' => $template['id'],
                        'client_id' => $client['id'],
                        'status' => 'sent'
                    ];
                } else {
                    $report['errors'][] = [
                        'template_id' => $template['id'],
                        'client_id' => $client['id'],
                        'error' => $result['error']
                    ];
                }
            }
        }

    } catch (Exception $e) {
        $report['errors'][] = ['global' => $e->getMessage()];
        error_log("Erro em runScheduledTemplates: " . $e->getMessage());
    }

    return $report;
}

/**
 * Buscar clientes que atendem aos critérios de um template
 * @param array $template
 * @param string $resellerId
 * @return array
 */
function getClientsForTemplate($template, $resellerId) {
    $clients = [];

    switch ($template['type']) {
        case 'expires_7d':
            $sql = "SELECT id, name, phone, renewal_date, value, plan, server, reseller_id,
                    DATEDIFF(renewal_date, CURDATE()) as days_diff
                    FROM clients 
                    WHERE reseller_id = ? 
                    AND status = 'active' 
                    AND phone IS NOT NULL 
                    AND phone != ''
                    AND DATEDIFF(renewal_date, CURDATE()) = 7";
            error_log("SQL expires_7d: $sql | resellerId: $resellerId");
            $clients = Database::fetchAll($sql, [$resellerId]);
            error_log("Clientes encontrados (expires_7d): " . count($clients));
            break;

        case 'expires_3d':
            $sql = "SELECT id, name, phone, renewal_date, value, plan, server, reseller_id,
                    DATEDIFF(renewal_date, CURDATE()) as days_diff
                    FROM clients 
                    WHERE reseller_id = ? 
                    AND status = 'active' 
                    AND phone IS NOT NULL 
                    AND phone != ''
                    AND DATEDIFF(renewal_date, CURDATE()) = 3";
            error_log("SQL expires_3d: $sql | resellerId: $resellerId");
            $clients = Database::fetchAll($sql, [$resellerId]);
            error_log("Clientes encontrados (expires_3d): " . count($clients));
            
            // Debug adicional: buscar TODOS os clientes ativos
            $allClients = Database::fetchAll(
                "SELECT id, name, renewal_date, status, phone,
                 DATEDIFF(renewal_date, CURDATE()) as days_diff
                 FROM clients 
                 WHERE reseller_id = ?",
                [$resellerId]
            );
            error_log("Total de clientes no sistema: " . count($allClients));
            foreach ($allClients as $c) {
                error_log("  - {$c['name']}: vence em {$c['days_diff']} dias (status: {$c['status']}, phone: " . ($c['phone'] ?: 'NULL') . ")");
            }
            break;

        case 'expired_1d':
            $clients = Database::fetchAll(
                "SELECT id, name, phone, renewal_date, value, plan, server, reseller_id
                 FROM clients 
                 WHERE reseller_id = ? 
                 AND status = 'active' 
                 AND phone IS NOT NULL 
                 AND phone != ''
                 AND DATEDIFF(CURDATE(), renewal_date) = 1",
                [$resellerId]
            );
            break;

        case 'expired_3d':
            $clients = Database::fetchAll(
                "SELECT id, name, phone, renewal_date, value, plan, server, reseller_id
                 FROM clients 
                 WHERE reseller_id = ? 
                 AND status = 'active' 
                 AND phone IS NOT NULL 
                 AND phone != ''
                 AND DATEDIFF(CURDATE(), renewal_date) = 3",
                [$resellerId]
            );
            break;

        case 'welcome':
            // Para boas-vindas, buscar clientes criados hoje
            $clients = Database::fetchAll(
                "SELECT id, name, phone, renewal_date, value, plan, server, reseller_id
                 FROM clients 
                 WHERE reseller_id = ? 
                 AND status = 'active' 
                 AND phone IS NOT NULL 
                 AND phone != ''
                 AND DATE(created_at) = CURDATE()",
                [$resellerId]
            );
            break;

        // Adicionar outros tipos conforme necessário
    }

    return $clients;
}

/**
 * Preparar variáveis para um template
 * @param array $template
 * @param array $client
 * @return array
 */
function prepareTemplateVariables($template, $client) {
    $variables = [
        'cliente_nome' => $client['name'],
        'cliente_vencimento' => date('d/m/Y', strtotime($client['renewal_date'])),
        'cliente_valor' => number_format($client['value'], 2, ',', '.'),
        'cliente_plano' => $client['plan'],
        'cliente_servidor' => $client['server'] ?? 'N/A'
    ];

    // Adicionar variáveis específicas do template se necessário
    if ($template['variables']) {
        $templateVars = json_decode($template['variables'], true);
        if (is_array($templateVars)) {
            $variables = array_merge($variables, $templateVars);
        }
    }

    return $variables;
}

/**
 * Executar automação de lembretes de vencimento
 * @return array Relatório da execução
 */
function runWhatsAppReminderAutomation() {
    try {
        $report = [
            'execution_time' => date('Y-m-d H:i:s'),
            'reminders_sent' => 0,
            'clients_processed' => [],
            'errors' => []
        ];

        // Buscar clientes ativos com vencimento próximo
        $clients = Database::fetchAll(
            "SELECT 
                c.id, c.name, c.phone, c.value, c.renewal_date, c.plan, c.server, c.reseller_id,
                DATEDIFF(c.renewal_date, CURDATE()) as days_until_renewal
             FROM clients c
             WHERE c.status = 'active' 
             AND c.phone IS NOT NULL 
             AND c.phone != ''
             AND DATEDIFF(c.renewal_date, CURDATE()) IN (3, 7, -1, -3)
             ORDER BY c.renewal_date ASC"
        );

        foreach ($clients as $client) {
            $daysUntilRenewal = $client['days_until_renewal'];
            $templateType = null;
            
            // Determinar tipo de template baseado nos dias
            if ($daysUntilRenewal == 7) {
                $templateType = 'expires_7d';
            } elseif ($daysUntilRenewal == 3) {
                $templateType = 'expires_3d';
            } elseif ($daysUntilRenewal == -1) {
                $templateType = 'expired_1d';
            } elseif ($daysUntilRenewal == -3) {
                $templateType = 'expired_3d';
            }
            
            if ($templateType) {
                // Verificar se já foi enviado hoje
                $alreadySent = Database::fetch(
                    "SELECT id FROM whatsapp_messages 
                     WHERE client_id = ? 
                     AND template_id IN (
                         SELECT id FROM whatsapp_templates 
                         WHERE type = ? AND reseller_id = ?
                     )
                     AND DATE(created_at) = CURDATE()",
                    [$client['id'], $templateType, $client['reseller_id']]
                );
                
                if (!$alreadySent) {
                    // Preparar variáveis do template
                    $variables = [
                        'cliente_nome' => $client['name'],
                        'cliente_plano' => $client['plan'] ?: 'Personalizado',
                        'cliente_vencimento' => date('d/m/Y', strtotime($client['renewal_date'])),
                        'cliente_valor' => 'R$ ' . number_format($client['value'], 2, ',', '.'),
                        'cliente_servidor' => $client['server'] ?: 'Principal'
                    ];
                    
                    // Enviar mensagem
                    $result = sendTemplateMessage(
                        $client['reseller_id'], 
                        $client['phone'], 
                        $templateType, 
                        $variables, 
                        $client['id']
                    );
                    
                    if ($result['success']) {
                        $report['reminders_sent']++;
                        $report['clients_processed'][] = [
                            'client_id' => $client['id'],
                            'client_name' => $client['name'],
                            'template_type' => $templateType,
                            'days_until_renewal' => $daysUntilRenewal,
                            'message_id' => $result['message_id']
                        ];
                    } else {
                        $report['errors'][] = [
                            'client_id' => $client['id'],
                            'client_name' => $client['name'],
                            'template_type' => $templateType,
                            'error' => $result['error']
                        ];
                    }
                }
            }
        }

        return $report;

    } catch (Exception $e) {
        error_log("Erro na automação de lembretes WhatsApp: " . $e->getMessage());
        return [
            'execution_time' => date('Y-m-d H:i:s'),
            'reminders_sent' => 0,
            'clients_processed' => [],
            'errors' => [['error' => $e->getMessage()]]
        ];
    }
}

/**
 * Enviar mensagem de fatura gerada automaticamente
 * @param string $invoiceId ID da fatura
 * @return array Resultado do envio
 */
function sendAutomaticInvoiceMessage($invoiceId) {
    try {
        // Buscar dados da fatura e cliente
        $invoice = Database::fetch(
            "SELECT i.*, c.name as client_name, c.phone as client_phone, c.plan as client_plan, c.reseller_id
             FROM invoices i 
             JOIN clients c ON i.client_id = c.id 
             WHERE i.id = ?",
            [$invoiceId]
        );
        
        if (!$invoice || !$invoice['client_phone']) {
            return ['success' => false, 'error' => 'Fatura não encontrada ou cliente sem telefone'];
        }
        
        // Verificar se deve enviar mensagem de fatura
        $settings = Database::fetch(
            "SELECT auto_send_invoice FROM whatsapp_settings WHERE reseller_id = ?",
            [$invoice['reseller_id']]
        );
        
        if (!$settings || !$settings['auto_send_invoice']) {
            return ['success' => false, 'error' => 'Envio automático de fatura desabilitado'];
        }
        
        // Preparar variáveis do template
        $variables = [
            'cliente_nome' => $invoice['client_name'],
            'fatura_valor' => 'R$ ' . number_format($invoice['final_value'], 2, ',', '.'),
            'fatura_vencimento' => date('d/m/Y', strtotime($invoice['due_date'])),
            'fatura_periodo' => date('m/Y', strtotime($invoice['issue_date'])),
            'cliente_plano' => $invoice['client_plan'] ?: 'Personalizado'
        ];
        
        return sendTemplateMessage(
            $invoice['reseller_id'], 
            $invoice['client_phone'], 
            'invoice_generated', 
            $variables, 
            $invoice['client_id'], 
            $invoiceId
        );
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Enviar mensagem de renovação confirmada automaticamente
 * @param string $clientId ID do cliente
 * @param string $invoiceId ID da fatura paga
 * @return array Resultado do envio
 */
function sendAutomaticRenewalMessage($clientId, $invoiceId) {
    try {
        // Buscar dados do cliente e fatura
        $data = Database::fetch(
            "SELECT c.*, i.final_value, i.paid_at
             FROM clients c 
             LEFT JOIN invoices i ON i.id = ? 
             WHERE c.id = ?",
            [$invoiceId, $clientId]
        );
        
        if (!$data || !$data['phone']) {
            return ['success' => false, 'error' => 'Cliente não encontrado ou sem telefone'];
        }
        
        // Verificar se deve enviar mensagem de renovação
        $settings = Database::fetch(
            "SELECT auto_send_renewal FROM whatsapp_settings WHERE reseller_id = ?",
            [$data['reseller_id']]
        );
        
        if (!$settings || !$settings['auto_send_renewal']) {
            return ['success' => false, 'error' => 'Envio automático de renovação desabilitado'];
        }
        
        // Preparar variáveis do template
        $variables = [
            'cliente_nome' => $data['name'],
            'cliente_vencimento' => date('d/m/Y', strtotime($data['renewal_date'])),
            'fatura_valor' => 'R$ ' . number_format($data['final_value'] ?: $data['value'], 2, ',', '.'),
            'cliente_plano' => $data['plan'] ?: 'Personalizado',
            'cliente_servidor' => $data['server'] ?: 'Principal'
        ];
        
        return sendTemplateMessage(
            $data['reseller_id'], 
            $data['phone'], 
            'renewed', 
            $variables, 
            $clientId, 
            $invoiceId
        );
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Verificar e enviar lembretes de vencimento
 * @param string $clientId ID do cliente
 * @return array Resultado da verificação
 */
function checkAndSendReminder($clientId) {
    try {
        // Buscar dados do cliente
        $client = Database::fetch(
            "SELECT id, name, phone, value, renewal_date, plan, server, reseller_id
             FROM clients 
             WHERE id = ? AND status = 'active' AND phone IS NOT NULL AND phone != ''",
            [$clientId]
        );
        
        if (!$client) {
            return ['success' => false, 'error' => 'Cliente não encontrado ou sem telefone'];
        }
        
        $daysUntilRenewal = (int)Database::fetch(
            "SELECT DATEDIFF(renewal_date, CURDATE()) as days FROM clients WHERE id = ?",
            [$clientId]
        )['days'];
        
        $templateType = null;
        
        // Determinar tipo de template baseado nos dias
        if ($daysUntilRenewal == 7) {
            $templateType = 'expires_7d';
        } elseif ($daysUntilRenewal == 3) {
            $templateType = 'expires_3d';
        } elseif ($daysUntilRenewal == -1) {
            $templateType = 'expired_1d';
        } elseif ($daysUntilRenewal == -3) {
            $templateType = 'expired_3d';
        }
        
        if (!$templateType) {
            return ['success' => false, 'error' => 'Cliente não está em período de lembrete'];
        }
        
        // Verificar se já foi enviado hoje
        $alreadySent = Database::fetch(
            "SELECT id FROM whatsapp_messages 
             WHERE client_id = ? 
             AND template_id IN (
                 SELECT id FROM whatsapp_templates 
                 WHERE type = ? AND reseller_id = ?
             )
             AND DATE(created_at) = CURDATE()",
            [$clientId, $templateType, $client['reseller_id']]
        );
        
        if ($alreadySent) {
            return ['success' => false, 'error' => 'Lembrete já enviado hoje'];
        }
        
        // Preparar variáveis do template
        $variables = [
            'cliente_nome' => $client['name'],
            'cliente_plano' => $client['plan'] ?: 'Personalizado',
            'cliente_vencimento' => date('d/m/Y', strtotime($client['renewal_date'])),
            'cliente_valor' => 'R$ ' . number_format($client['value'], 2, ',', '.'),
            'cliente_servidor' => $client['server'] ?: 'Principal'
        ];
        
        // Enviar mensagem
        return sendTemplateMessage(
            $client['reseller_id'], 
            $client['phone'], 
            $templateType, 
            $variables, 
            $clientId
        );
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
