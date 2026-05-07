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
        $recentBets = $bet->getRecentBets($userId, 10);
        $pendingBets = $bet->getPendingBets($userId);
        
        $currentBankroll = $user->getCurrentBankroll($userId);
        $bankrollHistory = $user->getBankrollHistory($userId, 30);
        if (empty($bankrollHistory)) {
            $bankrollHistory = [[
                'snapshot_date' => date('Y-m-d'),
                'balance' => $currentBankroll,
            ]];
        }
        $userData = getCurrentUser();
        
        // Calculate metrics
        $totalBets = $userStats['total_bets'] ?? 0;
        $wonBets = $userStats['won_bets'] ?? 0;
        $lostBets = $userStats['lost_bets'] ?? 0;
        $totalStaked = $userStats['total_staked'] ?? 0;
        $totalProfit = $userStats['total_profit'] ?? 0;
        $avgOdds = $userStats['avg_odds'] ?? 0;
        
        $winRate = $totalBets > 0 ? round(($wonBets / $totalBets) * 100, 2) : 0;
        $roi = calculateROI($totalProfit, $totalStaked);
        $yield = calculateYield($totalProfit, $userStats['total_returned'] ?? 0);
        
        // Profit by sport
        $profitBySport = $bet->getProfitByGroup($userId, 'sport_id');
        $profitByBookmaker = $bet->getProfitByGroup($userId, 'bookmaker_id');
        
        include __DIR__ . '/../views/dashboard.php';
    }
}


