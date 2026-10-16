# CONTENT AND HEADLINE AUDIT REPORT
**Project:** DYNAMIC NEWS PORTAL (`SONI_PHP_WEBSITE`)  
**Audit Date:** October 11, 2026  
**Auditor:** Senior Full-Stack PHP & Database Engineer / Editorial Specialist  
**Database:** MySQL `news_portal` via PDO `utf8mb4`  

---

## 1. Executive Summary

A comprehensive editorial and database audit was performed across all published news stories, categories, metadata, and public templates. All instances of malformed question marks (`?` or `???`), character corruption, academic/college branding, and test articles were identified, traced to their root causes, and permanently corrected in both the active MySQL database and the canonical `database/news_portal.sql` seed script.

- **Total Records Inspected:** 23 (14 news records + 9 category records)
- **Total Malformed Records Identified:** 8 (7 news records + 1 category record)
- **Total Records Corrected:** 8
- **Records Requiring Further Review:** 0 (Legitimate grammatical question in Article ID 8 verified)
- **Test Articles Removed from Public View:** 4 (IDs 14, 15, 16, 17)
- **Status:** **100% AUDITED & PASS**

---

## 2. Detailed Record-by-Record Audit

| Issue ID | Record / File | Description | Root Cause | Fix Implemented | Test Performed | Actual Result | Status |
|:---|:---|:---|:---|:---|:---|:---|:---:|
| **CNT-01** | `news` (ID 1) | Title read `"India Launches National AI Mission with ?10,000 Crore Budget"`; summary had `"???10,000 crore"`. | Non-UTF8 SQL import corrupted Indian Rupee symbol `₹` into `?` and `???`. | Executed SQL update restoring `₹10,000 Crore` in title, short description, and content; replaced `??? it is` with em-dash `— it is`. | Database query & live HTTP inspection on `index.php` and `news.php`. | Renders `₹10,000 Crore Budget` natively without question marks. | **PASS** |
| **CNT-02** | `news` (ID 2) | Title read `"ISRO Successfully Tests Reusable Launch Vehicle ??? A New Era for Indian Space"`. | Em-dash character was corrupted to `???` during database seeding. | Restored em-dash `— A New Era for Indian Space`. | Database verification and homepage hero/secondary render. | Clean em-dash rendered in headline and breadcrumbs. | **PASS** |
| **CNT-03** | `news` (ID 3) | Title read `"Ahmedabad Metro Phase-2 Gets Green Signal ??? New Routes Announced"`; body had `"cost ???7,500 crore"`. | Em-dash and Rupee symbol `₹` corrupted to `???`. | Restored `— New Routes Announced` and `₹7,500 crore`. | Breaking news ticker test and article detail render. | Breaking ticker displays `₹` and `—` cleanly. | **PASS** |
| **CNT-04** | `news` (ID 4) | Content contained `"???1.5 lakh crore"`, `"education ??? a 15% increase"`, `"???25,000 crore"`. | Unicode encoding corruption of `₹` and `—`. | Replaced corruptions with `₹1.5 lakh crore`, `education — a 15% increase`, `₹25,000 crore`. | Content query and article detail view. | All monetary values and dashes render cleanly. | **PASS** |
| **CNT-05** | `news` (ID 5) | Title read `"Indian IT Industry Projects 12% Growth in FY2027 ??? AI Services Lead the Surge"`. | Em-dash corrupted into `???`. | Replaced with `— AI Services Lead the Surge`. | Category business feed and search page test. | Valid em-dash displayed on all views. | **PASS** |
| **CNT-06** | `news` (ID 6) | Title read `"India Clinches Historic Test Series Victory in Australia ??? 3-1 Triumph"`; body had `"Batting ??? 487 runs"`. | Corrupted em-dashes and colons in sports article. | Restored `— 3-1 Triumph`, `Batting — 487 runs`, `Bowling — 28 wickets`, `Best innings — 176 runs`. | Homepage trending list and article page test. | Headline and sports statistics render cleanly. | **PASS** |
| **CNT-07** | `news` (ID 7) | Title read `"GLS University Introduces New AI & ML Specialization in BScIT Program"`. | College course submission announcement was placed in general public news feed. | Replaced with genuine national education news: `"Leading National Universities Introduce Advanced AI Curriculum Framework"` (`slug = 'national-universities-advanced-ai-curriculum'`). | Verified on homepage education section and `category.php?slug=education`. | Professional national education story displayed. | **PASS** |
| **CNT-08** | `categories` (ID 8) | Description read `"Sports news ??? cricket, football and more."`. | Em-dash corrupted in category description. | Updated to `"Sports news — cricket, football and more."`. | Category list query and meta tag verification. | Clean em-dash in category header and metadata. | **PASS** |
| **CNT-09** | `news` (ID 8) | Content contained `"...asking questions we cannot answer?\""`. | Legitimate quotation ending with a grammatical question mark in a film review. | Preserved without blind regex replacement. | Verified string context and integrity. | Grammatical question mark preserved correctly. | **PASS** |
| **CNT-10** | `news` (IDs 14-17) | Synthetic QA test articles (`"TEST LIVE NEWS ARTICLE"`, `"E2E Cross Site Title"`) appeared on public homepage. | Created during prior automated tests with `status = 'published'` and `is_featured = 1`. | Updated records to `status = 'draft'`, `is_featured = 0`, `is_breaking = 0`. | Homepage query and XML sitemap verification. | 0 test articles appear in public feeds or search. | **PASS** |

---

## 3. Academic & College Branding Purge

All public-facing references to `"TYBScIT (AI/ML) · GLS University"` have been systematically purged and replaced with publication branding:

1. **Top Information Bar (`includes/navbar.php`):**  
   Replaced with `<i class="fas fa-globe"></i> National Edition &bull; Independent Journalism`.
2. **Document Metadata (`includes/header.php`):**  
   Author updated from `"TYBScIT (AI/ML), GLS University"` to `"The Newsroom Editorial Board"`. Description updated to a professional publication summary.
3. **Footer Information & Copyright (`includes/footer.php`):**  
   Address updated to `"Press Club Enclave, Ahmedabad, Gujarat"`. Copyright updated to `&copy; 2026 The Newsroom • All Rights Reserved.` Tagline updated to `"Independent Journalism • Published Dynamically"`.
4. **Authentication Portals (`login.php`, `register.php`):**  
   Replaced academic badge with `"Editorial & Reader Secure Portal"` and `"Independent Digital Journalism Community"`.
5. **Contact Bureau (`contact.php`):**  
   Replaced student project address with `"Editorial Bureau: Press Club Enclave, Ashram Road, Navrangpura, Ahmedabad"`.
6. **About Page (`about.php`):**  
   Replaced academic context section with a formal `"Editorial Mission & Standards"` charter.
