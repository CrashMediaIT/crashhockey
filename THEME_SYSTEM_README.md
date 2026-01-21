# Theme Customization System

## Overview
The Crash Hockey application now includes a comprehensive theming system that allows administrators to customize all colors across the entire application through a user-friendly admin interface.

## Features

### Admin Interface
- **Location**: Dashboard → System Admin → Theme Settings
- **Access**: Admin users only
- **Live Preview**: See color changes in real-time before saving
- **Color Categories**:
  - Brand Colors (Primary, Secondary, Button Hover)
  - Background Colors (Main Background, Card Background, Sidebar)
  - Text Colors (Primary Text, Muted Text)
  - UI Colors (Border Color)
  - Status Colors (Success, Error, Warning)

### Database Structure
**Table**: `theme_settings`
- `id` - Primary key
- `setting_name` - Color setting name (e.g., primary_color)
- `setting_value` - Hex color code (e.g., #7000a4)
- `updated_at` - Timestamp of last update
- `updated_by` - User ID who made the change

### Default Colors
- **Primary Color**: #7000a4 (Deep Purple)
- **Secondary Color**: #c0c0c0 (Silver)
- **Background Color**: #06080b (Deep Black/Blue)
- **Card Background**: #0d1117 (Slightly Lighter)
- **Text Color**: #ffffff (White)
- **Text Muted**: #94a3b8 (Slate Gray)
- **Border Color**: #1e293b (Dark Slate)
- **Sidebar Color**: #020305 (Deepest Black)
- **Button Hover**: #a78bfa (Light Purple)
- **Success**: #22c55e (Green)
- **Error**: #ef4444 (Red)
- **Warning**: #f59e0b (Orange)

## Files

### Core Files
1. **views/admin_theme_settings.php** - Admin interface for theme customization
2. **process_theme_settings.php** - Backend processing with validation
3. **css/theme-variables.php** - Dynamic CSS generator that reads from database

### Integration Points
- **dashboard.php** - Added route and menu item for theme settings
- **index.php** - Landing page includes theme CSS
- **login.php** - Login page includes theme CSS
- **register.php** - Registration page includes theme CSS
- **public_sessions.php** - Public sessions page includes theme CSS

### Database Files
- **deployment/schema.sql** - Added theme_settings table and default values
- **setup.php** - Added validation for theme_settings table

## Usage

### For Administrators

1. **Navigate to Theme Settings**:
   - Log in as an admin user
   - Go to Dashboard → System Admin → Theme Settings

2. **Customize Colors**:
   - Click on any color swatch or type a hex code
   - Use the color picker for easy selection
   - See changes in the live preview panel on the right

3. **Save Changes**:
   - Click "Save Theme" button
   - Changes apply immediately across the entire application
   - All users will see the new theme on their next page load

4. **Reset to Defaults**:
   - Click "Reset to Defaults" button
   - Confirms before resetting
   - Restores original deep purple theme (#7000a4)

### Technical Implementation

#### How It Works
1. **Theme Storage**: Colors are stored in the `theme_settings` database table
2. **CSS Generation**: `css/theme-variables.php` dynamically generates CSS variables from database
3. **Cache Headers**: CSS file is cached for 1 hour for performance
4. **Fallback**: If database is unavailable, falls back to default colors

#### CSS Variables Created
```css
:root {
    --primary: [from database]
    --neon: [from database]
    --secondary: [from database]
    --accent: [from database]
    --bg: [from database]
    --bg-main: [from database]
    --bg-card: [from database]
    --sidebar: [from database]
    --text: [from database]
    --text-white: [from database]
    --text-dim: [from database]
    --border: [from database]
    --button-hover: [from database]
    --success: [from database]
    --error: [from database]
    --warning: [from database]
}
```

## Security Features

### Input Validation
- All hex color codes validated with regex: `/^#[0-9A-Fa-f]{6}$/`
- Prevents XSS attacks through color input
- Server-side validation in `process_theme_settings.php`

### CSRF Protection
- All form submissions require CSRF token
- Token validation on server side
- Prevents unauthorized theme changes

### Rate Limiting
- 10 requests per 60 seconds per IP
- Prevents abuse of theme API
- Returns HTTP 429 on rate limit exceeded

### Admin-Only Access
- Checked at both view and processing levels
- Returns HTTP 403 for non-admin users
- Logged in audit trail

### Audit Logging
- All theme changes logged to `audit_log` table
- Includes user ID and timestamp
- Tracks both saves and resets

## API Endpoints

### process_theme_settings.php

#### Save Theme
```javascript
POST /process_theme_settings.php
Content-Type: application/x-www-form-urlencoded

action=save
csrf_token=[token]
primary_color=#7000a4
secondary_color=#c0c0c0
// ... other colors
```

**Response**:
```json
{
    "success": true,
    "message": "Theme settings saved successfully"
}
```

#### Reset Theme
```javascript
POST /process_theme_settings.php
Content-Type: application/x-www-form-urlencoded

action=reset
csrf_token=[token]
```

**Response**:
```json
{
    "success": true,
    "message": "Theme reset to defaults",
    "colors": {
        "primary_color": "#7000a4",
        // ... all default colors
    }
}
```

#### Get Current Theme
```javascript
POST /process_theme_settings.php
Content-Type: application/x-www-form-urlencoded

action=get
csrf_token=[token]
```

**Response**:
```json
{
    "success": true,
    "colors": {
        "primary_color": "#7000a4",
        // ... all current colors
    }
}
```

## Browser Support
- Chrome 88+
- Firefox 85+
- Safari 14+
- Edge 88+

(All modern browsers with CSS custom properties support)

## Performance
- **CSS Generation**: < 10ms
- **Cache Duration**: 1 hour
- **Database Queries**: 1 simple SELECT per page load
- **No JavaScript Required**: For basic theming (JavaScript only for admin interface)

## Troubleshooting

### Theme Changes Not Appearing
1. **Clear Browser Cache**: Hard refresh (Ctrl+F5 or Cmd+Shift+R)
2. **Check Database**: Verify theme_settings table has correct values
3. **File Permissions**: Ensure css/theme-variables.php is accessible

### Colors Reverting to Defaults
1. **Database Connection**: Check if database is accessible
2. **Table Missing**: Verify theme_settings table exists
3. **Check Logs**: Review error_log for PHP errors

### Admin Interface Not Loading
1. **Check Role**: Ensure user has admin role
2. **Route Added**: Verify dashboard.php has theme settings route
3. **File Exists**: Confirm views/admin_theme_settings.php exists

## Future Enhancements
- [ ] Export/Import theme presets
- [ ] Multiple theme profiles (day/night modes)
- [ ] Font family customization
- [ ] Border radius customization
- [ ] Spacing/padding customization
- [ ] Theme preview before applying
- [ ] User-specific themes
- [ ] Theme marketplace/gallery

## Migration Notes
If updating from a version without theming:

1. **Run Schema Update**:
   ```sql
   -- Run the theme_settings table creation from deployment/schema.sql
   CREATE TABLE IF NOT EXISTS `theme_settings` (...)
   
   -- Insert default values
   INSERT IGNORE INTO `theme_settings` (...)
   ```

2. **Clear Cache**: Clear any CDN or browser caches

3. **Test**: Verify theme settings page loads and colors apply

## Support
For issues or questions:
- Check audit logs for theme change history
- Review error_log for PHP errors
- Contact system administrator

## Credits
- **Design**: Deep purple theme (#7000a4)
- **Implementation**: Crash Hockey Development Team
- **Security**: CSRF protection, input validation, rate limiting
- **Performance**: Cached CSS, minimal queries

---

**Last Updated**: January 2026
**Version**: 1.0.0
**Status**: Production Ready
