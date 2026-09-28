<?php
/**
 * Statistics Controller
 */

class StatisticsController {
    
    public static function index() {
        requireLogin();
        
        $userId = getCurrentUserId();
        $user = new User();
        $bet = new Bet();
        
        $userStats = $user->getStatistics($userId);
        
        $totalBets = (int)($userStats['total_bets'] ?? 0);
        $wonBets = (int)($userStats['won_bets'] ?? 0);
        $lostBets = (int)($userStats['lost_bets'] ?? 0);
        $cashedOutBets = (int)($userStats['cashed_out_bets'] ?? 0);
        $totalStaked = (float)($userStats['total_staked'] ?? 0);
        $totalReturned = (float)($userStats['total_returned'] ?? 0);
        $totalProfit = (float)($userStats['total_profit'] ?? 0);
        
        $winRate = $totalBets > 0 ? round(($wonBets / $totalBets) * 100, 1) : 0;
        $roi = calculateROI($totalProfit, $totalStaked);
        $yield = calculateYield($totalProfit, $totalReturned);
        $avgOdds = (float)($userStats['avg_odds'] ?? 0);
        $avgStake = $totalBets > 0 ? round($totalStaked / $totalBets, 2) : 0;
        
        // Breakdowns
        $profitBySport = $bet->getProfitByGroup($userId, 'sport_id');
        $profitByBookmaker = $bet->getProfitByGroup($userId, 'bookmaker_id');
        $analytics = Analytics::build($bet->getSettledBets($userId));
        
        $userData = getCurrentUser();
        
        include __DIR__ . '/../views/statistics.php';
    }
}
