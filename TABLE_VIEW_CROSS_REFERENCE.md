# Table-to-View Cross-Reference Matrix

This document shows which database tables are used in which view files.

## High-Traffic Tables (Used in 10+ Views)

### 👥 users (24 views)
- accounting.php
- accounts_payable.php
- admin_permissions.php
- athletes.php
- billing_dashboard.php
- drills.php
- home.php
- library_nutrition.php
- library_workouts.php
- manage_athletes.php
- mileage_tracker.php
- nutrition.php
- packages.php
- parent_home.php
- payment_history.php
- practice_plans.php
- profile.php
- reports_athlete.php
- reports_income.php
- schedule.php
- stats.php
- user_credits.php
- video.php
- workouts.php

### 📅 sessions (18 views)
- accounting.php
- admin_locations.php
- admin_packages.php
- admin_session_types.php
- athletes.php
- billing_dashboard.php
- home.php
- mileage_tracker.php ✅ FIXED
- parent_home.php
- payment_history.php
- refunds.php
- reports_athlete.php
- reports_income.php
- schedule.php
- session_detail.php
- session_history.php
- stats.php
- user_credits.php

### 💳 bookings (13 views)
- accounting.php
- athletes.php
- billing_dashboard.php
- home.php
- parent_home.php
- payment_history.php
- reports_athlete.php
- reports_income.php
- schedule.php
- session_detail.php
- session_history.php
- stats.php
- user_credits.php

## Medium-Traffic Tables (Used in 6-9 Views)

### 👨‍👩‍👧 managed_athletes (8 views)
- manage_athletes.php
- packages.php
- parent_home.php
- payment_history.php
- profile.php
- schedule.php
- session_history.php
- stats.php

### ⚡ skill_levels (7 views)
- admin_age_skill.php
- admin_packages.php
- create_session.php
- packages.php
- schedule.php
- session_history.php
- stats.php

### ⚙️ system_settings (7 views)
- create_session.php
- mileage_tracker.php
- packages.php
- reports_athlete.php
- reports_income.php
- schedule.php
- settings.php

### 📦 packages (6 views)
- accounting.php
- admin_packages.php
- packages.php
- payment_history.php
- reports_athlete.php
- reports_income.php

### 👶 age_groups (6 views)
- admin_age_skill.php
- admin_packages.php
- create_session.php
- packages.php
- schedule.php
- session_history.php

## Low-Traffic Tables (Used in 3-5 Views)

### 📋 practice_plans (5 views)
- create_session.php
- home.php
- ihs_import.php
- practice_plans.php
- session_detail.php

### 🏒 drills (4 views)
- drills.php
- ihs_import.php
- practice_plans.php
- session_detail.php

### 🏆 athlete_teams (4 views)
- athletes.php
- home.php
- parent_home.php
- stats.php

### 📂 expense_categories (4 views)
- accounting.php
- accounts_payable.php
- billing_dashboard.php
- expense_categories.php

### 💰 expenses (3 views)
- accounting.php
- accounts_payable.php
- billing_dashboard.php

### 🔔 notifications (3 views)
- home.php
- notifications.php
- parent_home.php

### 💵 refunds (3 views)
- billing_dashboard.php
- payment_history.php
- user_credits.php

### 🎁 user_credits (3 views)
- payment_history.php
- schedule.php
- user_credits.php

### 🎫 user_package_credits (3 views)
- admin_packages.php (COUNT query)
- packages.php
- Implicitly used via user_credits

## Specialized Tables (Used in 1-2 Views)

### Single-View Tables
- **admin_age_skill.php:** age_groups, skill_levels (admin only)
- **admin_discounts.php:** discount_codes
- **admin_permissions.php:** permissions, role_permissions, user_permissions
- **admin_plan_categories.php:** workout_plan_categories, nutrition_plan_categories, practice_plan_categories
- **admin_session_types.php:** session_types
- **athletes.php:** athlete_notes, athlete_stats
- **email_logs.php:** email_logs
- **expense_categories.php:** expense_categories
- **ihs_import.php:** drills (import tracking)
- **library_nutrition.php:** nutrition_templates, nutrition_plan_categories
- **library_sessions.php:** session_templates
- **library_workouts.php:** workout_templates, workout_plan_categories
- **mileage_tracker.php:** mileage_logs, mileage_stops
- **nutrition.php:** nutrition_plans
- **stats.php:** testing_results
- **video.php:** videos, video_notes
- **workouts.php:** user_workouts, user_workout_items, workouts

## Foreign Key Relationships Validated

### sessions table
- ✅ practice_plan_id → practice_plans.id
- ✅ age_group_id → age_groups.id
- ✅ skill_level_id → skill_levels.id

### bookings table
- ✅ user_id → users.id
- ✅ session_id → sessions.id
- ✅ booked_for_user_id → users.id (parent booking for athlete)
- ✅ package_id → packages.id

### managed_athletes table
- ✅ parent_id → users.id
- ✅ athlete_id → users.id

### packages table
- ✅ age_group_id → age_groups.id
- ✅ skill_level_id → skill_levels.id

### user_package_credits table
- ✅ user_id → users.id
- ✅ package_id → packages.id
- ✅ booking_id → bookings.id

### refunds table
- ✅ booking_id → bookings.id
- ✅ user_id → users.id
- ✅ refunded_by → users.id
- ✅ exchange_session_id → sessions.id

### user_credits table
- ✅ user_id → users.id
- ✅ refund_id → refunds.id

### expenses table
- ✅ category_id → expense_categories.id
- ✅ created_by → users.id

### mileage_logs table
- ✅ user_id → users.id
- ✅ athlete_id → users.id
- ✅ session_id → sessions.id

### All other foreign keys validated (30+ additional relationships)

## Summary Statistics

- **Total Tables:** 53
- **Total Views:** 39
- **Tables Used in Multiple Views:** 16 (30%)
- **Single-Purpose Tables:** 37 (70%)
- **Most Referenced Table:** users (24 views)
- **Foreign Key Relationships Validated:** 30+
- **Issues Found:** 1
- **Issues Fixed:** 1
- **Validation Status:** ✅ PASSED

---

**Last Updated:** 2024
**Validation Script:** validate_schema_references.php
**Schema Source:** deployment/schema.sql
