#!/bin/bash

# Library Management System - Docker Database Backup Script
# Creates backups of the MySQL database running in Docker

echo "💾 LMS Docker Database Backup"
echo "============================="

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
BACKUP_DIR="./backups"
DB_CONTAINER="lms_mysql"
DB_NAME="library_management_system"
DB_USER="lms_user"
DB_PASS="lms_password_123"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="lms_backup_${DATE}.sql"

# Create backup directory
mkdir -p $BACKUP_DIR

# Check if container is running
if ! docker ps | grep -q $DB_CONTAINER; then
    echo -e "${RED}❌ MySQL container '$DB_CONTAINER' is not running${NC}"
    echo "Start containers with: docker-compose up -d"
    exit 1
fi

echo -e "${BLUE}🗄️ Creating backup from Docker MySQL...${NC}"

# Create backup
docker exec $DB_CONTAINER mysqldump \
    -u $DB_USER \
    -p$DB_PASS \
    --single-transaction \
    --routines \
    --triggers \
    $DB_NAME > $BACKUP_DIR/$BACKUP_FILE

if [ $? -eq 0 ]; then
    # Compress backup
    gzip $BACKUP_DIR/$BACKUP_FILE
    COMPRESSED_FILE="$BACKUP_DIR/${BACKUP_FILE}.gz"
    
    echo -e "${GREEN}✅ Backup created successfully${NC}"
    echo "📁 Location: $COMPRESSED_FILE"
    echo "📊 Size: $(du -h $COMPRESSED_FILE | cut -f1)"
    
    # Show backup list
    echo ""
    echo -e "${BLUE}📋 Recent backups:${NC}"
    ls -lah $BACKUP_DIR/*.sql.gz 2>/dev/null | tail -5
    
    # Cleanup old backups (keep last 10)
    ls -t $BACKUP_DIR/lms_backup_*.sql.gz 2>/dev/null | tail -n +11 | xargs -r rm
    echo ""
    echo -e "${YELLOW}🗑️ Cleaned up old backups (keeping last 10)${NC}"
    
else
    echo -e "${RED}❌ Backup failed${NC}"
    rm -f $BACKUP_DIR/$BACKUP_FILE 2>/dev/null
    exit 1
fi

echo ""
echo -e "${BLUE}💡 To restore this backup:${NC}"
echo "   gunzip < $COMPRESSED_FILE | docker exec -i $DB_CONTAINER mysql -u $DB_USER -p$DB_PASS $DB_NAME"
