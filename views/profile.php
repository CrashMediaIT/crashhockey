<?php
/**
 * User Profile Editor
 * Edit user profile information and settings
 */

require_once __DIR__ . '/../security.php';

// Get user details
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_data = $user_stmt->fetch();

// Get managed athletes if parent
$managed_athletes = [];
if ($user_role === 'parent') {
    $athletes_stmt = $pdo->prepare("
        SELECT u.*, ma.relationship, ma.can_book, ma.can_view_stats
        FROM managed_athletes ma
        INNER JOIN users u ON ma.athlete_id = u.id
        WHERE ma.parent_id = ?
        ORDER BY u.first_name, u.last_name
    ");
    $athletes_stmt->execute([$user_id]);
    $managed_athletes = $athletes_stmt->fetchAll();
}
?>

<style>
    :root {
        --primary: #7000a4;
    }
    .profile-header {
        background: linear-gradient(135deg, var(--primary) 0%, #4a0070 100%);
        border-radius: 8px;
        padding: 30px;
        margin-bottom: 30px;
        color: #fff;
    }
    .profile-header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
        font-weight: 900;
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
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
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
        transition: all 0.2s;
    }
    .form-input:focus {
        outline: none;
        border-color: var(--primary);
    }
    .form-input:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .form-checkbox {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        cursor: pointer;
    }
    .form-checkbox input {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .form-checkbox label {
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-save {
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
    .btn-save:hover {
        background: #e64500;
    }
    .athlete-card {
        background: #06080b;
        border: 1px solid #1e293b;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 15px;
    }
    .athlete-name {
        font-size: 16px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 10px;
    }
    .athlete-meta {
        font-size: 13px;
        color: #64748b;
    }
    .profile-pic-preview {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 3px solid var(--primary);
        object-fit: cover;
        margin-bottom: 15px;
    }
</style>

<div class="profile-header">
    <h1><i class="fas fa-user-gear"></i> My Profile</h1>
    <div style="font-size: 14px; opacity: 0.9;">
        Manage your account information and preferences
    </div>
</div>

<form method="POST" action="process_profile_update.php">
    <?= csrfTokenInput() ?>
    
    <!-- Basic Information -->
    <div class="section-card">
        <h2 class="section-title"><i class="fas fa-user"></i> Basic Information</h2>
        
        <?php if ($user_data['profile_pic']): ?>
            <img src="<?= htmlspecialchars($user_data['profile_pic']) ?>" alt="Profile Picture" class="profile-pic-preview">
        <?php endif; ?>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-input" 
                       value="<?= htmlspecialchars($user_data['first_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-input" 
                       value="<?= htmlspecialchars($user_data['last_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" 
                       value="<?= htmlspecialchars($user_data['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Role</label>
                <input type="text" class="form-input" 
                       value="<?= ucfirst($user_data['role']) ?>" disabled>
            </div>
        </div>
    </div>
    
    <!-- Athlete-Specific Info -->
    <?php if ($user_role === 'athlete'): ?>
    <div class="section-card">
        <h2 class="section-title"><i class="fas fa-hockey-puck"></i> Hockey Information</h2>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Position</label>
                <select name="position" class="form-input">
                    <option value="">Select Position</option>
                    <option value="forward" <?= $user_data['position'] === 'forward' ? 'selected' : '' ?>>Forward</option>
                    <option value="defense" <?= $user_data['position'] === 'defense' ? 'selected' : '' ?>>Defense</option>
                    <option value="goalie" <?= $user_data['position'] === 'goalie' ? 'selected' : '' ?>>Goalie</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Birth Date</label>
                <input type="date" name="birth_date" class="form-input" 
                       value="<?= $user_data['birth_date'] ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Primary Arena</label>
                <input type="text" name="primary_arena" class="form-input" 
                       value="<?= htmlspecialchars($user_data['primary_arena'] ?? '') ?>">
            </div>
        </div>
    </div>
    
    <!-- Physical Stats -->
    <div class="section-card">
        <h2 class="section-title"><i class="fas fa-ruler-vertical"></i> Physical Stats</h2>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Weight (lbs)</label>
                <input type="number" name="weight" class="form-input" 
                       value="<?= $user_data['weight'] ?? '' ?>" placeholder="0">
            </div>
            
            <div class="form-group">
                <label class="form-label">Height (cm)</label>
                <input type="number" name="height" class="form-input" 
                       value="<?= $user_data['height'] ?? '' ?>" placeholder="0">
            </div>
            
            <div class="form-group">
                <label class="form-label">Shooting Hand</label>
                <select name="shooting_hand" class="form-input">
                    <option value="">Select Hand</option>
                    <option value="left" <?= ($user_data['shooting_hand'] ?? '') === 'left' ? 'selected' : '' ?>>Left</option>
                    <option value="right" <?= ($user_data['shooting_hand'] ?? '') === 'right' ? 'selected' : '' ?>>Right</option>
                    <option value="ambidextrous" <?= ($user_data['shooting_hand'] ?? '') === 'ambidextrous' ? 'selected' : '' ?>>Ambidextrous</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Catching Hand (Goalies)</label>
                <select name="catching_hand" class="form-input">
                    <option value="">Select Hand</option>
                    <option value="regular" <?= ($user_data['catching_hand'] ?? '') === 'regular' ? 'selected' : '' ?>>Regular</option>
                    <option value="full_right" <?= ($user_data['catching_hand'] ?? '') === 'full_right' ? 'selected' : '' ?>>Full Right</option>
                </select>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Notification Preferences -->
    <div class="section-card">
        <h2 class="section-title"><i class="fas fa-bell"></i> Notification Preferences</h2>
        
        <div class="form-checkbox">
            <input type="checkbox" name="email_notifications" id="email_notif" 
                   value="1" <?= $user_data['email_notifications'] ? 'checked' : '' ?>>
            <label for="email_notif">Receive email notifications</label>
        </div>
    </div>
    
    <!-- Change Password -->
    <div class="section-card">
        <h2 class="section-title"><i class="fas fa-lock"></i> Change Password</h2>
        
        <div class="form-grid">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-input">
            </div>
            
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-input">
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-input">
            </div>
        </div>
        
        <p style="font-size: 13px; color: #64748b; margin-top: 10px;">
            Leave password fields blank to keep your current password
        </p>
    </div>
    
    <!-- Managed Athletes (for parents) -->
    <?php if (!empty($managed_athletes)): ?>
    <div class="section-card">
        <h2 class="section-title"><i class="fas fa-users"></i> Managed Athletes</h2>
        
        <?php foreach ($managed_athletes as $athlete): ?>
            <div class="athlete-card">
                <div class="athlete-name">
                    <?= htmlspecialchars($athlete['first_name'] . ' ' . $athlete['last_name']) ?>
                </div>
                <div class="athlete-meta">
                    Relationship: <?= htmlspecialchars($athlete['relationship']) ?> •
                    Can Book: <?= $athlete['can_book'] ? 'Yes' : 'No' ?> •
                    Can View Stats: <?= $athlete['can_view_stats'] ? 'Yes' : 'No' ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <p style="font-size: 13px; color: #64748b; margin-top: 15px;">
            To manage athlete relationships, please contact your coach or administrator
        </p>
    </div>
    <?php endif; ?>
    
    <button type="submit" class="btn-save">
        <i class="fas fa-save"></i> Save Changes
    </button>
</form>
