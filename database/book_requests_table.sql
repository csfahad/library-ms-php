-- Book Request System Tables
-- Add to existing database schema

-- Book Requests Table
CREATE TABLE IF NOT EXISTS book_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    request_date DATE NOT NULL DEFAULT CURRENT_DATE,
    request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'pending', -- pending, approved, rejected, cancelled
    admin_id INT NULL,
    admin_response TEXT NULL,
    response_date TIMESTAMP NULL,
    issue_date DATE NULL,
    due_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admin(admin_id) ON DELETE SET NULL
);

-- Add index for better performance
CREATE INDEX idx_book_requests_user ON book_requests(user_id);
CREATE INDEX idx_book_requests_book ON book_requests(book_id);
CREATE INDEX idx_book_requests_status ON book_requests(status);
CREATE INDEX idx_book_requests_date ON book_requests(request_date);
