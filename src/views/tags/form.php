<?php
/**
 * Shared tag form. Expects $tag (array|null), $old (array|null) and, when editing, $stats.
 */
$isEdit = !empty($tag);
$old = $old ?? null;
$name = $old['name'] ?? plainText($tag['name'] ?? '');
$color = $old['color'] ?? ($tag['color'] ?? '#8b7bff');
$palette = ['#8b7bff', '#6366f1', '#38bdf8', '#14b8a6', '#22c55e', '#84cc16', '#eab308', '#f59e0b', '#f97316', '#ef4444', '#ec4899', '#a855f7', '#64748b'];
$action = $isEdit ? '/tags/' . (int)$tag['id'] . '/edit' : '/tags/add';
?>
<div class="grid grid-main-side" style="align-items:start">
    <form method="POST" action="<?php echo $action; ?>" class="card">
        <?php echo csrfField(); ?>
        <section class="form-section">
            <div class="form-grid">
                <div class="field col-12">
                    <label for="name">Name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" required maxlength="50" value="<?php echo e($name); ?>" placeholder="e.g. Value bet" data-mirror="#tagPreviewName" <?php echo $isEdit ? '' : 'autofocus'; ?>>
                </div>
                <div class="field col-12">
                    <label for="color">Colour</label>
                    <div class="color-field">
                        <input type="color" id="color" name="color" value="<?php echo e($color); ?>" aria-label="Pick any colour">
                        <div class="swatches" data-swatches="color">
                            <?php foreach ($palette as $c): ?>
                            <button type="button" class="swatch" style="--c: <?php echo $c; ?>" data-color="<?php echo $c; ?>" aria-label="Use <?php echo $c; ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <div class="card-footer">
            <?php if ($isEdit): ?>
            <button type="submit" form="deleteTagForm" class="btn btn-danger"><?php echo icon('trash-2', 'icon-sm'); ?> Delete</button>
            <?php else: ?>
            <span></span>
            <?php endif; ?>
            <div class="form-actions">
                <a href="/tags" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary"><?php echo icon('check', 'icon-sm'); ?> <?php echo $isEdit ? 'Save changes' : 'Create tag'; ?></button>
            </div>
        </div>
    </form>

    <aside class="stack">
        <section class="card">
            <div class="card-header"><div><h2>Preview</h2><div class="hint">How the tag shows on your bets</div></div></div>
            <div class="card-body">
                <div class="preview-bet">
                    <div class="row-between">
                        <div>
                            <div class="cell-title">Arsenal vs Chelsea</div>
                            <div class="cell-sub">Arsenal to win · @ 2.10</div>
                        </div>
                        <?php echo getStatusBadge(BET_STATUS_WON); ?>
                    </div>
                    <div class="chips" style="margin-top:12px">
                        <span class="chip" data-color-preview style="--chip: <?php echo e($color); ?>"><span id="tagPreviewName"><?php echo e($name !== '' ? $name : 'Value bet'); ?></span></span>
                    </div>
                </div>
            </div>
        </section>
        <?php if ($isEdit && $stats && (int)$stats['total_bets'] > 0): ?>
        <section class="card">
            <div class="card-body" style="padding-top:6px">
                <dl class="dl">
                    <dt>Bets with this tag</dt>
                    <dd class="num"><?php echo (int)$stats['total_bets']; ?></dd>
                    <dt>Profit</dt>
                    <dd class="num <?php echo toneClass($stats['profit_loss']); ?>" style="font-weight:620"><?php echo formatSigned($stats['profit_loss'] ?? 0); ?></dd>
                </dl>
            </div>
            <div class="card-footer">
                <a href="/bets?tag_id=<?php echo (int)$tag['id']; ?>" class="card-link">See these bets <?php echo icon('chevron-right', 'icon-xs'); ?></a>
            </div>
        </section>
        <?php endif; ?>
    </aside>
</div>

<?php if ($isEdit): ?>
<form method="POST" action="/tags/<?php echo (int)$tag['id']; ?>/delete" id="deleteTagForm" hidden data-confirm="The tag comes off every bet that has it. The bets themselves stay." data-confirm-title="Delete this tag?" data-confirm-button="Delete tag">
    <?php echo csrfField(); ?>
</form>
<?php endif; ?>
