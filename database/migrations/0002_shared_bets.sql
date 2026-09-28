-- Shared bets (issue #3): partners in a bet and each user's Shared bets bookkeeper.

-- One row per person a bet is shared with. The owner's bet is the real slip at
-- their bookmaker; each partner puts in part of its stake and gets the same
-- part of its return.
CREATE TABLE IF NOT EXISTS bet_shares (
    id INT PRIMARY KEY AUTO_INCREMENT,
    bet_id INT NOT NULL,                              -- the owner's bet
    owner_id INT NOT NULL,
    user_id INT NULL,                                 -- the partner, once the email matches an account
    invited_email VARCHAR(120) NOT NULL,
    stake DECIMAL(12, 2) NOT NULL,                    -- the partner's part of the stake
    status VARCHAR(20) NOT NULL DEFAULT 'invited',    -- invited, accepted, declined
    partner_bet_id INT NULL,                          -- the partner's copy of the bet once accepted
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL,
    FOREIGN KEY (bet_id) REFERENCES bets(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (partner_bet_id) REFERENCES bets(id) ON DELETE SET NULL,
    UNIQUE KEY unique_bet_email (bet_id, invited_email),
    INDEX idx_user_id (user_id),
    INDEX idx_invited_email (invited_email),
    INDEX idx_partner_bet_id (partner_bet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Each user's "Shared bets" bookkeeper: a bookmaker entry created on demand that
-- carries what partners owe you or you owe them when shared bets settle.
CREATE TABLE IF NOT EXISTS shared_bet_books (
    user_id INT PRIMARY KEY,
    bookmaker_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (bookmaker_id) REFERENCES bookmakers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
