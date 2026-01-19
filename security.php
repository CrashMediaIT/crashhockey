<?php
/**
 * Security Utilities
 * Provides CSRF protection, rate limiting, and other security features
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate CSRF Token
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF Token
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF Token HTML Input
 */
function csrfTokenInput() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Check and validate CSRF token from POST request
 */
function checkCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCsrfToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed. Please try again.');
        }
    }
}

/**
 * Rate Limiting
 * Returns true if rate limit exceeded, false otherwise
 */
function isRateLimited($action, $max_attempts = 5, $timeframe = 300) {
    $key = 'rate_limit_' . $action . '_' . $_SERVER['REMOTE_ADDR'];
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start_time' => time()];
    }
    
    $data = $_SESSION[$key];
    $elapsed = time() - $data['start_time'];
    
    // Reset if timeframe passed
    if ($elapsed > $timeframe) {
        $_SESSION[$key] = ['count' => 1, 'start_time' => time()];
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    
    // Check if limit exceeded
    if ($_SESSION[$key]['count'] > $max_attempts) {
        return true;
    }
    
    return false;
}

/**
 * Sanitize output (XSS protection)
 */
function sanitizeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize filename
 */
function sanitizeFilename($filename) {
    // Remove any path components
    $filename = basename($filename);
    // Remove special characters except dots, dashes, underscores
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}

/**
 * Check if user has permission
 */
function hasPermission($pdo, $user_id, $user_role, $permission_key) {
    // Admin always has all permissions
    if ($user_role === 'admin') {
        return true;
    }
    
    try {
        // Check user-specific permission override first
        $stmt = $pdo->prepare("
            SELECT up.granted 
            FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ? AND p.permission_key = ?
        ");
        $stmt->execute([$user_id, $permission_key]);
        $user_perm = $stmt->fetch();
        
        if ($user_perm !== false) {
            return (bool)$user_perm['granted'];
        }
        
        // Fall back to role permission
        $stmt = $pdo->prepare("
            SELECT rp.granted 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role = ? AND p.permission_key = ?
        ");
        $stmt->execute([$user_role, $permission_key]);
        $role_perm = $stmt->fetch();
        
        if ($role_perm !== false) {
            return (bool)$role_perm['granted'];
        }
        
        // No permission found
        return false;
        
    } catch (PDOException $e) {
        // On error, deny permission
        return false;
    }
}

/**
 * Require permission (redirect if not granted)
 */
function requirePermission($pdo, $user_id, $user_role, $permission_key, $redirect = 'dashboard.php') {
    if (!hasPermission($pdo, $user_id, $user_role, $permission_key)) {
        header("Location: $redirect?error=permission_denied");
        exit();
    }
}

/**
 * Set security headers
 */
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Content Type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; connect-src 'self' https:;");
    
    // HTTPS enforcement (uncomment when using HTTPS)
    // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

/**
 * Generate share token for practice plans
 */
function generateShareToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Password strength check
 */
function isStrongPassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

/**
 * Log security event
 */
function logSecurityEvent($pdo, $event_type, $description, $user_id = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO security_logs (event_type, description, user_id, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $event_type,
            $description,
            $user_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        // Silently fail if logging table doesn't exist yet
    }
}
