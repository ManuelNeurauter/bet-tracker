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
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as void_bets,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as settled_bets,
                SUM(stake) as total_staked,
                SUM(CASE WHEN actual_return IS NOT NULL THEN actual_return ELSE 0 END) as total_returned,
                SUM(CASE WHEN actual_return IS NOT NULL THEN (actual_return - stake) ELSE 0 END) as total_profit,
                AVG(CASE WHEN status IN (?, ?) THEN odds ELSE NULL END) as avg_odds
            FROM bets
            WHERE user_id = ? AND status != ?
        ');
        
        $stmt->execute([
            BET_STATUS_WON,
            BET_STATUS_LOST,
            BET_STATUS_VOID,
            BET_STATUS_WON,
            BET_STATUS_LOST,
            BET_STATUS_WON,
            BET_STATUS_LOST,
            $userId,
            BET_STATUS_PENDING
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get current bankroll
     */
    public function getCurrentBankroll($userId) {
        $stats = $this->getStatistics($userId);
        $user = $this->findById($userId);
        
        $startingBankroll = $user['bankroll_start'] ?? 0;
        $totalProfit = $stats['total_profit'] ?? 0;
        
        return $startingBankroll + $totalProfit;
    }
}


