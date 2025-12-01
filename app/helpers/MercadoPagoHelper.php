<?php
/**
 * Helper para integração com Mercado Pago
 * Facilita a criação de pagamentos PIX e gerenciamento de transações
 */

class MercadoPagoHelper {
    
    private $publicKey;
    private $accessToken;
    private $enabled;
    
    /**
     * Construtor - carrega configurações do banco
     */
    public function __construct($publicKey = null, $accessToken = null) {
        if ($publicKey && $accessToken) {
            // Usar credenciais fornecidas diretamente
            $this->publicKey = $publicKey;
            $this->accessToken = $accessToken;
            $this->enabled = true;
        } else {
            // Carregar do banco (método antigo para compatibilidade)
            $this->loadConfig();
        }
    }
    
    /**
     * Carregar configurações do banco de dados
     */
    private function loadConfig() {
        try {
            // Obter reseller_id da sessão ou contexto
            $resellerId = $this->getResellerId();
            
            if (!$resellerId) {
                error_log("Mercado Pago: reseller_id não encontrado");
                $this->enabled = false;
                return;
            }
            
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT config_value, enabled 
                FROM payment_methods 
                WHERE method_name = 'mercadopago' AND reseller_id = ?
            ");
            $stmt->execute([$resellerId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $config = json_decode($result['config_value'], true);
                $this->publicKey = $config['public_key'] ?? '';
                $this->accessToken = $config['access_token'] ?? '';
                $this->enabled = (bool)$result['enabled'];
            } else {
                $this->enabled = false;
            }
        } catch (Exception $e) {
            error_log("Erro ao carregar config Mercado Pago: " . $e->getMessage());
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
        return $this->enabled && !empty($this->publicKey) && !empty($this->accessToken);
    }
    
    /**
     * Obter Public Key
     */
    public function getPublicKey() {
        return $this->publicKey;
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
                'error' => 'Mercado Pago não está configurado ou ativo'
            ];
        }
        
        // Validar dados obrigatórios
        $required = ['amount', 'description', 'payer_email'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Campo obrigatório ausente: {$field}"
                ];
            }
        }
        
        try {
            $url = 'https://api.mercadopago.com/v1/payments';
            
            // Preparar dados do pagador
            $payerData = [
                'email' => $data['payer_email']
            ];
            
            // Adicionar nome se fornecido
            if (!empty($data['payer_name'])) {
                $payerData['first_name'] = $data['payer_name'];
            }
            
            // Adicionar documento se fornecido e válido
            if (!empty($data['payer_doc_number']) && $data['payer_doc_number'] !== '00000000000') {
                $payerData['identification'] = [
                    'type' => $data['payer_doc_type'] ?? 'CPF',
                    'number' => $data['payer_doc_number']
                ];
            }
            
            $payload = [
                'transaction_amount' => (float)$data['amount'],
                'description' => $data['description'],
                'payment_method_id' => 'pix',
                'payer' => $payerData
            ];
            
            // Adicionar campos opcionais apenas se fornecidos
            if (!empty($data['notification_url'])) {
                // Validar se a URL é válida e não é localhost
                $notificationUrl = $data['notification_url'];
                if (filter_var($notificationUrl, FILTER_VALIDATE_URL) && 
                    strpos($notificationUrl, 'localhost') === false && 
                    strpos($notificationUrl, '127.0.0.1') === false) {
                    $payload['notification_url'] = $notificationUrl;
                }
            }
            
            if (!empty($data['external_reference'])) {
                $payload['external_reference'] = $data['external_reference'];
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json',
                'X-Idempotency-Key: ' . uniqid('pix_', true)
            ]);
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
            
            if ($httpCode !== 201) {
                return [
                    'success' => false,
                    'error' => 'Erro ao criar pagamento: ' . ($result['message'] ?? 'Erro desconhecido'),
                    'details' => $result
                ];
            }
            
            // Extrair informações do PIX
            $pixData = $result['point_of_interaction']['transaction_data'] ?? null;
            
            if (!$pixData) {
                return [
                    'success' => false,
                    'error' => 'Dados do PIX não retornados pela API'
                ];
            }
            
            return [
                'success' => true,
                'payment_id' => $result['id'],
                'status' => $result['status'],
                'qr_code' => $pixData['qr_code'],
                'qr_code_base64' => $pixData['qr_code_base64'],
                'ticket_url' => $pixData['ticket_url'] ?? null,
                'expiration_date' => $result['date_of_expiration'] ?? null
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
                'error' => 'Mercado Pago não está configurado'
            ];
        }
        
        try {
            $url = "https://api.mercadopago.com/v1/payments/{$paymentId}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            // Desabilitar verificação SSL em desenvolvimento
            if (env('APP_ENV') === 'development') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'error' => 'Pagamento não encontrado'
                ];
            }
            
            $result = json_decode($response, true);
            
            return [
                'success' => true,
                'payment_id' => $result['id'],
                'status' => $result['status'],
                'status_detail' => $result['status_detail'],
                'amount' => $result['transaction_amount'],
                'date_approved' => $result['date_approved'] ?? null,
                'external_reference' => $result['external_reference'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao consultar pagamento: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Processar webhook do Mercado Pago
     * 
     * @param array $data Dados do webhook
     * @return array Informações processadas
     */
    public function processWebhook($data) {
        // Tipo de notificação
        $type = $data['type'] ?? null;
        
        if ($type !== 'payment') {
            return [
                'success' => false,
                'error' => 'Tipo de notificação não suportado'
            ];
        }
        
        // ID do pagamento
        $paymentId = $data['data']['id'] ?? null;
        
        if (!$paymentId) {
            return [
                'success' => false,
                'error' => 'ID do pagamento não encontrado'
            ];
        }
        
        // Consultar status atualizado
        return $this->getPaymentStatus($paymentId);
    }
}
