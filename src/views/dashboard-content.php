<?php
$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$changePct = $bankrollStart != 0 ? round($bankrollChange / abs($bankrollStart) * 100, 1) : 0;
$bankrollWhole = formatCurrency($currentBankroll, null, 2);
$isNewUser = $totalBets === 0 && empty($recentBets);
$streak = $analytics['streaks'];
$maxSportAbs = 0;
foreach ($profitBySport as $row) {
    $maxSportAbs = max($maxSportAbs, abs((float)$row['profit_loss']));
}
?>
<div class="page-header">
    <div>
        <h1><?php echo e($greeting); ?>, <?php echo e($userData['username']); ?></h1>
        <p class="subtitle">
            <?php if ($isNewUser): ?>
            Let's get your first bets on the board.
            <?php elseif (count($pendingBets) > 0): ?>
            You have <?php echo count($pendingBets); ?> open bet<?php echo count($pendingBets) === 1 ? '' : 's'; ?> worth <?php echo formatCurrency($pendingReturn); ?> if they all land.
            <?php else: ?>
            All your bets are settled. Here is how you are doing.
            <?php endif; ?>
        </p>
    </div>
    <div class="actions">
        <a href="/statistics" class="btn"><?php echo icon('chart-column'); ?> Statistics</a>
        <a href="/bets/add" class="btn btn-primary keep"><?php echo icon('plus'); ?> New bet</a>
    </div>
</div>

<?php if ($isNewUser): ?>
<div class="card hero mb-3">
    <div class="eyebrow"><?php echo icon('sparkles', 'icon-xs'); ?> Getting started</div>
    <h2 style="font-size:22px;letter-spacing:-.03em">Three steps to your first insights</h2>
    <div class="grid grid-3 mt-2" style="padding-bottom:18px">
        <a href="/bookmakers/add" class="card kpi" style="color:inherit">
            <div class="kpi-top"><span class="kpi-label">Step 1</span><span class="kpi-icon accent"><?php echo icon('landmark'); ?></span></div>
            <div class="kpi-value" style="font-size:17px">Add a bookmaker</div>
            <div class="kpi-meta">Enter your balance so your bankroll is tracked.</div>
        </a>
        <a href="/bets/add" class="card kpi" style="color:inherit">
            <div class="kpi-top"><span class="kpi-label">Step 2</span><span class="kpi-icon win"><?php echo icon('receipt-text'); ?></span></div>
            <div class="kpi-value" style="font-size:17px">Log a bet</div>
            <div class="kpi-meta">Odds, stake and selection. Settle it when it's done.</div>
        </a>
        <a href="/tags" class="card kpi" style="color:inherit">
            <div class="kpi-top"><span class="kpi-label">Step 3</span><span class="kpi-icon pending"><?php echo icon('tag'); ?></span></div>
            <div class="kpi-value" style="font-size:17px">Tag your strategies</div>
            <div class="kpi-meta">See which approaches actually make money.</div>
        </a>
    </div>
</div>
<?php endif; ?>

<div class="grid grid-main-side mb-2">
    <section class="card hero">
        <div class="hero-top">
            <div>
                <div class="hero-label"><?php echo icon('wallet', 'icon-sm'); ?> Bankroll</div>
                <div class="hero-value <?php echo $currentBankroll < 0 ? 'text-loss' : ''; ?>"><?php echo e($bankrollWhole); ?></div>
                <div class="hero-meta">
                    <?php if (count($bankrollHistory) > 1): ?>
                    <span class="delta <?php echo $bankrollChange > 0 ? 'up' : ($bankrollChange < 0 ? 'down' : 'flat'); ?>">
                        <?php echo icon($bankrollChange >= 0 ? 'arrow-up-right' : 'arrow-down-right'); ?>
                        <?php echo ($changePct > 0 ? '+' : '') . $changePct; ?>%
                    </span>
                    <span><?php echo formatSigned($bankrollChange); ?> since <?php echo formatUserDate($bankrollHistory[0]['snapshot_date']); ?></span>
                    <?php else: ?>
                    <span>Your bankroll history builds up as you settle bets.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-stats">
                <div class="hero-stat">
                    <div class="label">All-time profit</div>
                    <div class="value <?php echo toneClass($totalProfit); ?>"><?php echo formatSigned($totalProfit); ?></div>
                </div>
                <div class="hero-stat">
                    <div class="label">ROI</div>
                    <div class="value <?php echo toneClass($roi); ?>"><?php echo ($roi > 0 ? '+' : '') . $roi; ?>%</div>
                </div>
                <div class="hero-stat">
                    <div class="label">In play</div>
                    <div class="value"><?php echo formatCurrency($pendingStake); ?></div>
                </div>
            </div>
        </div>
        <div class="chart-box hero-chart">
            <canvas id="bankrollChart" aria-label="Bankroll over time"></canvas>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Open bets</h2>
                <div class="hint"><?php echo count($pendingBets); ?> pending · <?php echo formatCurrency($pendingStake); ?> staked</div>
            </div>
            <a href="/bets?status=pending" class="card-link">View all <?php echo icon('chevron-right', 'icon-xs'); ?></a>
        </div>
        <div class="card-body flush">
            <?php if ($pendingBets): ?>
            <div class="list">
                <?php foreach (array_slice($pendingBets, 0, 4) as $bet): ?>
                <div class="pending-item">
                    <a href="/bets/<?php echo (int)$bet['id']; ?>" class="cell-main" style="color:inherit">
                        <?php echo sportBadge($bet['sport_name']); ?>
                        <span style="min-width:0">
                            <span class="cell-title"><?php echo e($bet['event_name']); ?></span>
                            <span class="cell-sub"><?php echo e($bet['selection']); ?></span>
                        </span>
                    </a>
                    <div class="text-right">
                        <div class="num" style="font-weight:600"><?php echo formatCurrency($bet['potential_return']); ?></div>
                        <div class="when"><span class="mono">@<?php echo formatOdds($bet['odds']); ?></span> · <?php echo formatCurrency($bet['stake']); ?></div>
                    </div>
                    <div class="actions">
                        <?php include __DIR__ . '/partials/settle-buttons.php'; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty sm">
                <div class="empty-icon"><?php echo icon('ticket'); ?></div>
                <h3>No open bets</h3>
                <p>Bets you log as pending show up here so you can settle them in one tap.</p>
                <a href="/bets/add" class="btn btn-sm"><?php echo icon('plus', 'icon-sm'); ?> Log a bet</a>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="grid grid-4 mb-2">
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">Win rate</span><span class="kpi-icon win"><?php echo icon('target'); ?></span></div>
        <div class="kpi-value"><?php echo $winRate; ?>%</div>
        <div class="kpi-meta"><?php echo $wonBets; ?>W · <?php echo (int)($userStats['lost_bets'] ?? 0); ?>L · <?php echo (int)($userStats['cashed_out_bets'] ?? 0); ?> cashed out</div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">This month</span><span class="kpi-icon accent"><?php echo icon('calendar'); ?></span></div>
        <div class="kpi-value <?php echo toneClass($monthProfit); ?>"><?php echo formatSigned($monthProfit); ?></div>
        <div class="kpi-meta"><?php echo (int)$monthBets; ?> settled in <?php echo date('F'); ?></div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">Total staked</span><span class="kpi-icon cashout"><?php echo icon('coins'); ?></span></div>
        <div class="kpi-value"><?php echo formatCurrency($totalStaked); ?></div>
        <div class="kpi-meta">over <?php echo $totalBets; ?> settled bets</div>
    </div>
    <div class="card kpi">
        <div class="kpi-top"><span class="kpi-label">Average odds</span><span class="kpi-icon pending"><?php echo icon('scale'); ?></span></div>
        <div class="kpi-value"><?php echo $avgOdds > 0 ? formatOdds($avgOdds) : '—'; ?></div>
        <div class="kpi-meta"><?php echo $avgOdds > 0 ? round(100 / $avgOdds, 1) . '% implied chance' : 'No settled bets yet'; ?></div>
    </div>
</div>

<div class="grid grid-main-side mb-2">
    <section class="card" data-calendar>
        <div class="card-header">
            <div>
                <h2>Calendar</h2>
                <div class="hint">Profit or loss per day, and the stake riding on upcoming days. Tap a day to see its bets.</div>
            </div>
            <div class="calendar-head">
                <button type="button" class="btn btn-ghost btn-icon btn-sm" data-cal-prev aria-label="Previous month"><?php echo icon('chevron-left', 'icon-sm'); ?></button>
                <span class="month" data-cal-label></span>
                <button type="button" class="btn btn-ghost btn-icon btn-sm" data-cal-next aria-label="Next month"><?php echo icon('chevron-right', 'icon-sm'); ?></button>
                <button type="button" class="btn btn-sm desktop-only" data-cal-today>Today</button>
            </div>
        </div>
        <div class="card-body">
            <div class="calendar" data-cal-grid></div>
            <div class="legend mt-2">
                <span><i style="--c:var(--win)"></i>Profit</span>
                <span><i style="--c:var(--loss)"></i>Loss</span>
                <span><i style="--c:var(--pending)"></i>Staked, not settled</span>
                <span><i style="--c:var(--accent)"></i>Today</span>
            </div>
        </div>
    </section>

    <div class="stack">
        <section class="card">
            <div class="card-header">
                <div>
                    <h2>Recent form</h2>
                    <div class="hint">Last <?php echo count($recentForm); ?> settled bets, newest first</div>
                </div>
            </div>
            <div class="card-body">
                <?php if ($recentForm): ?>
                <div class="form-dots">
                    <?php foreach ($recentForm as $f): ?>
                    <a href="/bets/<?php echo (int)$f['id']; ?>" class="form-dot <?php echo e($f['status']); ?>" title="<?php echo e(plainText($f['event_name'])); ?>"><?php echo $f['status'] === 'won' ? 'W' : ($f['status'] === 'lost' ? 'L' : 'C'); ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="row mt-2 wrap" style="gap:18px">
                    <div>
                        <div class="text-muted" style="font-size:12px">Current streak</div>
                        <div style="font-weight:650;font-size:17px" class="<?php echo $streak['current_type'] === 'win' ? 'text-win' : ($streak['current_type'] === 'loss' ? 'text-loss' : ''); ?>">
                            <?php echo $streak['current'] ? $streak['current'] . ' ' . ($streak['current_type'] === 'win' ? 'win' : 'loss') . ($streak['current'] === 1 ? '' : ($streak['current_type'] === 'win' ? 's' : 'es')) : '—'; ?>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Best run</div>
                        <div style="font-weight:650;font-size:17px"><?php echo (int)$streak['longest_win']; ?> wins</div>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Worst run</div>
                        <div style="font-weight:650;font-size:17px"><?php echo (int)$streak['longest_loss']; ?> losses</div>
                    </div>
                </div>
                <?php else: ?>
                <p class="text-muted">Settle a few bets to see your form.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="card">
            <div class="card-header">
                <div>
                    <h2>Profit by sport</h2>
                    <div class="hint">Settled bets only</div>
                </div>
                <a href="/statistics" class="card-link">Details <?php echo icon('chevron-right', 'icon-xs'); ?></a>
            </div>
            <div class="card-body">
                <?php if ($profitBySport): ?>
                <div class="stack-sm" style="gap:14px">
                    <?php foreach (array_slice($profitBySport, 0, 5) as $row): $pl = (float)$row['profit_loss']; ?>
                    <div>
                        <div class="row-between" style="font-size:13px;margin-bottom:6px">
                            <span class="row" style="gap:10px"><?php echo sportBadge($row['sport_name'] ?? ''); ?><span style="font-weight:560"><?php echo e($row['sport_name'] ?? 'No sport'); ?></span></span>
                            <span class="num <?php echo toneClass($pl); ?>" style="font-weight:620"><?php echo formatSigned($pl); ?></span>
                        </div>
                        <div class="bar <?php echo $pl >= 0 ? 'win' : 'loss'; ?>"><span style="width:<?php echo $maxSportAbs > 0 ? max(3, round(abs($pl) / $maxSportAbs * 100)) : 0; ?>%"></span></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted">No settled bets yet.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<div class="grid grid-side-main">
    <div class="stack">
    <section class="card">
        <div class="card-header">
            <div>
                <h2>Monthly profit</h2>
                <div class="hint">Last <?php echo max(1, count($monthlyPl)); ?> months</div>
            </div>
        </div>
        <div class="card-body">
            <div class="chart-box sm"><canvas id="monthlyChart" aria-label="Monthly profit"></canvas></div>
        </div>
    </section>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Bookmakers</h2>
                <div class="hint">Where your bankroll sits</div>
            </div>
            <a href="/bookmakers" class="card-link">Manage <?php echo icon('chevron-right', 'icon-xs'); ?></a>
        </div>
        <div class="card-body flush">
            <?php if ($bookmakers): ?>
            <div class="list">
                <?php foreach (array_slice($bookmakers, 0, 5) as $bm): $bal = (float)$bm['account_balance'] + (float)$bm['bonus_balance']; ?>
                <a href="/bookmakers/<?php echo (int)$bm['id']; ?>/edit" class="list-item">
                    <?php echo avatar($bm['name'], 'sm'); ?>
                    <div class="grow">
                        <div class="title"><?php echo e($bm['name']); ?></div>
                        <div class="bar" style="margin-top:6px"><span style="width:<?php echo $currentBankroll > 0 ? max(2, min(100, round($bal / $currentBankroll * 100))) : 0; ?>%"></span></div>
                    </div>
                    <div class="end num" style="font-weight:600"><?php echo formatCurrency($bal); ?></div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty sm">
                <div class="empty-icon"><?php echo icon('landmark'); ?></div>
                <h3>No bookmakers</h3>
                <p>Add the bookmakers you use to track your bankroll.</p>
                <a href="/bookmakers/add" class="btn btn-sm"><?php echo icon('plus', 'icon-sm'); ?> Add bookmaker</a>
            </div>
            <?php endif; ?>
        </div>
    </section>
    </div>

    <section class="card">
        <div class="card-header">
            <div>
                <h2>Recent bets</h2>
                <div class="hint">The latest bets you logged</div>
            </div>
            <a href="/bets" class="card-link">All bets <?php echo icon('chevron-right', 'icon-xs'); ?></a>
        </div>
        <div class="card-body flush">
            <?php if ($recentBets): ?>
            <div class="table-wrap">
                <table class="table responsive">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th class="num">Odds</th>
                            <th class="num">Stake</th>
                            <th>Status</th>
                            <th class="num">P&amp;L</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBets as $bet): $profit = betProfit($bet); ?>
                        <tr class="is-link" data-href="/bets/<?php echo (int)$bet['id']; ?>">
                            <td class="m-main" data-label="Event">
                                <div class="cell-main">
                                    <?php echo sportBadge($bet['sport_name']); ?>
                                    <div style="min-width:0">
                                        <a href="/bets/<?php echo (int)$bet['id']; ?>" class="cell-title"><?php echo e($bet['event_name']); ?></a>
                                        <span class="cell-sub"><?php echo e($bet['selection']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="num m-inline" data-label="Odds"><span class="mono"><?php echo formatOdds($bet['odds']); ?></span></td>
                            <td class="num m-inline" data-label="Stake"><?php echo formatCurrency($bet['stake']); ?></td>
                            <td class="m-end" data-label="Status"><?php echo getStatusBadge($bet['status']); ?></td>
                            <td class="num m-inline <?php echo $profit === null ? 'text-muted' : toneClass($profit); ?>" data-label="P&amp;L" style="font-weight:600"><?php echo $profit === null ? '—' : formatSigned($profit); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty sm">
                <div class="empty-icon"><?php echo icon('receipt-text'); ?></div>
                <h3>No bets yet</h3>
                <p>Your latest bets will appear here.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include __DIR__ . '/partials/cashout-dialog.php'; ?>

<dialog class="modal wide" id="dayDialog">
    <div class="modal-head">
        <span class="modal-icon"><?php echo icon('calendar-days'); ?></span>
        <div>
            <h2 data-day-title></h2>
            <p data-day-sub></p>
        </div>
        <button type="button" class="btn btn-ghost btn-icon btn-sm close" data-dialog-close aria-label="Close"><?php echo icon('x', 'icon-sm'); ?></button>
    </div>
    <div class="modal-body" style="padding:14px 0 10px">
        <div class="list" data-day-list></div>
    </div>
</dialog>

<script type="application/json" id="calendarData"><?php echo json_encode(['summary' => $dailySummary, 'bets' => $betsByDate], JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const history = <?php echo json_encode(array_map(function ($p) { return ['d' => $p['snapshot_date'], 'v' => (float)$p['balance']]; }, $bankrollHistory)); ?>;
    const monthly = <?php echo json_encode(array_values(array_map(function ($m) { return ['m' => $m['label'], 'v' => $m['profit']]; }, $monthlyPl))); ?>;

    BL.chart(document.getElementById('bankrollChart'), function (t, canvas) {
        const o = BL.chartDefaults(t);
        const up = history.length < 2 || history[history.length - 1].v >= history[0].v;
        const color = up ? t.win : t.loss;
        o.scales.x.ticks.maxTicksLimit = 6;
        o.scales.y.ticks.callback = (v) => BL.money(v, { compact: true });
        o.plugins.tooltip.callbacks = { label: (c) => ' ' + BL.money(c.parsed.y) };
        return {
            type: 'line',
            data: {
                labels: history.map((p) => new Date(p.d + 'T12:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                datasets: [{
                    data: history.map((p) => p.v),
                    borderColor: color,
                    backgroundColor: BL.gradient(canvas, color, 220),
                    fill: true,
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

    BL.chart(document.getElementById('monthlyChart'), function (t) {
        const o = BL.chartDefaults(t);
        o.scales.y.ticks.callback = (v) => BL.money(v, { compact: true });
        o.plugins.tooltip.callbacks = { label: (c) => ' ' + BL.money(c.parsed.y, { sign: true }) };
        return {
            type: 'bar',
            data: {
                labels: monthly.map((m) => new Date(m.m + '-15').toLocaleDateString('en-US', { month: 'short' })),
                datasets: [{
                    data: monthly.map((m) => m.v),
                    backgroundColor: monthly.map((m) => m.v >= 0 ? t.win : t.loss),
                    borderRadius: 6,
                    maxBarThickness: 34,
                }],
            },
            options: o,
        };
    });
});
</script>
