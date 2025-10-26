<?php
/**
 * API Endpoints - Servers
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

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
                COUNT(c.id) as connected_clients
            FROM servers s
            LEFT JOIN clients c ON c.server = s.name AND c.reseller_id = s.user_id
            WHERE s.user_id = ? 
            GROUP BY s.id, s.name, s.billing_type, s.cost, s.panel_type, s.panel_url, s.reseller_user, s.status, s.created_at
            ORDER BY s.created_at DESC
        ");
        
        $stmt->execute([$user['id']]);
        $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate total cost based on billing type
        foreach ($servers as &$server) {
            $server['total_cost'] = $server['billing_type'] === 'per_active' 
                ? $server['cost'] * $server['connected_clients']
                : $server['cost'];
            
            // Keep original cost as number for calculations
            $server['cost'] = (float)$server['cost'];
            $server['total_cost'] = (float)$server['total_cost'];
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
        // Se sigma_token não for fornecido ou for vazio, manter o existente
        if (!empty($data['sigma_token'])) {
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
                $data['sigma_token'],
                $serverId,
                $user['id']
            ]);
        } else {
            // Não atualizar o token
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
    try {
        // Verify authentication
        $user = Auth::user();
        if (!$user) {
            Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
            return;
        }

        $db = Database::connect();
        
        // Verify ownership and delete
        $stmt = $db->prepare("DELETE FROM servers WHERE id = ? AND user_id = ?");
        $stmt->execute([$serverId, $user['id']]);

        if ($stmt->rowCount() === 0) {
            Response::json([
                'success' => false,
                'error' => 'Servidor não encontrado'
            ], 404);
            return;
        }

        Response::json([
            'success' => true,
            'message' => 'Servidor excluído com sucesso'
        ]);

    } catch (Exception $e) {
        error_log("Error deleting server: " . $e->getMessage());
        Response::json([
            'success' => false,
            'error' => 'Erro ao excluir servidor'
        ], 500);
    }
}
