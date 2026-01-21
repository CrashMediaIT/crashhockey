# Crash Hockey - Navigation Restructure & Health Coach Role Implementation

## Overview
This document outlines the comprehensive restructuring of the Crash Hockey coaching management system, including the addition of new roles, complete navigation overhaul, and database schema updates.

## Changes Summary

### 1. New Roles Added
The system now supports the following user roles:
- **athlete** - Athletes using the platform
- **coach** - Regular coaches
- **admin** - System administrators
- **parent** - Parents/guardians of athletes
- **health_coach** - Specialized health and fitness coaches
- **team_coach** - Coaches assigned to specific teams

### 2. Database Schema
**File**: `database_schema.sql`

A complete database schema has been created with 40+ tables including:
- User management with expanded roles
- Parent-athlete relationships
- Coach-athlete assignments
- Teams and team rosters
- Sessions, bookings, and packages
- Drills and practice plans
- Video management
- Workout and nutrition plans
- Performance stats and goals
- Mileage tracking
- Expenses and transactions
- System notifications
- Evaluation framework
- Audit logging
- And more...

**Action Required**: Execute the schema SQL file to create/update the database:
```bash
mysql -u your_username -p crashhockey < database_schema.sql
```

### 3. Index Page Fallback
**Files Modified**: `index.php`, `db_config.php`
**File Created**: `index_default.php`

The index page now gracefully handles database connection failures:
- If database is not connected, displays the marketing page (index_default.php)
- If database is connected and user is logged in, redirects to dashboard
- If database is connected but user is not logged in, shows marketing page
- No more fatal errors when database is not configured

### 4. Setup Wizard
**File Created**: `setup.php`

A 4-step setup wizard for initial system configuration:
1. **Database Configuration** - Set up database connection
2. **Admin User Creation** - Create the first admin account
3. **SMTP Configuration** - Configure email settings
4. **Finalization** - Complete setup and redirect to login

The setup wizard:
- Creates a `.setup_complete` file when finished
- Automatically redirects to login if setup is already complete
- Can be forced to run again with `?force` parameter
- Properly redirects after SMTP configuration (fixing the reported issue)

### 5. Navigation Restructure
**File Modified**: `dashboard.php`

Complete overhaul of the navigation system with hierarchical structure:

#### Main Menu (All Users)
- **Home** - Role-specific dashboard
- **Performance Stats** - Athlete stats and goals
- **Sessions** (submenu)
  - Upcoming Sessions
  - Booking
- **Video** (submenu)
  - Drill Review
  - Coaches Reviews
- **Health** (submenu)
  - Strength & Conditioning
  - Nutrition

#### Team Section (Team Coaches Only)
- **Roster** - Team roster management

#### Coaches Corner (Coaches, Health Coaches, Admins)
- **Drills** (submenu)
  - Library
  - Create a Drill
  - Import a Drill
- **Practice Plans** (submenu)
  - Library
  - Create a Practice
- **Roster** - Athlete management
- **Travel** (submenu)
  - Mileage

#### Accounting & Reports (Admins Only)
- **Accounting Dashboard**
- **Billing Dashboard**
- **Reports**
- **Schedules**
- **Credits and Refunds**
- **Expenses**
- **Products** (Sessions, Packages, Discounts)

#### HR (Admins Only)
- **Termination**

#### Administration (Admins Only)
- **All Users**
- **Categories**
- **Eval Framework**
- **System Notification**
- **Audit Log**
- **Cron Jobs**
- **System Tools** (Settings, Theme, Database)

### 6. View Files Created
**Directory**: `views/`

Created 33 view files with consistent styling:
- Main menu views (8 files)
- Team & coaches views (8 files)
- Accounting & reports views (7 files)
- HR & administration views (8 files)
- Supporting views (2 files)

All views feature:
- Dark theme with CSS variables
- Consistent input height (45px)
- Consistent button styling (45px)
- Inter font family throughout
- Font Awesome 6 icons
- Role-based content
- Professional layouts

### 7. Parent Role Features
Parents can:
- View their athlete's information
- Switch between multiple athletes using a dropdown
- Access all athlete-specific pages for their children

The parent dropdown appears in the top-right corner of the dashboard when logged in as a parent.

### 8. UI/UX Consistency
All elements now follow these standards:
- **Input boxes**: 45px height, Inter 14px font, dark background (#0d1116)
- **Buttons**: 45px height, Inter 14px bold font, consistent colors
- **Colors**: 
  - Primary: #ff4d00 (orange)
  - Background: #06080b (dark)
  - Card Background: #0d1116
  - Border: #1e293b
  - Text: #94a3b8

### 9. Collapsible Submenus
Navigation items with submenus can be expanded/collapsed:
- Click on menu item to toggle
- Smooth animations
- Chevron rotation indicator
- Auto-expands when viewing a page within the submenu

## File Structure

```
/crashhockey/
├── index.php                    # Main entry point (with fallback logic)
├── index_default.php            # Marketing page fallback
├── dashboard.php                # Main dashboard with navigation
├── setup.php                    # Setup wizard
├── database_schema.sql          # Complete database schema
├── db_config.php               # Database connection (with error handling)
├── login.php                    # Login page
├── register.php                 # Registration page
├── views/
│   ├── home.php                 # Dashboard home
│   ├── stats.php                # Performance stats
│   ├── sessions_upcoming.php    # Upcoming sessions
│   ├── sessions_booking.php     # Session booking
│   ├── video_drill_review.php   # Drill video reviews
│   ├── video_coach_reviews.php  # Coach reviews with upload
│   ├── health_workouts.php      # Workout plans
│   ├── health_nutrition.php     # Nutrition plans
│   ├── team_roster.php          # Team roster
│   ├── drills_library.php       # Drill library
│   ├── drills_create.php        # Create drill
│   ├── drills_import.php        # Import drill from IHS
│   ├── practice_library.php     # Practice plan library
│   ├── practice_create.php      # Create practice plan
│   ├── coach_roster.php         # Coach athlete roster
│   ├── travel_mileage.php       # Mileage tracking
│   ├── accounting_*.php         # 7 accounting/reports views
│   ├── hr_termination.php       # HR termination
│   ├── admin_*.php              # 7 administration views
│   ├── profile.php              # User profile
│   └── settings.php             # Global settings
└── ... (other files)
```

## Installation & Setup

1. **Upload Files**: Upload all files to your server
2. **Create Database**: Create a MySQL database (e.g., `crashhockey`)
3. **Run Setup Wizard**: Navigate to `https://yourdomain.com/setup.php`
4. **Follow Steps**:
   - Step 1: Enter database credentials
   - Step 2: Create admin account
   - Step 3: Configure SMTP settings
   - Step 4: Complete setup
5. **Login**: Access `https://yourdomain.com/login.php`

## Migration Notes

### For Existing Installations
If you're updating an existing installation:

1. **Backup Database**: Always backup your database first
2. **Review Schema Changes**: Compare the new schema with your existing one
3. **Update Tables**: Run ALTER TABLE statements as needed to add new columns
4. **Add New Tables**: Create the new tables from the schema
5. **Update User Roles**: Update any users who should have the new roles
6. **Test Thoroughly**: Test all functionality after migration

### Recommended Migration Path
```sql
-- Add new roles to existing users table
ALTER TABLE users MODIFY COLUMN role ENUM('athlete', 'coach', 'admin', 'parent', 'health_coach', 'team_coach') DEFAULT 'athlete';

-- Then create new tables from database_schema.sql
-- Then update any existing data to match new structure
```

## Security Considerations

1. **Setup.php**: After completing setup, consider:
   - Deleting `setup.php` for security
   - Or restricting access via .htaccess
   - Or checking IP whitelist

2. **Database Credentials**: Stored in `crashhockey.env`
   - Ensure this file is not web-accessible
   - Add to .gitignore if using version control

3. **Role-Based Access**: All views check user roles via `$user_role` variable
   - Admin-only pages check `$isAdmin`
   - Coach pages check `$isAnyCoach`
   - Implement proper session validation

4. **SQL Injection**: All database queries should use PDO prepared statements

5. **XSS Protection**: Use `htmlspecialchars()` when outputting user data

## Known Issues & Limitations

1. **View Files**: All view files are placeholder templates and need to be connected to actual database queries and business logic

2. **AJAX Functionality**: Parent athlete switching and other dynamic features need AJAX endpoints implemented

3. **File Uploads**: Video and image upload functionality needs to be implemented with proper storage and validation

4. **Payment Processing**: Payment gateway integration needed for session bookings and packages

5. **Email System**: SMTP configuration is saved but actual email sending needs mailer implementation

## Next Steps

1. **Connect Views to Database**: Implement actual queries in each view file
2. **Implement Business Logic**: Add processing files for forms and actions
3. **Add Validation**: Client-side and server-side validation for all forms
4. **Implement AJAX**: For dynamic updates without page refresh
5. **File Upload**: Implement secure file upload for videos, images, receipts
6. **Payment Gateway**: Integrate Stripe/PayPal for transactions
7. **Email Notifications**: Implement email sending using saved SMTP settings
8. **Testing**: Comprehensive testing of all features
9. **Documentation**: Document all API endpoints and functions

## Support & Maintenance

For questions or issues:
1. Check this documentation
2. Review the code comments
3. Test in a development environment first
4. Keep backups of all changes

## Version History

- **v1.0** (January 2026) - Initial navigation restructure and health coach role implementation

---

**Last Updated**: January 21, 2026
