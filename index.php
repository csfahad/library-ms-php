<?php
/**
 * Library Management System - Landing Page
 * Modern, responsive landing page for the library management system
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Get library settings
$libraryName = getSetting('library_name', 'Library Management System');
$libraryDescription = getSetting('library_description', 'Modern, Secure & Efficient Library Solution');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($libraryName); ?> - Digital Library Solution</title>
    <meta name="description" content="<?php echo htmlspecialchars($libraryDescription); ?>">
    <link rel="stylesheet" href="assets/css/fixed-modern.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Landing Page Specific Styles */
        .landing-page {
            min-height: 100vh;
            background: linear-gradient(135deg, 
                rgba(240, 114, 56, 0.08) 0%, 
                rgba(240, 114, 56, 0.04) 25%, 
                rgba(255, 255, 255, 0.95) 50%, 
                rgba(240, 114, 56, 0.04) 75%, 
                rgba(240, 114, 56, 0.08) 100%);
            position: relative;
        }

        .landing-page::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(240, 114, 56, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(240, 114, 56, 0.10) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        .landing-page > * {
            position: relative;
            z-index: 1;
        }

        /* Hero Background - Simplified since main gradient is on body */
        .hero {
            position: relative;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 4rem;
            padding: 10rem 2rem 6rem;
            text-align: center;
            max-width: 900px;
            margin: 0 auto;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-light);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 1rem 0;
            transition: var(--transition);
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-md);
        }

        .navbar-container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .navbar-brand .brand-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: var(--border-radius);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .nav-link:hover {
            color: var(--primary-color);
            background: var(--primary-light);
        }

        .btn-get-started {
            font-size: 0.9rem;
            background: var(--primary-color);
            color: white;
            padding: 0.7rem 1rem;
            border-radius: var(--border-radius-lg);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .btn-get-started:hover {
            background: var(--primary-hover);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .hero-text {
            max-width: 100%;
        }

        .hero-text h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--primary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .hero-text p {
            font-size: 1.4rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            line-height: 1.6;
            max-width: 750px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-actions {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius-lg);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-primary);
            padding: 1rem 2rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background: var(--bg-tertiary);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        /* Features Section */
        .features {
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            margin: 0 2rem;
            border-radius: var(--border-radius-xl);
            box-shadow: var(--shadow-lg);
            max-width: var(--max-width);
            margin-left: auto;
            margin-right: auto;
            border: 1px solid rgba(240, 114, 56, 0.1);
        }

        .features-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .features-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .features-header p {
            font-size: 1.1rem;
            color: var(--text-secondary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .feature-card {
            background: rgba(248, 250, 252, 0.8);
            backdrop-filter: blur(5px);
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            text-align: center;
            transition: var(--transition);
            border: 1px solid rgba(240, 114, 56, 0.08);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.95);
        }

        .feature-icon {
            background: var(--primary-light);
            color: var(--primary-color);
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .feature-card p {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Stats Section */
        .stats {
            background: var(--primary-color);
            color: white;
            padding: 3rem 2rem;
            margin: 4rem 0;
        }

        .stats-container {
            max-width: var(--max-width);
            margin: 0 auto;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
        }

        .stat-item h3 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: white;
        }

        .stat-item p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: var(--text-primary);
            color: white;
            padding: 3rem 2rem 1rem;
            text-align: center;
        }

        .footer-container {
            max-width: var(--max-width);
            margin: 0 auto;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .footer-brand .brand-icon {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem;
            border-radius: var(--border-radius-lg);
        }

        .footer p {
            opacity: 0.8;
            margin-bottom: 1rem;
            color: var(--text-muted);
        }

        .footer-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin: 2rem 0 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content {
                gap: 1.5rem;
            }

            .hero-text h1 {
                font-size: 2.8rem;
            }

            .hero-text p {
                font-size: 1.2rem;
                margin-bottom: 2rem;
            }

            .hero-actions {
                flex-direction: column;
                align-items: center;
                gap: 1rem;
            }

            .navbar-container {
                padding: 0 1rem;
            }

            .navbar-brand {
                font-size: 1.5rem;
            }

            .hero {
                padding: 8rem 1rem 4rem;
                max-width: 100%;
            }

            .features {
                margin: 0 1rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        .mobile-menu {
            display: none;
        }

        @media (max-width: 768px) {
            .navbar-nav {
                display: none;
            }

            .mobile-menu {
                display: block;
                background: none;
                border: none;
                font-size: 1.25rem;
                color: var(--text-primary);
                cursor: pointer;
            }
        }
    </style>
</head>
<body class="landing-page">
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="navbar-container">
            <a href="#" class="navbar-brand">
                <span class="brand-icon">
                    <i class="fas fa-book-open" style="font-size: 1.2rem;"></i>
                </span>
                <?php echo htmlspecialchars($libraryName); ?>
            </a>
            
            <div class="navbar-nav">
                <a href="#features" class="nav-link">Features</a>
                <a href="#about" class="nav-link">About</a>
                <a href="auth.php" class="btn-get-started">
                    <i class="fas fa-sign-in-alt"></i> Get Started
                </a>
            </div>

            <button class="mobile-menu" id="mobile-menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Transform Your Library Management</h1>
                <p>Experience the future of library management with our intuitive, secure, and efficient digital solution. Streamline operations, enhance user experience, and manage your collection with ease.</p>
                
                <div class="hero-actions">
                    <a href="auth.php" class="btn-primary">
                        <i class="fas fa-rocket" style="margin-right: 0.5rem;"></i> Start Your Journey
                    </a>
                    <a href="#features" class="btn-secondary">
                        <i class="fas fa-play-circle" style="margin-right: 0.5rem;"></i> Learn More
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="features-header">
            <h2>Why Choose Our System?</h2>
            <p>Discover the powerful features that make library management effortless and efficient</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h3>Smart Book Management</h3>
                <p>Effortlessly catalog, organize, and track your entire book collection with intelligent search and categorization features.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>User Management</h3>
                <p>Streamlined member registration, role-based access control, and comprehensive user activity tracking.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h3>Issue & Return System</h3>
                <p>Automated book lending with due date tracking, renewal options, and overdue fine calculations.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Analytics & Reports</h3>
                <p>Comprehensive reporting dashboard with insights on book popularity, user activity, and library performance.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Secure & Reliable</h3>
                <p>Advanced security measures with data encryption, regular backups, and user authentication protocols.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3>Mobile Responsive</h3>
                <p>Access your library system from any device with our fully responsive design and mobile-optimized interface.</p>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stats-grid">
                <div class="stat-item">
                    <h3>10,000+</h3>
                    <p>Books Managed</p>
                </div>
                <div class="stat-item">
                    <h3>500+</h3>
                    <p>Active Members</p>
                </div>
                <div class="stat-item">
                    <h3>99.9%</h3>
                    <p>Uptime Reliability</p>
                </div>
                <div class="stat-item">
                    <h3>24/7</h3>
                    <p>System Availability</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="about">
        <div class="footer-container">
            <div class="footer-brand">
                <span class="brand-icon">
                    <i class="fas fa-book-open"></i>
                </span>
                <?php echo htmlspecialchars($libraryName); ?>
            </div>
            <p><?php echo htmlspecialchars($libraryDescription); ?></p>
            <p>Empowering libraries with modern technology solutions</p>
            
            <div class="footer-divider"></div>
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($libraryName); ?>. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 100;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Animation on scroll (simple intersection observer)
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe feature cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });

        // Mobile menu toggle (for future enhancement)
        const mobileMenu = document.getElementById('mobile-menu');
        const navbarNav = document.querySelector('.navbar-nav');
        
        mobileMenu.addEventListener('click', function() {
            // This would toggle mobile menu visibility
            // For now, it will just redirect to auth page
            window.location.href = 'auth.php';
        });
    </script>
</body>
</html>
