<?php
$toRows = function ($groups, $labelKey) {
    return array_map(function ($row) use ($labelKey) {
        $bets = (int)$row['bet_count'];
        return [
            'label' => plainText($row[$labelKey] ?? '') ?: 'None',
            'bets' => $bets,
            'won' => (int)$row['won_count'],
            'staked' => (float)$row['total_staked'],
            'profit' => (float)$row['profit_loss'],
            'roi' => (float)$row['roi'],
            'win_rate' => $bets > 0 ? round($row['won_count'] / $bets * 100, 1) : 0,
        ];
    }, $groups);
};
$sportRows = $toRows($profitBySport, 'sport_name');
$bookmakerRows = $toRows($profitByBookmaker, 'bookmaker_name');
$best = $analytics['best'];
$worst = $analytics['worst'];
$edge = round($analytics['actual_win_rate'] - $analytics['implied_win_rate'], 1);
?>
<div class="page-header">
    <div>
        <h1>Statistics</h1>
        <p class="subtitle">Based on <?php echo $totalBets; ?> settled bet<?php echo $totalBets === 1 ? '' : 's'; ?>. Open bets are left out.</p>
    </div>
    <div class="actions">
        <a href="/bets/export" class="btn"><?php echo icon('download'); ?> Export CSV</a>
    </div>
</div>

<?php if ($totalBets === 0): ?>
<div class="card">
    <div class="empty">
        <div class="empty-icon"><?php echo icon('chart-column'); ?></div>
        <h3>No statistics yet</h3>
        <p>Once you settle a few bets you will see your profit curve, win rate by odds, your best sports and more.</p>
        <a href="/bets/add" class="btn btn-primary btn-sm"><?php echo icon('plus', 'icon-sm'); ?> Log a bet</a>
    </div>
</div>
<?php else: ?>

<div class="grid grid-4 mb-2">
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">Profit</span><span class="kpi-icon <?php echo $totalProfit >= 0 ? 'win' : 'loss'; ?>"><?php echo icon($totalProfit >= 0 ? 'trending-up' : 'trending-down'); ?></span></div>
        <div class="kpi-value <?php echo toneClass($totalProfit); ?>"><?php echo formatSigned($totalProfit); ?></div>
        <div class="kpi-meta"><span class="delta <?php echo $roi > 0 ? 'up' : ($roi < 0 ? 'down' : 'flat'); ?>"><?php echo ($roi > 0 ? '+' : '') . $roi; ?>%</span> return on stake</div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">Win rate</span><span class="kpi-icon accent"><?php echo icon('target'); ?></span></div>
        <div class="kpi-value"><?php echo $winRate; ?>%</div>
        <div class="kpi-meta"><?php echo $wonBets; ?> won · <?php echo $lostBets; ?> lost · <?php echo $cashedOutBets; ?> cashed out</div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">Staked</span><span class="kpi-icon cashout"><?php echo icon('coins'); ?></span></div>
        <div class="kpi-value"><?php echo formatCurrency($totalStaked); ?></div>
        <div class="kpi-meta">Average stake <?php echo formatCurrency($avgStake); ?></div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">Average odds</span><span class="kpi-icon pending"><?php echo icon('scale'); ?></span></div>
        <div class="kpi-value"><?php echo formatOdds($avgOdds); ?></div>
        <div class="kpi-meta">Yield <?php echo ($yield > 0 ? '+' : '') . $yield; ?>% of returns</div>
    </div>
</div>

<div class="grid grid-main-side mb-2">
    <section class="card">
        <div class="card-header">
            <div>
                <h2>Cumulative profit</h2>
                <div class="hint">Running total by the day each bet settled</div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-box lg"><canvas id="cumulativeChart" aria-label="Cumulative profit"></canvas></div>
        </div>
    </section>

    <div class="stack">
        <section class="card">
            <div class="card-header">
                <div>
                    <h2>Results</h2>
                    <div class="hint">How your settled bets ended</div>
                </div>
            </div>
            <div class="card-body">
                <div class="row" style="gap:20px">
                    <div class="chart-box" style="height:140px;width:140px;flex-shrink:0"><canvas id="resultsChart" aria-label="Results split"></canvas></div>
                    <div class="stack-sm" style="flex:1;gap:10px">
                        <?php foreach ([['Won', $wonBets, 'var(--win)'], ['Lost', $lostBets, 'var(--loss)'], ['Cashed out', $cashedOutBets, 'var(--cashout)']] as [$label, $count, $color]): ?>
                        <div class="row-between" style="font-size:13px">
                            <span class="row" style="gap:8px"><i style="width:10px;height:10px;border-radius:3px;background:<?php echo $color; ?>"></i><?php echo $label; ?></span>
                            <span class="num" style="font-weight:600"><?php echo $count; ?> <span class="text-muted" style="font-weight:450">· <?php echo $totalBets ? round($count / $totalBets * 100) : 0; ?>%</span></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2>Your edge</h2>
                    <div class="hint">Win rate compared to what the odds implied</div>
                </div>
                <span class="delta <?php echo $edge > 0 ? 'up' : ($edge < 0 ? 'down' : 'flat'); ?>"><?php echo ($edge > 0 ? '+' : '') . $edge; ?> pts</span>
            </div>
            <div class="card-body stack-sm" style="gap:12px">
                <div>
                    <div class="row-between" style="font-size:13px;margin-bottom:6px"><span>Actual win rate</span><strong class="num"><?php echo $analytics['actual_win_rate']; ?>%</strong></div>
                    <div class="bar win"><span style="width:<?php echo min(100, $analytics['actual_win_rate']); ?>%"></span></div>
                </div>
                <div>
                    <div class="row-between" style="font-size:13px;margin-bottom:6px"><span>Implied by the odds</span><strong class="num"><?php echo $analytics['implied_win_rate']; ?>%</strong></div>
                    <div class="bar"><span style="width:<?php echo min(100, $analytics['implied_win_rate']); ?>%"></span></div>
                </div>
                <p class="text-muted" style="font-size:12.5px">
                    <?php echo $edge >= 0 ? 'You win more often than the prices suggest. That is what long-term profit is built on.' : 'You win less often than the prices suggest. Look at the odds ranges below to see where it leaks.'; ?>
                </p>
            </div>
        </section>
    </div>
</div>

<div class="grid grid-2 mb-2">
    <section class="card">
        <div class="card-header">
            <div>
                <h2>Profit by odds range</h2>
                <div class="hint">Which prices work for you</div>
            </div>
        </div>
        <div class="card-body"><div class="chart-box"><canvas id="oddsChart" aria-label="Profit by odds range"></canvas></div></div>
    </section>
    <section class="card">
        <div class="card-header">
            <div>
                <h2>Profit by weekday</h2>
                <div class="hint">By the day the event took place</div>
            </div>
        </div>
        <div class="card-body"><div class="chart-box"><canvas id="weekdayChart" aria-label="Profit by weekday"></canvas></div></div>
    </section>
</div>

<div class="grid grid-3 mb-2">
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">Biggest win</span><span class="kpi-icon win"><?php echo icon('trophy'); ?></span></div>
        <div class="kpi-value text-win"><?php echo $best ? formatSigned($best['profit']) : '—'; ?></div>
        <div class="kpi-meta truncate"><?php echo $best ? '<a href="/bets/' . (int)$best['id'] . '" class="text-2">' . e($best['event_name']) . '</a> @ ' . formatOdds($best['odds']) : ''; ?></div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">Biggest loss</span><span class="kpi-icon loss"><?php echo icon('trending-down'); ?></span></div>
        <div class="kpi-value text-loss"><?php echo $worst ? formatSigned($worst['profit']) : '—'; ?></div>
        <div class="kpi-meta truncate"><?php echo $worst ? '<a href="/bets/' . (int)$worst['id'] . '" class="text-2">' . e($worst['event_name']) . '</a> @ ' . formatOdds($worst['odds']) : ''; ?></div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">Streaks</span><span class="kpi-icon pending"><?php echo icon('flame'); ?></span></div>
        <div class="kpi-value"><span class="text-win"><?php echo (int)$analytics['streaks']['longest_win']; ?>W</span> <span class="text-muted" style="font-weight:400">/</span> <span class="text-loss"><?php echo (int)$analytics['streaks']['longest_loss']; ?>L</span></div>
        <div class="kpi-meta">Longest winning and losing runs</div>
    </div>
</div>

<div class="grid grid-2 mb-2">
    <section class="card">
        <div class="card-header"><div><h2>By sport</h2><div class="hint">Where your profit comes from</div></div></div>
        <div class="card-body flush">
            <?php $rows = $sportRows; $labelHeader = 'Sport'; $labelBadge = function ($r) { return sportBadge($r['label'] === 'None' ? '' : $r['label']); }; include __DIR__ . '/partials/breakdown-table.php'; ?>
        </div>
    </section>
    <section class="card">
        <div class="card-header"><div><h2>By bookmaker</h2><div class="hint">Settled results per account</div></div></div>
        <div class="card-body flush">
            <?php $rows = $bookmakerRows; $labelHeader = 'Bookmaker'; $labelBadge = function ($r) { return avatar($r['label'], 'sm'); }; include __DIR__ . '/partials/breakdown-table.php'; ?>
        </div>
    </section>
    <section class="card">
        <div class="card-header"><div><h2>By bet type</h2><div class="hint">Singles, doubles, accumulators and more</div></div></div>
        <div class="card-body flush">
            <?php $rows = array_values($analytics['by_type']); $labelHeader = 'Type'; include __DIR__ . '/partials/breakdown-table.php'; ?>
        </div>
    </section>
    <section class="card">
        <div class="card-header"><div><h2>By competition</h2><div class="hint">Your eight most-bet competitions</div></div></div>
        <div class="card-body flush">
            <?php $rows = array_values($analytics['by_competition']); $labelHeader = 'Competition'; include __DIR__ . '/partials/breakdown-table.php'; ?>
        </div>
    </section>
</div>

<section class="card">
    <div class="card-header"><div><h2>By month</h2><div class="hint">Month the bets settled in</div></div></div>
    <div class="card-body flush">
        <?php
        $rows = array_map(function ($m) { $m['label'] = date('F Y', strtotime($m['label'] . '-01')); return $m; }, array_reverse(array_values($analytics['by_month'])));
        $labelHeader = 'Month';
        include __DIR__ . '/partials/breakdown-table.php';
        ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const cumulative = <?php echo json_encode($analytics['cumulative']); ?>;
    const byOdds = <?php echo json_encode(array_values($analytics['by_odds'])); ?>;
    const byWeekday = <?php echo json_encode(array_values($analytics['by_weekday'])); ?>;
    const results = <?php echo json_encode([$wonBets, $lostBets, $cashedOutBets]); ?>;

    BL.chart(document.getElementById('cumulativeChart'), function (t, canvas) {
        const o = BL.chartDefaults(t);
        const values = Object.values(cumulative);
        const color = (values[values.length - 1] || 0) >= 0 ? t.win : t.loss;
        o.scales.y.ticks.callback = (v) => BL.money(v, { compact: true });
        o.scales.x.ticks.maxTicksLimit = 8;
        o.plugins.tooltip.callbacks = { label: (c) => ' ' + BL.money(c.parsed.y, { sign: true }) };
        return {
            type: 'line',
            data: {
                labels: Object.keys(cumulative).map((d) => new Date(d + 'T12:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                datasets: [{
                    data: values,
                    borderColor: color,
                    backgroundColor: BL.gradient(canvas, color, 320),
                    fill: 'origin',
                    cubicInterpolationMode: 'monotone',
                    borderWidth: 2.2,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: color,
                    pointHoverBorderColor: t.surface,
                    pointHoverBorderWidth: 2,
                }],
            },
            options: o,
        };
    });

    BL.chart(document.getElementById('resultsChart'), function (t) {
        return {
            type: 'doughnut',
            data: {
                labels: ['Won', 'Lost', 'Cashed out'],
                datasets: [{ data: results, backgroundColor: [t.win, t.loss, t.cashout], borderColor: t.surface, borderWidth: 3, hoverOffset: 4 }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { display: false }, tooltip: BL.chartDefaults(t).plugins.tooltip },
            },
        };
    });

    function profitBars(id, rows) {
        BL.chart(document.getElementById(id), function (t) {
            const o = BL.chartDefaults(t);
            o.scales.y.ticks.callback = (v) => BL.money(v, { compact: true });
            o.scales.x.ticks.autoSkip = false;
            o.plugins.tooltip.callbacks = {
                label: (c) => ' ' + BL.money(c.parsed.y, { sign: true }),
                afterLabel: (c) => ` ${rows[c.dataIndex].bets} bets · ${rows[c.dataIndex].win_rate}% won · ROI ${rows[c.dataIndex].roi}%`,
            };
            return {
                type: 'bar',
                data: {
                    labels: rows.map((r) => r.label),
                    datasets: [{
                        data: rows.map((r) => r.profit),
                        backgroundColor: rows.map((r) => r.profit >= 0 ? t.win : t.loss),
                        borderRadius: 6,
                        maxBarThickness: 38,
                    }],
                },
                options: o,
            };
        });
    }
    profitBars('oddsChart', byOdds);
    profitBars('weekdayChart', byWeekday);
});
</script>
<?php endif; ?>
