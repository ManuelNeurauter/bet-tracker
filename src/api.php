<?php
/**
 * API Endpoints for AJAX Requests
 */

header('Content-Type: application/json');

// Check if API request
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($request_uri, '/api/') !== 0 && strpos($request_uri, '/api') !== 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

requireLogin();

// Parse API route
$segments = array_filter(explode('/', trim($request_uri, '/')));
$segments = array_values($segments);

// Default response
$response = ['success' => false, 'message' => 'Invalid request'];

// API Routes
if (($segments[1] ?? '') === 'competitions') {
    // Get competitions for a sport
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['sport_id'])) {
        $sportId = (int)$_GET['sport_id'];
        $sportModel = new Sport();
        $competitions = $sportModel->getCompetitions($sportId);
        $response = ['success' => true, 'competitions' => $competitions];
    }
}
elseif (($segments[1] ?? '') === 'stats') {
    // Get quick stats
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = getCurrentUserId();
        $user = new User();
        $stats = $user->getStatistics($userId);
        
        $response = [
            'success' => true,
            'stats' => [
                'totalBets' => $stats['total_bets'] ?? 0,
                'wonBets' => $stats['won_bets'] ?? 0,
                'lostBets' => $stats['lost_bets'] ?? 0,
                'totalStaked' => $stats['total_staked'] ?? 0,
                'totalProfit' => $stats['total_profit'] ?? 0,
                'winRate' => ($stats['total_bets'] > 0) ? round(($stats['won_bets'] / $stats['total_bets']) * 100, 2) : 0,
                'roi' => calculateROI($stats['total_profit'] ?? 0, $stats['total_staked'] ?? 0),
            ]
        ];
    }
}
elseif (($segments[1] ?? '') === 'bankroll') {
    // Get current bankroll
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = getCurrentUserId();
        $user = new User();
        $bankroll = $user->getCurrentBankroll($userId);
        $response = ['success' => true, 'bankroll' => $bankroll];
    }
}
elseif (($segments[1] ?? '') === 'bets' && ($segments[2] ?? '') === 'quick-settle') {
    // Quick settle a bet
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $csrfToken = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!verifyCSRFToken($csrfToken)) {
            $response = ['success' => false, 'message' => 'Invalid security token'];
            echo json_encode($response);
            exit;
        }

        $betId = (int)($input['betId'] ?? 0);
        $status = $input['status'] ?? null;
        $amount = isset($input['cashoutAmount']) && is_numeric($input['cashoutAmount'])
            ? round((float)$input['cashoutAmount'], 2)
            : (isset($input['actualReturn']) && is_numeric($input['actualReturn']) ? round((float)$input['actualReturn'], 2) : null);
        
        if (!$betId || !in_array((string)$status, [BET_STATUS_WON, BET_STATUS_LOST, BET_STATUS_CASHOUT], true)) {
            $response = ['success' => false, 'message' => 'Missing required fields'];
        } elseif ($status === BET_STATUS_CASHOUT && ($amount === null || $amount < 0)) {
            $response = ['success' => false, 'message' => 'Enter the cashout amount you received'];
        } else {
            $userId = getCurrentUserId();
            $betModel = new Bet();
            
            // Verify bet belongs to user
            $bet = $betModel->getById($betId, $userId);
            if (!$bet) {
                $response = ['success' => false, 'message' => 'Bet not found'];
            } else {
                $cashoutAmount = $status === BET_STATUS_CASHOUT ? $amount : null;
                $actualReturn = calculateSettlementReturn(
                    $status,
                    (float)$bet['stake'],
                    (float)$bet['odds'],
                    $cashoutAmount,
                    $cashoutAmount,
                    (float)($bet['tax_amount'] ?? 0)
                );

                $updateData = [
                    'status' => $status,
                    'actual_return' => $actualReturn,
                    'cashout_amount' => $cashoutAmount,
                    'settled_at' => $status === BET_STATUS_PENDING ? null : date('Y-m-d H:i:s'),
                ];
                
                if ($betModel->update($betId, $userId, $updateData)) {
                    if ($status === BET_STATUS_WON && isChainedBetType($bet['bet_type'])) {
                        (new BetLeg())->settleOpenAsWon($betId);
                    }
                    $oldImpact = calculateSettlementImpact(
                        $bet['status'],
                        (float)$bet['stake'],
                        (float)$bet['odds'],
                        $bet['cashout_amount'] ?? null,
                        $bet['actual_return'] ?? null,
                        (float)($bet['tax_amount'] ?? 0)
                    );
                    $newImpact = calculateSettlementImpact(
                        $status,
                        (float)$bet['stake'],
                        (float)$bet['odds'],
                        $cashoutAmount,
                        $actualReturn,
                        (float)($bet['tax_amount'] ?? 0)
                    );

                    $bookmakerId = $bet['bookmaker_id'];
                    if ($bookmakerId && $oldImpact != $newImpact) {
                        $bookmakerModel = new Bookmaker();
                        $bookmaker = $bookmakerModel->getById($bookmakerId, $userId);
                        if ($bookmaker) {
                            $bookmakerModel->update($bookmakerId, $userId, [
                                'account_balance' => round((float)$bookmaker['account_balance'] - $oldImpact + $newImpact, 2),
                            ]);
                            syncBankrollSnapshot($userId);
                        }
                    }

                    $labels = betStatuses();
                    setFlash('success', e(plainText($bet['event_name'])) . ' marked as ' . strtolower($labels[$status]) . '.');
                    $response = ['success' => true, 'message' => 'Bet updated successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to update bet'];
                }
            }
        }
    }
}
elseif (($segments[1] ?? '') === 'bets' && ctype_digit($segments[2] ?? '') && ($segments[3] ?? '') === 'legs' && ctype_digit($segments[4] ?? '')) {
    // Set the result of one leg of a multiple
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $csrfToken = $input['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $status = $input['status'] ?? null;
        $betModel = new Bet();
        if (!verifyCSRFToken($csrfToken)) {
            $response = ['success' => false, 'message' => 'Invalid security token'];
        } elseif (!array_key_exists((string)$status, legStatuses())) {
            $response = ['success' => false, 'message' => 'Pick a valid result'];
        } elseif (!$betModel->getById((int)$segments[2], getCurrentUserId())) {
            $response = ['success' => false, 'message' => 'Bet not found'];
        } elseif ((new BetLeg())->updateStatus((int)$segments[4], (int)$segments[2], $status)) {
            $response = ['success' => true];
        } else {
            $response = ['success' => false, 'message' => 'Selection not found'];
        }
    }
}
elseif (($segments[1] ?? '') === 'odds-convert') {
    // Convert odds format
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $odds = (float)($_GET['odds'] ?? 0);
        $fromFormat = $_GET['from'] ?? 'decimal';
        $toFormat = $_GET['to'] ?? 'decimal';
        
        // Convert to decimal first
        if ($fromFormat === 'fractional') {
            $odds = fractionalToDecimal($odds);
        } elseif ($fromFormat === 'american') {
            $odds = americanToDecimal($odds);
        }
        
        // Convert to target format
        $converted = $odds;
        if ($toFormat === 'fractional') {
            $converted = decimalToFractional($odds);
        } elseif ($toFormat === 'american') {
            $converted = decimalToAmerican($odds);
        }
        
        $response = ['success' => true, 'converted' => $converted];
    }
}
elseif (($segments[1] ?? '') === 'validate-email') {
    // Validate email availability
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $email = $_GET['email'] ?? '';
        if (empty($email)) {
            $response = ['success' => false, 'available' => false];
        } else {
            $user = new User();
            $available = !$user->emailExists($email);
            $response = ['success' => true, 'available' => $available];
        }
    }
}
elseif (($segments[1] ?? '') === 'validate-username') {
    // Validate username availability
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $username = $_GET['username'] ?? '';
        if (empty($username)) {
            $response = ['success' => false, 'available' => false];
        } else {
            $user = new User();
            $available = !$user->usernameExists($username);
            $response = ['success' => true, 'available' => $available];
        }
    }
}
else {
    http_response_code(404);
    $response = ['success' => false, 'message' => 'API endpoint not found'];
}

// Send response
echo json_encode($response);
exit;

?>
