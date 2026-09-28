<?php
/**
 * Shared bookmaker form. Expects $bookmaker (array|null) and, when editing, $stats.
 */
$isEdit = !empty($bookmaker);
$bm = $bookmaker ?? [];
$symbol = CURRENCY_SYMBOLS[userPref('currency', DEFAULT_CURRENCY)] ?? '$';
$action = $isEdit ? '/bookmakers/' . (int)$bm['id'] . '/edit' : '/bookmakers/add';
?>
<div class="grid grid-main-side" style="align-items:start">
    <form method="POST" action="<?php echo $action; ?>" class="card">
        <?php echo csrfField(); ?>
        <section class="form-section">
            <div class="form-section-head">
                <span class="step"><?php echo icon('landmark'); ?></span>
                <div>
                    <h2>Account</h2>
                    <p>The name you know it by and a link to the site.</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="field col-6">
                    <label for="name">Name <span class="req">*</span></label>
                    <input type="text" id="name" name="name" required maxlength="100" value="<?php echo e(plainText($bm['name'] ?? '')); ?>" placeholder="e.g. bet365" <?php echo $isEdit ? '' : 'autofocus'; ?>>
                </div>
                <div class="field col-6">
                    <label for="url">Website</label>
                    <div class="input-affix">
                        <span class="affix"><?php echo icon('globe', 'icon-sm'); ?></span>
                        <input type="text" id="url" name="url" inputmode="url" maxlength="255" value="<?php echo e(plainText($bm['url'] ?? '')); ?>" placeholder="bet365.com">
                    </div>
                </div>
            </div>
        </section>

        <section class="form-section">
            <div class="form-section-head">
                <span class="step"><?php echo icon('wallet'); ?></span>
                <div>
                    <h2>Money</h2>
                    <p>What is in the account right now. Settled bets keep it up to date from here on.</p>
                </div>
            </div>
            <div class="form-grid">
                <div class="field col-4">
                    <label for="account_balance">Balance</label>
                    <div class="input-affix">
                        <span class="affix"><?php echo e($symbol); ?></span>
                        <input type="number" id="account_balance" name="account_balance" step="0.01" inputmode="decimal" value="<?php echo e(inputNumber($bm['account_balance'] ?? 0)); ?>">
                    </div>
                </div>
                <div class="field col-4">
                    <label for="bonus_balance">Bonus funds</label>
                    <div class="input-affix">
                        <span class="affix"><?php echo e($symbol); ?></span>
                        <input type="number" id="bonus_balance" name="bonus_balance" step="0.01" min="0" inputmode="decimal" value="<?php echo e(inputNumber($bm['bonus_balance'] ?? 0)); ?>">
                    </div>
                </div>
                <div class="field col-4">
                    <label for="tax_percentage">Tax on winnings</label>
                    <div class="input-affix end">
                        <input type="number" id="tax_percentage" name="tax_percentage" step="0.01" min="0" max="100" inputmode="decimal" value="<?php echo e(inputNumber($bm['tax_percentage'] ?? 0)); ?>">
                        <span class="affix affix-end">%</span>
                    </div>
                </div>
                <div class="field col-12">
                    <span class="help">The tax rate fills in automatically on new bets with this bookmaker. In Germany, for example, many bookmakers take 5%.</span>
                </div>
            </div>
        </section>

        <section class="form-section">
            <div class="form-section-head">
                <span class="step"><?php echo icon('pencil'); ?></span>
                <div>
                    <h2>Notes</h2>
                    <p>Limits, promotions, anything worth remembering.</p>
                </div>
            </div>
            <div class="field">
                <textarea id="notes" name="notes" rows="3" aria-label="Notes" placeholder="e.g. Stake limited on tennis since March"><?php echo e(plainText($bm['notes'] ?? '')); ?></textarea>
            </div>
            <?php if ($isEdit): ?>
            <label class="switch" style="margin-top:16px">
                <span class="text">
                    <strong>Archived</strong>
                    <span>Hide it from the bet form and the bankroll. Its bets stay in your stats.</span>
                </span>
                <input type="checkbox" name="is_archived" value="1" <?php echo !empty($bm['is_archived']) ? 'checked' : ''; ?>>
            </label>
            <?php endif; ?>
        </section>

        <div class="card-footer">
            <span class="text-muted desktop-only" style="font-size:12.5px"><span class="req text-accent">*</span> Required</span>
            <div class="form-actions">
                <a href="/bookmakers" class="btn btn-ghost">Cancel</a>
                <button type="submit" class="btn btn-primary"><?php echo icon('check', 'icon-sm'); ?> <?php echo $isEdit ? 'Save changes' : 'Add bookmaker'; ?></button>
            </div>
        </div>
    </form>

    <aside class="stack">
        <?php if ($isEdit): ?>
        <?php $settled = (int)($stats['total_bets'] ?? 0); $pl = (float)($stats['profit_loss'] ?? 0); ?>
        <section class="card">
            <div class="card-header"><h2>Results here</h2></div>
            <div class="card-body" style="padding-top:6px">
                <dl class="dl">
                    <dt>Settled bets</dt>
                    <dd class="num"><?php echo $settled; ?></dd>
                    <dt>Won / lost</dt>
                    <dd class="num"><?php echo (int)($stats['won_bets'] ?? 0); ?> / <?php echo (int)($stats['lost_bets'] ?? 0); ?></dd>
                    <dt>Staked</dt>
                    <dd class="num"><?php echo formatCurrency($stats['total_staked'] ?? 0); ?></dd>
                    <dt>Tax paid</dt>
                    <dd class="num"><?php echo formatCurrency($stats['tax_paid'] ?? 0); ?></dd>
                    <dt>Profit</dt>
                    <dd class="num <?php echo toneClass($pl); ?>" style="font-weight:620"><?php echo formatSigned($pl); ?></dd>
                </dl>
            </div>
            <div class="card-footer">
                <a href="/bets?bookmaker_id=<?php echo (int)$bm['id']; ?>" class="card-link">See its bets <?php echo icon('chevron-right', 'icon-xs'); ?></a>
            </div>
        </section>
        <form method="POST" action="/bookmakers/<?php echo (int)$bm['id']; ?>/delete" data-confirm="Its bets stay in your history without a bookmaker. This cannot be undone." data-confirm-title="Delete <?php echo e($bm['name']); ?>?" data-confirm-button="Delete bookmaker">
            <?php echo csrfField(); ?>
            <button type="submit" class="btn btn-danger btn-block"><?php echo icon('trash-2', 'icon-sm'); ?> Delete bookmaker</button>
        </form>
        <?php else: ?>
        <section class="card callout">
            <div class="card-body">
                <div class="callout-icon"><?php echo icon('info'); ?></div>
                <h3>How balances work</h3>
                <p>Enter what the account holds today. Each time a bet with this bookmaker settles, its profit or loss is added to the balance for you.</p>
                <p>Cash and bonus funds are tracked separately, and both count toward your bankroll.</p>
            </div>
        </section>
        <?php endif; ?>
    </aside>
</div>
