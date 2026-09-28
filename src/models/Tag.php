<?php
/**
 * Tag Model
 */

class Tag {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new tag
     */
    public function create($userId, $name, $color = '#3498db') {
        $stmt = $this->db->prepare('
            INSERT INTO tags (user_id, name, color)
            VALUES (?, ?, ?)
        ');
        
        return $stmt->execute([$userId, $name, $color]);
    }
    
    /**
     * Get all tags for user
     */
    public function getByUser($userId) {
        $stmt = $this->db->prepare('SELECT * FROM tags WHERE user_id = ? ORDER BY name');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check whether the user already has a tag with this name
     */
    public function nameExists($userId, $name, $exceptId = null) {
        $stmt = $this->db->prepare('SELECT id FROM tags WHERE user_id = ? AND name = ? AND id <> ?');
        $stmt->execute([$userId, $name, (int)$exceptId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Get tag by ID
     */
    public function getById($tagId, $userId) {
        $stmt = $this->db->prepare('SELECT * FROM tags WHERE id = ? AND user_id = ?');
        $stmt->execute([$tagId, $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Update tag
     */
    public function update($tagId, $userId, $name, $color) {
        $stmt = $this->db->prepare('UPDATE tags SET name = ?, color = ? WHERE id = ? AND user_id = ?');
        return $stmt->execute([$name, $color, $tagId, $userId]);
    }
    
    /**
     * Delete tag
     */
    public function delete($tagId, $userId) {
        $this->db->prepare('DELETE FROM bet_tags WHERE tag_id = ?')->execute([$tagId]);
        $stmt = $this->db->prepare('DELETE FROM tags WHERE id = ? AND user_id = ?');
        return $stmt->execute([$tagId, $userId]);
    }
    
    /**
     * Add tag to bet
     */
    public function addToBet($betId, $tagId) {
        $stmt = $this->db->prepare('INSERT IGNORE INTO bet_tags (bet_id, tag_id) VALUES (?, ?)');
        return $stmt->execute([$betId, $tagId]);
    }
    
    /**
     * Remove tag from bet
     */
    public function removeFromBet($betId, $tagId) {
        $stmt = $this->db->prepare('DELETE FROM bet_tags WHERE bet_id = ? AND tag_id = ?');
        return $stmt->execute([$betId, $tagId]);
    }
    
    /**
     * Get tags for bet
     */
    public function getByBet($betId) {
        $stmt = $this->db->prepare('
            SELECT t.* FROM tags t
            JOIN bet_tags bt ON t.id = bt.tag_id
            WHERE bt.bet_id = ?
        ');
        $stmt->execute([$betId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get tags for several bets, keyed by bet id
     */
    public function getByBets(array $betIds) {
        $betIds = array_values(array_filter(array_map('intval', $betIds)));
        if (!$betIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($betIds), '?'));
        $stmt = $this->db->prepare("
            SELECT bt.bet_id, t.* FROM tags t
            JOIN bet_tags bt ON t.id = bt.tag_id
            WHERE bt.bet_id IN ($placeholders)
            ORDER BY t.name
        ");
        $stmt->execute($betIds);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['bet_id']][] = $row;
        }
        return $out;
    }

    /**
     * Usage and profit/loss per tag
     */
    public function getStatistics($userId) {
        $stmt = $this->db->prepare("
            SELECT t.id,
                   COUNT(b.id) as total_bets,
                   SUM(CASE WHEN b.status = 'won' THEN 1 ELSE 0 END) as won_bets,
                   SUM(CASE WHEN b.status IN ('won', 'lost', 'cashout') THEN 1 ELSE 0 END) as settled_bets,
                   SUM(CASE WHEN b.status IN ('won', 'lost', 'cashout') THEN b.stake ELSE 0 END) as total_staked,
                   SUM(CASE WHEN b.status IN ('won', 'lost', 'cashout') THEN COALESCE(b.actual_return, 0) - b.stake ELSE 0 END) as profit_loss
            FROM tags t
            LEFT JOIN bet_tags bt ON t.id = bt.tag_id
            LEFT JOIN bets b ON b.id = bt.bet_id AND b.user_id = t.user_id
            WHERE t.user_id = ?
            GROUP BY t.id
        ");
        $stmt->execute([$userId]);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[$row['id']] = $row;
        }
        return $out;
    }

    /**
     * Get bets by tag
     */
    public function getBetsByTag($tagId, $userId) {
        $stmt = $this->db->prepare('
            SELECT b.* FROM bets b
            JOIN bet_tags bt ON b.id = bt.bet_id
            WHERE bt.tag_id = ? AND b.user_id = ?
        ');
        $stmt->execute([$tagId, $userId]);
        return $stmt->fetchAll();
    }
}


