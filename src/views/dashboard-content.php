<div class="dashboard-container">
    <h1>Dashboard</h1>
    
    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Bets</div>
            <div class="kpi-value"><?php echo $totalBets; ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Win Rate</div>
            <div class="kpi-value"><?php echo $winRate; ?>%</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Profit/Loss</div>
            <div class="kpi-value <?php echo $totalProfit >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo formatCurrency($totalProfit, $userData['currency']); ?>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">ROI</div>
            <div class="kpi-value"><?php echo $roi; ?>%</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Yield</div>
            <div class="kpi-value"><?php echo $yield; ?>%</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Avg Odds</div>
            <div class="kpi-value"><?php echo number_format($avgOdds, 2); ?></div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="charts-row">
        <div class="chart-container">
            <h3>Bankroll Over Time</h3>
            <canvas id="bankrollChart"></canvas>
        </div>
        <div class="chart-container">
            <h3>P&L by Sport</h3>
            <canvas id="sportChart"></canvas>
        </div>
    </div>
    
    <!-- Recent Bets and Pending Bets -->
    <div class="dashboard-row">
        <div class="dashboard-section">
            <h3>Recent Bets</h3>
            <div class="bets-table">
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Type</th>
                            <th>Odds</th>
                            <th>Stake</th>
                            <th>Status</th>
                            <th>Return</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBets as $bet): ?>
                        <tr class="row-<?php echo $bet['status']; ?>">
                            <td><a href="/bets/<?php echo $bet['id']; ?>"><?php echo sanitize($bet['event_name']); ?></a></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $bet['bet_type'])); ?></td>
                            <td><?php echo number_format($bet['odds'], 2); ?></td>
                            <td><?php echo formatCurrency($bet['stake'], $userData['currency']); ?></td>
                            <td><?php echo getStatusBadge($bet['status']); ?></td>
                            <td><?php echo $bet['actual_return'] ? formatCurrency($bet['actual_return'], $userData['currency']) : '-'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <a href="/bets" class="btn btn-secondary">View All Bets</a>
        </div>
        
        <div class="dashboard-section">
            <h3>Pending Bets (<?php echo count($pendingBets); ?>)</h3>
            <div class="pending-bets">
                <?php if (count($pendingBets) > 0): ?>
                <?php foreach (array_slice($pendingBets, 0, 5) as $bet): ?>
                <div class="pending-bet-card">
                    <div class="bet-event">
                        <strong><?php echo sanitize($bet['event_name']); ?></strong>
                        <span class="date"><?php echo formatDate($bet['event_date']); ?></span>
                    </div>
                    <div class="bet-odds">
                        <span class="label">Odds:</span>
                        <span><?php echo number_format($bet['odds'], 2); ?></span>
                    </div>
                    <div class="bet-stake">
                        <span class="label">Stake:</span>
                        <span><?php echo formatCurrency($bet['stake'], $userData['currency']); ?></span>
                    </div>
                    <div class="bet-return">
                        <span class="label">Potential Return:</span>
                        <span><?php echo formatCurrency($bet['potential_return'], $userData['currency']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="empty-state">No pending bets</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="/bets/add" class="btn btn-primary btn-large">+ Add Bet</a>
    </div>
</div>

<script>
// Placeholder for chart initialization
document.addEventListener('DOMContentLoaded', function() {
    // Bankroll chart
    const bankrollCtx = document.getElementById('bankrollChart');
    if (bankrollCtx) {
        new Chart(bankrollCtx, {
            type: 'line',
            data: {
                labels: ['This would be populated from data'],
                datasets: [{
                    label: 'Bankroll',
                    data: [100],
                    borderColor: '#00d084',
                    backgroundColor: 'rgba(0, 208, 132, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });
    }
    
    // Sport chart
    const sportCtx = document.getElementById('sportChart');
    if (sportCtx) {
        new Chart(sportCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($profitBySport, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($profitBySport, 'profit')); ?>,
                    backgroundColor: ['#00d084', '#ff4757', '#3498db', '#ffa502', '#9b59b6'],
                }]
            },
            options: { responsive: true }
        });
    }
});
</script>
