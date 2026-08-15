-- NetMatrix Database Schema
-- Author: Amir Hossein Nourzadeh

CREATE DATABASE IF NOT EXISTS netmatrix;
USE netmatrix;

-- جدول کاربران
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- جدول دستگاه‌ها
CREATE TABLE IF NOT EXISTS devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    ip VARCHAR(45) NOT NULL,
    mac VARCHAR(20),
    vendor VARCHAR(100),
    status ENUM('active', 'inactive', 'unknown') DEFAULT 'unknown',
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول هشدارها
CREATE TABLE IF NOT EXISTS alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_id INT,
    type VARCHAR(50),
    message TEXT,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    status ENUM('active', 'resolved') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE CASCADE
);

-- جدول لاگ‌ها
CREATE TABLE IF NOT EXISTS logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- جدول تحلیل‌ها
CREATE TABLE IF NOT EXISTS analyses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50),
    data JSON,
    result JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ایجاد کاربر پیش‌فرض
INSERT INTO users (username, password, email, role) 
VALUES ('admin', '$2y$10$H2eQ5q6Zq3z4l5m6n7o8p9/', 'admin@netmatrix.com', 'admin');
-- رمز عبور: admin123 (با bcrypt)

-- ایجاد داده‌های نمونه
INSERT INTO devices (name, ip, mac, vendor, status) VALUES
('سوئیچ اصلی', '192.168.1.1', '00:1C:42:AB:CD:EF', 'Cisco', 'active'),
('سرور اصلی', '192.168.1.10', '00:1A:E3:12:34:56', 'Dell', 'active'),
('پرینتر لیزری', '192.168.1.20', 'AA:BB:CC:DD:EE:FF', 'HP', 'active'),
('دوربین ورودی', '192.168.1.30', '11:22:33:44:55:66', 'Hikvision', 'active');
