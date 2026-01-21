-- =========================================================
-- CRASH HOCKEY DATABASE SCHEMA
-- Complete schema for hockey coaching management system
-- =========================================================

-- Users table with expanded roles
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `role` ENUM('athlete', 'coach', 'admin', 'parent', 'health_coach', 'team_coach') DEFAULT 'athlete',
    `is_verified` TINYINT(1) DEFAULT 0,
    `verification_code` VARCHAR(10) DEFAULT NULL,
    `force_pass_change` TINYINT(1) DEFAULT 0,
    `phone` VARCHAR(20) DEFAULT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `profile_image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_role` (`role`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parent-Athlete relationships
CREATE TABLE IF NOT EXISTS `parent_athlete_relationships` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT NOT NULL,
    `athlete_id` INT NOT NULL,
    `relationship_type` ENUM('parent', 'guardian', 'other') DEFAULT 'parent',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`parent_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`athlete_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_parent_athlete` (`parent_id`, `athlete_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Coach-Athlete assignments
CREATE TABLE IF NOT EXISTS `coach_athlete_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `coach_id` INT NOT NULL,
    `athlete_id` INT NOT NULL,
    `assignment_type` ENUM('active', 'past') DEFAULT 'active',
    `assigned_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `end_date` TIMESTAMP NULL,
    FOREIGN KEY (`coach_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`athlete_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_coach` (`coach_id`),
    INDEX `idx_athlete` (`athlete_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Teams
CREATE TABLE IF NOT EXISTS `teams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `age_group` VARCHAR(50) DEFAULT NULL,
    `skill_level` VARCHAR(50) DEFAULT NULL,
    `season` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Team-Coach assignments
CREATE TABLE IF NOT EXISTS `team_coach_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` INT NOT NULL,
    `coach_id` INT NOT NULL,
    `role` ENUM('head_coach', 'assistant_coach') DEFAULT 'head_coach',
    `assigned_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`coach_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Team roster (athlete memberships)
CREATE TABLE IF NOT EXISTS `team_roster` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` INT NOT NULL,
    `athlete_id` INT NOT NULL,
    `jersey_number` INT DEFAULT NULL,
    `position` VARCHAR(50) DEFAULT NULL,
    `joined_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`athlete_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_team_athlete` (`team_id`, `athlete_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Locations
CREATE TABLE IF NOT EXISTS `locations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(50) DEFAULT NULL,
    `postal_code` VARCHAR(10) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session types
CREATE TABLE IF NOT EXISTS `session_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `default_price` DECIMAL(10,2) DEFAULT 0.00,
    `duration_minutes` INT DEFAULT 60,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_type_id` INT DEFAULT NULL,
    `location_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `session_date` DATETIME NOT NULL,
    `duration_minutes` INT DEFAULT 60,
    `price` DECIMAL(10,2) DEFAULT 0.00,
    `max_participants` INT DEFAULT NULL,
    `age_group` VARCHAR(50) DEFAULT NULL,
    `skill_level` VARCHAR(50) DEFAULT NULL,
    `team_id` INT DEFAULT NULL,
    `coach_id` INT DEFAULT NULL,
    `status` ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`session_type_id`) REFERENCES `session_types`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`location_id`) REFERENCES `locations`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`coach_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_date` (`session_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Practice Plans
CREATE TABLE IF NOT EXISTS `practice_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `version` INT DEFAULT 1,
    `parent_plan_id` INT DEFAULT NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_plan_id`) REFERENCES `practice_plans`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session-Practice Plan association
CREATE TABLE IF NOT EXISTS `session_practice_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT NOT NULL,
    `practice_plan_id` INT NOT NULL,
    FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`practice_plan_id`) REFERENCES `practice_plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drill categories
CREATE TABLE IF NOT EXISTS `drill_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Drills
CREATE TABLE IF NOT EXISTS `drills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category_id` INT DEFAULT NULL,
    `created_by` INT NOT NULL,
    `diagram_data` TEXT DEFAULT NULL,
    `custom_image` VARCHAR(255) DEFAULT NULL,
    `video_url` VARCHAR(255) DEFAULT NULL,
    `ihs_source_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `version` INT DEFAULT 1,
    `parent_drill_id` INT DEFAULT NULL,
    FOREIGN KEY (`category_id`) REFERENCES `drill_categories`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_drill_id`) REFERENCES `drills`(`id`) ON DELETE SET NULL,
    INDEX `idx_category` (`category_id`),
    INDEX `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Practice Plan-Drill association
CREATE TABLE IF NOT EXISTS `practice_plan_drills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `practice_plan_id` INT NOT NULL,
    `drill_id` INT NOT NULL,
    `drill_order` INT DEFAULT 0,
    `duration_minutes` INT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    FOREIGN KEY (`practice_plan_id`) REFERENCES `practice_plans`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`drill_id`) REFERENCES `drills`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session bookings
CREATE TABLE IF NOT EXISTS `session_bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `booking_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `payment_status` ENUM('pending', 'paid', 'refunded', 'credit_used') DEFAULT 'pending',
    `amount_paid` DECIMAL(10,2) DEFAULT 0.00,
    `credits_used` INT DEFAULT 0,
    `status` ENUM('booked', 'attended', 'no_show', 'cancelled') DEFAULT 'booked',
    FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Packages
CREATE TABLE IF NOT EXISTS `packages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `credits` INT NOT NULL,
    `age_group` VARCHAR(50) DEFAULT NULL,
    `skill_level` VARCHAR(50) DEFAULT NULL,
    `team_id` INT DEFAULT NULL,
    `valid_days` INT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User package purchases
CREATE TABLE IF NOT EXISTS `user_packages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `package_id` INT NOT NULL,
    `purchase_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `credits_remaining` INT NOT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `payment_status` ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    `amount_paid` DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Discount codes
CREATE TABLE IF NOT EXISTS `discount_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `discount_type` ENUM('percentage', 'fixed') DEFAULT 'percentage',
    `discount_value` DECIMAL(10,2) NOT NULL,
    `max_uses` INT DEFAULT NULL,
    `times_used` INT DEFAULT 0,
    `valid_from` DATE DEFAULT NULL,
    `valid_until` DATE DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Video uploads
CREATE TABLE IF NOT EXISTS `videos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_id` INT NOT NULL,
    `coach_id` INT DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `video_url` VARCHAR(255) NOT NULL,
    `thumbnail_url` VARCHAR(255) DEFAULT NULL,
    `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `video_type` ENUM('drill_review', 'coach_review', 'uploaded_by_athlete') DEFAULT 'drill_review',
    `drill_id` INT DEFAULT NULL,
    `session_id` INT DEFAULT NULL,
    `status` ENUM('pending_review', 'reviewed', 'archived') DEFAULT 'pending_review',
    `coach_notes` TEXT DEFAULT NULL,
    `athlete_notes` TEXT DEFAULT NULL,
    `reviewed_at` TIMESTAMP NULL,
    FOREIGN KEY (`athlete_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`coach_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`drill_id`) REFERENCES `drills`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE SET NULL,
    INDEX `idx_athlete` (`athlete_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workout exercises library
CREATE TABLE IF NOT EXISTS `exercise_library` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `equipment_needed` TEXT DEFAULT NULL,
    `difficulty_level` VARCHAR(50) DEFAULT NULL,
    `video_url` VARCHAR(255) DEFAULT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workout plans
CREATE TABLE IF NOT EXISTS `workout_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_by` INT NOT NULL,
    `duration_weeks` INT DEFAULT NULL,
    `difficulty_level` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Workout plan exercises
CREATE TABLE IF NOT EXISTS `workout_plan_exercises` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `workout_plan_id` INT NOT NULL,
    `exercise_id` INT NOT NULL,
    `day_number` INT DEFAULT 1,
    `sets` INT DEFAULT NULL,
    `reps` VARCHAR(50) DEFAULT NULL,
    `rest_seconds` INT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `exercise_order` INT DEFAULT 0,
    FOREIGN KEY (`workout_plan_id`) REFERENCES `workout_plans`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exercise_id`) REFERENCES `exercise_library`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Athlete workout assignments
CREATE TABLE IF NOT EXISTS `athlete_workout_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_id` INT NOT NULL,
    `workout_plan_id` INT NOT NULL,
    `assigned_by` INT NOT NULL,
    `assigned_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `start_date` DATE DEFAULT NULL,
    `status` ENUM('active', 'completed', 'paused') DEFAULT 'active',
    FOREIGN KEY (`athlete_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`workout_plan_id`) REFERENCES `workout_plans`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_athlete` (`athlete_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Athlete workout feedback
CREATE TABLE IF NOT EXISTS `athlete_workout_feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `assignment_id` INT NOT NULL,
    `exercise_id` INT NOT NULL,
    `feedback` TEXT NOT NULL,
    `feedback_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `coach_response` TEXT DEFAULT NULL,
    `responded_at` TIMESTAMP NULL,
    FOREIGN KEY (`assignment_id`) REFERENCES `athlete_workout_assignments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`exercise_id`) REFERENCES `exercise_library`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nutrition food library
CREATE TABLE IF NOT EXISTS `food_library` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `calories` DECIMAL(10,2) DEFAULT NULL,
    `protein_g` DECIMAL(10,2) DEFAULT NULL,
    `carbs_g` DECIMAL(10,2) DEFAULT NULL,
    `fat_g` DECIMAL(10,2) DEFAULT NULL,
    `serving_size` VARCHAR(100) DEFAULT NULL,
    `created_by` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nutrition plans
CREATE TABLE IF NOT EXISTS `nutrition_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_by` INT NOT NULL,
    `target_calories` INT DEFAULT NULL,
    `target_protein_g` INT DEFAULT NULL,
    `target_carbs_g` INT DEFAULT NULL,
    `target_fat_g` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nutrition plan meals
CREATE TABLE IF NOT EXISTS `nutrition_plan_meals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nutrition_plan_id` INT NOT NULL,
    `meal_type` ENUM('breakfast', 'lunch', 'dinner', 'snack', 'pre_workout', 'post_workout') DEFAULT 'breakfast',
    `day_number` INT DEFAULT 1,
    `meal_order` INT DEFAULT 0,
    FOREIGN KEY (`nutrition_plan_id`) REFERENCES `nutrition_plans`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nutrition plan meal foods
CREATE TABLE IF NOT EXISTS `nutrition_plan_meal_foods` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `meal_id` INT NOT NULL,
    `food_id` INT NOT NULL,
    `serving_quantity` DECIMAL(10,2) DEFAULT 1,
    `notes` TEXT DEFAULT NULL,
    FOREIGN KEY (`meal_id`) REFERENCES `nutrition_plan_meals`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`food_id`) REFERENCES `food_library`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Athlete nutrition assignments
CREATE TABLE IF NOT EXISTS `athlete_nutrition_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_id` INT NOT NULL,
    `nutrition_plan_id` INT NOT NULL,
    `assigned_by` INT NOT NULL,
    `assigned_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `start_date` DATE DEFAULT NULL,
    `status` ENUM('active', 'completed', 'paused') DEFAULT 'active',
    FOREIGN KEY (`athlete_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`nutrition_plan_id`) REFERENCES `nutrition_plans`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_athlete` (`athlete_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Athlete nutrition feedback
CREATE TABLE IF NOT EXISTS `athlete_nutrition_feedback` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `assignment_id` INT NOT NULL,
    `feedback` TEXT NOT NULL,
    `feedback_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `coach_response` TEXT DEFAULT NULL,
    `responded_at` TIMESTAMP NULL,
    FOREIGN KEY (`assignment_id`) REFERENCES `athlete_nutrition_assignments`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Performance stats
CREATE TABLE IF NOT EXISTS `performance_stats` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_id` INT NOT NULL,
    `stat_date` DATE NOT NULL,
    `stat_type` VARCHAR(100) NOT NULL,
    `stat_value` DECIMAL(10,2) NOT NULL,
    `stat_unit` VARCHAR(50) DEFAULT NULL,
    `session_id` INT DEFAULT NULL,
    `recorded_by` INT DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`athlete_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_athlete` (`athlete_id`),
    INDEX `idx_stat_type` (`stat_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Goals
CREATE TABLE IF NOT EXISTS `goals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_id` INT NOT NULL,
    `goal_title` VARCHAR(255) NOT NULL,
    `goal_description` TEXT DEFAULT NULL,
    `target_value` DECIMAL(10,2) DEFAULT NULL,
    `current_value` DECIMAL(10,2) DEFAULT NULL,
    `target_date` DATE DEFAULT NULL,
    `status` ENUM('active', 'completed', 'abandoned') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`athlete_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_athlete` (`athlete_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mileage tracking
CREATE TABLE IF NOT EXISTS `mileage_tracking` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `trip_date` DATE NOT NULL,
    `start_location` VARCHAR(255) NOT NULL,
    `end_location` VARCHAR(255) NOT NULL,
    `distance_km` DECIMAL(10,2) NOT NULL,
    `purpose` VARCHAR(255) DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_date` (`trip_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expenses
CREATE TABLE IF NOT EXISTS `expenses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `expense_date` DATE NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `receipt_url` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `approved_by` INT DEFAULT NULL,
    `approved_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_date` (`expense_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions (payments, credits, refunds)
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `transaction_type` ENUM('payment', 'credit', 'refund', 'package_purchase', 'session_booking') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `hst_amount` DECIMAL(10,2) DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT NULL,
    `transaction_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_date` (`transaction_date`),
    INDEX `idx_type` (`transaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System notifications
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `notification_type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `link_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_read` (`user_id`, `is_read`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Evaluation framework categories
CREATE TABLE IF NOT EXISTS `eval_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Evaluation framework skills
CREATE TABLE IF NOT EXISTS `eval_skills` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `eval_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Athlete evaluations
CREATE TABLE IF NOT EXISTS `athlete_evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `athlete_id` INT NOT NULL,
    `evaluator_id` INT NOT NULL,
    `skill_id` INT NOT NULL,
    `rating` INT NOT NULL,
    `comments` TEXT DEFAULT NULL,
    `evaluation_date` DATE NOT NULL,
    `session_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`athlete_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`evaluator_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`skill_id`) REFERENCES `eval_skills`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`session_id`) REFERENCES `sessions`(`id`) ON DELETE SET NULL,
    INDEX `idx_athlete` (`athlete_id`),
    INDEX `idx_skill` (`skill_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scheduled reports
CREATE TABLE IF NOT EXISTS `scheduled_reports` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `report_name` VARCHAR(255) NOT NULL,
    `report_config` TEXT NOT NULL,
    `schedule_frequency` ENUM('daily', 'weekly', 'monthly') NOT NULL,
    `schedule_day` INT DEFAULT NULL,
    `schedule_time` TIME DEFAULT NULL,
    `recipients` TEXT NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_run_at` TIMESTAMP NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` VARCHAR(50) DEFAULT 'text',
    `description` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(100) DEFAULT NULL,
    `record_id` INT DEFAULT NULL,
    `old_values` TEXT DEFAULT NULL,
    `new_values` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Theme settings
CREATE TABLE IF NOT EXISTS `theme_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `theme_name` VARCHAR(100) NOT NULL,
    `primary_color` VARCHAR(7) DEFAULT '#ff4d00',
    `secondary_color` VARCHAR(7) DEFAULT '#00ff88',
    `background_color` VARCHAR(7) DEFAULT '#06080b',
    `logo_url` VARCHAR(255) DEFAULT NULL,
    `custom_css` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cron jobs
CREATE TABLE IF NOT EXISTS `cron_jobs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `job_name` VARCHAR(100) NOT NULL,
    `job_description` TEXT DEFAULT NULL,
    `schedule` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `last_run_at` TIMESTAMP NULL,
    `next_run_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
