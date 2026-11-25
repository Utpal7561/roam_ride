-- Rome & Ride Database Schema
-- Pick. Ride. Explore.

CREATE DATABASE IF NOT EXISTS `rome_and_ride` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `rome_and_ride`;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if re-running
DROP TABLE IF EXISTS `rate_limits`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `support_tickets`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `vehicle_handover`;
DROP TABLE IF EXISTS `refunds`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `documents`;
DROP TABLE IF EXISTS `booking_status_history`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `vehicle_images`;
DROP TABLE IF EXISTS `vehicles`;
DROP TABLE IF EXISTS `vehicle_categories`;
DROP TABLE IF EXISTS `locations`;
DROP TABLE IF EXISTS `promo_codes`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `role_permissions`;
DROP TABLE IF EXISTS `permissions`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(25) NOT NULL UNIQUE,
  `email` VARCHAR(150) NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin', 'admin', 'booking_manager', 'payment_manager', 'vehicle_manager', 'staff', 'customer') NOT NULL DEFAULT 'customer',
  `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
  `avatar` VARCHAR(255) NULL,
  `address` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_phone` (`phone`),
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Roles
CREATE TABLE `roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Permissions
CREATE TABLE `permissions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `module` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Role Permissions
CREATE TABLE `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Locations
CREATE TABLE `locations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `address` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL DEFAULT 'Guwahati',
  `phone` VARCHAR(30) NOT NULL,
  `is_pickup` TINYINT(1) NOT NULL DEFAULT 1,
  `is_return` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Vehicle Categories
CREATE TABLE `vehicle_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(80) NOT NULL UNIQUE,
  `icon` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Vehicles
CREATE TABLE `vehicles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `brand` VARCHAR(80) NOT NULL,
  `model` VARCHAR(80) NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `reg_number` VARCHAR(50) NOT NULL UNIQUE,
  `daily_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 1000.00,
  `fuel_type` ENUM('Petrol', 'Electric', 'Hybrid') NOT NULL DEFAULT 'Petrol',
  `transmission` ENUM('Automatic', 'Manual') NOT NULL DEFAULT 'Automatic',
  `engine_capacity` VARCHAR(50) NOT NULL DEFAULT '110cc',
  `color` VARCHAR(50) NOT NULL DEFAULT 'Yellow/Black',
  `year` INT NOT NULL DEFAULT 2024,
  `mileage` VARCHAR(50) NOT NULL DEFAULT '45 km/l',
  `passenger_capacity` INT NOT NULL DEFAULT 2,
  `helmet_included` INT NOT NULL DEFAULT 1,
  `accessories` VARCHAR(255) NULL DEFAULT 'Mobile Holder, USB Charger',
  `pickup_location_id` INT UNSIGNED NOT NULL,
  `availability_status` ENUM('available', 'reserved', 'booked', 'assigned', 'rental_active', 'maintenance', 'out_of_service') NOT NULL DEFAULT 'available',
  `condition_status` ENUM('excellent', 'good', 'fair') NOT NULL DEFAULT 'excellent',
  `insurance_expiry` DATE NULL,
  `reg_expiry` DATE NULL,
  `last_service_date` DATE NULL,
  `next_service_date` DATE NULL,
  `primary_image` VARCHAR(255) NOT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `rental_rules` TEXT NULL,
  `cancellation_policy` TEXT NULL,
  `notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `vehicle_categories` (`id`),
  FOREIGN KEY (`pickup_location_id`) REFERENCES `locations` (`id`),
  INDEX `idx_vehicles_avail` (`availability_status`),
  INDEX `idx_vehicles_cat` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Vehicle Images
CREATE TABLE `vehicle_images` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `caption` VARCHAR(100) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Promo Codes
CREATE TABLE `promo_codes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) NULL,
  `discount_type` ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
  `discount_value` DECIMAL(10,2) NOT NULL,
  `min_booking_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_discount_amount` DECIMAL(10,2) NULL,
  `max_uses` INT NOT NULL DEFAULT 100,
  `current_uses` INT NOT NULL DEFAULT 0,
  `per_customer_limit` INT NOT NULL DEFAULT 1,
  `start_date` DATE NOT NULL,
  `expiry_date` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Bookings
CREATE TABLE `bookings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_code` VARCHAR(30) NOT NULL UNIQUE,
  `customer_id` INT UNSIGNED NOT NULL,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `assigned_vehicle_id` INT UNSIGNED NULL,
  `pickup_location_id` INT UNSIGNED NOT NULL,
  `return_location_id` INT UNSIGNED NOT NULL,
  `pickup_datetime` DATETIME NOT NULL,
  `return_datetime` DATETIME NOT NULL,
  `actual_pickup_datetime` DATETIME NULL,
  `actual_return_datetime` DATETIME NULL,
  `total_days` INT NOT NULL DEFAULT 1,
  `total_hours` INT NOT NULL DEFAULT 24,
  `daily_rate` DECIMAL(10,2) NOT NULL,
  `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `rental_charge` DECIMAL(10,2) NOT NULL,
  `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `additional_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `promo_code` VARCHAR(50) NULL,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `advance_required` DECIMAL(10,2) NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `remaining_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_option` ENUM('advance', 'pay_later', 'full') NOT NULL DEFAULT 'advance',
  `payment_status` ENUM('pending', 'partial', 'paid', 'refund_pending', 'refunded') NOT NULL DEFAULT 'pending',
  `booking_status` ENUM(
    'draft',
    'pending_approval',
    'pending_booking',
    'pending_payment',
    'payment_partially_completed',
    'payment_completed',
    'pending_document_verification',
    'documents_approved',
    'booking_confirmed',
    'vehicle_assigned',
    'ready_for_pickup',
    'rental_active',
    'returned',
    'completed',
    'cancelled',
    'rejected'
  ) NOT NULL DEFAULT 'pending_approval',
  `document_verification_status` ENUM('not_uploaded', 'uploaded', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'not_uploaded',
  `payment_deadline` DATETIME NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_phone` VARCHAR(25) NOT NULL,
  `customer_email` VARCHAR(150) NULL,
  `customer_notes` TEXT NULL,
  `admin_notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`),
  FOREIGN KEY (`assigned_vehicle_id`) REFERENCES `vehicles` (`id`),
  FOREIGN KEY (`pickup_location_id`) REFERENCES `locations` (`id`),
  FOREIGN KEY (`return_location_id`) REFERENCES `locations` (`id`),
  INDEX `idx_bookings_code` (`booking_code`),
  INDEX `idx_bookings_status` (`booking_status`),
  INDEX `idx_bookings_dates` (`pickup_datetime`, `return_datetime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Booking Status History
CREATE TABLE `booking_status_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(50) NOT NULL,
  `notes` TEXT NULL,
  `changed_by_user_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Documents
CREATE TABLE `documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `booking_id` INT UNSIGNED NULL,
  `vehicle_id` INT UNSIGNED NULL,
  `title` VARCHAR(150) NOT NULL,
  `doc_type` ENUM(
    'driving_license_front',
    'driving_license_back',
    'rental_agreement',
    'booking_confirmation',
    'registration_certificate',
    'insurance',
    'pickup_instructions',
    'payment_receipt',
    'handover_checklist',
    'other'
  ) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED NOT NULL DEFAULT 0,
  `mime_type` VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
  `verification_status` ENUM('not_uploaded', 'uploaded', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'uploaded',
  `is_customer_visible` TINYINT(1) NOT NULL DEFAULT 1,
  `is_confidential` TINYINT(1) NOT NULL DEFAULT 0,
  `rejection_reason` TEXT NULL,
  `verified_by_user_id` INT UNSIGNED NULL,
  `verified_at` DATETIME NULL,
  `expiry_date` DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL,
  INDEX `idx_docs_booking` (`booking_id`),
  INDEX `idx_docs_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Payments
CREATE TABLE `payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payment_code` VARCHAR(30) NOT NULL UNIQUE,
  `booking_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_type` ENUM(
    'booking_advance',
    'partial_payment',
    'full_payment',
    'security_deposit',
    'additional_charges',
    'late_fee',
    'refund'
  ) NOT NULL DEFAULT 'booking_advance',
  `payment_method` ENUM('upi', 'cash', 'card', 'bank_transfer', 'online_gateway', 'other') NOT NULL DEFAULT 'upi',
  `transaction_reference` VARCHAR(100) NULL,
  `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_status` ENUM('pending', 'successful', 'failed', 'refunded') NOT NULL DEFAULT 'successful',
  `notes` TEXT NULL,
  `created_by_user_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  INDEX `idx_payments_booking` (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Refunds
CREATE TABLE `refunds` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payment_id` INT UNSIGNED NULL,
  `booking_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `refund_amount` DECIMAL(10,2) NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'processed') NOT NULL DEFAULT 'pending',
  `approved_by_user_id` INT UNSIGNED NULL,
  `transaction_ref` VARCHAR(100) NULL,
  `processed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Vehicle Handover & Return Checklist
CREATE TABLE `vehicle_handover` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT UNSIGNED NOT NULL,
  `vehicle_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `handover_type` ENUM('pickup', 'return') NOT NULL DEFAULT 'pickup',
  `handover_datetime` DATETIME NOT NULL,
  `odometer_reading` INT UNSIGNED NOT NULL DEFAULT 0,
  `fuel_level` VARCHAR(50) NOT NULL DEFAULT 'Full (100%)',
  `condition_notes` TEXT NULL,
  `existing_scratches` TEXT NULL,
  `accessories_supplied` VARCHAR(255) NULL,
  `helmet_count` INT NOT NULL DEFAULT 1,
  `customer_signature_confirmed` TINYINT(1) NOT NULL DEFAULT 1,
  `staff_signature_confirmed` TINYINT(1) NOT NULL DEFAULT 1,
  `photos_json` TEXT NULL,
  `new_damages` TEXT NULL,
  `cleaning_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fuel_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `late_fee_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_extra_charges` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Support Tickets
CREATE TABLE `support_tickets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ticket_code` VARCHAR(30) NOT NULL UNIQUE,
  `user_id` INT UNSIGNED NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(150) NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `booking_id` INT UNSIGNED NULL,
  `category` ENUM('booking_issue', 'payment_issue', 'vehicle_issue', 'document_issue', 'general') NOT NULL DEFAULT 'general',
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
  `status` ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open',
  `admin_reply` TEXT NULL,
  `assigned_to_user_id` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Notifications
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('booking', 'payment', 'document', 'system') NOT NULL DEFAULT 'system',
  `link` VARCHAR(255) NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Audit Logs
CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `user_role` VARCHAR(50) NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(80) NOT NULL,
  `entity_id` VARCHAR(50) NULL,
  `old_values` TEXT NULL,
  `new_values` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_action` (`action`),
  INDEX `idx_audit_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Settings
CREATE TABLE `settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` TEXT NULL,
  `group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `description` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Reviews
CREATE TABLE `reviews` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT UNSIGNED NULL,
  `user_id` INT UNSIGNED NULL,
  `customer_name` VARCHAR(100) NOT NULL,
  `rating` INT NOT NULL DEFAULT 5,
  `comment` TEXT NOT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'approved',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Token Bucket Rate Limits
CREATE TABLE `rate_limits` (
  `key` VARCHAR(150) NOT NULL PRIMARY KEY,
  `tokens` DECIMAL(8,2) NOT NULL,
  `last_refill_time` INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
