<?php
/**
 * Mock helper for EfiBank integrations in development environments
 */
class EfiBankMockHelper
{

    /**
     * Mock createPixPayment
     */
    public function createPixPayment($data)
    {
        $txid = 'MOCKTEST' . strtoupper(substr(uniqid(), -10));

        // Gerar um código copia e cola fake
        $copiaECola = '00020126580014BR.GOV.BCB.PIX0136' . $txid .
            '520400005303986540515.005802BR5913MOCK USER6008BRASILIA62070503***6304E2CA';

        // Imagem base64 de 1x1 pixel apenas para preencher (sem o prefixo data:image/png;base64, pois o EfiBankHelper geralmente espera raw ou o frontend trata)
        // Verificando EfiBankHelper: ele retorna o que vem da API 'imagemQrcode'. Geralmente é base64 puro sem header.
        $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        $amount = $data['amount'] ?? 0;

        error_log("EFI Mock: Criando pagamento PIX fake de R$ {$amount}");

        return [
            'success' => true,
            'payment_id' => $txid,
            'status' => 'ATIVA',
            'qr_code' => $copiaECola,
            'qr_code_base64' => $base64Image,
            'expiration_date' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'mock' => true
        ];
    }
}
