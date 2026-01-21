<?php
// =========================================================
// DASHBOARD CONTROLLER - COMPREHENSIVE VERSION WITH NEW NAVIGATION
// =========================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/db_config.php';

if (!isset($_SESSION['logged_in'])) { header("Location: login.php"); exit(); }

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'athlete';
$user_name = $_SESSION['user_name'] ?? 'Guest';

// Role checks including new roles
$isAdmin       = ($user_role === 'admin');
$isCoach       = ($user_role === 'coach');
$isHealthCoach = ($user_role === 'health_coach');
$isTeamCoach   = ($user_role === 'team_coach');
$isParent      = ($user_role === 'parent');

// Combined role checks for sections
$isAnyCoach    = ($isCoach || $isHealthCoach || $isAdmin);
$isTeamStaff   = ($isTeamCoach);

$page = $_GET['page'] ?? 'home';

// FULL ROUTING TABLE
$allowed_pages = [
    // Main Menu
    'home'                    => 'views/home.php',
    'stats'                   => 'views/stats.php',
    'upcoming_sessions'       => 'views/sessions_upcoming.php',
    'booking'                 => 'views/sessions_booking.php',
    'drill_review'            => 'views/video_drill_review.php',
    'coaches_reviews'         => 'views/video_coach_reviews.php',
    'strength_conditioning'   => 'views/health_workouts.php',
    'nutrition'               => 'views/health_nutrition.php',
    
    // Team (Team Coaches)
    'team_roster'             => 'views/team_roster.php',
    
    // Coaches Corner
    'drill_library'           => 'views/drills_library.php',
    'create_drill'            => 'views/drills_create.php',
    'import_drill'            => 'views/drills_import.php',
    'practice_library'        => 'views/practice_library.php',
    'create_practice'         => 'views/practice_create.php',
    'roster'                  => 'views/coach_roster.php',
    'mileage'                 => 'views/travel_mileage.php',
    
    // Accounting and Reports (Admin)
    'accounting_dashboard'    => 'views/accounting_dashboard.php',
    'billing_dashboard'       => 'views/accounting_billing.php',
    'reports'                 => 'views/accounting_reports.php',
    'schedules'               => 'views/accounting_schedules.php',
    'credits_refunds'         => 'views/accounting_credits.php',
    'expenses'                => 'views/accounting_expenses.php',
    'products'                => 'views/accounting_products.php',
    
    // HR (Admin)
    'termination'             => 'views/hr_termination.php',
    
    // Administration (Admin)
    'all_users'               => 'views/admin_users.php',
    'categories'              => 'views/admin_categories.php',
    'eval_framework'          => 'views/admin_eval_framework.php',
    'system_notification'     => 'views/admin_notifications.php',
    'audit_log'               => 'views/admin_audit_log.php',
    'cron_jobs'               => 'views/admin_cron_jobs.php',
    'system_tools'            => 'views/admin_system_tools.php',
    
    // Additional views
    'profile'                 => 'views/profile.php',
    'settings'                => 'views/settings.php'
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
        :root { 
            --primary: #6B46C1; 
            --primary-hover: #7C3AED; 
            --primary-light: #8B5CF6;
            --bg: #0A0A0F; 
            --bg-secondary: #13131A;
            --sidebar: #0D0D14; 
            --border: #2D2D3F; 
            --border-light: #3A3A4F;
            --text: #A8A8B8; 
            --text-muted: #6B6B7B;
            --card-bg: #16161F;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); font-family: 'Inter', sans-serif; color: #fff; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 280px; background: var(--sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 25px; overflow-y: auto; }
        .brand { font-size: 22px; font-weight: 900; margin-bottom: 40px; letter-spacing: -1px; display: flex; align-items: center; gap: 10px; text-decoration: none; color: #fff; }
        .brand span { color: var(--primary); }
        .brand img { height: 35px; width: auto; }
        
        /* Navigation Groups */
        .nav-group { margin-bottom: 25px; }
        .nav-label { font-size: 10px; text-transform: uppercase; color: #475569; font-weight: 800; margin-bottom: 12px; display: block; letter-spacing: 1.5px; }
        .nav-menu { list-style: none; padding: 0; margin: 0; }
        .nav-link { display: flex; align-items: center; gap: 14px; padding: 10px 15px; color: var(--text); text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: 600; transition: 0.2s; margin-bottom: 2px; cursor: pointer; }
        .nav-link i { width: 18px; text-align: center; }
        .nav-link:hover, .nav-link.active { background: rgba(107, 70, 193, 0.1); color: var(--primary-light); }
        
        /* Collapsible Submenus */
        .nav-parent { display: flex; align-items: center; gap: 14px; padding: 10px 15px; color: var(--text); border-radius: 8px; font-size: 13px; font-weight: 600; transition: 0.2s; margin-bottom: 2px; cursor: pointer; justify-content: space-between; }
        .nav-parent:hover { background: rgba(107, 70, 193, 0.1); color: var(--primary-light); }
        .nav-parent i.icon { width: 18px; text-align: center; }
        .nav-parent i.chevron { font-size: 10px; transition: transform 0.2s; }
        .nav-parent.expanded i.chevron { transform: rotate(90deg); }
        .nav-submenu { list-style: none; padding: 0; margin: 0 0 0 32px; max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .nav-submenu.expanded { max-height: 500px; }
        .nav-submenu .nav-link { font-size: 12px; padding: 8px 15px; }
        
        /* Main Content */
        .main-content { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow: hidden; }
        
        /* Top Bar for Parent Selector */
        .top-bar { display: flex; justify-content: flex-end; padding: 20px 40px; border-bottom: 1px solid var(--border); background: var(--sidebar); }
        .athlete-selector { display: flex; align-items: center; gap: 10px; }
        .athlete-selector label { font-size: 13px; color: var(--text); font-weight: 600; }
        .athlete-selector select { padding: 8px 15px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; }
        .athlete-selector select:focus { outline: none; border-color: var(--primary); }
        
        /* Content Area */
        .content-area { flex: 1; padding: 40px; overflow-y: auto; }
        
        /* Sidebar Footer */
        .sidebar-footer { margin-top: auto; padding-top: 20px; border-top: 1px solid var(--border); }
        .avatar { width: 35px; height: 35px; background: linear-gradient(135deg, var(--primary), var(--primary-light)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; }
        
        /* Custom Scrollbar */
        .sidebar::-webkit-scrollbar, .content-area::-webkit-scrollbar { width: 8px; }
        .sidebar::-webkit-scrollbar-track, .content-area::-webkit-scrollbar-track { background: var(--bg); }
        .sidebar::-webkit-scrollbar-thumb, .content-area::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        .sidebar::-webkit-scrollbar-thumb:hover, .content-area::-webkit-scrollbar-thumb:hover { background: var(--border-light); }
        * { scrollbar-width: thin; scrollbar-color: var(--border) var(--bg); }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="?page=home" class="brand">
        <img src="https://images.crashmedia.ca/images/2026/01/18/logo.png" alt="Logo">
        CRASH <span>HOCKEY</span>
    </a>
    
    <!-- MAIN MENU (For all users) -->
    <div class="nav-group">
        <span class="nav-label">Main Menu</span>
        <nav class="nav-menu">
            <a href="?page=home" class="nav-link <?= $page=='home'?'active':'' ?>">
                <i class="fa-solid fa-house icon"></i> Home
            </a>
            <a href="?page=stats" class="nav-link <?= $page=='stats'?'active':'' ?>">
                <i class="fa-solid fa-chart-line icon"></i> Performance Stats
            </a>
            
            <!-- Sessions Submenu -->
            <div class="nav-parent" onclick="toggleSubmenu(this)">
                <span><i class="fa-solid fa-calendar-check icon"></i> Sessions</span>
                <i class="fa-solid fa-chevron-right chevron"></i>
            </div>
            <ul class="nav-submenu">
                <li><a href="?page=upcoming_sessions" class="nav-link <?= $page=='upcoming_sessions'?'active':'' ?>">Upcoming Sessions</a></li>
                <li><a href="?page=booking" class="nav-link <?= $page=='booking'?'active':'' ?>">Booking</a></li>
            </ul>
            
            <!-- Video Submenu -->
            <div class="nav-parent" onclick="toggleSubmenu(this)">
                <span><i class="fa-solid fa-video icon"></i> Video</span>
                <i class="fa-solid fa-chevron-right chevron"></i>
            </div>
            <ul class="nav-submenu">
                <li><a href="?page=drill_review" class="nav-link <?= $page=='drill_review'?'active':'' ?>">Drill Review</a></li>
                <li>
                    <a href="?page=coaches_reviews" class="nav-link <?= $page=='coaches_reviews'?'active':'' ?>">
                        Coaches Reviews 
                        <?php if($isAnyCoach): ?>
                        <span style="margin-left:auto; font-size:11px; color:var(--primary);">[Upload]</span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            
            <!-- Health Submenu -->
            <div class="nav-parent" onclick="toggleSubmenu(this)">
                <span><i class="fa-solid fa-heart-pulse icon"></i> Health</span>
                <i class="fa-solid fa-chevron-right chevron"></i>
            </div>
            <ul class="nav-submenu">
                <li><a href="?page=strength_conditioning" class="nav-link <?= $page=='strength_conditioning'?'active':'' ?>">Strength & Conditioning</a></li>
                <li><a href="?page=nutrition" class="nav-link <?= $page=='nutrition'?'active':'' ?>">Nutrition</a></li>
            </ul>
        </nav>
    </div>

    <!-- TEAM (Team Coaches only) -->
    <?php if($isTeamStaff): ?>
    <div class="nav-group">
        <span class="nav-label">Team</span>
        <nav class="nav-menu">
            <a href="?page=team_roster" class="nav-link <?= $page=='team_roster'?'active':'' ?>">
                <i class="fa-solid fa-users icon"></i> Roster
            </a>
        </nav>
    </div>
    <?php endif; ?>

    <!-- COACHES CORNER (Coaches, Health Coaches, and Admins) -->
    <?php if($isAnyCoach): ?>
    <div class="nav-group">
        <span class="nav-label">Coaches Corner</span>
        <nav class="nav-menu">
            <!-- Drills Submenu -->
            <div class="nav-parent" onclick="toggleSubmenu(this)">
                <span><i class="fa-solid fa-clipboard-list icon"></i> Drills</span>
                <i class="fa-solid fa-chevron-right chevron"></i>
            </div>
            <ul class="nav-submenu">
                <li><a href="?page=drill_library" class="nav-link <?= $page=='drill_library'?'active':'' ?>">Library</a></li>
                <li><a href="?page=create_drill" class="nav-link <?= $page=='create_drill'?'active':'' ?>">Create a Drill</a></li>
                <li><a href="?page=import_drill" class="nav-link <?= $page=='import_drill'?'active':'' ?>">Import a Drill</a></li>
            </ul>
            
            <!-- Practice Plans Submenu -->
            <div class="nav-parent" onclick="toggleSubmenu(this)">
                <span><i class="fa-solid fa-file-lines icon"></i> Practice Plans</span>
                <i class="fa-solid fa-chevron-right chevron"></i>
            </div>
            <ul class="nav-submenu">
                <li><a href="?page=practice_library" class="nav-link <?= $page=='practice_library'?'active':'' ?>">Library</a></li>
                <li><a href="?page=create_practice" class="nav-link <?= $page=='create_practice'?'active':'' ?>">Create a Practice</a></li>
            </ul>
            
            <a href="?page=roster" class="nav-link <?= $page=='roster'?'active':'' ?>">
                <i class="fa-solid fa-users-gear icon"></i> Roster
            </a>
            
            <!-- Travel Submenu -->
            <div class="nav-parent" onclick="toggleSubmenu(this)">
                <span><i class="fa-solid fa-plane icon"></i> Travel</span>
                <i class="fa-solid fa-chevron-right chevron"></i>
            </div>
            <ul class="nav-submenu">
                <li><a href="?page=mileage" class="nav-link <?= $page=='mileage'?'active':'' ?>">Mileage</a></li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>

    <!-- ACCOUNTING AND REPORTS (Admins only) -->
    <?php if($isAdmin): ?>
    <div class="nav-group">
        <span class="nav-label">Accounting & Reports</span>
        <nav class="nav-menu">
            <a href="?page=accounting_dashboard" class="nav-link <?= $page=='accounting_dashboard'?'active':'' ?>">
                <i class="fa-solid fa-chart-pie icon"></i> Accounting Dashboard
            </a>
            <a href="?page=billing_dashboard" class="nav-link <?= $page=='billing_dashboard'?'active':'' ?>">
                <i class="fa-solid fa-file-invoice-dollar icon"></i> Billing Dashboard
            </a>
            <a href="?page=reports" class="nav-link <?= $page=='reports'?'active':'' ?>">
                <i class="fa-solid fa-file-chart-column icon"></i> Reports
            </a>
            <a href="?page=schedules" class="nav-link <?= $page=='schedules'?'active':'' ?>">
                <i class="fa-solid fa-calendar-days icon"></i> Schedules
            </a>
            <a href="?page=credits_refunds" class="nav-link <?= $page=='credits_refunds'?'active':'' ?>">
                <i class="fa-solid fa-money-bill-transfer icon"></i> Credits & Refunds
            </a>
            <a href="?page=expenses" class="nav-link <?= $page=='expenses'?'active':'' ?>">
                <i class="fa-solid fa-receipt icon"></i> Expenses
            </a>
            <a href="?page=products" class="nav-link <?= $page=='products'?'active':'' ?>">
                <i class="fa-solid fa-box-open icon"></i> Products
            </a>
        </nav>
    </div>
    <?php endif; ?>

    <!-- HR (Admins only) -->
    <?php if($isAdmin): ?>
    <div class="nav-group">
        <span class="nav-label">HR</span>
        <nav class="nav-menu">
            <a href="?page=termination" class="nav-link <?= $page=='termination'?'active':'' ?>">
                <i class="fa-solid fa-user-slash icon"></i> Termination
            </a>
        </nav>
    </div>
    <?php endif; ?>

    <!-- ADMINISTRATION (Admins only) -->
    <?php if($isAdmin): ?>
    <div class="nav-group">
        <span class="nav-label">Administration</span>
        <nav class="nav-menu">
            <a href="?page=all_users" class="nav-link <?= $page=='all_users'?'active':'' ?>">
                <i class="fa-solid fa-users icon"></i> All Users
            </a>
            <a href="?page=categories" class="nav-link <?= $page=='categories'?'active':'' ?>">
                <i class="fa-solid fa-folder-tree icon"></i> Categories
            </a>
            <a href="?page=eval_framework" class="nav-link <?= $page=='eval_framework'?'active':'' ?>">
                <i class="fa-solid fa-clipboard-check icon"></i> Eval Framework
            </a>
            <a href="?page=system_notification" class="nav-link <?= $page=='system_notification'?'active':'' ?>">
                <i class="fa-solid fa-bell icon"></i> System Notification
            </a>
            <a href="?page=audit_log" class="nav-link <?= $page=='audit_log'?'active':'' ?>">
                <i class="fa-solid fa-list-check icon"></i> Audit Log
            </a>
            <a href="?page=cron_jobs" class="nav-link <?= $page=='cron_jobs'?'active':'' ?>">
                <i class="fa-solid fa-clock icon"></i> Cron Jobs
            </a>
            <a href="?page=system_tools" class="nav-link <?= $page=='system_tools'?'active':'' ?>">
                <i class="fa-solid fa-screwdriver-wrench icon"></i> System Tools
            </a>
        </nav>
    </div>
    <?php endif; ?>

    <div class="sidebar-footer">
        <a href="?page=profile" class="nav-link <?= $page=='profile'?'active':'' ?>">
            <i class="fa-solid fa-user-gear"></i> Profile Settings
        </a>
        <a href="logout.php" class="nav-link" style="color:#ef4444;">
            <i class="fa-solid fa-power-off"></i> Sign Out
        </a>
        <div style="display:flex; align-items:center; gap:12px; padding:10px; border-top:1px solid #1e293b; margin-top:10px;">
            <div class="avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
            <div style="font-size:12px;">
                <strong><?= htmlspecialchars($user_name) ?></strong><br>
                <span style="color:var(--text); text-transform:capitalize;"><?= str_replace('_', ' ', $user_role) ?></span>
            </div>
        </div>
    </div>
</aside>

<main class="main-content">
    <!-- Top Bar with Parent Athlete Selector -->
    <?php if($isParent): ?>
    <div class="top-bar">
        <div class="athlete-selector">
            <label for="athlete-select">Viewing as:</label>
            <select id="athlete-select" onchange="switchAthlete(this.value)">
                <option value="">Select Athlete</option>
                <?php
                // Fetch parent's children/athletes from database
                $stmt = $conn->prepare("SELECT id, name FROM users WHERE parent_id = ? AND user_role = 'athlete'");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while($athlete = $result->fetch_assoc()):
                    $selected = (isset($_SESSION['viewing_athlete_id']) && $_SESSION['viewing_athlete_id'] == $athlete['id']) ? 'selected' : '';
                ?>
                    <option value="<?= $athlete['id'] ?>" <?= $selected ?>><?= htmlspecialchars($athlete['name']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="content-area">
        <?php 
        if (file_exists($view_file)) { include $view_file; } 
        else { echo "<h2 style='color:#ef4444;'>Module missing: $view_file</h2>"; }
        ?>
    </div>
</main>

<script>
// Toggle submenu expansion
function toggleSubmenu(element) {
    element.classList.toggle('expanded');
    const submenu = element.nextElementSibling;
    if (submenu && submenu.classList.contains('nav-submenu')) {
        submenu.classList.toggle('expanded');
    }
}

// Persist submenu state on page load based on active page
document.addEventListener('DOMContentLoaded', function() {
    // Find all active nav links in submenus
    const activeSubLinks = document.querySelectorAll('.nav-submenu .nav-link.active');
    activeSubLinks.forEach(link => {
        const submenu = link.closest('.nav-submenu');
        const parent = submenu?.previousElementSibling;
        if (submenu && parent) {
            submenu.classList.add('expanded');
            parent.classList.add('expanded');
        }
    });
});

// Switch athlete for parent view
function switchAthlete(athleteId) {
    if (athleteId) {
        fetch('process_switch_athlete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'athlete_id=' + athleteId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to switch athlete view');
            }
        });
    }
}
</script>

</body>
</html>