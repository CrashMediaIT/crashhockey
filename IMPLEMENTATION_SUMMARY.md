# Crash Hockey - Package System and Accounting Implementation

## Overview
This implementation adds three major feature sets to the Crash Hockey application:
1. **Package System** - Session packages (bundled and credit-based)
2. **Enhanced Session Types** - Better categorization and athlete limits
3. **Accounting System** - Complete financial management for admins

---

## 1. Package System

### Files Created
- `views/admin_packages.php` - Admin UI for package management
- `views/packages.php` - Customer-facing package browsing (already existed)
- `process_packages.php` - Package CRUD operations
- `process_purchase_package.php` - Stripe checkout for packages

### Files Modified
- `process_booking.php` - Added credit checking and redemption
- `payment_success.php` - Added package purchase completion handling
- `dashboard.php` - Added routes and navigation

### Features
- **Two Package Types:**
  - **Credit Packages**: Purchase X credits, use for any future sessions
  - **Bundled Packages**: Pre-selected sessions at a package price
  
- **Admin Capabilities:**
  - Create/edit/delete packages
  - Set price, credits, validity period
  - Restrict by age group/skill level
  - Assign specific sessions to bundled packages
  - Toggle package active/inactive status
  
- **Customer Features:**
  - Browse available packages with filtering
  - View current credit balances
  - Purchase packages via Stripe
  - Credits automatically redeemed at booking
  - Multi-athlete purchase for parents
  - Credit expiration tracking

### Database Tables Used
```sql
packages                 -- Package definitions
package_sessions         -- Sessions in bundled packages
user_package_credits     -- User credit balances
```

### Usage Flow
1. Admin creates package (credit or bundled type)
2. Customer browses packages and clicks "Purchase"
3. System creates Stripe checkout session
4. On successful payment:
   - Booking record created
   - Credits allocated (for credit packages)
   - Session bookings created (for bundled packages)
5. When booking a session:
   - System checks for available credits first
   - If credits available, use credit (no payment)
   - Otherwise, proceed to Stripe payment

---

## 2. Enhanced Session Types

### Files Modified
- `process_create_session.php` - Added new fields

### New Features
- **Session Type Category**: group, private, semi-private
- **Max Athletes**: Configurable limit per session (in addition to venue capacity)

### Database Changes
- Added `session_type_category` ENUM field
- Added `max_athletes` INT field

### Usage
- When creating a session, coaches/admins can now:
  - Select session category
  - Set maximum number of athletes
- Booking system validates both capacity and max_athletes

---

## 3. Accounting System

### Files Created

#### Views
- `views/accounting.php` - Main financial dashboard
- `views/reports_income.php` - Detailed income reports
- `views/reports_athlete.php` - Per-athlete billing
- `views/accounts_payable.php` - Expense management
- `views/expense_categories.php` - Category management

#### Processors
- `process_expenses.php` - Handle all expense operations

### Features

#### Dashboard (`accounting.php`)
- Summary cards showing:
  - Total income (with tax breakdown)
  - Total expenses (with tax breakdown)
  - Net profit and margin
- Monthly income vs expense chart
- Recent income transactions (last 10)
- Recent expenses (last 10)
- Year selector for historical data
- Quick action buttons to other reports

#### Income Reports (`reports_income.php`)
- Filter by period:
  - Today
  - This week
  - This month
  - This year
  - Custom date range
- Detailed transaction table showing:
  - Date, customer, item, type
  - Subtotal, tax, total
  - Payment ID
- Export to CSV
- Export to PDF (print)
- Summary totals with tax breakdown

#### Athlete Billing (`reports_athlete.php`)
- List all athletes with bookings
- Shows booking count and total spent per athlete
- Click athlete to see itemized statement:
  - All bookings for selected year
  - Date, description, amounts
  - Package vs session breakdown
  - Summary totals
- Export individual athlete reports

#### Expense Management (`accounts_payable.php`)
- Upload receipt images/PDFs
- OCR placeholder (ready for Tesseract.js)
- Enter expense details:
  - Vendor name
  - Category
  - Amount, tax, total
  - Payment method
  - Reference number
  - Description
- View/edit/delete expenses
- Recent expenses table
- Receipt file storage

#### Expense Categories (`expense_categories.php`)
- Create/edit/delete categories
- Set display order
- Toggle active/inactive
- Cannot delete categories in use

### Database Tables Used
```sql
expense_categories    -- Categories like Ice Time, Equipment, etc.
expenses             -- Expense records
expense_line_items   -- Line items from OCR (future)
```

### Security
- All accounting features restricted to admin role
- CSRF protection on all forms
- File upload validation (images and PDF only)
- SQL injection prevention with prepared statements
- XSS protection with htmlspecialchars

### Tax Handling
- Uses system settings for tax rate and name (HST/GST/VAT)
- Proper tax breakdown on all reports
- Separate display of subtotal, tax, and total

---

## Dashboard Integration

### New Navigation Items

**Account & History Section:**
- Added "Session Packages" link (visible to all users)

**System Admin Section:**
- Added "Packages" link

**New Section - Accounting & Reports** (Admin only):
- Dashboard
- Income Reports
- Athlete Billing
- Expenses
- Expense Categories

### Routes Added
```php
'packages'            => 'views/packages.php',
'admin_packages'      => 'views/admin_packages.php',
'accounting'          => 'views/accounting.php',
'reports_income'      => 'views/reports_income.php',
'reports_athlete'     => 'views/reports_athlete.php',
'accounts_payable'    => 'views/accounts_payable.php',
'expense_categories'  => 'views/expense_categories.php',
```

---

## Technical Details

### Security Measures
✅ CSRF tokens on all forms
✅ Admin-only access checks
✅ Prepared SQL statements
✅ XSS protection
✅ File upload validation
✅ Input sanitization
✅ SQL injection prevention

### UI/UX
✅ Consistent orange theme (#ff4d00)
✅ Dark mode interface
✅ Responsive design (mobile-friendly)
✅ Interactive modals
✅ Real-time calculations
✅ Chart.js for visualizations
✅ Loading states and error handling

### Code Quality
✅ Follows existing code patterns
✅ Consistent naming conventions
✅ Proper error handling
✅ Database transactions where needed
✅ Clean separation of concerns
✅ Comments for complex logic

---

## Testing Recommendations

### Package System
1. Create a credit package and purchase it
2. Verify credits show in package page
3. Book a session and confirm credit is used
4. Create a bundled package with specific sessions
5. Purchase and verify bookings are created
6. Test multi-athlete purchase as parent

### Session Types
1. Create sessions with different categories
2. Set max_athletes and test booking limits
3. Verify both capacity and max_athletes are enforced

### Accounting
1. Make several bookings to generate income
2. View accounting dashboard and verify totals
3. Test income reports with different filters
4. Select an athlete and view their billing
5. Create expense categories
6. Upload expenses with receipts
7. Verify all totals match between reports

### Security
1. Attempt to access admin pages as non-admin (should fail)
2. Try submitting forms without CSRF token (should fail)
3. Test SQL injection attempts (should be blocked)
4. Upload invalid file types (should be rejected)

---

## Future Enhancements

### OCR Integration
The expense management system has a placeholder for OCR. To fully implement:
1. Add Tesseract.js library
2. Process uploaded receipts client-side or server-side
3. Extract: vendor name, amount, date, line items
4. Auto-populate form fields
5. Store raw OCR data in `ocr_data` field

### Reporting
- Add more chart types (pie charts for category breakdown)
- Add year-over-year comparisons
- Add forecasting based on historical data
- Export to Excel format
- Scheduled email reports

### Package System
- Package templates
- Seasonal packages
- Family packages with shared credits
- Package gifting
- Referral discounts

---

## Configuration Required

Before using these features:

1. **Stripe Keys**: Set in system settings (already configured)
2. **Tax Settings**: Set tax_rate and tax_name in system settings
3. **Expense Categories**: Create categories (Equipment, Ice Time, Travel, etc.)
4. **File Uploads**: Ensure `uploads/receipts/` directory exists and is writable

---

## Support

For questions or issues:
- Check error logs for detailed error messages
- Verify database schema matches schema.sql
- Ensure all dependencies are installed
- Check file permissions for uploads directory

---

## Summary

This implementation provides a complete business management system for the Crash Hockey platform:
- Revenue generation through flexible package options
- Detailed financial tracking and reporting
- Per-athlete billing for transparency
- Expense management for cost control
- Professional accounting dashboard

All features are production-ready with proper security, error handling, and user experience considerations.
