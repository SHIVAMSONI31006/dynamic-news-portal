# Dynamic News Portal & Content Management System

## Project Definition
Publish news dynamically.

## Academic Context
TYBScIT (AI/ML)  
GLS University

---

## Live Deployment & Verified URLs

- **Public News Website:** [https://dynamic-news-portal-rose.vercel.app](https://dynamic-news-portal-rose.vercel.app)
- **Editorial Management Portal (CMS):** [https://dynamic-news-portal-rose.vercel.app/admin/login.php](https://dynamic-news-portal-rose.vercel.app/admin/login.php)
- **Deployment Platform:** Vercel Serverless (`vercel-php@0.9.0`)
- **Production Database:** TiDB Cloud Serverless MySQL (Encrypted TLS/SSL)

---

## Overview
The Dynamic News Portal is a high-performance web platform built with PHP 8.3, MySQL, and modern Vanilla CSS / Bootstrap 5. It allows readers to browse real-time breaking news, trending articles, and category-filtered stories, while providing editors and administrators with a restricted SaaS-style management portal to author, curate, moderate comments, and publish news content dynamically.

The application utilizes a clean page-controller architecture and incorporates defense-in-depth security:
- Anti-CSRF protection via 64-hex tokens and `hash_equals()`
- Password hashing using standard Bcrypt (`PASSWORD_DEFAULT`)
- SQL injection immunity through 100% PDO prepared statements
- Context-aware XSS escaping (`htmlspecialchars()`)
- Database-backed cross-container session management for stateless serverless environments

---

## Features

### Public Portal
* **Breaking News Ticker:** Dynamic marquee highlighting critical real-time alerts.
* **Hero Editorial Grid:** Prominent featured stories with category metadata.
* **Latest & Trending News:** Automated view-counting sidebar and latest publication feeds.
* **Taxonomy Browsing:** Dynamic category-specific archives (e.g., AI & ML, Technology, Gujarat, India, Business, Sports).
* **Live Search:** Fast keyword search engine with pagination and query sanitization.
* **Interactive Article Reader:** Full article views, reading time calculations, author credits, and reader comments.
* **Engagement Engine:** AJAX article like toggler and newsletter subscription.
* **Reader Inquiries:** Contact form with database storage and administrative inbox notification.

### Authentication & Authorization
* Role-based access control (RBAC): `admin`, `editor`, and `user` roles.
* Public user registration, secure login, profile management, and logout.
* Administrative session guards (`requireAdmin()`, `requireEditor()`) protecting management routes.
* Secure cookie attributes (`HttpOnly`, `SameSite=Lax`, `Secure`).

### Editorial CMS (`admin/`)
* **Analytics Dashboard:** Article counts, total views, active categories, pending comments, inbox inquiries, and subscribers.
* **Article Publishing:** Rich article authoring, editing, status toggling (draft/published), breaking/featured flags, and image upload with `.htaccess` execution locks.
* **Category Management:** Full CRUD operations and live slug generation.
* **User & Staff Administration:** Role management, account status activation, and staff creation.
* **Comment Moderation:** Editorial approval and deletion queues.
* **Subscriber Management:** Email list viewing, activation toggling, and one-click CSV export.
* **Contact Inbox:** Real-time reader message viewing, unread flags, and reply tracking.

---

## Technology Stack

- **Backend:** PHP 8.3+ (Native Page-Controller Architecture, PDO)
- **Frontend:** HTML5, CSS3 (Modern SaaS design tokens), Bootstrap 5.3, JavaScript (ES6+)
- **Database:** MySQL 8.0+ / TiDB Cloud Serverless MySQL
- **Web Servers:** Vercel Serverless (Production), Dual PHP CLI built-in servers (Local Development), Apache/XAMPP compatible

---

## Database Schema
The database consists of 7 normalized relational tables:
1. `users`: Authenticated user credentials, bcrypt password hashes, and authorization roles (`admin`, `editor`, `user`).
2. `categories`: News article categories, descriptions, and URL slugs.
3. `news`: Core article table storing titles, slugs, summaries, content, timestamps, view counts, and category/author foreign keys.
4. `comments`: Reader comments attached to news articles with an approval state for moderation.
5. `likes`: Unique user-to-article like tracking table enforcing one like per user per article.
6. `contact_messages`: Reader inquiries submitted through the contact desk.
7. `newsletter_subscribers`: Verified subscriber email registry.

---

## Local Setup & Development

### Option A: Dual-Port Local Server (Recommended)
This runs the public portal on port 8000 and the admin portal on port 9000:
1. Double-click `start-dual-servers.bat` (or run `./start-dual-servers.ps1`).
2. Access the public portal: [http://localhost:8000](http://localhost:8000)
3. Access the admin portal: [http://localhost:9000/login.php](http://localhost:9000/login.php)
4. To stop the servers, double-click `stop-dual-servers.bat`.

### Option B: Apache / XAMPP
1. Copy the project into your web root (e.g., `C:/xampp/htdocs/SONI_PHP_WEBSITE`).
2. Start Apache and MySQL in XAMPP.
3. Open `http://localhost/phpmyadmin` and import `database/news_portal.sql`.
4. Browse to [http://localhost/SONI_PHP_WEBSITE](http://localhost/SONI_PHP_WEBSITE).

---

## Environment Variables

For cloud or custom deployments, configure the following variables in `.env` or your hosting dashboard:

| Variable | Description | Example / Default |
|---|---|---|
| `DB_HOST` | Database host | `gateway01.ap-northeast-1.prod.aws.tidbcloud.com` |
| `DB_PORT` | Database port | `4000` (TiDB) or `3306` (Local MySQL) |
| `DB_NAME` | Database schema name | `news_portal` |
| `DB_USER` | Database username | `root` |
| `DB_PASS` | Database password | `********` |
| `DB_SSL` | Enable TLS/SSL connection | `true` (Cloud) or `false` (Local) |
| `APP_ENV` | Environment mode | `production` or `development` |
| `APP_URL` | Public application URL | `https://dynamic-news-portal-rose.vercel.app` |

---

## Demonstration Accounts

Importing `database/news_portal.sql` or `database/production_schema.sql` seeds the following test accounts:
* **Administrator:** `admin@newsportal.local` / `NewsPortal@2026`
* **Editor:** `editor@newsportal.local` / `Editor@2026`
* **Reader:** `user@newsportal.local` / `User@2026`

*Security Note: Default passwords should be updated before public demonstrations.*

---

## Automated Test Suite

Run the full automated test suite (38 checks covering database connection, schema tables, bcrypt auth, CSRF, XSS escaping, SQL injection isolation, transactional CRUD, and live HTTP routes):

```bash
php run_tests.php
```

To test against a specific deployment (such as Vercel production):
```bash
TEST_BASE_URL="https://dynamic-news-portal-rose.vercel.app" php run_tests.php
```

---

## Project Documentation & Architecture Guides

* [PROJECT_STRUCTURE.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/PROJECT_STRUCTURE.md) — Comprehensive directory tree & architectural guide.
* [CLEANUP_REPORT.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/CLEANUP_REPORT.md) — Master cleanup, audit, duplicate removal, and validation report.
* [docs/VIVA_NOTES.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/docs/VIVA_NOTES.md) — 30 viva questions and concise answers for academic project defense.
* [docs/VERCEL_DEPLOYMENT_GUIDE.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/docs/VERCEL_DEPLOYMENT_GUIDE.md) — Production serverless deployment & TiDB Cloud manual.
* [docs/DUAL_LOCALHOST_SETUP.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/docs/DUAL_LOCALHOST_SETUP.md) — Dual local server operations manual (ports 8000 and 9000).
* [docs/reports/](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/docs/reports/) — Historical audit, UI/UX transformation, QA, and security logs.
