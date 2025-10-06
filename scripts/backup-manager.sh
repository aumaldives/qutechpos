#!/bin/bash

#############################################################################
# IsleBooks Backup Manager
# 
# This script provides a simple interface for managing application backups
#############################################################################

set -euo pipefail

BACKUP_DIR="/var/backups/islebooks"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

show_menu() {
    echo -e "${BLUE}╔══════════════════════════════════╗${NC}"
    echo -e "${BLUE}║     IsleBooks Backup Manager     ║${NC}"
    echo -e "${BLUE}╚══════════════════════════════════╝${NC}"
    echo
    echo "1. Create new backup"
    echo "2. List available backups"
    echo "3. Restore from backup"
    echo "4. Delete old backups"
    echo "5. View backup info"
    echo "6. Cleanup (remove backups older than 30 days)"
    echo "7. Exit"
    echo
}

list_backups() {
    echo -e "${BLUE}Available Backups:${NC}"
    echo "=================="
    
    if [ ! -d "$BACKUP_DIR" ] || [ -z "$(ls -A $BACKUP_DIR/islebooks_*.tar.gz 2>/dev/null)" ]; then
        echo -e "${YELLOW}No backups found${NC}"
        return
    fi
    
    echo "$(printf "%-5s %-30s %-15s %-20s %s" "No." "Filename" "Size" "Date" "Description")"
    echo "$(printf "%s" "$(printf '=%.0s' {1..80})")"
    
    local i=1
    for backup in $(ls -t $BACKUP_DIR/islebooks_*.tar.gz 2>/dev/null); do
        local filename=$(basename "$backup")
        local size=$(du -sh "$backup" | cut -f1)
        local date=$(stat -c %y "$backup" | cut -d' ' -f1,2 | cut -d'.' -f1)
        local desc=$(echo "$filename" | sed 's/islebooks_\(.*\)_[0-9]*_[0-9]*.tar.gz/\1/')
        
        printf "%-5s %-30s %-15s %-20s %s\n" "$i" "$filename" "$size" "$date" "$desc"
        i=$((i+1))
    done
}

create_backup() {
    echo -e "${BLUE}Creating New Backup${NC}"
    echo "==================="
    
    read -p "Enter backup description (or press Enter for 'manual'): " description
    description=${description:-"manual"}
    
    if [ -f "$SCRIPT_DIR/backup.sh" ]; then
        "$SCRIPT_DIR/backup.sh" "$description"
    else
        echo -e "${RED}Error: backup.sh script not found in $SCRIPT_DIR${NC}"
    fi
}

restore_backup() {
    echo -e "${BLUE}Restore from Backup${NC}"
    echo "==================="
    
    list_backups
    echo
    
    if [ ! -d "$BACKUP_DIR" ] || [ -z "$(ls -A $BACKUP_DIR/islebooks_*.tar.gz 2>/dev/null)" ]; then
        return
    fi
    
    read -p "Enter backup number to restore (or 'c' to cancel): " choice
    
    if [ "$choice" = "c" ] || [ "$choice" = "C" ]; then
        return
    fi
    
    if ! [[ "$choice" =~ ^[0-9]+$ ]]; then
        echo -e "${RED}Invalid selection${NC}"
        return
    fi
    
    local backup_files=($(ls -t $BACKUP_DIR/islebooks_*.tar.gz 2>/dev/null))
    local selected_backup="${backup_files[$((choice-1))]}"
    
    if [ -z "$selected_backup" ] || [ ! -f "$selected_backup" ]; then
        echo -e "${RED}Invalid backup selection${NC}"
        return
    fi
    
    echo -e "${YELLOW}Selected backup: $(basename "$selected_backup")${NC}"
    
    if [ -f "$SCRIPT_DIR/restore.sh" ]; then
        "$SCRIPT_DIR/restore.sh" "$selected_backup"
    else
        echo -e "${RED}Error: restore.sh script not found in $SCRIPT_DIR${NC}"
    fi
}

delete_backups() {
    echo -e "${BLUE}Delete Old Backups${NC}"
    echo "=================="
    
    list_backups
    echo
    
    if [ ! -d "$BACKUP_DIR" ] || [ -z "$(ls -A $BACKUP_DIR/islebooks_*.tar.gz 2>/dev/null)" ]; then
        return
    fi
    
    read -p "Enter backup number to delete (or 'c' to cancel): " choice
    
    if [ "$choice" = "c" ] || [ "$choice" = "C" ]; then
        return
    fi
    
    if ! [[ "$choice" =~ ^[0-9]+$ ]]; then
        echo -e "${RED}Invalid selection${NC}"
        return
    fi
    
    local backup_files=($(ls -t $BACKUP_DIR/islebooks_*.tar.gz 2>/dev/null))
    local selected_backup="${backup_files[$((choice-1))]}"
    
    if [ -z "$selected_backup" ] || [ ! -f "$selected_backup" ]; then
        echo -e "${RED}Invalid backup selection${NC}"
        return
    fi
    
    echo -e "${YELLOW}Selected backup: $(basename "$selected_backup")${NC}"
    read -p "Are you sure you want to delete this backup? (y/N): " confirm
    
    if [[ $confirm =~ ^[Yy]$ ]]; then
        rm -f "$selected_backup"
        echo -e "${GREEN}Backup deleted successfully${NC}"
    else
        echo -e "${YELLOW}Deletion cancelled${NC}"
    fi
}

view_backup_info() {
    echo -e "${BLUE}View Backup Information${NC}"
    echo "======================="
    
    if [ -f "$BACKUP_DIR/LATEST_BACKUP.txt" ]; then
        cat "$BACKUP_DIR/LATEST_BACKUP.txt"
    else
        echo -e "${YELLOW}No backup information available${NC}"
    fi
}

cleanup_old_backups() {
    echo -e "${BLUE}Cleanup Old Backups${NC}"
    echo "==================="
    
    echo "This will remove backups older than 30 days..."
    read -p "Continue? (y/N): " confirm
    
    if [[ $confirm =~ ^[Yy]$ ]]; then
        find "$BACKUP_DIR" -name "islebooks_*.tar.gz" -mtime +30 -delete 2>/dev/null || true
        find "$BACKUP_DIR" -name "backup_*.log" -mtime +30 -delete 2>/dev/null || true
        echo -e "${GREEN}Old backups cleaned up${NC}"
    else
        echo -e "${YELLOW}Cleanup cancelled${NC}"
    fi
}

# Main menu loop
while true; do
    clear
    show_menu
    read -p "Select an option (1-7): " choice
    
    case $choice in
        1)
            create_backup
            read -p "Press Enter to continue..."
            ;;
        2)
            list_backups
            read -p "Press Enter to continue..."
            ;;
        3)
            restore_backup
            read -p "Press Enter to continue..."
            ;;
        4)
            delete_backups
            read -p "Press Enter to continue..."
            ;;
        5)
            view_backup_info
            read -p "Press Enter to continue..."
            ;;
        6)
            cleanup_old_backups
            read -p "Press Enter to continue..."
            ;;
        7)
            echo -e "${GREEN}Goodbye!${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}Invalid option. Please try again.${NC}"
            sleep 2
            ;;
    esac
done