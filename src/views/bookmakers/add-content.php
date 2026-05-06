<div class="form-container">
    <h1>Add New Bookmaker</h1>
    
    <form method="POST" action="/bookmakers/add" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
            <label for="name">Bookmaker Name *</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="url">Website URL</label>
            <input type="url" id="url" name="url">
        </div>
        
        <div class="form-group">
            <label for="account_balance">Account Balance</label>
            <input type="number" id="account_balance" name="account_balance" step="0.01" value="0">
        </div>
        
        <div class="form-group">
            <label for="bonus_balance">Bonus Balance</label>
            <input type="number" id="bonus_balance" name="bonus_balance" step="0.01" value="0">
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="4" placeholder="E.g., promotions, restrictions, etc."></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Bookmaker</button>
            <a href="/bookmakers" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
