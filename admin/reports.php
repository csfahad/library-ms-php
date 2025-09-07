<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

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
        $stmt = $pdo->query("SELECT c.category_name, COUNT(b.book_id) as book_count 
                            FROM categories c 
                            LEFT JOIN books b ON c.category_id = b.category_id 
                            GROUP BY c.category_id 
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
        
        $stmt = $pdo->prepare("SELECT b.title, b.author, b.isbn, c.category_name,
                              COUNT(ib.issue_id) as issue_count,
                              COUNT(CASE WHEN ib.issue_date BETWEEN ? AND ? THEN 1 END) as recent_issues,
                              b.quantity, 
                              (b.quantity - COUNT(CASE WHEN ib.status = 'issued' THEN 1 END)) as available_copies
                              FROM books b
                              LEFT JOIN issued_books ib ON b.book_id = ib.book_id
                              LEFT JOIN categories c ON b.category_id = c.category_id
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
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .reports-container {
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
        
        .report-controls {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .control-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }
        
        .control-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .control-group label {
            font-size: 0.875rem;
            color: #666;
            font-weight: 500;
        }
        
        .control-group select,
        .control-group input {
            padding: 0.5rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .report-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .tab-button {
            padding: 0.5rem 1rem;
            border: none;
            background: #f8f9fa;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
        }
        
        .tab-button.active {
            background: #667eea;
            color: white;
        }
        
        .tab-button:hover {
            background: #e9ecef;
        }
        
        .tab-button.active:hover {
            background: #5a6fd8;
        }
        
        .report-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .report-header {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .report-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        
        .report-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-export {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-export.pdf {
            background: #e74c3c;
            color: white;
        }
        
        .btn-export.excel {
            background: #27ae60;
            color: white;
        }
        
        .btn-export.print {
            background: #3498db;
            color: white;
        }
        
        .report-body {
            padding: 1.5rem;
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2rem;
            margin: 0 0 0.5rem 0;
        }
        
        .stat-card p {
            margin: 0;
            opacity: 0.9;
        }
        
        .chart-container {
            margin-bottom: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-issued {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-returned {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .fine-amount {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .control-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .report-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .report-actions {
                width: 100%;
                justify-content: center;
            }
            
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                font-size: 0.875rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.5rem;
            }
        }
        
        @media print {
            .report-controls,
            .report-actions,
            nav {
                display: none !important;
            }
            
            .report-content {
                box-shadow: none;
                border: 1px solid #ddd;
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
            <a href="return-book.php"><i class="fas fa-undo"></i> Return</a>
            <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="../includes/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
        <div class="nav-toggle">
            <i class="fas fa-bars"></i>
        </div>
    </nav>

    <div class="main-content">
        <div class="reports-container">
            <!-- Header Section -->
            <div class="header-section">
                <h1><i class="fas fa-chart-bar"></i> Library Reports</h1>
                <p>Generate comprehensive reports and analytics</p>
            </div>

            <!-- Report Controls -->
            <div class="report-controls">
                <form method="GET" id="reportForm">
                    <div class="report-tabs">
                        <button type="button" class="tab-button <?= $report_type === 'overview' ? 'active' : '' ?>" onclick="setReportType('overview')">
                            <i class="fas fa-chart-pie"></i> Overview
                        </button>
                        <button type="button" class="tab-button <?= $report_type === 'issued_books' ? 'active' : '' ?>" onclick="setReportType('issued_books')">
                            <i class="fas fa-hand-holding"></i> Issued Books
                        </button>
                        <button type="button" class="tab-button <?= $report_type === 'returned_books' ? 'active' : '' ?>" onclick="setReportType('returned_books')">
                            <i class="fas fa-undo"></i> Returned Books
                        </button>
                        <button type="button" class="tab-button <?= $report_type === 'overdue_books' ? 'active' : '' ?>" onclick="setReportType('overdue_books')">
                            <i class="fas fa-exclamation-triangle"></i> Overdue Books
                        </button>
                        <button type="button" class="tab-button <?= $report_type === 'user_activity' ? 'active' : '' ?>" onclick="setReportType('user_activity')">
                            <i class="fas fa-users"></i> User Activity
                        </button>
                        <button type="button" class="tab-button <?= $report_type === 'popular_books' ? 'active' : '' ?>" onclick="setReportType('popular_books')">
                            <i class="fas fa-star"></i> Popular Books
                        </button>
                        <button type="button" class="tab-button <?= $report_type === 'financial' ? 'active' : '' ?>" onclick="setReportType('financial')">
                            <i class="fas fa-dollar-sign"></i> Financial
                        </button>
                    </div>
                    
                    <?php if (in_array($report_type, ['issued_books', 'returned_books', 'popular_books', 'financial'])): ?>
                    <div class="control-row">
                        <div class="control-group">
                            <label for="date_from">From Date:</label>
                            <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        </div>
                        <div class="control-group">
                            <label for="date_to">To Date:</label>
                            <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                        </div>
                        <div class="control-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Generate Report
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <input type="hidden" name="type" id="reportType" value="<?= htmlspecialchars($report_type) ?>">
                </form>
            </div>

            <!-- Report Content -->
            <div class="report-content">
                <div class="report-header">
                    <h2 class="report-title"><?= htmlspecialchars($report_title) ?></h2>
                    <div class="report-actions">
                        <button class="btn-export print" onclick="window.print()">
                            <i class="fas fa-print"></i> Print
                        </button>
                        <button class="btn-export pdf" onclick="exportToPDF()">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                        <button class="btn-export excel" onclick="exportToExcel()">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                    </div>
                </div>

                <div class="report-body">
                    <?php if ($report_type === 'overview'): ?>
                        <!-- Overview Report -->
                        <div class="stats-overview">
                            <div class="stat-card">
                                <h3><?= number_format($report_data['stats']['total_books']) ?></h3>
                                <p><i class="fas fa-book"></i> Total Books</p>
                            </div>
                            <div class="stat-card">
                                <h3><?= number_format($report_data['stats']['total_users']) ?></h3>
                                <p><i class="fas fa-users"></i> Total Users</p>
                            </div>
                            <div class="stat-card">
                                <h3><?= number_format($report_data['stats']['currently_issued']) ?></h3>
                                <p><i class="fas fa-hand-holding"></i> Currently Issued</p>
                            </div>
                            <div class="stat-card">
                                <h3><?= number_format($report_data['stats']['overdue_books']) ?></h3>
                                <p><i class="fas fa-exclamation-triangle"></i> Overdue Books</p>
                            </div>
                            <div class="stat-card">
                                <h3>$<?= number_format($report_data['stats']['total_fines'], 2) ?></h3>
                                <p><i class="fas fa-dollar-sign"></i> Total Fines</p>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
                            <!-- Popular Books -->
                            <div class="chart-container">
                                <h3 class="chart-title">Popular Books</h3>
                                <table class="data-table">
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
                                            <td><?= number_format($book['issue_count']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Categories -->
                            <div class="chart-container">
                                <h3 class="chart-title">Book Categories</h3>
                                <canvas id="categoryChart" width="300" height="200"></canvas>
                            </div>
                        </div>

                    <?php elseif ($report_type === 'issued_books'): ?>
                        <!-- Issued Books Report -->
                        <?php if (empty($report_data)): ?>
                            <div class="empty-state">
                                <i class="fas fa-book-open"></i>
                                <h3>No Data Found</h3>
                                <p>No books were issued in the selected date range.</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
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
                                            <small><?= htmlspecialchars($record['author']) ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($record['name']) ?><br>
                                            <small><?= htmlspecialchars($record['email']) ?></small>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($record['issue_date'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($record['due_date'])) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $record['status'] ?>">
                                                <?= ucfirst($record['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($record['days_overdue'] > 0): ?>
                                                <span class="fine-amount"><?= $record['days_overdue'] ?> days</span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    <?php elseif ($report_type === 'returned_books'): ?>
                        <!-- Returned Books Report -->
                        <?php if (empty($report_data)): ?>
                            <div class="empty-state">
                                <i class="fas fa-undo"></i>
                                <h3>No Data Found</h3>
                                <p>No books were returned in the selected date range.</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
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
                                            <small><?= htmlspecialchars($record['author']) ?></small>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($record['name']) ?><br>
                                            <small><?= htmlspecialchars($record['email']) ?></small>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($record['issue_date'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($record['due_date'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($record['return_date'])) ?></td>
                                        <td>
                                            <?php if ($record['days_late'] > 0): ?>
                                                <span class="fine-amount"><?= $record['days_late'] ?> days</span>
                                            <?php else: ?>
                                                On time
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($record['fine_amount'] > 0): ?>
                                                <span class="fine-amount">$<?= number_format($record['fine_amount'], 2) ?></span>
                                            <?php else: ?>
                                                $0.00
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    <?php elseif ($report_type === 'overdue_books'): ?>
                        <!-- Overdue Books Report -->
                        <?php if (empty($report_data)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <h3>No Overdue Books</h3>
                                <p>Great! There are currently no overdue books.</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
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
                                            <small>ISBN: <?= htmlspecialchars($record['isbn']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($record['name']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($record['email']) ?><br>
                                            <?php if ($record['phone']): ?>
                                                <small><?= htmlspecialchars($record['phone']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($record['due_date'])) ?></td>
                                        <td>
                                            <span class="fine-amount"><?= $record['days_overdue'] ?> days</span>
                                        </td>
                                        <td>
                                            <span class="fine-amount">$<?= number_format($record['fine_amount'], 2) ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>

                    <?php elseif ($report_type === 'user_activity'): ?>
                        <!-- User Activity Report -->
                        <table class="data-table">
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
                                        <small><?= htmlspecialchars($record['email']) ?></small>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($record['registration_date'])) ?></td>
                                    <td><?= number_format($record['total_issued']) ?></td>
                                    <td><?= number_format($record['currently_issued']) ?></td>
                                    <td><?= number_format($record['total_returned']) ?></td>
                                    <td>
                                        <?php if ($record['overdue_count'] > 0): ?>
                                            <span class="fine-amount"><?= $record['overdue_count'] ?></span>
                                        <?php else: ?>
                                            0
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record['total_fines'] > 0): ?>
                                            <span class="fine-amount">$<?= number_format($record['total_fines'], 2) ?></span>
                                        <?php else: ?>
                                            $0.00
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php elseif ($report_type === 'popular_books'): ?>
                        <!-- Popular Books Report -->
                        <table class="data-table">
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
                                        <small>ISBN: <?= htmlspecialchars($record['isbn']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($record['author']) ?></td>
                                    <td><?= htmlspecialchars($record['category_name'] ?? 'Uncategorized') ?></td>
                                    <td><?= number_format($record['issue_count']) ?></td>
                                    <td><?= number_format($record['recent_issues']) ?></td>
                                    <td><?= number_format($record['available_copies']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php elseif ($report_type === 'financial'): ?>
                        <!-- Financial Report -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
                            <!-- Monthly Fines -->
                            <div class="chart-container">
                                <h3 class="chart-title">Monthly Fine Collection</h3>
                                <?php if (empty($report_data['monthly_fines'])): ?>
                                    <p>No fines collected in the selected period.</p>
                                <?php else: ?>
                                    <table class="data-table">
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
                                                <td class="fine-amount">$<?= number_format($fine['monthly_fines'], 2) ?></td>
                                                <td><?= number_format($fine['returns_with_fines']) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>

                            <!-- Outstanding Fines -->
                            <div class="chart-container">
                                <h3 class="chart-title">Outstanding Fines</h3>
                                <?php if (empty($report_data['outstanding_fines'])): ?>
                                    <p>No outstanding fines.</p>
                                <?php else: ?>
                                    <table class="data-table">
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
                                                    <small><?= htmlspecialchars($fine['email']) ?></small>
                                                </td>
                                                <td><?= number_format($fine['overdue_books']) ?></td>
                                                <td class="fine-amount">$<?= number_format($fine['outstanding_fine'], 2) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        function setReportType(type) {
            document.getElementById('reportType').value = type;
            document.getElementById('reportForm').submit();
        }
        
        function exportToPDF() {
            // Simple implementation - opens print dialog
            // In a real implementation, you might use jsPDF or similar
            window.print();
        }
        
        function exportToExcel() {
            // Simple implementation - you might use SheetJS or similar
            alert('Excel export functionality would be implemented here');
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
                        '#667eea', '#764ba2', '#f093fb', '#f5576c', 
                        '#4facfe', '#00f2fe', '#43e97b', '#38f9d7'
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
        
        // Auto-submit form on date change
        document.addEventListener('DOMContentLoaded', function() {
            const dateInputs = document.querySelectorAll('input[type="date"]');
            dateInputs.forEach(input => {
                input.addEventListener('change', function() {
                    // Add a small delay to allow user to select both dates
                    setTimeout(() => {
                        document.getElementById('reportForm').submit();
                    }, 1000);
                });
            });
        });
    </script>
</body>
</html>
