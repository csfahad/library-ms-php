# Library Management System (LMS)

A comprehensive web-based Library Management System built with PHP, MySQL, HTML5, CSS3, and JavaScript. This system automates library operations including book management, member registration, book issue/return tracking, and report generation.

## Features

### Core Functionality

-   **User Management**: Registration, authentication, and role-based access control
-   **Book Management**: Add, update, delete, and search books with real-time availability
-   **Issue/Return System**: Automated book lending with due date tracking
-   **Fine Calculation**: Automatic fine calculation for overdue books
-   **Search & Catalog**: Advanced search functionality with multiple filters
-   **Reports**: Comprehensive reporting system for library analytics
-   **Dashboard**: Role-specific dashboards for admin and students

### Technical Features

-   **Responsive Design**: Mobile-friendly interface that works on all devices
-   **Security**: Password hashing, input sanitization, and SQL injection prevention
-   **Data Validation**: Client-side and server-side validation
-   **Real-time Updates**: Live availability status and notifications
-   **Database Optimization**: Indexed tables and optimized queries
-   **Error Handling**: Comprehensive error logging and user-friendly messages

## Requirements

### Software Requirements

-   **PHP**: Version 7.4 or higher with required extensions
-   **Database**: MySQL 5.7+/MariaDB 10.3+ (Local, Docker, or Cloud)
-   **Web Server**: Apache 2.4+, Nginx, or PHP built-in server
-   **Browser**: Modern browsers (Chrome, Firefox, Safari, Edge)

### Required PHP Extensions

The following PHP extensions are required:

-   `pdo` - Database abstraction layer
-   `pdo_mysql` - MySQL driver for PDO
-   `mbstring` - Multi-byte string handling
-   `openssl` - Encryption functions
-   `session` - Session management
-   `json` - JSON handling

### Hardware Requirements

-   **Development**: Minimum 2GB RAM, 100MB storage
-   **Production**: Minimum 4GB RAM, 500MB storage
-   **Client**: Any device with internet connection

## Installation

### Quick Setup (Automated)

**RECOMMENDED: Complete Docker Setup (Zero Local Installations)**

**For detailed Docker setup:** See [docker-setup-guide.md](docker-setup-guide.md)

```bash
# Everything in Docker - No PHP, MySQL, or Apache installation needed!

git clone https://github.com/csfahad/library-ms-php

cd library-ms-php/

./setup_complete_docker.sh

# Then open: http://localhost:8000
```

**Complete Docker Setup Features:**

-   ✅ **Zero local software required** - just Docker Desktop
-   ✅ PHP 8.1 + Apache web server in container
-   ✅ MySQL 8.0 database with persistent storage
-   ✅ phpMyAdmin web interface (localhost:8080)
-   ✅ Redis for session storage and caching
-   ✅ Professional container architecture
-   ✅ One-command setup and deployment
-   ✅ Automatic browser opening to your app

### Manual Setup (Step by Step)

If you prefer to set up manually or the automated script doesn't work:

### Option 1: Docker MySQL Database (Recommended for Development)

Docker provides an isolated, consistent MySQL environment that's easy to set up and tear down.

#### Prerequisites

```bash
# Install Docker Desktop

# macOS/Windows: Download from https://www.docker.com/products/docker-desktop/

# Ubuntu:
sudo apt update
sudo apt install docker.io docker-compose
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -aG docker $USER  # Logout and login again

# Verify Docker installation
docker --version
docker-compose --version
```

#### 1. Create Docker Compose Configuration

Create `docker-compose.yml` in your project root:

```yaml
version: "3.8"

services:
    mysql:
        image: mysql:8.0
        container_name: lms_mysql
        restart: unless-stopped
        environment:
            MYSQL_ROOT_PASSWORD: root_password_123
            MYSQL_DATABASE: library_management_system
            MYSQL_USER: lms_user
            MYSQL_PASSWORD: lms_password_123
        ports:
            - "3306:3306"
        volumes:
            - mysql_data:/var/lib/mysql
            - ./database/library_management_system.sql:/docker-entrypoint-initdb.d/init.sql
        command: --default-authentication-plugin=mysql_native_password

    phpmyadmin:
        image: phpmyadmin/phpmyadmin
        container_name: lms_phpmyadmin
        restart: unless-stopped
        ports:
            - "8080:80"
        environment:
            PMA_HOST: mysql
            PMA_PORT: 3306
            PMA_USER: lms_user
            PMA_PASSWORD: lms_password_123
        depends_on:
            - mysql

volumes:
    mysql_data:
```

#### 2. Start Docker Containers

```bash
# Start the containers (from project root directory)
docker-compose up -d

# Check if containers are running
docker-compose ps

# View logs if needed
docker-compose logs mysql
```

#### 3. Configure Database Connection

Update `config/database.php`:

```php
// Database Configuration for Docker
define('DB_HOST', 'localhost');  // or '127.0.0.1'
define('DB_NAME', 'library_management_system');
define('DB_USER', 'lms_user');
define('DB_PASS', 'lms_password_123');
define('DB_CHARSET', 'utf8mb4');
```

#### 4. Verify Database Setup

```bash
# Connect to MySQL container
docker exec -it lms_mysql mysql -u lms_user -p

# Or use phpMyAdmin at http://localhost:8080
# Username: lms_user
# Password: lms_password_123
```

#### 5. Docker Management Commands

```bash
# Stop containers
docker-compose down

# Stop and remove all data (BE CAREFUL!)
docker-compose down -v

# Restart containers
docker-compose restart

# View container logs
docker-compose logs -f mysql

# Access MySQL shell
docker exec -it lms_mysql mysql -u lms_user -p library_management_system
```

#### 6. Database Persistence & Management

**IMPORTANT: Database Schema Persistence**

The database schema and all changes are automatically persisted through:

-   **Main Schema**: `database/library_management_system.sql` (automatically loaded on container creation)
-   **Migrations**: `database/migrations/*.sql` (versioned schema changes)
-   **Docker Volume**: `mysql_data` volume ensures data persistence across container restarts

**Database Management Commands:**

```bash
# Manual migration runner
php database/migrate.php run
php database/migrate.php status
```

**What's automatically included:**

-   ✅ All required tables (users, books, admin, system_settings, etc.)
-   ✅ Default admin account: admin@library.com / password
-   ✅ Sample user account: john@example.com / password
-   ✅ 5 sample books with proper categories
-   ✅ System settings (library info, fine rates, etc.)
-   ✅ Database constraints and indexes

### Option 2: Local MySQL Database (Traditional Setup)

#### 1. Install MySQL Server

```bash
# macOS (using Homebrew)
brew install mysql
brew services start mysql

# Ubuntu/Debian
sudo apt update
sudo apt install mysql-server
sudo systemctl start mysql
sudo systemctl enable mysql

# Windows
# Download MySQL installer from https://dev.mysql.com/downloads/installer/
# Run the installer and follow the setup wizard
```

#### 2. Secure MySQL Installation

```bash
# Run MySQL secure installation (recommended)
sudo mysql_secure_installation

# Follow the prompts to:
# - Set root password
# - Remove anonymous users
# - Disable root login remotely
# - Remove test database
# - Reload privilege tables
```

#### 3. Create Database and User

```bash
# Login to MySQL as root
mysql -u root -p

# Create database
CREATE DATABASE library_management_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create a dedicated user (recommended for security)
CREATE USER 'lms_user'@'localhost' IDENTIFIED BY 'your_secure_password';

# Grant privileges
GRANT ALL PRIVILEGES ON library_management_system.* TO 'lms_user'@'localhost';

# Flush privileges and exit
FLUSH PRIVILEGES;
EXIT;
```

#### 4. Import Database Schema

```bash
# Navigate to your project directory
cd /path/to/lms

# Import the database schema
mysql -u lms_user -p library_management_system < database/library_management_system.sql

# Or if using root user:
mysql -u root -p library_management_system < database/library_management_system.sql
```

#### 5. Install PHP (Required)

**Note**: PHP installation is required regardless of database choice.

```bash
# Quick install commands:

# macOS
brew install php

# Ubuntu/Debian
sudo apt install php php-mysql php-pdo php-mbstring

# Windows
# Download from https://windows.php.net/download/ or use package managers
```

#### 6. Configure Database Connection

Edit `config/database.php` with your database credentials:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_management_system');
define('DB_USER', 'lms_user');        // Your MySQL username
define('DB_PASS', 'your_secure_password'); // Your MySQL password
define('DB_CHARSET', 'utf8mb4');
```

### Option 2: Using XAMPP/WAMP (Alternative)

#### 1. Environment Setup

```bash
# Install XAMPP (Windows/Mac/Linux) or WAMP (Windows)
# Start Apache and MySQL services from control panel
```

#### 2. Database Setup via phpMyAdmin

```
# Open http://localhost/phpmyadmin
# Create new database: library_management_system
# Import database/library_management_system.sql
```

#### 3. Configuration for XAMPP

```php
# Edit config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'library_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');  # Usually empty for XAMPP
```

### 7. Configure Web Server

#### For Apache:

```bash
# Create a virtual host (optional but recommended)
sudo nano /etc/apache2/sites-available/lms.conf

# Add the following configuration:
<VirtualHost *:80>
    ServerName lms.local
    DocumentRoot /path/to/lms
    <Directory /path/to/lms>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog ${APACHE_LOG_DIR}/lms_error.log
    CustomLog ${APACHE_LOG_DIR}/lms_access.log combined
</VirtualHost>

# Enable the site
sudo a2ensite lms.conf
sudo systemctl reload apache2

# Add to /etc/hosts (optional)
echo "127.0.0.1 lms.local" | sudo tee -a /etc/hosts
```

#### For PHP Built-in Server (Development only):

```bash
# Navigate to project directory
cd /path/to/lms

# Start PHP built-in server
php -S localhost:8000

# Access via http://localhost:8000
```

### 8. Set File Permissions

```bash
# Set appropriate permissions (Linux/Mac)
chmod -R 755 /path/to/lms
chmod -R 777 uploads/ # If file uploads are needed

# For web server user (usually www-data on Ubuntu)
sudo chown -R www-data:www-data /path/to/lms
sudo chmod -R 755 /path/to/lms
sudo chmod -R 775 uploads/ # If file uploads are needed
```

### 9. Test Database Connection

Create a test file `test_db.php` in your project root:

```php
<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if ($pdo) {
        echo "Database connection successful!<br>";
        echo "Server info: " . $pdo->getAttribute(PDO::ATTR_SERVER_INFO) . "<br>";

        // Test if tables exist
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Found " . count($tables) . " tables: " . implode(', ', $tables);
    }
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>
```

### 10. Access the System

```
# If using virtual host:
http://lms.local

# If using document root:
http://localhost/lms/

# If using PHP built-in server:
http://localhost:8000
```

## Default Login Credentials

### Admin Access

-   **Email**: admin@library.com
-   **Password**: password

### Student Access

-   **Email**: john@example.com
-   **Password**: password

## Project Structure

```
lms/
├── admin/                 # Admin panel files
│   ├── dashboard.php      # Admin dashboard
│   ├── books.php          # Book management
│   ├── users.php          # User management
│   ├── issue-book.php     # Issue books
│   ├── return-book.php    # Return books
│   ├── books-requests.php # View all books request
│   ├── reports.php        # Reports generation
│   └── settings.php       # System settings
├── student/               # Student panel files
│   ├── dashboard.php      # Student dashboard
│   ├── search-books.php   # Search books
│   ├── my-books.php       # Current issues
│   ├── history.php        # Borrowing history
│   ├── profile.php        # User profile
│   └── feedback.php       # Submit feedback
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   ├── js/
│   │   └── script.js      # Main JavaScript
│   └── images/            # Image assets
├── config/                # Configuration files
│   └── database.php       # Database config
├── includes/              # PHP includes
│   ├── auth.php           # Authentication functions
│   ├── functions.php      # Library functions
│   └── logout.php         # Logout handler
├── database/              # Database files
│   └── library_management_system.sql
├── index.php              # Login page
└── README.md              # Documentation
```

## Usage Guide

### For Administrators

1. **Login** with admin credentials
2. **Manage Books**: Add, edit, or delete books from the collection
3. **Manage Users**: Register new users and manage existing ones
4. **Issue Books**: Issue books to registered users
5. **Return Books**: Process book returns and calculate fines
6. **View Reports**: Generate various reports for analysis
7. **System Settings**: Configure library rules and settings

### For Students

1. **Register** for a new account or login with existing credentials
2. **Search Books**: Browse and search the library catalog
3. **View Availability**: Check real-time book availability
4. **Track Issues**: Monitor currently borrowed books and due dates
5. **View History**: Check complete borrowing history
6. **Update Profile**: Manage personal information
7. **Submit Feedback**: Provide feedback to library administration

## Database Schema

### Main Tables

-   **users**: User information and authentication
-   **admin**: Administrator accounts
-   **books**: Book catalog and inventory
-   **issued_books**: Book lending transactions
-   **categories**: Book categories
-   **feedback**: User feedback and suggestions
-   **system_settings**: Configurable system parameters

### Key Relationships

-   Users can have multiple book issues
-   Books can be issued to multiple users (over time)
-   Categories organize books by subject/genre
-   System settings control library rules

## Configuration Options

### Library Settings (configurable)

```php
MAX_BOOKS_PER_USER = 5;        # Maximum books per user
DEFAULT_ISSUE_DAYS = 14;       # Default lending period
FINE_PER_DAY = 2.00;           # Daily fine amount
SESSION_TIMEOUT = 3600;        # Session timeout (seconds)
```

### Security Settings

-   Password hashing using PHP's `password_hash()`
-   CSRF token protection
-   Input sanitization and validation
-   SQL injection prevention with prepared statements

## Advanced Features

### Automated Workflows

-   **Auto-Fine Calculation**: Automatically calculates fines for overdue books
-   **Availability Tracking**: Real-time inventory management
-   **Due Date Reminders**: System tracks and displays due dates
-   **Status Updates**: Automatic status updates for books and users

### Reporting System

-   **Book Statistics**: Most popular books, category distribution
-   **User Activity**: Borrowing patterns and user engagement
-   **Overdue Analysis**: Overdue books and fine collection
-   **System Health**: Database statistics and system status

### Search Capabilities

-   **Multi-field Search**: Search by title, author, ISBN, category
-   **Real-time Filtering**: Instant search results
-   **Availability Filter**: Show only available books
-   **Category Browsing**: Browse by book categories

## Mobile Responsiveness

The system is fully responsive and provides optimal experience across:

-   **Desktop**: Full-featured interface with all functionalities
-   **Tablet**: Touch-optimized interface with adapted layouts
-   **Mobile**: Streamlined interface optimized for small screens

## Security Features

### Authentication & Authorization

-   Secure password hashing (bcrypt)
-   Session management with timeout
-   Role-based access control (Admin, Librarian, Student)
-   CSRF protection for forms

### Data Protection

-   Input sanitization and validation
-   SQL injection prevention
-   XSS attack prevention
-   Error message sanitization

## Troubleshooting

### Database Setup Issues

1. **MySQL Connection Refused**

```bash
# Check if MySQL is running
sudo systemctl status mysql          # Linux
brew services list | grep mysql      # macOS
net start mysql                      # Windows

# Start MySQL if not running
sudo systemctl start mysql           # Linux
brew services start mysql            # macOS
net start mysql                      # Windows
```

2. **Access Denied for User**

```bash
# Reset MySQL root password if forgotten
sudo mysql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'new_password';
FLUSH PRIVILEGES;
EXIT;

# Or use MySQL safe mode
sudo systemctl stop mysql
sudo mysqld_safe --skip-grant-tables &
mysql -u root
```

3. **Database Import Errors**

```bash
# Check MySQL version compatibility
mysql --version

# Import with specific character set
mysql -u root -p --default-character-set=utf8mb4 library_management_system < database/library_management_system.sql

# Import with verbose output to see errors
mysql -u root -p -v library_management_system < database/library_management_system.sql
```

4. **PHP PDO MySQL Extension Missing**

```bash
# Ubuntu/Debian
sudo apt install php-mysql php-pdo

# macOS
brew install php
# PDO MySQL is usually included

# Windows
# Uncomment in php.ini:
# extension=pdo_mysql
```

5. **Connection Charset Issues**

```php
// In config/database.php, ensure UTF8MB4 is set:
define('DB_CHARSET', 'utf8mb4');

// And in database DSN:
$dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
```

### Common Issues

1. **Database Connection Error**

    - Check database credentials in `config/database.php`
    - Ensure MySQL service is running
    - Verify database exists and is accessible
    - Test connection with the test file provided above

2. **Login Issues**

    - Clear browser cache and cookies
    - Check username/password combination
    - Verify user account is active
    - Check if sample data was imported correctly

3. **File Permission Issues**

    - Ensure web server has read access to files
    - Set appropriate directory permissions
    - Check PHP error logs for detailed messages

4. **Port Conflicts**

```bash
# Check what's running on port 3306 (MySQL default)
sudo netstat -tlnp | grep :3306       # Linux
lsof -i :3306                         # macOS
netstat -an | findstr :3306           # Windows

# Check what's running on port 80/8000 (Web server)
sudo netstat -tlnp | grep :80         # Linux
lsof -i :80                           # macOS
```

### Debug Mode

Enable debug mode by adding to the top of `config/database.php`:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');
```

### Testing Your Setup

1. **Test MySQL Connection**

```bash
# Command line test
mysql -u lms_user -p -h localhost library_management_system

# Should connect without errors
```

2. **Test PHP MySQL Extension**
   Create `phpinfo.php` in your project root:

```php
<?php
phpinfo();
?>
```

Look for "PDO" and "mysql" sections.

3. **Test Application**
   Visit your application URL and check for:

-   Login page loads correctly
-   No PHP errors in browser console
-   Database connection test passes

## Backup & Maintenance

### Database Backup

```bash
# Create backup with your database user
mysqldump -u lms_user -p library_management_system > backup_$(date +%Y%m%d).sql

# Or with root user
mysqldump -u root -p library_management_system > backup_$(date +%Y%m%d).sql

# Create compressed backup
mysqldump -u lms_user -p library_management_system | gzip > backup_$(date +%Y%m%d).sql.gz

# Restore backup
mysql -u lms_user -p library_management_system < backup_file.sql

# Restore from compressed backup
gunzip < backup_file.sql.gz | mysql -u lms_user -p library_management_system
```

### Automated Backup Script

Create a backup script `backup_lms.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/path/to/backups"
DB_USER="lms_user"
DB_PASS="your_password"
DB_NAME="library_management_system"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory if it doesn't exist
mkdir -p $BACKUP_DIR

# Create backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/lms_backup_$DATE.sql.gz

# Remove backups older than 30 days
find $BACKUP_DIR -name "lms_backup_*.sql.gz" -mtime +30 -delete

echo "Backup completed: lms_backup_$DATE.sql.gz"
```

Make it executable and add to crontab:

```bash
chmod +x backup_lms.sh

# Add to crontab for daily backup at 2 AM
crontab -e
# Add: 0 2 * * * /path/to/backup_lms.sh
```

### Regular Maintenance

-   Monitor database size and optimize tables
-   Review error logs regularly
-   Update PHP and MySQL as needed
-   Backup database weekly
-   Monitor system performance

### Database Optimization

```sql
-- Check table sizes
SELECT
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'library_management_system'
ORDER BY (data_length + index_length) DESC;

-- Optimize all tables
OPTIMIZE TABLE books, users, issued_books, categories, admin, feedback, system_settings;

-- Check for unused indexes
SELECT * FROM sys.schema_unused_indexes WHERE object_schema = 'library_management_system';
```

-   Backup database weekly
-   Monitor system performance

## Future Enhancements

### Planned Features

-   **Email Notifications**: Automated email reminders for due dates
-   **Barcode Integration**: QR/Barcode scanning for books
-   **Mobile App**: Native mobile application
-   **Digital Library**: E-book integration
-   **API Development**: RESTful API for external integrations
-   **Multi-branch Support**: Support for multiple library locations
-   **Advanced Analytics**: Detailed reporting with charts
-   **Online Payments**: Integration with payment gateways

### Technical Improvements

-   **Caching System**: Redis/Memcached integration
-   **Load Balancing**: Support for multiple servers
-   **Cloud Integration**: AWS/Google Cloud deployment options

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines

-   Follow PSR-4 coding standards for PHP
-   Use meaningful variable and function names
-   Comment complex logic thoroughly
-   Test all functionality before submitting
-   Ensure responsive design compatibility

## Support

For support and questions:

-   **Email**: support@library-lms.com(dummy)
-   **Documentation**: Check this README and inline code comments
-   **Issues**: Report bugs via GitHub issues

## Acknowledgments

-   Built with modern web technologies
-   Icons provided by Font Awesome
-   UI components inspired by Bootstrap
-   Database design follows normalization principles
-   Security practices based on OWASP guidelines

---

**Version**: 1.0.0  
**Last Updated**: September 2025  
**Minimum PHP Version**: 7.4  
**Database**: MySQL 5.7+

For the latest updates and documentation, visit the project repository.
