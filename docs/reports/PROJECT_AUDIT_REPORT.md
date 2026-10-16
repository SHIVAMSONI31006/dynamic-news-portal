# DYNAMIC NEWS PORTAL — MASTER PROJECT AUDIT REPORT

**Project Name:** Dynamic News Portal — Publish News Dynamically  
**Academic Context:** TYBScIT (AI/ML), GLS University  
**Stack:** Native PHP 8.3+, MySQL 8.0, PDO, Bootstrap 5, Vanilla JavaScript, Apache (XAMPP)  
**Inspection Date:** 2026-10-11  
**Project Path:** `C:\Users\dixit\OneDrive\Desktop\COLLEGE_PROJECT\SONI_PHP_WEBSITE`  
**Deployment Path:** `C:\xampp\htdocs\SONI_PHP_WEBSITE`  
**Audit Status:** **AUDITED, HARDENED & VERIFIED**

---

## 1. EXECUTIVE SUMMARY & REPOSITORY BASELINE

The project was audited by inspecting all source files, routing mechanics, database schemas, authentication guards, and live HTTP responses.

Before any modifications were made:
- A baseline Git repository was initialized with commit `287fdd6` ("Baseline: Project checkpoint before audit and enhancements").
- All 51 PHP files were syntax-checked using `php -l` with 0 syntax errors detected.
- Local XAMPP MySQL was confirmed active on port 3306 with database `news_portal`.
- Apache was verified serving the application under `http://localhost/SONI_PHP_WEBSITE/`.

---

## 2. DEFECT IDENTIFICATION & RESOLUTION MATRIX

| Defect ID | Severity | File / Component | Observed Problem | Applied Engineering Fix | Verified Status |
| :--- | :---: | :--- | :--- | :--- | :---: |
| **DEF-01** | **CRITICAL** | `users` table & `news_portal.sql` | Documented seed passwords (`NewsPortal@2026`, `Editor@2026`) failed login because database contained invalid/corrupted Bcrypt hashes. | Recomputed valid standard PHP Bcrypt hashes (`$2y$10$...`), updated the live database, and updated `database/news_portal.sql`. Verified login now succeeds. | **PASS** |
| **DEF-02** | **HIGH** | `config/database.php` | Database credentials were hardcoded to `root` with no password; no support for environment variables or `.env` files for production deployments. | Added native `.env` parser and `env()` helper with fallbacks. Supported `APP_ENV`, `APP_DEBUG`, `APP_URL`, `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASS`. Created `.env.example`. | **PASS** |
| **DEF-03** | **HIGH** | `includes/navbar.php` | Breaking news ticker queried arbitrary published articles (`WHERE status='published'`) rather than respecting `is_breaking = 1`. | Updated query to `WHERE status='published' AND is_breaking=1`. Added pause-on-hover, focus controls, and `aria-live="polite"` accessibility attributes. | **PASS** |
| **DEF-04** | **MEDIUM** | `database_test.php` | Diagnostic script exposed server configuration, table structures, and seed emails publicly without access controls. | Added production guard (`if (APP_ENV === 'production') http_response_code(403); die(...)`) preventing public exposure in production environments. | **PASS** |
| **DEF-05** | **MEDIUM** | `news.php` | `$currentUrl` was urlencoded before passing to `<link rel="canonical">` in header, causing percent-encoded canonical links (`http%3A%2F%2F...`). | Separated unencoded `$rawUrl` for canonical link and copy actions from `$shareUrl` used for social share query strings. | **PASS** |
| **DEF-06** | **MEDIUM** | Root Directory | Missing search engine directives and sitemap files (`robots.txt` and `sitemap.xml`). | Created standards-compliant `robots.txt` disallowing private administrative paths, and created `sitemap.php` generating XML sitemaps dynamically for published articles. | **PASS** |
| **DEF-07** | **LOW** | `admin/newsletter/` | No CSV export option existed for newsletter subscribers. | Created `admin/newsletter/export.php` with admin auth guard and streamed CSV download with UTF-8 BOM. Added "Export CSV" button to index. | **PASS** |
| **DEF-08** | **LOW** | `index.php` | Homepage lacked dedicated editorial category rows (India, Tech/AI, Business/Edu, World/Culture). | Added `getCategoryNews()` helper and rendered responsive editorial category grid rows with responsive cards and clean empty-state guards. | **PASS** |

---

## 3. ARCHITECTURE & ROUTE VERIFICATION

### Public Routes Matrix
| Route | Method | Controller | Purpose | HTTP Status |
| :--- | :---: | :--- | :--- | :---: |
| `/` or `/index.php` | GET | `index.php` | Homepage (Hero, Trending, Latest, Category focus, Newsletter) | **200 OK** |
| `/news.php?slug=...` | GET | `news.php` | Article detail, view count, comments, like button | **200 OK** |
| `/category.php?slug=...` | GET | `category.php` | Category archive with pagination | **200 OK** |
| `/search.php?q=...` | GET | `search.php` | Full-text search and sorting (Latest / Trending) | **200 OK** |
| `/about.php` | GET | `about.php` | About publication & academic context | **200 OK** |
| `/contact.php` | GET/POST | `contact.php` | Public feedback form with MySQL persistence | **200 OK** |
| `/login.php` | GET/POST | `login.php` | User authentication | **200 OK** |
| `/register.php` | GET/POST | `register.php` | Account registration | **200 OK** |
| `/logout.php` | GET | `logout.php` | Session invalidation and redirect | **302 Redirect** |
| `/profile.php` | GET/POST | `profile.php` | User profile, password change & avatar | **302 / 200** |
| `/like.php` | POST | `like.php` | AJAX like toggle (authenticated only) | **302 / JSON** |
| `/submit-comment.php` | POST | `submit-comment.php` | Comment submission with moderation queue | **302 Redirect** |
| `/sitemap.php` | GET | `sitemap.php` | Dynamic XML sitemap for search crawlers | **200 OK** |
| `/robots.txt` | GET | `robots.txt` | Crawler directives & sitemap reference | **200 OK** |

### Administrative Routes Matrix (`admin/`)
| Route | Access Guard | Controller | Functionality | Verified Status |
| :--- | :---: | :--- | :--- | :---: |
| `/admin/login.php` | Public | `admin/login.php` | Dedicated admin sign-in | **PASS (200 OK)** |
| `/admin/index.php` | `admin`, `editor` | `admin/index.php` | Metrics dashboard & statistics | **PASS (302 Guard)** |
| `/admin/news/` | `admin`, `editor` | `admin/news/index.php` | Article management & search | **PASS (302 Guard)** |
| `/admin/news/create.php` | `admin`, `editor` | `admin/news/create.php` | Create article with image upload | **PASS (302 Guard)** |
| `/admin/news/edit.php` | `admin`, `editor` | `admin/news/edit.php` | Edit article & replace image | **PASS (302 Guard)** |
| `/admin/news/delete.php` | `admin` | `admin/news/delete.php` | Delete article & delete image file | **PASS (302 Guard)** |
| `/admin/categories/` | `admin`, `editor` | `admin/categories/index.php`| Category CRUD with slug collision check | **PASS (302 Guard)** |
| `/admin/users/` | `admin` | `admin/users/index.php` | User list, role escalation & deactivation | **PASS (302 Guard)** |
| `/admin/comments/` | `admin`, `editor` | `admin/comments/index.php` | Comment moderation (approve/reject/delete) | **PASS (302 Guard)** |
| `/admin/messages/` | `admin`, `editor` | `admin/messages/index.php` | View visitor inquiries & mark read | **PASS (302 Guard)** |
| `/admin/newsletter/` | `admin` | `admin/newsletter/index.php`| Subscriber list & active toggle | **PASS (302 Guard)** |
| `/admin/newsletter/export.php` | `admin` | `admin/newsletter/export.php` | Stream CSV export of all subscribers | **PASS (302 Guard)** |

---

## 4. VERIFICATION CONCLUSION

The audit confirms that the **Dynamic News Portal** satisfies all functional, architectural, and security requirements of the project brief. All identified defects have been patched, verified, and synchronized to the XAMPP htdocs deployment.
