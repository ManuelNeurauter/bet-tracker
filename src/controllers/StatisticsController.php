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
        
        $totalBets = $userStats['total_bets'] ?? 0;
        $wonBets = $userStats['won_bets'] ?? 0;
        $lostBets = $userStats['lost_bets'] ?? 0;
        $voidBets = $userStats['void_bets'] ?? 0;
        $totalStaked = $userStats['total_staked'] ?? 0;
        $totalReturned = $userStats['total_returned'] ?? 0;
        $totalProfit = $userStats['total_profit'] ?? 0;
        
        $winRate = $totalBets > 0 ? round(($wonBets / $totalBets) * 100, 2) : 0;
        $roi = calculateROI($totalProfit, $totalStaked);
        $yield = calculateYield($totalProfit, $totalReturned);
        $avgOdds = $userStats['avg_odds'] ?? 0;
        $avgStake = $totalBets > 0 ? round($totalStaked / $totalBets, 2) : 0;
        $avgProfit = $wonBets > 0 ? round($totalProfit / $wonBets, 2) : 0;
        
        // Profit by sport
        $profitBySport = $bet->getProfitByGroup($userId, 'sport_id');
        $profitByBookmaker = $bet->getProfitByGroup($userId, 'bookmaker_id');
        
        $userData = getCurrentUser();
        
        include __DIR__ . '/../views/statistics.php';
    }
}

?>
