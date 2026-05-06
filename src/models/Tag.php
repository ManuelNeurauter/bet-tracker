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

?>
