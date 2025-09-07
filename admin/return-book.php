<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

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
    <title>Return Books - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .return-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
        }
        
        .stat-card.overdue { border-color: #e74c3c; }
        .stat-card.due-soon { border-color: #f39c12; }
        .stat-card.normal { border-color: #2ecc71; }
        .stat-card.total { border-color: #3498db; }
        
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stat-card p {
            margin: 0;
            color: #666;
        }
        
        .search-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .search-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .filter-select {
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            background: white;
            font-size: 1rem;
            min-width: 150px;
        }
        
        .books-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        .book-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-left: 6px solid;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .book-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .book-card.overdue { border-color: #e74c3c; }
        .book-card.due-soon { border-color: #f39c12; }
        .book-card.normal { border-color: #2ecc71; }
        
        .book-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .book-info {
            flex: 1;
        }
        
        .book-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 0 0 0.5rem 0;
        }
        
        .book-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .issue-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .detail-item {
            text-align: center;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }
        
        .detail-value {
            font-weight: bold;
            font-size: 1rem;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-overdue {
            background: #e74c3c;
            color: white;
        }
        
        .status-due-soon {
            background: #f39c12;
            color: white;
        }
        
        .status-normal {
            background: #2ecc71;
            color: white;
        }
        
        .fine-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 0.75rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: center;
            font-weight: bold;
        }
        
        .return-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
            margin-top: 1rem;
        }
        
        .return-button:hover {
            transform: translateY(-1px);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .search-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input, .filter-select {
                min-width: auto;
            }
            
            .issue-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="admin-nav">
        <div class="nav-brand">
            <i class="fas fa-book"></i>
            <span>Library Admin</span>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="books.php"><i class="fas fa-book"></i> Books</a>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
            <a href="issue-book.php"><i class="fas fa-hand-holding"></i> Issue</a>
            <a href="return-book.php" class="active"><i class="fas fa-undo"></i> Return</a>
            <a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        <div class="nav-toggle">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <div class="main-content">
        <div class="return-container">
            <!-- Header Section -->
            <div class="header-section">
                <h1><i class="fas fa-undo"></i> Return Books</h1>
                <p>Process book returns and manage fines</p>
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

            <div class="stats-grid">
                <div class="stat-card overdue">
                    <h3><?= $overdue_count ?></h3>
                    <p><i class="fas fa-exclamation-triangle"></i> Overdue Books</p>
                </div>
                <div class="stat-card due-soon">
                    <h3><?= $due_soon_count ?></h3>
                    <p><i class="fas fa-clock"></i> Due Soon</p>
                </div>
                <div class="stat-card normal">
                    <h3><?= $normal_count ?></h3>
                    <p><i class="fas fa-check"></i> Normal</p>
                </div>
                <div class="stat-card total">
                    <h3>$<?= number_format($total_fine, 2) ?></h3>
                    <p><i class="fas fa-dollar-sign"></i> Total Fines</p>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="search-bar">
                <div class="search-controls">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search by book title, user name, or email...">
                    <select id="statusFilter" class="filter-select">
                        <option value="all">All Books</option>
                        <option value="overdue">Overdue</option>
                        <option value="due_soon">Due Soon</option>
                        <option value="normal">Normal</option>
                    </select>
                    <button onclick="clearFilters()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>

            <!-- Books List -->
            <div class="books-grid" id="booksGrid">
                <?php if (empty($issued_books)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <h3>No Issued Books</h3>
                        <p>There are currently no books issued that need to be returned.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($issued_books as $book): ?>
                        <div class="book-card <?= $book['status_class'] ?>" 
                             data-title="<?= strtolower($book['title']) ?>"
                             data-user="<?= strtolower($book['name'] . ' ' . $book['email']) ?>"
                             data-status="<?= $book['status_class'] ?>">
                            
                            <div class="book-header">
                                <div class="book-info">
                                    <h3 class="book-title"><?= htmlspecialchars($book['title']) ?></h3>
                                    <div class="book-meta">
                                        <i class="fas fa-barcode"></i> ISBN: <?= htmlspecialchars($book['isbn']) ?>
                                    </div>
                                    <div class="book-meta">
                                        <i class="fas fa-user"></i> <?= htmlspecialchars($book['name']) ?> (<?= htmlspecialchars($book['email']) ?>)
                                    </div>
                                </div>
                                <span class="status-badge status-<?= $book['status_class'] ?>">
                                    <?php if ($book['status_class'] === 'overdue'): ?>
                                        <i class="fas fa-exclamation-triangle"></i> Overdue
                                    <?php elseif ($book['status_class'] === 'due_soon'): ?>
                                        <i class="fas fa-clock"></i> Due Soon
                                    <?php else: ?>
                                        <i class="fas fa-check"></i> Normal
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="issue-details">
                                <div class="detail-item">
                                    <div class="detail-label">Issue Date</div>
                                    <div class="detail-value"><?= date('M d, Y', strtotime($book['issue_date'])) ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Due Date</div>
                                    <div class="detail-value"><?= date('M d, Y', strtotime($book['due_date'])) ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Days <?= $book['days_overdue'] > 0 ? 'Overdue' : 'Remaining' ?></div>
                                    <div class="detail-value">
                                        <?= $book['days_overdue'] > 0 ? $book['days_overdue'] : abs($book['days_overdue']) ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Fine Amount</div>
                                    <div class="detail-value">
                                        $<?= number_format(max(0, $book['days_overdue'] * FINE_PER_DAY), 2) ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($book['days_overdue'] > 0): ?>
                                <div class="fine-info">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    This book is <?= $book['days_overdue'] ?> day(s) overdue. 
                                    Fine: $<?= number_format($book['days_overdue'] * FINE_PER_DAY, 2) ?>
                                </div>
                            <?php endif; ?>

                            <button class="return-button" 
                                    onclick="confirmReturn(<?= $book['issue_id'] ?>, '<?= htmlspecialchars($book['title']) ?>', '<?= htmlspecialchars($book['name']) ?>', <?= max(0, $book['days_overdue'] * FINE_PER_DAY) ?>)">
                                <i class="fas fa-undo"></i> Return This Book
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Return Confirmation Modal -->
    <div id="returnModal" class="modal">
        <div class="modal-content">
            <h2><i class="fas fa-undo"></i> Confirm Book Return</h2>
            <div id="returnDetails"></div>
            <form method="POST" style="margin-top: 1.5rem;">
                <input type="hidden" name="issue_id" id="returnIssueId">
                <div style="display: flex; gap: 1rem; justify-content: center;">
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

    <script src="../assets/js/script.js"></script>
    <script>
        // Search and Filter functionality
        function filterBooks() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const bookCards = document.querySelectorAll('.book-card');
            
            let visibleCount = 0;
            
            bookCards.forEach(card => {
                const title = card.dataset.title;
                const user = card.dataset.user;
                const status = card.dataset.status;
                
                const matchesSearch = title.includes(searchTerm) || user.includes(searchTerm);
                const matchesStatus = statusFilter === 'all' || status === statusFilter;
                
                if (matchesSearch && matchesStatus) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide empty state
            const emptyState = document.querySelector('.empty-state');
            const booksGrid = document.getElementById('booksGrid');
            
            if (visibleCount === 0 && !emptyState) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'empty-state';
                emptyDiv.innerHTML = `
                    <i class="fas fa-search"></i>
                    <h3>No Books Found</h3>
                    <p>No books match your current search criteria.</p>
                `;
                booksGrid.appendChild(emptyDiv);
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
                `<div class="fine-info" style="margin: 1rem 0;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Fine Amount: $${fineAmount.toFixed(2)}</strong>
                </div>` : '';
            
            document.getElementById('returnDetails').innerHTML = `
                <p><strong>Book:</strong> ${bookTitle}</p>
                <p><strong>User:</strong> ${userName}</p>
                <p><strong>Return Date:</strong> ${new Date().toLocaleDateString()}</p>
                ${fineText}
                <p>Are you sure you want to process this return?</p>
            `;
            
            document.getElementById('returnModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('returnModal').style.display = 'none';
        }
        
        // Event listeners
        document.getElementById('searchInput').addEventListener('input', filterBooks);
        document.getElementById('statusFilter').addEventListener('change', filterBooks);
        
        // Close modal on outside click
        document.getElementById('returnModal').addEventListener('click', function(e) {
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
