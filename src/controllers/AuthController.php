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
        
        $email = postString('email', '');
        $password = $_POST['password'] ?? '';
        $_SESSION['old_login'] = $email;
        
        if (empty($email) || empty($password)) {
            setFlash('error', 'Email and password are required.');
            redirect('/login');
        }
        
        $user = new User();
        $userData = $user->findByEmail($email) ?: $user->findByUsername($email);
        
        if (!$userData || !verifyPassword($password, $userData['password_hash'])) {
            setFlash('error', 'That email and password combination is not right.');
            redirect('/login');
        }
        
        self::logIn($userData);
        setFlash('success', 'Welcome back, ' . e($userData['username']) . '!');
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
        
        $username = postString('username', '');
        $email = postString('email', '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $currency = postString('currency', 'USD');
        $timezone = postString('timezone', 'UTC');
        $_SESSION['old_register'] = ['username' => $username, 'email' => $email, 'currency' => $currency, 'timezone' => $timezone];
        if (!isset(CURRENCY_SYMBOLS[$currency])) {
            $currency = DEFAULT_CURRENCY;
        }
        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            $timezone = 'UTC';
        }
        
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
            setFlash('error', implode('<br>', array_map('e', $errors)));
            redirect('/register');
        }
        
        // Create user and sign them in
        if ($user->create($username, $email, $password, $currency, $timezone)) {
            unset($_SESSION['old_register']);
            self::logIn($user->findByEmail($email));
            setFlash('success', 'Welcome to ' . APP_NAME . ', ' . e($username) . '! Add a bookmaker to start tracking.');
            redirect('/');
        } else {
            setFlash('error', 'An error occurred during registration. Please try again.');
            redirect('/register');
        }
    }
    
    /**
     * Start an authenticated session
     */
    private static function logIn(array $userData) {
        session_regenerate_id(true);
        unset($_SESSION['old_login']);
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
    }

    /**
     * Handle logout
     */
    public static function handleLogout() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            redirect('/');
        }
        $_SESSION = [];
        session_regenerate_id(true);
        setFlash('success', 'You have been signed out.');
        redirect('/login');
    }
}

