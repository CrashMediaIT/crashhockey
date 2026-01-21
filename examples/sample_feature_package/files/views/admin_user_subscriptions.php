<?php
/**
 * User Subscriptions Management
 * Sample feature view demonstrating the enhanced import system
 */

require_once __DIR__ . '/../security.php';

if ($user_role !== 'admin') {
    header('Location: dashboard.php?page=home&error=' . urlencode('Admin privileges required.'));
    exit;
}
?>

<div style="padding: 20px; max-width: 1200px; margin: 0 auto;">
    <h1 style="color: #fff; margin-bottom: 20px;">
        <i class="fas fa-crown"></i> User Subscriptions
    </h1>
    
    <div style="background: #0d1117; border: 1px solid #1e293b; border-radius: 8px; padding: 20px;">
        <p style="color: #94a3b8;">
            This is a sample feature demonstrating the intelligent feature import system.
            The system automatically added new columns to the users table:
        </p>
        
        <ul style="color: #94a3b8; margin: 15px 0;">
            <li><code>subscription_tier</code> - User's subscription level</li>
            <li><code>subscription_expires</code> - Expiration date</li>
            <li><code>last_login</code> - Last login timestamp</li>
        </ul>
        
        <p style="color: #94a3b8;">
            All migrations were tracked in the <code>feature_versions</code> table,
            and this navigation item was automatically added to the dashboard.
        </p>
        
        <div style="margin-top: 20px; padding: 15px; background: rgba(112, 0, 164, 0.1); border: 1px solid #7000a4; border-radius: 6px;">
            <strong style="color: #7000a4;">✓ Feature successfully imported!</strong>
        </div>
    </div>
</div>
