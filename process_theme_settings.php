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

// Helper function to sanitize filename
function sanitizeFilename($filename) {
    $filename = basename($filename);
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return time() . '_' . $filename;
}

// Helper function to validate and process file upload
function processFileUpload($file, $allowed_types, $max_size, $upload_dir) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed');
    }
    
    // Validate file size
    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds maximum allowed (' . ($max_size / 1024 / 1024) . 'MB)');
    }
    
    // Validate extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
        throw new Exception('Invalid file type. Allowed: ' . implode(', ', $allowed_types));
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg'
    ];
    
    if (!isset($allowed_mimes[$ext]) || $mime !== $allowed_mimes[$ext]) {
        throw new Exception('Invalid file MIME type');
    }
    
    // Check for directory traversal
    $filename = sanitizeFilename($file['name']);
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
        throw new Exception('Invalid filename');
    }
    
    // Create upload directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $filepath = $upload_dir . '/' . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    return $filename;
}

// Helper function to validate URL
function validateUrl($url) {
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        throw new Exception('Invalid URL format');
    }
    return $url;
}

// Helper function to log audit
function logAudit($pdo, $user_id, $action_type, $table_name, $record_id, $old_values, $new_values) {
    $audit_stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action_type, table_name, record_id, old_values, new_values, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $audit_stmt->execute([
        $user_id,
        $action_type,
        $table_name,
        $record_id,
        $old_values ? json_encode($old_values) : null,
        $new_values ? json_encode($new_values) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
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
        logAudit($pdo, $user_id, 'UPDATE', 'theme_settings', 0, null, $colors);
        
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
        logAudit($pdo, $user_id, 'UPDATE', 'theme_settings', 0, null, $defaults);
        
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
        
    } elseif ($action === 'save_logo') {
        // Save logo (upload or URL)
        $logo_url = '';
        
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle file upload
            $filename = processFileUpload($_FILES['logo'], ['png'], 2 * 1024 * 1024, __DIR__ . '/uploads/branding');
            $logo_url = '/uploads/branding/' . $filename;
        } elseif (!empty($_POST['logo_url'])) {
            // Handle URL
            $logo_url = validateUrl($_POST['logo_url']);
        } else {
            throw new Exception('No logo file or URL provided');
        }
        
        $pdo->beginTransaction();
        
        // Save logo URL
        $stmt = $pdo->prepare("
            INSERT INTO theme_settings (setting_name, setting_value, updated_by)
            VALUES ('logo_url', ?, ?)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$logo_url, $user_id]);
        
        logAudit($pdo, $user_id, 'UPDATE', 'theme_settings', 0, null, ['logo_url' => $logo_url]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Logo saved successfully',
            'logo_url' => $logo_url
        ]);
        
    } elseif ($action === 'save_hero') {
        // Save hero image and content
        $hero_image_url = '';
        
        if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle file upload
            $filename = processFileUpload($_FILES['hero_image'], ['png', 'jpg', 'jpeg'], 5 * 1024 * 1024, __DIR__ . '/uploads/branding');
            $hero_image_url = '/uploads/branding/' . $filename;
        } elseif (!empty($_POST['hero_image_url'])) {
            // Handle URL
            $hero_image_url = validateUrl($_POST['hero_image_url']);
        } else {
            throw new Exception('No hero image file or URL provided');
        }
        
        $hero_title = $_POST['hero_title'] ?? '';
        $hero_subtitle = $_POST['hero_subtitle'] ?? '';
        
        $pdo->beginTransaction();
        
        // Save hero settings
        $settings = [
            'hero_image_url' => $hero_image_url,
            'hero_title' => $hero_title,
            'hero_subtitle' => $hero_subtitle
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO theme_settings (setting_name, setting_value, updated_by)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_by = VALUES(updated_by),
                updated_at = CURRENT_TIMESTAMP
        ");
        
        foreach ($settings as $name => $value) {
            $stmt->execute([$name, $value, $user_id]);
        }
        
        logAudit($pdo, $user_id, 'UPDATE', 'theme_settings', 0, null, $settings);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Hero section saved successfully',
            'hero_image_url' => $hero_image_url,
            'hero_title' => $hero_title,
            'hero_subtitle' => $hero_subtitle
        ]);
        
    } elseif ($action === 'save_program') {
        // Add or edit training program
        $program_id = $_POST['program_id'] ?? null;
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        
        // Validation
        if (empty($title)) {
            throw new Exception('Program title is required');
        }
        if (strlen($title) > 255) {
            throw new Exception('Program title must be 255 characters or less');
        }
        if (empty($description)) {
            throw new Exception('Program description is required');
        }
        
        $image_url = '';
        
        if (isset($_FILES['program_image']) && $_FILES['program_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            // Handle file upload
            $filename = processFileUpload($_FILES['program_image'], ['png', 'jpg', 'jpeg'], 5 * 1024 * 1024, __DIR__ . '/uploads/programs');
            $image_url = '/uploads/programs/' . $filename;
        } elseif (!empty($_POST['program_image_url'])) {
            // Handle URL
            $image_url = validateUrl($_POST['program_image_url']);
        }
        
        $pdo->beginTransaction();
        
        if ($program_id) {
            // Get old values for audit
            $old_stmt = $pdo->prepare("SELECT * FROM training_programs WHERE id = ?");
            $old_stmt->execute([$program_id]);
            $old_values = $old_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$old_values) {
                throw new Exception('Program not found');
            }
            
            // Update existing program
            $stmt = $pdo->prepare("
                UPDATE training_programs 
                SET title = ?, description = ?, tags = ?, display_order = ?, image_url = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $tags, $display_order, $image_url ?: $old_values['image_url'], $program_id]);
            
            $new_values = [
                'title' => $title,
                'description' => $description,
                'tags' => $tags,
                'display_order' => $display_order,
                'image_url' => $image_url ?: $old_values['image_url']
            ];
            
            logAudit($pdo, $user_id, 'UPDATE', 'training_programs', $program_id, $old_values, $new_values);
            
            $message = 'Program updated successfully';
        } else {
            // Insert new program
            $stmt = $pdo->prepare("
                INSERT INTO training_programs (title, description, tags, display_order, image_url, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $description, $tags, $display_order, $image_url, $user_id]);
            $program_id = $pdo->lastInsertId();
            
            $new_values = [
                'id' => $program_id,
                'title' => $title,
                'description' => $description,
                'tags' => $tags,
                'display_order' => $display_order,
                'image_url' => $image_url,
                'created_by' => $user_id
            ];
            
            logAudit($pdo, $user_id, 'INSERT', 'training_programs', $program_id, null, $new_values);
            
            $message = 'Program created successfully';
        }
        
        $pdo->commit();
        
        // Get updated program
        $stmt = $pdo->prepare("SELECT * FROM training_programs WHERE id = ?");
        $stmt->execute([$program_id]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'program' => $program
        ]);
        
    } elseif ($action === 'delete_program') {
        // Delete training program
        $program_id = $_POST['program_id'] ?? null;
        
        if (!$program_id) {
            throw new Exception('Program ID is required');
        }
        
        $pdo->beginTransaction();
        
        // Get program details before deletion
        $stmt = $pdo->prepare("SELECT * FROM training_programs WHERE id = ?");
        $stmt->execute([$program_id]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$program) {
            throw new Exception('Program not found');
        }
        
        // Delete from database
        $delete_stmt = $pdo->prepare("DELETE FROM training_programs WHERE id = ?");
        $delete_stmt->execute([$program_id]);
        
        // Delete uploaded file if exists
        if (!empty($program['image_url']) && strpos($program['image_url'], '/uploads/programs/') === 0) {
            $filepath = __DIR__ . $program['image_url'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        logAudit($pdo, $user_id, 'DELETE', 'training_programs', $program_id, $program, null);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Program deleted successfully'
        ]);
        
    } elseif ($action === 'get_programs') {
        // Get all training programs
        $stmt = $pdo->query("SELECT * FROM training_programs ORDER BY display_order ASC, id ASC");
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'programs' => $programs
        ]);
        
    } elseif ($action === 'get_program') {
        // Get single training program
        $program_id = $_GET['program_id'] ?? $_POST['program_id'] ?? null;
        
        if (!$program_id) {
            throw new Exception('Program ID is required');
        }
        
        $stmt = $pdo->prepare("SELECT * FROM training_programs WHERE id = ?");
        $stmt->execute([$program_id]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$program) {
            throw new Exception('Program not found');
        }
        
        echo json_encode([
            'success' => true,
            'program' => $program
        ]);
        
    } else {
        http_response_code(400);
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Set appropriate HTTP status code
    $code = http_response_code();
    if ($code === 200) {
        // Determine error type
        $message = $e->getMessage();
        if (strpos($message, 'required') !== false || strpos($message, 'Invalid') !== false) {
            http_response_code(400); // Bad request
        } elseif (strpos($message, 'not found') !== false) {
            http_response_code(404); // Not found
        } else {
            http_response_code(500); // Server error
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
