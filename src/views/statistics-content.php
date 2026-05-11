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
                <span class="metric-label">Cashed Out</span>
                <span class="metric-value"><?php echo $cashedOutBets; ?></span>
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
                <span class="metric-value"><?php echo $avgOdds; ?></span>
            </div>
            <div class="metric-card">
                <span class="metric-label">Avg Stake</span>
                <span class="metric-value"><?php echo formatCurrency($avgStake, $userData['currency']); ?></span>
            </div>
        </div>
    </div>
    
    <!-- P&L by Sport -->
    <div class="stats-section">
        <h2>P&L by Sport</h2>
        <div style="overflow-x: auto;">
            <table>
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
                    <?php if (!empty($profitBySport)): ?>
                        <?php foreach ($profitBySport as $row): ?>
                        <tr>
                            <td><?php echo $row['sport_name'] ?? 'N/A'; ?></td>
                            <td><?php echo $row['bet_count']; ?></td>
                            <td><?php echo $row['won_count']; ?></td>
                            <td><?php echo $row['lost_count']; ?></td>
                            <td><?php echo formatCurrency($row['total_staked'], $userData['currency']); ?></td>
                            <td><?php echo formatCurrency($row['total_returned'], $userData['currency']); ?></td>
                            <td class="<?php echo $row['profit_loss'] >= 0 ? 'success' : 'danger'; ?>" style="font-weight: 600;">
                                <?php echo formatCurrency($row['profit_loss'], $userData['currency']); ?>
                            </td>
                            <td><?php echo $row['roi']; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; color: var(--text-tertiary);">No data available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- P&L by Bookmaker -->
    <div class="stats-section">
        <h2>P&L by Bookmaker</h2>
        <div style="overflow-x: auto;">
            <table>
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
                    <?php if (!empty($profitByBookmaker)): ?>
                        <?php foreach ($profitByBookmaker as $row): ?>
                        <tr>
                            <td><?php echo $row['bookmaker_name'] ?? 'N/A'; ?></td>
                            <td><?php echo $row['bet_count']; ?></td>
                            <td><?php echo $row['won_count']; ?></td>
                            <td><?php echo $row['lost_count']; ?></td>
                            <td><?php echo formatCurrency($row['total_staked'], $userData['currency']); ?></td>
                            <td><?php echo formatCurrency($row['total_returned'], $userData['currency']); ?></td>
                            <td class="<?php echo $row['profit_loss'] >= 0 ? 'success' : 'danger'; ?>" style="font-weight: 600;">
                                <?php echo formatCurrency($row['profit_loss'], $userData['currency']); ?>
                            </td>
                            <td><?php echo $row['roi']; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; color: var(--text-tertiary);">No data available</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
