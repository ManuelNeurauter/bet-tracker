<?php
/**
 * Tag Controller
 */

class TagController {
    
    /**
     * Show tags list
     */
    public static function list() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $tagModel = new Tag();
        $tags = $tagModel->getByUser($userId);
        
        include 'src/views/tags/list.php';
    }
    
    /**
     * Show add tag page
     */
    public static function showAdd() {
        requireLogin();
        include 'src/views/tags/add.php';
    }
    
    /**
     * Handle add tag form submission
     */
    public static function handleAdd() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/tags/add');
        }
        
        $userId = getCurrentUserId();
        $name = sanitize($_POST['name'] ?? '');
        $color = sanitize($_POST['color'] ?? '#3498db');
        
        if (empty($name)) {
            setFlash('error', 'Tag name is required.');
            redirect('/tags/add');
        }
        
        $tagModel = new Tag();
        if ($tagModel->create($userId, $name, $color)) {
            setFlash('success', 'Tag created successfully!');
            redirect('/tags');
        } else {
            setFlash('error', 'Failed to create tag. Please try again.');
            redirect('/tags/add');
        }
    }
    
    /**
     * Show edit tag page
     */
    public static function showEdit($tagId) {
        requireLogin();
        
        $userId = getCurrentUserId();
        $tagModel = new Tag();
        $tag = $tagModel->getById($tagId, $userId);
        
        if (!$tag) {
            http_response_code(404);
            die('Tag not found');
        }
        
        include 'src/views/tags/edit.php';
    }
    
    /**
     * Handle edit tag form submission
     */
    public static function handleEdit($tagId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/tags/' . $tagId . '/edit');
        }
        
        $userId = getCurrentUserId();
        $tagModel = new Tag();
        
        if (!$tagModel->getById($tagId, $userId)) {
            http_response_code(404);
            die('Tag not found');
        }
        
        $name = sanitize($_POST['name'] ?? '');
        $color = sanitize($_POST['color'] ?? '#3498db');
        
        if (empty($name)) {
            setFlash('error', 'Tag name is required.');
            redirect('/tags/' . $tagId . '/edit');
        }
        
        if ($tagModel->update($tagId, $userId, $name, $color)) {
            setFlash('success', 'Tag updated successfully!');
            redirect('/tags');
        } else {
            setFlash('error', 'Failed to update tag.');
            redirect('/tags/' . $tagId . '/edit');
        }
    }
    
    /**
     * Delete tag
     */
    public static function delete($tagId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/tags');
        }
        
        $userId = getCurrentUserId();
        $tagModel = new Tag();
        
        if (!$tagModel->getById($tagId, $userId)) {
            http_response_code(404);
            die('Tag not found');
        }
        
        if ($tagModel->delete($tagId, $userId)) {
            setFlash('success', 'Tag deleted successfully!');
        } else {
            setFlash('error', 'Failed to delete tag.');
        }
        
        redirect('/tags');
    }
}

?>
