#!/bin/bash

# Library Management System - Complete Docker Setup
# No local installations required - Everything runs in Docker!

echo "Library Management System - Complete Docker Setup"
echo "====================================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

echo -e "${BLUE} This setup requires ONLY Docker - no local PHP, MySQL, or Apache needed!${NC}"
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker is not installed${NC}"
    echo ""
    echo "Please install Docker Desktop first:"
    echo "  macOS: https://www.docker.com/products/docker-desktop/"
    echo "  Windows: https://www.docker.com/products/docker-desktop/"
    echo "  Linux: sudo apt install docker.io docker-compose"
    echo ""
    exit 1
fi

echo -e "${GREEN}Docker found: $(docker --version | cut -d',' -f1)${NC}"

# Check if Docker is running
if ! docker info &> /dev/null; then
    echo -e "${RED}Docker is not running${NC}"
    echo "Please start Docker Desktop or Docker service"
    exit 1
fi

echo -e "${GREEN}Docker daemon is running${NC}"

# Check Docker Compose
if command -v docker-compose &> /dev/null; then
    COMPOSE_CMD="docker-compose"
    echo -e "${GREEN}Docker Compose found: $(docker-compose --version | cut -d',' -f1)${NC}"
elif docker compose version &> /dev/null; then
    COMPOSE_CMD="docker compose"
    echo -e "${GREEN}Docker Compose found: $(docker compose version | cut -d',' -f1)${NC}"
else
    echo -e "${RED}Docker Compose not found${NC}"
    echo "Please install Docker Compose or update Docker Desktop"
    exit 1
fi

# Check if database schema exists
if [ ! -f "database/library_management_system.sql" ]; then
    echo -e "${RED}Database schema file not found: database/library_management_system.sql${NC}"
    echo "Please make sure you're running this script from the project root directory."
    exit 1
fi

echo -e "${GREEN}Database schema file found${NC}"

echo ""
echo -e "${PURPLE}Docker Architecture:${NC}"
echo "┌─────────────────────────────────────────────┐"
echo "│  Web Server (PHP 8.1 + Apache)              │"
echo "│  Port: 8000 → http://localhost:8000         │"
echo "└─────────────────┬───────────────────────────┘"
echo "                  │"
echo "┌─────────────────┴───────────────────────────┐"
echo "│  MySQL Database Server                      │"
echo "│  Port: 3306 (internal container network)    │"
echo "└─────────────────┬───────────────────────────┘"
echo "                  │"
echo "┌─────────────────┴───────────────────────────┐"
echo "│  phpMyAdmin (Database Management)           │"
echo "│  Port: 8080 → http://localhost:8080         │"
echo "└─────────────────┬───────────────────────────┘"
echo "                  │"
echo "┌─────────────────┴───────────────────────────┐"
echo "│  Redis (Session Storage & Caching)          │"
echo "│  Port: 6379 (internal container network)    │"
echo "└─────────────────────────────────────────────┘"

echo ""
read -p "Ready to build and start the containers? [Y/n]: " confirm
if [[ $confirm =~ ^[Nn]$ ]]; then
    echo "Setup cancelled."
    exit 0
fi

# Stop existing containers if running
echo ""
echo -e "${BLUE}Stopping any existing containers...${NC}"
$COMPOSE_CMD down 2>/dev/null || true

# Build and start containers
echo ""
echo -e "${BLUE}Building Docker images (this may take a few minutes)...${NC}"
$COMPOSE_CMD build --no-cache

if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to build Docker images${NC}"
    exit 1
fi

echo -e "${GREEN}Docker images built successfully${NC}"

echo ""
echo -e "${BLUE}Starting containers...${NC}"
$COMPOSE_CMD up -d

if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to start containers${NC}"
    exit 1
fi

echo -e "${GREEN}Containers started successfully${NC}"

# Wait for services to be ready
echo ""
echo -e "${BLUE}Waiting for services to initialize...${NC}"

# Wait for MySQL
echo "Waiting for MySQL..."
for i in {1..60}; do
    if docker exec lms_mysql mysqladmin ping -h"localhost" --silent 2>/dev/null; then
        echo -e "  ${GREEN}MySQL is ready${NC}"
        break
    fi
    echo -n "."
    sleep 2
    if [ $i -eq 60 ]; then
        echo -e "\n  ${RED}MySQL failed to start${NC}"
        echo "  Check logs: $COMPOSE_CMD logs mysql"
        exit 1
    fi
done

# Wait for web server
echo "  Waiting for web server..."
for i in {1..30}; do
    if curl -s http://localhost:8000 > /dev/null 2>&1; then
        echo -e "  ${GREEN}Web server is ready${NC}"
        break
    fi
    echo -n "."
    sleep 2
    if [ $i -eq 30 ]; then
        echo -e "\n  ${YELLOW}Web server may still be starting${NC}"
        break
    fi
done

# Test database connection
# Run PHP migration runner to apply all migrations
echo ""
echo -e "${BLUE}Running database migrations...${NC}"


# Wait for MySQL root access to be ready
echo -e "${BLUE}Waiting for MySQL root access...${NC}"
for i in {1..30}; do
    docker exec lms_mysql mysqladmin ping -u root -proot_password_123 > /dev/null 2>&1 && \
    docker exec lms_mysql mysql -u root -proot_password_123 -e "SELECT 1;" > /dev/null 2>&1 && break
    echo -n "."
    sleep 2
    if [ $i -eq 30 ]; then
        echo -e "\n${RED}MySQL root access not available after waiting. Check container logs.${NC}"
        exit 1
    fi
done
echo -e "\n${GREEN}MySQL root access is ready${NC}"

# Drop and recreate the database for a clean setup
echo -e "${BLUE}Dropping and recreating database...${NC}"
docker exec lms_mysql mysql -u root -proot_password_123 -e "DROP DATABASE IF EXISTS library_management_system; CREATE DATABASE library_management_system;"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}Database dropped and recreated${NC}"
else
    echo -e "${RED}Failed to drop/recreate database. Check logs above.${NC}"
    exit 1
fi

# Set log_bin_trust_function_creators=1 to allow triggers without SUPER privilege
echo -e "${BLUE}Setting log_bin_trust_function_creators=1...${NC}"
docker exec lms_mysql mysql -u root -proot_password_123 -e "SET GLOBAL log_bin_trust_function_creators = 1;"
if [ $? -eq 0 ]; then
    echo -e "${GREEN}log_bin_trust_function_creators set${NC}"
else
    echo -e "${RED}Failed to set log_bin_trust_function_creators. Check logs above.${NC}"
    exit 1
fi

echo -e "${BLUE}Importing base schema...${NC}"
docker exec -i lms_mysql mysql -u lms_user -plms_password_123 library_management_system < database/library_management_system.sql
if [ $? -eq 0 ]; then
    echo -e "${GREEN}Base schema imported successfully${NC}"
else
    echo -e "${RED}Failed to import base schema. Check logs above.${NC}"
    exit 1
fi

docker exec lms_web php /var/www/html/database/migrate.php run
if [ $? -eq 0 ]; then
    echo -e "${GREEN}All migrations applied successfully${NC}"
else
    echo -e "${RED}Migration runner failed. Check logs above.${NC}"
    exit 1
fi
echo ""
echo -e "${BLUE}Testing application...${NC}"

# Test if we can reach the application
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000 | grep -q "200\|302"; then
    echo -e "${GREEN}Application is responding${NC}"
else
    echo -e "${YELLOW}Application may still be loading${NC}"
fi

# Check container status
echo ""
echo -e "${BLUE}Container Status:${NC}"
$COMPOSE_CMD ps

echo ""
echo -e "${GREEN}Setup completed successfully!${NC}"
echo ""
echo -e "${PURPLE}Access Points:${NC}"
echo "┌────────────────────────────────────────────────────────┐"
echo "│  LMS Application:  http://localhost:8000               │"
echo "│  phpMyAdmin:       http://localhost:8080               │"
echo "│  Container Logs:   $COMPOSE_CMD logs -f                │"
echo "└────────────────────────────────────────────────────────┘"

echo ""
echo -e "${BLUE}Default Login Credentials:${NC}"
echo "┌────────────────────────────────────────────────────────┐"
echo "│  Admin Portal:                                         │"
echo "│     Username: admin@library.com                        │"
echo "│     Password: password                                 │"
echo "│                                                        │"
echo "│  Student Portal:                                       │"
echo "│     Email: john@example.com                            │"
echo "│     Password: password                                 │"
echo "│                                                        │"
echo "│  Database (phpMyAdmin):                                │"
echo "│     Username: lms_user                                 │"
echo "│     Password: lms_password_123                         │"
echo "└────────────────────────────────────────────────────────┘"

echo ""
echo -e "${BLUE}Management Commands:${NC}"
echo "┌────────────────────────────────────────────────────────┐"
echo "│  Stop containers:     $COMPOSE_CMD down                │"
echo "│  Restart containers:  $COMPOSE_CMD restart             │"
echo "│  View logs:           $COMPOSE_CMD logs -f             │"
echo "│  Container shell:     docker exec -it lms_web bash     │"
echo "│  MySQL shell:         docker exec -it lms_mysql \\     │"
echo "│                          mysql -u lms_user -p          │"
echo "│  Backup database:     ./backup_docker.sh               │"
echo "└────────────────────────────────────────────────────────┘"

echo ""
echo -e "${YELLOW}Security Reminders:${NC}"
echo "• Change default passwords before production use"
echo "• Review docker-compose.yml for production deployment"
echo "• Enable HTTPS for production environments"
echo "• Regular database backups are recommended"

echo ""
echo -e "${GREEN}Everything is containerized - no local software needed!${NC}"
echo -e "${BLUE}Open http://localhost:8000 to start using your LMS${NC}"

# Try to open browser automatically
if command -v open &> /dev/null; then
    echo ""
    echo -e "${BLUE}Opening browser...${NC}"
    open http://localhost:8000
elif command -v xdg-open &> /dev/null; then
    echo ""
    echo -e "${BLUE}Opening browser...${NC}"
    xdg-open http://localhost:8000
fi
