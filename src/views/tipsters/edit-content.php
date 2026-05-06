<div class="form-container">
    <h1>Edit Tipster</h1>
    
    <form method="POST" action="/tipsters/<?php echo $tipster['id']; ?>/edit" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
            <label for="name">Tipster Name *</label>
            <input type="text" id="name" name="name" value="<?php echo sanitize($tipster['name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="source_url">Source URL</label>
            <input type="url" id="source_url" name="source_url" value="<?php echo sanitize($tipster['source_url'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="4"><?php echo sanitize($tipster['notes'] ?? ''); ?></textarea>
        </div>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo ($tipster['is_active']) ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Tipster</button>
            <a href="/tipsters" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
