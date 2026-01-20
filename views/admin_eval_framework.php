<?php
/**
 * Admin - Evaluation Framework Management
 * Manage evaluation categories and skills with drag-and-drop ordering
 */

require_once __DIR__ . '/../security.php';

// Check admin permission
if ($user_role !== 'admin') {
    header('Location: dashboard.php?page=home');
    exit;
}

// Get all categories with skill counts
$categories = $pdo->query("
    SELECT ec.*, 
           (SELECT COUNT(*) FROM eval_skills WHERE category_id = ec.id) as skill_count
    FROM eval_categories ec
    ORDER BY ec.display_order, ec.name
")->fetchAll();

// Get all skills grouped by category
$skills_by_category = [];
$all_skills = $pdo->query("
    SELECT es.*,
           (SELECT COUNT(*) FROM evaluation_scores WHERE skill_id = es.id) as usage_count
    FROM eval_skills es
    ORDER BY es.category_id, es.display_order, es.name
")->fetchAll();

foreach ($all_skills as $skill) {
    if (!isset($skills_by_category[$skill['category_id']])) {
        $skills_by_category[$skill['category_id']] = [];
    }
    $skills_by_category[$skill['category_id']][] = $skill;
}
?>

<style>
    :root {
        --primary: #7000a4;
        --primary-hover: #5a0083;
    }
    
    .admin-container {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .page-title {
        font-size: 28px;
        font-weight: 900;
        color: #fff;
    }
    
    .btn-create {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 700;
        font-size: 14px;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .btn-create:hover {
        background: var(--primary-hover);
    }
    
    .categories-grid {
        display: grid;
        gap: 25px;
    }
    
    .category-card {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .category-card.inactive {
        opacity: 0.6;
    }
    
    .category-header {
        background: #06080b;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: grab;
        border-bottom: 2px solid var(--primary);
    }
    
    .category-header.dragging {
        cursor: grabbing;
        opacity: 0.5;
    }
    
    .category-info {
        flex: 1;
    }
    
    .category-name {
        font-size: 20px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 5px;
    }
    
    .category-description {
        font-size: 14px;
        color: #94a3b8;
    }
    
    .category-meta {
        font-size: 12px;
        color: #64748b;
        margin-top: 8px;
    }
    
    .category-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .drag-handle {
        color: #64748b;
        font-size: 20px;
        cursor: grab;
        margin-right: 15px;
    }
    
    .btn-edit, .btn-delete, .btn-toggle {
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    
    .btn-edit {
        background: var(--primary);
        color: #fff;
    }
    
    .btn-edit:hover {
        background: var(--primary-hover);
    }
    
    .btn-delete {
        background: transparent;
        border: 1px solid #ef4444;
        color: #ef4444;
    }
    
    .btn-delete:hover {
        background: #ef4444;
        color: #fff;
    }
    
    .btn-toggle {
        background: transparent;
        border: 1px solid #10b981;
        color: #10b981;
    }
    
    .btn-toggle.inactive {
        border-color: #64748b;
        color: #64748b;
    }
    
    .btn-toggle:hover {
        background: #10b981;
        color: #fff;
    }
    
    .btn-toggle.inactive:hover {
        background: #64748b;
    }
    
    .skills-list {
        padding: 20px;
    }
    
    .skills-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #1e293b;
    }
    
    .skills-title {
        font-size: 16px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
    }
    
    .btn-add-skill {
        background: transparent;
        border: 1px dashed var(--primary);
        color: var(--primary);
        padding: 8px 16px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-add-skill:hover {
        background: rgba(112, 0, 164, 0.1);
    }
    
    .skill-item {
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: start;
        cursor: grab;
    }
    
    .skill-item.dragging {
        cursor: grabbing;
        opacity: 0.5;
    }
    
    .skill-item.inactive {
        opacity: 0.5;
    }
    
    .skill-info {
        flex: 1;
    }
    
    .skill-name {
        font-size: 16px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 5px;
    }
    
    .skill-description {
        font-size: 14px;
        color: #94a3b8;
        margin-bottom: 8px;
    }
    
    .skill-criteria {
        font-size: 12px;
        color: #64748b;
        background: #0d1117;
        padding: 8px;
        border-radius: 4px;
        border-left: 2px solid var(--primary);
    }
    
    .skill-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
    }
    
    .empty-state i {
        font-size: 48px;
        color: #64748b;
        opacity: 0.3;
        margin-bottom: 15px;
    }
    
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
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
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
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #94a3b8;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    
    .form-input, .form-textarea, .form-select {
        width: 100%;
        padding: 12px;
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        color: #fff;
        font-size: 14px;
    }
    
    .form-textarea {
        min-height: 100px;
        resize: vertical;
        font-family: inherit;
    }
    
    .form-input:focus, .form-textarea:focus, .form-select:focus {
        outline: none;
        border-color: var(--primary);
    }
    
    .btn-submit {
        width: 100%;
        padding: 12px;
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        font-size: 14px;
    }
    
    .btn-submit:hover {
        background: var(--primary-hover);
    }
</style>

<div class="admin-container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-cogs"></i> Evaluation Framework
        </h1>
        <button onclick="openCategoryModal()" class="btn-create">
            <i class="fas fa-plus"></i> Add Category
        </button>
    </div>
    
    <?php if (empty($categories)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h2 style="font-size: 24px; color: #fff; margin-bottom: 10px;">No Categories</h2>
            <p style="color: #64748b;">Create your first evaluation category to get started</p>
        </div>
    <?php else: ?>
        <div id="categoriesGrid" class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card <?= !$category['is_active'] ? 'inactive' : '' ?>" data-category-id="<?= $category['id'] ?>">
                    <div class="category-header" draggable="true">
                        <span class="drag-handle">
                            <i class="fas fa-grip-vertical"></i>
                        </span>
                        <div class="category-info">
                            <div class="category-name"><?= htmlspecialchars($category['name']) ?></div>
                            <?php if ($category['description']): ?>
                                <div class="category-description"><?= htmlspecialchars($category['description']) ?></div>
                            <?php endif; ?>
                            <div class="category-meta">
                                <?= $category['skill_count'] ?> skills
                                <?php if (!$category['is_active']): ?>
                                    • <strong>INACTIVE</strong>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="category-actions">
                            <button class="btn-toggle <?= !$category['is_active'] ? 'inactive' : '' ?>" 
                                    onclick="toggleCategory(<?= $category['id'] ?>, <?= $category['is_active'] ? 'false' : 'true' ?>)">
                                <i class="fas fa-<?= $category['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                <?= $category['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                            <button class="btn-edit" onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($category['description'] ?? '', ENT_QUOTES) ?>')">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <?php if ($category['skill_count'] == 0): ?>
                                <button class="btn-delete" onclick="deleteCategory(<?= $category['id'] ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="skills-list">
                        <div class="skills-header">
                            <div class="skills-title">
                                <i class="fas fa-list"></i> Skills
                            </div>
                            <button class="btn-add-skill" onclick="openSkillModal(<?= $category['id'] ?>)">
                                <i class="fas fa-plus"></i> Add Skill
                            </button>
                        </div>
                        
                        <?php if (isset($skills_by_category[$category['id']]) && !empty($skills_by_category[$category['id']])): ?>
                            <div class="skills-sortable" data-category-id="<?= $category['id'] ?>">
                                <?php foreach ($skills_by_category[$category['id']] as $skill): ?>
                                    <div class="skill-item <?= !$skill['is_active'] ? 'inactive' : '' ?>" 
                                         data-skill-id="<?= $skill['id'] ?>" draggable="true">
                                        <span class="drag-handle">
                                            <i class="fas fa-grip-vertical"></i>
                                        </span>
                                        <div class="skill-info">
                                            <div class="skill-name">
                                                <?= htmlspecialchars($skill['name']) ?>
                                                <?php if (!$skill['is_active']): ?>
                                                    <span style="font-size: 12px; color: #64748b; font-weight: normal;"> (inactive)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="skill-description"><?= htmlspecialchars($skill['description']) ?></div>
                                            <?php if ($skill['criteria']): ?>
                                                <div class="skill-criteria">
                                                    <strong>Criteria:</strong> <?= htmlspecialchars($skill['criteria']) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($skill['usage_count'] > 0): ?>
                                                <div style="font-size: 12px; color: #64748b; margin-top: 5px;">
                                                    Used in <?= $skill['usage_count'] ?> evaluations
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="skill-actions">
                                            <button class="btn-toggle <?= !$skill['is_active'] ? 'inactive' : '' ?>" 
                                                    onclick="toggleSkill(<?= $skill['id'] ?>, <?= $skill['is_active'] ? 'false' : 'true' ?>)">
                                                <i class="fas fa-<?= $skill['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                            </button>
                                            <button class="btn-edit" 
                                                    onclick="editSkill(<?= $skill['id'] ?>, <?= $category['id'] ?>, '<?= htmlspecialchars($skill['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($skill['description'], ENT_QUOTES) ?>', '<?= htmlspecialchars($skill['criteria'] ?? '', ENT_QUOTES) ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($skill['usage_count'] == 0): ?>
                                                <button class="btn-delete" onclick="deleteSkill(<?= $skill['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-clipboard-list"></i>
                                <p style="color: #64748b; margin: 0; font-size: 14px;">No skills yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="categoryModalTitle">Add Category</h2>
            <button class="modal-close" onclick="closeCategoryModal()">&times;</button>
        </div>
        <form id="categoryForm" onsubmit="saveCategory(event)">
            <?= csrfTokenInput() ?>
            <input type="hidden" name="category_id" id="categoryId">
            <div class="form-group">
                <label class="form-label">Category Name</label>
                <input type="text" name="name" id="categoryName" class="form-input" required placeholder="e.g., Skating Skills">
            </div>
            <div class="form-group">
                <label class="form-label">Description (Optional)</label>
                <textarea name="description" id="categoryDescription" class="form-textarea" placeholder="Brief description of this category..."></textarea>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Save Category
            </button>
        </form>
    </div>
</div>

<!-- Skill Modal -->
<div id="skillModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="skillModalTitle">Add Skill</h2>
            <button class="modal-close" onclick="closeSkillModal()">&times;</button>
        </div>
        <form id="skillForm" onsubmit="saveSkill(event)">
            <?= csrfTokenInput() ?>
            <input type="hidden" name="skill_id" id="skillId">
            <input type="hidden" name="category_id" id="skillCategoryId">
            <div class="form-group">
                <label class="form-label">Skill Name</label>
                <input type="text" name="name" id="skillName" class="form-input" required placeholder="e.g., Forward Crossovers">
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" id="skillDescription" class="form-textarea" required placeholder="What is this skill about?"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Grading Criteria (Optional)</label>
                <textarea name="criteria" id="skillCriteria" class="form-textarea" placeholder="e.g., 1 = Cannot perform, 5 = Average, 10 = Elite level"></textarea>
            </div>
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Save Skill
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
// Initialize drag-and-drop for categories
const categoriesGrid = document.getElementById('categoriesGrid');
if (categoriesGrid) {
    new Sortable(categoriesGrid, {
        animation: 150,
        handle: '.category-header',
        ghostClass: 'dragging',
        onEnd: function(evt) {
            const categoryIds = Array.from(categoriesGrid.children).map(el => el.dataset.categoryId);
            reorderCategories(categoryIds);
        }
    });
}

// Initialize drag-and-drop for skills
document.querySelectorAll('.skills-sortable').forEach(list => {
    new Sortable(list, {
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'dragging',
        onEnd: function(evt) {
            const skillIds = Array.from(list.children).map(el => el.dataset.skillId);
            reorderSkills(skillIds);
        }
    });
});

// Category Management
function openCategoryModal() {
    document.getElementById('categoryModalTitle').textContent = 'Add Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModal').classList.add('active');
}

function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('active');
}

function editCategory(id, name, description) {
    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
    document.getElementById('categoryId').value = id;
    document.getElementById('categoryName').value = name;
    document.getElementById('categoryDescription').value = description || '';
    document.getElementById('categoryModal').classList.add('active');
}

async function saveCategory(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const categoryId = formData.get('category_id');
    formData.append('action', categoryId ? 'update_category' : 'create_category');
    
    try {
        const response = await fetch('process_eval_framework.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error saving category');
    }
}

async function deleteCategory(id) {
    if (!confirm('Delete this category? This action cannot be undone.')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_category');
    formData.append('category_id', id);
    formData.append('csrf_token', '<?= generateCsrfToken() ?>');
    
    try {
        const response = await fetch('process_eval_framework.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error deleting category');
    }
}

async function toggleCategory(id, activate) {
    const formData = new FormData();
    formData.append('action', 'toggle_active');
    formData.append('type', 'category');
    formData.append('id', id);
    formData.append('active', activate ? '1' : '0');
    formData.append('csrf_token', '<?= generateCsrfToken() ?>');
    
    try {
        const response = await fetch('process_eval_framework.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Error toggling category');
        }
    } catch (error) {
        alert('Error toggling category');
    }
}

async function reorderCategories(categoryIds) {
    const formData = new FormData();
    formData.append('action', 'reorder_categories');
    formData.append('category_ids', JSON.stringify(categoryIds));
    formData.append('csrf_token', '<?= generateCsrfToken() ?>');
    
    try {
        const response = await fetch('process_eval_framework.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (!data.success) {
            alert('Error reordering categories');
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Skill Management
function openSkillModal(categoryId) {
    document.getElementById('skillModalTitle').textContent = 'Add Skill';
    document.getElementById('skillForm').reset();
    document.getElementById('skillId').value = '';
    document.getElementById('skillCategoryId').value = categoryId;
    document.getElementById('skillModal').classList.add('active');
}

function closeSkillModal() {
    document.getElementById('skillModal').classList.remove('active');
}

function editSkill(id, categoryId, name, description, criteria) {
    document.getElementById('skillModalTitle').textContent = 'Edit Skill';
    document.getElementById('skillId').value = id;
    document.getElementById('skillCategoryId').value = categoryId;
    document.getElementById('skillName').value = name;
    document.getElementById('skillDescription').value = description;
    document.getElementById('skillCriteria').value = criteria || '';
    document.getElementById('skillModal').classList.add('active');
}

async function saveSkill(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const skillId = formData.get('skill_id');
    formData.append('action', skillId ? 'update_skill' : 'create_skill');
    
    try {
        const response = await fetch('process_eval_framework.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error saving skill');
    }
}

async function deleteSkill(id) {
    if (!confirm('Delete this skill? This action cannot be undone.')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_skill');
    formData.append('skill_id', id);
    formData.append('csrf_token', '<?= generateCsrfToken() ?>');
    
    try {
        const response = await fetch('process_eval_framework.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error deleting skill');
    }
}

async function toggleSkill(id, activate) {
    const formData = new FormData();
    formData.append('action', 'toggle_active');
    formData.append('type', 'skill');
    formData.append('id', id);
    formData.append('active', activate ? '1' : '0');
    formData.append('csrf_token', '<?= generateCsrfToken() ?>');
    
    try {
        const response = await fetch('process_eval_framework.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Error toggling skill');
        }
    } catch (error) {
        alert('Error toggling skill');
    }
}

async function reorderSkills(skillIds) {
    const formData = new FormData();
    formData.append('action', 'reorder_skills');
    formData.append('skill_ids', JSON.stringify(skillIds));
    formData.append('csrf_token', '<?= generateCsrfToken() ?>');
    
    try {
        const response = await fetch('process_eval_framework.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (!data.success) {
            alert('Error reordering skills');
            location.reload();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
</script>
