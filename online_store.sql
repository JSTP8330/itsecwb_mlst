-- Simple schema for audit_logs and users tables
-- Compatible with MySQL 8.0+

CREATE SCHEMA IF NOT EXISTS online_store;
USE online_store;

-- Audit logs table to track changes across the system
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(50) NOT NULL,
  `action_type` varchar(20) NOT NULL,
  `record_id` int DEFAULT NULL,
  `changed_by` varchar(100) DEFAULT NULL,
  `change_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB;

-- Users table for authentication and roles
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` text NOT NULL,
  `role` enum('admin','staff','customer') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5;

-- If you already have a users table, add: ALTER TABLE users ADD UNIQUE KEY email (email);

-- Login attempts for brute force detection
CREATE TABLE `login_attempts` (
  `attempt_id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `attempt_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attempt_id`)
) ENGINE=InnoDB;

-- Optional helper: matches audit_logs columns (table_name, action_type, record_id, changed_by, change_time)

DROP PROCEDURE IF EXISTS `log_audit_action`;

DELIMITER $$

CREATE DEFINER=`root`@`localhost` PROCEDURE `log_audit_action`(
    IN p_table_name VARCHAR(50),
    IN p_action_type VARCHAR(20),
    IN p_record_id INT,
    IN p_changed_by VARCHAR(100)
)
BEGIN
    INSERT INTO audit_logs (table_name, action_type, record_id, changed_by)
    VALUES (p_table_name, p_action_type, p_record_id, p_changed_by);
END $$

DELIMITER ;