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
        
        // Validar dados obrigatórios (apenas amount é realmente obrigatório)
        if (empty($data['amount'])) {
            return [
                'success' => false,
                'error' => "Campo obrigatório ausente: amount"
            ];
        }
        
        try {
            // Log do ambiente
            error_log("EFI Bank - Modo: " . ($this->sandbox ? 'SANDBOX (Homologação)' : 'PRODUÇÃO'));
            error_log("EFI Bank - Client ID: " . substr($this->clientId, 0, 20) . '...');
            error_log("EFI Bank - Certificado: " . ($this->certificate ?: 'não configurado'));
            
            // Obter token de acesso
            $accessToken = $this->getAccessToken();
            error_log("EFI Bank - Token obtido com sucesso");
            
            // URL da API
            $url = $this->sandbox ? 
                'https://api-pix-h.gerencianet.com.br/v2/cob' : 
                'https://api-pix.gerencianet.com.br/v2/cob';
            
            error_log("EFI Bank - URL da API: " . $url);
            
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
            if (!empty($data['payer_doc_number']) && !empty($data['payer_name'])) {
                $cpf = preg_replace('/[^0-9]/', '', $data['payer_doc_number']);
                
                // Log para debug
                error_log("EFI Bank - CPF recebido: " . $data['payer_doc_number']);
                error_log("EFI Bank - CPF limpo: " . $cpf);
                error_log("EFI Bank - Tamanho CPF: " . strlen($cpf));
                
                // Validar CPF (11 dígitos e algoritmo válido)
                if (strlen($cpf) === 11 && $this->validarCPF($cpf)) {
                    $payload['devedor'] = [
                        'nome' => $data['payer_name'],
                        'cpf' => $cpf
                    ];
                    error_log("EFI Bank - Devedor adicionado ao payload");
                } else {
                    error_log("EFI Bank - CPF inválido, devedor não será adicionado (cobrança será criada sem devedor)");
                }
            } else {
                error_log("EFI Bank - Sem CPF ou nome, devedor não será adicionado");
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
            
            // Log detalhado da resposta
            error_log("EFI Bank Response - HTTP Code: {$httpCode}");
            error_log("EFI Bank Response - Body: " . $response);
            error_log("EFI Bank Request - Payload: " . json_encode($payload));
            
            if ($error) {
                error_log("EFI Bank cURL Error: " . $error);
                return [
                    'success' => false,
                    'error' => 'Erro de conexão: ' . $error
                ];
            }
            
            $result = json_decode($response, true);
            
            if ($httpCode !== 201 && $httpCode !== 200) {
                $errorMsg = 'Erro desconhecido';
                if (isset($result['mensagem'])) {
                    $errorMsg = $result['mensagem'];
                } elseif (isset($result['error_description'])) {
                    $errorMsg = $result['error_description'];
                } elseif (isset($result['message'])) {
                    $errorMsg = $result['message'];
                } elseif (isset($result['violations'])) {
                    $violations = array_map(function($v) {
                        return ($v['property'] ?? 'campo') . ': ' . ($v['reason'] ?? $v['message'] ?? 'erro');
                    }, $result['violations']);
                    $errorMsg = implode(', ', $violations);
                }
                
                error_log("EFI Bank Error Details: " . json_encode($result));
                
                return [
                    'success' => false,
                    'error' => 'Erro ao criar cobrança: ' . $errorMsg,
                    'details' => $result,
                    'http_code' => $httpCode
                ];
            }
            
            $txid = $result['txid'];
            
            // A resposta da criação já inclui o pixCopiaECola (QR Code texto)
            // Mas não inclui a imagem base64, então precisamos buscar
            if (isset($result['pixCopiaECola'])) {
                error_log("EFI Bank - QR Code (copia e cola) já veio na resposta da cobrança");
                
                // Buscar apenas a imagem do QR Code
                $locationId = $result['loc']['id'] ?? null;
                
                if ($locationId) {
                    $qrImageData = $this->getQRCodeImage($locationId, $accessToken);
                    
                    return [
                        'success' => true,
                        'payment_id' => $txid,
                        'status' => $result['status'],
                        'qr_code' => $result['pixCopiaECola'],
                        'qr_code_base64' => $qrImageData['qr_code_base64'] ?? '',
                        'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 hour'))
                    ];
                }
            }
            
            // Fallback: buscar QR Code completo se não veio na resposta
            error_log("EFI Bank - QR Code não veio na resposta, buscando separadamente");
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
     * Obter apenas a imagem do QR Code (base64)
     */
    private function getQRCodeImage($locationId, $accessToken) {
        try {
            $qrUrl = $this->sandbox ? 
                "https://api-pix-h.gerencianet.com.br/v2/loc/{$locationId}/qrcode" : 
                "https://api-pix.gerencianet.com.br/v2/loc/{$locationId}/qrcode";
            
            error_log("EFI Bank - Buscando imagem QR Code: " . $qrUrl);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $qrUrl);
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
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                error_log("EFI Bank - Erro ao buscar imagem QR Code: " . $error);
                return ['qr_code_base64' => ''];
            }
            
            if ($httpCode !== 200) {
                error_log("EFI Bank - Erro HTTP ao buscar imagem QR Code: " . $httpCode);
                return ['qr_code_base64' => ''];
            }
            
            $result = json_decode($response, true);
            
            return [
                'qr_code_base64' => $result['imagemQrcode'] ?? ''
            ];
            
        } catch (Exception $e) {
            error_log("EFI Bank - Exception ao buscar imagem QR Code: " . $e->getMessage());
            return ['qr_code_base64' => ''];
        }
    }
    
    /**
     * Obter QR Code de uma cobrança
     */
    private function getQRCode($txid, $accessToken) {
        try {
            // A API EFI usa o campo 'loc' da resposta da cobrança, não o txid diretamente
            // Precisamos buscar a cobrança primeiro para pegar o location ID
            $cobUrl = $this->sandbox ? 
                "https://api-pix-h.gerencianet.com.br/v2/cob/{$txid}" : 
                "https://api-pix.gerencianet.com.br/v2/cob/{$txid}";
            
            error_log("EFI Bank - Buscando cobrança para obter location: " . $cobUrl);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $cobUrl);
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
            $error = curl_error($ch);
            curl_close($ch);
            
            error_log("EFI Bank - Resposta cobrança HTTP: " . $httpCode);
            error_log("EFI Bank - Resposta cobrança Body: " . $response);
            
            if ($error) {
                error_log("EFI Bank - Erro cURL ao buscar cobrança: " . $error);
                return [
                    'success' => false,
                    'error' => 'Erro de conexão ao buscar cobrança: ' . $error
                ];
            }
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'error' => 'Erro ao buscar cobrança (HTTP ' . $httpCode . ')'
                ];
            }
            
            $cobResult = json_decode($response, true);
            
            // Extrair location ID
            $locationId = $cobResult['loc']['id'] ?? null;
            
            if (!$locationId) {
                error_log("EFI Bank - Location ID não encontrado na resposta");
                return [
                    'success' => false,
                    'error' => 'Location ID não encontrado na cobrança'
                ];
            }
            
            error_log("EFI Bank - Location ID: " . $locationId);
            
            // Agora buscar o QR Code usando o location ID
            $qrUrl = $this->sandbox ? 
                "https://api-pix-h.gerencianet.com.br/v2/loc/{$locationId}/qrcode" : 
                "https://api-pix.gerencianet.com.br/v2/loc/{$locationId}/qrcode";
            
            error_log("EFI Bank - Buscando QR Code: " . $qrUrl);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $qrUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            
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
            $error = curl_error($ch);
            curl_close($ch);
            
            error_log("EFI Bank - Resposta QR Code HTTP: " . $httpCode);
            error_log("EFI Bank - Resposta QR Code Body: " . substr($response, 0, 200));
            
            if ($error) {
                error_log("EFI Bank - Erro cURL ao buscar QR Code: " . $error);
                return [
                    'success' => false,
                    'error' => 'Erro de conexão ao buscar QR Code: ' . $error
                ];
            }
            
            if ($httpCode !== 200) {
                $result = json_decode($response, true);
                $errorMsg = $result['mensagem'] ?? 'HTTP ' . $httpCode;
                error_log("EFI Bank - Erro ao obter QR Code: " . $errorMsg);
                return [
                    'success' => false,
                    'error' => 'Erro ao obter QR Code: ' . $errorMsg
                ];
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['qrcode']) || !isset($result['imagemQrcode'])) {
                error_log("EFI Bank - QR Code não encontrado na resposta");
                return [
                    'success' => false,
                    'error' => 'QR Code não encontrado na resposta da API'
                ];
            }
            
            error_log("EFI Bank - QR Code obtido com sucesso");
            
            return [
                'success' => true,
                'qr_code' => $result['qrcode'],
                'qr_code_base64' => $result['imagemQrcode']
            ];
            
        } catch (Exception $e) {
            error_log("EFI Bank - Exception ao obter QR Code: " . $e->getMessage());
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
