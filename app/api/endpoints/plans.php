<?php
/**
 * API Endpoints - Plans
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Response.php';

/**
 * Get all plans for authenticated user
 */
function getPlans() {
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
                p.id,
                p.name,
                p.description,
                p.price,
                p.duration_days,
                p.max_screens,
                p.features,
                p.status,
                p.server_id,
                s.name as server_name,
                s.status as server_status,
                p.created_at,
                p.updated_at
            FROM plans p
            LEFT JOIN servers s ON p.server_id = s.id
            WHERE p.user_id = ? 
            ORDER BY s.name ASC, p.name ASC
        ");
        
        $stmt->execute([$user['id']]);
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse features JSON
        foreach ($plans as &$plan) {
            $plan['features'] = json_decode($plan['features'], true) ?: [];
        }

        Response::json([
            'success' => true,
            'plans' => $plans
        ]);

    } catch (Exception $e) {
        error_log("Error fetching plans: " . $e->getMessage());
        Response::json([
            'success' => false,
            'error' => 'Erro ao buscar planos'
        ], 500);
    }
}

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
                id,
                name,
                status,
                billing_type,
                cost
            FROM servers
            WHERE user_id = ? AND status = 'active'
            ORDER BY name ASC
        ");
        
        $stmt->execute([$user['id']]);
        $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
 * Create new plan
 */
function createPlan() {
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
        if (empty($data['name']) || empty($data['price']) || empty($data['server_id'])) {
            Response::json([
                'success' => false,
                'error' => 'Campos obrigatórios não preenchidos'
            ], 400);
            return;
        }

        $db = Database::connect();
        
        // Verify server ownership
        $stmt = $db->prepare("SELECT id FROM servers WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['server_id'], $user['id']]);
        
        if (!$stmt->fetch()) {
            Response::json([
                'success' => false,
                'error' => 'Servidor não encontrado'
            ], 404);
            return;
        }

        // Prepare features
        $features = [];
        if (!empty($data['features'])) {
            $features = $data['features'];
        }

        $stmt = $db->prepare("
            INSERT INTO plans (
                user_id,
                server_id,
                name,
                description,
                price,
                duration_days,
                max_screens,
                features,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user['id'],
            $data['server_id'],
            $data['name'],
            $data['description'] ?? '',
            $data['price'],
            $data['duration_days'] ?? 30,
            $data['max_screens'] ?? 1,
            json_encode($features),
            $data['status'] ?? 'active'
        ]);

        $planId = $db->lastInsertId();

        Response::json([
            'success' => true,
            'message' => 'Plano criado com sucesso',
            'plan_id' => $planId
        ]);

    } catch (Exception $e) {
        error_log("Error creating plan: " . $e->getMessage());
        Response::json([
            'success' => false,
            'error' => 'Erro ao criar plano'
        ], 500);
    }
}

/**
 * Update plan
 */
function updatePlan($planId) {
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
        if (empty($data['name']) || empty($data['price']) || empty($data['server_id'])) {
            Response::json([
                'success' => false,
                'error' => 'Campos obrigatórios não preenchidos'
            ], 400);
            return;
        }

        $db = Database::connect();
        
        // Verify ownership
        $stmt = $db->prepare("SELECT id FROM plans WHERE id = ? AND user_id = ?");
        $stmt->execute([$planId, $user['id']]);
        
        if (!$stmt->fetch()) {
            Response::json([
                'success' => false,
                'error' => 'Plano não encontrado'
            ], 404);
            return;
        }

        // Verify server ownership
        $stmt = $db->prepare("SELECT id FROM servers WHERE id = ? AND user_id = ?");
        $stmt->execute([$data['server_id'], $user['id']]);
        
        if (!$stmt->fetch()) {
            Response::json([
                'success' => false,
                'error' => 'Servidor não encontrado'
            ], 404);
            return;
        }

        // Prepare features
        $features = [];
        if (!empty($data['features'])) {
            $features = $data['features'];
        }

        // Update plan
        $stmt = $db->prepare("
            UPDATE plans SET
                server_id = ?,
                name = ?,
                description = ?,
                price = ?,
                duration_days = ?,
                max_screens = ?,
                features = ?,
                status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([
            $data['server_id'],
            $data['name'],
            $data['description'] ?? '',
            $data['price'],
            $data['duration_days'] ?? 30,
            $data['max_screens'] ?? 1,
            json_encode($features),
            $data['status'] ?? 'active',
            $planId,
            $user['id']
        ]);

        Response::json([
            'success' => true,
            'message' => 'Plano atualizado com sucesso'
        ]);

    } catch (Exception $e) {
        error_log("Error updating plan: " . $e->getMessage());
        Response::json([
            'success' => false,
            'error' => 'Erro ao atualizar plano'
        ], 500);
    }
}

/**
 * Delete plan
 */
function deletePlan($planId) {
    try {
        // Verify authentication
        $user = Auth::user();
        if (!$user) {
            Response::json(['success' => false, 'error' => 'Não autorizado'], 401);
            return;
        }

        $db = Database::connect();
        
        // Verify ownership and delete
        $stmt = $db->prepare("DELETE FROM plans WHERE id = ? AND user_id = ?");
        $stmt->execute([$planId, $user['id']]);

        if ($stmt->rowCount() === 0) {
            Response::json([
                'success' => false,
                'error' => 'Plano não encontrado'
            ], 404);
            return;
        }

        Response::json([
            'success' => true,
            'message' => 'Plano excluído com sucesso'
        ]);

    } catch (Exception $e) {
        error_log("Error deleting plan: " . $e->getMessage());
        Response::json([
            'success' => false,
            'error' => 'Erro ao excluir plano'
        ], 500);
    }
}
?>
