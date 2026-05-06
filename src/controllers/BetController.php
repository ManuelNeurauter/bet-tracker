<?php
/**
 * Bet Controller
 */

class BetController {
    
    /**
     * Show add bet page
     */
    public static function showAdd() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $bookmakerModel = new Bookmaker();
        $sportModel = new Sport();
        $tagModel = new Tag();
        $tipsterModel = new Tipster();
        
        $bookmakers = $bookmakerModel->getByUser($userId);
        $sports = $sportModel->getAll();
        $tags = $tagModel->getByUser($userId);
        $tipsters = $tipsterModel->getByUser($userId);
        
        include 'src/views/bets/add.php';
    }
    
    /**
     * Handle add bet form submission
     */
    public static function handleAdd() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/bets/add');
        }
        
        $userId = getCurrentUserId();
        
        $data = [
            'user_id' => $userId,
            'bookmaker_id' => !empty($_POST['bookmaker_id']) ? (int)$_POST['bookmaker_id'] : null,
            'sport_id' => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
            'competition_id' => !empty($_POST['competition_id']) ? (int)$_POST['competition_id'] : null,
            'event_name' => sanitize($_POST['event_name'] ?? ''),
            'event_date' => !empty($_POST['event_date']) ? $_POST['event_date'] : null,
            'bet_type' => sanitize($_POST['bet_type'] ?? BET_TYPE_SINGLE),
            'selection' => sanitize($_POST['selection'] ?? ''),
            'odds' => (float)($_POST['odds'] ?? 1.0),
            'stake' => (float)($_POST['stake'] ?? 0),
            'status' => sanitize($_POST['status'] ?? BET_STATUS_PENDING),
            'each_way' => isset($_POST['each_way']) ? 1 : 0,
            'notes' => sanitize($_POST['notes'] ?? ''),
        ];
        
        // Validation
        if (empty($data['event_name']) || empty($data['selection']) || $data['stake'] <= 0 || $data['odds'] <= 0) {
            setFlash('error', 'Please fill in all required fields correctly.');
            redirect('/bets/add');
        }
        
        $bet = new Bet();
        $betId = $bet->create($data);
        
        if (!$betId) {
            setFlash('error', 'Failed to create bet. Please try again.');
            redirect('/bets/add');
        }
        
        // Add tags if any
        if (!empty($_POST['tags'])) {
            $tagModel = new Tag();
            foreach ((array)$_POST['tags'] as $tagId) {
                $tagModel->addToBet($betId, (int)$tagId);
            }
        }
        
        // Add tipsters if any
        if (!empty($_POST['tipsters'])) {
            $tipsterModel = new Tipster();
            foreach ((array)$_POST['tipsters'] as $tipsterId) {
                $tipsterModel->addToBet($betId, (int)$tipsterId);
            }
        }
        
        setFlash('success', 'Bet created successfully!');
        redirect('/bets/' . $betId);
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
            http_response_code(404);
            die('Bet not found');
        }
        
        $tagModel = new Tag();
        $tipsterModel = new Tipster();
        
        $tags = $tagModel->getByBet($betId);
        $tipsters = $tipsterModel->getByBet($betId);
        
        include 'src/views/bets/view.php';
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
            http_response_code(404);
            die('Bet not found');
        }
        
        $bookmakerModel = new Bookmaker();
        $sportModel = new Sport();
        $tagModel = new Tag();
        $tipsterModel = new Tipster();
        
        $bookmakers = $bookmakerModel->getByUser($userId);
        $sports = $sportModel->getAll();
        $tags = $tagModel->getByUser($userId);
        $tipsters = $tipsterModel->getByUser($userId);
        $selectedTags = $tagModel->getByBet($betId);
        $selectedTipsters = $tipsterModel->getByBet($betId);
        
        include 'src/views/bets/edit.php';
    }
    
    /**
     * Handle edit bet form submission
     */
    public static function handleEdit($betId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/bets/' . $betId . '/edit');
        }
        
        $userId = getCurrentUserId();
        $betModel = new Bet();
        
        // Verify ownership
        if (!$betModel->getById($betId, $userId)) {
            http_response_code(404);
            die('Bet not found');
        }
        
        $data = [
            'bookmaker_id' => !empty($_POST['bookmaker_id']) ? (int)$_POST['bookmaker_id'] : null,
            'sport_id' => !empty($_POST['sport_id']) ? (int)$_POST['sport_id'] : null,
            'competition_id' => !empty($_POST['competition_id']) ? (int)$_POST['competition_id'] : null,
            'event_name' => sanitize($_POST['event_name'] ?? ''),
            'event_date' => !empty($_POST['event_date']) ? $_POST['event_date'] : null,
            'bet_type' => sanitize($_POST['bet_type'] ?? BET_TYPE_SINGLE),
            'selection' => sanitize($_POST['selection'] ?? ''),
            'odds' => (float)($_POST['odds'] ?? 1.0),
            'stake' => (float)($_POST['stake'] ?? 0),
            'status' => sanitize($_POST['status'] ?? BET_STATUS_PENDING),
            'actual_return' => !empty($_POST['actual_return']) ? (float)$_POST['actual_return'] : null,
            'cashout_amount' => !empty($_POST['cashout_amount']) ? (float)$_POST['cashout_amount'] : null,
            'each_way' => isset($_POST['each_way']) ? 1 : 0,
            'notes' => sanitize($_POST['notes'] ?? ''),
        ];
        
        if ($data['status'] !== BET_STATUS_PENDING && is_null($data['actual_return'])) {
            $data['settled_at'] = date('Y-m-d H:i:s');
        }
        
        if (!$betModel->update($betId, $userId, $data)) {
            setFlash('error', 'Failed to update bet. Please try again.');
            redirect('/bets/' . $betId . '/edit');
        }
        
        // Update tags
        $tagModel = new Tag();
        $currentTags = $tagModel->getByBet($betId);
        
        // Remove all current tags
        foreach ($currentTags as $tag) {
            $tagModel->removeFromBet($betId, $tag['id']);
        }
        
        // Add new tags
        if (!empty($_POST['tags'])) {
            foreach ((array)$_POST['tags'] as $tagId) {
                $tagModel->addToBet($betId, (int)$tagId);
            }
        }
        
        setFlash('success', 'Bet updated successfully!');
        redirect('/bets/' . $betId);
    }
    
    /**
     * Show bet history/list
     */
    public static function list() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $page = (int)($_GET['page'] ?? 1);
        $page = max(1, $page);
        
        $filters = [
            'status' => $_GET['status'] ?? '',
            'sport_id' => $_GET['sport_id'] ?? '',
            'bookmaker_id' => $_GET['bookmaker_id'] ?? '',
            'bet_type' => $_GET['bet_type'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'min_odds' => $_GET['min_odds'] ?? '',
            'max_odds' => $_GET['max_odds'] ?? '',
            'min_stake' => $_GET['min_stake'] ?? '',
            'max_stake' => $_GET['max_stake'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];
        
        $betModel = new Bet();
        $bets = $betModel->getByUser($userId, $filters, $page);
        $totalBets = $betModel->countByUser($userId, $filters);
        $totalPages = ceil($totalBets / ITEMS_PER_PAGE);
        
        $bookmakerModel = new Bookmaker();
        $sportModel = new Sport();
        
        $bookmakers = $bookmakerModel->getByUser($userId);
        $sports = $sportModel->getAll();
        
        include 'src/views/bets/list.php';
    }
    
    /**
     * Delete bet
     */
    public static function delete($betId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/bets');
        }
        
        $userId = getCurrentUserId();
        $betModel = new Bet();
        
        // Verify ownership
        if (!$betModel->getById($betId, $userId)) {
            http_response_code(404);
            die('Bet not found');
        }
        
        if ($betModel->delete($betId, $userId)) {
            setFlash('success', 'Bet deleted successfully!');
        } else {
            setFlash('error', 'Failed to delete bet.');
        }
        
        redirect('/bets');
    }
}

?>
