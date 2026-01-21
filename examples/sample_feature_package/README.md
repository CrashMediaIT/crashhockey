# Sample Feature Package

This is a demonstration package for the Intelligent Feature Import System.

## Package Contents

- **Feature Name**: Enhanced User System
- **Version**: 2.0.0
- **Requires**: Version 1.0.0

## What This Package Does

1. **Adds Database Columns**:
   - `subscription_tier` VARCHAR(50) - User's subscription level
   - `subscription_expires` TIMESTAMP - Subscription expiration date
   - `last_login` TIMESTAMP - Last login tracking

2. **Creates New View**:
   - `views/admin_user_subscriptions.php` - Admin interface for managing subscriptions

3. **Creates Directory**:
   - `uploads/subscription_receipts/` - Storage for subscription receipts

4. **Adds Navigation**:
   - Automatically adds "User Subscriptions" link to admin dashboard

## How to Use

### 1. Create the ZIP Package

```bash
cd examples/sample_feature_package
zip -r ../sample_feature.zip manifest.json files/
```

### 2. Import via Web Interface

1. Log in as admin
2. Navigate to Admin > Feature Import
3. Upload `sample_feature.zip`
4. Review pre-import analysis
5. Click "Import Feature"
6. Monitor progress
7. View "User Subscriptions" in navigation

### 3. Verify Installation

Check that:
- New columns exist in `users` table
- New view accessible at `?page=admin_user_subscriptions`
- Directory `uploads/subscription_receipts/` created
- Entry added to `feature_versions` table

## What Happens During Import

1. **Version Check**: Validates version 2.0.0 is higher than current
2. **Pre-Analysis**: 
   - Tables affected: 1 (users)
   - Columns affected: 3
   - Files affected: 1
3. **Backup**: Creates backup of modified files
4. **Database Migration**: Adds 3 columns to users table
5. **File Processing**: Copies new view file
6. **Directory Creation**: Creates subscription_receipts directory
7. **Navigation Update**: Adds menu item to dashboard.php
8. **Version Recording**: Records installation in feature_versions
9. **Schema Update**: Updates deployment/schema.sql

## Expected Log Output

```
Starting feature import...
Extracting ZIP package...
Loading manifest...
Feature: Enhanced User System v2.0.0
Checking version compatibility...
New feature installation
Analyzing database migrations...
Analysis: 1 tables, 3 columns, 1 files
Running system validation...
System validation passed
Creating backup...
Backup created: backup_1234567890
Running intelligent database migrations...
Column added: users.subscription_tier
Column added: users.subscription_expires
Column added: users.last_login
Creating directories...
Directory created: uploads/subscription_receipts/
Processing files...
File created: views/admin_user_subscriptions.php
Updating navigation...
Route added: admin_user_subscriptions
Updating schema.sql...
Feature version recorded: Enhanced User System v2.0.0
Feature imported successfully!
```

## Upgrading to Version 3.0.0

To create an upgrade package that renames a column:

```json
{
  "name": "Enhanced User System",
  "version": "3.0.0",
  "requires_version": "2.0.0",
  
  "database_migrations": [
    {
      "type": "rename_column",
      "table": "users",
      "old_name": "subscription_tier",
      "new_name": "membership_level"
    }
  ]
}
```

The system will automatically:
- Rename the column in the database
- Update schema.sql
- Update all PHP code references
- Track the migration

## Testing

Run these checks after import:

```sql
-- Check columns were added
DESCRIBE users;

-- Check feature was recorded
SELECT * FROM feature_versions WHERE feature_name = 'Enhanced User System';

-- Verify data structure
SHOW CREATE TABLE users;
```

```bash
# Check files were created
ls -la views/admin_user_subscriptions.php
ls -la uploads/subscription_receipts/

# Check navigation was updated
grep -n "admin_user_subscriptions" dashboard.php
```

## Rollback

If import fails, the system automatically:
- Rolls back database changes
- Restores files from backup
- Cleans up partial changes
- Reports specific error

Manual rollback if needed:
```bash
# Find backup
ls -la tmp/feature_backups/

# View backup contents
ls -la tmp/feature_backups/backup_1234567890/
```

## Notes

- This is a sample package for testing only
- Real packages should include proper error handling
- Always test on development environment first
- Create database backups before major changes
