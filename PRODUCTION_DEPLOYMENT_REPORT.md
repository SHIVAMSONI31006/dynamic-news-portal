# PRODUCTION DEPLOYMENT & HOSTING COMPATIBILITY REPORT

**Project:** Dynamic News Portal (`SONI_PHP_WEBSITE`)  
**Deployment Specialist:** Senior Infrastructure & Full-Stack Deployment Engineer  
**Date:** 2026-10-11  
**Architecture:** Native LAMP / WAMP Stack (PHP 8.2+, MySQL 8.0, Apache)

---

## 1. HOSTING COMPATIBILITY & ARCHITECTURE ANALYSIS

The **Dynamic News Portal** is engineered as a native PHP/MySQL web application utilizing:
- Apache `.htaccess` directives for upload directory execution locking.
- Native filesystem image uploads (`uploads/news/`).
- PHP native session handling (`$_SESSION`).
- Persistent MySQL relational database with foreign key constraints.

### Critical Hosting Assessment
1. **Incompatible Hosts:** Static hosts (e.g., standard Vercel, Netlify, GitHub Pages) **cannot** host this application because they run serverless/edge environments that lack persistent local filesystems and native PHP runtimes.
2. **Compatible Production Hosts:**
   - **Conventional PHP Hosting (cPanel / Hostinger / Bluehost / SiteGround / InfinityFree):** 100% native compatibility with zero architecture modification.
   - **Cloud VPS / Containers (DigitalOcean Droplet, AWS Lightsail, Linode, Ubuntu LAMP):** 100% native compatibility.
   - **PaaS with Persistent Volumes (Railway / Render with Docker / Fly.io with mounted volume):** Compatible when configured with a persistent storage mount for `uploads/` and a managed MySQL database instance.

---

## 2. ENVIRONMENT CONFIGURATION ARCHITECTURE

The application now supports flexible configuration via `.env` files and system environment variables while preserving local XAMPP compatibility without breaking existing setups.

### Environment Template (`.env.example`)
```env
# Application Environment: 'development' | 'production' | 'staging'
APP_ENV=production

# Enable detailed error messages in development; set to false in production
APP_DEBUG=false

# Public Base URL of your deployed application
APP_URL=https://your-domain.com

# Database Connection Settings
DB_HOST=your-db-host.com
DB_PORT=3306
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_strong_database_password
DB_CHARSET=utf8mb4

# Application Timezone
APP_TIMEZONE=Asia/Kolkata
```

---

## 3. STEP-BY-STEP PRODUCTION DEPLOYMENT GUIDE

### Option A: Conventional cPanel / Web Host (Recommended)
1. **Export Database:** Export your local MySQL database `news_portal` or use [database/news_portal.sql](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/database/news_portal.sql).
2. **Create Remote Database:** In cPanel MySQL Databases, create a database (e.g., `user_newsportal`), create a user, and grant all privileges.
3. **Import SQL:** In phpMyAdmin on cPanel, import `news_portal.sql`.
4. **Upload Files:** Upload all project files to `public_html/`.
5. **Configure Environment:** Create a `.env` file in the root directory containing the cPanel database credentials.
6. **Set Permissions:** Ensure `uploads/news/` is writable (`chmod 755`).
7. **Verify HTTPS:** Ensure SSL is issued via AutoSSL / Let's Encrypt.

### Option B: Free Hosting Demonstration (InfinityFree / 000webhost alternative)
1. Register for an account on [InfinityFree](https://www.infinityfree.com/) (free PHP & MySQL hosting).
2. Create a free subdomain (e.g. `newsportal-gls.infinityfreeapp.com`).
3. In the control panel, create a MySQL database and import `database/news_portal.sql`.
4. Upload all project files via FTP into `htdocs/`.
5. Add `.env` with the assigned MySQL host, database name, user, and password.
6. Verify live routes over HTTPS.

---

## 4. PERSISTENT STORAGE VS. EPHEMERAL STORAGE

| Component | Local XAMPP | Container / Serverless Platform | Persistent cPanel / VPS |
| :--- | :--- | :--- | :--- |
| **Database** | Persistent MySQL | Requires external managed database (e.g. PlanetScale, Supabase MySQL, Railway DB) | Native persistent MySQL |
| **Image Uploads** | Persistent (`uploads/news/`) | **Ephemeral by default** (lost on redeploy unless mounted volume or AWS S3 is used) | Native persistent filesystem |
| **User Sessions** | Stored in `php/tmp` | Stored in container tmp or Redis | Native PHP session files |

---

## 5. BACKUP & DISASTER RECOVERY PROCEDURES

### Database Backup Command
```bash
# Export production database
mysqldump -h DB_HOST -u DB_USER -p DB_NAME > backup_news_portal_$(date +%Y%m%d_%H%M%S).sql
```

### Media Files Backup
```bash
# Archive all user-uploaded news images
tar -czvf backup_uploads_$(date +%Y%m%d).tar.gz uploads/news/
```

### Rollback Procedure
If an issue occurs after an update:
1. Restore previous code commit via Git: `git checkout <previous-commit-hash>`.
2. Restore database from SQL backup via phpMyAdmin or `mysql -u DB_USER -p DB_NAME < backup.sql`.
3. Check Apache error logs for any diagnostic traces.
