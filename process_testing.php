<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'coach')) {
    header("Location: dashboard.php"); exit();
}

// DELETE ACTION
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $id = $_POST['id'];
    $pdo->prepare("DELETE FROM testing_results WHERE id = ?")->execute([$id]);
    header("Location: dashboard.php?page=testing");
    exit();
}

// INSERT ACTION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $cat     = $_POST['category'];
    $test    = trim($_POST['test_name']);
    $date    = $_POST['test_date'];
    
    $weight = null;
    $sets   = null;
    $reps   = null;
    $time   = null;

    if ($cat == 'Cardio') {
        $time = $_POST['time_result'];
        $reps = !empty($_POST['reps_cardio']) ? $_POST['reps_cardio'] : null;
    } else {
        // Weights
        $weight = $_POST['weight'];
        $reps   = $_POST['reps_weight'];
        $sets   = $_POST['sets'];
    }

    try {
        $sql = "INSERT INTO testing_results (user_id, category, test_name, weight, reps, sets, time_result, test_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $cat, $test, $weight, $reps, $sets, $time, $date]);
        
        header("Location: dashboard.php?page=testing&status=recorded");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>