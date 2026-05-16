-- Peddi Database Schema
-- Run once on initial setup (local XAMPP and Bluehost production).
-- Compatible with MySQL 5.7+ / MariaDB 10.3+

CREATE DATABASE IF NOT EXISTS indiclex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE indiclex;

-- ---------------------------------------------------------------
-- Tables
-- ---------------------------------------------------------------

CREATE TABLE IF NOT EXISTS dictionaries (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100)  NOT NULL,
    language_code VARCHAR(20)   NOT NULL COMMENT 'ISO 639 code e.g. tel, san, hin, or combined e.g. eng-tel-hin',
    description   TEXT,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS dictionary_entries (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dictionary_id INT UNSIGNED  NOT NULL,
    word          VARCHAR(255)  NOT NULL,
    translation   TEXT          NOT NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_entry_dictionary
        FOREIGN KEY (dictionary_id) REFERENCES dictionaries(id) ON DELETE CASCADE,
    INDEX idx_word (word),
    INDEX idx_dictionary_id (dictionary_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)   NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL COMMENT 'bcrypt via password_hash()',
    role          ENUM('admin','visitor') NOT NULL DEFAULT 'visitor',
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS preferences (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pref_key   VARCHAR(50)  NOT NULL UNIQUE COMMENT 'System-wide default; per-user prefs use cookies',
    pref_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------
-- Sample data — Dictionaries
-- ---------------------------------------------------------------

INSERT INTO dictionaries (id, name, language_code, description) VALUES
(1, 'Telugu-English Dictionary', 'tel', 'A foundational Telugu to English word list covering common everyday vocabulary.'),
(2, 'Sanskrit-English Dictionary', 'san', 'Classical Sanskrit vocabulary with English translations drawn from traditional texts.');

-- ---------------------------------------------------------------
-- Sample data — Telugu-English entries (dictionary_id = 1)
-- ---------------------------------------------------------------

INSERT INTO dictionary_entries (dictionary_id, word, translation) VALUES
(1, 'నీరు',      'water'),
(1, 'అగ్ని',     'fire'),
(1, 'భూమి',     'earth / ground'),
(1, 'ఆకాశం',    'sky'),
(1, 'చెట్టు',    'tree'),
(1, 'పువ్వు',    'flower'),
(1, 'పండు',     'fruit'),
(1, 'ఇల్లు',     'house / home'),
(1, 'మనిషి',    'person / human being'),
(1, 'తల్లి',     'mother'),
(1, 'తండ్రి',    'father'),
(1, 'సూర్యుడు',  'sun'),
(1, 'చంద్రుడు',  'moon');

-- ---------------------------------------------------------------
-- Sample data — Sanskrit-English entries (dictionary_id = 2)
-- ---------------------------------------------------------------

INSERT INTO dictionary_entries (dictionary_id, word, translation) VALUES
(2, 'जल',       'water'),
(2, 'अग्नि',    'fire'),
(2, 'पृथ्वी',   'earth'),
(2, 'आकाश',    'sky / ether'),
(2, 'वृक्ष',    'tree'),
(2, 'पुष्प',    'flower'),
(2, 'फल',      'fruit'),
(2, 'गृह',      'house / abode'),
(2, 'मनुष्य',   'human being'),
(2, 'माता',     'mother'),
(2, 'पिता',     'father'),
(2, 'सूर्य',    'sun'),
(2, 'चन्द्र',   'moon'),
(2, 'ज्ञान',    'knowledge / wisdom');

-- ---------------------------------------------------------------
-- Sample data — Admin user
-- Password is 'admin123' hashed with PASSWORD_DEFAULT (bcrypt).
-- CHANGE THIS before deploying to production.
-- ---------------------------------------------------------------

INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$10$ahAZfj22aVvSvZ8SxrKYxeSpSOGme3RxyyekSdFAKctIZ5z6efvem', 'admin');

-- ---------------------------------------------------------------
-- Sample data — System preferences (site-wide defaults)
-- ---------------------------------------------------------------

INSERT INTO preferences (pref_key, pref_value) VALUES
('default_dictionary_id', '1'),
('default_results_per_page', '25'),
('default_theme',            'light'),
('default_search_mode',      'substring');
