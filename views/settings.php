<?php
// views/settings.php - Global system settings
require_once __DIR__ . '/../security.php';

// Check permission
requirePermission($pdo, $_SESSION['user_id'], $_SESSION['user_role'], 'edit_system_settings');

// Get current settings
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$tax_rate = $settings['tax_rate'] ?? '13.00';
$tax_name = $settings['tax_name'] ?? 'HST';
$nextcloud_url = $settings['nextcloud_url'] ?? '';
$nextcloud_username = $settings['nextcloud_username'] ?? '';
$nextcloud_password = $settings['nextcloud_password'] ?? '';
$nextcloud_folder = $settings['nextcloud_receipt_folder'] ?? '/receipts';
$google_maps_api_key = $settings['google_maps_api_key'] ?? '';
$mileage_rate_km = $settings['mileage_rate_per_km'] ?? '0.68';
$mileage_rate_mile = $settings['mileage_rate_per_mile'] ?? '1.10';
$receipt_scanning_enabled = $settings['receipt_scanning_enabled'] ?? '0';
?>

<div class="dash-content">
    <div class="dash-header">
        <h2><i class="fas fa-gears"></i> Global Settings</h2>
        <p style="color: rgba(255, 255, 255, 0.6);">Configure system-wide settings</p>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Settings updated successfully!
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .setting-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 25px;
        }

        .setting-card h3 {
            color: white;
            font-size: 1.2rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .setting-card h3 i {
            color: var(--primary);
        }

        .setting-card p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 6px;
            color: white;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .input-hint {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 5px;
        }

        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-save:hover {
            background: #e64500;
            transform: scale(1.02);
        }

        .current-value {
            background: rgba(255, 77, 0, 0.1);
            border: 1px solid rgba(255, 77, 0, 0.3);
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .current-value strong {
            color: var(--primary);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.4);
            color: #5dff7f;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #7000a4;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="settings-grid">
        <!-- Tax Configuration -->
        <div class="setting-card">
            <h3><i class="fas fa-percent"></i> Tax Configuration</h3>
            <p>Configure tax rate for session bookings</p>

            <div class="current-value">
                <strong>Current:</strong> <?= htmlspecialchars($tax_name) ?> @ <?= htmlspecialchars($tax_rate) ?>%
            </div>

            <form action="process_admin_age_skill.php" method="POST">
                <?= csrfTokenInput() ?>
                <input type="hidden" name="action" value="update_tax_settings">

                <div class="form-group">
                    <label>Tax Name</label>
                    <input type="text" name="tax_name" value="<?= htmlspecialchars($tax_name) ?>" 
                           required placeholder="e.g., HST, GST, VAT">
                    <div class="input-hint">Display name for the tax (e.g., HST, GST, VAT)</div>
                </div>

                <div class="form-group">
                    <label>Tax Rate (%)</label>
                    <input type="number" name="tax_rate" value="<?= htmlspecialchars($tax_rate) ?>" 
                           required step="0.01" min="0" max="100" placeholder="e.g., 13.00">
                    <div class="input-hint">Percentage added to session prices (e.g., 13.00 for 13% HST)</div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Tax Settings
                </button>
            </form>
        </div>

        <!-- Additional Settings Placeholders -->
        <div class="setting-card">
            <h3><i class="fas fa-credit-card"></i> Payment Integration</h3>
            <p>Stripe and payment gateway configuration</p>
            <div style="color: rgba(255, 255, 255, 0.5); text-align: center; padding: 40px 20px;">
                <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>Configure via Admin Settings panel</p>
            </div>
        </div>

        <div class="setting-card">
            <h3><i class="fas fa-envelope"></i> Email Configuration</h3>
            <p>SMTP settings for system emails</p>
            <div style="color: rgba(255, 255, 255, 0.5); text-align: center; padding: 40px 20px;">
                <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>Configured during setup</p>
            </div>
        </div>

        <div class="setting-card">
            <h3><i class="fas fa-globe"></i> Site Information</h3>
            <p>General site settings and branding</p>
            <div style="color: rgba(255, 255, 255, 0.5); text-align: center; padding: 40px 20px;">
                <i class="fas fa-wrench" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>Coming soon</p>
            </div>
        </div>

        <!-- Nextcloud Configuration -->
        <div class="setting-card">
            <h3><i class="fas fa-cloud"></i> Nextcloud Integration</h3>
            <p>Configure automatic receipt scanning from Nextcloud</p>

            <form id="nextcloudForm">
                <?= csrfTokenInput() ?>

                <div class="form-group">
                    <label>Nextcloud URL</label>
                    <input type="url" name="nextcloud_url" value="<?= htmlspecialchars($nextcloud_url) ?>" 
                           placeholder="https://your-nextcloud.com">
                    <div class="input-hint">Full URL to your Nextcloud instance</div>
                </div>

                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="nextcloud_username" value="<?= htmlspecialchars($nextcloud_username) ?>" 
                           placeholder="username">
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="nextcloud_password" value="<?= htmlspecialchars($nextcloud_password) ?>" 
                           placeholder="••••••••">
                    <div class="input-hint">App password recommended</div>
                </div>

                <div class="form-group">
                    <label>Receipt Folder Path</label>
                    <input type="text" name="nextcloud_receipt_folder" value="<?= htmlspecialchars($nextcloud_folder) ?>" 
                           placeholder="/receipts">
                    <div class="input-hint">Folder path where receipts are stored</div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="receipt_scanning_enabled" value="1" 
                               <?= $receipt_scanning_enabled == '1' ? 'checked' : '' ?>
                               style="width: auto;">
                        Enable Automatic Scanning
                    </label>
                </div>

                <button type="button" onclick="testNextcloud()" class="btn-save" style="margin-bottom: 10px; background: #3b82f6;">
                    <i class="fas fa-plug"></i> Test Connection
                </button>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Nextcloud Settings
                </button>
            </form>
        </div>

        <!-- Google Maps API -->
        <div class="setting-card">
            <h3><i class="fas fa-map"></i> Google Maps API</h3>
            <p>Configure Google Maps for mileage tracking</p>

            <form action="process_settings.php" method="POST">
                <?= csrfTokenInput() ?>
                <input type="hidden" name="action" value="update_google_maps">

                <div class="form-group">
                    <label>API Key</label>
                    <input type="text" name="google_maps_api_key" value="<?= htmlspecialchars($google_maps_api_key) ?>" 
                           placeholder="AIza...">
                    <div class="input-hint">Get your API key from Google Cloud Console</div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save API Key
                </button>
            </form>
        </div>

        <!-- Mileage Rates -->
        <div class="setting-card">
            <h3><i class="fas fa-car"></i> Mileage Rates</h3>
            <p>Set reimbursement rates for mileage tracking</p>

            <form action="process_settings.php" method="POST">
                <?= csrfTokenInput() ?>
                <input type="hidden" name="action" value="update_mileage_rates">

                <div class="form-group">
                    <label>Rate per Kilometer ($)</label>
                    <input type="number" name="mileage_rate_per_km" value="<?= htmlspecialchars($mileage_rate_km) ?>" 
                           step="0.01" min="0" placeholder="0.68">
                    <div class="input-hint">Standard rate per kilometer</div>
                </div>

                <div class="form-group">
                    <label>Rate per Mile ($)</label>
                    <input type="number" name="mileage_rate_per_mile" value="<?= htmlspecialchars($mileage_rate_mile) ?>" 
                           step="0.01" min="0" placeholder="1.10">
                    <div class="input-hint">Standard rate per mile</div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Mileage Rates
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('nextcloudForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'update_nextcloud');
    
    try {
        const response = await fetch('process_settings.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            window.location.href = '?page=settings&success=1';
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error saving settings: ' + error.message);
    }
});

async function testNextcloud() {
    const formData = new FormData(document.getElementById('nextcloudForm'));
    formData.append('action', 'test_nextcloud');
    
    try {
        const response = await fetch('process_settings.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            alert('✓ Connection successful!\n\nFound ' + result.file_count + ' files in receipt folder.');
        } else {
            alert('✗ Connection failed:\n\n' + result.message);
        }
    } catch (error) {
        alert('Error testing connection: ' + error.message);
    }
}
</script>
