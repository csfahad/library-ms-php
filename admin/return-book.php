<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

// Get database connection
$pdo = getDB();

// Process return if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_book'])) {
    $issue_id = (int)$_POST['issue_id'];
    $return_date = date('Y-m-d H:i:s');
    
    // Get issue details
    $stmt = $pdo->prepare("SELECT * FROM issued_books ib 
                          JOIN books b ON ib.book_id = b.book_id 
                          JOIN users u ON ib.user_id = u.user_id 
                          WHERE ib.issue_id = ? AND ib.status = 'issued'");
    $stmt->execute([$issue_id]);
    $issue = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($issue) {
        // Calculate days
        $issue_date = new DateTime($issue['issue_date']);
        $return_date_obj = new DateTime($return_date);
        $due_date = new DateTime($issue['due_date']);
        $days_diff = $return_date_obj->diff($due_date)->days;
        $is_overdue = $return_date_obj > $due_date;
        
        // Calculate fine
        $fine_amount = 0;
        if ($is_overdue) {
            $fine_amount = $days_diff * FINE_PER_DAY;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Update issued_books record
            $stmt = $pdo->prepare("UPDATE issued_books 
                                  SET status = 'returned', 
                                      return_date = ?, 
                                      fine_amount = ?
                                  WHERE issue_id = ?");
            $stmt->execute([$return_date, $fine_amount, $issue_id]);
            
            // Update book quantity
            $stmt = $pdo->prepare("UPDATE books SET quantity = quantity + 1 WHERE book_id = ?");
            $stmt->execute([$issue['book_id']]);
            
            $pdo->commit();
            
            $success_message = "Book returned successfully!";
            if ($fine_amount > 0) {
                $success_message .= " Fine: $" . number_format($fine_amount, 2);
            }
            
        } catch (Exception $e) {
            $pdo->rollback();
            $error_message = "Error processing return: " . $e->getMessage();
        }
    } else {
        $error_message = "Invalid issue record or book already returned.";
    }
}

// Get all issued books for return
$stmt = $pdo->prepare("SELECT ib.*, b.title, b.isbn, u.name, u.email,
                      DATEDIFF(NOW(), ib.due_date) as days_overdue,
                      CASE 
                          WHEN NOW() > ib.due_date THEN 'overdue'
                          WHEN DATEDIFF(ib.due_date, NOW()) <= 3 THEN 'due_soon'
                          ELSE 'normal'
                      END as status_class
                      FROM issued_books ib
                      JOIN books b ON ib.book_id = b.book_id
                      JOIN users u ON ib.user_id = u.user_id
                      WHERE ib.status = 'issued'
                      ORDER BY 
                          CASE 
                              WHEN NOW() > ib.due_date THEN 1
                              WHEN DATEDIFF(ib.due_date, NOW()) <= 3 THEN 2
                              ELSE 3
                          END,
                          ib.due_date ASC");
$stmt->execute();
$issued_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Books - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Custom styles for return book page */
        
        /* Search form styling */
        .search-field-group {
            margin-bottom: 0;
        }
        
        .search-input-container {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: nowrap;
        }
        
        .search-input-container .form-control,
        .search-input-container .form-select,
        .search-input-container .btn {
            height: 42px;
            margin: 0;
        }
        
        .search-input-container .form-control {
            flex: 2;
            min-width: 250px;
        }
        
        .search-input-container .form-select {
            flex: 1.2;
            min-width: 180px;
            padding: 0.5rem 0.75rem;
            line-height: 1.5;
        }
        
        .search-input-container .clear-btn {
            flex-shrink: 0;
            background: var(--secondary-color);
            color: white;
            border: 1px solid var(--secondary-color);
            padding: 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .search-input-container .clear-btn:hover {
            background: var(--secondary-hover);
            border-color: var(--secondary-hover);
        }
        
        .form-label {
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        /* Compact stats grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            padding: 1.25rem 1rem;
            min-height: auto;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 0.5rem;
        }
        
        .stat-icon i {
            font-size: 1.25rem;
        }
        
        .stat-content {
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin: 0;
        }
        
        .books-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 1rem;
        }
        
        .book-item {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        .book-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* Main content area with horizontal layout */
        .book-content {
            display: flex;
            padding: 0.5rem;
            gap: 0.75rem;
            flex: 1;
        }
        
        .book-info-section {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .book-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        
        .book-info {
            flex: 1;
        }
        
        .book-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
            line-height: 1.3;
        }
        
        .book-meta {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .book-meta span {
            color: var(--text-secondary);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        
        .book-meta i {
            width: 12px;
            color: var(--text-light);
            flex-shrink: 0;
            font-size: 0.75rem;
        }
        
        .book-status {
            flex-shrink: 0;
        }
        
        /* Horizontal details grid */
        .book-details-horizontal {
            display: flex;
            gap: 0.75rem;
            margin-top: auto;
            padding: 0.5rem;
            background: var(--bg-tertiary);
            border-radius: var(--border-radius);
        }
        
        .detail-item-horizontal {
            text-align: center;
            flex: 1;
        }
        
        .detail-item-horizontal label {
            display: block;
            font-size: 0.65rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        
        .detail-item-horizontal span {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.8rem;
            line-height: 1.2;
        }
        
        /* Button section */
        .book-action-section {
            padding: 0.5rem 0.75rem 0.75rem 0.75rem;
        }
        
        .alert-warning {
            background: var(--warning-light);
            color: var(--warning-color);
            border: 1px solid rgba(217, 119, 6, 0.2);
            border-radius: var(--border-radius);
            padding: 0.5rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
        }
        
        .alert-warning i {
            color: var(--warning-color);
            font-size: 0.8rem;
        }
        
        .book-actions {
            margin-top: 0;
        }
        
        .book-actions .btn {
            width: 100%;
            padding: 0.6rem 1rem;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-secondary);
        }
        
        .empty-icon {
            margin-bottom: 1rem;
        }
        
        .empty-icon i {
            font-size: 4rem;
            color: var(--text-light);
        }
        
        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        /* Modal improvements */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            max-width: 500px;
            width: 90%;
            border: 1px solid var(--border-color);
        }
        
        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-title {
            margin: 0;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .return-details p {
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
        }
        
        .return-details strong {
            color: var(--text-primary);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .stat-label {
                font-size: 0.8rem;
            }
            
            .search-input-container {
                flex-wrap: wrap;
                align-items: stretch;
            }
            
            .search-input-container .form-control,
            .search-input-container .form-select,
            .search-input-container .btn {
                height: 40px;
            }
            
            .search-input-container .form-control {
                flex: 1 1 100%;
                min-width: auto;
                margin-bottom: 0.5rem;
            }
            
            .search-input-container .form-select {
                flex: 1 1 60%;
                min-width: auto;
            }
            
            .search-input-container .clear-btn {
                flex: 1 1 35%;
            }
            
            .books-container {
                grid-template-columns: 1fr;
            }
            
            .detail-row {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }
            
            .book-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .book-status {
                margin-left: 0;
                align-self: flex-start;
            }
            
            .modal-buttons {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .search-input-container .form-select,
            .search-input-container .clear-btn {
                flex: 1 1 100%;
                margin-bottom: 0.5rem;
            }
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
                    <a href="issue-book.php" class="nav-link">
                        <i class="fas fa-hand-holding"></i> Issue Book
                    </a>
                </li>
                <li class="nav-item">
                    <a href="return-book.php" class="nav-link active">
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
            <!-- Dashboard Header -->
            <div class="admin-page-header">
                <h1 class="admin-page-title">
                    <i class="fas fa-undo"></i> Return Books
                </h1>
                <p class="admin-page-description">Process book returns and manage fines</p>
            </div>

            <!-- Display Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <?php
            $overdue_count = 0;
            $due_soon_count = 0;
            $normal_count = 0;
            $total_fine = 0;

            foreach ($issued_books as $book) {
                if ($book['status_class'] === 'overdue') {
                    $overdue_count++;
                    $total_fine += max(0, $book['days_overdue'] * FINE_PER_DAY);
                } elseif ($book['status_class'] === 'due_soon') {
                    $due_soon_count++;
                } else {
                    $normal_count++;
                }
            }
            ?>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card books">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= $overdue_count ?></h3>
                        <p class="stat-label">Overdue Books</p>
                    </div>
                </div>

                <div class="stat-card users">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= $due_soon_count ?></h3>
                        <p class="stat-label">Due Soon</p>
                    </div>
                </div>

                <div class="stat-card issued">
                    <div class="stat-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?= $normal_count ?></h3>
                        <p class="stat-label">Normal</p>
                    </div>
                </div>

                <div class="stat-card overdue">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number">$<?= number_format($total_fine, 2) ?></h3>
                        <p class="stat-label">Total Fines</p>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="content-card">
                <div class="content-card-header">
                    <h3 class="content-card-title">
                        <i class="fas fa-search"></i> Search & Filter Books
                    </h3>
                </div>
                <div class="content-card-body">
                    <div class="simple-search-form">
                        <div class="search-field-group">
                            <label for="searchInput" class="form-label">Search Books</label>
                            <div class="search-input-container">
                                <input 
                                    type="text" 
                                    id="searchInput" 
                                    class="form-control search-field" 
                                    placeholder="Search by book title, user name, or email..."
                                >
                                <select id="statusFilter" class="form-select">
                                    <option value="all">All Books</option>
                                    <option value="overdue">Overdue</option>
                                    <option value="due_soon">Due Soon</option>
                                    <option value="normal">Normal</option>
                                </select>
                                <button onclick="clearFilters()" class="btn btn-secondary clear-btn">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Books List -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2 class="content-card-title">
                        <i class="fas fa-list"></i> Issued Books for Return
                    </h2>
                </div>
                <div class="content-card-body">
                    <div id="booksGrid">
                        <?php if (empty($issued_books)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <h3>No Issued Books</h3>
                                <p>There are currently no books issued that need to be returned.</p>
                            </div>
                        <?php else: ?>
                            <div class="books-container">
                                <?php foreach ($issued_books as $book): ?>
                                    <div class="book-item" 
                                         data-title="<?= strtolower($book['title']) ?>"
                                         data-user="<?= strtolower($book['name'] . ' ' . $book['email']) ?>"
                                         data-status="<?= $book['status_class'] ?>">
                                        
                                        <div class="book-card <?= $book['status_class'] ?>">
                                            <div class="book-content">
                                                <div class="book-info-section">
                                                    <div class="book-header">
                                                        <div class="book-info">
                                                            <h4 class="book-title"><?= htmlspecialchars($book['title']) ?></h4>
                                                            <div class="book-meta">
                                                                <span><i class="fas fa-barcode"></i> ISBN: <?= htmlspecialchars($book['isbn']) ?></span>
                                                                <span><i class="fas fa-user"></i> <?= htmlspecialchars($book['name']) ?></span>
                                                                <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($book['email']) ?></span>
                                                            </div>
                                                        </div>
                                                        <div class="book-status">
                                                            <?php if ($book['status_class'] === 'overdue'): ?>
                                                                <span class="badge badge-danger">
                                                                    <i class="fas fa-exclamation-triangle"></i> Overdue
                                                                </span>
                                                            <?php elseif ($book['status_class'] === 'due_soon'): ?>
                                                                <span class="badge badge-warning">
                                                                    <i class="fas fa-clock"></i> Due Soon
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge badge-success">
                                                                    <i class="fas fa-check"></i> Normal
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                    <div class="book-details-horizontal">
                                                        <div class="detail-item-horizontal">
                                                            <label>Issue Date</label>
                                                            <span><?= date('M d,<br>Y', strtotime($book['issue_date'])) ?></span>
                                                        </div>
                                                        <div class="detail-item-horizontal">
                                                            <label>Due Date</label>
                                                            <span><?= date('M d,<br>Y', strtotime($book['due_date'])) ?></span>
                                                        </div>
                                                        <div class="detail-item-horizontal">
                                                            <label>Days <?= $book['days_overdue'] > 0 ? 'Overdue' : 'Remaining' ?></label>
                                                            <span class="<?= ($book['days_overdue'] > 0) ? 'text-danger' : 'text-success' ?>">
                                                                <?= $book['days_overdue'] > 0 ? $book['days_overdue'] : abs($book['days_overdue']) ?> days
                                                            </span>
                                                        </div>
                                                        <div class="detail-item-horizontal">
                                                            <label>Fine Amount</label>
                                                            <span class="<?= ($book['days_overdue'] > 0) ? 'text-danger' : '' ?>">
                                                                $<?= number_format(max(0, $book['days_overdue'] * FINE_PER_DAY), 2) ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="book-action-section">
                                                <?php if ($book['days_overdue'] > 0): ?>
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        This book is <?= $book['days_overdue'] ?> day(s) overdue. 
                                                        Fine: $<?= number_format($book['days_overdue'] * FINE_PER_DAY, 2) ?>
                                                    </div>
                                                <?php endif; ?>

                                                <div class="book-actions">
                                                    <button class="btn btn-primary" 
                                                            onclick="confirmReturn(<?= $book['issue_id'] ?>, '<?= htmlspecialchars($book['title']) ?>', '<?= htmlspecialchars($book['name']) ?>', <?= max(0, $book['days_overdue'] * FINE_PER_DAY) ?>)">
                                                        <i class="fas fa-undo"></i> Return Book
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Return Confirmation Modal -->
    <div id="returnModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fas fa-undo"></i> Confirm Book Return
                </h3>
            </div>
            <div class="modal-body">
                <div id="returnDetails"></div>
            </div>
            <div class="modal-footer">
                <form method="POST" class="modal-form">
                    <input type="hidden" name="issue_id" id="returnIssueId">
                    <div class="modal-buttons">
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="return_book" class="btn btn-primary">
                            <i class="fas fa-check"></i> Confirm Return
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Search and Filter functionality
        function filterBooks() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const bookItems = document.querySelectorAll('.book-item');
            
            let visibleCount = 0;
            
            bookItems.forEach(item => {
                const title = item.dataset.title;
                const user = item.dataset.user;
                const status = item.dataset.status;
                
                const matchesSearch = title.includes(searchTerm) || user.includes(searchTerm);
                const matchesStatus = statusFilter === 'all' || status === statusFilter;
                
                if (matchesSearch && matchesStatus) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Show/hide empty state
            const emptyState = document.querySelector('.empty-state');
            const booksContainer = document.querySelector('.books-container');
            
            if (visibleCount === 0 && !emptyState && booksContainer) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty-state';
                emptyDiv.innerHTML = `
                    <div class="empty-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>No Books Found</h3>
                    <p>No books match your current search criteria.</p>
                `;
                booksContainer.appendChild(emptyDiv);
            } else if (visibleCount > 0) {
                const existingEmpty = document.querySelector('.empty-state');
                if (existingEmpty && existingEmpty.innerHTML.includes('No Books Found')) {
                    existingEmpty.remove();
                }
            }
        }
        
        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = 'all';
            filterBooks();
        }
        
        // Return confirmation
        function confirmReturn(issueId, bookTitle, userName, fineAmount) {
            document.getElementById('returnIssueId').value = issueId;
            
            const fineText = fineAmount > 0 ? 
                `<div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Fine Amount: $${fineAmount.toFixed(2)}</strong>
                </div>` : '';
            
            document.getElementById('returnDetails').innerHTML = `
                <div class="return-details">
                    <p><strong>Book:</strong> ${bookTitle}</p>
                    <p><strong>User:</strong> ${userName}</p>
                    <p><strong>Return Date:</strong> ${new Date().toLocaleDateString()}</p>
                    ${fineText}
                    <p>Are you sure you want to process this return?</p>
                </div>
            `;
            
            document.getElementById('returnModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('returnModal').style.display = 'none';
        }
        
        // Event listeners
        document.getElementById('searchInput')?.addEventListener('input', filterBooks);
        document.getElementById('statusFilter')?.addEventListener('change', filterBooks);
        
        // Close modal on outside click
        document.getElementById('returnModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Auto-hide success messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
