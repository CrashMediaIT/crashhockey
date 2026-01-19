<?php
session_start();
require 'db_config.php';
require 'security.php';
require 'notifications.php';

// Security: Coaches Only
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'coach' && $_SESSION['user_role'] != 'coach_plus')) {
    header("Location: dashboard.php"); exit();
}

// Set security headers
setSecurityHeaders();

// Validate CSRF token
checkCsrfToken();

$coach_id = $_SESSION['user_id'];
$user_id  = $_POST['user_id']; // The athlete
$action   = $_POST['action'];

// Get coach name for notifications
$coach_stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$coach_stmt->execute([$coach_id]);
$coach = $coach_stmt->fetch();
$coach_name = $coach ? $coach['first_name'] . ' ' . $coach['last_name'] : 'Coach';

if ($action == 'add_note') {
    $content = $_POST['note_content'];
    $private = isset($_POST['is_private']) ? 1 : 0;
    
    $stmt = $pdo->prepare("INSERT INTO athlete_notes (user_id, coach_id, note_content, is_private) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $coach_id, $content, $private]);
    
    // Notify athlete (only for public notes)
    notifyNewNote($pdo, $user_id, $coach_name, $private);
}

if ($action == 'assign_workout') {
    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $link  = $_POST['link'];
    
    $stmt = $pdo->prepare("INSERT INTO workouts (user_id, coach_id, title, description, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $coach_id, $title, $desc, $link]);
    $workout_id = $pdo->lastInsertId();
    
    // Notify athlete
    notifyWorkoutAssignment($pdo, $user_id, $workout_id, $coach_name);
}

if ($action == 'assign_nutrition') {
    $title   = $_POST['title'];
    $content = $_POST['content'];
    
    $stmt = $pdo->prepare("INSERT INTO nutrition_plans (user_id, coach_id, title, content) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $coach_id, $title, $content]);
    $plan_id = $pdo->lastInsertId();
    
    // Notify athlete
    notifyNutritionAssignment($pdo, $user_id, $plan_id, $coach_name);
}

if ($action == 'assign_coach') {
    // Admin or coach_plus can assign coaches to athletes
    requirePermission($pdo, $coach_id, $_SESSION['user_role'], 'edit_athlete_profiles');
    
    $athlete_id = intval($_POST['athlete_id']);
    $new_coach_id = !empty($_POST['coach_id']) ? intval($_POST['coach_id']) : null;
    
    $stmt = $pdo->prepare("UPDATE users SET assigned_coach_id = ? WHERE id = ? AND role = 'athlete'");
    $stmt->execute([$new_coach_id, $athlete_id]);
    
    header("Location: dashboard.php?page=athletes&msg=coach_assigned");
    exit();
}

// Redirect back to that specific athlete's page
header("Location: dashboard.php?page=athlete_detail&id=" . $user_id . "&msg=success");
?>