<?php
/**
 * API para gerenciamento de perfil do usuário
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/functions.php';
require_once __DIR__ . '/../app/core/Auth.php';

try {
    // Carregar variáveis de ambiente
    loadEnv(__DIR__ . '/../.env');
    
    // Iniciar sessão
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar autenticação
    $currentUser = Auth::user();
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Não autenticado']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGetProfile($currentUser);
            break;
            
        case 'PUT':
            handleUpdateProfile($currentUser);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erro na API de perfil: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erro interno do servidor',
        'debug' => $e->getMessage() // Remover em produção
    ]);
}

/**
 * Obter dados do perfil - APENAS DO BANCO DE DADOS
 */
function handleGetProfile($currentUser) {
    try {
        // Buscar dados completos do usuário pelo ID
        // Usar COALESCE para tratar colunas que podem não existir
        $user = Database::fetch("
            SELECT 
                id, 
                name, 
                email, 
                COALESCE(phone, whatsapp) as phone,
                COALESCE(company, '') as company,
                role, 
                COALESCE(is_admin, CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as is_admin,
                current_plan_id, 
                plan_expires_at, 
                created_at
            FROM users 
            WHERE id = ?
        ", [$currentUser['id']]);
        
        if (!$user) {
            throw new Exception('Usuário não encontrado');
        }
        
        // Garantir que os valores estão corretos
        $user['phone'] = $user['phone'] ?? null;
        $user['company'] = $user['company'] ?? null;
        $user['is_admin'] = isset($user['is_admin']) ? (int)$user['is_admin'] : 0;
        
        $response = [
            'success' => true,
            'user' => $user
        ];
        
        // Se não for admin, buscar informações do plano
        $isAdmin = ($user['is_admin'] == 1) || ($user['role'] === 'admin');
        
        if (!$isAdmin) {
            // Verificar se a tabela reseller_plans existe antes de fazer JOIN
            try {
                // Buscar informações do plano atual do revendedor
                $planInfo = Database::fetch("
                    SELECT 
                        rp.id as plan_id,
                        rp.name as plan_name,
                        rp.price,
                        rp.duration_days,
                        u.plan_expires_at,
                        u.current_plan_id,
                        CASE 
                            WHEN u.plan_expires_at IS NULL THEN 0
                            WHEN DATE(u.plan_expires_at) < CURDATE() THEN DATEDIFF(CURDATE(), DATE(u.plan_expires_at))
                            WHEN DATE(u.plan_expires_at) = CURDATE() THEN 0
                            ELSE DATEDIFF(DATE(u.plan_expires_at), CURDATE())
                        END as days_remaining,
                        CASE 
                            WHEN u.plan_expires_at IS NULL THEN 1
                            WHEN DATE(u.plan_expires_at) < CURDATE() THEN 1
                            ELSE 0
                        END as is_expired
                    FROM users u
                    LEFT JOIN reseller_plans rp ON u.current_plan_id = rp.id
                    WHERE u.id = ?
                ", [$user['id']]);
                
                if ($planInfo && isset($planInfo['plan_name']) && $planInfo['plan_name']) {
                    // Verificar se é trial baseado no nome do plano ou duração
                    $isTrial = false;
                    $planName = strtolower($planInfo['plan_name'] ?? '');
                    
                    if (stripos($planName, 'trial') !== false || 
                        stripos($planName, 'teste') !== false ||
                        stripos($planName, 'grátis') !== false ||
                        stripos($planName, 'gratuito') !== false ||
                        (isset($planInfo['duration_days']) && $planInfo['duration_days'] <= 7)) {
                        $isTrial = true;
                    }
                    
                    $response['plan'] = [
                        'id' => $planInfo['plan_id'] ?? null,
                        'name' => $planInfo['plan_name'] ?? 'Sem plano',
                        'price' => (float)($planInfo['price'] ?? 0),
                        'duration_days' => (int)($planInfo['duration_days'] ?? 0),
                        'expires_at' => $planInfo['plan_expires_at'] ?? null,
                        'days_remaining' => (int)($planInfo['days_remaining'] ?? 0),
                        'is_expired' => (bool)($planInfo['is_expired'] ?? true),
                        'is_trial' => $isTrial
                    ];
                } else {
                    // Revendedor sem plano ativo
                    $response['plan'] = [
                        'id' => null,
                        'name' => 'Sem plano ativo',
                        'price' => 0,
                        'duration_days' => 0,
                        'expires_at' => $user['plan_expires_at'] ?? null,
                        'days_remaining' => 0,
                        'is_expired' => true,
                        'is_trial' => false
                    ];
                }
            } catch (Exception $planError) {
                // Se der erro ao buscar plano, retornar sem informações de plano
                error_log("Erro ao buscar plano: " . $planError->getMessage());
                $response['plan'] = [
                    'id' => null,
                    'name' => 'Sem plano ativo',
                    'price' => 0,
                    'duration_days' => 0,
                    'expires_at' => $user['plan_expires_at'] ?? null,
                    'days_remaining' => 0,
                    'is_expired' => true,
                    'is_trial' => false
                ];
            }
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log("Erro ao obter perfil: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage(),
            'debug' => $e->getMessage() // Remover em produção
        ]);
    }
}

/**
 * Atualizar dados do perfil
 */
function handleUpdateProfile($currentUser) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Dados inválidos');
        }
        
        // Validar campos obrigatórios
        if (empty($input['name']) || empty($input['email'])) {
            throw new Exception('Nome e email são obrigatórios');
        }
        
        // Validar email
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }
        
        // Verificar se o email já existe (exceto para o usuário atual)
        $existingUser = Database::fetch("
            SELECT id FROM users 
            WHERE email = ? AND id != ?
        ", [$input['email'], $currentUser['id']]);
        
        if ($existingUser) {
            throw new Exception('Este email já está em uso');
        }
        
        // Atualizar dados - verificar quais colunas existem antes de atualizar
        $updateFields = ['name = ?', 'email = ?', 'updated_at = NOW()'];
        $updateValues = [$input['name'], $input['email']];
        
        // Verificar se coluna phone existe usando INFORMATION_SCHEMA
        $dbName = env('DB_NAME', 'ultragestor_php');
        $checkPhone = Database::fetch("
            SELECT COUNT(*) as col_count 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = 'users' 
            AND COLUMN_NAME = 'phone'
        ", [$dbName]);
        
        if ($checkPhone && (int)($checkPhone['col_count'] ?? 0) > 0) {
            $updateFields[] = 'phone = ?';
            $updateValues[] = $input['phone'] ?? null;
        } else {
            // Tentar whatsapp como fallback
            $checkWhatsapp = Database::fetch("
                SELECT COUNT(*) as col_count 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'users' 
                AND COLUMN_NAME = 'whatsapp'
            ", [$dbName]);
            
            if ($checkWhatsapp && (int)($checkWhatsapp['col_count'] ?? 0) > 0) {
                $updateFields[] = 'whatsapp = ?';
                $updateValues[] = $input['phone'] ?? null;
            }
        }
        
        // Verificar se coluna company existe
        $checkCompany = Database::fetch("
            SELECT COUNT(*) as col_count 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = 'users' 
            AND COLUMN_NAME = 'company'
        ", [$dbName]);
        
        if ($checkCompany && (int)($checkCompany['col_count'] ?? 0) > 0) {
            $updateFields[] = 'company = ?';
            $updateValues[] = $input['company'] ?? null;
        }
        
        $updateValues[] = $currentUser['id'];
        
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        Database::query($sql, $updateValues);
        
        // Atualizar sessão
        $_SESSION['user']['name'] = $input['name'];
        $_SESSION['user']['email'] = $input['email'];
        
        echo json_encode([
            'success' => true,
            'message' => 'Perfil atualizado com sucesso'
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao atualizar perfil: " . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>