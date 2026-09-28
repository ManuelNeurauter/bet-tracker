<div class="page-header">
    <div class="row" style="gap:14px">
        <?php echo avatar($tipster['name'], 'lg round'); ?>
        <div>
            <h1><?php echo e($tipster['name']); ?></h1>
            <p class="subtitle"><?php echo !empty($tipster['is_active']) ? 'Active tipster' : 'Inactive tipster'; ?></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/form.php'; ?>
