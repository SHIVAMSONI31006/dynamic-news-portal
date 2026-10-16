# Dynamic News Portal — Project Architecture & Directory Structure

This document provides a comprehensive structural and architectural guide to the **Dynamic News Portal & Content Management System** codebase.

---

## 1. Directory Tree Overview

```text
SONI_PHP_WEBSITE/
├── .env.example              # Environment variables template for local & cloud
├── .gitignore                # Git exclusions (local PID, logs, secrets, caches)
├── .htaccess                 # Apache security, MIME headers & folder protection
├── .vercelignore             # Vercel serverless deployment build exclusions
├── Dockerfile                # Optional containerized runtime (Render / Cloud Run)
├── .dockerignore             # Docker build layer exclusions
├── README.md                 # Primary project documentation & operational guide
├── PROJECT_STRUCTURE.md      # Architecture & directory guide (this document)
├── CLEANUP_REPORT.md         # Complete project audit & cleanup report
├── vercel.json               # Vercel Serverless routing & PHP runtime configuration
├── robots.txt                # Search engine crawler policies
├── sitemap.php               # Dynamic XML sitemap generator
│
├── api/                      # Vercel Serverless Function Dispatcher
│   └── index.php             # Request normalization & script execution router
│
├── admin/                    # Editorial & Management Portal (CMS)
│   ├── index.php             # Admin dashboard & analytics overview
│   ├── login.php             # Admin privileged authentication
│   ├── logout.php            # Secure admin session termination
│   ├── categories/           # Category management (CRUD)
│   ├── comments/             # Comment moderation & audit
│   ├── includes/             # Admin-only templates (header, sidebar, footer)
│   ├── messages/             # Contact inquiry inbox & reader communication
│   ├── news/                 # Article authoring, editing & publishing
│   ├── newsletter/           # Subscriber list management & CSV export
│   └── users/                # User & staff role management (admin/editor/user)
│
├── assets/                   # Public Web Static Assets
│   ├── css/                  # Compiled stylesheets (style.css, admin.css, bootstrap.min.css)
│   ├── js/                   # Client scripts (main.js, admin.js, auth.js, bootstrap.bundle)
│   └── images/               # Core brand graphics & category fallbacks
│
├── config/                   # Configuration & Connection Layer
│   └── database.php          # PDO connection factory, SSL/TLS, and cloud TiDB support
│
├── database/                 # Database Schemas & Migrations
│   ├── news_portal.sql       # Local development schema & seed data (with CREATE DB)
│   └── production_schema.sql # Cloud production schema & seed data (portable UTF-8)
│
├── docs/                     # Project Documentation & Architecture Guides
│   ├── DUAL_LOCALHOST_SETUP.md # Dual local server operations guide (8000 & 9000)
│   ├── VERCEL_DEPLOYMENT_GUIDE.md # Cloud serverless deployment guide
│   ├── VIVA_NOTES.md         # Academic viva defense prep (30 technical Q&As)
│   └── reports/              # Historical QA, audit & transformation reports (21 reports)
│
├── errors/                   # HTTP Error Handlers
│   ├── 403.php               # Forbidden access template
│   └── 404.php               # Page not found template
│
├── includes/                 # Shared Public Application Components
│   ├── auth.php              # Session handling, database session handler, auth guards
│   ├── csrf.php              # Anti-CSRF token generation & validation
│   ├── footer.php            # Public site footer template
│   ├── functions.php         # Global utilities (url, sanitize, flash messages, images)
│   ├── header.php            # Public site header template
│   └── navbar.php            # Public navigation bar with dynamic categories
│
├── tests/                    # Automated Test Suites & Verification
│   ├── run_tests.php         # Master test suite (DB, Auth, CSRF, XSS, CRUD, Routes)
│   └── database_test.php     # Diagnostic schema inspector (dev-only protected)
│
├── uploads/                  # User & Article Media Uploads
│   └── news/                 # Published article editorial imagery (guarded by .htaccess)
│
├── run_tests.php             # Root forwarder delegating to tests/run_tests.php
├── router_public.php         # Router for PHP dev server on port 8000
├── router_admin.php          # Router for PHP dev server on port 9000
├── start-dual-servers.bat    # Windows launcher for dual servers (8000 & 9000)
├── start-dual-servers.ps1    # PowerShell core script for dual servers
├── stop-dual-servers.bat     # Windows launcher to stop dual servers
├── stop-dual-servers.ps1     # PowerShell script to gracefully stop dual servers
├── start-both.bat            # Quick alias forwarding to start-dual-servers.ps1
├── stop-both.bat             # Quick alias forwarding to stop-dual-servers.ps1
│
├── index.php                 # Public Homepage (Breaking news, hero grid, categories)
├── about.php                 # Editorial About Us page & team roster
├── category.php              # Category news archive & filtered feeds
├── contact.php               # Contact Us form & inquiry submission
├── like.php                  # AJAX article like toggler
├── login.php                 # Public user login
├── logout.php                # Public session termination
├── news.php                  # Single article view & reader comments
├── profile.php               # Registered user profile & activity
├── register.php              # Public user account registration
├── search.php                # Real-time search with pagination
├── submit-comment.php        # Comment submission endpoint
└── subscribe.php             # Newsletter AJAX subscriber endpoint
```

---

## 2. Component Responsibilities & Boundaries

### 2.1 Public Website Entry Points
- **Homepage (`index.php`)**: Queries breaking news tickers, hero articles, editor's picks, latest articles, and trending sidebars with category badges and view counters.
- **Article Details (`news.php`)**: Renders full article content, author credits, published timestamp, like counter, and verified user comments with submission form.
- **Category Archive (`category.php`)**: Dynamically resolves categories by slug (e.g., `ai-ml`, `technology`, `business`) and paginates matching stories.
- **Search System (`search.php`)**: Full-text and keyword query engine with pagination, sorting, and sanitized query escaping.
- **User Authentication (`login.php`, `register.php`, `logout.php`, `profile.php`)**: End-user registration, secure password hashing, session state, and saved reading profile.

### 2.2 Editorial & Management Portal (`admin/`)
- **Dashboard (`admin/index.php`)**: High-level metrics (total articles, views, published count, pending comments, inbox inquiries, active subscribers).
- **Article Management (`admin/news/`)**: Full editorial lifecycle (Create, Read, Update, Delete, Draft, and Instant Publish toggle).
- **Taxonomy Management (`admin/categories/`)**: Category creation, slug generation, descriptions, and activation states.
- **Comment Moderation (`admin/comments/`)**: Approval and deletion workflow to prevent spam and toxic submissions.
- **Contact Desk (`admin/messages/`)**: Reader inquiry tracking, unread badges, and detailed view modal.
- **Newsletter (`admin/newsletter/`)**: Subscriber list management, status toggle, and one-click CSV export.
- **User Administration (`admin/users/`)**: Role assignment (`admin`, `editor`, `user`), status toggles, and staff account creation.

### 2.3 Shared Code & Architecture Layers
- **Database Layer (`config/database.php`)**: Implements `connectDB()` returning a shared PDO instance configured with `PDO::ERRMODE_EXCEPTION`, `PDO::FETCH_ASSOC`, UTF-8 charset, and SSL certificates for cloud TiDB / MySQL gateways.
- **Security & Session Layer (`includes/auth.php`)**: Manages role authorization (`requireAdmin()`, `requireEditor()`, `requireLogin()`), secure session cookie flags (`HttpOnly`, `SameSite=Lax`, `Secure`), and a database-backed MySQL session handler (`PdoSessionHandler`) ensuring persistent logins across stateless Vercel Serverless lambda containers.
- **CSRF Protection (`includes/csrf.php`)**: Issues cryptographically secure 64-hex anti-CSRF tokens verified with `hash_equals()`.
- **Global Helper Utilities (`includes/functions.php`)**: Provides URL resolution (`url()`, `buildUrl()`), XSS sanitization (`e()`), flash messages, relative time formatting, reading time calculation, and image fallback resolution.

### 2.4 Serverless Deployment Architecture (`api/index.php`, `vercel.json`)
- **Vercel Functions**: Configured via `vercel.json` targeting `vercel-php@0.9.0` runtime.
- **Request Dispatcher (`api/index.php`)**: Serves as the central gateway on Vercel. Normalizes incoming paths, enforces project root working directory, dynamically routes to public scripts or `admin/` scripts, and presents a customized 404 page for nonexistent routes.
- **Direct Static Serving**: `/assets/(.*)` and `/uploads/(.*)` are served directly at the CDN edge by Vercel's edge network for optimal performance.

### 2.5 Testing & Quality Assurance (`tests/`)
- **Automated Verification Suite (`tests/run_tests.php`)**: Master test suite running 38 comprehensive checks across 5 groups (Database, Auth/Bcrypt, Security/CSRF/XSS/SQLi, CRUD Transactions, and Live HTTP Endpoints).
- **Root Forwarder (`run_tests.php`)**: Allows developers to run `php run_tests.php` or `php tests/run_tests.php` interchangeably.
- **Diagnostic Tool (`tests/database_test.php`)**: Development-only HTML visual schema inspector guarded by `APP_ENV === 'production'` controls.

### 2.6 Local Dual-Server Architecture
- **Public Website (Port 8000)**: Serves public newsreaders using `router_public.php`.
- **Admin Management Portal (Port 9000)**: Serves editors and administrators using `router_admin.php`.
- **Unified Control**: Managed via `start-dual-servers.bat` and `stop-dual-servers.bat`.
