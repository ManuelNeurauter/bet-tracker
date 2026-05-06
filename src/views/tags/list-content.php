<div class="tags-container">
    <h1>Tags</h1>
    
    <div class="tags-grid">
        <?php foreach ($tags as $tag): ?>
        <div class="tag-card" style="border-left: 4px solid <?php echo $tag['color']; ?>">
            <h3><?php echo sanitize($tag['name']); ?></h3>
            <div class="tag-actions">
                <a href="/tags/<?php echo $tag['id']; ?>/edit" class="btn btn-small">Edit</a>
                <form method="POST" action="/tags/<?php echo $tag['id']; ?>/delete" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Delete tag?')">Delete</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="quick-actions">
        <a href="/tags/add" class="btn btn-primary btn-large">+ Add Tag</a>
    </div>
</div>
