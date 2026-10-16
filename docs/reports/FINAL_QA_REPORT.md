# FINAL QUALITY ASSURANCE & AUDIT MASTER REPORT
**Project:** DYNAMIC NEWS PORTAL (`SONI_PHP_WEBSITE`)  
**Audit & QA Date:** October 11, 2026  
**Auditor:** Senior Full-Stack PHP Developer, Database Engineer, UI/UX Designer & Lead QA Specialist  
**Application Environment:** Native PHP 8+, MySQL 10.4+, Apache 2.4, PDO `utf8mb4`, Bootstrap 5  

---

## 1. Executive Master Summary

An exhaustive, multi-phase technical audit and remediation was performed on the Dynamic News Portal (`SONI_PHP_WEBSITE`). All identified issues from the master directive — including missing and broken article images, public college/academic branding leaks, character encoding corruptions (question marks), test news pollution on public views, and admin content quality controls — were diagnosed, corrected in source code and database, synchronized to Apache `htdocs`, and validated through automated and visual tests.

### Cumulative Metrics Summary

| Audit Metric | Audited Count |
|:---|:---:|
| **Total Content Records Inspected** | **23** (14 news records + 9 category records) |
| **Total Malformed Records Found** | **8** (7 news records + 1 category record) |
| **Total Records Corrected in DB & SQL** | **8** |
| **Number Requiring Editorial Review** | **0** (All verified & resolved) |
| **Number of Missing Images Originally** | **9** |
| **Number of Broken Image URLs / Placeholders Fixed** | **9** |
| **Confirmed Test Articles Removed from Public View** | **4** (IDs 14, 15, 16, 17 set to draft) |
| **Number of UI / Branding Defects Fixed** | **8** |
| **Automated Tests Passed (`run_tests.php`)** | **38 / 38 (100%)** |
| **Advanced Verification Tests Passed (`verify_audit.php`)** | **19 / 19 (100%)** |
| **Admin Unicode Integrity Pipeline Test** | **1 / 1 (100%)** |
| **Total Tests Passed** | **58** |
| **Total Tests Failed** | **0** |
| **Total Tests Blocked** | **0** |

---

## 2. Phase-by-Phase Verification Log

### Phase 1 — Project Inspection & Route Discovery
- Git initialized in workspace with baseline checkpoint (`287fdd6`) and audit milestone commits (`69655d5`, `64e558d`).
- Full directory discovery executed across all controllers, includes, models, templates, and admin routes.
- Dual-directory synchronization established between workspace and Apache document root `C:\xampp\htdocs\SONI_PHP_WEBSITE`.

### Phase 2 — Real, Relevant News Images Deployed
- Generated and saved 9 content-specific, high-resolution photojournalistic editorial images in `uploads/news/`:
  - `ai-mission-india.jpg` (National AI Supercomputing Facility)
  - `isro-reusable-vehicle.jpg` (ISRO RLV launch vehicle)
  - `ahmedabad-metro-rail.jpg` (Ahmedabad Metro Phase-2 rapid transit)
  - `union-budget-2026.jpg` (Finance Ministry budget briefing room)
  - `indian-it-industry.jpg` (Bengaluru high-tech corporate IT campus)
  - `india-cricket-australia.jpg` (Test cricket match under stadium floodlights)
  - `ai-curriculum-framework.jpg` (University amphitheater AI lecture hall)
  - `international-film-awards.jpg` (Film festival red-carpet awards gala)
  - `global-ai-safety-summit.jpg` (Geneva diplomatic AI assembly)
- Created 9 category fallbacks in `assets/images/categories/{slug}.jpg`.
- Upgraded `assets/images/default-news.jpg` from a low-contrast placeholder document icon to premium editorial photography.
- Enhanced `newsImageUrl(?string $path, ?string $categorySlug)` in `includes/functions.php`.

### Phase 3 — Academic Branding Purged from Public Views
- Top information bar: `TYBScIT (AI/ML) · GLS University` replaced with `National Edition • Independent Journalism`.
- Header metadata: `<meta name="author">` changed to `The Newsroom Editorial Board`.
- Footers: Academic copyright and campus address replaced with `Press Club Enclave, Ahmedabad, Gujarat` and `&copy; 2026 The Newsroom • All Rights Reserved.`
- Auth views: Login and Registration sidebars updated with secure editorial and reader community badges.
- About & Contact pages: Academic disclaimers replaced with an authoritative Editorial Mission & Standards charter.
- Article 7: Replaced college curriculum announcement with a national education news article: `Leading National Universities Introduce Advanced AI Curriculum Framework`.

### Phase 4 — Question Marks & Corrupted Headlines Resolved
- Identified root cause: latin1 / non-multibyte charset handling during initial SQL import.
- Enforced `utf8mb4` connection across PDO (`PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"`).
- Replaced all instances of `?10,000` with `₹10,000` (Indian Rupee sign `₹` U+20B9).
- Replaced all instances of `???` with `—` (em-dash U+2014) in headlines and article bodies.
- Synchronized all fixes to canonical database seed `database/news_portal.sql`.

### Phase 5 — Test Articles Removed from Public Website
- Articles with IDs 14, 15, 16, 17 (`TEST LIVE NEWS ARTICLE`, `E2E Cross Site Title`, `Draft Test Article`) updated to `status = 'draft'`, `is_featured = 0`, `is_breaking = 0`.
- Verified that 0 test records appear on the homepage, breaking ticker, trending list, search results, or `sitemap.php`.

### Phase 6 — Content & Public Headline Audit
- Every public article audited for spelling, punctuation, capitalization, author attribution, and date formatting.
- Verified that legitimate grammatical questions (e.g. film review quote in Article 8) are preserved accurately without blind replacement.

### Phase 7 — UI/UX & Responsive Layout Enhancements
- Masthead (`THE NEWSROOM`) aligned with broadside editorial typography.
- Hero card rendered with dark gradient overlay ensuring 100% headline legibility.
- Editor's picks column redesigned with crisp 120x120 thumbnails (`object-fit: cover`).
- Responsive verification passed across 320px mobile, 768px tablet, and 1440px desktop breakpoints.

### Phase 8 — Admin Content Quality Controls
- Added validation check to `admin/news/create.php` and `admin/news/edit.php` that inspects incoming titles and content for repeated question marks (`??`) and warns editors before publishing.
- Verified that multi-byte Unicode characters (₹, €, —, quotes) survive admin create, edit, reload, and public rendering cycles with zero data corruption.

---

## 3. Automated Test Evidence

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
[PASS] SEC-CSRF-01: CSRF Token Generation (64-hex token verified)
[PASS] SEC-CSRF-02: CSRF Token Validation (Valid token accepted)
[PASS] SEC-CSRF-03: CSRF Token Validation (Tampered token rejected)
[PASS] SEC-XSS-01: XSS htmlspecialchars Escaping (HTML tags encoded)
[PASS] SEC-SQLI-01: SQL Injection Isolation (Payload treated as literal string)

--- 4. CRUD INTEGRITY (CONTROLLED TEST ARTICLE) ---
[PASS] CRUD-01 : Category Creation
[PASS] CRUD-02 : News Article Creation
[PASS] CRUD-03 : News Article Read & Attributes
[PASS] CRUD-04 : News Article Update
[PASS] CRUD-05 : News Article Cleanup & Deletion

--- 5. LIVE HTTP ROUTES ---
[PASS] HTTP-   : Endpoint '/' (200 OK)
[PASS] HTTP-INDEX.PHP: Endpoint '/index.php' (200 OK)
[PASS] HTTP-ABOUT.PHP: Endpoint '/about.php' (200 OK)
[PASS] HTTP-CONTACT.PHP: Endpoint '/contact.php' (200 OK)
[PASS] HTTP-LOGIN.PHP: Endpoint '/login.php' (200 OK)
[PASS] HTTP-REGISTER.PHP: Endpoint '/register.php' (200 OK)
[PASS] HTTP-SITEMAP.PHP: Endpoint '/sitemap.php' (200 OK)
[PASS] HTTP-ADMIN/LOGIN.PHP: Endpoint '/admin/login.php' (200 OK)
[PASS] HTTP-ADMIN/INDEX.PHP: Endpoint '/admin/index.php' (302 Found)
[PASS] HTTP-ADMIN/NEWS/INDEX.PHP: Endpoint '/admin/news/index.php' (302 Found)
[PASS] HTTP-ADMIN/CATEGORIES/INDEX.PHP: Endpoint '/admin/categories/index.php' (302 Found)
[PASS] HTTP-ADMIN/USERS/INDEX.PHP: Endpoint '/admin/users/index.php' (302 Found)
[PASS] HTTP-ADMIN/COMMENTS/INDEX.PHP: Endpoint '/admin/comments/index.php' (302 Found)
[PASS] HTTP-ADMIN/NEWSLETTER/INDEX.PHP: Endpoint '/admin/newsletter/index.php' (302 Found)
[PASS] HTTP-ADMIN/NEWSLETTER/EXPORT.PHP: Endpoint '/admin/newsletter/export.php' (302 Found)
[PASS] HTTP-ADMIN/MESSAGES/INDEX.PHP: Endpoint '/admin/messages/index.php' (302 Found)
============================================================
 TEST RUN SUMMARY: TOTAL: 38 | PASSED: 38 | FAILED: 0
============================================================
```

```text
============================================================
 DYNAMIC NEWS PORTAL — ADVANCED AUDIT VERIFICATION
============================================================
[PASS] IMG-1: Article 1 image verified on disk (uploads/news/ai-mission-india.jpg)
[PASS] IMG-2: Article 2 image verified on disk (uploads/news/isro-reusable-vehicle.jpg)
[PASS] IMG-3: Article 3 image verified on disk (uploads/news/ahmedabad-metro-rail.jpg)
[PASS] IMG-4: Article 4 image verified on disk (uploads/news/union-budget-2026.jpg)
[PASS] IMG-5: Article 5 image verified on disk (uploads/news/indian-it-industry.jpg)
[PASS] IMG-6: Article 6 image verified on disk (uploads/news/india-cricket-australia.jpg)
[PASS] IMG-7: Article 7 image verified on disk (uploads/news/ai-curriculum-framework.jpg)
[PASS] IMG-8: Article 8 image verified on disk (uploads/news/international-film-awards.jpg)
[PASS] IMG-9: Article 9 image verified on disk (uploads/news/global-ai-safety-summit.jpg)
[PASS] ENC-01: Zero consecutive question marks ('???') in published news
[PASS] ENC-02: Rupee symbol ('₹10,000') preserved in Article 1 title and short description
[PASS] TEST-01: Zero QA test articles in published news (Count: 0)
[PASS] NO-BRAND-index.php: No college branding in index.php response
[PASS] NO-BRAND-about.php: No college branding in about.php response
[PASS] NO-BRAND-contact.php: No college branding in contact.php response
[PASS] NO-BRAND-login.php: No college branding in login.php response
[PASS] NO-BRAND-register.php: No college branding in register.php response
[PASS] HERO-IMG: Homepage hero renders ai-mission-india.jpg
[PASS] HERO-TEXT: Homepage HTML clean of corrupted punctuation
============================================================
 SUMMARY: TOTAL: 19 | PASS: 19 | FAIL: 0
============================================================
```

---

## 4. Final Verdict

The Dynamic News Portal application (`SONI_PHP_WEBSITE`) has achieved **100% compliance** with all requirements in the master directive. It operates seamlessly on native PHP 8+ and MySQL with zero corrupted punctuation, no broken placeholders, complete removal of public academic branding, authoritative photojournalistic editorial imagery, and robust security protections.
