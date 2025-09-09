<?php
/**
 * Library Management System - Main Login Page
 * This is the entry point for the library management system
 */

require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// If user is already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['register'])) {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? 'user';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } else {
        $user = loginUser($email, $password, $userType);
        
        if ($user) {
            // Successful login, redirect based on role
            if ($userType === 'admin' || $user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: student/dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid credentials. Please try again.';
        }
    }
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all required fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'student',
            'phone' => $phone,
            'address' => $address
        ];
        
        if (registerUser($userData)) {
            $success = 'Registration successful! You can now login.';
        } else {
            $error = 'Registration failed. Email might already be in use.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Welcome</title>
    <link rel="stylesheet" href="assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-book-open"></i>
                </div>
                <h1><?php echo htmlspecialchars(getSetting('library_name', 'Library Management System')); ?></h1>
                <p>Modern, Secure & Efficient Library Solution</p>
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

            <!-- Auth Tabs -->
            <div class="auth-tabs">
                <button class="tab-button active" onclick="switchTab('login')">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <button class="tab-button" onclick="switchTab('register')">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </div>

            <!-- Login Form -->
            <form class="auth-form active" id="login-form" method="POST" action="">
                <!-- User Type Selection -->
                <div class="user-type-group">
                    <div class="user-type-option">
                        <input type="radio" name="user_type" value="user" id="user-student" checked>
                        <label for="user-student" class="user-type-label">
                            <i class="fas fa-user-graduate"></i>
                            <span>Student</span>
                        </label>
                    </div>
                    <div class="user-type-option">
                        <input type="radio" name="user_type" value="admin" id="user-admin">
                        <label for="user-admin" class="user-type-label">
                            <i class="fas fa-user-shield"></i>
                            <span>Admin</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="login_email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input 
                        type="email" 
                        id="login_email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Enter your email address"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="login_password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        type="password" 
                        id="login_password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <!-- Registration Form -->
            <form class="auth-form" id="register-form" method="POST" action="">
                <input type="hidden" name="register" value="1">
                
                <!-- Row 1: Name and Email -->
                <div class="register-form-row">
                    <div class="form-group">
                        <label for="reg_name" class="form-label">
                            <i class="fas fa-user"></i> Full Name
                        </label>
                        <input 
                            type="text" 
                            id="reg_name" 
                            name="name" 
                            class="form-control" 
                            placeholder="Enter your full name"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="reg_email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address
                        </label>
                        <input 
                            type="email" 
                            id="reg_email" 
                            name="email" 
                            class="form-control" 
                            placeholder="Enter your email address"
                            required
                        >
                    </div>
                </div>

                <!-- Row 2: Password and Confirm Password -->
                <div class="register-form-row">
                    <div class="form-group">
                        <label for="reg_password" class="form-label">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input 
                            type="password" 
                            id="reg_password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Choose a strong password"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="reg_confirm_password" class="form-label">
                            <i class="fas fa-lock"></i> Confirm Password
                        </label>
                        <input 
                            type="password" 
                            id="reg_confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            placeholder="Confirm your password"
                            required
                        >
                    </div>
                </div>

                <!-- Row 3: Phone (full width) -->
                <div class="register-form-full-width">
                    <div class="form-group">
                        <label for="reg_phone" class="form-label">
                            <i class="fas fa-phone"></i> Phone Number (Optional)
                        </label>
                        <input 
                            type="tel" 
                            id="reg_phone" 
                            name="phone" 
                            class="form-control" 
                            placeholder="Enter your phone number"
                        >
                    </div>
                </div>

                <!-- Row 4: Address (full width) -->
                <div class="register-form-full-width">
                    <div class="form-group">
                        <label for="reg_address" class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Address (Optional)
                        </label>
                        <textarea 
                            id="reg_address" 
                            name="address" 
                            class="form-control" 
                            placeholder="Enter your address"
                            rows="2"
                        ></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <!-- Footer with demo credentials -->
            <div class="auth-footer">
                <div id="demo-credentials">
                    <div class="demo-title">
                        <i class="fas fa-key"></i>
                        <span>Quick Login</span>
                    </div>
                    <div class="demo-cards">
                        <div class="demo-card" onclick="fillDemoCredentials('student')">
                            <div class="demo-card-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="demo-card-content">
                                <h4>Student Access</h4>
                                <p>student@lms.com</p>
                            </div>
                        </div>
                        <div class="demo-card" onclick="fillDemoCredentials('admin')">
                            <div class="demo-card-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div class="demo-card-content">
                                <h4>Admin Access</h4>
                                <p>admin@lms.com</p>
                            </div>
                        </div>
                    </div>
                </div>
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSetting('library_name', 'Library Management System')); ?>. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tab) {
            // Remove active class from all tabs and forms
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Get auth-card and demo credentials elements
            const authCard = document.querySelector('.auth-card');
            const demoCredentials = document.getElementById('demo-credentials');
            
            // Show corresponding form and manage demo credentials visibility
            if (tab === 'login') {
                document.getElementById('login-form').classList.add('active');
                demoCredentials.style.display = 'block';
                authCard.classList.remove('register-active');
            } else if (tab === 'register') {
                document.getElementById('register-form').classList.add('active');
                demoCredentials.style.display = 'none';
                authCard.classList.add('register-active');
            }
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const btn = form.querySelector('button[type="submit"]');
                    btn.innerHTML = '<i class="loading"></i> Please wait...';
                    btn.disabled = true;
                    
                    // Re-enable button after 3 seconds (in case of error)
                    setTimeout(() => {
                        btn.disabled = false;
                        btn.innerHTML = btn.getAttribute('data-original-text') || 'Submit';
                    }, 3000);
                });
            });

            // Store original button text
            document.querySelectorAll('button[type="submit"]').forEach(btn => {
                btn.setAttribute('data-original-text', btn.innerHTML);
            });
        });

        // Password strength indicator
        document.getElementById('reg_password')?.addEventListener('input', function() {
            const password = this.value;
            const strength = getPasswordStrength(password);
            // Add visual indicator logic here
        });

        function getPasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Fill demo credentials function
        function fillDemoCredentials(type) {
            const emailInput = document.getElementById('login_email');
            const passwordInput = document.getElementById('login_password');
            const userTypeRadios = document.querySelectorAll('input[name="user_type"]');
            
            if (type === 'admin') {
                emailInput.value = 'admin@lms.com';
                passwordInput.value = 'password';
                // Select admin radio button
                userTypeRadios.forEach(radio => {
                    if (radio.value === 'admin') {
                        radio.checked = true;
                    }
                });
            } else if (type === 'student') {
                emailInput.value = 'student@lms.com';
                passwordInput.value = 'password';
                // Select user radio button
                userTypeRadios.forEach(radio => {
                    if (radio.value === 'user') {
                        radio.checked = true;
                    }
                });
            }
            
            // Add a subtle animation to show the fields were filled
            emailInput.style.backgroundColor = 'var(--primary-light)';
            passwordInput.style.backgroundColor = 'var(--primary-light)';
            
            setTimeout(() => {
                emailInput.style.backgroundColor = '';
                passwordInput.style.backgroundColor = '';
            }, 1000);
        }
    </script>
</body>
</html>
