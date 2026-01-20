# Project Updates & Change Log

This file tracks all major changes and updates made to the Crash Hockey Training Platform project.

---

## January 20, 2026 - Repository Reorganization & Theme Update

**Primary Changes:**
- Repository restructured with `/deployment` folder
- Documentation consolidated
- Theme changed from orange to purple
- Email logging and debugging added

**Details:**
- Created `/deployment` folder for all server configuration files
- Renamed `nginx.conf` to `crashhockey.conf` for clarity
- Consolidated 4 documentation files into single `DEPLOYMENT.md`
- Changed primary color from orange (#ff4d00) to purple (#8b5cf6)
- Changed accent color from amber (#ff9d00) to silver (#c0c0c0)
- Added email logs viewer page for admins (`views/email_logs.php`)
- Fixed setup wizard schema path reference
- Added SMTP error display in setup wizard

---

## January 20, 2026 - Docker Compatibility Fixes

**Primary Changes:**
- Fixed NGINX configuration for linuxserver/docker-nginx container
- Corrected log paths for Docker environment
- Fixed PHP configuration syntax errors

**Details:**
- Changed FastCGI from Unix socket to TCP socket (127.0.0.1:9000)
- Updated all log paths from `/config/nginx/log/` to `/config/log/`
- Fixed PHP ini syntax (removed inline comments, changed # to ;, removed parentheses)
- Increased upload limit from 2GB to 10GB
- Updated PHP memory limit to 1024M
- Extended timeouts to 600 seconds (10 minutes)

---

## January 20, 2026 - Setup Wizard Database Initialization Fix

**Primary Changes:**
- Fixed table creation order issue in setup wizard
- Resolved skill_levels table not found error

**Details:**
- Setup wizard was querying tables before creation
- Added proper error handling and table existence checks
- Fixed schema execution order

---

## January 20, 2026 - Plan Categorization System

**Primary Changes:**
- Added categorization for workout, nutrition, and practice plans
- Admin UI to manage categories

**Details:**
- 3 new category tables created
- 18 default categories across 3 plan types
- Filter plans by category and coach creator
- Admin page to add/delete categories with usage tracking
- Coaches automatically assigned as plan creators

---

## January 20, 2026 - Parent/Manager Accounts & Age Groups

**Primary Changes:**
- New parent/manager role for managing multiple athletes
- Age groups and skill levels system
- HST tax system

**Details:**
- Parent dashboard with multi-athlete management
- Multi-athlete booking in single transaction
- 8 age groups (Mite U8 to Adult 18+)
- 5 skill levels (Beginner to Pro)
- Configurable HST tax rate (default 13%)
- Session filtering by age group and skill level

---

## January 20, 2026 - Package System & Accounting

**Primary Changes:**
- Credit-based package system
- Bundled session packages
- Comprehensive accounting area
- Receipt scanning with OCR

**Details:**
- Two package types: credit-based and bundled sessions
- Session types: group, private, semi-private
- Configurable athlete limits per session type
- Income reports by day/week/month/year
- Per-athlete itemized billing reports
- Accounts payable with receipt OCR (Nextcloud integration)
- Expense tracking and categorization
- Mileage tracking with Google Maps

---

## January 20, 2026 - Enhanced Dashboards & Notifications

**Primary Changes:**
- Role-specific dashboards
- Email and in-app notifications
- Video review system

**Details:**
- Athlete dashboard: stats, upcoming sessions, coach info
- Coach dashboard: athlete count, pending reviews, activity feed
- Parent dashboard: multi-athlete grid view
- Email notifications for plan assignments, workouts, notes
- In-app notification center
- Video upload and review system with coach feedback
- Public/private notes system

---

## Initial Implementation - Core Platform Features

**Primary Changes:**
- Complete training platform foundation
- Security infrastructure
- Drill library and practice plans
- Role-based permissions

**Details:**
- AES-256 database encryption
- CSRF protection and rate limiting
- 4-step setup wizard
- Drill library with categories and search
- Practice plan builder with IHS import
- 5 user roles with 30+ permissions
- Session booking and management
- Stripe payment integration
- SMTP email configuration
