# Viva Preparation Notes

This document provides a reference guide for the oral viva examination of the **Dynamic News Portal** project. 

## Project Introduction (30-45 Seconds)
"My project is the **Dynamic News Portal**. The problem it solves is that static websites make it very difficult and time-consuming to update or publish content regularly. The solution is a dynamic, database-driven platform where administrators can manage news effortlessly through a secure dashboard, while readers can browse, search, comment, and like articles instantly. I built this using native PHP 8 and MySQL, without relying on complex backend frameworks. It features a complete CRUD management system for news and categories, role-based access control, and strict security implementations like CSRF protection and prepared statements to prevent SQL injection."

## Core Concepts & Definitions

**What is the objective of your project?**  
The objective is to provide a dynamic platform where news content can be easily managed through a relational database and displayed beautifully through a responsive public portal.

**Why was PHP used?**  
PHP is a powerful, server-side scripting language designed specifically for web development. It integrates seamlessly with HTML and provides excellent native support for connecting to databases like MySQL. It allows for rapid development of dynamic pages.

**Why was MySQL used?**  
MySQL is a robust relational database management system. It was chosen because news portals are inherently relational—users write news, news belongs to categories, users leave comments on news, and users like news. MySQL handles these relationships efficiently using foreign keys.

**Why did you use PDO instead of directly constructing SQL queries?**  
PDO (PHP Data Objects) provides a secure abstraction layer for database access. More importantly, it supports **prepared statements** and **parameter binding**. This separates the SQL query logic from the user input, completely preventing SQL Injection attacks, making it much safer than using older functions like `mysqli_query` with concatenated strings.

**What is CRUD?**  
CRUD stands for Create, Read, Update, and Delete. It represents the four basic functions of persistent storage. For example, in my news module, an admin can *Create* an article, visitors can *Read* it, an editor can *Update* it, and an admin can *Delete* it.

**What is the difference between Authentication and Authorization?**  
Authentication asks "Who are you?" (verifying identity via email and password during login). Authorization asks "What are you allowed to do?" (checking if your role is `admin`, `editor`, or `user` before granting access to the dashboard or management tools).

**How are passwords secured in your system?**  
Passwords are **hashed** (not encrypted) using PHP's native `password_hash()` function, which uses strong algorithms like Bcrypt. We verify them upon login using `password_verify()`. The plaintext password is never stored in the database.

**What is CSRF?**  
CSRF (Cross-Site Request Forgery) is an attack where a malicious site tricks an authenticated user's browser into submitting an unauthorized request to our application. I prevented this by generating a cryptographically secure token using `random_bytes()` in the session and requiring that token to be passed in a hidden field on every state-changing POST form.

**What is XSS?**  
XSS (Cross-Site Scripting) occurs when an application renders untrusted user input as executable code (like JavaScript) on the web page. I mitigate this by using `htmlspecialchars()` (via a helper function `e()`) to safely encode output, and `strip_tags()` to sanitize HTML before storing comments.

**What is SQL Injection?**  
SQL Injection is an attack where malicious SQL statements are inserted into input fields to manipulate the database. My project prevents this entirely by using PDO prepared statements.

**What is a session?**  
A session is a way to store information (in variables) across multiple pages. Since HTTP is stateless, PHP sessions allow us to remember that a user is logged in as they navigate from the homepage to their profile or the admin dashboard. 

**What are foreign keys?**  
Foreign keys are used to link two tables together. They enforce referential integrity. For example, the `news` table has a `category_id` foreign key that references the `id` in the `categories` table. This prevents deleting a category if articles still belong to it.

**Why do you use Unique Constraints?**  
Unique constraints ensure that no two rows can have the same value in a specific column. For example, they prevent duplicate user registrations (`users.email`) and duplicate article URLs (`news.slug`).

**What is a database index?**  
An index is a database structure that improves the speed of data retrieval (searching and sorting), at the cost of additional storage space and slower writes. I added indexes to columns frequently used in `WHERE` clauses, such as `news.status` and `news.category_id`.

**How do you secure news image uploads?**  
Image uploads are verified server-side using `finfo_file()` to ensure the true MIME type is an image (ignoring the file extension the user provided). The filename is completely randomized using `uniqid()` to prevent path traversal, and the upload directory is protected by an `.htaccess` file that disables PHP execution inside that specific folder.

**How is your PHP application organized?**  
It uses a Page/Controller architecture. Individual PHP files (like `news.php` or `category.php`) act as controllers. They include shared utilities (`includes/functions.php`, `includes/auth.php`), execute their specific database logic, and then render the HTML. 

**What can the admin do?**  
The admin can manage news, categories, users, comments, newsletter subscribers, contact messages, and view real-time statistics on the dashboard.

**Future Scope**  
- Image optimization integration / CDN delivery
- Advanced Elasticsearch-style text searching
- Automated email notifications for subscribers
- REST API implementation for a mobile app extension
- Richer analytics charting in the admin dashboard

---

## Likely Viva Questions & Answers

1. **What is your project?**  
   A dynamic, database-driven news portal for publishing and managing articles.
2. **What problem does it solve?**  
   It eliminates the need to manually code HTML files to publish news, providing a secure backend interface to manage content dynamically.
3. **What is the objective?**  
   To build a secure CMS to publish news dynamically.
4. **Why PHP?**  
   It's a powerful server-side language built for the web with excellent MySQL integration.
5. **Why MySQL?**  
   Because the data structure (users, categories, articles) is strictly relational.
6. **Why PDO?**  
   It provides prepared statements which prevent SQL injection.
7. **What is CRUD?**  
   Create, Read, Update, Delete.
8. **What is authentication?**  
   Verifying a user's identity (logging in).
9. **What is authorization?**  
   Verifying a user's permissions (roles like admin or editor).
10. **What is CSRF?**  
    Cross-Site Request Forgery. Protected via unique session tokens on forms.
11. **What is XSS?**  
    Cross-Site Scripting. Protected by escaping output using `htmlspecialchars()`.
12. **What is SQL Injection?**  
    An attack manipulating SQL queries. Prevented via PDO binding.
13. **What is a primary key?**  
    A unique identifier for a database record (like `id`).
14. **What is a foreign key?**  
    A key that links a record to the primary key of another table, ensuring referential integrity.
15. **What is an index?**  
    A data structure that speeds up database lookups.
16. **Why hash passwords?**  
    So that if the database is compromised, the plaintext passwords remain secure.
17. **What is a PHP session?**  
    A mechanism to store user state across multiple HTTP requests.
18. **How are news images uploaded securely?**  
    By validating the MIME type server-side, randomizing filenames, and disabling PHP execution in the upload folder.
19. **What are the roles in your system?**  
    Admin, Editor, and User.
20. **What is the future scope?**  
    Email notifications, REST APIs, and advanced analytics.

---

## Recommended Demonstration Sequence (College Review)

1. **Open homepage** (`localhost/SONI_PHP_WEBSITE`) to show dynamic UI.
2. **Show dynamic news** (Scroll through featured, latest, trending).
3. **Open category** (Click a navbar link).
4. **Search news** (Type a keyword in the search bar).
5. **Open article** (Click a news title).
6. **Show article details** (View counts, images).
7. **Login** as an admin.
8. **Open admin dashboard** to show statistics.
9. **Create/edit news** (Demonstrate the editor and image upload).
10. **Change publication status** (Toggle an article to Draft).
11. **Manage category** (Show the list).
12. **Moderate comments** (Approve a pending comment).
13. **Show users** (Display role lists).
14. **Show newsletter subscribers** (Display active emails).
15. **Show contact messages** (Mark one as read).
16. **Logout** and return to public view.
