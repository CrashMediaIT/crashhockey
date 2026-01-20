<?php
/**
 * Admin - Manage Locations
 * Add, edit, and manage training locations
 */

require_once __DIR__ . '/../security.php';

// Check if user has permission
if ($user_role !== 'admin') {
    header('Location: dashboard.php?page=home');
    exit;
}

// Get all locations
$locations = $pdo->query("
    SELECT l.*, 
           (SELECT COUNT(*) FROM sessions WHERE arena = l.name) as session_count
    FROM locations l
    ORDER BY l.city, l.name
")->fetchAll();
?>

<style>
    :root {
        --primary: #7000a4;
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
    }
    .btn-create:hover {
        background: #e64500;
    }
    .locations-table {
        width: 100%;
        border-collapse: collapse;
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        overflow: hidden;
    }
    .locations-table thead {
        background: #06080b;
    }
    .locations-table th {
        text-align: left;
        padding: 15px;
        color: #94a3b8;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 700;
    }
    .locations-table td {
        padding: 15px;
        border-bottom: 1px solid #1e293b;
        color: #fff;
    }
    .locations-table tr:hover {
        background: rgba(255, 77, 0, 0.05);
    }
    .btn-edit, .btn-delete {
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 12px;
        font-weight: 600;
        margin-right: 8px;
        transition: all 0.2s;
    }
    .btn-edit {
        background: var(--primary);
        color: #fff;
    }
    .btn-edit:hover {
        background: #e64500;
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
    .form-input {
        width: 100%;
        padding: 12px;
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        color: #fff;
        font-size: 14px;
    }
    .form-input:focus {
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
        background: #e64500;
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
</style>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-map-marker-alt"></i> Manage Locations
    </h1>
    <button onclick="openCreateModal()" class="btn-create">
        <i class="fas fa-plus"></i> Add Location
    </button>
</div>

<?php if (empty($locations)): ?>
    <div class="empty-state">
        <i class="fas fa-map-marker-alt"></i>
        <h2 style="font-size: 24px; color: #fff; margin-bottom: 10px;">No Locations</h2>
        <p style="color: #64748b;">Add your first training location to get started</p>
    </div>
<?php else: ?>
    <table class="locations-table">
        <thead>
            <tr>
                <th>Arena Name</th>
                <th>City</th>
                <th>Sessions</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($locations as $location): ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($location['name']) ?></td>
                    <td><?= htmlspecialchars($location['city']) ?></td>
                    <td><?= $location['session_count'] ?> sessions</td>
                    <td><?= date('M d, Y', strtotime($location['created_at'])) ?></td>
                    <td>
                        <a href="#" onclick="openEditModal(<?= $location['id'] ?>, '<?= htmlspecialchars($location['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($location['city'], ENT_QUOTES) ?>')" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php if ($location['session_count'] == 0): ?>
                            <a href="#" onclick="deleteLocation(<?= $location['id'] ?>)" class="btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- Create/Edit Modal -->
<div id="locationModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="modalTitle">Add Location</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        
        <form method="POST" action="process_admin_action.php" id="locationForm">
            <?= csrfTokenInput() ?>
            <input type="hidden" name="action" id="formAction" value="create_location">
            <input type="hidden" name="location_id" id="locationId">
            
            <div class="form-group">
                <label class="form-label">Arena Name *</label>
                <input type="text" name="name" id="locationName" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">City *</label>
                <input type="text" name="city" id="locationCity" class="form-input" required>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-save"></i> Save Location
            </button>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add Location';
    document.getElementById('formAction').value = 'create_location';
    document.getElementById('locationId').value = '';
    document.getElementById('locationName').value = '';
    document.getElementById('locationCity').value = '';
    document.getElementById('locationModal').classList.add('active');
}

function openEditModal(id, name, city) {
    document.getElementById('modalTitle').textContent = 'Edit Location';
    document.getElementById('formAction').value = 'edit_location';
    document.getElementById('locationId').value = id;
    document.getElementById('locationName').value = name;
    document.getElementById('locationCity').value = city;
    document.getElementById('locationModal').classList.add('active');
}

function closeModal() {
    document.getElementById('locationModal').classList.remove('active');
}

function deleteLocation(id) {
    if (confirm('Are you sure you want to delete this location?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'process_admin_action.php';
        form.innerHTML = '<?= csrfTokenInput() ?>' +
            '<input type="hidden" name="action" value="delete_location">' +
            '<input type="hidden" name="location_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('locationModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
