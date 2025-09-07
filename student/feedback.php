<?php
/**
 * Student Feedback Page
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

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);

    // Validate required fields
    if (empty($subject) || empty($message) || empty($category)) {
        $error = 'Subject, category, and message are required.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Please provide a valid rating between 1 and 5 stars.';
    } else {
        try {
            $db = getDB();
            
            // Insert feedback
            $insertQuery = "INSERT INTO feedback (user_id, subject, category, message, rating, created_at, status) 
                           VALUES (?, ?, ?, ?, ?, NOW(), 'pending')";
            $stmt = $db->prepare($insertQuery);
            $stmt->execute([$currentUser['user_id'], $subject, $category, $message, $rating]);
            
            $success = 'Thank you for your feedback! We appreciate your input and will review it soon.';
            
            // Clear form data
            $_POST = [];
            
        } catch (Exception $e) {
            $error = 'An error occurred while submitting your feedback. Please try again.';
            error_log("Feedback submission error: " . $e->getMessage());
        }
    }
}

// Get user's previous feedback
$feedbackHistory = [];
try {
    $db = getDB();
    $historyQuery = "SELECT * FROM feedback WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = $db->prepare($historyQuery);
    $stmt->execute([$currentUser['user_id']]);
    $feedbackHistory = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Feedback history error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - <?php echo SITE_NAME; ?></title>
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
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a></li>
                    <li><a href="feedback.php" class="nav-link active"><i class="fas fa-comment"></i> Feedback</a></li>
                    <li><a href="../includes/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Send Feedback</h1>
                <p class="page-subtitle">Help us improve our library services with your valuable feedback</p>
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
                <!-- Feedback Form -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-comment-alt"></i> Submit New Feedback
                            </h3>
                        </div>
                        <form method="POST" action="feedback.php" id="feedbackForm">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="subject" class="form-label">Subject *</label>
                                            <input 
                                                type="text" 
                                                id="subject" 
                                                name="subject" 
                                                class="form-control" 
                                                value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>"
                                                placeholder="Brief subject of your feedback"
                                                required
                                            >
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="category" class="form-label">Category *</label>
                                            <select id="category" name="category" class="form-control" required>
                                                <option value="">Select a category</option>
                                                <option value="service_quality" <?php echo ($_POST['category'] ?? '') === 'service_quality' ? 'selected' : ''; ?>>Service Quality</option>
                                                <option value="book_collection" <?php echo ($_POST['category'] ?? '') === 'book_collection' ? 'selected' : ''; ?>>Book Collection</option>
                                                <option value="staff_behavior" <?php echo ($_POST['category'] ?? '') === 'staff_behavior' ? 'selected' : ''; ?>>Staff Behavior</option>
                                                <option value="facilities" <?php echo ($_POST['category'] ?? '') === 'facilities' ? 'selected' : ''; ?>>Facilities & Infrastructure</option>
                                                <option value="website" <?php echo ($_POST['category'] ?? '') === 'website' ? 'selected' : ''; ?>>Website & Online Services</option>
                                                <option value="suggestion" <?php echo ($_POST['category'] ?? '') === 'suggestion' ? 'selected' : ''; ?>>Suggestions</option>
                                                <option value="complaint" <?php echo ($_POST['category'] ?? '') === 'complaint' ? 'selected' : ''; ?>>Complaint</option>
                                                <option value="other" <?php echo ($_POST['category'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Overall Rating *</label>
                                    <div class="rating-container">
                                        <div class="star-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <label class="star-label">
                                                    <input 
                                                        type="radio" 
                                                        name="rating" 
                                                        value="<?php echo $i; ?>" 
                                                        <?php echo (intval($_POST['rating'] ?? 0) === $i) ? 'checked' : ''; ?>
                                                        required
                                                    >
                                                    <i class="far fa-star" data-rating="<?php echo $i; ?>"></i>
                                                </label>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-text">Please rate your overall experience</span>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="message" class="form-label">Your Message *</label>
                                    <textarea 
                                        id="message" 
                                        name="message" 
                                        class="form-control" 
                                        rows="6" 
                                        placeholder="Please share your detailed feedback, suggestions, or concerns..."
                                        required
                                    ><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">Minimum 10 characters required</small>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Submit Feedback
                                </button>
                                <button type="reset" class="btn btn-secondary ml-2">
                                    <i class="fas fa-undo"></i> Clear Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-md-4">
                    <!-- Feedback Guidelines -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-info-circle"></i> Feedback Guidelines
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="feedback-guidelines">
                                <div class="guideline-item mb-3">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Be specific and constructive in your feedback</span>
                                </div>
                                <div class="guideline-item mb-3">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Include relevant details about your experience</span>
                                </div>
                                <div class="guideline-item mb-3">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Suggest improvements where applicable</span>
                                </div>
                                <div class="guideline-item mb-3">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <span>Be respectful and professional</span>
                                </div>
                                <div class="guideline-item">
                                    <i class="fas fa-clock text-info"></i>
                                    <span>We typically respond within 2-3 business days</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">
                                <i class="fas fa-phone"></i> Other Ways to Reach Us
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="contact-info">
                                <div class="contact-item mb-3">
                                    <i class="fas fa-envelope text-primary"></i>
                                    <div>
                                        <strong>Email</strong>
                                        <br>library@university.edu
                                    </div>
                                </div>
                                <div class="contact-item mb-3">
                                    <i class="fas fa-phone text-success"></i>
                                    <div>
                                        <strong>Phone</strong>
                                        <br>(555) 123-4567
                                    </div>
                                </div>
                                <div class="contact-item mb-3">
                                    <i class="fas fa-map-marker-alt text-danger"></i>
                                    <div>
                                        <strong>Visit Us</strong>
                                        <br>Main Library, Room 101
                                        <br>University Campus
                                    </div>
                                </div>
                                <div class="contact-item">
                                    <i class="fas fa-clock text-warning"></i>
                                    <div>
                                        <strong>Hours</strong>
                                        <br>Mon-Fri: 8:00 AM - 8:00 PM
                                        <br>Sat-Sun: 10:00 AM - 6:00 PM
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback History -->
            <?php if (!empty($feedbackHistory)): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-history"></i> Your Recent Feedback
                                </h3>
                            </div>
                            <div class="card-body">
                                <div class="feedback-history">
                                    <?php foreach ($feedbackHistory as $feedback): ?>
                                        <div class="feedback-item">
                                            <div class="feedback-header">
                                                <h5 class="feedback-subject"><?php echo htmlspecialchars($feedback['subject']); ?></h5>
                                                <div class="feedback-meta">
                                                    <span class="badge badge-<?php 
                                                        echo $feedback['status'] === 'pending' ? 'warning' : 
                                                            ($feedback['status'] === 'reviewed' ? 'success' : 'info'); 
                                                    ?>">
                                                        <?php echo ucfirst($feedback['status']); ?>
                                                    </span>
                                                    <span class="feedback-date">
                                                        <?php echo date('M d, Y', strtotime($feedback['created_at'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="feedback-content">
                                                <div class="feedback-rating mb-2">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fa<?php echo $i <= $feedback['rating'] ? 's' : 'r'; ?> fa-star text-warning"></i>
                                                    <?php endfor; ?>
                                                    <span class="ml-2">Rating: <?php echo $feedback['rating']; ?>/5</span>
                                                </div>
                                                <p class="feedback-message"><?php echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                                                <div class="feedback-category">
                                                    <small class="text-muted">
                                                        Category: <?php echo ucwords(str_replace('_', ' ', $feedback['category'])); ?>
                                                    </small>
                                                </div>
                                                
                                                <?php if (!empty($feedback['admin_response'])): ?>
                                                    <div class="admin-response mt-3">
                                                        <h6><i class="fas fa-reply text-primary"></i> Library Response:</h6>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($feedback['admin_response'])); ?></p>
                                                        <?php if (!empty($feedback['response_date'])): ?>
                                                            <small class="text-muted">
                                                                Responded on <?php echo date('M d, Y', strtotime($feedback['response_date'])); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Star rating functionality
        document.querySelectorAll('.star-label input').forEach((input, index) => {
            input.addEventListener('change', function() {
                updateStarDisplay(this.value);
                updateRatingText(this.value);
            });
        });

        document.querySelectorAll('.star-label').forEach((label, index) => {
            label.addEventListener('mouseenter', function() {
                const rating = this.querySelector('input').value;
                highlightStars(rating);
            });
        });

        document.querySelector('.star-rating').addEventListener('mouseleave', function() {
            const checkedRating = document.querySelector('.star-label input:checked');
            if (checkedRating) {
                updateStarDisplay(checkedRating.value);
            } else {
                resetStars();
            }
        });

        function updateStarDisplay(rating) {
            document.querySelectorAll('.star-label i').forEach((star, index) => {
                if (index < rating) {
                    star.className = 'fas fa-star';
                } else {
                    star.className = 'far fa-star';
                }
            });
        }

        function highlightStars(rating) {
            document.querySelectorAll('.star-label i').forEach((star, index) => {
                if (index < rating) {
                    star.className = 'fas fa-star';
                    star.style.color = '#ffc107';
                } else {
                    star.className = 'far fa-star';
                    star.style.color = '#dee2e6';
                }
            });
        }

        function resetStars() {
            document.querySelectorAll('.star-label i').forEach(star => {
                star.className = 'far fa-star';
                star.style.color = '#dee2e6';
            });
        }

        function updateRatingText(rating) {
            const texts = {
                '1': 'Poor - Needs significant improvement',
                '2': 'Fair - Could be better',
                '3': 'Good - Satisfactory service',
                '4': 'Very Good - Exceeds expectations',
                '5': 'Excellent - Outstanding service'
            };
            document.querySelector('.rating-text').textContent = texts[rating] || 'Please rate your experience';
        }

        // Form validation
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            const message = document.getElementById('message').value.trim();
            if (message.length < 10) {
                e.preventDefault();
                alert('Please provide a more detailed message (at least 10 characters).');
                return false;
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>

    <style>
        .star-rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .star-label {
            cursor: pointer;
            margin: 0;
        }

        .star-label input {
            display: none;
        }

        .star-label i {
            font-size: 1.5rem;
            color: #dee2e6;
            transition: color 0.2s ease;
        }

        .star-label:hover i,
        .star-label input:checked ~ i {
            color: #ffc107;
        }

        .rating-container {
            padding: 1rem;
            background: var(--light-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .rating-text {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-style: italic;
        }

        .feedback-guidelines .guideline-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .contact-info .contact-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .contact-item i {
            width: 20px;
            text-align: center;
            margin-top: 2px;
        }

        .feedback-history .feedback-item {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .feedback-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
        }

        .feedback-subject {
            margin: 0;
            color: var(--primary-color);
        }

        .feedback-date {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .admin-response {
            background: var(--light-bg);
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            border-radius: 0 8px 8px 0;
        }

        @media (max-width: 768px) {
            .feedback-header {
                flex-direction: column;
                gap: 0.5rem;
            }

            .feedback-meta {
                align-items: flex-start;
            }
        }
    </style>
</body>
</html>
