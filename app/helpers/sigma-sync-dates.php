<?php
/**
 * Sincronização Reversa Sigma -> Gestor
 * Atualiza datas de vencimento do gestor baseado no Sigma
 */

require_once __DIR__ . '/SigmaAPI.php';
require_once __DIR__ . '/../core/Database.php';

/**
 * Sincronizar data de vencimento do Sigma para o Gestor
 * Busca a data no Sigma e atualiza no gestor se for diferente
 */
function syncDateFromSigmaToGestor($clientId, $resellerId) {
    try {
        error_log("🔄 SINCRONIZAÇÃO REVERSA SIGMA -> GESTOR");
        error_log("Cliente ID: {$clientId}");
        
        // Buscar dados do cliente no gestor
        $client = Database::fetch(
            "SELECT id, username, renewal_date, reseller_id FROM clients WHERE id = ? AND reseller_id = ?",
            [$clientId, $resellerId]
        );
        
        if (!$client) {
            error_log("❌ Cliente não encontrado no gestor");
            return [
                'success' => false,
                'message' => 'Cliente não encontrado'
            ];
        }
        
        if (empty($client['username'])) {
            error_log("⚠️ Cliente sem username - não pode sincronizar");
            return [
                'success' => false,
                'message' => 'Cliente sem username configurado'
            ];
        }
        
        // Buscar servidor Sigma configurado
        $server = Database::fetch(
            "SELECT id, panel_url, reseller_user, sigma_token 
             FROM servers 
             WHERE user_id = ? AND panel_type = 'sigma' AND status = 'active' 
             LIMIT 1",
            [$resellerId]
        );
        
        if (!$server) {
            error_log("⚠️ Nenhum servidor Sigma configurado");
            return [
                'success' => false,
                'message' => 'Nenhum servidor Sigma configurado'
            ];
        }
        
        // Criar instância da API Sigma
        $sigmaAPI = new SigmaAPI($server['panel_url'], $server['sigma_token'], $server['reseller_user']);
        
        // Buscar dados do cliente no Sigma
        error_log("🔍 Buscando cliente no Sigma: " . $client['username']);
        $sigmaResponse = $sigmaAPI->getCustomer($client['username']);
        
        if (!isset($sigmaResponse['data']) || empty($sigmaResponse['data'])) {
            error_log("⚠️ Cliente não encontrado no Sigma");
            return [
                'success' => false,
                'message' => 'Cliente não encontrado no Sigma'
            ];
        }
        
        $sigmaCustomer = $sigmaResponse['data'];
        
        // Verificar se tem data de expiração no Sigma
        // A API do Sigma pode retornar 'expires_at' ou 'expiration_date'
        $sigmaExpirationDate = null;
        
        if (isset($sigmaCustomer['expires_at']) && !empty($sigmaCustomer['expires_at'])) {
            $sigmaExpirationDate = $sigmaCustomer['expires_at'];
        } elseif (isset($sigmaCustomer['expires_at_tz']) && !empty($sigmaCustomer['expires_at_tz'])) {
            $sigmaExpirationDate = $sigmaCustomer['expires_at_tz'];
        } elseif (isset($sigmaCustomer['expiration_date']) && !empty($sigmaCustomer['expiration_date'])) {
            $sigmaExpirationDate = $sigmaCustomer['expiration_date'];
        }
        
        if (!$sigmaExpirationDate) {
            error_log("⚠️ Cliente no Sigma não tem data de expiração");
            return [
                'success' => false,
                'message' => 'Cliente no Sigma não tem data de expiração'
            ];
        }
        $gestorRenewalDate = $client['renewal_date'];
        
        error_log("📅 Data no Sigma (raw): {$sigmaExpirationDate}");
        error_log("📅 Data no Gestor: {$gestorRenewalDate}");
        
        // Converter datas para comparação (formato Y-m-d)
        // O Sigma retorna em UTC, então precisamos ajustar para o timezone local
        // Exemplo: "2025-11-28T02:59:59.000000Z" deve ser convertido para "2025-11-27"
        
        // Criar DateTime object com timezone UTC
        $sigmaDateTime = new DateTime($sigmaExpirationDate, new DateTimeZone('UTC'));
        
        // Converter para timezone de Brasília (America/Sao_Paulo)
        $sigmaDateTime->setTimezone(new DateTimeZone('America/Sao_Paulo'));
        
        // Pegar apenas a data (Y-m-d)
        $sigmaDate = $sigmaDateTime->format('Y-m-d');
        $gestorDate = date('Y-m-d', strtotime($gestorRenewalDate));
        
        error_log("📅 Data no Sigma (convertida): {$sigmaDate}");
        error_log("📅 Data no Gestor (formatada): {$gestorDate}");
        
        // Se as datas são iguais, não precisa atualizar
        if ($sigmaDate === $gestorDate) {
            error_log("✅ Datas já estão sincronizadas");
            return [
                'success' => true,
                'message' => 'Datas já sincronizadas',
                'date_changed' => false,
                'sigma_date' => $sigmaDate,
                'gestor_date' => $gestorDate
            ];
        }
        
        // Atualizar data no gestor
        error_log("🔄 Atualizando data no gestor: {$gestorDate} -> {$sigmaDate}");
        
        Database::update('clients', [
            'renewal_date' => $sigmaDate
        ], 'id = :id', ['id' => $clientId]);
        
        error_log("✅ Data sincronizada com sucesso!");
        
        return [
            'success' => true,
            'message' => 'Data sincronizada do Sigma para o Gestor',
            'date_changed' => true,
            'old_date' => $gestorDate,
            'new_date' => $sigmaDate,
            'sigma_date' => $sigmaDate,
            'gestor_date' => $sigmaDate
        ];
        
    } catch (Exception $e) {
        error_log("❌ Erro na sincronização reversa: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro na sincronização: ' . $e->getMessage()
        ];
    }
}

/**
 * Sincronizar datas de todos os clientes de um reseller
 */
function syncAllClientsDatesFromSigma($resellerId) {
    try {
        error_log("🔄 SINCRONIZAÇÃO EM MASSA - SIGMA -> GESTOR");
        error_log("Reseller ID: {$resellerId}");
        
        // Buscar todos os clientes com username configurado
        $clients = Database::fetchAll(
            "SELECT id, username, name FROM clients 
             WHERE reseller_id = ? AND username IS NOT NULL AND username != ''",
            [$resellerId]
        );
        
        if (empty($clients)) {
            return [
                'success' => true,
                'message' => 'Nenhum cliente com username para sincronizar',
                'total' => 0,
                'synced' => 0,
                'errors' => 0
            ];
        }
        
        $results = [
            'total' => count($clients),
            'synced' => 0,
            'errors' => 0,
            'unchanged' => 0,
            'details' => []
        ];
        
        foreach ($clients as $client) {
            $result = syncDateFromSigmaToGestor($client['id'], $resellerId);
            
            if ($result['success']) {
                if (isset($result['date_changed']) && $result['date_changed']) {
                    $results['synced']++;
                } else {
                    $results['unchanged']++;
                }
            } else {
                $results['errors']++;
            }
            
            $results['details'][] = [
                'client_id' => $client['id'],
                'client_name' => $client['name'],
                'username' => $client['username'],
                'result' => $result
            ];
        }
        
        error_log("✅ Sincronização em massa concluída: {$results['synced']} atualizados, {$results['unchanged']} inalterados, {$results['errors']} erros");
        
        return [
            'success' => true,
            'message' => "Sincronização concluída: {$results['synced']} atualizados, {$results['unchanged']} inalterados, {$results['errors']} erros",
            'results' => $results
        ];
        
    } catch (Exception $e) {
        error_log("❌ Erro na sincronização em massa: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro na sincronização: ' . $e->getMessage()
        ];
    }
}
