<?php
// process_booking.php
session_start();
require 'db_config.php';
require 'security.php';
require 'notifications.php';

// Set security headers
setSecurityHeaders();

// 1. SECURITY: Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. LOAD STRIPE LIBRARY
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
} elseif (file_exists('stripe-php/init.php')) {
    require 'stripe-php/init.php';
} else {
    die("Error: Stripe library not found in /stripe-php/ folder.");
}

// 3. LOAD KEYS FROM DB
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$stripe_secret = $settings['stripe_secret_key'] ?? '';
$currency = $settings['currency'] ?? 'CAD';
$tax_rate = floatval($settings['tax_rate'] ?? 13.00); // Default 13% HST
$tax_name = $settings['tax_name'] ?? 'HST';

if (empty($stripe_secret)) { die("Stripe is not configured in Admin Settings."); }
\Stripe\Stripe::setApiKey($stripe_secret);

// 4. GET BOOKING DETAILS
$session_id = $_POST['session_id'];
$user_code  = isset($_POST['discount_code']) ? strtoupper(trim($_POST['discount_code'])) : '';
$user_id    = $_SESSION['user_id'];
$user_role  = $_SESSION['user_role'] ?? 'athlete';
$domain     = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']); 

// Handle multi-athlete booking for parents
$athlete_ids = [];
$is_parent_booking = false;

if ($user_role === 'parent' && isset($_POST['athlete_ids']) && is_array($_POST['athlete_ids'])) {
    $is_parent_booking = true;
    $athlete_ids = array_map('intval', $_POST['athlete_ids']);
    
    // Validate that parent manages these athletes
    if (!empty($athlete_ids)) {
        $placeholders = str_repeat('?,', count($athlete_ids) - 1) . '?';
        $verify_stmt = $pdo->prepare("
            SELECT athlete_id FROM managed_athletes 
            WHERE parent_id = ? AND athlete_id IN ($placeholders) AND can_book = 1
        ");
        $verify_params = array_merge([$user_id], $athlete_ids);
        $verify_stmt->execute($verify_params);
        $verified_athletes = $verify_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Only use verified athletes
        $athlete_ids = array_intersect($athlete_ids, $verified_athletes);
    }
    
    if (empty($athlete_ids)) {
        die("No valid athletes selected for booking.");
    }
} else {
    // Single athlete booking (the logged-in user)
    $athlete_ids = [$user_id];
}

// Fetch Session Info
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch();
if (!$session) { die("Session not found."); }

// CHECK FOR PACKAGE CREDITS FIRST
$has_credits = false;
$credit_bookings = [];

foreach ($athlete_ids as $athlete_id) {
    // Check if athlete has available credits
    $credit_check = $pdo->prepare("
        SELECT upc.id, upc.credits_remaining, upc.package_id
        FROM user_package_credits upc
        WHERE upc.user_id = ? AND upc.credits_remaining > 0 AND upc.expiry_date >= CURDATE()
        ORDER BY upc.expiry_date ASC
        LIMIT 1
    ");
    $credit_check->execute([$athlete_id]);
    $credit = $credit_check->fetch(PDO::FETCH_ASSOC);
    
    if ($credit) {
        $has_credits = true;
        $credit_bookings[$athlete_id] = $credit;
    }
}

// If all athletes have credits, use them instead of payment
if ($has_credits && count($credit_bookings) === count($athlete_ids)) {
    $pdo->beginTransaction();
    
    try {
        foreach ($athlete_ids as $athlete_id) {
            $credit = $credit_bookings[$athlete_id];
            
            // Create booking using credit
            $booking_stmt = $pdo->prepare("
                INSERT INTO bookings (user_id, session_id, package_id, amount_paid, original_price, 
                                    tax_amount, booked_for_user_id, payment_type, status)
                VALUES (?, ?, ?, 0, 0, 0, ?, 'package', 'paid')
            ");
            $booking_stmt->execute([$user_id, $session_id, $credit['package_id'], $athlete_id]);
            
            // Deduct credit
            $update_credit = $pdo->prepare("
                UPDATE user_package_credits 
                SET credits_remaining = credits_remaining - 1 
                WHERE id = ?
            ");
            $update_credit->execute([$credit['id']]);
            
            // Send notification
            createNotification(
                $pdo,
                $athlete_id,
                'booking',
                'Session Booked with Package Credit',
                "Booked " . $session['title'] . " using package credit",
                'dashboard.php?page=session_detail&id=' . $session_id,
                true
            );
        }
        
        $pdo->commit();
        header("Location: dashboard.php?page=schedule&status=booked_with_credit");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Credit booking error: " . $e->getMessage());
        die("Error processing credit booking: " . $e->getMessage());
    }
}

// Check capacity for multiple bookings
$current_bookings_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE session_id = ? AND status = 'paid'");
$current_bookings_stmt->execute([$session_id]);
$current_bookings = $current_bookings_stmt->fetchColumn();
$available_spots = $session['max_capacity'] - $current_bookings;

if (count($athlete_ids) > $available_spots) {
    die("Not enough spots available. Only $available_spots spot(s) remaining.");
}

// 5. CALCULATE PRICE (Discount Logic)
$original_price = $session['price'];
$final_price    = $original_price;
$applied_code   = null;

if (!empty($user_code)) {
    $stmt = $pdo->prepare("SELECT * FROM discount_codes WHERE code = ?");
    $stmt->execute([$user_code]);
    $discount = $stmt->fetch();

    if ($discount) {
        $now = date('Y-m-d');
        $is_expired = ($discount['expiry_date'] && $discount['expiry_date'] < $now);
        $is_limit_reached = ($discount['usage_limit'] > 0 && $discount['times_used'] >= $discount['usage_limit']);

        if (!$is_expired && !$is_limit_reached) {
            if ($discount['type'] == 'percent') {
                $deduction = $original_price * ($discount['value'] / 100);
            } else {
                $deduction = $discount['value'];
            }
            $final_price = max(0, $original_price - $deduction);
            $applied_code = $user_code;
            
            // Increment usage
            $pdo->prepare("UPDATE discount_codes SET times_used = times_used + 1 WHERE id = ?")->execute([$discount['id']]);
        }
    }
}

// Calculate tax and total (multiply by number of athletes)
$num_athletes = count($athlete_ids);
$tax_amount = $final_price * ($tax_rate / 100);
$total_per_athlete = $final_price + $tax_amount;
$total_with_tax = $total_per_athlete * $num_athletes;

// 6. CREATE STRIPE SESSION
try {
    $description = $applied_code ? "Discount '$applied_code' applied" : 'Regular Rate';
    $description .= " + $tax_name ($tax_rate%)";
    
    if ($num_athletes > 1) {
        $description .= " | Booking for $num_athletes athletes";
    }
    
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => round($total_per_athlete * 100), // Convert to cents (includes tax)
                'product_data' => [
                    'name' => 'Training Session: ' . $session['title'],
                    'description' => $description,
                ],
            ],
            'quantity' => $num_athletes,
        ]],
        'mode' => 'payment',
        'success_url' => $domain . '/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $domain . '/dashboard.php?page=schedule&error=cancelled',
        'client_reference_id' => $user_id,
    ]);

    // 7. SAVE PENDING BOOKINGS IN DB (one for each athlete)
    // Store metadata to connect all bookings from this transaction
    $stripe_session_id = $checkout_session->id;
    
    foreach ($athlete_ids as $athlete_id) {
        $stmt = $pdo->prepare("
            INSERT INTO bookings (user_id, session_id, stripe_session_id, amount_paid, original_price, tax_amount, discount_code, booked_for_user_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        // user_id is who made the booking (parent or athlete themselves)
        // booked_for_user_id is who the booking is for (the athlete)
        $booked_for = ($is_parent_booking) ? $athlete_id : null;
        
        $stmt->execute([
            $user_id,
            $session_id,
            $stripe_session_id,
            $total_per_athlete,
            $original_price,
            $tax_amount,
            $applied_code,
            $booked_for
        ]);
        
        // Log security event
        logSecurityEvent($pdo, 'booking_created', "User $user_id created pending booking for athlete $athlete_id, session $session_id", $user_id);
    }

    // Redirect user to Stripe
    header("Location: " . $checkout_session->url);
    exit();

} catch (Exception $e) {
    error_log("Stripe Error in process_booking.php: " . $e->getMessage());
    die("Stripe Error: " . $e->getMessage());
}
?>