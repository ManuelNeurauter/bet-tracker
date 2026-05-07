<?php
/**
 * Bet Model
 */

class Bet {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new bet
     */
    public function create($data) {
        $stmt = $this->db->prepare('
            INSERT INTO bets (
                user_id, bookmaker_id, sport_id, competition_id,
                event_name, event_date, bet_type, selection, odds, stake,
                potential_return, status, each_way, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        
        $potentialReturn = calculatePotentialReturn($data['stake'], $data['odds']);
        
        $success = $stmt->execute([
            $data['user_id'],
            $data['bookmaker_id'] ?? null,
            $data['sport_id'] ?? null,
            $data['competition_id'] ?? null,
            $data['event_name'],
            $data['event_date'] ?? null,
            $data['bet_type'] ?? BET_TYPE_SINGLE,
            $data['selection'],
            $data['odds'],
            $data['stake'],
            $potentialReturn,
            $data['status'] ?? BET_STATUS_PENDING,
            $data['each_way'] ?? false,
            $data['notes'] ?? null
        ]);
        
        if ($success) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    /**
     * Get bet by ID
     */
    public function getById($betId, $userId) {
        $stmt = $this->db->prepare('
            SELECT b.*, 
                   bm.name as bookmaker_name,
                   s.name as sport_name
            FROM bets b
            LEFT JOIN bookmakers bm ON b.bookmaker_id = bm.id
            LEFT JOIN sports s ON b.sport_id = s.id
            WHERE b.id = ? AND b.user_id = ?
        ');
        $stmt->execute([$betId, $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Get all bets for user with filters
     */
    public function getByUser($userId, $filters = [], $page = 1, $limit = ITEMS_PER_PAGE) {
        $sql = 'SELECT * FROM bets WHERE user_id = ?';
        $params = [$userId];
        
        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['sport_id'])) {
            $sql .= ' AND sport_id = ?';
            $params[] = $filters['sport_id'];
        }
        
        if (!empty($filters['bookmaker_id'])) {
            $sql .= ' AND bookmaker_id = ?';
            $params[] = $filters['bookmaker_id'];
        }
        
        if (!empty($filters['bet_type'])) {
            $sql .= ' AND bet_type = ?';
            $params[] = $filters['bet_type'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(event_date) >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(event_date) <= ?';
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['min_odds'])) {
            $sql .= ' AND odds >= ?';
            $params[] = $filters['min_odds'];
        }
        
        if (!empty($filters['max_odds'])) {
            $sql .= ' AND odds <= ?';
            $params[] = $filters['max_odds'];
        }
        
        if (!empty($filters['min_stake'])) {
            $sql .= ' AND stake >= ?';
            $params[] = $filters['min_stake'];
        }
        
        if (!empty($filters['max_stake'])) {
            $sql .= ' AND stake <= ?';
            $params[] = $filters['max_stake'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= ' AND (event_name LIKE ? OR selection LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = ($page - 1) * $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Count bets for user
     */
    public function countByUser($userId, $filters = []) {
        $sql = 'SELECT COUNT(*) as count FROM bets WHERE user_id = ?';
        $params = [$userId];
        
        if (!empty($filters['status'])) {
            $sql .= ' AND status = ?';
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['sport_id'])) {
            $sql .= ' AND sport_id = ?';
            $params[] = $filters['sport_id'];
        }
        
        if (!empty($filters['bookmaker_id'])) {
            $sql .= ' AND bookmaker_id = ?';
            $params[] = $filters['bookmaker_id'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    /**
     * Update bet
     */
    public function update($betId, $userId, $data) {
        $updates = [];
        $values = [];
        
        $allowedFields = [
            'bookmaker_id', 'sport_id', 'competition_id', 'event_name',
            'event_date', 'bet_type', 'selection', 'odds', 'stake',
            'potential_return', 'status', 'actual_return', 'cashout_amount',
            'each_way', 'notes', 'settled_at'
        ];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "$key = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updates)) return false;
        
        $values[] = $betId;
        $values[] = $userId;
        $sql = 'UPDATE bets SET ' . implode(', ', $updates) . ' WHERE id = ? AND user_id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Delete bet
     */
    public function delete($betId, $userId) {
        $stmt = $this->db->prepare('DELETE FROM bets WHERE id = ? AND user_id = ?');
        return $stmt->execute([$betId, $userId]);
    }
    
    /**
     * Get bets for dashboard (recent bets)
     */
    public function getRecentBets($userId, $limit = 10) {
        $stmt = $this->db->prepare('
            SELECT b.*, s.name as sport_name, bm.name as bookmaker_name
            FROM bets b
            LEFT JOIN sports s ON b.sport_id = s.id
            LEFT JOIN bookmakers bm ON b.bookmaker_id = bm.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
            LIMIT ?
        ');
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get pending bets
     */
    public function getPendingBets($userId) {
        $stmt = $this->db->prepare('
            SELECT b.*, s.name as sport_name, bm.name as bookmaker_name
            FROM bets b
            LEFT JOIN sports s ON b.sport_id = s.id
            LEFT JOIN bookmakers bm ON b.bookmaker_id = bm.id
            WHERE b.user_id = ? AND b.status = ?
            ORDER BY b.event_date ASC
        ');
        $stmt->execute([$userId, BET_STATUS_PENDING]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get profit/loss by sport
     */
    public function getProfitByGroup($userId, $groupBy = 'sport_id') {
        if ($groupBy === 'sport_id') {
            $stmt = $this->db->prepare('
                SELECT
                    s.id,
                    s.name as sport_name,
                    COUNT(b.id) as bet_count,
                    SUM(CASE WHEN b.status = ? THEN 1 ELSE 0 END) as won_count,
                    SUM(CASE WHEN b.status = ? THEN 1 ELSE 0 END) as lost_count,
                    SUM(b.stake) as total_staked,
                    SUM(CASE WHEN b.actual_return IS NOT NULL THEN b.actual_return ELSE 0 END) as total_returned,
                    SUM(CASE WHEN b.actual_return IS NOT NULL THEN (b.actual_return - b.stake) ELSE 0 END) as profit_loss
                FROM bets b
                LEFT JOIN sports s ON b.sport_id = s.id
                WHERE b.user_id = ? AND b.status IN (?, ?, ?)
                GROUP BY s.id, s.name
                ORDER BY profit_loss DESC
            ');
            $stmt->execute([BET_STATUS_WON, BET_STATUS_LOST, $userId, BET_STATUS_WON, BET_STATUS_LOST, BET_STATUS_CASHOUT]);
        } elseif ($groupBy === 'bookmaker_id') {
            $stmt = $this->db->prepare('
                SELECT
                    bm.id,
                    bm.name as bookmaker_name,
                    COUNT(b.id) as bet_count,
                    SUM(CASE WHEN b.status = ? THEN 1 ELSE 0 END) as won_count,
                    SUM(CASE WHEN b.status = ? THEN 1 ELSE 0 END) as lost_count,
                    SUM(b.stake) as total_staked,
                    SUM(CASE WHEN b.actual_return IS NOT NULL THEN b.actual_return ELSE 0 END) as total_returned,
                    SUM(CASE WHEN b.actual_return IS NOT NULL THEN (b.actual_return - b.stake) ELSE 0 END) as profit_loss
                FROM bets b
                LEFT JOIN bookmakers bm ON b.bookmaker_id = bm.id
                WHERE b.user_id = ? AND b.status IN (?, ?, ?)
                GROUP BY bm.id, bm.name
                ORDER BY profit_loss DESC
            ');
            $stmt->execute([BET_STATUS_WON, BET_STATUS_LOST, $userId, BET_STATUS_WON, BET_STATUS_LOST, BET_STATUS_CASHOUT]);
        }
        
        // Calculate ROI for each row
        $results = $stmt->fetchAll();
        foreach ($results as &$row) {
            $row['roi'] = ($row['total_staked'] > 0) ? round(($row['profit_loss'] / $row['total_staked']) * 100, 2) : 0;
        }
        
        return $results;
    }
}


