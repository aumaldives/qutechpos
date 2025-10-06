#!/bin/bash

#############################################################################
# IsleBooks Application Backup Script
# 
# This script creates a comprehensive backup of the Laravel application
# excluding database (since this is dev server with migrations)
#
# Usage: ./backup.sh [description]
# Example: ./backup.sh "before_major_update"
#############################################################################

# Set strict error handling
set -euo pipefail

# Configuration
APP_PATH="/var/www/pos.islebooks.mv"
BACKUP_DIR="/var/backups/islebooks"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DESCRIPTION="${1:-automatic}"
BACKUP_NAME="islebooks_${DESCRIPTION}_${TIMESTAMP}"
LOG_FILE="${BACKUP_DIR}/backup_${TIMESTAMP}.log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Start logging
log "${BLUE}=== IsleBooks Application Backup Started ===${NC}"
log "${BLUE}Backup Name: ${BACKUP_NAME}${NC}"
log "${BLUE}Description: ${DESCRIPTION}${NC}"

# Check if application directory exists
if [ ! -d "$APP_PATH" ]; then
    log "${RED}ERROR: Application directory $APP_PATH does not exist${NC}"
    exit 1
fi

# Change to application directory
cd "$APP_PATH"

# Create temporary directory for staging
TEMP_DIR="/tmp/${BACKUP_NAME}"
mkdir -p "$TEMP_DIR"

log "${YELLOW}Creating backup staging area...${NC}"

# Copy application files with exclusions (production-ready)
log "${YELLOW}Backing up application code (excluding development data and uploads)...${NC}"
rsync -av \
    --exclude='node_modules/' \
    --exclude='vendor/' \
    --exclude='storage/logs/' \
    --exclude='storage/framework/cache/' \
    --exclude='storage/framework/sessions/' \
    --exclude='storage/framework/testing/' \
    --exclude='storage/framework/views/' \
    --exclude='storage/app/public/temp/' \
    --exclude='storage/app/public/uploads/' \
    --exclude='storage/app/uploads/' \
    --exclude='storage/app/documents/' \
    --exclude='storage/app/invoices/' \
    --exclude='storage/app/expense/' \
    --exclude='storage/app/import_files/' \
    --exclude='storage/debugbar/' \
    --exclude='public/uploads/' \
    --exclude='public/temp/' \
    --exclude='public/documents/' \
    --exclude='public/invoices/' \
    --exclude='public/expense/' \
    --exclude='public/storage/' \
    --exclude='bootstrap/cache/' \
    --exclude='.git/' \
    --exclude='.gitignore' \
    --exclude='.env.backup*' \
    --exclude='.env.production*' \
    --exclude='.env.example.backup*' \
    --exclude='*.log' \
    --exclude='npm-debug.log*' \
    --exclude='yarn-debug.log*' \
    --exclude='yarn-error.log*' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='*.swp' \
    --exclude='*.tmp' \
    --exclude='*.temp' \
    --exclude='*.sql' \
    --exclude='*.backup' \
    --exclude='debug_*' \
    --exclude='test_*' \
    --exclude='monitor-db.php' \
    --exclude='crontab-monitoring.txt' \
    --exclude='DEVELOPMENT_STATUS.md' \
    --exclude='SMS_*.md' \
    --exclude='MSGOWL_*.md' \
    --exclude='create_application_backup.sh' \
    --exclude='islebookpos_live_backup.sql' \
    --exclude='SellPosController.php' \
    --exclude='deploy.sh' \
    --exclude='validate-env.sh' \
    "$APP_PATH/" "$TEMP_DIR/"

# Create backup info file
log "${YELLOW}Creating backup metadata...${NC}"
cat > "$TEMP_DIR/BACKUP_INFO.txt" << EOF
IsleBooks Application Backup
============================

Backup Date: $(date)
Backup Name: $BACKUP_NAME
Description: $DESCRIPTION
Application Path: $APP_PATH
PHP Version: $(php --version | head -1)
Laravel Version: $(cd "$APP_PATH" && php artisan --version 2>/dev/null || echo "Unknown")

Git Information:
$(cd "$APP_PATH" && git log -1 --oneline 2>/dev/null || echo "No git repository")

Backup Contents:
- Application code (PHP, JavaScript, CSS)
- Configuration files (.env included)
- Public assets (CSS, JS, images - NO uploads)
- Database migrations
- Module system
- Language files

Database: Not included (development server with migrations)

EXCLUDED (Development-only content):
- storage/app/public/uploads/ (development uploads)
- storage/app/documents/ (development documents) 
- storage/app/invoices/ (development invoices)
- storage/app/expense/ (development expense files)
- storage/app/import_files/ (development imports)
- node_modules/ and vendor/ (will be reinstalled)
- storage/logs/ and all cache directories
- .git/ repository data
- All .sql backup files
- Debug and monitoring files
- Temporary and log files

To restore:
1. Extract archive to web directory
2. Run: composer install
3. Run: npm install
4. Copy .env.example to .env and configure
5. Run: php artisan key:generate
6. Run: php artisan migrate
7. Set proper permissions: chown -R www-data:www-data storage bootstrap/cache

EOF

# Create file list
log "${YELLOW}Creating file inventory...${NC}"
find "$TEMP_DIR" -type f > "$TEMP_DIR/FILE_LIST.txt"
TOTAL_FILES=$(wc -l < "$TEMP_DIR/FILE_LIST.txt")
log "${GREEN}Total files to backup: $TOTAL_FILES${NC}"

# Calculate size
BACKUP_SIZE=$(du -sh "$TEMP_DIR" | cut -f1)
log "${GREEN}Backup size: $BACKUP_SIZE${NC}"

# Create compressed archive
log "${YELLOW}Creating compressed archive...${NC}"
cd "/tmp"
tar -czf "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" "$BACKUP_NAME"

# Verify archive
if [ -f "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" ]; then
    ARCHIVE_SIZE=$(du -sh "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" | cut -f1)
    log "${GREEN}Archive created successfully: ${BACKUP_NAME}.tar.gz ($ARCHIVE_SIZE)${NC}"
else
    log "${RED}ERROR: Failed to create archive${NC}"
    exit 1
fi

# Clean up temporary directory
rm -rf "$TEMP_DIR"

# Create backup summary
BACKUP_SUMMARY="${BACKUP_DIR}/LATEST_BACKUP.txt"
cat > "$BACKUP_SUMMARY" << EOF
Latest IsleBooks Backup
======================

Backup File: ${BACKUP_NAME}.tar.gz
Created: $(date)
Description: $DESCRIPTION
Original Size: $BACKUP_SIZE
Compressed Size: $ARCHIVE_SIZE
Total Files: $TOTAL_FILES
Location: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz

Restore Command:
cd /var/www && sudo tar -xzf ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz && sudo mv ${BACKUP_NAME} pos.islebooks.mv

EOF

# Display summary
log "${GREEN}=== Backup Completed Successfully ===${NC}"
log "${GREEN}Backup file: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz${NC}"
log "${GREEN}Archive size: $ARCHIVE_SIZE${NC}"
log "${GREEN}Original size: $BACKUP_SIZE${NC}"
log "${GREEN}Files backed up: $TOTAL_FILES${NC}"

# Cleanup old backups (keep last 10)
log "${YELLOW}Cleaning up old backups (keeping last 10)...${NC}"
cd "$BACKUP_DIR"
ls -t islebooks_*.tar.gz 2>/dev/null | tail -n +11 | xargs -r rm -f
OLD_LOGS=$(ls -t backup_*.log 2>/dev/null | tail -n +11)
if [ -n "$OLD_LOGS" ]; then
    echo "$OLD_LOGS" | xargs rm -f
fi

log "${GREEN}Backup process completed!${NC}"
echo
echo "Backup Summary:"
echo "==============="
cat "$BACKUP_SUMMARY"