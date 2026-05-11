<?php
/**
 * User Model
 */

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new user
     */
    public function create($username, $email, $password, $currency = 'USD', $timezone = 'UTC', $bankroll = 0) {
        $stmt = $this->db->prepare('
            INSERT INTO users (username, email, password_hash, currency, timezone, bankroll_start)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        
        $passwordHash = hashPassword($password);
        return $stmt->execute([$username, $email, $passwordHash, $currency, $timezone, $bankroll]);
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ? AND deleted_at IS NULL');
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM users WHERE email = ? AND deleted_at IS NULL');
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists($username) {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM users WHERE username = ? AND deleted_at IS NULL');
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
    
    /**
     * Update user
     */
    public function update($userId, $data) {
        $updates = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['username', 'email', 'currency', 'timezone', 'odds_format', 'date_format', 'bankroll_start'])) {
                $updates[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updates)) return false;
        
        $values[] = $userId;
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Update password
     */
    public function updatePassword($userId, $newPassword) {
        $hash = hashPassword($newPassword);
        $stmt = $this->db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        return $stmt->execute([$hash, $userId]);
    }
    
    /**
     * Get user statistics
     */
    public function getStatistics($userId) {
        $stmt = $this->db->prepare('
            SELECT
                COUNT(*) as total_bets,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as won_bets,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as lost_bets,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cashed_out_bets,
                SUM(CASE WHEN status IN (?, ?, ?) THEN 1 ELSE 0 END) as settled_bets,
                SUM(stake) as total_staked,
                SUM(CASE WHEN actual_return IS NOT NULL THEN actual_return ELSE 0 END) as total_returned,
                SUM(CASE WHEN actual_return IS NOT NULL THEN (actual_return - stake) ELSE 0 END) as total_profit,
                AVG(CASE WHEN status IN (?, ?, ?) THEN odds ELSE NULL END) as avg_odds
            FROM bets
            WHERE user_id = ? AND status IN (?, ?, ?)
        ');
        
        $stmt->execute([
            BET_STATUS_WON,
            BET_STATUS_LOST,
            BET_STATUS_CASHOUT,
            BET_STATUS_WON,
            BET_STATUS_LOST,
            BET_STATUS_CASHOUT,
            BET_STATUS_WON,
            BET_STATUS_LOST,
            BET_STATUS_CASHOUT,
            $userId,
            BET_STATUS_WON,
            BET_STATUS_LOST,
            BET_STATUS_CASHOUT
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get current bankroll - sum of all bookmaker balances
     */
    public function getCurrentBankroll($userId) {
        $stmt = $this->db->prepare('
            SELECT SUM(account_balance + bonus_balance) as total_balance
            FROM bookmakers
            WHERE user_id = ? AND is_archived = 0
        ');
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result['total_balance'] ?? 0;
    }

    /**
     * Record a daily bankroll snapshot
     */
    public function recordBankrollSnapshot($userId, $balance = null, $snapshotDate = null) {
        if (!$userId) {
            return false;
        }

        if ($balance === null) {
            $balance = $this->getCurrentBankroll($userId);
        }

        if ($snapshotDate === null) {
            $snapshotDate = date('Y-m-d');
        }

        $stmt = $this->db->prepare('
            INSERT INTO bankroll_snapshots (user_id, snapshot_date, balance)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE balance = VALUES(balance)
        ');

        return $stmt->execute([$userId, $snapshotDate, $balance]);
    }

    /**
     * Get bankroll history for charting
     */
    public function getBankrollHistory($userId, $limit = 30) {
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 30;
        }

        $stmt = $this->db->prepare('
            SELECT snapshot_date, balance
            FROM bankroll_snapshots
            WHERE user_id = ?
            ORDER BY snapshot_date DESC, id DESC
            LIMIT ?
        ');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        $history = $stmt->fetchAll();
        return array_reverse($history);
    }
}

