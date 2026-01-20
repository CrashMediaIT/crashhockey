<?php
/**
 * Enhanced Home Dashboard
 * Shows role-specific content for athletes, coaches, and admins
 */

require_once __DIR__ . '/../security.php';

$is_athlete = ($user_role === 'athlete');
$is_coach = ($user_role === 'coach' || $user_role === 'coach_plus' || $user_role === 'admin');

// Get unread notifications count
$notif_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_status = 0");
$notif_stmt->execute([$user_id]);
$unread_count = $notif_stmt->fetchColumn();
?>

<style>
    :root {
        --primary: #7000a4;
    }
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 25px;
        margin-bottom: 30px;
    }
    .dashboard-card {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        padding: 25px;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .card-title {
        font-size: 20px;
        font-weight: 700;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-action {
        font-size: 13px;
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    .stat-box {
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        padding: 15px;
        text-align: center;
    }
    .stat-value {
        font-size: 32px;
        font-weight: 900;
        color: var(--primary);
        display: block;
    }
    .stat-label {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 700;
        margin-top: 5px;
    }
    .session-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .session-item {
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        padding: 15px;
        transition: 0.2s;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    .session-item:hover {
        border-color: var(--primary);
        transform: translateX(5px);
    }
    .session-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 10px;
    }
    .session-title {
        font-size: 16px;
        font-weight: 700;
        color: #fff;
    }
    .session-date {
        font-size: 13px;
        color: var(--primary);
        font-weight: 600;
    }
    .session-meta {
        display: flex;
        gap: 15px;
        font-size: 13px;
        color: #64748b;
    }
    .session-meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .notification-badge {
        background: var(--primary);
        color: #fff;
        border-radius: 12px;
        padding: 2px 8px;
        font-size: 11px;
        font-weight: 700;
        margin-left: 8px;
    }
    .notification-item {
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 10px;
        display: flex;
        gap: 15px;
        align-items: start;
    }
    .notification-item.unread {
        border-color: var(--primary);
        background: rgba(255, 77, 0, 0.05);
    }
    .notification-icon {
        width: 40px;
        height: 40px;
        background: rgba(255, 77, 0, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        flex-shrink: 0;
    }
    .notification-content {
        flex: 1;
    }
    .notification-title {
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 5px;
    }
    .notification-message {
        font-size: 13px;
        color: #94a3b8;
        line-height: 1.5;
    }
    .notification-time {
        font-size: 12px;
        color: #64748b;
        margin-top: 5px;
    }
    .team-section {
        margin-bottom: 20px;
    }
    .team-header {
        font-size: 16px;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #1e293b;
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #64748b;
    }
    .empty-state i {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.3;
    }
    .coach-info {
        background: rgba(255, 77, 0, 0.1);
        border: 1px solid var(--primary);
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .coach-info strong {
        color: var(--primary);
    }
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h1 style="font-size: 28px; font-weight: 900; color: #fff; margin: 0;">
        <i class="fas fa-home"></i> Dashboard
    </h1>
    <a href="?page=profile" style="font-size: 14px; color: var(--primary); text-decoration: none; font-weight: 600;">
        <i class="fas fa-user-gear"></i> My Profile
    </a>
</div>

<?php if ($is_athlete): ?>
    <!-- ATHLETE DASHBOARD -->
    
    <!-- Coach Assignment Info -->
    <?php
    $coach_stmt = $pdo->prepare("SELECT u.first_name, u.last_name, u.email FROM users u INNER JOIN users athlete ON athlete.assigned_coach_id = u.id WHERE athlete.id = ?");
    $coach_stmt->execute([$user_id]);
    $assigned_coach = $coach_stmt->fetch();
    
    if ($assigned_coach):
    ?>
    <div class="coach-info">
        <strong><i class="fas fa-user-tie"></i> Your Coach:</strong> 
        <?= htmlspecialchars($assigned_coach['first_name'] . ' ' . $assigned_coach['last_name']) ?>
        <span style="color: #64748b; margin-left: 10px;">(<?= htmlspecialchars($assigned_coach['email']) ?>)</span>
    </div>
    <?php endif; ?>
    
    <!-- Notifications -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-bell"></i> Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?= $unread_count ?></span>
                <?php endif; ?>
            </h2>
            <a href="?page=notifications" class="card-action">View All →</a>
        </div>
        
        <?php
        $notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
        $notifs->execute([$user_id]);
        $notifications = $notifs->fetchAll();
        
        if (empty($notifications)):
        ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash"></i>
                <p>No notifications yet</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <div class="notification-item <?= $notif['read_status'] == 0 ? 'unread' : '' ?>">
                    <div class="notification-icon">
                        <i class="fas fa-<?= getNotificationIcon($notif['type']) ?>"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-title"><?= htmlspecialchars($notif['title']) ?></div>
                        <div class="notification-message"><?= htmlspecialchars($notif['message']) ?></div>
                        <div class="notification-time">
                            <?= timeAgo($notif['created_at']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="dashboard-grid">
        <!-- Current Season Stats -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-line"></i> Current Season Stats</h2>
                <a href="?page=stats" class="card-action">Full Stats →</a>
            </div>
            
            <?php
            // Get current teams
            $teams_stmt = $pdo->prepare("
                SELECT t.*, s.* 
                FROM athlete_teams t
                LEFT JOIN athlete_stats s ON t.id = s.team_id AND s.user_id = ?
                WHERE t.user_id = ? AND t.is_current = 1
                ORDER BY t.created_at DESC
            ");
            $teams_stmt->execute([$user_id, $user_id]);
            $teams = $teams_stmt->fetchAll();
            
            if (empty($teams)):
            ?>
                <div class="empty-state">
                    <i class="fas fa-hockey-puck"></i>
                    <p>No current season stats yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($teams as $team): ?>
                    <div class="team-section">
                        <div class="team-header">
                            <?= htmlspecialchars($team['team_name']) ?> - <?= htmlspecialchars($team['season_year']) ?>
                        </div>
                        <div class="stats-grid">
                            <div class="stat-box">
                                <span class="stat-value"><?= $team['games_played'] ?? 0 ?></span>
                                <span class="stat-label">Games Played</span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-value"><?= $team['goals'] ?? 0 ?></span>
                                <span class="stat-label">Goals</span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-value"><?= $team['assists'] ?? 0 ?></span>
                                <span class="stat-label">Assists</span>
                            </div>
                            <div class="stat-box">
                                <span class="stat-value"><?= $team['points'] ?? 0 ?></span>
                                <span class="stat-label">Points</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Upcoming Sessions -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-calendar-alt"></i> Upcoming Sessions</h2>
                <a href="?page=schedule" class="card-action">View Schedule →</a>
            </div>
            
            <?php
            // Get upcoming sessions with practice plans
            $sessions_stmt = $pdo->prepare("
                SELECT s.*, pp.title as plan_title, b.id as booking_id
                FROM sessions s
                LEFT JOIN practice_plans pp ON s.practice_plan_id = pp.id
                LEFT JOIN bookings b ON s.id = b.session_id AND b.user_id = ? AND b.status = 'paid'
                WHERE s.session_date >= CURDATE()
                ORDER BY s.session_date ASC, s.session_time ASC
                LIMIT 5
            ");
            $sessions_stmt->execute([$user_id]);
            $sessions = $sessions_stmt->fetchAll();
            
            if (empty($sessions)):
            ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <p>No upcoming sessions</p>
                </div>
            <?php else: ?>
                <div class="session-list">
                    <?php foreach ($sessions as $session): ?>
                        <a href="?page=session_detail&id=<?= $session['id'] ?>" class="session-item">
                            <div class="session-header">
                                <div class="session-title"><?= htmlspecialchars($session['title']) ?></div>
                                <div class="session-date">
                                    <?= date('M d', strtotime($session['session_date'])) ?>
                                </div>
                            </div>
                            <div class="session-meta">
                                <span class="session-meta-item">
                                    <i class="fas fa-clock"></i>
                                    <?= date('g:i A', strtotime($session['session_time'])) ?>
                                </span>
                                <span class="session-meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($session['arena']) ?>
                                </span>
                                <?php if ($session['plan_title']): ?>
                                    <span class="session-meta-item">
                                        <i class="fas fa-clipboard-list"></i>
                                        <?= htmlspecialchars($session['plan_title']) ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($session['booking_id']): ?>
                                    <span class="session-meta-item" style="color: #00ff88;">
                                        <i class="fas fa-check-circle"></i>
                                        Booked
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- COACH/ADMIN DASHBOARD -->
    
    <div class="dashboard-grid">
        <!-- Quick Stats -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-chart-bar"></i> Quick Stats</h2>
            </div>
            
            <?php
            // Get coach stats
            $total_athletes = $pdo->prepare("SELECT COUNT(*) FROM users WHERE assigned_coach_id = ? AND role = 'athlete'");
            $total_athletes->execute([$user_id]);
            $athlete_count = $total_athletes->fetchColumn();
            
            $upcoming_sessions = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE session_date >= CURDATE()");
            $upcoming_sessions->execute();
            $session_count = $upcoming_sessions->fetchColumn();
            
            $pending_reviews = $pdo->prepare("SELECT COUNT(*) FROM videos v INNER JOIN users u ON v.uploader_id = u.id WHERE u.assigned_coach_id = ? AND v.review_requested = 1 AND v.reviewed = 0");
            $pending_reviews->execute([$user_id]);
            $review_count = $pending_reviews->fetchColumn();
            ?>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-value"><?= $athlete_count ?></span>
                    <span class="stat-label">My Athletes</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value"><?= $session_count ?></span>
                    <span class="stat-label">Upcoming Sessions</span>
                </div>
                <div class="stat-box">
                    <span class="stat-value"><?= $review_count ?></span>
                    <span class="stat-label">Videos to Review</span>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-history"></i> Recent Activity</h2>
            </div>
            
            <?php
            // Get recent activities
            $activities = $pdo->prepare("
                SELECT 'workout' as type, w.title, w.created_at, u.first_name, u.last_name
                FROM workouts w
                INNER JOIN users u ON w.user_id = u.id
                WHERE w.coach_id = ?
                UNION ALL
                SELECT 'nutrition' as type, np.title, np.created_at, u.first_name, u.last_name
                FROM nutrition_plans np
                INNER JOIN users u ON np.user_id = u.id
                WHERE np.coach_id = ?
                UNION ALL
                SELECT 'note' as type, SUBSTRING(an.note_content, 1, 50), an.created_at, u.first_name, u.last_name
                FROM athlete_notes an
                INNER JOIN users u ON an.user_id = u.id
                WHERE an.coach_id = ?
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $activities->execute([$user_id, $user_id, $user_id]);
            $recent_activities = $activities->fetchAll();
            
            if (empty($recent_activities)):
            ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No recent activity</p>
                </div>
            <?php else: ?>
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="notification-item">
                        <div class="notification-icon">
                            <i class="fas fa-<?= $activity['type'] == 'workout' ? 'dumbbell' : ($activity['type'] == 'nutrition' ? 'apple-whole' : 'sticky-note') ?>"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">
                                <?= htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']) ?>
                            </div>
                            <div class="notification-message">
                                <?= ucfirst($activity['type']) ?>: <?= htmlspecialchars($activity['title']) ?>
                            </div>
                            <div class="notification-time">
                                <?= timeAgo($activity['created_at']) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?php
// Helper functions
function getNotificationIcon($type) {
    $icons = [
        'practice_plan' => 'clipboard-list',
        'workout' => 'dumbbell',
        'nutrition' => 'apple-whole',
        'note' => 'sticky-note',
        'video_review' => 'video',
        'default' => 'bell'
    ];
    return $icons[$type] ?? $icons['default'];
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $timestamp);
}
?>
