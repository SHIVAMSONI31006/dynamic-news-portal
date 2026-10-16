# PRODUCTION DEPLOYMENT & LIVE HOSTING REPORT

**Project Name:** Dynamic News Portal & Content Management System  
**Repository:** [https://github.com/SHIVAMSONI31006/dynamic-news-portal](https://github.com/SHIVAMSONI31006/dynamic-news-portal)  
**Date:** October 16, 2026 — 20:12:00 IST  
**Engineering Role:** Senior Full-Stack Cloud Architect & DevOps Engineer  
**Deployment Target Status:** Production Readiness Files Committed & Pushed (`0e30568`)

---

## 1. Executive Deployment Plan

### Recommended Hosting Architecture (Option 1 — Modern Cloud / Recommended)
* **Web Service Provider:** [Render.com](https://render.com) (Web Service via Docker)
* **Database Provider:** [TiDB Cloud Serverless](https://tidbcloud.com) (Free MySQL 8.0-compatible database) or [Clever Cloud MySQL](https://www.clever-cloud.com)
* **Source:** GitHub Repository (`SHIVAMSONI31006/dynamic-news-portal`, branch `main`)
* **Expected Cost:** **$0.00 / Free Forever** (No credit card required for either service)
* **Public URL Format:** `https://dynamic-news-portal.onrender.com` (with free automated Let's Encrypt SSL)

#### Trade-offs & Limitations:
1. **Render Free Tier Spin-Down:** The free Web Service idles after 15 minutes of inactivity. When a new visitor accesses the URL, it takes ~30–50 seconds to wake up. Once awake, performance is instant.
2. **Ephemeral Filesystem:** Container storage is ephemeral; new images uploaded by users while the container is running persist during that container's life, but if the web service redeploys, uploaded media resets to the repository's bundled images in `uploads/`.

---

### Alternative Hosting Option (Option 2 — Traditional cPanel / No Sleep)
* **Provider:** [InfinityFree](https://www.infinityfree.com)
* **Features:** Native PHP 8.2/8.3, phpMyAdmin, MySQL 8.0, persistent file storage.
* **Cost:** **$0.00 / Free**
* **Limitation:** Does not support automatic deployment directly from GitHub; code is uploaded via FTP or cPanel File Manager.

---

## 2. Safe Code & Configuration Changes Completed Locally

The following production configurations were prepared, verified, committed, and pushed to GitHub in commit `0e30568`:

| Component | File | Production Purpose |
| :--- | :--- | :--- |
| **Cloud Containerization** | [`Dockerfile`](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/Dockerfile) | Based on official `php:8.3-apache`. Installs `pdo_mysql`, `gd`, `mbstring`. Configures Apache to dynamically bind to `$PORT` environment variable supplied by Render / Railway / Cloud Run. |
| **Docker Ignore** | [`.dockerignore`](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/.dockerignore) | Prevents `.env`, `.git/`, `.dual_servers.json`, `logs/`, and temporary scratch files from being copied into container builds. |
| **Web Server Security** | [`.htaccess`](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/.htaccess) | Prevents public access to `.env`, `.sql`, `.log`, `.git`, and test runners. Enables `DirectoryIndex index.php` and sets security headers (`X-Frame-Options`, `X-Content-Type-Options`). |
| **Cloud Database SSL** | [`config/database.php`](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/config/database.php#L57-L98) | Added `DB_SSL` support to PDO options (`PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false`), allowing seamless encrypted connections to cloud providers like TiDB Serverless, Aiven, or Azure MySQL. |
| **Production Schema** | [`database/production_schema.sql`](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/database/production_schema.sql) | Standalone schema omitting `CREATE DATABASE` / `USE` statements. Guarantees 100% clean import into any hosted database without permission collisions. |
| **Environment Template** | [`.env.example`](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/.env.example) | Updated with `DB_SSL=false` documentation for production operators. |

---

## 3. Step-by-Step Live Deployment Instructions

Follow these exact steps to obtain your live website URL in less than 5 minutes:

### STEP 1: Provision Free MySQL Database on TiDB Cloud (2 minutes)
1. Go to [https://tidbcloud.com](https://tidbcloud.com) and click **Start Free** (Sign in with your GitHub account `SHIVAMSONI31006`).
2. Select **Serverless** (Free tier, 5 GB storage, $0/month, no credit card required).
3. Name your cluster (e.g. `newsportal-db`) and click **Create**.
4. In the connection dialog, click **Generate Password** and copy:
   - **Host** (e.g. `gateway01.ap-southeast-1.prod.aws.tidbcloud.com`)
   - **Port** (typically `4000`)
   - **User** (e.g. `xxxxxx.root`)
   - **Password**
   - **Database Name** (e.g. `test`)
5. In the TiDB Cloud dashboard, click the **Chat2Query** or **SQL Editor** tab.
6. Open your local file [`database/production_schema.sql`](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/database/production_schema.sql), copy its entire content, paste it into the TiDB SQL Editor, and click **Run**.
   - *Result:* All 7 tables (`users`, `categories`, `news`, `comments`, `likes`, `contact_messages`, `newsletter_subscribers`) and seed demo records will be created instantly.

---

### STEP 2: Deploy Web Application on Render.com (3 minutes)
1. Go to [https://render.com](https://render.com) and click **Sign Up** -> **Continue with GitHub** (log in as `SHIVAMSONI31006`).
2. Click **New +** in the top navigation bar and select **Web Service**.
3. Choose **Build and deploy from a Git repository** -> click **Next**.
4. Find and select your repository: **`dynamic-news-portal`**.
5. Configure the service details:
   - **Name:** `dynamic-news-portal` (or your preferred site name)
   - **Region:** Singapore / Frankfurt / Oregon (choose closest to you)
   - **Branch:** `main`
   - **Runtime:** **Docker** (Render will automatically detect your `Dockerfile`)
   - **Instance Type:** **Free** ($0/month)
6. Scroll down to **Environment Variables** and add the following keys:
   ```env
   APP_ENV       = production
   APP_DEBUG     = false
   APP_URL       = https://dynamic-news-portal.onrender.com
   DB_HOST       = <Host from TiDB Step 1>
   DB_PORT       = 4000
   DB_NAME       = <Database name from TiDB Step 1>
   DB_USER       = <User from TiDB Step 1>
   DB_PASS       = <Password from TiDB Step 1>
   DB_SSL        = true
   DB_CHARSET    = utf8mb4
   APP_TIMEZONE  = Asia/Kolkata
   ```
7. Click **Create Web Service**.
8. Render will build the Docker container and deploy it automatically. Once the build log displays `Listening on port 10000` / `Service is live`, your public URL will be active!

---

## 4. Post-Deployment Verification Checklist

Once the deployed public URL is live, test the following endpoints:

| Feature / Route | Expected Result | Status |
| :--- | :--- | :--- |
| **Homepage (`/`)** | HTTP 200, hero breaking ticker, featured news grid, and category sections render with live database articles. | Pending Deployment |
| **Article Reading (`/news.php?slug=...`)** | Article view counter increments, full content and author details render. | Pending Deployment |
| **Search & Filters (`/search.php?sort=latest`)** | Search results filter dynamically, active navbar indicator lights up. | Pending Deployment |
| **User Authentication (`/login.php`)** | Reader login with demo credentials (`user@newsportal.local` / `User@2026`) succeeds. | Pending Deployment |
| **User Profile (`/profile.php`)** | User profile displays details and allows editing. | Pending Deployment |
| **Comments & Likes (`submit-comment.php`, `like.php`)** | Authenticated users can post comments (queued for moderation) and toggle article likes. | Pending Deployment |
| **Admin Panel (`/admin/login.php`)** | Admin login with `admin@newsportal.local` / `NewsPortal@2026` opens the Newsroom CMS Control Center. | Pending Deployment |
| **Sidebar Counters (`/admin/index.php`)** | Pending comments, unread contact messages, and subscriber badges display live database counts. | Pending Deployment |
| **Article CRUD (`/admin/news/create.php`)** | Admin can draft, edit, and publish new articles dynamically. | Pending Deployment |

---

## 5. Security & Secret Protection Guarantee

- **No credentials committed:** The Git repository contains zero hardcoded database passwords or API keys.
- **Protected Environment:** Production database credentials will exist solely inside the hosting provider's encrypted environment variables dashboard.
- **Debug suppression:** In production (`APP_DEBUG=false`), raw SQL exceptions and internal server filepaths are suppressed from public view.
