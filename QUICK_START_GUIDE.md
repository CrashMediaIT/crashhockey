# Crash Hockey - Quick Start Guide
## Package System & Accounting Features

---

## Table of Contents
1. [Package System Setup](#1-package-system-setup)
2. [Using Packages as a Customer](#2-using-packages-as-a-customer)
3. [Accounting Setup](#3-accounting-setup)
4. [Daily Accounting Tasks](#4-daily-accounting-tasks)
5. [Monthly Reporting](#5-monthly-reporting)
6. [Troubleshooting](#6-troubleshooting)

---

## 1. Package System Setup

### Step 1: Create Your First Credit Package
1. Login as **Admin**
2. Click **System Admin → Packages**
3. Click **"+ Create Package"**
4. Fill in the form:
   - **Name:** "10 Session Credit Pack"
   - **Type:** Credit Package
   - **Price:** 450.00
   - **Credits:** 10
   - **Valid for:** 365 days
   - **Age Group:** (optional) Select if age-specific
   - **Skill Level:** (optional) Select if skill-specific
   - **Active:** ✓ Checked
5. Click **"Save Package"**

**Result:** Customers now see this package and can purchase it. They'll get 10 credits valid for 1 year.

---

### Step 2: Create Your First Bundled Package
1. First, create some upcoming sessions:
   - Navigate to **Coach Management → Create Session**
   - Create 3-5 sessions for next month
   
2. Go back to **System Admin → Packages**
3. Click **"+ Create Package"**
4. Fill in the form:
   - **Name:** "February Skills Series"
   - **Type:** Bundled Package
   - **Price:** 275.00
   - **Valid for:** 365 days
   - **Active:** ✓ Checked
5. Click **"Save Package"**

6. Find your new package in the table
7. Click the **list icon** (📋) next to it
8. Check the boxes for the 3-5 sessions you created
9. Click **"Update Sessions"**

**Result:** Customers can now purchase this bundle and will be automatically enrolled in all selected sessions.

---

## 2. Using Packages as a Customer

### Purchasing a Package

1. Login as **Athlete** or **Parent**
2. Click **Account & History → Session Packages**
3. Browse available packages
4. Use filter buttons to show only Credits or Bundled
5. Click **"Purchase Package"**
6. If you're a parent, select which athletes to purchase for
7. Click **"Purchase Package"** again
8. You'll be redirected to Stripe checkout
9. Enter test card: `4242 4242 4242 4242`, any future date, any CVV
10. Complete payment

**Result:** You'll return to the dashboard with a success message. Credits will appear in your account.

---

### Using Credits

1. After purchasing credits, go to **Book Sessions**
2. Find a session and click **"Book"**
3. If you have available credits:
   - No payment screen will appear
   - Credit is automatically used
   - You'll see "Booked with Package Credit" message
4. If you don't have credits:
   - Regular Stripe checkout appears
   - Pay for individual session

**Result:** Your session is booked and your credit balance decreases by 1.

---

### Checking Your Credits

1. Go to **Account & History → Session Packages**
2. Top section shows **"Your Active Credits"**
3. For each package you've purchased:
   - See credits remaining
   - See expiration date
4. Expired credits won't be used automatically

---

## 3. Accounting Setup

### Step 1: Create Expense Categories

Before tracking expenses, set up categories:

1. Login as **Admin**
2. Navigate to **Accounting & Reports → Expense Categories**
3. Create these essential categories:
   - **Ice Time** (display order: 1)
   - **Equipment** (display order: 2)
   - **Staff Wages** (display order: 3)
   - **Marketing** (display order: 4)
   - **Travel** (display order: 5)
   - **Utilities** (display order: 6)
   - **Insurance** (display order: 7)
   - **Other** (display order: 99)

**Tip:** Lower display order = shows first in dropdowns

---

### Step 2: Configure Tax Settings

Ensure your tax settings are correct:

1. Go to **System Admin → Global Settings**
2. Find **Tax Settings** section
3. Set:
   - **Tax Rate:** 13.00 (for 13% HST)
   - **Tax Name:** HST (or GST/VAT as appropriate)
4. Save changes

**Result:** All income and expense reports will correctly calculate tax.

---

## 4. Daily Accounting Tasks

### Recording an Expense

1. Navigate to **Accounting & Reports → Expenses**
2. Click **"Add Expense"**
3. Fill in the form:
   - **Vendor Name:** "City Arena"
   - **Expense Date:** Select date (usually today)
   - **Category:** Select "Ice Time"
   - **Amount:** 200.00 (before tax)
   - **Tax Amount:** 26.00 (if applicable)
   - **Total Amount:** 226.00 (auto-calculated)
   - **Payment Method:** Credit Card
   - **Reference Number:** Invoice #12345 (optional)
   - **Description:** "Friday evening ice rental"
   - **Upload Receipt:** Choose file (JPG, PNG, or PDF)
4. Click **"Save Expense"**

**Result:** Expense is recorded and will appear in reports.

---

### Viewing Daily Income

1. Go to **Accounting & Reports → Income Reports**
2. Select filter: **"Today"**
3. View all bookings for today:
   - Customer names
   - What they purchased
   - Amounts (subtotal, tax, total)
   - Payment IDs

**Tip:** Keep this page open to monitor daily revenue in real-time.

---

## 5. Monthly Reporting

### Month-End Income Report

1. Navigate to **Accounting & Reports → Income Reports**
2. Set filter to **"This Month"**
3. Review the summary:
   - Total bookings this month
   - Gross revenue (subtotal)
   - Tax collected
   - Total revenue
4. Click **"Export CSV"** to download
5. Open in Excel/Google Sheets for further analysis

---

### Month-End Expense Report

1. Navigate to **Accounting & Reports → Expenses**
2. Scroll through expenses for the month
3. Verify all expenses are recorded
4. Check all receipts are uploaded
5. Total expenses shown at top

---

### Profit & Loss Statement

1. Go to **Accounting & Reports → Dashboard**
2. Select current year
3. Review the 3 summary cards:
   - **Total Income:** All revenue for the year
   - **Total Expenses:** All costs for the year
   - **Net Profit:** Income minus Expenses
4. Check the **Monthly Overview** chart:
   - Blue line = Income
   - Red line = Expenses
   - Gap between = Profit or Loss
5. Review profit margin percentage

**Action:** If profit margin is low, consider:
- Increasing session prices
- Creating package deals
- Reducing controllable expenses
- Offering premium private sessions

---

### Per-Athlete Billing

Use this for transparency or collections:

1. Go to **Accounting & Reports → Athlete Billing**
2. Select current year
3. See all athletes with their total spending
4. Click on any athlete to see itemized list:
   - Every session booked
   - Every package purchased
   - Dates and amounts
5. Click **"Export Report"** to print/PDF
6. Send to athlete or parent if requested

---

## 6. Troubleshooting

### Problem: Package Credits Not Appearing After Purchase

**Check:**
1. Was payment successful? Check payment_history
2. Is package active? Check admin_packages
3. Are credits expired? Check expiry_date
4. Clear browser cache and refresh

**Fix:** Admin can manually add credits in database if needed

---

### Problem: Credits Not Being Used When Booking

**Check:**
1. Does athlete have credits? Check packages page
2. Are credits expired?
3. Is the session eligible for credit use?

**Fix:** Customer should see "Booked with Package Credit" if credits were used. If charged, check for available credits at time of booking.

---

### Problem: Income Totals Don't Match Bank Deposits

**Check:**
1. Stripe fees are deducted by Stripe (not shown in reports)
2. Refunds/cancellations may not be processed yet
3. Check payment_status in bookings table
4. Some bookings may be pending

**Fix:** 
- Export income report for date range
- Compare with Stripe dashboard
- Account for Stripe's 2.9% + $0.30 per transaction fee

---

### Problem: Can't Delete Expense Category

**Reason:** Category is being used by existing expenses

**Fix:**
1. Go to Expenses
2. Find all expenses using that category
3. Either delete those expenses OR
4. Change them to a different category
5. Then delete the category

---

### Problem: Receipt Upload Fails

**Check:**
1. File size (max 10MB recommended)
2. File type (only JPG, PNG, PDF allowed)
3. File permissions on uploads/receipts/ folder
4. Disk space on server

**Fix:**
- Compress large images before uploading
- Convert other formats to JPG/PNG/PDF
- Contact hosting provider if disk full

---

### Problem: Dashboard Shows $0 for Everything

**Check:**
1. Are there any paid bookings? Check session_history
2. Is correct year selected?
3. Database connection working?

**Fix:**
- Make a test booking to verify system is recording income
- Verify year selector is set correctly
- Check database credentials in db_config.php

---

## Best Practices

### Daily
- ✅ Record expenses same day they occur
- ✅ Upload all receipts immediately
- ✅ Monitor daily income report

### Weekly
- ✅ Review new bookings
- ✅ Check credit package sales
- ✅ Verify all expenses categorized correctly

### Monthly
- ✅ Export income report for records
- ✅ Reconcile with bank statements
- ✅ Review profit margins
- ✅ Plan next month's sessions

### Quarterly
- ✅ Review package pricing and popularity
- ✅ Analyze expense trends
- ✅ Create financial projections
- ✅ Adjust pricing if needed

### Yearly
- ✅ Export full year income report for taxes
- ✅ Export full year expense report for taxes
- ✅ Review year-over-year growth
- ✅ Set goals for next year

---

## Tips for Success

1. **Create Package Incentives**
   - Price packages at 10-15% discount vs individual sessions
   - Offer early-bird package deals
   - Create seasonal packages

2. **Expense Management**
   - Photograph receipts immediately
   - Store physical receipts in labeled folders
   - Review expenses monthly to find savings
   - Negotiate bulk rates with arenas

3. **Customer Communication**
   - Email customers when new packages launch
   - Remind customers of expiring credits
   - Send monthly billing statements
   - Offer package upgrades

4. **Financial Planning**
   - Set aside 30% of income for taxes
   - Build emergency fund (3 months expenses)
   - Reinvest 10-20% in marketing/equipment
   - Monitor cash flow weekly

---

## Support

Need help?
- Check IMPLEMENTATION_SUMMARY.md for technical details
- Review TESTING_CHECKLIST.md for verification steps
- Check system logs for error messages
- Contact your developer for custom support

---

**Version:** 1.0  
**Last Updated:** 2024  
**For:** Crash Hockey Application
