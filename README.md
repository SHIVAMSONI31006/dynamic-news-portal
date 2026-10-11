# Dynamic News Portal

## Project Definition
Publish news dynamically.

## Academic Context
TYBScIT (AI/ML)  
GLS University

## Overview
The Dynamic News Portal is a web-based platform built with PHP and MySQL that allows visitors to browse the latest news articles across multiple categories. It provides administrators and editors with a secure, responsive dashboard to seamlessly create, edit, manage, and publish news content to the public dynamically. The project utilizes a native page-controller architecture and implements security features such as CSRF protection, password hashing, and prepared statements.

## Objectives
- To develop a dynamic, data-driven web application without relying on external frontend or backend frameworks (excluding Bootstrap).
- To provide a functional Content Management System (CMS) tailored specifically for news publishing.
- To demonstrate practical knowledge of CRUD operations, relational databases, user authentication, and web security best practices (e.g., preventing XSS, CSRF, and SQL Injection).

## Features

### Public Portal
* Homepage
* Breaking news
* Featured news
* Latest news
* Trending news
* Category browsing
* Search
* Article details
* Comments
* Likes
* Newsletter subscription
* Contact form

### Authentication
* Registration
* Login
* Logout
* Profile
* Role-based access

### Admin
* Dashboard
* News management
* Category management
* User management
* Comment moderation
* Newsletter management
* Contact message management

## Technology Stack

Frontend:
- HTML5
- CSS3
- Bootstrap 5
- JavaScript

Backend:
- PHP 8+

Database:
- MySQL

Database Access:
- PDO (PHP Data Objects)

Environment:
- XAMPP

## Database Schema
The database consists of the following 7 core tables:

1. **users**: Stores authenticated users, their hashed passwords, and their authorization roles (`admin`, `editor`, `user`).
2. **categories**: Defines the organizational structure for news articles, tracking names and URL slugs.
3. **news**: The core content table storing article metadata, HTML content, status (draft/published/archived), relationships to authors and categories, and visibility flags (breaking/featured).
4. **comments**: Stores user-submitted comments attached to specific news articles, incorporating an approval state for moderation.
5. **likes**: A tracking table managing many-to-many relationships denoting which user liked which news article (enforced uniquely).
6. **contact_messages**: Stores inquiries submitted through the public contact form.
7. **newsletter_subscribers**: Tracks unique email addresses of users requesting future updates.

## Installation Guide

Follow these steps to deploy the project locally using XAMPP:

1. Install XAMPP.
2. Start Apache via the XAMPP Control Panel.
3. Start MySQL via the XAMPP Control Panel.
4. Copy the entire project folder into the XAMPP `htdocs` directory (e.g., `C:\xampp\htdocs\SONI_PHP_WEBSITE`).
5. Open `http://localhost/phpmyadmin` and create a database named `news_portal`.
6. Import the database schema (locate `database/news_portal.sql` and run it via the Import tab).
7. Configure the database connection if necessary by editing `config/database.php` (it defaults to `root` with no password).
8. Open the local project URL (e.g., `http://localhost/SONI_PHP_WEBSITE`) in your browser.

## Demonstration Accounts
When importing `database/news_portal.sql`, the following pre-seeded test accounts are available:
* **Administrator:** `admin@newsportal.local` / `NewsPortal@2026`
* **Editor:** `editor@newsportal.local` / `Editor@2026`
* **Reader:** `user@newsportal.local` / `User@2026`

*Security Note: Change default passwords through the Admin Users panel before any public demonstration.*

## Automated Test Execution
Run the complete automated test suite (38 verification checks covering database connectivity, Bcrypt verification, CSRF, XSS escaping, SQL injection isolation, transactional CRUD, and live HTTP routes):
```bash
php -f run_tests.php
```

## Security & Architectural Reports
Detailed documentation and quality assurance audits are available in the project root:
* [PROJECT_AUDIT_REPORT.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/PROJECT_AUDIT_REPORT.md) — Comprehensive code and route audit.
* [UI_UX_IMPROVEMENTS.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/UI_UX_IMPROVEMENTS.md) — Editorial UI/UX transformation & viewport tests.
* [DATABASE_AND_SECURITY_AUDIT.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/DATABASE_AND_SECURITY_AUDIT.md) — Schema integrity, PDO binding, and security controls.
* [PRODUCTION_DEPLOYMENT_REPORT.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/PRODUCTION_DEPLOYMENT_REPORT.md) — Deployment instructions, persistent storage, and .env configuration.
* [TEST_RESULTS.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/TEST_RESULTS.md) — Automated & manual test pass logs (38/38 PASS).
* [VIVA_NOTES.md](file:///c:/Users/dixit/OneDrive/Desktop/COLLEGE_PROJECT/SONI_PHP_WEBSITE/VIVA_NOTES.md) — 30 viva questions and concise answers for college defense.

## Project Structure
```text
Dynamic News Portal/
│
├── admin/                 # Restricted dashboard panel
│   ├── categories/        # Category management (CRUD)
│   ├── comments/          # Comment moderation queue
│   ├── includes/          # Admin headers, sidebars, footers
│   ├── messages/          # Contact inquiries viewer
│   ├── newsletter/        # Newsletter subscribers & CSV export
│   ├── news/              # News article management & image upload
│   ├── users/             # User management & role assignment
│   ├── index.php          # Admin metrics dashboard
│   ├── login.php          # Admin login controller
│   └── logout.php         # Admin logout
│
├── assets/                # Static frontend assets
│   ├── css/               # Bootstrap 5 & editorial styles (style.css)
│   ├── js/                # Scripts & Bootstrap bundle
│   └── images/            # Default fallback images & logos
│
├── config/                # Environment configuration
│   └── database.php       # Centralized PDO connection & .env loader
│
├── database/              # Schema and test structures
│   └── news_portal.sql    # Complete MySQL schema & seed data
│
├── errors/                # Global generic error pages (403, 404)
│
├── includes/              # Shared utilities, auth guards, headers/footers
│   ├── auth.php           # RBAC authorization middleware
│   ├── csrf.php           # CSRF token generator & validator
│   ├── functions.php      # Reusable helpers & output escaper
│   ├── header.php         # HTML document head & Open Graph tags
│   ├── navbar.php         # Masthead, navigation & breaking ticker
│   └── footer.php         # Public footer & navigation links
│
├── uploads/               # Dynamic binary storage
│   └── news/              # Uploaded images (protected by .htaccess)
│
├── .env.example           # Production environment configuration template
├── robots.txt             # Search crawler directives
├── sitemap.php            # Dynamic XML sitemap generator
├── index.php              # Public homepage
├── news.php               # Article read page with comments & likes
├── category.php           # Category archive with pagination
├── search.php             # Search engine & sorting controller
├── about.php              # About page
├── contact.php            # Contact form
├── login.php              # User login
├── register.php           # User registration
├── logout.php             # User logout
├── profile.php            # User profile management
├── run_tests.php          # Automated test suite (38 checks)
├── VIVA_NOTES.md          # College viva defense prep (30 Q&As)
└── README.md              # Project documentation
```
