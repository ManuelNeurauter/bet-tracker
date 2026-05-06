<?php
/**
 * Auth Controller
 */

class AuthController {
    
    /**
     * Show login page
     */
    public static function showLogin() {
        if (isLoggedIn()) {
            redirect('/');
        }
        include __DIR__ . '/../views/auth/login.php';
    }
    
    /**
     * Handle login form submission
     */
    public static function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token. Please try again.');
            redirect('/login');
        }
        
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            setFlash('error', 'Email and password are required.');
            redirect('/login');
        }
        
        $user = new User();
        $userData = $user->findByEmail($email);
        
        if (!$userData || !verifyPassword($password, $userData['password_hash'])) {
            setFlash('error', 'Invalid email or password.');
            redirect('/login');
        }
        
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        
        setFlash('success', 'Welcome back, ' . $userData['username'] . '!');
        redirect('/');
    }
    
    /**
     * Show register page
     */
    public static function showRegister() {
        if (isLoggedIn()) {
            redirect('/');
        }
        include __DIR__ . '/../views/auth/register.php';
    }
    
    /**
     * Handle register form submission
     */
    public static function handleRegister() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlash('error', 'Invalid security token. Please try again.');
            redirect('/register');
        }
        
        $username = sanitize($_POST['username'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $currency = sanitize($_POST['currency'] ?? 'USD');
        $timezone = sanitize($_POST['timezone'] ?? 'UTC');
        $bankroll = (float)($_POST['bankroll_start'] ?? 0);
        
        // Validation
        $errors = [];
        
        if (empty($username) || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        
        if (!isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        
        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }
        
        $user = new User();
        
        if ($user->usernameExists($username)) {
            $errors[] = 'Username is already taken.';
        }
        
        if ($user->emailExists($email)) {
            $errors[] = 'Email is already registered.';
        }
        
        if (!empty($errors)) {
            setFlash('error', implode('<br>', $errors));
            redirect('/register');
        }
        
        // Create user
        if ($user->create($username, $email, $password, $currency, $timezone, $bankroll)) {
            setFlash('success', 'Account created successfully! You can now log in.');
            redirect('/login');
        } else {
            setFlash('error', 'An error occurred during registration. Please try again.');
            redirect('/register');
        }
    }
    
    /**
     * Handle logout
     */
    public static function handleLogout() {
        session_destroy();
        redirect('/');
    }
}

