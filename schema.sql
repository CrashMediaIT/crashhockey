-- =========================================================
-- CRASH HOCKEY DATABASE SCHEMA
-- Complete database structure for the application
-- =========================================================

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('athlete', 'coach', 'coach_plus', 'admin') DEFAULT 'athlete',
  `position` VARCHAR(50) DEFAULT NULL,
  `birth_date` DATE DEFAULT NULL,
  `primary_arena` VARCHAR(255) DEFAULT NULL,
  `profile_pic` VARCHAR(255) DEFAULT NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `verification_code` VARCHAR(10) DEFAULT NULL,
  `force_pass_change` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Locations
CREATE TABLE IF NOT EXISTS `locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session Types
CREATE TABLE IF NOT EXISTS `session_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_type` VARCHAR(100) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `session_date` DATE NOT NULL,
  `session_time` TIME NOT NULL,
  `session_plan` TEXT,
  `arena` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `price` DECIMAL(10,2) DEFAULT 0.00,
  `max_capacity` INT DEFAULT 20,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_date` (`session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bookings
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `session_id` INT NOT NULL,
  `stripe_session_id` VARCHAR(255) DEFAULT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL,
  `original_price` DECIMAL(10,2) NOT NULL,
  `discount_code` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_session` (`user_id`, `session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Discount Codes
CREATE TABLE IF NOT EXISTS `discount_codes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('percent', 'fixed') NOT NULL,
  `value` DECIMAL(10,2) NOT NULL,
  `usage_limit` INT DEFAULT NULL,
  `times_used` INT DEFAULT 0,
  `expiry_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exercises
CREATE TABLE IF NOT EXISTS `exercises` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `target_area` VARCHAR(100) DEFAULT NULL,
  `video_link` VARCHAR(500) DEFAULT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workout Templates
CREATE TABLE IF NOT EXISTS `workout_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workout Template Items
CREATE TABLE IF NOT EXISTS `workout_template_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `template_id` INT NOT NULL,
  `exercise_id` INT NOT NULL,
  `sets` INT DEFAULT 3,
  `reps` INT DEFAULT 10,
  `order_index` INT DEFAULT 0,
  FOREIGN KEY (`template_id`) REFERENCES `workout_templates`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`exercise_id`) REFERENCES `exercises`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Workouts
CREATE TABLE IF NOT EXISTS `user_workouts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `coach_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `assigned_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Workout Items
CREATE TABLE IF NOT EXISTS `user_workout_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_workout_id` INT NOT NULL,
  `exercise_id` INT NOT NULL,
  `sets` INT DEFAULT 3,
  `reps` INT DEFAULT 10,
  `weight` DECIMAL(10,2) DEFAULT NULL,
  `is_completed` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`user_workout_id`) REFERENCES `user_workouts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`exercise_id`) REFERENCES `exercises`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foods
CREATE TABLE IF NOT EXISTS `foods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `type` VARCHAR(100) DEFAULT NULL,
  `recipe` TEXT,
  `calories` INT DEFAULT NULL,
  `protein` DECIMAL(10,2) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nutrition Templates
CREATE TABLE IF NOT EXISTS `nutrition_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nutrition Template Items
CREATE TABLE IF NOT EXISTS `nutrition_template_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `template_id` INT NOT NULL,
  `food_id` INT NOT NULL,
  `meal_type` VARCHAR(50) DEFAULT NULL,
  `default_portion` VARCHAR(100) DEFAULT NULL,
  FOREIGN KEY (`template_id`) REFERENCES `nutrition_templates`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`food_id`) REFERENCES `foods`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session Templates
CREATE TABLE IF NOT EXISTS `session_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `session_type` VARCHAR(100) DEFAULT NULL,
  `age_group` VARCHAR(50) DEFAULT NULL,
  `description` TEXT,
  `session_plan` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Videos
CREATE TABLE IF NOT EXISTS `videos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `uploader_id` INT NOT NULL,
  `assigned_to_user_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `video_type` VARCHAR(50) DEFAULT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`uploader_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_assigned` (`assigned_to_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Athlete Teams
CREATE TABLE IF NOT EXISTS `athlete_teams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `team_name` VARCHAR(255) NOT NULL,
  `season_year` VARCHAR(20) NOT NULL,
  `season_type` VARCHAR(50) DEFAULT NULL,
  `season` VARCHAR(100) DEFAULT NULL,
  `is_current` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_current` (`user_id`, `is_current`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Athlete Stats
CREATE TABLE IF NOT EXISTS `athlete_stats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `team_id` INT NOT NULL,
  `games_played` INT DEFAULT 0,
  `goals` INT DEFAULT 0,
  `assists` INT DEFAULT 0,
  `points` INT DEFAULT 0,
  `penalties` INT DEFAULT 0,
  `penalty_minutes` INT DEFAULT 0,
  `shots` INT DEFAULT 0,
  `shooting_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `plus_minus` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`team_id`) REFERENCES `athlete_teams`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_team` (`user_id`, `team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Testing Results
CREATE TABLE IF NOT EXISTS `testing_results` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `test_name` VARCHAR(255) NOT NULL,
  `weight` DECIMAL(10,2) DEFAULT NULL,
  `reps` INT DEFAULT NULL,
  `sets` INT DEFAULT NULL,
  `time_result` VARCHAR(50) DEFAULT NULL,
  `test_date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_date` (`user_id`, `test_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Athlete Notes
CREATE TABLE IF NOT EXISTS `athlete_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `coach_id` INT NOT NULL,
  `note_content` TEXT NOT NULL,
  `is_private` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`coach_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workouts (Generic)
CREATE TABLE IF NOT EXISTS `workouts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `coach_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `link` VARCHAR(500) DEFAULT NULL,
  `is_completed` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nutrition Plans
CREATE TABLE IF NOT EXISTS `nutrition_plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `coach_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email Logs
CREATE TABLE IF NOT EXISTS `email_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `recipient` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(500) NOT NULL,
  `log_data` TEXT,
  `template_type` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('sent', 'failed') DEFAULT 'sent',
  `error_message` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_recipient` (`recipient`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Security Logs
CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_type` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `user_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- NEW TABLES FOR DRILL LIBRARY & PRACTICE PLANS
-- =========================================================

-- Drill Categories
CREATE TABLE IF NOT EXISTS `drill_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drills
CREATE TABLE IF NOT EXISTS `drills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `category_id` INT DEFAULT NULL,
  `diagram_data` TEXT,
  `duration_minutes` INT DEFAULT NULL,
  `skill_level` ENUM('beginner', 'intermediate', 'advanced', 'all') DEFAULT 'all',
  `age_group` VARCHAR(50) DEFAULT NULL,
  `equipment_needed` TEXT,
  `coaching_points` TEXT,
  `video_url` VARCHAR(500) DEFAULT NULL,
  `imported_from_ihs` TINYINT(1) DEFAULT 0,
  `ihs_drill_id` VARCHAR(100) DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `drill_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_category` (`category_id`),
  INDEX `idx_skill` (`skill_level`),
  INDEX `idx_ihs` (`imported_from_ihs`, `ihs_drill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drill Tags
CREATE TABLE IF NOT EXISTS `drill_tags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `drill_id` INT NOT NULL,
  `tag` VARCHAR(50) NOT NULL,
  FOREIGN KEY (`drill_id`) REFERENCES `drills`(`id`) ON DELETE CASCADE,
  INDEX `idx_drill` (`drill_id`),
  INDEX `idx_tag` (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Practice Plans
CREATE TABLE IF NOT EXISTS `practice_plans` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `total_duration` INT DEFAULT 60,
  `age_group` VARCHAR(50) DEFAULT NULL,
  `focus_area` VARCHAR(100) DEFAULT NULL,
  `share_token` VARCHAR(64) UNIQUE DEFAULT NULL,
  `is_public` TINYINT(1) DEFAULT 0,
  `imported_from_ihs` TINYINT(1) DEFAULT 0,
  `ihs_plan_id` VARCHAR(100) DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_share_token` (`share_token`),
  INDEX `idx_public` (`is_public`),
  INDEX `idx_ihs` (`imported_from_ihs`, `ihs_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Practice Plan Drills
CREATE TABLE IF NOT EXISTS `practice_plan_drills` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_id` INT NOT NULL,
  `drill_id` INT NOT NULL,
  `order_index` INT NOT NULL DEFAULT 0,
  `duration_minutes` INT DEFAULT NULL,
  `notes` TEXT,
  FOREIGN KEY (`plan_id`) REFERENCES `practice_plans`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`drill_id`) REFERENCES `drills`(`id`) ON DELETE CASCADE,
  INDEX `idx_plan_order` (`plan_id`, `order_index`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Practice Plan Shares (Track who accessed shared plans)
CREATE TABLE IF NOT EXISTS `practice_plan_shares` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_id` INT NOT NULL,
  `accessed_by_ip` VARCHAR(45) DEFAULT NULL,
  `accessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`plan_id`) REFERENCES `practice_plans`(`id`) ON DELETE CASCADE,
  INDEX `idx_plan` (`plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- PERMISSIONS SYSTEM
-- =========================================================

-- Permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `permission_key` VARCHAR(100) NOT NULL UNIQUE,
  `permission_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(100) DEFAULT 'general',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_key` (`permission_key`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role Permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role` ENUM('athlete', 'coach', 'coach_plus', 'admin') NOT NULL,
  `permission_id` INT NOT NULL,
  `granted` TINYINT(1) DEFAULT 1,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_role_permission` (`role`, `permission_id`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Permissions (Override role permissions for specific users)
CREATE TABLE IF NOT EXISTS `user_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `permission_id` INT NOT NULL,
  `granted` TINYINT(1) DEFAULT 1,
  `granted_by` INT DEFAULT NULL,
  `granted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`granted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  UNIQUE KEY `unique_user_permission` (`user_id`, `permission_id`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- INSERT DEFAULT PERMISSIONS
-- =========================================================

INSERT IGNORE INTO `permissions` (`permission_key`, `permission_name`, `description`, `category`) VALUES
('view_dashboard', 'View Dashboard', 'Access main dashboard', 'general'),
('view_stats', 'View Statistics', 'View performance statistics', 'stats'),
('edit_own_stats', 'Edit Own Statistics', 'Edit personal statistics', 'stats'),
('view_schedule', 'View Schedule', 'View and book sessions', 'schedule'),
('book_sessions', 'Book Sessions', 'Book training sessions', 'schedule'),
('cancel_bookings', 'Cancel Bookings', 'Cancel own bookings', 'schedule'),
('view_workouts', 'View Workouts', 'View assigned workouts', 'training'),
('create_workouts', 'Create Workouts', 'Create workout plans', 'training'),
('assign_workouts', 'Assign Workouts', 'Assign workouts to athletes', 'training'),
('view_nutrition', 'View Nutrition', 'View nutrition plans', 'nutrition'),
('create_nutrition', 'Create Nutrition Plans', 'Create nutrition plans', 'nutrition'),
('assign_nutrition', 'Assign Nutrition Plans', 'Assign nutrition plans to athletes', 'nutrition'),
('view_videos', 'View Videos', 'Access video library', 'media'),
('upload_videos', 'Upload Videos', 'Upload videos', 'media'),
('delete_videos', 'Delete Videos', 'Delete videos', 'media'),
('view_drills', 'View Drills', 'Access drill library', 'drills'),
('create_drills', 'Create Drills', 'Create and edit drills', 'drills'),
('delete_drills', 'Delete Drills', 'Delete drills', 'drills'),
('manage_drill_categories', 'Manage Drill Categories', 'Create and manage drill categories', 'drills'),
('view_practice_plans', 'View Practice Plans', 'View practice plans', 'practice'),
('create_practice_plans', 'Create Practice Plans', 'Create practice plans', 'practice'),
('share_practice_plans', 'Share Practice Plans', 'Generate shareable links for practice plans', 'practice'),
('delete_practice_plans', 'Delete Practice Plans', 'Delete practice plans', 'practice'),
('import_from_ihs', 'Import from IHS', 'Import drills and plans from IHS Hockey', 'integration'),
('manage_athletes', 'Manage Athletes', 'View and manage athlete roster', 'management'),
('create_athletes', 'Create Athlete Accounts', 'Create new athlete accounts', 'management'),
('edit_athlete_profiles', 'Edit Athlete Profiles', 'Edit athlete information', 'management'),
('view_athlete_notes', 'View Athlete Notes', 'View coaching notes', 'management'),
('create_athlete_notes', 'Create Athlete Notes', 'Add coaching notes', 'management'),
('manage_sessions', 'Manage Sessions', 'Create and edit sessions', 'admin'),
('manage_locations', 'Manage Locations', 'Add and edit locations', 'admin'),
('manage_session_types', 'Manage Session Types', 'Add and edit session types', 'admin'),
('manage_discounts', 'Manage Discounts', 'Create and manage discount codes', 'admin'),
('manage_roles', 'Manage User Roles', 'Change user roles', 'admin'),
('manage_permissions', 'Manage Permissions', 'Assign permissions to roles and users', 'admin'),
('view_system_settings', 'View System Settings', 'Access system configuration', 'admin'),
('edit_system_settings', 'Edit System Settings', 'Modify system configuration', 'admin');

-- =========================================================
-- ASSIGN DEFAULT ROLE PERMISSIONS
-- =========================================================

-- Athlete permissions
INSERT IGNORE INTO `role_permissions` (`role`, `permission_id`, `granted`)
SELECT 'athlete', id, 1 FROM permissions WHERE permission_key IN (
  'view_dashboard', 'view_stats', 'edit_own_stats', 'view_schedule', 'book_sessions', 
  'cancel_bookings', 'view_workouts', 'view_nutrition', 'view_videos', 'view_drills', 'view_practice_plans'
);

-- Coach permissions (all athlete permissions plus coach-specific)
INSERT IGNORE INTO `role_permissions` (`role`, `permission_id`, `granted`)
SELECT 'coach', id, 1 FROM permissions WHERE permission_key IN (
  'view_dashboard', 'view_stats', 'view_schedule', 'view_workouts', 'create_workouts', 'assign_workouts',
  'view_nutrition', 'create_nutrition', 'assign_nutrition', 'view_videos', 'upload_videos',
  'view_drills', 'create_drills', 'view_practice_plans', 'create_practice_plans', 'share_practice_plans',
  'manage_athletes', 'create_athletes', 'view_athlete_notes', 'create_athlete_notes'
);

-- Coach+ permissions (all coach permissions plus extended)
INSERT IGNORE INTO `role_permissions` (`role`, `permission_id`, `granted`)
SELECT 'coach_plus', id, 1 FROM permissions WHERE permission_key IN (
  'view_dashboard', 'view_stats', 'view_schedule', 'view_workouts', 'create_workouts', 'assign_workouts',
  'view_nutrition', 'create_nutrition', 'assign_nutrition', 'view_videos', 'upload_videos', 'delete_videos',
  'view_drills', 'create_drills', 'delete_drills', 'view_practice_plans', 'create_practice_plans', 
  'share_practice_plans', 'delete_practice_plans', 'import_from_ihs',
  'manage_athletes', 'create_athletes', 'edit_athlete_profiles', 'view_athlete_notes', 'create_athlete_notes',
  'manage_sessions'
);

-- Admin permissions (all permissions)
INSERT IGNORE INTO `role_permissions` (`role`, `permission_id`, `granted`)
SELECT 'admin', id, 1 FROM permissions;
