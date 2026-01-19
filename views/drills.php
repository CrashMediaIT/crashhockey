<?php
/**
 * Drill Library View
 * Browse, search, and manage hockey drills
 */

require_once __DIR__ . '/../security.php';

$can_create = hasPermission($pdo, $user_id, $user_role, 'create_drills');
$can_delete = hasPermission($pdo, $user_id, $user_role, 'delete_drills');
$can_manage_categories = hasPermission($pdo, $user_id, $user_role, 'manage_drill_categories');

// Get filters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$skill_filter = $_GET['skill'] ?? '';

// Build query
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(d.title LIKE ? OR d.description LIKE ? OR d.coaching_points LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($category_filter)) {
    $where[] = "d.category_id = ?";
    $params[] = $category_filter;
}

if (!empty($skill_filter)) {
    $where[] = "d.skill_level = ?";
    $params[] = $skill_filter;
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get drills
$stmt = $pdo->prepare("
    SELECT d.*, dc.name as category_name,
           u.first_name, u.last_name,
           GROUP_CONCAT(dt.tag SEPARATOR ', ') as tags
    FROM drills d
    LEFT JOIN drill_categories dc ON d.category_id = dc.id
    LEFT JOIN users u ON d.created_by = u.id
    LEFT JOIN drill_tags dt ON d.id = dt.drill_id
    $where_clause
    GROUP BY d.id
    ORDER BY d.created_at DESC
");
$stmt->execute($params);
$drills = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT * FROM drill_categories ORDER BY name")->fetchAll();
?>

<style>
    :root {
        --primary: #ff4d00;
    }
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .page-title {
        font-size: 28px;
        font-weight: 900;
        color: #fff;
    }
    .btn {
        padding: 10px 20px;
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: 0.2s;
        font-size: 13px;
    }
    .btn:hover {
        background: #ff6a00;
        transform: translateY(-2px);
    }
    .btn-secondary {
        background: #1e293b;
        color: #fff;
    }
    .btn-secondary:hover {
        background: #2d3b52;
    }
    .filter-bar {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    .filter-group {
        flex: 1;
        min-width: 200px;
    }
    .filter-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 8px;
        letter-spacing: 0.5px;
    }
    .filter-input, .filter-select {
        width: 100%;
        padding: 10px 12px;
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        color: #fff;
        font-size: 13px;
    }
    .filter-input:focus, .filter-select:focus {
        outline: none;
        border-color: var(--primary);
    }
    .drills-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
    }
    .drill-card {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        padding: 20px;
        transition: 0.2s;
    }
    .drill-card:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
    }
    .drill-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    .drill-title {
        font-size: 16px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 5px;
    }
    .drill-category {
        display: inline-block;
        padding: 4px 10px;
        background: rgba(255, 77, 0, 0.1);
        color: var(--primary);
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    .drill-description {
        color: #94a3b8;
        font-size: 13px;
        line-height: 1.5;
        margin-bottom: 15px;
    }
    .drill-meta {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        font-size: 12px;
        color: #64748b;
        margin-bottom: 12px;
    }
    .drill-meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .drill-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 15px;
    }
    .tag {
        padding: 3px 8px;
        background: #1e293b;
        color: #94a3b8;
        border-radius: 4px;
        font-size: 11px;
    }
    .drill-actions {
        display: flex;
        gap: 8px;
        padding-top: 15px;
        border-top: 1px solid #1e293b;
    }
    .btn-icon {
        padding: 8px 12px;
        background: #1e293b;
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: 0.2s;
    }
    .btn-icon:hover {
        background: #2d3b52;
    }
    .btn-icon.danger:hover {
        background: #dc2626;
    }
    .modal {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .modal.active {
        display: flex;
    }
    .modal-content {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 12px;
        padding: 30px;
        max-width: 700px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
    }
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    .modal-title {
        font-size: 20px;
        font-weight: 700;
        color: #fff;
    }
    .close-modal {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 8px;
    }
    .form-input, .form-textarea, .form-select {
        width: 100%;
        padding: 10px 12px;
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        color: #fff;
        font-size: 14px;
        font-family: inherit;
    }
    .form-textarea {
        min-height: 100px;
        resize: vertical;
    }
    .form-input:focus, .form-textarea:focus, .form-select:focus {
        outline: none;
        border-color: var(--primary);
    }
    .alert {
        padding: 12px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 13px;
    }
    .alert-success {
        background: rgba(0, 255, 136, 0.1);
        border: 1px solid #00ff88;
        color: #00ff88;
    }
    .alert-error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid #ef4444;
        color: #ef4444;
    }
</style>

<div class="page-header">
    <h1 class="page-title"><i class="fas fa-hockey-puck"></i> Drill Library</h1>
    <div style="display: flex; gap: 10px;">
        <?php if ($can_manage_categories): ?>
            <button class="btn btn-secondary" onclick="openCategoryModal()">
                <i class="fas fa-folder-plus"></i> Manage Categories
            </button>
        <?php endif; ?>
        <?php if ($can_create): ?>
            <button class="btn" onclick="openDrillModal()">
                <i class="fas fa-plus"></i> Create Drill
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['status'])): ?>
    <div class="alert alert-success">
        <?php
        $messages = [
            'drill_saved' => 'Drill saved successfully!',
            'drill_deleted' => 'Drill deleted successfully!',
            'category_created' => 'Category created successfully!',
            'category_deleted' => 'Category deleted successfully!'
        ];
        echo $messages[$_GET['status']] ?? 'Operation completed successfully!';
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <?php
        $errors = [
            'title_required' => 'Drill title is required.',
            'save_failed' => 'Failed to save drill.',
            'delete_failed' => 'Failed to delete drill.',
            'category_name_required' => 'Category name is required.',
            'category_exists' => 'A category with this name already exists.',
            'category_failed' => 'Failed to create category.'
        ];
        echo $errors[$_GET['error']] ?? 'An error occurred.';
        ?>
    </div>
<?php endif; ?>

<div class="filter-bar">
    <div class="filter-group">
        <label class="filter-label">Search Drills</label>
        <input type="text" class="filter-input" id="searchInput" placeholder="Search by title, description..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="filter-group">
        <label class="filter-label">Category</label>
        <select class="filter-select" id="categoryFilter">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="filter-group">
        <label class="filter-label">Skill Level</label>
        <select class="filter-select" id="skillFilter">
            <option value="">All Levels</option>
            <option value="beginner" <?= $skill_filter == 'beginner' ? 'selected' : '' ?>>Beginner</option>
            <option value="intermediate" <?= $skill_filter == 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
            <option value="advanced" <?= $skill_filter == 'advanced' ? 'selected' : '' ?>>Advanced</option>
            <option value="all" <?= $skill_filter == 'all' ? 'selected' : '' ?>>All Ages</option>
        </select>
    </div>
    <button class="btn" onclick="applyFilters()">
        <i class="fas fa-filter"></i> Apply
    </button>
</div>

<?php if (empty($drills)): ?>
    <div style="text-align: center; padding: 60px 20px; color: #64748b;">
        <i class="fas fa-hockey-puck" style="font-size: 48px; margin-bottom: 20px; opacity: 0.3;"></i>
        <p style="font-size: 16px;">No drills found. <?= $can_create ? 'Create your first drill to get started!' : '' ?></p>
    </div>
<?php else: ?>
    <div class="drills-grid">
        <?php foreach ($drills as $drill): ?>
            <div class="drill-card">
                <div class="drill-header">
                    <div>
                        <?php if ($drill['category_name']): ?>
                            <div class="drill-category"><?= htmlspecialchars($drill['category_name']) ?></div>
                        <?php endif; ?>
                        <h3 class="drill-title"><?= htmlspecialchars($drill['title']) ?></h3>
                    </div>
                </div>
                
                <?php if ($drill['description']): ?>
                    <p class="drill-description"><?= htmlspecialchars(substr($drill['description'], 0, 120)) ?><?= strlen($drill['description']) > 120 ? '...' : '' ?></p>
                <?php endif; ?>
                
                <div class="drill-meta">
                    <?php if ($drill['duration_minutes']): ?>
                        <span class="drill-meta-item">
                            <i class="fas fa-clock"></i> <?= $drill['duration_minutes'] ?> min
                        </span>
                    <?php endif; ?>
                    <span class="drill-meta-item">
                        <i class="fas fa-signal"></i> <?= ucfirst($drill['skill_level']) ?>
                    </span>
                    <?php if ($drill['age_group']): ?>
                        <span class="drill-meta-item">
                            <i class="fas fa-users"></i> <?= htmlspecialchars($drill['age_group']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($drill['tags']): ?>
                    <div class="drill-tags">
                        <?php foreach (explode(', ', $drill['tags']) as $tag): ?>
                            <span class="tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="drill-actions">
                    <button class="btn-icon" onclick="viewDrill(<?= $drill['id'] ?>)">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <?php if ($can_create && $drill['created_by'] == $user_id): ?>
                        <button class="btn-icon" onclick="editDrill(<?= $drill['id'] ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    <?php endif; ?>
                    <?php if ($can_delete && $drill['created_by'] == $user_id): ?>
                        <form method="POST" action="process_drills.php" style="display: inline;" onsubmit="return confirm('Delete this drill?');">
                            <?= csrfTokenInput() ?>
                            <input type="hidden" name="action" value="delete_drill">
                            <input type="hidden" name="drill_id" value="<?= $drill['id'] ?>">
                            <button type="submit" class="btn-icon danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Create/Edit Drill Modal -->
<div id="drillModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="drillModalTitle">Create Drill</h2>
            <button class="close-modal" onclick="closeDrillModal()">&times;</button>
        </div>
        <form method="POST" action="process_drills.php">
            <?= csrfTokenInput() ?>
            <input type="hidden" name="action" value="save_drill">
            <input type="hidden" name="drill_id" id="drillId">
            
            <div class="form-group">
                <label class="form-label">Drill Title *</label>
                <input type="text" name="title" id="drillTitle" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">No Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="drillDescription" class="form-textarea"></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" class="form-input" min="1">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Skill Level</label>
                    <select name="skill_level" class="form-select">
                        <option value="all">All Levels</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Age Group</label>
                <input type="text" name="age_group" class="form-input" placeholder="e.g., U10, U12, U14">
            </div>
            
            <div class="form-group">
                <label class="form-label">Equipment Needed</label>
                <textarea name="equipment_needed" class="form-textarea" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Coaching Points</label>
                <textarea name="coaching_points" class="form-textarea"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Video URL</label>
                <input type="url" name="video_url" class="form-input" placeholder="https://youtube.com/...">
            </div>
            
            <button type="submit" class="btn" style="width: 100%;">
                <i class="fas fa-save"></i> Save Drill
            </button>
        </form>
    </div>
</div>

<!-- Category Management Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">Manage Categories</h2>
            <button class="close-modal" onclick="closeCategoryModal()">&times;</button>
        </div>
        
        <form method="POST" action="process_drills.php" style="margin-bottom: 25px;">
            <?= csrfTokenInput() ?>
            <input type="hidden" name="action" value="create_category">
            <div class="form-group">
                <label class="form-label">New Category Name</label>
                <input type="text" name="category_name" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="category_description" class="form-textarea" rows="2"></textarea>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-plus"></i> Create Category
            </button>
        </form>
        
        <div style="border-top: 1px solid #1e293b; padding-top: 20px;">
            <h3 style="font-size: 14px; font-weight: 700; color: #94a3b8; margin-bottom: 15px;">Existing Categories</h3>
            <?php foreach ($categories as $cat): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #06080b; border-radius: 6px; margin-bottom: 8px;">
                    <span style="color: #fff;"><?= htmlspecialchars($cat['name']) ?></span>
                    <form method="POST" action="process_drills.php" style="display: inline;" onsubmit="return confirm('Delete this category? Drills in this category will not be deleted.');">
                        <?= csrfTokenInput() ?>
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn-icon danger">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function openDrillModal() {
    document.getElementById('drillModal').classList.add('active');
    document.getElementById('drillModalTitle').textContent = 'Create Drill';
    document.querySelector('form').reset();
    document.getElementById('drillId').value = '';
}

function closeDrillModal() {
    document.getElementById('drillModal').classList.remove('active');
}

function openCategoryModal() {
    document.getElementById('categoryModal').classList.add('active');
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('active');
}

function viewDrill(id) {
    window.location.href = 'dashboard.php?page=drill_view&id=' + id;
}

function editDrill(id) {
    // In a real implementation, fetch drill data via AJAX and populate form
    alert('Edit functionality requires AJAX implementation');
}

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;
    const skill = document.getElementById('skillFilter').value;
    
    let url = 'dashboard.php?page=drills';
    if (search) url += '&search=' + encodeURIComponent(search);
    if (category) url += '&category=' + category;
    if (skill) url += '&skill=' + skill;
    
    window.location.href = url;
}

// Close modal when clicking outside
document.querySelectorAll('.modal').forEach(modal => {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});
</script>
