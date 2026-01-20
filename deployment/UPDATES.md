# Crash Hockey Platform - Updates Log

This file tracks all major updates and changes to the Crash Hockey platform. Each update is organized by date with a clear title describing the primary change.

---

## January 20, 2026 (Latest) - Deep Purple Theme & Permission Fixes

**Primary Changes:**
- **Brand color updated**: Changed from royal purple (#6b46c1) to deep purple (#7000a4)
- **Consistent branding**: All 20+ files updated with new color across entire application
- **Permission fix enhanced**: Added comprehensive troubleshooting for "Failed to write configuration file" error
- **Setup wizard colors**: Updated setup.php from orange to purple theme
- **Silver accents**: Maintained silver (#c0c0c0) for contrast elements

**Files Updated:**
- setup.php - Full purple theme implementation
- style.css - CSS variables updated to #7000a4
- All views (20+ files) - Consistent purple branding
- dashboard.php, mailer.php, public_sessions.php - Theme updates
- deployment/DEPLOYMENT.md - Enhanced permission troubleshooting

**Permission Troubleshooting Added:**
- Explicit chmod 775 commands for root directory
- Container write access test command
- Detailed permission verification steps
- Owner/group confirmation (911:911)

**Theme:**
- Deep purple primary (#7000a4)
- Silver accents (#c0c0c0)
- Navy background (#06080b) - unchanged

---

## January 20, 2026 - Repository Reorganization & Royal Purple

**Primary Changes:**
- Created `/deployment` folder for server configs and documentation
- Consolidated all guides into single `deployment/DEPLOYMENT.md`
- NGINX config renamed: `nginx.conf` → `crashhockey.conf`
- Setup wizard fixed: Corrected schema path and added SMTP error display
- Email logs viewer added: New admin interface for debugging
- Theme changed: Orange → Royal purple (#6b46c1)

**Documentation:**
- All guides merged into comprehensive DEPLOYMENT.md
- Separate UPDATES.md created for change tracking
- Clean root README.md with quick start
- 10-step deployment process documented

**Fixes:**
- Schema file path corrected in setup wizard
- SMTP error messages now displayed during setup
- Database initialization order fixed

---

## January 20, 2026 - Initial Platform Implementation

**Primary Changes:**
- Complete hockey training platform implementation
- Drill library with categories, tags, and search functionality
- Practice plan builder with IHS JSON import
- Coach-athlete assignment system with video review
- Email notification system with in-app notifications
- Parent/manager accounts with multi-athlete booking
- Age groups and skill levels system
- HST tax configuration
- Package system (credit-based and bundled)
- Enhanced session types (group/private/semi-private)
- Accounting and reporting system
- Cloud receipt integration with Nextcloud OCR
- Mileage tracking with Google Maps
- Refund management system
- Comprehensive role-based permissions (5 roles, 30+ permissions)
- AES-256 database encryption
- CSRF protection and rate limiting
- Security headers and session management

**Database:**
- 28+ tables created
- Complete schema with relationships
- Encrypted configuration storage

**Theme:**
- Orange primary color (#ff4d00)
- Amber accent color (#ff9d00)
- Navy background (#06080b)

**Deployment:**
- Initial deployment guide created
- Docker compatibility with linuxserver/docker-nginx
- 10GB video upload support

---
