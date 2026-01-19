<?php
// db_config.php

// 1. ENVIRONMENT LOADER FUNCTION
// Wrapped to prevent "Cannot redeclare" crashes
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) { return; }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments
            if (strpos($line, '#') === 0 || empty($line)) continue;
            
            // Parse Key=Value
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                $value = trim($value, '"\''); // Remove quotes
                $_ENV[$name] = $value;
            }
        }
    }
}

// 2. LOAD ENVIRONMENT FILE
// We prioritize the path you specified
$possible_paths = [
    '/config/crashhockey.env',      // <--- YOUR SPECIFIC PATH
    __DIR__ . '/crashhockey.env',   // Local fallback
    __DIR__ . '/.env'               // Standard fallback
];

$env_loaded = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        loadEnv($path);
        $env_loaded = true;
        break;
    }
}

// 3. DB CONNECTION PARAMETERS
// Defaults are provided just in case env fails completely
$host = $_ENV['DB_HOST'] ?? 'localhost';
$db   = $_ENV['DB_NAME'] ?? 'crashhockey';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If connection fails, show a clear error
    die("Database Connection Failed. <br>Checked Env: " . ($env_loaded ? "Yes" : "No") . "<br>Error: " . $e->getMessage());
}
?>