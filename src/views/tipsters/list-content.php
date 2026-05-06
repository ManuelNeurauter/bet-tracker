<div class="tipsters-container">
    <h1>Tipsters</h1>
    
    <table class="tipsters-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Bets</th>
                <th>Won</th>
                <th>Lost</th>
                <th>Win Rate</th>
                <th>Profit/Loss</th>
                <th>ROI</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tipsters as $tipster): ?>
            <tr>
                <td><strong><?php echo sanitize($tipster['name']); ?></strong></td>
                <td><?php echo $tipster['stats']['total_bets'] ?? 0; ?></td>
                <td><?php echo $tipster['stats']['won_bets'] ?? 0; ?></td>
                <td><?php echo $tipster['stats']['lost_bets'] ?? 0; ?></td>
                <td><?php echo $tipster['win_rate']; ?>%</td>
                <td class="<?php echo ($tipster['stats']['profit'] >= 0) ? 'success' : 'danger'; ?>">
                    <?php echo formatCurrency($tipster['stats']['profit'] ?? 0, getCurrentUser()['currency']); ?>
                </td>
                <td><?php echo $tipster['roi']; ?>%</td>
                <td><?php echo $tipster['is_active'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>'; ?></td>
                <td>
                    <a href="/tipsters/<?php echo $tipster['id']; ?>/edit" class="btn btn-small">Edit</a>
                    <form method="POST" action="/tipsters/<?php echo $tipster['id']; ?>/delete" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete tipster?')">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="quick-actions">
        <a href="/tipsters/add" class="btn btn-primary btn-large">+ Add Tipster</a>
    </div>
</div>
