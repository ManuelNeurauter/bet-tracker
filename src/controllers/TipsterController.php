<?php
/**
 * Tipster Controller
 */

class TipsterController {
    
    /**
     * Show tipsters list
     */
    public static function list() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $tipsterModel = new Tipster();
        $tipsters = $tipsterModel->getByUser($userId, false);
        
        foreach ($tipsters as &$tipster) {
            $stats = $tipsterModel->getStatistics($tipster['id'], $userId);
            $tipster['stats'] = $stats;
            
            if ($stats && $stats['total_bets'] > 0) {
                $tipster['roi'] = $stats['total_staked'] > 0 ? round($stats['profit_loss'] / $stats['total_staked'] * 100, 1) : 0;
                $tipster['win_rate'] = round(($stats['won_bets'] / $stats['total_bets']) * 100, 1);
            } else {
                $tipster['roi'] = 0;
                $tipster['win_rate'] = 0;
            }
        }
        
        unset($tipster);
        // Best performers first
        usort($tipsters, function ($a, $b) {
            return ((float)($b['stats']['profit_loss'] ?? 0) <=> (float)($a['stats']['profit_loss'] ?? 0)) ?: strcasecmp($a['name'], $b['name']);
        });
        $activeTipsters = array_values(array_filter($tipsters, function ($t) { return $t['is_active']; }));
        $inactiveTipsters = array_values(array_filter($tipsters, function ($t) { return !$t['is_active']; }));

        include __DIR__ . '/../views/tipsters/list.php';
    }
    
    /**
     * Show add tipster page
     */
    public static function showAdd() {
        requireLogin();
        include __DIR__ . '/../views/tipsters/add.php';
    }
    
    /**
     * Handle add tipster form submission
     */
    public static function handleAdd() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/tipsters/add');
        }
        
        $userId = getCurrentUserId();
        $name = postString('name', '');
        $sourceUrl = postString('source_url', '');
        $notes = postString('notes', '');
        
        if (empty($name)) {
            setFlash('error', 'Tipster name is required.');
            redirect('/tipsters/add');
        }
        
        $tipsterModel = new Tipster();
        if ($tipsterModel->create($userId, $name, $sourceUrl, $notes)) {
            setFlash('success', 'Tipster created successfully!');
            redirect('/tipsters');
        } else {
            setFlash('error', 'Failed to create tipster. Please try again.');
            redirect('/tipsters/add');
        }
    }
    
    /**
     * Show edit tipster page
     */
    public static function showEdit($tipsterId) {
        requireLogin();
        
        $userId = getCurrentUserId();
        $tipsterModel = new Tipster();
        $tipster = $tipsterModel->getById($tipsterId, $userId);
        
        if (!$tipster) {
            notFound('That tipster does not exist or was deleted.');
        }
        
        $stats = $tipsterModel->getStatistics($tipsterId, $userId);
        include __DIR__ . '/../views/tipsters/edit.php';
    }
    
    /**
     * Handle edit tipster form submission
     */
    public static function handleEdit($tipsterId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/tipsters/' . $tipsterId . '/edit');
        }
        
        $userId = getCurrentUserId();
        $tipsterModel = new Tipster();
        
        if (!$tipsterModel->getById($tipsterId, $userId)) {
            notFound('That tipster does not exist or was deleted.');
        }
        
        $data = [
            'name' => postString('name', ''),
            'source_url' => postString('source_url', ''),
            'notes' => postString('notes', ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        
        if (empty($data['name'])) {
            setFlash('error', 'Tipster name is required.');
            redirect('/tipsters/' . $tipsterId . '/edit');
        }
        
        if ($tipsterModel->update($tipsterId, $userId, $data)) {
            setFlash('success', 'Tipster updated successfully!');
            redirect('/tipsters');
        } else {
            setFlash('error', 'Failed to update tipster.');
            redirect('/tipsters/' . $tipsterId . '/edit');
        }
    }
    
    /**
     * Delete tipster
     */
    public static function delete($tipsterId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/tipsters');
        }
        
        $userId = getCurrentUserId();
        $tipsterModel = new Tipster();
        
        if (!$tipsterModel->getById($tipsterId, $userId)) {
            notFound('That tipster does not exist or was deleted.');
        }
        
        if ($tipsterModel->delete($tipsterId, $userId)) {
            setFlash('success', 'Tipster deleted successfully!');
        } else {
            setFlash('error', 'Failed to delete tipster.');
        }
        
        redirect('/tipsters');
    }
}

