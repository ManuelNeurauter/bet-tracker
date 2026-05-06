<div class="bet-detail-container">
    <div class="bet-detail-header">
        <h1><?php echo sanitize($bet['event_name']); ?></h1>
        <div class="bet-actions">
            <a href="/bets/<?php echo $bet['id']; ?>/edit" class="btn btn-primary">Edit</a>
            <a href="/bets" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
    
    <div class="bet-detail-grid">
        <div class="detail-card">
            <h3>Bet Details</h3>
            <table class="detail-table">
                <tr>
                    <td><strong>Status:</strong></td>
                    <td><?php echo getStatusBadge($bet['status']); ?></td>
                </tr>
                <tr>
                    <td><strong>Bet Type:</strong></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $bet['bet_type'])); ?></td>
                </tr>
                <tr>
                    <td><strong>Event Date:</strong></td>
                    <td><?php echo $bet['event_date'] ? formatDate($bet['event_date']) : '-'; ?></td>
                </tr>
                <tr>
                    <td><strong>Created:</strong></td>
                    <td><?php echo formatDate($bet['created_at']); ?></td>
                </tr>
                <?php if ($bet['settled_at']): ?>
                <tr>
                    <td><strong>Settled:</strong></td>
                    <td><?php echo formatDate($bet['settled_at']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div class="detail-card">
            <h3>Selection & Odds</h3>
            <table class="detail-table">
                <tr>
                    <td><strong>Selection:</strong></td>
                    <td><?php echo sanitize($bet['selection']); ?></td>
                </tr>
                <tr>
                    <td><strong>Odds:</strong></td>
                    <td><?php echo number_format($bet['odds'], 3); ?></td>
                </tr>
                <tr>
                    <td><strong>Each Way:</strong></td>
                    <td><?php echo $bet['each_way'] ? 'Yes' : 'No'; ?></td>
                </tr>
            </table>
        </div>
        
        <div class="detail-card">
            <h3>Financial Summary</h3>
            <table class="detail-table">
                <tr>
                    <td><strong>Stake:</strong></td>
                    <td><?php echo formatCurrency($bet['stake'], getCurrentUser()['currency']); ?></td>
                </tr>
                <tr>
                    <td><strong>Potential Return:</strong></td>
                    <td><?php echo formatCurrency($bet['potential_return'], getCurrentUser()['currency']); ?></td>
                </tr>
                <?php if ($bet['actual_return']): ?>
                <tr>
                    <td><strong>Actual Return:</strong></td>
                    <td><?php echo formatCurrency($bet['actual_return'], getCurrentUser()['currency']); ?></td>
                </tr>
                <tr>
                    <td><strong>Profit/Loss:</strong></td>
                    <td class="<?php echo ($bet['actual_return'] - $bet['stake']) >= 0 ? 'success' : 'danger'; ?>">
                        <?php echo formatCurrency($bet['actual_return'] - $bet['stake'], getCurrentUser()['currency']); ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ($bet['cashout_amount']): ?>
                <tr>
                    <td><strong>Cashout Amount:</strong></td>
                    <td><?php echo formatCurrency($bet['cashout_amount'], getCurrentUser()['currency']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div class="detail-card">
            <h3>Bookmaker & Sport</h3>
            <table class="detail-table">
                <tr>
                    <td><strong>Bookmaker:</strong></td>
                    <td><?php echo $bet['bookmaker_id'] ? sanitize($bet['bookmaker_name']) : '-'; ?></td>
                </tr>
                <tr>
                    <td><strong>Sport:</strong></td>
                    <td><?php echo $bet['sport_id'] ? sanitize($bet['sport_name']) : '-'; ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <?php if (!empty($tags) || !empty($tipsters)): ?>
    <div class="bet-metadata">
        <?php if (!empty($tags)): ?>
        <div class="tags-section">
            <h3>Tags</h3>
            <div class="tags-list">
                <?php foreach ($tags as $tag): ?>
                <span class="tag-badge" style="background-color: <?php echo $tag['color']; ?>">
                    <?php echo sanitize($tag['name']); ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($tipsters)): ?>
        <div class="tipsters-section">
            <h3>Tipsters</h3>
            <ul>
                <?php foreach ($tipsters as $tipster): ?>
                <li><?php echo sanitize($tipster['name']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($bet['notes']): ?>
    <div class="notes-section">
        <h3>Notes</h3>
        <p><?php echo nl2br(sanitize($bet['notes'])); ?></p>
    </div>
    <?php endif; ?>
</div>
