<?php
/**
 * Admin Theme Settings
 * Comprehensive theming portal for customizing application colors
 */

require_once __DIR__ . '/../security.php';

// Check if user is admin
if ($user_role !== 'admin') {
    header('Location: dashboard.php?page=home');
    exit;
}

// Get current theme settings
try {
    $stmt = $pdo->query("SELECT setting_name, setting_value FROM theme_settings");
    $theme_colors = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $theme_colors = [];
}

// Default colors
$defaults = [
    'primary_color' => '#7000a4',
    'secondary_color' => '#c0c0c0',
    'background_color' => '#06080b',
    'card_background_color' => '#0d1117',
    'text_color' => '#ffffff',
    'text_muted_color' => '#94a3b8',
    'border_color' => '#1e293b',
    'sidebar_color' => '#020305',
    'button_hover_color' => '#a78bfa',
    'success_color' => '#22c55e',
    'error_color' => '#ef4444',
    'warning_color' => '#f59e0b'
];

$colors = array_merge($defaults, $theme_colors);
?>

<style>
    :root {
        --primary: #7000a4;
    }
    
    .theme-header {
        margin-bottom: 30px;
    }
    
    .theme-header h1 {
        font-size: 32px;
        font-weight: 900;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .theme-header h1 i {
        color: var(--primary);
    }
    
    .theme-header p {
        color: #94a3b8;
        font-size: 14px;
    }
    
    .theme-container {
        display: grid;
        grid-template-columns: 1fr 450px;
        gap: 30px;
        margin-top: 30px;
    }
    
    .color-settings-panel {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 12px;
        padding: 30px;
    }
    
    .settings-section {
        margin-bottom: 35px;
    }
    
    .settings-section:last-child {
        margin-bottom: 0;
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 20px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-title i {
        color: var(--primary);
        font-size: 14px;
    }
    
    .color-input-group {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .color-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .color-field label {
        font-size: 12px;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .color-picker-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 8px;
        padding: 8px 12px;
        transition: all 0.2s;
    }
    
    .color-picker-wrapper:hover,
    .color-picker-wrapper:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 2px rgba(112, 0, 164, 0.1);
    }
    
    .color-swatch {
        width: 40px;
        height: 40px;
        border-radius: 6px;
        border: 2px solid #1e293b;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }
    
    .color-swatch:hover {
        transform: scale(1.05);
        border-color: var(--primary);
    }
    
    .color-hex-input {
        flex: 1;
        background: transparent;
        border: none;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        font-family: 'Courier New', monospace;
        outline: none;
    }
    
    .color-picker {
        position: absolute;
        opacity: 0;
        pointer-events: none;
        width: 0;
        height: 0;
    }
    
    .preview-panel {
        background: #0d1117;
        border: 1px solid #1e293b;
        border-radius: 12px;
        padding: 30px;
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 140px);
        overflow-y: auto;
    }
    
    .preview-title {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 20px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .preview-title i {
        color: var(--primary);
    }
    
    .preview-container {
        background: var(--bg, #06080b);
        border: 1px solid var(--border, #1e293b);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .preview-card {
        background: var(--card-bg, #0d1117);
        border: 1px solid var(--border, #1e293b);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 15px;
    }
    
    .preview-card h3 {
        color: var(--text-color, #fff);
        font-size: 18px;
        margin-bottom: 8px;
    }
    
    .preview-card p {
        color: var(--text-muted, #94a3b8);
        font-size: 14px;
        line-height: 1.6;
    }
    
    .preview-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .preview-btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .preview-btn-primary {
        background: var(--primary, #7000a4);
        color: #fff;
    }
    
    .preview-btn-primary:hover {
        background: var(--button-hover, #a78bfa);
        transform: translateY(-2px);
    }
    
    .preview-btn-secondary {
        background: transparent;
        color: var(--primary, #7000a4);
        border: 1px solid var(--border, #1e293b);
    }
    
    .preview-sidebar {
        background: var(--sidebar, #020305);
        border: 1px solid var(--border, #1e293b);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .preview-nav-item {
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 13px;
        color: var(--text-muted, #94a3b8);
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .preview-nav-item.active {
        background: rgba(112, 0, 164, 0.1);
        color: var(--primary, #7000a4);
    }
    
    .preview-status {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }
    
    .status-success {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success, #22c55e);
        border: 1px solid var(--success, #22c55e);
    }
    
    .status-error {
        background: rgba(239, 68, 68, 0.1);
        color: var(--error, #ef4444);
        border: 1px solid var(--error, #ef4444);
    }
    
    .status-warning {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning, #f59e0b);
        border: 1px solid var(--warning, #f59e0b);
    }
    
    .action-buttons {
        display: flex;
        gap: 12px;
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #1e293b;
    }
    
    .btn {
        padding: 14px 28px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-save {
        background: var(--primary);
        color: #fff;
        flex: 1;
    }
    
    .btn-save:hover {
        background: #a78bfa;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(112, 0, 164, 0.3);
    }
    
    .btn-reset {
        background: transparent;
        color: #ef4444;
        border: 1px solid #ef4444;
    }
    
    .btn-reset:hover {
        background: rgba(239, 68, 68, 0.1);
    }
    
    .alert {
        padding: 14px 18px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        display: none;
        align-items: center;
        gap: 10px;
    }
    
    .alert.show {
        display: flex;
    }
    
    .alert-success {
        background: rgba(34, 197, 94, 0.1);
        color: #22c55e;
        border: 1px solid #22c55e;
    }
    
    .alert-error {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
        border: 1px solid #ef4444;
    }
    
    @media (max-width: 1200px) {
        .theme-container {
            grid-template-columns: 1fr;
        }
        
        .preview-panel {
            position: relative;
            max-height: none;
        }
    }
</style>

<div class="theme-header">
    <h1><i class="fas fa-palette"></i> Theme Settings</h1>
    <p>Customize colors across the entire application. Changes apply immediately to all pages.</p>
</div>

<div id="alertContainer"></div>

<div class="theme-container">
    <div class="color-settings-panel">
        <form id="themeForm">
            <?= csrfTokenInput() ?>
            
            <!-- Brand Colors -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-paint-brush"></i>
                    Brand Colors
                </h3>
                <div class="color-input-group">
                    <div class="color-field">
                        <label>Primary Color</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['primary_color']) ?>;" onclick="document.getElementById('primary_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="primary_color" value="<?= htmlspecialchars($colors['primary_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="primary_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['primary_color']) ?>">
                        </div>
                    </div>
                    
                    <div class="color-field">
                        <label>Secondary/Accent</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['secondary_color']) ?>;" onclick="document.getElementById('secondary_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="secondary_color" value="<?= htmlspecialchars($colors['secondary_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="secondary_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['secondary_color']) ?>">
                        </div>
                    </div>
                    
                    <div class="color-field">
                        <label>Button Hover</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['button_hover_color']) ?>;" onclick="document.getElementById('button_hover_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="button_hover_color" value="<?= htmlspecialchars($colors['button_hover_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="button_hover_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['button_hover_color']) ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Background Colors -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-fill-drip"></i>
                    Background Colors
                </h3>
                <div class="color-input-group">
                    <div class="color-field">
                        <label>Main Background</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['background_color']) ?>;" onclick="document.getElementById('background_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="background_color" value="<?= htmlspecialchars($colors['background_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="background_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['background_color']) ?>">
                        </div>
                    </div>
                    
                    <div class="color-field">
                        <label>Card Background</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['card_background_color']) ?>;" onclick="document.getElementById('card_background_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="card_background_color" value="<?= htmlspecialchars($colors['card_background_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="card_background_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['card_background_color']) ?>">
                        </div>
                    </div>
                    
                    <div class="color-field">
                        <label>Sidebar Background</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['sidebar_color']) ?>;" onclick="document.getElementById('sidebar_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="sidebar_color" value="<?= htmlspecialchars($colors['sidebar_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="sidebar_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['sidebar_color']) ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Text Colors -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-font"></i>
                    Text Colors
                </h3>
                <div class="color-input-group">
                    <div class="color-field">
                        <label>Primary Text</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['text_color']) ?>;" onclick="document.getElementById('text_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="text_color" value="<?= htmlspecialchars($colors['text_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="text_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['text_color']) ?>">
                        </div>
                    </div>
                    
                    <div class="color-field">
                        <label>Muted Text</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['text_muted_color']) ?>;" onclick="document.getElementById('text_muted_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="text_muted_color" value="<?= htmlspecialchars($colors['text_muted_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="text_muted_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['text_muted_color']) ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- UI Colors -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-border-style"></i>
                    UI Colors
                </h3>
                <div class="color-input-group">
                    <div class="color-field">
                        <label>Border Color</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['border_color']) ?>;" onclick="document.getElementById('border_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="border_color" value="<?= htmlspecialchars($colors['border_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="border_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['border_color']) ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Status Colors -->
            <div class="settings-section">
                <h3 class="section-title">
                    <i class="fas fa-check-circle"></i>
                    Status Colors
                </h3>
                <div class="color-input-group">
                    <div class="color-field">
                        <label>Success</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['success_color']) ?>;" onclick="document.getElementById('success_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="success_color" value="<?= htmlspecialchars($colors['success_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="success_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['success_color']) ?>">
                        </div>
                    </div>
                    
                    <div class="color-field">
                        <label>Error</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['error_color']) ?>;" onclick="document.getElementById('error_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="error_color" value="<?= htmlspecialchars($colors['error_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="error_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['error_color']) ?>">
                        </div>
                    </div>
                    
                    <div class="color-field">
                        <label>Warning</label>
                        <div class="color-picker-wrapper">
                            <div class="color-swatch" style="background: <?= htmlspecialchars($colors['warning_color']) ?>;" onclick="document.getElementById('warning_color_picker').click()"></div>
                            <input type="text" class="color-hex-input" name="warning_color" value="<?= htmlspecialchars($colors['warning_color']) ?>" pattern="^#[0-9A-Fa-f]{6}$" required>
                            <input type="color" id="warning_color_picker" class="color-picker" value="<?= htmlspecialchars($colors['warning_color']) ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <button type="submit" class="btn btn-save">
                    <i class="fas fa-save"></i> Save Theme
                </button>
                <button type="button" class="btn btn-reset" onclick="resetTheme()">
                    <i class="fas fa-undo"></i> Reset to Defaults
                </button>
            </div>
        </form>
    </div>
    
    <div class="preview-panel">
        <h3 class="preview-title">
            <i class="fas fa-eye"></i>
            Live Preview
        </h3>
        
        <div class="preview-container" id="previewContainer">
            <div class="preview-sidebar">
                <div class="preview-nav-item active">
                    <i class="fas fa-home"></i> Home
                </div>
                <div class="preview-nav-item">
                    <i class="fas fa-chart-line"></i> Stats
                </div>
                <div class="preview-nav-item">
                    <i class="fas fa-calendar"></i> Schedule
                </div>
            </div>
            
            <div class="preview-card">
                <h3>Sample Card</h3>
                <p>This preview shows how your theme colors will look across the application. Update any color to see changes instantly.</p>
            </div>
            
            <div class="preview-buttons">
                <button class="preview-btn preview-btn-primary">Primary Button</button>
                <button class="preview-btn preview-btn-secondary">Secondary Button</button>
            </div>
            
            <div class="preview-status">
                <span class="status-badge status-success">Success</span>
                <span class="status-badge status-error">Error</span>
                <span class="status-badge status-warning">Warning</span>
            </div>
        </div>
    </div>
</div>

<script>
// Sync color picker with text input
document.querySelectorAll('.color-picker').forEach(picker => {
    const name = picker.id.replace('_picker', '');
    const textInput = document.querySelector(`input[name="${name}"]`);
    const swatch = picker.previousElementSibling.previousElementSibling;
    
    picker.addEventListener('input', function() {
        textInput.value = this.value;
        swatch.style.background = this.value;
        updatePreview();
    });
    
    textInput.addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            picker.value = this.value;
            swatch.style.background = this.value;
            updatePreview();
        }
    });
});

// Update live preview
function updatePreview() {
    const formData = new FormData(document.getElementById('themeForm'));
    const container = document.getElementById('previewContainer');
    
    container.style.setProperty('--primary', formData.get('primary_color'));
    container.style.setProperty('--bg', formData.get('background_color'));
    container.style.setProperty('--card-bg', formData.get('card_background_color'));
    container.style.setProperty('--sidebar', formData.get('sidebar_color'));
    container.style.setProperty('--text-color', formData.get('text_color'));
    container.style.setProperty('--text-muted', formData.get('text_muted_color'));
    container.style.setProperty('--border', formData.get('border_color'));
    container.style.setProperty('--button-hover', formData.get('button_hover_color'));
    container.style.setProperty('--success', formData.get('success_color'));
    container.style.setProperty('--error', formData.get('error_color'));
    container.style.setProperty('--warning', formData.get('warning_color'));
}

// Save theme
document.getElementById('themeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'save');
    
    try {
        const response = await fetch('process_theme_settings.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        showAlert('error', 'An error occurred while saving the theme.');
    }
});

// Reset to defaults
async function resetTheme() {
    if (!confirm('Are you sure you want to reset all colors to their default values?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'reset');
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    try {
        const response = await fetch('process_theme_settings.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('success', data.message);
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        showAlert('error', 'An error occurred while resetting the theme.');
    }
}

// Show alert
function showAlert(type, message) {
    const container = document.getElementById('alertContainer');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} show`;
    alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
    container.appendChild(alert);
    
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 300);
    }, 5000);
}

// Initialize preview
updatePreview();
</script>
