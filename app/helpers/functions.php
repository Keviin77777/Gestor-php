<?php
/**
 * Funções auxiliares globais
 */

/**
 * Carregar variáveis de ambiente do arquivo .env
 */
function loadEnv($path) {
    // Se for um diretório, adicionar /.env
    if (is_dir($path)) {
        $path = rtrim($path, '/\\') . '/.env';
    }
    
    if (!file_exists($path)) {
        return;
    }
    
    // Verificar se é um arquivo válido
    if (!is_file($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($lines === false) {
        return;
    }
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') === false) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

/**
 * Obter variável de ambiente
 */
function env($key, $default = null) {
    return $_ENV[$key] ?? $default;
}

/**
 * Sanitizar string removendo tags HTML
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar telefone brasileiro
 */
function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 11;
}

/**
 * Formatar telefone para WhatsApp (55 + DDD + número)
 */
function formatPhoneForWhatsApp($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 11) {
        return '55' . $phone;
    } elseif (strlen($phone) === 10) {
        return '55' . $phone;
    }
    
    return $phone;
}

/**
 * Formatar data para exibição
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date)) return '';
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Formatar valor monetário
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Gerar UUID v4
 */
function generateUuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Calcular dias até uma data
 */
function daysUntil($date) {
    $now = new DateTime();
    $target = new DateTime($date);
    $diff = $now->diff($target);
    return $diff->days * ($diff->invert ? -1 : 1);
}

/**
 * Verificar se data está expirada
 */
function isExpired($date) {
    return strtotime($date) < time();
}

/**
 * Log de erro
 */
function logError($message, $context = []) {
    $logDir = __DIR__ . '/../../storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/error-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextStr\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Debug helper
 */
function dd(...$vars) {
    echo '<pre>';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    die();
}

/**
 * Validar token JWT simples
 */
function validateToken($token) {
    try {
        require_once __DIR__ . '/../core/Database.php';
        $pdo = Database::connect();
        
        // Buscar usuário pelo token (implementação simples)
        $stmt = $pdo->prepare("
            SELECT id, name, email 
            FROM users 
            WHERE remember_token = ? AND status = 'active'
        ");
        $stmt->execute([$token]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}