<?php
// process_refunds.php - Handle refund operations
session_start();
require 'db_config.php';
require 'security.php';
require 'notifications.php';
require 'mailer.php';

setSecurityHeaders();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'search_bookings':
            $email = $_GET['email'] ?? '';
            $session_id = $_GET['session_id'] ?? '';
            $start_date = $_GET['start_date'] ?? '';
            $end_date = $_GET['end_date'] ?? '';
            
            $query = "
                SELECT b.*, u.email, u.first_name, u.last_name,
                       s.session_name, s.session_date, s.session_time,
                       CONCAT(bf.first_name, ' ', bf.last_name) as athlete_name
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                LEFT JOIN sessions s ON b.session_id = s.id
                LEFT JOIN users bf ON b.booked_for_user_id = bf.id
                WHERE b.status = 'paid'
            ";
            
            $params = [];
            
            if ($email) {
                $query .= " AND u.email LIKE ?";
                $params[] = "%$email%";
            }
            
            if ($session_id) {
                $query .= " AND b.session_id = ?";
                $params[] = $session_id;
            }
            
            if ($start_date && $end_date) {
                $query .= " AND s.session_date BETWEEN ? AND ?";
                $params[] = $start_date;
                $params[] = $end_date;
            }
            
            $query .= " ORDER BY s.session_date DESC LIMIT 100";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $bookings = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'bookings' => $bookings]);
            break;
            
        case 'process_refund':
            checkCsrfToken();
            
            $booking_id = intval($_POST['booking_id']);
            $refund_amount = floatval($_POST['refund_amount']);
            $reason = trim($_POST['reason']);
            $refund_type = $_POST['refund_type']; // 'full' or 'partial'
            
            // Get booking details
            $booking_stmt = $pdo->prepare("
                SELECT b.*, u.email, u.first_name, s.session_name
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                LEFT JOIN sessions s ON b.session_id = s.id
                WHERE b.id = ? AND b.status = 'paid'
            ");
            $booking_stmt->execute([$booking_id]);
            $booking = $booking_stmt->fetch();
            
            if (!$booking) {
                throw new Exception('Booking not found or already refunded');
            }
            
            if ($refund_amount > $booking['amount_paid']) {
                throw new Exception('Refund amount cannot exceed paid amount');
            }
            
            // Get Stripe settings
            $stripe_stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('stripe_secret_key', 'stripe_mode')");
            $stripe_settings = $stripe_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $stripe_secret = $stripe_settings['stripe_secret_key'] ?? '';
            
            if (empty($stripe_secret)) {
                throw new Exception('Stripe not configured');
            }
            
            // Process Stripe refund
            if (!empty($booking['stripe_payment_intent_id'])) {
                $refund_result = processStripeRefund($booking['stripe_payment_intent_id'], $refund_amount, $stripe_secret);
                
                if (!$refund_result['success']) {
                    throw new Exception('Stripe refund failed: ' . $refund_result['message']);
                }
                
                $stripe_refund_id = $refund_result['refund_id'];
            } else {
                $stripe_refund_id = null;
            }
            
            // Create refund record
            $stmt = $pdo->prepare("
                INSERT INTO refunds (booking_id, user_id, original_amount, refund_amount, refund_reason, stripe_refund_id, refunded_by, refund_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $booking_id,
                $booking['user_id'],
                $booking['amount_paid'],
                $refund_amount,
                $reason,
                $stripe_refund_id,
                $user_id
            ]);
            
            $refund_id = $pdo->lastInsertId();
            
            // Update booking status
            if ($refund_type === 'full') {
                $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?")->execute([$booking_id]);
            } else {
                // Keep as 'paid' for partial refunds
                $pdo->prepare("UPDATE bookings SET status = 'paid' WHERE id = ?")->execute([$booking_id]);
            }
            
            // If package credit purchase, add credits back
            if ($booking['booking_type'] === 'package' && $refund_type === 'full') {
                $package_stmt = $pdo->prepare("SELECT credits_purchased FROM package_purchases WHERE booking_id = ?");
                $package_stmt->execute([$booking_id]);
                $package = $package_stmt->fetch();
                
                if ($package) {
                    $pdo->prepare("
                        UPDATE package_purchases 
                        SET credits_purchased = credits_purchased - ?, status = 'refunded'
                        WHERE booking_id = ?
                    ")->execute([$package['credits_purchased'], $booking_id]);
                }
            }
            
            // Send notification
            createNotification(
                $pdo,
                $booking['user_id'],
                'refund',
                'Refund Processed',
                "Your refund of $" . number_format($refund_amount, 2) . " for {$booking['session_name']} has been processed.",
                "dashboard.php?page=payment_history",
                false
            );
            
            // Send email
            sendRefundEmail($booking['email'], $booking['first_name'], $refund_amount, $booking['session_name'], $reason);
            
            echo json_encode(['success' => true, 'message' => 'Refund processed successfully', 'refund_id' => $refund_id]);
            break;
            
        case 'list_refunds':
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-t');
            
            $stmt = $pdo->prepare("
                SELECT r.*, u.email, u.first_name, u.last_name,
                       s.session_name, s.session_date,
                       CONCAT(admin.first_name, ' ', admin.last_name) as processed_by_name
                FROM refunds r
                JOIN users u ON r.user_id = u.id
                LEFT JOIN bookings b ON r.booking_id = b.id
                LEFT JOIN sessions s ON b.session_id = s.id
                LEFT JOIN users admin ON r.processed_by = admin.id
                WHERE DATE(r.processed_at) BETWEEN ? AND ?
                ORDER BY r.processed_at DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $refunds = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'refunds' => $refunds]);
            break;
            
        case 'export_refunds':
            $start_date = $_GET['start_date'] ?? date('Y-m-01');
            $end_date = $_GET['end_date'] ?? date('Y-m-t');
            
            $stmt = $pdo->prepare("
                SELECT r.*, u.email, u.first_name, u.last_name,
                       s.session_name, s.session_date,
                       CONCAT(admin.first_name, ' ', admin.last_name) as processed_by_name
                FROM refunds r
                JOIN users u ON r.user_id = u.id
                LEFT JOIN bookings b ON r.booking_id = b.id
                LEFT JOIN sessions s ON b.session_id = s.id
                LEFT JOIN users admin ON r.processed_by = admin.id
                WHERE DATE(r.processed_at) BETWEEN ? AND ?
                ORDER BY r.processed_at DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $refunds = $stmt->fetchAll();
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="refunds_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Customer', 'Email', 'Session', 'Original Amount', 'Refund Amount', 'Type', 'Reason', 'Processed By']);
            
            foreach ($refunds as $refund) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($refund['refund_date'])),
                    $refund['first_name'] . ' ' . $refund['last_name'],
                    $refund['email'],
                    $refund['session_name'] ?: 'N/A',
                    '$' . number_format($refund['original_amount'], 2),
                    '$' . number_format($refund['refund_amount'], 2),
                    ucfirst($refund['status']),
                    $refund['refund_reason'],
                    $refund['processed_by_name']
                ]);
            }
            
            fclose($output);
            exit;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Process Stripe refund
 */
function processStripeRefund($payment_intent_id, $amount, $secret_key) {
    $url = 'https://api.stripe.com/v1/refunds';
    
    $data = [
        'payment_intent' => $payment_intent_id,
        'amount' => intval($amount * 100) // Convert to cents
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secret_key
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($http_code === 200 && isset($result['id'])) {
        return ['success' => true, 'refund_id' => $result['id']];
    } else {
        $error = $result['error']['message'] ?? 'Unknown error';
        return ['success' => false, 'message' => $error];
    }
}

/**
 * Send refund email to customer
 */
function sendRefundEmail($to_email, $name, $amount, $session_name, $reason) {
    $subject = 'Refund Processed - Crash Hockey';
    
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif; color: #333;'>
        <h2 style='color: #ff4d00;'>Refund Processed</h2>
        <p>Hi $name,</p>
        <p>Your refund has been processed successfully.</p>
        <div style='background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 20px 0;'>
            <strong>Refund Amount:</strong> $" . number_format($amount, 2) . "<br>
            <strong>Session:</strong> " . htmlspecialchars($session_name) . "<br>
            <strong>Reason:</strong> " . htmlspecialchars($reason) . "
        </div>
        <p>The refund will appear in your account within 5-10 business days.</p>
        <p>If you have any questions, please contact us.</p>
        <p>Best regards,<br>Crash Hockey Team</p>
    </body>
    </html>
    ";
    
    try {
        sendEmail($to_email, 'refund', [
            'name' => $name,
            'amount' => number_format($amount, 2),
            'session' => $session_name,
            'reason' => $reason
        ]);
    } catch (Exception $e) {
        error_log("Failed to send refund email: " . $e->getMessage());
    }
}
?>
