<?php
/**
 * Helper para WhatsApp com API Nativa (whatsapp-web.js)
 */

require_once __DIR__ . '/../core/Database.php';

class WhatsAppNativeAPI {
    private $apiUrl;
    private $apiKey;
    
    public function __construct() {
        $this->apiUrl = env('WHATSAPP_NATIVE_API_URL', 'http://localhost:3000');
        $this->apiKey = env('WHATSAPP_NATIVE_API_KEY', '');
    }
    
    /**
     * Fazer requisição à API
     */
    private function request($method, $endpoint, $data = null) {
        $url = rtrim($this->apiUrl, '/') . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey
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
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Erro de conexão: ' . $error);
        }
        
        if ($httpCode !== 200) {
            // Tentar decodificar a resposta de erro
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error'] ?? $errorData['message'] ?? 'Erro HTTP: ' . $httpCode;
            throw new Exception($errorMsg);
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Resposta inválida da API');
        }
        
        return $result;
    }
    
    /**
     * Conectar instância
     */
    public function connect($resellerId) {
        return $this->request('POST', '/api/instance/connect', [
            'reseller_id' => $resellerId
        ]);
    }
    
    /**
     * Obter QR Code
     */
    public function getQRCode($resellerId) {
        return $this->request('GET', '/api/instance/qrcode/' . $resellerId);
    }
    
    /**
     * Verificar status
     */
    public function getStatus($resellerId) {
        return $this->request('GET', '/api/instance/status/' . $resellerId);
    }
    
    /**
     * Desconectar
     */
    public function disconnect($resellerId) {
        return $this->request('POST', '/api/instance/disconnect', [
            'reseller_id' => $resellerId
        ]);
    }
    
    /**
     * Enviar mensagem
     */
    public function sendMessage($resellerId, $phoneNumber, $message, $templateId = null, $clientId = null, $invoiceId = null) {
        return $this->request('POST', '/api/message/send', [
            'reseller_id' => $resellerId,
            'phone_number' => $phoneNumber,
            'message' => $message,
            'template_id' => $templateId,
            'client_id' => $clientId,
            'invoice_id' => $invoiceId
        ]);
    }
    
    /**
     * Enviar mensagens em massa
     */
    public function sendBulk($resellerId, $messages) {
        return $this->request('POST', '/api/message/send-bulk', [
            'reseller_id' => $resellerId,
            'messages' => $messages
        ]);
    }
    
    /**
     * Buscar fila pendente
     */
    public function getPendingQueue($resellerId, $limit = 10) {
        return $this->request('GET', '/api/queue/pending/' . $resellerId . '?limit=' . $limit);
    }
}
