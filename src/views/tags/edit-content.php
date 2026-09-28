<div class="page-header">
    <div class="row" style="gap:14px">
        <span class="tag-swatch" style="--chip: <?php echo e($tag['color']); ?>;width:44px;height:44px;border-radius:12px"><?php echo icon('tag'); ?></span>
        <div>
            <h1><?php echo e($tag['name']); ?></h1>
            <p class="subtitle">Rename it or change its colour. Every bet with this tag updates.</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/form.php'; ?>
