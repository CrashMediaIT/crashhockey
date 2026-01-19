<?php
// views/settings.php - Global system settings
require_once __DIR__ . '/../security.php';

// Check permission
requirePermission($pdo, $_SESSION['user_id'], $_SESSION['user_role'], 'edit_system_settings');

// Get current settings
$settings = $pdo->query("SELECT * FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$tax_rate = $settings['tax_rate'] ?? '13.00';
$tax_name = $settings['tax_name'] ?? 'HST';
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
            color: #ff6b7a;
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
    </div>
</div>
