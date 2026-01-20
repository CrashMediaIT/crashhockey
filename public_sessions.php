<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Upcoming Sessions | Crash Hockey</title>
    <meta name="description" content="Browse and register for upcoming hockey training sessions.">
    
    <link rel="icon" type="image/png" href="https://images.crashmedia.ca/images/2026/01/18/logo.png">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .sessions-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
            padding: 80px 0 40px;
        }
        
        .sessions-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 0 20px;
        }
        
        .sessions-header h1 {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 10px;
        }
        
        .sessions-header .highlight {
            color: var(--primary, #7000a4);
        }
        
        .sessions-header p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
        }
        
        .filters-bar {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .filter-group select {
            width: 100%;
            padding: 10px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            color: white;
            font-size: 1rem;
        }
        
        .filter-group select option {
            background: #1a1f3a;
        }
        
        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .session-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s ease;
        }
        
        .session-card:hover {
            border-color: var(--primary, #7000a4);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 77, 0, 0.2);
        }
        
        .session-type-badge {
            display: inline-block;
            background: var(--primary, #7000a4);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .session-card h3 {
            color: white;
            font-size: 1.4rem;
            margin-bottom: 15px;
        }
        
        .session-detail {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .session-detail i {
            color: var(--primary, #7000a4);
            width: 20px;
        }
        
        .session-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 15px 0;
        }
        
        .session-tag {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.9);
        }
        
        .session-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary, #7000a4);
            margin: 15px 0;
        }
        
        .session-price small {
            font-size: 0.6rem;
            color: rgba(255, 255, 255, 0.5);
            font-weight: 400;
        }
        
        .session-capacity {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .session-capacity.low {
            color: #7000a4;
        }
        
        .register-btn {
            width: 100%;
            padding: 12px;
            background: var(--primary, #7000a4);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .register-btn:hover {
            background: #e64500;
            transform: scale(1.02);
        }
        
        .no-sessions {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .no-sessions i {
            font-size: 4rem;
            color: var(--primary, #7000a4);
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .sessions-header h1 {
                font-size: 2rem;
            }
            
            .sessions-grid {
                grid-template-columns: 1fr;
            }
            
            .filters-bar {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<?php
require_once 'security.php';
require_once 'db_config.php';
setSecurityHeaders();

// Get filter parameters
$age_group_filter = isset($_GET['age_group']) ? $_GET['age_group'] : '';
$skill_level_filter = isset($_GET['skill_level']) ? $_GET['skill_level'] : '';
$session_type_filter = isset($_GET['session_type']) ? $_GET['session_type'] : '';

// Get all age groups and skill levels for filters
$age_groups = $pdo->query("SELECT * FROM age_groups ORDER BY display_order")->fetchAll();
$skill_levels = $pdo->query("SELECT * FROM skill_levels ORDER BY display_order")->fetchAll();
$session_types = $pdo->query("SELECT DISTINCT session_type FROM sessions ORDER BY session_type")->fetchAll();

// Get tax settings
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$tax_rate = floatval($settings['tax_rate'] ?? 13.00);
$tax_name = $settings['tax_name'] ?? 'HST';

// Build query for sessions
$query = "SELECT s.*, 
          ag.name as age_group_name,
          sl.name as skill_level_name,
          (SELECT COUNT(*) FROM bookings WHERE session_id = s.id AND status = 'paid') as booked_count
          FROM sessions s
          LEFT JOIN age_groups ag ON s.age_group_id = ag.id
          LEFT JOIN skill_levels sl ON s.skill_level_id = sl.id
          WHERE s.session_date >= CURDATE()";

$params = [];

if ($age_group_filter) {
    $query .= " AND s.age_group_id = ?";
    $params[] = $age_group_filter;
}

if ($skill_level_filter) {
    $query .= " AND s.skill_level_id = ?";
    $params[] = $skill_level_filter;
}

if ($session_type_filter) {
    $query .= " AND s.session_type = ?";
    $params[] = $session_type_filter;
}

$query .= " ORDER BY s.session_date ASC, s.session_time ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sessions = $stmt->fetchAll();
?>

    <header>
        <nav class="container nav-flex">
            <div class="logo-area" style="display: flex; align-items: center; gap: 15px;">
                <img src="https://images.crashmedia.ca/images/2026/01/18/logo.png" alt="Crash Hockey Logo" style="height: 40px; width: auto;">
                <div>
                    <div class="logo-text">CRASH<span>HOCKEY</span></div>
                </div>
            </div>
            
            <div class="nav-menu">
                <a href="index.php">Home</a>
                <a href="public_sessions.php" class="active">Sessions</a>
                <a href="login.php" class="nav-btn">Login</a>
            </div>
        </nav>
    </header>

    <section class="sessions-page">
        <div class="container">
            <div class="sessions-header">
                <h1>Upcoming <span class="highlight">Training Sessions</span></h1>
                <p>Browse available sessions and register to start your development journey</p>
            </div>
            
            <div class="filters-bar">
                <div class="filter-group">
                    <label><i class="fas fa-users"></i> Age Group</label>
                    <select id="age_group_filter" onchange="applyFilters()">
                        <option value="">All Age Groups</option>
                        <?php foreach ($age_groups as $ag): ?>
                            <option value="<?= $ag['id'] ?>" <?= $age_group_filter == $ag['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ag['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-chart-line"></i> Skill Level</label>
                    <select id="skill_level_filter" onchange="applyFilters()">
                        <option value="">All Skill Levels</option>
                        <?php foreach ($skill_levels as $sl): ?>
                            <option value="<?= $sl['id'] ?>" <?= $skill_level_filter == $sl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sl['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-hockey-puck"></i> Session Type</label>
                    <select id="session_type_filter" onchange="applyFilters()">
                        <option value="">All Types</option>
                        <?php foreach ($session_types as $st): ?>
                            <option value="<?= htmlspecialchars($st['session_type']) ?>" 
                                    <?= $session_type_filter == $st['session_type'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($st['session_type']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <?php if (count($sessions) > 0): ?>
                <div class="sessions-grid">
                    <?php foreach ($sessions as $session): ?>
                        <?php
                        $spots_left = $session['max_capacity'] - $session['booked_count'];
                        $is_low = $spots_left <= 5;
                        $price_with_tax = $session['price'] * (1 + $tax_rate / 100);
                        ?>
                        <div class="session-card">
                            <span class="session-type-badge"><?= htmlspecialchars($session['session_type']) ?></span>
                            <h3><?= htmlspecialchars($session['title']) ?></h3>
                            
                            <div class="session-detail">
                                <i class="fas fa-calendar"></i>
                                <span><?= date('l, F j, Y', strtotime($session['session_date'])) ?></span>
                            </div>
                            
                            <div class="session-detail">
                                <i class="fas fa-clock"></i>
                                <span><?= date('g:i A', strtotime($session['session_time'])) ?></span>
                            </div>
                            
                            <div class="session-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($session['arena']) ?>, <?= htmlspecialchars($session['city']) ?></span>
                            </div>
                            
                            <?php if ($session['age_group_name'] || $session['skill_level_name']): ?>
                                <div class="session-tags">
                                    <?php if ($session['age_group_name']): ?>
                                        <span class="session-tag"><?= htmlspecialchars($session['age_group_name']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($session['skill_level_name']): ?>
                                        <span class="session-tag"><?= htmlspecialchars($session['skill_level_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="session-price">
                                $<?= number_format($price_with_tax, 2) ?>
                                <small>(includes HST)</small>
                            </div>
                            
                            <div class="session-capacity <?= $is_low ? 'low' : '' ?>">
                                <?= $spots_left ?> spot<?= $spots_left != 1 ? 's' : '' ?> remaining
                            </div>
                            
                            <a href="login.php?redirect=dashboard.php?page=schedule&session_id=<?= $session['id'] ?>" class="register-btn">
                                Register for Session
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-sessions">
                    <i class="fas fa-calendar-times"></i>
                    <h2>No Sessions Available</h2>
                    <p>Check back soon for upcoming training sessions matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer class="site-footer">
        <div class="container footer-flex">
            <div class="footer-left">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <img src="https://images.crashmedia.ca/images/2026/01/18/logo.png" alt="Logo" style="height: 30px; opacity: 0.8;">
                    <div class="logo-text" style="font-size: 1.2rem;">CRASH<span>HOCKEY</span></div>
                </div>
                <p class="footer-desc">High-performance athletic development.</p>
            </div>
            <div class="footer-right">
                <div class="footer-col">
                    <h4>Account</h4>
                    <a href="login.php">Athlete Portal</a>
                    <a href="register.php">Registration</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container footer-bottom-flex">
                <p>&copy; 2026 Crash Hockey Development. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function applyFilters() {
            const ageGroup = document.getElementById('age_group_filter').value;
            const skillLevel = document.getElementById('skill_level_filter').value;
            const sessionType = document.getElementById('session_type_filter').value;
            
            let url = 'public_sessions.php?';
            const params = [];
            
            if (ageGroup) params.push('age_group=' + ageGroup);
            if (skillLevel) params.push('skill_level=' + skillLevel);
            if (sessionType) params.push('session_type=' + encodeURIComponent(sessionType));
            
            window.location.href = url + params.join('&');
        }
    </script>
</body>
</html>
