<?php
/**
 * Admin Settings
 * Library Management System
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        try {
            // Update library settings
            $settings = [
                'max_books_per_user' => (int)$_POST['max_books_per_user'],
                'issue_duration_days' => (int)$_POST['issue_duration_days'],
                'fine_per_day' => (float)$_POST['fine_per_day'],
                'library_name' => trim($_POST['library_name']),
                'library_address' => trim($_POST['library_address']),
                'library_phone' => trim($_POST['library_phone']),
                'library_email' => trim($_POST['library_email'])
            ];

            // Update each setting in the database
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                                     VALUES (?, ?) 
                                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $value]);
            }

            $message = 'Settings updated successfully!';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Error updating settings: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get current settings
function getSetting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

$pageTitle = 'System Settings';
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
<body class="dashboard-container">
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="dashboard.php" class="navbar-brand">
                    <div class="logo">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
                <ul class="navbar-nav">
                    <li><span class="nav-text"><i class="fas fa-user-shield"></i> Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span></li>
                    <li><a href="../includes/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-layout">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-content">
                    <nav class="sidebar-nav">
                        <a href="dashboard.php" class="sidebar-link">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="books.php" class="sidebar-link">
                            <i class="fas fa-book"></i> Manage Books
                        </a>
                        <a href="users.php" class="sidebar-link">
                            <i class="fas fa-users"></i> Manage Users
                        </a>
                        <a href="issue-book.php" class="sidebar-link">
                            <i class="fas fa-hand-holding"></i> Issue Book
                        </a>
                        <a href="return-book.php" class="sidebar-link">
                            <i class="fas fa-undo"></i> Return Book
                        </a>
                        <a href="issued-books.php" class="sidebar-link">
                            <i class="fas fa-list"></i> Issued Books
                        </a>
                        <a href="overdue-books.php" class="sidebar-link">
                            <i class="fas fa-exclamation-triangle"></i> Overdue Books
                        </a>
                        <a href="reports.php" class="sidebar-link">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                        <a href="settings.php" class="sidebar-link active">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <div class="page-header">
                    <h1><i class="fas fa-cog"></i> System Settings</h1>
                    <p class="page-description">Configure library management system settings</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?>">
                        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-wrench"></i> Library Settings</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="library_name" class="form-label">Library Name</label>
                                        <input type="text" class="form-control" id="library_name" name="library_name" 
                                               value="<?php echo htmlspecialchars(getSetting('library_name', 'City Public Library')); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="library_email" class="form-label">Library Email</label>
                                        <input type="email" class="form-control" id="library_email" name="library_email" 
                                               value="<?php echo htmlspecialchars(getSetting('library_email', 'info@library.com')); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="library_phone" class="form-label">Library Phone</label>
                                        <input type="tel" class="form-control" id="library_phone" name="library_phone" 
                                               value="<?php echo htmlspecialchars(getSetting('library_phone', '+1-234-567-8900')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_books_per_user" class="form-label">Max Books Per User</label>
                                        <input type="number" class="form-control" id="max_books_per_user" name="max_books_per_user" 
                                               value="<?php echo htmlspecialchars(getSetting('max_books_per_user', '3')); ?>" min="1" max="20" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="library_address" class="form-label">Library Address</label>
                                <textarea class="form-control" id="library_address" name="library_address" rows="3"><?php echo htmlspecialchars(getSetting('library_address', '123 Main Street, City, State 12345')); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="issue_duration_days" class="form-label">Issue Duration (Days)</label>
                                        <input type="number" class="form-control" id="issue_duration_days" name="issue_duration_days" 
                                               value="<?php echo htmlspecialchars(getSetting('issue_duration_days', '14')); ?>" min="1" max="90" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fine_per_day" class="form-label">Fine Per Day ($)</label>
                                        <input type="number" class="form-control" id="fine_per_day" name="fine_per_day" 
                                               value="<?php echo htmlspecialchars(getSetting('fine_per_day', '2.00')); ?>" step="0.01" min="0" max="50" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" name="update_settings" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Settings
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- System Information -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle"></i> System Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Library Settings</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Max Books per User:</strong> <?php echo getSetting('max_books_per_user', '3'); ?></li>
                                    <li><strong>Default Issue Days:</strong> <?php echo getSetting('issue_duration_days', '14'); ?></li>
                                    <li><strong>Fine per Day:</strong> $<?php echo number_format(getSetting('fine_per_day', '2.00'), 2); ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Quick Stats</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Available Books:</strong> <?php echo getTotalAvailableBooks(); ?></li>
                                    <li><strong>Books in Circulation:</strong> <?php echo getBooksInCirculation(); ?></li>
                                    <li><strong>System Health:</strong> <span class="text-success">Good</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>
