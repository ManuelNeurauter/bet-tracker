<?php
/**
 * Bet Model
 */

class Bet {
    private $db;

    /** Sort options for the bet list: key => ORDER BY clause */
    const SORTS = [
        'date_desc' => 'COALESCE(b.event_date, b.created_at) DESC, b.id DESC',
        'date_asc' => 'COALESCE(b.event_date, b.created_at) ASC, b.id ASC',
        'odds_desc' => 'b.odds DESC, b.id DESC',
        'odds_asc' => 'b.odds ASC, b.id DESC',
        'stake_desc' => 'b.stake DESC, b.id DESC',
        'stake_asc' => 'b.stake ASC, b.id DESC',
        'pl_desc' => '(COALESCE(b.actual_return, b.stake) - b.stake) DESC, b.id DESC',
        'pl_asc' => '(COALESCE(b.actual_return, b.stake) - b.stake) ASC, b.id DESC',
        'created_desc' => 'b.created_at DESC, b.id DESC',
    ];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get daily summary (stakes, returns, profit) between dates
     */
    public function getDailySummary($userId, $startDate, $endDate) {
        $startDateTime = $startDate . ' 00:00:00';
        $endDateExclusive = date('Y-m-d 00:00:00', strtotime($endDate . ' +1 day'));

        $stmt = $this->db->prepare("
            SELECT DATE(COALESCE(event_date, created_at)) as day,
                   COUNT(id) as bet_count,
                   SUM(stake) as total_staked,
                   SUM(CASE WHEN status IN ('won', 'lost', 'cashout') THEN COALESCE(actual_return, 0) ELSE 0 END) as total_returned,
                   SUM(CASE WHEN status IN ('won', 'lost', 'cashout') THEN (COALESCE(actual_return, 0) - stake) ELSE 0 END) as profit_loss,
                   SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
            FROM bets
            WHERE user_id = ? AND COALESCE(event_date, created_at) >= ? AND COALESCE(event_date, created_at) < ?
            GROUP BY DATE(COALESCE(event_date, created_at))
        ");
        $stmt->execute([$userId, $startDateTime, $endDateExclusive]);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['day']] = [
                'bet_count' => (int)$r['bet_count'],
                'pending_count' => (int)$r['pending_count'],
                'total_staked' => (float)$r['total_staked'],
                'total_returned' => (float)$r['total_returned'],
                'profit_loss' => (float)$r['profit_loss']
            ];
        }
        return $out;
    }

    /**
     * Get bets for a user between dates, grouped by day
     */
    public function getBetsByDateRange($userId, $startDate, $endDate) {
        $startDateTime = $startDate . ' 00:00:00';
        $endDateExclusive = date('Y-m-d 00:00:00', strtotime($endDate . ' +1 day'));

        $stmt = $this->db->prepare('
            SELECT b.id, b.event_name, b.selection, COALESCE(b.event_date, b.created_at) as event_date,
                   b.odds, b.stake, b.status, b.actual_return
            FROM bets b
            WHERE b.user_id = ? AND COALESCE(b.event_date, b.created_at) >= ? AND COALESCE(b.event_date, b.created_at) < ?
            ORDER BY COALESCE(b.event_date, b.created_at) ASC
        ');
        $stmt->execute([$userId, $startDateTime, $endDateExclusive]);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $r) {
            $d = date('Y-m-d', strtotime($r['event_date']));
            $r['event_name'] = plainText($r['event_name']);
            $r['selection'] = plainText($r['selection']);
            $r['odds_display'] = formatOdds($r['odds']);
            $out[$d][] = $r;
        }
        return $out;
    }

    /**
     * Create a new bet
     */
    public function create($data) {
        $stmt = $this->db->prepare('
            INSERT INTO bets (
                user_id, bookmaker_id, sport_id, competition_id,
                event_name, event_date, bet_type, selection, odds, stake,
                potential_return, status, tax_amount, each_way, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $data['tax_amount'] ?? 0,
            $data['each_way'] ?? 0,
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
                   s.name as sport_name,
                   c.name as competition_name
            FROM bets b
            LEFT JOIN bookmakers bm ON b.bookmaker_id = bm.id
            LEFT JOIN sports s ON b.sport_id = s.id
            LEFT JOIN competitions c ON b.competition_id = c.id
            WHERE b.id = ? AND b.user_id = ?
        ');
        $stmt->execute([$betId, $userId]);
        return $stmt->fetch();
    }

    /**
     * Append WHERE conditions for the bet list filters
     */
    private function applyFilters(&$sql, &$params, $filters, $skipStatus = false) {
        if (!$skipStatus && !empty($filters['status'])) {
            $sql .= ' AND b.status = ?';
            $params[] = $filters['status'];
        }

        $exact = ['sport_id' => 'b.sport_id', 'bookmaker_id' => 'b.bookmaker_id', 'bet_type' => 'b.bet_type'];
        foreach ($exact as $key => $column) {
            if (!empty($filters[$key])) {
                $sql .= " AND $column = ?";
                $params[] = $filters[$key];
            }
        }

        if (!empty($filters['tag_id'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM bet_tags bt WHERE bt.bet_id = b.id AND bt.tag_id = ?)';
            $params[] = $filters['tag_id'];
        }

        if (!empty($filters['tipster_id'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM bet_tipsters btp WHERE btp.bet_id = b.id AND btp.tipster_id = ?)';
            $params[] = $filters['tipster_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(COALESCE(b.event_date, b.created_at)) >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(COALESCE(b.event_date, b.created_at)) <= ?';
            $params[] = $filters['date_to'];
        }

        $ranges = [
            'min_odds' => 'b.odds >= ?', 'max_odds' => 'b.odds <= ?',
            'min_stake' => 'b.stake >= ?', 'max_stake' => 'b.stake <= ?',
        ];
        foreach ($ranges as $key => $condition) {
            if (isset($filters[$key]) && $filters[$key] !== '' && is_numeric($filters[$key])) {
                $sql .= " AND $condition";
                $params[] = (float)$filters[$key];
            }
        }

        if (!empty($filters['search'])) {
            $sql .= ' AND (b.event_name LIKE ? OR b.selection LIKE ? OR b.notes LIKE ?)';
            $searchTerm = '%' . $filters['search'] . '%';
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
        }
    }

    /**
     * Get all bets for user with filters
     */
    public function getByUser($userId, $filters = [], $page = 1, $limit = ITEMS_PER_PAGE, $sort = 'date_desc') {
        $sql = 'SELECT b.*, s.name as sport_name, bm.name as bookmaker_name
                FROM bets b
                LEFT JOIN sports s ON b.sport_id = s.id
                LEFT JOIN bookmakers bm ON b.bookmaker_id = bm.id
                WHERE b.user_id = ?';
        $params = [$userId];

        $this->applyFilters($sql, $params, $filters);

        $orderBy = self::SORTS[$sort] ?? self::SORTS['date_desc'];
        $sql .= ' ORDER BY ' . $orderBy;

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)(($page - 1) * $limit);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count bets for user
     */
    public function countByUser($userId, $filters = []) {
        $sql = 'SELECT COUNT(*) as count FROM bets b WHERE b.user_id = ?';
        $params = [$userId];

        $this->applyFilters($sql, $params, $filters);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Count bets per status for the current filters (ignoring the status filter)
     */
    public function countByStatus($userId, $filters = []) {
        $sql = 'SELECT b.status, COUNT(*) as count FROM bets b WHERE b.user_id = ?';
        $params = [$userId];

        $this->applyFilters($sql, $params, $filters, true);
        $sql .= ' GROUP BY b.status';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $counts = array_fill_keys(array_keys(betStatuses()), 0);
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }
        return $counts;
    }

    /**
     * Totals (stake, returns, profit) for the current filters
     */
    public function getTotals($userId, $filters = []) {
        $sql = "SELECT COUNT(*) as bet_count,
                       COALESCE(SUM(b.stake), 0) as total_staked,
                       COALESCE(SUM(CASE WHEN b.status IN ('won', 'lost', 'cashout') THEN COALESCE(b.actual_return, 0) - b.stake ELSE 0 END), 0) as profit_loss
                FROM bets b WHERE b.user_id = ?";
        $params = [$userId];

        $this->applyFilters($sql, $params, $filters);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
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
            'potential_return', 'status', 'actual_return', 'tax_amount', 'cashout_amount',
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
            ORDER BY b.created_at DESC, b.id DESC
            LIMIT ' . (int)$limit
        );
        $stmt->execute([$userId]);
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
            ORDER BY COALESCE(b.event_date, b.created_at) ASC
        ');
        $stmt->execute([$userId, BET_STATUS_PENDING]);
        return $stmt->fetchAll();
    }

    /**
     * Settled bets (won, lost, cashed out) with names, oldest first
     */
    public function getSettledBets($userId) {
        $stmt = $this->db->prepare("
            SELECT b.id, b.event_name, b.selection, b.bet_type, b.odds, b.stake, b.actual_return, b.status,
                   COALESCE(b.event_date, b.created_at) as bet_date, COALESCE(b.settled_at, b.event_date, b.created_at) as settle_date,
                   s.name as sport_name, bm.name as bookmaker_name, c.name as competition_name
            FROM bets b
            LEFT JOIN sports s ON b.sport_id = s.id
            LEFT JOIN bookmakers bm ON b.bookmaker_id = bm.id
            LEFT JOIN competitions c ON b.competition_id = c.id
            WHERE b.user_id = ? AND b.status IN ('won', 'lost', 'cashout')
            ORDER BY settle_date ASC, b.id ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get profit/loss grouped by sport or bookmaker
     */
    public function getProfitByGroup($userId, $groupBy = 'sport_id') {
        $joins = [
            'sport_id' => ['LEFT JOIN sports g ON b.sport_id = g.id', 'sport_name'],
            'bookmaker_id' => ['LEFT JOIN bookmakers g ON b.bookmaker_id = g.id', 'bookmaker_name'],
        ];
        if (!isset($joins[$groupBy])) {
            return [];
        }
        [$join, $alias] = $joins[$groupBy];

        $stmt = $this->db->prepare("
            SELECT
                g.id,
                g.name as $alias,
                COUNT(b.id) as bet_count,
                SUM(CASE WHEN b.status = 'won' THEN 1 ELSE 0 END) as won_count,
                SUM(CASE WHEN b.status = 'lost' THEN 1 ELSE 0 END) as lost_count,
                SUM(b.stake) as total_staked,
                SUM(COALESCE(b.actual_return, 0)) as total_returned,
                SUM(COALESCE(b.actual_return, 0) - b.stake) as profit_loss
            FROM bets b
            $join
            WHERE b.user_id = ? AND b.status IN ('won', 'lost', 'cashout')
            GROUP BY g.id, g.name
            ORDER BY profit_loss DESC
        ");
        $stmt->execute([$userId]);

        // Calculate ROI for each row
        $results = $stmt->fetchAll();
        foreach ($results as &$row) {
            $row['roi'] = ($row['total_staked'] > 0) ? round(($row['profit_loss'] / $row['total_staked']) * 100, 1) : 0;
        }

        return $results;
    }
}
