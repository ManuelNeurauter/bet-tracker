<?php
/**
 * Bet Controller
 */

class BetController {

    /**
     * Load the lists every bet form needs
     */
    private static function formData($userId, $keepBookmakerId = null) {
        $bookmakerModel = new Bookmaker();
        $sportModel = new Sport();
        $tagModel = new Tag();
        $tipsterModel = new Tipster();

        // Archived bookmakers are hidden, except the one an existing bet already uses
        $bookmakers = $bookmakerModel->getByUser($userId);
        // The Shared bets bookkeeper only holds partner copies, which the owner's bet drives
        $sharedBetModel = new SharedBet();
        $bookkeeperId = $sharedBetModel->bookkeeperId($userId);
        $bookmakers = array_values(array_filter($bookmakers, function ($bm) use ($bookkeeperId, $keepBookmakerId) {
            return (int)$bm['id'] !== $bookkeeperId || (int)$bm['id'] === (int)$keepBookmakerId;
        }));
        if ($keepBookmakerId && !in_array((int)$keepBookmakerId, array_map('intval', array_column($bookmakers, 'id')), true)) {
            $kept = $bookmakerModel->getById($keepBookmakerId, $userId);
            if ($kept) {
                $bookmakers[] = $kept;
            }
        }

        return [
            'bookmakers' => $bookmakers,
            'sports' => $sportModel->getAll(),
            'tags' => $tagModel->getByUser($userId),
            // Inactive tipsters are included; the form only shows them on bets that already use them
            'tipsters' => $tipsterModel->getByUser($userId, false),
        ];
    }

    /**
     * Read and validate the bet form. Returns [data, errors].
     */
    private static function readForm($userId, $currentStatus = null) {
        $sportId = isset($_POST['sport_id']) && ctype_digit((string)$_POST['sport_id']) ? (int)$_POST['sport_id'] : null;
        $competitionId = isset($_POST['competition_id']) && ctype_digit((string)$_POST['competition_id']) ? (int)$_POST['competition_id'] : null;

        $bookmakerId = !empty($_POST['bookmaker_id']) ? (int)$_POST['bookmaker_id'] : null;
        if ($bookmakerId) {
            $bookmakerModel = new Bookmaker();
            if (!$bookmakerModel->getById($bookmakerId, $userId)) {
                $bookmakerId = null;
            }
        }

        $status = postString('status', $currentStatus ?? BET_STATUS_PENDING);
        $statusError = null;
        if (!array_key_exists($status, selectableStatuses($currentStatus))) {
            $statusError = $status === BET_STATUS_PENDING
                ? 'A settled bet cannot go back to pending. Pick won, lost or cashed out.'
                : 'Pick a valid result for this bet.';
            $status = $currentStatus ?? BET_STATUS_PENDING;
        }
        $betType = postString('bet_type', BET_TYPE_SINGLE);
        if (!array_key_exists($betType, betTypes())) {
            $betType = BET_TYPE_SINGLE;
        }

        $eventDate = postString('event_date');
        $eventDate = ($eventDate !== '' && strtotime($eventDate) !== false) ? date('Y-m-d H:i:s', strtotime($eventDate)) : null;

        $data = [
            'bookmaker_id' => $bookmakerId,
            'sport_id' => $sportId,
            'competition_id' => $sportId ? $competitionId : null,
            'event_name' => mb_substr(postString('event_name'), 0, 255),
            'event_date' => $eventDate,
            'bet_type' => $betType,
            'selection' => mb_substr(postString('selection'), 0, 500),
            'odds' => (float)(postFloatOrNull('odds') ?? 0),
            'stake' => (float)(postFloatOrNull('stake') ?? 0),
            'status' => $status,
            'actual_return' => postFloatOrNull('actual_return'),
            'tax_amount' => max(0, (float)(postFloatOrNull('tax_amount') ?? 0)),
            'cashout_amount' => postFloatOrNull('cashout_amount'),
            'each_way' => isset($_POST['each_way']) ? 1 : 0,
            'notes' => postString('notes'),
        ];

        // The return override only applies to wins (boosts, dead heats). The field stays in the
        // form while hidden, so a stale value must not leak into a lost or cashed-out bet.
        if ($data['status'] !== BET_STATUS_WON) {
            $data['actual_return'] = null;
        }
        if ($data['status'] === BET_STATUS_CASHOUT) {
            // A cashout returns exactly what the bookmaker paid, same as quick settle
            $data['actual_return'] = $data['cashout_amount'];
        } else {
            $data['cashout_amount'] = null;
        }

        $errors = [];
        if ($statusError) $errors[] = $statusError;
        if ($data['event_name'] === '') $errors[] = 'Event name is required.';
        if ($data['selection'] === '') $errors[] = 'Selection is required.';
        if ($data['odds'] < 1.01) $errors[] = 'Odds must be at least 1.01.';
        if ($data['stake'] <= 0) $errors[] = 'Stake must be greater than zero.';
        if ($data['status'] === BET_STATUS_CASHOUT && $data['cashout_amount'] === null) {
            $errors[] = 'Enter the cashout amount you received.';
        }

        return [$data, $errors];
    }

    /**
     * Apply a change in settlement to a bookmaker's balance
     */
    private static function adjustBookmaker($bookmakerId, $userId, $delta) {
        if (!$bookmakerId || round($delta, 2) == 0) {
            return;
        }
        $bookmakerModel = new Bookmaker();
        $bookmaker = $bookmakerModel->getById($bookmakerId, $userId);
        if ($bookmaker) {
            $bookmakerModel->update($bookmakerId, $userId, [
                'account_balance' => round((float)$bookmaker['account_balance'] + $delta, 2),
            ]);
        }
    }

    /**
     * A partner's copy of a shared bet follows the owner's bet; only the owner can change it
     */
    private static function guardPartnerCopy($bet) {
        if (!empty($bet['shared_from'])) {
            setFlash('error', 'This bet is shared by ' . e($bet['shared_from']) . '. Only they can edit, settle or delete it.');
            redirect('/bets/' . (int)$bet['id']);
        }
    }

    /**
     * Replace the tags and tipsters linked to a bet
     */
    private static function syncLinks($betId, $userId) {
        $tagModel = new Tag();
        $tipsterModel = new Tipster();
        $ownTags = array_column($tagModel->getByUser($userId), 'id');
        $ownTipsters = array_column($tipsterModel->getByUser($userId, false), 'id');

        foreach ($tagModel->getByBet($betId) as $tag) {
            $tagModel->removeFromBet($betId, $tag['id']);
        }
        foreach ((array)($_POST['tags'] ?? []) as $tagId) {
            if (in_array((int)$tagId, array_map('intval', $ownTags), true)) {
                $tagModel->addToBet($betId, (int)$tagId);
            }
        }

        foreach ($tipsterModel->getByBet($betId) as $tipster) {
            $tipsterModel->removeFromBet($betId, $tipster['id']);
        }
        foreach ((array)($_POST['tipsters'] ?? []) as $tipsterId) {
            if (in_array((int)$tipsterId, array_map('intval', $ownTipsters), true)) {
                $tipsterModel->addToBet($betId, (int)$tipsterId);
            }
        }
    }

    /**
     * Show add bet page
     */
    public static function showAdd() {
        requireLogin();

        $userId = getCurrentUserId();
        extract(self::formData($userId));
        $bet = null;
        $selectedTags = [];
        $selectedTipsters = [];
        $old = $_SESSION['old_bet'] ?? null;
        unset($_SESSION['old_bet']);

        include __DIR__ . '/../views/bets/add.php';
    }

    /**
     * Handle add bet form submission
     */
    public static function handleAdd() {
        requireLogin();

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Your session expired. Please try again.');
            redirect('/bets/add');
        }

        $userId = getCurrentUserId();
        [$data, $errors] = self::readForm($userId);
        $data['user_id'] = $userId;

        if ($errors) {
            $_SESSION['old_bet'] = $_POST;
            setFlash('error', implode('<br>', array_map('e', $errors)));
            redirect('/bets/add');
        }

        $bet = new Bet();
        $betId = $bet->create($data);

        if (!$betId) {
            setFlash('error', 'Failed to create bet. Please try again.');
            redirect('/bets/add');
        }

        $settledReturn = calculateSettlementReturn(
            $data['status'],
            $data['stake'],
            $data['odds'],
            $data['cashout_amount'],
            $data['actual_return'],
            $data['tax_amount']
        );

        if ($settledReturn !== null) {
            $bet->update($betId, $userId, [
                'actual_return' => $settledReturn,
                'cashout_amount' => $data['cashout_amount'],
                'settled_at' => date('Y-m-d H:i:s'),
            ]);

            $impact = calculateSettlementImpact($data['status'], $data['stake'], $data['odds'], $data['cashout_amount'], $settledReturn, $data['tax_amount']);
            if ($data['bookmaker_id'] && $impact != 0) {
                self::adjustBookmaker($data['bookmaker_id'], $userId, $impact);
                syncBankrollSnapshot($userId);
            }
        }

        self::syncLinks($betId, $userId);

        setFlash('success', 'Bet added.');
        redirect(isset($_POST['add_another']) ? '/bets/add' : '/bets/' . $betId);
    }

    /**
     * Show bet details
     */
    public static function show($betId) {
        requireLogin();

        $userId = getCurrentUserId();
        $betModel = new Bet();
        $bet = $betModel->getById($betId, $userId);

        if (!$bet) {
            notFound('That bet does not exist or was deleted.');
        }

        $tagModel = new Tag();
        $tipsterModel = new Tipster();

        $tags = $tagModel->getByBet($betId);
        $tipsters = $tipsterModel->getByBet($betId);

        $sharedBetModel = new SharedBet();
        $partnerShare = !empty($bet['shared_from']) ? $sharedBetModel->findByPartnerBet($betId) : null;
        $shares = $sharedBetModel->getByBet($partnerShare ? $partnerShare['bet_id'] : $betId);
        // The whole slip, which a partner's copy only holds part of
        $slip = $partnerShare ? $betModel->getById($partnerShare['bet_id'], $partnerShare['owner_id']) : $bet;
        $oldShare = $_SESSION['old_share'] ?? null;
        unset($_SESSION['old_share']);

        include __DIR__ . '/../views/bets/view.php';
    }

    /**
     * Show edit bet page
     */
    public static function showEdit($betId) {
        requireLogin();

        $userId = getCurrentUserId();
        $betModel = new Bet();
        $bet = $betModel->getById($betId, $userId);

        if (!$bet) {
            notFound('That bet does not exist or was deleted.');
        }

        self::guardPartnerCopy($bet);

        extract(self::formData($userId, $bet['bookmaker_id']));
        $tagModel = new Tag();
        $tipsterModel = new Tipster();
        $selectedTags = array_map('intval', array_column($tagModel->getByBet($betId), 'id'));
        $selectedTipsters = array_map('intval', array_column($tipsterModel->getByBet($betId), 'id'));
        $old = $_SESSION['old_bet'] ?? null;
        unset($_SESSION['old_bet']);

        include __DIR__ . '/../views/bets/edit.php';
    }

    /**
     * Handle edit bet form submission
     */
    public static function handleEdit($betId) {
        requireLogin();

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Your session expired. Please try again.');
            redirect('/bets/' . $betId . '/edit');
        }

        $userId = getCurrentUserId();
        $betModel = new Bet();

        // Get current bet before updating
        $currentBet = $betModel->getById($betId, $userId);
        if (!$currentBet) {
            notFound('That bet does not exist or was deleted.');
        }

        self::guardPartnerCopy($currentBet);

        [$data, $errors] = self::readForm($userId, $currentBet['status']);
        $sharedBetModel = new SharedBet();
        $partnerStake = $sharedBetModel->partnerStake($betId);
        if ($partnerStake > 0 && $data['stake'] <= $partnerStake) {
            $errors[] = 'The people you share this bet with put in ' . formatCurrency($partnerStake) . '. The stake has to be more than that.';
        }
        if ($errors) {
            $_SESSION['old_bet'] = $_POST;
            setFlash('error', implode('<br>', array_map('e', $errors)));
            redirect('/bets/' . $betId . '/edit');
        }

        $data['potential_return'] = calculatePotentialReturn($data['stake'], $data['odds']);
        $data['actual_return'] = calculateSettlementReturn(
            $data['status'],
            $data['stake'],
            $data['odds'],
            $data['cashout_amount'],
            $data['actual_return'],
            $data['tax_amount']
        );

        if ($data['status'] === BET_STATUS_PENDING) {
            $data['settled_at'] = null;
        } elseif ($currentBet['status'] !== $data['status'] || empty($currentBet['settled_at'])) {
            $data['settled_at'] = date('Y-m-d H:i:s');
        }

        if (!$betModel->update($betId, $userId, $data)) {
            setFlash('error', 'Failed to update bet. Please try again.');
            redirect('/bets/' . $betId . '/edit');
        }

        // Move the settlement effect between bookmaker balances
        $oldImpact = calculateSettlementImpact(
            $currentBet['status'],
            (float)$currentBet['stake'],
            (float)$currentBet['odds'],
            $currentBet['cashout_amount'] ?? null,
            $currentBet['actual_return'] ?? null,
            (float)($currentBet['tax_amount'] ?? 0)
        );
        $newImpact = calculateSettlementImpact(
            $data['status'],
            $data['stake'],
            $data['odds'],
            $data['cashout_amount'],
            $data['actual_return'],
            $data['tax_amount']
        );

        self::adjustBookmaker($currentBet['bookmaker_id'] ?? null, $userId, -$oldImpact);
        self::adjustBookmaker($data['bookmaker_id'], $userId, $newImpact);

        if ($oldImpact != 0 || $newImpact != 0) {
            syncBankrollSnapshot($userId);
        }

        self::syncLinks($betId, $userId);
        $sharedBetModel->syncFromOwner($betId, $userId);

        setFlash('success', 'Bet updated.');
        redirect('/bets/' . $betId);
    }

    /**
     * Current list filters from the query string
     */
    private static function listFilters() {
        $keys = ['status', 'sport_id', 'bookmaker_id', 'bet_type', 'tag_id', 'tipster_id', 'date_from', 'date_to',
                 'min_odds', 'max_odds', 'min_stake', 'max_stake', 'search'];
        $filters = [];
        foreach ($keys as $key) {
            $value = $_GET[$key] ?? '';
            $filters[$key] = is_string($value) ? trim($value) : '';
        }
        return $filters;
    }

    /**
     * Show bet history/list
     */
    public static function list() {
        requireLogin();

        $userId = getCurrentUserId();
        $filters = self::listFilters();
        $sort = isset(Bet::SORTS[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'date_desc';

        $betModel = new Bet();
        $totalBets = $betModel->countByUser($userId, $filters);
        $totalPages = max(1, (int)ceil($totalBets / ITEMS_PER_PAGE));
        $page = min(max(1, (int)($_GET['page'] ?? 1)), $totalPages);

        $bets = $betModel->getByUser($userId, $filters, $page, ITEMS_PER_PAGE, $sort);
        $statusCounts = $betModel->countByStatus($userId, $filters);
        $totals = $betModel->getTotals($userId, $filters);

        $tagModel = new Tag();
        $betTags = $tagModel->getByBets(array_column($bets, 'id'));

        $bookmakerModel = new Bookmaker();
        $sportModel = new Sport();
        $tipsterModel = new Tipster();

        $bookmakers = $bookmakerModel->getByUser($userId, true);
        $sports = $sportModel->getAll();
        $tags = $tagModel->getByUser($userId);
        $tipsters = $tipsterModel->getByUser($userId, false);

        include __DIR__ . '/../views/bets/list.php';
    }

    /**
     * Download the filtered bet list as CSV
     */
    public static function export() {
        requireLogin();

        $userId = getCurrentUserId();
        $filters = self::listFilters();
        $sort = isset(Bet::SORTS[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'date_desc';

        $betModel = new Bet();
        $bets = $betModel->getByUser($userId, $filters, 1, null, $sort);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="bets-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel detects the encoding
        fputcsv($out, ['ID', 'Event date', 'Event', 'Selection', 'Sport', 'Bookmaker', 'Type', 'Odds', 'Stake',
                       'Potential return', 'Status', 'Return', 'Profit/Loss', 'Tax', 'Notes', 'Created'], ',', '"', '');
        foreach ($bets as $bet) {
            $profit = betProfit($bet);
            fputcsv($out, [
                $bet['id'],
                $bet['event_date'],
                plainText($bet['event_name']),
                plainText($bet['selection']),
                plainText($bet['sport_name']),
                plainText($bet['bookmaker_name']),
                betTypeLabel($bet['bet_type']),
                $bet['odds'],
                $bet['stake'],
                $bet['potential_return'],
                betStatuses()[$bet['status']] ?? $bet['status'],
                $bet['actual_return'],
                $profit === null ? '' : number_format($profit, 2, '.', ''),
                $bet['tax_amount'],
                plainText($bet['notes']),
                $bet['created_at'],
            ], ',', '"', '');
        }
        fclose($out);
        exit;
    }

    /**
     * Delete bet
     */
    public static function delete($betId) {
        requireLogin();

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Your session expired. Please try again.');
            redirect('/bets');
        }

        $userId = getCurrentUserId();
        $betModel = new Bet();

        // Verify ownership
        $currentBet = $betModel->getById($betId, $userId);
        if (!$currentBet) {
            notFound('That bet does not exist or was deleted.');
        }
        self::guardPartnerCopy($currentBet);

        $impact = calculateSettlementImpact(
            $currentBet['status'],
            (float)$currentBet['stake'],
            (float)$currentBet['odds'],
            $currentBet['cashout_amount'] ?? null,
            $currentBet['actual_return'] ?? null,
            (float)($currentBet['tax_amount'] ?? 0)
        );

        $sharedBetModel = new SharedBet();
        $sharedUsers = $sharedBetModel->beforeOwnerDelete($currentBet);

        if ($betModel->delete($betId, $userId)) {
            foreach ($sharedUsers as $sharedUserId) {
                syncBankrollSnapshot($sharedUserId);
            }
            self::adjustBookmaker($currentBet['bookmaker_id'] ?? null, $userId, -$impact);
            if ($impact != 0) {
                syncBankrollSnapshot($userId);
            }
            setFlash('success', 'Bet deleted.');
        } else {
            setFlash('error', 'Failed to delete bet.');
        }

        $back = $_POST['redirect'] ?? '/bets';
        redirect(is_string($back) && strpos($back, '/bets') === 0 ? $back : '/bets');
    }
}
