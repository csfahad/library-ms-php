-- Add 'cancelled' status to book_requests table
-- Migration: 008b_add_cancelled_status.sql

USE library_management_system;

ALTER TABLE book_requests 
MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'issued', 'returned', 'cancelled') DEFAULT 'pending';
