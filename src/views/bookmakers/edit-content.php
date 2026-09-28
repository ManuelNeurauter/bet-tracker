<div class="page-header">
    <div class="row" style="gap:14px">
        <?php echo avatar($bookmaker['name'], 'lg'); ?>
        <div>
            <h1><?php echo e($bookmaker['name']); ?></h1>
            <p class="subtitle"><?php echo !empty($bookmaker['is_archived']) ? 'Archived bookmaker' : 'Edit the account details and balance'; ?></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/form.php'; ?>
