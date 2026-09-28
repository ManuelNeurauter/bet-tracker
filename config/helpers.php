<?php
/**
 * Helper Functions
 */

/**
 * Escape a value for HTML output.
 * Existing entities are not double-encoded, so rows that were stored
 * escaped by older versions still render correctly.
 */
function sanitize($input) {
    return htmlspecialchars((string)($input ?? ''), ENT_QUOTES, 'UTF-8', false);
}

/**
 * Short alias of sanitize() for templates
 */
function e($input) {
    return sanitize($input);
}

/**
 * Decode legacy HTML-escaped text back to plain text (for JSON, CSV)
 */
function plainText($input) {
    return html_entity_decode((string)($input ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Read a trimmed string from POST data
 */
function postString($key, $default = '') {
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

/**
 * Read a float from POST data, or null when empty
 */
function postFloatOrNull($key) {
    $value = $_POST[$key] ?? '';
    if (!is_string($value) || trim($value) === '' || !is_numeric($value)) {
        return null;
    }
    return (float)$value;
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password using bcrypt
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Hidden CSRF input for forms
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Convert decimal odds to fractional format
 */
function decimalToFractional($decimal) {
    $decimal = (float) $decimal;
    if ($decimal <= 1) return '0/1';

    $numerator = (int)round(($decimal - 1) * 100);
    $denominator = 100;

    $gcd = gcd($numerator, $denominator);
    return ($numerator / $gcd) . '/' . ($denominator / $gcd);
}

/**
 * Convert decimal odds to American format
 */
function decimalToAmerican($decimal) {
    $decimal = (float) $decimal;
    if ($decimal <= 1) return '0';
    if ($decimal >= 2) {
        return '+' . round(($decimal - 1) * 100);
    }
    return (string)round(-100 / ($decimal - 1));
}

/**
 * Convert American odds to decimal
 */
function americanToDecimal($american) {
    $american = (int) $american;
    if ($american > 0) {
        return 1 + ($american / 100);
    } else {
        return 1 + (100 / abs($american));
    }
}

/**
 * Convert fractional odds to decimal
 */
function fractionalToDecimal($fraction) {
    $parts = explode('/', trim($fraction));
    if (count($parts) === 2) {
        return 1 + ((float)$parts[0] / (float)$parts[1]);
    }
    return (float) $fraction;
}

/**
 * Calculate potential return
 */
function calculatePotentialReturn($stake, $odds) {
    return round($stake * $odds, 2);
}

/**
 * Calculate settled return based on bet status
 */
function calculateSettlementReturn($status, $stake, $odds = 1.0, $cashoutAmount = null, $actualReturn = null, $taxAmount = 0) {
    switch ($status) {
        case BET_STATUS_WON:
            if ($actualReturn !== null) {
                return round((float)$actualReturn, 2);
            }
            // For won bets: payout - tax
            $payout = calculatePotentialReturn($stake, $odds);
            return round($payout - $taxAmount, 2);
        case BET_STATUS_LOST:
            if ($actualReturn !== null) {
                return round((float)$actualReturn, 2);
            }
            return 0;
        case BET_STATUS_VOID:
            // A void bet refunds the stake
            return round((float)$stake, 2);
        case BET_STATUS_CASHOUT:
            if ($actualReturn !== null) {
                return round((float)$actualReturn, 2);
            }
            if ($cashoutAmount !== null) {
                // For cashout: cashout amount - tax
                return round((float)$cashoutAmount - $taxAmount, 2);
            }
            return 0;
        default:
            return null;
    }
}

/**
 * Calculate bankroll impact for a bet settlement
 */
function calculateSettlementImpact($status, $stake, $odds = 1.0, $cashoutAmount = null, $actualReturn = null, $taxAmount = 0) {
    if ($status === BET_STATUS_PENDING || $status === BET_STATUS_VOID) {
        return 0;
    }

    $settledReturn = calculateSettlementReturn($status, $stake, $odds, $cashoutAmount, $actualReturn, $taxAmount);

    if ($settledReturn === null) {
        return 0;
    }

    return round($settledReturn - $stake, 2);
}

/**
 * Persist a bankroll snapshot for the current day
 */
function syncBankrollSnapshot($userId) {
    if (!$userId) {
        return false;
    }

    $user = new User();
    return $user->recordBankrollSnapshot($userId);
}

/**
 * Calculate profit/loss
 */
function calculateProfit($actualReturn, $stake) {
    return round($actualReturn - $stake, 2);
}

/**
 * Calculate ROI percentage
 */
function calculateROI($profit, $totalStaked) {
    if ($totalStaked == 0) return 0;
    return round(($profit / $totalStaked) * 100, 2);
}

/**
 * Calculate Yield percentage
 */
function calculateYield($profit, $totalReturned) {
    if ($totalReturned == 0) return 0;
    return round(($profit / $totalReturned) * 100, 2);
}

/**
 * Calculate Kelly Criterion stake recommendation
 */
function calculateKellyCriterion($odds, $winProbability, $bankroll) {
    if ($winProbability <= 0 || $winProbability >= 1) return 0;

    $impliedProbability = 1 / $odds;
    $edge = ($winProbability - $impliedProbability) / $impliedProbability;

    if ($edge <= 0) return 0;

    $kellyFraction = $edge / ($odds - 1);
    return round($bankroll * $kellyFraction, 2);
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = null, $decimals = 2) {
    if ($currency === null) {
        $currency = userPref('currency', DEFAULT_CURRENCY);
    }
    $symbols = CURRENCY_SYMBOLS;
    $symbol = isset($symbols[$currency]) ? $symbols[$currency] : '$';
    $amount = (float)$amount;

    $formatted = number_format(abs($amount), $decimals, '.', ',');

    if (round($amount, $decimals) < 0) {
        return '-' . $symbol . $formatted;
    }
    return $symbol . $formatted;
}

/**
 * Format a profit/loss amount with an explicit sign
 */
function formatSigned($amount, $currency = null) {
    $amount = (float)$amount;
    $formatted = formatCurrency($amount, $currency);
    return round($amount, 2) > 0 ? '+' . $formatted : $formatted;
}

/**
 * Profit/loss of a bet row, or null while it is unsettled
 */
function betProfit(array $bet) {
    if (in_array($bet['status'], [BET_STATUS_PENDING, BET_STATUS_VOID], true)) {
        return $bet['status'] === BET_STATUS_VOID ? 0.0 : null;
    }
    if ($bet['actual_return'] === null) {
        return null;
    }
    return round((float)$bet['actual_return'] - (float)$bet['stake'], 2);
}

/**
 * CSS tone for a profit/loss value
 */
function toneClass($amount) {
    $amount = round((float)$amount, 2);
    if ($amount > 0) return 'text-win';
    if ($amount < 0) return 'text-loss';
    return 'text-muted';
}

/**
 * Format decimal odds in the user's preferred format
 */
function formatOdds($decimal, $format = null) {
    if ($format === null) {
        $format = userPref('odds_format', ODDS_FORMAT_DECIMAL);
    }
    if ($format === ODDS_FORMAT_FRACTIONAL) {
        return decimalToFractional($decimal);
    }
    if ($format === ODDS_FORMAT_AMERICAN) {
        return decimalToAmerican($decimal);
    }
    return number_format((float)$decimal, 2);
}

/**
 * Plain numeric value for number inputs (no thousands separators)
 */
function inputNumber($value, $decimals = 2) {
    if ($value === null || $value === '') {
        return '';
    }
    return number_format((float)$value, $decimals, '.', '');
}

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d H:i') {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Format a date in the user's preferred date format
 */
function formatUserDate($date, $withTime = false) {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '—';
    }
    $format = userPref('date_format', 'Y-m-d');
    if (!in_array($format, ['Y-m-d', 'd/m/Y', 'm/d/Y'], true)) {
        $format = 'Y-m-d';
    }
    return date($format . ($withTime ? ' H:i' : ''), strtotime($date));
}

/**
 * Get time ago string
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' hours ago';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' days ago';
    } else {
        return floor($diff / 604800) . ' weeks ago';
    }
}

/**
 * Human readable bet statuses
 */
function betStatuses() {
    return [
        BET_STATUS_PENDING => 'Pending',
        BET_STATUS_WON => 'Won',
        BET_STATUS_LOST => 'Lost',
        BET_STATUS_CASHOUT => 'Cashed out',
        BET_STATUS_VOID => 'Void',
    ];
}

/**
 * Statuses a user can pick for a bet. Void is not offered (issue #2) and a settled
 * bet cannot go back to pending, so it is not settled again by accident.
 * Pass the bet's current status when editing; null for a new bet.
 */
function selectableStatuses($currentStatus = null) {
    $statuses = betStatuses();
    $keep = [BET_STATUS_WON, BET_STATUS_LOST, BET_STATUS_CASHOUT];
    if ($currentStatus === null || $currentStatus === BET_STATUS_PENDING) {
        array_unshift($keep, BET_STATUS_PENDING);
    }
    if ($currentStatus === BET_STATUS_VOID) {
        $keep[] = BET_STATUS_VOID; // older bets saved as void keep their status
    }
    return array_intersect_key($statuses, array_flip($keep));
}

/**
 * Human readable bet types
 */
function betTypes() {
    return [
        BET_TYPE_SINGLE => 'Single',
        BET_TYPE_DOUBLE => 'Double',
        BET_TYPE_TREBLE => 'Treble',
        BET_TYPE_ACCUMULATOR => 'Accumulator',
        BET_TYPE_LUCKY_15 => 'Lucky 15',
        BET_TYPE_LUCKY_31 => 'Lucky 31',
        BET_TYPE_LUCKY_63 => 'Lucky 63',
        BET_TYPE_SYSTEM => 'System',
        BET_TYPE_EACHWAY => 'Each way',
    ];
}

function betTypeLabel($type) {
    $types = betTypes();
    return $types[$type] ?? ucfirst(str_replace('_', ' ', (string)$type));
}

/**
 * Status pill HTML
 */
function getStatusBadge($status) {
    $statuses = betStatuses();
    $label = $statuses[$status] ?? ucfirst((string)$status);
    return '<span class="pill pill-' . e($status) . '"><span class="pill-dot"></span>' . e($label) . '</span>';
}

/**
 * Inline SVG icon from the sprite
 */
function icon($name, $class = '') {
    return '<svg class="icon ' . e($class) . '" aria-hidden="true"><use href="' . asset('assets/icons.svg') . '#' . e($name) . '"></use></svg>';
}

/**
 * Versioned URL for a file in /public
 */
function asset($path) {
    $file = __DIR__ . '/../public/' . ltrim($path, '/');
    $version = file_exists($file) ? filemtime($file) : '1';
    return '/' . ltrim($path, '/') . '?v=' . $version;
}

/**
 * Short code and colour for a sport avatar
 */
function sportBadge($sportName) {
    $name = trim((string)$sportName);
    if ($name === '') {
        return '<span class="sport-badge sport-none" title="No sport">—</span>';
    }
    $words = preg_split('/\s+/', $name);
    $code = count($words) > 1
        ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
        : strtoupper(substr($name, 0, 2));
    $hue = abs(crc32(strtolower($name))) % 360;
    return '<span class="sport-badge" style="--hue:' . $hue . '" title="' . e($name) . '">' . e($code) . '</span>';
}

/**
 * Initials avatar with a stable colour
 */
function avatar($name, $class = '') {
    $name = plainText($name);
    $initial = strtoupper(mb_substr(trim($name) ?: '?', 0, 1));
    $hue = abs(crc32(strtolower($name))) % 360;
    return '<span class="avatar ' . e($class) . '" style="--hue:' . $hue . '">' . e($initial) . '</span>';
}

/**
 * GCD function for fraction calculation
 */
function gcd($a, $b) {
    return $b == 0 ? $a : gcd($b, $a % $b);
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Render the 404 page and stop
 */
function notFound($message = null) {
    http_response_code(404);
    $notFoundMessage = $message;
    include __DIR__ . '/../src/views/404.php';
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data (cached per request)
 */
function getCurrentUser($refresh = false) {
    static $user = null;
    if (!isLoggedIn()) return null;

    if ($user === null || $refresh) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([getCurrentUserId()]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

/**
 * Read a preference of the current user
 */
function userPref($key, $default = null) {
    $user = getCurrentUser();
    return ($user && !empty($user[$key])) ? $user[$key] : $default;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login');
    }
    if (!getCurrentUser()) {
        // Session points to a user that no longer exists
        session_destroy();
        redirect('/login');
    }
}

/**
 * Generate pagination array
 */
function getPaginationPages($currentPage, $totalPages, $maxPages = 7) {
    $pages = [];

    if ($totalPages <= $maxPages) {
        for ($i = 1; $i <= $totalPages; $i++) {
            $pages[] = $i;
        }
    } else {
        $leftWindow = max(1, $currentPage - floor($maxPages / 2));
        $rightWindow = min($totalPages, $leftWindow + $maxPages - 1);

        if ($rightWindow - $leftWindow < $maxPages - 1) {
            $leftWindow = max(1, $rightWindow - $maxPages + 1);
        }

        for ($i = $leftWindow; $i <= $rightWindow; $i++) {
            $pages[] = (int)$i;
        }
    }

    return $pages;
}

/**
 * Build a URL for the current path with changed query parameters
 */
function urlWithQuery(array $changes) {
    $query = array_merge($_GET, $changes);
    $query = array_filter($query, function ($value) {
        return $value !== '' && $value !== null;
    });
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return $path . ($query ? '?' . http_build_query($query) : '');
}

/**
 * Start session if not already started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => '/',
            'samesite' => 'Lax',
            'httponly' => true,
        ]);
        session_start();
    }
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get flash message
 */
function getFlash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

/**
 * Check if flash message exists
 */
function hasFlash($type) {
    return isset($_SESSION['flash'][$type]);
}
