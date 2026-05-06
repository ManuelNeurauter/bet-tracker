<div class="form-container">
    <h1>Edit Tag</h1>
    
    <form method="POST" action="/tags/<?php echo $tag['id']; ?>/edit" class="form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-group">
            <label for="name">Tag Name *</label>
            <input type="text" id="name" name="name" value="<?php echo sanitize($tag['name']); ?>" required maxlength="50">
        </div>
        
        <div class="form-group">
            <label for="color">Color *</label>
            <input type="color" id="color" name="color" value="<?php echo $tag['color']; ?>" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Tag</button>
            <a href="/tags" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
