<?php
/**
 * Bookmaker Model
 */

class Bookmaker {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new bookmaker
     */
    public function create($userId, $data) {
        $stmt = $this->db->prepare('
            INSERT INTO bookmakers (user_id, name, url, account_balance, bonus_balance, tax_percentage, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
        return $stmt->execute([
            $userId,
            $data['name'],
            $data['url'] ?? null,
            $data['account_balance'] ?? 0,
            $data['bonus_balance'] ?? 0,
            $data['tax_percentage'] ?? 0,
            $data['notes'] ?? null
        ]);
    }
    
    /**
     * Get all bookmakers for user
     */
    public function getByUser($userId, $includeArchived = false) {
        $sql = 'SELECT * FROM bookmakers WHERE user_id = ?';
        $params = [$userId];
        
        if (!$includeArchived) {
            $sql .= ' AND is_archived = 0';
        }
        
        $sql .= ' ORDER BY name';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get bookmaker by ID
     */
    public function getById($bookmakerId, $userId) {
        $stmt = $this->db->prepare('SELECT * FROM bookmakers WHERE id = ? AND user_id = ?');
        $stmt->execute([$bookmakerId, $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Update bookmaker
     */
    public function update($bookmakerId, $userId, $data) {
        $updates = [];
        $values = [];
        
        $allowedFields = ['name', 'url', 'account_balance', 'bonus_balance', 'tax_percentage', 'notes', 'is_archived'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updates)) return false;
        
        $values[] = $bookmakerId;
        $values[] = $userId;
        $sql = 'UPDATE bookmakers SET ' . implode(', ', $updates) . ' WHERE id = ? AND user_id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete bookmaker
     */
    public function delete($bookmakerId, $userId) {
        $stmt = $this->db->prepare('DELETE FROM bookmakers WHERE id = ? AND user_id = ?');
        return $stmt->execute([$bookmakerId, $userId]);
    }
    
    /**
     * Get bookmaker statistics
     */
    public function getStatistics($bookmakerId, $userId) {
        $stmt = $this->db->prepare('
            SELECT
                COUNT(*) as total_bets,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as won_bets,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as lost_bets,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cashout_bets,
                SUM(stake) as total_staked,
                SUM(CASE WHEN actual_return IS NOT NULL THEN actual_return ELSE 0 END) as total_returned,
                SUM(CASE WHEN actual_return IS NOT NULL THEN (actual_return - stake) ELSE 0 END) as profit,
                SUM(CASE WHEN actual_return IS NOT NULL THEN (actual_return - stake) ELSE 0 END) as profit_loss
            FROM bets
            WHERE bookmaker_id = ? AND user_id = ? AND status IN (?, ?, ?)
        ');
        
        $stmt->execute([
            BET_STATUS_WON,
            BET_STATUS_LOST,
            BET_STATUS_CASHOUT,
            $bookmakerId,
            $userId,
            BET_STATUS_WON,
            BET_STATUS_LOST,
            BET_STATUS_CASHOUT
        ]);
        
        return $stmt->fetch();
    }
}


