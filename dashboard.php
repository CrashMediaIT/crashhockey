<?php
// =========================================================
// DASHBOARD CONTROLLER - FULL COMPREHENSIVE VERSION
// =========================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/security.php';

// Set security headers
setSecurityHeaders();

if (!isset($_SESSION['logged_in'])) { header("Location: login.php"); exit(); }

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'athlete';
$user_name = $_SESSION['user_name'] ?? 'Guest';

$isAdmin   = ($user_role === 'admin');
$isCoach   = ($user_role === 'coach' || $user_role === 'coach_plus');

$page = $_GET['page'] ?? 'home';

// FULL ROUTING TABLE
$allowed_pages = [
    'home'                => 'views/home.php',
    'stats'               => 'views/stats.php',
    'schedule'            => 'views/schedule.php',
    'session_history'     => 'views/session_history.php',
    'payment_history'     => 'views/payment_history.php',
    'profile'             => 'views/profile.php',
    'video_library'       => 'views/video.php',
    'workout_builder'     => 'views/workouts.php',
    'nutrition_builder'   => 'views/nutrition.php',
    'library_workouts'    => 'views/library_workouts.php',
    'library_nutrition'   => 'views/library_nutrition.php',
    'drills'              => 'views/drills.php',
    'practice_plans'      => 'views/practice_plans.php',
    'athletes'            => 'views/athletes.php',
    'create_session'      => 'views/create_session.php',
    'session_templates'   => 'views/library_sessions.php',
    'admin_locations'     => 'views/admin_locations.php',
    'admin_session_types' => 'views/admin_session_types.php',
    'admin_discounts'     => 'views/admin_discounts.php',
    'admin_permissions'   => 'views/admin_permissions.php',
    'settings'            => 'views/settings.php'
];

$view_file = $allowed_pages[$page] ?? 'views/home.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Crash Hockey Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #ff4d00; --bg: #06080b; --sidebar: #020305; --border: #1e293b; --text: #94a3b8; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; color: #fff; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 280px; background: var(--sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 25px; overflow-y: auto; }
        .brand { font-size: 22px; font-weight: 900; margin-bottom: 40px; letter-spacing: -1px; display: flex; align-items: center; gap: 10px; text-decoration: none; color: #fff; }
        .brand span { color: var(--primary); }
        .brand img { height: 35px; width: auto; }
        .nav-group { margin-bottom: 25px; }
        .nav-label { font-size: 10px; text-transform: uppercase; color: #475569; font-weight: 800; margin-bottom: 12px; display: block; letter-spacing: 1.5px; }
        .nav-menu { list-style: none; padding: 0; margin: 0; }
        .nav-link { display: flex; align-items: center; gap: 14px; padding: 10px 15px; color: var(--text); text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 600; transition: 0.2s; margin-bottom: 2px; }
        .nav-link i { width: 18px; text-align: center; }
        .nav-link:hover, .nav-link.active { background: rgba(255, 77, 0, 0.1); color: var(--primary); }
        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border); }
        .avatar { width: 35px; height: 35px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="?page=home" class="brand">
        <img src="https://images.crashmedia.ca/images/2026/01/18/logo.png" alt="Logo">
        CRASH <span>HOCKEY</span>
    </a>
    
    <div class="nav-group">
        <span class="nav-label">Main Menu</span>
        <nav class="nav-menu">
            <a href="?page=home" class="nav-link <?= $page=='home'?'active':'' ?>"><i class="fa-solid fa-house"></i> Home</a>
            <a href="?page=stats" class="nav-link <?= $page=='stats'?'active':'' ?>"><i class="fa-solid fa-chart-line"></i> Performance Stats</a>
            <a href="?page=schedule" class="nav-link <?= $page=='schedule'?'active':'' ?>"><i class="fa-solid fa-calendar-check"></i> Book Sessions</a>
            <a href="?page=workout_builder" class="nav-link <?= $page=='workout_builder'?'active':'' ?>"><i class="fa-solid fa-dumbbell"></i> Workout Plans</a>
            <a href="?page=nutrition_builder" class="nav-link <?= $page=='nutrition_builder'?'active':'' ?>"><i class="fa-solid fa-apple-whole"></i> Nutrition Plans</a>
            <a href="?page=video_library" class="nav-link <?= $page=='video_library'?'active':'' ?>"><i class="fa-solid fa-video"></i> Video Drills</a>
            <a href="?page=drills" class="nav-link <?= $page=='drills'?'active':'' ?>"><i class="fa-solid fa-hockey-puck"></i> Drill Library</a>
            <a href="?page=practice_plans" class="nav-link <?= $page=='practice_plans'?'active':'' ?>"><i class="fa-solid fa-clipboard-list"></i> Practice Plans</a>
        </nav>
    </div>

    <div class="nav-group">
        <span class="nav-label">Account & History</span>
        <nav class="nav-menu">
            <a href="?page=session_history" class="nav-link <?= $page=='session_history'?'active':'' ?>"><i class="fa-solid fa-clock-rotate-left"></i> Session History</a>
            <a href="?page=payment_history" class="nav-link <?= $page=='payment_history'?'active':'' ?>"><i class="fa-solid fa-credit-card"></i> Payment History</a>
        </nav>
    </div>

    <?php if($isCoach || $isAdmin): ?>
    <div class="nav-group">
        <span class="nav-label">Coach Management</span>
        <nav class="nav-menu">
            <a href="?page=athletes" class="nav-link <?= $page=='athletes'?'active':'' ?>"><i class="fa-solid fa-users-gear"></i> Manage Roster</a>
            <a href="?page=library_workouts" class="nav-link <?= $page=='library_workouts'?'active':'' ?>"><i class="fa-solid fa-book"></i> Exercise Library</a>
            <a href="?page=library_nutrition" class="nav-link <?= $page=='library_nutrition'?'active':'' ?>"><i class="fa-solid fa-utensils"></i> Food Library</a>
            <a href="?page=session_templates" class="nav-link <?= $page=='session_templates'?'active':'' ?>"><i class="fa-solid fa-file-invoice"></i> Session Templates</a>
            <a href="?page=create_session" class="nav-link <?= $page=='create_session'?'active':'' ?>"><i class="fa-solid fa-plus-circle"></i> Create Session</a>
        </nav>
    </div>
    <?php endif; ?>

    <?php if($isAdmin): ?>
    <div class="nav-group">
        <span class="nav-label">System Admin</span>
        <nav class="nav-menu">
            <a href="?page=admin_locations" class="nav-link <?= $page=='admin_locations'?'active':'' ?>"><i class="fa-solid fa-map-location-dot"></i> Locations</a>
            <a href="?page=admin_session_types" class="nav-link <?= $page=='admin_session_types'?'active':'' ?>"><i class="fa-solid fa-tags"></i> Session Types</a>
            <a href="?page=admin_discounts" class="nav-link <?= $page=='admin_discounts'?'active':'' ?>"><i class="fa-solid fa-percent"></i> Discounts</a>
            <a href="?page=admin_permissions" class="nav-link <?= $page=='admin_permissions'?'active':'' ?>"><i class="fa-solid fa-shield-halved"></i> Permissions</a>
            <a href="?page=settings" class="nav-link <?= $page=='settings'?'active':'' ?>"><i class="fa-solid fa-gears"></i> Global Settings</a>
        </nav>
    </div>
    <?php endif; ?>

    <div class="sidebar-footer">
        <a href="?page=profile" class="nav-link <?= $page=='profile'?'active':'' ?>"><i class="fa-solid fa-user-gear"></i> Profile Settings</a>
        <a href="logout.php" class="nav-link" style="color:#ef4444;"><i class="fa-solid fa-power-off"></i> Sign Out</a>
        <div style="display:flex; align-items:center; gap:12px; padding:10px; border-top:1px solid #1e293b; margin-top:10px;">
            <div class="avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
            <div style="font-size:12px;"><strong><?= htmlspecialchars($user_name) ?></strong><br><span style="color:var(--text); text-transform:capitalize;"><?= $user_role ?></span></div>
        </div>
    </div>
</aside>

<main class="main-content">
    <?php 
    if (file_exists($view_file)) { include $view_file; } 
    else { echo "<h2 style='color:#ef4444;'>Module missing: $view_file</h2>"; }
    ?>
</main>

</body>
</html>