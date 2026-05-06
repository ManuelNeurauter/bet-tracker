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
        $tipsters = $tipsterModel->getByUser($userId);
        
        foreach ($tipsters as &$tipster) {
            $stats = $tipsterModel->getStatistics($tipster['id'], $userId);
            $tipster['stats'] = $stats;
            
            if ($stats && $stats['total_bets'] > 0) {
                $tipster['roi'] = calculateROI($stats['profit'], $stats['total_staked']);
                $tipster['win_rate'] = round(($stats['won_bets'] / $stats['total_bets']) * 100, 2);
            } else {
                $tipster['roi'] = 0;
                $tipster['win_rate'] = 0;
            }
        }
        
        include 'src/views/tipsters/list.php';
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
        $name = sanitize($_POST['name'] ?? '');
        $sourceUrl = sanitize($_POST['source_url'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        
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
            http_response_code(404);
            die('Tipster not found');
        }
        
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
            http_response_code(404);
            die('Tipster not found');
        }
        
        $data = [
            'name' => sanitize($_POST['name'] ?? ''),
            'source_url' => sanitize($_POST['source_url'] ?? ''),
            'notes' => sanitize($_POST['notes'] ?? ''),
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
            http_response_code(404);
            die('Tipster not found');
        }
        
        if ($tipsterModel->delete($tipsterId, $userId)) {
            setFlash('success', 'Tipster deleted successfully!');
        } else {
            setFlash('error', 'Failed to delete tipster.');
        }
        
        redirect('/tipsters');
    }
}

?>
