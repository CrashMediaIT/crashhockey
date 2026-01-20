<?php
/**
 * Athlete Statistics Dashboard
 * Displays athlete statistics, testing results, and performance metrics
 */

require_once __DIR__ . '/../security.php';

$viewing_user_id = $user_id;
$is_coach = ($user_role === 'coach' || $user_role === 'coach_plus' || $user_role === 'admin');
$is_parent = ($user_role === 'parent');

// Allow coaches to view athlete stats
if ($is_coach && isset($_GET['athlete_id'])) {
    $viewing_user_id = intval($_GET['athlete_id']);
} elseif ($is_parent && isset($_GET['athlete_id'])) {
    // Verify parent has permission
    $verify_stmt = $pdo->prepare("SELECT athlete_id FROM managed_athletes WHERE parent_id = ? AND athlete_id = ? AND can_view_stats = 1");
    $verify_stmt->execute([$user_id, intval($_GET['athlete_id'])]);
    if ($verify_stmt->fetch()) {
        $viewing_user_id = intval($_GET['athlete_id']);
    }
}

// Get athlete info
$athlete_stmt = $pdo->prepare("SELECT first_name, last_name, position FROM users WHERE id = ?");
$athlete_stmt->execute([$viewing_user_id]);
$athlete = $athlete_stmt->fetch();

// Get current teams and stats
$teams_stmt = $pdo->prepare("
    SELECT t.*, s.*, sl.name as skill_level_name
    FROM athlete_teams t
    LEFT JOIN athlete_stats s ON t.id = s.team_id AND s.user_id = ?
    LEFT JOIN skill_levels sl ON t.skill_level_id = sl.id
    WHERE t.user_id = ?
    ORDER BY t.is_current DESC, t.created_at DESC
");
$teams_stmt->execute([$viewing_user_id, $viewing_user_id]);
$teams = $teams_stmt->fetchAll();

// Get testing results
$testing_stmt = $pdo->prepare("
    SELECT * FROM testing_results
    WHERE user_id = ?
    ORDER BY test_date DESC
");
$testing_stmt->execute([$viewing_user_id]);
$testing_results = $testing_stmt->fetchAll();

// Get session attendance
$attendance_stmt = $pdo->prepare("
    SELECT COUNT(*) as total_sessions, 
           COUNT(CASE WHEN s.session_date <= CURDATE() THEN 1 END) as completed_sessions
    FROM bookings b
    INNER JOIN sessions s ON b.session_id = s.id
    WHERE (b.user_id = ? OR b.booked_for_user_id = ?) AND b.status = 'paid'
");
$attendance_stmt->execute([$viewing_user_id, $viewing_user_id]);
$attendance = $attendance_stmt->fetch();
?>

<style>
    :root {
        --primary: #7000a4;
    }
    .stats-header {
        background: linear-gradient(135deg, var(--primary) 0%, #4a0070 100%);
        border-radius: 8px;
        padding: 30px;
        margin-bottom: 30px;
        color: #fff;
    }
    .stats-header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
        font-weight: 900;
    }
    .stats-header .subtitle {
        font-size: 14px;
        opacity: 0.9;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }
    .stat-value {
        font-size: 36px;
        font-weight: 900;
        color: var(--primary);
        display: block;
        margin-bottom: 10px;
    }
    .stat-label {
        font-size: 13px;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 700;
    }
    .section-card {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 30px;
    }
    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .team-section {
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .team-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .team-name {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
    }
    .current-badge {
        background: var(--primary);
        color: #fff;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 11px;
        font-weight: 700;
    }
    .stats-table {
        width: 100%;
        border-collapse: collapse;
    }
    .stats-table th {
        text-align: left;
        padding: 10px;
        background: rgba(255, 255, 255, 0.05);
        color: #94a3b8;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 700;
    }
    .stats-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #1e293b;
        color: #fff;
    }
    .btn-edit {
        background: var(--primary);
        color: #fff;
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        display: inline-block;
        transition: all 0.2s;
    }
    .btn-edit:hover {
        background: #e64500;
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
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
</style>

<div class="stats-header">
    <h1><i class="fas fa-chart-line"></i> Player Statistics</h1>
    <div class="subtitle">
        <?= htmlspecialchars($athlete['first_name'] . ' ' . $athlete['last_name']) ?>
        <?php if ($athlete['position']): ?>
            • <?= htmlspecialchars($athlete['position']) ?>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-value"><?= $attendance['completed_sessions'] ?? 0 ?></span>
        <span class="stat-label">Training Sessions</span>
    </div>
    
    <?php
    $current_team = null;
    foreach ($teams as $team) {
        if ($team['is_current']) {
            $current_team = $team;
            break;
        }
    }
    
    if ($current_team && isset($current_team['goals'])):
    ?>
    <div class="stat-card">
        <span class="stat-value"><?= $current_team['goals'] ?? 0 ?></span>
        <span class="stat-label">Goals</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= $current_team['assists'] ?? 0 ?></span>
        <span class="stat-label">Assists</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= $current_team['points'] ?? 0 ?></span>
        <span class="stat-label">Points</span>
    </div>
    <div class="stat-card">
        <span class="stat-value"><?= $current_team['games_played'] ?? 0 ?></span>
        <span class="stat-label">Games Played</span>
    </div>
    <?php endif; ?>
</div>

<!-- Team Stats -->
<div class="section-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 class="section-title" style="margin: 0;">
            <i class="fas fa-users"></i> Season Statistics
        </h2>
        <?php if ($viewing_user_id === $user_id || $is_coach): ?>
            <a href="?page=manage_athletes&action=edit_stats&id=<?= $viewing_user_id ?>" class="btn-edit">
                <i class="fas fa-edit"></i> Update Stats
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($teams)): ?>
        <div class="empty-state">
            <i class="fas fa-hockey-puck"></i>
            <p>No team stats recorded yet</p>
        </div>
    <?php else: ?>
        <?php foreach ($teams as $team): ?>
            <div class="team-section">
                <div class="team-header">
                    <div>
                        <div class="team-name"><?= htmlspecialchars($team['team_name']) ?></div>
                        <div style="font-size: 13px; color: #64748b; margin-top: 5px;">
                            <?= htmlspecialchars($team['season_year']) ?>
                            <?php if ($team['skill_level_name']): ?>
                                • <?= htmlspecialchars($team['skill_level_name']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($team['is_current']): ?>
                        <span class="current-badge">CURRENT</span>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($team['games_played'])): ?>
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>GP</th>
                                <th>G</th>
                                <th>A</th>
                                <th>PTS</th>
                                <th>+/-</th>
                                <th>PIM</th>
                                <th>SOG</th>
                                <th>S%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= $team['games_played'] ?? 0 ?></td>
                                <td><?= $team['goals'] ?? 0 ?></td>
                                <td><?= $team['assists'] ?? 0 ?></td>
                                <td style="font-weight: 700; color: var(--primary);"><?= $team['points'] ?? 0 ?></td>
                                <td><?= ($team['plus_minus'] > 0 ? '+' : '') . ($team['plus_minus'] ?? 0) ?></td>
                                <td><?= $team['penalty_minutes'] ?? 0 ?></td>
                                <td><?= $team['shots'] ?? 0 ?></td>
                                <td><?= number_format($team['shooting_percentage'] ?? 0, 1) ?>%</td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #64748b; font-size: 14px;">No stats recorded for this season</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Testing Results -->
<div class="section-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 class="section-title" style="margin: 0;">
            <i class="fas fa-stopwatch"></i> Testing Results
        </h2>
        <?php if ($viewing_user_id === $user_id || $is_coach): ?>
            <a href="?page=testing&athlete_id=<?= $viewing_user_id ?>" class="btn-edit">
                <i class="fas fa-plus"></i> Add Result
            </a>
        <?php endif; ?>
    </div>
    
    <?php if (empty($testing_results)): ?>
        <div class="empty-state">
            <i class="fas fa-stopwatch"></i>
            <p>No testing results recorded yet</p>
        </div>
    <?php else: ?>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Test</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($testing_results as $result): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($result['test_date'])) ?></td>
                        <td><?= htmlspecialchars($result['category']) ?></td>
                        <td><?= htmlspecialchars($result['test_name']) ?></td>
                        <td>
                            <?php if ($result['time_result']): ?>
                                <?= htmlspecialchars($result['time_result']) ?>
                            <?php elseif ($result['weight']): ?>
                                <?= $result['weight'] ?>lbs
                                <?php if ($result['reps']): ?>
                                    x <?= $result['reps'] ?> reps
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
