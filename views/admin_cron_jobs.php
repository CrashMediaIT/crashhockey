<?php
/**
 * Admin Cron Job Management
 * Comprehensive interface for managing scheduled tasks
 */

require_once __DIR__ . '/../security.php';

// Check if user is admin
if ($user_role !== 'admin') {
    header('Location: dashboard.php?page=home');
    exit;
}

// Fetch all cron jobs
$jobs_query = $pdo->query("
    SELECT cj.*, u.full_name as created_by_name
    FROM cron_jobs cj
    LEFT JOIN users u ON cj.created_by = u.id
    ORDER BY cj.created_at DESC
");
$jobs = $jobs_query->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root {
        --primary: #7000a4;
    }
    
    .cron-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }
    
    .cron-header h1 {
        font-size: 32px;
        font-weight: 900;
        margin: 0;
    }
    
    .cron-header p {
        color: #94a3b8;
        font-size: 14px;
        margin: 5px 0 0 0;
    }
    
    .btn-create {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-create:hover {
        background: #5a0080;
        transform: translateY(-2px);
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 6px;
        margin-bottom: 20px;
        display: none;
        align-items: center;
        gap: 10px;
    }
    
    .alert.show {
        display: flex;
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
    
    .jobs-table-container {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .jobs-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .jobs-table thead {
        background: #06080b;
    }
    
    .jobs-table th {
        padding: 15px;
        text-align: left;
        font-size: 12px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #1e293b;
    }
    
    .jobs-table td {
        padding: 15px;
        border-bottom: 1px solid #1e293b;
        color: #fff;
        font-size: 14px;
    }
    
    .jobs-table tbody tr:hover {
        background: rgba(112, 0, 164, 0.05);
    }
    
    .jobs-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-active {
        background: rgba(0, 255, 136, 0.15);
        color: #00ff88;
        border: 1px solid #00ff88;
    }
    
    .badge-inactive {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid #ef4444;
    }
    
    .badge-report {
        background: rgba(59, 130, 246, 0.15);
        color: #3b82f6;
        border: 1px solid #3b82f6;
    }
    
    .badge-admin {
        background: rgba(112, 0, 164, 0.15);
        color: var(--primary);
        border: 1px solid var(--primary);
    }
    
    .badge-backup {
        background: rgba(251, 191, 36, 0.15);
        color: #fbbf24;
        border: 1px solid #fbbf24;
    }
    
    .badge-maintenance {
        background: rgba(156, 163, 175, 0.15);
        color: #9ca3af;
        border: 1px solid #9ca3af;
    }
    
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    
    .btn-icon {
        background: transparent;
        border: 1px solid #1e293b;
        color: #94a3b8;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 14px;
    }
    
    .btn-icon:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    
    .btn-icon.danger:hover {
        border-color: #ef4444;
        color: #ef4444;
    }
    
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        overflow-y: auto;
        padding: 20px;
    }
    
    .modal.show {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 8px;
        width: 100%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-header {
        padding: 20px 25px;
        border-bottom: 1px solid #1e293b;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-header h2 {
        font-size: 20px;
        font-weight: 700;
        margin: 0;
        color: #fff;
    }
    
    .modal-close {
        background: transparent;
        border: none;
        color: #94a3b8;
        font-size: 24px;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .modal-close:hover {
        color: #fff;
    }
    
    .modal-body {
        padding: 25px;
    }
    
    .modal-footer {
        padding: 20px 25px;
        border-top: 1px solid #1e293b;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
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
        letter-spacing: 0.5px;
    }
    
    .form-label .required {
        color: #ef4444;
    }
    
    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        padding: 12px;
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        color: #fff;
        font-size: 14px;
        font-family: inherit;
    }
    
    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        outline: none;
        border-color: var(--primary);
    }
    
    .form-textarea {
        resize: vertical;
        min-height: 100px;
        font-family: 'Courier New', monospace;
    }
    
    .help-text {
        font-size: 12px;
        color: #64748b;
        margin-top: 5px;
        line-height: 1.4;
    }
    
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-weight: 700;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .btn-primary:hover {
        background: #5a0080;
    }
    
    .btn-secondary {
        background: transparent;
        border: 1px solid #1e293b;
        color: #94a3b8;
        padding: 12px 24px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .btn-secondary:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    
    .schedule-helpers {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 8px;
        margin-top: 10px;
    }
    
    .schedule-helper-btn {
        background: #06080b;
        border: 1px solid #1e293b;
        color: #94a3b8;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.2s;
        text-align: center;
    }
    
    .schedule-helper-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #64748b;
    }
    
    .empty-state i {
        font-size: 64px;
        color: #1e293b;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 8px;
        color: #94a3b8;
    }
    
    .empty-state p {
        font-size: 14px;
        margin-bottom: 20px;
    }
    
    .code-block {
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 4px;
        padding: 8px 12px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        color: var(--primary);
        overflow-x: auto;
    }
    
    .time-display {
        color: #64748b;
        font-size: 13px;
    }
</style>

<div class="cron-header">
    <div>
        <h1><i class="fas fa-clock"></i> Cron Job Management</h1>
        <p>Manage scheduled tasks and automated processes</p>
    </div>
    <button class="btn-create" onclick="openCreateModal()">
        <i class="fas fa-plus"></i> Create Cron Job
    </button>
</div>

<div id="alertContainer"></div>

<?php if (empty($jobs)): ?>
    <div class="jobs-table-container">
        <div class="empty-state">
            <i class="fas fa-clock"></i>
            <h3>No Cron Jobs Found</h3>
            <p>Create your first cron job to automate scheduled tasks</p>
            <button class="btn-create" onclick="openCreateModal()">
                <i class="fas fa-plus"></i> Create Cron Job
            </button>
        </div>
    </div>
<?php else: ?>
    <div class="jobs-table-container">
        <table class="jobs-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Last Run</th>
                    <th>Next Run</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($job['name']) ?></strong>
                            <?php if ($job['description']): ?>
                                <div style="font-size: 12px; color: #64748b; margin-top: 3px;">
                                    <?= htmlspecialchars($job['description']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($job['type']) ?>">
                                <?= htmlspecialchars($job['type']) ?>
                            </span>
                        </td>
                        <td>
                            <code class="code-block"><?= htmlspecialchars($job['schedule']) ?></code>
                        </td>
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($job['status']) ?>">
                                <?= htmlspecialchars($job['status']) ?>
                            </span>
                        </td>
                        <td class="time-display">
                            <?= $job['last_run'] ? date('M j, Y g:i A', strtotime($job['last_run'])) : 'Never' ?>
                        </td>
                        <td class="time-display">
                            <?= $job['next_run'] ? date('M j, Y g:i A', strtotime($job['next_run'])) : 'N/A' ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-icon" onclick="toggleStatus(<?= $job['id'] ?>)" title="Toggle Status">
                                    <i class="fas fa-power-off"></i>
                                </button>
                                <button class="btn-icon" onclick="editJob(<?= $job['id'] ?>)" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon danger" onclick="deleteJob(<?= $job['id'] ?>, '<?= htmlspecialchars($job['name'], ENT_QUOTES) ?>')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Create/Edit Modal -->
<div id="jobModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Create Cron Job</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form id="jobForm" onsubmit="submitForm(event)">
            <input type="hidden" name="csrf_token" value="<?= csrfTokenInput() ?>">
            <input type="hidden" id="jobId" name="id">
            <input type="hidden" id="formAction" name="action" value="create">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">
                        Name <span class="required">*</span>
                    </label>
                    <input type="text" id="jobName" name="name" class="form-input" required placeholder="e.g., Daily Database Backup">
                    <div class="help-text">A descriptive name for this cron job</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input type="text" id="jobDescription" name="description" class="form-input" placeholder="Optional description">
                    <div class="help-text">Brief explanation of what this job does</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Command <span class="required">*</span>
                    </label>
                    <input type="text" id="jobCommand" name="command" class="form-input" required placeholder="e.g., cron_database_backup.php">
                    <div class="help-text">PHP file or command to execute</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Schedule Type <span class="required">*</span>
                    </label>
                    <select id="scheduleType" class="form-select" onchange="updateScheduleUI()" required>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="custom">Custom (Cron Expression)</option>
                    </select>
                </div>

                <div class="form-group" id="dailyOptions">
                    <label class="form-label">Time <span class="required">*</span></label>
                    <input type="time" id="dailyTime" class="form-input" value="00:00">
                </div>

                <div class="form-group" id="weeklyOptions" style="display: none;">
                    <label class="form-label">Day of Week <span class="required">*</span></label>
                    <select id="weeklyDay" class="form-select">
                        <option value="0">Sunday</option>
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                        <option value="6">Saturday</option>
                    </select>
                    <label class="form-label" style="margin-top: 10px;">Time <span class="required">*</span></label>
                    <input type="time" id="weeklyTime" class="form-input" value="00:00">
                </div>

                <div class="form-group" id="monthlyOptions" style="display: none;">
                    <label class="form-label">Day of Month <span class="required">*</span></label>
                    <select id="monthlyDay" class="form-select">
                        <option value="1">1st of month</option>
                        <option value="15">15th of month</option>
                        <option value="L">Last day of month</option>
                        <?php for($i = 2; $i <= 31; $i++): ?>
                            <?php if($i != 15): ?>
                                <option value="<?= $i ?>"><?= $i ?><?php
                                    if($i == 2 || $i == 22) echo 'nd';
                                    elseif($i == 3 || $i == 23) echo 'rd';
                                    elseif($i == 21 || $i == 31) echo 'st';
                                    else echo 'th';
                                ?> of month</option>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </select>
                    <label class="form-label" style="margin-top: 10px;">Time <span class="required">*</span></label>
                    <input type="time" id="monthlyTime" class="form-input" value="00:00">
                </div>

                <div class="form-group" id="customOptions" style="display: none;">
                    <label class="form-label">Cron Expression <span class="required">*</span></label>
                    <input type="text" id="customCron" class="form-input" placeholder="0 * * * *" pattern="^[\d\*\-\/,\s]+$">
                    <div class="help-text">Cron expression (5 fields: minute hour day month weekday)</div>
                    
                    <div class="schedule-helpers">
                        <button type="button" class="schedule-helper-btn" onclick="setCustomSchedule('0 * * * *')">
                            <i class="fas fa-clock"></i> Every Hour
                        </button>
                        <button type="button" class="schedule-helper-btn" onclick="setCustomSchedule('*/15 * * * *')">
                            <i class="fas fa-redo"></i> Every 15 Min
                        </button>
                        <button type="button" class="schedule-helper-btn" onclick="setCustomSchedule('0 */3 * * *')">
                            <i class="fas fa-clock"></i> Every 3 Hours
                        </button>
                        <button type="button" class="schedule-helper-btn" onclick="setCustomSchedule('0 */6 * * *')">
                            <i class="fas fa-clock"></i> Every 6 Hours
                        </button>
                    </div>
                </div>

                <input type="hidden" id="jobSchedule" name="schedule" required>
                
                <div class="form-group">
                    <label class="form-label">
                        Type <span class="required">*</span>
                    </label>
                    <select id="jobType" name="type" class="form-select" required>
                        <option value="admin">Admin</option>
                        <option value="report">Report</option>
                        <option value="backup">Backup</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                    <div class="help-text">Category for organizing jobs</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        Status <span class="required">*</span>
                    </label>
                    <select id="jobStatus" name="status" class="form-select" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <div class="help-text">Only active jobs will run</div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Parameters (JSON)</label>
                    <textarea id="jobParameters" name="parameters" class="form-textarea" placeholder='{"key": "value"}'></textarea>
                    <div class="help-text">Optional JSON parameters for the job (must be valid JSON)</div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save Job
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Job data for editing
const jobsData = <?= json_encode($jobs) ?>;

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Cron Job';
    document.getElementById('formAction').value = 'create';
    document.getElementById('jobForm').reset();
    document.getElementById('jobId').value = '';
    document.querySelector('input[name="csrf_token"]').value = '<?= generateCSRFToken() ?>';
    document.getElementById('jobModal').classList.add('show');
}

function editJob(jobId) {
    const job = jobsData.find(j => j.id == jobId);
    if (!job) return;
    
    document.getElementById('modalTitle').textContent = 'Edit Cron Job';
    document.getElementById('formAction').value = 'update';
    document.getElementById('jobId').value = job.id;
    document.getElementById('jobName').value = job.name;
    document.getElementById('jobDescription').value = job.description || '';
    document.getElementById('jobCommand').value = job.command;
    document.getElementById('jobSchedule').value = job.schedule;
    document.getElementById('jobType').value = job.type;
    document.getElementById('jobStatus').value = job.status;
    document.getElementById('jobParameters').value = job.parameters || '';
    document.querySelector('input[name="csrf_token"]').value = '<?= generateCSRFToken() ?>';
    
    document.getElementById('jobModal').classList.add('show');
}

function closeModal() {
    document.getElementById('jobModal').classList.remove('show');
}

function setCustomSchedule(expression) {
    document.getElementById('customCron').value = expression;
    updateScheduleFromUI();
}

function updateScheduleUI() {
    const type = document.getElementById('scheduleType').value;
    
    // Hide all options
    document.getElementById('dailyOptions').style.display = 'none';
    document.getElementById('weeklyOptions').style.display = 'none';
    document.getElementById('monthlyOptions').style.display = 'none';
    document.getElementById('customOptions').style.display = 'none';
    
    // Show selected option
    if (type === 'daily') {
        document.getElementById('dailyOptions').style.display = 'block';
    } else if (type === 'weekly') {
        document.getElementById('weeklyOptions').style.display = 'block';
    } else if (type === 'monthly') {
        document.getElementById('monthlyOptions').style.display = 'block';
    } else if (type === 'custom') {
        document.getElementById('customOptions').style.display = 'block';
    }
    
    updateScheduleFromUI();
}

function updateScheduleFromUI() {
    const type = document.getElementById('scheduleType').value;
    let cron = '';
    
    if (type === 'daily') {
        const time = document.getElementById('dailyTime').value.split(':');
        cron = `${time[1]} ${time[0]} * * *`;
    } else if (type === 'weekly') {
        const time = document.getElementById('weeklyTime').value.split(':');
        const day = document.getElementById('weeklyDay').value;
        cron = `${time[1]} ${time[0]} * * ${day}`;
    } else if (type === 'monthly') {
        const time = document.getElementById('monthlyTime').value.split(':');
        const day = document.getElementById('monthlyDay').value;
        if (day === 'L') {
            // Last day of month - use 28 as approximation, cron doesn't support 'L'
            cron = `${time[1]} ${time[0]} 28 * *`;
        } else {
            cron = `${time[1]} ${time[0]} ${day} * *`;
        }
    } else if (type === 'custom') {
        cron = document.getElementById('customCron').value;
    }
    
    document.getElementById('jobSchedule').value = cron;
}

// Add event listeners to update schedule on change
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('dailyTime')) {
        document.getElementById('dailyTime').addEventListener('change', updateScheduleFromUI);
        document.getElementById('weeklyDay').addEventListener('change', updateScheduleFromUI);
        document.getElementById('weeklyTime').addEventListener('change', updateScheduleFromUI);
        document.getElementById('monthlyDay').addEventListener('change', updateScheduleFromUI);
        document.getElementById('monthlyTime').addEventListener('change', updateScheduleFromUI);
        document.getElementById('customCron').addEventListener('input', updateScheduleFromUI);
    }
});

function submitForm(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Validate JSON parameters if provided
    const params = formData.get('parameters');
    if (params && params.trim()) {
        try {
            JSON.parse(params);
        } catch (e) {
            showAlert('Parameters must be valid JSON', 'error');
            return;
        }
    }
    
    // Submit via AJAX
    fetch('process_cron_jobs.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal();
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        showAlert('An error occurred: ' + error.message, 'error');
    });
}

function toggleStatus(jobId) {
    if (!confirm('Toggle the status of this cron job?')) return;
    
    const formData = new FormData();
    formData.append('action', 'toggle_status');
    formData.append('id', jobId);
    formData.append('csrf_token', '<?= generateCSRFToken() ?>');
    
    fetch('process_cron_jobs.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        showAlert('An error occurred: ' + error.message, 'error');
    });
}

function deleteJob(jobId, jobName) {
    if (!confirm(`Are you sure you want to delete the cron job "${jobName}"?\n\nThis action cannot be undone.`)) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', jobId);
    formData.append('csrf_token', '<?= generateCSRFToken() ?>');
    
    fetch('process_cron_jobs.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        showAlert('An error occurred: ' + error.message, 'error');
    });
}

function showAlert(message, type) {
    const container = document.getElementById('alertContainer');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} show`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    container.innerHTML = '';
    container.appendChild(alert);
    
    setTimeout(() => {
        alert.classList.remove('show');
    }, 5000);
}

// Close modal when clicking outside
document.getElementById('jobModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
