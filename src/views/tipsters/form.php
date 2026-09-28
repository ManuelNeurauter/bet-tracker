<?php
/**
 * Shared tipster form. Expects $tipster (array|null) and, when editing, $stats.
 */
$isEdit = !empty($tipster);
$t = $tipster ?? [];
$action = $isEdit ? '/tipsters/' . (int)$t['id'] . '/edit' : '/tipsters/add';
?>
<div class="grid grid-main-side" style="align-items:start">
    <form method="POST" action="<?php echo $action; ?>" class="card">
        <?php echo csrfField(); ?>
        <section class="form-section">
            <div class="form-grid">
                <div class="field col-6">
                    <label for="name">Name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" required maxlength="100" value="<?php echo e(plainText($t['name'] ?? '')); ?>" placeholder="e.g. Tennis Insider" <?php echo $isEdit ? '' : 'autofocus'; ?>>
                </div>
                <div class="field col-6">
                    <label for="source_url">Where their tips come from</label>
                    <input type="text" id="source_url" name="source_url" maxlength="255" value="<?php echo e(plainText($t['source_url'] ?? '')); ?>" placeholder="Website, Telegram channel, @handle">
                </div>
                <div class="field col-12">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="What they specialise in, how they size stakes, subscription cost"><?php echo e(plainText($t['notes'] ?? '')); ?></textarea>
                </div>
                <?php if ($isEdit): ?>
                <div class="col-12">
                    <label class="switch">
                        <span class="text">
                            <strong>Active</strong>
                            <span>Turn off to hide them from the bet form. Their past bets still count.</span>
                        </span>
                        <input type="checkbox" name="is_active" value="1" <?php echo !empty($t['is_active']) ? 'checked' : ''; ?>>
                    </label>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <div class="card-footer">
            <?php if ($isEdit): ?>
            <button type="submit" form="deleteTipsterForm" class="btn btn-danger"><?php echo icon('trash-2', 'icon-sm'); ?> Delete</button>
            <?php else: ?>
            <span class="text-muted desktop-only" style="font-size:12.5px"><span class="req text-accent">*</span> Required</span>
            <?php endif; ?>
            <div class="form-actions">
                <a href="/tipsters" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary"><?php echo icon('check', 'icon-sm'); ?> <?php echo $isEdit ? 'Save changes' : 'Add tipster'; ?></button>
            </div>
        </div>
    </form>

    <aside class="stack">
        <?php if ($isEdit): ?>
        <?php $settled = (int)($stats['total_bets'] ?? 0); $pl = (float)($stats['profit_loss'] ?? 0); $staked = (float)($stats['total_staked'] ?? 0); ?>
        <section class="card">
            <div class="card-header"><h2>Track record</h2></div>
            <div class="card-body" style="padding-top:6px">
                <?php if ($settled): ?>
                <dl class="dl">
                    <dt>Tips settled</dt>
                    <dd class="num"><?php echo $settled; ?></dd>
                    <dt>Won / lost</dt>
                    <dd class="num"><?php echo (int)$stats['won_bets']; ?> / <?php echo (int)$stats['lost_bets']; ?></dd>
                    <dt>Staked</dt>
                    <dd class="num"><?php echo formatCurrency($staked); ?></dd>
                    <dt>ROI</dt>
                    <dd class="num <?php echo toneClass($pl); ?>"><?php echo $staked > 0 ? (($pl > 0 ? '+' : '') . round($pl / $staked * 100, 1)) : 0; ?>%</dd>
                    <dt>Profit</dt>
                    <dd class="num <?php echo toneClass($pl); ?>" style="font-weight:620"><?php echo formatSigned($pl); ?></dd>
                </dl>
                <?php else: ?>
                <p class="text-muted" style="font-size:13px;padding-top:8px">No settled bets linked yet. Pick this tipster on the bet form when you follow one of their tips.</p>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="/bets?tipster_id=<?php echo (int)$t['id']; ?>" class="card-link">See their bets <?php echo icon('chevron-right', 'icon-xs'); ?></a>
            </div>
        </section>
        <?php else: ?>
        <section class="card callout">
            <div class="card-body">
                <div class="callout-icon"><?php echo icon('user-round-check'); ?></div>
                <h3>Keep them honest</h3>
                <p>When you place a bet on someone's advice, tick them on the bet form. Their record here is built only from your real stakes and results, not the numbers they advertise.</p>
            </div>
        </section>
        <?php endif; ?>
    </aside>
</div>

<?php if ($isEdit): ?>
<form method="POST" action="/tipsters/<?php echo (int)$t['id']; ?>/delete" id="deleteTipsterForm" hidden data-confirm="They come off every bet they are linked to. The bets themselves stay." data-confirm-title="Delete this tipster?" data-confirm-button="Delete tipster">
    <?php echo csrfField(); ?>
</form>
<?php endif; ?>
