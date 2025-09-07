<?php
/**
 * Issue Book Page
 * Admin Panel - Library Management System
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin or librarian access
requireLibrarianOrAdmin();

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $bookId = (int)($_POST['book_id'] ?? 0);
    
    if ($userId && $bookId) {
        $result = issueBook($userId, $bookId);
        
        if ($result === true) {
            $message = 'Book issued successfully!';
            $messageType = 'success';
        } else {
            $message = $result; // Error message from issueBook function
            $messageType = 'danger';
        }
    } else {
        $message = 'Please select both user and book.';
        $messageType = 'danger';
    }
}

// Get available books and active users for dropdowns
$availableBooks = getBooks(); // Only get available books
$activeUsers = getUsers('', ''); // Get all users

// Filter only available books
$availableBooks = array_filter($availableBooks, function($book) {
    return $book['available_quantity'] > 0;
});

// Filter only active users
$activeUsers = array_filter($activeUsers, function($user) {
    return $user['status'] === 'active';
});

$pageTitle = 'Issue Book';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .book-card, .user-card {
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .book-card.selected, .user-card.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        
        .book-card:hover, .user-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
        }
        
        .search-input {
            margin-bottom: 20px;
        }
        
        .selection-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin: 0 10px;
        }
        
        .step-number {
            background: #6c757d;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .step.active .step-number {
            background: #007bff;
        }
        
        .step.completed .step-number {
            background: #28a745;
        }
        
        .step-line {
            width: 50px;
            height: 2px;
            background: #dee2e6;
        }
        
        .step.completed + .step .step-line {
            background: #28a745;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <nav class="navbar">
                <a href="dashboard.php" class="navbar-brand">
                    <i class="fas fa-book-open"></i> <?php echo SITE_NAME; ?>
                </a>
                <div class="navbar-nav">
                    <span class="nav-item">
                        <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="../includes/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2">
                <div class="sidebar">
                    <ul class="sidebar-nav">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="books.php" class="nav-link">
                                <i class="fas fa-book"></i> Manage Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="users.php" class="nav-link">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="issue-book.php" class="nav-link active">
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
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="main-content">
                    <div class="content-wrapper">
                        <h1><i class="fas fa-hand-holding"></i> Issue Book</h1>
                        <p class="text-muted">Issue books to library members</p>

                        <!-- Display messages -->
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step completed">
                                <div class="step-number">1</div>
                                <span>Select User</span>
                            </div>
                            <div class="step-line"></div>
                            <div class="step completed">
                                <div class="step-number">2</div>
                                <span>Select Book</span>
                            </div>
                            <div class="step-line"></div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <span>Confirm</span>
                            </div>
                        </div>

                        <!-- Selection Summary -->
                        <div class="selection-summary">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><i class="fas fa-user"></i> Selected User</h5>
                                    <div id="selectedUserInfo">
                                        <p class="text-muted">No user selected</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5><i class="fas fa-book"></i> Selected Book</h5>
                                    <div id="selectedBookInfo">
                                        <p class="text-muted">No book selected</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Issue Form -->
                        <form method="POST" id="issueForm" style="display: none;">
                            <input type="hidden" name="user_id" id="selectedUserId">
                            <input type="hidden" name="book_id" id="selectedBookId">
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-check"></i> Confirm Book Issue</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Issue Details</h6>
                                            <ul class="list-unstyled">
                                                <li><strong>Issue Date:</strong> <?php echo date('M j, Y'); ?></li>
                                                <li><strong>Due Date:</strong> <?php echo date('M j, Y', strtotime('+' . DEFAULT_ISSUE_DAYS . ' days')); ?></li>
                                                <li><strong>Issue Period:</strong> <?php echo DEFAULT_ISSUE_DAYS; ?> days</li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Library Rules</h6>
                                            <ul class="list-unstyled text-muted">
                                                <li><i class="fas fa-info-circle"></i> Maximum <?php echo MAX_BOOKS_PER_USER; ?> books per user</li>
                                                <li><i class="fas fa-calendar"></i> <?php echo DEFAULT_ISSUE_DAYS; ?> days issue period</li>
                                                <li><i class="fas fa-dollar-sign"></i> $<?php echo number_format(FINE_PER_DAY, 2); ?> fine per day for overdue books</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <button type="button" class="btn btn-secondary" onclick="resetSelection()">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Issue Book
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- User Selection Tab -->
                        <div id="userSelectionTab">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-users"></i> Step 1: Select User</h3>
                                </div>
                                <div class="card-body">
                                    <!-- Search Users -->
                                    <div class="search-input">
                                        <input 
                                            type="text" 
                                            id="userSearch" 
                                            class="form-control" 
                                            placeholder="Search users by name or email..."
                                        >
                                    </div>

                                    <?php if (empty($activeUsers)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h4>No Active Users Found</h4>
                                            <p class="text-muted">Please add users first.</p>
                                            <a href="users.php" class="btn btn-primary">
                                                <i class="fas fa-user-plus"></i> Manage Users
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="row" id="usersContainer">
                                            <?php foreach ($activeUsers as $user): ?>
                                                <div class="col-md-4 mb-3 user-item" data-user='<?php echo json_encode($user); ?>'>
                                                    <div class="card user-card" onclick="selectUser(<?php echo $user['user_id']; ?>)">
                                                        <div class="card-body">
                                                            <h6 class="card-title">
                                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['name']); ?>
                                                            </h6>
                                                            <p class="card-text">
                                                                <small class="text-muted">
                                                                    <?php echo htmlspecialchars($user['email']); ?><br>
                                                                    Role: <?php echo ucfirst($user['role']); ?>
                                                                    <?php if (!empty($user['phone'])): ?>
                                                                        <br>Phone: <?php echo htmlspecialchars($user['phone']); ?>
                                                                    <?php endif; ?>
                                                                </small>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Book Selection Tab -->
                        <div id="bookSelectionTab" style="display: none;">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fas fa-book"></i> Step 2: Select Book</h3>
                                </div>
                                <div class="card-body">
                                    <!-- Search Books -->
                                    <div class="search-input">
                                        <input 
                                            type="text" 
                                            id="bookSearch" 
                                            class="form-control" 
                                            placeholder="Search books by title, author, or ISBN..."
                                        >
                                    </div>

                                    <?php if (empty($availableBooks)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                            <h4>No Available Books Found</h4>
                                            <p class="text-muted">All books are currently issued or no books available.</p>
                                            <a href="books.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add Books
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="row" id="booksContainer">
                                            <?php foreach ($availableBooks as $book): ?>
                                                <div class="col-md-4 mb-3 book-item" data-book='<?php echo json_encode($book); ?>'>
                                                    <div class="card book-card" onclick="selectBook(<?php echo $book['book_id']; ?>)">
                                                        <div class="card-body">
                                                            <h6 class="card-title">
                                                                <i class="fas fa-book"></i> <?php echo htmlspecialchars($book['title']); ?>
                                                            </h6>
                                                            <p class="card-text">
                                                                <strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?><br>
                                                                <strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?><br>
                                                                <strong>Available:</strong> 
                                                                <span class="text-success"><?php echo $book['available_quantity']; ?> copies</span>
                                                                <?php if (!empty($book['isbn'])): ?>
                                                                    <br><strong>ISBN:</strong> <code><?php echo htmlspecialchars($book['isbn']); ?></code>
                                                                <?php endif; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="text-center mt-3">
                                        <button type="button" class="btn btn-secondary" onclick="showUserSelection()">
                                            <i class="fas fa-arrow-left"></i> Back to User Selection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        let selectedUser = null;
        let selectedBook = null;
        const usersData = <?php echo json_encode(array_values($activeUsers)); ?>;
        const booksData = <?php echo json_encode(array_values($availableBooks)); ?>;

        // Search functionality
        document.getElementById('userSearch').addEventListener('input', function() {
            searchUsers(this.value);
        });

        document.getElementById('bookSearch').addEventListener('input', function() {
            searchBooks(this.value);
        });

        function searchUsers(query) {
            const userItems = document.querySelectorAll('.user-item');
            userItems.forEach(item => {
                const userData = JSON.parse(item.dataset.user);
                const searchText = (userData.name + ' ' + userData.email + ' ' + userData.role).toLowerCase();
                const shouldShow = query === '' || searchText.includes(query.toLowerCase());
                item.style.display = shouldShow ? 'block' : 'none';
            });
        }

        function searchBooks(query) {
            const bookItems = document.querySelectorAll('.book-item');
            bookItems.forEach(item => {
                const bookData = JSON.parse(item.dataset.book);
                const searchText = (bookData.title + ' ' + bookData.author + ' ' + bookData.category + ' ' + (bookData.isbn || '')).toLowerCase();
                const shouldShow = query === '' || searchText.includes(query.toLowerCase());
                item.style.display = shouldShow ? 'block' : 'none';
            });
        }

        function selectUser(userId) {
            selectedUser = usersData.find(u => u.user_id == userId);
            
            // Update UI
            document.querySelectorAll('.user-card').forEach(card => card.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            
            // Update selection summary
            document.getElementById('selectedUserInfo').innerHTML = `
                <strong>${selectedUser.name}</strong><br>
                <small class="text-muted">${selectedUser.email}</small><br>
                <small class="text-muted">Role: ${selectedUser.role.charAt(0).toUpperCase() + selectedUser.role.slice(1)}</small>
            `;
            
            // Update step indicator
            document.querySelector('.step:first-child').classList.add('completed');
            
            // Show next step
            setTimeout(() => {
                showBookSelection();
            }, 500);
        }

        function selectBook(bookId) {
            selectedBook = booksData.find(b => b.book_id == bookId);
            
            // Update UI
            document.querySelectorAll('.book-card').forEach(card => card.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            
            // Update selection summary
            document.getElementById('selectedBookInfo').innerHTML = `
                <strong>${selectedBook.title}</strong><br>
                <small class="text-muted">by ${selectedBook.author}</small><br>
                <small class="text-muted">Available: ${selectedBook.available_quantity} copies</small>
            `;
            
            // Update step indicator
            document.querySelector('.step:nth-child(3)').classList.add('completed');
            document.querySelector('.step:nth-child(5)').classList.add('active');
            
            // Show form
            setTimeout(() => {
                showIssueForm();
            }, 500);
        }

        function showUserSelection() {
            document.getElementById('userSelectionTab').style.display = 'block';
            document.getElementById('bookSelectionTab').style.display = 'none';
            document.getElementById('issueForm').style.display = 'none';
        }

        function showBookSelection() {
            document.getElementById('userSelectionTab').style.display = 'none';
            document.getElementById('bookSelectionTab').style.display = 'block';
            document.getElementById('issueForm').style.display = 'none';
        }

        function showIssueForm() {
            if (selectedUser && selectedBook) {
                document.getElementById('selectedUserId').value = selectedUser.user_id;
                document.getElementById('selectedBookId').value = selectedBook.book_id;
                
                document.getElementById('userSelectionTab').style.display = 'none';
                document.getElementById('bookSelectionTab').style.display = 'none';
                document.getElementById('issueForm').style.display = 'block';
                
                // Scroll to form
                document.getElementById('issueForm').scrollIntoView({behavior: 'smooth'});
            }
        }

        function resetSelection() {
            selectedUser = null;
            selectedBook = null;
            
            // Reset UI
            document.querySelectorAll('.user-card, .book-card').forEach(card => card.classList.remove('selected'));
            document.querySelectorAll('.step').forEach(step => {
                step.classList.remove('completed', 'active');
            });
            document.querySelector('.step:first-child').classList.add('active');
            
            // Reset selection summary
            document.getElementById('selectedUserInfo').innerHTML = '<p class="text-muted">No user selected</p>';
            document.getElementById('selectedBookInfo').innerHTML = '<p class="text-muted">No book selected</p>';
            
            // Show user selection
            showUserSelection();
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.step:first-child').classList.add('active');
        });
    </script>
</body>
</html>
