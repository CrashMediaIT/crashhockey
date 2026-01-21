<?php
/**
 * Process System Notifications
 * Handles CRUD operations for global system notifications
 */

session_start();
require_once 'db_config.php';
require_once 'security.php';
require_once 'mailer.php';

// Set security headers
setSecurityHeaders();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

// Validate CSRF token
checkCsrfToken();

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'create' || $action === 'update') {
        $title = trim($_POST['title']);
        $message = trim($_POST['message']);
        $notification_type = $_POST['notification_type'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'] ?? null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $send_email = isset($_POST['send_email']) ? 1 : 0;
        
        // Validation
        if (empty($title) || empty($message)) {
            throw new Exception('Title and message are required');
        }
        
        if (!in_array($notification_type, ['maintenance', 'update', 'alert'])) {
            throw new Exception('Invalid notification type');
        }
        
        // Convert datetime to MySQL format
        $start_time = date('Y-m-d H:i:s', strtotime($start_time));
        if ($end_time) {
            $end_time = date('Y-m-d H:i:s', strtotime($end_time));
        }
        
        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO system_notifications 
                (title, message, notification_type, start_time, end_time, is_active, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $title,
                $message,
                $notification_type,
                $start_time,
                $end_time,
                $is_active,
                $user_id
            ]);
            
            $notification_id = $pdo->lastInsertId();
            $success_message = 'System notification created successfully';
            
        } else {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("
                UPDATE system_notifications 
                SET title = ?, 
                    message = ?, 
                    notification_type = ?, 
                    start_time = ?, 
                    end_time = ?, 
                    is_active = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $title,
                $message,
                $notification_type,
                $start_time,
                $end_time,
                $is_active,
                $id
            ]);
            
            $notification_id = $id;
            $success_message = 'System notification updated successfully';
        }
        
        // Send email notifications if requested
        if ($send_email && $is_active) {
            try {
                // Get all users with email notifications enabled
                $users_stmt = $pdo->query("
                    SELECT id, email, CONCAT(first_name, ' ', last_name) as name
                    FROM users 
                    WHERE email_notifications = 1 
                    AND (is_deleted = 0 OR is_deleted IS NULL)
                    AND is_verified = 1
                ");
                $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $email_count = 0;
                foreach ($users as $user) {
                    // Create notification in database
                    $notif_stmt = $pdo->prepare("
                        INSERT INTO notifications 
                        (user_id, type, title, message, read_status, created_at)
                        VALUES (?, 'system_notification', ?, ?, 0, NOW())
                    ");
                    $notif_stmt->execute([$user['id'], $title, $message]);
                    
                    // Send email
                    $email_body = "
                        <h2>{$title}</h2>
                        <p>" . nl2br(htmlspecialchars($message)) . "</p>
                        <hr>
                        <p><strong>Type:</strong> " . ucfirst($notification_type) . "</p>
                        <p><strong>Effective:</strong> " . date('M j, Y g:i A', strtotime($start_time)) . "</p>
                    ";
                    
                    if ($end_time) {
                        $email_body .= "<p><strong>Until:</strong> " . date('M j, Y g:i A', strtotime($end_time)) . "</p>";
                    }
                    
                    try {
                        sendEmail($user['email'], $title, $email_body);
                        $email_count++;
                    } catch (Exception $e) {
                        error_log("Failed to send notification email to {$user['email']}: " . $e->getMessage());
                    }
                }
                
                $success_message .= " (Sent to {$email_count} users)";
                
            } catch (Exception $e) {
                error_log("Email notification error: " . $e->getMessage());
                $success_message .= " (Email notifications failed)";
            }
        }
        
        // Create audit log
        $audit_stmt = $pdo->prepare("
            INSERT INTO audit_logs 
            (user_id, action_type, table_name, record_id, new_values, ip_address, user_agent, created_at)
            VALUES (?, ?, 'system_notifications', ?, ?, ?, ?, NOW())
        ");
        
        $audit_data = json_encode([
            'title' => $title,
            'type' => $notification_type,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'is_active' => $is_active,
            'send_email' => $send_email
        ]);
        
        $audit_stmt->execute([
            $user_id,
            $action === 'create' ? 'INSERT' : 'UPDATE',
            $notification_id,
            $audit_data,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => $success_message
        ]);
        
    } else if ($action === 'toggle_active') {
        $id = intval($_POST['id']);
        
        $stmt = $pdo->prepare("
            UPDATE system_notifications 
            SET is_active = NOT is_active,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification status updated'
        ]);
        
    } else if ($action === 'delete') {
        $id = intval($_POST['id']);
        
        // Get notification data for audit log
        $stmt = $pdo->prepare("SELECT * FROM system_notifications WHERE id = ?");
        $stmt->execute([$id]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$notification) {
            throw new Exception('Notification not found');
        }
        
        // Delete notification
        $delete_stmt = $pdo->prepare("DELETE FROM system_notifications WHERE id = ?");
        $delete_stmt->execute([$id]);
        
        // Create audit log
        $audit_stmt = $pdo->prepare("
            INSERT INTO audit_logs 
            (user_id, action_type, table_name, record_id, old_values, ip_address, user_agent, created_at)
            VALUES (?, 'DELETE', 'system_notifications', ?, ?, ?, ?, NOW())
        ");
        
        $audit_stmt->execute([
            $user_id,
            $id,
            json_encode($notification),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
