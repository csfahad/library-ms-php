<?php
/**
 * Student Search Books Page
 * Library Management System
 */

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require login
requireLogin();

// Get current user
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
                    <li><a href="search-books.php" class="nav-link active"><i class="fas fa-search"></i> Search Books</a></li>
                    <li><a href="my-books.php" class="nav-link"><i class="fas fa-book"></i> My Books</a></li>
                    <li><a href="history.php" class="nav-link"><i class="fas fa-history"></i> History</a></li>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a></li>
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
                    <form method="GET" action="search-books.php" class="row">
                        <div class="col-md-6 col-12">
                            <div class="search-box">
                                <i class="fas fa-search search-icon"></i>
                                <input 
                                    type="text" 
                                    name="search" 
                                    class="search-input" 
                                    placeholder="Search by title, author, or ISBN..." 
                                    value="<?php echo htmlspecialchars($search); ?>"
                                >
                            </div>
                        </div>
                        <div class="col-md-4 col-12">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                            <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 col-12">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search"></i> Search
                            </button>
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
                    <a href="search-books.php" class="btn btn-secondary btn-sm">
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
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <span class="badge badge-primary"><?php echo htmlspecialchars($book['category']); ?></span>
                                        <?php if ($book['available_quantity'] > 0): ?>
                                            <span class="badge badge-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Not Available</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                    <p class="text-secondary mb-2">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($book['author']); ?>
                                    </p>
                                    
                                    <?php if (!empty($book['publisher'])): ?>
                                        <p class="text-secondary mb-2">
                                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($book['publisher']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($book['isbn'])): ?>
                                        <p class="text-secondary mb-2">
                                            <i class="fas fa-barcode"></i> <?php echo htmlspecialchars($book['isbn']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($book['description'])): ?>
                                        <p class="text-secondary mb-3">
                                            <?php echo substr(htmlspecialchars($book['description']), 0, 100); ?>
                                            <?php echo strlen($book['description']) > 100 ? '...' : ''; ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <?php echo $book['available_quantity']; ?>/<?php echo $book['quantity']; ?> available
                                        </small>
                                        <?php if ($book['available_quantity'] > 0): ?>
                                            <button class="btn btn-primary btn-sm" onclick="requestBook(<?php echo $book['book_id']; ?>)">
                                                <i class="fas fa-plus"></i> Request
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-clock"></i> Wait List
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

    <script>
        function requestBook(bookId) {
            if (confirm('Would you like to request this book?')) {
                // Here you would typically make an AJAX request to request the book
                // For now, we'll redirect to a request page or show a success message
                alert('Book request feature will be implemented soon!');
            }
        }

        // Auto-submit form on category change
        document.querySelector('select[name="category"]').addEventListener('change', function() {
            this.closest('form').submit();
        });
    </script>
</body>
</html>

<style>
.book-card {
    height: 100%;
    transition: var(--transition);
}

.book-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
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
