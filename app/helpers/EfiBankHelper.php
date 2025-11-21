<?php
/**
 * Helper para integração com EFI Bank (Gerencianet)
 * Facilita a criação de pagamentos PIX e gerenciamento de transações
 */

class EfiBankHelper {
    
    private $clientId;
    private $clientSecret;
    private $certificate;
    private $enabled;
    private $sandbox;
    private $useMock;
    
    /**
     * Construtor - carrega configurações
     */
    public function __construct($clientId = null, $clientSecret = null, $certificate = null, $sandbox = false) {
        if ($clientId && $clientSecret) {
            // Usar credenciais fornecidas diretamente
            $this->clientId = $clientId;
            $this->clientSecret = $clientSecret;
            $this->certificate = $certificate;
            $this->sandbox = $sandbox;
            $this->enabled = true;
        } else {
            // Carregar do banco (método antigo para compatibilidade)
            $this->loadConfig();
        }
        
        // Usar mock em desenvolvimento ou se configurado
        $this->useMock = env('EFI_USE_MOCK', 'false') === 'true' || 
                         (env('APP_ENV') === 'development' && empty($this->certificate));
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
                WHERE method_name = 'efibank'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $config = json_decode($result['config_value'], true);
                $this->clientId = $config['client_id'] ?? '';
                $this->clientSecret = $config['client_secret'] ?? '';
                $this->certificate = $config['certificate'] ?? '';
                $this->sandbox = $config['sandbox'] ?? false;
                $this->enabled = (bool)$result['enabled'];
            } else {
                $this->enabled = false;
            }
        } catch (Exception $e) {
            error_log("Erro ao carregar config EFI Bank: " . $e->getMessage());
            $this->enabled = false;
        }
    }
    
    /**
     * Verificar se está configurado e ativo
     */
    public function isEnabled() {
        return $this->enabled && !empty($this->clientId) && !empty($this->clientSecret);
    }
    
    /**
     * Obter Client ID
     */
    public function getClientId() {
        return $this->clientId;
    }
    
    /**
     * Obter token de acesso OAuth
     */
    private function getAccessToken() {
        $url = $this->sandbox ? 
            'https://api-pix-h.gerencianet.com.br/oauth/token' : 
            'https://api-pix.gerencianet.com.br/oauth/token';
        
        $auth = base64_encode($this->clientId . ':' . $this->clientSecret);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['grant_type' => 'client_credentials']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        
        // Configurações SSL
        $isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                       strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
        
        if ($isLocalhost || env('APP_ENV') === 'development') {
            // Em desenvolvimento, desabilitar verificação SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        } else {
            // Em produção, usar certificado se fornecido
            if (!empty($this->certificate) && file_exists($this->certificate)) {
                curl_setopt($ch, CURLOPT_SSLCERT, $this->certificate);
            }
        }
        
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro de conexão: ' . $error);
        }
        
        if ($httpCode !== 200) {
            $result = json_decode($response, true);
            $errorMsg = $result['error_description'] ?? $result['mensagem'] ?? 'HTTP ' . $httpCode;
            throw new Exception('Erro ao obter token: ' . $errorMsg);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['access_token'])) {
            throw new Exception('Token não retornado pela API');
        }
        
        return $result['access_token'];
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
                'error' => 'EFI Bank não está configurado ou ativo'
            ];
        }
        
        // Usar mock se configurado
        if ($this->useMock) {
            require_once __DIR__ . '/EfiBankMockHelper.php';
            $mock = new EfiBankMockHelper();
            return $mock->createPixPayment($data);
        }
        
        // Validar dados obrigatórios
        $required = ['amount', 'payer_name', 'payer_doc_number'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Campo obrigatório ausente: {$field}"
                ];
            }
        }
        
        try {
            // Obter token de acesso
            $accessToken = $this->getAccessToken();
            
            // URL da API
            $url = $this->sandbox ? 
                'https://api-pix-h.gerencianet.com.br/v2/cob' : 
                'https://api-pix.gerencianet.com.br/v2/cob';
            
            // Preparar payload
            $payload = [
                'calendario' => [
                    'expiracao' => 3600 // 1 hora
                ],
                'valor' => [
                    'original' => number_format((float)$data['amount'], 2, '.', '')
                ],
                'chave' => $this->getPixKey(), // Chave PIX cadastrada
                'solicitacaoPagador' => $data['description'] ?? 'Pagamento'
            ];
            
            // Adicionar devedor apenas se tiver CPF válido
            if (!empty($data['payer_doc_number'])) {
                $cpf = preg_replace('/[^0-9]/', '', $data['payer_doc_number']);
                if (strlen($cpf) === 11 && $this->validarCPF($cpf)) {
                    $payload['devedor'] = [
                        'nome' => $data['payer_name'],
                        'cpf' => $cpf
                    ];
                }
            }
            
            // Adicionar informações adicionais se fornecidas
            if (!empty($data['external_reference'])) {
                $payload['infoAdicionais'] = [
                    [
                        'nome' => 'Referência',
                        'valor' => $data['external_reference']
                    ]
                ];
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            
            // Configurações SSL
            $isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                           strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
            
            if ($isLocalhost || env('APP_ENV') === 'development') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            } else {
                if (!empty($this->certificate) && file_exists($this->certificate)) {
                    curl_setopt($ch, CURLOPT_SSLCERT, $this->certificate);
                }
            }
            
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            
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
            
            if ($httpCode !== 201 && $httpCode !== 200) {
                return [
                    'success' => false,
                    'error' => 'Erro ao criar cobrança: ' . ($result['mensagem'] ?? 'Erro desconhecido'),
                    'details' => $result
                ];
            }
            
            // Obter QR Code
            $txid = $result['txid'];
            $qrCodeData = $this->getQRCode($txid, $accessToken);
            
            if (!$qrCodeData['success']) {
                return $qrCodeData;
            }
            
            return [
                'success' => true,
                'payment_id' => $txid,
                'status' => $result['status'],
                'qr_code' => $qrCodeData['qr_code'],
                'qr_code_base64' => $qrCodeData['qr_code_base64'],
                'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao criar pagamento: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter QR Code de uma cobrança
     */
    private function getQRCode($txid, $accessToken) {
        try {
            $url = $this->sandbox ? 
                "https://api-pix-h.gerencianet.com.br/v2/loc/{$txid}/qrcode" : 
                "https://api-pix.gerencianet.com.br/v2/loc/{$txid}/qrcode";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            
            // Configurações SSL
            $isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                           strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
            
            if ($isLocalhost || env('APP_ENV') === 'development') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            } else {
                if (!empty($this->certificate) && file_exists($this->certificate)) {
                    curl_setopt($ch, CURLOPT_SSLCERT, $this->certificate);
                }
            }
            
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'error' => 'Erro ao obter QR Code'
                ];
            }
            
            $result = json_decode($response, true);
            
            return [
                'success' => true,
                'qr_code' => $result['qrcode'],
                'qr_code_base64' => $result['imagemQrcode']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao obter QR Code: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter chave PIX cadastrada
     */
    private function getPixKey() {
        // Buscar chave PIX do banco de dados
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT config_value 
                FROM payment_methods 
                WHERE method_name = 'efibank'
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $config = json_decode($result['config_value'], true);
                return $config['pix_key'] ?? '';
            }
        } catch (Exception $e) {
            error_log("Erro ao obter chave PIX: " . $e->getMessage());
        }
        
        return '';
    }
    
    /**
     * Consultar status de um pagamento
     * 
     * @param string $txid ID da transação
     * @return array Status do pagamento
     */
    public function getPaymentStatus($txid) {
        if (!$this->isEnabled()) {
            return [
                'success' => false,
                'error' => 'EFI Bank não está configurado'
            ];
        }
        
        try {
            $accessToken = $this->getAccessToken();
            
            $url = $this->sandbox ? 
                "https://api-pix-h.gerencianet.com.br/v2/cob/{$txid}" : 
                "https://api-pix.gerencianet.com.br/v2/cob/{$txid}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            
            // Configurações SSL
            $isLocalhost = strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false || 
                           strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
            
            if ($isLocalhost || env('APP_ENV') === 'development') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            } else {
                if (!empty($this->certificate) && file_exists($this->certificate)) {
                    curl_setopt($ch, CURLOPT_SSLCERT, $this->certificate);
                }
            }
            
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'error' => 'Cobrança não encontrada'
                ];
            }
            
            $result = json_decode($response, true);
            
            // Mapear status EFI para padrão
            $status = 'pending';
            if (isset($result['pix']) && count($result['pix']) > 0) {
                $status = 'approved';
            }
            
            return [
                'success' => true,
                'payment_id' => $result['txid'],
                'status' => $status,
                'amount' => $result['valor']['original'] ?? 0,
                'payer_name' => $result['devedor']['nome'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Erro ao consultar pagamento: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Processar webhook do EFI Bank
     * 
     * @param array $data Dados do webhook
     * @return array Informações processadas
     */
    public function processWebhook($data) {
        // Extrair txid do webhook
        $txid = $data['pix'][0]['txid'] ?? null;
        
        if (!$txid) {
            return [
                'success' => false,
                'error' => 'TXID não encontrado no webhook'
            ];
        }
        
        // Consultar status atualizado
        return $this->getPaymentStatus($txid);
    }
    
    /**
     * Validar CPF
     */
    private function validarCPF($cpf) {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        // Valida primeiro dígito verificador
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
}
