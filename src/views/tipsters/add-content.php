<div class="form-container">
    <h1>Add New Tipster</h1>
    
    <form method="POST" action="/tipsters/add" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
            <label for="name">Tipster Name *</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="source_url">Source URL</label>
            <input type="url" id="source_url" name="source_url">
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="4" placeholder="E.g., specialty, style, etc."></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Tipster</button>
            <a href="/tipsters" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
