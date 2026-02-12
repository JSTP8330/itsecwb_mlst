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
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5;
;

-- Login attempts for brute force detection
CREATE TABLE `login_attempts` (
  `attempt_id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `attempt_time` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attempt_id`)
) ENGINE=InnoDB;

-- -- Audit log definer

DELIMITER $$

CREATE DEFINER=`root`@`localhost` PROCEDURE `log_audit_action`(
    IN p_user_id INT,
    IN p_role VARCHAR(50),
    IN p_action VARCHAR(255),
    IN p_target_table VARCHAR(100),
    IN p_target_id INT
)
BEGIN
    INSERT INTO audit_logs (user_id, role, action, target_table, target_id)
    VALUES (p_user_id, p_role, p_action, p_target_table, p_target_id);
END $$

DELIMITER ;