# Database Management Guide

## Overview

This guide explains how to properly manage the database for the Library Management System, ensuring all changes persist across container restarts and deployments.

## Database Structure

The LMS uses MySQL 8.0 with the following key tables:

-   `users` - System users (students, librarians)
-   `admin` - Administrator accounts
-   `books` - Book catalog
-   `categories` - Book categories
-   `issued_books` - Book loan tracking
-   `system_settings` - Application configuration
-   `feedback` - User feedback
-   `migrations` - Migration tracking

## Database Initialization

### Method 1: Using the Setup Script (Recommended)

```bash
# Initialize database
./setup-database.sh setup

# Reset database completely
./setup-database.sh reset

# Show database information
./setup-database.sh info

# Run migrations only
./setup-database.sh migrate
```

### Method 2: Using Docker Compose

When you run `docker-compose up -d`, the database is automatically initialized from `database/library_management_system.sql`.

### Method 3: Manual Setup

```bash
# Copy SQL file to container and execute
docker exec -i lms_mysql mysql -u root -proot_password_123 < database/library_management_system.sql

# Run migrations
php database/migrate.php run
```

## Migration System

### What are Migrations?

Migrations are version-controlled database changes that ensure all environments have the same database structure.

### Creating a New Migration

1. Create a new file in `database/migrations/` with format: `XXX_description.sql`
2. Write your SQL changes
3. Run migrations: `php database/migrate.php run`

### Example Migration

```sql
-- Migration: Add new column to users table
-- Date: 2025-09-06
-- Description: Add phone verification status

ALTER TABLE users ADD COLUMN phone_verified BOOLEAN DEFAULT FALSE;
UPDATE users SET phone_verified = TRUE WHERE phone IS NOT NULL;
```

### Migration Commands

```bash
# Run all pending migrations
php database/migrate.php run

# Show migration status
php database/migrate.php status
```

## Configuration Management

### System Settings

The `system_settings` table stores application configuration:

```sql
-- View current settings
SELECT * FROM system_settings;

-- Update a setting
UPDATE system_settings SET setting_value = '5' WHERE setting_key = 'max_books_per_user';

-- Add a new setting
INSERT INTO system_settings (setting_key, setting_value) VALUES ('new_setting', 'value');
```

### Available Settings

-   `library_name` - Library display name
-   `library_address` - Library physical address
-   `library_phone` - Contact phone number
-   `library_email` - Contact email
-   `max_books_per_user` - Maximum books per user
-   `issue_duration_days` - Default loan period
-   `fine_per_day` - Daily fine amount

## Database Backup & Restore

### Create Backup

```bash
# Backup entire database
docker exec lms_mysql mysqldump -u root -proot_password_123 library_management_system > backup.sql

# Backup with timestamp
docker exec lms_mysql mysqldump -u root -proot_password_123 library_management_system > "backup_$(date +%Y%m%d_%H%M%S).sql"
```

### Restore from Backup

```bash
# Restore database
docker exec -i lms_mysql mysql -u root -proot_password_123 library_management_system < backup.sql
```

## Troubleshooting

### Container Issues

```bash
# Check if container is running
docker ps | grep lms_mysql

# Start containers
docker-compose up -d

# View MySQL logs
docker logs lms_mysql

# Access MySQL shell
docker exec -it lms_mysql mysql -u root -proot_password_123
```

### Connection Issues

1. Verify container is running: `docker ps`
2. Check database credentials in `config/database.php`
3. Ensure database name matches: `library_management_system`
4. Wait for MySQL to be ready (can take 30-60 seconds on first start)

### Migration Issues

```bash
# Check migration status
php database/migrate.php status

# View migrations table
docker exec lms_mysql mysql -u root -proot_password_123 library_management_system -e "SELECT * FROM migrations ORDER BY id;"

# Reset migrations (careful!)
docker exec lms_mysql mysql -u root -proot_password_123 library_management_system -e "DROP TABLE migrations;"
```

### Data Issues

```bash
# Check table structure
docker exec lms_mysql mysql -u root -proot_password_123 library_management_system -e "DESCRIBE table_name;"

# View table contents
docker exec lms_mysql mysql -u root -proot_password_123 library_management_system -e "SELECT * FROM table_name LIMIT 10;"

# Check constraints
docker exec lms_mysql mysql -u root -proot_password_123 library_management_system -e "SHOW CREATE TABLE table_name;"
```

## Best Practices

### Development

1. Always use migrations for schema changes
2. Test migrations on a copy of production data
3. Never edit the main SQL file directly for changes
4. Use the setup script for clean database resets

### Production

1. Always backup before migrations
2. Test migrations in staging environment first
3. Document all database changes
4. Monitor migration execution time for large datasets

### Schema Changes

1. Create migrations for all changes
2. Use `IF NOT EXISTS` and `IF EXISTS` where appropriate
3. Make migrations reversible when possible
4. Include proper error handling

## Environment-Specific Notes

### Development

-   Database resets are safe and encouraged
-   Use `./setup-database.sh reset` to start fresh
-   All test data is recreated automatically

### Staging/Production

-   Never use reset commands
-   Always backup before changes
-   Use migrations for all schema updates
-   Monitor database performance after changes

## Files and Locations

-   `database/library_management_system.sql` - Main schema file
-   `database/migrations/` - Migration files
-   `database/migrate.php` - Migration runner
-   `setup-database.sh` - Database setup script
-   `config/database.php` - Database configuration
-   `docker-compose.yml` - Container configuration

## Support

If you encounter database issues:

1. Check this documentation first
2. Review container logs: `docker logs lms_mysql`
3. Verify file permissions on SQL files
4. Ensure Docker has sufficient resources
5. Check network connectivity between containers
