<?php
/* Admin Book Requests Management Page */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();
if (!isAdmin()) {
    header('Location: ../auth.php');
    exit;
}

$currentUser = getCurrentUser();

// handle status filter
$statusFilter = $_GET['status'] ?? 'pending';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// get requests based on status
if ($statusFilter === 'all') {
    $requests = [];
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT br.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
                             b.title, b.author, b.isbn, b.category, b.available_quantity,
                             a.full_name as approved_by_name
                             FROM book_requests br 
                             JOIN users u ON br.user_id = u.user_id 
                             JOIN books b ON br.book_id = b.book_id 
                             LEFT JOIN admin a ON br.approved_by = a.admin_id
                             ORDER BY br.request_date DESC 
                             LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $requests = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get all requests error: " . $e->getMessage());
    }
} else {
    $requests = [];
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT br.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
                             b.title, b.author, b.isbn, b.category, b.available_quantity,
                             a.full_name as approved_by_name
                             FROM book_requests br 
                             JOIN users u ON br.user_id = u.user_id 
                             JOIN books b ON br.book_id = b.book_id 
                             LEFT JOIN admin a ON br.approved_by = a.admin_id
                             WHERE br.status = :status
                             ORDER BY br.request_date DESC 
                             LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':status', $statusFilter);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $requests = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get requests by status error: " . $e->getMessage());
    }
}

// get counts for each status
$statusCounts = [];
try {
    $db = getDB();
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM book_requests GROUP BY status");
    while ($row = $stmt->fetch()) {
        $statusCounts[$row['status']] = $row['count'];
    }
} catch (Exception $e) {
    error_log("Get status counts error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Requests - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Filter Tabs Styling */
        .dashboard-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 20px;
            background: #f8f9fa;
            color: #6c757d;
            text-decoration: none;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            transition: all 0.3s ease;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            background: #e9ecef;
            color: #495057;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .tab-btn.active {
            background: #ff6b35;
            color: white;
            border-color: #ff6b35;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
        }

        .tab-btn i {
            font-size: 14px;
        }

        /* Table Styling */
        .requests-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-collapse: collapse;
        }

        .requests-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }

        .requests-table td {
            padding: 18px 15px;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: top;
            font-size: 15px;
            line-height: 1.5;
        }

        .requests-table tbody tr {
            transition: all 0.2s ease;
        }

        .requests-table tbody tr:hover {
            background: #f8f9fa;
        }

        .requests-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-issued { background: #cce7ff; color: #004085; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-returned { background: #e2e3e5; color: #383d41; }

        /* Action Buttons */
        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-right: 8px;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background: #218838;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        .btn-return {
            background: #17a2b8;
            color: white;
        }

        .btn-return:hover {
            background: #138496;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(2px);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 500px;
            max-width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            transition: all 0.3s ease;
        }

        .modal.show .modal-content {
            transform: scale(1);
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ff6b35;
            color: white;
        }

        .modal-header h4 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .btn-modal {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .dashboard-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e1e5e9;
        }

        /* Notification Modal Specific Styling */
        #notificationModal .modal-header.success {
            background: #28a745;
        }

        #notificationModal .modal-header.error {
            background: #dc3545;
        }

        #notificationModal .modal-header.warning {
            background: #ffc107;
            color: #212529;
        }

        #notificationModal .modal-header.info {
            background: #17a2b8;
        }

        .notification-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .notification-icon.success {
            color: #28a745;
        }

        .notification-icon.error {
            color: #dc3545;
        }

        .notification-icon.warning {
            color: #ffc107;
        }

        .notification-icon.info {
            color: #17a2b8;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                justify-content: center;
                text-align: center;
            }
            
            .requests-table {
                font-size: 13px;
            }
            
            .requests-table th,
            .requests-table td {
                padding: 12px 8px;
            }
            
            .modal-content {
                width: 95%;
                margin: 20px;
            }
        }
    </style>
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
                    <a href="book-requests.php" class="nav-link active">
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
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Admin Main Content -->
        <main class="admin-main">
            <!-- Page Header -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">
                    <i class="fas fa-hand-paper"></i> Book Requests
                </h1>
                <p class="dashboard-subtitle">Manage student book requests and approvals</p>
            </div>

            <!-- Status Filter Tabs -->
            <div class="dashboard-tabs mb-4">
                <a href="?status=pending" class="tab-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> 
                    Pending (<?php echo $statusCounts['pending'] ?? 0; ?>)
                </a>
                <a href="?status=issued" class="tab-btn <?php echo $statusFilter === 'issued' ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i> 
                    Issued (<?php echo $statusCounts['issued'] ?? 0; ?>)
                </a>
                <a href="?status=rejected" class="tab-btn <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">
                    <i class="fas fa-times"></i> 
                    Rejected (<?php echo $statusCounts['rejected'] ?? 0; ?>)
                </a>
                <a href="?status=returned" class="tab-btn <?php echo $statusFilter === 'returned' ? 'active' : ''; ?>">
                    <i class="fas fa-undo"></i> 
                    Returned (<?php echo $statusCounts['returned'] ?? 0; ?>)
                </a>
                <a href="?status=all" class="tab-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> 
                    All Requests
                </a>
            </div>

            <?php if (empty($requests)): ?>
                <div class="dashboard-card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-hand-paper text-muted mb-3" style="font-size: 3rem;"></i>
                        <h3 class="text-muted">No <?php echo ucfirst($statusFilter); ?> Requests</h3>
                        <p class="text-secondary">There are no <?php echo $statusFilter === 'all' ? '' : $statusFilter; ?> book requests at the moment.</p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Requests Table -->
                <div class="requests-table-container">
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>STUDENT</th>
                                <th>BOOK</th>
                                <th>REQUEST DATE</th>
                                <th>STATUS</th>
                                <th>DUE DATE</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr id="request-<?php echo $request['request_id']; ?>">
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($request['user_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($request['user_email']); ?></small>
                                            <?php if ($request['user_phone']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($request['user_phone']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($request['title']); ?></strong>
                                            <br><small class="text-muted">by <?php echo htmlspecialchars($request['author']); ?></small>
                                            <br><span class="badge badge-secondary"><?php echo htmlspecialchars($request['category']); ?></span>
                                            <small class="text-muted">(<?php echo $request['available_quantity']; ?> available)</small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($request['request_date'])); ?>
                                        <br><small class="text-muted"><?php echo date('g:i A', strtotime($request['request_date'])); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'pending' => 'status-pending',
                                            'approved' => 'status-approved', 
                                            'issued' => 'status-issued',
                                            'rejected' => 'status-rejected',
                                            'returned' => 'status-returned'
                                        ];
                                        ?>
                                        <span class="status-badge <?php echo $statusClass[$request['status']] ?? 'status-pending'; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                        <?php if ($request['approved_by_name']): ?>
                                            <br><small class="text-muted">by <?php echo htmlspecialchars($request['approved_by_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($request['due_date']): ?>
                                                <?php echo date('M j, Y', strtotime($request['due_date'])); ?>
                                                <?php if ($request['status'] === 'issued' && strtotime($request['due_date']) < time()): ?>
                                                    <br><small class="text-danger">Overdue</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($request['status'] === 'pending'): ?>
                                                <?php if ($request['available_quantity'] > 0): ?>
                                                    <button class="btn-action btn-approve" onclick="approveRequest(<?php echo $request['request_id']; ?>)">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn-action" style="background: #ffc107; color: #212529;" disabled title="Book not available">
                                                        <i class="fas fa-exclamation-triangle"></i> No Stock
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn-action btn-reject" onclick="rejectRequest(<?php echo $request['request_id']; ?>)">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            <?php elseif ($request['status'] === 'issued'): ?>
                                                <button class="btn-action btn-return" onclick="returnBook(<?php echo $request['request_id']; ?>)">
                                                    <i class="fas fa-undo"></i> Mark Returned
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">No actions available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Approval Modal -->
    <div class="modal" id="approvalModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Approve Book Request</h4>
                <button type="button" class="modal-close" onclick="closeModal('approvalModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="approvalForm">
                    <input type="hidden" id="approvalRequestId" name="request_id">
                    <div class="form-group">
                        <label for="approvalNotes">Notes (Optional)</label>
                        <textarea class="form-control" id="approvalNotes" name="notes" rows="3" placeholder="Any additional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal('approvalModal')">Cancel</button>
                <button type="button" class="btn-modal btn-success" onclick="submitApproval()">
                    <i class="fas fa-check"></i> Approve Request
                </button>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div class="modal" id="rejectionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Reject Book Request</h4>
                <button type="button" class="modal-close" onclick="closeModal('rejectionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="rejectionForm">
                    <input type="hidden" id="rejectionRequestId" name="request_id">
                    <div class="form-group">
                        <label for="rejectionReason">Reason for Rejection *</label>
                        <textarea class="form-control" id="rejectionReason" name="reason" rows="3" placeholder="Please provide a reason for rejection..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal('rejectionModal')">Cancel</button>
                <button type="button" class="btn-modal btn-danger" onclick="submitRejection()">
                    <i class="fas fa-times"></i> Reject Request
                </button>
            </div>
        </div>
    </div>

    <!-- Return Modal -->
    <div class="modal" id="returnModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Return Book</h4>
                <button type="button" class="modal-close" onclick="closeModal('returnModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form id="returnForm">
                    <input type="hidden" id="returnRequestId" name="request_id">
                    <div class="form-group">
                        <label for="returnFine">Fine Amount (if any)</label>
                        <input type="number" class="form-control" id="returnFine" name="fine" min="0" step="0.01" value="0.00">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal('returnModal')">Cancel</button>
                <button type="button" class="btn-modal btn-primary" onclick="submitReturn()">
                    <i class="fas fa-undo"></i> Mark as Returned
                </button>
            </div>
        </div>
    </div>

    <!-- Notification Modal -->
    <div class="modal" id="notificationModal">
        <div class="modal-content">
            <div class="modal-header" id="notificationHeader">
                <h4 id="notificationTitle">Notification</h4>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
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
            
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

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
            
            const form = modal.querySelector('form');
            if (form) {
                form.reset();
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                const modalId = event.target.id;
                closeModal(modalId);
            }
        });

        // Request Management Functions
        function approveRequest(requestId) {
            document.getElementById('approvalRequestId').value = requestId;
            openModal('approvalModal');
        }

        function rejectRequest(requestId) {
            document.getElementById('rejectionRequestId').value = requestId;
            openModal('rejectionModal');
        }

        function returnBook(requestId) {
            document.getElementById('returnRequestId').value = requestId;
            openModal('returnModal');
        }

        // Form Submission Functions
        function submitApproval() {
            const requestId = document.getElementById('approvalRequestId').value;
            const notes = document.getElementById('approvalNotes').value;
            
            const submitBtn = event.target;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('../api/book-requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'approve_request',
                    request_id: requestId,
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('approvalModal');
                    showNotification('Success!', data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification('Error', data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Approve Request';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error', 'An error occurred while processing the request.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check"></i> Approve Request';
            });
        }

        function submitRejection() {
            const requestId = document.getElementById('rejectionRequestId').value;
            const reason = document.getElementById('rejectionReason').value;
            
            if (!reason.trim()) {
                showNotification('Warning', 'Please provide a reason for rejection.', 'warning');
                return;
            }
            
            const submitBtn = event.target;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('../api/book-requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'reject_request',
                    request_id: requestId,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('rejectionModal');
                    showNotification('Success!', data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification('Error', data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-times"></i> Reject Request';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error', 'An error occurred while processing the request.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-times"></i> Reject Request';
            });
        }

        function submitReturn() {
            const requestId = document.getElementById('returnRequestId').value;
            const fine = document.getElementById('returnFine').value || 0;
            
            const submitBtn = event.target;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            fetch('../api/book-requests.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'return_book',
                    request_id: requestId,
                    fine: fine
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeModal('returnModal');
                    showNotification('Success!', data.message, 'success');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification('Error', data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-undo"></i> Mark as Returned';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error', 'An error occurred while processing the request.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-undo"></i> Mark as Returned';
            });
        }
    </script>
</body>
</html>
