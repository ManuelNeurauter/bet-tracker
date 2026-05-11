<?php
/**
 * Helper Functions
 */

/**
 * Sanitize user input to prevent XSS attacks
 */
function sanitize($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
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
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Convert decimal odds to fractional format
 */
function decimalToFractional($decimal) {
    $decimal = (float) $decimal;
    if ($decimal < 1) return '0/1';
    
    $whole = floor($decimal);
    $fraction = $decimal - $whole;
    
    $numerator = round($fraction * 1000);
    $denominator = 1000;
    
    $gcd = gcd($numerator, $denominator);
    $numerator /= $gcd;
    $denominator /= $gcd;
    
    if ($whole > 0) {
        return $whole . ' ' . $numerator . '/' . $denominator;
    }
    return $numerator . '/' . $denominator;
}

/**
 * Convert decimal odds to American format
 */
function decimalToAmerican($decimal) {
    $decimal = (float) $decimal;
    if ($decimal >= 2) {
        return '+' . round(($decimal - 1) * 100);
    } else {
        return round(-100 / ($decimal - 1));
    }
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
            return 0;
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
function formatCurrency($amount, $currency = 'USD', $decimals = 2) {
    $symbols = CURRENCY_SYMBOLS;
    $symbol = isset($symbols[$currency]) ? $symbols[$currency] : '$';
    
    $formatted = number_format($amount, $decimals, '.', ',');
    
    if ($amount < 0) {
        return '-' . $symbol . abs($amount);
    }
    return $symbol . $formatted;
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
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'won' => '<span class="badge badge-success">Won</span>',
        'lost' => '<span class="badge badge-danger">Lost</span>',
        'void' => '<span class="badge badge-secondary">Void</span>',
        'cashout' => '<span class="badge badge-info">Cashed Out</span>',
    ];
    
    return isset($badges[$status]) ? $badges[$status] : $status;
}

/**
 * GCD function for fraction calculation
 */
function gcd($a, $b) {
    return $b === 0 ? $a : gcd($b, $a % $b);
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header('Location: ' . $url);
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
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([getCurrentUserId()]);
    return $stmt->fetch();
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
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
            $pages[] = $i;
        }
    }
    
    return $pages;
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


