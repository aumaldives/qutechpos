# IsleBooks Backup Scripts

This directory contains comprehensive backup and restore scripts for the IsleBooks Laravel application.

## Scripts Overview

### 1. `backup.sh` - Application Backup Script
Creates compressed backups of the entire application excluding database and temporary files.

**Usage:**
```bash
./scripts/backup.sh [description]
```

**Examples:**
```bash
./scripts/backup.sh                    # Creates backup with "automatic" description
./scripts/backup.sh "before_update"    # Creates backup with custom description
```

**What gets backed up:**
- All application code (PHP, JavaScript, CSS)
- Configuration files (.env, config/)
- Public assets (CSS, JS, images - NO uploads)
- Database migrations
- All custom modules
- Composer configuration (composer.json)

**What gets excluded (production-ready):**
- storage/app/public/uploads/ (development uploads)
- storage/app/documents/ (development documents)
- storage/app/invoices/ (development invoices)
- node_modules/ and vendor/ (will be reinstalled)
- storage/logs/ and all cache directories
- .git/ repository data
- All .sql backup files
- Debug and monitoring files
- Database (since this is development server with migrations)

### 2. `restore.sh` - Application Restore Script
Restores a backup to specified location with proper setup.

**Usage:**
```bash
./scripts/restore.sh <backup_file> [target_directory]
```

**Examples:**
```bash
./scripts/restore.sh /var/backups/islebooks/islebooks_backup_20250905_1430.tar.gz
./scripts/restore.sh /path/to/backup.tar.gz /var/www
```

**What it does:**
- Backs up current installation
- Extracts backup archive
- Sets proper file permissions
- Creates .env from .env.example if needed
- Provides post-restore instructions

### 3. `backup-manager.sh` - Interactive Backup Manager
Provides a user-friendly menu interface for backup operations.

**Usage:**
```bash
./scripts/backup-manager.sh
```

**Features:**
- Create new backups with custom descriptions
- List all available backups
- Restore from backup with selection menu
- Delete old backups
- View backup information
- Automatic cleanup of old backups

## Backup Storage

**Location:** `/var/backups/islebooks/`

**Files:**
- `islebooks_[description]_[timestamp].tar.gz` - Backup archives
- `LATEST_BACKUP.txt` - Information about the most recent backup
- `backup_[timestamp].log` - Detailed backup logs

## Backup Features

### Automatic Cleanup
- Keeps last 10 backups by default
- Removes old log files
- Configurable retention policies

### Compression
- Uses gzip compression for efficient storage
- Typical compression ratio: ~40-60%
- Large applications (5GB) compress to ~2-3GB

### Error Handling
- Comprehensive error checking
- Detailed logging
- Safe failure modes

### Metadata
- Backup information files
- File inventories
- Git commit information
- System information

## Quick Commands

### Create a backup before major changes:
```bash
cd /var/www/beta.islebooks.mv
./scripts/backup.sh "before_major_update"
```

### List available backups:
```bash
ls -la /var/backups/islebooks/islebooks_*.tar.gz
```

### View latest backup info:
```bash
cat /var/backups/islebooks/LATEST_BACKUP.txt
```

### Restore specific backup:
```bash
./scripts/restore.sh /var/backups/islebooks/islebooks_backup_20250905_1430.tar.gz
```

## Post-Restore Steps

After restoring a backup, you'll need to:

1. **Configure Environment:**
   ```bash
   cp .env.example .env
   # Edit .env with your database and other settings
   ```

2. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

4. **Run Migrations:**
   ```bash
   php artisan migrate
   ```

5. **Build Assets:**
   ```bash
   npm run dev    # For development
   npm run prod   # For production
   ```

6. **Set Permissions:**
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 755 storage bootstrap/cache
   ```

## Best Practices

### Regular Backups
- Create backups before major updates
- Create backups before installing new modules
- Create backups before database changes

### Naming Convention
- Use descriptive names: "before_update", "working_version", "pre_migration"
- Include version numbers: "v2.1_stable", "release_candidate"

### Storage Management
- Monitor backup directory size
- Remove old backups periodically
- Consider offsite backup storage for production

### Testing Restores
- Periodically test restore process
- Verify backup integrity
- Document recovery procedures

## Troubleshooting

### Common Issues

**Permission Errors:**
```bash
sudo chown -R www-data:www-data /var/www/beta.islebooks.mv
```

**Disk Space Issues:**
```bash
df -h /var/backups
./scripts/backup-manager.sh  # Use cleanup option
```

**Missing Dependencies:**
```bash
composer install
npm install
```

### Log Files
Check backup logs for detailed error information:
```bash
cat /var/backups/islebooks/backup_[timestamp].log
```

## Security Notes

- Backup files contain sensitive configuration data
- Secure backup storage location
- Consider encrypting backups for production use
- Regular access audits for backup directory

---

**Created:** 2025-09-05  
**Version:** 1.0  
**Compatibility:** Laravel 9.x, IsleBooks POS System