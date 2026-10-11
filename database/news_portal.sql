-- ============================================================
-- NEWS PORTAL — Database Schema
-- ============================================================
-- Project  : Dynamic News Portal — Publish News Dynamically
-- Student  : TYBScIT (AI/ML), GLS University
-- Stack    : PHP 8+ / MySQL / XAMPP
-- Version  : 1.0
-- Date     : 2026-09-25
-- ============================================================
-- This file is importable via phpMyAdmin.
-- It creates the database, all 7 tables, indexes, foreign keys,
-- and inserts demo/seed data for development.
-- ============================================================

-- Create database (safe — skips if already exists)
CREATE DATABASE IF NOT EXISTS `news_portal`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `news_portal`;


-- ============================================================
-- TABLE 1: users
-- ============================================================
-- Stores all users — admins, editors, and public users.
-- Passwords stored as bcrypt hashes via password_hash().
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100)    NOT NULL,
    `email`         VARCHAR(255)    NOT NULL,
    `password`      VARCHAR(255)    NOT NULL COMMENT 'Stored via password_hash(PASSWORD_DEFAULT)',
    `role`          ENUM('admin', 'editor', 'user') NOT NULL DEFAULT 'user',
    `profile_image` VARCHAR(255)    DEFAULT NULL,
    `status`        ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 2: categories
-- ============================================================
-- News categories — managed dynamically by admin.
-- Slugs are URL-friendly identifiers (e.g. "ai-ml").
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100)    NOT NULL,
    `slug`          VARCHAR(120)    NOT NULL,
    `description`   TEXT            DEFAULT NULL,
    `status`        ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_categories_name` (`name`),
    UNIQUE KEY `uk_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 3: news
-- ============================================================
-- Primary table — stores all articles.
-- Status: draft / published / archived.
-- is_featured and is_breaking control homepage placement.
-- views tracks article read count.
-- published_at is set when status changes to 'published'.
-- ============================================================
CREATE TABLE IF NOT EXISTS `news` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `category_id`       INT UNSIGNED    NOT NULL,
    `author_id`         INT UNSIGNED    NOT NULL,
    `title`             VARCHAR(255)    NOT NULL,
    `slug`              VARCHAR(280)    NOT NULL,
    `short_description` VARCHAR(500)    NOT NULL,
    `content`           LONGTEXT        NOT NULL,
    `featured_image`    VARCHAR(255)    DEFAULT NULL,
    `status`            ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'draft',
    `is_featured`       TINYINT(1)      NOT NULL DEFAULT 0,
    `is_breaking`       TINYINT(1)      NOT NULL DEFAULT 0,
    `views`             INT UNSIGNED    NOT NULL DEFAULT 0,
    `published_at`      DATETIME        DEFAULT NULL,
    `created_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_news_slug` (`slug`),
    INDEX `idx_news_status_published` (`status`, `published_at`),
    INDEX `idx_news_category` (`category_id`),
    INDEX `idx_news_author` (`author_id`),
    INDEX `idx_news_featured` (`is_featured`),
    INDEX `idx_news_breaking` (`is_breaking`),
    INDEX `idx_news_views` (`views`),

    CONSTRAINT `fk_news_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE,

    CONSTRAINT `fk_news_author`
        FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 4: comments
-- ============================================================
-- User comments on news articles, with moderation support.
-- Status: pending / approved / rejected.
-- Cascades on delete — if a news article or user is removed,
-- their comments are also removed.
-- ============================================================
CREATE TABLE IF NOT EXISTS `comments` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `news_id`       INT UNSIGNED    NOT NULL,
    `user_id`       INT UNSIGNED    NOT NULL,
    `comment`       TEXT            NOT NULL,
    `status`        ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    INDEX `idx_comments_news_status` (`news_id`, `status`),
    INDEX `idx_comments_user` (`user_id`),
    INDEX `idx_comments_status` (`status`),

    CONSTRAINT `fk_comments_news`
        FOREIGN KEY (`news_id`) REFERENCES `news` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `fk_comments_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 5: likes
-- ============================================================
-- One like per user per article — enforced by UNIQUE constraint.
-- Cascades on delete of either news or user.
-- ============================================================
CREATE TABLE IF NOT EXISTS `likes` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `news_id`       INT UNSIGNED    NOT NULL,
    `user_id`       INT UNSIGNED    NOT NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_likes_user_news` (`news_id`, `user_id`),

    CONSTRAINT `fk_likes_news`
        FOREIGN KEY (`news_id`) REFERENCES `news` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `fk_likes_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 6: contact_messages
-- ============================================================
-- Messages submitted through the public contact form.
-- Status: unread / read — for admin tracking.
-- ============================================================
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(100)    NOT NULL,
    `email`         VARCHAR(255)    NOT NULL,
    `subject`       VARCHAR(255)    NOT NULL,
    `message`       TEXT            NOT NULL,
    `status`        ENUM('unread', 'read') NOT NULL DEFAULT 'unread',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE 7: newsletter_subscribers
-- ============================================================
-- Email addresses subscribed to the newsletter.
-- Status: active / unsubscribed.
-- ============================================================
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
    `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `email`         VARCHAR(255)    NOT NULL,
    `status`        ENUM('active', 'unsubscribed') NOT NULL DEFAULT 'active',
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_newsletter_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SEED DATA — Demo data for development & demonstration
-- ============================================================
-- These records provide realistic content for development
-- and project demonstration. They are clearly structured as
-- sample/demo data.
-- ============================================================


-- ------------------------------------------------------------
-- SEED: Users
-- ------------------------------------------------------------
-- Passwords are hashed using PHP password_hash(PASSWORD_DEFAULT).
-- The hashes below were generated on 2026-09-25 using PHP 8.2.
--
-- Admin    : admin@newsportal.local  / NewsPortal@2026
-- Editor   : editor@newsportal.local / Editor@2026
-- User     : user@newsportal.local   / User@2026
-- ------------------------------------------------------------

INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('Admin User', 'admin@newsportal.local',
 '$2y$10$f4C5hsl0xOJWFmRg4oPrwOZmyp7zhlUWLMY8pluM0m4aWz1Ld7iqa',
 'admin', 'active');

INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('Editor User', 'editor@newsportal.local',
 '$2y$10$FLS.SpRP/P42oDsYyrqgx.haByjV7bkuefK8YIZsU1jMMET8KiHFm',
 'editor', 'active');

INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`) VALUES
('Dixit Soni', 'user@newsportal.local',
 '$2y$10$AOk/E3jarZ35VAhLzoQxI.h79Fc4kaHd5BwYJiNIyn6dXmzMrCKHK',
 'user', 'active');


-- ------------------------------------------------------------
-- SEED: Categories
-- ------------------------------------------------------------
-- 9 realistic news categories. These are demo records;
-- categories are managed dynamically by the admin panel.
-- ------------------------------------------------------------

INSERT INTO `categories` (`name`, `slug`, `description`, `status`) VALUES
('India',          'india',          'National news from across India.',                          'active'),
('Gujarat',        'gujarat',        'State news from Gujarat.',                                  'active'),
('World',          'world',          'International news and global events.',                     'active'),
('Technology',     'technology',     'Latest in tech, gadgets and innovation.',                   'active'),
('AI & ML',        'ai-ml',          'Artificial Intelligence and Machine Learning updates.',     'active'),
('Business',       'business',       'Business, economy and market news.',                        'active'),
('Education',      'education',      'Education news, policies and opportunities.',               'active'),
('Sports',         'sports',         'Sports news — cricket, football and more.',                 'active'),
('Entertainment',  'entertainment',  'Bollywood, Hollywood, TV and celebrity news.',              'active');


-- ------------------------------------------------------------
-- SEED: News Articles
-- ------------------------------------------------------------
-- 10 demo articles using author_id = 1 (Admin) and various
-- categories. 9 are published, 1 is draft for admin panel testing.
-- All content is realistic and relevant to India / AI / Tech.
-- ------------------------------------------------------------

INSERT INTO `news`
    (`category_id`, `author_id`, `title`, `slug`, `short_description`, `content`, `status`, `is_featured`, `is_breaking`, `views`, `published_at`)
VALUES
-- Article 1: Featured + Breaking — AI & ML (category 5)
(5, 1,
 'India Launches National AI Mission with ₹10,000 Crore Investment',
 'india-launches-national-ai-mission',
 'The Government of India has announced a landmark ₹10,000 crore National AI Mission aimed at fostering innovation, building AI infrastructure, and establishing India as a global AI hub.',
 '<p>In a historic move, the Government of India has announced the launch of the National AI Mission with an investment of ₹10,000 crore over the next five years. The mission aims to build a robust AI ecosystem across the country.</p>
<p>Key highlights of the mission include:</p>
<ul>
    <li>Establishment of AI Centres of Excellence in 10 major cities</li>
    <li>Compute infrastructure with 10,000+ GPU capacity</li>
    <li>AI skilling programs for 1 million students</li>
    <li>Support for AI startups with dedicated funding</li>
    <li>Ethical AI framework and governance guidelines</li>
</ul>
<p>The Prime Minister stated, "Artificial Intelligence is not just a technology — it is a transformational force that will redefine how India solves its biggest challenges, from healthcare to agriculture to education."</p>
<p>The mission will be implemented in three phases, with Phase 1 focusing on infrastructure and talent development. Leading institutions like IITs, IIITs, and private tech companies will be partners in this initiative.</p>
<p>Industry leaders have welcomed the announcement, calling it a game-changer for India\'s technology landscape. The mission is expected to create over 500,000 new AI-related jobs by 2030.</p>',
 'published', 1, 1, 1247,
 '2026-09-24 08:00:00'),

-- Article 2: Featured — Technology (category 4)
(4, 1,
 'ISRO Successfully Tests Reusable Launch Vehicle — A New Era for Indian Space',
 'isro-tests-reusable-launch-vehicle',
 'ISRO has completed a successful autonomous landing test of its Reusable Launch Vehicle, marking a significant milestone in reducing space launch costs.',
 '<p>The Indian Space Research Organisation (ISRO) has achieved a major milestone with the successful test of its Reusable Launch Vehicle (RLV) technology demonstrator. The test involved an autonomous landing from a height of 4.5 km.</p>
<p>The RLV, nicknamed "Pushpak," completed all test objectives perfectly. This technology could reduce launch costs by up to 80%, making space more accessible.</p>
<p>"This is a proud moment for Indian space technology," said the ISRO Chairman. "Reusable launch vehicles represent the future of space access, and India is now firmly in this race."</p>
<p>The next phase will involve orbital re-entry tests planned for 2027. If successful, India will join the US and Europe as the only nations with operational reusable launch vehicle technology.</p>',
 'published', 1, 0, 893,
 '2026-09-23 14:30:00'),

-- Article 3: Breaking — Gujarat (category 2)
(2, 1,
 'Ahmedabad Metro Phase-2 Gets Green Signal — New Routes Announced',
 'ahmedabad-metro-phase-2-approved',
 'The Ahmedabad Metro Phase-2 project has received approval, adding 28.5 km of new metro lines connecting key areas of the city.',
 '<p>The Gujarat government has given the green signal to Ahmedabad Metro Phase-2, which will add 28.5 kilometres of new metro lines to the city\'s growing rapid transit network.</p>
<p>The new phase will include two corridors:</p>
<ul>
    <li><strong>Corridor 1:</strong> Motera to Mahatma Mandir (15.6 km)</li>
    <li><strong>Corridor 2:</strong> GNLU to Hansol (12.9 km)</li>
</ul>
<p>The project is estimated to cost ₹7,500 crore and is expected to be completed by 2029. It will serve an estimated 4 lakh daily passengers.</p>
<p>Chief Minister stated that this expansion will significantly improve connectivity for students, professionals, and residents across Ahmedabad.</p>',
 'published', 0, 1, 562,
 '2026-09-24 10:15:00'),

-- Article 4: Regular — India (category 1)
(1, 1,
 'Union Budget 2026: Key Highlights for Education and Technology Sectors',
 'union-budget-2026-education-technology',
 'The Union Budget 2026-27 brings significant allocations for education reform and technology infrastructure across India.',
 '<p>Finance Minister presented the Union Budget 2026-27 in Parliament today, with major focus on education and technology sectors.</p>
<p><strong>Education Highlights:</strong></p>
<ul>
    <li>₹1.5 lakh crore allocation for education — a 15% increase</li>
    <li>New National Digital University to serve 10 million students</li>
    <li>AI-integrated curriculum for all universities by 2028</li>
    <li>50,000 new scholarships for STEM students</li>
</ul>
<p><strong>Technology Highlights:</strong></p>
<ul>
    <li>₹25,000 crore for semiconductor manufacturing</li>
    <li>Tax incentives for AI and ML startups</li>
    <li>Digital infrastructure push for Tier-2 and Tier-3 cities</li>
</ul>
<p>Industry experts have called this budget "forward-looking" with its emphasis on building a knowledge economy.</p>',
 'published', 0, 0, 1105,
 '2026-09-22 09:00:00'),

-- Article 5: Regular — Business (category 6)
(6, 1,
 'Indian IT Industry Projects 12% Growth in FY2027 — AI Services Lead the Surge',
 'indian-it-industry-12-percent-growth',
 'India\'s IT industry is projected to grow at 12% in FY2027, driven primarily by demand for AI and ML services globally.',
 '<p>The National Association of Software and Services Companies (NASSCOM) has projected a 12% growth rate for India\'s IT industry in FY2027, with revenues expected to cross $300 billion.</p>
<p>The growth is being driven by:</p>
<ul>
    <li>Surge in AI/ML service demand from global enterprises</li>
    <li>Cloud computing adoption acceleration</li>
    <li>Digital transformation projects across sectors</li>
    <li>Growth in Global Capability Centres (GCCs) in India</li>
</ul>
<p>AI-related services alone are expected to contribute $50 billion, making India the largest AI services exporter globally.</p>
<p>Hiring in the IT sector is also expected to revive, with an estimated 300,000 new jobs being created, primarily in AI, data science, and cloud engineering roles.</p>',
 'published', 0, 0, 734,
 '2026-09-21 11:00:00'),

-- Article 6: Regular — Sports (category 8)
(8, 1,
 'India Clinches Historic Test Series Victory in Australia — 3-1 Triumph',
 'india-test-series-victory-australia',
 'Team India has won the Border-Gavaskar Trophy with a convincing 3-1 series victory in Australia, creating history.',
 '<p>Team India has created history by winning the Border-Gavaskar Trophy on Australian soil with a commanding 3-1 series victory.</p>
<p>The decisive fifth Test in Sydney saw India chase down a target of 287 runs with 4 wickets to spare. The series was marked by outstanding performances from both bat and ball.</p>
<p><strong>Top performers of the series:</strong></p>
<ul>
    <li>Batting — 487 runs at an average of 54.11</li>
    <li>Bowling — 28 wickets at an average of 19.32</li>
    <li>Best innings — 176 runs in Melbourne</li>
</ul>
<p>The BCCI congratulated the team, calling it "one of the greatest achievements in Indian cricket history."</p>
<p>Captain said in the post-match ceremony: "This team has belief, resilience and hunger. We came here to win, and we did."</p>',
 'published', 0, 0, 2340,
 '2026-09-20 18:00:00'),

-- Article 7: Regular — Education (category 7)
(7, 1,
 'GLS University Introduces New AI & ML Specialization in BScIT Program',
 'gls-university-ai-ml-specialization',
 'GLS University has announced a new AI & ML specialization track within its BScIT program, aligning with industry demand for AI professionals.',
 '<p>GLS University, Ahmedabad, has announced the introduction of a specialized AI & Machine Learning track within its Bachelor of Science in Information Technology (BScIT) program.</p>
<p>The new specialization will cover:</p>
<ul>
    <li>Fundamentals of Machine Learning</li>
    <li>Deep Learning and Neural Networks</li>
    <li>Natural Language Processing</li>
    <li>Computer Vision</li>
    <li>AI Ethics and Responsible AI</li>
    <li>Capstone Project with Industry Mentorship</li>
</ul>
<p>The program is designed to produce industry-ready AI professionals who can contribute to India\'s growing AI ecosystem.</p>
<p>"Our students will not just learn AI theory — they will build real AI solutions," said the Department Head.</p>',
 'published', 0, 0, 456,
 '2026-09-19 10:00:00'),

-- Article 8: Regular — Entertainment (category 9)
(9, 1,
 'Indian Film "The Last Algorithm" Wins Best Picture at International Film Festival',
 'indian-film-last-algorithm-wins',
 'The Indian sci-fi film "The Last Algorithm," exploring AI consciousness, has won Best Picture at a major international film festival.',
 '<p>Indian cinema has made waves internationally as "The Last Algorithm," a thought-provoking science fiction film about artificial consciousness, won the Best Picture award at the Berlin International Film Festival.</p>
<p>The film tells the story of an AI researcher who creates a machine learning model that develops unexpected emergent behaviours, raising profound questions about consciousness and ethics.</p>
<p>Director stated: "I wanted to tell a story that resonates with the AI era we live in. What happens when the algorithm we create starts asking questions we cannot answer?"</p>
<p>The film has been praised for its scientifically accurate portrayal of machine learning concepts and its emotional depth. It is set for a worldwide release next month.</p>',
 'published', 0, 0, 678,
 '2026-09-18 16:00:00'),

-- Article 9: Regular — World (category 3)
(3, 1,
 'Global AI Safety Summit 2026: World Leaders Agree on AI Governance Framework',
 'global-ai-safety-summit-2026',
 'Leaders from 40+ nations have signed the landmark AI Governance Framework at the Global AI Safety Summit, establishing international AI safety standards.',
 '<p>The Global AI Safety Summit 2026, held in Geneva, Switzerland, has concluded with a historic agreement. Leaders from over 40 nations, including India, have signed the International AI Governance Framework.</p>
<p><strong>Key agreements include:</strong></p>
<ul>
    <li>Mandatory safety testing for frontier AI models</li>
    <li>International AI incident reporting system</li>
    <li>Shared standards for AI transparency and explainability</li>
    <li>Framework for AI use in critical infrastructure</li>
    <li>Protection of human rights in AI deployment</li>
</ul>
<p>India\'s delegation played a significant role in ensuring that the framework balances innovation with safety, particularly for developing nations.</p>
<p>The framework will be implemented in phases, with signatory nations expected to establish national AI safety bodies by 2028.</p>',
 'published', 0, 0, 891,
 '2026-09-17 12:00:00'),

-- Article 10: Draft (for testing admin panel)
(4, 1,
 'Draft: Upcoming Trends in Quantum Computing for 2027',
 'upcoming-trends-quantum-computing-2027',
 'An exploration of quantum computing trends expected to shape the technology landscape in 2027.',
 '<p>This is a draft article about upcoming quantum computing trends. Content will be expanded before publication.</p>
<p>Topics to cover:</p>
<ul>
    <li>Quantum error correction breakthroughs</li>
    <li>Quantum-AI integration</li>
    <li>Commercial quantum applications</li>
</ul>',
 'draft', 0, 0, 0,
 NULL);


-- ============================================================
-- END OF SCHEMA
-- ============================================================
