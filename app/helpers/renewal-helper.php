<?php
/**
 * Helper para cálculo de renovação de clientes
 * Mantém o mesmo dia de vencimento quando configurado
 */

/**
 * Calcula a próxima data de vencimento
 * 
 * @param string $currentRenewalDate Data atual de vencimento (YYYY-MM-DD)
 * @param int $durationDays Duração em dias (30, 60, 90, etc)
 * @param bool $keepSameDueDay Se deve manter o mesmo dia do mês
 * @return string Nova data de vencimento (YYYY-MM-DD)
 */
function calculateNextRenewalDate($currentRenewalDate, $durationDays, $keepSameDueDay = false) {
    try {
        $currentDate = new DateTime($currentRenewalDate);
        
        if (!$keepSameDueDay) {
            // Comportamento padrão: adicionar dias
            $currentDate->modify("+{$durationDays} days");
            return $currentDate->format('Y-m-d');
        }
        
        // Manter o mesmo dia do mês
        $dayOfMonth = (int)$currentDate->format('d');
        
        // Calcular quantos meses adicionar baseado nos dias
        $monthsToAdd = (int)floor($durationDays / 30);
        if ($monthsToAdd < 1) {
            $monthsToAdd = 1; // Mínimo de 1 mês
        }
        
        // Adicionar os meses
        $currentDate->modify("+{$monthsToAdd} months");
        
        // Ajustar para o dia correto
        $newMonth = (int)$currentDate->format('m');
        $newYear = (int)$currentDate->format('Y');
        
        // Verificar quantos dias tem o mês de destino
        $daysInMonth = (int)date('t', mktime(0, 0, 0, $newMonth, 1, $newYear));
        
        // Se o dia desejado não existe no mês (ex: 31 em fevereiro), usar o último dia do mês
        $finalDay = min($dayOfMonth, $daysInMonth);
        
        // Criar a nova data com o dia correto
        $newDate = new DateTime("{$newYear}-{$newMonth}-{$finalDay}");
        
        return $newDate->format('Y-m-d');
        
    } catch (Exception $e) {
        error_log("Erro ao calcular próxima data de renovação: " . $e->getMessage());
        // Fallback: adicionar dias normalmente
        $fallbackDate = new DateTime($currentRenewalDate);
        $fallbackDate->modify("+{$durationDays} days");
        return $fallbackDate->format('Y-m-d');
    }
}

/**
 * Renova um cliente mantendo o mesmo dia se configurado
 * 
 * @param string $clientId ID do cliente
 * @param int $durationDays Duração da renovação em dias
 * @return array Resultado da renovação
 */
function renewClientWithSameDueDay($clientId, $durationDays = 30) {
    try {
        // Buscar dados do cliente
        $client = Database::fetchOne(
            "SELECT id, renewal_date, keep_same_due_day FROM clients WHERE id = ?",
            [$clientId]
        );
        
        if (!$client) {
            return [
                'success' => false,
                'message' => 'Cliente não encontrado'
            ];
        }
        
        $keepSameDueDay = (bool)($client['keep_same_due_day'] ?? false);
        $currentRenewalDate = $client['renewal_date'];
        
        // Calcular nova data
        $newRenewalDate = calculateNextRenewalDate($currentRenewalDate, $durationDays, $keepSameDueDay);
        
        // Atualizar no banco
        Database::update(
            'clients',
            ['renewal_date' => $newRenewalDate, 'status' => 'active'],
            'id = :id',
            ['id' => $clientId]
        );
        
        return [
            'success' => true,
            'old_date' => $currentRenewalDate,
            'new_date' => $newRenewalDate,
            'kept_same_day' => $keepSameDueDay,
            'message' => 'Cliente renovado com sucesso'
        ];
        
    } catch (Exception $e) {
        error_log("Erro ao renovar cliente: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro ao renovar cliente: ' . $e->getMessage()
        ];
    }
}

/**
 * Exemplo de uso:
 * 
 * // Cliente com vencimento em 20/12/2025 e keep_same_due_day = true
 * $result = renewClientWithSameDueDay('client-123', 30);
 * // Resultado: 20/01/2026
 * 
 * $result = renewClientWithSameDueDay('client-123', 60);
 * // Resultado: 20/02/2026
 * 
 * // Cliente com vencimento em 31/01/2025 e keep_same_due_day = true
 * $result = renewClientWithSameDueDay('client-456', 30);
 * // Resultado: 28/02/2025 (ou 29 se ano bissexto) - ajusta para último dia do mês
 */
