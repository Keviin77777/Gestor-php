<?php
/**
 * Classe para integração com API do Sigma
 * Baseada na collection Postman fornecida
 */

require_once __DIR__ . '/../core/Database.php';

class SigmaAPI {
    private $panelUrl;
    private $token;
    private $userId;
    
    public function __construct($panelUrl, $token, $userId) {
        // Normalizar URL - adicionar https:// se não tiver protocolo
        if (!preg_match('/^https?:\/\//', $panelUrl)) {
            $panelUrl = 'https://' . $panelUrl;
        }
        
        $this->panelUrl = rtrim($panelUrl, '/');
        $this->token = $token;
        $this->userId = $userId;
    }
    
    /**
     * Atualizar o userId
     */
    public function setUserId($userId) {
        $this->userId = $userId;
    }
    
    /**
     * Fazer requisição HTTP para a API do Sigma
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        // Remove trailing slash from URL
        $baseUrl = rtrim($this->panelUrl, '/');
        
        // If URL already ends with /api, just add /webhook/
        // Otherwise add /api/webhook/
        if (substr($baseUrl, -4) === '/api') {
            $url = $baseUrl . '/webhook/' . ltrim($endpoint, '/');
        } else {
            $url = $baseUrl . '/api/webhook/' . ltrim($endpoint, '/');
        }
        
        // Log da URL para debug
        error_log("Sigma API URL: " . $url);
        
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Erro cURL: " . $error);
        }
        
        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMessage = "Sigma API Error: $httpCode";
            
            // Log the full response for debugging
            error_log("Sigma API Error Response: " . $response);
            
            // Try to get more specific error from response
            if ($response) {
                $errorData = json_decode($response, true);
                if ($errorData) {
                    if (isset($errorData['message'])) {
                        $errorMessage = "Sigma API Error: " . $errorData['message'];
                    } elseif (isset($errorData['error'])) {
                        $errorMessage = "Sigma API Error: " . $errorData['error'];
                    } elseif (isset($errorData['errors']) && is_array($errorData['errors'])) {
                        $errorMessage = "Sigma API Error: " . implode(', ', $errorData['errors']);
                    }
                } else {
                    // If not JSON, include raw response
                    $errorMessage = "Sigma API Error: $httpCode - " . substr($response, 0, 200);
                }
            }
            
            // Add specific guidance for common errors
            if ($httpCode === 400) {
                $errorMessage .= " (Verifique se todos os campos obrigatórios estão preenchidos)";
            } elseif ($httpCode === 401) {
                $errorMessage .= " (Token inválido ou expirado)";
            } elseif ($httpCode === 404) {
                $errorMessage .= " (Endpoint não encontrado - verifique a URL do painel)";
            }
            
            throw new Exception($errorMessage);
        }
        
        if (empty($response)) {
            throw new Exception("Resposta vazia do servidor (HTTP {$httpCode})");
        }
        
        // Verificar se a resposta é HTML (indica URL incorreta ou redirecionamento)
        if (stripos($response, '<!DOCTYPE html>') !== false || stripos($response, '<html') !== false) {
            throw new Exception("Servidor retornou HTML em vez de JSON. Verifique se a URL da API está correta.");
        }
        
        // Log da resposta para debug
        error_log("Sigma API Response: " . substr($response, 0, 500));
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Erro ao decodificar JSON: " . json_last_error_msg() . " | Response: " . substr($response, 0, 200));
        }
        
        return $decoded;
    }
    
    /**
     * Testar conexão com o painel
     */
    public function testConnection() {
        $originalUrl = $this->panelUrl;
        $username = $this->userId; // userId é na verdade o username
        
        try {
            // First, try to search for the specific user by username
            error_log("🔍 Buscando usuário específico: $username");
            
            try {
                $specificUserResponse = $this->makeRequest('user?username=' . urlencode($username));
                error_log("🔍 Resposta da busca específica: " . json_encode($specificUserResponse));
                
                if ($specificUserResponse && isset($specificUserResponse['data']) && !empty($specificUserResponse['data'])) {
                    // Handle paginated response
                    $userData = $specificUserResponse['data'];
                    
                    if (is_array($userData) && isset($userData[0])) {
                        $user = $userData[0];
                    } else {
                        $user = $userData;
                    }
                    
                    error_log("✅ Usuário encontrado via busca específica");
                    
                    return [
                        'success' => true,
                        'message' => 'Conexão estabelecida com sucesso (busca específica)',
                        'userId' => $user['id'] ?? null,
                        'username' => $user['username'] ?? null,
                        'data' => $user
                    ];
                }
            } catch (Exception $e) {
                error_log("❌ Erro na busca específica: " . $e->getMessage());
                // Continue para o fallback
            }
            
            // If not found via search, try listing all users
            error_log("Usuário não encontrado via busca, listando todos os usuários...");
            $response = $this->makeRequest('user');
            
            if (!$response || !isset($response['data'])) {
                throw new Exception('Resposta inválida do Sigma');
            }
            
            $users = $response['data'];
            if (!is_array($users) || empty($users)) {
                throw new Exception('Nenhum usuário encontrado no painel');
            }
            
            // Log available users
            $availableUsernames = array_map(function($u) {
                return $u['username'] ?? 'N/A';
            }, $users);
            error_log("Usuários disponíveis: " . implode(', ', $availableUsernames));
            
            // Find user by username in the list
            $user = null;
            foreach ($users as $u) {
                if (isset($u['username']) && $u['username'] === $username) {
                    $user = $u;
                    break;
                }
            }
            
            if (!$user) {
                $errorMsg = "Usuário '$username' não encontrado na API. ";
                $errorMsg .= "Usuários disponíveis: " . implode(', ', $availableUsernames);
                throw new Exception($errorMsg);
            }
            
            error_log("✅ Usuário encontrado na lista");
            
            return [
                'success' => true,
                'message' => 'Conexão estabelecida com sucesso',
                'userId' => $user['id'] ?? null,
                'username' => $user['username'] ?? null,
                'data' => $user
            ];
            
        } catch (Exception $e) {
            // Se falhou com HTTPS, tentar com HTTP
            if (strpos($originalUrl, 'https://') === 0) {
                try {
                    error_log("Tentando com HTTP...");
                    $this->panelUrl = str_replace('https://', 'http://', $originalUrl);
                    
                    // Tentar busca específica com HTTP
                    $specificUserResponse = $this->makeRequest('user?username=' . urlencode($username));
                    
                    if ($specificUserResponse && isset($specificUserResponse['data']) && !empty($specificUserResponse['data'])) {
                        $userData = $specificUserResponse['data'];
                        
                        if (is_array($userData) && isset($userData[0])) {
                            $user = $userData[0];
                        } else {
                            $user = $userData;
                        }
                        
                        return [
                            'success' => true,
                            'message' => 'Conexão estabelecida com sucesso via HTTP (HTTPS falhou)',
                            'userId' => $user['id'] ?? null,
                            'username' => $user['username'] ?? null,
                            'recommended_url' => $this->panelUrl,
                            'data' => $user
                        ];
                    }
                } catch (Exception $e2) {
                    // Restaurar URL original
                    $this->panelUrl = $originalUrl;
                    
                    error_log("Sigma connection test error (both HTTPS and HTTP failed): HTTPS: " . $e->getMessage() . " | HTTP: " . $e2->getMessage());
                    
                    return [
                        'success' => false,
                        'message' => 'Erro na conexão (HTTPS e HTTP falharam): ' . $e->getMessage()
                    ];
                }
            }
            
            error_log("Sigma connection test error: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Erro na conexão: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Listar usuários do painel
     */
    public function getUsers($page = 1, $username = null) {
        $params = [];
        
        if ($page > 1) {
            $params['page'] = $page;
        }
        
        if ($username) {
            $params['username'] = $username;
        }
        
        $query = !empty($params) ? '?' . http_build_query($params) : '';
        return $this->makeRequest('user' . $query);
    }
    
    /**
     * Listar pacotes disponíveis
     */
    public function getPackages($page = 1) {
        $query = http_build_query(['page' => $page]);
        return $this->makeRequest('package?' . $query);
    }
    
    /**
     * Buscar cliente por username
     */
    public function getCustomer($username) {
        $query = http_build_query(['username' => $username]);
        return $this->makeRequest('customer?' . $query);
    }
    
    /**
     * Criar novo cliente no Sigma
     */
    public function createCustomer($customerData) {
        $data = [
            'userId' => $this->userId,
            'packageId' => $customerData['packageId'],
            'username' => $customerData['username'] ?? '',
            'password' => $customerData['password'] ?? '',
            'name' => $customerData['name'] ?? '',
            'email' => $customerData['email'] ?? '',
            'whatsapp' => $customerData['whatsapp'] ?? '',
            'note' => $customerData['note'] ?? ''
        ];
        
        return $this->makeRequest('customer/create', 'POST', $data);
    }
    
    /**
     * Renovar cliente existente
     */
    public function renewCustomer($username, $packageId) {
        $data = [
            'userId' => $this->userId,
            'username' => $username,
            'packageId' => $packageId
        ];
        
        return $this->makeRequest('customer/renew', 'POST', $data);
    }
    
    /**
     * Atualizar status do cliente
     */
    public function updateCustomerStatus($username, $status) {
        $data = [
            'userId' => $this->userId,
            'username' => $username,
            'status' => strtoupper($status) // ACTIVE ou INACTIVE
        ];
        
        return $this->makeRequest('customer/status', 'PUT', $data);
    }
    
    /**
     * Deletar cliente
     */
    public function deleteCustomer($username) {
        $data = [
            'userId' => $this->userId,
            'username' => $username
        ];
        
        return $this->makeRequest('customer', 'DELETE', $data);
    }
    
    /**
     * Sincronizar cliente do gestor com o Sigma
     */
    public function syncCustomer($clientData, $packageId) {
        try {
            // Gerar username e password se não fornecidos
            $username = $clientData['username'];
            $password = $clientData['iptv_password'];
            
            if (empty($username)) {
                // Gerar username baseado no nome (sem espaços, minúsculo)
                $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $clientData['name']));
                // Adicionar números aleatórios se muito curto
                if (strlen($username) < 4) {
                    $username .= rand(100, 999);
                }
            }
            
            if (empty($password)) {
                // Gerar password aleatório
                $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
            }
            
            // Verificar se cliente já existe
            $existing = null;
            try {
                $existingResponse = $this->getCustomer($username);
                if (isset($existingResponse['data']) && !empty($existingResponse['data'])) {
                    // A API do Sigma retorna um objeto diretamente, não um array
                    $existing = $existingResponse['data'];
                }
            } catch (Exception $e) {
                // Cliente não existe, será criado
                error_log("Cliente não encontrado no Sigma, será criado: " . $e->getMessage());
            }
            
            if ($existing && isset($existing['username'])) {
                // Cliente existe, renovar
                error_log("Cliente existe no Sigma, renovando: " . $username);
                $result = $this->renewCustomer($username, $packageId);
                
                // Atualizar status se necessário
                $sigmaStatus = $clientData['status'] === 'active' ? 'ACTIVE' : 'INACTIVE';
                $this->updateCustomerStatus($username, $sigmaStatus);
                
                return [
                    'success' => true,
                    'action' => 'renewed',
                    'message' => 'Cliente renovado no Sigma',
                    'username' => $username,
                    'data' => $result
                ];
            } else {
                // Cliente não existe, criar
                error_log("Criando novo cliente no Sigma: " . $username);
                $customerData = [
                    'packageId' => $packageId,
                    'username' => $username,
                    'password' => $password,
                    'name' => $clientData['name'],
                    'email' => $clientData['email'] ?? '',
                    'whatsapp' => $this->formatWhatsApp($clientData['phone'] ?? ''),
                    'note' => $clientData['notes'] ?? ''
                ];
                
                $result = $this->createCustomer($customerData);
                
                // Atualizar cliente no gestor com username e password gerados
                if (empty($clientData['username']) || empty($clientData['iptv_password'])) {
                    try {
                        Database::update('clients', [
                            'username' => $username,
                            'iptv_password' => $password
                        ], 'id = :id', ['id' => $clientData['id']]);
                        
                        error_log("Cliente atualizado no gestor com credenciais: " . $username);
                    } catch (Exception $e) {
                        error_log("Erro ao atualizar credenciais no gestor: " . $e->getMessage());
                    }
                }
                
                return [
                    'success' => true,
                    'action' => 'created',
                    'message' => 'Cliente criado no Sigma',
                    'username' => $username,
                    'password' => $password,
                    'data' => $result
                ];
            }
        } catch (Exception $e) {
            error_log("Erro na sincronização Sigma: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erro na sincronização: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Formatar número de WhatsApp para o padrão internacional
     */
    private function formatWhatsApp($phone) {
        if (empty($phone)) {
            return '';
        }
        
        // Remover caracteres não numéricos
        $phone = preg_replace('/\D/', '', $phone);
        
        // Se não tem código do país, assumir Brasil (55)
        if (strlen($phone) === 11 && substr($phone, 0, 1) === '9') {
            $phone = '55' . $phone;
        } elseif (strlen($phone) === 10) {
            $phone = '559' . $phone;
        }
        
        // Formatar: 55 11 99999 9999
        if (strlen($phone) === 13 && substr($phone, 0, 2) === '55') {
            return substr($phone, 0, 2) . ' ' . substr($phone, 2, 2) . ' ' . substr($phone, 4, 5) . ' ' . substr($phone, 9);
        }
        
        return $phone;
    }
}