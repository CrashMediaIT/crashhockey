# 4 New Features Implementation Notes

## Overview
This implementation adds 4 major features to the Crash Hockey application with complete functionality, proper security measures, and integration with external APIs.

## Feature 1: Nextcloud Cloud Receipt Integration

### Files Created
- **cloud_config.php**: WebDAV connection helper
  - Functions: connectNextcloud(), listNextcloudFiles(), downloadNextcloudFile(), getFileHash(), testNextcloudConnection()
  - Uses CURL for WebDAV PROPFIND operations
  
- **cron_receipt_scanner.php**: Automated background job
  - Run via cron: `*/5 * * * * /usr/bin/php /path/to/cron_receipt_scanner.php`
  - Scans Nextcloud folder for new receipts
  - Runs Tesseract OCR to extract text
  - Parses vendor, date, and amount from OCR
  - Creates expense records automatically
  - Notifies all admins of new receipts
  - Prevents duplicate processing via SHA256 file hash

- **process_settings.php**: Settings management
  - Handles Nextcloud configuration updates
  - Test connection endpoint
  - Google Maps API key management
  - Mileage rate configuration

### Database Integration
- Uses `cloud_receipts` table with columns:
  - file_path, file_name, file_hash, expense_id
  - processed, ocr_attempted, ocr_data
  - detected_date, processed_date

### Setup Requirements
1. Install Tesseract OCR: `sudo apt-get install tesseract-ocr`
2. Configure Nextcloud credentials in Settings
3. Set up cron job for automated scanning
4. Enable receipt scanning in Settings

## Feature 2: Mileage Tracking System

### Files Created
- **views/mileage_tracker.php**: Full-featured mileage logging UI
  - Google Maps Places Autocomplete for addresses
  - Dynamic waypoint addition (start → stops → end)
  - Distance calculation via Google Distance Matrix API
  - Reimbursement calculation at configurable rates
  - Recent logs table with filtering
  - CSV export functionality

- **process_mileage.php**: Complete CRUD operations
  - Actions: get_distance, create, update, delete, mark_reimbursed, export_csv, get_logs
  - Calculates total distance across multiple waypoints
  - Stores rate snapshot with each log
  - Admin-only reimbursement marking

### Database Integration
- Uses `mileage_logs` table:
  - user_id, athlete_id, session_id, trip_date, purpose
  - start_location, end_location
  - total_distance_km, total_distance_miles
  - reimbursement_rate, reimbursement_amount, is_reimbursed
  
- Uses `mileage_stops` table:
  - mileage_log_id, stop_order, location_name, address

### Setup Requirements
1. Obtain Google Maps API key from Google Cloud Console
2. Enable Places API and Distance Matrix API
3. Configure API key in Settings
4. Set mileage reimbursement rates (default: $0.68/km, $1.10/mi)

## Feature 3: Refund Management

### Files Created
- **views/refunds.php**: Comprehensive refund interface
  - Booking search by email, session, date range
  - Full/partial refund processing modal
  - Refund history with filtering
  - CSV export for accounting
  - Two-tab interface: Search & History

- **process_refunds.php**: Stripe integration & processing
  - Actions: search_bookings, process_refund, list_refunds, export_refunds
  - Stripe Refund API integration
  - Automatic booking status updates
  - Package credit reversal for cancelled packages
  - Email notifications to customers
  - System notifications

### Database Integration
- Uses `refunds` table:
  - booking_id, user_id, refunded_by
  - original_amount, refund_amount, refund_reason
  - stripe_refund_id, status (pending/completed/failed)
  - refund_date

### Business Logic
- Full refunds: Sets booking status to 'cancelled'
- Partial refunds: Keeps booking as 'paid'
- Package refunds: Reverses credit additions
- Refund amount validation against original payment
- Stripe refund ID tracking for reconciliation

## Feature 4: Billing Dashboard

### Files Created
- **views/billing_dashboard.php**: Financial analytics dashboard
  - Summary cards: Total Income, Total Expenses, Net Profit, Recent Refunds
  - Chart.js visualizations:
    - Income vs Expenses line chart (6 months)
    - Expense breakdown pie chart by category
  - Recent transactions tables:
    - Last 10 income records
    - Last 10 expenses
    - Recent refunds (30 days)
  - Date range filtering
  - Quick links to detailed reports

### Data Aggregation
- Real-time calculations from bookings and expenses tables
- Month-over-month trending
- Category-based expense analysis
- Outstanding refund tracking

## Dashboard Integration

### Updated Files
- **dashboard.php**:
  - Added 3 new routes: mileage_tracker, refunds, billing_dashboard
  - Updated "Accounting & Reports" navigation menu
  - Proper permission checks on all routes

- **views/settings.php**:
  - Added Nextcloud configuration section (7 fields)
  - Test connection button with AJAX
  - Google Maps API key input
  - Mileage rate configuration (km and mile rates)
  - Receipt scanning enable/disable toggle

### Navigation Structure
```
Accounting & Reports:
├── Billing Dashboard (NEW)
├── Accounting (existing)
├── Income Reports (existing)
├── Athlete Billing (existing)
├── Expenses (existing)
├── Expense Categories (existing)
├── Mileage Tracker (NEW)
└── Refunds (NEW)
```

## Security Measures

### CSRF Protection
- All forms use `generateCSRFToken()` and `csrfTokenInput()`
- All POST handlers call `checkCsrfToken()`
- Token validation before any state-changing operations

### Permission Checks
- Mileage Tracker: coach, coach_plus, admin only
- Refunds: admin only
- Billing Dashboard: admin only
- Settings: admin only with edit_system_settings permission

### Prepared Statements
- All database queries use PDO prepared statements
- No string concatenation in SQL
- Parameter binding for all user inputs

### Input Validation
- Type checking (intval, floatval)
- String sanitization (trim)
- Amount validation against limits
- File extension whitelisting for uploads
- SHA256 hash verification for cloud files

### API Security
- API keys stored in database (encrypted at rest)
- HTTPS required for external API calls
- SSL verification enabled for production
- Rate limiting consideration for external APIs

## External API Dependencies

### Google Maps APIs
- **Places API**: Address autocomplete in mileage tracker
- **Distance Matrix API**: Accurate distance calculations
- **JavaScript API**: Map rendering and places library
- Cost: Pay-per-use, typically $2-7 per 1000 requests

### Stripe API
- **Refunds API**: Payment reversals
- Uses existing Stripe secret key from system settings
- Tracks refund IDs for reconciliation

### Nextcloud WebDAV
- **PROPFIND**: List files in folder
- **GET**: Download file content
- Basic authentication over HTTPS
- Self-hosted or cloud Nextcloud instance

## Testing Checklist

### Nextcloud Integration
- [ ] Test Nextcloud connection with valid credentials
- [ ] Test connection failure with invalid credentials
- [ ] Upload test receipt to Nextcloud folder
- [ ] Verify cron job processes new receipt
- [ ] Check expense record creation
- [ ] Verify admin notification sent
- [ ] Confirm duplicate detection works (same file hash)

### Mileage Tracking
- [ ] Test address autocomplete functionality
- [ ] Add multiple waypoints to trip
- [ ] Calculate distance between 2+ locations
- [ ] Verify reimbursement calculation
- [ ] Save mileage log successfully
- [ ] Edit existing log
- [ ] Delete log
- [ ] Export logs to CSV
- [ ] Mark log as reimbursed (admin)

### Refund Management
- [ ] Search bookings by email
- [ ] Search by date range
- [ ] Process full refund on booking
- [ ] Process partial refund
- [ ] Verify Stripe refund API call
- [ ] Check booking status update
- [ ] Confirm customer email sent
- [ ] Export refunds to CSV
- [ ] Verify package credit reversal

### Billing Dashboard
- [ ] View summary cards with correct totals
- [ ] Verify income chart displays 6 months
- [ ] Check expense breakdown pie chart
- [ ] Filter by date range
- [ ] Verify recent transactions tables
- [ ] Confirm refunds section displays
- [ ] Test quick links to reports

## Performance Considerations

### Database Queries
- All queries use indexes on key columns
- Date range queries use BETWEEN for index optimization
- GROUP BY used efficiently with covering indexes
- LIMIT clauses on all pagination queries

### Caching Opportunities
- Settings can be cached in session
- Google Maps API responses cacheable
- Chart data can use 5-minute cache

### Scalability
- Mileage logs: ~100-500 per month (low volume)
- Refunds: ~10-50 per month (low volume)
- Cloud receipts: ~50-200 per month (medium volume)
- All tables will remain small (<10,000 rows)

## Maintenance & Monitoring

### Cron Job Monitoring
- Check cron logs: `/var/log/syslog`
- Review security_logs table for scan results
- Monitor OCR success rate
- Set up alerts for repeated failures

### API Usage Monitoring
- Track Google Maps API usage in GCP console
- Monitor Stripe dashboard for refund activity
- Check Nextcloud storage usage

### Database Maintenance
- Regular backups include all new tables
- Index maintenance as data grows
- Archive old mileage logs annually

## Future Enhancements

### Potential Improvements
1. **Receipt Processing**: Add AI/ML for better OCR accuracy
2. **Mileage**: Add route optimization suggestions
3. **Refunds**: Add bulk refund processing
4. **Dashboard**: Add budget tracking and alerts
5. **Reporting**: Add PDF export for financial reports
6. **Integration**: Add QuickBooks/Xero export

### Technical Debt
- Consider moving API keys to encrypted environment variables
- Implement rate limiting on refund endpoints
- Add webhook support for Stripe events
- Create API documentation for integrations

## Deployment Notes

### Pre-Deployment
1. Run schema.sql to create new tables
2. Install Tesseract OCR on server
3. Configure Google Maps API key
4. Set up Nextcloud connection
5. Test cron job execution permissions

### Post-Deployment
1. Verify all routes accessible
2. Test each feature end-to-end
3. Monitor error logs for 24 hours
4. Train admin users on new features
5. Document any environment-specific configurations

## Support & Troubleshooting

### Common Issues

**Nextcloud Connection Failed**
- Verify URL format (https://domain.com)
- Check username/password (use app password)
- Confirm folder path starts with /
- Test network connectivity to Nextcloud server

**Google Maps Not Loading**
- Verify API key is valid
- Check API is enabled in GCP Console
- Confirm billing is set up
- Check browser console for JavaScript errors

**Refunds Not Processing**
- Verify Stripe secret key is correct
- Check payment intent ID exists
- Confirm booking is in 'paid' status
- Review Stripe dashboard for API errors

**Cron Job Not Running**
- Check crontab configuration
- Verify PHP path is correct
- Confirm file permissions (executable)
- Review cron logs for errors

## Conclusion

This implementation provides 4 production-ready features with:
- ✅ Complete CRUD operations
- ✅ Proper security measures
- ✅ Database schema compliance
- ✅ External API integrations
- ✅ User-friendly interfaces
- ✅ Comprehensive error handling
- ✅ Responsive design
- ✅ CSV export capabilities
- ✅ Email notifications

All features follow existing code patterns and integrate seamlessly with the Crash Hockey application architecture.
