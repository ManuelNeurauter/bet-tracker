<div class="statistics-container">
    <h1>Statistics & Analytics</h1>
    
    <!-- Key Metrics -->
    <div class="stats-section">
        <h2>Performance Metrics</h2>
        <div class="metrics-grid">
            <div class="metric-card">
                <span class="metric-label">Total Bets</span>
                <span class="metric-value"><?php echo $totalBets; ?></span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Won</span>
                <span class="metric-value success"><?php echo $wonBets; ?></span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Lost</span>
                <span class="metric-value danger"><?php echo $lostBets; ?></span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Void</span>
                <span class="metric-value"><?php echo $voidBets; ?></span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Win Rate</span>
                <span class="metric-value"><?php echo $winRate; ?>%</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Total Staked</span>
                <span class="metric-value"><?php echo formatCurrency($totalStaked, $userData['currency']); ?></span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Total Returned</span>
                <span class="metric-value"><?php echo formatCurrency($totalReturned, $userData['currency']); ?></span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Total Profit</span>
                <span class="metric-value <?php echo $totalProfit >= 0 ? 'success' : 'danger'; ?>">
                    <?php echo formatCurrency($totalProfit, $userData['currency']); ?>
                </span>
            </div>
            <div class="metric-card">
                <span class="metric-label">ROI</span>
                <span class="metric-value"><?php echo $roi; ?>%</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Yield</span>
                <span class="metric-value"><?php echo $yield; ?>%</span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Avg Odds</span>
                <span class="metric-value"><?php echo number_format($avgOdds, 2); ?></span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Avg Stake</span>
                <span class="metric-value"><?php echo formatCurrency($avgStake, $userData['currency']); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Profit by Sport Table -->
    <div class="stats-section">
        <h2>P&L by Sport</h2>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Sport</th>
                    <th>Bets</th>
                    <th>Won</th>
                    <th>Lost</th>
                    <th>Total Staked</th>
                    <th>Total Returned</th>
                    <th>Profit/Loss</th>
                    <th>ROI</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profitBySport as $sport): ?>
                <tr>
                    <td><strong><?php echo sanitize($sport['name'] ?? 'Unknown'); ?></strong></td>
                    <td><?php echo $sport['total_bets']; ?></td>
                    <td class="success"><?php echo $sport['won_bets']; ?></td>
                    <td class="danger"><?php echo $sport['lost_bets']; ?></td>
                    <td><?php echo formatCurrency($sport['total_staked'], $userData['currency']); ?></td>
                    <td><?php echo formatCurrency($sport['total_returned'], $userData['currency']); ?></td>
                    <td class="<?php echo ($sport['profit'] >= 0) ? 'success' : 'danger'; ?>">
                        <?php echo formatCurrency($sport['profit'], $userData['currency']); ?>
                    </td>
                    <td><?php echo calculateROI($sport['profit'], $sport['total_staked']); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Profit by Bookmaker Table -->
    <div class="stats-section">
        <h2>P&L by Bookmaker</h2>
        <table class="stats-table">
            <thead>
                <tr>
                    <th>Bookmaker</th>
                    <th>Bets</th>
                    <th>Won</th>
                    <th>Lost</th>
                    <th>Total Staked</th>
                    <th>Total Returned</th>
                    <th>Profit/Loss</th>
                    <th>ROI</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profitByBookmaker as $bookmaker): ?>
                <tr>
                    <td><strong><?php echo sanitize($bookmaker['name'] ?? 'Unknown'); ?></strong></td>
                    <td><?php echo $bookmaker['total_bets']; ?></td>
                    <td class="success"><?php echo $bookmaker['won_bets']; ?></td>
                    <td class="danger"><?php echo $bookmaker['lost_bets']; ?></td>
                    <td><?php echo formatCurrency($bookmaker['total_staked'], $userData['currency']); ?></td>
                    <td><?php echo formatCurrency($bookmaker['total_returned'], $userData['currency']); ?></td>
                    <td class="<?php echo ($bookmaker['profit'] >= 0) ? 'success' : 'danger'; ?>">
                        <?php echo formatCurrency($bookmaker['profit'], $userData['currency']); ?>
                    </td>
                    <td><?php echo calculateROI($bookmaker['profit'], $bookmaker['total_staked']); ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
