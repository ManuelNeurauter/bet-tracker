<?php
/**
 * Settings Controller
 */

class SettingsController {
    
    /**
     * Show settings page
     */
    public static function show() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $user = new User();
        $userData = $user->findById($userId);
        
        include __DIR__ . '/../views/settings.php';
    }
    
    /**
     * Handle settings update
     */
    public static function handleUpdate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/settings');
        }
        
        $userId = getCurrentUserId();
        $user = new User();
        
        $data = [
            'currency' => sanitize($_POST['currency'] ?? 'USD'),
            'timezone' => sanitize($_POST['timezone'] ?? 'UTC'),
            'odds_format' => sanitize($_POST['odds_format'] ?? 'decimal'),
            'date_format' => sanitize($_POST['date_format'] ?? 'Y-m-d'),
        ];
        
        if ($user->update($userId, $data)) {
            setFlash('success', 'Settings updated successfully!');
        } else {
            setFlash('error', 'Failed to update settings.');
        }
        
        redirect('/settings');
    }
    
    /**
     * Handle password change
     */
    public static function handleChangePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        requireLogin();
        
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/settings');
        }
        
        $userId = getCurrentUserId();
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            setFlash('error', 'All password fields are required.');
            redirect('/settings');
        }
        
        $user = new User();
        $userData = $user->findById($userId);
        
        if (!verifyPassword($currentPassword, $userData['password_hash'])) {
            setFlash('error', 'Current password is incorrect.');
            redirect('/settings');
        }
        
        if ($newPassword !== $confirmPassword) {
            setFlash('error', 'New passwords do not match.');
            redirect('/settings');
        }
        
        if (strlen($newPassword) < 6) {
            setFlash('error', 'Password must be at least 6 characters.');
            redirect('/settings');
        }
        
        if ($user->updatePassword($userId, $newPassword)) {
            setFlash('success', 'Password changed successfully!');
        } else {
            setFlash('error', 'Failed to change password.');
        }
        
        redirect('/settings');
    }
}


