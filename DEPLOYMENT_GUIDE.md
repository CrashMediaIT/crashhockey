# NGINX & PHP Deployment Guide for Crash Hockey

## Directory Structure

**IMPORTANT:** This guide uses a custom `/config` directory structure:
- Web root: `/config/www/crashhockey`
- NGINX configuration: `/config/nginx/`
- PHP socket: `/config/php/php8.1-fpm.sock`
- Logs: `/config/nginx/log/`
- SSL certificates: `/config/nginx/ssl/`
- Backups: `/config/backups/crashhockey`

If your system uses standard paths (e.g., `/var/www`, `/etc/nginx`), adjust the paths accordingly throughout this guide.

---

## Prerequisites

- Ubuntu 20.04/22.04 or similar Linux distribution
- Root or sudo access
- Domain name pointed to your server

## 1. Install Required Software

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install NGINX
sudo apt install nginx -y

# Install PHP 8.1 and extensions
sudo apt install php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-intl php8.1-bcmath -y

# Install MySQL/MariaDB
sudo apt install mysql-server -y

# Secure MySQL installation
sudo mysql_secure_installation
```

## 2. Configure PHP for Large File Uploads

### Option A: Modify php.ini directly

```bash
# Edit PHP-FPM configuration
sudo nano /etc/php/8.1/fpm/php.ini
```

Add or modify these lines:
```ini
upload_max_filesize = 2048M
post_max_size = 2048M
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
```

### Option B: Use the provided php-config.ini file

```bash
# Copy the custom PHP configuration
sudo cp php-config.ini /etc/php/8.1/fpm/conf.d/99-crashhockey.ini

# For HTTP only (development), change this line in the config:
# session.cookie_secure = 0
```

## 3. Configure PHP-FPM

Edit PHP-FPM pool configuration:

```bash
sudo nano /etc/php/8.1/fpm/pool.d/www.conf
```

Ensure these settings are present:

```ini
; Request timeout for large uploads
request_terminate_timeout = 300

; Maximum number of child processes
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.1-fpm
```

## 4. Deploy Application Files

```bash
# Create web root directory
sudo mkdir -p /config/www/crashhockey

# Copy application files
sudo cp -r * /config/www/crashhockey/

# Set ownership (adjust user if different from www-data)
sudo chown -R www-data:www-data /config/www/crashhockey

# Set permissions
sudo find /config/www/crashhockey -type f -exec chmod 644 {} \;
sudo find /config/www/crashhockey -type d -exec chmod 755 {} \;

# Create upload directory for videos
sudo mkdir -p /config/www/crashhockey/uploads/videos
sudo chown -R www-data:www-data /config/www/crashhockey/uploads
sudo chmod -R 775 /config/www/crashhockey/uploads
```

## 5. Configure NGINX

### Copy the configuration file

```bash
# Copy Crash Hockey NGINX configuration
sudo cp nginx.conf /config/nginx/crashhockey.conf

# Edit the configuration to set your domain name
sudo nano /config/nginx/crashhockey.conf
```

Update these lines:
```nginx
server_name your-domain.com www.your-domain.com;
root /config/www/crashhockey;
```

### Create NGINX directories for large uploads and logs

```bash
# Create temporary upload directory
sudo mkdir -p /config/nginx/client_body_temp
sudo mkdir -p /config/nginx/log

# Set ownership (adjust user if different from www-data)
sudo chown -R www-data:www-data /config/nginx
sudo chmod -R 755 /config/nginx
```

### Enable the site

```bash
# Test NGINX configuration
sudo nginx -t

# If test passes, reload NGINX
sudo systemctl reload nginx
```

## 6. Configure Firewall

```bash
# Allow HTTP and HTTPS
sudo ufw allow 'Nginx Full'

# Or allow specific ports
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable firewall if not already enabled
sudo ufw enable
```

## 7. Run Application Setup

Navigate to your domain in a web browser:

```
http://your-domain.com/setup.php
```

Follow the 4-step setup process:
1. Database Configuration
2. Initialize Database Tables
3. SMTP Configuration (test email will be sent)
4. Create Admin Account

## 8. SSL/HTTPS Setup (Recommended for Production)

### Install Certbot for Let's Encrypt

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtain SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Follow the prompts to configure automatic HTTPS redirect
```

### Manual SSL Configuration

If using a custom certificate:

1. Create SSL directory and place your certificates:
   ```bash
   sudo mkdir -p /config/nginx/ssl/your-domain.com
   # Copy your SSL certificate files to /config/nginx/ssl/your-domain.com/
   ```

2. Uncomment the SSL server block in `/config/nginx/crashhockey.conf`
3. Update certificate paths:
   ```nginx
   ssl_certificate /config/nginx/ssl/your-domain.com/fullchain.pem;
   ssl_certificate_key /config/nginx/ssl/your-domain.com/privkey.pem;
   ```
4. Test and reload NGINX:
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   ```

### Update PHP session settings for HTTPS

After enabling SSL, update PHP configuration:

```bash
sudo nano /etc/php/8.1/fpm/conf.d/99-crashhockey.ini
```

Change:
```ini
session.cookie_secure = 1
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
```

## 9. Security Hardening (After Setup)

### Restrict setup.php access

After completing setup, uncomment these lines in nginx.conf:

```nginx
location = /setup.php {
    deny all;
    return 404;
}
```

Reload NGINX:
```bash
sudo systemctl reload nginx
```

### Set proper file permissions

```bash
# Restrict access to sensitive files
sudo chmod 600 /config/www/crashhockey/crashhockey.env
sudo chmod 600 /config/www/crashhockey/.setup_complete

# Ensure web server can't write to most files
sudo find /config/www/crashhockey -type f -exec chmod 644 {} \;
sudo find /config/www/crashhockey -type d -exec chmod 755 {} \;

# Exception: uploads directory needs write access
sudo chmod -R 775 /config/www/crashhockey/uploads
```

## 10. Configure Automatic Backups

### Database Backup Script

Create `/usr/local/bin/backup-crashhockey.sh`:

```bash
#!/bin/bash
BACKUP_DIR="/config/backups/crashhockey"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="crashhockey"

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u root -p$MYSQL_PASSWORD $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup files
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /config/www/crashhockey/uploads

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

Make it executable:
```bash
sudo chmod +x /usr/local/bin/backup-crashhockey.sh
```

Add to crontab (daily at 2 AM):
```bash
sudo crontab -e

# Add this line:
0 2 * * * /usr/local/bin/backup-crashhockey.sh >> /var/log/crashhockey-backup.log 2>&1
```

## 11. Monitoring and Logs

### View logs

```bash
# NGINX access log
sudo tail -f /var/log/nginx/crashhockey_access.log

# NGINX error log
sudo tail -f /var/log/nginx/crashhockey_error.log

# PHP error log
sudo tail -f /var/log/php/error.log

# PHP-FPM log
sudo tail -f /var/log/php8.1-fpm.log
```

### Monitor disk space (for video uploads)

```bash
# Check disk usage
df -h

# Check upload directory size
du -sh /config/www/crashhockey/uploads
```

## 12. Performance Optimization

### Enable OPcache

Edit `/etc/php/8.1/fpm/conf.d/10-opcache.ini`:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

Restart PHP-FPM:
```bash
sudo systemctl restart php8.1-fpm
```

### Configure NGINX worker processes

Edit `/etc/nginx/nginx.conf`:

```nginx
# Set to number of CPU cores
worker_processes auto;
worker_connections 1024;
```

## Troubleshooting

### 413 Request Entity Too Large

If you still see this error:
1. Check NGINX config: `client_max_body_size 2G;`
2. Check PHP config: `upload_max_filesize` and `post_max_size`
3. Restart both services:
   ```bash
   sudo systemctl restart nginx
   sudo systemctl restart php8.1-fpm
   ```

### 504 Gateway Timeout

If uploads timeout:
1. Increase timeouts in NGINX config
2. Increase timeouts in PHP-FPM config
3. Check PHP-FPM is running: `sudo systemctl status php8.1-fpm`

### File Upload Errors

Check permissions:
```bash
sudo ls -la /config/www/crashhockey/uploads
# Should show: drwxrwxr-x www-data www-data
```

Check PHP-FPM error log:
```bash
sudo tail -f /var/log/php8.1-fpm.log
```

## Support

For issues or questions:
1. Check logs in `/var/log/nginx/` and `/var/log/php/`
2. Verify configuration with `sudo nginx -t`
3. Ensure all services are running:
   ```bash
   sudo systemctl status nginx
   sudo systemctl status php8.1-fpm
   sudo systemctl status mysql
   ```
