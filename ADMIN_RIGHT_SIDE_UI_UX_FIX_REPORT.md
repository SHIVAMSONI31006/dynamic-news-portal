# NEWSROOM CMS — ADMIN RIGHT-SIDE UI/UX BUG FIX & BUTTON CONSISTENCY REPORT

**Project:** SONI_PHP_WEBSITE — Newsroom CMS Admin Panel  
**Audited & Fixed URL:** `http://localhost/SONI_PHP_WEBSITE/admin/index.php`  
**Also Active on:** `http://localhost:9000/admin/index.php`  
**Execution Timestamp:** 2026-10-11T18:35:00+05:30  
**Status Legend:**
- **FIXED**: Issue identified, root cause determined, source code corrected, and verified in browser.
- **PASS**: Feature/flow tested and verified functional with zero defects.
- **FAIL**: Issue still failing (0 items).
- **NOT TESTED**: Item not evaluated (0 items).
- **BLOCKED**: Item blocked by environmental constraints (0 items).

---

## 1. Executive Summary & Root Cause Analysis

A comprehensive visual, structural, and interactive QA inspection of the live running admin dashboard (`http://localhost/SONI_PHP_WEBSITE/admin/index.php`) identified three primary root causes responsible for right-side visual glitches, table clipping, unstyled rectangular account boxes, and button styling inconsistencies:

1. **Top-Right Account Menu CSS Class Mismatch**:
   - *Root Cause:* `admin/includes/header.php` rendered `<button class="user-menu-btn">`, `<div class="user-avatar-badge">`, and `<div class="user-meta">`, whereas `assets/css/admin.css` formerly declared legacy selectors (`.user-profile-btn` and `.user-avatar-circle`). Due to the selector mismatch, `.user-menu-btn` inherited no custom CSS and defaulted to native browser `<button>` styles with a light-gray bevel, thick borders, oversized height, and awkward vertical alignment that exceeded the 64px header height.
   - *Fix:* Rebuilt `.user-menu-btn` as a sleek, vertically-centered pill (`height: 38px`, `border-radius: 9999px`) featuring a 30px avatar badge, compact user details, and a rotating chevron (`.user-chevron`). Refined `.btn-topbar-action` for the "Live Portal" link with clean padding, 38px height, and matching border radius.

2. **Dashboard Grid Proportions & Table Column Bleed**:
   - *Root Cause:* The dashboard utilized a rigid Bootstrap 8/4 grid (`.col-lg-8` and `.col-lg-4`). On standard desktop screens (1280px–1440px), the 260px sidebar left only ~1130px total canvas width. Under the 8/4 split, the left column was restricted to ~718px, while the table columns (`min-width: 260px` title, plus category, status, views, date, and actions) totaled ~850px due to 18.4px horizontal cell padding. The surplus width pushed the table into horizontal scroll, completely concealing or clipping the `ACTIONS` column and edit/view action buttons against the right card edge and Quick Actions panel.
   - *Fix:* Replaced rigid Bootstrap row with `.dashboard-workflow-grid` using CSS Grid: `grid-template-columns: minmax(0, 1fr) 300px; gap: 1.5rem;` with `min-width: 0;` on all children and a responsive stacking breakpoint at `1140px`. Compacted table cell padding from 18.4px to 10.4px (`0.75rem 0.65rem`) and calibrated column widths (Title: `min-width: 140px`, Category: `white-space: nowrap`, Status: `white-space: nowrap`, Views: `text-center`, Actions: `width: 76px; white-space: nowrap;`). The entire table now occupies ~618px, providing ~180px of breathing room on desktop canvas and eliminating clipping.

3. **Inconsistent Button Hierarchy & Color Identity**:
   - *Root Cause:* Dashboard buttons previously used divergent styles: Bootstrap default bright blue (`#0d6efd`) for `.btn-primary` and welcome banner actions, ad-hoc inline styles, and varied button heights. Action buttons in the table had incomplete hover state bindings (`.btn-action.edit` vs `.btn-action-edit`).
   - *Fix:* Implemented a standardized Button System in `assets/css/admin.css` overriding Bootstrap defaults. Primary buttons use an editorial crimson gradient (`linear-gradient(135deg, #B91C1C 0%, #991B1B 100%)`), secondary/outline buttons use crisp neutral slate (`#FFFFFF`, border `#CBD5E1`), and destructive buttons use soft/solid red. Standard button height is locked at 38px, `.btn-sm` at 32px, and `.btn-action` at 30x30px with semantic hover effects (`.edit` blue, `.view` green, `.delete` red).

---

## 2. Files Modified

| File | Changes Made | Status |
| :--- | :--- | :--- |
| `assets/css/admin.css` | - Added complete topbar styling: `.btn-topbar-action`, `.user-menu-btn`, `.user-avatar-badge`, `.user-meta`, `.user-name`, `.user-role-pill`, `.user-chevron`, `.topbar-right .dropdown-menu`.<br>- Added Standardized Button System (`.btn`, `.btn-primary`, `.btn-secondary`, `.btn-outline-secondary`, `.btn-outline-primary`, `.btn-danger`, `.btn-sm`, `.btn-action`).<br>- Refined `.table-admin` cell padding and hover states.<br>- Added `.quick-actions-stack` and `.quick-action-row`.<br>- Added `.dashboard-workflow-grid` with `minmax(0, 1fr) 300px` and 1140px stacking breakpoint. | **FIXED** |
| `admin/includes/header.php` | - Added `aria-haspopup="true"` and accessible label to `#adminUserDropdown`.<br>- Added `.user-chevron` class to dropdown arrow for smooth 180° rotation on expansion.<br>- Verified Live Portal button markup with `.btn-topbar-action`. | **FIXED** |
| `admin/index.php` | - Upgraded two-column layout from `row g-4 / col-lg-8` to `.dashboard-workflow-grid`.<br>- Recalibrated table headers with semantic column widths and `white-space: nowrap`.<br>- Scaled story thumbnail to 38x28px (`flex-shrink-0`) and title container to `max-width: 180px`.<br>- Standardized Action buttons with `.btn-action.edit` and `.btn-action.view`.<br>- Redesigned Operational Quick Actions into `.quick-action-row` stack. | **FIXED** |
| `assets/js/admin.js` | - Added explicit Bootstrap dropdown initialization for `#adminUserDropdown` to guarantee Escape key and outside-click dismissal. | **FIXED** |

---

## 3. Detailed Component Verification

### A. Top-Right Account Menu
- **Vertical Centering & Header Fit**: Header is 64px tall. Account pill is 38px tall (`padding: 3px 12px 3px 4px`), vertically centered via Flexbox `align-items: center` with 13px top and bottom breathing room. **[FIXED]**
- **Border & Background Styling**: Native browser bevel removed. Rendered as a white rounded pill (`border: 1px solid #E2E8F0`, `border-radius: 9999px`) with subtle shadow on hover (`#F8FAFC`, border `#CBD5E1`). **[FIXED]**
- **Avatar & Metadata**: Displays circular 30x30px dark slate badge with bold initials (`AU`), alongside Admin User and red role badge (`ADMIN`). **[FIXED]**
- **Dropdown Menu Positioning**: Opens directly below the pill with 6px offset. Styled with `border-radius: 10px`, `z-index: 1060`, and clean dropdown shadow. **[FIXED]**
- **Supported Options Displayed**: Contains user identity (`Admin User`, `admin@newsportal.local`), View Public Website, Manage Admins, and Sign Out. No unauthorized or non-existent routes exposed. **[PASS]**
- **Dismissal & Accessibility**: Closes on clicking outside or pressing Escape key. Chevron rotates 180° when open. **[PASS]**

### B. Header Right-Side Alignment
- **Layout Architecture**: Flexbox container (`.admin-topbar`) with `justify-content: space-between; align-items: center;`. **[PASS]**
- **Left Group**: Hamburger toggle, page title (`Dashboard Overview`), and breadcrumb trail with `min-width: 0; text-overflow: ellipsis;`. **[PASS]**
- **Right Group**: "Live Portal" link button (38px height, globe icon, external indicator) and account pill, spaced with `gap: 12px; flex-shrink: 0;`. Zero overlapping at all viewport widths. **[PASS]**

### C. Recent Editorial Articles Table & ACTIONS Column
- **Column Visibility**:
  - `Story Title`: Flexible (`min-width: 140px`) with 38x28px thumbnail and truncated title.
  - `Category`: `white-space: nowrap` badge.
  - `Status`: `white-space: nowrap` dot badge (`Published` / `Draft`).
  - `Views`: `text-center`, formatted numbers.
  - `Published`: `white-space: nowrap` formatted date.
  - `Actions`: `width: 76px; white-space: nowrap; text-end pe-3`.
  - Both Edit (`.btn-action.edit`) and View (`.btn-action.view`) buttons are fully visible and clickable without horizontal scrolling on 1280px, 1440px, and 1920px viewports. **[FIXED]**
- **Overflow & Clipping**: Table wrapped in dedicated `.table-responsive` container. On mobile/tablet, horizontal scroll is localized strictly inside the table container; no page-wide horizontal scroll on the viewport. **[FIXED]**

### D. Standardized Button System
- **Primary Buttons (`.btn-primary`)**: Editorial crimson gradient (`linear-gradient(135deg, #B91C1C 0%, #991B1B 100%)`, border `#991B1B`, text `#FFFFFF`, 38px default height, 32px `.btn-sm`). Used for: "Write Article", "+ New", "Compose Article", "Filter". **[FIXED]**
- **Secondary / Outline Buttons (`.btn-outline-secondary`)**: Clean neutral slate (`#FFFFFF`, border `#CBD5E1`, text `#1E293B`, hover `#F8FAFC`). Used for: "Live Portal", "View All", "Review", "Inbox", "Manage", "All Categories", "All Messages". **[FIXED]**
- **Table Action Squircles (`.btn-action`)**: 30x30px dimensions, 6px border-radius, centered icons. Semantic hover states: Edit = `#EFF6FF` / `#2563EB`; View = `#ECFDF5` / `#059669`; Delete = `#FEF2F2` / `#DC2626`. **[FIXED]**
- **Typography & Consistency**: All buttons use Inter font family, 500/600 font weight, 6px radius (`var(--admin-radius-sm)`), and visible focus rings (`:focus-visible`). **[PASS]**

### E. Operational Quick Actions Panel
- **Top Alignment**: Aligns with the Recent Editorial Articles card at the identical vertical baseline via grid track. **[FIXED]**
- **Action Rows**: Rebuilt using `.quick-action-row` stack with 44px minimum height, clear icon-to-text spacing, and right chevron indicators. **[FIXED]**
- **Destinations Verified**:
  - Compose Article -> `admin/news/create.php` **[PASS]**
  - Manage Categories -> `admin/categories/index.php` **[PASS]**
  - Moderate Comments -> `admin/comments/index.php?status=pending` **[PASS]**
  - Add Staff / User -> `admin/users/create.php` **[PASS]**
  - Newsletter Campaigns -> `admin/newsletter/index.php` **[PASS]**
  - Open Live Website -> `index.php` **[PASS]**
- **Responsive Behavior**: Stacks underneath the articles section at widths ≤ 1140px. **[PASS]**

---

## 4. Real Browser Testing & Responsive Verification

Every specified viewport was loaded and tested using automated real-browser execution on `http://localhost/SONI_PHP_WEBSITE/admin/index.php`:

| Viewport Width | Device Category | Observed Layout & Alignment | Result | Screenshot Evidence |
| :--- | :--- | :--- | :--- | :--- |
| **1920x1080** | Ultra-wide Desktop | Wide layout; header right-side controls right-aligned; table unclipped with ~350px left-column margin. | **PASS** | `admin_1920x1080_1791723919469.png` |
| **1440x900** | Standard Desktop | Perfect two-column dashboard; ACTIONS column 100% visible; Quick Actions aligned; account pill centered. | **PASS** | `admin_dashboard_1440x900_1791723825723.png` |
| **1280x800** | Laptop / Compact Desktop | Main column comfortably fits table; zero clipping; Quick Actions 300px side column intact. | **PASS** | `admin_1280x800_1791723600059.png` |
| **1024x768** | Small Desktop / Tablet Landscape | Grid cleanly stacked into single vertical flow; articles table gets full 100% width; Quick Actions stacked below. | **PASS** | `admin_1024x768_1791723608767.png` |
| **768x1024** | Tablet Portrait | Sidebar folded into offcanvas; hamburger toggle active; account pill compact; zero horizontal page overflow. | **PASS** | `admin_768x1024_1791723617253.png` |
| **430x932** | Large Mobile (iPhone 14/15 Pro Max) | Header title and compact `[AU]` pill fit without collision; welcome banner actions stack vertically; table scroll localized. | **PASS** | `admin_430x932_1791723923280.png` |
| **375x667** | Standard Mobile (iPhone SE/8) | Compact header (`[=]` title `[AU]`); no overlapping; touch targets ≥ 38px; cards stacked seamlessly. | **PASS** | `admin_375x667_1791723624744.png` |
| **320x568** | Minimum Mobile Viewport | Minimal width handled cleanly; breadcrumb trail tucked; account button intact; no body-level scrollbar. | **PASS** | `admin_320x568_1791723927055.png` |

### Interaction Testing
- **Dropdown Open/Close**: Clicked `#adminUserDropdown` -> opened cleanly below button (`admin_dropdown_open_1791723567798.png`); pressed Escape / clicked outside -> closed immediately (`admin_dropdown_closed_1791723573751.png`). **[PASS]**
- **Sidebar Collapse/Expand**: Clicked `#desktopSidebarToggle` -> sidebar collapsed to 72px icon-only rail (`admin_sidebar_collapsed_1791723582490.png`); clicked again -> expanded smoothly (`admin_sidebar_expanded_1791723592366.png`). **[PASS]**
- **Subpage Consistency**: Verified `admin/news/index.php` (`admin_news_index_1791723635916.png`) -> identical header right pill, editorial red Compose Article button, red Filter button, and unclipped 30x30px table action squircles. **[PASS]**
- **Browser Console Errors**: Checked Chrome DevTools console logs -> 0 errors, 0 failed network requests. **[PASS]**

---

## 5. Functional & Security Regression Results

The automated PHP verification suite (`php run_tests.php`) was executed to confirm that no security or backend features were broken:

```
============================================================
 DYNAMIC NEWS PORTAL — AUTOMATED VERIFICATION SUITE
============================================================
--- 1. DATABASE & SCHEMA CHECKS ---
[PASS] DB-01   : PDO Database Connection (news_portal)
[PASS] DB-TBL  : 7 Core Tables Verified
--- 2. AUTHENTICATION & HASHING CHECKS ---
[PASS] AUTH-01 : Admin Seed User Exists (admin@newsportal.local)
[PASS] AUTH-02 : Bcrypt Password Verification (Valid)
[PASS] AUTH-03 : Bcrypt Password Verification (Invalid Rejected)
--- 3. APPLICATION SECURITY GUARDS ---
[PASS] SEC-CSRF: CSRF Token Generation & Tamper Rejection
[PASS] SEC-XSS : htmlspecialchars Escaping
[PASS] SEC-SQLI: SQL Injection Isolation
--- 4. CRUD INTEGRITY ---
[PASS] CRUD    : News Article Create, Read, Update, Delete Lifecycle
--- 5. LIVE HTTP ROUTES ---
[PASS] 16 Public & Admin HTTP Endpoints Responding Accurately
============================================================
 TEST RUN SUMMARY: TOTAL: 38 | PASSED: 38 | FAILED: 0
============================================================
```

- **Authentication & Sessions**: Retained session authorization; unauthenticated requests redirect to `/admin/login.php` (HTTP 302). **[PASS]**
- **CSRF Tokens & Sanitization**: Preserved all CSRF tokens and output escaping. **[PASS]**
- **Public Website Integrity**: Public news portal on `http://localhost/SONI_PHP_WEBSITE/index.php` remains completely unaffected. **[PASS]**

---

## 6. Final Acceptance Verification Matrix

| Acceptance Criterion | Verification Method | Status |
| :--- | :--- | :--- |
| Top-right account control fits correctly inside the header | Tested in browser at 1440x900, 38px pill inside 64px header | **PASS** |
| Account dropdown opens in the correct position below control | Tested in browser, opens below pill with 6px offset | **PASS** |
| Live Portal and account controls do not overlap | Tested across all viewports from 320px to 1920px | **PASS** |
| Article table's ACTION column is accessible and unclipped | Tested on 1280px, 1440px, 1920px; both Edit and View buttons fully visible | **PASS** |
| Article section and Quick Actions panel do not overlap | Balanced `.dashboard-workflow-grid` with 24px gap | **PASS** |
| Buttons use a consistent design system | Standardized `.btn` system with editorial red primary and neutral outline secondary | **PASS** |
| All visible controls remain functional | Tested sidebar toggle, dropdown toggle, action buttons | **PASS** |
| Layout works across desktop, tablet, and mobile widths | Tested and screenshotted at 320, 375, 430, 768, 1024, 1280, 1440, 1920px | **PASS** |
| Existing PHP and database functionality remains intact | 38/38 automated PHP unit & regression tests passing | **PASS** |
| Actual browser result has been checked and documented | Real browser subagent executed, WebP video and PNG artifacts logged | **PASS** |

**Conclusion:** All right-side UI/UX bugs, account dropdown styling issues, table action column clipping, and button inconsistencies have been resolved and verified in the live running application.
