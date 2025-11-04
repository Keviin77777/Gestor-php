<?php
/**
 * Helper para integração com Sigma
 */

require_once __DIR__ . '/SigmaAPI.php';
require_once __DIR__ . '/../core/Database.php';

/**
 * Obter configuração do Sigma para um servidor
 */
function getSigmaConfig($serverId) {
    try {
        $server = Database::fetch(
            "SELECT panel_type, panel_url, reseller_user, sigma_token 
             FROM servers 
             WHERE id = ? AND panel_type = 'sigma' AND status = 'active'",
            [$serverId]
        );
        
        if (!$server || empty($server['sigma_token'])) {
            return null;
        }
        
        return [
            'panel_url' => $server['panel_url'],
            'token' => $server['sigma_token'],
            'user_id' => $server['reseller_user']
        ];
    } catch (Exception $e) {
        error_log("Erro ao obter config Sigma: " . $e->getMessage());
        return null;
    }
}

/**
 * Sincronizar cliente com Sigma
 */
function syncClientWithSigma($clientData, $serverId = null, $packageId = null) {
    try {
        // Se não foi especificado servidor, tentar encontrar um servidor Sigma ativo
        if (!$serverId) {
            $user = Auth::user();
            if (!$user) {
                return ['success' => false, 'message' => 'Usuário não autenticado'];
            }
            
            $server = Database::fetch(
                "SELECT id FROM servers 
                 WHERE user_id = ? AND panel_type = 'sigma' AND status = 'active' 
                 LIMIT 1",
                [$user['id']]
            );
            
            if (!$server) {
                // Não há servidor Sigma configurado, retornar sucesso silencioso
                return ['success' => true, 'message' => 'Nenhum servidor Sigma configurado - sincronização ignorada'];
            }
            
            $serverId = $server['id'];
        }
        
        $config = getSigmaConfig($serverId);
        if (!$config) {
            return ['success' => false, 'message' => 'Configuração Sigma não encontrada'];
        }
        
        // Se não foi especificado packageId, buscar packages disponíveis e usar o primeiro
        if (!$packageId) {
            try {
                $sigmaAPI = new SigmaAPI($config['panel_url'], $config['token'], $config['user_id']);
                $packagesResponse = $sigmaAPI->getPackages();
                
                if (isset($packagesResponse['data']) && !empty($packagesResponse['data'])) {
                    $packages = $packagesResponse['data'];
                    // Usar o primeiro package disponível
                    $packageId = $packages[0]['id'] ?? null;
                    
                    if (!$packageId) {
                        return ['success' => false, 'message' => 'Nenhum package disponível no Sigma'];
                    }
                    
                    error_log("Usando package automático: " . $packageId);
                } else {
                    return ['success' => false, 'message' => 'Não foi possível obter packages do Sigma'];
                }
            } catch (Exception $e) {
                error_log("Erro ao buscar packages: " . $e->getMessage());
                return ['success' => false, 'message' => 'Erro ao buscar packages: ' . $e->getMessage()];
            }
        }
        
        $sigmaAPI = new SigmaAPI($config['panel_url'], $config['token'], $config['user_id']);
        
        return $sigmaAPI->syncCustomer($clientData, $packageId);
        
    } catch (Exception $e) {
        error_log("Erro na sincronização Sigma: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro na sincronização: ' . $e->getMessage()
        ];
    }
}

/**
 * Testar conexão com Sigma (helper function)
 */
function testSigmaConnectionHelper($panelUrl, $token, $userId) {
    try {
        $sigmaAPI = new SigmaAPI($panelUrl, $token, $userId);
        return $sigmaAPI->testConnection();
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro no teste: ' . $e->getMessage()
        ];
    }
}

/**
 * Obter packages disponíveis no Sigma (helper function)
 */
function getSigmaPackagesHelper($serverId) {
    try {
        $config = getSigmaConfig($serverId);
        if (!$config) {
            return ['success' => false, 'message' => 'Configuração não encontrada'];
        }
        
        $sigmaAPI = new SigmaAPI($config['panel_url'], $config['token'], $config['user_id']);
        $packages = $sigmaAPI->getPackages();
        
        return [
            'success' => true,
            'packages' => $packages['data'] ?? []
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao buscar packages: ' . $e->getMessage()
        ];
    }
}

/**
 * Obter usuários disponíveis no Sigma (helper function)
 */
function getSigmaUsersHelper($serverId) {
    try {
        $config = getSigmaConfig($serverId);
        if (!$config) {
            return ['success' => false, 'message' => 'Configuração não encontrada'];
        }
        
        $sigmaAPI = new SigmaAPI($config['panel_url'], $config['token'], $config['user_id']);
        $users = $sigmaAPI->getUsers();
        
        return [
            'success' => true,
            'users' => $users['data'] ?? []
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao buscar usuários: ' . $e->getMessage()
        ];
    }
}