<?php
/**
 * API Endpoints - Servers
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';
require_once __DIR__ . '/../../helpers/sigma-integration.php';

/**
 * Get all servers for authenticated user
 */
function getServers() {
    try {
        // Verify authentication
        $user = Auth::user();
        
        if (!$user) {
            Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
            return;
        }

        $db = Database::connect();
        
        // Consulta com contagem de clientes conectados
        $stmt = $db->prepare("
            SELECT 
                s.id,
                s.name,
                s.billing_type,
                s.cost,
                s.panel_type,
                s.panel_url,
                s.reseller_user,
                s.status,
                s.created_at,
                COUNT(CASE WHEN c.status = 'active' THEN 1 END) as connected_clients,
                COUNT(c.id) as total_clients
            FROM servers s
            LEFT JOIN clients c ON c.server = s.name AND c.reseller_id = s.user_id
            WHERE s.user_id = ? 
            GROUP BY s.id, s.name, s.billing_type, s.cost, s.panel_type, s.panel_url, s.reseller_user, s.status, s.created_at
            ORDER BY s.created_at DESC
        ");
        
        $stmt->execute([$user['id']]);
        $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Processar dados dos servidores
        foreach ($servers as &$server) {
            $server['cost'] = (float)$server['cost'];
            $server['connected_clients'] = (int)$server['connected_clients'];
            $server['total_clients'] = (int)$server['total_clients'];
            
            // Calcular custo total baseado no tipo de cobrança
            if ($server['billing_type'] === 'per_active') {
                // Cobrança por cliente ativo
                $server['total_cost'] = $server['cost'] * $server['connected_clients'];
            } else {
                // Cobrança fixa
                $server['total_cost'] = $server['cost'];
            }
        }

        Response::json([
            'success' => true,
            'servers' => $servers
        ]);

    } catch (Exception $e) {
        error_log("Error fetching servers: " . $e->getMessage());
        Response::json([
            'success' => false,
            'error' => 'Erro ao buscar servidores'
        ], 500);
    }
}

/**
 * Create new server
 */
function createServer() {
    try {
        // Verify authentication
        $user = Auth::user();
        if (!$user) {
            Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
            return;
        }

        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (empty($data['name']) || empty($data['billing_type']) || empty($data['cost'])) {
            Response::json([
                'success' => false,
                'error' => 'Campos obrigatórios não preenchidos'
            ], 400);
            return;
        }

        // Parse cost (remove R$ and format)
        $cost = $data['cost'];
        $cost = preg_replace('/[^0-9,]/', '', $cost);
        $cost = str_replace(',', '.', $cost);
        $cost = floatval($cost);

        $db = Database::connect();
        
        $stmt = $db->prepare("
            INSERT INTO servers (
                user_id,
                name,
                billing_type,
                cost,
                panel_type,
                panel_url,
                reseller_user,
                sigma_token,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->execute([
            $user['id'],
            $data['name'],
            $data['billing_type'],
            $cost,
            $data['panel_type'] ?? null,
            $data['panel_url'] ?? null,
            $data['reseller_user'] ?? null,
            $data['sigma_token'] ?? null
        ]);

        $serverId = $db->lastInsertId();

        Response::json([
            'success' => true,
            'message' => 'Servidor criado com sucesso',
            'server_id' => $serverId
        ]);

    } catch (Exception $e) {
        error_log("Error creating server: " . $e->getMessage());
        Response::json([
            'success' => false,
            'error' => 'Erro ao criar servidor'
        ], 500);
    }
}

/**
 * Update server
 */
function updateServer($serverId) {
    try {
        // Verify authentication
        $user = Auth::user();
        if (!$user) {
            Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
            return;
        }

        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        if (empty($data['name']) || empty($data['billing_type']) || empty($data['cost'])) {
            Response::json([
                'success' => false,
                'error' => 'Campos obrigatórios não preenchidos'
            ], 400);
            return;
        }

        // Parse cost
        $cost = $data['cost'];
        $cost = preg_replace('/[^0-9,]/', '', $cost);
        $cost = str_replace(',', '.', $cost);
        $cost = floatval($cost);

        $db = Database::connect();
        
        // Verify ownership
        $stmt = $db->prepare("SELECT id FROM servers WHERE id = ? AND user_id = ?");
        $stmt->execute([$serverId, $user['id']]);
        
        if (!$stmt->fetch()) {
            Response::json([
                'success' => false,
                'error' => 'Servidor não encontrado'
            ], 404);
            return;
        }

        // Update server
        // Se sigma_token for fornecido e não estiver vazio, atualizar
        // Caso contrário, manter o token existente
        if (isset($data['sigma_token']) && !empty(trim($data['sigma_token']))) {
            $stmt = $db->prepare("
                UPDATE servers SET
                    name = ?,
                    billing_type = ?,
                    cost = ?,
                    panel_type = ?,
                    panel_url = ?,
                    reseller_user = ?,
                    sigma_token = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $data['billing_type'],
                $cost,
                $data['panel_type'] ?? null,
                $data['panel_url'] ?? null,
                $data['reseller_user'] ?? null,
                trim($data['sigma_token']),
                $serverId,
                $user['id']
            ]);
        } else {
            // Não atualizar o token - manter o existente
            $stmt = $db->prepare("
                UPDATE servers SET
                    name = ?,
                    billing_type = ?,
                    cost = ?,
                    panel_type = ?,
                    panel_url = ?,
                    reseller_user = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $data['billing_type'],
                $cost,
                $data['panel_type'] ?? null,
                $data['panel_url'] ?? null,
                $data['reseller_user'] ?? null,
                $serverId,
                $user['id']
            ]);
        }

        Response::json([
            'success' => true,
            'message' => 'Servidor atualizado com sucesso'
        ]);

    } catch (Exception $e) {
        error_log("Error updating server: " . $e->getMessage());
        Response::json([
            'success' => false,
            'error' => 'Erro ao atualizar servidor'
        ], 500);
    }
}

/**
 * Delete server
 */
function deleteServer($serverId) {
    // Limpar qualquer output anterior
    if (ob_get_level()) {
        ob_clean();
    }
    
    try {
        error_log("DELETE Server - Iniciando exclusão do servidor ID: $serverId");
        
        // Verify authentication
        $user = Auth::user();
        if (!$user) {
            error_log("DELETE Server - Usuário não autenticado");
            Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
            return;
        }

        error_log("DELETE Server - Usuário autenticado: " . $user['id']);

        $db = Database::connect();
        
        // Verificar se o servidor existe e pertence ao usuário
        $stmt = $db->prepare("SELECT id, name FROM servers WHERE id = ? AND user_id = ?");
        $stmt->execute([$serverId, $user['id']]);
        $server = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$server) {
            error_log("DELETE Server - Servidor não encontrado ou não pertence ao usuário");
            Response::json([
                'success' => false,
                'error' => 'Servidor não encontrado'
            ], 404);
            return;
        }
        
        error_log("DELETE Server - Servidor encontrado: " . $server['name']);
        
        // Deletar o servidor
        $stmt = $db->prepare("DELETE FROM servers WHERE id = ? AND user_id = ?");
        $stmt->execute([$serverId, $user['id']]);

        error_log("DELETE Server - Servidor excluído com sucesso. Rows affected: " . $stmt->rowCount());

        Response::json([
            'success' => true,
            'message' => 'Servidor excluído com sucesso'
        ]);

    } catch (Exception $e) {
        error_log("DELETE Server - Erro: " . $e->getMessage());
        error_log("DELETE Server - Stack trace: " . $e->getTraceAsString());
        
        // Limpar buffer novamente em caso de erro
        if (ob_get_level()) {
            ob_clean();
        }
        
        Response::json([
            'success' => false,
            'error' => 'Erro ao excluir servidor: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Test Sigma connection
 */
function testSigmaConnection() {
    try {
        // Verify authentication
        $user = Auth::user();
        if (!$user) {
            Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
            return;
        }

        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);

        // Se usar token salvo, buscar do banco de dados
        if (!empty($data['use_saved_token']) && !empty($data['server_id'])) {
            $db = Database::connect();
            
            // Buscar dados do servidor
            $stmt = $db->prepare("SELECT panel_url, sigma_token, reseller_user FROM servers WHERE id = ? AND user_id = ?");
            $stmt->execute([$data['server_id'], $user['id']]);
            $server = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$server) {
                Response::json([
                    'success' => false,
                    'error' => 'Servidor não encontrado'
                ], 404);
                return;
            }
            
            // Usar dados do banco, mas permitir override da URL e usuário se fornecidos
            $panelUrl = !empty($data['panel_url']) ? $data['panel_url'] : $server['panel_url'];
            $resellerUser = !empty($data['reseller_user']) ? $data['reseller_user'] : $server['reseller_user'];
            $sigmaToken = $server['sigma_token'];
            
            if (empty($panelUrl) || empty($sigmaToken) || empty($resellerUser)) {
                Response::json([
                    'success' => false,
                    'error' => 'Dados de integração incompletos no servidor salvo'
                ], 400);
                return;
            }
            
        } else {
            // Validação normal para novos testes
            if (empty($data['panel_url']) || empty($data['sigma_token']) || empty($data['reseller_user'])) {
                Response::json([
                    'success' => false,
                    'error' => 'URL do painel, token e usuário são obrigatórios'
                ], 400);
                return;
            }
            
            $panelUrl = $data['panel_url'];
            $sigmaToken = $data['sigma_token'];
            $resellerUser = $data['reseller_user'];
        }

        $result = testSigmaConnectionHelper($panelUrl, $sigmaToken, $resellerUser);
        
        Response::json($result);

    } catch (Exception $e) {
        error_log("Error testing Sigma connection: " . $e->getMessage());
        Response::json([
            'success' => false,
            'error' => 'Erro ao testar conexão'
        ], 500);
    }
}

/**
 * Get Sigma packages
 */
function getSigmaPackages($serverId) {
    try {
        // Verify authentication
        $user = Auth::user();
        if (!$user) {
            Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
            return;
        }

        $result = getSigmaPackagesHelper($serverId);
        Response::json($result);

    } catch (Exception $e) {
        error_log("Error getting Sigma packages: " . $e->getMessage());
        Response::json([
            'success' => false,
            'error' => 'Erro ao buscar packages'
        ], 500);
    }
}
