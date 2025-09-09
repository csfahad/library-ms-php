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
            $pdo = getDB();
            
            // Update library settings
            $settings = [
                'library_name' => trim($_POST['library_name']),
                'library_address' => trim($_POST['library_address']),
                'library_phone' => trim($_POST['library_phone']),
                'library_email' => trim($_POST['library_email']),
                'library_website' => trim($_POST['library_website']),
                'max_books_per_user' => (int)$_POST['max_books_per_user'],
                'issue_duration_days' => (int)$_POST['issue_duration_days'],
                'fine_per_day' => (float)$_POST['fine_per_day'],
                'max_fine_amount' => (float)$_POST['max_fine_amount'],
                'renewal_limit' => (int)$_POST['renewal_limit'],
                'grace_period_days' => (int)$_POST['grace_period_days'],
                'notification_email' => trim($_POST['notification_email']),
                'reminder_days_before' => (int)$_POST['reminder_days_before'],
                'email_overdue' => isset($_POST['email_overdue']) ? 1 : 0,
                'email_due_reminder' => isset($_POST['email_due_reminder']) ? 1 : 0,
                'email_reservation' => isset($_POST['email_reservation']) ? 1 : 0
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

$pageTitle = 'System Settings';

// Debug: Let's test if database connection works
try {
    $testSetting = getSetting('library_name', 'Default Library');
    error_log("Settings page - Test setting value: " . $testSetting);
} catch (Exception $e) {
    error_log("Settings page error: " . $e->getMessage());
}
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
                    <a href="book-requests.php" class="nav-link">
                        <i class="fas fa-hand-paper"></i> Book Requests
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
                    <a href="reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a href="settings.php" class="nav-link active">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Admin Main Content -->
        <main class="admin-main">
            <!-- Settings Header -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">
                    <i class="fas fa-cog"></i> System Settings
                </h1>
                <p class="dashboard-subtitle">Configure library management system settings</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Settings Form -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2 class="content-card-title">
                        <i class="fas fa-wrench"></i> Library Configuration
                    </h2>
                </div>
                <div class="content-card-body">
                    <form method="POST" action="" class="settings-form">
                        <!-- Library Information Section -->
                        <div style="margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0;">
                            <h3 style="font-size: 1.25rem; font-weight: 600; color: #0f172a; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-building" style="color: #f07238;"></i> Library Information
                            </h3>
                            
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
                                        <label for="library_website" class="form-label">Library Website</label>
                                        <input type="url" class="form-control" id="library_website" name="library_website" 
                                               value="<?php echo htmlspecialchars(getSetting('library_website', 'https://www.library.com')); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="library_address" class="form-label">Library Address</label>
                                <textarea class="form-control" id="library_address" name="library_address" rows="3"><?php echo htmlspecialchars(getSetting('library_address', '123 Main Street, City, State 12345')); ?></textarea>
                            </div>
                        </div>

                        <!-- System Settings Section -->
                        <div style="margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0;">
                            <h3 style="font-size: 1.25rem; font-weight: 600; color: #0f172a; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-cogs" style="color: #f07238;"></i> System Configuration
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_books_per_user" class="form-label">Max Books Per User</label>
                                        <input type="number" class="form-control" id="max_books_per_user" name="max_books_per_user" 
                                               value="<?php echo htmlspecialchars(getSetting('max_books_per_user', '3')); ?>" min="1" max="20" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="issue_duration_days" class="form-label">Issue Duration (Days)</label>
                                        <input type="number" class="form-control" id="issue_duration_days" name="issue_duration_days" 
                                               value="<?php echo htmlspecialchars(getSetting('issue_duration_days', '14')); ?>" min="1" max="90" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="fine_per_day" class="form-label">Fine Per Day ($)</label>
                                        <input type="number" class="form-control" id="fine_per_day" name="fine_per_day" 
                                               value="<?php echo htmlspecialchars(getSetting('fine_per_day', '2.00')); ?>" step="0.01" min="0" max="50" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="max_fine_amount" class="form-label">Maximum Fine Amount ($)</label>
                                        <input type="number" class="form-control" id="max_fine_amount" name="max_fine_amount" 
                                               value="<?php echo htmlspecialchars(getSetting('max_fine_amount', '50.00')); ?>" step="0.01" min="0">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="renewal_limit" class="form-label">Max Renewals Allowed</label>
                                        <input type="number" class="form-control" id="renewal_limit" name="renewal_limit" 
                                               value="<?php echo htmlspecialchars(getSetting('renewal_limit', '2')); ?>" min="0" max="10">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="grace_period_days" class="form-label">Grace Period (Days)</label>
                                        <input type="number" class="form-control" id="grace_period_days" name="grace_period_days" 
                                               value="<?php echo htmlspecialchars(getSetting('grace_period_days', '1')); ?>" min="0" max="7">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Settings Section -->
                        <div style="margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e2e8f0;">
                            <h3 style="font-size: 1.25rem; font-weight: 600; color: #0f172a; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fas fa-bell" style="color: #f07238;"></i> Notification Settings
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="notification_email" class="form-label">System Notification Email</label>
                                        <input type="email" class="form-control" id="notification_email" name="notification_email" 
                                               value="<?php echo htmlspecialchars(getSetting('notification_email', 'noreply@library.com')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="reminder_days_before" class="form-label">Reminder Days Before Due</label>
                                        <input type="number" class="form-control" id="reminder_days_before" name="reminder_days_before" 
                                               value="<?php echo htmlspecialchars(getSetting('reminder_days_before', '2')); ?>" min="1" max="7">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" id="email_overdue" name="email_overdue" <?php echo getSetting('email_overdue', '1') ? 'checked' : ''; ?>>
                                            Send overdue notifications
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" id="email_due_reminder" name="email_due_reminder" <?php echo getSetting('email_due_reminder', '1') ? 'checked' : ''; ?>>
                                            Send due date reminders
                                        </label>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>
                                            <input type="checkbox" id="email_reservation" name="email_reservation" <?php echo getSetting('email_reservation', '1') ? 'checked' : ''; ?>>
                                            Send reservation notifications
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" name="update_settings" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Settings Overview -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2 class="content-card-title">
                        <i class="fas fa-info-circle"></i> Current Configuration
                    </h2>
                </div>
                <div class="content-card-body">
                    <?php
                    // Get database connection for statistics
                    $pdo = getDB();
                    ?>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo getTotalAvailableBooks(); ?></h3>
                                <p class="stat-label">Available Books</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-hand-holding"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo getBooksInCirculation(); ?></h3>
                                <p class="stat-label">Books in Circulation</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <?php
                                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
                                $totalUsers = $stmt->fetchColumn();
                                ?>
                                <h3 class="stat-number"><?php echo $totalUsers; ?></h3>
                                <p class="stat-label">Active Users</p>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="stat-content">
                                <h3 class="stat-number"><?php echo getSetting('max_books_per_user', '3'); ?></h3>
                                <p class="stat-label">Max Books/User</p>
                            </div>
                        </div>
                    </div>

                    <div class="settings-summary">
                        <div class="row">
                            <div class="col-md-6">
                                <div style="background: #f8fafc; border-radius: 8px; padding: 1.5rem; height: 100%;">
                                    <h4 style="font-size: 1.1rem; font-weight: 600; color: #0f172a; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-book" style="color: #f07238;"></i> Lending Policy</h4>
                                    <ul style="list-style: none; padding: 0;">
                                        <li style="padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;">Issue Duration: <?php echo getSetting('issue_duration_days', '14'); ?> days</li>
                                        <li style="padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;">Fine per Day: $<?php echo number_format(getSetting('fine_per_day', '2.00'), 2); ?></li>
                                        <li style="padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;">Max Renewals: <?php echo getSetting('renewal_limit', '2'); ?></li>
                                        <li style="padding: 0.5rem 0;">Grace Period: <?php echo getSetting('grace_period_days', '1'); ?> days</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div style="background: #f8fafc; border-radius: 8px; padding: 1.5rem; height: 100%;">
                                    <h4 style="font-size: 1.1rem; font-weight: 600; color: #0f172a; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;"><i class="fas fa-bell" style="color: #f07238;"></i> Notifications</h4>
                                    <ul style="list-style: none; padding: 0;">
                                        <li style="padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;">Overdue Alerts: <?php echo getSetting('email_overdue', '1') ? 'Enabled' : 'Disabled'; ?></li>
                                        <li style="padding: 0.5rem 0; border-bottom: 1px solid #e2e8f0;">Due Reminders: <?php echo getSetting('email_due_reminder', '1') ? 'Enabled' : 'Disabled'; ?></li>
                                        <li style="padding: 0.5rem 0;">Reservation Alerts: <?php echo getSetting('email_reservation', '1') ? 'Enabled' : 'Disabled'; ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin-dashboard.js"></script>
</body>
</html>
