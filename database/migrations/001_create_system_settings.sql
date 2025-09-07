-- Migration: Create system_settings table
-- Date: 2025-09-06
-- Description: Add system settings table for application configuration

CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
('library_name', 'City Public Library'),
('library_address', '123 Main Street, City, State 12345'),
('library_phone', '+1-234-567-8900'),
('library_email', 'info@library.com'),
('max_books_per_user', '3'),
('issue_duration_days', '14'),
('fine_per_day', '2.00');
