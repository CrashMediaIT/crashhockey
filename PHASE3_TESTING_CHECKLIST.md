# PHASE 3 TESTING CHECKLIST

## Overview
This checklist ensures all Phase 3 features are thoroughly tested before production deployment.

---

## 1. DATABASE SCHEMA TESTING

### Schema Installation
- [ ] Run `deployment/schema.sql` on fresh database
- [ ] Verify all 4 new tables created successfully
  - [ ] `reports` table exists with all columns
  - [ ] `report_schedules` table exists with all columns
  - [ ] `security_scans` table exists with all columns
  - [ ] `database_maintenance_logs` table exists with all columns
- [ ] Verify `locations` table has new columns
  - [ ] `google_place_id` column exists
  - [ ] `image_url` column exists
- [ ] Run `setup.php` validation
- [ ] Confirm 76 tables detected
- [ ] No missing tables in validation report

### Foreign Keys & Constraints
- [ ] All foreign keys created successfully
- [ ] Cascade deletes work correctly
- [ ] Unique constraints enforced
- [ ] Index performance verified

---

## 2. COMPREHENSIVE REPORTING SYSTEM

### Report Generation - Athlete Progress (Coach Access)
- [ ] Login as coach account
- [ ] Navigate to Reports & Analytics
- [ ] Select "Athlete Progress" report type
- [ ] Select one athlete from dropdown
- [ ] Set date range (last 30 days)
- [ ] Generate PDF format
  - [ ] Report generates successfully
  - [ ] File downloads correctly
  - [ ] Content displays athlete data
  - [ ] Formatting is correct
  - [ ] Deep purple branding present
- [ ] Generate CSV format
  - [ ] CSV downloads correctly
  - [ ] Headers are correct
  - [ ] Data is accurate
  - [ ] Opens in Excel/Sheets
- [ ] Test with multiple athletes selected
- [ ] Test with all athletes (no selection)

### Report Generation - Team Roster (Coach/Admin Access)
- [ ] Login as coach
- [ ] Select "Team Roster" report
- [ ] Select specific team
- [ ] Generate PDF
  - [ ] Team name displayed
  - [ ] All members listed
  - [ ] Statistics accurate
- [ ] Generate CSV
  - [ ] All columns present
  - [ ] Data matches database
- [ ] Test with "All Teams" option
- [ ] Login as admin - verify access
- [ ] Login as athlete - verify no access

### Report Generation - Session Attendance
- [ ] Select "Session Attendance" report
- [ ] Set date range
- [ ] Generate report
  - [ ] Sessions listed correctly
  - [ ] Attendance counts accurate
  - [ ] Booking status correct
- [ ] Filter by specific dates
- [ ] Verify totals match database queries

### Report Generation - All Athletes (Admin Only)
- [ ] Login as admin
- [ ] Select "All Athletes" report
- [ ] Generate report
  - [ ] All athletes listed
  - [ ] Coach assignments correct
  - [ ] Session counts accurate
- [ ] Test CSV export
- [ ] Login as coach - verify blocked
- [ ] Login as athlete - verify blocked

### Report Generation - All Teams (Admin Only)
- [ ] Select "All Teams" report
- [ ] Generate report
  - [ ] All teams listed
  - [ ] Member counts correct
  - [ ] Coach assignments displayed
- [ ] Export formats work
- [ ] Access control verified

### Report Generation - Packages & Discounts (Admin Only)
- [ ] Select "Packages & Discounts" report
- [ ] Set date range
- [ ] Generate report
  - [ ] Package sales listed
  - [ ] Revenue calculated correctly
  - [ ] Discount usage accurate
- [ ] Test different date ranges
- [ ] Verify financial calculations

### Report Scheduling
- [ ] Generate a report
- [ ] Check "Schedule this report" box
- [ ] Select frequency (Weekly)
- [ ] Select format (PDF)
- [ ] Add email recipients
- [ ] Submit form
- [ ] Verify schedule appears in "Scheduled Reports"
- [ ] Check `report_schedules` table entry
- [ ] Verify `next_run` date is calculated
- [ ] Test Pause schedule
- [ ] Test Delete schedule
- [ ] Test multiple schedules

### Report Sharing
- [ ] Generate a report
- [ ] Click "Copy Share Link" icon
- [ ] Verify token in clipboard
- [ ] Open link in incognito/private window
- [ ] Verify login redirect
- [ ] Login and access report
- [ ] Verify report displays correctly
- [ ] Test with different report types
- [ ] Verify share token is unique
- [ ] Test token security (invalid tokens blocked)

### Report Management
- [ ] View recent reports list
- [ ] Download existing report
- [ ] Share existing report
- [ ] Delete report
  - [ ] File removed from server
  - [ ] Database record removed
  - [ ] No longer in list
- [ ] Test with different file formats
- [ ] Verify file permissions

### Report Access Control
- [ ] Athlete role - no access to reports
- [ ] Parent role - no access to reports
- [ ] Coach - access to allowed reports only
- [ ] Coach Plus - access to allowed reports only
- [ ] Team Coach - access to team reports
- [ ] Admin - access to all reports

---

## 3. COACH ATHLETE FILTERING

### Filter by Team
- [ ] Login as coach
- [ ] Navigate to Athletes page
- [ ] Open Team filter dropdown
- [ ] Select a specific team
- [ ] Click Filter button
  - [ ] Only athletes from selected team shown
  - [ ] Athlete count updates
  - [ ] Team names displayed correctly
- [ ] Select different team
- [ ] Verify results change
- [ ] Select "All Teams"
- [ ] Verify all athletes return

### Filter by Age Group
- [ ] Open Age Group dropdown
- [ ] Select an age group
- [ ] Click Filter
  - [ ] Only athletes in age range shown
  - [ ] Birth dates match age group
  - [ ] Count is accurate
- [ ] Test with different age groups
- [ ] Verify edge cases (exact min/max age)

### Filter by Name/Email
- [ ] Enter partial name in search box
- [ ] Click Filter
  - [ ] Matching athletes shown
  - [ ] Case-insensitive search works
- [ ] Enter email address
  - [ ] Email search works
- [ ] Enter non-existent name
  - [ ] Empty state displays
- [ ] Test special characters
- [ ] Test with spacing

### Combined Filters
- [ ] Apply team + age group filters
- [ ] Verify results match both criteria
- [ ] Add name filter
- [ ] Verify all three filters applied
- [ ] Remove one filter at a time
- [ ] Verify results update correctly

### Clear Filters
- [ ] Apply multiple filters
- [ ] Click "Clear" button
  - [ ] All dropdowns reset
  - [ ] Search box cleared
  - [ ] All athletes displayed
  - [ ] URL parameters cleared

### Filter Persistence
- [ ] Apply filters
- [ ] Click on an athlete
- [ ] Return to athletes page
- [ ] Verify filters NOT persisted (correct behavior)

---

## 4. WEEKLY SECURITY SCANNER

### Manual Execution (CLI)
- [ ] SSH into server
- [ ] Run: `php cron_security_scan.php`
- [ ] Verify scan completes
- [ ] Check console output
  - [ ] Checks performed count
  - [ ] Vulnerabilities found count
  - [ ] Scan duration displayed
- [ ] Verify database entry created
- [ ] Check `security_scans` table

### Manual Execution (Web with Key)
- [ ] Access: `/cron_security_scan.php?key=CORRECT_KEY`
- [ ] Verify scan runs
- [ ] Try with incorrect key
  - [ ] 403 Forbidden returned
  - [ ] Scan does not execute

### Vulnerability Detection
- [ ] Check SQL injection detection
  - [ ] Scans process files
  - [ ] Identifies potential issues
- [ ] Check XSS detection
  - [ ] Scans view files
  - [ ] Identifies unescaped output
- [ ] Check file permissions
  - [ ] Identifies world-readable files
  - [ ] Reports permission issues
- [ ] Check CSRF protection
  - [ ] Identifies missing protection
  - [ ] Reports files without tokens
- [ ] Check password security
  - [ ] Queries database
  - [ ] Identifies weak hashes
- [ ] Check session security
  - [ ] Reviews PHP configuration
  - [ ] Reports missing settings

### Admin Notifications
- [ ] Run scan with vulnerabilities present
- [ ] Check admin email received
  - [ ] Subject line correct
  - [ ] Vulnerability count in email
  - [ ] Severity summary included
  - [ ] Top 5 vulnerabilities listed
  - [ ] Link to full results works
- [ ] Check in-app notification
  - [ ] Notification created
  - [ ] Shows in notifications page
  - [ ] Marked as high priority
  - [ ] Contains summary
- [ ] Verify `notified_admins` flag set
- [ ] Test with multiple admins

### Scan Results
- [ ] View scan results in database
- [ ] Verify JSON structure
- [ ] Check severity classification
- [ ] Verify scan duration recorded
- [ ] Check vulnerability count accurate

### Scheduled Execution
- [ ] Set up cron job: `0 2 * * 0`
- [ ] Wait for scheduled run OR manually trigger
- [ ] Verify execution on Sunday 2 AM
- [ ] Check logs for errors
- [ ] Verify consistent execution

---

## 5. GOOGLE PLACES API INTEGRATION

### Location Search
- [ ] Login as admin
- [ ] Navigate to Locations page
- [ ] Click "Add Location"
- [ ] Type location name in search box
  - [ ] Wait 500ms for debounce
  - [ ] Results appear below search
  - [ ] Results are relevant
- [ ] Test with different queries
  - [ ] Arena names
  - [ ] Street addresses
  - [ ] City names
- [ ] Test minimum character requirement (3 chars)

### Location Selection
- [ ] Search for a location
- [ ] Click on a result
  - [ ] Arena name field populated
  - [ ] City field populated (from address)
  - [ ] Google Place ID stored (hidden field)
  - [ ] Image preview appears
  - [ ] Image URL stored (hidden field)
  - [ ] Search results hide
- [ ] Verify data accuracy
- [ ] Test with locations without images

### Saving Location with Google Data
- [ ] Select location from search
- [ ] Save location
  - [ ] Record created in database
  - [ ] `google_place_id` saved
  - [ ] `image_url` saved
  - [ ] Appears in locations list
- [ ] Verify location in table
  - [ ] Image thumbnail shows
  - [ ] "View on Map" link present

### Viewing Location on Map
- [ ] Click "View on Map" link
  - [ ] Opens in new tab
  - [ ] Google Maps loads
  - [ ] Correct location shown
- [ ] Test on mobile device
  - [ ] Opens in map app

### Editing Locations
- [ ] Edit existing location
- [ ] Search for new location
- [ ] Update location details
- [ ] Save changes
  - [ ] Google data updates
  - [ ] Image changes
- [ ] Test removing image
- [ ] Verify updates in database

### Image Management
- [ ] Select location with image
- [ ] Preview displays correctly
- [ ] Click "Remove Image"
  - [ ] Preview hides
  - [ ] Image URL cleared
- [ ] Save without image
- [ ] Verify no image in database

### Manual Entry (Without Google)
- [ ] Click "Add Location"
- [ ] Do NOT use search
- [ ] Manually enter name and city
- [ ] Save location
  - [ ] Works without Google data
  - [ ] No Place ID stored
  - [ ] No image
- [ ] Verify backward compatibility

---

## 6. DATABASE REPAIR & MAINTENANCE TOOL

### Access Control
- [ ] Login as athlete - verify blocked
- [ ] Login as coach - verify blocked
- [ ] Login as admin - access granted
- [ ] Verify URL direct access blocked for non-admins

### Check Database Integrity
- [ ] Click "Run Integrity Check"
- [ ] Wait for completion
- [ ] Review results panel
  - [ ] Checks performed count
  - [ ] Issues found count
  - [ ] Issue details listed
- [ ] Verify orphaned records detection
- [ ] Check different table relationships
- [ ] Test with clean database (no issues)
- [ ] Test with orphaned records present

### Repair Tables
- [ ] Click "Repair All Tables"
- [ ] Wait for completion
- [ ] Review results
  - [ ] Tables repaired count
  - [ ] Table names listed
- [ ] Check database for repairs
- [ ] Verify table integrity after repair
- [ ] Test error handling

### Optimize Tables
- [ ] Click "Optimize All Tables"
- [ ] Wait for completion
- [ ] Review results
  - [ ] Tables optimized count
  - [ ] Performance improvement noted
- [ ] Check table sizes before/after
- [ ] Verify data integrity maintained
- [ ] Test on large tables

### Check Foreign Keys
- [ ] Click "Check Foreign Keys"
- [ ] Review results
  - [ ] Foreign keys checked count
  - [ ] Violations found
  - [ ] Violation details
- [ ] Test with valid FKs
- [ ] Test with broken FKs
- [ ] Verify constraint names correct

### Repair Foreign Keys
- [ ] Click "Repair Foreign Keys"
- [ ] Review recommendations
- [ ] Verify manual review note present
- [ ] Check repair attempts
- [ ] Test with actual FK violations
- [ ] Verify data safety

### Performance Analysis
- [ ] Click "Analyze Performance"
- [ ] Review large tables list
- [ ] Check table sizes
- [ ] Verify tables without PK identified
- [ ] Review recommendations
- [ ] Test with various database states

### Table Status Overview
- [ ] Scroll to Table Status section
- [ ] Verify all tables listed
- [ ] Check columns
  - [ ] Table name
  - [ ] Engine type
  - [ ] Row count
  - [ ] Data size
  - [ ] Index size
  - [ ] Total size
- [ ] Verify calculations accurate
- [ ] Test sorting if implemented

### Maintenance Logs
- [ ] Perform various maintenance tasks
- [ ] Scroll to Recent Maintenance Logs
- [ ] Verify each action logged
  - [ ] Action type correct
  - [ ] User name displayed
  - [ ] Timestamp accurate
  - [ ] Status badge correct
- [ ] Test log persistence
- [ ] Verify 20 most recent shown

### Error Handling
- [ ] Test with database connection issues
- [ ] Test with insufficient permissions
- [ ] Verify error messages displayed
- [ ] Check status badges (error state)
- [ ] Verify rollback on failures

---

## 7. NAVIGATION & UI TESTING

### Navigation - Coach Access
- [ ] Login as coach
- [ ] Verify "Reports & Analytics" in Coach Management
- [ ] Click reports link
- [ ] Verify page loads
- [ ] Check active state in navigation

### Navigation - Admin Access
- [ ] Login as admin
- [ ] Verify "Database Tools" in System Admin
- [ ] Verify "Reports & Analytics" in Accounting
- [ ] Test both links
- [ ] Verify active states

### Navigation - Access Control
- [ ] Test athlete access
  - [ ] No reports link visible
  - [ ] Direct URL blocked
- [ ] Test parent access
  - [ ] No access to admin tools
- [ ] Test team coach access
  - [ ] Reports available
  - [ ] Database tools blocked

### Responsive Design
- [ ] Test on desktop (1920x1080)
- [ ] Test on laptop (1366x768)
- [ ] Test on tablet (768px)
- [ ] Test on mobile (375px)
- [ ] Verify all features accessible
- [ ] Check table scrolling
- [ ] Verify modal display

### Cross-Browser Testing
- [ ] Test on Chrome
- [ ] Test on Firefox
- [ ] Test on Safari
- [ ] Test on Edge
- [ ] Verify consistent behavior
- [ ] Check for console errors

---

## 8. PERFORMANCE TESTING

### Report Generation Performance
- [ ] Generate small report (<100 records)
  - [ ] Under 5 seconds
- [ ] Generate medium report (100-1000 records)
  - [ ] Under 15 seconds
- [ ] Generate large report (>1000 records)
  - [ ] Complete within reasonable time
  - [ ] No timeout errors
- [ ] Test concurrent report generation
- [ ] Monitor server resources

### Database Tool Performance
- [ ] Run integrity check on large database
  - [ ] Completes without timeout
- [ ] Optimize all tables
  - [ ] Reasonable completion time
- [ ] Monitor query execution times
- [ ] Check for slow queries

### Security Scanner Performance
- [ ] Run complete security scan
  - [ ] Completes in under 5 minutes
- [ ] Monitor resource usage
- [ ] Check for memory issues
- [ ] Verify no impact on live site

### Page Load Performance
- [ ] Reports page load time < 2 seconds
- [ ] Athletes page with filters < 2 seconds
- [ ] Database tools page < 2 seconds
- [ ] Locations page < 2 seconds

---

## 9. SECURITY TESTING

### CSRF Protection
- [ ] Test form submission without token
  - [ ] 403 Forbidden returned
- [ ] Test with invalid token
  - [ ] Blocked
- [ ] Test with valid token
  - [ ] Succeeds
- [ ] Test all new forms

### SQL Injection Prevention
- [ ] Test report filters with SQL code
  - [ ] Properly escaped
- [ ] Test athlete search with injection
  - [ ] No database error
- [ ] Test location search
  - [ ] Sanitized input
- [ ] Review all queries for prepared statements

### XSS Prevention
- [ ] Test report names with script tags
  - [ ] Escaped on display
- [ ] Test location names with HTML
  - [ ] Properly sanitized
- [ ] Test all user input fields
- [ ] Check for reflected XSS

### Access Control
- [ ] Test direct URL access
  - [ ] Non-admin blocked from admin pages
  - [ ] Non-coach blocked from coach pages
- [ ] Test API endpoints
- [ ] Verify session validation
- [ ] Test privilege escalation

### File Security
- [ ] Check report file permissions
  - [ ] Not world-readable
  - [ ] Accessible only via application
- [ ] Test direct file access
  - [ ] Blocked or requires authentication
- [ ] Verify upload directory security
- [ ] Check for directory traversal

---

## 10. ERROR HANDLING & VALIDATION

### Form Validation
- [ ] Submit empty report form
  - [ ] Error message displayed
- [ ] Submit with invalid date range
  - [ ] Validation error shown
- [ ] Test required fields
- [ ] Test email format validation
- [ ] Test all form validations

### Database Errors
- [ ] Simulate connection failure
  - [ ] Graceful error message
- [ ] Test with invalid foreign key
  - [ ] Proper error handling
- [ ] Test constraint violations
  - [ ] User-friendly error

### File System Errors
- [ ] Test report generation with full disk
  - [ ] Error reported
- [ ] Test with no write permissions
  - [ ] Appropriate error message
- [ ] Test file not found scenarios

### API Errors (Google Places)
- [ ] Test with invalid API key
  - [ ] Error handling works
- [ ] Test with quota exceeded
  - [ ] Fallback mechanism
- [ ] Test with network timeout

---

## 11. DATA INTEGRITY TESTING

### Report Data Accuracy
- [ ] Compare report data with database
- [ ] Verify calculations (totals, averages)
- [ ] Check date filtering accuracy
- [ ] Verify athlete assignments
- [ ] Check team membership data

### Filter Data Accuracy
- [ ] Verify filtered results match criteria
- [ ] Check age calculations
- [ ] Verify team associations
- [ ] Test edge cases

### Maintenance Log Accuracy
- [ ] Verify log entries match actions
- [ ] Check timestamps
- [ ] Verify user attribution
- [ ] Check status reporting

---

## 12. INTEGRATION TESTING

### Reports + Athletes
- [ ] Filter athletes
- [ ] Generate report on filtered set
- [ ] Verify report matches filter

### Security Scan + Notifications
- [ ] Run scan
- [ ] Verify notification created
- [ ] Check email sent
- [ ] Verify notification content

### Database Tools + Logs
- [ ] Run maintenance task
- [ ] Verify log created
- [ ] Check log details
- [ ] Verify searchable/sortable

### Scheduled Reports + Email
- [ ] Create scheduled report
- [ ] Wait for execution
- [ ] Verify email sent
- [ ] Check report generated

---

## 13. DEPLOYMENT TESTING

### Fresh Installation
- [ ] Deploy to clean environment
- [ ] Run schema.sql
- [ ] Access setup.php
- [ ] Initialize database
- [ ] Verify all tables created
- [ ] Create admin user
- [ ] Test all features

### Update/Migration
- [ ] Deploy over existing installation
- [ ] Run schema updates only
- [ ] Verify existing data intact
- [ ] Test new features
- [ ] Verify no breaking changes

### Rollback Testing
- [ ] Document rollback procedure
- [ ] Test rolling back schema
- [ ] Verify data recovery
- [ ] Test application stability

---

## 14. DOCUMENTATION TESTING

### Implementation Tracking
- [ ] Review IMPLEMENTATION_TRACKING.md
- [ ] Verify all features documented
- [ ] Check instructions accuracy
- [ ] Test setup procedures
- [ ] Verify troubleshooting guides

### Code Comments
- [ ] Review code comments
- [ ] Verify function documentation
- [ ] Check complex logic explained
- [ ] Verify security notes present

### User Documentation
- [ ] Create user guide if needed
- [ ] Document report types
- [ ] Explain filtering options
- [ ] Document maintenance procedures

---

## 15. ACCEPTANCE CRITERIA

### Must Pass (Critical)
- [ ] All database tables created successfully
- [ ] Reports generate correctly (all types)
- [ ] Athlete filtering works accurately
- [ ] Security scanner executes without errors
- [ ] Database tools function correctly
- [ ] No security vulnerabilities introduced
- [ ] Access control enforced
- [ ] CSRF protection on all forms
- [ ] No SQL injection vulnerabilities
- [ ] All error handling in place

### Should Pass (Important)
- [ ] Google Places API integration works
- [ ] Scheduled reports execute on time
- [ ] Email notifications sent
- [ ] Performance is acceptable
- [ ] UI is responsive
- [ ] Cross-browser compatibility
- [ ] Navigation is intuitive

### Nice to Have
- [ ] PDF generation with library
- [ ] Report caching
- [ ] Advanced filtering options
- [ ] Export to Excel

---

## SIGN-OFF

### Tester Information
- **Name:** ___________________________
- **Date:** ___________________________
- **Environment:** ___________________________

### Test Results
- **Total Tests:** _____
- **Passed:** _____
- **Failed:** _____
- **Blocked:** _____

### Critical Issues Found
1. ___________________________________________
2. ___________________________________________
3. ___________________________________________

### Approval
- [ ] Ready for production deployment
- [ ] Requires fixes before deployment

**Signature:** ___________________________
**Date:** ___________________________

---

## NOTES

Use this section to document any additional findings, suggestions, or observations during testing.

---

**Document Version:** 1.0
**Last Updated:** January 2025
**Status:** Ready for Testing
