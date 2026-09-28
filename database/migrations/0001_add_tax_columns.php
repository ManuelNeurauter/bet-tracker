<?php
/**
 * Add bookmaker tax rate and per-bet tax amount (added to schema.sql in May 2026).
 * Databases created after that change already have the columns, so each one
 * is only added when it is missing.
 */

return function (PDO $db) {
    if (!columnExists($db, 'bookmakers', 'tax_percentage')) {
        $db->exec('ALTER TABLE bookmakers ADD COLUMN tax_percentage DECIMAL(5, 2) DEFAULT 0 AFTER bonus_balance');
    }
    if (!columnExists($db, 'bets', 'tax_amount')) {
        $db->exec('ALTER TABLE bets ADD COLUMN tax_amount DECIMAL(12, 2) DEFAULT 0 AFTER actual_return');
    }
};
