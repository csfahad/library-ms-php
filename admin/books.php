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
    <div id="addBookModal" class="modal">
        <div class="modal-container">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Add New Book</h3>
                    <button type="button" class="modal-close" data-modal-close>&times;</button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="title" class="form-label">Book Title *</label>
                                    <input type="text" id="title" name="title" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="author" class="form-label">Author *</label>
                                    <input type="text" id="author" name="author" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="publisher" class="form-label">Publisher</label>
                                    <input type="text" id="publisher" name="publisher" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category" class="form-label">Category *</label>
                                    <select id="category" name="category" class="form-control" required>
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
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="isbn" class="form-label">ISBN</label>
                                    <input type="text" id="isbn" name="isbn" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="quantity" class="form-label">Quantity *</label>
                                    <input type="number" id="quantity" name="quantity" min="1" value="1" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="price" class="form-label">Price</label>
                                    <input type="number" id="price" name="price" step="0.01" min="0" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" rows="3" class="form-control"></textarea>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Book
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div id="editBookModal" class="modal">
        <div class="modal-container">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Book</h3>
                    <button type="button" class="modal-close" data-modal-close>&times;</button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" id="editBookId" name="book_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editTitle" class="form-label">Book Title *</label>
                                    <input type="text" id="editTitle" name="title" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editAuthor" class="form-label">Author *</label>
                                    <input type="text" id="editAuthor" name="author" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editPublisher" class="form-label">Publisher</label>
                                    <input type="text" id="editPublisher" name="publisher" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editCategory" class="form-label">Category *</label>
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
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editIsbn" class="form-label">ISBN</label>
                                    <input type="text" id="editIsbn" name="isbn" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editQuantity" class="form-label">Quantity *</label>
                                    <input type="number" id="editQuantity" name="quantity" min="1" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="editPrice" class="form-label">Price</label>
                                    <input type="number" id="editPrice" name="price" step="0.01" min="0" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea id="editDescription" name="description" rows="3" class="form-control"></textarea>
                        </div>
                        
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-container">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">
                        <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                        Confirm Delete
                    </h3>
                    <button type="button" class="modal-close" data-modal-close>&times;</button>
                </div>
                <div class="modal-body">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <i class="fas fa-trash-alt" style="font-size: 48px; color: #dc3545; margin-bottom: 15px;"></i>
                        <h4 style="margin: 0 0 10px 0; color: #333;">Delete Book</h4>
                        <p style="margin: 0; color: #666;">Are you sure you want to delete "<strong id="deleteBookTitle"></strong>"?</p>
                    </div>
                    
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 15px; color: #721c24; margin-bottom: 20px;">
                        <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong>
                        This action cannot be undone. The book will be permanently removed from your library.
                    </div>
                    
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="book_id" id="deleteBookId">
                        
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Book
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
                
    <script>
        // Store books data for JavaScript functions
        const booksData = <?php echo json_encode($books); ?>;
        
        // Modal functions using LMS modal system
        function showAddBookModal() {
            LMS.openModal('addBookModal');
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
                LMS.openModal('editBookModal');
            }
        }
        
        function deleteBook(bookId, bookTitle) {
            // Set the book details in the delete modal
            document.getElementById('deleteBookTitle').textContent = bookTitle;
            document.getElementById('deleteBookId').value = bookId;
            
            // Show the delete confirmation modal
            LMS.openModal('deleteConfirmModal');
        }
    </script>

    <script src="../assets/js/script.js"></script>
            
</body>
</html>
