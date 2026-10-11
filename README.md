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

## Admin Access
Admin credentials are created initially during setup or via the database seeder. You can login using the designated admin email and password at the `/admin/login.php` route. 
*Note: Change the default development password through the Admin Users panel before any public demonstration or submission.*

## Security Implementations
The following mechanisms secure the application against common web vulnerabilities:
* **PDO Prepared Statements**: Parametrized queries are enforced globally to prevent SQL injection.
* **Password Hashing**: `password_hash()` and `password_verify()` utilizing Bcrypt safeguard user credentials.
* **CSRF Protection**: All state-changing POST requests require a cryptographically secure token (`random_bytes`).
* **XSS Escaping**: Output variables are rendered via a centralized escaping helper (`htmlspecialchars`). Untrusted HTML (like comments) is sanitized with `strip_tags()`.
* **Role-Based Authorization**: A centralized guard blocks direct URL traversal depending on the active user session ID and role array.
* **Session Protection**: Implements `session_regenerate_id()` during authentication alongside HTTP-only cookies.
* **Upload Validation**: Image uploads verify authentic MIME types (`finfo_file`), implement random string filenames, restrict extensions, and are protected by a `.htaccess` file prohibiting backend PHP script execution in the directory.
* **Security Headers**: Injecting headers (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`) dynamically on response boundaries.
* **Safe Error Handling**: Exceptions are caught to hide DB internal schemas from the end user viewport.

## Project Structure
```text
Dynamic News Portal/
│
├── admin/                 # Restricted dashboard panel
│   ├── categories/        
│   ├── comments/          
│   ├── includes/          
│   ├── messages/          
│   ├── newsletter/        
│   ├── news/              
│   ├── users/             
│   ├── index.php          
│   ├── login.php          
│   └── logout.php         
│
├── assets/                # Static frontend assets
│   ├── css/               
│   ├── js/                
│   └── images/            
│
├── config/                # Environment config
│   └── database.php       
│
├── database/              # Schema and test structures
│
├── errors/                # Global generic error pages (403, 404)
│
├── includes/              # Shared controllers, auth, headers/footers
│
├── uploads/               # Dynamic binary storage
│   └── news/              
│
├── index.php              # Public homepage
├── news.php               # Article read page
├── category.php           
├── search.php             
├── about.php              
├── contact.php            
├── login.php              
├── register.php           
├── logout.php             
├── profile.php            
├── VIVA_NOTES.md          # College viva defense prep
└── README.md              # Project documentation
```
