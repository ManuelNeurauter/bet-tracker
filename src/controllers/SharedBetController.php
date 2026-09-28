<?php
/**
 * Shared Bet Controller
 */

class SharedBetController {

    /**
     * Invitations, bets you share and bets shared with you
     */
    public static function index() {
        requireLogin();

        $user = getCurrentUser();
        $sharedBetModel = new SharedBet();
        $invites = $sharedBetModel->getInvitesFor($user);
        $sharedByMe = $sharedBetModel->getSharedByOwner($user['id']);
        $sharedWithMe = $sharedBetModel->getSharedWithUser($user['id']);

        $bookkeeper = null;
        $bookkeeperId = $sharedBetModel->bookkeeperId($user['id']);
        if ($bookkeeperId) {
            $bookmakerModel = new Bookmaker();
            $bookkeeper = $bookmakerModel->getById($bookkeeperId, $user['id']);
        }

        include __DIR__ . '/../views/shared/index.php';
    }

    /**
     * Invite someone to put part of the stake into one of your open bets
     */
    public static function invite($betId) {
        requireLogin();
        self::checkToken('/bets/' . $betId);

        $user = getCurrentUser();
        $betModel = new Bet();
        $bet = $betModel->getById($betId, $user['id']);
        if (!$bet) {
            notFound('That bet does not exist or was deleted.');
        }
        if (!empty($bet['shared_from'])) {
            setFlash('error', 'Only ' . e($bet['shared_from']) . ' can invite people to this bet.');
            redirect('/bets/' . $betId);
        }

        $email = postString('email');
        $stake = postFloatOrNull('share_stake');
        $sharedBetModel = new SharedBet();
        $error = $sharedBetModel->invite($bet, $user, $email, $stake ?? 0);

        if ($error) {
            $_SESSION['old_share'] = ['email' => $email, 'share_stake' => $_POST['share_stake'] ?? ''];
            setFlash('error', e($error));
        } else {
            $userModel = new User();
            $known = $userModel->findByEmail(mb_strtolower(trim($email)));
            setFlash('success', $known
                ? 'Invited ' . e($known['username']) . '. The bet shows up in their invitations.'
                : 'Invitation saved. It will be waiting when ' . e($email) . ' signs up.');
        }
        redirect('/bets/' . $betId . '#sharing');
    }

    public static function accept($shareId) {
        self::respond($shareId, 'accept');
    }

    public static function decline($shareId) {
        self::respond($shareId, 'decline');
    }

    private static function respond($shareId, $action) {
        requireLogin();
        self::checkToken('/shared');

        $user = getCurrentUser();
        $sharedBetModel = new SharedBet();
        $share = $sharedBetModel->getById($shareId);
        if (!$share || !$sharedBetModel->isInvitee($share, $user)) {
            notFound('That invitation does not exist.');
        }

        $error = $action === 'accept' ? $sharedBetModel->accept($share, $user) : $sharedBetModel->decline($share, $user);
        if ($error) {
            setFlash('error', e($error));
            redirect('/shared');
        }

        if ($action === 'accept') {
            $share = $sharedBetModel->getById($shareId);
            setFlash('success', 'You are in. Your part of the bet is on your Shared bets bookkeeper.');
            redirect('/bets/' . (int)$share['partner_bet_id']);
        }
        setFlash('success', 'Invitation declined.');
        redirect('/shared');
    }

    /**
     * The owner cancels an invitation or takes someone off, or a partner leaves
     */
    public static function remove($shareId) {
        requireLogin();
        self::checkToken('/shared');

        $userId = (int)getCurrentUserId();
        $sharedBetModel = new SharedBet();
        $share = $sharedBetModel->getById($shareId);
        $isOwner = $share && (int)$share['owner_id'] === $userId;
        $isPartner = $share && (int)$share['user_id'] === $userId && $share['status'] === SharedBet::STATUS_ACCEPTED;
        if (!$isOwner && !$isPartner) {
            notFound('That shared bet does not exist.');
        }

        $error = $sharedBetModel->remove($share);
        if ($error) {
            setFlash('error', e($error));
        } else {
            setFlash('success', $isOwner ? 'Removed from this bet.' : 'You left the shared bet.');
        }
        redirect($isOwner ? '/bets/' . (int)$share['bet_id'] . '#sharing' : '/shared');
    }

    private static function checkToken($back) {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Your session expired. Please try again.');
            redirect($back);
        }
    }
}
