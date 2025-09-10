<?php
/* Student Dashboard */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

$issuedBooks = getUserIssuedBooks($userId, 'issued');
$returnedBooks = getUserIssuedBooks($userId, 'returned');

$stats = [
    'books_issued' => count($issuedBooks),
    'books_returned' => count($returnedBooks),
    'overdue_books' => 0,
    'total_fine' => 0
];

// Calculate overdue books and fines
foreach ($issuedBooks as $book) {
    if (strtotime($book['due_date']) < time()) {
        $stats['overdue_books']++;
        $daysOverdue = ceil((time() - strtotime($book['due_date'])) / (60 * 60 * 24));
        $finePerDay = getSetting('fine_per_day', '2.00');
        $stats['total_fine'] += $daysOverdue * $finePerDay;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                    <li><a href="dashboard.php" class="nav-link active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="search-books.php" class="nav-link"><i class="fas fa-search"></i> Search Books</a></li>
                    <li><a href="my-books.php" class="nav-link"><i class="fas fa-book"></i> My Books</a></li>
                    <li><a href="history.php" class="nav-link"><i class="fas fa-history"></i> History</a></li>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="feedback.php" class="nav-link"><i class="fas fa-comment"></i> Feedback</a></li>
                    <li><a href="../includes/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>!</p>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card primary">
                        <div class="stats-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $stats['books_issued']; ?></div>
                            <div class="stats-label">Currently Borrowed</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card success">
                        <div class="stats-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $stats['books_returned']; ?></div>
                            <div class="stats-label">Total Returned</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card <?php echo $stats['overdue_books'] > 0 ? 'danger' : 'info'; ?>">
                        <div class="stats-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number"><?php echo $stats['overdue_books']; ?></div>
                            <div class="stats-label">Overdue Books</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card <?php echo $stats['total_fine'] > 0 ? 'warning' : 'info'; ?>">
                        <div class="stats-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stats-content">
                            <div class="stats-number">$<?php echo number_format($stats['total_fine'], 2); ?></div>
                            <div class="stats-label">Outstanding Fines</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Content -->
            <div class="row">
                <!-- Currently Borrowed Books -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-book-reader"></i> Currently Borrowed Books
                            </h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($issuedBooks)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-2">No books currently borrowed</p>
                                    <a href="search-books.php" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Browse Books
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Book</th>
                                                <th>Issue Date</th>
                                                <th>Due Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($issuedBooks, 0, 5) as $book): ?>
                                                <?php
                                                $isOverdue = strtotime($book['due_date']) < time();
                                                $daysRemaining = ceil((strtotime($book['due_date']) - time()) / (60 * 60 * 24));
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($book['title']); ?></strong><br>
                                                        <small class="text-secondary"><?php echo htmlspecialchars($book['author']); ?></small>
                                                    </td>
                                                    <td><?php echo date('M d, Y', strtotime($book['issue_date'])); ?></td>
                                                    <td>
                                                        <span class="<?php echo $isOverdue ? 'text-danger' : ($daysRemaining <= 3 ? 'text-warning' : ''); ?>">
                                                            <?php echo date('M d, Y', strtotime($book['due_date'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($isOverdue): ?>
                                                            <span class="badge badge-danger">Overdue</span>
                                                        <?php elseif ($daysRemaining <= 3): ?>
                                                            <span class="badge badge-warning">Due Soon</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-success">Active</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="my-books.php" class="btn btn-outline-primary">
                                        <i class="fas fa-book"></i> View All My Books
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Search -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-search"></i> Quick Book Search
                            </h3>
                        </div>
                        <div class="card-body">
                            <form action="search-books.php" method="GET" class="search-form">
                                <div class="col-md-8">
                                    <input 
                                        type="text" 
                                        name="search" 
                                        class="form-control" 
                                        placeholder="Search by title, author, or ISBN..."
                                    >
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-bolt"></i> Quick Actions
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="search-books.php" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i> Browse Books
                                </a>
                                <a href="my-books.php" class="btn btn-outline-success">
                                    <i class="fas fa-book"></i> My Books
                                </a>
                                <a href="history.php" class="btn btn-outline-info">
                                    <i class="fas fa-history"></i> View History
                                </a>
                                <a href="profile.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-user"></i> Edit Profile
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Library Information -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-info-circle"></i> Library Hours
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="library-hours">
                                <div class="hours-item">
                                    <span class="day">Monday - Friday</span>
                                    <span class="time">8:00 AM - 8:00 PM</span>
                                </div>
                                <div class="hours-item">
                                    <span class="day">Saturday</span>
                                    <span class="time">10:00 AM - 6:00 PM</span>
                                </div>
                                <div class="hours-item">
                                    <span class="day">Sunday</span>
                                    <span class="time">12:00 PM - 5:00 PM</span>
                                </div>
                            </div>
                            <hr>
                            <div class="contact-info mt-2">
                                <p class="mb-2"><i class="fas fa-phone text-primary"></i> <?php echo htmlspecialchars(getSetting('library_phone', '(555) 123-4567')); ?></p>
                                <p class="mb-2"><i class="fas fa-envelope text-primary"></i> <?php echo htmlspecialchars(getSetting('library_email', 'library@university.edu')); ?></p>
                                <p class="mb-0"><i class="fas fa-map-marker-alt text-primary"></i> <?php echo htmlspecialchars(getSetting('library_address', 'Main Library, Room 101')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .hours-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .day {
            font-weight: 500;
        }
        
        .time {
            color: var(--text-secondary);
        }
        
        .contact-info p {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</body>
</html>
