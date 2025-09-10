<?php
/* Student Search Books Page */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

$currentUser = getCurrentUser();

// Search functionality
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12; // Books per page
$offset = ($page - 1) * $limit;

// Get books with search filters
$books = searchBooks($search, $category, '', $limit, $offset);
$totalBooks = getSearchBooksCount($search, $category, '');
$totalPages = ceil($totalBooks / $limit);

// Get categories for filter
$categories = getBookCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Books - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="search-books.php" class="nav-link active"><i class="fas fa-search"></i> Search Books</a></li>
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
                <h1 class="page-title">Search Books</h1>
                <p class="page-subtitle">Find and explore books in our library collection</p>
            </div>

            <!-- Search and Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="search-books.php" class="search-form">
                        <div class="search-row">
                            <div class="search-input-container">
                                <i class="fas fa-search search-icon"></i>
                                <input 
                                    type="text" 
                                    name="search" 
                                    class="search-input" 
                                    placeholder="Search by title, author, or ISBN..." 
                                    value="<?php echo htmlspecialchars($search); ?>"
                                >
                            </div>
                            <div class="category-select-container">
                                <select name="category" class="category-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                                <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="search-button-container">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Summary -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="text-secondary mb-0">
                    <?php if ($search || $category): ?>
                        Found <?php echo $totalBooks; ?> books
                        <?php if ($search): ?>
                            for "<?php echo htmlspecialchars($search); ?>"
                        <?php endif; ?>
                        <?php if ($category): ?>
                            in "<?php echo htmlspecialchars($category); ?>"
                        <?php endif; ?>
                    <?php else: ?>
                        Showing all <?php echo $totalBooks; ?> books
                    <?php endif; ?>
                </p>
                <?php if ($search || $category): ?>
                    <a href="search-books.php" class="btn btn-secondary btn-sm" style="padding: 0.5rem 1rem; border: 0.5px solid var(--border-dark);">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>

            <!-- Books Grid -->
            <?php if (empty($books)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-book-open text-muted mb-3" style="font-size: 3rem;"></i>
                        <h3 class="text-muted">No books found</h3>
                        <p class="text-secondary">Try adjusting your search criteria or browse all books.</p>
                        <a href="search-books.php" class="btn btn-primary">Browse All Books</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($books as $book): ?>
                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="card book-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span class="badge badge-primary"><?php echo htmlspecialchars($book['category']); ?></span>
                                        <?php if ($book['available_quantity'] > 0): ?>
                                            <span class="badge badge-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Not Available</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                    <p class="text-secondary">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($book['author']); ?>
                                    </p>
                                    
                                    <?php if (!empty($book['publisher'])): ?>
                                        <p class="text-secondary">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($book['publisher']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($book['isbn'])): ?>
                                        <p class="text-secondary">
                                            <i class="fas fa-barcode"></i> <?php echo htmlspecialchars($book['isbn']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($book['description'])): ?>
                                        <p class="text-secondary">
                                            <?php echo substr(htmlspecialchars($book['description']), 0, 100); ?>
                                            <?php echo strlen($book['description']) > 100 ? '...' : ''; ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <?php echo $book['available_quantity']; ?>/<?php echo $book['quantity']; ?> available
                                        </small>
                                        <?php if ($book['available_quantity'] > 0): ?>
                                            <button class="btn btn-primary btn-sm" style="padding: 0.375rem 0.75rem;" onclick="requestBook(<?php echo $book['book_id']; ?>)">
                                                <i class="fas fa-plus"></i> Make a Borrow Request
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-clock"></i> Wait for Stock
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&page=<?php echo $page - 1; ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>&page=<?php echo $page + 1; ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
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

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h4>Confirm Action</h4>
                <button type="button" class="modal-close" onclick="closeModal('confirmationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div id="confirmationMessage" style="padding: 20px; text-align: center; font-size: 16px;">
                    <!-- Message will be inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-cancel" onclick="closeModal('confirmationModal')">Cancel</button>
                <button type="button" class="btn-modal btn-success" id="confirmButton" onclick="confirmAction()">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // Global variable to store the book ID for confirmation
        let pendingBookId = null;

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

        function showConfirmation(title, message, onConfirm) {
            const titleEl = document.querySelector('#confirmationModal h4');
            const messageEl = document.getElementById('confirmationMessage');
            
            titleEl.textContent = title;
            messageEl.innerHTML = `
                <i class="fas fa-question-circle" style="font-size: 48px; color: #17a2b8; margin-bottom: 15px;"></i>
                <div>${message}</div>
            `;
            
            // Store the confirm callback
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

        // Updated requestBook function
        function requestBook(bookId) {
            showConfirmation('Request Book', 'Would you like to request this book?', function() {
                // Show loading state
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Requesting...';
                button.disabled = true;
                
                // Make AJAX request
                fetch('../api/book-requests.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=submit_request&book_id=' + bookId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Success!', data.message, 'success');
                        // Reload page to update button state after a delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showNotification('Error', data.message, 'error');
                        // Restore button
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Error', 'An error occurred while submitting your request.', 'error');
                    // Restore button
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            });
        }

        // Auto-submit form on category change
        document.querySelector('select[name="category"]').addEventListener('change', function() {
            this.closest('form').submit();
        });
    </script>
</body>
</html>

<style>
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
    background: var(--primary-color);
    color: #fff;
}

.btn-primary:hover {
    background: var(--primary-hover);
    color: #fff;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
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

/* Search Form Styling */
.search-form {
    width: 100%;
}

.search-row {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
}

.search-input-container {
    flex: 2;
    position: relative;
    min-width: 200px;
}

.search-input-container .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 14px;
    z-index: 2;
}

.search-input {
    width: 100%;
    height: 44px;
    padding: 10px 12px 10px 36px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    transition: all 0.3s ease;
    box-sizing: border-box;
}

.search-input:focus {
    border-color: #ff6b35;
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.category-select-container {
    flex: 1;
    min-width: 150px;
}

.category-select {
    width: 100%;
    height: 44px;
    padding: 10px 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    cursor: pointer;
    transition: all 0.3s ease;
    box-sizing: border-box;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 12px center;
    background-repeat: no-repeat;
    background-size: 16px;
    padding-right: 40px;
}

.category-select:focus {
    border-color: #ff6b35;
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.search-button-container {
    flex-shrink: 0;
}

.search-btn {
    height: 44px;
    padding: 10px 20px;
    background: #ff6b35;
    color: white;
    border: 2px solid #ff6b35;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    box-sizing: border-box;
}

.search-btn:hover {
    background: #e55a2b;
    border-color: #e55a2b;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.search-btn:active {
    transform: translateY(0);
}

/* Responsive Design */
@media (max-width: 768px) {
    .search-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .search-input-container,
    .category-select-container {
        width: 100%;
        flex: none;
    }
    
    .search-button-container {
        width: 100%;
    }
    
    .search-btn {
        width: 100%;
        justify-content: center;
    }
}

.book-card {
    height: 100%;
    transition: var(--transition);
    margin-bottom: 30px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    overflow: hidden;
}

.book-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.book-card .card-body {
    padding: 16px;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.book-card .d-flex.justify-content-between.align-items-start {
    margin-bottom: 8px;
}

.book-card .badge {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: 500;
}

.book-card .badge-primary {
    background-color: #E1E1E1;
    color: var(--text-secondary);
    text-transform: uppercase;
}

.book-card .badge-success {
    background-color: #28a745;
    color: white;
}

.book-card .badge-danger {
    background-color: #dc3545;
    color: white;
}

.book-card .card-title {
    font-size: 17px;
    font-weight: 600;
    margin: 0 0 10px 0;
    line-height: 1.3;
    color: #2c3e50;
}

.book-card .text-secondary {
    font-size: 14px;
    margin-bottom: 7px;
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 6px;
    line-height: 1.4;
}

.book-card .text-secondary i {
    width: 12px;
    font-size: 12px;
    color: #999;
}

.book-card .text-secondary:last-of-type {
    margin-bottom: 14px;
}

.book-card .d-flex.justify-content-between.align-items-center {
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid #f8f9fa;
}

.book-card .text-muted {
    font-size: 13px;
    color: #999;
}

.book-card .btn {
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 500;
    border: none;
    transition: all 0.2s ease;
}

.book-card .btn-primary {
    background-color: #ff6b35;
    color: white;
}

.book-card .btn-primary:hover {
    background-color: #e55a2b;
    transform: translateY(-1px);
}

.book-card .btn-secondary {
    background-color: #6c757d;
    color: white;
}

/* Row spacing fix */
.row {
    margin-left: -10px;
    margin-right: -10px;
}

.row > [class*="col-"] {
    padding: 10px;
    margin-bottom: 0;
}

/* Remove default Bootstrap card margins */
.card {
    margin-bottom: 0;
}

.pagination {
    display: flex;
    list-style: none;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
}

.page-item .page-link {
    padding: 0.5rem 0.75rem;
    color: var(--text-secondary);
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    text-decoration: none;
    transition: var(--transition);
}

.page-item.active .page-link {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.page-item .page-link:hover {
    background: var(--primary-light);
    color: var(--primary-color);
    border-color: var(--primary-color);
}
</style>
