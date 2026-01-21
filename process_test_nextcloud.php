<?php
/**
 * Test Nextcloud Connection
 * Tests WebDAV connection and folder access
 */

session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/cloud_config.php';
require_once __DIR__ . '/security.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        throw new Exception('Invalid CSRF token');
    }
    
    // Get Nextcloud settings
    $settings = getNextcloudSettings($pdo);
    
    if (empty($settings['nextcloud_url']) || empty($settings['nextcloud_username']) || empty($settings['nextcloud_password'])) {
        throw new Exception('Nextcloud settings are incomplete. Please configure Nextcloud settings first.');
    }
    
    // Connect to Nextcloud
    $connection = connectNextcloud($settings);
    
    // Test connection to main folder
    $receipt_folder = $settings['nextcloud_receipt_folder'] ?? '/receipts';
    
    // Test if we can list files
    try {
        $files = listNextcloudFiles($connection, $receipt_folder);
        $file_count = count($files);
    } catch (Exception $e) {
        throw new Exception('Cannot access receipt folder: ' . $e->getMessage());
    }
    
    // Test if backup folder exists or can be created
    $backup_folder = '/CrashHockey/Backups';
    try {
        $backup_files = listNextcloudFiles($connection, $backup_folder);
        $backup_accessible = true;
    } catch (Exception $e) {
        $backup_accessible = false;
    }
    
    // Test write permissions
    $test_content = 'Test file created at ' . date('Y-m-d H:i:s');
    $test_filename = '/CrashHockey/test_' . time() . '.txt';
    
    $webdav_url = $connection['url'] . '/remote.php/dav/files/' . $connection['username'] . $test_filename;
    
    $ch = curl_init($webdav_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, $connection['username'] . ':' . $connection['password']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $test_content);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $write_permissions = ($http_code === 201 || $http_code === 204);
    
    // Delete test file
    if ($write_permissions) {
        $ch = curl_init($webdav_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $connection['username'] . ':' . $connection['password']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
    }
    
    $message = '<strong>Connection successful!</strong><br><br>';
    $message .= 'Receipt folder: ' . $file_count . ' files found<br>';
    $message .= 'Backup folder: ' . ($backup_accessible ? 'Accessible' : 'Not found (will be created on first backup)') . '<br>';
    $message .= 'Write permissions: ' . ($write_permissions ? '<span style="color: #00ff88;">✓ OK</span>' : '<span style="color: #ff4444;">✗ Failed</span>');
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'details' => [
            'receipt_files' => $file_count,
            'backup_accessible' => $backup_accessible,
            'write_permissions' => $write_permissions
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Connection failed: ' . $e->getMessage()
    ]);
}
?>
