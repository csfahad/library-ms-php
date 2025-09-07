<?php
/**
 * Books Management Page
 * Admin Panel - Library Management System
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add') {
        // Add new book
        $bookData = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'author' => sanitizeInput($_POST['author'] ?? ''),
            'publisher' => sanitizeInput($_POST['publisher'] ?? ''),
            'category' => sanitizeInput($_POST['category'] ?? ''),
            'isbn' => sanitizeInput($_POST['isbn'] ?? ''),
            'quantity' => (int)($_POST['quantity'] ?? 1),
            'price' => (float)($_POST['price'] ?? 0),
            'description' => sanitizeInput($_POST['description'] ?? '')
        ];
        
        if (addBook($bookData)) {
            $message = 'Book added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to add book. Please try again.';
            $messageType = 'danger';
        }
    }
    
    elseif ($action == 'edit') {
        // Edit existing book
        $bookId = (int)($_POST['book_id'] ?? 0);
        $bookData = [
            'title' => sanitizeInput($_POST['title'] ?? ''),
            'author' => sanitizeInput($_POST['author'] ?? ''),
            'publisher' => sanitizeInput($_POST['publisher'] ?? ''),
            'category' => sanitizeInput($_POST['category'] ?? ''),
            'isbn' => sanitizeInput($_POST['isbn'] ?? ''),
            'quantity' => (int)($_POST['quantity'] ?? 1),
            'price' => (float)($_POST['price'] ?? 0),
            'description' => sanitizeInput($_POST['description'] ?? '')
        ];
        
        if (updateBook($bookId, $bookData)) {
            $message = 'Book updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update book. Please try again.';
            $messageType = 'danger';
        }
    }
    
    elseif ($action == 'delete') {
        // Delete book
        $bookId = (int)($_POST['book_id'] ?? 0);
        
        if (deleteBook($bookId)) {
            $message = 'Book deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete book. Book may be currently issued.';
            $messageType = 'danger';
        }
    }
}

// Get search parameters
$search = sanitizeInput($_GET['search'] ?? '');

// Debug: Let's see what we have
error_log("Books search term: '$search'");

// Get books
$books = getBooks($search);

// Debug: Let's see the results
error_log("Books found: " . count($books));
if (!empty($search) && empty($books)) {
    error_log("Search returned no results for: '$search'");
}

// Additional debug - let's check if we have ANY books at all
$allBooks = getBooks('');
error_log("Total books in database: " . count($allBooks));

// Let's also test database connection
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM books");
    $stmt->execute();
    $result = $stmt->fetch();
    $totalBooks = $result['total'];
    error_log("Direct count from books table: " . $totalBooks);
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
}

// Get categories for dropdown
$categories = getBookCategories();

$pageTitle = 'Manage Books';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-layout">
    <!-- Admin Navbar -->
    <nav class="admin-navbar">
        <div class="navbar-content">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-book-open"></i>
                <?php echo SITE_NAME; ?>
            </a>
            <ul class="navbar-nav">
                <li><span class="nav-text"><i class="fas fa-user-shield"></i> Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span></li>
                <li><a href="../includes/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Admin Container -->
    <div class="admin-container">
        <!-- Admin Sidebar -->
        <aside class="admin-sidebar">
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="books.php" class="nav-link active">
                        <i class="fas fa-book"></i> Manage Books
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users.php" class="nav-link">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="issue-book.php" class="nav-link">
                        <i class="fas fa-hand-holding"></i> Issue Book
                    </a>
                </li>
                <li class="nav-item">
                    <a href="return-book.php" class="nav-link">
                        <i class="fas fa-undo"></i> Return Book
                    </a>
                </li>
                <li class="nav-item">
                    <a href="issued-books.php" class="nav-link">
                        <i class="fas fa-list"></i> Issued Books
                    </a>
                </li>
                <li class="nav-item">
                    <a href="overdue-books.php" class="nav-link">
                        <i class="fas fa-exclamation-triangle"></i> Overdue Books
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Admin Main Content -->
        <main class="admin-main">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> mb-4">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Books Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-book"></i> Manage Books
                </h1>
                <button class="btn btn-primary" onclick="showAddBookModal()">
                    <i class="fas fa-plus"></i> Add New Book
                </button>
            </div>

            <!-- Search and Filters -->
            <div class="content-card">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-search"></i> Search Books
                    </h3>
                </div>
                <div class="content-card-body">
                    <form method="GET" class="simple-search-form">
                        <div class="search-field-group">
                            <label for="search" class="form-label">Search Books</label>
                            <div class="search-input-container">
                                <input 
                                    type="text" 
                                    id="search" 
                                    name="search" 
                                    class="form-control search-field" 
                                    placeholder="Search by title, author, ISBN, or category..."
                                    value="<?php echo htmlspecialchars($search); ?>"
                                >
                                <button type="submit" class="btn btn-primary search-btn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="books.php" class="btn btn-secondary clear-btn">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Books Table -->
            <div class="content-card">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-list"></i> Books List
                        <span class="text-muted">(<?php echo count($books); ?> books found)</span>
                    </h3>
                </div>
                <div class="content-card-body">
                    <?php if (empty($books)): ?>
                        <div class="empty-state">
                            <i class="fas fa-book"></i>
                            <h4>No books found</h4>
                            <p>No books match your search criteria.</p>
                            <button class="btn btn-primary" onclick="showAddBookModal()">
                                <i class="fas fa-plus"></i> Add First Book
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author</th>
                                        <th>Category</th>
                                        <th>ISBN</th>
                                        <th>Quantity</th>
                                        <th>Available</th>
                                        <th>Price</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($books as $book): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                                <?php if (!empty($book['description'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($book['description'], 0, 50)) . (strlen($book['description']) > 50 ? '...' : ''); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($book['category']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></code>
                                            </td>
                                            <td><?php echo $book['quantity']; ?></td>
                                            <td>
                                                <span class="<?php echo $book['available_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $book['available_quantity']; ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($book['price'], 2); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-warning" onclick="editBook(<?php echo $book['book_id']; ?>)" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteBook(<?php echo $book['book_id']; ?>, '<?php echo htmlspecialchars($book['title']); ?>')" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Book Modal -->
        <!-- Simple Working Modals -->
    <style>
        .simple-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
        }
        .simple-modal.show {
            display: flex !important;
            align-items: center;
            justify-content: center;
        }
        .modal-dialog {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            transform: scale(0.7);
            transition: transform 0.3s ease;
        }
        .simple-modal.show .modal-dialog {
            transform: scale(1);
        }
        .modal-header-new {
            background: linear-gradient(135deg, #f07238, #ff8c42);
            color: white;
            padding: 25px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header-new h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .modal-close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .modal-close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }
        .modal-body-new {
            padding: 30px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-grid.full-width {
            grid-template-columns: 1fr;
        }
        .input-group {
            display: flex;
            flex-direction: column;
        }
        .input-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }
        .input-group input,
        .input-group select,
        .input-group textarea {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        .input-group input:focus,
        .input-group select:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: #f07238;
            box-shadow: 0 0 0 3px rgba(240, 114, 56, 0.1);
        }
        .modal-actions-new {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .btn-new {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary-new {
            background: linear-gradient(135deg, #f07238, #ff8c42);
            color: white;
        }
        .btn-primary-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(240, 114, 56, 0.4);
        }
        .btn-secondary-new {
            background: #6c757d;
            color: white;
        }
        .btn-secondary-new:hover {
            background: #545b62;
            transform: translateY(-2px);
        }
        .btn-danger-new {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        .btn-danger-new:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.4);
        }
        .delete-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
        }
        .delete-warning i {
            font-size: 48px;
            color: #e17055;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .modal-actions-new {
                flex-direction: column;
            }
        }
    </style>

    <!-- Add Book Modal -->
    <div id="addBookModal" class="simple-modal">
        <div class="modal-dialog">
            <div class="modal-header-new">
                <h3><i class="fas fa-plus-circle"></i> Add New Book</h3>
                <button class="modal-close-btn" onclick="hideModal('addBookModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-new">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="title">Book Title *</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        <div class="input-group">
                            <label for="author">Author *</label>
                            <input type="text" id="author" name="author" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="publisher">Publisher</label>
                            <input type="text" id="publisher" name="publisher">
                        </div>
                        <div class="input-group">
                            <label for="category">Category *</label>
                            <select id="category" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Fiction">Fiction</option>
                                <option value="Non-Fiction">Non-Fiction</option>
                                <option value="Science">Science</option>
                                <option value="Technology">Technology</option>
                                <option value="History">History</option>
                                <option value="Biography">Biography</option>
                                <option value="Education">Education</option>
                                <option value="Literature">Literature</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Science Fiction">Science Fiction</option>
                                <option value="Romance">Romance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="isbn">ISBN</label>
                            <input type="text" id="isbn" name="isbn">
                        </div>
                        <div class="input-group">
                            <label for="quantity">Quantity *</label>
                            <input type="number" id="quantity" name="quantity" min="1" value="1" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="price">Price</label>
                            <input type="number" id="price" name="price" step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div class="form-grid full-width">
                        <div class="input-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-actions-new">
                        <button type="button" class="btn-new btn-secondary-new" onclick="hideModal('addBookModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn-new btn-primary-new">
                            <i class="fas fa-plus"></i> Add Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div id="editBookModal" class="simple-modal">
        <div class="modal-dialog">
            <div class="modal-header-new">
                <h3><i class="fas fa-edit"></i> Edit Book</h3>
                <button class="modal-close-btn" onclick="hideModal('editBookModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-new">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="editBookId" name="book_id">
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="editTitle">Book Title *</label>
                            <input type="text" id="editTitle" name="title" required>
                        </div>
                        <div class="input-group">
                            <label for="editAuthor">Author *</label>
                            <input type="text" id="editAuthor" name="author" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="editPublisher">Publisher</label>
                            <input type="text" id="editPublisher" name="publisher">
                        </div>
                        <div class="input-group">
                            <label for="editCategory">Category *</label>
                            <select id="editCategory" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Fiction">Fiction</option>
                                <option value="Non-Fiction">Non-Fiction</option>
                                <option value="Science">Science</option>
                                <option value="Technology">Technology</option>
                                <option value="History">History</option>
                                <option value="Biography">Biography</option>
                                <option value="Education">Education</option>
                                <option value="Literature">Literature</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Science Fiction">Science Fiction</option>
                                <option value="Romance">Romance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="editIsbn">ISBN</label>
                            <input type="text" id="editIsbn" name="isbn">
                        </div>
                        <div class="input-group">
                            <label for="editQuantity">Quantity *</label>
                            <input type="number" id="editQuantity" name="quantity" min="1" required>
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="editPrice">Price</label>
                            <input type="number" id="editPrice" name="price" step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div class="form-grid full-width">
                        <div class="input-group">
                            <label for="editDescription">Description</label>
                            <textarea id="editDescription" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-actions-new">
                        <button type="button" class="btn-new btn-secondary-new" onclick="hideModal('editBookModal')">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn-new btn-primary-new">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="simple-modal">
        <div class="modal-dialog" style="max-width: 500px;">
            <div class="modal-header-new" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h3>
                <button class="modal-close-btn" onclick="hideModal('deleteConfirmModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body-new">
                <div class="delete-warning">
                    <i class="fas fa-trash-alt"></i>
                    <h4 style="margin: 0 0 10px 0; color: #856404;">Delete Book</h4>
                    <p style="margin: 0; color: #856404;">Are you sure you want to delete "<strong id="deleteBookTitle"></strong>"?</p>
                </div>
                
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 15px; color: #721c24;">
                    <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong>
                    This action cannot be undone. The book will be permanently removed from your library.
                </div>
                
                <form method="POST" id="deleteForm" style="display: none;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="book_id" id="deleteBookId">
                </form>
                
                <div class="modal-actions-new">
                    <button type="button" class="btn-new btn-secondary-new" onclick="hideModal('deleteConfirmModal')">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn-new btn-danger-new" onclick="submitDelete()">
                        <i class="fas fa-trash"></i> Delete Book
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Book Modal -->
    <div id="editBookModal" class="modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-container">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-section">
                        <div class="modal-icon">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div>
                            <h2 class="modal-title">Edit Book</h2>
                            <p class="modal-subtitle">Update book information</p>
                        </div>
                    </div>
                    <button class="modal-close" onclick="closeModal('editBookModal')" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-body">
                    <form method="POST" class="modal-form">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="editBookId" name="book_id">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editTitle" class="form-label">
                                    <i class="fas fa-book"></i>
                                    Book Title <span class="required">*</span>
                                </label>
                                <input type="text" id="editTitle" name="title" class="form-control" placeholder="Enter book title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="editAuthor" class="form-label">
                                    <i class="fas fa-user-edit"></i>
                                    Author <span class="required">*</span>
                                </label>
                                <input type="text" id="editAuthor" name="author" class="form-control" placeholder="Enter author name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editPublisher" class="form-label">
                                    <i class="fas fa-building"></i>
                                    Publisher
                                </label>
                                <input type="text" id="editPublisher" name="publisher" class="form-control" placeholder="Enter publisher name">
                            </div>
                            
                            <div class="form-group">
                                <label for="editCategory" class="form-label">
                                    <i class="fas fa-tags"></i>
                                    Category <span class="required">*</span>
                                </label>
                                <select id="editCategory" name="category" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <option value="Fiction">Fiction</option>
                                    <option value="Non-Fiction">Non-Fiction</option>
                                    <option value="Science">Science</option>
                                    <option value="Technology">Technology</option>
                                    <option value="History">History</option>
                                    <option value="Biography">Biography</option>
                                    <option value="Education">Education</option>
                                    <option value="Literature">Literature</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Science Fiction">Science Fiction</option>
                                    <option value="Romance">Romance</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editIsbn" class="form-label">
                                    <i class="fas fa-barcode"></i>
                                    ISBN
                                </label>
                                <input type="text" id="editIsbn" name="isbn" class="form-control" placeholder="978-0-123456-78-9">
                            </div>
                            
                            <div class="form-group">
                                <label for="editQuantity" class="form-label">
                                    <i class="fas fa-sort-numeric-up"></i>
                                    Quantity <span class="required">*</span>
                                </label>
                                <input type="number" id="editQuantity" name="quantity" class="form-control" min="1" placeholder="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="editPrice" class="form-label">
                                    <i class="fas fa-dollar-sign"></i>
                                    Price
                                </label>
                                <input type="number" id="editPrice" name="price" class="form-control" step="0.01" min="0" placeholder="0.00">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="editDescription" class="form-label">
                                <i class="fas fa-file-alt"></i>
                                Description
                            </label>
                            <textarea id="editDescription" name="description" class="form-control" rows="3" placeholder="Enter book description (optional)"></textarea>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeModal('editBookModal')">
                                <i class="fas fa-times"></i>
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal" style="display: none;">
        <div class="modal-overlay"></div>
        <div class="modal-container" style="max-width: 500px;">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-title-section">
                        <div class="modal-icon" style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.3);">
                            <i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i>
                        </div>
                        <div>
                            <h2 class="modal-title">Confirm Delete</h2>
                            <p class="modal-subtitle">This action cannot be undone</p>
                        </div>
                    </div>
                    <button class="modal-close" onclick="closeModal('deleteConfirmModal')" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="modal-warning">
                        <i class="fas fa-trash-alt"></i>
                        <h4>Delete Book</h4>
                        <p>Are you sure you want to delete "<span id="deleteBookTitle" class="book-title-highlight"></span>"?</p>
                    </div>
                    
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px; margin: 20px 0;">
                        <div style="display: flex; align-items: center; gap: 8px; color: #dc2626; font-weight: 600; margin-bottom: 8px;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Warning
                        </div>
                        <p style="color: #7f1d1d; margin: 0; font-size: 14px;">
                            This will permanently remove the book from your library system. 
                            If this book has been issued to any students, please return it first.
                        </p>
                    </div>
                    
                    <form method="POST" id="deleteForm" style="display: none;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="book_id" id="deleteBookId">
                    </form>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('deleteConfirmModal')">
                            <i class="fas fa-times"></i>
                            Cancel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash"></i>
                            Delete Book
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Store books data for JavaScript functions
        const booksData = <?php echo json_encode($books); ?>;
        
        // Simple working modal functions
        function showAddBookModal() {
            const modal = document.getElementById('addBookModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Reset form
            const form = modal.querySelector('form');
            form.reset();
        }
        
        function editBook(bookId) {
            const book = booksData.find(b => b.book_id == bookId);
            if (book) {
                // Populate form
                document.getElementById('editBookId').value = book.book_id;
                document.getElementById('editTitle').value = book.title;
                document.getElementById('editAuthor').value = book.author;
                document.getElementById('editPublisher').value = book.publisher || '';
                document.getElementById('editCategory').value = book.category;
                document.getElementById('editIsbn').value = book.isbn || '';
                document.getElementById('editQuantity').value = book.quantity;
                document.getElementById('editPrice').value = book.price || '';
                document.getElementById('editDescription').value = book.description || '';
                
                // Show modal
                const modal = document.getElementById('editBookModal');
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function deleteBook(bookId, bookTitle) {
            document.getElementById('deleteBookId').value = bookId;
            document.getElementById('deleteBookTitle').textContent = bookTitle;
            
            // Show modal
            const modal = document.getElementById('deleteConfirmModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function hideModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }
        
        function submitDelete() {
            const form = document.getElementById('deleteForm');
            form.submit();
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('simple-modal')) {
                hideModal(e.target.id);
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const openModal = document.querySelector('.simple-modal.show');
                if (openModal) {
                    hideModal(openModal.id);
                }
            }
        });
    </script>
            
</body>
</html>
