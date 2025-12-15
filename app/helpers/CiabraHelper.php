<?php
/**
 * Helper para integração com Ciabra
 * Facilita a criação de pagamentos PIX e gerenciamento de transações
 * 
 * Documentação: https://docs.ciabra.com.br/
 */

class CiabraHelper {
    
    private $apiKey;
    private $enabled;
    private $baseUrl;
    
    /**
     * Construtor - carrega configurações do banco
     */
    public function __construct($apiKey = null) {
        if ($apiKey) {
            // Usar credenciais fornecidas diretamente
            $this->apiKey = $apiKey;
            $this->enabled = true;
        } else {
            // Carregar do banco (método antigo para compatibilidade)
            $this->loadConfig();
        }
        
        // URL base da API Ciabra (sempre produção)
        $this->baseUrl = 'https://api.ciabra.com.br/v1';
    }
    
    /**
     * Carregar configurações do banco de dados
     */
    private function loadConfig() {
        try {
            // Obter reseller_id da sessão ou contexto
            $resellerId = $this->getResellerId();
            
            if (!$resellerId) {
                error_log("Ciabra: reseller_id não encontrado");
                $this->enabled = false;
                return;
            }
            
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT config_value, enabled 
                FROM payment_methods 
                WHERE method_name = 'ciabra' AND reseller_id = ?
            ");
            $stmt->execute([$resellerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $config = json_decode($result['config_value'], true);
                $this->apiKey = $config['api_key'] ?? '';
                $this->enabled = (bool)$result['enabled'];
            } else {
                $this->enabled = false;
            }
        } catch (Exception $e) {
            error_log("Erro ao carregar config Ciabra: " . $e->getMessage());
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
     * Fazer requisição à API do Ciabra
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        // Log para debug
        error_log("Ciabra Request - URL: {$url}");
        error_log("Ciabra Request - Method: {$method}");
        error_log("Ciabra Request - API Key: " . substr($this->apiKey, 0, 30) . "...");
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Ciabra usa Bearer token no header Authorization
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . trim($this->apiKey),
            'Content-Type: application/json',
            'Accept: application/json',
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
        error_log("Ciabra Response - HTTP Code: {$httpCode}");
        error_log("Ciabra Response - Body: " . substr($response, 0, 500));
        
        if ($error) {
            error_log("Ciabra cURL Error: {$error}");
            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $error
            ];
        }
        
        $result = json_decode($response, true);
        
        // Se houver erro de autenticação, logar detalhes
        if ($httpCode === 401 || $httpCode === 403) {
            error_log("Ciabra Auth Error - Details: " . json_encode($result));
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
                'error' => 'Ciabra não está configurado ou ativo'
            ];
        }
        
        // Validar dados obrigatórios
        $required = ['amount', 'description'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Campo obrigatório ausente: {$field}"
                ];
            }
        }
        
        try {
            // Preparar payload para Ciabra
            // Ciabra não exige CPF/CNPJ para criar cobrança PIX
            $paymentData = [
                'value' => (float)$data['amount'],
                'description' => $data['description'],
                'payment_method' => 'pix'
            ];
            
            // Adicionar campos opcionais se fornecidos
            if (!empty($data['customer_name'])) {
                $paymentData['customer_name'] = $data['customer_name'];
            }
            
            if (!empty($data['customer_email'])) {
                $paymentData['customer_email'] = $data['customer_email'];
            }
            
            if (!empty($data['customer_phone'])) {
                $paymentData['customer_phone'] = $data['customer_phone'];
            }
            
            if (!empty($data['external_reference'])) {
                $paymentData['external_reference'] = $data['external_reference'];
            }
            
            if (!empty($data['due_date'])) {
                $paymentData['due_date'] = $data['due_date'];
            } else {
                // Padrão: vence em 24 horas
                $paymentData['due_date'] = date('Y-m-d', strtotime('+1 day'));
            }
            
            // Criar cobrança PIX
            $paymentResult = $this->makeRequest('/charges', 'POST', $paymentData);
            
            if (!$paymentResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Erro ao criar cobrança: ' . ($paymentResult['data']['message'] ?? 'Erro desconhecido'),
                    'details' => $paymentResult['data']
                ];
            }
            
            $charge = $paymentResult['data'];
            $chargeId = $charge['id'] ?? $charge['charge_id'];
            
            // Verificar se o QR Code já veio na resposta
            if (isset($charge['pix'])) {
                return [
                    'success' => true,
                    'payment_id' => $chargeId,
                    'status' => strtolower($charge['status'] ?? 'pending'),
                    'qr_code' => $charge['pix']['qr_code'] ?? $charge['pix']['emv'],
                    'qr_code_base64' => $charge['pix']['qr_code_image'] ?? '',
                    'invoice_url' => $charge['invoice_url'] ?? null,
                    'expiration_date' => $charge['due_date'] ?? null
                ];
            }
            
            // Se não veio, buscar separadamente
            $qrCodeResult = $this->makeRequest("/charges/{$chargeId}/pix");
            
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
                'payment_id' => $chargeId,
                'status' => strtolower($charge['status'] ?? 'pending'),
                'qr_code' => $qrCode['qr_code'] ?? $qrCode['emv'],
                'qr_code_base64' => $qrCode['qr_code_image'] ?? '',
                'invoice_url' => $charge['invoice_url'] ?? null,
                'expiration_date' => $charge['due_date'] ?? null
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
                'error' => 'Ciabra não está configurado'
            ];
        }
        
        try {
            $result = $this->makeRequest("/charges/{$paymentId}");
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => 'Pagamento não encontrado'
                ];
            }
            
            $charge = $result['data'];
            
            // Mapear status do Ciabra para padrão
            $statusMap = [
                'paid' => 'approved',
                'pending' => 'pending',
                'expired' => 'expired',
                'cancelled' => 'cancelled',
                'refunded' => 'refunded'
            ];
            
            $status = strtolower($charge['status'] ?? 'pending');
            $mappedStatus = $statusMap[$status] ?? $status;
            
            return [
                'success' => true,
                'payment_id' => $charge['id'] ?? $charge['charge_id'],
                'status' => $mappedStatus,
                'status_detail' => $charge['status'],
                'amount' => $charge['value'] ?? 0,
                'date_approved' => $charge['paid_at'] ?? null,
                'external_reference' => $charge['external_reference'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao consultar pagamento: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Processar webhook do Ciabra
     * 
     * @param array $data Dados do webhook
     * @return array Informações processadas
     */
    public function processWebhook($data) {
        // Evento de pagamento
        $event = $data['event'] ?? $data['type'] ?? null;
        
        // Eventos suportados
        $supportedEvents = [
            'charge.paid',
            'charge.confirmed',
            'charge.expired',
            'charge.cancelled',
            'charge.refunded'
        ];
        
        if (!in_array($event, $supportedEvents)) {
            return [
                'success' => false,
                'error' => 'Tipo de evento não suportado: ' . $event
            ];
        }
        
        // ID do pagamento
        $paymentId = $data['charge']['id'] ?? $data['charge']['charge_id'] ?? $data['data']['id'] ?? null;
        
        if (!$paymentId) {
            return [
                'success' => false,
                'error' => 'ID do pagamento não encontrado'
            ];
        }
        
        // Mapear status do Ciabra para nosso sistema
        $statusMap = [
            'charge.paid' => 'approved',
            'charge.confirmed' => 'approved',
            'charge.expired' => 'expired',
            'charge.cancelled' => 'cancelled',
            'charge.refunded' => 'refunded'
        ];
        
        $status = $statusMap[$event] ?? 'pending';
        
        return [
            'success' => true,
            'payment_id' => $paymentId,
            'status' => $status,
            'event' => $event,
            'amount' => $data['charge']['value'] ?? $data['data']['value'] ?? 0,
            'external_reference' => $data['charge']['external_reference'] ?? $data['data']['external_reference'] ?? null
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
            
            // Testar listando cobranças (endpoint simples)
            $result = $this->makeRequest('/charges?limit=1');
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Conexão estabelecida com sucesso',
                    'environment' => 'Produção'
                ];
            } else {
                $errorMsg = 'Erro desconhecido';
                if (isset($result['data']['message'])) {
                    $errorMsg = $result['data']['message'];
                } elseif (isset($result['data']['error'])) {
                    $errorMsg = $result['data']['error'];
                }
                
                // Mensagem mais clara para erro de autenticação
                if ($result['http_code'] === 401) {
                    $errorMsg = "API Key inválida. Verifique:\n\n";
                    $errorMsg .= "1. Se você está usando a API Key correta do Ciabra\n";
                    $errorMsg .= "2. Acesse o painel Ciabra para obter sua chave\n";
                    $errorMsg .= "3. A API Key deve estar ativa e com permissões corretas\n\n";
                    $errorMsg .= "Erro original: " . ($result['data']['message'] ?? 'Chave inválida');
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
