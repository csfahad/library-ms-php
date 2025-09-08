<?php
/**
 * Student My Books Page
 * Library Management System
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

// Get current user
$currentUser = getCurrentUser();

// Get currently issued books for this user
$issuedBooks = getUserIssuedBooks($currentUser['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Books - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="navbar-content">
                <a href="dashboard.php" class="navbar-brand">
                    <div class="logo">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <?php echo htmlspecialchars(getSetting('library_name', 'Library MS')); ?>
                </a>
                <ul class="navbar-nav">
                    <li><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="search-books.php" class="nav-link"><i class="fas fa-search"></i> Search Books</a></li>
                    <li><a href="my-books.php" class="nav-link active"><i class="fas fa-book"></i> My Books</a></li>
                    <li><a href="history.php" class="nav-link"><i class="fas fa-history"></i> History</a></li>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../includes/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">My Books</h1>
                <p class="page-subtitle">Books currently borrowed by you</p>
            </div>

            <?php if (empty($issuedBooks)): ?>
                <!-- No books message -->
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-book-open text-muted mb-3" style="font-size: 3rem;"></i>
                        <h3 class="text-muted">No books borrowed</h3>
                        <p class="text-secondary">You haven't borrowed any books yet. Browse our collection to find something interesting!</p>
                        <a href="search-books.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse Books
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Books Summary -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <div class="stat-number text-primary"><?php echo count($issuedBooks); ?></div>
                                <div class="stat-label">Books Borrowed</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <?php 
                                $overdueCount = 0;
                                foreach ($issuedBooks as $book) {
                                    if (strtotime($book['due_date']) < time() && $book['status'] === 'issued') {
                                        $overdueCount++;
                                    }
                                }
                                ?>
                                <div class="stat-number <?php echo $overdueCount > 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo $overdueCount; ?>
                                </div>
                                <div class="stat-label">Overdue Books</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Books -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Currently Borrowed Books</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Author</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Fine</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($issuedBooks as $book): ?>
                                    <?php
                                    $isOverdue = strtotime($book['due_date']) < time() && $book['status'] === 'issued';
                                    $daysOverdue = $isOverdue ? floor((time() - strtotime($book['due_date'])) / (60*60*24)) : 0;
                                    $fine = $daysOverdue * 2; // $2 per day fine
                                    ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                                <?php if (!empty($book['isbn'])): ?>
                                                    <br><small class="text-muted">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($book['due_date'])); ?>
                                            <?php if ($isOverdue): ?>
                                                <br><small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> <?php echo $daysOverdue; ?> day(s) overdue
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($book['status'] === 'issued'): ?>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge badge-danger">Overdue</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php endif; ?>
                                            <?php elseif ($book['status'] === 'returned'): ?>
                                                <span class="badge badge-secondary">Returned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($fine > 0): ?>
                                                <span class="text-danger">$<?php echo number_format($fine, 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-success">$0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($book['status'] === 'issued'): ?>
                                                <button class="btn btn-warning btn-sm" onclick="renewBook(<?php echo $book['issue_id']; ?>)">
                                                    <i class="fas fa-redo"></i> Renew
                                                </button>
                                                <button class="btn btn-danger btn-sm ml-2" onclick="returnBook(<?php echo $book['issue_id']; ?>)">
                                                    <i class="fas fa-arrow-left"></i> Return
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Renewal Policy -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle"></i> Library Policies
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Borrowing Period</h5>
                                <ul class="text-secondary">
                                    <li>Regular books: <?php echo getSetting('issue_duration_days', '14'); ?> days</li>
                                    <li>Reference books: 7 days</li>
                                    <li>Magazines: 3 days</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Renewal Policy</h5>
                                <ul class="text-secondary">
                                    <li>Books can be renewed up to <?php echo getSetting('renewal_limit', '2'); ?> times</li>
                                    <li>No renewal for overdue books</li>
                                    <li>Renewal period: Same as original borrowing period</li>
                                </ul>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h5>Fine Structure</h5>
                                <ul class="text-secondary">
                                    <li>Overdue fine: $<?php echo number_format(getSetting('fine_per_day', '2.00'), 2); ?> per day</li>
                                    <li>Lost book: Full replacement cost</li>
                                    <li>Damaged book: Varies by damage</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Return Policy</h5>
                                <ul class="text-secondary">
                                    <li>Books must be returned by due date</li>
                                    <li>Drop box available 24/7</li>
                                    <li>Online return confirmation available</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function renewBook(issueId) {
            if (confirm('Would you like to renew this book for another 14 days?')) {
                // Here you would make an AJAX request to renew the book
                alert('Book renewal feature will be implemented soon!');
                // location.reload();
            }
        }

        function returnBook(issueId) {
            if (confirm('Are you sure you want to return this book?')) {
                // Here you would make an AJAX request to return the book
                alert('Book return feature will be implemented soon!');
                // location.reload();
            }
        }

        // Add auto-refresh every 30 seconds for real-time updates
        setInterval(function() {
            // You could implement real-time updates here
        }, 30000);
    </script>

    <style>
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .table td {
            vertical-align: middle;
        }

        .btn-sm {
            margin-right: 0.25rem;
        }
    </style>
</body>
</html>
