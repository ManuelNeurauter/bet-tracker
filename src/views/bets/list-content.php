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
                        <option value="void" <?php echo ($filters['status'] === 'void') ? 'selected' : ''; ?>>Void</option>
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
                        <a href="/bets/<?php echo $bet['id']; ?>" class="btn btn-small">View</a>
                        <a href="/bets/<?php echo $bet['id']; ?>/edit" class="btn btn-small">Edit</a>
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
