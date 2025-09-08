-- Migration: Add extended system settings
-- Date: 2025-09-08
-- Description: Add extended system settings for comprehensive library management

-- Insert extended system settings with default values
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
-- Extended Library Information
('library_code', 'LIB001'),
('library_website', 'https://www.library.com'),
('library_hours', 'Mon-Fri: 9:00 AM - 8:00 PM'),

-- Extended System Settings
('renewal_limit', '2'),
('reservation_limit', '5'),
('max_fine_amount', '50.00'),
('grace_period_days', '1'),
('fine_calculation', 'daily'),

-- Notification Settings
('email_overdue', '1'),
('email_due_reminder', '1'),
('email_reservation', '1'),
('reminder_days_before', '2'),
('notification_email', 'noreply@library.com'),

-- Security Settings
('min_password_length', '8'),
('session_timeout', '30'),
('require_strong_password', '1'),
('enable_login_attempts', '1');
