<div class="form-container">
    <h1>Edit Bookmaker</h1>
    
    <form method="POST" action="/bookmakers/<?php echo $bookmaker['id']; ?>/edit" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
            <label for="name">Bookmaker Name *</label>
            <input type="text" id="name" name="name" value="<?php echo sanitize($bookmaker['name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="url">Website URL</label>
            <input type="url" id="url" name="url" value="<?php echo sanitize($bookmaker['url'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="account_balance">Account Balance</label>
            <input type="number" id="account_balance" name="account_balance" step="0.01" value="<?php echo number_format($bookmaker['account_balance'], 2); ?>">
        </div>
        
        <div class="form-group">
            <label for="bonus_balance">Bonus Balance</label>
            <input type="number" id="bonus_balance" name="bonus_balance" step="0.01" value="<?php echo number_format($bookmaker['bonus_balance'], 2); ?>">
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="4"><?php echo sanitize($bookmaker['notes'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Bookmaker</button>
            <a href="/bookmakers" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
