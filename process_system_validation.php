<?php
/**
 * Process System Validation
 * Executes validation checks and returns results as JSON
 */

session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/admin/system_validator.php';

setSecurityHeaders();

// Only admins can run validation
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die(json_encode([
        'success' => false,
        'error' => 'Access denied. Admin privileges required.'
    ]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrfToken();
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'run_validation':
                // Initialize validator
                $validator = new SystemValidator($pdo, __DIR__);
                
                // Run all checks
                $results = $validator->runAllChecks();
                
                // Return results
                echo json_encode([
                    'success' => true,
                    'results' => $results
                ]);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}
