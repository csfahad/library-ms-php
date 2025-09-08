<?php
/**
 * Library Helper Functions
 * Library Management System
 */

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Get system setting from database
 * @param string $key Setting key
 * @param string $default Default value if setting doesn't exist
 * @return string Setting value or default
 */
function getSetting($key, $default = '') {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        error_log("getSetting error for key '$key': " . $e->getMessage());
        return $default;
    }
}

/**
 * Get all books with optional search and pagination
 * @param string $search
 * @param int $limit
 * @param int $offset
 * @return array
 */
function getBooks($search = '', $limit = 0, $offset = 0) {
    try {
        $db = getDB();
        
        // Simplified query first - let's see if the issue is with the JOIN
        $sql = "SELECT *, quantity as available_quantity FROM books WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR author LIKE ? OR isbn LIKE ? OR category LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY title ASC";
        
        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        // Debug logging
        error_log("getBooks SQL: " . $sql);
        error_log("getBooks params: " . json_encode($params));
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        // Debug logging
        error_log("getBooks returned " . count($results) . " results");
        if (count($results) > 0) {
            error_log("First book: " . json_encode($results[0]));
        }
        
        return $results;
        
    } catch (Exception $e) {
        error_log("Get books error: " . $e->getMessage());
        error_log("Get books error trace: " . $e->getTraceAsString());
        return [];
    }
}

/**
 * Get book by ID
 * @param int $bookId
 * @return array|false
 */
function getBookById($bookId) {
    try {
        $db = getDB();
        $sql = "SELECT * FROM books WHERE book_id = :book_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':book_id', $bookId);
        $stmt->execute();
        
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Get book by ID error: " . $e->getMessage());
        return false;
    }
}

/**
 * Add new book
 * @param array $bookData
 * @return bool
 */
function addBook($bookData) {
    try {
        $db = getDB();
        
        $sql = "INSERT INTO books (title, author, publisher, category, isbn, quantity, available_quantity, price, description) 
                VALUES (:title, :author, :publisher, :category, :isbn, :quantity, :available_quantity, :price, :description)";
        
        $stmt = $db->prepare($sql);
        
        $stmt->bindParam(':title', $bookData['title']);
        $stmt->bindParam(':author', $bookData['author']);
        $stmt->bindParam(':publisher', $bookData['publisher']);
        $stmt->bindParam(':category', $bookData['category']);
        $stmt->bindParam(':isbn', $bookData['isbn']);
        $stmt->bindParam(':quantity', $bookData['quantity']);
        $stmt->bindParam(':available_quantity', $bookData['quantity']); // Initially all are available
        $stmt->bindParam(':price', $bookData['price']);
        $stmt->bindParam(':description', $bookData['description']);
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Add book error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update book
 * @param int $bookId
 * @param array $bookData
 * @return bool
 */
function updateBook($bookId, $bookData) {
    try {
        $db = getDB();
        
        $sql = "UPDATE books SET 
                title = :title, 
                author = :author, 
                publisher = :publisher, 
                category = :category, 
                isbn = :isbn, 
                quantity = :quantity, 
                price = :price, 
                description = :description,
                updated_at = CURRENT_TIMESTAMP
                WHERE book_id = :book_id";
        
        $stmt = $db->prepare($sql);
        
        $stmt->bindParam(':title', $bookData['title']);
        $stmt->bindParam(':author', $bookData['author']);
        $stmt->bindParam(':publisher', $bookData['publisher']);
        $stmt->bindParam(':category', $bookData['category']);
        $stmt->bindParam(':isbn', $bookData['isbn']);
        $stmt->bindParam(':quantity', $bookData['quantity']);
        $stmt->bindParam(':price', $bookData['price']);
        $stmt->bindParam(':description', $bookData['description']);
        $stmt->bindParam(':book_id', $bookId);
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Update book error: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete book
 * @param int $bookId
 * @return bool
 */
function deleteBook($bookId) {
    try {
        $db = getDB();
        
        // Check if book is currently issued
        $checkSql = "SELECT COUNT(*) FROM issued_books WHERE book_id = :book_id AND status = 'issued'";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':book_id', $bookId);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            return false; // Cannot delete book that is currently issued
        }
        
        $sql = "DELETE FROM books WHERE book_id = :book_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':book_id', $bookId);
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Delete book error: " . $e->getMessage());
        return false;
    }
}

/**
 * Issue book to user
 * @param int $userId
 * @param int $bookId
 * @return bool|string
 */
function issueBook($userId, $bookId) {
    try {
        $db = getDB();
        
        // Check if book is available
        $bookSql = "SELECT available_quantity FROM books WHERE book_id = :book_id";
        $bookStmt = $db->prepare($bookSql);
        $bookStmt->bindParam(':book_id', $bookId);
        $bookStmt->execute();
        $book = $bookStmt->fetch();
        
        if (!$book || $book['available_quantity'] <= 0) {
            return "Book is not available";
        }
        
        // Check if user already has this book
        $checkSql = "SELECT COUNT(*) FROM issued_books WHERE user_id = :user_id AND book_id = :book_id AND status = 'issued'";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->bindParam(':user_id', $userId);
        $checkStmt->bindParam(':book_id', $bookId);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            return "User already has this book issued";
        }
        
        // Check if user has reached maximum books limit
        $countSql = "SELECT COUNT(*) FROM issued_books WHERE user_id = :user_id AND status = 'issued'";
        $countStmt = $db->prepare($countSql);
        $countStmt->bindParam(':user_id', $userId);
        $countStmt->execute();
        
        $maxBooksPerUser = getSetting('max_books_per_user', '3');
        if ($countStmt->fetchColumn() >= $maxBooksPerUser) {
            return "User has reached maximum books limit ($maxBooksPerUser)";
        }
        
        // Issue the book
        $issueDate = date('Y-m-d');
        $issueDays = getSetting('issue_duration_days', '14');
        $dueDate = date('Y-m-d', strtotime('+' . $issueDays . ' days'));
        
        $sql = "INSERT INTO issued_books (user_id, book_id, issue_date, due_date, status) 
                VALUES (:user_id, :book_id, :issue_date, :due_date, 'issued')";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':book_id', $bookId);
        $stmt->bindParam(':issue_date', $issueDate);
        $stmt->bindParam(':due_date', $dueDate);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return "Failed to issue book";
        
    } catch (Exception $e) {
        error_log("Issue book error: " . $e->getMessage());
        return "An error occurred while issuing the book";
    }
}

/**
 * Return book
 * @param int $issueId
 * @return bool|string
 */
function returnBook($issueId) {
    try {
        $db = getDB();
        
        // Get issue details
        $issueSql = "SELECT * FROM issued_books WHERE issue_id = :issue_id AND status = 'issued'";
        $issueStmt = $db->prepare($issueSql);
        $issueStmt->bindParam(':issue_id', $issueId);
        $issueStmt->execute();
        $issue = $issueStmt->fetch();
        
        if (!$issue) {
            return "Issue record not found or book already returned";
        }
        
        // Calculate fine if overdue
        $returnDate = date('Y-m-d');
        $fine = 0;
        
        if ($returnDate > $issue['due_date']) {
            $daysOverdue = (strtotime($returnDate) - strtotime($issue['due_date'])) / (60 * 60 * 24);
            $finePerDay = getSetting('fine_per_day', '2.00');
            $fine = $daysOverdue * $finePerDay;
        }
        
        // Update issue record
        $sql = "UPDATE issued_books SET 
                return_date = :return_date, 
                fine = :fine, 
                status = 'returned',
                updated_at = CURRENT_TIMESTAMP
                WHERE issue_id = :issue_id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':return_date', $returnDate);
        $stmt->bindParam(':fine', $fine);
        $stmt->bindParam(':issue_id', $issueId);
        
        if ($stmt->execute()) {
            return true;
        }
        
        return "Failed to return book";
        
    } catch (Exception $e) {
        error_log("Return book error: " . $e->getMessage());
        return "An error occurred while returning the book";
    }
}

/**
 * Get issued books for a user
 * @param int $userId
 * @param string $status
 * @return array
 */
function getUserIssuedBooks($userId, $status = 'issued') {
    try {
        $db = getDB();
        
        $sql = "SELECT ib.*, b.title, b.author, b.isbn 
                FROM issued_books ib 
                JOIN books b ON ib.book_id = b.book_id 
                WHERE ib.user_id = :user_id AND ib.status = :status 
                ORDER BY ib.issue_date DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Get user issued books error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all issued books with user and book details
 * @param string $status
 * @return array
 */
function getAllIssuedBooks($status = 'issued') {
    try {
        $db = getDB();
        
        $sql = "SELECT ib.*, u.name as user_name, u.email, b.title as book_title, b.author, b.isbn 
                FROM issued_books ib 
                JOIN users u ON ib.user_id = u.user_id 
                JOIN books b ON ib.book_id = b.book_id 
                WHERE ib.status = :status 
                ORDER BY ib.issue_date DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Get all issued books error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get overdue books
 * @return array
 */
function getOverdueBooks() {
    try {
        $db = getDB();
        
        $sql = "SELECT 
                    ib.issue_id,
                    u.name AS user_name,
                    u.email,
                    b.title as book_title,
                    b.author,
                    ib.issue_date,
                    ib.due_date,
                    DATEDIFF(CURRENT_DATE, ib.due_date) AS days_overdue,
                    (DATEDIFF(CURRENT_DATE, ib.due_date) * 2.00) AS calculated_fine
                FROM issued_books ib
                JOIN users u ON ib.user_id = u.user_id
                JOIN books b ON ib.book_id = b.book_id
                WHERE ib.status = 'issued' 
                AND ib.due_date < CURRENT_DATE";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Get overdue books error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get users with optional search
 * @param string $search
 * @param string $role
 * @return array
 */
function getUsers($search = '', $role = '') {
    try {
        $db = getDB();
        
        $sql = "SELECT user_id, name, email, role, phone, address, registration_date, status 
                FROM users WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (name LIKE :search1 OR email LIKE :search2)";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
            
            // Temporary debug - remove after testing
            file_put_contents('/tmp/search_debug.txt', "Searching for: $search\nSQL: $sql\nParam1: " . $params['search1'] . "\nParam2: " . $params['search2'] . "\n", FILE_APPEND);
        }
        
        if (!empty($role)) {
            $sql .= " AND role = :role";
            $params['role'] = $role;
        }
        
        $sql .= " ORDER BY name ASC";
        
        $stmt = $db->prepare($sql);
        
        if (!$stmt) {
            file_put_contents('/tmp/search_debug.txt', "Failed to prepare statement\n", FILE_APPEND);
            return [];
        }
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $execute_result = $stmt->execute();
        if (!$execute_result) {
            file_put_contents('/tmp/search_debug.txt', "Failed to execute statement\n", FILE_APPEND);
            return [];
        }
        
        $results = $stmt->fetchAll();
        
        // More debug
        file_put_contents('/tmp/search_debug.txt', "Results count: " . count($results) . "\n", FILE_APPEND);
        foreach ($results as $result) {
            file_put_contents('/tmp/search_debug.txt', "Found: " . $result['name'] . " (" . $result['email'] . ")\n", FILE_APPEND);
        }
        
        return $results;
        
    } catch (Exception $e) {
        file_put_contents('/tmp/search_debug.txt', "Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        error_log("Get users error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user by ID
 * @param int $userId
 * @return array|false
 */
function getUserById($userId) {
    try {
        $db = getDB();
        $sql = "SELECT * FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch();
        
    } catch (Exception $e) {
        error_log("Get user by ID error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get categories
 * @return array
 */
function getCategories() {
    try {
        $db = getDB();
        $sql = "SELECT DISTINCT category as category_name FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Get categories error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get dashboard statistics
 * @return array
 */
function getDashboardStats() {
    try {
        $db = getDB();
        
        $stats = [];
        
        // Total books
        $stmt = $db->query("SELECT COUNT(*) as count FROM books");
        $stats['total_books'] = $stmt->fetch()['count'];
        
        // Available books
        $stmt = $db->query("SELECT SUM(available_quantity) as count FROM books");
        $stats['available_books'] = $stmt->fetch()['count'] ?? 0;
        
        // Issued books
        $stmt = $db->query("SELECT COUNT(*) as count FROM issued_books WHERE status = 'issued'");
        $stats['issued_books'] = $stmt->fetch()['count'];
        
        // Total users
        $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $stats['total_users'] = $stmt->fetch()['count'];
        
        // Overdue books
        $stmt = $db->query("SELECT COUNT(*) as count FROM issued_books WHERE status = 'issued' AND due_date < CURRENT_DATE");
        $stats['overdue_books'] = $stmt->fetch()['count'];
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Get dashboard stats error: " . $e->getMessage());
        return [];
    }
}

/**
 * Search books for students
 * @param string $query
 * @return array
 */
function searchAvailableBooks($query) {
    try {
        $db = getDB();
        
        $sql = "SELECT * FROM available_books 
                WHERE title LIKE :query 
                OR author LIKE :query 
                OR category LIKE :query 
                OR isbn LIKE :query
                ORDER BY title ASC";
        
        $stmt = $db->prepare($sql);
        $searchTerm = "%$query%";
        $stmt->bindParam(':query', $searchTerm);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Search available books error: " . $e->getMessage());
        return [];
    }
}

/**
 * Format date for display
 * @param string $date
 * @return string
 */
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

/**
 * Calculate days difference
 * @param string $date1
 * @param string $date2
 * @return int
 */
function calculateDaysDifference($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    return $interval->days;
}

/**
 * Search books with advanced filtering
 * @param string $query
 * @param string $category
 * @param string $availability
 * @param int $limit
 * @param int $offset
 * @return array
 */
function searchBooks($query = '', $category = '', $availability = '', $limit = 12, $offset = 0) {
    try {
        $db = getDB();
        
        $sql = "SELECT b.*,
                (b.quantity - COALESCE(issued.issued_count, 0)) as available_copies,
                COALESCE(issued.issued_count, 0) as issued_count
                FROM books b 
                LEFT JOIN (
                    SELECT book_id, COUNT(*) as issued_count 
                    FROM issued_books 
                    WHERE status = 'issued' 
                    GROUP BY book_id
                ) issued ON b.book_id = issued.book_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($query)) {
            $sql .= " AND (b.title LIKE :query OR b.author LIKE :query OR b.isbn LIKE :query OR b.description LIKE :query)";
            $params['query'] = "%$query%";
        }
        
        if (!empty($category)) {
            $sql .= " AND b.category = :category";
            $params['category'] = $category;
        }
        
        if ($availability === 'available') {
            $sql .= " AND (b.quantity - COALESCE(issued.issued_count, 0)) > 0";
        } elseif ($availability === 'unavailable') {
            $sql .= " AND (b.quantity - COALESCE(issued.issued_count, 0)) = 0";
        }
        
        $sql .= " ORDER BY b.title ASC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $stmt = $db->prepare($sql);
        
        foreach ($params as $key => $value) {
            if (in_array($key, ['limit', 'offset'])) {
                $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(":$key", $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Search books error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total count of search results
 * @param string $query
 * @param string $category
 * @param string $availability
 * @return int
 */
function getSearchBooksCount($query = '', $category = '', $availability = '') {
    try {
        $db = getDB();
        
        $sql = "SELECT COUNT(*) as total
                FROM books b 
                LEFT JOIN (
                    SELECT book_id, COUNT(*) as issued_count 
                    FROM issued_books 
                    WHERE status = 'issued' 
                    GROUP BY book_id
                ) issued ON b.book_id = issued.book_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($query)) {
            $sql .= " AND (b.title LIKE :query OR b.author LIKE :query OR b.isbn LIKE :query OR b.description LIKE :query)";
            $params['query'] = "%$query%";
        }
        
        if (!empty($category)) {
            $sql .= " AND b.category = :category";
            $params['category'] = $category;
        }
        
        if ($availability === 'available') {
            $sql .= " AND (b.quantity - COALESCE(issued.issued_count, 0)) > 0";
        } elseif ($availability === 'unavailable') {
            $sql .= " AND (b.quantity - COALESCE(issued.issued_count, 0)) = 0";
        }
        
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Count search books error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get user borrowing history
 * @param int $userId
 * @param int $limit
 * @param int $offset
 * @return array
 */
function getUserBorrowingHistory($userId, $limit = 10, $offset = 0) {
    try {
        $db = getDB();
        
        $sql = "SELECT ib.*, b.title, b.author, b.isbn, b.category
                FROM issued_books ib
                JOIN books b ON ib.book_id = b.id
                WHERE ib.user_id = :user_id
                ORDER BY ib.issue_date DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        error_log("Get user borrowing history error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user borrowing statistics
 * @param int $userId
 * @return array
 */
function getUserBorrowingStats($userId) {
    try {
        $db = getDB();
        
        // Total books borrowed
        $totalStmt = $db->prepare("SELECT COUNT(*) as total FROM issued_books WHERE user_id = ?");
        $totalStmt->execute([$userId]);
        $total = $totalStmt->fetch()['total'] ?? 0;
        
        // Currently borrowed
        $currentStmt = $db->prepare("SELECT COUNT(*) as current FROM issued_books WHERE user_id = ? AND status = 'issued'");
        $currentStmt->execute([$userId]);
        $current = $currentStmt->fetch()['current'] ?? 0;
        
        // Returned books
        $returnedStmt = $db->prepare("SELECT COUNT(*) as returned FROM issued_books WHERE user_id = ? AND status = 'returned'");
        $returnedStmt->execute([$userId]);
        $returned = $returnedStmt->fetch()['returned'] ?? 0;
        
        // Overdue books
        $overdueStmt = $db->prepare("SELECT COUNT(*) as overdue FROM issued_books WHERE user_id = ? AND status = 'issued' AND due_date < CURDATE()");
        $overdueStmt->execute([$userId]);
        $overdue = $overdueStmt->fetch()['overdue'] ?? 0;
        
        // Total fines
        $finesStmt = $db->prepare("SELECT COALESCE(SUM(fine_amount), 0) as total_fines FROM issued_books WHERE user_id = ?");
        $finesStmt->execute([$userId]);
        $totalFines = $finesStmt->fetch()['total_fines'] ?? 0;
        
        return [
            'total_borrowed' => $total,
            'currently_borrowed' => $current,
            'returned' => $returned,
            'overdue' => $overdue,
            'total_fines' => $totalFines
        ];
        
    } catch (Exception $e) {
        error_log("Get user borrowing stats error: " . $e->getMessage());
        return [
            'total_borrowed' => 0,
            'currently_borrowed' => 0,
            'returned' => 0,
            'overdue' => 0,
            'total_fines' => 0
        ];
    }
}

/**
 * Get all book categories
 * @return array
 */
function getBookCategories() {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        $stmt->execute();
        
        $categories = [];
        while ($row = $stmt->fetch()) {
            $categories[] = $row['category'];
        }
        
        return $categories;
        
    } catch (Exception $e) {
        error_log("Get book categories error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total available books count
 * @return int
 */
function getTotalAvailableBooks() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT SUM(available_quantity) as total FROM books WHERE available_quantity > 0");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    } catch (Exception $e) {
        error_log("Get total available books error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get books in circulation count
 * @return int
 */
function getBooksInCirculation() {
    try {
        $db = getDB();
        $stmt = $db->query("SELECT COUNT(*) as total FROM issued_books WHERE status = 'issued'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    } catch (Exception $e) {
        error_log("Get books in circulation error: " . $e->getMessage());
        return 0;
    }
}
?>
