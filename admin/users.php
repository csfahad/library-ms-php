<?php
/**
 * Users Management Page
 * Admin Panel - Library Management System
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin access
requireAdmin();

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add') {
        // Add new user
        $userData = [
            'name' => sanitizeInput($_POST['name'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'role' => sanitizeInput($_POST['role'] ?? 'student'),
            'phone' => sanitizeInput($_POST['phone'] ?? ''),
            'address' => sanitizeInput($_POST['address'] ?? '')
        ];
        
        if (registerUser($userData)) {
            $message = 'User added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to add user. Email might already be in use.';
            $messageType = 'danger';
        }
    }
    
    elseif ($action == 'edit') {
        // Edit existing user
        $userId = (int)($_POST['user_id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $role = sanitizeInput($_POST['role'] ?? 'student');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $status = sanitizeInput($_POST['status'] ?? 'active');
        
        try {
            $db = getDB();
            $sql = "UPDATE users SET 
                    name = :name, 
                    email = :email, 
                    role = :role, 
                    phone = :phone, 
                    address = :address, 
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP 
                    WHERE user_id = :user_id";
            
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                $message = 'User updated successfully!';
                $messageType = 'success';
            } else {
                $message = 'Failed to update user.';
                $messageType = 'danger';
            }
        } catch (Exception $e) {
            $message = 'Failed to update user. Email might already be in use.';
            $messageType = 'danger';
        }
    }
    
    elseif ($action == 'delete') {
        // Delete user
        $userId = (int)($_POST['user_id'] ?? 0);
        
        try {
            $db = getDB();
            
            // Check if user has active book issues
            $checkSql = "SELECT COUNT(*) FROM issued_books WHERE user_id = :user_id AND status = 'issued'";
            $checkStmt = $db->prepare($checkSql);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn() > 0) {
                $message = 'Cannot delete user. User has active book issues.';
                $messageType = 'danger';
            } else {
                $sql = "DELETE FROM users WHERE user_id = :user_id";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':user_id', $userId);
                
                if ($stmt->execute()) {
                    $message = 'User deleted successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to delete user.';
                    $messageType = 'danger';
                }
            }
        } catch (Exception $e) {
            $message = 'Failed to delete user.';
            $messageType = 'danger';
        }
    }
}

// Get search parameters
$search = sanitizeInput($_GET['search'] ?? '');
$role = sanitizeInput($_GET['role'] ?? '');

// Get users
$users = getUsers($search, $role);

$pageTitle = 'Manage Users';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container-fluid">
            <nav class="navbar">
                <a href="dashboard.php" class="navbar-brand">
                    <i class="fas fa-book-open"></i> <?php echo SITE_NAME; ?>
                </a>
                <div class="navbar-nav">
                    <span class="nav-item">
                        <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="../includes/logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2">
                <div class="sidebar">
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
                            <a href="users.php" class="nav-link active">
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
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <div class="main-content">
                    <div class="content-wrapper">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h1><i class="fas fa-users"></i> Manage Users</h1>
                            <button class="btn btn-primary" data-modal-target="addUserModal">
                                <i class="fas fa-user-plus"></i> Add New User
                            </button>
                        </div>

                        <!-- Display messages -->
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                                <?php echo htmlspecialchars($message); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Search and Filters -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <form method="GET" class="row align-items-end">
                                    <div class="col-md-5">
                                        <label for="search" class="form-label">Search Users</label>
                                        <input 
                                            type="text" 
                                            id="search" 
                                            name="search" 
                                            class="form-control" 
                                            placeholder="Search by name or email..."
                                            value="<?php echo htmlspecialchars($search); ?>"
                                        >
                                    </div>
                                    <div class="col-md-3">
                                        <label for="role" class="form-label">Filter by Role</label>
                                        <select id="role" name="role" class="form-control form-select">
                                            <option value="">All Roles</option>
                                            <option value="student" <?php echo $role === 'student' ? 'selected' : ''; ?>>Student</option>
                                            <option value="librarian" <?php echo $role === 'librarian' ? 'selected' : ''; ?>>Librarian</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                        <?php if (!empty($search) || !empty($role)): ?>
                                            <a href="users.php" class="btn btn-secondary">
                                                <i class="fas fa-times"></i> Clear
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-list"></i> Users List
                                    <span class="text-muted">(<?php echo count($users); ?> users found)</span>
                                </h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($users)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h4>No users found</h4>
                                        <p class="text-muted">No users match your search criteria.</p>
                                        <button class="btn btn-primary" data-modal-target="addUserModal">
                                            <i class="fas fa-user-plus"></i> Add First User
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table" id="usersTable">
                                            <thead>
                                                <tr>
                                                    <th data-sort="name">Name <i class="fas fa-sort"></i></th>
                                                    <th data-sort="email">Email <i class="fas fa-sort"></i></th>
                                                    <th data-sort="role">Role <i class="fas fa-sort"></i></th>
                                                    <th data-sort="phone">Phone <i class="fas fa-sort"></i></th>
                                                    <th data-sort="registration_date">Joined <i class="fas fa-sort"></i></th>
                                                    <th data-sort="status">Status <i class="fas fa-sort"></i></th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($users as $user): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                                            <?php if (!empty($user['address'])): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($user['address'], 0, 30)) . (strlen($user['address']) > 30 ? '...' : ''); ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-danger' : ($user['role'] === 'librarian' ? 'badge-warning' : 'badge-primary'); ?>">
                                                                <?php echo ucfirst($user['role']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                                        <td><?php echo formatDate($user['registration_date']); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo $user['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                                                <?php echo ucfirst($user['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button class="btn btn-sm btn-info" onclick="viewUser(<?php echo $user['user_id']; ?>)" title="View Details">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $user['user_id']; ?>)" title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['user_id']; ?>)" title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New User</h3>
                <button type="button" class="close" data-modal-close>&times;</button>
            </div>
            <form method="POST" data-validate="true">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_name" class="form-label">Full Name *</label>
                                <input type="text" id="add_name" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_email" class="form-label">Email Address *</label>
                                <input type="email" id="add_email" name="email" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_password" class="form-label">Password *</label>
                                <input type="password" id="add_password" name="password" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_role" class="form-label">Role *</label>
                                <select id="add_role" name="role" class="form-control form-select" required>
                                    <option value="student">Student</option>
                                    <option value="librarian">Librarian</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_phone" class="form-label">Phone Number</label>
                        <input type="tel" id="add_phone" name="phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="add_address" class="form-label">Address</label>
                        <textarea id="add_address" name="address" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit User</h3>
                <button type="button" class="close" data-modal-close>&times;</button>
            </div>
            <form method="POST" data-validate="true">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_name" class="form-label">Full Name *</label>
                                <input type="text" id="edit_name" name="name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email" class="form-label">Email Address *</label>
                                <input type="email" id="edit_email" name="email" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_role" class="form-label">Role *</label>
                                <select id="edit_role" name="role" class="form-control form-select" required>
                                    <option value="student">Student</option>
                                    <option value="librarian">Librarian</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status" class="form-label">Status *</label>
                                <select id="edit_status" name="status" class="form-control form-select" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone" class="form-label">Phone Number</label>
                        <input type="tel" id="edit_phone" name="phone" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_address" class="form-label">Address</label>
                        <textarea id="edit_address" name="address" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">User Details</h3>
                <button type="button" class="close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body" id="viewUserContent">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        // Users data for JavaScript operations
        const usersData = <?php echo json_encode($users); ?>;
        
        function editUser(userId) {
            const user = usersData.find(u => u.user_id == userId);
            if (user) {
                document.getElementById('edit_user_id').value = user.user_id;
                document.getElementById('edit_name').value = user.name;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_role').value = user.role;
                document.getElementById('edit_status').value = user.status;
                document.getElementById('edit_phone').value = user.phone || '';
                document.getElementById('edit_address').value = user.address || '';
                
                LMS.openModal('editUserModal');
            }
        }
        
        function viewUser(userId) {
            const user = usersData.find(u => u.user_id == userId);
            if (user) {
                const content = `
                    <div class="row">
                        <div class="col-md-12">
                            <h4>${user.name}</h4>
                            <p class="text-muted">${user.email}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Role:</strong> ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}<br>
                            <strong>Phone:</strong> ${user.phone || 'N/A'}<br>
                            <strong>Status:</strong> <span class="badge badge-${user.status === 'active' ? 'success' : 'secondary'}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</span><br>
                            <strong>Registration Date:</strong> ${user.registration_date}
                        </div>
                        <div class="col-md-6">
                            <strong>Address:</strong><br>
                            ${user.address || 'Not provided'}
                        </div>
                    </div>
                `;
                
                document.getElementById('viewUserContent').innerHTML = content;
                LMS.openModal('viewUserModal');
            }
        }
        
        function deleteUser(userId) {
            const user = usersData.find(u => u.user_id == userId);
            if (user && LMS.confirmAction(`Are you sure you want to delete user "${user.name}"?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
