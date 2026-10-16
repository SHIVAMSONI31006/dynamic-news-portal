# Master Cleanup & Optimization Report

**Project:** Dynamic News Portal & Content Management System  
**Repository:** [https://github.com/SHIVAMSONI31006/dynamic-news-portal](https://github.com/SHIVAMSONI31006/dynamic-news-portal)  
**Date:** 2026-10-16  
**Status:** COMPLETE & VERIFIED (100% Pass)  
**Production URL:** [https://dynamic-news-portal-rose.vercel.app](https://dynamic-news-portal-rose.vercel.app)  

---

## 1. Executive Summary

A comprehensive architectural audit, folder structure optimization, duplicate code elimination, and documentation consolidation was performed across the **Dynamic News Portal & Content Management System** codebase.

The cleanup successfully achieved:
1. **Zero Breaking Changes:** All public reader features, editorial admin capabilities, authentication guards, and database schemas remain fully functional.
2. **Eliminated Root Clutter:** Relocated 21 historical audit/QA reports and operational documentation into structured subdirectories (`docs/` and `docs/reports/`).
3. **Consolidated Duplicate Scripts:** Eliminated ~13.5 KB of duplicate PowerShell server management logic across `start-both.*` and `stop-both.*` by standardizing on `start-dual-servers.*` and `stop-dual-servers.*` with lightweight delegation forwarders.
4. **Organized Test Infrastructure:** Created a dedicated `tests/` directory housing `run_tests.php` and `database_test.php`, supported by a root forwarder `run_tests.php`.
5. **Cleaned Database Schemas:** Fixed mojibake characters in `database/production_schema.sql` to clean UTF-8 (`—` and `₹`) and removed hardcoded `USE test;` to make cloud imports fully portable.
6. **Removed Orphan & Obsolete Files:** Removed obsolete test scripts (`test_routes.php`), unused test uploads (`news_test_1791178716.jpg`), and empty directory placeholders (`assets/vendor/`).
7. **End-to-End Test Validation:** Verified 59/59 PHP files passed recursive syntax linting (`php -l`), and 38/38 automated unit, database, authentication, security, and live Vercel HTTP route tests passed (100% PASS).

---

## 2. File & Directory Inventory

### 2.1 Before vs. After Root Comparison

| Metric | Before Cleanup | After Cleanup | Change |
|---|---|---|---|
| Root Directory Files | 62 | 27 | **-35 (-56%)** |
| Root `.md` Clutter | 25 files | 3 files (`README.md`, `PROJECT_STRUCTURE.md`, `CLEANUP_REPORT.md`) | **Cleaned & Consolidated** |
| Test Directory Structure | Root-level scripts | Dedicated `tests/` + root forwarder | **Organized** |
| Documentation Structure | Root-level sprawl | Structured `docs/` & `docs/reports/` | **Organized** |
| Orphan / Obsolete Files | 3 files identified | 0 | **Safely Removed** |
| PHP Syntax Errors | 0 | 0 | **100% Clean** |
| Automated Test Pass Rate | 38 / 38 | 38 / 38 | **100% Pass** |

---

## 3. Duplicate Files & Code Consolidation

### 3.1 Server Management Scripts Consolidation
- **Problem:** `start-both.bat` / `start-both.ps1` (10,093 bytes) was nearly an identical duplicate of `start-dual-servers.bat` / `start-dual-servers.ps1` (9,813 bytes). Similarly, `stop-both.ps1` (3,432 bytes) duplicated `stop-dual-servers.ps1` (3,462 bytes).
- **Resolution:**
  - Kept `start-dual-servers.ps1` and `stop-dual-servers.ps1` as canonical implementations.
  - Converted `start-both.ps1` into a 1-line delegation forwarder: `& "$PSScriptRoot\start-dual-servers.ps1" @args`.
  - Converted `stop-both.ps1` into a 1-line delegation forwarder: `& "$PSScriptRoot\stop-dual-servers.ps1" @args`.
  - Converted `start-both.bat` and `stop-both.bat` into forwarders calling `start-dual-servers.bat` and `stop-dual-servers.bat`.
- **Benefit:** Eliminated 13.5 KB of redundant PowerShell code, maintained a single source of truth, and preserved full backward compatibility for existing user habits.

### 3.2 Category & Fallback Images (Evaluated & Intentionally Preserved)
- **Investigation:** Hash comparison found that images in `assets/images/categories/*.jpg` share MD5 hashes with initial articles in `uploads/news/*.jpg`.
- **Decision:** **Intentionally Preserved.** `assets/images/categories/` serves as permanent static fallback graphics used by `newsImageUrl()` in `includes/functions.php` when articles are published without an uploaded image. Removing them would cause broken images across the portal.

---

## 4. Removed Unnecessary & Obsolete Files

| File Path | Original Purpose | Reason for Removal | Evidence & Verification |
|---|---|---|---|
| `test_routes.php` | Ad-hoc HTTP route tester | Obsolete; hardcoded `http://localhost/SONI_PHP_WEBSITE` port 80. | Replaced by `tests/run_tests.php` Group 5, which handles configurable base URLs, socket probing, and Vercel HTTPS. |
| `uploads/news/news_test_1791178716.jpg` | Temporary test image (56 KB) | Orphan test upload from early development. | Unreferenced in all SQL schema dumps, unreferenced in code, unreferenced in documentation. |
| `assets/vendor/.gitkeep` | Empty folder placeholder | Unused directory. | No packages or libraries reside in `assets/vendor/`; unreferenced project-wide. |

---

## 5. Renamed & Reorganized Files

| Original Path | New Path | Rationale |
|---|---|---|
| `DUAL_LOCALHOST_SETUP.md` | `docs/DUAL_LOCALHOST_SETUP.md` | Core dual-port local operational manual relocated to documentation root. |
| `VERCEL_DEPLOYMENT_GUIDE.md` | `docs/VERCEL_DEPLOYMENT_GUIDE.md` | Serverless deployment guide relocated to documentation root. |
| `VIVA_NOTES.md` | `docs/VIVA_NOTES.md` | Academic defense preparation guide relocated to documentation root. |
| `database_test.php` | `tests/database_test.php` | Diagnostic developer test relocated to dedicated test suite directory. |
| `run_tests.php` | `tests/run_tests.php` | Master automated test suite relocated to dedicated test suite directory. |
| `21 historical audit/QA reports` | `docs/reports/*.md` | Historical audit logs, scroll fixes, and asset audits preserved under `docs/reports/`. |

---

## 6. Database Schema Hardening & Encoding Fix

### 6.1 `database/production_schema.sql`
- **Issue 1:** The file contained double UTF-8 / CP1252 mojibake (`Ã¢â‚¬â€` and `Ã¢â€šÂ¹`).
- **Issue 2:** Line 9 contained a hardcoded `USE test;` statement, contradicting line 6 ("Does NOT execute CREATE DATABASE or USE statements") and potentially causing accidental imports into the `test` schema instead of `news_portal`.
- **Fix Applied:**
  - Removed `USE test;` and replaced with an explicit instruction: `-- Ensure the target database is selected before importing.`
  - Re-encoded all em-dashes (`—`) and rupee symbols (`₹`) into clean UTF-8 matching `database/news_portal.sql`.

---

## 7. Security & Configuration Review

1. **`api/index.php` (Vercel Serverless Gateway):**
   - Verified request path normalization, directory traversal protections (`str_starts_with($realCandidate, $realRoot)`), and custom 404 handling.
2. **`.vercelignore`:**
   - Updated to exclude `docs/`, `tests/`, `scratch/`, `logs/`, server scripts, and local configuration from Vercel deployment bundles.
3. **`robots.txt`:**
   - Updated `Disallow` rules: replaced obsolete `/test_routes.php` and `/database_test.php` with `/tests/`, `/docs/`, and `/run_tests.php`.
4. **`.htaccess`:**
   - Updated `FilesMatch` to remove `test_routes.php` and added Apache `mod_rewrite` rule forbidding direct web browser access to `/tests`, `/docs`, `/config`, `/database`, `/includes`, and `/errors`.
5. **Credentials & Secrets:**
   - Confirmed no database passwords, API keys, or private tokens are hardcoded in tracked files. Production database credentials reside exclusively in Vercel Encrypted Environment Variables.

---

## 8. Verification & Validation Results

### 8.1 PHP Syntax Linting
```text
Total PHP files checked: 59
Errors encountered: 0
Result: 100% PASS (php -l across all project files)
```

### 8.2 Master Verification Suite (`php run_tests.php`)
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
[PASS] SEC-CSRF-01: CSRF Token Generation (64-hex token: e3e8a82c0b...)
[PASS] SEC-CSRF-02: CSRF Token Validation (Valid) (Valid token accepted)
[PASS] SEC-CSRF-03: CSRF Token Validation (Tampered Rejected) (Tampered token rejected)
[PASS] SEC-XSS-01: XSS htmlspecialchars Escaping (HTML tags encoded)
[PASS] SEC-SQLI-01: SQL Injection Isolation (Payload treated as literal string; zero records returned)

--- 4. CRUD INTEGRITY (CONTROLLED TEST ARTICLE) ---
[PASS] CRUD-01 : Category Creation (Created category ID 20)
[PASS] CRUD-02 : News Article Creation (Created article ID 21)
[PASS] CRUD-03 : News Article Read & Attributes (Verified slug and flags)
[PASS] CRUD-04 : News Article Update (Title updated in persistence)
[PASS] CRUD-05 : News Article Cleanup & Deletion (Test record deleted cleanly)

--- 5. LIVE HTTP ROUTES (https://dynamic-news-portal-rose.vercel.app) ---
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

## 9. Conclusion & Maintainability Improvements

The project is now organized into a clean, modern, enterprise-ready PHP application structure:
- **Clean Root:** Reduced root file clutter from 62 to 27 items, making it straightforward to navigate and maintain.
- **Clear Separation of Concerns:** Core application, editorial CMS, database layer, static assets, tests, and documentation are strictly partitioned.
- **Cloud & Serverless Ready:** Vercel deployment configurations and cross-container session management remain verified and operational.
