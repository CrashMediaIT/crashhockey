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
$isParent  = ($user_role === 'parent');

$page = $_GET['page'] ?? 'home';

// FULL ROUTING TABLE
$allowed_pages = [
    'home'                => $isParent ? 'views/parent_home.php' : 'views/home.php',
    'stats'               => 'views/stats.php',
    'schedule'            => 'views/schedule.php',
    'session_history'     => 'views/session_history.php',
    'payment_history'     => 'views/payment_history.php',
    'user_credits'        => 'views/user_credits.php',
    'profile'             => 'views/profile.php',
    'video_library'       => 'views/video.php',
    'workout_builder'     => 'views/workouts.php',
    'nutrition_builder'   => 'views/nutrition.php',
    'library_workouts'    => 'views/library_workouts.php',
    'library_nutrition'   => 'views/library_nutrition.php',
    'drills'              => 'views/drills.php',
    'practice_plans'      => 'views/practice_plans.php',
    'ihs_import'          => 'views/ihs_import.php',
    'notifications'       => 'views/notifications.php',
    'athletes'            => 'views/athletes.php',
    'create_session'      => 'views/create_session.php',
    'session_templates'   => 'views/library_sessions.php',
    'session_detail'      => 'views/session_detail.php',
    'packages'            => 'views/packages.php',
    'admin_locations'     => 'views/admin_locations.php',
    'admin_session_types' => 'views/admin_session_types.php',
    'admin_discounts'     => 'views/admin_discounts.php',
    'admin_permissions'   => 'views/admin_permissions.php',
    'admin_age_skill'     => 'views/admin_age_skill.php',
    'admin_plan_categories' => 'views/admin_plan_categories.php',
    'admin_packages'      => 'views/admin_packages.php',
    'accounting'          => 'views/accounting.php',
    'reports_income'      => 'views/reports_income.php',
    'reports_athlete'     => 'views/reports_athlete.php',
    'accounts_payable'    => 'views/accounts_payable.php',
    'expense_categories'  => 'views/expense_categories.php',
    'billing_dashboard'   => 'views/billing_dashboard.php',
    'mileage_tracker'     => 'views/mileage_tracker.php',
    'refunds'             => 'views/refunds.php',
    'settings'            => 'views/settings.php',
    'manage_athletes'     => 'views/manage_athletes.php',
    'goals'               => 'views/goals.php',
    'evaluations_goals'   => 'views/evaluations_goals.php',
    'evaluations_skills'  => 'views/evaluations_skills.php',
    'admin_settings'      => 'views/admin_settings.php',
    'admin_eval_framework' => 'views/admin_eval_framework.php'
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
        :root { --primary: #7000a4; --bg: #06080b; --sidebar: #020305; --border: #1e293b; --text: #94a3b8; }
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
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 10000;
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 12px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
        }
        
        /* Responsive Styles */
        @media (max-width: 1024px) {
            .main-content {
                padding: 30px 20px;
            }
        }
        
        @media (max-width: 768px) {
            body {
                display: block;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                height: 100vh;
                z-index: 9999;
                transition: left 0.3s ease;
            }
            
            .sidebar.mobile-open {
                left: 0;
            }
            
            .main-content {
                padding: 70px 15px 20px 15px;
                width: 100%;
            }
            
            .mobile-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.7);
                z-index: 9998;
            }
            
            .mobile-overlay.active {
                display: block;
            }
        }
    </style>
</head>
<body>

<button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
    <i class="fas fa-bars"></i>
</button>

<div class="mobile-overlay" onclick="toggleMobileMenu()"></div>

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
            <a href="?page=user_credits" class="nav-link <?= $page=='user_credits'?'active':'' ?>"><i class="fa-solid fa-wallet"></i> My Store Credits</a>
            <a href="?page=packages" class="nav-link <?= $page=='packages'?'active':'' ?>"><i class="fa-solid fa-box"></i> Session Packages</a>
        </nav>
    </div>

    <div class="nav-group">
        <span class="nav-label">Goals & Evaluations</span>
        <nav class="nav-menu">
            <a href="?page=goals" class="nav-link <?= $page=='goals'?'active':'' ?>"><i class="fa-solid fa-bullseye"></i> Goals Tracker</a>
            <a href="?page=evaluations_goals" class="nav-link <?= $page=='evaluations_goals'?'active':'' ?>"><i class="fa-solid fa-tasks"></i> Goal Evaluations</a>
            <a href="?page=evaluations_skills" class="nav-link <?= $page=='evaluations_skills'?'active':'' ?>"><i class="fa-solid fa-star"></i> Skills Evaluations</a>
        </nav>
    </div>

    <?php if($isParent): ?>
    <div class="nav-group">
        <span class="nav-label">Parent Management</span>
        <nav class="nav-menu">
            <a href="?page=manage_athletes" class="nav-link <?= $page=='manage_athletes'?'active':'' ?>"><i class="fa-solid fa-user-plus"></i> Manage Athletes</a>
        </nav>
    </div>
    <?php endif; ?>

    <?php if($isCoach || $isAdmin): ?>
    <div class="nav-group">
        <span class="nav-label">Coach Management</span>
        <nav class="nav-menu">
            <a href="?page=athletes" class="nav-link <?= $page=='athletes'?'active':'' ?>"><i class="fa-solid fa-users-gear"></i> Manage Roster</a>
            <a href="?page=library_workouts" class="nav-link <?= $page=='library_workouts'?'active':'' ?>"><i class="fa-solid fa-book"></i> Exercise Library</a>
            <a href="?page=library_nutrition" class="nav-link <?= $page=='library_nutrition'?'active':'' ?>"><i class="fa-solid fa-utensils"></i> Food Library</a>
            <a href="?page=session_templates" class="nav-link <?= $page=='session_templates'?'active':'' ?>"><i class="fa-solid fa-file-invoice"></i> Session Templates</a>
            <a href="?page=create_session" class="nav-link <?= $page=='create_session'?'active':'' ?>"><i class="fa-solid fa-plus-circle"></i> Create Session</a>
            <a href="?page=ihs_import" class="nav-link <?= $page=='ihs_import'?'active':'' ?>"><i class="fa-solid fa-file-import"></i> IHS Import</a>
        </nav>
    </div>
    <?php endif; ?>

    <?php if($isAdmin): ?>
    <div class="nav-group">
        <span class="nav-label">System Admin</span>
        <nav class="nav-menu">
            <a href="?page=admin_locations" class="nav-link <?= $page=='admin_locations'?'active':'' ?>"><i class="fa-solid fa-map-location-dot"></i> Locations</a>
            <a href="?page=admin_session_types" class="nav-link <?= $page=='admin_session_types'?'active':'' ?>"><i class="fa-solid fa-tags"></i> Session Types</a>
            <a href="?page=admin_age_skill" class="nav-link <?= $page=='admin_age_skill'?'active':'' ?>"><i class="fa-solid fa-users-cog"></i> Age & Skill Levels</a>
            <a href="?page=admin_plan_categories" class="nav-link <?= $page=='admin_plan_categories'?'active':'' ?>"><i class="fa-solid fa-folder-tree"></i> Plan Categories</a>
            <a href="?page=admin_discounts" class="nav-link <?= $page=='admin_discounts'?'active':'' ?>"><i class="fa-solid fa-percent"></i> Discounts</a>
            <a href="?page=admin_packages" class="nav-link <?= $page=='admin_packages'?'active':'' ?>"><i class="fa-solid fa-box-open"></i> Packages</a>
            <a href="?page=admin_permissions" class="nav-link <?= $page=='admin_permissions'?'active':'' ?>"><i class="fa-solid fa-shield-halved"></i> Permissions</a>
            <a href="?page=admin_eval_framework" class="nav-link <?= $page=='admin_eval_framework'?'active':'' ?>"><i class="fa-solid fa-list-check"></i> Eval Framework</a>
            <a href="?page=admin_settings" class="nav-link <?= $page=='admin_settings'?'active':'' ?>"><i class="fa-solid fa-cog"></i> System Settings</a>
            <a href="?page=settings" class="nav-link <?= $page=='settings'?'active':'' ?>"><i class="fa-solid fa-gears"></i> Global Settings</a>
        </nav>
    </div>
    
    <div class="nav-group">
        <span class="nav-label">Accounting & Reports</span>
        <nav class="nav-menu">
            <a href="?page=billing_dashboard" class="nav-link <?= $page=='billing_dashboard'?'active':'' ?>"><i class="fa-solid fa-chart-pie"></i> Billing Dashboard</a>
            <a href="?page=accounting" class="nav-link <?= $page=='accounting'?'active':'' ?>"><i class="fa-solid fa-chart-line"></i> Accounting</a>
            <a href="?page=reports_income" class="nav-link <?= $page=='reports_income'?'active':'' ?>"><i class="fa-solid fa-file-invoice-dollar"></i> Income Reports</a>
            <a href="?page=reports_athlete" class="nav-link <?= $page=='reports_athlete'?'active':'' ?>"><i class="fa-solid fa-user-tag"></i> Athlete Billing</a>
            <a href="?page=accounts_payable" class="nav-link <?= $page=='accounts_payable'?'active':'' ?>"><i class="fa-solid fa-receipt"></i> Expenses</a>
            <a href="?page=expense_categories" class="nav-link <?= $page=='expense_categories'?'active':'' ?>"><i class="fa-solid fa-tags"></i> Expense Categories</a>
            <a href="?page=mileage_tracker" class="nav-link <?= $page=='mileage_tracker'?'active':'' ?>"><i class="fa-solid fa-route"></i> Mileage Tracker</a>
            <a href="?page=refunds" class="nav-link <?= $page=='refunds'?'active':'' ?>"><i class="fa-solid fa-undo"></i> Refunds</a>
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

<script>
function toggleMobileMenu() {
    document.querySelector('.sidebar').classList.toggle('mobile-open');
    document.querySelector('.mobile-overlay').classList.toggle('active');
}

// Close mobile menu when clicking a link
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
            toggleMobileMenu();
        }
    });
});
</script>

</body>
</html>