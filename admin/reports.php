<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

// Initialize database connection
$pdo = getDB();

// Handle report generation
$report_type = $_GET['type'] ?? 'overview';
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today

// Generate reports based on type
$report_data = [];
$report_title = '';
$chart_data = [];

switch ($report_type) {
    case 'overview':
        $report_title = 'Library Overview Report';
        
        // Get total statistics
        $stmt = $pdo->query("SELECT COUNT(*) as total_books FROM books");
        $total_books = $stmt->fetch(PDO::FETCH_ASSOC)['total_books'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
        $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as currently_issued FROM issued_books WHERE status = 'issued'");
        $currently_issued = $stmt->fetch(PDO::FETCH_ASSOC)['currently_issued'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as total_issued FROM issued_books");
        $total_issued = $stmt->fetch(PDO::FETCH_ASSOC)['total_issued'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as overdue_books FROM issued_books WHERE status = 'issued' AND due_date < NOW()");
        $overdue_books = $stmt->fetch(PDO::FETCH_ASSOC)['overdue_books'];
        
        $stmt = $pdo->query("SELECT SUM(fine_amount) as total_fines FROM issued_books WHERE fine_amount > 0");
        $total_fines = $stmt->fetch(PDO::FETCH_ASSOC)['total_fines'] ?? 0;
        
        // Popular books
        $stmt = $pdo->query("SELECT b.title, COUNT(ib.book_id) as issue_count 
                            FROM books b 
                            LEFT JOIN issued_books ib ON b.book_id = ib.book_id 
                            GROUP BY b.book_id 
                            ORDER BY issue_count DESC 
                            LIMIT 5");
        $popular_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Category distribution
        $stmt = $pdo->query("SELECT b.category as category_name, COUNT(b.book_id) as book_count 
                            FROM books b 
                            GROUP BY b.category 
                            ORDER BY book_count DESC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $report_data = [
            'stats' => [
                'total_books' => $total_books,
                'total_users' => $total_users,
                'currently_issued' => $currently_issued,
                'total_issued' => $total_issued,
                'overdue_books' => $overdue_books,
                'total_fines' => $total_fines
            ],
            'popular_books' => $popular_books,
            'categories' => $categories
        ];
        break;
        
    case 'issued_books':
        $report_title = 'Issued Books Report';
        
        $stmt = $pdo->prepare("SELECT ib.*, b.title, b.isbn, b.author, u.name, u.email,
                              DATEDIFF(NOW(), ib.due_date) as days_overdue
                              FROM issued_books ib
                              JOIN books b ON ib.book_id = b.book_id
                              JOIN users u ON ib.user_id = u.user_id
                              WHERE ib.issue_date BETWEEN ? AND ?
                              ORDER BY ib.issue_date DESC");
        $stmt->execute([$date_from, $date_to]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'returned_books':
        $report_title = 'Returned Books Report';
        
        $stmt = $pdo->prepare("SELECT ib.*, b.title, b.isbn, b.author, u.name, u.email,
                              DATEDIFF(ib.return_date, ib.due_date) as days_late
                              FROM issued_books ib
                              JOIN books b ON ib.book_id = b.book_id
                              JOIN users u ON ib.user_id = u.user_id
                              WHERE ib.status = 'returned' 
                              AND ib.return_date BETWEEN ? AND ?
                              ORDER BY ib.return_date DESC");
        $stmt->execute([$date_from, $date_to]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'overdue_books':
        $report_title = 'Overdue Books Report';
        
        $stmt = $pdo->query("SELECT ib.*, b.title, b.isbn, b.author, u.name, u.email, u.phone,
                            DATEDIFF(NOW(), ib.due_date) as days_overdue,
                            (DATEDIFF(NOW(), ib.due_date) * " . FINE_PER_DAY . ") as fine_amount
                            FROM issued_books ib
                            JOIN books b ON ib.book_id = b.book_id
                            JOIN users u ON ib.user_id = u.user_id
                            WHERE ib.status = 'issued' AND ib.due_date < NOW()
                            ORDER BY days_overdue DESC");
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'user_activity':
        $report_title = 'User Activity Report';
        
        $stmt = $pdo->prepare("SELECT u.name, u.email, u.registration_date,
                              COUNT(ib.issue_id) as total_issued,
                              COUNT(CASE WHEN ib.status = 'issued' THEN 1 END) as currently_issued,
                              COUNT(CASE WHEN ib.status = 'returned' THEN 1 END) as total_returned,
                              COUNT(CASE WHEN ib.status = 'issued' AND ib.due_date < NOW() THEN 1 END) as overdue_count,
                              COALESCE(SUM(ib.fine_amount), 0) as total_fines
                              FROM users u
                              LEFT JOIN issued_books ib ON u.user_id = ib.user_id
                              GROUP BY u.user_id
                              ORDER BY total_issued DESC");
        $stmt->execute();
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'popular_books':
        $report_title = 'Popular Books Report';
        
        $stmt = $pdo->prepare("SELECT b.title, b.author, b.isbn, b.category as category_name,
                              COUNT(ib.issue_id) as issue_count,
                              COUNT(CASE WHEN ib.issue_date BETWEEN ? AND ? THEN 1 END) as recent_issues,
                              b.quantity, 
                              (b.quantity - COUNT(CASE WHEN ib.status = 'issued' THEN 1 END)) as available_copies
                              FROM books b
                              LEFT JOIN issued_books ib ON b.book_id = ib.book_id
                              GROUP BY b.book_id
                              ORDER BY issue_count DESC");
        $stmt->execute([$date_from, $date_to]);
        $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'financial':
        $report_title = 'Financial Report';
        
        // Monthly fine collection
        $stmt = $pdo->prepare("SELECT DATE_FORMAT(return_date, '%Y-%m') as month,
                              SUM(fine_amount) as monthly_fines,
                              COUNT(*) as returns_with_fines
                              FROM issued_books 
                              WHERE status = 'returned' 
                              AND fine_amount > 0 
                              AND return_date BETWEEN ? AND ?
                              GROUP BY DATE_FORMAT(return_date, '%Y-%m')
                              ORDER BY month DESC");
        $stmt->execute([$date_from, $date_to]);
        $monthly_fines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Outstanding fines
        $stmt = $pdo->query("SELECT u.name, u.email, u.phone,
                            SUM(DATEDIFF(NOW(), ib.due_date) * " . FINE_PER_DAY . ") as outstanding_fine,
                            COUNT(*) as overdue_books
                            FROM issued_books ib
                            JOIN users u ON ib.user_id = u.user_id
                            WHERE ib.status = 'issued' AND ib.due_date < NOW()
                            GROUP BY u.user_id
                            ORDER BY outstanding_fine DESC");
        $outstanding_fines = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $report_data = [
            'monthly_fines' => $monthly_fines,
            'outstanding_fines' => $outstanding_fines
        ];
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="return-book.php" class="nav-link">
                        <i class="fas fa-undo"></i> Return Book
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link active">
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
            <div class="dashboard-header" >
                <h1 class="dashboard-title" >
                    <i class="fas fa-chart-bar"></i> Library Reports
                </h1>
                <p class="dashboard-subtitle" style="margin-bottom: 0;">Generate comprehensive reports and analytics</p>
            </div>

            <!-- Report Controls -->
            <div class="admin-card" >
                <div class="card-body">
                    <form method="GET" id="reportForm">
                        <div class="form-row" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                            <div class="form-group" style="flex: 1; min-width: 200px;">
                                <label class="form-label">Select Report:</label>
                                <select name="type" class="form-control" onchange="this.form.submit()">
                                    <option value="overview" <?= $report_type === 'overview' ? 'selected' : '' ?>>Library Overview</option>
                                    <option value="issued_books" <?= $report_type === 'issued_books' ? 'selected' : '' ?>>Issued Books</option>
                                    <option value="returned_books" <?= $report_type === 'returned_books' ? 'selected' : '' ?>>Returned Books</option>
                                    <option value="overdue_books" <?= $report_type === 'overdue_books' ? 'selected' : '' ?>>Overdue Books</option>
                                    <option value="user_activity" <?= $report_type === 'user_activity' ? 'selected' : '' ?>>User Activity</option>
                                    <option value="popular_books" <?= $report_type === 'popular_books' ? 'selected' : '' ?>>Popular Books</option>
                                    <option value="financial" <?= $report_type === 'financial' ? 'selected' : '' ?>>Financial Report</option>
                                </select>
                            </div>
                            
                            <?php if (in_array($report_type, ['issued_books', 'returned_books', 'popular_books', 'financial'])): ?>
                            <div class="form-group" style="flex: 0 0 auto; min-width: 160px;">
                                <label class="form-label">From Date:</label>
                                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
                            </div>
                            <div class="form-group" style="flex: 0 0 auto; min-width: 160px;">
                                <label class="form-label">To Date:</label>
                                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
                            </div>
                            <div class="form-group" style="flex: 0 0 auto; margin: auto;">
                                <button type="submit" class="btn btn-primary" style="padding: 18px 20px;">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </form>
            <!-- Report Content -->
            <div class="admin-card">
                <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-radius: 10px">
                    <h3 style="margin: 0;"><?= htmlspecialchars($report_title) ?></h3>
                    <button class="btn btn-secondary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($report_type === 'overview'): ?>
                        <!-- Overview Report -->
                        <div class="stats-grid">
                            <div class="stat-card books">
                                <div class="stat-icon">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number"><?= number_format($report_data['stats']['total_books']) ?></h3>
                                    <p class="stat-label">Total Books</p>
                                </div>
                            </div>

                            <div class="stat-card users">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number"><?= number_format($report_data['stats']['total_users']) ?></h3>
                                    <p class="stat-label">Total Users</p>
                                </div>
                            </div>

                            <div class="stat-card issued">
                                <div class="stat-icon">
                                    <i class="fas fa-hand-holding"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number"><?= number_format($report_data['stats']['currently_issued']) ?></h3>
                                    <p class="stat-label">Currently Issued</p>
                                </div>
                            </div>

                            <div class="stat-card overdue">
                                <div class="stat-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="stat-content">
                                    <h3 class="stat-number"><?= number_format($report_data['stats']['overdue_books']) ?></h3>
                                    <p class="stat-label">Overdue Books</p>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h4>Popular Books</h4>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Book Title</th>
                                                <th>Issues</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data['popular_books'] as $book): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($book['title']) ?></td>
                                                <td><span class="badge badge-primary"><?= number_format($book['issue_count']) ?></span></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h4>Book Categories</h4>
                                <canvas id="categoryChart" width="300" height="200"></canvas>
                            </div>
                        </div>

                    <?php elseif ($report_type === 'overdue_books'): ?>
                        <!-- Overdue Books Report -->
                        <?php if (empty($report_data)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h4>No Overdue Books</h4>
                                <p class="text-muted">Great! There are currently no overdue books.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>User</th>
                                            <th>Contact</th>
                                            <th>Due Date</th>
                                            <th>Days Overdue</th>
                                            <th>Fine Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $record): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($record['title']) ?></strong><br>
                                                <small class="text-muted">ISBN: <?= htmlspecialchars($record['isbn']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($record['name']) ?></td>
                                            <td>
                                                <?= htmlspecialchars($record['email']) ?><br>
                                                <?php if ($record['phone']): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($record['phone']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($record['due_date'])) ?></td>
                                            <td>
                                                <span class="badge badge-danger"><?= $record['days_overdue'] ?> days</span>
                                            </td>
                                            <td>
                                                <span class="text-danger font-weight-bold">$<?= number_format($record['fine_amount'], 2) ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($report_type === 'issued_books'): ?>
                        <!-- Issued Books Report -->
                        <?php if (empty($report_data)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                                <h4>No Data Found</h4>
                                <p class="text-muted">No books were issued in the selected date range.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>User</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th>Days Overdue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $record): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($record['title']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($record['author']) ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($record['name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($record['email']) ?></small>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($record['issue_date'])) ?></td>
                                            <td><?= date('M d, Y', strtotime($record['due_date'])) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $record['status'] === 'issued' ? 'warning' : 'success' ?>">
                                                    <?= ucfirst($record['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (isset($record['days_overdue']) && $record['days_overdue'] > 0): ?>
                                                    <span class="badge badge-danger"><?= $record['days_overdue'] ?> days</span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($report_type === 'returned_books'): ?>
                        <!-- Returned Books Report -->
                        <?php if (empty($report_data)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-undo fa-3x text-muted mb-3"></i>
                                <h4>No Data Found</h4>
                                <p class="text-muted">No books were returned in the selected date range.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>User</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Return Date</th>
                                            <th>Days Late</th>
                                            <th>Fine</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $record): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($record['title']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($record['author']) ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($record['name']) ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($record['email']) ?></small>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($record['issue_date'])) ?></td>
                                            <td><?= date('M d, Y', strtotime($record['due_date'])) ?></td>
                                            <td><?= date('M d, Y', strtotime($record['return_date'])) ?></td>
                                            <td>
                                                <?php if (isset($record['days_late']) && $record['days_late'] > 0): ?>
                                                    <span class="badge badge-warning"><?= $record['days_late'] ?> days</span>
                                                <?php else: ?>
                                                    <span class="text-success">On time</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($record['fine_amount']) && $record['fine_amount'] > 0): ?>
                                                    <span class="text-danger font-weight-bold">$<?= number_format($record['fine_amount'], 2) ?></span>
                                                <?php else: ?>
                                                    $0.00
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($report_type === 'user_activity'): ?>
                        <!-- User Activity Report -->
                        <?php if (empty($report_data)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h4>No Data Found</h4>
                                <p class="text-muted">No user activity data available.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Registration Date</th>
                                            <th>Total Issued</th>
                                            <th>Currently Issued</th>
                                            <th>Returned</th>
                                            <th>Overdue</th>
                                            <th>Total Fines</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $record): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($record['name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($record['email']) ?></small>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($record['registration_date'])) ?></td>
                                            <td><span class="badge badge-primary"><?= number_format($record['total_issued']) ?></span></td>
                                            <td><span class="badge badge-warning"><?= number_format($record['currently_issued']) ?></span></td>
                                            <td><span class="badge badge-success"><?= number_format($record['total_returned']) ?></span></td>
                                            <td>
                                                <?php if ($record['overdue_count'] > 0): ?>
                                                    <span class="badge badge-danger"><?= $record['overdue_count'] ?></span>
                                                <?php else: ?>
                                                    0
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($record['total_fines'] > 0): ?>
                                                    <span class="text-danger font-weight-bold">$<?= number_format($record['total_fines'], 2) ?></span>
                                                <?php else: ?>
                                                    $0.00
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($report_type === 'popular_books'): ?>
                        <!-- Popular Books Report -->
                        <?php if (empty($report_data)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                <h4>No Data Found</h4>
                                <p class="text-muted">No popular books data available.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>Author</th>
                                            <th>Category</th>
                                            <th>Total Issues</th>
                                            <th>Recent Issues</th>
                                            <th>Available Copies</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data as $record): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($record['title']) ?></strong><br>
                                                <small class="text-muted">ISBN: <?= htmlspecialchars($record['isbn'] ?? 'N/A') ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($record['author']) ?></td>
                                            <td><?= htmlspecialchars($record['category_name'] ?? 'Uncategorized') ?></td>
                                            <td><span class="badge badge-primary"><?= number_format($record['issue_count']) ?></span></td>
                                            <td><span class="badge badge-info"><?= number_format($record['recent_issues'] ?? 0) ?></span></td>
                                            <td><span class="badge badge-success"><?= number_format($record['available_copies'] ?? 0) ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    <?php elseif ($report_type === 'financial'): ?>
                        <!-- Financial Report -->
                        <div class="row">
                            <!-- Monthly Fines -->
                            <div class="col-md-6">
                                <h4>Monthly Fine Collection</h4>
                                <?php if (empty($report_data['monthly_fines'])): ?>
                                    <p class="text-muted">No fines collected in the selected period.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Month</th>
                                                    <th>Amount</th>
                                                    <th>Returns</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($report_data['monthly_fines'] as $fine): ?>
                                                <tr>
                                                    <td><?= date('F Y', strtotime($fine['month'] . '-01')) ?></td>
                                                    <td><span class="text-success font-weight-bold">$<?= number_format($fine['monthly_fines'], 2) ?></span></td>
                                                    <td><?= number_format($fine['returns_with_fines']) ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Outstanding Fines -->
                            <div class="col-md-6">
                                <h4>Outstanding Fines</h4>
                                <?php if (empty($report_data['outstanding_fines'])): ?>
                                    <p class="text-muted">No outstanding fines.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Overdue Books</th>
                                                    <th>Fine Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($report_data['outstanding_fines'] as $fine): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($fine['name']) ?></strong><br>
                                                        <small class="text-muted"><?= htmlspecialchars($fine['email']) ?></small>
                                                    </td>
                                                    <td><span class="badge badge-warning"><?= number_format($fine['overdue_books']) ?></span></td>
                                                    <td><span class="text-danger font-weight-bold">$<?= number_format($fine['outstanding_fine'], 2) ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Other Reports - Generic Table -->
                        <?php if (empty($report_data)): ?>
                            <div class="empty-state text-center py-5">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <h4>No Data Found</h4>
                                <p class="text-muted">No data available for the selected criteria.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tbody>
                                        <?php foreach ($report_data as $record): ?>
                                        <tr>
                                            <td><?= json_encode($record) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        function setReportType(type) {
            document.getElementById('reportType').value = type;
            document.getElementById('reportForm').submit();
        }
        
        // Chart for overview page
        <?php if ($report_type === 'overview' && !empty($report_data['categories'])): ?>
        const categoryData = <?= json_encode($report_data['categories']) ?>;
        const ctx = document.getElementById('categoryChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(cat => cat.category_name),
                datasets: [{
                    data: categoryData.map(cat => cat.book_count),
                    backgroundColor: [
                        '#007bff', '#28a745', '#dc3545', '#ffc107', 
                        '#17a2b8', '#6f42c1', '#fd7e14', '#20c997'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
