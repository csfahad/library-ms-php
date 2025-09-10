<?php
/* Student My Books Page */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$currentUser = getCurrentUser();

// Get user's book requests (pending, approved, etc.)
$bookRequests = getUserBookRequests($currentUser['id']);

// Separate requests by status (exclude cancelled requests)
$pendingRequests = array_filter($bookRequests, function($req) { return $req['status'] === 'pending'; });
$issuedRequests = array_filter($bookRequests, function($req) { return $req['status'] === 'issued'; });
$rejectedRequests = array_filter($bookRequests, function($req) { return $req['status'] === 'rejected' || $req['status'] === 'cancelled'; });
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Books - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li><a href="search-books.php" class="nav-link"><i class="fas fa-search"></i> Search Books</a></li>
                    <li><a href="my-books.php" class="nav-link active"><i class="fas fa-book"></i> My Books</a></li>
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
                <h1 class="page-title">My Books</h1>
                <p class="page-subtitle">Your book requests and currently borrowed books</p>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-warning"><?php echo count($pendingRequests); ?></div>
                            <div class="stat-label">Pending Requests</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-success"><?php echo count($issuedRequests); ?></div>
                            <div class="stat-label">Books Borrowed</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <?php 
                            $overdueCount = 0;
                            foreach ($issuedRequests as $req) {
                                if ($req['due_date'] && strtotime($req['due_date']) < time()) {
                                    $overdueCount++;
                                }
                            }
                            ?>
                            <div class="stat-number text-danger"><?php echo $overdueCount; ?></div>
                            <div class="stat-label">Overdue</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <div class="stat-number text-secondary"><?php echo count($rejectedRequests); ?></div>
                            <div class="stat-label">Rejected/Cancelled</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs for different sections -->
            <div class="tabs-container mb-4">
                <ul class="tabs-nav">
                    <li class="tab-nav-item active" data-tab="pending">
                        <i class="fas fa-clock"></i> Pending Requests (<?php echo count($pendingRequests); ?>)
                    </li>
                    <li class="tab-nav-item" data-tab="issued">
                        <i class="fas fa-book"></i> Currently Borrowed (<?php echo count($issuedRequests); ?>)
                    </li>
                    <li class="tab-nav-item" data-tab="rejected">
                        <i class="fas fa-times-circle"></i> Rejected/Cancelled (<?php echo count($rejectedRequests); ?>)
                    </li>
                </ul>
                
                <!-- Pending Requests Tab -->
                <div class="tab-content active" id="pending">
                    <?php if (empty($pendingRequests)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-clock text-muted mb-3" style="font-size: 3rem;"></i>
                                <h3 class="text-muted">No Pending Requests</h3>
                                <p class="text-secondary">You don't have any pending book requests.</p>
                                <a href="search-books.php" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Browse Books
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($pendingRequests as $request): ?>
                                <div class="col-lg-6 col-12">
                                    <div class="card book-request-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <span class="badge badge-warning">Pending</span>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($request['request_date'])); ?>
                                                </small>
                                            </div>
                                            
                                            <h5 class="card-title"><?php echo htmlspecialchars($request['title']); ?></h5>
                                            <p class="text-secondary mb-2">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($request['author']); ?>
                                            </p>
                                            <p class="text-secondary mb-2">
                                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($request['category']); ?>
                                            </p>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar"></i> 
                                                    Requested <?php echo date('M j', strtotime($request['request_date'])); ?>
                                                </small>
                                                <button class="btn btn-sm" style="background: var(--bg-tertiary); color: var(--primary-color); padding: 0.5rem 1rem; border: 1px solid var(--primary-color);" onclick="cancelRequest(<?php echo $request['request_id']; ?>)">
                                                    <i class="fas fa-times"></i> Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Currently Borrowed Tab -->
                <div class="tab-content" id="issued">
                    <?php if (empty($issuedRequests)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-book text-muted mb-3" style="font-size: 3rem;"></i>
                                <h3 class="text-muted">No Books Borrowed</h3>
                                <p class="text-secondary">You haven't borrowed any books yet.</p>
                                <a href="search-books.php" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Browse Books
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($issuedRequests as $request): ?>
                                <div class="col-lg-6 col-12">
                                    <div class="card book-request-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <?php 
                                                $isOverdue = $request['due_date'] && strtotime($request['due_date']) < time();
                                                ?>
                                                <span class="badge <?php echo $isOverdue ? 'badge-danger' : 'badge-success'; ?>">
                                                    <?php echo $isOverdue ? 'Overdue' : 'Borrowed'; ?>
                                                </span>
                                                <small class="text-muted">
                                                    Issued: <?php echo date('M j, Y', strtotime($request['issue_date'])); ?>
                                                </small>
                                            </div>
                                            
                                            <h5 class="card-title"><?php echo htmlspecialchars($request['title']); ?></h5>
                                            <p class="text-secondary mb-2">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($request['author']); ?>
                                            </p>
                                            <p class="text-secondary mb-2">
                                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($request['category']); ?>
                                            </p>
                                            
                                            <div class="due-date-info">
                                                <div class="d-flex justify-content-between">
                                                    <span class="text-secondary">Due Date:</span>
                                                    <span class="<?php echo $isOverdue ? 'text-danger font-weight-bold' : 'text-primary'; ?>">
                                                        <?php echo date('M j, Y', strtotime($request['due_date'])); ?>
                                                    </span>
                                                </div>
                                                <?php if ($isOverdue): ?>
                                                    <div class="text-danger small">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        <?php 
                                                        $daysOverdue = ceil((time() - strtotime($request['due_date'])) / (60*60*24));
                                                        echo "$daysOverdue day(s) overdue";
                                                        ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($request['fine'] > 0): ?>
                                                <div class="fine-info text-danger mb-2">
                                                    <i class="fas fa-dollar-sign"></i> 
                                                    Fine: $<?php echo number_format($request['fine'], 2); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Rejected Requests Tab -->
                <div class="tab-content" id="rejected">
                    <?php if (empty($rejectedRequests)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-check-circle text-muted mb-3" style="font-size: 3rem;"></i>
                                <h3 class="text-muted">No Rejected Requests</h3>
                                <p class="text-secondary">You don't have any rejected requests.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($rejectedRequests as $request): ?>
                                <div class="col-lg-6 col-12">
                                    <div class="card book-request-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <?php if ($request['status'] === 'cancelled'): ?>
                                                    <span class="badge badge-secondary">Cancelled</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Rejected</span>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <?php 
                                                    $displayDate = $request['status'] === 'cancelled' ? $request['updated_at'] : $request['approved_date'];
                                                    echo date('M j, Y', strtotime($displayDate)); 
                                                    ?>
                                                </small>
                                            </div>
                                            
                                            <h5 class="card-title"><?php echo htmlspecialchars($request['title']); ?></h5>
                                            <p class="text-secondary mb-2">
                                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($request['author']); ?>
                                            </p>
                                            
                                            <?php if ($request['rejection_reason']): ?>
                                                <div class="rejection-reason text-danger small">
                                                    <strong>Reason:</strong> <?php echo htmlspecialchars($request['rejection_reason']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Notification Modal -->
    <div class="modal" id="notificationModal">
        <div class="modal-dialog">
            <div class="modal-header" id="notificationHeader">
                <h4 id="notificationTitle" class="modal-title">Notification</h4>
                <button type="button" class="modal-close" onclick="closeModal('notificationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="notificationMessage" style="padding: 20px; text-align: center; font-size: 16px;">
                    <!-- Message will be inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-primary" onclick="closeModal('notificationModal')">OK</button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmationModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h4 class="modal-title">Confirm Action</h4>
                <button type="button" class="modal-close" onclick="closeModal('confirmationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="confirmationMessage" style="padding: 20px; text-align: center; font-size: 16px;">
                    <!-- Message will be inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-secondary" onclick="closeModal('confirmationModal')">Cancel</button>
                <button type="button" class="btn-modal btn-danger" id="confirmButton" onclick="confirmAction()">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // Modal Management Functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                const modalId = event.target.id;
                closeModal(modalId);
            }
        });

        function showNotification(title, message, type = 'info') {
            const modal = document.getElementById('notificationModal');
            const header = document.getElementById('notificationHeader');
            const titleEl = document.getElementById('notificationTitle');
            const messageEl = document.getElementById('notificationMessage');
            
            titleEl.textContent = title;
            
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };
            
            messageEl.innerHTML = `
                <i class="${icons[type]} notification-icon ${type}"></i>
                <div>${message}</div>
            `;
            
            header.className = `modal-header ${type}`;
            
            openModal('notificationModal');
        }

        function showConfirmation(title, message, onConfirm, buttonText = 'Confirm', buttonType = 'danger') {
            const titleEl = document.querySelector('#confirmationModal h4');
            const messageEl = document.getElementById('confirmationMessage');
            const confirmBtn = document.getElementById('confirmButton');
            
            titleEl.textContent = title;
            messageEl.innerHTML = `
                <i class="fas fa-question-circle" style="font-size: 48px; color: #17a2b8; margin-bottom: 15px;"></i>
                <div>${message}</div>
            `;
            
            confirmBtn.textContent = buttonText;
            confirmBtn.className = `btn-modal btn-${buttonType}`;
            
            window.confirmCallback = onConfirm;
            
            openModal('confirmationModal');
        }

        // Handle confirmation
        function confirmAction() {
            if (window.confirmCallback) {
                window.confirmCallback();
                window.confirmCallback = null;
            }
            closeModal('confirmationModal');
        }

        // Tab functionality
        document.querySelectorAll('.tab-nav-item').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active classes from all tabs and panes
                document.querySelectorAll('.tab-nav-item').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(p => p.classList.remove('active'));
                
                // Add active class to clicked tab
                this.classList.add('active');
                
                // Show corresponding tab pane
                const targetId = this.getAttribute('data-tab');
                document.getElementById(targetId).classList.add('active');
            });
        });

        // Updated Cancel request function with modal
        function cancelRequest(requestId) {
            showConfirmation(
                'Cancel Book Request',
                'Are you sure you want to cancel this book request?',
                function() {
                    // Find the button that was clicked - use a more specific selector
                    const button = document.querySelector(`button[onclick*="cancelRequest(${requestId})"]`);
                    let originalText = '';
                    
                    if (button) {
                        originalText = button.innerHTML;
                        // Show loading
                        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cancelling...';
                        button.disabled = true;
                    }
                    
                    fetch('../api/book-requests.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=cancel_request&request_id=' + requestId
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showNotification('Success!', data.message, 'success');
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        } else {
                            showNotification('Error', data.message || 'Unknown error occurred', 'error');
                            // Restore button
                            if (button) {
                                button.innerHTML = originalText;
                                button.disabled = false;
                            }
                        }
                    })
                    .catch(error => {
                        showNotification('Error', 'An error occurred while cancelling the request.', 'error');
                        // Restore button
                        if (button) {
                            button.innerHTML = originalText;
                            button.disabled = false;
                        }
                    });
                },
                'Cancel Request',
                'danger'
            );
        }
    </script>

    <style>
        /* Modal Base Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(3px);
        }

        .modal.show {
            display: flex !important;
        }

        .modal-dialog {
            background: white;
            border-radius: 12px;
            max-width: 450px;
            width: 90%;
            max-height: 90vh;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease;
            overflow: hidden;
            position: relative;
            margin: 20px;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            position: relative;
        }

        .modal-header.success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .modal-header.error {
            background: linear-gradient(135deg, #dc3545, #e74c3c);
            color: white;
        }

        .modal-header.warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }

        .modal-header.info {
            background: linear-gradient(135deg, #17a2b8, #007bff);
            color: white;
        }

        .modal-title {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            color: inherit;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .modal-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 25px;
            text-align: center;
            min-height: 80px;
        }

        .modal-body .notification-message {
            display: flex;
            align-items: center;
            gap: 15px;
            text-align: left;
        }

        #confirmationMessage {
            padding: 20px !important;
            text-align: center !important;
            font-size: 16px !important;
        }

        #notificationMessage {
            padding: 20px !important;
            text-align: center !important;
            font-size: 16px !important;
        }

        .notification-icon {
            font-size: 24px;
            min-width: 24px;
        }

        .notification-icon.success { color: #28a745; }
        .notification-icon.error { color: #dc3545; }
        .notification-icon.warning { color: #ffc107; }
        .notification-icon.info { color: #17a2b8; }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            text-align: right;
            gap: 10px;
            display: flex;
            justify-content: flex-end;
        }

        .btn-modal {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 80px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            color: white;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--error-color);
            color: white;
        }

        .btn-danger:hover {
            background: var(--error-color);
            color: white;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-1px);
        }

        /* Responsive Modal */
        @media (max-width: 576px) {
            .modal-dialog {
                max-width: 95%;
                margin: 10px;
            }
            
            .modal-header, .modal-body, .modal-footer {
                padding: 15px;
            }
            
            .modal-title {
                font-size: 1.1rem;
            }
        }

        .tabs-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .tabs-nav {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            border-bottom: 1px solid #e9ecef;
            background: #f8f9fa;
        }

        .tab-nav-item {
            flex: 1;
            text-align: center;
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }

        .tab-nav-item:hover {
            background: #e9ecef;
        }

        .tab-nav-item.active {
            background: #fff;
            border-bottom-color: #ff6b35;
            color: #ff6b35;
        }

        .tab-content {
            display: none;
            padding: 20px;
        }

        .tab-content.active {
            display: block;
        }

        .book-request-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .book-request-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .stat-card {
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-success {
            background-color: #28a745;
            color: #fff;
        }

        .badge-danger {
            background-color: #dc3545;
            color: #fff;
        }

        .due-date-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }

        .fine-info {
            background: #f8d7da;
            padding: 8px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }

        .rejection-reason {
            background: #f8d7da;
            padding: 8px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</body>
</html>
