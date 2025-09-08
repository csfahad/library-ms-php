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
            border: 2px solid var(--border-color);
            cursor: pointer;
            transition: var(--transition);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: var(--bg-primary);
        }
        
        .book-card.selected, .user-card.selected {
            border-color: var(--primary-color);
            background-color: var(--primary-light);
        }
        
        .book-card:hover, .user-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            margin: 0 1rem;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .step.completed {
            background: var(--success-color);
            color: white;
        }
        
        .step.active {
            background: var(--primary-color);
            color: white;
        }
        
        .step.pending {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
        }
        
        .step-number {
            background: white;
            color: var(--success-color);
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .step.active .step-number {
            background: white;
            color: var(--primary-color);
        }
        
        .step.pending .step-number {
            background: var(--text-secondary);
            color: white;
        }
        
        .selection-summary {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .selection-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
        }
        
        .selection-item:last-child {
            margin-bottom: 0;
        }
        
        .selection-info h4 {
            margin: 0 0 0.25rem 0;
            color: var(--text-primary);
        }
        
        .selection-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .user-card-content, .book-card-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-icon, .book-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-light);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .user-details, .book-details {
            flex: 1;
        }
        
        .user-details h4, .book-details h4 {
            margin: 0 0 0.25rem 0;
            color: var(--text-primary);
            font-size: 1rem;
        }
        
        .user-details p, .book-details p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }
    </style>
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

        <!-- Main Content -->
        <main class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-hand-holding"></i>
                    Issue Book
                </h1>
                <p class="page-subtitle">Issue books to library members</p>
            </div>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> mb-4">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step completed">
                    <div class="step-number">1</div>
                    Select User
                </div>
                <div class="step active">
                    <div class="step-number">2</div>
                    Select Book
                </div>
                <div class="step pending">
                    <div class="step-number">3</div>
                    Confirm
                </div>
            </div>

            <!-- Selection Summary -->
            <div class="selection-summary">
                <div class="row">
                    <div class="col-md-6">
                        <div class="selection-item">
                            <div class="selection-info">
                                <h4><i class="fas fa-user"></i> Selected User</h4>
                                <p id="selectedUserText">No user selected</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="selection-item">
                            <div class="selection-info">
                                <h4><i class="fas fa-book"></i> Selected Book</h4>
                                <p id="selectedBookText">No book selected</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 1: Select User -->
            <div class="card" id="step1">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i>
                        Step 1: Select User
                    </h3>
                </div>
                <div class="card-body">
                    <div class="search-box mb-3">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="userSearch" class="search-input" placeholder="Search users by name or email...">
                    </div>

                    <div class="row" id="usersList">
                        <?php if (!empty($activeUsers)): ?>
                            <?php foreach ($activeUsers as $user): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="user-card" data-user-id="<?php echo $user['user_id']; ?>" data-user-name="<?php echo htmlspecialchars($user['name']); ?>" data-user-email="<?php echo htmlspecialchars($user['email']); ?>" data-user-role="<?php echo htmlspecialchars($user['role']); ?>" data-user-phone="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        <div class="user-card-content">
                                            <div class="user-icon">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="user-details">
                                                <h4><?php echo htmlspecialchars($user['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($user['email']); ?></p>
                                                <p>Role: <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                                                <?php if (!empty($user['phone'])): ?>
                                                    <p>Phone: <?php echo htmlspecialchars($user['phone']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    No active users found.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Step 2: Select Book -->
            <div class="card" id="step2" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-book"></i>
                        Step 2: Select Book
                    </h3>
                </div>
                <div class="card-body">
                    <div class="search-box mb-3">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="bookSearch" class="search-input" placeholder="Search books by title, author, or ISBN...">
                    </div>

                    <div class="row" id="booksList">
                        <?php if (!empty($availableBooks)): ?>
                            <?php foreach ($availableBooks as $book): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="book-card" data-book-id="<?php echo $book['book_id']; ?>" data-book-title="<?php echo htmlspecialchars($book['title']); ?>" data-book-author="<?php echo htmlspecialchars($book['author']); ?>" data-book-category="<?php echo htmlspecialchars($book['category']); ?>" data-book-isbn="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>" data-book-available="<?php echo $book['available_quantity']; ?>">
                                        <div class="book-card-content">
                                            <div class="book-icon">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <div class="book-details">
                                                <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                                                <p>by <?php echo htmlspecialchars($book['author']); ?></p>
                                                <p>Category: <?php echo htmlspecialchars($book['category']); ?></p>
                                                <p>Available: <?php echo $book['available_quantity']; ?> copies</p>
                                                <?php if (!empty($book['isbn'])): ?>
                                                    <p>ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    No books available for issue.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-secondary" onclick="showStep(1)">
                            <i class="fas fa-arrow-left"></i> Back to Select User
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Confirmation -->
            <div class="card" id="step3" style="display: none;">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-check"></i>
                        Step 3: Confirm Book Issue
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" id="issueForm">
                        <input type="hidden" name="user_id" id="selectedUserId">
                        <input type="hidden" name="book_id" id="selectedBookId">
                        
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle"></i>
                            Please review the details below and click "Issue Book" to confirm.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4><i class="fas fa-user"></i> User Details</h4>
                                    </div>
                                    <div class="card-body" id="confirmUserDetails">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h4><i class="fas fa-book"></i> Book Details</h4>
                                    </div>
                                    <div class="card-body" id="confirmBookDetails">
                                        <!-- Will be populated by JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <button type="button" class="btn btn-secondary me-2" onclick="showStep(2)">
                                <i class="fas fa-arrow-left"></i> Back to Select Book
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check"></i> Issue Book
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        let selectedUser = null;
        let selectedBook = null;
        let currentStep = 1;

        // Show specific step
        function showStep(step) {
            // Hide all steps
            for (let i = 1; i <= 3; i++) {
                document.getElementById(`step${i}`).style.display = 'none';
            }
            
            // Show current step
            document.getElementById(`step${step}`).style.display = 'block';
            currentStep = step;
            
            // Update step indicator
            updateStepIndicator();
            
            // Scroll to top of content
            document.querySelector('.main-content').scrollIntoView({behavior: 'smooth'});
        }

        // Update step indicator
        function updateStepIndicator() {
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                const stepNumber = index + 1;
                step.classList.remove('completed', 'active', 'pending');
                
                if (stepNumber < currentStep) {
                    step.classList.add('completed');
                } else if (stepNumber === currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.add('pending');
                }
            });
        }

        // Select user
        function selectUser(element) {
            // Remove previous selection
            document.querySelectorAll('.user-card').forEach(card => card.classList.remove('selected'));
            
            // Add selection to clicked card
            element.classList.add('selected');
            
            // Store selected user data
            selectedUser = {
                user_id: element.getAttribute('data-user-id'),
                name: element.getAttribute('data-user-name'),
                email: element.getAttribute('data-user-email'),
                role: element.getAttribute('data-user-role'),
                phone: element.getAttribute('data-user-phone')
            };
            
            // Update selection summary
            document.getElementById('selectedUserText').innerHTML = `${selectedUser.name} (${selectedUser.email})`;
            
            // Auto-advance to book selection after 1 second
            setTimeout(() => {
                showStep(2);
            }, 1000);
        }

        // Select book
        function selectBook(element) {
            // Remove previous selection
            document.querySelectorAll('.book-card').forEach(card => card.classList.remove('selected'));
            
            // Add selection to clicked card
            element.classList.add('selected');
            
            // Store selected book data
            selectedBook = {
                book_id: element.getAttribute('data-book-id'),
                title: element.getAttribute('data-book-title'),
                author: element.getAttribute('data-book-author'),
                category: element.getAttribute('data-book-category'),
                isbn: element.getAttribute('data-book-isbn'),
                available: element.getAttribute('data-book-available')
            };
            
            // Update selection summary
            document.getElementById('selectedBookText').innerHTML = `${selectedBook.title} by ${selectedBook.author}`;
            
            // Auto-advance to confirmation after 1 second
            setTimeout(() => {
                showConfirmation();
            }, 1000);
        }

        // Show confirmation step
        function showConfirmation() {
            // Populate confirmation details
            document.getElementById('confirmUserDetails').innerHTML = `
                <p><strong>Name:</strong> ${selectedUser.name}</p>
                <p><strong>Email:</strong> ${selectedUser.email}</p>
                <p><strong>Role:</strong> ${selectedUser.role}</p>
                ${selectedUser.phone ? `<p><strong>Phone:</strong> ${selectedUser.phone}</p>` : ''}
            `;
            
            document.getElementById('confirmBookDetails').innerHTML = `
                <p><strong>Title:</strong> ${selectedBook.title}</p>
                <p><strong>Author:</strong> ${selectedBook.author}</p>
                <p><strong>Category:</strong> ${selectedBook.category}</p>
                <p><strong>Available Copies:</strong> ${selectedBook.available}</p>
                ${selectedBook.isbn ? `<p><strong>ISBN:</strong> ${selectedBook.isbn}</p>` : ''}
            `;
            
            // Set hidden form values
            document.getElementById('selectedUserId').value = selectedUser.user_id;
            document.getElementById('selectedBookId').value = selectedBook.book_id;
            
            // Show confirmation step
            showStep(3);
        }

        // Search functionality
        function setupSearch() {
            // User search
            const userSearch = document.getElementById('userSearch');
            if (userSearch) {
                userSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const userCards = document.querySelectorAll('.user-card');
                    
                    userCards.forEach(card => {
                        const userName = card.getAttribute('data-user-name').toLowerCase();
                        const userEmail = card.getAttribute('data-user-email').toLowerCase();
                        
                        if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
                            card.closest('.col-md-6').style.display = 'block';
                        } else {
                            card.closest('.col-md-6').style.display = 'none';
                        }
                    });
                });
            }
            
            // Book search
            const bookSearch = document.getElementById('bookSearch');
            if (bookSearch) {
                bookSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();
                    const bookCards = document.querySelectorAll('.book-card');
                    
                    bookCards.forEach(card => {
                        const bookTitle = card.getAttribute('data-book-title').toLowerCase();
                        const bookAuthor = card.getAttribute('data-book-author').toLowerCase();
                        const bookIsbn = card.getAttribute('data-book-isbn').toLowerCase();
                        
                        if (bookTitle.includes(searchTerm) || bookAuthor.includes(searchTerm) || bookIsbn.includes(searchTerm)) {
                            card.closest('.col-md-6').style.display = 'block';
                        } else {
                            card.closest('.col-md-6').style.display = 'none';
                        }
                    });
                });
            }
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers to user cards
            document.querySelectorAll('.user-card').forEach(card => {
                card.addEventListener('click', () => selectUser(card));
            });
            
            // Add click handlers to book cards
            document.querySelectorAll('.book-card').forEach(card => {
                card.addEventListener('click', () => selectBook(card));
            });
            
            // Setup search functionality
            setupSearch();
            
            // Initialize step indicator
            updateStepIndicator();
        });
    </script>
</body>
</html>
