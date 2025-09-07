-- Library Management System Database
-- Created: September 6, 2025
-- Author: Library Management System Project

-- Create Database
CREATE DATABASE IF NOT EXISTS library_management_system;
USE library_management_system;

-- Users Table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'student',
    phone VARCHAR(20),
    address TEXT,
    registration_date DATE DEFAULT (CURRENT_DATE),
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin Table
CREATE TABLE admin (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    contact_email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Books Table
CREATE TABLE books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(100) NOT NULL,
    publisher VARCHAR(100),
    category VARCHAR(100) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    quantity INT NOT NULL DEFAULT 1,
    available_quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2),
    description TEXT,
    added_date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Issued Books Table
CREATE TABLE issued_books (
    issue_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    issue_date DATE NOT NULL DEFAULT CURRENT_DATE,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    fine DECIMAL(10,2) DEFAULT 0.00,
    status VARCHAR(20) DEFAULT 'issued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);

-- Categories Table
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Feedback Table
CREATE TABLE feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    feedback_date DATE DEFAULT CURRENT_DATE,
    status VARCHAR(20) DEFAULT 'pending',
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- System Settings Table
CREATE TABLE system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert Default Admin
INSERT INTO admin (username, password, full_name, contact_email) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@library.com');

-- Insert Default Categories
INSERT INTO categories (category_name, description) VALUES 
('Fiction', 'Fictional books and novels'),
('Non-Fiction', 'Non-fictional educational books'),
('Science', 'Science and technology books'),
('History', 'Historical books and references'),
('Biography', 'Biographical books'),
('Technology', 'Computer and technology books'),
('Literature', 'Literature and poetry books'),
('Reference', 'Reference and dictionary books');

-- Insert Default System Settings
INSERT INTO system_settings (setting_name, setting_value, description) VALUES
('library_name', 'City Library Management System', 'Name of the library'),
('max_books_per_user', '3', 'Maximum books a user can issue'),
('default_issue_days', '14', 'Default number of days for book issue'),
('fine_per_day', '2.00', 'Fine amount per day for overdue books'),
('library_email', 'info@library.com', 'Library contact email'),
('library_phone', '+1-234-567-8900', 'Library contact phone');

-- Insert Sample Books
INSERT INTO books (title, author, publisher, category, isbn, quantity, available_quantity, price, description) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'Scribner', 'Fiction', '9780743273565', 5, 5, 12.99, 'Classic American novel set in the 1920s'),
('To Kill a Mockingbird', 'Harper Lee', 'J.B. Lippincott & Co.', 'Fiction', '9780061120084', 3, 3, 14.99, 'Pulitzer Prize-winning novel about racial injustice'),
('1984', 'George Orwell', 'Secker & Warburg', 'Fiction', '9780452284234', 4, 4, 13.99, 'Dystopian social science fiction novel'),
('The Art of Computer Programming', 'Donald Knuth', 'Addison-Wesley', 'Technology', '9780201896831', 2, 2, 89.99, 'Comprehensive computer science reference'),
('A Brief History of Time', 'Stephen Hawking', 'Bantam Doubleday Dell', 'Science', '9780553380163', 3, 3, 18.99, 'Popular science book about cosmology'),
('Steve Jobs', 'Walter Isaacson', 'Simon & Schuster', 'Biography', '9781451648539', 2, 2, 24.99, 'Biography of Apple co-founder Steve Jobs');

-- Insert Sample Users
INSERT INTO users (name, email, password, role, phone, address) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '123-456-7890', '123 Main St, City'),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', '123-456-7891', '456 Oak Ave, City'),
('Bob Johnson', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'librarian', '123-456-7892', '789 Pine St, City');

-- Create Indexes for Better Performance
CREATE INDEX idx_books_title ON books(title);
CREATE INDEX idx_books_author ON books(author);
CREATE INDEX idx_books_category ON books(category);
CREATE INDEX idx_issued_books_user ON issued_books(user_id);
CREATE INDEX idx_issued_books_book ON issued_books(book_id);
CREATE INDEX idx_issued_books_status ON issued_books(status);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);

-- Create Views for Common Queries
CREATE VIEW available_books AS
SELECT 
    book_id,
    title,
    author,
    publisher,
    category,
    isbn,
    available_quantity,
    price
FROM books 
WHERE available_quantity > 0;

CREATE VIEW overdue_books AS
SELECT 
    ib.issue_id,
    u.name AS user_name,
    u.email,
    b.title,
    b.author,
    ib.issue_date,
    ib.due_date,
    DATEDIFF(CURRENT_DATE, ib.due_date) AS days_overdue,
    (DATEDIFF(CURRENT_DATE, ib.due_date) * 2.00) AS calculated_fine
FROM issued_books ib
JOIN users u ON ib.user_id = u.user_id
JOIN books b ON ib.book_id = b.book_id
WHERE ib.status = 'issued' 
AND ib.due_date < CURRENT_DATE;

-- Triggers for Automatic Updates
DELIMITER //

CREATE TRIGGER update_book_quantity_on_issue 
AFTER INSERT ON issued_books
FOR EACH ROW
BEGIN
    UPDATE books 
    SET available_quantity = available_quantity - 1 
    WHERE book_id = NEW.book_id;
END //

CREATE TRIGGER update_book_quantity_on_return 
AFTER UPDATE ON issued_books
FOR EACH ROW
BEGIN
    IF OLD.status = 'issued' AND NEW.status = 'returned' THEN
        UPDATE books 
        SET available_quantity = available_quantity + 1 
        WHERE book_id = NEW.book_id;
    END IF;
END //

DELIMITER ;

-- System Settings Table
CREATE TABLE system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value) VALUES
('library_name', 'City Public Library'),
('library_address', '123 Main Street, City, State 12345'),
('library_phone', '+1-234-567-8900'),
('library_email', 'info@library.com'),
('max_books_per_user', '3'),
('issue_duration_days', '14'),
('fine_per_day', '2.00');

-- Add constraints to ensure data integrity
ALTER TABLE books ADD CONSTRAINT chk_quantity CHECK (quantity >= 0);
ALTER TABLE books ADD CONSTRAINT chk_available_quantity CHECK (available_quantity >= 0);
ALTER TABLE books ADD CONSTRAINT chk_available_le_total CHECK (available_quantity <= quantity);
