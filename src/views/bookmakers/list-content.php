<div class="bookmakers-container">
    <h1>Bookmakers</h1>
    
    <div class="bookmakers-grid">
        <?php foreach ($bookmakers as $bm): ?>
        <div class="bookmaker-card">
            <div class="card-header">
                <h3><?php echo sanitize($bm['name']); ?></h3>
                <div class="card-actions">
                    <a href="/bookmakers/<?php echo $bm['id']; ?>/edit" class="btn btn-small">Edit</a>
                    <form method="POST" action="/bookmakers/<?php echo $bm['id']; ?>/delete" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete bookmaker?')">Delete</button>
                    </form>
                </div>
            </div>
            
            <div class="card-body">
                <table class="card-table">
                    <tr>
                        <td><strong>Account Balance:</strong></td>
                        <td><?php echo formatCurrency($bm['account_balance'], getCurrentUser()['currency']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Bonus Balance:</strong></td>
                        <td><?php echo formatCurrency($bm['bonus_balance'], getCurrentUser()['currency']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Bets:</strong></td>
                        <td><?php echo $bm['stats']['total_bets'] ?? 0; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Win Rate:</strong></td>
                        <td><?php echo $bm['win_rate']; ?>%</td>
                    </tr>
                    <tr>
                        <td><strong>ROI:</strong></td>
                        <td><?php echo $bm['roi']; ?>%</td>
                    </tr>
                    <tr>
                        <td><strong>Profit/Loss:</strong></td>
                        <td class="<?php echo ($bm['stats']['profit'] >= 0) ? 'success' : 'danger'; ?>">
                            <?php echo formatCurrency($bm['stats']['profit'] ?? 0, getCurrentUser()['currency']); ?>
                        </td>
                    </tr>
                </table>
                
                <?php if ($bm['url']): ?>
                <p><small>URL: <a href="<?php echo sanitize($bm['url']); ?>" target="_blank"><?php echo sanitize($bm['url']); ?></a></small></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="quick-actions">
        <a href="/bookmakers/add" class="btn btn-primary btn-large">+ Add Bookmaker</a>
    </div>
</div>
