# COMPREHENSIVE QUALITY ASSURANCE & TEST RESULTS REPORT

**Project:** Dynamic News Portal (`SONI_PHP_WEBSITE`)  
**QA Lead:** Senior Full-Stack & QA Automation Engineer  
**Date:** 2026-10-11  
**Execution Environment:** Windows 11, PHP 8.3.33 (CLI & Apache mod_php), MySQL 8.0, XAMPP  
**Overall Automated Test Status:** **38 / 38 TESTS PASSED (100% PASS RATE)**

---

## 1. AUTOMATED TEST SUITE EXECUTION LOGS

Executed via `php -f run_tests.php` against the live local deployment:

```text
============================================================
 DYNAMIC NEWS PORTAL — AUTOMATED VERIFICATION SUITE
============================================================

--- 1. DATABASE & SCHEMA CHECKS ---
[PASS] DB-01   : PDO Database Connection (Connected to news_portal)
[PASS] DB-TBL-USERS: Table 'users' Exists (Table verified)
[PASS] DB-TBL-CATEGORIES: Table 'categories' Exists (Table verified)
[PASS] DB-TBL-NEWS: Table 'news' Exists (Table verified)
[PASS] DB-TBL-COMMENTS: Table 'comments' Exists (Table verified)
[PASS] DB-TBL-LIKES: Table 'likes' Exists (Table verified)
[PASS] DB-TBL-CONTACT_MESSAGES: Table 'contact_messages' Exists (Table verified)
[PASS] DB-TBL-NEWSLETTER_SUBSCRIBERS: Table 'newsletter_subscribers' Exists (Table verified)

--- 2. AUTHENTICATION & HASHING CHECKS ---
[PASS] AUTH-01 : Admin Seed User Exists (Found admin@newsportal.local)
[PASS] AUTH-02 : Bcrypt Password Verification (Valid) (Bcrypt verified NewsPortal@2026)
[PASS] AUTH-03 : Bcrypt Password Verification (Invalid Rejected) (Rejected wrong password)
[PASS] AUTH-04 : Bcrypt Hash Generation (Standard Bcrypt prefix generated)

--- 3. APPLICATION SECURITY GUARDS ---
[PASS] SEC-CSRF-01: CSRF Token Generation (64-hex token generated)
[PASS] SEC-CSRF-02: CSRF Token Validation (Valid) (Valid token accepted)
[PASS] SEC-CSRF-03: CSRF Token Validation (Tampered Rejected) (Tampered token rejected)
[PASS] SEC-XSS-01: XSS htmlspecialchars Escaping (HTML tags encoded)
[PASS] SEC-SQLI-01: SQL Injection Isolation (Payload treated as literal string; zero records returned)

--- 4. CRUD INTEGRITY (CONTROLLED TEST ARTICLE) ---
[PASS] CRUD-01 : Category Creation (Created category in persistence)
[PASS] CRUD-02 : News Article Creation (Created article in persistence)
[PASS] CRUD-03 : News Article Read & Attributes (Verified slug and flags)
[PASS] CRUD-04 : News Article Update (Title updated in persistence)
[PASS] CRUD-05 : News Article Cleanup & Deletion (Test record deleted cleanly)

--- 5. LIVE HTTP ROUTES ---
[PASS] HTTP-   : Endpoint '/' (Expected 200, Got: HTTP/1.1 200 OK)
[PASS] HTTP-INDEX.PHP: Endpoint '/index.php' (Expected 200, Got: HTTP/1.1 200 OK)
[PASS] HTTP-ABOUT.PHP: Endpoint '/about.php' (Expected 200, Got: HTTP/1.1 200 OK)
[PASS] HTTP-CONTACT.PHP: Endpoint '/contact.php' (Expected 200, Got: HTTP/1.1 200 OK)
[PASS] HTTP-LOGIN.PHP: Endpoint '/login.php' (Expected 200, Got: HTTP/1.1 200 OK)
[PASS] HTTP-REGISTER.PHP: Endpoint '/register.php' (Expected 200, Got: HTTP/1.1 200 OK)
[PASS] HTTP-SITEMAP.PHP: Endpoint '/sitemap.php' (Expected 200, Got: HTTP/1.1 200 OK)
[PASS] HTTP-ADMIN/LOGIN.PHP: Endpoint '/admin/login.php' (Expected 200, Got: HTTP/1.1 200 OK)
[PASS] HTTP-ADMIN/INDEX.PHP: Endpoint '/admin/index.php' (Expected 302, Got: HTTP/1.1 302 Found)
[PASS] HTTP-ADMIN/NEWS/INDEX.PHP: Endpoint '/admin/news/index.php' (Expected 302, Got: HTTP/1.1 302 Found)
[PASS] HTTP-ADMIN/CATEGORIES/INDEX.PHP: Endpoint '/admin/categories/index.php' (Expected 302, Got: HTTP/1.1 302 Found)
[PASS] HTTP-ADMIN/USERS/INDEX.PHP: Endpoint '/admin/users/index.php' (Expected 302, Got: HTTP/1.1 302 Found)
[PASS] HTTP-ADMIN/COMMENTS/INDEX.PHP: Endpoint '/admin/comments/index.php' (Expected 302, Got: HTTP/1.1 302 Found)
[PASS] HTTP-ADMIN/NEWSLETTER/INDEX.PHP: Endpoint '/admin/newsletter/index.php' (Expected 302, Got: HTTP/1.1 302 Found)
[PASS] HTTP-ADMIN/NEWSLETTER/EXPORT.PHP: Endpoint '/admin/newsletter/export.php' (Expected 302, Got: HTTP/1.1 302 Found)
[PASS] HTTP-ADMIN/MESSAGES/INDEX.PHP: Endpoint '/admin/messages/index.php' (Expected 302, Got: HTTP/1.1 302 Found)

============================================================
 TEST RUN SUMMARY: TOTAL: 38 | PASSED: 38 | FAILED: 0
============================================================
```

---

## 2. MANUAL WORKFLOW & BROWSER QA VERIFICATION

| Test Case | Description | Expected Outcome | Observed Result | Status |
| :--- | :--- | :--- | :--- | :---: |
| **TC-01** | Load homepage in browser | Header, Breaking News ticker, Hero story, Latest feed, Category rows load | Rendered with 0 console errors | **PASS** |
| **TC-02** | Click Breaking News story | Navigates to corresponding article detail page | Loaded correct article slug | **PASS** |
| **TC-03** | Article page view counter | Counter increments by 1 in MySQL and on page | Verified increment in database | **PASS** |
| **TC-04** | Like button click (Logged-in) | Increases like count and marks active heart | AJAX like recorded in `likes` table | **PASS** |
| **TC-05** | Like button click (Guest) | Disabled with tooltip "Log in to like" | Disabled, zero guest records | **PASS** |
| **TC-06** | Comment submission | Submitted comment placed in `pending` state | Verified row in `comments` table with `status='pending'` | **PASS** |
| **TC-07** | Admin comment approval | Admin changes status to `approved` | Comment appears on public article page | **PASS** |
| **TC-08** | Admin unauthenticated access | Direct visit to `/admin/index.php` without session | Redirected to `/admin/login.php` | **PASS** |
| **TC-09** | Admin login with credentials | Enter `admin@newsportal.local` & `NewsPortal@2026` | Logs in and displays Admin Dashboard | **PASS** |
| **TC-10** | Article creation with image upload | Fill form in `/admin/news/create.php`, upload JPG | Image saved to `uploads/news/`, article in DB | **PASS** |
| **TC-11** | Category deletion with articles | Try deleting category with assigned articles | Blocked with warning message; referential integrity preserved | **PASS** |
| **TC-12** | Admin self-lockout defense | Try deactivating current admin account | Blocked with warning; account remains active | **PASS** |
| **TC-13** | Newsletter CSV export | Click "Export CSV" in `/admin/newsletter/` | Streams UTF-8 encoded CSV file download | **PASS** |
| **TC-14** | XML Sitemap validation | Request `/sitemap.php` | Returns valid XML with published article URLs | **PASS** |
| **TC-15** | Robots.txt directives | Request `/robots.txt` | Disallows `/admin/`, points to sitemap | **PASS** |

---

## 3. SECURITY PENETRATION AUDIT SUMMARY

- **SQL Injection:** Tested with `' OR 1=1 --` on login, category, and search. **0 vulnerabilities found.**
- **Cross-Site Scripting (XSS):** Tested with `<script>alert('xss')</script>` on comments, search, and contact form. **All inputs escaped or stripped.**
- **Cross-Site Request Forgery (CSRF):** State-changing POST requests without CSRF token were rejected with 403 / redirect.
- **Unauthorized Upload Execution:** Verified `.htaccess` in `uploads/news/` prohibits executing PHP scripts in the directory.
