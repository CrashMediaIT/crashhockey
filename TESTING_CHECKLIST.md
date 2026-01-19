# Crash Hockey - Testing Checklist

## Pre-Testing Setup
- [ ] Database schema is up to date (run schema.sql)
- [ ] Stripe keys are configured in system settings
- [ ] Tax rate and tax name are set in system settings
- [ ] At least one expense category exists
- [ ] `uploads/receipts/` directory exists and is writable (755)
- [ ] At least one test admin, coach, and athlete account exists

---

## 1. Package System Testing

### Admin Package Management
- [ ] Login as admin
- [ ] Navigate to Admin → Packages
- [ ] Create a credit package:
  - [ ] Name: "10 Session Pack"
  - [ ] Type: Credit Package
  - [ ] Price: $500
  - [ ] Credits: 10
  - [ ] Valid for: 365 days
  - [ ] Set Active
- [ ] Create a bundled package:
  - [ ] Create 2-3 upcoming sessions first
  - [ ] Name: "Summer Series Bundle"
  - [ ] Type: Bundled Package
  - [ ] Price: $300
  - [ ] Select the sessions created above
  - [ ] Set Active
- [ ] Edit a package and change price
- [ ] Toggle package active/inactive
- [ ] Try to delete package (should warn if purchased)

### Customer Package Purchase
- [ ] Logout and login as athlete
- [ ] Navigate to Account & History → Session Packages
- [ ] Verify both packages are visible
- [ ] Click filter buttons (All, Credits, Bundled)
- [ ] Click "Purchase Package" on credit package
- [ ] Use Stripe test card: 4242 4242 4242 4242
- [ ] Complete purchase
- [ ] Verify success message
- [ ] Check that credits appear in "Your Active Credits" section
- [ ] Verify email receipt was sent

### Credit Redemption
- [ ] With athlete account that has credits
- [ ] Navigate to Book Sessions
- [ ] Select a session and click Book
- [ ] If athlete has credits, should use credit automatically (no payment)
- [ ] Verify booking success
- [ ] Check packages page - credits should be reduced by 1
- [ ] Try booking another session - should use next credit

### Parent Multi-Athlete Purchase
- [ ] Login as parent account
- [ ] Ensure parent manages at least 2 athletes
- [ ] Navigate to Session Packages
- [ ] Select a package
- [ ] Check both athlete checkboxes
- [ ] Purchase package
- [ ] Verify both athletes receive credits

---

## 2. Enhanced Session Types Testing

### Session Creation
- [ ] Login as admin or coach
- [ ] Navigate to Coach Management → Create Session
- [ ] Create session with:
  - [ ] Session Type Category: Private
  - [ ] Max Athletes: 4
  - [ ] Fill other required fields
  - [ ] Save session
- [ ] Verify session appears in schedule

### Booking Limits
- [ ] Login as 5 different athlete accounts
- [ ] Have all 5 attempt to book the session with max_athletes=4
- [ ] First 4 should succeed
- [ ] 5th attempt should fail with "not enough spots" message

---

## 3. Accounting System Testing

### Initial Setup
- [ ] Login as admin
- [ ] Navigate to Accounting & Reports → Expense Categories
- [ ] Create categories:
  - [ ] Ice Time
  - [ ] Equipment
  - [ ] Travel
  - [ ] Marketing
  - [ ] Utilities

### Dashboard
- [ ] Navigate to Accounting & Reports → Dashboard
- [ ] Verify summary cards show correct totals:
  - [ ] Total Income (should match sum of all paid bookings)
  - [ ] Total Expenses (should be $0 initially)
  - [ ] Net Profit (should equal income)
- [ ] Check monthly chart displays
- [ ] Verify recent transactions show recent bookings
- [ ] Change year selector - data should update

### Income Reports
- [ ] Navigate to Income Reports
- [ ] Test period filters:
  - [ ] Today
  - [ ] This Week
  - [ ] This Month
  - [ ] This Year
  - [ ] Custom Range (select specific dates)
- [ ] Verify transaction table shows:
  - [ ] Correct dates
  - [ ] Customer names
  - [ ] Session/package names
  - [ ] Subtotal, tax, total columns
  - [ ] Payment IDs
- [ ] Verify summary totals match table
- [ ] Click "Export CSV" - file should download
- [ ] Click "Export PDF" - print dialog should open

### Athlete Billing
- [ ] Navigate to Athlete Billing
- [ ] Verify all athletes with bookings are listed
- [ ] Click on an athlete
- [ ] Verify right panel shows:
  - [ ] Athlete name and email
  - [ ] Billing summary (bookings, total, tax)
  - [ ] Itemized list of all bookings
  - [ ] Date, description, amounts for each
- [ ] Click "Export Report" - should trigger print
- [ ] Change year - list should update

### Expense Management
- [ ] Navigate to Accounts Payable
- [ ] Click "Add Expense"
- [ ] Fill in expense form:
  - [ ] Vendor: "City Arena"
  - [ ] Date: Today
  - [ ] Category: Ice Time
  - [ ] Amount: $200
  - [ ] Tax: $26 (13%)
  - [ ] Total should auto-calculate to $226
  - [ ] Payment Method: Credit Card
  - [ ] Description: "Friday evening ice rental"
  - [ ] Upload a receipt image (JPG/PNG)
- [ ] Save expense
- [ ] Verify expense appears in table
- [ ] Verify receipt icon is clickable
- [ ] Click receipt icon - should open in new tab
- [ ] Edit the expense - change amount
- [ ] Save and verify update
- [ ] Try deleting expense
- [ ] Confirm deletion

### Expense Categories
- [ ] Navigate to Expense Categories
- [ ] Create new category "Staff Salaries"
- [ ] Set display order to 1
- [ ] Edit category - change name
- [ ] Create expense using this category
- [ ] Try to delete category - should fail (in use)
- [ ] Delete expense first
- [ ] Delete category - should succeed

### Cross-Verification
- [ ] Go back to Dashboard
- [ ] Verify:
  - [ ] Total Expenses now includes added expenses
  - [ ] Net Profit = Income - Expenses
  - [ ] Recent expenses shows new entries
- [ ] Check that all monetary values have proper tax breakdown

---

## 4. Security Testing

### Access Control
- [ ] Logout
- [ ] Try accessing: `/dashboard.php?page=accounting`
  - [ ] Should redirect to login
- [ ] Login as athlete (non-admin)
- [ ] Try accessing: `/dashboard.php?page=accounting`
  - [ ] Should redirect or show error
- [ ] Try accessing: `/dashboard.php?page=admin_packages`
  - [ ] Should redirect or show error
- [ ] Verify "Accounting & Reports" menu doesn't show for non-admins

### CSRF Protection
- [ ] Open browser dev tools → Network tab
- [ ] Submit any form (package, expense, etc.)
- [ ] Copy the form data
- [ ] Try resubmitting with:
  - [ ] No csrf_token
  - [ ] Invalid csrf_token
  - [ ] Old csrf_token
- [ ] All should fail with 403 error

### SQL Injection
- [ ] In any text field, try entering:
  - [ ] `' OR '1'='1`
  - [ ] `'; DROP TABLE users; --`
  - [ ] `<script>alert('xss')</script>`
- [ ] All should be safely escaped/rejected

### File Upload
- [ ] Try uploading:
  - [ ] .jpg - should work
  - [ ] .png - should work
  - [ ] .pdf - should work
  - [ ] .exe - should fail
  - [ ] .php - should fail
  - [ ] File with ../ in name - should be sanitized

---

## 5. UI/UX Testing

### Responsive Design
- [ ] Test on desktop (1920x1080)
- [ ] Test on tablet (768px width)
- [ ] Test on mobile (375px width)
- [ ] Verify:
  - [ ] Navigation menu works on mobile (hamburger)
  - [ ] Tables scroll horizontally if needed
  - [ ] Cards stack properly
  - [ ] Forms remain usable
  - [ ] Modals are responsive

### Navigation
- [ ] Test all menu links work
- [ ] Verify active page is highlighted
- [ ] Test breadcrumbs/back navigation
- [ ] Verify logo link returns to home

### Forms
- [ ] All required fields are marked with *
- [ ] Validation messages appear for invalid input
- [ ] Success messages show after save
- [ ] Error messages show on failure
- [ ] Modals open and close properly
- [ ] Form resets work correctly

### Data Display
- [ ] Currency formatted consistently ($X.XX)
- [ ] Dates formatted consistently (MMM DD, YYYY)
- [ ] Times formatted (HH:MM AM/PM)
- [ ] Percentages show correctly
- [ ] Large numbers have comma separators

---

## 6. Integration Testing

### End-to-End Flow: Customer Journey
1. [ ] Customer creates account
2. [ ] Views available packages
3. [ ] Purchases credit package
4. [ ] Receives email confirmation
5. [ ] Views credit balance
6. [ ] Books session using credit
7. [ ] Views booking in session history
8. [ ] Credit balance decreases
9. [ ] Attends session
10. [ ] Receives post-session email

### End-to-End Flow: Admin Journey
1. [ ] Admin creates expense categories
2. [ ] Creates new sessions
3. [ ] Creates bundled package with sessions
4. [ ] Customer purchases package
5. [ ] Admin views income report
6. [ ] Adds expense with receipt
7. [ ] Views accounting dashboard
8. [ ] Profit calculation includes both
9. [ ] Exports athlete billing report
10. [ ] Reconciles with bank statements

---

## 7. Performance Testing

- [ ] Load dashboard with 1000+ bookings
  - [ ] Should load within 2-3 seconds
- [ ] Load athlete billing with 100+ athletes
  - [ ] List should load quickly
  - [ ] Pagination/filtering should work
- [ ] Export large CSV (500+ rows)
  - [ ] Should complete without timeout
- [ ] Upload 10MB receipt image
  - [ ] Should handle or reject gracefully

---

## 8. Error Handling

### Network Errors
- [ ] Disconnect internet during Stripe checkout
  - [ ] Should show appropriate error
  - [ ] Should not create partial booking
- [ ] Timeout during form submission
  - [ ] Should not duplicate records
  - [ ] Should show retry option

### Data Validation
- [ ] Try saving package with:
  - [ ] Negative price - should fail
  - [ ] Zero credits - should fail
  - [ ] Invalid date - should fail
- [ ] Try saving expense with:
  - [ ] Future date - should work or warn
  - [ ] Negative amount - should fail
  - [ ] Missing required fields - should fail

### Database Errors
- [ ] Simulate database disconnect
  - [ ] Should show friendly error message
  - [ ] Should not expose SQL errors to user
  - [ ] Should log errors server-side

---

## Bug Report Template

If you find issues, report using this format:

```
**Bug Title:** [Short description]

**Severity:** [Critical/High/Medium/Low]

**Steps to Reproduce:**
1. 
2. 
3. 

**Expected Result:**
[What should happen]

**Actual Result:**
[What actually happened]

**Environment:**
- Browser: [Chrome/Firefox/Safari/Edge + version]
- Device: [Desktop/Tablet/Mobile]
- Screen Size: [1920x1080, etc.]
- User Role: [Admin/Coach/Athlete/Parent]

**Screenshots:**
[Attach if applicable]

**Console Errors:**
[Copy from browser console if any]
```

---

## Sign-Off

### Developer
- [ ] All files created and tested locally
- [ ] Code reviewed for security issues
- [ ] Documentation complete
- [ ] No console errors or warnings

**Signed:** _________________  **Date:** __________

### QA Tester
- [ ] All test cases executed
- [ ] Critical bugs resolved
- [ ] Known issues documented
- [ ] Ready for production

**Signed:** _________________  **Date:** __________

### Product Owner
- [ ] Features meet requirements
- [ ] User experience acceptable
- [ ] Business logic correct
- [ ] Approved for deployment

**Signed:** _________________  **Date:** __________

