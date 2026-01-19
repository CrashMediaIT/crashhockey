<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['logged_in'])) { header("Location: login.php"); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $workout_id = $_POST['workout_id'];
    $current    = $_POST['current_status'];
    $user_id    = $_SESSION['user_id'];

    // Toggle: If 1, make 0. If 0, make 1.
    $new_status = ($current == 1) ? 0 : 1;

    try {
        // Security: Ensure the workout actually belongs to this user before updating
        $stmt = $pdo->prepare("UPDATE workouts SET is_completed = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$new_status, $workout_id, $user_id]);

        header("Location: dashboard.php?page=workouts");
        exit();

    } catch (PDOException $e) {
        die("Error updating workout: " . $e->getMessage());
    }
}
?>