<?php
/**
 * Test Google Places API
 * Verifies API key and quota
 */

session_start();
require_once __DIR__ . '/db_config.php';
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
    
    // Get Google API key from settings
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'google_api_key'");
    $api_key = $stmt->fetchColumn();
    
    if (empty($api_key)) {
        throw new Exception('Google API key is not configured. Please add it in System Settings.');
    }
    
    // Test with a sample place search
    $test_query = 'hockey arena';
    $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?query=' . urlencode($test_query) . '&key=' . $api_key;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        throw new Exception('API request failed with HTTP code: ' . $http_code);
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        throw new Exception('Invalid API response');
    }
    
    // Check status
    if ($data['status'] === 'REQUEST_DENIED') {
        throw new Exception('API request denied: ' . ($data['error_message'] ?? 'Invalid API key or API not enabled'));
    }
    
    if ($data['status'] === 'OVER_QUERY_LIMIT') {
        throw new Exception('API quota exceeded. Please check your Google Cloud Console for usage limits.');
    }
    
    if ($data['status'] !== 'OK' && $data['status'] !== 'ZERO_RESULTS') {
        throw new Exception('API error: ' . $data['status'] . ' - ' . ($data['error_message'] ?? 'Unknown error'));
    }
    
    $result_count = isset($data['results']) ? count($data['results']) : 0;
    
    $message = '<strong>API test successful!</strong><br><br>';
    $message .= 'Status: ' . $data['status'] . '<br>';
    $message .= 'Test query: "' . $test_query . '"<br>';
    $message .= 'Results found: ' . $result_count . '<br>';
    
    if ($result_count > 0) {
        $message .= 'Sample result: ' . $data['results'][0]['name'] . '<br>';
    }
    
    $message .= '<br><span style="color: #00ff88;">✓ API is working correctly</span>';
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'details' => [
            'status' => $data['status'],
            'result_count' => $result_count
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'API test failed: ' . $e->getMessage()
    ]);
}
?>
