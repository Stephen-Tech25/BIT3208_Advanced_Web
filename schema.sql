-- Database Schema for Crime Reporting System
CREATE DATABASE IF NOT EXISTS `crimereport_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `crimereport_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'manager', 'admin') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE INDEX `idx_email` (`email`)
) ENGINE=InnoDB;

-- 2. Reports Table
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `evidence_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Pending', 'Under Investigation', 'Resolved', 'Dismissed') NOT NULL DEFAULT 'Pending',
  `feedback` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB;

-- 3. Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `actor_id` INT DEFAULT NULL,
  `action_performed` VARCHAR(255) NOT NULL,
  `target_id` INT DEFAULT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  INDEX `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB;

-- Seed Default Accounts
-- Passwords:
-- Citizen123! -> citizen@example.com
-- Manager123! -> manager@example.com
-- Admin123!   -> admin@example.com
INSERT INTO `users` (`fullname`, `email`, `password`, `role`) VALUES
('Jane Doe', 'citizen@example.com', '$2y$10$jhqd75ZsTZQyM.B1Grkk3.2wFVs/6WPBzPmfF9TiioFqAKnjFjmNS', 'user')
ON DUPLICATE KEY UPDATE `fullname` = VALUES(`fullname`);

INSERT INTO `users` (`fullname`, `email`, `password`, `role`) VALUES
('Investigator Smith', 'manager@example.com', '$2y$10$FzjXGoEB./0kgxSBvqhPI.oleKygs2LuexjbMwk09DuUCCujYPqZC', 'manager')
ON DUPLICATE KEY UPDATE `fullname` = VALUES(`fullname`);

INSERT INTO `users` (`fullname`, `email`, `password`, `role`) VALUES
('Super Admin', 'admin@example.com', '$2y$10$VIeKl9CrrrpFvRjpUF9b6.dfP7pzguQwuyK2aWyXjlWXZDog/svTG', 'admin')
ON DUPLICATE KEY UPDATE `fullname` = VALUES(`fullname`);
