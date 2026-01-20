<?php
session_start();
require 'db_config.php';
require 'security.php';

// CSRF protection
if (!isset($_POST['csrf_token']) || !csrfTokenValidate($_POST['csrf_token'])) {
    die("CSRF token validation failed");
}

// Ensure user is logged in
if (!isset($_SESSION['logged_in'])) { 
    header("Location: login.php"); 
    exit(); 
}

$current_user_id = $_SESSION['user_id'];
$role = $_SESSION['user_role'];

try {
    // Update basic information
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
    $stmt->execute([$first_name, $last_name, $email, $current_user_id]);
    
    // Update athlete-specific fields
    if ($role === 'athlete') {
        $position = $_POST['position'] ?? null;
        $birth_date = $_POST['birth_date'] ?? null;
        $primary_arena = trim($_POST['primary_arena'] ?? '');
        $weight = $_POST['weight'] ? intval($_POST['weight']) : null;
        $height = $_POST['height'] ? intval($_POST['height']) : null;
        $shooting_hand = $_POST['shooting_hand'] ?? null;
        $catching_hand = $_POST['catching_hand'] ?? null;
        
        // Validate ENUM values against database schema
        $valid_positions = ['forward', 'defense', 'goalie', ''];
        $valid_shooting_hands = ['left', 'right', 'ambidextrous', ''];
        $valid_catching_hands = ['regular', 'full_right', ''];
        
        if (!in_array($position, $valid_positions, true)) {
            $position = null;
        }
        if (!in_array($shooting_hand, $valid_shooting_hands, true)) {
            $shooting_hand = null;
        }
        if (!in_array($catching_hand, $valid_catching_hands, true)) {
            $catching_hand = null;
        }
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET position = ?, birth_date = ?, primary_arena = ?, 
                weight = ?, height = ?, shooting_hand = ?, catching_hand = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $position ?: null, $birth_date, $primary_arena, 
            $weight, $height, $shooting_hand ?: null, $catching_hand ?: null,
            $current_user_id
        ]);
    }
    
    // Update email notifications
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE users SET email_notifications = ? WHERE id = ?");
    $stmt->execute([$email_notifications, $current_user_id]);
    
    // Handle password change if provided
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        // Verify current password
        $user_stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $user_stmt->execute([$current_user_id]);
        $user = $user_stmt->fetch();
        
        if (password_verify($_POST['current_password'], $user['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $new_hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$new_hash, $current_user_id]);
                header("Location: dashboard.php?page=profile&msg=password_updated");
                exit();
            } else {
                header("Location: dashboard.php?page=profile&error=passwords_dont_match");
                exit();
            }
        } else {
            header("Location: dashboard.php?page=profile&error=incorrect_password");
            exit();
        }
    }
    
    header("Location: dashboard.php?page=profile&msg=updated");
    exit();
    
} catch (PDOException $e) {
    error_log("Profile update error: " . $e->getMessage());
    header("Location: dashboard.php?page=profile&error=update_failed");
    exit();
}
?>