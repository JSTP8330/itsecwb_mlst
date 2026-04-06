-- Simple schema for audit_logs and users tables
-- Compatible with MySQL 8.0+

CREATE SCHEMA IF NOT EXISTS online_store;
USE online_store;

-- Milestone 1: audit logs, users and login attempts
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

-- Milestone 2: store tables (products, orders, order_items)

CREATE TABLE `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock` int NOT NULL DEFAULT 0,
  `is_active` boolean NOT NULL DEFAULT true,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB;

CREATE TABLE `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `status` enum('pending','completed','cancelled') NOT NULL DEFAULT 'pending',
  `total` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  KEY `idx_orders_user_id` (`user_id`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE `order_items` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `idx_order_items_order_id` (`order_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Optional sample products (Phase 3A). Omit this block if you prefer an empty catalog.
INSERT INTO `products` (`product_id`, `name`, `category`, `description`, `price`, `stock`, `is_active`) VALUES
(1, 'Bytech Mechanical Keyboard K1', 'keyboards', 'Tactile switches, RGB backlight, full-size layout.', 3499.00, 25, 1),
(2, 'Bytech Compact Keyboard C60', 'keyboards', '60% layout, hot-swap sockets, USB-C.', 2299.00, 30, 1),
(3, 'Bytech Studio Headphones H200', 'headphones', 'Closed-back, detachable mic, 3.5mm + USB.', 1899.00, 40, 1),
(4, 'Bytech Wireless Earbuds E5', 'headphones', 'Bluetooth 5.3, charging case, low latency.', 1299.00, 60, 1),
(5, 'Bytech 24" Monitor M24', 'monitors', '1080p IPS, 75Hz, HDMI/VGA.', 6999.00, 15, 1),
(6, 'Bytech 27" Monitor M27', 'monitors', '1440p IPS, 100Hz, DisplayPort + HDMI.', 11999.00, 12, 1),
(7, 'Bytech Ergo Mouse M500', 'mice', 'Wireless, ergonomic right-hand shape, multi-device.', 899.00, 45, 1),
(8, 'Bytech Gaming Mouse G1', 'mice', 'Wired, 12000 DPI, lightweight shell.', 649.00, 50, 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `category` = VALUES(`category`),
  `description` = VALUES(`description`),
  `price` = VALUES(`price`),
  `stock` = VALUES(`stock`),
  `is_active` = VALUES(`is_active`);