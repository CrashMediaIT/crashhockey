<?php
/**
 * Process Athlete Switch for Parents
 * Allows parents to switch between viewing different athletes they manage
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a parent
if (!isset($_SESSION['logged_in']) || $_SESSION['user_role'] !== 'parent') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$athlete_id = $input['athlete_id'] ?? null;

if (!$athlete_id) {
    echo json_encode(['success' => false, 'error' => 'No athlete ID provided']);
    exit();
}

require_once __DIR__ . '/db_config.php';

try {
    // Verify this athlete belongs to this parent
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ? AND parent_id = ? AND role = 'athlete'");
    $stmt->execute([$athlete_id, $_SESSION['user_id']]);
    $athlete = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$athlete) {
        echo json_encode(['success' => false, 'error' => 'Athlete not found or not authorized']);
        exit();
    }
    
    // Update session with selected athlete
    $_SESSION['viewing_athlete_id'] = $athlete['id'];
    $_SESSION['viewing_athlete_name'] = $athlete['first_name'] . ' ' . $athlete['last_name'];
    
    echo json_encode([
        'success' => true,
        'athlete' => [
            'id' => $athlete['id'],
            'name' => $athlete['first_name'] . ' ' . $athlete['last_name']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Error switching athlete: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
