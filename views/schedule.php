<?php
/**
 * Schedule View - Book Sessions
 * Shows upcoming sessions with booking functionality
 * Supports multi-athlete booking for parents
 */

require_once __DIR__ . '/../security.php';

$isParent = ($user_role === 'parent');

// Get managed athletes if parent
$managed_athletes = [];
if ($isParent) {
    $athletes_stmt = $pdo->prepare("
        SELECT u.id, u.first_name, u.last_name, ma.can_book
        FROM managed_athletes ma
        INNER JOIN users u ON ma.athlete_id = u.id
        WHERE ma.parent_id = ? AND ma.can_book = 1
        ORDER BY u.first_name, u.last_name
    ");
    $athletes_stmt->execute([$user_id]);
    $managed_athletes = $athletes_stmt->fetchAll();
}

// Get filter parameters
$age_group_filter = isset($_GET['age_group']) ? $_GET['age_group'] : '';
$skill_level_filter = isset($_GET['skill_level']) ? $_GET['skill_level'] : '';
$session_type_filter = isset($_GET['session_type']) ? $_GET['session_type'] : '';

// Get all age groups and skill levels for filters
$age_groups = $pdo->query("SELECT * FROM age_groups ORDER BY display_order")->fetchAll();
$skill_levels = $pdo->query("SELECT * FROM skill_levels ORDER BY display_order")->fetchAll();
$session_types = $pdo->query("SELECT DISTINCT session_type FROM sessions ORDER BY session_type")->fetchAll();

// Build query for upcoming sessions
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

// Get tax settings
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$tax_rate = floatval($settings['tax_rate'] ?? 13.00);
$tax_name = $settings['tax_name'] ?? 'HST';
?>

<style>
    :root {
        --primary: #ff4d00;
    }
    .page-header {
        margin-bottom: 30px;
    }
    .page-title {
        font-size: 28px;
        font-weight: 900;
        color: #fff;
        margin-bottom: 10px;
    }
    .page-subtitle {
        color: #94a3b8;
        font-size: 14px;
    }
    .filters-bar {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    .filter-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #94a3b8;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    .filter-select {
        width: 100%;
        padding: 10px;
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        color: #fff;
        font-size: 14px;
    }
    .sessions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
    }
    .session-card {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        padding: 25px;
        transition: all 0.2s;
    }
    .session-card:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
    }
    .session-type-badge {
        display: inline-block;
        background: var(--primary);
        color: #fff;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 15px;
    }
    .session-title {
        font-size: 20px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 15px;
    }
    .session-detail {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #94a3b8;
        margin-bottom: 8px;
        font-size: 14px;
    }
    .session-detail i {
        color: var(--primary);
        width: 18px;
    }
    .session-tags {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin: 15px 0;
    }
    .session-tag {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid #1e293b;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 12px;
        color: #94a3b8;
    }
    .session-price {
        font-size: 24px;
        font-weight: 900;
        color: var(--primary);
        margin: 15px 0;
    }
    .session-price small {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
    }
    .session-capacity {
        font-size: 13px;
        color: #94a3b8;
        margin-bottom: 15px;
    }
    .session-capacity.low {
        color: #ef4444;
        font-weight: 700;
    }
    .btn-book {
        width: 100%;
        padding: 12px;
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }
    .btn-book:hover {
        background: #e64500;
    }
    .btn-book:disabled {
        background: #1e293b;
        color: #64748b;
        cursor: not-allowed;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
    }
    .empty-state i {
        font-size: 64px;
        color: #64748b;
        opacity: 0.3;
        margin-bottom: 20px;
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        align-items: center;
        justify-content: center;
    }
    .modal.active {
        display: flex;
    }
    .modal-content {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #fff;
    }
    .modal-close {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
    }
    .athlete-checkbox-group {
        margin-bottom: 15px;
    }
    .athlete-checkbox {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .athlete-checkbox:hover {
        border-color: var(--primary);
    }
    .athlete-checkbox input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .athlete-checkbox-label {
        font-size: 14px;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
    }
    .discount-input {
        width: 100%;
        padding: 10px;
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        color: #fff;
        font-size: 14px;
        margin-top: 10px;
    }
    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #94a3b8;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    
    @media (max-width: 768px) {
        .sessions-grid {
            grid-template-columns: 1fr;
        }
        .filters-bar {
            flex-direction: column;
        }
    }
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-calendar-check"></i> Book Sessions
    </h1>
    <p class="page-subtitle">Browse and book upcoming training sessions</p>
</div>

<!-- Filters -->
<div class="filters-bar">
    <div class="filter-group">
        <label class="filter-label">Age Group</label>
        <select class="filter-select" onchange="applyFilters(this, 'age_group')">
            <option value="">All Age Groups</option>
            <?php foreach ($age_groups as $ag): ?>
                <option value="<?= $ag['id'] ?>" <?= $age_group_filter == $ag['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ag['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="filter-group">
        <label class="filter-label">Skill Level</label>
        <select class="filter-select" onchange="applyFilters(this, 'skill_level')">
            <option value="">All Skill Levels</option>
            <?php foreach ($skill_levels as $sl): ?>
                <option value="<?= $sl['id'] ?>" <?= $skill_level_filter == $sl['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($sl['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="filter-group">
        <label class="filter-label">Session Type</label>
        <select class="filter-select" onchange="applyFilters(this, 'session_type')">
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

<!-- Sessions Grid -->
<?php if (empty($sessions)): ?>
    <div class="empty-state">
        <i class="fas fa-calendar-times"></i>
        <h2 style="font-size: 24px; color: #fff; margin-bottom: 10px;">No Sessions Available</h2>
        <p style="color: #64748b;">Check back soon for upcoming training sessions</p>
    </div>
<?php else: ?>
    <div class="sessions-grid">
        <?php foreach ($sessions as $session): ?>
            <?php
            $spots_left = $session['max_capacity'] - $session['booked_count'];
            $is_full = $spots_left <= 0;
            $is_low = $spots_left <= 5;
            $price_with_tax = $session['price'] * (1 + $tax_rate / 100);
            
            // Check if current user/athlete already booked this session
            $booked = false;
            if ($isParent && isset($_GET['athlete_id'])) {
                $check_stmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND session_id = ? AND status = 'paid'");
                $check_stmt->execute([$_GET['athlete_id'], $session['id']]);
                $booked = $check_stmt->fetch() !== false;
            } elseif (!$isParent) {
                $check_stmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND session_id = ? AND status = 'paid'");
                $check_stmt->execute([$user_id, $session['id']]);
                $booked = $check_stmt->fetch() !== false;
            }
            ?>
            
            <div class="session-card">
                <span class="session-type-badge"><?= htmlspecialchars($session['session_type']) ?></span>
                <h3 class="session-title"><?= htmlspecialchars($session['title']) ?></h3>
                
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
                    <small>(includes <?= $tax_name ?>)</small>
                </div>
                
                <div class="session-capacity <?= $is_low ? 'low' : '' ?>">
                    <?php if ($is_full): ?>
                        <i class="fas fa-exclamation-circle"></i> Session Full
                    <?php else: ?>
                        <i class="fas fa-users"></i> <?= $spots_left ?> spot<?= $spots_left != 1 ? 's' : '' ?> remaining
                    <?php endif; ?>
                </div>
                
                <?php if ($booked): ?>
                    <button class="btn-book" style="background: #00ff88; color: #06080b;" disabled>
                        <i class="fas fa-check-circle"></i> Already Booked
                    </button>
                <?php elseif ($is_full): ?>
                    <button class="btn-book" disabled>
                        <i class="fas fa-ban"></i> Session Full
                    </button>
                <?php else: ?>
                    <?php if ($isParent && count($managed_athletes) > 0): ?>
                        <button class="btn-book" onclick="openBookingModal(<?= $session['id'] ?>, '<?= htmlspecialchars($session['title'], ENT_QUOTES) ?>', <?= $session['price'] ?>)">
                            <i class="fas fa-ticket-alt"></i> Book Session
                        </button>
                    <?php else: ?>
                        <form method="POST" action="process_booking.php" style="margin: 0;">
                            <?= csrfTokenInput() ?>
                            <input type="hidden" name="session_id" value="<?= $session['id'] ?>">
                            <button type="submit" class="btn-book">
                                <i class="fas fa-ticket-alt"></i> Book Session
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Multi-Athlete Booking Modal (for parents) -->
<?php if ($isParent && count($managed_athletes) > 0): ?>
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Book Session</h2>
            <button class="modal-close" onclick="closeBookingModal()">&times;</button>
        </div>
        
        <form method="POST" action="process_booking.php" id="bookingForm">
            <?= csrfTokenInput() ?>
            <input type="hidden" name="session_id" id="modal_session_id">
            
            <div style="margin-bottom: 20px;">
                <div style="font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 5px;" id="modal_session_title"></div>
                <div style="font-size: 14px; color: #94a3b8;">
                    Price per athlete: <span style="color: var(--primary); font-weight: 700;">$<span id="modal_session_price"></span></span>
                </div>
            </div>
            
            <label class="form-label">Select Athletes to Book</label>
            <div class="athlete-checkbox-group">
                <?php foreach ($managed_athletes as $athlete): ?>
                    <label class="athlete-checkbox">
                        <input type="checkbox" name="athlete_ids[]" value="<?= $athlete['id'] ?>">
                        <span class="athlete-checkbox-label">
                            <?= htmlspecialchars($athlete['first_name'] . ' ' . $athlete['last_name']) ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <div style="margin-top: 20px;">
                <label class="form-label">Discount Code (Optional)</label>
                <input type="text" name="discount_code" class="discount-input" placeholder="Enter discount code">
            </div>
            
            <button type="submit" class="btn-book" style="margin-top: 20px;">
                <i class="fas fa-ticket-alt"></i> Proceed to Payment
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function applyFilters(select, filterType) {
    const url = new URL(window.location);
    const value = select.value;
    
    if (value) {
        url.searchParams.set(filterType, value);
    } else {
        url.searchParams.delete(filterType);
    }
    
    window.location = url.toString();
}

<?php if ($isParent && count($managed_athletes) > 0): ?>
function openBookingModal(sessionId, sessionTitle, sessionPrice) {
    document.getElementById('modal_session_id').value = sessionId;
    document.getElementById('modal_session_title').textContent = sessionTitle;
    document.getElementById('modal_session_price').textContent = (sessionPrice * (1 + <?= $tax_rate ?> / 100)).toFixed(2);
    document.getElementById('bookingModal').classList.add('active');
    
    // Uncheck all checkboxes
    document.querySelectorAll('input[name="athlete_ids[]"]').forEach(cb => cb.checked = false);
}

function closeBookingModal() {
    document.getElementById('bookingModal').classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('bookingModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBookingModal();
    }
});
<?php endif; ?>
</script>
