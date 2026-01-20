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
        // Sandbox usa o mesmo domínio, mas com credenciais diferentes
        $this->baseUrl = $this->sandbox ? 
            'https://sandbox.asaas.com/api/v3' : 
            'https://www.asaas.com/api/v3';
    }
    
    /**
     * Carregar configurações do banco de dados
     */
    private function loadConfig() {
        try {
            // Obter reseller_id da sessão ou contexto
            $resellerId = $this->getResellerId();
            
            if (!$resellerId) {
                error_log("Asaas: reseller_id não encontrado");
                $this->enabled = false;
                return;
            }
            
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT config_value, enabled 
                FROM payment_methods 
                WHERE method_name = 'asaas' AND reseller_id = ?
            ");
            $stmt->execute([$resellerId]);
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
     * Obter reseller_id do contexto atual
     */
    private function getResellerId() {
        // Tentar obter da sessão
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        
        // Tentar obter do Auth
        require_once __DIR__ . '/../core/Auth.php';
        $user = Auth::user();
        if ($user && isset($user['id'])) {
            return $user['id'];
        }
        
        return null;
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
        
        // Log para debug
        error_log("Asaas Request - URL: {$url}");
        error_log("Asaas Request - Method: {$method}");
        error_log("Asaas Request - API Key: " . substr($this->apiKey, 0, 30) . "...");
        error_log("Asaas Request - Environment: " . ($this->sandbox ? 'Sandbox' : 'Production'));
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Asaas usa o header "access_token" (sem Bearer)
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'access_token: ' . trim($this->apiKey),
            'Content-Type: application/json',
            'User-Agent: UltraGestor/1.0'
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
        
        // Log da resposta
        error_log("Asaas Response - HTTP Code: {$httpCode}");
        error_log("Asaas Response - Body: " . substr($response, 0, 500));
        
        if ($error) {
            error_log("Asaas cURL Error: {$error}");
            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $error
            ];
        }
        
        $result = json_decode($response, true);
        
        // Se houver erro de autenticação, logar detalhes
        if ($httpCode === 401 || $httpCode === 403) {
            error_log("Asaas Auth Error - Details: " . json_encode($result));
        }
        
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
            // Preparar dados do cliente (CPF/CNPJ é opcional)
            $customerData = [
                'name' => $data['customer_name']
            ];
            
            // Adicionar CPF/CNPJ apenas se fornecido e válido
            if (!empty($data['customer_doc']) && $data['customer_doc'] !== '00000000000') {
                $customerData['cpfCnpj'] = preg_replace('/[^0-9]/', '', $data['customer_doc']);
            }
            
            // Adicionar email se fornecido
            if (!empty($data['customer_email'])) {
                $customerData['email'] = $data['customer_email'];
            }
            
            // Adicionar telefone se fornecido
            if (!empty($data['customer_phone'])) {
                $customerData['phone'] = $data['customer_phone'];
                $customerData['mobilePhone'] = $data['customer_phone'];
            }
            
            $customerResult = $this->makeRequest('/customers', 'POST', $customerData);
            
            if (!$customerResult['success']) {
                // Se falhar, pode ser que o cliente já existe
                // Tentar buscar por CPF/CNPJ ou email
                if (!empty($data['customer_doc']) && $data['customer_doc'] !== '00000000000') {
                    $searchResult = $this->makeRequest('/customers?cpfCnpj=' . preg_replace('/[^0-9]/', '', $data['customer_doc']));
                    if ($searchResult['success'] && !empty($searchResult['data']['data'])) {
                        $customerId = $searchResult['data']['data'][0]['id'];
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Erro ao criar/buscar cliente: ' . ($customerResult['data']['errors'][0]['description'] ?? 'Erro desconhecido')
                        ];
                    }
                } elseif (!empty($data['customer_email'])) {
                    // Tentar buscar por email
                    $searchResult = $this->makeRequest('/customers?email=' . urlencode($data['customer_email']));
                    if ($searchResult['success'] && !empty($searchResult['data']['data'])) {
                        $customerId = $searchResult['data']['data'][0]['id'];
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Erro ao criar cliente: ' . ($customerResult['data']['errors'][0]['description'] ?? 'Erro desconhecido')
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
            
            // IMPORTANTE: Asaas exige CPF/CNPJ para PIX
            // Se não foi fornecido, precisamos atualizar o cliente com um CPF genérico
            if (empty($customerData['cpfCnpj'])) {
                // Usar CPF genérico válido para testes (não é de ninguém real)
                $genericCpf = '00000000191'; // CPF válido mas genérico
                
                $updateResult = $this->makeRequest("/customers/{$customerId}", 'POST', [
                    'cpfCnpj' => $genericCpf
                ]);
                
                if (!$updateResult['success']) {
                    error_log("Aviso: Não foi possível adicionar CPF genérico ao cliente");
                }
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
            // Validar formato da API Key
            $apiKeyValidation = $this->validateApiKey();
            if (!$apiKeyValidation['valid']) {
                return [
                    'success' => false,
                    'error' => $apiKeyValidation['message']
                ];
            }
            
            $result = $this->makeRequest('/customers?limit=1');
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Conexão estabelecida com sucesso',
                    'environment' => $this->sandbox ? 'Sandbox (Homologação)' : 'Produção'
                ];
            } else {
                $errorMsg = 'Erro desconhecido';
                if (isset($result['data']['errors'][0])) {
                    $error = $result['data']['errors'][0];
                    $errorMsg = $error['description'] ?? $error['code'] ?? 'Erro desconhecido';
                }
                
                // Mensagem mais clara para erro de autenticação
                if ($result['http_code'] === 401) {
                    $errorMsg = "API Key inválida. Verifique:\n\n";
                    $errorMsg .= "1. Se você está usando a API Key correta do ambiente " . ($this->sandbox ? 'SANDBOX' : 'PRODUÇÃO') . "\n";
                    $errorMsg .= "2. A API Key deve começar com '\$aact_' (produção) ou ser a chave de homologação\n";
                    $errorMsg .= "3. Acesse: " . ($this->sandbox ? 'https://sandbox.asaas.com/customerConfigIntegrations/index' : 'https://www.asaas.com/config/api') . "\n\n";
                    $errorMsg .= "Erro original: " . ($result['data']['errors'][0]['description'] ?? 'Chave inválida');
                }
                
                return [
                    'success' => false,
                    'error' => $errorMsg
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao testar conexão: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar formato da API Key
     */
    private function validateApiKey() {
        if (empty($this->apiKey)) {
            return [
                'valid' => false,
                'message' => 'API Key não fornecida'
            ];
        }
        
        // Remover espaços em branco
        $this->apiKey = trim($this->apiKey);
        
        // API Key do Asaas geralmente tem formato específico
        // Produção: $aact_YTU5YTE0M2M2N2I4MTliNzk0YTI5N2U5MzdjNWZmNDQ6OjAwMDAwMDAwMDAwMDAwMDAwMDA6OiRhYWNoXzRlNTU=
        // Sandbox: pode ser UUID simples ou formato similar
        
        // Verificar se tem tamanho mínimo razoável
        if (strlen($this->apiKey) < 20) {
            return [
                'valid' => false,
                'message' => 'API Key muito curta. Verifique se copiou a chave completa.'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'API Key válida'
        ];
    }
}
