<?php
/**
 * Admin Dashboard
 * Library Management System
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Get dashboard statistics
$stats = getDashboardStats();

// Get recent activities
$recentIssues = getAllIssuedBooks('issued');
$overdueBooks = getOverdueBooks();

// Limit recent activities for dashboard
$recentIssues = array_slice($recentIssues, 0, 5);
$overdueBooks = array_slice($overdueBooks, 0, 5);

$pageTitle = 'Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a href="dashboard.php" class="nav-link active">
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
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">
                    <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                </h1>
                <p class="dashboard-subtitle">Welcome back! Here's an overview of your library system.</p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card books">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $stats['total_books'] ?? 0; ?></h3>
                        <p class="stat-label">Total Books</p>
                    </div>
                </div>

                <div class="stat-card users">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $stats['total_users'] ?? 0; ?></h3>
                        <p class="stat-label">Registered Users</p>
                    </div>
                </div>

                <div class="stat-card issued">
                    <div class="stat-icon">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $stats['issued_books'] ?? 0; ?></h3>
                        <p class="stat-label">Books Issued</p>
                    </div>
                </div>

                <div class="stat-card overdue">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <h3 class="stat-number"><?php echo $stats['overdue_books'] ?? 0; ?></h3>
                        <p class="stat-label">Overdue Books</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-section">
                <h2 class="section-title">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h2>
                <div class="quick-actions-grid">
                    <a href="books.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="action-content">
                            <h4>Add New Book</h4>
                            <p>Add books to the library</p>
                        </div>
                    </a>

                    <a href="users.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="action-content">
                            <h4>Register User</h4>
                            <p>Add new library member</p>
                        </div>
                    </a>

                    <a href="issue-book.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-hand-holding"></i>
                        </div>
                        <div class="action-content">
                            <h4>Issue Book</h4>
                            <p>Issue book to member</p>
                        </div>
                    </a>

                    <a href="return-book.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-undo"></i>
                        </div>
                        <div class="action-content">
                            <h4>Return Book</h4>
                            <p>Process book returns</p>
                        </div>
                    </a>

                    <a href="reports.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="action-content">
                            <h4>View Reports</h4>
                            <p>Generate system reports</p>
                        </div>
                    </a>

                    <a href="settings.php" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div class="action-content">
                            <h4>System Settings</h4>
                            <p>Configure library settings</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="two-column-grid">
                <!-- Recent Book Issues -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="content-card-title">
                            <i class="fas fa-clock"></i> Recent Book Issues
                        </h3>
                    </div>
                    <div class="content-card-body">
                        <?php if (empty($recentIssues)): ?>
                            <div class="empty-state">
                                <i class="fas fa-book-open"></i>
                                <p>No recent book issues</p>
                            </div>
                        <?php else: ?>
                            <div class="recent-issues-list">
                                <?php foreach ($recentIssues as $issue): ?>
                                    <div class="recent-issue-item">
                                        <div class="issue-info">
                                            <strong><?php echo htmlspecialchars($issue['book_title'] ?? 'Unknown Book'); ?></strong><br>
                                            <small class="text-muted">
                                                Issued to <?php echo htmlspecialchars($issue['user_name'] ?? 'Unknown User'); ?> 
                                                on <?php echo date('M j, Y', strtotime($issue['issue_date'] ?? 'now')); ?>
                                            </small>
                                        </div>
                                        <span class="badge badge-<?php echo ($issue['status'] ?? 'issued') === 'issued' ? 'primary' : 'success'; ?>">
                                            <?php echo ucfirst($issue['status'] ?? 'Issued'); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Overdue Books -->
                <div class="content-card">
                    <div class="content-card-header">
                        <h3 class="content-card-title">
                            <i class="fas fa-exclamation-triangle"></i> Overdue Books
                        </h3>
                    </div>
                    <div class="content-card-body">
                        <?php if (empty($overdueBooks)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p>No overdue books</p>
                            </div>
                        <?php else: ?>
                            <div class="overdue-books-list">
                                <?php foreach ($overdueBooks as $book): ?>
                                    <div class="overdue-book-item">
                                        <div class="book-info">
                                            <strong><?php echo htmlspecialchars($book['book_title'] ?? 'Unknown Book'); ?></strong><br>
                                            <small class="text-muted">
                                                Due: <?php echo date('M j, Y', strtotime($book['due_date'] ?? 'now')); ?>
                                                (<?php echo abs($book['days_overdue'] ?? 0); ?> days overdue)
                                            </small>
                                        </div>
                                        <span class="badge badge-danger">Overdue</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="system-info">
                <h3><i class="fas fa-info-circle"></i> System Information</h3>
                <div class="info-grid">
                    <div class="info-section">
                        <h5>Library Settings</h5>
                        <ul class="info-list">
                            <li><strong>Max Books per User:</strong> <span class="info-value">3</span></li>
                            <li><strong>Default Issue Days:</strong> <span class="info-value">14</span></li>
                            <li><strong>Fine per Day:</strong> <span class="info-value">$2.00</span></li>
                        </ul>
                    </div>
                    <div class="info-section">
                        <h5>Quick Stats</h5>
                        <ul class="info-list">
                            <li><strong>Available Books:</strong> <span class="info-value"><?php echo getTotalAvailableBooks(); ?></span></li>
                            <li><strong>Books in Circulation:</strong> <span class="info-value"><?php echo getBooksInCirculation(); ?></span></li>
                            <li><strong>System Health:</strong> <span class="info-value text-success">Good</span></li>
                        </ul>
                    </div>
                    <div class="info-section">
                        <h5>System Status</h5>
                        <ul class="info-list">
                            <li><strong>Database:</strong> <span class="info-value text-success">Connected</span></li>
                            <li><strong>Last Login:</strong> <span class="info-value"><?php echo date('M j, Y g:i A'); ?></span></li>
                            <li><strong>System Health:</strong> <span class="info-value text-success">Good</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin-dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers to stat cards for navigation
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('click', function() {
                    if (this.classList.contains('books')) {
                        window.location.href = 'books.php';
                    } else if (this.classList.contains('users')) {
                        window.location.href = 'users.php';
                    } else if (this.classList.contains('issued')) {
                        window.location.href = 'issued-books.php';
                    } else if (this.classList.contains('overdue')) {
                        window.location.href = 'overdue-books.php';
                    }
                });
                
                card.style.cursor = 'pointer';
                card.title = 'Click to view details';
            });
        });
    </script>
</body>
</html>
