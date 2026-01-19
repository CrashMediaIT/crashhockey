<?php
// payment_success.php
session_start();
require 'db_config.php';
require 'mailer.php';
require 'notifications.php';

// 1. LOAD STRIPE
if (file_exists('vendor/autoload.php')) { require 'vendor/autoload.php'; } 
elseif (file_exists('stripe-php/init.php')) { require 'stripe-php/init.php'; }

// 2. GET KEYS
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
\Stripe\Stripe::setApiKey($settings['stripe_secret_key']);

$stripe_sid = $_GET['session_id'] ?? '';
$payment_type = $_GET['type'] ?? 'session';
if (!$stripe_sid) { header("Location: dashboard.php"); exit(); }

try {
    // 3. VERIFY PAYMENT WITH STRIPE API
    $checkout = \Stripe\Checkout\Session::retrieve($stripe_sid);

    if ($checkout->payment_status == 'paid') {
        
        // Handle package purchase
        if ($payment_type === 'package' && isset($_SESSION['package_purchase'])) {
            $purchase_data = $_SESSION['package_purchase'];
            
            // Check if already processed
            $check = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE stripe_session_id = ? AND payment_type = 'package'");
            $check->execute([$stripe_sid]);
            
            if ($check->fetchColumn() == 0) {
                // Get package details
                $pkg_stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
                $pkg_stmt->execute([$purchase_data['package_id']]);
                $package = $pkg_stmt->fetch(PDO::FETCH_ASSOC);
                
                foreach ($purchase_data['athlete_ids'] as $athlete_id) {
                    // Create booking record
                    $booking_stmt = $pdo->prepare("
                        INSERT INTO bookings (user_id, package_id, stripe_session_id, amount_paid, 
                                            original_price, tax_amount, booked_for_user_id, payment_type, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'package', 'paid')
                    ");
                    $booking_stmt->execute([
                        $_SESSION['user_id'],
                        $purchase_data['package_id'],
                        $stripe_sid,
                        $purchase_data['total'],
                        $purchase_data['subtotal'],
                        $purchase_data['tax_amount'],
                        $athlete_id
                    ]);
                    
                    $booking_id = $pdo->lastInsertId();
                    
                    // Add credits if credit package
                    if ($package['package_type'] === 'credits') {
                        $expiry_date = date('Y-m-d', strtotime("+{$package['valid_days']} days"));
                        
                        $credit_stmt = $pdo->prepare("
                            INSERT INTO user_package_credits 
                            (user_id, package_id, booking_id, credits_purchased, credits_remaining, expiry_date)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $credit_stmt->execute([
                            $athlete_id,
                            $package['id'],
                            $booking_id,
                            $package['credits'],
                            $package['credits'],
                            $expiry_date
                        ]);
                    } else {
                        // For bundled packages, create bookings for each session
                        $sessions_stmt = $pdo->prepare("
                            SELECT session_id FROM package_sessions WHERE package_id = ?
                        ");
                        $sessions_stmt->execute([$package['id']]);
                        $package_sessions = $sessions_stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        foreach ($package_sessions as $session_id) {
                            $session_booking_stmt = $pdo->prepare("
                                INSERT INTO bookings (user_id, session_id, package_id, stripe_session_id, 
                                                    amount_paid, original_price, tax_amount, 
                                                    booked_for_user_id, payment_type, status)
                                VALUES (?, ?, ?, ?, 0, 0, 0, ?, 'package', 'paid')
                            ");
                            $session_booking_stmt->execute([
                                $_SESSION['user_id'],
                                $session_id,
                                $package['id'],
                                $stripe_sid,
                                $athlete_id
                            ]);
                        }
                    }
                    
                    // Send email and notification
                    $athlete_stmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE id = ?");
                    $athlete_stmt->execute([$athlete_id]);
                    $athlete = $athlete_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    sendEmail($athlete['email'], 'package_receipt', [
                        'name' => $athlete['first_name'] . ' ' . $athlete['last_name'],
                        'package_name' => $package['name'],
                        'amount' => number_format($purchase_data['total'], 2),
                        'credits' => $package['credits'] ?? 0,
                        'trans_id' => $stripe_sid
                    ]);
                    
                    createNotification(
                        $pdo,
                        $athlete_id,
                        'package',
                        'Package Purchase Confirmed',
                        "Successfully purchased " . $package['name'],
                        'dashboard.php?page=packages',
                        false
                    );
                }
            }
            
            unset($_SESSION['package_purchase']);
        } else {
            // Handle session booking (original code)
            // 4. FIND ALL PENDING BOOKINGS WITH THIS STRIPE SESSION (for multi-athlete bookings)
            $stmt = $pdo->prepare("
            SELECT b.*, s.title, s.session_date, s.session_time, u.email, u.first_name,
                   athlete.first_name as athlete_first_name, athlete.last_name as athlete_last_name,
                   athlete.email as athlete_email
            FROM bookings b
            JOIN sessions s ON b.session_id = s.id
            JOIN users u ON b.user_id = u.id
            LEFT JOIN users athlete ON b.booked_for_user_id = athlete.id
            WHERE b.stripe_session_id = ? AND b.status = 'pending'
        ");
        $stmt->execute([$stripe_sid]);
        $bookings = $stmt->fetchAll();

        // Only process if bookings haven't been processed yet
        if (!empty($bookings)) {
            
            foreach ($bookings as $booking) {
                // 5. MARK AS PAID IN DB
                $pdo->prepare("UPDATE bookings SET status = 'paid' WHERE id = ?")->execute([$booking['id']]);

                // 6. SEND EMAIL RECEIPT
                $session_date = date('M j, Y', strtotime($booking['session_date']));
                $session_time = date('g:i A', strtotime($booking['session_time']));
                
                // Determine recipient (athlete if booked by parent, or booker themselves)
                $recipient_email = $booking['athlete_email'] ?: $booking['email'];
                $recipient_name = $booking['athlete_first_name'] ? 
                    $booking['athlete_first_name'] . ' ' . $booking['athlete_last_name'] : 
                    $booking['first_name'];
                
                sendEmail($recipient_email, 'payment_receipt', [
                    'name'          => $recipient_name,
                    'session_title' => $booking['title'],
                    'amount'        => number_format($booking['amount_paid'], 2),
                    'date'          => $session_date,
                    'time'          => $session_time,
                    'trans_id'      => $stripe_sid
                ]);
                
                // 7. CREATE NOTIFICATION FOR ATHLETE
                $notify_user_id = $booking['booked_for_user_id'] ?: $booking['user_id'];
                $booker_name = $booking['athlete_first_name'] ? 
                    $booking['first_name'] : // Parent's name
                    'You';
                
                $notification_msg = $booking['booked_for_user_id'] ? 
                    "Booked by $booker_name for " . $booking['title'] . " on $session_date at $session_time" :
                    "Successfully booked " . $booking['title'] . " on $session_date at $session_time";
                
                createNotification(
                    $pdo,
                    $notify_user_id,
                    'booking',
                    'Session Booking Confirmed',
                    $notification_msg,
                    'dashboard.php?page=session_detail&id=' . $booking['session_id'],
                    false // Email already sent
                );
            }
        }
        }
    }
} catch (Exception $e) {
    error_log("Payment verification error: " . $e->getMessage());
    die("Payment Verification Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Payment Success | Crash Hockey</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh; background:#06080b; color:#fff;">
    
    <div style="text-align:center; padding: 40px; border: 1px solid #1e293b; background: #0d1116; border-radius: 12px; max-width: 400px; margin: 20px;">
        <i class="fa-solid fa-circle-check" style="font-size: 60px; color: #00ff88; margin-bottom: 20px;"></i>
        <h1 style="margin: 0 0 10px 0;">Booking Confirmed!</h1>
        <p style="color: #94a3b8; margin-bottom: 30px;">A receipt has been sent to your email.</p>
        
        <a href="dashboard.php?page=schedule" class="btn-primary" style="text-decoration:none; padding:12px 30px; border-radius:6px; display:inline-block;">
            Return to Schedule
        </a>
    </div>

</body>
</html>