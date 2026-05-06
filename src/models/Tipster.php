<?php
/**
 * Tipster Model
 */

class Tipster {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new tipster
     */
    public function create($userId, $name, $sourceUrl = null, $notes = null) {
        $stmt = $this->db->prepare('
            INSERT INTO tipsters (user_id, name, source_url, notes)
            VALUES (?, ?, ?, ?)
        ');
        
        return $stmt->execute([$userId, $name, $sourceUrl, $notes]);
    }
    
    /**
     * Get all tipsters for user
     */
    public function getByUser($userId, $activeOnly = true) {
        $sql = 'SELECT * FROM tipsters WHERE user_id = ?';
        $params = [$userId];
        
        if ($activeOnly) {
            $sql .= ' AND is_active = 1';
        }
        
        $sql .= ' ORDER BY name';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get tipster by ID
     */
    public function getById($tipsterId, $userId) {
        $stmt = $this->db->prepare('SELECT * FROM tipsters WHERE id = ? AND user_id = ?');
        $stmt->execute([$tipsterId, $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Update tipster
     */
    public function update($tipsterId, $userId, $data) {
        $updates = [];
        $values = [];
        
        $allowedFields = ['name', 'source_url', 'notes', 'is_active'];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updates)) return false;
        
        $values[] = $tipsterId;
        $values[] = $userId;
        $sql = 'UPDATE tipsters SET ' . implode(', ', $updates) . ' WHERE id = ? AND user_id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete tipster
     */
    public function delete($tipsterId, $userId) {
        $this->db->prepare('DELETE FROM bet_tipsters WHERE tipster_id = ?')->execute([$tipsterId]);
        $stmt = $this->db->prepare('DELETE FROM tipsters WHERE id = ? AND user_id = ?');
        return $stmt->execute([$tipsterId, $userId]);
    }
    
    /**
     * Add tipster to bet
     */
    public function addToBet($betId, $tipsterId) {
        $stmt = $this->db->prepare('INSERT IGNORE INTO bet_tipsters (bet_id, tipster_id) VALUES (?, ?)');
        return $stmt->execute([$betId, $tipsterId]);
    }
    
    /**
     * Get tipsters for bet
     */
    public function getByBet($betId) {
        $stmt = $this->db->prepare('
            SELECT t.* FROM tipsters t
            JOIN bet_tipsters bt ON t.id = bt.tipster_id
            WHERE bt.bet_id = ?
        ');
        $stmt->execute([$betId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get tipster statistics
     */
    public function getStatistics($tipsterId, $userId) {
        $stmt = $this->db->prepare('
            SELECT
                COUNT(DISTINCT b.id) as total_bets,
                SUM(CASE WHEN b.status = ? THEN 1 ELSE 0 END) as won_bets,
                SUM(CASE WHEN b.status = ? THEN 1 ELSE 0 END) as lost_bets,
                SUM(b.stake) as total_staked,
                SUM(CASE WHEN b.actual_return IS NOT NULL THEN b.actual_return ELSE 0 END) as total_returned,
                SUM(CASE WHEN b.actual_return IS NOT NULL THEN (b.actual_return - b.stake) ELSE 0 END) as profit
            FROM bets b
            JOIN bet_tipsters bt ON b.id = bt.bet_id
            WHERE bt.tipster_id = ? AND b.user_id = ? AND b.status IN (?, ?)
        ');
        
        $stmt->execute([
            BET_STATUS_WON,
            BET_STATUS_LOST,
            $tipsterId,
            $userId,
            BET_STATUS_WON,
            BET_STATUS_LOST
        ]);
        
        return $stmt->fetch();
    }
}


