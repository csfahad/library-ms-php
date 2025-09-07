#!/bin/bash

# LMS Docker Development Helper Script
# Quick commands for daily development tasks

echo "🔧 LMS Docker Development Helper"
echo "==============================="

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Check if containers exist
if ! docker-compose ps | grep -q lms_; then
    echo -e "${YELLOW}⚠️ Containers not found. Run ./setup_complete_docker.sh first${NC}"
    exit 1
fi

echo ""
echo "Select an action:"
echo ""
echo "1. 🚀 Start containers"
echo "2. 🛑 Stop containers"  
echo "3. 🔄 Restart containers"
echo "4. 📋 View logs"
echo "5. 📊 Container status"
echo "6. 🌐 Open application"
echo "7. 🔧 Open phpMyAdmin"
echo "8. 💾 Create backup"
echo "9. 🐚 Access web container shell"
echo "10. 🗄️ Access MySQL shell"
echo "11. 🧹 Clean up (stop and remove)"
echo "12. 🏗️ Rebuild containers"
echo ""

read -p "Enter your choice (1-12): " choice

case $choice in
    1)
        echo -e "${BLUE}🚀 Starting containers...${NC}"
        docker-compose up -d
        echo -e "${GREEN}✅ Containers started${NC}"
        echo "🌐 Access at: http://localhost:8000"
        ;;
    2)
        echo -e "${BLUE}🛑 Stopping containers...${NC}"
        docker-compose down
        echo -e "${GREEN}✅ Containers stopped${NC}"
        ;;
    3)
        echo -e "${BLUE}🔄 Restarting containers...${NC}"
        docker-compose restart
        echo -e "${GREEN}✅ Containers restarted${NC}"
        ;;
    4)
        echo -e "${BLUE}📋 Viewing logs (Ctrl+C to exit)...${NC}"
        docker-compose logs -f
        ;;
    5)
        echo -e "${BLUE}📊 Container status:${NC}"
        docker-compose ps
        ;;
    6)
        echo -e "${BLUE}🌐 Opening application...${NC}"
        if command -v open &> /dev/null; then
            open http://localhost:8000
        elif command -v xdg-open &> /dev/null; then
            xdg-open http://localhost:8000
        else
            echo "Open: http://localhost:8000"
        fi
        ;;
    7)
        echo -e "${BLUE}🔧 Opening phpMyAdmin...${NC}"
        if command -v open &> /dev/null; then
            open http://localhost:8080
        elif command -v xdg-open &> /dev/null; then
            xdg-open http://localhost:8080
        else
            echo "Open: http://localhost:8080"
        fi
        ;;
    8)
        echo -e "${BLUE}💾 Creating backup...${NC}"
        ./backup_docker.sh
        ;;
    9)
        echo -e "${BLUE}🐚 Accessing web container shell...${NC}"
        docker exec -it lms_web bash
        ;;
    10)
        echo -e "${BLUE}🗄️ Accessing MySQL shell...${NC}"
        echo "Database: library_management_system"
        echo "User: lms_user"
        echo "Password: lms_password_123"
        echo ""
        docker exec -it lms_mysql mysql -u lms_user -p library_management_system
        ;;
    11)
        echo -e "${YELLOW}⚠️ This will stop and remove all containers (data will be preserved)${NC}"
        read -p "Are you sure? [y/N]: " confirm
        if [[ $confirm =~ ^[Yy]$ ]]; then
            docker-compose down
            docker system prune -f
            echo -e "${GREEN}✅ Cleanup completed${NC}"
        fi
        ;;
    12)
        echo -e "${BLUE}🏗️ Rebuilding containers...${NC}"
        docker-compose down
        docker-compose build --no-cache
        docker-compose up -d
        echo -e "${GREEN}✅ Containers rebuilt${NC}"
        ;;
    *)
        echo "Invalid option. Please select 1-12."
        ;;
esac
