# DATABASE INTEGRITY & APPLICATION SECURITY AUDIT REPORT

**Project:** Dynamic News Portal (`SONI_PHP_WEBSITE`)  
**Auditor:** Senior Application Security & Database Engineer  
**Date:** 2026-10-11  
**Target Environment:** PHP 8.3+, MySQL 8.0, PDO Abstraction Layer

---

## 1. DATABASE SCHEMA & INTEGRITY AUDIT

### A. Connection & Collation
- **PDO Singleton Pattern:** Implemented in `config/database.php` via `connectDB()`, preventing connection leaks by reusing an active PDO instance across a single request cycle.
- **Emulation Disabled:** `PDO::ATTR_EMULATE_PREPARES => false` ensures that MySQL performs genuine server-side statement preparation, preventing emulation-based query manipulation.
- **Strict Error Handling:** `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` throws catchable exceptions on query failure.
- **Encoding:** Explicitly enforces `utf8mb4` character set and `utf8mb4_unicode_ci` collation on both the database definition and connection handshake via `PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"`. Supports international text, Hindi/Gujarati scripts, currency symbols (₹), and emojis.

### B. Relational Integrity & Foreign Keys
The relational structure was verified across all 7 core tables:

```
[users] (id) <----+ (author_id)
                  |
             [news] (id) <----+ (news_id) ----- [comments]
                  |           + (news_id) ----- [likes]
[categories] (id) <+ (category_id)
```

1. **Foreign Key Constraints:**
   - `news.category_id` references `categories.id` (`ON DELETE RESTRICT ON UPDATE CASCADE`). Prevents deleting a category while published articles belong to it.
   - `news.author_id` references `users.id` (`ON DELETE RESTRICT ON UPDATE CASCADE`).
   - `comments.news_id` references `news.id` (`ON DELETE CASCADE`). Deleting an article automatically deletes all attached comments.
   - `comments.user_id` references `users.id` (`ON DELETE CASCADE`).
   - `likes.news_id` references `news.id` (`ON DELETE CASCADE`).
   - `likes.user_id` references `users.id` (`ON DELETE CASCADE`).
2. **Unique Integrity Constraints:**
   - `uk_users_email` on `users(email)` prevents duplicate account registrations.
   - `uk_news_slug` on `news(slug)` guarantees collision-free URLs.
   - `uk_categories_name` and `uk_categories_slug` prevent duplicate category records.
   - `uk_likes_user_news` on `likes(user_id, news_id)` prevents a user from liking an article more than once.
   - `uk_newsletter_email` on `newsletter_subscribers(email)` prevents duplicate subscriptions.
3. **Database Performance Indexing:**
   - `idx_news_status_published` on `news(status, published_at)` optimizes homepage and feed queries.
   - `idx_news_views` on `news(views)` accelerates Trending queries.
   - `idx_news_breaking` on `news(is_breaking)` accelerates ticker lookups.

---

## 2. SECURITY VULNERABILITY AUDIT & MITIGATION

### A. SQL Injection (SQLi)
- **Status:** **IMMUNE (VERIFIED PASS)**
- **Audit Findings:** Every query throughout both public controllers (`index.php`, `news.php`, `category.php`, `search.php`) and admin modules (`admin/news/`, `admin/categories/`, `admin/users/`, `admin/comments/`) uses PDO prepared statements with bound parameters (`:param` or `?`).
- **Penetration Test:** Injection string `' OR '1'='1` submitted to login and search queries was treated as a literal search term, returning 0 records and generating zero database syntax errors.

### B. Password Storage & Authentication
- **Status:** **SECURED (VERIFIED PASS)**
- **Audit Findings:** Passwords use native PHP `password_hash($password, PASSWORD_DEFAULT)` utilizing Bcrypt with auto-generated salts. Login queries retrieve the user by email first, then verify the hash using `password_verify($password, $user['password'])`.
- **Defect Resolved:** Seed accounts in the database were re-hashed with standard Bcrypt algorithms (`$2y$10$...`) so default accounts authenticate cleanly.

### C. Cross-Site Request Forgery (CSRF)
- **Status:** **PROTECTED (VERIFIED PASS)**
- **Audit Findings:** Implemented via `includes/csrf.php`. Every state-changing form (login, registration, comments, likes, article creation, edits, deletions, and newsletter subscription) embeds a 64-character hexadecimal token generated via `bin2hex(random_bytes(32))`.
- **Penetration Test:** Submitting POST requests without a token or with a modified token triggers an immediate rejection.

### D. Cross-Site Scripting (XSS)
- **Status:** **MITIGATED (VERIFIED PASS)**
- **Reflected & Stored XSS Defense:** All output strings are processed through the centralized escaping helper `e()` which enforces `htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- **Rich Content Sanitization:** For news article bodies where formatted text is required, `news.php` enforces strict server-side tag filtering using `strip_tags($content, '<p><br><strong><em><b><i><h2><h3><h4><ul><ol><li><blockquote><a>')`, stripping out `<script>`, `<iframe>`, `<object>`, and `onload`/`onerror` event handlers.
- **Comment Sanitization:** Comments are stripped of all HTML tags prior to database insertion.

### E. File Upload Hardening
- **Status:** **HARDENED (VERIFIED PASS)**
- **Upload Restrictions:**
  1. Image MIME inspection is performed using PHP's fileinfo extension (`finfo_file()`), ensuring that renaming malicious scripts (e.g. `shell.php` to `shell.jpg`) is detected and rejected.
  2. Uploaded filenames are randomized using `uniqid()` and timestamps, preventing path traversal attacks (`../../`).
  3. Execution prevention: The upload folder contains an active `.htaccess` file ([uploads/news/.htaccess](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/uploads/news/.htaccess)) specifying `php_flag engine off` and `AddType text/plain .php`, preventing Apache from executing PHP files in the upload directory.

### F. Privilege Escalation & Self-Lockout Protection
- **Status:** **PROTECTED (VERIFIED PASS)**
- **Audit Findings:**
  1. Role checks are performed server-side (`requireAdmin()`, `requireRole()`). Hiding UI links is supplemented by strict controller guards.
  2. `admin/users/edit.php` and `admin/users/toggle-status.php` include self-lockout guards preventing an administrator from accidentally removing their own admin role or deactivating their own active account.

### G. Diagnostic Tool Exposure Mitigation
- **Status:** **PROTECTED (VERIFIED PASS)**
- **Audit Findings:** `database_test.php` was audited and hardened with an environment guard (`if (APP_ENV === 'production') http_response_code(403); die(...)`) preventing public exposure of diagnostic schemas in production.
