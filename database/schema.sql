-- BetLedger Database Schema
-- MySQL 8.0+ / MariaDB Compatible

-- Create Database
CREATE DATABASE IF NOT EXISTS bet_tracker_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bet_tracker_db;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(120) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    timezone VARCHAR(50) DEFAULT 'UTC',
    odds_format VARCHAR(20) DEFAULT 'decimal',
    date_format VARCHAR(20) DEFAULT 'Y-m-d',
    bankroll_start DECIMAL(12, 2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SPORTS TABLE
-- ============================================
CREATE TABLE sports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon_slug VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- COMPETITIONS TABLE
-- ============================================
CREATE TABLE competitions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sport_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    country VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE CASCADE,
    INDEX idx_sport_id (sport_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BOOKMAKERS TABLE
-- ============================================
CREATE TABLE bookmakers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(255),
    account_balance DECIMAL(12, 2) DEFAULT 0,
    bonus_balance DECIMAL(12, 2) DEFAULT 0,
    tax_percentage DECIMAL(5, 2) DEFAULT 0,
    notes TEXT,
    is_archived BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TIPSTERS TABLE
-- ============================================
CREATE TABLE tipsters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    source_url VARCHAR(255),
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TAGS TABLE
-- ============================================
CREATE TABLE tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tag (user_id, name),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BETS TABLE
-- ============================================
CREATE TABLE bets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bookmaker_id INT,
    sport_id INT,
    competition_id INT,
    event_name VARCHAR(255) NOT NULL,
    event_date DATETIME,
    bet_type VARCHAR(50) NOT NULL DEFAULT 'single',
    selection VARCHAR(500) NOT NULL,
    odds DECIMAL(8, 3) NOT NULL,
    stake DECIMAL(12, 2) NOT NULL,
    potential_return DECIMAL(12, 2),
    status VARCHAR(50) DEFAULT 'pending',
    actual_return DECIMAL(12, 2),
    tax_amount DECIMAL(12, 2) DEFAULT 0,
    cashout_amount DECIMAL(12, 2),
    each_way BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    settled_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bookmaker_id) REFERENCES bookmakers(id) ON DELETE SET NULL,
    FOREIGN KEY (sport_id) REFERENCES sports(id) ON DELETE SET NULL,
    FOREIGN KEY (competition_id) REFERENCES competitions(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_event_date (event_date),
    INDEX idx_sport_id (sport_id),
    INDEX idx_bookmaker_id (bookmaker_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BET_LEGS TABLE (for Accumulators/Parlays)
-- ============================================
CREATE TABLE bet_legs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bet_id INT NOT NULL,
    leg_number INT NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    selection VARCHAR(500) NOT NULL,
    odds DECIMAL(8, 3) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bet_id) REFERENCES bets(id) ON DELETE CASCADE,
    INDEX idx_bet_id (bet_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BET_TAGS TABLE
-- ============================================
CREATE TABLE bet_tags (
    bet_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (bet_id, tag_id),
    FOREIGN KEY (bet_id) REFERENCES bets(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BET_TIPSTERS TABLE
-- ============================================
CREATE TABLE bet_tipsters (
    bet_id INT NOT NULL,
    tipster_id INT NOT NULL,
    PRIMARY KEY (bet_id, tipster_id),
    FOREIGN KEY (bet_id) REFERENCES bets(id) ON DELETE CASCADE,
    FOREIGN KEY (tipster_id) REFERENCES tipsters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BANKROLL_SNAPSHOTS TABLE
-- ============================================
CREATE TABLE bankroll_snapshots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    balance DECIMAL(12, 2) NOT NULL,
    snapshot_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_date (user_id, snapshot_date),
    INDEX idx_user_id (user_id),
    INDEX idx_snapshot_date (snapshot_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- BANKROLL_ADJUSTMENTS TABLE
-- ============================================
CREATE TABLE bankroll_adjustments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    bookmaker_id INT,
    type VARCHAR(50) NOT NULL, -- 'deposit', 'withdrawal', 'bonus'
    amount DECIMAL(12, 2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bookmaker_id) REFERENCES bookmakers(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CREATE SAMPLE SPORTS DATA
-- ============================================
INSERT INTO sports (name, icon_slug) VALUES
('Football', 'football'),
('Tennis', 'tennis'),
('Basketball', 'basketball'),
('Ice Hockey', 'ice-hockey'),
('Baseball', 'baseball'),
('Rugby', 'rugby'),
('Cricket', 'cricket'),
('Volleyball', 'volleyball'),
('Handball', 'handball'),
('American Football', 'american-football');

COMMIT;
