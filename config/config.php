<?php
/**
 * BetLedger - Sports Betting Tracker Application
 * Configuration File
 */

// Database Configuration
// Load from environment variables if available (Docker), otherwise use defaults
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'bettracker');
define('DB_PASS', getenv('DB_PASS') ?: 'bettracker_password');
define('DB_NAME', getenv('DB_NAME') ?: 'bet_tracker_db');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));

// Application Settings
define('APP_NAME', 'BetLedger');
define('APP_URL', 'http://localhost:8000');
define('APP_TIMEZONE', 'UTC');
define('APP_ENV', 'development'); // 'production' or 'development'

// Security Settings
define('SESSION_LIFETIME', 2592000); // 30 days in seconds
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_OPTIONS', ['cost' => 12]);

// Currency Settings
define('DEFAULT_CURRENCY', 'USD');
define('CURRENCY_SYMBOLS', [
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
    'AUD' => 'A$',
    'CAD' => 'C$',
]);

// Odds Formats
define('ODDS_FORMAT_DECIMAL', 'decimal');
define('ODDS_FORMAT_FRACTIONAL', 'fractional');
define('ODDS_FORMAT_AMERICAN', 'american');

// Bet Status Constants
define('BET_STATUS_PENDING', 'pending');
define('BET_STATUS_WON', 'won');
define('BET_STATUS_LOST', 'lost');
define('BET_STATUS_VOID', 'void');
define('BET_STATUS_CASHOUT', 'cashout');

// Bet Type Constants
define('BET_TYPE_SINGLE', 'single');
define('BET_TYPE_DOUBLE', 'double');
define('BET_TYPE_TREBLE', 'treble');
define('BET_TYPE_ACCUMULATOR', 'accumulator');
define('BET_TYPE_LUCKY_15', 'lucky_15');
define('BET_TYPE_LUCKY_31', 'lucky_31');
define('BET_TYPE_LUCKY_63', 'lucky_63');
define('BET_TYPE_SYSTEM', 'system');
define('BET_TYPE_EACHWAY', 'eachway');

// Theme Colors
define('THEME_PRIMARY_BG', '#0f1117');
define('THEME_SECONDARY_BG', '#1a1d2e');
define('THEME_SUCCESS', '#00d084');
define('THEME_DANGER', '#ff4757');
define('THEME_WARNING', '#ffa502');
define('THEME_INFO', '#3498db');

// Pagination
define('ITEMS_PER_PAGE', 20);

// API/File Upload Settings
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_UPLOAD_TYPES', ['csv', 'json']);

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// Set Default Timezone
date_default_timezone_set(APP_TIMEZONE);


