<?php
$profit = betProfit($bet);
$isPending = $bet['status'] === BET_STATUS_PENDING;
$taxAmount = (float)($bet['tax_amount'] ?? 0);
$netPotential = max(0, (float)$bet['potential_return'] - $taxAmount);
?>
<div class="page-header">
    <div>
        <div class="eyebrow"><?php echo icon('ticket', 'icon-xs'); ?> Bet #<?php echo (int)$bet['id']; ?></div>
        <h1><?php echo e($bet['event_name']); ?></h1>
        <p class="subtitle">
            <?php echo $bet['sport_name'] ? e($bet['sport_name']) : 'No sport'; ?><?php echo $bet['competition_name'] ? ' · ' . e($bet['competition_name']) : ''; ?>
            <?php if ($bet['event_date']): ?> · <?php echo formatUserDate($bet['event_date'], true); ?><?php endif; ?>
        </p>
    </div>
    <div class="actions">
        <a href="/bets" class="btn btn-ghost"><?php echo icon('arrow-left'); ?> All bets</a>
        <a href="/bets/<?php echo (int)$bet['id']; ?>/edit" class="btn keep"><?php echo icon('pencil'); ?> Edit</a>
    </div>
</div>

<div class="grid grid-main-side" style="align-items:start">
    <div class="stack">
        <section class="card ticket" data-status="<?php echo e($bet['status']); ?>">
            <div class="ticket-top">
                <div class="row-between wrap">
                    <div class="row wrap">
                        <?php echo getStatusBadge($bet['status']); ?>
                        <span class="badge"><?php echo e(betTypeLabel($bet['bet_type'])); ?></span>
                        <?php if (!empty($bet['each_way'])): ?><span class="badge">Each way</span><?php endif; ?>
                    </div>
                    <span class="odds-chip" title="Odds">@ <?php echo formatOdds($bet['odds']); ?></span>
                </div>
                <div class="ticket-event"><?php echo e($bet['selection']); ?></div>
                <div class="ticket-selection"><?php echo e($bet['event_name']); ?></div>
            </div>
            <div class="ticket-divider"></div>
            <div class="ticket-figures">
                <div class="figure">
                    <div class="label">Stake</div>
                    <div class="value"><?php echo formatCurrency($bet['stake']); ?></div>
                </div>
                <div class="figure">
                    <div class="label"><?php echo $isPending ? 'To return' : 'Returned'; ?></div>
                    <div class="value"><?php echo formatCurrency($isPending ? $netPotential : ($bet['actual_return'] ?? 0)); ?></div>
                </div>
                <div class="figure">
                    <div class="label"><?php echo $isPending ? 'Potential profit' : 'Profit / loss'; ?></div>
                    <div class="value <?php echo $isPending ? 'text-muted' : toneClass($profit); ?>">
                        <?php echo $isPending ? formatSigned($netPotential - (float)$bet['stake']) : formatSigned($profit ?? 0); ?>
                    </div>
                </div>
                <div class="figure">
                    <div class="label">Implied chance</div>
                    <div class="value"><?php echo (float)$bet['odds'] > 0 ? round(100 / (float)$bet['odds'], 1) : 0; ?>%</div>
                </div>
            </div>
            <?php if ($isPending): ?>
            <div class="card-footer">
                <span class="text-2" style="font-size:13px">Result in? Settle it and the bookmaker balance updates.</span>
                <div class="row wrap">
                    <?php include __DIR__ . '/../partials/settle-buttons.php'; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>

        <?php if ($bet['notes']): ?>
        <section class="card">
            <div class="card-header"><h2>Notes</h2></div>
            <div class="card-body"><p class="notes"><?php echo e($bet['notes']); ?></p></div>
        </section>
        <?php endif; ?>
    </div>

    <div class="stack">
        <section class="card">
            <div class="card-header"><h2>Details</h2></div>
            <div class="card-body" style="padding-top:6px">
                <dl class="dl">
                    <dt>Bookmaker</dt>
                    <dd><?php echo $bet['bookmaker_name'] ? e($bet['bookmaker_name']) : '<span class="text-muted">None</span>'; ?></dd>
                    <dt>Sport</dt>
                    <dd><?php echo $bet['sport_name'] ? e($bet['sport_name']) : '<span class="text-muted">None</span>'; ?></dd>
                    <?php if ($bet['competition_name']): ?>
                    <dt>Competition</dt>
                    <dd><?php echo e($bet['competition_name']); ?></dd>
                    <?php endif; ?>
                    <dt>Event starts</dt>
                    <dd><?php echo $bet['event_date'] ? formatUserDate($bet['event_date'], true) : '<span class="text-muted">Not set</span>'; ?></dd>
                    <dt>Payout at odds</dt>
                    <dd class="num"><?php echo formatCurrency($bet['potential_return']); ?></dd>
                    <dt>Tax</dt>
                    <dd class="num"><?php echo formatCurrency($taxAmount); ?></dd>
                    <?php if ($bet['status'] === BET_STATUS_CASHOUT && $bet['cashout_amount'] !== null): ?>
                    <dt>Cashed out for</dt>
                    <dd class="num"><?php echo formatCurrency($bet['cashout_amount']); ?></dd>
                    <?php endif; ?>
                    <dt>Logged</dt>
                    <dd><?php echo formatUserDate($bet['created_at'], true); ?></dd>
                    <?php if ($bet['settled_at']): ?>
                    <dt>Settled</dt>
                    <dd><?php echo formatUserDate($bet['settled_at'], true); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><h2>Tags &amp; tipsters</h2></div>
            <div class="card-body">
                <?php if ($tags || $tipsters): ?>
                <div class="chips">
                    <?php foreach ($tags as $tag): ?>
                    <a href="/bets?tag_id=<?php echo (int)$tag['id']; ?>" class="chip" style="--chip: <?php echo e($tag['color']); ?>"><?php echo e($tag['name']); ?></a>
                    <?php endforeach; ?>
                    <?php foreach ($tipsters as $tipster): ?>
                    <a href="/bets?tipster_id=<?php echo (int)$tipster['id']; ?>" class="chip no-dot" style="--chip: var(--cashout)"><?php echo icon('user', 'icon-xs'); ?><?php echo e($tipster['name']); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted" style="font-size:13px">None yet. <a href="/bets/<?php echo (int)$bet['id']; ?>/edit">Add tags or a tipster</a> to see this bet in the breakdowns.</p>
                <?php endif; ?>
            </div>
        </section>

        <form method="POST" action="/bets/<?php echo (int)$bet['id']; ?>/delete" data-confirm="This bet will be removed and its result taken off the bookmaker balance." data-confirm-title="Delete this bet?" data-confirm-button="Delete bet">
            <?php echo csrfField(); ?>
            <button type="submit" class="btn btn-danger btn-block"><?php echo icon('trash-2', 'icon-sm'); ?> Delete bet</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../partials/cashout-dialog.php'; ?>
