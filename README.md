# Crash Hockey Training Platform

A comprehensive hockey training platform with drill management, practice plans, athlete tracking, and administrative tools.

## 🚀 Quick Start

### Prerequisites
- MySQL 8.0+
- PHP 8.1+ with extensions: pdo_mysql, openssl, curl, mbstring
- NGINX or Apache web server
- Docker (optional, see deployment guide)

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/CrashMediaIT/crashhockey.git
cd crashhockey
```

2. **Set up database**
```sql
CREATE DATABASE crashhockey CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'crashhockey'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON crashhockey.* TO 'crashhockey'@'localhost';
FLUSH PRIVILEGES;
```

3. **Deploy configuration files**

For Docker (linuxserver/docker-nginx):
```bash
# Copy application files
sudo cp -r * /config/www/crashhockey/

# Deploy server configuration
sudo cp deployment/crashhockey.conf /config/nginx/site-confs/
sudo cp deployment/php-config.ini /config/php/

# Create required directories
sudo mkdir -p /config/www/crashhockey/{uploads,sessions,cache}
sudo chown -R 911:911 /config/www/crashhockey
sudo chmod -R 775 /config/www/crashhockey/{uploads,sessions,cache}

# Restart services
docker restart <container_name>
```

4. **Run setup wizard**
Navigate to `https://your-domain.com/setup.php` and follow the 4-step wizard:
- Step 1: Database credentials & encryption key
- Step 2: Initialize database tables
- Step 3: Configure and test SMTP
- Step 4: Create admin account

## 📁 Project Structure

```
crashhockey/
├── deployment/          # Server configuration & documentation
│   ├── DEPLOYMENT.md   # Complete deployment guide
│   ├── schema.sql      # Database schema
│   ├── crashhockey.conf # NGINX configuration
│   └── php-config.ini  # PHP settings (10GB upload limit)
├── views/              # Page templates
├── setup.php           # Installation wizard
├── dashboard.php       # Main application entry
├── style.css           # Purple theme styling
└── *.php              # Application files
```

## 📚 Documentation

See `/deployment/DEPLOYMENT.md` for comprehensive documentation including:
- Complete deployment instructions
- Feature documentation
- Troubleshooting guide
- API configuration
- Security best practices

## 🎨 Theme

- **Primary Color**: Purple (#8b5cf6)
- **Accent Color**: Silver (#c0c0c0)
- **Background**: Navy/Dark (#06080b)

## 🔧 Features

- ✅ Drill library with categorization
- ✅ Practice plan builder
- ✅ Coach-athlete assignments
- ✅ Video review system
- ✅ Parent/manager accounts
- ✅ Package system
- ✅ Accounting & reporting
- ✅ Email notifications
- ✅ Multi-role permissions
- ✅ Age group & skill level filtering
- ✅ HST tax system
- ✅ Cloud receipt integration
- ✅ Mileage tracking

## 🆘 Support

For issues or questions:
1. Check `/deployment/DEPLOYMENT.md` for troubleshooting
2. Review email logs at Dashboard → System Admin → Email Logs
3. Check PHP error log: `/config/log/php-error.log`

## 📄 License

Copyright © 2026 Crash Hockey. All rights reserved.
