# DATABASE ENCODING AUDIT REPORT
**Project:** DYNAMIC NEWS PORTAL (`SONI_PHP_WEBSITE`)  
**Audit Date:** October 11, 2026  
**Auditor:** Senior Database & Security Engineer  
**Database Engine:** MySQL 10.4+ (InnoDB, UTF-8 Multibyte)  

---

## 1. Executive Summary

An investigation was performed to identify the root cause of question mark corruptions (e.g. `?10,000` and `???`) observed in news headlines and articles. The corruption was traced to an import of multi-byte Unicode characters (specifically the 3-byte Indian Rupee sign `₹` U+20B9 and the em-dash `—` U+2014) through a client interface or SQL seed file configured with latin1 or non-multibyte charset, causing MySQL to store ASCII question marks.

The database connection, schema definitions, and table columns were verified for full `utf8mb4` support. All corrupted records were identified, updated with authentic Unicode characters, and verified through a complete write-read-render lifecycle.

- **Total Database Tables Inspected:** 7 (`categories`, `comments`, `contact_messages`, `likes`, `news`, `newsletter_subscribers`, `users`)
- **Tables Containing Corrupted Records:** 2 (`news`, `categories`)
- **Total Character Corruptions Fixed:** 15 individual character instances across 8 records
- **PDO Connection Charset:** `utf8mb4` (enforced with `SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci`)
- **HTML Document Charset:** `<meta charset="UTF-8">`
- **Unicode Pipeline Verification:** **100% PASS**

---

## 2. Encoding Stack Architecture Audit

| Layer | Configuration | Verified Value | Status |
|:---|:---|:---|:---:|
| **MySQL Server Default** | Server variable `character_set_server` | `utf8mb4` | **PASS** |
| **Database Collation** | `news_portal` collation | `utf8mb4_unicode_ci` | **PASS** |
| **Table Definitions** | `news`, `categories`, `comments`, etc. | `CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci` | **PASS** |
| **PDO Driver Options** | `config/database.php` | `PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"` | **PASS** |
| **HTTP Response Header** | `includes/header.php` | `Content-Type: text/html; charset=UTF-8` | **PASS** |
| **HTML Meta Tag** | `<meta charset="UTF-8">` | Present on all public views | **PASS** |

---

## 3. Database Corruptions Remediated

| Table | Record ID | Field | Corrupted Raw Value | Remediated Value | Unicode Hex Code | Status |
|:---|:---:|:---|:---|:---|:---:|:---:|
| `news` | 1 | `title` | `...with ?10,000 Crore Budget` | `...with ₹10,000 Crore Budget` | `U+20B9` (Indian Rupee) | **PASS** |
| `news` | 1 | `short_description` | `...landmark ???10,000 crore...` | `...landmark ₹10,000 crore...` | `U+20B9` | **PASS** |
| `news` | 1 | `content` | `...investment of ???10,000 crore...` | `...investment of ₹10,000 crore...` | `U+20B9` | **PASS** |
| `news` | 1 | `content` | `...technology ??? it is...` | `...technology — it is...` | `U+2014` (Em-Dash) | **PASS** |
| `news` | 2 | `title` | `...Launch Vehicle ??? A New Era...` | `...Launch Vehicle — A New Era...` | `U+2014` | **PASS** |
| `news` | 3 | `title` | `...Gets Green Signal ??? New Routes...` | `...Gets Green Signal — New Routes...` | `U+2014` | **PASS** |
| `news` | 3 | `content` | `...estimated to cost ???7,500 crore...` | `...estimated to cost ₹7,500 crore...` | `U+20B9` | **PASS** |
| `news` | 4 | `content` | `...???1.5 lakh crore allocation...` | `...₹1.5 lakh crore allocation...` | `U+20B9` | **PASS** |
| `news` | 4 | `content` | `...education ??? a 15% increase...` | `...education — a 15% increase...` | `U+2014` | **PASS** |
| `news` | 4 | `content` | `...???25,000 crore for semiconductor...` | `...₹25,000 crore for semiconductor...` | `U+20B9` | **PASS** |
| `news` | 5 | `title` | `...Growth in FY2027 ??? AI Services...` | `...Growth in FY2027 — AI Services...` | `U+2014` | **PASS** |
| `news` | 6 | `title` | `...Australia ??? 3-1 Triumph` | `...Australia — 3-1 Triumph` | `U+2014` | **PASS** |
| `news` | 6 | `content` | `...Batting ??? 487 runs...` | `...Batting — 487 runs...` | `U+2014` | **PASS** |
| `news` | 6 | `content` | `...Bowling ??? 28 wickets...` | `...Bowling — 28 wickets...` | `U+2014` | **PASS** |
| `news` | 6 | `content` | `...Best innings ??? 176 runs...` | `...Best innings — 176 runs...` | `U+2014` | **PASS** |
| `news` | 7 | `content` | `...learn AI theory ??? they will build...` | `...learn AI theory — they will build...` | `U+2014` | **PASS** |
| `categories` | 8 | `description` | `Sports news ??? cricket, football...` | `Sports news — cricket, football...` | `U+2014` | **PASS** |

---

## 4. Admin Editor Unicode Lifecycle Test

A standalone test script (`scratch/test_admin_unicode.php`) was executed to confirm that the admin panel can insert, update, reload, and display multi-byte Unicode strings without character degradation:

1. **Insert:** Created a test article containing `₹15,000 Crore` and `— Mega Solar Initiative`.
2. **Persistence Check:** Retrieved the newly inserted record directly from MySQL.
3. **Integrity Validation:** Checked that byte length, symbol codepoint (`₹` U+20B9), and em-dash matched character-for-character.
4. **Result:** **PASS** — Exact Unicode integrity was preserved with zero data corruption.
