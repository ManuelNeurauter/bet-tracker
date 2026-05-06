<div class="bets-list-container">
    <h1>Bet History</h1>
    
    <!-- Filters -->
    <div class="filters-panel">
        <form method="GET" class="filters-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="pending" <?php echo ($filters['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="won" <?php echo ($filters['status'] === 'won') ? 'selected' : ''; ?>>Won</option>
                        <option value="lost" <?php echo ($filters['status'] === 'lost') ? 'selected' : ''; ?>>Lost</option>
                        <option value="cashout" <?php echo ($filters['status'] === 'cashout') ? 'selected' : ''; ?>>Cashed Out</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Sport</label>
                    <select name="sport_id">
                        <option value="">All</option>
                        <?php foreach ($sports as $sport): ?>
                        <option value="<?php echo $sport['id']; ?>" <?php echo ($filters['sport_id'] == $sport['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($sport['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Bookmaker</label>
                    <select name="bookmaker_id">
                        <option value="">All</option>
                        <?php foreach ($bookmakers as $bm): ?>
                        <option value="<?php echo $bm['id']; ?>" <?php echo ($filters['bookmaker_id'] == $bm['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($bm['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-secondary">Filter</button>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo sanitize($filters['search']); ?>" placeholder="Event or selection...">
                </div>
                
                <div class="filter-group">
                    <label>Min Odds</label>
                    <input type="number" name="min_odds" step="0.01" value="<?php echo sanitize($filters['min_odds']); ?>">
                </div>
                
                <div class="filter-group">
                    <label>Max Odds</label>
                    <input type="number" name="max_odds" step="0.01" value="<?php echo sanitize($filters['max_odds']); ?>">
                </div>
            </div>
        </form>
    </div>
    
    <!-- Bets Table -->
    <div class="bets-table-wrapper">
        <table class="bets-table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Sport</th>
                    <th>Type</th>
                    <th>Selection</th>
                    <th>Odds</th>
                    <th>Stake</th>
                    <th>Return</th>
                    <th>P&L</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bets as $bet): ?>
                <tr class="row-<?php echo $bet['status']; ?>">
                    <td><a href="/bets/<?php echo $bet['id']; ?>" class="bet-link"><?php echo sanitize($bet['event_name']); ?></a></td>
                    <td><?php echo isset($sports[$bet['sport_id']]) ? sanitize($sports[$bet['sport_id']]['name']) : '-'; ?></td>
                    <td><?php echo ucfirst(str_replace('_', ' ', $bet['bet_type'])); ?></td>
                    <td><?php echo sanitize(substr($bet['selection'], 0, 30)); ?></td>
                    <td><?php echo number_format($bet['odds'], 2); ?></td>
                    <td><?php echo formatCurrency($bet['stake'], $userData['currency']); ?></td>
                    <td><?php echo $bet['actual_return'] ? formatCurrency($bet['actual_return'], $userData['currency']) : '-'; ?></td>
                    <td class="<?php echo ($bet['actual_return'] && ($bet['actual_return'] - $bet['stake']) >= 0) ? 'success' : 'danger'; ?>">
                        <?php echo ($bet['actual_return']) ? formatCurrency($bet['actual_return'] - $bet['stake'], $userData['currency']) : '-'; ?>
                    </td>
                    <td><?php echo getStatusBadge($bet['status']); ?></td>
                    <td><?php echo formatDate($bet['created_at'], 'M d'); ?></td>
                    <td>
                        <?php if ($bet['status'] === 'pending'): ?>
                        <div class="bet-actions-quick">
                            <button class="btn btn-success btn-small quick-settle" data-bet-id="<?php echo $bet['id']; ?>" data-status="won" title="Mark as Won">Win</button>
                            <button class="btn btn-danger btn-small quick-settle" data-bet-id="<?php echo $bet['id']; ?>" data-status="lost" title="Mark as Lost">Lose</button>
                            <button class="btn btn-info btn-small quick-cashout" data-bet-id="<?php echo $bet['id']; ?>" title="Cashout">Cashout</button>
                            <a href="/bets/<?php echo $bet['id']; ?>/edit" class="btn btn-small">Edit</a>
                        </div>
                        <?php else: ?>
                        <a href="/bets/<?php echo $bet['id']; ?>/edit" class="btn btn-small">Edit</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="?page=<?php echo $i; ?>" class="page-link <?php echo ($i === $page) ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    
    <!-- Quick Add -->
    <div class="quick-actions">
        <a href="/bets/add" class="btn btn-primary btn-large">+ Add Bet</a>
    </div>
</div>

<!-- Cashout Modal -->
<div id="cashoutModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Cashout Bet</h2>
            <button class="modal-close" onclick="closeCashoutModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="cashoutForm">
                <div class="form-group">
                    <label for="cashoutAmount">Amount Received *</label>
                    <input type="number" id="cashoutAmount" name="cashoutAmount" step="0.01" min="0" required placeholder="Enter amount received">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Confirm Cashout</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCashoutModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentBetId = null;

// Quick settle buttons (Win/Lose)
document.querySelectorAll('.quick-settle').forEach(btn => {
    btn.addEventListener('click', function() {
        const betId = this.dataset.betId;
        const status = this.dataset.status;
        
        if (confirm(`Mark bet as ${status}?`)) {
            quickSettleBet(betId, status);
        }
    });
});

// Cashout button
document.querySelectorAll('.quick-cashout').forEach(btn => {
    btn.addEventListener('click', function() {
        currentBetId = this.dataset.betId;
        document.getElementById('cashoutModal').style.display = 'flex';
        document.getElementById('cashoutAmount').focus();
    });
});

// Cashout form submission
document.getElementById('cashoutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const amount = parseFloat(document.getElementById('cashoutAmount').value);
    
    if (amount >= 0 && currentBetId) {
        quickSettleBet(currentBetId, 'cashout', amount);
    }
});

function quickSettleBet(betId, status, actualReturn = null) {
    const data = {
        betId: betId,
        status: status,
        actualReturn: actualReturn
    };
    
    if (status === 'won' && !actualReturn) {
        // For won bets, fetch the potential return
        const row = document.querySelector(`button[data-bet-id="${betId}"]`).closest('tr');
        const potentialReturnCell = row.querySelector('td:nth-child(7)'); // Return column
        data.actualReturn = parseFloat(potentialReturnCell.textContent.replace(/[^\d.-]/g, ''));
    } else if (status === 'lost' && !actualReturn) {
        // For lost bets, actual return = 0
        data.actualReturn = 0;
    }
    
    fetch('/api/bets/quick-settle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeCashoutModal();
            // Refresh the page to show updated status
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to update bet'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating bet');
    });
}

function closeCashoutModal() {
    document.getElementById('cashoutModal').style.display = 'none';
    document.getElementById('cashoutForm').reset();
    currentBetId = null;
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('cashoutModal');
    if (event.target === modal) {
        closeCashoutModal();
    }
});
</script>
