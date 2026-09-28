<?php
/**
 * Bookmaker Controller
 */

class BookmakerController {
    
    /**
     * Show bookmakers list
     */
    public static function list() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $bookmakerModel = new Bookmaker();
        $bookmakers = $bookmakerModel->getByUser($userId, true);
        
        // Get statistics for each bookmaker
        foreach ($bookmakers as &$bookmaker) {
            $stats = $bookmakerModel->getStatistics($bookmaker['id'], $userId);
            $bookmaker['stats'] = $stats;
            
            if ($stats['total_bets'] > 0) {
                $bookmaker['roi'] = $stats['total_staked'] > 0 ? round($stats['profit_loss'] / $stats['total_staked'] * 100, 1) : 0;
                $bookmaker['win_rate'] = round(($stats['won_bets'] / $stats['total_bets']) * 100, 1);
            } else {
                $bookmaker['roi'] = 0;
                $bookmaker['win_rate'] = 0;
            }
        }
        
        unset($bookmaker);
        $activeBookmakers = array_values(array_filter($bookmakers, function ($bm) { return !$bm['is_archived']; }));
        $archivedBookmakers = array_values(array_filter($bookmakers, function ($bm) { return $bm['is_archived']; }));
        $totalBalance = array_sum(array_map(function ($bm) { return (float)$bm['account_balance']; }, $activeBookmakers));
        $totalBonus = array_sum(array_map(function ($bm) { return (float)$bm['bonus_balance']; }, $activeBookmakers));
        $totalProfit = array_sum(array_map(function ($bm) { return (float)($bm['stats']['profit_loss'] ?? 0); }, $bookmakers));
        
        include __DIR__ . '/../views/bookmakers/list.php';
    }
    
    /**
     * Keep only http(s) links; add a scheme when it is missing
     */
    private static function normaliseUrl($url) {
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . preg_replace('#^[a-z]+:/*#i', '', $url);
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    /**
     * Show add bookmaker page
     */
    public static function showAdd() {
        requireLogin();
        include __DIR__ . '/../views/bookmakers/add.php';
    }
    
    /**
     * Handle add bookmaker form submission
     */
    public static function handleAdd() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/bookmakers/add');
        }
        
        $userId = getCurrentUserId();
        
        $data = [
            'name' => postString('name', ''),
            'url' => self::normaliseUrl(postString('url', '')),
            'account_balance' => (float)($_POST['account_balance'] ?? 0),
            'bonus_balance' => (float)($_POST['bonus_balance'] ?? 0),
            'tax_percentage' => min(100, max(0, (float)($_POST['tax_percentage'] ?? 0))),
            'notes' => postString('notes', ''),
        ];
        
        if (empty($data['name'])) {
            setFlash('error', 'Bookmaker name is required.');
            redirect('/bookmakers/add');
        }
        
        $bookmakerModel = new Bookmaker();
        if ($bookmakerModel->create($userId, $data)) {
            syncBankrollSnapshot($userId);
            setFlash('success', 'Bookmaker added successfully!');
            redirect('/bookmakers');
        } else {
            setFlash('error', 'Failed to add bookmaker. Please try again.');
            redirect('/bookmakers/add');
        }
    }
    
    /**
     * Show edit bookmaker page
     */
    public static function showEdit($bookmakerId) {
        requireLogin();
        
        $userId = getCurrentUserId();
        $bookmakerModel = new Bookmaker();
        $bookmaker = $bookmakerModel->getById($bookmakerId, $userId);
        
        if (!$bookmaker) {
            notFound('That bookmaker does not exist or was deleted.');
        }
        
        $stats = $bookmakerModel->getStatistics($bookmakerId, $userId);
        include __DIR__ . '/../views/bookmakers/edit.php';
    }
    
    /**
     * Handle edit bookmaker form submission
     */
    public static function handleEdit($bookmakerId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/bookmakers/' . $bookmakerId . '/edit');
        }
        
        $userId = getCurrentUserId();
        $bookmakerModel = new Bookmaker();
        
        if (!$bookmakerModel->getById($bookmakerId, $userId)) {
            notFound('That bookmaker does not exist or was deleted.');
        }
        
        $data = [
            'name' => postString('name', ''),
            'url' => self::normaliseUrl(postString('url', '')),
            'account_balance' => (float)($_POST['account_balance'] ?? 0),
            'bonus_balance' => (float)($_POST['bonus_balance'] ?? 0),
            'tax_percentage' => min(100, max(0, (float)($_POST['tax_percentage'] ?? 0))),
            'notes' => postString('notes', ''),
            'is_archived' => isset($_POST['is_archived']) ? 1 : 0,
        ];
        
        if (empty($data['name'])) {
            setFlash('error', 'Bookmaker name is required.');
            redirect('/bookmakers/' . $bookmakerId . '/edit');
        }
        
        if ($bookmakerModel->update($bookmakerId, $userId, $data)) {
            syncBankrollSnapshot($userId);
            setFlash('success', 'Bookmaker updated successfully!');
            redirect('/bookmakers');
        } else {
            setFlash('error', 'Failed to update bookmaker.');
            redirect('/bookmakers/' . $bookmakerId . '/edit');
        }
    }
    
    /**
     * Delete bookmaker
     */
    public static function delete($bookmakerId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/bookmakers');
        }
        
        $userId = getCurrentUserId();
        $bookmakerModel = new Bookmaker();
        
        if (!$bookmakerModel->getById($bookmakerId, $userId)) {
            notFound('That bookmaker does not exist or was deleted.');
        }
        
        if ($bookmakerModel->delete($bookmakerId, $userId)) {
            syncBankrollSnapshot($userId);
            setFlash('success', 'Bookmaker deleted successfully!');
        } else {
            setFlash('error', 'Failed to delete bookmaker.');
        }
        
        redirect('/bookmakers');
    }
}

