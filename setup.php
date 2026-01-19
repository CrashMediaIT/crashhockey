<?php
/**
 * Database Setup Page
 * - Creates encrypted database configuration
 * - Initializes database tables
 * - Should only be run once during initial setup
 */

// Prevent re-running if already configured
$config_file = __DIR__ . '/crashhockey.env';
$lock_file = __DIR__ . '/.setup_complete';

if (file_exists($lock_file)) {
    die('Setup has already been completed. If you need to reconfigure, delete the .setup_complete file.');
}

$error = '';
$success = false;
$step = isset($_POST['step']) ? $_POST['step'] : 1;

// Step 1: Collect database credentials
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 1) {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_name = trim($_POST['db_name'] ?? 'crashhockey');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $encryption_key = $_POST['encryption_key'] ?? '';
    
    if (empty($db_user)) {
        $error = 'Database username is required.';
    } elseif (empty($encryption_key) || strlen($encryption_key) < 32) {
        $error = 'Encryption key must be at least 32 characters long.';
    } else {
        // Test database connection
        try {
            $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create database if it doesn't exist
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");
            
            // Encrypt credentials using AES-256-CBC
            $encryption_key_hash = hash('sha256', $encryption_key, true);
            $iv = openssl_random_pseudo_bytes(16);
            
            $encrypted_pass = openssl_encrypt($db_pass, 'AES-256-CBC', $encryption_key_hash, 0, $iv);
            $encrypted_data = base64_encode($iv . '::' . $encrypted_pass);
            
            // Create .env file with encrypted password
            $env_content = "# Crash Hockey Configuration\n";
            $env_content .= "# Generated: " . date('Y-m-d H:i:s') . "\n\n";
            $env_content .= "DB_HOST=$db_host\n";
            $env_content .= "DB_NAME=$db_name\n";
            $env_content .= "DB_USER=$db_user\n";
            $env_content .= "DB_PASS_ENCRYPTED=$encrypted_data\n";
            $env_content .= "ENCRYPTION_KEY_HASH=" . bin2hex($encryption_key_hash) . "\n";
            
            if (file_put_contents($config_file, $env_content) === false) {
                $error = 'Failed to write configuration file. Check directory permissions.';
            } else {
                // Move to step 2
                $_SESSION['setup_pdo'] = serialize([
                    'host' => $db_host,
                    'name' => $db_name,
                    'user' => $db_user,
                    'pass' => $db_pass
                ]);
                $step = 2;
            }
            
        } catch (PDOException $e) {
            $error = 'Database connection failed: ' . $e->getMessage();
        }
    }
}

// Step 2: Initialize database tables
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    session_start();
    if (!isset($_SESSION['setup_pdo'])) {
        die('Invalid setup session. Please start over.');
    }
    
    $db_creds = unserialize($_SESSION['setup_pdo']);
    
    try {
        $pdo = new PDO(
            "mysql:host={$db_creds['host']};dbname={$db_creds['name']};charset=utf8mb4",
            $db_creds['user'],
            $db_creds['pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Read and execute schema file
        $schema_file = __DIR__ . '/schema.sql';
        if (!file_exists($schema_file)) {
            $error = 'Schema file not found. Please ensure schema.sql exists.';
        } else {
            $schema = file_get_contents($schema_file);
            
            // Split by semicolons and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && strpos($statement, '--') !== 0) {
                    $pdo->exec($statement);
                }
            }
            
            // Create lock file to prevent re-running
            file_put_contents($lock_file, date('Y-m-d H:i:s'));
            
            // Clear session
            unset($_SESSION['setup_pdo']);
            
            $success = true;
        }
        
    } catch (PDOException $e) {
        $error = 'Database initialization failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Crash Hockey</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #06080b 0%, #0d1117 100%);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .setup-container {
            background: #0d1117;
            border: 1px solid #1e293b;
            border-radius: 12px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -1px;
        }
        .logo span {
            color: #ff4d00;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        .step.active {
            background: #ff4d00;
            color: #000;
        }
        .step.complete {
            background: #00ff88;
            color: #000;
        }
        h2 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #fff;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            background: #06080b;
            border: 1px solid #1e293b;
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
            transition: 0.2s;
        }
        input:focus {
            outline: none;
            border-color: #ff4d00;
            box-shadow: 0 0 0 3px rgba(255, 77, 0, 0.1);
        }
        .help-text {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: #ff4d00;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn:hover {
            background: #ff6a00;
            box-shadow: 0 5px 15px rgba(255, 77, 0, 0.3);
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #ef4444;
        }
        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            color: #00ff88;
        }
        .success-card {
            text-align: center;
        }
        .success-card i {
            font-size: 60px;
            color: #00ff88;
            margin-bottom: 20px;
        }
        .success-card h2 {
            margin-bottom: 15px;
        }
        .success-card p {
            color: #94a3b8;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .success-card a {
            display: inline-block;
            padding: 14px 30px;
            background: #ff4d00;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 700;
            transition: 0.3s;
        }
        .success-card a:hover {
            background: #ff6a00;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">
            <h1>CRASH <span>HOCKEY</span></h1>
            <p style="color: #64748b; font-size: 13px; margin-top: 5px;">Database Setup</p>
        </div>

        <?php if (!$success): ?>
            <div class="step-indicator">
                <div class="step <?= $step >= 1 ? 'active' : '' ?>">1</div>
                <div class="step <?= $step >= 2 ? 'active' : '' ?>">2</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <h2>Database Configuration</h2>
                <form method="POST">
                    <input type="hidden" name="step" value="1">
                    
                    <div class="form-group">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="localhost" required>
                        <div class="help-text">Usually "localhost" or an IP address</div>
                    </div>

                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="db_name" value="crashhockey" required>
                        <div class="help-text">Will be created if it doesn't exist</div>
                    </div>

                    <div class="form-group">
                        <label>Database Username</label>
                        <input type="text" name="db_user" required>
                    </div>

                    <div class="form-group">
                        <label>Database Password</label>
                        <input type="password" name="db_pass">
                        <div class="help-text">Leave blank if no password</div>
                    </div>

                    <div class="form-group">
                        <label>Encryption Key</label>
                        <input type="password" name="encryption_key" required minlength="32">
                        <div class="help-text">
                            <strong>Important:</strong> At least 32 characters. Store this securely - you'll need it to recover your configuration.
                        </div>
                    </div>

                    <button type="submit" class="btn">
                        <i class="fas fa-arrow-right"></i> Continue to Database Setup
                    </button>
                </form>
            <?php elseif ($step == 2): ?>
                <h2>Initialize Database Tables</h2>
                <p style="color: #94a3b8; margin-bottom: 20px; line-height: 1.6;">
                    Click the button below to create all necessary database tables and default data.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="step" value="2">
                    <button type="submit" class="btn">
                        <i class="fas fa-database"></i> Initialize Database
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="success-card">
                <i class="fas fa-check-circle"></i>
                <h2>Setup Complete!</h2>
                <p>
                    Your database has been successfully configured and initialized. 
                    You can now proceed to the registration page to create your admin account.
                </p>
                <a href="register.php">
                    <i class="fas fa-user-plus"></i> Create Admin Account
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
