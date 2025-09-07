-- Migration: Add feedback table
-- Run this SQL to add the feedback functionality to your database

CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    category ENUM('service_quality', 'book_collection', 'staff_behavior', 'facilities', 'website', 'suggestion', 'complaint', 'other') NOT NULL,
    message TEXT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    admin_response TEXT NULL,
    response_date DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    
    INDEX idx_user_feedback (user_id),
    INDEX idx_feedback_status (status),
    INDEX idx_feedback_category (category),
    INDEX idx_feedback_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
