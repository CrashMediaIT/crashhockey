#!/bin/bash

echo "=== DETAILED SQL VALIDATION ==="
echo ""
echo "Checking critical table columns in views..."
echo ""

# Check sessions table references
echo "1. SESSIONS TABLE VALIDATION:"
echo "   Schema columns: id, session_type, session_type_category, title, session_date, session_time, session_plan, practice_plan_id, age_group_id, skill_level_id, arena, city, price, max_capacity, max_athletes, created_at"
echo ""
grep -n "s\\.session_date\|s\\.start_time\|s\\.end_time\|s\\.location_id\|s\\.coach_id\|s\\.capacity\|s\\.description" views/*.php | head -20
echo ""

# Check bookings table references  
echo "2. BOOKINGS TABLE VALIDATION:"
echo "   Schema columns: id, user_id, session_id, package_id, stripe_session_id, amount_paid, original_price, tax_amount, discount_code, credit_applied, booked_for_user_id, payment_type, status, created_at"
echo ""
grep -n "b\\.booking_date\|b\\.payment_method\|b\\.notes" views/*.php | head -20
echo ""

# Check users table references
echo "3. USERS TABLE VALIDATION:"
echo "   Schema columns: id, first_name, last_name, email, password, role, position, birth_date, primary_arena, profile_pic, assigned_coach_id, email_notifications, is_verified, verification_code, force_pass_change, created_at"
echo ""
grep -n "u\\.phone\|u\\.is_active\|u\\.encryption_key" views/*.php | head -20
echo ""

# Check age_groups table
echo "4. AGE_GROUPS TABLE VALIDATION:"
echo "   Schema columns: id, name, min_age, max_age, description, display_order, created_at"
echo ""
grep -n "ag\\.age_range" views/*.php | head -20
echo ""

# Check skill_levels table
echo "5. SKILL_LEVELS TABLE VALIDATION:"
echo "   Schema columns: id, name, description, display_order, created_at"
echo ""
grep -n "sl\\.level" views/*.php | head -20
echo ""

echo "=== CHECKING JOIN CONDITIONS ==="
echo ""

# Sessions JOINs
echo "Sessions table JOINs:"
grep -n "JOIN sessions\|sessions.*ON" views/*.php | grep -i "ON\|join" | head -30
echo ""

# Bookings JOINs
echo "Bookings table JOINs:"
grep -n "JOIN bookings\|bookings.*ON" views/*.php | grep -i "ON\|join" | head -30
echo ""

# Users JOINs
echo "Users table JOINs:"
grep -n "JOIN users\|users.*ON" views/*.php | grep -i "ON\|join" | head -30
echo ""

