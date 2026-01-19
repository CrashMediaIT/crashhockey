<?php
// process_settings.php - Handle system settings updates
session_start();
require 'db_config.php';
require 'security.php';
require 'cloud_config.php';

setSecurityHeaders();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {
    checkCsrfToken();
    
    switch ($action) {
        case 'update_nextcloud':
            $url = trim($_POST['nextcloud_url']);
            $username = trim($_POST['nextcloud_username']);
            $password = trim($_POST['nextcloud_password']);
            $folder = trim($_POST['nextcloud_receipt_folder']);
            $enabled = isset($_POST['receipt_scanning_enabled']) ? '1' : '0';
            
            updateSetting($pdo, 'nextcloud_url', $url);
            updateSetting($pdo, 'nextcloud_username', $username);
            updateSetting($pdo, 'nextcloud_password', $password);
            updateSetting($pdo, 'nextcloud_receipt_folder', $folder);
            updateSetting($pdo, 'receipt_scanning_enabled', $enabled);
            
            echo json_encode(['success' => true, 'message' => 'Nextcloud settings updated']);
            break;
            
        case 'test_nextcloud':
            $settings = [
                'nextcloud_url' => trim($_POST['nextcloud_url']),
                'nextcloud_username' => trim($_POST['nextcloud_username']),
                'nextcloud_password' => trim($_POST['nextcloud_password']),
                'nextcloud_receipt_folder' => trim($_POST['nextcloud_receipt_folder'])
            ];
            
            $result = testNextcloudConnection($settings);
            echo json_encode($result);
            break;
            
        case 'update_google_maps':
            $api_key = trim($_POST['google_maps_api_key']);
            updateSetting($pdo, 'google_maps_api_key', $api_key);
            
            header('Location: dashboard.php?page=settings&success=1');
            exit;
            
        case 'update_mileage_rates':
            $rate_km = floatval($_POST['mileage_rate_per_km']);
            $rate_mile = floatval($_POST['mileage_rate_per_mile']);
            
            updateSetting($pdo, 'mileage_rate_per_km', $rate_km);
            updateSetting($pdo, 'mileage_rate_per_mile', $rate_mile);
            
            header('Location: dashboard.php?page=settings&success=1');
            exit;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Update or insert a system setting
 */
function updateSetting($pdo, $key, $value) {
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    $stmt->execute([$key, $value, $value]);
}
?>
