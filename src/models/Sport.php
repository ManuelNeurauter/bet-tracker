<?php
/**
 * Sport Model
 */

class Sport {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all sports
     */
    public function getAll() {
        $stmt = $this->db->prepare('SELECT * FROM sports ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get sport by ID
     */
    public function getById($sportId) {
        $stmt = $this->db->prepare('SELECT * FROM sports WHERE id = ?');
        $stmt->execute([$sportId]);
        return $stmt->fetch();
    }
    
    /**
     * Get competitions for sport
     */
    public function getCompetitions($sportId) {
        $stmt = $this->db->prepare('SELECT * FROM competitions WHERE sport_id = ? ORDER BY name');
        $stmt->execute([$sportId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get competition by ID
     */
    public function getCompetitionById($competitionId) {
        $stmt = $this->db->prepare('SELECT * FROM competitions WHERE id = ?');
        $stmt->execute([$competitionId]);
        return $stmt->fetch();
    }
}

?>
