<?php
/**
 * Analytics
 * Derives breakdowns and trends from a user's settled bets.
 */

class Analytics {

    const ODDS_BUCKETS = [
        [1.0, 1.5, '1.01–1.49'],
        [1.5, 2.0, '1.50–1.99'],
        [2.0, 3.0, '2.00–2.99'],
        [3.0, 5.0, '3.00–4.99'],
        [5.0, 10.0, '5.00–9.99'],
        [10.0, INF, '10.00+'],
    ];

    /**
     * Build all statistics for a list of settled bets (oldest first)
     */
    public static function build(array $bets) {
        $result = [
            'by_type' => [],
            'by_odds' => [],
            'by_weekday' => [],
            'by_month' => [],
            'by_competition' => [],
            'cumulative' => [],
            'streaks' => ['longest_win' => 0, 'longest_loss' => 0, 'current' => 0, 'current_type' => null],
            'best' => null,
            'worst' => null,
            'implied_win_rate' => 0,
            'actual_win_rate' => 0,
            'avg_odds_won' => 0,
            'avg_odds_lost' => 0,
        ];

        foreach (self::ODDS_BUCKETS as $bucket) {
            $result['by_odds'][$bucket[2]] = self::emptyGroup($bucket[2]);
        }
        foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day) {
            $result['by_weekday'][$day] = self::emptyGroup($day);
        }

        $running = 0.0;
        $impliedSum = 0.0;
        $wins = 0;
        $winOdds = [];
        $lossOdds = [];
        $streak = 0;
        $streakType = null;

        foreach ($bets as $bet) {
            $profit = round((float)$bet['actual_return'] - (float)$bet['stake'], 2);
            $won = $bet['status'] === BET_STATUS_WON;
            $odds = (float)$bet['odds'];

            self::add($result['by_type'], betTypeLabel($bet['bet_type']), $bet, $profit, $won);

            foreach (self::ODDS_BUCKETS as $bucket) {
                if ($odds >= $bucket[0] && $odds < $bucket[1]) {
                    self::add($result['by_odds'], $bucket[2], $bet, $profit, $won);
                    break;
                }
            }

            $weekday = date('D', strtotime($bet['bet_date']));
            self::add($result['by_weekday'], $weekday, $bet, $profit, $won);

            $month = date('Y-m', strtotime($bet['settle_date']));
            self::add($result['by_month'], $month, $bet, $profit, $won);

            if (!empty($bet['competition_name'])) {
                self::add($result['by_competition'], plainText($bet['competition_name']), $bet, $profit, $won);
            }

            $day = date('Y-m-d', strtotime($bet['settle_date']));
            $running = round($running + $profit, 2);
            $result['cumulative'][$day] = $running;

            if ($result['best'] === null || $profit > $result['best']['profit']) {
                $result['best'] = $bet + ['profit' => $profit];
            }
            if ($result['worst'] === null || $profit < $result['worst']['profit']) {
                $result['worst'] = $bet + ['profit' => $profit];
            }

            if ($odds > 0) {
                $impliedSum += 1 / $odds;
            }
            if ($won) {
                $wins++;
                $winOdds[] = $odds;
            } elseif ($bet['status'] === BET_STATUS_LOST) {
                $lossOdds[] = $odds;
            }

            // Streaks count wins and losses by profit sign
            $type = $profit > 0 ? 'win' : ($profit < 0 ? 'loss' : null);
            if ($type === null) {
                continue;
            }
            $streak = ($type === $streakType) ? $streak + 1 : 1;
            $streakType = $type;
            if ($type === 'win') {
                $result['streaks']['longest_win'] = max($result['streaks']['longest_win'], $streak);
            } else {
                $result['streaks']['longest_loss'] = max($result['streaks']['longest_loss'], $streak);
            }
        }

        $result['streaks']['current'] = $streak;
        $result['streaks']['current_type'] = $streakType;

        $count = count($bets);
        if ($count > 0) {
            $result['implied_win_rate'] = round($impliedSum / $count * 100, 1);
            $result['actual_win_rate'] = round($wins / $count * 100, 1);
        }
        $result['avg_odds_won'] = $winOdds ? round(array_sum($winOdds) / count($winOdds), 2) : 0;
        $result['avg_odds_lost'] = $lossOdds ? round(array_sum($lossOdds) / count($lossOdds), 2) : 0;

        foreach (['by_type', 'by_odds', 'by_weekday', 'by_month', 'by_competition'] as $key) {
            $result[$key] = array_map([self::class, 'finish'], $result[$key]);
        }

        uasort($result['by_type'], function ($a, $b) { return $b['bets'] <=> $a['bets']; });
        uasort($result['by_competition'], function ($a, $b) { return $b['bets'] <=> $a['bets']; });
        $result['by_competition'] = array_slice($result['by_competition'], 0, 8, true);
        ksort($result['by_month']);

        return $result;
    }

    private static function emptyGroup($label) {
        return ['label' => $label, 'bets' => 0, 'won' => 0, 'staked' => 0.0, 'profit' => 0.0];
    }

    private static function add(array &$groups, $key, array $bet, $profit, $won) {
        if (!isset($groups[$key])) {
            $groups[$key] = self::emptyGroup($key);
        }
        $groups[$key]['bets']++;
        $groups[$key]['won'] += $won ? 1 : 0;
        $groups[$key]['staked'] += (float)$bet['stake'];
        $groups[$key]['profit'] = round($groups[$key]['profit'] + $profit, 2);
    }

    private static function finish(array $group) {
        $group['roi'] = $group['staked'] > 0 ? round($group['profit'] / $group['staked'] * 100, 1) : 0;
        $group['win_rate'] = $group['bets'] > 0 ? round($group['won'] / $group['bets'] * 100, 1) : 0;
        return $group;
    }
}
