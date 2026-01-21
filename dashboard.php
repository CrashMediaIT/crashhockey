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
    'admin_eval_framework' => 'views/admin_eval_framework.php',
    'admin_team_coaches'  => 'views/admin_team_coaches.php',
    'admin_system_check'  => 'views/admin_system_check.php',
    'admin_feature_import' => 'views/admin_feature_import.php',
    'admin_database_tools' => 'views/admin_database_tools.php',
    'admin_cron_jobs'     => 'views/admin_cron_jobs.php',
    'admin_database_backup' => 'views/admin_database_backup.php',
    'admin_database_restore' => 'views/admin_database_restore.php',
    'admin_audit_logs'    => 'views/admin_audit_logs.php',
    'admin_coach_termination' => 'views/admin_coach_termination.php',
    'admin_system_notifications' => 'views/admin_system_notifications.php',
    'admin_theme_settings' => 'views/admin_theme_settings.php',
    'reports'             => 'views/reports.php',
    'report_view'         => 'views/report_view.php',
    'scheduled_reports'   => 'views/scheduled_reports.php'
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
    <link rel="stylesheet" href="css/theme-variables.php">
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
            <a href="?page=reports" class="nav-link <?= $page=='reports'?'active':'' ?>"><i class="fa-solid fa-chart-bar"></i> Reports & Analytics</a>
            <a href="?page=scheduled_reports" class="nav-link <?= $page=='scheduled_reports'?'active':'' ?>"><i class="fa-solid fa-calendar-alt"></i> Scheduled Reports</a>
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
            <a href="?page=admin_team_coaches" class="nav-link <?= $page=='admin_team_coaches'?'active':'' ?>"><i class="fa-solid fa-user-tie"></i> Team Coaches</a>
            <a href="?page=admin_coach_termination" class="nav-link <?= $page=='admin_coach_termination'?'active':'' ?>"><i class="fa-solid fa-user-times"></i> Coach Termination</a>
            <a href="?page=admin_cron_jobs" class="nav-link <?= $page=='admin_cron_jobs'?'active':'' ?>"><i class="fa-solid fa-clock"></i> Cron Jobs</a>
            <a href="?page=admin_audit_logs" class="nav-link <?= $page=='admin_audit_logs'?'active':'' ?>"><i class="fa-solid fa-history"></i> Audit Logs</a>
            <a href="?page=admin_system_notifications" class="nav-link <?= $page=='admin_system_notifications'?'active':'' ?>"><i class="fa-solid fa-bullhorn"></i> System Notifications</a>
            <a href="?page=admin_theme_settings" class="nav-link <?= $page=='admin_theme_settings'?'active':'' ?>"><i class="fa-solid fa-palette"></i> Theme Settings</a>
            <a href="?page=admin_database_backup" class="nav-link <?= $page=='admin_database_backup'?'active':'' ?>"><i class="fa-solid fa-database"></i> Database Backup</a>
            <a href="?page=admin_database_restore" class="nav-link <?= $page=='admin_database_restore'?'active':'' ?>"><i class="fa-solid fa-upload"></i> Database Restore</a>
            <a href="?page=admin_system_check" class="nav-link <?= $page=='admin_system_check'?'active':'' ?>"><i class="fa-solid fa-shield-alt"></i> System Validation</a>
            <a href="?page=admin_feature_import" class="nav-link <?= $page=='admin_feature_import'?'active':'' ?>"><i class="fa-solid fa-file-import"></i> Feature Import</a>
            <a href="?page=admin_database_tools" class="nav-link <?= $page=='admin_database_tools'?'active':'' ?>"><i class="fa-solid fa-tools"></i> Database Tools</a>
            <a href="?page=admin_settings" class="nav-link <?= $page=='admin_settings'?'active':'' ?>"><i class="fa-solid fa-cog"></i> System Settings</a>
            <a href="?page=settings" class="nav-link <?= $page=='settings'?'active':'' ?>"><i class="fa-solid fa-gears"></i> Global Settings</a>
        </nav>
    </div>
    
    <div class="nav-group">
        <span class="nav-label">Accounting & Reports</span>
        <nav class="nav-menu">
            <a href="?page=reports" class="nav-link <?= $page=='reports'?'active':'' ?>"><i class="fa-solid fa-chart-bar"></i> Reports & Analytics</a>
            <a href="?page=scheduled_reports" class="nav-link <?= $page=='scheduled_reports'?'active':'' ?>"><i class="fa-solid fa-calendar-alt"></i> Scheduled Reports</a>
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

<?php
// Get active system notifications
$notifications_query = $pdo->query("
    SELECT * FROM system_notifications
    WHERE is_active = 1
    AND start_time <= NOW()
    AND (end_time IS NULL OR end_time >= NOW())
    ORDER BY created_at DESC
");
$active_notifications = $notifications_query->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (!empty($active_notifications)): ?>
    <style>
        .system-notification-banner {
            background: linear-gradient(135deg, #ff4500 0%, #ff6b00 100%);
            color: #fff;
            padding: 15px 40px;
            position: relative;
            z-index: 1000;
            border-bottom: 2px solid #ff8800;
            animation: slideDown 0.3s ease-out;
        }
        
        .system-notification-banner.maintenance {
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            border-bottom-color: #d97706;
        }
        
        .system-notification-banner.alert {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-bottom-color: #b91c1c;
        }
        
        .system-notification-banner.update {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-bottom-color: #1d4ed8;
        }
        
        .notification-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .notification-icon {
            font-size: 24px;
        }
        
        .notification-text h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            font-weight: 700;
        }
        
        .notification-text p {
            margin: 0;
            font-size: 14px;
            opacity: 0.95;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .system-notification-banner {
                padding: 12px 15px;
            }
            
            .notification-icon {
                font-size: 20px;
            }
            
            .notification-text h4 {
                font-size: 14px;
            }
            
            .notification-text p {
                font-size: 12px;
            }
        }
    </style>
    
    <?php foreach ($active_notifications as $notif): ?>
        <div class="system-notification-banner <?= htmlspecialchars($notif['notification_type']) ?>">
            <div class="notification-content">
                <div class="notification-icon">
                    <?php if ($notif['notification_type'] === 'maintenance'): ?>
                        <i class="fas fa-tools"></i>
                    <?php elseif ($notif['notification_type'] === 'alert'): ?>
                        <i class="fas fa-exclamation-triangle"></i>
                    <?php else: ?>
                        <i class="fas fa-info-circle"></i>
                    <?php endif; ?>
                </div>
                <div class="notification-text">
                    <h4><?= htmlspecialchars($notif['title']) ?></h4>
                    <p><?= htmlspecialchars($notif['message']) ?></p>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

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