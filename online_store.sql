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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Users table for authentication and roles
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password_hash` text NOT NULL,
  `role` enum('admin','staff','customer') NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
;

INSERT INTO `users` VALUES (1,'john_doe','ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f','customer','john@example.com','2025-07-20 17:18:35'),(2,'admin_user','713bfda78870bf9d1b261f565286f85e97ee614efe5f0faf7c34e7ca4f65baca','admin','admin@example.com','2025-07-20 17:18:35'),(3,'staff_user','bcb53b632fbbba88d20863a26a528240f3e5a8d936d722bde3c95ce398a9236d','staff','staff@example.com','2025-07-20 17:18:35');

-- -- Audit log definer

DELIMITER $$

CREATE DEFINER=`root`@`localhost` PROCEDURE `log_audit_action`(
    IN p_user_id INT,
    IN p_role VARCHAR(50),
    IN p_action VARCHAR(255),
    IN p_target_table VARCHAR(100),
    IN p_target_id INTusers
)
BEGIN
    INSERT INTO audit_logs (user_id, role, action, target_table, target_id)
    VALUES (p_user_id, p_role, p_action, p_target_table, p_target_id);
END $$

DELIMITER ;