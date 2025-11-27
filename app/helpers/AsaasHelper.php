<?php
/**
 * Helper para integração com Asaas
 * Facilita a criação de pagamentos PIX e gerenciamento de transações
 * 
 * Documentação: https://docs.asaas.com/
 */

class AsaasHelper {
    
    private $apiKey;
    private $enabled;
    private $sandbox;
    private $baseUrl;
    
    /**
     * Construtor - carrega configurações do banco
     */
    public function __construct($apiKey = null, $sandbox = false) {
        if ($apiKey) {
            // Usar credenciais fornecidas diretamente
            $this->apiKey = $apiKey;
            $this->sandbox = $sandbox;
            $this->enabled = true;
        } else {
            // Carregar do banco (método antigo para compatibilidade)
            $this->loadConfig();
        }
        
        // Definir URL base conforme ambiente
        $this->baseUrl = $this->sandbox ? 
            'https://sandbox.asaas.com/api/v3' : 
            'https://api.asaas.com/v3';
    }
    
    /**
     * Carregar configurações do banco de dados
     */
    private function loadConfig() {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT config_value, enabled 
                FROM payment_methods 
                WHERE method_name = 'asaas'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $config = json_decode($result['config_value'], true);
                $this->apiKey = $config['api_key'] ?? '';
                $this->sandbox = $config['sandbox'] ?? false;
                $this->enabled = (bool)$result['enabled'];
            } else {
                $this->enabled = false;
            }
        } catch (Exception $e) {
            error_log("Erro ao carregar config Asaas: " . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    /**
     * Verificar se está configurado e ativo
     */
    public function isEnabled() {
        return $this->enabled && !empty($this->apiKey);
    }
    
    /**
     * Obter API Key
     */
    public function getApiKey() {
        return $this->apiKey;
    }
    
    /**
     * Fazer requisição à API do Asaas
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'access_token: ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Desabilitar verificação SSL em desenvolvimento
        if (env('APP_ENV') === 'development') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $error
            ];
        }
        
        $result = json_decode($response, true);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $result
        ];
    }
    
    /**
     * Criar pagamento PIX
     * 
     * @param array $data Dados do pagamento
     * @return array Resultado com QR Code e informações
     */
    public function createPixPayment($data) {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Asaas não está configurado ou ativo'
            ];
        }
        
        // Validar dados obrigatórios
        $required = ['amount', 'description', 'customer_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Campo obrigatório ausente: {$field}"
                ];
            }
        }
        
        try {
            // 1. Criar ou buscar cliente
            $customerData = [
                'name' => $data['customer_name'],
                'cpfCnpj' => $data['customer_doc'] ?? null,
                'email' => $data['customer_email'] ?? null,
                'phone' => $data['customer_phone'] ?? null,
                'mobilePhone' => $data['customer_phone'] ?? null
            ];
            
            // Remover campos vazios
            $customerData = array_filter($customerData, function($value) {
                return !empty($value) && $value !== '00000000000';
            });
            
            $customerResult = $this->makeRequest('/customers', 'POST', $customerData);
            
            if (!$customerResult['success']) {
                // Se falhar, pode ser que o cliente já existe
                // Tentar buscar por CPF/CNPJ ou email
                if (!empty($data['customer_doc'])) {
                    $searchResult = $this->makeRequest('/customers?cpfCnpj=' . $data['customer_doc']);
                    if ($searchResult['success'] && !empty($searchResult['data']['data'])) {
                        $customerId = $searchResult['data']['data'][0]['id'];
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Erro ao criar/buscar cliente: ' . ($customerResult['data']['errors'][0]['description'] ?? 'Erro desconhecido')
                        ];
                    }
                } else {
                    return [
                        'success' => false,
                        'error' => 'Erro ao criar cliente: ' . ($customerResult['data']['errors'][0]['description'] ?? 'Erro desconhecido')
                    ];
                }
            } else {
                $customerId = $customerResult['data']['id'];
            }
            
            // 2. Criar cobrança PIX
            $paymentData = [
                'customer' => $customerId,
                'billingType' => 'PIX',
                'value' => (float)$data['amount'],
                'dueDate' => $data['due_date'] ?? date('Y-m-d'),
                'description' => $data['description']
            ];
            
            // Adicionar campos opcionais
            if (!empty($data['external_reference'])) {
                $paymentData['externalReference'] = $data['external_reference'];
            }
            
            $paymentResult = $this->makeRequest('/payments', 'POST', $paymentData);
            
            if (!$paymentResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Erro ao criar cobrança: ' . ($paymentResult['data']['errors'][0]['description'] ?? 'Erro desconhecido'),
                    'details' => $paymentResult['data']
                ];
            }
            
            $payment = $paymentResult['data'];
            $paymentId = $payment['id'];
            
            // 3. Gerar QR Code PIX
            $qrCodeResult = $this->makeRequest("/payments/{$paymentId}/pixQrCode");
            
            if (!$qrCodeResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Erro ao gerar QR Code PIX',
                    'details' => $qrCodeResult['data']
                ];
            }
            
            $qrCode = $qrCodeResult['data'];
            
            return [
                'success' => true,
                'payment_id' => $paymentId,
                'status' => strtolower($payment['status']),
                'qr_code' => $qrCode['payload'],
                'qr_code_base64' => $qrCode['encodedImage'],
                'invoice_url' => $payment['invoiceUrl'] ?? null,
                'expiration_date' => $payment['dueDate'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao criar pagamento: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Consultar status de um pagamento
     * 
     * @param string $paymentId ID do pagamento
     * @return array Status do pagamento
     */
    public function getPaymentStatus($paymentId) {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'Asaas não está configurado'
            ];
        }
        
        try {
            $result = $this->makeRequest("/payments/{$paymentId}");
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => 'Pagamento não encontrado'
                ];
            }
            
            $payment = $result['data'];
            
            return [
                'success' => true,
                'payment_id' => $payment['id'],
                'status' => strtolower($payment['status']),
                'status_detail' => $payment['status'],
                'amount' => $payment['value'],
                'date_approved' => $payment['confirmedDate'] ?? $payment['paymentDate'] ?? null,
                'external_reference' => $payment['externalReference'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao consultar pagamento: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Processar webhook do Asaas
     * 
     * @param array $data Dados do webhook
     * @return array Informações processadas
     */
    public function processWebhook($data) {
        // Evento de pagamento
        $event = $data['event'] ?? null;
        
        // Eventos suportados
        $supportedEvents = [
            'PAYMENT_RECEIVED',
            'PAYMENT_CONFIRMED',
            'PAYMENT_OVERDUE',
            'PAYMENT_DELETED',
            'PAYMENT_RESTORED',
            'PAYMENT_REFUNDED',
            'PAYMENT_RECEIVED_IN_CASH',
            'PAYMENT_ANTICIPATED'
        ];
        
        if (!in_array($event, $supportedEvents)) {
            return [
                'success' => false,
                'error' => 'Tipo de evento não suportado: ' . $event
            ];
        }
        
        // ID do pagamento
        $paymentId = $data['payment']['id'] ?? null;
        
        if (!$paymentId) {
            return [
                'success' => false,
                'error' => 'ID do pagamento não encontrado'
            ];
        }
        
        // Mapear status do Asaas para nosso sistema
        $statusMap = [
            'PAYMENT_RECEIVED' => 'approved',
            'PAYMENT_CONFIRMED' => 'approved',
            'PAYMENT_OVERDUE' => 'overdue',
            'PAYMENT_DELETED' => 'cancelled',
            'PAYMENT_REFUNDED' => 'refunded',
            'PAYMENT_RECEIVED_IN_CASH' => 'approved',
            'PAYMENT_ANTICIPATED' => 'approved'
        ];
        
        $status = $statusMap[$event] ?? 'pending';
        
        return [
            'success' => true,
            'payment_id' => $paymentId,
            'status' => $status,
            'event' => $event,
            'amount' => $data['payment']['value'] ?? 0,
            'external_reference' => $data['payment']['externalReference'] ?? null
        ];
    }
    
    /**
     * Testar conexão com API
     */
    public function testConnection() {
        try {
            $result = $this->makeRequest('/customers?limit=1');
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Conexão estabelecida com sucesso',
                    'environment' => $this->sandbox ? 'Sandbox' : 'Produção'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Falha na autenticação: ' . ($result['data']['errors'][0]['description'] ?? 'Erro desconhecido')
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao testar conexão: ' . $e->getMessage()
            ];
        }
    }
}
