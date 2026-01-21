# Content Security Policy (CSP) Migration Plan

## Overview
This document outlines the plan to migrate the Crash Hockey platform away from inline styles to comply with strict Content Security Policy (CSP) requirements. This is a **low priority** task that should be addressed in a future phase to improve security posture.

## Current State
The application currently uses inline styles extensively in PHP views for styling components. This approach:
- ✅ Works well for rapid development
- ✅ Keeps styles close to their HTML components
- ❌ Violates strict CSP policies that disallow `'unsafe-inline'`
- ❌ Could pose XSS risks in certain edge cases

## Security Context
**Risk Level**: Low
- The application has comprehensive XSS protections through:
  - Input sanitization with `htmlspecialchars()`
  - Prepared SQL statements (preventing SQL injection)
  - CSRF token validation on all forms
  - Security headers set via `security.php`
  
Inline styles alone do not introduce significant XSS vulnerabilities when input is properly sanitized.

## Migration Strategy

### Phase 1: Infrastructure Setup (2-4 hours)
1. **Create centralized CSS file**
   - Create `/public/css/app.css` for global styles
   - Create `/public/css/admin.css` for admin-specific styles
   - Create `/public/css/components.css` for reusable components

2. **Set up build process (optional)**
   - Consider SASS/SCSS for better organization
   - Set up minification for production
   - Implement CSS purging to remove unused styles

### Phase 2: Extract Common Styles (8-12 hours)
1. **Identify common patterns**
   - Forms (inputs, selects, textareas, buttons)
   - Tables and data grids
   - Modal dialogs
   - Cards and containers
   - Navigation elements

2. **Create CSS classes**
   ```css
   /* Example: Form Components */
   .form-group { margin-bottom: 20px; }
   .form-label { ... }
   .form-input { ... }
   .form-select { ... }
   .form-textarea { ... }
   
   /* Example: Buttons */
   .btn { ... }
   .btn-primary { ... }
   .btn-secondary { ... }
   .btn-danger { ... }
   ```

3. **Update views incrementally**
   - Start with most-used components (buttons, forms)
   - Update one view file at a time
   - Test thoroughly after each change

### Phase 3: View-Specific Styles (20-30 hours)
1. **Create per-view CSS sections**
   - Option A: Single large CSS file with view-specific namespaces
   - Option B: Separate CSS file per view (loaded conditionally)
   
   Example:
   ```css
   /* admin_audit_logs.css */
   .audit-logs-page .filter-container { ... }
   .audit-logs-page .logs-table { ... }
   ```

2. **Update each PHP view file**
   - Remove `<style>` tags
   - Add CSS class names to HTML elements
   - Link to external CSS file

### Phase 4: Dynamic Styles (4-6 hours)
Some styles need to be dynamic (e.g., based on database values). Options:

1. **CSS Custom Properties (Recommended)**
   ```php
   <div style="--primary-color: <?= $theme_color ?>;" class="themed-container">
   ```
   This is CSP-compliant when used with nonces.

2. **Data Attributes + JavaScript**
   ```php
   <div data-color="<?= $theme_color ?>" class="themed-container">
   ```
   Apply styles via JavaScript.

3. **Generate CSS classes server-side**
   Generate unique CSS classes for dynamic colors/sizes.

### Phase 5: Update CSP Headers (1 hour)
Once all inline styles are removed:

```php
// security.php
$csp = "default-src 'self'; " .
       "script-src 'self' 'nonce-" . $nonce . "' https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .
       "style-src 'self' https://cdnjs.cloudflare.com https://fonts.googleapis.com; " .  // Remove 'unsafe-inline'
       "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; " .
       "img-src 'self' data: https:; " .
       "connect-src 'self'";
```

## Implementation Timeline

### Recommended Approach
Implement this migration in a **dedicated sprint** separate from feature development:

1. **Sprint Preparation** (1 day)
   - Set up CSS infrastructure
   - Create base CSS files
   - Document CSS architecture

2. **Week 1: Common Components** (5 days)
   - Extract and implement form styles
   - Extract and implement button styles
   - Extract and implement table styles
   - Extract and implement modal styles
   - Update 5-10 most-used views

3. **Week 2: Admin Views** (5 days)
   - Migrate all admin_* views
   - Test admin functionality thoroughly
   - Create admin.css

4. **Week 3: User Views** (5 days)
   - Migrate user-facing views
   - Test user functionality
   - Create user.css

5. **Week 4: Testing & Refinement** (5 days)
   - Cross-browser testing
   - Mobile responsiveness testing
   - Performance optimization
   - Update CSP headers
   - Final QA

**Total Estimated Time**: 40-60 hours (5-8 days)

## Files Affected
Based on current codebase, approximately **50+ view files** will need updates:

### High Priority (Admin Tools)
- `/views/admin_*.php` (15+ files)
- `/views/dashboard.php`
- `/views/home.php`

### Medium Priority (User Features)
- `/views/goals.php`
- `/views/evaluations_*.php`
- `/views/athletes.php`
- `/views/schedule.php`
- `/views/stats.php`

### Lower Priority (Reports & Specialized)
- `/views/reports*.php`
- `/views/library_*.php`
- `/views/practice_plans.php`

## Testing Checklist
After migration, verify:

- [ ] All forms render correctly
- [ ] All buttons maintain styling
- [ ] Modal dialogs work properly
- [ ] Tables display correctly
- [ ] Responsive design works on mobile
- [ ] No console errors
- [ ] CSP violations are eliminated (check browser console)
- [ ] All animations/transitions work
- [ ] Print styles work (if applicable)
- [ ] Accessibility is maintained (colors, contrast)

## Rollback Plan
If issues arise during migration:

1. **Version Control**: Each view migration should be a separate commit
2. **Feature Flag**: Optionally use a feature flag to switch between inline and external styles
3. **Gradual Rollout**: Deploy to staging first, then production incrementally

## Benefits After Migration

### Security
- ✅ Strict CSP compliance (no `'unsafe-inline'`)
- ✅ Reduced XSS attack surface
- ✅ Better audit trail for style changes

### Performance
- ✅ CSS caching (faster page loads)
- ✅ Reduced HTML size
- ✅ Parallel CSS downloads

### Maintainability
- ✅ Centralized style management
- ✅ Easier to update global styling
- ✅ Better code organization
- ✅ Easier for new developers to understand

### Development
- ✅ Style reusability across views
- ✅ Easier A/B testing of styles
- ✅ Better separation of concerns

## Alternatives Considered

### Option 1: CSP Nonces for Inline Styles (NOT RECOMMENDED)
Use `style-src 'nonce-{random}'` to allow inline styles with nonces:
```php
<style nonce="<?= $csp_nonce ?>">
  .custom { color: red; }
</style>
```
**Pros**: Quick implementation
**Cons**: 
- Each page load generates unique nonce
- Breaks CSS caching
- Defeats purpose of CSP

### Option 2: Hash-Based CSP (NOT RECOMMENDED)
Calculate SHA-256 hash of each `<style>` block and add to CSP:
```php
style-src 'sha256-abc123...' 'sha256-def456...'
```
**Pros**: Maintains some caching
**Cons**:
- Requires updating CSP header for every style change
- Unmanageable with many style blocks
- Brittle and error-prone

### Option 3: External CSS (RECOMMENDED - THIS PLAN)
Move all styles to external CSS files.

## Resources & References
- [Content Security Policy Reference](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [CSP Evaluator Tool](https://csp-evaluator.withgoogle.com/)
- [OWASP CSP Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html)

## Notes
- This migration is **low priority** and should not block current development
- Current inline style approach is acceptable for internal tools with proper input sanitization
- Consider migrating during a slower development period or dedicated refactoring sprint
- The application remains secure without this migration due to comprehensive XSS protections

---

**Document Version**: 1.0  
**Created**: Phase 5 Implementation  
**Status**: Planning  
**Priority**: Low  
**Estimated Effort**: 40-60 hours
