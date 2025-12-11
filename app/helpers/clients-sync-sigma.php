<?php
/**
 * Sigma Integration for Clients
 * Baseado no código que funcionou no outro projeto
 */

require_once __DIR__ . '/SigmaAPI.php';
require_once __DIR__ . '/../core/Database.php';

/**
 * Sincronizar cliente com Sigma após criação/atualização
 */
function syncClientWithSigmaAfterSave($clientData, $reseller_id) {
    try {
        error_log("🔥 INICIANDO SINCRONIZAÇÃO SIGMA - Cliente: " . $clientData['name']);
        
        // Buscar servidor Sigma configurado para este reseller
        $server = Database::fetch(
            "SELECT id, panel_url, reseller_user, sigma_token 
             FROM servers 
             WHERE user_id = ? AND panel_type = 'sigma' AND status = 'active' 
             LIMIT 1",
            [$reseller_id]
        );
        
        if (!$server) {
            error_log("⚠️ Nenhum servidor Sigma configurado para reseller: " . $reseller_id);
            return [
                'success' => true, 
                'message' => 'Nenhum servidor Sigma configurado - sincronização ignorada'
            ];
        }
        
        error_log("✅ Servidor Sigma encontrado: " . $server['id']);
        
        // Validar configuração Sigma
        if (empty($server['panel_url']) || empty($server['sigma_token']) || empty($server['reseller_user'])) {
            error_log("❌ Configuração Sigma incompleta");
            return [
                'success' => false,
                'message' => 'Configuração Sigma incompleta'
            ];
        }
        
        // Criar instância da API Sigma
        $sigmaAPI = new SigmaAPI($server['panel_url'], $server['sigma_token'], $server['reseller_user']);
        
        // Primeiro, verificar se o usuário existe e obter o userId correto
        error_log("🔍 Verificando usuário no Sigma: " . $server['reseller_user']);
        
        $userId = null;
        
        // Tentar buscar usuário específico primeiro (método mais eficiente)
        try {
            error_log("🔍 Buscando usuário específico: " . $server['reseller_user']);
            $userResponse = $sigmaAPI->getUsers(1, $server['reseller_user']);
            
            if (isset($userResponse['data']) && !empty($userResponse['data'])) {
                $userData = $userResponse['data'];
                
                // Pegar o primeiro usuário da resposta
                if (is_array($userData) && isset($userData[0])) {
                    $user = $userData[0];
                } else {
                    $user = $userData;
                }
                
                $userId = $user['id'] ?? null;
                
                if ($userId) {
                    error_log("✅ Usuário encontrado via busca específica - ID: " . $userId);
                } else {
                    error_log("⚠️ Resposta da busca específica não contém ID");
                }
            } else {
                error_log("⚠️ Busca específica não retornou dados");
            }
        } catch (Exception $e) {
            error_log("⚠️ Busca específica falhou: " . $e->getMessage());
        }
        
        // Se não conseguiu obter o userId, retornar erro
        if (!$userId) {
            return [
                'success' => false,
                'message' => "Não foi possível obter o ID do usuário '{$server['reseller_user']}' no Sigma"
            ];
        }
        
        // Atualizar o userId na instância da API
        $sigmaAPI->setUserId($userId);
        
        // Buscar packages disponíveis
        error_log("🔍 Buscando packages disponíveis...");
        $packagesResponse = $sigmaAPI->getPackages();
        
        if (!isset($packagesResponse['data']) || empty($packagesResponse['data'])) {
            error_log("❌ Nenhum package disponível no Sigma");
            return [
                'success' => false,
                'message' => 'Nenhum package disponível no Sigma'
            ];
        }
        
        $packages = $packagesResponse['data'];
        
        // Procurar por um package pago (não trial) para renovações
        $packageId = null;
        $trialPackageId = null;
        
        foreach ($packages as $package) {
            $isTrial = ($package['is_trial'] ?? 'NO') === 'YES';
            $isActive = ($package['status'] ?? 'INACTIVE') === 'ACTIVE';
            
            if ($isTrial) {
                // Package trial
                if (!$trialPackageId && $isActive) {
                    $trialPackageId = $package['id'];
                    error_log("📝 Package trial encontrado: " . $package['id'] . " - " . ($package['name'] ?? 'N/A'));
                }
            } else {
                // Package pago (não trial)
                if (!$packageId && $isActive) {
                    $packageId = $package['id'];
                    error_log("✅ Package pago encontrado: " . $package['id'] . " - " . ($package['name'] ?? 'N/A') . " - Preço: " . ($package['plan_price'] ?? 0));
                }
            }
        }
        
        // Se não encontrou package pago, usar trial como fallback
        if (!$packageId) {
            $packageId = $trialPackageId;
            error_log("⚠️ Nenhum package pago encontrado, usando trial: " . $packageId);
        }
        
        if (!$packageId) {
            error_log("❌ Nenhum package encontrado");
            return [
                'success' => false,
                'message' => 'Nenhum package encontrado'
            ];
        }
        
        error_log("✅ Usando package: " . $packageId);
        
        // Preparar dados do cliente para Sigma
        $username = $clientData['username'];
        $password = $clientData['iptv_password'] ?? $clientData['password'] ?? '';
        
        // Gerar username se não fornecido
        if (empty($username)) {
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $clientData['name']));
            if (strlen($username) < 4) {
                $username .= rand(100, 999);
            }
            error_log("📝 Username gerado: " . $username);
        }
        
        // Gerar password se não fornecido
        if (empty($password)) {
            $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
            error_log("🔑 Password gerado: " . $password);
        }
        
        // Verificar se cliente já existe no Sigma
        error_log("🔍 Verificando se cliente existe no Sigma: " . $username);
        $existingCustomer = null;
        
        try {
            $customerResponse = $sigmaAPI->getCustomer($username);
            error_log("🔍 Resposta da busca do cliente: " . json_encode($customerResponse));
            
            if (isset($customerResponse['data']) && !empty($customerResponse['data'])) {
                // A API do Sigma retorna um objeto diretamente, não um array
                $existingCustomer = $customerResponse['data'];
                error_log("✅ Cliente encontrado no Sigma: " . json_encode($existingCustomer));
            } else {
                error_log("ℹ️ Cliente não encontrado - resposta vazia ou sem dados");
            }
        } catch (Exception $e) {
            error_log("ℹ️ Cliente não encontrado no Sigma (será criado): " . $e->getMessage());
        }
        
        if ($existingCustomer) {
            // Cliente existe - APENAS sincronizar status, NÃO renovar
            // A renovação só deve acontecer quando uma fatura for paga ou renovação manual
            error_log("ℹ️ Cliente já existe no Sigma - sincronizando apenas status (sem renovar)");
            
            // Atualizar apenas o status se necessário
            $sigmaStatus = ($clientData['status'] === 'active') ? 'ACTIVE' : 'INACTIVE';
            
            try {
                $sigmaAPI->updateCustomerStatus($username, $sigmaStatus);
                error_log("✅ Status do cliente atualizado no Sigma: " . $sigmaStatus);
            } catch (Exception $e) {
                error_log("⚠️ Erro ao atualizar status: " . $e->getMessage());
            }
            
            return [
                'success' => true,
                'action' => 'synced',
                'message' => 'Cliente já existe no Sigma - status sincronizado (data de vencimento mantida)',
                'username' => $username,
                'note' => 'Para renovar o cliente no Sigma, marque uma fatura como paga ou use a opção de renovação manual'
            ];
            
        } else {
            // Cliente não existe - criar
            error_log("➕ Criando cliente no Sigma...");
            
            $customerData = [
                'packageId' => $packageId,
                'username' => $username,
                'password' => $password,
                'name' => $clientData['name'],
                'email' => $clientData['email'] ?? '',
                'whatsapp' => formatWhatsAppNumber($clientData['phone'] ?? ''),
                'note' => $clientData['notes'] ?? 'Cliente criado via UltraGestor'
            ];
            
            error_log("📤 Dados para Sigma: " . json_encode($customerData));
            
            $createResult = $sigmaAPI->createCustomer($customerData);
            
            // Atualizar cliente no gestor com credenciais geradas
            if (empty($clientData['username']) || empty($clientData['iptv_password'])) {
                try {
                    Database::update('clients', [
                        'username' => $username,
                        'iptv_password' => $password
                    ], 'id = :id', ['id' => $clientData['id']]);
                    
                    error_log("✅ Cliente atualizado no gestor com credenciais");
                } catch (Exception $e) {
                    error_log("⚠️ Erro ao atualizar credenciais no gestor: " . $e->getMessage());
                }
            }
            
            error_log("✅ Cliente criado no Sigma com sucesso");
            
            return [
                'success' => true,
                'action' => 'created',
                'message' => 'Cliente criado no Sigma',
                'username' => $username,
                'password' => $password,
                'data' => $createResult
            ];
        }
        
    } catch (Exception $e) {
        error_log("❌ Erro na sincronização Sigma: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro na sincronização Sigma: ' . $e->getMessage()
        ];
    }
}

/**
 * Renovar cliente no Sigma após pagamento de fatura
 * Esta função DEVE ser chamada apenas quando uma fatura for paga
 */
function renewClientInSigmaAfterPayment($clientData, $reseller_id) {
    try {
        error_log("🔥 RENOVAÇÃO SIGMA - PAGAMENTO CONFIRMADO - Cliente: " . $clientData['name']);
        
        // Buscar servidor Sigma configurado para este reseller
        $server = Database::fetch(
            "SELECT id, panel_url, reseller_user, sigma_token 
             FROM servers 
             WHERE user_id = ? AND panel_type = 'sigma' AND status = 'active' 
             LIMIT 1",
            [$reseller_id]
        );
        
        if (!$server) {
            error_log("⚠️ Nenhum servidor Sigma configurado para reseller: " . $reseller_id);
            return [
                'success' => true, 
                'message' => 'Nenhum servidor Sigma configurado - renovação ignorada'
            ];
        }
        
        // Validar configuração Sigma
        if (empty($server['panel_url']) || empty($server['sigma_token']) || empty($server['reseller_user'])) {
            error_log("❌ Configuração Sigma incompleta");
            return [
                'success' => false,
                'message' => 'Configuração Sigma incompleta'
            ];
        }
        
        // Criar instância da API Sigma
        $sigmaAPI = new SigmaAPI($server['panel_url'], $server['sigma_token'], $server['reseller_user']);
        
        // Obter userId correto
        $userResponse = $sigmaAPI->getUsers(1, $server['reseller_user']);
        
        if (!isset($userResponse['data']) || empty($userResponse['data'])) {
            return [
                'success' => false,
                'message' => "Não foi possível obter o ID do usuário '{$server['reseller_user']}' no Sigma"
            ];
        }
        
        $userData = $userResponse['data'];
        $user = is_array($userData) && isset($userData[0]) ? $userData[0] : $userData;
        $userId = $user['id'] ?? null;
        
        if (!$userId) {
            return [
                'success' => false,
                'message' => "Não foi possível obter o ID do usuário no Sigma"
            ];
        }
        
        $sigmaAPI->setUserId($userId);
        
        // Buscar packages disponíveis
        $packagesResponse = $sigmaAPI->getPackages();
        
        if (!isset($packagesResponse['data']) || empty($packagesResponse['data'])) {
            return [
                'success' => false,
                'message' => 'Nenhum package disponível no Sigma'
            ];
        }
        
        $packages = $packagesResponse['data'];
        
        // Procurar por um package pago (não trial)
        $packageId = null;
        $trialPackageId = null;
        
        foreach ($packages as $package) {
            $isTrial = ($package['is_trial'] ?? 'NO') === 'YES';
            $isActive = ($package['status'] ?? 'INACTIVE') === 'ACTIVE';
            
            if ($isTrial) {
                if (!$trialPackageId && $isActive) {
                    $trialPackageId = $package['id'];
                }
            } else {
                if (!$packageId && $isActive) {
                    $packageId = $package['id'];
                    error_log("✅ Package pago encontrado para renovação: " . $package['id']);
                }
            }
        }
        
        if (!$packageId) {
            $packageId = $trialPackageId;
            error_log("⚠️ Usando package trial para renovação: " . $packageId);
        }
        
        if (!$packageId) {
            return [
                'success' => false,
                'message' => 'Nenhum package encontrado para renovação'
            ];
        }
        
        $username = $clientData['username'];
        
        if (empty($username)) {
            return [
                'success' => false,
                'message' => 'Username do cliente não encontrado'
            ];
        }
        
        // RENOVAR o cliente no Sigma (adicionar +30 dias)
        error_log("🔄 RENOVANDO cliente no Sigma após pagamento: " . $username);
        
        $renewResult = $sigmaAPI->renewCustomer($username, $packageId);
        
        // Atualizar status para ACTIVE
        $sigmaAPI->updateCustomerStatus($username, 'ACTIVE');
        
        error_log("✅ Cliente renovado no Sigma com sucesso após pagamento");
        
        return [
            'success' => true,
            'action' => 'renewed',
            'message' => 'Cliente renovado no Sigma após pagamento',
            'username' => $username,
            'data' => $renewResult
        ];
        
    } catch (Exception $e) {
        error_log("❌ Erro na renovação Sigma após pagamento: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Erro na renovação Sigma: ' . $e->getMessage()
        ];
    }
}

/**
 * Formatar número de WhatsApp para padrão internacional
 */
function formatWhatsAppNumber($phone) {
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