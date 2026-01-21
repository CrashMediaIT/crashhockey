# Crash Hockey Management System

Professional hockey coaching and athlete management platform with comprehensive training, nutrition, and performance tracking capabilities.

## 🚀 Quick Start

For complete deployment instructions, see **[DEPLOYMENT.md](DEPLOYMENT.md)**

### Fast Deploy
1. Clone repository: `git clone https://github.com/CrashMediaIT/crashhockey.git`
2. Import database: `mysql -u user -p crashhockey < database_schema.sql`
3. Run setup wizard: Navigate to `/setup.php`
4. Login and start using the system

## 📋 Key Features

- **Multi-Role Support**: Athletes, Parents, Coaches, Health Coaches, Team Coaches, Admins
- **Session Management**: Booking, scheduling, and attendance tracking
- **Video Analysis**: Drill reviews and coach feedback
- **Health & Fitness**: Workout plans and nutrition tracking
- **Drill Library**: Create, import, and organize training drills
- **Practice Planning**: Build comprehensive practice plans
- **Performance Stats**: Track athlete progress and goals
- **Financial Management**: Packages, billing, and expenses (Admin)
- **Reports & Analytics**: Comprehensive reporting system (Admin)

## 📚 Documentation

- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Complete deployment guide with Docker instructions
- **[NAVIGATION_REFERENCE.md](NAVIGATION_REFERENCE.md)** - Navigation structure and database schema reference
- **[database_schema.sql](database_schema.sql)** - Complete database schema

## 🛠️ Technical Requirements

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.3+
- Apache 2.4+ or Nginx 1.18+
- 512MB RAM minimum

## 🔐 Security Features

- SQL injection protection (PDO)
- XSS protection (htmlspecialchars)
- Password hashing (bcrypt)
- Session security
- Role-based access control

## 📦 Project Structure

```
/crashhockey/
├── index.php              # Entry point
├── dashboard.php          # Main dashboard
├── setup.php              # Setup wizard
├── database_schema.sql    # Database schema
├── DEPLOYMENT.md          # Deployment guide
├── NAVIGATION_REFERENCE.md # Quick reference
└── views/                 # 33 view files
```

## 🚢 Deployment

See **[DEPLOYMENT.md](DEPLOYMENT.md)** for:
- Complete installation instructions
- Docker deployment with docker-compose
- File permissions (including all docker exec commands)
- Production hardening
- Backup strategies
- Troubleshooting guide

## 📈 Current Status

✅ Navigation Structure Complete
✅ Database Schema Complete  
✅ All View Files Created
✅ Documentation Complete
✅ Security Audit Complete

## 📞 Support

Review the comprehensive documentation files:
- DEPLOYMENT.md - Installation and deployment
- NAVIGATION_REFERENCE.md - Structure reference
- Check error logs for troubleshooting

---

**Version**: 1.0 | **Last Updated**: January 21, 2026