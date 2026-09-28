<?php
$renderTipster = function ($t, $isTop = false) {
    $st = $t['stats'] ?: [];
    $settled = (int)($st['total_bets'] ?? 0);
    $pl = (float)($st['profit_loss'] ?? 0);
    $source = plainText($t['source_url'] ?? '');
    $isLink = (bool)preg_match('#^https?://#i', $source);
    ob_start(); ?>
    <article class="card entity <?php echo $t['is_active'] ? '' : 'archived'; ?>">
        <div class="entity-head">
            <?php echo avatar($t['name'], 'lg round'); ?>
            <div class="grow">
                <div class="entity-name"><?php echo e($t['name']); ?></div>
                <div class="entity-sub">
                    <?php if ($isLink): ?>
                    <a href="<?php echo e($source); ?>" target="_blank" rel="noopener noreferrer" class="inline-link"><?php echo e(preg_replace('#^https?://(www\.)?#i', '', rtrim($source, '/'))); ?><?php echo icon('external-link', 'icon-xs'); ?></a>
                    <?php elseif ($source !== ''): ?>
                    <?php echo e($source); ?>
                    <?php else: ?>
                    No source saved
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($isTop): ?><span class="badge badge-win" title="Most profit of your active tipsters"><?php echo icon('star', 'icon-xs'); ?> Top</span><?php endif; ?>
            <?php if (!$t['is_active']): ?><span class="badge">Inactive</span><?php endif; ?>
        </div>
        <div class="entity-stats">
            <div>
                <div class="label">Tips settled</div>
                <div class="value"><?php echo $settled; ?></div>
            </div>
            <div>
                <div class="label">Win rate</div>
                <div class="value"><?php echo $settled ? round($t['win_rate'], 1) . '%' : '—'; ?></div>
            </div>
            <div>
                <div class="label">ROI</div>
                <div class="value <?php echo $settled ? toneClass($t['roi']) : 'text-muted'; ?>"><?php echo $settled ? ($t['roi'] > 0 ? '+' : '') . round($t['roi'], 1) . '%' : '—'; ?></div>
            </div>
        </div>
        <div class="entity-balance" style="padding-top:12px">
            <div class="label">Profit following them</div>
            <div class="value <?php echo $settled ? toneClass($pl) : 'text-muted'; ?>" style="font-size:22px"><?php echo $settled ? formatSigned($pl) : '—'; ?></div>
        </div>
        <div class="entity-foot">
            <a href="/bets?tipster_id=<?php echo (int)$t['id']; ?>" class="btn btn-ghost btn-sm"><?php echo icon('receipt-text', 'icon-sm'); ?> Bets</a>
            <span class="spacer"></span>
            <a href="/tipsters/<?php echo (int)$t['id']; ?>/edit" class="btn btn-sm"><?php echo icon('pencil', 'icon-sm'); ?> Edit</a>
            <form method="POST" action="/tipsters/<?php echo (int)$t['id']; ?>/delete" data-confirm="They come off every bet they are linked to. The bets themselves stay." data-confirm-title="Delete <?php echo e($t['name']); ?>?" data-confirm-button="Delete tipster">
                <?php echo csrfField(); ?>
                <button type="submit" class="btn btn-ghost btn-sm btn-icon" title="Delete" aria-label="Delete <?php echo e($t['name']); ?>"><?php echo icon('trash-2', 'icon-sm'); ?></button>
            </form>
        </div>
    </article>
    <?php return ob_get_clean();
};
$topId = null;
foreach ($activeTipsters as $t) {
    if ((int)($t['stats']['total_bets'] ?? 0) > 0 && (float)$t['stats']['profit_loss'] > 0) {
        $topId = $t['id'];
    }
    break;
}
?>
<div class="page-header">
    <div>
        <h1>Tipsters</h1>
        <p class="subtitle">Whose picks you follow, and whether following them pays. Best performers first.</p>
    </div>
    <div class="actions">
        <a href="/tipsters/add" class="btn btn-primary"><?php echo icon('plus'); ?> Add tipster</a>
    </div>
</div>

<?php if (!$activeTipsters && !$inactiveTipsters): ?>
<div class="card">
    <div class="empty">
        <div class="empty-icon"><?php echo icon('users'); ?></div>
        <h3>No tipsters yet</h3>
        <p>Following someone's picks? Add them here, link them to the bets you place on their advice, and see who actually makes you money.</p>
        <a href="/tipsters/add" class="btn btn-primary"><?php echo icon('plus'); ?> Add your first tipster</a>
    </div>
</div>
<?php else: ?>

<?php if ($activeTipsters): ?>
<div class="entity-grid">
    <?php foreach ($activeTipsters as $t) echo $renderTipster($t, $t['id'] === $topId); ?>
    <a href="/tipsters/add" class="entity-add">
        <span class="entity-add-icon"><?php echo icon('plus'); ?></span>
        <span>Add tipster</span>
    </a>
</div>
<?php else: ?>
<div class="card">
    <div class="empty sm">
        <div class="empty-icon"><?php echo icon('users'); ?></div>
        <p>You are not following anyone right now.</p>
        <a href="/tipsters/add" class="btn btn-primary btn-sm"><?php echo icon('plus', 'icon-sm'); ?> Add tipster</a>
    </div>
</div>
<?php endif; ?>

<?php if ($inactiveTipsters): ?>
<details class="section-toggle" <?php echo !$activeTipsters ? 'open' : ''; ?>>
    <summary><?php echo icon('chevron-right', 'icon-sm'); ?> Inactive <span class="count"><?php echo count($inactiveTipsters); ?></span></summary>
    <p class="text-muted" style="font-size:13px;margin:0 0 14px">Inactive tipsters are hidden from the bet form. Their past bets still count.</p>
    <div class="entity-grid">
        <?php foreach ($inactiveTipsters as $t) echo $renderTipster($t); ?>
    </div>
</details>
<?php endif; ?>

<?php endif; ?>
