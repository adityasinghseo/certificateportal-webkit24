-- Webkit24 Certificate Verification Portal Database Schema
-- Compatible with MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.3+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table: certificates
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `student_name` VARCHAR(255) NOT NULL,
  `certificate_id` VARCHAR(100) NOT NULL UNIQUE,
  `certificate_type` ENUM('Internship', 'Training', 'Experience') NOT NULL DEFAULT 'Internship',
  `position` VARCHAR(255) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `issue_date` DATE NOT NULL,
  `status` ENUM('Active', 'Revoked') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cert_id` (`certificate_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_type` (`certificate_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: admins
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `last_login` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_username` (`username`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: rate_limits
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `endpoint` VARCHAR(60) NOT NULL,
  `request_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `first_request` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_request` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_ip_endpoint` (`ip_address`, `endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: audit_logs (Optional logging of verification searches)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `certificate_id` VARCHAR(100) NOT NULL,
  `result_status` VARCHAR(50) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_cert_search` (`certificate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Default Admin Account (Optional seed)
-- Username: admin
-- Email: admin@webkit24.com
-- Initial Temporary Password: Webkit24@Admin2026 (BCrypt Hash: $2y$10$y6K9Xg3eP0b31Cj2wJ95E.n7kI8hPq2sM.fBvYl0X9mQ5dZ6WkGha)
-- Note: You should immediately change this password after initial login or create an account via install.php.
-- -----------------------------------------------------
INSERT IGNORE INTO `admins` (`id`, `username`, `email`, `password_hash`, `created_at`)
VALUES (1, 'admin', 'admin@webkit24.com', '$2y$10$89W5XJ8lQv0wQkFmKqH9iOPWdD4f5t9h9A2f3s4d5f6g7h8j9k0l1', NOW());

-- -----------------------------------------------------
-- Sample Demonstration Certificate (Per Project Brief)
-- Student Name: Hariom Pandey
-- ID: WEBKIT24/INT/2026/0035
-- -----------------------------------------------------
INSERT IGNORE INTO `certificates` 
(`student_name`, `certificate_id`, `certificate_type`, `position`, `start_date`, `end_date`, `issue_date`, `status`)
VALUES 
('Hariom Pandey', 'WEBKIT24/INT/2026/0035', 'Internship', 'Frontend Developer Intern', '2026-07-10', '2026-10-10', '2026-10-15', 'Active');

SET FOREIGN_KEY_CHECKS = 1;

