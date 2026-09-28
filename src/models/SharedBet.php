<?php
/**
 * Shared Bet Model
 *
 * A shared bet is one slip that several users pay into. The owner logs the real bet at their
 * bookmaker. Each partner is invited by email with the part of the stake they put in; on
 * accepting, they get their own copy of the bet with that stake, booked on their "Shared bets"
 * bookkeeper. When the owner settles, every copy gets the same share of the return, the
 * partner's bookkeeper moves by their profit or loss, and the owner's bookkeeper moves the
 * other way, because the owner's bookmaker paid out the whole slip.
 */

class SharedBet {
    const STATUS_INVITED = 'invited';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_DECLINED = 'declined';

    const BOOKKEEPER_NAME = 'Shared bets';

    /** Fields copied from the owner's bet onto every partner copy */
    const COPIED_FIELDS = ['sport_id', 'competition_id', 'event_name', 'event_date', 'bet_type', 'selection', 'odds', 'each_way'];

    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Everyone a bet is shared with (not declined), with their usernames
     */
    public function getByBet($betId) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.username
            FROM bet_shares s
            LEFT JOIN users u ON u.id = s.user_id
            WHERE s.bet_id = ? AND s.status <> ?
            ORDER BY s.status = 'accepted' DESC, s.created_at ASC, s.id ASC
        ");
        $stmt->execute([$betId, self::STATUS_DECLINED]);
        return $stmt->fetchAll();
    }

    /**
     * Total stake partners hold or have been offered on a bet
     */
    public function partnerStake($betId, $excludeShareId = null) {
        $sql = 'SELECT COALESCE(SUM(stake), 0) FROM bet_shares WHERE bet_id = ? AND status <> ?';
        $params = [$betId, self::STATUS_DECLINED];
        if ($excludeShareId) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeShareId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return round((float)$stmt->fetchColumn(), 2);
    }

    /**
     * The share row behind a partner's copy of a bet, with the owner's name
     */
    public function findByPartnerBet($partnerBetId) {
        $stmt = $this->db->prepare('
            SELECT s.*, o.username as owner_name
            FROM bet_shares s
            JOIN users o ON o.id = s.owner_id
            WHERE s.partner_bet_id = ?
        ');
        $stmt->execute([$partnerBetId]);
        return $stmt->fetch();
    }

    /**
     * Invitations waiting for this user (only on bets that are still open)
     */
    public function getInvitesFor($user) {
        $stmt = $this->db->prepare("
            SELECT s.*, o.username as owner_name,
                   b.event_name, b.selection, b.odds, b.stake as total_stake, b.event_date, b.bet_type,
                   sp.name as sport_name
            FROM bet_shares s
            JOIN bets b ON b.id = s.bet_id
            JOIN users o ON o.id = s.owner_id
            LEFT JOIN sports sp ON sp.id = b.sport_id
            WHERE s.status = ? AND b.status = ?
              AND (s.user_id = ? OR (s.user_id IS NULL AND s.invited_email = ?))
            ORDER BY s.created_at DESC, s.id DESC
        ");
        $stmt->execute([self::STATUS_INVITED, BET_STATUS_PENDING, $user['id'], mb_strtolower($user['email'])]);
        return $stmt->fetchAll();
    }

    public function countInvitesFor($user) {
        return count($this->getInvitesFor($user));
    }

    /**
     * Bets this user owns that have partners, with their share rows
     */
    public function getSharedByOwner($ownerId) {
        $stmt = $this->db->prepare("
            SELECT b.*, s2.name as sport_name, bm.name as bookmaker_name
            FROM bets b
            LEFT JOIN sports s2 ON s2.id = b.sport_id
            LEFT JOIN bookmakers bm ON bm.id = b.bookmaker_id
            WHERE b.user_id = ? AND EXISTS (SELECT 1 FROM bet_shares s WHERE s.bet_id = b.id AND s.status <> ?)
            ORDER BY b.status = 'pending' DESC, COALESCE(b.event_date, b.created_at) DESC
        ");
        $stmt->execute([$ownerId, self::STATUS_DECLINED]);
        $bets = $stmt->fetchAll();
        foreach ($bets as &$bet) {
            $bet['shares'] = $this->getByBet($bet['id']);
        }
        return $bets;
    }

    /**
     * Partner copies this user holds in other people's bets
     */
    public function getSharedWithUser($userId) {
        $stmt = $this->db->prepare("
            SELECT b.*, s.id as share_id, o.username as owner_name, ob.stake as total_stake, sp.name as sport_name
            FROM bet_shares s
            JOIN bets b ON b.id = s.partner_bet_id
            JOIN bets ob ON ob.id = s.bet_id
            JOIN users o ON o.id = s.owner_id
            LEFT JOIN sports sp ON sp.id = b.sport_id
            WHERE s.user_id = ? AND s.status = ?
            ORDER BY b.status = 'pending' DESC, COALESCE(b.event_date, b.created_at) DESC
        ");
        $stmt->execute([$userId, self::STATUS_ACCEPTED]);
        return $stmt->fetchAll();
    }

    /**
     * Invite someone by email to put $stake into the owner's bet. Returns an error message or null.
     */
    public function invite(array $bet, array $owner, $email, $stake) {
        $email = mb_strtolower(trim((string)$email));
        $stake = round((float)$stake, 2);

        if ($bet['status'] !== BET_STATUS_PENDING) {
            return 'Only open bets can be shared.';
        }
        if (!isValidEmail($email)) {
            return 'Enter a valid email address.';
        }
        if ($email === mb_strtolower($owner['email'])) {
            return 'That is your own email. Invite the people you are betting with.';
        }
        if ($stake <= 0) {
            return 'Their stake must be greater than zero.';
        }

        $existing = $this->findByBetAndEmail($bet['id'], $email);
        if ($existing && $existing['status'] !== self::STATUS_DECLINED) {
            return 'That person is already on this bet.';
        }

        $available = round((float)$bet['stake'] - $this->partnerStake($bet['id']), 2);
        if ($stake >= $available) {
            return 'Their stake has to leave part of the bet for you. At most ' . formatCurrency(max(0, $available - 0.01)) . ' is left.';
        }

        $userModel = new User();
        $partner = $userModel->findByEmail($email);
        $partnerId = $partner ? (int)$partner['id'] : null;

        if ($existing) {
            // Asking again after a decline reuses the row
            $stmt = $this->db->prepare('UPDATE bet_shares SET stake = ?, status = ?, user_id = ?, partner_bet_id = NULL, responded_at = NULL, created_at = NOW() WHERE id = ?');
            $stmt->execute([$stake, self::STATUS_INVITED, $partnerId, $existing['id']]);
        } else {
            $stmt = $this->db->prepare('INSERT INTO bet_shares (bet_id, owner_id, user_id, invited_email, stake, status) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$bet['id'], $owner['id'], $partnerId, $email, $stake, self::STATUS_INVITED]);
        }
        return null;
    }

    private function findByBetAndEmail($betId, $email) {
        $stmt = $this->db->prepare('SELECT * FROM bet_shares WHERE bet_id = ? AND invited_email = ?');
        $stmt->execute([$betId, $email]);
        return $stmt->fetch();
    }

    public function getById($shareId) {
        $stmt = $this->db->prepare('
            SELECT s.*, b.status as bet_status, b.user_id as bet_owner_id
            FROM bet_shares s
            JOIN bets b ON b.id = s.bet_id
            WHERE s.id = ?
        ');
        $stmt->execute([$shareId]);
        return $stmt->fetch();
    }

    /**
     * Whether this invitation is addressed to the user
     */
    public function isInvitee(array $share, array $user) {
        if ($share['user_id'] !== null) {
            return (int)$share['user_id'] === (int)$user['id'];
        }
        return $share['invited_email'] === mb_strtolower($user['email']);
    }

    /**
     * Accept an invitation: create the partner's copy of the bet on their bookkeeper
     */
    public function accept(array $share, array $user) {
        if ($share['status'] !== self::STATUS_INVITED || !$this->isInvitee($share, $user)) {
            return 'That invitation is no longer open.';
        }
        if ($share['bet_status'] !== BET_STATUS_PENDING) {
            return 'That bet has already been settled.';
        }

        $betModel = new Bet();
        $ownerBet = $betModel->getById($share['bet_id'], $share['owner_id']);
        $bookkeeperId = $this->bookkeeperFor($user['id']);

        $data = ['user_id' => $user['id'], 'bookmaker_id' => $bookkeeperId, 'stake' => (float)$share['stake'], 'status' => BET_STATUS_PENDING, 'tax_amount' => 0];
        foreach (self::COPIED_FIELDS as $field) {
            $data[$field] = $ownerBet[$field];
        }
        $data['notes'] = 'Shared bet from ' . $this->ownerName($share['owner_id']) . '. Your stake is '
            . formatCurrency($share['stake'], $user['currency'] ?: null) . ' of ' . formatCurrency($ownerBet['stake'], $user['currency'] ?: null) . '.';

        $this->db->beginTransaction();
        try {
            $partnerBetId = $betModel->create($data);
            $stmt = $this->db->prepare('UPDATE bet_shares SET status = ?, user_id = ?, partner_bet_id = ?, responded_at = NOW() WHERE id = ?');
            $stmt->execute([self::STATUS_ACCEPTED, $user['id'], $partnerBetId, $share['id']]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return 'Could not join that bet. Please try again.';
        }
        return null;
    }

    public function decline(array $share, array $user) {
        if ($share['status'] !== self::STATUS_INVITED || !$this->isInvitee($share, $user)) {
            return 'That invitation is no longer open.';
        }
        $stmt = $this->db->prepare('UPDATE bet_shares SET status = ?, user_id = ?, responded_at = NOW() WHERE id = ?');
        $stmt->execute([self::STATUS_DECLINED, $user['id'], $share['id']]);
        return null;
    }

    /**
     * Take a partner off an open bet: the owner cancelling an invite or removing someone,
     * or a partner leaving. Their copy of the bet goes too; it has not settled, so no balance moves.
     */
    public function remove(array $share) {
        if ($share['bet_status'] !== BET_STATUS_PENDING) {
            return 'Settled bets keep their partners.';
        }
        $this->db->beginTransaction();
        try {
            if ($share['partner_bet_id']) {
                $stmt = $this->db->prepare('DELETE FROM bets WHERE id = ? AND user_id = ?');
                $stmt->execute([$share['partner_bet_id'], $share['user_id']]);
            }
            $stmt = $this->db->prepare('DELETE FROM bet_shares WHERE id = ?');
            $stmt->execute([$share['id']]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return 'Could not remove that person. Please try again.';
        }
        return null;
    }

    /**
     * Bring every partner copy in line with the owner's bet after it was edited or settled,
     * moving the difference between the partners' and the owner's bookkeepers.
     */
    public function syncFromOwner($betId, $ownerId) {
        $betModel = new Bet();
        $ownerBet = $betModel->getById($betId, $ownerId);
        if (!$ownerBet) {
            return;
        }

        $stmt = $this->db->prepare('SELECT * FROM bet_shares WHERE bet_id = ? AND status = ? AND partner_bet_id IS NOT NULL');
        $stmt->execute([$betId, self::STATUS_ACCEPTED]);
        $shares = $stmt->fetchAll();
        if (!$shares) {
            return;
        }

        // Invitations nobody answered before the result came in lapse
        if ($ownerBet['status'] !== BET_STATUS_PENDING) {
            $stmt = $this->db->prepare('UPDATE bet_shares SET status = ?, responded_at = NOW() WHERE bet_id = ? AND status = ?');
            $stmt->execute([self::STATUS_DECLINED, $betId, self::STATUS_INVITED]);
        }

        $ownerDelta = 0.0;
        $touched = [];
        foreach ($shares as $share) {
            $partnerBet = $betModel->getById($share['partner_bet_id'], $share['user_id']);
            if (!$partnerBet) {
                continue;
            }

            $update = [];
            foreach (self::COPIED_FIELDS as $field) {
                $update[$field] = $ownerBet[$field];
            }
            $update += $this->partnerSettlement($ownerBet, (float)$share['stake']);

            $oldImpact = self::impactOf($partnerBet);
            $newImpact = self::impactOf(array_merge($partnerBet, $update));
            $betModel->update($partnerBet['id'], $share['user_id'], $update);

            $delta = round($newImpact - $oldImpact, 2);
            if ($delta != 0) {
                $this->moveBookkeeper($share['user_id'], $delta, $partnerBet['bookmaker_id']);
                $ownerDelta -= $delta;
                $touched[$share['user_id']] = true;
            }
        }

        if (round($ownerDelta, 2) != 0) {
            $this->moveBookkeeper($ownerId, round($ownerDelta, 2));
            $touched[$ownerId] = true;
        }
        foreach (array_keys($touched) as $userId) {
            syncBankrollSnapshot($userId);
        }
    }

    /**
     * The owner is deleting their bet: take every partner copy and its settlement back out
     */
    public function beforeOwnerDelete(array $ownerBet) {
        $stmt = $this->db->prepare('SELECT * FROM bet_shares WHERE bet_id = ? AND status = ? AND partner_bet_id IS NOT NULL');
        $stmt->execute([$ownerBet['id'], self::STATUS_ACCEPTED]);
        $betModel = new Bet();
        $ownerDelta = 0.0;
        $touched = [];

        foreach ($stmt->fetchAll() as $share) {
            $partnerBet = $betModel->getById($share['partner_bet_id'], $share['user_id']);
            if (!$partnerBet) {
                continue;
            }
            $impact = self::impactOf($partnerBet);
            if ($impact != 0) {
                $this->moveBookkeeper($share['user_id'], -$impact, $partnerBet['bookmaker_id']);
                $ownerDelta += $impact;
                $touched[$share['user_id']] = true;
            }
            $betModel->delete($partnerBet['id'], $share['user_id']);
        }

        if (round($ownerDelta, 2) != 0) {
            $this->moveBookkeeper($ownerBet['user_id'], round($ownerDelta, 2));
            $touched[$ownerBet['user_id']] = true;
        }
        return array_keys($touched);
    }

    /**
     * A partner's status, return and cashout for their part of the owner's bet
     */
    private function partnerSettlement(array $ownerBet, $partnerStake) {
        $ratio = (float)$ownerBet['stake'] > 0 ? $partnerStake / (float)$ownerBet['stake'] : 0;
        $scale = function ($value) use ($ratio) {
            return $value === null ? null : round((float)$value * $ratio, 2);
        };
        return [
            'status' => $ownerBet['status'],
            'potential_return' => calculatePotentialReturn($partnerStake, (float)$ownerBet['odds']),
            'actual_return' => $scale($ownerBet['actual_return']),
            'cashout_amount' => $scale($ownerBet['cashout_amount']),
            'tax_amount' => $scale($ownerBet['tax_amount']) ?? 0,
            'settled_at' => $ownerBet['settled_at'],
        ];
    }

    private static function impactOf(array $bet) {
        return calculateSettlementImpact(
            $bet['status'],
            (float)$bet['stake'],
            (float)$bet['odds'],
            $bet['cashout_amount'] ?? null,
            $bet['actual_return'] ?? null,
            (float)($bet['tax_amount'] ?? 0)
        );
    }

    /**
     * Move a user's bookkeeper balance. Partner copies carry the bookkeeper they were booked on.
     */
    private function moveBookkeeper($userId, $delta, $bookmakerId = null) {
        $bookmakerModel = new Bookmaker();
        $bookmaker = $bookmakerId ? $bookmakerModel->getById($bookmakerId, $userId) : null;
        if (!$bookmaker) {
            $bookmaker = $bookmakerModel->getById($this->bookkeeperFor($userId), $userId);
        }
        $bookmakerModel->update($bookmaker['id'], $userId, [
            'account_balance' => round((float)$bookmaker['account_balance'] + $delta, 2),
        ]);
    }

    /**
     * The user's "Shared bets" bookkeeper, created the first time it is needed
     */
    public function bookkeeperFor($userId) {
        $id = $this->bookkeeperId($userId);
        if ($id) {
            return $id;
        }

        $bookmakerModel = new Bookmaker();
        $bookmakerModel->create($userId, [
            'name' => self::BOOKKEEPER_NAME,
            'notes' => 'Kept by BetLedger for shared bets. It shows what you and the people you bet with owe each other.',
        ]);
        $id = (int)$this->db->lastInsertId();
        $stmt = $this->db->prepare('INSERT INTO shared_bet_books (user_id, bookmaker_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE bookmaker_id = VALUES(bookmaker_id)');
        $stmt->execute([$userId, $id]);
        return $id;
    }

    public function bookkeeperId($userId) {
        $stmt = $this->db->prepare('SELECT bookmaker_id FROM shared_bet_books WHERE user_id = ?');
        $stmt->execute([$userId]);
        $id = $stmt->fetchColumn();
        return $id ? (int)$id : null;
    }

    private function ownerName($ownerId) {
        $userModel = new User();
        $owner = $userModel->findById($ownerId);
        return $owner ? $owner['username'] : 'another user';
    }
}
