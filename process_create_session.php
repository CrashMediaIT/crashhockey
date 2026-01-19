<?php
session_start();
require 'db_config.php';
require 'security.php';
require 'notifications.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'coach' && $_SESSION['user_role'] != 'coach_plus')) {
    header("Location: dashboard.php"); exit();
}

// Set security headers
setSecurityHeaders();

// Validate CSRF token
checkCsrfToken();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type      = $_POST['session_type'];
    $type_category = $_POST['session_type_category'] ?? 'group';
    $title     = trim($_POST['title']);
    $plan_text = trim($_POST['session_plan'] ?? '');
    $practice_plan_id = !empty($_POST['practice_plan_id']) ? intval($_POST['practice_plan_id']) : null;
    $date      = $_POST['date'];
    $time      = $_POST['time'];
    $price     = !empty($_POST['price']) ? floatval($_POST['price']) : 0;
    $capacity  = !empty($_POST['capacity']) ? intval($_POST['capacity']) : 20;
    $max_athletes = !empty($_POST['max_athletes']) ? intval($_POST['max_athletes']) : null;
    
    // FETCH LOCATION DETAILS from ID
    $loc_id = $_POST['location_id'];
    $stmt = $pdo->prepare("SELECT * FROM locations WHERE id = ?");
    $stmt->execute([$loc_id]);
    $loc = $stmt->fetch();
    
    if (!$loc) {
        header("Location: dashboard.php?page=create_session&error=invalid_location");
        exit();
    }
    
    $arena = $loc['name'];
    $city  = $loc['city'];

    try {
        $sql = "INSERT INTO sessions 
                (session_type, session_type_category, title, session_plan, practice_plan_id, session_date, 
                 session_time, max_capacity, max_athletes, price, arena, city) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$type, $type_category, $title, $plan_text, $practice_plan_id, $date, $time, 
                       $capacity, $max_athletes, $price, $arena, $city]);
        
        $session_id = $pdo->lastInsertId();
        
        // If practice plan was assigned, notify booked athletes
        if ($practice_plan_id) {
            notifyPracticePlanAssignment($pdo, $session_id, $practice_plan_id);
        }

        header("Location: dashboard.php?page=create_session&status=created");
        exit();
    } catch (PDOException $e) {
        header("Location: dashboard.php?page=create_session&error=create_failed");
        exit();
    }
}
?>