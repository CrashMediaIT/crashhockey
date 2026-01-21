<?php
/**
 * Process Theme Settings
 * Handles theme customization with security validation
 */

session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/security.php';

header('Content-Type: application/json');

// Admin check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// CSRF protection
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

// Rate limiting
if (isRateLimited('theme_settings', 10, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}

try {
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    if ($action === 'save') {
        // Validate and save theme colors
        $colors = [
            'primary_color' => $_POST['primary_color'] ?? '',
            'secondary_color' => $_POST['secondary_color'] ?? '',
            'background_color' => $_POST['background_color'] ?? '',
            'card_background_color' => $_POST['card_background_color'] ?? '',
            'text_color' => $_POST['text_color'] ?? '',
            'text_muted_color' => $_POST['text_muted_color'] ?? '',
            'border_color' => $_POST['border_color'] ?? '',
            'sidebar_color' => $_POST['sidebar_color'] ?? '',
            'button_hover_color' => $_POST['button_hover_color'] ?? '',
            'success_color' => $_POST['success_color'] ?? '',
            'error_color' => $_POST['error_color'] ?? '',
            'warning_color' => $_POST['warning_color'] ?? ''
        ];
        
        // Validate hex color codes
        foreach ($colors as $name => $value) {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                throw new Exception("Invalid color format for $name. Must be a valid hex color (e.g., #7000a4)");
            }
        }
        
        // Begin transaction
        $pdo->beginTransaction();
        
        // Save each color
        $stmt = $pdo->prepare("
            INSERT INTO theme_settings (setting_name, setting_value, updated_by)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        foreach ($colors as $name => $value) {
            $stmt->execute([$name, $value, $user_id]);
        }
        
        // Log the change
        $audit_stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action_type, table_name, record_id, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $audit_stmt->execute([
            $user_id,
            'UPDATE',
            'theme_settings',
            0, // Multiple records
            json_encode($colors),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Theme settings saved successfully'
        ]);
        
    } elseif ($action === 'reset') {
        // Reset to default colors
        $defaults = [
            'primary_color' => '#7000a4',
            'secondary_color' => '#c0c0c0',
            'background_color' => '#06080b',
            'card_background_color' => '#0d1117',
            'text_color' => '#ffffff',
            'text_muted_color' => '#94a3b8',
            'border_color' => '#1e293b',
            'sidebar_color' => '#020305',
            'button_hover_color' => '#a78bfa',
            'success_color' => '#22c55e',
            'error_color' => '#ef4444',
            'warning_color' => '#f59e0b'
        ];
        
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO theme_settings (setting_name, setting_value, updated_by)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        foreach ($defaults as $name => $value) {
            $stmt->execute([$name, $value, $user_id]);
        }
        
        // Log the reset
        $audit_stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action_type, table_name, record_id, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $audit_stmt->execute([
            $user_id,
            'UPDATE',
            'theme_settings',
            0, // Multiple records
            json_encode($defaults),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Theme reset to defaults',
            'colors' => $defaults
        ]);
        
    } elseif ($action === 'get') {
        // Get current theme settings
        $stmt = $pdo->query("SELECT setting_name, setting_value FROM theme_settings");
        $colors = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        echo json_encode([
            'success' => true,
            'colors' => $colors
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
