# Crash Hockey Management System

Professional hockey coaching and athlete management platform with comprehensive training, nutrition, and performance tracking capabilities.

## 🚀 Quick Start

1. **Setup Database**
   ```bash
   mysql -u username -p crashhockey < database_schema.sql
   ```

2. **Run Setup Wizard**
   - Navigate to: `https://yourdomain.com/setup.php`
   - Complete 4-step configuration
   - Creates admin account and configures system

3. **Login**
   - Access: `https://yourdomain.com/login.php`
   - Use admin credentials created in setup

## 📋 Features

### User Roles
- **Athletes** - Performance tracking, session booking, video reviews
- **Parents** - View athlete progress, manage multiple children
- **Coaches** - Athlete management, drill creation, practice planning
- **Health Coaches** - Workout and nutrition plan management
- **Team Coaches** - Team roster management
- **Admins** - Full system access, accounting, reports

### Core Modules
- Performance Stats & Goals Tracking
- Session Booking & Management
- Video Analysis & Reviews
- Workout Plans & Exercise Library
- Nutrition Plans & Food Database
- Drill Library & Practice Planning
- Mileage Tracking
- Expense Management
- Comprehensive Reporting

## 📚 Documentation

- **[NAVIGATION_RESTRUCTURE.md](NAVIGATION_RESTRUCTURE.md)** - Complete implementation guide
- **[SECURITY_AUDIT.md](SECURITY_AUDIT.md)** - Security analysis and best practices
- **[IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)** - Project overview

## 🛠️ Technical Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Frontend**: HTML5, CSS3, JavaScript
- **Icons**: Font Awesome 6.5.1
- **Fonts**: Inter (Google Fonts)

## 📦 File Structure

```
/crashhockey/
├── index.php                   # Entry point with DB fallback
├── dashboard.php               # Main dashboard with navigation
├── setup.php                   # Setup wizard
├── database_schema.sql         # Complete DB schema
├── views/                      # 33 view files
│   ├── home.php
│   ├── stats.php
│   ├── sessions_*.php
│   ├── video_*.php
│   ├── health_*.php
│   ├── drills_*.php
│   ├── practice_*.php
│   ├── accounting_*.php
│   ├── admin_*.php
│   └── ...
└── DOCUMENTATION.md files

## 🔐 Security

- SQL injection protection (PDO prepared statements)
- XSS protection (htmlspecialchars)
- Password hashing (bcrypt)
- Session-based authentication
- Role-based access control

## 📈 Status

✅ **Navigation Structure**: Complete
✅ **Database Schema**: Complete
✅ **View Files**: 33 files created
✅ **Documentation**: Comprehensive
✅ **Security Audit**: Complete

⏳ **Pending**: Database queries and business logic implementation

## 🤝 Contributing

1. Review documentation files
2. Follow coding standards
3. Test thoroughly
4. Update documentation

## 📞 Support

For issues or questions, review the comprehensive documentation files included in this repository.