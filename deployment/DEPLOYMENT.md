# Crash Hockey Training Platform - Deployment Guide

Complete step-by-step deployment instructions for Docker setup using **linuxserver.io nginx** and **official MariaDB** containers.

---

## Step 1: Setup Fedora Server and Update via SSH

```bash
# Connect to your server
ssh root@your-server-ip

# Update the system
sudo dnf update -y
sudo dnf upgrade -y

# Install basic tools
sudo dnf install -y git curl wget nano
```

---

## Step 2: Install Docker

```bash
# Install Docker on Fedora
sudo dnf install -y dnf-plugins-core
sudo dnf config-manager --add-repo https://download.docker.com/linux/fedora/docker-ce.repo
sudo dnf install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Start and enable Docker
sudo systemctl start docker
sudo systemctl enable docker

# Verify Docker installation
docker --version
docker compose version

# Add your user to docker group (optional, requires re-login)
sudo usermod -aG docker $USER
```

---

## Step 3: Install Portainer (Container Management UI)

```bash
# Create Portainer volume
docker volume create portainer_data

# Run Portainer
docker run -d \
  -p 8000:8000 \
  -p 9443:9443 \
  --name portainer \
  --restart=always \
  -v /var/run/docker.sock:/var/run/docker.sock \
  -v portainer_data:/data \
  portainer/portainer-ce:latest

# Access Portainer at https://your-server-ip:9443
echo "Access Portainer at: https://$(hostname -I | awk '{print $1}'):9443"
```

---

## Step 4: Setup MariaDB Database

```bash
# Create MariaDB container
docker run -d \
  --name mariadb \
  --restart=always \
  -e MYSQL_ROOT_PASSWORD=your_secure_root_password \
  -e MYSQL_DATABASE=crashhockey \
  -e MYSQL_USER=chroot \
  -e MYSQL_PASSWORD=your_secure_password \
  -v mariadb_data:/var/lib/mysql \
  -p 3306:3306 \
  mariadb:latest

# Wait for MariaDB to start (about 30 seconds)
sleep 30

# Verify MariaDB is running
docker ps | grep mariadb

# Test connection
docker exec -it mariadb mysql -uchroot -p crashhockey
# Enter the password when prompted, then type 'EXIT;' to quit
```

**Important:** Note down these credentials:
- **Database**: `crashhockey`
- **Username**: `chroot`
- **Password**: `your_secure_password`
- **Host**: `mariadb` (when accessed from other containers) or `localhost` (from host)

---

## Step 5: Setup linuxserver.io NGINX Container

```bash
# Create config directory
sudo mkdir -p /config

# Run linuxserver/nginx container with bind mount
docker run -d \
  --name=nginx \
  --restart=always \
  -e PUID=911 \
  -e PGID=911 \
  -e TZ=America/Toronto \
  -p 80:80 \
  -p 443:443 \
  -v /config:/config \
  --link mariadb:mariadb \
  lscr.io/linuxserver/nginx:latest

# Verify NGINX is running
docker ps | grep nginx

# Check NGINX logs
docker logs nginx
```

**Important Directory Structure Created:**
- `/config/www/` - Web root directory
- `/config/nginx/site-confs/` - NGINX site configurations
- `/config/php/` - PHP configuration files
- `/config/log/` - Log files

---

## Step 6: Clone Repository to WWW Folder

```bash
# Navigate to NGINX www directory
cd /config/www

# Remove default files if they exist
sudo rm -rf html

# Clone the Crash Hockey repository
sudo git clone https://github.com/CrashMediaIT/crashhockey.git crashhockey

# Navigate into the directory
cd crashhockey

# Verify files are present
ls -la
```

---

## Step 7: Create Missing Folders and Set Permissions

```bash
# Create required directories
sudo mkdir -p /config/www/crashhockey/uploads
sudo mkdir -p /config/www/crashhockey/sessions
sudo mkdir -p /config/www/crashhockey/cache
sudo mkdir -p /config/log

# Set ownership to NGINX user (UID 911 = 'abc' user in linuxserver containers)
sudo chown -R 911:911 /config/www/crashhockey
sudo chown -R 911:911 /config/log

# Set directory permissions (755 for directories, 644 for files)
sudo find /config/www/crashhockey -type d -exec chmod 755 {} \;
sudo find /config/www/crashhockey -type f -exec chmod 644 {} \;

# Make writable directories (775 for uploads, sessions, cache, and root)
sudo chmod 775 /config/www/crashhockey
sudo chmod -R 775 /config/www/crashhockey/uploads
sudo chmod -R 775 /config/www/crashhockey/sessions
sudo chmod -R 775 /config/www/crashhockey/cache

# Verify permissions
ls -la /config/www/crashhockey
```

**Permission Summary:**
- **Root directory**: `775` (allows PHP to write config file during setup)
- **Uploads/Sessions/Cache**: `775` (web server needs write access)
- **Regular files**: `644` (readable by web server, not writable)
- **Regular directories**: `755` (traversable by web server)
- **Owner**: `911:911` (abc user in linuxserver container)

---

## Step 8: Move Configuration Files to Correct Directories

```bash
# Copy NGINX configuration
sudo cp /config/www/crashhockey/deployment/crashhockey.conf \
  /config/nginx/site-confs/crashhockey.conf

# Copy PHP configuration
sudo cp /config/www/crashhockey/deployment/php-config.ini \
  /config/php/php-config.ini

# Set correct ownership
sudo chown 911:911 /config/nginx/site-confs/crashhockey.conf
sudo chown 911:911 /config/php/php-config.ini

# Verify files are in place
ls -la /config/nginx/site-confs/
ls -la /config/php/
```

**What Each File Does:**
- **crashhockey.conf**: NGINX server configuration (domain, PHP, upload limits)
- **php-config.ini**: PHP settings (upload limits, memory, timeouts)

---

## Step 9: Restart NGINX Container

```bash
# Restart NGINX to apply configurations
docker restart nginx

# Wait for container to restart
sleep 10

# Check NGINX status
docker ps | grep nginx

# View NGINX logs for any errors
docker logs nginx --tail 50

# Test NGINX configuration
docker exec nginx nginx -t

# Check that the site is accessible
curl -I http://localhost
# Should return "HTTP/1.1 200 OK" or redirect to HTTPS
```

**Troubleshooting:**
- If NGINX won't start, check logs: `docker logs nginx`
- Test config syntax: `docker exec nginx nginx -t`
- Check file permissions: `ls -la /config/nginx/site-confs/`

---

## Step 10: Run the Setup Wizard

### Access the Setup Wizard

1. Open your browser and navigate to:
   ```
   http://your-domain.com/setup.php
   ```
   Or use your server IP:
   ```
   http://your-server-ip/setup.php
   ```

### Step-by-Step Setup Process

#### **Page 1: Database Configuration**
- **Database Host**: `mariadb` (Docker container name)
- **Database Name**: `crashhockey`
- **Database Username**: `chroot`
- **Database Password**: `your_secure_password` (from Step 4)
- **Encryption Key**: Click "Generate" or enter your own 32-character key
- Click **"Save Configuration"**

#### **Page 2: Initialize Database**
- Review the schema that will be created
- Click **"Initialize Database"**
- Wait for all tables to be created (may take 30-60 seconds)
- You should see success messages for each table created

#### **Page 3: SMTP Configuration**
- **SMTP Host**: Your email provider (e.g., `smtp.gmail.com`)
- **SMTP Port**: Usually `587` for TLS or `465` for SSL
- **SMTP Username**: Your full email address
- **SMTP Password**: App password (not your regular email password)
- **Encryption**: Select `TLS` (recommended) or `SSL`
- **From Name**: `Crash Hockey`
- **From Email**: `noreply@your-domain.com`
- Click **"Test SMTP Connection"** to verify
- Click **"Save SMTP Settings"**

**SMTP Providers:**
- **Gmail**: Use App Password (not your regular password)
  - Generate at: https://myaccount.google.com/apppasswords
  - SMTP Host: `smtp.gmail.com`, Port: `587`, Encryption: `TLS`
- **Office365**: Use your email and password
  - SMTP Host: `smtp.office365.com`, Port: `587`, Encryption: `TLS`
- **Custom SMTP**: Contact your email provider for settings

#### **Page 4: Create Admin Account**
- **Full Name**: Your name
- **Email Address**: Your admin email (will receive verification email)
- **Username**: Admin username for login
- **Password**: Strong password (minimum 8 characters)
- **Confirm Password**: Re-enter password
- Click **"Create Admin Account"**

### Post-Setup

After completing the setup wizard:
1. **Check your email** for the admin verification email
2. **Login** with your admin credentials at: `http://your-domain.com/login.php`
3. **Verify SMTP** by checking the Email Logs page (Dashboard → System Admin → Email Logs)
4. **Setup is complete!** You can now configure your training platform

---

## Verification Checklist

After deployment, verify everything is working:

- [ ] **NGINX Container Running**: `docker ps | grep nginx`
- [ ] **MariaDB Container Running**: `docker ps | grep mariadb`
- [ ] **Website Accessible**: Visit `http://your-domain.com`
- [ ] **Setup Wizard Completed**: All 4 steps finished
- [ ] **Admin Login Works**: Can login at `/login.php`
- [ ] **Email Sending Works**: Check Email Logs page
- [ ] **File Uploads Work**: Try uploading a profile picture
- [ ] **Permissions Correct**: Check `/config/www/crashhockey` ownership is `911:911`

---

## Common Issues and Solutions

### Issue: "Failed to write configuration file"
**Solution:**
```bash
# Ensure root directory is writable
sudo chmod 775 /config/www/crashhockey
sudo chown 911:911 /config/www/crashhockey

# Verify
ls -ld /config/www/crashhockey
# Should show: drwxrwxr-x  911 911  /config/www/crashhockey
```

### Issue: "502 Bad Gateway"
**Solutions:**
1. Check PHP-FPM is running:
   ```bash
   docker exec nginx ps aux | grep php-fpm
   ```
2. Verify NGINX config:
   ```bash
   docker exec nginx nginx -t
   ```
3. Check logs:
   ```bash
   docker logs nginx
   tail -50 /config/log/php-error.log
   ```

### Issue: "Can't connect to database"
**Solutions:**
1. Verify MariaDB is running:
   ```bash
   docker ps | grep mariadb
   ```
2. Test connection:
   ```bash
   docker exec -it mariadb mysql -uchroot -p crashhockey
   ```
3. Check Docker link:
   ```bash
   docker exec nginx ping -c 3 mariadb
   ```

### Issue: "Email not sending"
**Solutions:**
1. Check Email Logs page (Dashboard → System Admin → Email Logs)
2. View error messages in the logs table
3. Verify SMTP settings are correct
4. For Gmail, ensure you're using an App Password, not your regular password
5. Check PHP error log:
   ```bash
   tail -50 /config/log/php-error.log | grep -i mail
   ```

### Issue: "Permission denied" errors
**Solution:**
```bash
# Reset all permissions
cd /config/www/crashhockey
sudo chown -R 911:911 .
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;
sudo chmod 775 .
sudo chmod -R 775 uploads sessions cache
```

---

## SSL/HTTPS Setup (Optional but Recommended)

For production deployment, enable HTTPS:

### Option 1: Using Let's Encrypt (Recommended)

```bash
# Install certbot in NGINX container
docker exec -it nginx apk add certbot certbot-nginx

# Obtain certificate (replace with your domain)
docker exec -it nginx certbot --nginx -d your-domain.com -d www.your-domain.com

# Certificates will be auto-renewed
```

### Option 2: Using Existing Certificates

```bash
# Copy your certificates to NGINX
sudo mkdir -p /config/nginx/ssl
sudo cp your-certificate.crt /config/nginx/ssl/crashhockey.ca.crt
sudo cp your-private-key.key /config/nginx/ssl/crashhockey.ca.key
sudo chown -R 911:911 /config/nginx/ssl

# The crashhockey.conf is already configured to use these paths
# Just restart NGINX
docker restart nginx
```

---

## Maintenance Commands

### View Logs
```bash
# NGINX access log
tail -f /config/log/crashhockey_access.log

# NGINX error log
tail -f /config/log/crashhockey_error.log

# PHP error log
tail -f /config/log/php-error.log

# Docker container logs
docker logs nginx --tail 100 -f
docker logs mariadb --tail 100 -f
```

### Backup Database
```bash
# Backup to file
docker exec mariadb mysqldump -uchroot -p crashhockey > backup_$(date +%Y%m%d).sql

# Restore from backup
docker exec -i mariadb mysql -uchroot -p crashhockey < backup_20260120.sql
```

### Update Application
```bash
cd /config/www/crashhockey
sudo git pull origin main
sudo chown -R 911:911 .
docker restart nginx
```

### Restart Services
```bash
# Restart NGINX
docker restart nginx

# Restart MariaDB
docker restart mariadb

# Restart both
docker restart nginx mariadb
```

---

## Security Best Practices

1. **Change default passwords** - Never use example passwords in production
2. **Enable HTTPS** - Use Let's Encrypt or purchase SSL certificate
3. **Regular backups** - Automate daily database backups
4. **Keep updated** - Regularly update Docker containers and application
5. **Firewall rules** - Only expose ports 80, 443, and 9443 (Portainer)
6. **Strong encryption key** - Use a random 32-character encryption key
7. **Monitor logs** - Regularly check logs for suspicious activity

---

## Additional Configuration

### Setup Cron Jobs (Automated Tasks)

Create a cron script on your host:

```bash
# Create cron script
sudo nano /usr/local/bin/crashhockey-cron.sh
```

Add the following:
```bash
#!/bin/bash
# Crash Hockey Cron Jobs

# Receipt scanner (every 5 minutes)
docker exec nginx /usr/bin/php /config/www/crashhockey/cron_receipt_scanner.php

# Notifications (every 10 minutes)
docker exec nginx /usr/bin/php /config/www/crashhockey/cron_notifications.php

# Credit expiry check (daily at 2 AM)
docker exec nginx /usr/bin/php /config/www/crashhockey/cron_credit_expiry.php
```

Make executable and add to crontab:
```bash
sudo chmod +x /usr/local/bin/crashhockey-cron.sh

# Edit root crontab
sudo crontab -e

# Add these lines:
*/5 * * * * /usr/local/bin/crashhockey-cron.sh >> /var/log/crashhockey-cron.log 2>&1
```

---

## Support and Documentation

- **Project Updates**: See `deployment/UPDATES.md` for change history
- **Quick Reference**: See `/README.md` in repository root
- **Email Debugging**: Dashboard → System Admin → Email Logs
- **Error Logs**: `/config/log/` directory

For issues during deployment, check:
1. Docker container status: `docker ps -a`
2. NGINX logs: `docker logs nginx`
3. PHP error log: `tail -f /config/log/php-error.log`
4. Database connectivity: `docker exec -it mariadb mysql -uchroot -p crashhockey`
5. File permissions: `ls -la /config/www/crashhockey`

---

**Deployment complete!** Your Crash Hockey Training Platform should now be fully operational.
