<?php
/**
 * Student History Page
 * Library Management System
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

// Get current user
$currentUser = getCurrentUser();

// Get user's borrowing history
$history = getUserBorrowingHistory($currentUser['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing History - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="my-books.php" class="nav-link"><i class="fas fa-book"></i> My Books</a></li>
                    <li><a href="history.php" class="nav-link active"><i class="fas fa-history"></i> History</a></li>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../includes/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Borrowing History</h1>
                <p class="page-subtitle">Complete history of all your library transactions</p>
            </div>

            <?php if (empty($history)): ?>
                <!-- No history message -->
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-history text-muted mb-3" style="font-size: 3rem;"></i>
                        <h3 class="text-muted">No borrowing history</h3>
                        <p class="text-secondary">You haven't borrowed any books yet. Start exploring our collection!</p>
                        <a href="search-books.php" class="btn btn-primary">
                            <i class="fas fa-search"></i> Browse Books
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Statistics Summary -->
                <div class="row mb-4">
                    <?php
                    $totalBorrowed = count($history);
                    $totalReturned = count(array_filter($history, function($h) { return $h['status'] === 'returned'; }));
                    $totalOverdue = 0;
                    $totalFines = 0;
                    
                    foreach ($history as $h) {
                        if ($h['fine_amount'] > 0) {
                            $totalFines += $h['fine_amount'];
                        }
                        if ($h['status'] === 'issued' && strtotime($h['due_date']) < time()) {
                            $totalOverdue++;
                        }
                    }
                    ?>
                    
                    <div class="col-md-3 col-6">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <div class="stat-number text-primary"><?php echo $totalBorrowed; ?></div>
                                <div class="stat-label">Total Borrowed</div>
                                <i class="stat-icon fas fa-book"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-6">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <div class="stat-number text-success"><?php echo $totalReturned; ?></div>
                                <div class="stat-label">Returned</div>
                                <i class="stat-icon fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-6">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <div class="stat-number text-warning"><?php echo $totalOverdue; ?></div>
                                <div class="stat-label">Currently Overdue</div>
                                <i class="stat-icon fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-6">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <div class="stat-number text-danger">$<?php echo number_format($totalFines, 2); ?></div>
                                <div class="stat-label">Total Fines</div>
                                <i class="stat-icon fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Complete Borrowing History</h3>
                        <div class="card-actions">
                            <button class="btn btn-outline-primary btn-sm" onclick="exportHistory()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="historyTable">
                            <thead>
                                <tr>
                                    <th>Book Details</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                    <th>Fine</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $record): ?>
                                    <?php
                                    $isOverdue = $record['status'] === 'issued' && strtotime($record['due_date']) < time();
                                    $isReturned = $record['status'] === 'returned';
                                    $isLate = $isReturned && $record['return_date'] && strtotime($record['return_date']) > strtotime($record['due_date']);
                                    
                                    if ($isReturned && $record['return_date']) {
                                        $duration = floor((strtotime($record['return_date']) - strtotime($record['issue_date'])) / (60*60*24));
                                    } elseif ($record['status'] === 'issued') {
                                        $duration = floor((time() - strtotime($record['issue_date'])) / (60*60*24));
                                    } else {
                                        $duration = 0;
                                    }
                                    ?>
                                    <tr class="<?php echo $isOverdue ? 'table-warning' : ''; ?>">
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($record['title']); ?></strong>
                                                <br><small class="text-muted">by <?php echo htmlspecialchars($record['author']); ?></small>
                                                <?php if (!empty($record['isbn'])): ?>
                                                    <br><small class="text-muted">ISBN: <?php echo htmlspecialchars($record['isbn']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($record['issue_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($record['due_date'])); ?></td>
                                        <td>
                                            <?php if ($record['return_date']): ?>
                                                <?php echo date('M d, Y', strtotime($record['return_date'])); ?>
                                                <?php if ($isLate): ?>
                                                    <br><small class="text-danger">
                                                        <i class="fas fa-clock"></i> Late return
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not returned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($record['status'] === 'issued'): ?>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge badge-danger">Overdue</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php endif; ?>
                                            <?php elseif ($record['status'] === 'returned'): ?>
                                                <?php if ($isLate): ?>
                                                    <span class="badge badge-warning">Returned Late</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Returned</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($record['fine_amount'] > 0): ?>
                                                <span class="text-danger">$<?php echo number_format($record['fine_amount'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-success">$0.00</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-secondary"><?php echo $duration; ?> days</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Reading Statistics -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Reading Patterns</h4>
                            </div>
                            <div class="card-body">
                                <?php
                                $categoryCounts = [];
                                foreach ($history as $record) {
                                    $cat = $record['category'] ?? 'Uncategorized';
                                    $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
                                }
                                arsort($categoryCounts);
                                ?>
                                <h6>Favorite Categories:</h6>
                                <?php foreach (array_slice($categoryCounts, 0, 5, true) as $category => $count): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><?php echo htmlspecialchars($category); ?></span>
                                        <span class="badge badge-primary"><?php echo $count; ?> books</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Reading Goals</h4>
                            </div>
                            <div class="card-body">
                                <?php
                                $thisYear = date('Y');
                                $booksThisYear = count(array_filter($history, function($h) use ($thisYear) {
                                    return date('Y', strtotime($h['issue_date'])) === $thisYear;
                                }));
                                $yearGoal = 24; // 2 books per month
                                $progress = min(100, ($booksThisYear / $yearGoal) * 100);
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span><?php echo $thisYear; ?> Reading Goal</span>
                                        <span><?php echo $booksThisYear; ?> / <?php echo $yearGoal; ?> books</span>
                                    </div>
                                    <div class="progress mt-2" style="height: 8px;">
                                        <div class="progress-bar" 
                                             style="width: <?php echo $progress; ?>%; background-color: var(--primary-color);">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo round($progress, 1); ?>% complete</small>
                                </div>
                                
                                <div class="text-center">
                                    <?php if ($booksThisYear >= $yearGoal): ?>
                                        <span class="badge badge-success">🎉 Goal Achieved!</span>
                                    <?php else: ?>
                                        <small class="text-muted">
                                            <?php echo $yearGoal - $booksThisYear; ?> more books to reach your goal
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function exportHistory() {
            // Simple CSV export functionality
            const table = document.getElementById('historyTable');
            let csv = [];
            
            // Get headers
            const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent.trim());
            csv.push(headers.join(','));
            
            // Get data rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = Array.from(row.querySelectorAll('td')).map(td => {
                    return '"' + td.textContent.trim().replace(/"/g, '""') + '"';
                });
                csv.push(cells.join(','));
            });
            
            // Download CSV
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'borrowing_history_<?php echo date('Y-m-d'); ?>.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Add search functionality
        function filterHistory() {
            const searchTerm = document.getElementById('historySearch').value.toLowerCase();
            const rows = document.querySelectorAll('#historyTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }
    </script>

    <style>
        .table-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        
        .progress {
            background-color: var(--bg-tertiary);
            border-radius: 50px;
        }
        
        .card-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .stat-card {
            position: relative;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
    </style>
</body>
</html>
