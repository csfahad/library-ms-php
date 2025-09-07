<?php
/**
 * Student Profile Page
 * Library Management System
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

// Get current user
$currentUser = getCurrentUser();

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $db = getDB();
            
            // Check if email is already taken by another user
            $emailCheck = $db->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $emailCheck->execute([$email, $currentUser['user_id']]);
            
            if ($emailCheck->rowCount() > 0) {
                $error = 'This email address is already in use.';
            } else {
                // Update profile information
                $updateQuery = "UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$name, $email, $phone, $address, $currentUser['user_id']]);
                
                // Handle password change
                if (!empty($newPassword)) {
                    if (empty($currentPassword)) {
                        $error = 'Current password is required to set a new password.';
                    } elseif (strlen($newPassword) < 6) {
                        $error = 'New password must be at least 6 characters long.';
                    } elseif ($newPassword !== $confirmPassword) {
                        $error = 'New password and confirmation do not match.';
                    } else {
                        // Verify current password
                        $userCheck = $db->prepare("SELECT password FROM users WHERE user_id = ?");
                        $userCheck->execute([$currentUser['user_id']]);
                        $userData = $userCheck->fetch();
                        
                        if (!password_verify($currentPassword, $userData['password'])) {
                            $error = 'Current password is incorrect.';
                        } else {
                            // Update password
                            $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                            $passwordUpdate = $db->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                            $passwordUpdate->execute([$newHashedPassword, $currentUser['user_id']]);
                            $success = 'Profile and password updated successfully!';
                        }
                    }
                } else {
                    $success = 'Profile updated successfully!';
                }
                
                if (!$error) {
                    // Refresh user data
                    $currentUser = getCurrentUser();
                    $_SESSION['user_name'] = $currentUser['name'];
                    $_SESSION['user_email'] = $currentUser['email'];
                }
            }
        } catch (Exception $e) {
            $error = 'An error occurred while updating your profile.';
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
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
                    Library MS
                </a>
                <ul class="navbar-nav">
                    <li><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="search-books.php" class="nav-link"><i class="fas fa-search"></i> Search Books</a></li>
                    <li><a href="my-books.php" class="nav-link"><i class="fas fa-book"></i> My Books</a></li>
                    <li><a href="history.php" class="nav-link"><i class="fas fa-history"></i> History</a></li>
                    <li><a href="profile.php" class="nav-link active"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="../includes/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">My Profile</h1>
                <p class="page-subtitle">Manage your account information and settings</p>
            </div>

            <!-- Display alerts -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user-edit"></i> Personal Information
                            </h3>
                        </div>
                        <form method="POST" action="profile.php">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name" class="form-label">Full Name *</label>
                                            <input 
                                                type="text" 
                                                id="name" 
                                                name="name" 
                                                class="form-control" 
                                                value="<?php echo htmlspecialchars($currentUser['name']); ?>"
                                                required
                                            >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email Address *</label>
                                            <input 
                                                type="email" 
                                                id="email" 
                                                name="email" 
                                                class="form-control" 
                                                value="<?php echo htmlspecialchars($currentUser['email']); ?>"
                                                required
                                            >
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input 
                                                type="tel" 
                                                id="phone" 
                                                name="phone" 
                                                class="form-control" 
                                                value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>"
                                            >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="registration_date" class="form-label">Member Since</label>
                                            <input 
                                                type="text" 
                                                class="form-control" 
                                                value="<?php echo date('F d, Y', strtotime($currentUser['registration_date'])); ?>"
                                                readonly
                                            >
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea 
                                        id="address" 
                                        name="address" 
                                        class="form-control" 
                                        rows="3"
                                    ><?php echo htmlspecialchars($currentUser['address'] ?? ''); ?></textarea>
                                </div>

                                <hr class="my-4">

                                <!-- Password Change Section -->
                                <h4 class="mb-3">
                                    <i class="fas fa-lock"></i> Change Password
                                </h4>
                                <p class="text-secondary mb-3">Leave password fields empty if you don't want to change your password.</p>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="current_password" class="form-label">Current Password</label>
                                            <input 
                                                type="password" 
                                                id="current_password" 
                                                name="current_password" 
                                                class="form-control"
                                            >
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="new_password" class="form-label">New Password</label>
                                            <input 
                                                type="password" 
                                                id="new_password" 
                                                name="new_password" 
                                                class="form-control" 
                                                minlength="6"
                                            >
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                                            <input 
                                                type="password" 
                                                id="confirm_password" 
                                                name="confirm_password" 
                                                class="form-control" 
                                                minlength="6"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                                <button type="reset" class="btn btn-secondary ml-2">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Profile Summary -->
                <div class="col-md-4">
                    <!-- Account Summary -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-info-circle"></i> Account Summary
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="profile-avatar">
                                    <i class="fas fa-user-circle" style="font-size: 4rem; color: var(--primary-color);"></i>
                                </div>
                                <h5 class="mt-2"><?php echo htmlspecialchars($currentUser['name']); ?></h5>
                                <p class="text-secondary"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                                <span class="badge badge-success">Active Member</span>
                            </div>
                            
                            <div class="profile-stats">
                                <?php
                                // Get user statistics
                                try {
                                    $db = getDB();
                                    
                                    // Current borrowed books
                                    $currentBooks = $db->prepare("SELECT COUNT(*) as count FROM issued_books WHERE user_id = ? AND status = 'issued'");
                                    $currentBooks->execute([$currentUser['user_id']]);
                                    $currentCount = $currentBooks->fetch()['count'] ?? 0;
                                    
                                    // Total books borrowed
                                    $totalBooks = $db->prepare("SELECT COUNT(*) as count FROM issued_books WHERE user_id = ?");
                                    $totalBooks->execute([$currentUser['user_id']]);
                                    $totalCount = $totalBooks->fetch()['count'] ?? 0;
                                    
                                    // Total fines
                                    $totalFines = $db->prepare("SELECT COALESCE(SUM(fine_amount), 0) as total FROM issued_books WHERE user_id = ?");
                                    $totalFines->execute([$currentUser['user_id']]);
                                    $fineAmount = $totalFines->fetch()['total'] ?? 0;
                                    
                                } catch (Exception $e) {
                                    $currentCount = $totalCount = $fineAmount = 0;
                                }
                                ?>
                                
                                <div class="stat-item mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span><i class="fas fa-book text-primary"></i> Currently Borrowed:</span>
                                        <strong><?php echo $currentCount; ?></strong>
                                    </div>
                                </div>
                                
                                <div class="stat-item mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span><i class="fas fa-history text-secondary"></i> Total Borrowed:</span>
                                        <strong><?php echo $totalCount; ?></strong>
                                    </div>
                                </div>
                                
                                <div class="stat-item mb-3">
                                    <div class="d-flex justify-content-between">
                                        <span><i class="fas fa-dollar-sign text-warning"></i> Total Fines:</span>
                                        <strong class="<?php echo $fineAmount > 0 ? 'text-danger' : 'text-success'; ?>">
                                            $<?php echo number_format($fineAmount, 2); ?>
                                        </strong>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="d-flex justify-content-between">
                                        <span><i class="fas fa-calendar text-info"></i> Member Since:</span>
                                        <strong><?php echo date('M Y', strtotime($currentUser['registration_date'])); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-bolt"></i> Quick Actions
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="search-books.php" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i> Search Books
                                </a>
                                <a href="my-books.php" class="btn btn-outline-success">
                                    <i class="fas fa-book"></i> My Current Books
                                </a>
                                <a href="history.php" class="btn btn-outline-info">
                                    <i class="fas fa-history"></i> Borrowing History
                                </a>
                                <a href="feedback.php" class="btn btn-outline-warning">
                                    <i class="fas fa-comment"></i> Send Feedback
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword && confirmPassword !== '') {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>

    <style>
        .profile-avatar {
            margin-bottom: 1rem;
        }
        
        .stat-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .d-grid {
            display: grid;
        }
        
        .gap-2 {
            gap: 0.5rem;
        }
    </style>
</body>
</html>
