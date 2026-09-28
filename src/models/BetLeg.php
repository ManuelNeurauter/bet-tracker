<?php
/**
 * Bet Leg Model - the individual selections of a multiple bet
 */

class BetLeg {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Legs of one bet, in order
     */
    public function getByBet($betId) {
        $stmt = $this->db->prepare('SELECT * FROM bet_legs WHERE bet_id = ? ORDER BY leg_number, id');
        $stmt->execute([$betId]);
        return $stmt->fetchAll();
    }

    /**
     * Leg counts for several bets: bet_id => count
     */
    public function countByBets(array $betIds) {
        $betIds = array_values(array_filter(array_map('intval', $betIds)));
        if (!$betIds) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($betIds), '?'));
        $stmt = $this->db->prepare("SELECT bet_id, COUNT(*) AS n FROM bet_legs WHERE bet_id IN ($placeholders) GROUP BY bet_id");
        $stmt->execute($betIds);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int)$row['bet_id']] = (int)$row['n'];
        }
        return $out;
    }

    /**
     * Replace all legs of a bet with the given list
     */
    public function replaceForBet($betId, array $legs) {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM bet_legs WHERE bet_id = ?')->execute([$betId]);
            $stmt = $this->db->prepare('
                INSERT INTO bet_legs (bet_id, leg_number, event_name, selection, odds, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            foreach (array_values($legs) as $i => $leg) {
                $stmt->execute([$betId, $i + 1, $leg['event_name'], $leg['selection'], $leg['odds'], $leg['status']]);
            }
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Set the result of one leg. The caller must have checked the bet belongs to the user.
     */
    public function updateStatus($legId, $betId, $status) {
        $find = $this->db->prepare('SELECT id FROM bet_legs WHERE id = ? AND bet_id = ?');
        $find->execute([$legId, $betId]);
        if (!$find->fetch()) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE bet_legs SET status = ? WHERE id = ? AND bet_id = ?');
        return $stmt->execute([$status, $legId, $betId]);
    }

    /**
     * Mark every open leg of a bet as won (a chained bet only wins if all its legs won)
     */
    public function settleOpenAsWon($betId) {
        $stmt = $this->db->prepare('UPDATE bet_legs SET status = ? WHERE bet_id = ? AND status = ?');
        return $stmt->execute([BET_STATUS_WON, $betId, BET_STATUS_PENDING]);
    }
}
