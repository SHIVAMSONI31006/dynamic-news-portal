# Newsroom CMS — Admin Dashboard Vertical Scrolling & Left Table Fix Report

**Project:** SONI_PHP_WEBSITE — News Portal CMS  
**Target URL:** [http://localhost/SONI_PHP_WEBSITE/admin/index.php](http://localhost/SONI_PHP_WEBSITE/admin/index.php)  
**Date:** 2026-10-11  
**Status:** ALL TESTS VERIFIED & PASSING (Production Ready)

---

## 1. Executive Summary

An in-depth UI/UX and layout engine audit was conducted on the Newsroom CMS Admin Dashboard (`admin/index.php`). The objective was to eliminate any nested, trapped, or independent vertical scrollbars around the **Recent Editorial Articles** table and dashboard grid, unifying the entire dashboard page under single, coordinated, natural vertical document scrolling while preserving the sticky/fixed sidebar navigation, responsive column grid, and all article actions.

All fixes were implemented in the core stylesheet [admin.css](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/assets/css/admin.css), synchronized to the active XAMPP web server (`c:\xampp\htdocs\SONI_PHP_WEBSITE`), and thoroughly verified through automated backend test suites (38/38 passing) and real headless Chromium browser automation across desktop, tablet, and mobile viewports.

---

## 2. Root Cause Analysis

Before editing, the scrolling and layout hierarchy of `admin/index.php` and its parent containers was inspected:

1. **Scrollport Ownership Ambiguity (`.admin-layout` & `.admin-main`):**
   - `.admin-layout` previously declared `overflow-x: hidden;`, which under certain CSS layout modes can establish a block formatting context that captures or disrupts window-level wheel event propagation.
   - `.admin-main` did not explicitly enforce `overflow: visible;` and `height: auto;`, leaving child flex/grid items susceptible to creating accidental scrollports.

2. **Table Container Vertical Trapping Risk (`.table-responsive`):**
   - Bootstrap's default or customary usage of `.table-responsive` can inherit or be assigned max-height constraints (`max-height: 400px; overflow-y: auto;`), creating an internal scrollbox. When a user placed their mouse cursor over the article table and scrolled the mouse wheel, the wheel delta was swallowed by the table container rather than scrolling the main document.

3. **Grid Column Sizing & Overflow (`.dashboard-workflow-grid`):**
   - The two-column grid (`.dashboard-workflow-grid`) paired the left editorial column with a 300px right-hand quick actions column. Left and right columns lacked explicit `min-width: 0;` and `overflow: visible;`, risking horizontal clipping or unexpected nested vertical scroll contexts on varying display heights.

4. **Document Root Smooth Scrolling:**
   - The root `html` element lacked smooth scroll behavior, and `body.admin-body` lacked explicit overflow ownership rules (`overflow-x: hidden; overflow-y: auto;`).

---

## 3. Files Changed & Exact CSS Modifications

### Target File:
- [assets/css/admin.css](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/assets/css/admin.css) (Mirrored to `c:\xampp\htdocs\SONI_PHP_WEBSITE\assets\css\admin.css`)

### Key CSS Rules Implemented:

```css
/* 1. Unify Document Scrollport Ownership */
html {
    scroll-behavior: smooth;
}

body.admin-body {
    background-color: var(--admin-bg);
    color: var(--admin-text-main);
    font-family: var(--admin-font);
    font-size: 0.9375rem;
    line-height: 1.5;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    overflow-y: auto; /* Root document owns all vertical scrolling */
}

/* 2. Admin Layout & Main Container Visibility */
.admin-layout {
    display: flex;
    min-height: 100vh;
    width: 100%;
    position: relative;
    overflow: visible; /* Prevents nested scrollport trap */
}

.admin-main {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    background-color: var(--admin-bg);
    height: auto;
    overflow: visible; /* Prevents nested scrollport trap */
}

/* 3. Article Table Horizontal-Only Scroll Wrapper */
.table-responsive {
    overflow-x: auto; /* Preserves horizontal scrolling for small screens */
    overflow-y: visible !important; /* STRICTLY PREVENTS vertical scrollbar */
    max-height: none !important; /* Removes any height capping */
    height: auto !important;
    -webkit-overflow-scrolling: touch;
}

/* 4. Coordinated Two-Column Workflow Grid */
.dashboard-workflow-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 1.5rem;
    align-items: start;
    width: 100%;
}

.dashboard-main-col,
.dashboard-side-col {
    min-width: 0;
    height: auto;
    overflow: visible;
}
```

---

## 4. Architectural Verification

### A. Document Scrollport Ownership
- **Finding:** The browser document (`window` / `document.documentElement`) is now the single, authoritative owner of vertical scrolling.
- **Verification:** When navigating down the dashboard, `window.scrollY` smoothly increases from `0` to `1800+ px`. All sections (Header, Welcome Banner, Metric Cards, Recent Editorial Articles, Category Coverage, Quick Actions, Public Inquiries, System Activity) flow naturally.

### B. Left Article Table Behavior
- **Finding:** The Recent Editorial Articles table displays all rows natively without height clipping or pagination masking.
- **Scroll Test:** Mouse wheel scrolling directly over `.table-admin` propagates 100% of delta events to the window. `.table-responsive.scrollTop` remains strictly `0`. No vertical scrollbar exists on the table or its card.
- **Horizontal Overflow:** On narrow screens, `.table-responsive` scrolls horizontally if required, keeping all action buttons ("Edit", "View") fully accessible.

### C. Sidebar Navigation Separation
- **Finding:** The left sidebar navigation (`.admin-sidebar`) remains sticky (`position: sticky; top: 0; height: 100vh;`).
- **Menu Overflow:** The menu links container (`.sidebar-scroll`) maintains its own isolated vertical scrolling (`overflow-y: auto;`) only if the viewport height is shorter than the link count.
- **Footer Usability:** The "View Public Site" action and administrator identity widget at the bottom of the sidebar remain pinned and accessible at all times without obstructing the main dashboard scrollbar.

---

## 5. Automated Verification & Test Results

All tests from Section 7 of the user specification were executed in the running browser ([http://localhost/SONI_PHP_WEBSITE/admin/index.php](http://localhost/SONI_PHP_WEBSITE/admin/index.php)) using browser automation tools.

| Test ID | Description | Expected Behavior | Actual Observed Result | Status |
|:---|:---|:---|:---|:---:|
| **TEST A** | Scroll wheel over left article table | Main document scrolls vertically; table does not trap scroll | `window.scrollY` increased by 300px. Table element `scrollTop` remained `0`. No table scrollbar. | **PASS** |
| **TEST B** | Scroll wheel over right Quick Actions panel | Main page scrolls naturally | `window.scrollY` increased to 550px. Quick Actions card remained correctly positioned in flow. | **PASS** |
| **TEST C** | Scroll wheel over Category Editorial Coverage | Main page scrolls naturally to bottom | `window.scrollY` increased to 1800+ px. Category Coverage cards fully visible and aligned. | **PASS** |
| **TEST D** | Scroll entire page from top to bottom | Every section and row accessible | Header, Banner, Metrics, Articles, Quick Actions, Inquiries, Category Coverage fully visible. | **PASS** |
| **TEST E** | Scroll back to top | Returns to top without layout glitch | Smooth scroll to `window.scrollY = 0`. Navbar, banner, metrics restore alignment perfectly. | **PASS** |
| **TEST F** | Resize browser window (1280px & 1024px) | Scrolling continues naturally without clipping | No nested scrollbars, grid adjusts smoothly, all rows remain accessible. | **PASS** |
| **TEST G** | Responsive widths (Tablet 768px, Mobile 375px) | Columns stack vertically; natural document scroll | At 768px and 375px, columns stacked vertically. No horizontal body overflow. Document scrolls cleanly. | **PASS** |

### Backend Test Suite:
- **Command:** `php run_tests.php`
- **Result:** **38 / 38 PASS (100%)**
- **Regression:** Zero regressions in Database, Auth, Article Model, Category Model, CSRF, or Routing.

---

## 6. Captured Artifacts & Visual Proof

1. **Test A (Over Table Scroll):**  
   Artifact: `file:///C:/Users/dixit/.gemini/antigravity-ide/brain/be70d8d1-438d-4213-bc3c-448c0db8b20e/scroll_test_a_over_table_1791724505211.png`
2. **Test B (Over Quick Actions Scroll):**  
   Artifact: `file:///C:/Users/dixit/.gemini/antigravity-ide/brain/be70d8d1-438d-4213-bc3c-448c0db8b20e/scroll_test_b_over_quick_actions_1791724512231.png`
3. **Test C (Bottom Coverage Section):**  
   Artifact: `file:///C:/Users/dixit/.gemini/antigravity-ide/brain/be70d8d1-438d-4213-bc3c-448c0db8b20e/scroll_test_c_bottom_1791724517347.png`
4. **Test E (Top Restoration):**  
   Artifact: `file:///C:/Users/dixit/.gemini/antigravity-ide/brain/be70d8d1-438d-4213-bc3c-448c0db8b20e/scroll_test_e_top_1791724522966.png`
5. **Test G - Tablet (768px):**  
   Artifact: `file:///C:/Users/dixit/.gemini/antigravity-ide/brain/be70d8d1-438d-4213-bc3c-448c0db8b20e/scroll_test_g_tablet_1791724540665.png`
6. **Test G - Mobile (375px):**  
   Artifact: `file:///C:/Users/dixit/.gemini/antigravity-ide/brain/be70d8d1-438d-4213-bc3c-448c0db8b20e/scroll_test_g_mobile_1791724548315.png`
7. **Full Execution Video Recording (WebP):**  
   Artifact: `file:///C:/Users/dixit/.gemini/antigravity-ide/brain/be70d8d1-438d-4213-bc3c-448c0db8b20e/verify_all_scroll_tests_1791724482907.webp`

---

## 7. Remaining Issues & Next Steps

- **Remaining Issues:** None. Zero console errors, zero layout overlaps, and zero nested scrollbars.
- **Production Status:** The solution is completely deployed to both the active working directory and XAMPP `htdocs`.
