# Complete Docker Setup guide for Library Management System

## **No LOCAL Installation Required!**

**Everything runs in Docker containers:**

-   **PHP 8.1 + Apache** Web Server
-   **MySQL 8.0** Database
-   **phpMyAdmin** Database Management
-   **Redis** Session Storage & Caching

**You only need:** Docker Desktop (that's it!)

---

## **One-Command Setup:**

```bash
# Navigate to LMS directory
cd /path/to/lms

# Run the complete setup
./setup_complete_docker.sh
```

**This single script:**

-   ✅ Validates Docker installation
-   ✅ Builds custom PHP/Apache container
-   ✅ Starts MySQL with your data
-   ✅ Sets up phpMyAdmin web interface
-   ✅ Configures Redis for sessions
-   ✅ Tests everything automatically
-   ✅ Opens your browser to the app

---

## **Container Architecture:**

```
┌─────────────────────────────────────────────┐
│  Web Server (lms_web)                   │
│  • PHP 8.1 + Apache                        │
│  • Port: 8000 → http://localhost:8000      │
│  • All PHP extensions included             │
│  • Automatic SSL headers                   │
│  • Gzip compression enabled                │
└─────────────────┬───────────────────────────┘
                  │ Docker Network
┌─────────────────┴───────────────────────────┐
│  MySQL Database (lms_mysql)             │
│  • MySQL 8.0 with your LMS data           │
│  • Internal network communication          │
│  • Persistent data storage                 │
│  • Health checks enabled                   │
└─────────────────┬───────────────────────────┘
                  │ Docker Network
┌─────────────────┴───────────────────────────┐
│  phpMyAdmin (lms_phpmyadmin)            │
│  • Port: 8080 → http://localhost:8080     │
│  • Web-based database management           │
│  • Direct connection to MySQL              │
└─────────────────┬───────────────────────────┘
                  │ Docker Network
┌─────────────────┴───────────────────────────┐
│  Redis (lms_redis)                      │
│  • Session storage for better performance  │
│  • Caching layer for database queries      │
│  • Persistent data across restarts         │
└─────────────────────────────────────────────┘
```

---

## **Prerequisites:**

### **Only Docker Desktop Required:**

```bash
# macOS
Download from: https://www.docker.com/products/docker-desktop/

# Windows
Download from: https://www.docker.com/products/docker-desktop/

# Linux
sudo apt update
sudo apt install docker.io docker-compose
sudo systemctl start docker
sudo usermod -aG docker $USER  # Logout and login
```

### **Verify Installation:**

```bash
docker --version          # Should show Docker version
docker-compose --version  # Should show Compose version
docker info               # Should connect without errors
```

---

---

## **Access Points After Setup:**

### **Main Application:**

```
http://localhost:8000
```

-   Complete LMS with admin and student portals
-   Fully functional with all features
-   Responsive design for all devices

### **Database Management (phpMyAdmin):**

```
http://localhost:8080
```

-   Username: `lms_user`
-   Password: `lms_password_123`
-   Visual database management interface
-   Query execution and data export

### **Login Credentials:**

```
Admin Portal:
   Username: admin
   Password: password

Student Portal:
   Email: john@example.com
   Password: password
```

---

## **Container Management:**

### **Daily Operations:**

```bash
# Start containers (if stopped)
docker-compose up -d

# Stop containers (keeps data)
docker-compose down

# Restart all containers
docker-compose restart

# View real-time logs
docker-compose logs -f

# Check container status
docker-compose ps
```

### **Development Commands:**

```bash
# Access web container shell
docker exec -it lms_web bash

# Access MySQL shell
docker exec -it lms_mysql mysql -u lms_user -p

# View specific container logs
docker-compose logs lms_web
docker-compose logs lms_mysql

# Rebuild containers (after code changes)
docker-compose build --no-cache
docker-compose up -d
```

### **Data Management:**

```bash
# Create database backup
./backup_docker.sh

# View Docker volumes
docker volume ls

# Reset everything (DELETES ALL DATA!)
docker-compose down -v
docker-compose up -d
```

---

## **Customization:**

### **Environment Variables (.env):**

Create `.env` file from `.env.example`:

```bash
cp .env.example .env
```

Edit values:

```env
DB_HOST=mysql
DB_NAME=library_management_system
DB_USER=lms_user
DB_PASS=your_new_password
SITE_URL=http://localhost:8000
```

### **PHP Configuration:**

Edit `docker/php/php.ini`:

```ini
memory_limit = 512M
upload_max_filesize = 64M
max_execution_time = 600
```

### **Apache Configuration:**

Edit `docker/apache/vhost.conf` for:

-   SSL certificates
-   Custom domains
-   Security headers
-   URL rewrites

---

## **Production Deployment:**

### **Security Hardening:**

```yaml
# docker-compose.prod.yml
services:
    web:
        environment:
            - APP_ENV=production
            - APP_DEBUG=false
        # Remove port mapping for reverse proxy

    mysql:
        # Remove port mapping for security
        # ports: []
        environment:
            MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
```

### **SSL/HTTPS Setup:**

```yaml
# Add nginx reverse proxy
nginx:
    image: nginx:alpine
    ports:
        - "80:80"
        - "443:443"
    volumes:
        - ./nginx.conf:/etc/nginx/nginx.conf
        - ./ssl:/etc/ssl/certs
```

### **Backup Strategy:**

```bash
# Automated daily backups
echo "0 2 * * * /path/to/lms/backup_docker.sh" | crontab -

# Backup to cloud storage
./backup_docker.sh
aws s3 cp ./backups/ s3://your-bucket/lms-backups/ --recursive
```

---

## **Troubleshooting:**

### **Container Issues:**

```bash
# Container won't start
docker-compose logs [service_name]

# Port conflicts
# Change ports in docker-compose.yml:
ports:
  - "8001:80"  # Instead of 8000:80

# Permission issues
docker exec -it lms_web chown -R www-data:www-data /var/www/html
```

### **Database Connection:**

```bash
# Test MySQL connectivity
docker exec lms_mysql mysqladmin ping -h localhost

# Reset MySQL root password
docker exec -it lms_mysql mysql -u root -p
ALTER USER 'root'@'localhost' IDENTIFIED BY 'new_password';

# Import fresh database
docker exec -i lms_mysql mysql -u lms_user -p library_management_system < database/library_management_system.sql
```

### **Application Issues:**

```bash
# Clear application cache
docker exec lms_web rm -rf /tmp/*

# Check PHP errors
docker exec lms_web tail -f /var/log/apache2/php_error.log

# Restart web server
docker-compose restart web
```

---

## **Performance Optimization:**

### **Resource Limits:**

```yaml
# docker-compose.yml
services:
    web:
        deploy:
            resources:
                limits:
                    memory: 512M
                    cpus: "0.5"

    mysql:
        deploy:
            resources:
                limits:
                    memory: 1G
                    cpus: "1.0"
```

### **Database Optimization:**

```sql
-- Run in phpMyAdmin or MySQL shell
OPTIMIZE TABLE books, users, issued_books;
ANALYZE TABLE books, users, issued_books;
```

### **Redis Caching:**

The setup includes Redis for:

-   Session storage (faster than file sessions)
-   Database query caching
-   Application-level caching

---

## **Benefits of This Docker Setup:**

### **✅ Zero Local Dependencies:**

-   No PHP installation required
-   No MySQL server installation
-   No Apache/Nginx configuration
-   No version conflicts

### **✅ Development Advantages:**

-   Identical environment for all developers
-   Easy to reset/recreate
-   Isolated from host system
-   Version-controlled infrastructure

### **✅ Production Ready:**

-   Professional container architecture
-   Health checks included
-   Proper networking and security
-   Easy scaling and deployment

### **✅ Complete Tooling:**

-   Automated setup scripts
-   Backup and restore tools
-   Monitoring and logging
-   Performance optimization

---

## **Get Started Now:**

```bash
# 1. Clone or navigate to LMS directory
cd /path/to/lms

# 2. Run one command setup
./setup_complete_docker.sh

# 3. Open browser
open http://localhost:8000

# 4. Login and enjoy!
# Admin: admin@library.com / password
# Student: john@example.com / password
```

**That's it! The complete Library Managemet System is running with zero local installations required!**
