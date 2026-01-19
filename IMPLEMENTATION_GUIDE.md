# Crash Hockey - Feature Enhancement & Security Update

## Overview

This update implements comprehensive security enhancements, adds new coaching features (drill library and practice plan builder), implements a modular permissions system, and ensures responsive design across all pages.

## 🔒 Security Enhancements

### Database Setup
- **Secure Setup Page** (`setup.php`): First-time database configuration with AES-256 encryption
- **Credential Encryption**: Database passwords encrypted before storage in `.env` file
- **One-Time Setup**: Lock file prevents re-running setup after completion

### Authentication Security
- **CSRF Protection**: Tokens on all authentication forms
- **Rate Limiting**: 
  - Login: 5 attempts per 5 minutes
  - Registration: 3 attempts per 10 minutes
- **Session Security**: Session regeneration on login to prevent fixation attacks
- **Password Validation**: Minimum 8 characters with uppercase, lowercase, and number requirements
- **Email Validation**: Format checking on registration

### Security Headers
- Content Security Policy (CSP)
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection
- X-Content-Type-Options: nosniff
- Referrer-Policy: strict-origin-when-cross-origin

### Security Logging
- Complete audit trail in `security_logs` table
- Logs authentication events, permission changes, imports
- Tracks IP address and user agent

## 🏒 New Features

### 1. Drill Library
**Location**: Dashboard → Drill Library

Features:
- Create, edit, and delete hockey drills
- Categorize drills (admin-only category creation)
- Search and filter by:
  - Category
  - Skill level (beginner, intermediate, advanced)
  - Keywords in title/description
- Tag system for organization
- Duration tracking
- Equipment lists
- Coaching points
- Video URL support
- IHS import tracking

**Permissions**:
- `view_drills`: View drill library
- `create_drills`: Create and edit drills
- `delete_drills`: Delete drills
- `manage_drill_categories`: Create and manage categories (admin only)

### 2. Practice Plan Builder
**Location**: Dashboard → Practice Plans

Features:
- Create complete practice plans
- Add multiple drills to plans
- Set duration for each drill
- Reorder drills with up/down arrows
- Age group and focus area tags
- Public/private visibility
- **Shareable Links**: Generate unique URLs to share plans
- Import from IHS Hockey format

**Permissions**:
- `view_practice_plans`: View practice plans
- `create_practice_plans`: Create practice plans
- `share_practice_plans`: Generate shareable links
- `delete_practice_plans`: Delete practice plans

### 3. IHS Import
**Location**: Dashboard → Coach Management → IHS Import

Import drills and practice plans from IHS Hockey JSON format.

Features:
- Import individual drills or complete practice plans
- Preview before import
- Auto-categorization option
- Duplicate detection (skip or overwrite)
- Create missing drills when importing plans
- Import history tracking

**Permissions**:
- `import_from_ihs`: Access IHS import interface

**Sample JSON Format**:
```json
{
  "drills": [
    {
      "title": "2-on-1 Rush Drill",
      "description": "Offensive rush focusing on decision making",
      "duration": 15,
      "skill_level": "intermediate",
      "equipment": "Cones, pucks",
      "coaching_points": "Keep head up, support puck carrier"
    }
  ]
}
```

### 4. Permissions Management
**Location**: Dashboard → System Admin → Permissions (Admin Only)

Modular permission system with 30+ granular permissions across 7 categories:
- General (dashboard, stats)
- Schedule (booking, cancellations)
- Training (workouts, assignments)
- Nutrition (plans, assignments)
- Media (videos, uploads)
- Drills (library, categories)
- Practice (plans, sharing)
- Integration (IHS imports)
- Management (athletes, notes)
- Admin (system settings, roles)

**Two-Level System**:
1. **Role Permissions**: Default permissions for each role
2. **User Overrides**: Individual permission grants/revokes

**Roles**:
- **Athlete**: View own stats, book sessions, view assignments
- **Coach**: All athlete permissions + create workouts, nutrition plans, manage athletes
- **Coach+**: All coach permissions + session management, IHS imports, extended media access
- **Admin**: All permissions

## 📱 Responsive Design

### Dashboard
- Mobile menu toggle button (hamburger icon)
- Sidebar collapses on mobile (< 768px)
- Overlay when menu is open
- Touch-friendly navigation

### Breakpoints
- **Desktop**: 1024px+
- **Tablet**: 768px - 1023px
- **Mobile**: < 768px

### Design Consistency
- Orange accent color: `#ff4d00`
- Dark theme: `#06080b` background, `#0d1117` cards
- Consistent spacing and typography
- Mobile-optimized forms and buttons

## 🗄️ Database Schema

### New Tables

1. **drills** - Hockey drill library
2. **drill_categories** - Drill organization
3. **drill_tags** - Tag system for drills
4. **practice_plans** - Practice plan definitions
5. **practice_plan_drills** - Drills in each plan
6. **practice_plan_shares** - Share link tracking
7. **permissions** - Available permissions
8. **role_permissions** - Default role permissions
9. **user_permissions** - User-specific overrides
10. **security_logs** - Security event audit trail

### Modified Tables
- **users**: Added `coach_plus` to role enum

## 🚀 Getting Started

### Initial Setup

1. **Database Setup** (First Time Only):
   ```
   Navigate to: https://yourdomain.com/setup.php
   ```
   - Enter database credentials
   - Create encryption key (32+ characters)
   - Initialize database tables
   - **Important**: Store encryption key securely!

2. **Create Admin Account**:
   ```
   Navigate to: https://yourdomain.com/register.php
   ```
   - Register first account
   - Manually set role to 'admin' in database:
   ```sql
   UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
   ```

3. **Configure Permissions** (Optional):
   - Login as admin
   - Go to Dashboard → System Admin → Permissions
   - Customize role permissions as needed

### Environment File

After setup, `crashhockey.env` will contain:
```env
DB_HOST=localhost
DB_NAME=crashhockey
DB_USER=your_user
DB_PASS_ENCRYPTED=<encrypted_password>
ENCRYPTION_KEY_HASH=<key_hash>
```

**Security**: Keep this file secure and outside web root if possible.

## 📋 Usage Examples

### Creating a Drill

1. Navigate to Dashboard → Drill Library
2. Click "Create Drill"
3. Fill in drill details:
   - Title (required)
   - Category
   - Description
   - Duration
   - Skill level
   - Equipment needed
   - Coaching points
4. Click "Save Drill"

### Building a Practice Plan

1. Navigate to Dashboard → Practice Plans
2. Click "Create Plan"
3. Fill in plan details:
   - Title (required)
   - Description
   - Total duration
   - Age group
   - Focus area
4. Add drills:
   - Search for existing drills
   - Click "Add to Plan"
   - Set duration for each drill
   - Reorder with arrows
5. Click "Save Plan"

### Sharing a Practice Plan

1. View practice plan
2. Click "Share" button
3. Click "Generate Share Link"
4. Copy and share URL
5. Recipients can view plan without login

### Importing from IHS

1. Navigate to Dashboard → IHS Import
2. Paste JSON data
3. Click "Preview" to verify
4. Select import options
5. Click "Import"

## 🔧 Configuration

### Security Settings

**Rate Limiting** (in `security.php`):
```php
isRateLimited('login', 5, 300)  // 5 attempts per 5 minutes
isRateLimited('register', 3, 600)  // 3 attempts per 10 minutes
```

**Session Settings** (recommended in `php.ini`):
```ini
session.cookie_httponly = 1
session.cookie_secure = 1  # If using HTTPS
session.use_strict_mode = 1
```

### HTTPS (Recommended)

Uncomment in `security.php` when using HTTPS:
```php
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
```

Update CSP to require HTTPS:
```php
header("upgrade-insecure-requests");
```

## 🐛 Troubleshooting

### Database Connection Failed
- Verify credentials in `crashhockey.env`
- Check MySQL service is running
- Ensure database exists
- Verify user has proper permissions

### CSRF Token Validation Failed
- Session may have expired - refresh page
- Clear browser cookies
- Check session settings in `php.ini`

### Rate Limit Exceeded
- Wait 5-10 minutes before retrying
- Clear session data if persistent
- Check `security_logs` table for details

### Permission Denied Errors
- Verify user role in database
- Check role permissions in admin panel
- Clear any user-specific overrides

### Import Fails
- Verify JSON format is correct
- Check for required fields (title)
- Review error message details
- Ensure drills exist for practice plans

## 📊 Monitoring

### Security Logs

Query security events:
```sql
SELECT * FROM security_logs 
WHERE event_type = 'login_failed' 
ORDER BY created_at DESC 
LIMIT 50;
```

Monitor rate limiting:
```sql
SELECT * FROM security_logs 
WHERE event_type = 'rate_limit_exceeded' 
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

### Import History

View recent IHS imports:
```sql
SELECT type, title, created_at 
FROM (
    SELECT 'drill' as type, title, created_at 
    FROM drills WHERE imported_from_ihs = 1
    UNION ALL
    SELECT 'plan' as type, title, created_at 
    FROM practice_plans WHERE imported_from_ihs = 1
) AS imports
ORDER BY created_at DESC
LIMIT 20;
```

## 🔐 Security Best Practices

1. **Encryption Key**: Store securely, never commit to version control
2. **HTTPS**: Use SSL/TLS in production
3. **Backups**: Regular database backups
4. **Updates**: Keep PHP and MySQL updated
5. **Permissions**: Review user permissions regularly
6. **Logs**: Monitor security logs for suspicious activity
7. **Rate Limits**: Adjust based on legitimate usage patterns

## 📞 Support

For issues or questions:
1. Check this documentation
2. Review error messages in browser console
3. Check `security_logs` table for details
4. Verify file permissions are correct

## 🎯 Roadmap

Future enhancements:
- [ ] XML format support for IHS imports
- [ ] AJAX-based drill and plan editing
- [ ] Drill diagram designer with canvas
- [ ] Practice plan templates
- [ ] Email notifications for shared plans
- [ ] Mobile app integration
- [ ] Video upload and hosting
- [ ] Advanced analytics and reporting

## 📝 License

Copyright © 2026 Crash Hockey Development. All Rights Reserved.

---

**Version**: 2.0.0  
**Last Updated**: January 19, 2026  
**Author**: Crash Media IT
