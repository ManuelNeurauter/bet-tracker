<?php
/**
 * Dashboard Controller
 */

class DashboardController {
    
    public static function index() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $user = new User();
        $userStats = $user->getStatistics($userId);
        
        $bet = new Bet();
        $recentBets = $bet->getRecentBets($userId, 8);
        $pendingBets = $bet->getPendingBets($userId);
        
        $currentBankroll = (float)$user->getCurrentBankroll($userId);
        $bankrollHistory = $user->getBankrollHistory($userId, 90);
        if (empty($bankrollHistory) || end($bankrollHistory)['snapshot_date'] !== date('Y-m-d')) {
            $bankrollHistory[] = [
                'snapshot_date' => date('Y-m-d'),
                'balance' => $currentBankroll,
            ];
        }
        $bankrollStart = (float)$bankrollHistory[0]['balance'];
        $bankrollChange = $currentBankroll - $bankrollStart;
        $userData = getCurrentUser();
        
        // Calculate metrics
        $totalBets = (int)($userStats['total_bets'] ?? 0);
        $wonBets = (int)($userStats['won_bets'] ?? 0);
        $totalStaked = (float)($userStats['total_staked'] ?? 0);
        $totalProfit = (float)($userStats['total_profit'] ?? 0);
        $avgOdds = (float)($userStats['avg_odds'] ?? 0);
        
        $winRate = $totalBets > 0 ? round(($wonBets / $totalBets) * 100, 1) : 0;
        $roi = calculateROI($totalProfit, $totalStaked);

        // Open exposure
        $pendingStake = array_sum(array_map('floatval', array_column($pendingBets, 'stake')));
        $pendingReturn = array_sum(array_map('floatval', array_column($pendingBets, 'potential_return')));

        // Settled bets drive the trend widgets
        $settled = $bet->getSettledBets($userId);
        $analytics = Analytics::build($settled);
        $recentForm = array_slice(array_reverse($settled), 0, 10);

        $thisMonth = date('Y-m');
        $monthProfit = $analytics['by_month'][$thisMonth]['profit'] ?? 0;
        $monthBets = $analytics['by_month'][$thisMonth]['bets'] ?? 0;
        $monthlyPl = array_slice($analytics['by_month'], -6, 6, true);

        $bookmakerModel = new Bookmaker();
        $bookmakers = $bookmakerModel->getByUser($userId);

        // Profit by sport
        $profitBySport = $bet->getProfitByGroup($userId, 'sport_id');

        // Calendar window: limit payload size while still supporting nearby month navigation
        $startDate = date('Y-m-d', strtotime('-180 days'));
        $endDate = date('Y-m-d', strtotime('+180 days'));
        $dailySummary = $bet->getDailySummary($userId, $startDate, $endDate);
        $betsByDate = $bet->getBetsByDateRange($userId, $startDate, $endDate);
        
        include __DIR__ . '/../views/dashboard.php';
    }
}
