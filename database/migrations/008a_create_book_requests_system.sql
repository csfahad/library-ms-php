-- Migration: Add Book Requests System
-- Description: Creates book_requests table and related functionality for student book requests and admin approvals

-- Book Requests Table
CREATE TABLE IF NOT EXISTS book_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'issued', 'returned') DEFAULT 'pending',
    approved_by INT NULL,
    approved_date TIMESTAMP NULL,
    issue_date TIMESTAMP NULL,
    due_date TIMESTAMP NULL,
    return_date TIMESTAMP NULL,
    fine DECIMAL(10,2) DEFAULT 0.00,
    admin_notes TEXT,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES admin(admin_id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_status_date (status, request_date),
    INDEX idx_book_status (book_id, status)
);

-- View for pending requests with book and user details
CREATE OR REPLACE VIEW pending_book_requests AS
SELECT 
    br.request_id,
    br.request_date,
    br.status,
    br.admin_notes,
    u.user_id,
    u.name as user_name,
    u.email as user_email,
    u.phone as user_phone,
    b.book_id,
    b.title as book_title,
    b.author as book_author,
    b.isbn,
    b.category,
    b.available_quantity,
    b.quantity as total_quantity
FROM book_requests br
JOIN users u ON br.user_id = u.user_id
JOIN books b ON br.book_id = b.book_id
WHERE br.status = 'pending'
ORDER BY br.request_date ASC;

-- View for issued books (approved requests that are currently with students)
CREATE OR REPLACE VIEW issued_books_via_requests AS
SELECT 
    br.request_id,
    br.user_id,
    br.book_id,
    br.issue_date,
    br.due_date,
    br.fine,
    u.name as user_name,
    u.email as user_email,
    b.title as book_title,
    b.author as book_author,
    b.isbn,
    DATEDIFF(NOW(), br.due_date) as days_overdue,
    CASE 
        WHEN br.due_date < NOW() THEN 'overdue'
        ELSE 'active'
    END as issue_status
FROM book_requests br
JOIN users u ON br.user_id = u.user_id
JOIN books b ON br.book_id = b.book_id
WHERE br.status = 'issued'
ORDER BY br.due_date ASC;

-- Triggers for automatic book availability management

-- Trigger to decrease available quantity when request is approved and issued
CREATE TRIGGER update_book_quantity_on_approval 
AFTER UPDATE ON book_requests
FOR EACH ROW
BEGIN
    -- If request status changed to 'issued' (approved and book given to student)
    IF OLD.status != 'issued' AND NEW.status = 'issued' THEN
        -- Decrease available quantity
        UPDATE books 
        SET available_quantity = GREATEST(0, available_quantity - 1) 
        WHERE book_id = NEW.book_id;
        
        -- Also create/update entry in issued_books table for compatibility
        INSERT INTO issued_books (user_id, book_id, issue_date, due_date, status)
        VALUES (NEW.user_id, NEW.book_id, NEW.issue_date, NEW.due_date, 'issued')
        ON DUPLICATE KEY UPDATE 
            issue_date = NEW.issue_date,
            due_date = NEW.due_date,
            status = 'issued';
    END IF;
    
    -- If book is returned (status changed to 'returned')
    IF OLD.status = 'issued' AND NEW.status = 'returned' THEN
        -- Increase available quantity back
        UPDATE books 
        SET available_quantity = available_quantity + 1 
        WHERE book_id = NEW.book_id;
        
        -- Update issued_books table
        UPDATE issued_books 
        SET status = 'returned', return_date = NEW.return_date
        WHERE user_id = NEW.user_id AND book_id = NEW.book_id AND status = 'issued';
    END IF;
    
    -- If request is cancelled or rejected after being issued
    IF OLD.status = 'issued' AND NEW.status IN ('cancelled', 'rejected') THEN
        -- Increase available quantity back
        UPDATE books 
        SET available_quantity = available_quantity + 1 
        WHERE book_id = NEW.book_id;
        
        -- Update issued_books table
        UPDATE issued_books 
        SET status = 'returned', return_date = NOW()
        WHERE user_id = NEW.user_id AND book_id = NEW.book_id AND status = 'issued';
    END IF;
END;

-- Trigger to prevent multiple pending requests for same book by same user
CREATE TRIGGER prevent_duplicate_requests
BEFORE INSERT ON book_requests
FOR EACH ROW
BEGIN
    DECLARE existing_count INT DEFAULT 0;
    
    -- Check for existing pending/approved request
    SELECT COUNT(*) INTO existing_count
    FROM book_requests 
    WHERE user_id = NEW.user_id 
    AND book_id = NEW.book_id 
    AND status IN ('pending', 'approved', 'issued');
    
    -- Also check for active issued book in the old table
    IF existing_count = 0 THEN
        SELECT COUNT(*) INTO existing_count
        FROM issued_books 
        WHERE user_id = NEW.user_id 
        AND book_id = NEW.book_id 
        AND status = 'issued';
    END IF;
    
    IF existing_count > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User already has pending request or active issue for this book';
    END IF;
END;