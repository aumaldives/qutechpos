#!/bin/bash

#############################################################################
# IsleBooks Application Restore Script
# 
# This script restores a backup of the Laravel application
#
# Usage: ./restore.sh <backup_file> [target_directory]
# Example: ./restore.sh /var/backups/islebooks/islebooks_backup_20250905_1430.tar.gz
#############################################################################

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
BACKUP_FILE="${1:-}"
TARGET_DIR="${2:-/var/www}"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Functions
log() {
    echo -e "$(date '+%Y-%m-%d %H:%M:%S') - $1"
}

show_usage() {
    echo "Usage: $0 <backup_file> [target_directory]"
    echo
    echo "Examples:"
    echo "  $0 /var/backups/islebooks/islebooks_backup_20250905_1430.tar.gz"
    echo "  $0 /var/backups/islebooks/islebooks_backup_20250905_1430.tar.gz /var/www"
    echo
    echo "Available backups:"
    if [ -d "/var/backups/islebooks" ]; then
        ls -la /var/backups/islebooks/islebooks_*.tar.gz 2>/dev/null || echo "  No backups found"
    else
        echo "  Backup directory not found"
    fi
}

# Check if backup file is provided
if [ -z "$BACKUP_FILE" ]; then
    log "${RED}ERROR: Backup file not specified${NC}"
    show_usage
    exit 1
fi

# Check if backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    log "${RED}ERROR: Backup file '$BACKUP_FILE' does not exist${NC}"
    show_usage
    exit 1
fi

# Check if target directory exists
if [ ! -d "$TARGET_DIR" ]; then
    log "${RED}ERROR: Target directory '$TARGET_DIR' does not exist${NC}"
    exit 1
fi

# Extract backup name from file
BACKUP_FILENAME=$(basename "$BACKUP_FILE" .tar.gz)

log "${BLUE}=== IsleBooks Application Restore Started ===${NC}"
log "${BLUE}Backup File: $BACKUP_FILE${NC}"
log "${BLUE}Target Directory: $TARGET_DIR${NC}"
log "${BLUE}Extracted Name: $BACKUP_FILENAME${NC}"

# Ask for confirmation
echo
echo -e "${YELLOW}WARNING: This will extract the backup to: ${TARGET_DIR}/${BACKUP_FILENAME}${NC}"
echo -e "${YELLOW}Make sure this is what you want to do.${NC}"
echo
read -p "Continue? (y/N): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    log "${YELLOW}Restore cancelled by user${NC}"
    exit 0
fi

# Create backup of current installation if it exists
CURRENT_APP="${TARGET_DIR}/pos.islebooks.mv"
if [ -d "$CURRENT_APP" ]; then
    log "${YELLOW}Backing up current installation...${NC}"
    if [ -w "$TARGET_DIR" ]; then
        mv "$CURRENT_APP" "${CURRENT_APP}.backup.${TIMESTAMP}"
        log "${GREEN}Current installation backed up to: ${CURRENT_APP}.backup.${TIMESTAMP}${NC}"
    else
        log "${RED}ERROR: No write permission to $TARGET_DIR${NC}"
        exit 1
    fi
fi

# Extract backup
log "${YELLOW}Extracting backup archive...${NC}"
cd "$TARGET_DIR"
tar -xzf "$BACKUP_FILE"

if [ ! -d "$BACKUP_FILENAME" ]; then
    log "${RED}ERROR: Extraction failed - directory $BACKUP_FILENAME not found${NC}"
    exit 1
fi

# Rename to standard application directory
if [ "$BACKUP_FILENAME" != "pos.islebooks.mv" ]; then
    log "${YELLOW}Renaming directory to pos.islebooks.mv...${NC}"
    mv "$BACKUP_FILENAME" "pos.islebooks.mv"
fi

cd "pos.islebooks.mv"

# Display backup info
if [ -f "BACKUP_INFO.txt" ]; then
    log "${BLUE}Backup Information:${NC}"
    cat "BACKUP_INFO.txt" | head -20
    echo
fi

# Post-restore setup
log "${YELLOW}Setting up restored application...${NC}"

# Create .env if it doesn't exist
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        log "${YELLOW}Creating .env from .env.example...${NC}"
        cp .env.example .env
        log "${YELLOW}Please configure .env file with your settings${NC}"
    else
        log "${YELLOW}No .env.example found, please create .env manually${NC}"
    fi
fi

# Set proper permissions
log "${YELLOW}Setting file permissions...${NC}"
if command -v www-data >/dev/null 2>&1; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || log "${YELLOW}Could not set www-data ownership (may need sudo)${NC}"
fi

chmod -R 755 storage bootstrap/cache 2>/dev/null || log "${YELLOW}Could not set permissions (may need sudo)${NC}"

# Create required directories
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

log "${GREEN}=== Restore Completed Successfully ===${NC}"
log "${GREEN}Application restored to: ${TARGET_DIR}/pos.islebooks.mv${NC}"

echo
echo -e "${BLUE}Next Steps:${NC}"
echo -e "${BLUE}===========${NC}"
echo "1. Configure .env file with your database and other settings"
echo "2. Install dependencies: composer install"
echo "3. Install Node modules: npm install"
echo "4. Generate app key: php artisan key:generate"
echo "5. Run migrations: php artisan migrate"
echo "6. Build assets: npm run dev (or npm run prod)"
echo "7. Set proper web server permissions if needed"
echo
echo "If something went wrong, your previous installation is at:"
echo "${CURRENT_APP}.backup.${TIMESTAMP}"