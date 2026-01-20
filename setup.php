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
$admin_created = false;
$smtp_tested = false;
$step = isset($_POST['step']) ? intval($_POST['step']) : 1;

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
            
            // Capture actual error if write fails
            $write_result = @file_put_contents($config_file, $env_content);
            if ($write_result === false) {
                $last_error = error_get_last();
                $error_detail = $last_error ? $last_error['message'] : 'Unknown error';
                $error = "Failed to write configuration file to: $config_file<br>";
                $error .= "Error: $error_detail<br>";
                $error .= "Directory: " . dirname($config_file) . "<br>";
                $error .= "Writable: " . (is_writable(dirname($config_file)) ? 'Yes' : 'No') . "<br>";
                $error .= "Web server user: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown');
            } else {
                // Move to step 2 (credentials stored in file, not session)
                $step = 2;
            }
            
        } catch (PDOException $e) {
            $error = 'Database connection failed: ' . $e->getMessage();
        }
    }
}

// Step 2: Initialize database tables
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 2) {
    // Read credentials from config file
    if (!file_exists($config_file)) {
        $error = 'Configuration file not found. Please go back to step 1.';
    } else {
        $env_data = file_get_contents($config_file);
        preg_match('/DB_HOST=(.+)/', $env_data, $host_match);
        preg_match('/DB_NAME=(.+)/', $env_data, $name_match);
        preg_match('/DB_USER=(.+)/', $env_data, $user_match);
        preg_match('/DB_PASS_ENCRYPTED=(.+)/', $env_data, $pass_match);
        preg_match('/ENCRYPTION_KEY_HASH=(.+)/', $env_data, $key_match);
        
        if (!$host_match || !$name_match || !$user_match) {
            $error = 'Invalid configuration file. Please start over.';
        } else {
            $db_host = trim($host_match[1]);
            $db_name = trim($name_match[1]);
            $db_user = trim($user_match[1]);
            
            // Decrypt password if encrypted
            if ($pass_match && $key_match) {
                try {
                    $encrypted_data = trim($pass_match[1]);
                    $key_hash = hex2bin(trim($key_match[1]));
                    
                    $parts = explode('::', base64_decode($encrypted_data), 2);
                    if (count($parts) === 2) {
                        $iv = $parts[0];
                        $encrypted = $parts[1];
                        $db_pass = openssl_decrypt($encrypted, 'AES-256-CBC', $key_hash, 0, $iv);
                    } else {
                        $db_pass = '';
                    }
                } catch (Exception $e) {
                    $db_pass = '';
                }
            } else {
                $db_pass = '';
            }
    
            try {
                $pdo = new PDO(
                    "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                    $db_user,
                    $db_pass
                );
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Read and execute schema file
                $schema_file = __DIR__ . '/deployment/schema.sql';
                if (!file_exists($schema_file)) {
                    $error = 'Schema file not found at: ' . $schema_file;
                } else {
                    $schema = file_get_contents($schema_file);
                    
                    if (empty($schema)) {
                        $error = 'Schema file is empty.';
                    } else {
                        // Disable foreign key checks temporarily to handle table creation order
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                        $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
                        
                        // Remove comments first
                        $schema = preg_replace('/^--.*$/m', '', $schema); // Remove single-line comments
                        $schema = preg_replace('/\/\*.*?\*\//s', '', $schema); // Remove multi-line comments
                        
                        // Split by semicolons (handle both ; and ;\n)
                        $statements = array_filter(array_map('trim', preg_split('/;[\s]*(\n|$)/', $schema)));
                        
                        $failed_statements = [];
                        $successful_count = 0;
                        $total_statements = 0;
                        
                        // Separate CREATE TABLE statements from others
                        $create_statements = [];
                        $other_statements = [];
                        
                        foreach ($statements as $statement) {
                            if (empty($statement)) continue;
                            
                            $total_statements++;
                            
                            if (stripos($statement, 'CREATE TABLE') !== false || 
                                stripos($statement, 'CREATE INDEX') !== false ||
                                stripos($statement, 'CREATE UNIQUE INDEX') !== false) {
                                $create_statements[] = $statement;
                            } else {
                                $other_statements[] = $statement;
                            }
                        }
                        
                        // Execute CREATE TABLE statements first
                        foreach ($create_statements as $statement) {
                            try {
                                $pdo->exec($statement);
                                $successful_count++;
                            } catch (PDOException $e) {
                                $failed_statements[] = [
                                    'statement' => substr($statement, 0, 200) . (strlen($statement) > 200 ? '...' : ''),
                                    'error' => $e->getMessage(),
                                    'type' => 'CREATE'
                                ];
                            }
                        }
                        
                        // Then execute INSERT/ALTER and other statements
                        foreach ($other_statements as $statement) {
                            try {
                                $pdo->exec($statement);
                                $successful_count++;
                            } catch (PDOException $e) {
                                // Don't fail on INSERT errors if table doesn't exist (means CREATE failed)
                                $failed_statements[] = [
                                    'statement' => substr($statement, 0, 200) . (strlen($statement) > 200 ? '...' : ''),
                                    'error' => $e->getMessage(),
                                    'type' => 'INSERT/ALTER'
                                ];
                            }
                        }
                        
                        // Re-enable foreign key checks
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                        
                        // Store results for validation page
                        $_SESSION['schema_stats'] = [
                            'total' => $total_statements,
                            'successful' => $successful_count,
                            'failed' => count($failed_statements),
                            'failed_statements' => $failed_statements
                        ];
                        
                        // Move to validation page regardless of success/failure
                        $step = 2.5; // New validation step
                    }
                }
                
            } catch (PDOException $e) {
                $error = '<strong>CRITICAL: Database connection or initialization failed</strong><br><br>';
                $error .= 'Error: ' . htmlspecialchars($e->getMessage()) . '<br><br>';
                $error .= 'Please check:<br>';
                $error .= '• Database credentials are correct<br>';
                $error .= '• Database server is running<br>';
                $error .= '• User has permissions to create tables<br>';
            }
        }
    }
}

// Step 3: Configure and test SMTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 3) {
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = trim($_POST['smtp_port'] ?? '587');
    $smtp_encryption = $_POST['smtp_encryption'] ?? 'tls';
    $smtp_user = trim($_POST['smtp_user'] ?? '');
    $smtp_pass = $_POST['smtp_pass'] ?? '';
    $smtp_from_email = trim($_POST['smtp_from_email'] ?? '');
    $smtp_from_name = trim($_POST['smtp_from_name'] ?? 'Crash Hockey');
    $test_email = trim($_POST['test_email'] ?? '');
    
    if (empty($smtp_host) || empty($smtp_user) || empty($smtp_from_email)) {
        $error = 'SMTP host, username, and from email are required.';
    } elseif (empty($test_email)) {
        $error = 'Test email address is required to verify SMTP configuration.';
    } else {
        // Read DB credentials to save SMTP settings
        if (!file_exists($config_file)) {
            $error = 'Configuration file not found. Please start setup from the beginning.';
        } else {
            $env_data = file_get_contents($config_file);
            preg_match('/DB_HOST=(.+)/', $env_data, $host_match);
            preg_match('/DB_NAME=(.+)/', $env_data, $name_match);
            preg_match('/DB_USER=(.+)/', $env_data, $user_match);
            preg_match('/DB_PASS_ENCRYPTED=(.+)/', $env_data, $pass_match);
            preg_match('/ENCRYPTION_KEY_HASH=(.+)/', $env_data, $key_match);
            
            if ($host_match && $name_match && $user_match) {
                $db_host = trim($host_match[1]);
                $db_name = trim($name_match[1]);
                $db_user = trim($user_match[1]);
                
                // Decrypt password
                if ($pass_match && $key_match) {
                    try {
                        $encrypted_data = trim($pass_match[1]);
                        $key_hash = hex2bin(trim($key_match[1]));
                        
                        $parts = explode('::', base64_decode($encrypted_data), 2);
                        if (count($parts) === 2) {
                            $iv = $parts[0];
                            $encrypted = $parts[1];
                            $db_pass = openssl_decrypt($encrypted, 'AES-256-CBC', $key_hash, 0, $iv);
                        }
                    } catch (Exception $e) {
                        $db_pass = '';
                    }
                } else {
                    $db_pass = '';
                }
                
                try {
                    $pdo = new PDO(
                        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                        $db_user,
                        $db_pass
                    );
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // VERIFY SYSTEM_SETTINGS TABLE EXISTS
                    $result = $pdo->query("SHOW TABLES LIKE 'system_settings'");
                    if ($result->rowCount() === 0) {
                        $error = '<strong>CRITICAL ERROR:</strong> system_settings table does not exist!<br><br>';
                        $error .= 'The database initialization did not complete successfully. Please go back to Step 2 and retry database initialization.<br><br>';
                        $error .= '<form method="POST" style="margin-top: 10px;"><input type="hidden" name="step" value="2"><button type="submit" class="btn" style="background: #7000a4;">← Back to Database Initialization</button></form>';
                    } else {
                        // Save SMTP settings to database
                        $settings = [
                            'smtp_host' => $smtp_host,
                            'smtp_port' => $smtp_port,
                            'smtp_encryption' => $smtp_encryption,
                            'smtp_user' => $smtp_user,
                            'smtp_pass' => $smtp_pass,
                            'smtp_from_email' => $smtp_from_email,
                            'smtp_from_name' => $smtp_from_name
                        ];
                        
                        $del = $pdo->prepare("DELETE FROM system_settings WHERE setting_key = ?");
                        $ins = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
                        
                        foreach ($settings as $key => $value) {
                            $del->execute([$key]);
                            $ins->execute([$key, $value]);
                        }
                        
                        // Test SMTP by sending a test email
                        require_once __DIR__ . '/mailer.php';
                        
                        // Temporarily override settings for testing
                        $_ENV['SMTP_HOST'] = $smtp_host;
                        $_ENV['SMTP_PORT'] = $smtp_port;
                        $_ENV['SMTP_ENCRYPTION'] = $smtp_encryption;
                        $_ENV['SMTP_USER'] = $smtp_user;
                        $_ENV['SMTP_PASS'] = $smtp_pass;
                        $_ENV['SMTP_FROM_EMAIL'] = $smtp_from_email;
                        $_ENV['SMTP_FROM_NAME'] = $smtp_from_name;
                        
                        $test_result = sendEmail($test_email, 'test', []);
                        
                        if ($test_result) {
                            $smtp_tested = true;
                            $step = 4; // Move to admin creation
                        } else {
                            // Fetch the actual error message from email_logs
                            $stmt = $pdo->prepare("SELECT error_message FROM email_logs WHERE recipient = ? AND status = 'FAILED' ORDER BY sent_at DESC LIMIT 1");
                            $stmt->execute([$test_email]);
                            $email_error = $stmt->fetchColumn();
                            
                            $error = 'SMTP test failed: ' . ($email_error ?: 'Please check your settings and try again.');
                        }
                    }
                    
                } catch (PDOException $e) {
                    $error = '<strong>Database error:</strong> ' . htmlspecialchars($e->getMessage()) . '<br><br>';
                    $error .= 'Please ensure the database was initialized correctly in Step 2.';
                }
            } else {
                $error = 'Invalid configuration file.';
            }
        }
    }
}

// Step 4: Create first admin account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step == 4) {
    // Read credentials from config file
    if (!file_exists($config_file)) {
        $error = 'Configuration file not found. Please start setup from the beginning.';
    } else {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            // Read DB credentials
            $env_data = file_get_contents($config_file);
            preg_match('/DB_HOST=(.+)/', $env_data, $host_match);
            preg_match('/DB_NAME=(.+)/', $env_data, $name_match);
            preg_match('/DB_USER=(.+)/', $env_data, $user_match);
            preg_match('/DB_PASS_ENCRYPTED=(.+)/', $env_data, $pass_match);
            preg_match('/ENCRYPTION_KEY_HASH=(.+)/', $env_data, $key_match);
            
            if ($host_match && $name_match && $user_match) {
                $db_host = trim($host_match[1]);
                $db_name = trim($name_match[1]);
                $db_user = trim($user_match[1]);
                
                // Decrypt password
                if ($pass_match && $key_match) {
                    try {
                        $encrypted_data = trim($pass_match[1]);
                        $key_hash = hex2bin(trim($key_match[1]));
                        
                        $parts = explode('::', base64_decode($encrypted_data), 2);
                        if (count($parts) === 2) {
                            $iv = $parts[0];
                            $encrypted = $parts[1];
                            $db_pass = openssl_decrypt($encrypted, 'AES-256-CBC', $key_hash, 0, $iv);
                        }
                    } catch (Exception $e) {
                        $db_pass = '';
                    }
                } else {
                    $db_pass = '';
                }
                
                try {
                    $pdo = new PDO(
                        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                        $db_user,
                        $db_pass
                    );
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // VERIFY USERS TABLE EXISTS
                    $result = $pdo->query("SHOW TABLES LIKE 'users'");
                    if ($result->rowCount() === 0) {
                        $error = '<strong>CRITICAL ERROR:</strong> users table does not exist!<br><br>';
                        $error .= 'The database initialization did not complete successfully. Please go back to Step 2 and retry database initialization.<br><br>';
                        $error .= '<form method="POST" style="margin-top: 10px;"><input type="hidden" name="step" value="2"><button type="submit" class="btn" style="background: #7000a4;">← Back to Database Initialization</button></form>';
                    } else {
                        // Create admin account
                        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("
                            INSERT INTO users (first_name, last_name, email, password, role, is_verified, email_notifications)
                            VALUES (?, ?, ?, ?, 'admin', 1, 1)
                        ");
                        $stmt->execute([$first_name, $last_name, $email, $hashed_password]);
                        
                        // Create lock file now
                        file_put_contents($lock_file, date('Y-m-d H:i:s'));
                        
                        $admin_created = true;
                        $success = true;
                    }
                    
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = 'An account with this email already exists.';
                    } else {
                        $error = 'Failed to create admin account: ' . $e->getMessage();
                    }
                }
            } else {
                $error = 'Invalid configuration file.';
            }
        }
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
            color: #7000a4;
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
            background: #7000a4;
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
            border-color: #7000a4;
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
            background: #7000a4;
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
            background: #5a0080;
            box-shadow: 0 5px 15px rgba(112, 0, 164, 0.3);
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
            background: #7000a4;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 700;
            transition: 0.3s;
        }
        .success-card a:hover {
            background: #5a0080;
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
                <div class="step <?= ($step >= 2.5 && $step < 3) ? 'active' : ($step >= 3 ? 'complete' : '') ?>"><i class="fas fa-check"></i></div>
                <div class="step <?= $step >= 3 ? 'active' : '' ?>">3</div>
                <div class="step <?= $step >= 4 ? 'active' : '' ?>">4</div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if ($smtp_tested): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    SMTP configuration successful! Test email sent.
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
            <?php elseif ($step == 2.5): ?>
                <?php
                // VALIDATION PAGE: Check all tables
                // Reconnect to database to verify tables
                $env_data = file_get_contents($config_file);
                preg_match('/DB_HOST=(.+)/', $env_data, $host_match);
                preg_match('/DB_NAME=(.+)/', $env_data, $name_match);
                preg_match('/DB_USER=(.+)/', $env_data, $user_match);
                preg_match('/DB_PASS_ENCRYPTED=(.+)/', $env_data, $pass_match);
                preg_match('/ENCRYPTION_KEY_HASH=(.+)/', $env_data, $key_match);
                
                $db_host = trim($host_match[1]);
                $db_name = trim($name_match[1]);
                $db_user = trim($user_match[1]);
                
                // Decrypt password
                if ($pass_match && $key_match) {
                    $encrypted_data = trim($pass_match[1]);
                    $key_hash = hex2bin(trim($key_match[1]));
                    $parts = explode('::', base64_decode($encrypted_data), 2);
                    $iv = $parts[0];
                    $encrypted = $parts[1];
                    $db_pass = openssl_decrypt($encrypted, 'AES-256-CBC', $key_hash, 0, $iv);
                }
                
                $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Define all critical tables that MUST exist
                $all_tables = [
                    'users', 'locations', 'age_groups', 'skill_levels', 'managed_athletes',
                    'session_types', 'sessions', 'practice_plans', 'drill_categories', 'drills',
                    'practice_plan_drills', 'bookings', 'athlete_sessions', 'team_sessions',
                    'athlete_teams', 'teams', 'workout_templates', 'nutrition_templates',
                    'athlete_notes', 'videos', 'video_notes', 'notifications', 'email_logs',
                    'permissions', 'role_permissions', 'user_permissions', 'system_settings',
                    'packages', 'user_credits', 'transactions', 'workout_plan_categories',
                    'nutrition_plan_categories', 'practice_plan_categories', 'accounting_entries',
                    'expense_categories', 'receipts', 'mileage_logs', 'refunds'
                ];
                
                $existing_tables = [];
                $missing_tables = [];
                
                foreach ($all_tables as $table) {
                    $result = $pdo->query("SHOW TABLES LIKE '$table'");
                    if ($result->rowCount() > 0) {
                        $existing_tables[] = $table;
                    } else {
                        $missing_tables[] = $table;
                    }
                }
                
                $stats = $_SESSION['schema_stats'] ?? ['total' => 0, 'successful' => 0, 'failed' => 0, 'failed_statements' => []];
                $has_critical_failures = count($missing_tables) > 0;
                ?>
                
                <h2><i class="fas fa-clipboard-check"></i> Database Validation</h2>
                
                <div style="background: #0a0f14; border: 1px solid #1e293b; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; color: #94a3b8; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.5px;">
                        Initialization Summary
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                        <div style="text-align: center; padding: 15px; background: #06080b; border-radius: 6px;">
                            <div style="font-size: 28px; font-weight: 700; color: #64748b;"><?= $stats['total'] ?></div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 5px;">TOTAL STATEMENTS</div>
                        </div>
                        <div style="text-align: center; padding: 15px; background: #06080b; border-radius: 6px;">
                            <div style="font-size: 28px; font-weight: 700; color: #00ff88;"><?= $stats['successful'] ?></div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 5px;">SUCCESSFUL</div>
                        </div>
                        <div style="text-align: center; padding: 15px; background: #06080b; border-radius: 6px;">
                            <div style="font-size: 28px; font-weight: 700; color: #ff4444;"><?= $stats['failed'] ?></div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 5px;">FAILED</div>
                        </div>
                    </div>
                </div>
                
                <div style="background: #0a0f14; border: 1px solid #1e293b; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; color: #94a3b8; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.5px;">
                        Table Validation (<?= count($existing_tables) ?>/<?= count($all_tables) ?>)
                    </h3>
                    
                    <div style="max-height: 300px; overflow-y: auto;">
                        <?php foreach ($all_tables as $table): ?>
                            <?php $exists = in_array($table, $existing_tables); ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; margin-bottom: 4px; background: #06080b; border-radius: 4px; border-left: 3px solid <?= $exists ? '#00ff88' : '#ff4444' ?>;">
                                <span style="font-size: 13px; font-family: monospace; color: <?= $exists ? '#fff' : '#ff8888' ?>;"><?= $table ?></span>
                                <?php if ($exists): ?>
                                    <i class="fas fa-check-circle" style="color: #00ff88;"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle" style="color: #ff4444;"></i>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if (!empty($stats['failed_statements'])): ?>
                <div style="background: #2d1a1a; border: 1px solid #5a2828; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; color: #ff8888; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 0.5px;">
                        <i class="fas fa-exclamation-triangle"></i> Failed SQL Statements
                    </h3>
                    <div style="max-height: 200px; overflow-y: auto;">
                        <?php foreach ($stats['failed_statements'] as $fail): ?>
                            <div style="margin-bottom: 15px; padding: 10px; background: #1a0f0f; border-radius: 4px; border-left: 3px solid #ff4444;">
                                <div style="font-size: 10px; color: #aaa; margin-bottom: 3px;"><strong>Type:</strong> <?= htmlspecialchars($fail['type'] ?? 'UNKNOWN') ?></div>
                                <div style="font-size: 11px; font-family: monospace; color: #94a3b8; margin-bottom: 5px;"><?= htmlspecialchars($fail['statement']) ?></div>
                                <div style="font-size: 11px; color: #ff8888;"><strong>Error:</strong> <?= htmlspecialchars($fail['error']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($has_critical_failures): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>CRITICAL:</strong> <?= count($missing_tables) ?> required table(s) are missing. You cannot proceed until all tables are created successfully. Please check the error messages above and fix any database issues.
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="step" value="2">
                        <button type="submit" class="btn" style="background: #7000a4;">
                            <i class="fas fa-redo"></i> Retry Database Initialization
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i>
                        <strong>SUCCESS:</strong> All required database tables have been created successfully! You can now proceed to SMTP configuration.
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="step" value="3">
                        <button type="submit" class="btn">
                            <i class="fas fa-arrow-right"></i> Continue to SMTP Configuration
                        </button>
                    </form>
                <?php endif; ?>
            <?php elseif ($step == 3): ?>
                <h2>SMTP Configuration</h2>
                <p style="color: #94a3b8; margin-bottom: 20px; line-height: 1.6;">
                    Configure email settings to enable verification emails and notifications.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="step" value="3">
                    
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" name="smtp_host" placeholder="smtp.gmail.com" required>
                        <div class="help-text">Your SMTP server address</div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="number" name="smtp_port" value="587" required>
                            <div class="help-text">Usually 587 for TLS or 465 for SSL</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Encryption</label>
                            <select name="smtp_encryption" style="width: 100%; padding: 12px 15px; background: #06080b; border: 1px solid #1e293b; border-radius: 6px; color: #fff; font-size: 14px;">
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="">None</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Username</label>
                        <input type="text" name="smtp_user" required>
                        <div class="help-text">Usually your email address</div>
                    </div>
                    
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" name="smtp_pass" required>
                        <div class="help-text">Your email password or app-specific password</div>
                    </div>
                    
                    <div class="form-group">
                        <label>From Email</label>
                        <input type="email" name="smtp_from_email" required>
                        <div class="help-text">Email address that will appear as sender</div>
                    </div>
                    
                    <div class="form-group">
                        <label>From Name</label>
                        <input type="text" name="smtp_from_name" value="Crash Hockey" required>
                        <div class="help-text">Name that will appear as sender</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Test Email Address</label>
                        <input type="email" name="test_email" required>
                        <div class="help-text">
                            <strong>Important:</strong> A test email will be sent to verify your SMTP settings
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-envelope"></i> Test & Save SMTP Settings
                    </button>
                </form>
            <?php elseif ($step == 4): ?>
                <h2>Create Admin Account</h2>
                <p style="color: #94a3b8; margin-bottom: 20px; line-height: 1.6;">
                    Create your first administrator account to manage the system.
                </p>
                
                <form method="POST">
                    <input type="hidden" name="step" value="4">
                    
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required>
                        <div class="help-text">You'll use this to log in</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required minlength="8">
                        <div class="help-text">At least 8 characters</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn">
                        <i class="fas fa-user-shield"></i> Create Admin Account
                    </button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="success-card">
                <i class="fas fa-check-circle"></i>
                <h2>Setup Complete!</h2>
                <p>
                    Your admin account has been created successfully. 
                    You can now log in to access the dashboard.
                </p>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
