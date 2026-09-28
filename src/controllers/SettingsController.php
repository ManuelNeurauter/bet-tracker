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
        $betCount = (new Bet())->countByUser($userId);
        
        include __DIR__ . '/../views/settings.php';
    }
    
    /**
     * Route a settings form post to the right handler
     */
    public static function handlePost() {
        $action = $_POST['action'] ?? '';
        if ($action === 'update-password') {
            self::handleChangePassword();
        } elseif ($action === 'update-profile') {
            self::handleUpdateProfile();
        } else {
            self::handleUpdate();
        }
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
            'currency' => postString('currency', 'USD'),
            'timezone' => postString('timezone', 'UTC'),
            'odds_format' => postString('odds_format', 'decimal'),
            'date_format' => postString('date_format', 'Y-m-d'),
        ];
        
        if (!isset(CURRENCY_SYMBOLS[$data['currency']])) {
            $data['currency'] = DEFAULT_CURRENCY;
        }
        if (!in_array($data['timezone'], DateTimeZone::listIdentifiers(), true)) {
            $data['timezone'] = 'UTC';
        }
        if (!in_array($data['odds_format'], [ODDS_FORMAT_DECIMAL, ODDS_FORMAT_FRACTIONAL, ODDS_FORMAT_AMERICAN], true)) {
            $data['odds_format'] = ODDS_FORMAT_DECIMAL;
        }
        if (!in_array($data['date_format'], ['Y-m-d', 'd/m/Y', 'm/d/Y'], true)) {
            $data['date_format'] = 'Y-m-d';
        }

        if ($user->update($userId, $data)) {
            setFlash('success', 'Preferences saved.');
        } else {
            setFlash('error', 'Failed to update settings.');
        }
        
        redirect('/settings');
    }
    
    /**
     * Handle username and email change
     */
    public static function handleUpdateProfile() {
        requireLogin();

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token.');
            redirect('/settings');
        }

        $userId = getCurrentUserId();
        $user = new User();
        $username = postString('username', '');
        $email = postString('email', '');

        $errors = [];
        if (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters.';
        } else {
            $existing = $user->findByUsername($username);
            if ($existing && (int)$existing['id'] !== (int)$userId) {
                $errors[] = 'That username is already taken.';
            }
        }
        if (!isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            $existing = $user->findByEmail($email);
            if ($existing && (int)$existing['id'] !== (int)$userId) {
                $errors[] = 'Another account already uses that email.';
            }
        }

        if ($errors) {
            setFlash('error', implode('<br>', array_map('e', $errors)));
            redirect('/settings#profile');
        }

        if ($user->update($userId, ['username' => $username, 'email' => $email])) {
            $_SESSION['username'] = $username;
            setFlash('success', 'Profile updated.');
        } else {
            setFlash('error', 'Failed to update your profile.');
        }

        redirect('/settings#profile');
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
            redirect('/settings#password');
        }
        
        $userId = getCurrentUserId();
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            setFlash('error', 'All password fields are required.');
            redirect('/settings#password');
        }
        
        $user = new User();
        $userData = $user->findById($userId);
        
        if (!verifyPassword($currentPassword, $userData['password_hash'])) {
            setFlash('error', 'Current password is incorrect.');
            redirect('/settings#password');
        }
        
        if ($newPassword !== $confirmPassword) {
            setFlash('error', 'New passwords do not match.');
            redirect('/settings#password');
        }
        
        if (strlen($newPassword) < 6) {
            setFlash('error', 'Password must be at least 6 characters.');
            redirect('/settings#password');
        }
        
        if ($user->updatePassword($userId, $newPassword)) {
            setFlash('success', 'Password changed.');
        } else {
            setFlash('error', 'Failed to change password.');
        }
        
        redirect('/settings#password');
    }
}


