# Dual Localhost Architecture & Deployment Guide
**Project:** SONI_PHP_WEBSITE — Dynamic News Portal & Admin CMS  
**Architecture:** Dual Concurrent Localhost PHP 8+ Development Servers  

---

## 1. Executive Summary & Site Endpoints

The project is configured to run **two separate, independent localhost development servers** simultaneously from a unified, high-performance codebase sharing the same MySQL database:

| Application | Localhost URL | Primary Purpose | Session Cookie Name | Document Root / Router |
| :--- | :--- | :--- | :--- | :--- |
| **Website 1 (Public Portal)** | [http://localhost:8000](http://localhost:8000) | Public News Website, Categories, Search, Detail, User Profile | `NEWS_PUBLIC_SESSION` | Project Root + `router_public.php` |
| **Website 2 (Admin CMS)** | [http://localhost:9000](http://localhost:9000) | Editorial News CMS, Category & Comment Moderation, User Control | `NEWS_ADMIN_SESSION` | Project Root + `router_admin.php` |

---

## 2. Startup Instructions

### Method A: One-Click Windows Batch Launcher (Recommended)
Double-click `start-dual-servers.bat` located in the project root:
```bat
start-dual-servers.bat
```
This automatically initiates PowerShell with `-ExecutionPolicy Bypass`, verifies prerequisites, launches both servers in the background, checks socket connectivity, and automatically opens default browser tabs for both websites.

### Method B: PowerShell Command Line
From PowerShell inside `C:\Users\dixit\OneDrive\Desktop\COLLEGE_PROJECT\SONI_PHP_WEBSITE`:
```powershell
.\start-dual-servers.ps1
```
*Note: To start without automatically opening browser tabs, use the `-NoBrowser` flag:*
```powershell
.\start-dual-servers.ps1 -NoBrowser
```

### What the Startup Script Does Automatically:
1. **Verifies PHP Runtime:** Confirms PHP 8+ is available on PATH (detects version, CLI binary path).
2. **Verifies Project Layout:** Validates presence of `router_public.php`, `router_admin.php`, and initializes `logs/`.
3. **Inspects Port Availability:** Verifies ports `8000` and `9000` are free; if an external process occupies a port, reports the process name and PID without terminating unrelated software.
4. **Starts Detached Background Servers:**
   - **Port 8000:** `php -S 127.0.0.1:8000 -t . router_public.php`
   - **Port 9000:** `php -S 127.0.0.1:9000 -t . router_admin.php`
5. **Performs Socket Health Checks:** Tests active TCP listening sockets on both ports.
6. **Persists Process IDs:** Writes server metadata and PIDs into `.dual_servers.json`.
7. **Launches Default Browser:** Opens `http://localhost:8000` and `http://localhost:9000`.

---

## 3. Shutdown Instructions

### Method A: One-Click Windows Batch Launcher
Double-click `stop-dual-servers.bat` located in the project root:
```bat
stop-dual-servers.bat
```

### Method B: PowerShell Command Line
From PowerShell inside the project root:
```powershell
.\stop-dual-servers.ps1
```

### Targeted Process Safety:
- **No Indiscriminate Kills:** The shutdown script reads `.dual_servers.json` and terminates **strictly** the PHP server processes started by this project.
- **Port Fallback Verification:** If the PID file was deleted or corrupted, it checks listening connections on ports `8000` and `9000`, confirms the process name is `php`, and terminates only those specific processes.
- Unrelated PHP processes (e.g., XAMPP Apache, php-cgi, background tasks) are never touched.

---

## 4. Technical Requirements & Environment

- **PHP Version:** PHP 8.2 or 8.3+ (Verified on PHP 8.3.33 Visual C++ 2019 x64).
  - Required Extensions: `pdo_mysql`, `curl`, `mbstring`, `openssl`, `fileinfo`, `gd`.
- **Database Engine:** MySQL 8.0+ / MariaDB 10.4+ (Default: `127.0.0.1:3306`).
- **Operating System:** Windows 10 / 11 (PowerShell 5.1+ or PowerShell 7+).

---

## 5. Database Configuration & Shared Publishing

Both applications share the exact same MySQL database instance defined in `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'news_portal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');
define('DB_CHARSET', 'utf8mb4');
```

### Verified Publishing Workflow:
1. **Creation:** An administrator signs into `http://localhost:9000/admin/login.php` and creates a news article via `admin/news/create.php`.
2. **Persistence:** The article record is stored in MySQL `news_portal.news` with `status = 'published'` and an associated category.
3. **Immediate Public Availability:** Visiting `http://localhost:8000/index.php` instantly renders the new article in homepage breaking news / latest news grids.
4. **Article Reading:** Visiting `http://localhost:8000/news.php?slug=<slug>` loads full article details, category pills, and full-resolution hero images.
5. **Live Updates:** Editing the article on `http://localhost:9000/admin/news/edit.php?id=<id>` immediately updates title, summary, and content on port 8000.
6. **Unpublishing:** Changing status to `draft` removes the article from all public listings and returns HTTP 404 to unauthenticated public visitors attempting to view draft URLs.

---

## 6. Session & Authentication Isolation Architecture

Because both servers run under the `localhost` domain, standard browser cookies would collide if identical cookie names were used. To guarantee complete session isolation:

### Implementation in `includes/auth.php`:
```php
$port = (string)($_SERVER['SERVER_PORT'] ?? '');
$script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
$uri = (string)($_SERVER['REQUEST_URI'] ?? '');

$isAdmin = ($port === '9000') || str_contains($script, '/admin') || str_contains($uri, '/admin');
$sessionName = $isAdmin ? 'NEWS_ADMIN_SESSION' : 'NEWS_PUBLIC_SESSION';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_name($sessionName);
session_start();
```

### Security Guarantees:
- **Zero Collision:** Public visitors receiving `NEWS_PUBLIC_SESSION` cannot forge administrative access.
- **Independent Expiry & Logout:** Logging out of the admin panel on port 9000 destroys only `NEWS_ADMIN_SESSION`. Public readers logged into user accounts on port 8000 remain undisturbed.
- **Session Fixation Defense:** `loginUser()` triggers `session_regenerate_id(true)` immediately upon credential verification.
- **Role Guards:** `requireAdmin()` strictly guards every administrative controller (`admin/news/*`, `admin/categories/*`, `admin/users/*`, `admin/comments/*`, `admin/messages/*`, `admin/newsletter/*`).

---

## 7. URL Routing & Navigation Engine

All application links, asset URLs, and redirection targets are dynamically resolved through `url()` in `includes/functions.php`:

1. **Port 8000 (Public Portal):**
   - Public pages (`index.php`, `news.php`, `category.php`, `search.php`) resolve to `http://localhost:8000/<page>`.
   - Asset URLs (`assets/...`, `uploads/...`) resolve to `http://localhost:8000/<asset>`.
   - Administrative links navigate to `http://localhost:9000/admin/<path>`.
2. **Port 9000 (Admin CMS):**
   - Admin routes resolve to `http://localhost:9000/admin/<path>`.
   - Asset URLs resolve to `http://localhost:9000/<asset>`.
   - "View Website" link in sidebar resolves to `http://localhost:8000/index.php`.
3. **Cross-Port Protection in `router_public.php`:**
   - Any direct HTTP request to `/admin/*` on port 8000 is automatically redirected with HTTP 302 to `http://localhost:9000/admin/*`.
4. **Apache / XAMPP Backward Compatibility:**
   - When run under standard Apache (`http://localhost/SONI_PHP_WEBSITE`), the routing engine seamlessly falls back to relative project paths without configuration changes.

---

## 8. Verification & Acceptance Test Results

All verification tests executed against the live dual server instances passed with 100% compliance:

```
============================================================
 DUAL LOCALHOST SERVERS VERIFICATION SUITE
============================================================
 [PASS] Public Server (port 8000) responds with HTTP 200
        Status code: 200, Body length: 54,867 bytes
 [PASS] Public Server sets NEWS_PUBLIC_SESSION cookie
        Headers: Set-Cookie: NEWS_PUBLIC_SESSION=...
 [PASS] Public Homepage displays actual news portal content
        Found brand and navigation structure in HTML
 [PASS] Admin Server (port 9000 root) redirects to /admin/index.php
        Status: 302, Location: /admin/index.php
 [PASS] Unauthenticated admin dashboard request redirects to /admin/login.php
        Status: 302, Location: http://localhost:9000/admin/login.php
 [PASS] Admin Login page loads with HTTP 200
        Status: 200, Contains 'Admin Login'
 [PASS] Admin Login sets NEWS_ADMIN_SESSION cookie
        Headers: Set-Cookie: NEWS_ADMIN_SESSION=...
 [PASS] Static CSS loads on Port 8000
        Status: 200, Size: 25,060 bytes
 [PASS] Static CSS loads on Port 9000
        Status: 200, Size: 25,060 bytes
 [PASS] Accessing /admin on Port 8000 redirects to Port 9000
        Status: 302, Location: http://localhost:9000/admin/index.php
------------------------------------------------------------
 RESULTS: 10 / 10 tests passed (100%)
============================================================

============================================================
 PUBLISHING WORKFLOW VERIFICATION (PORT 9000 -> PORT 8000)
============================================================
 [PASS] Step 1: Administrator Authentication on Port 9000
 [PASS] Step 2: Extract Form CSRF Token
 [PASS] Step 3: Publish Article to MySQL (ID: 25)
 [PASS] Step 4: Verify Article Appears on Public Homepage (Port 8000)
 [PASS] Step 5: Verify Full Article Detail and Image Rendering
 [PASS] Step 6: Edit Article via Admin CMS and Verify Real-time Public Update
 [PASS] Step 7: Unpublish Article to Draft; Verify 404 Access Guard for Visitors
 [PASS] Step 8: Clean Database Integrity Maintained
============================================================
 WORKFLOW VERIFIED 100% SUCCESSFUL
============================================================
```

---

## 9. Default Administrative Credentials

For immediate evaluation and login at [http://localhost:9000/admin/login.php](http://localhost:9000/admin/login.php):

| Role | Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@newsportal.local` | `NewsPortal@2026` |
| **Editor** | `editor@newsportal.local` | `Editor@2026` |
| **Registered User** | `user@newsportal.local` | `User@2026` |

---

## 10. Known Considerations & Architecture Notes

1. **MySQL Daemon Required:** Both servers require the local MySQL service to be running on port 3306 (e.g. via XAMPP Control Panel).
2. **Single-Threaded PHP Built-in Server:** The PHP CLI built-in web server (`php -S`) processes requests synchronously per port. This is standard for local development and academic evaluation. For production workloads, deploy behind Nginx or Apache.
3. **Log Monitoring:** Real-time request and error output for both servers is continuously logged to `logs/server_public.log` and `logs/server_admin.log`.
