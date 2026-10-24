-- Create database
CREATE DATABASE IF NOT EXISTS barcode_attendance;
USE barcode_attendance;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    department VARCHAR(100),
    position VARCHAR(100),
    barcode VARCHAR(100) UNIQUE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    barcode VARCHAR(100) NOT NULL,
    scan_type ENUM('in', 'out') NOT NULL,
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    -- Plain composite index (MariaDB 10.4 has no functional key parts).
    -- Serves WHERE user_id=? AND DATE(scan_time)=... just as well here.
    INDEX idx_user_date (user_id, scan_time),
    INDEX idx_barcode (barcode),
    INDEX idx_scan_time (scan_time)
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (username: admin, password: admin123)
-- Hash generated with PHP: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$R3CEVAHby8OM6m/ym.e8DO6sbp.HY3rCp3eFbCs45Z1OkmTDCHwJW') 
ON DUPLICATE KEY UPDATE password = VALUES(password);