<?php
$bookkeeperBalance = $bookkeeper ? (float)$bookkeeper['account_balance'] : 0.0;
$nothingYet = !$invites && !$sharedByMe && !$sharedWithMe;
?>
<div class="page-header">
    <div>
        <h1>Shared bets</h1>
        <p class="subtitle">Bets you pay into together. Everyone's return follows their part of the stake.</p>
    </div>
    <div class="actions">
        <a href="/bets?status=pending" class="btn"><?php echo icon('user-plus'); ?> Share an open bet</a>
    </div>
</div>

<?php if ($nothingYet): ?>
<div class="card">
    <div class="empty">
        <div class="empty-icon"><?php echo icon('handshake'); ?></div>
        <h3>Nothing shared yet</h3>
        <p>Open one of your pending bets and invite people by email with the part of the stake they put in. Bets others share with you show up here to accept.</p>
        <a href="/bets?status=pending" class="btn btn-primary"><?php echo icon('receipt-text'); ?> Pick an open bet</a>
    </div>
</div>
<?php else: ?>

<div class="grid grid-main-side" style="align-items:start">
    <div class="stack">
        <?php if ($invites): ?>
        <section class="card" id="invitations">
            <div class="card-header">
                <h2>Invitations</h2>
                <span class="hint"><?php echo count($invites); ?> waiting</span>
            </div>
            <div class="list">
                <?php foreach ($invites as $invite):
                    $ratio = (float)$invite['total_stake'] > 0 ? (float)$invite['stake'] / (float)$invite['total_stake'] : 0;
                ?>
                <div class="list-item invite">
                    <?php echo avatar($invite['owner_name'], 'round'); ?>
                    <div class="grow">
                        <div class="cell-title"><?php echo e($invite['owner_name']); ?> asks you to join <?php echo e($invite['event_name']); ?></div>
                        <div class="cell-sub">
                            <?php echo e($invite['selection']); ?> <span class="mono">@<?php echo formatOdds($invite['odds']); ?></span>
                            <?php if ($invite['event_date']): ?> · <?php echo formatUserDate($invite['event_date'], true); ?><?php endif; ?>
                        </div>
                        <div class="invite-terms">
                            Your stake <strong><?php echo formatCurrency($invite['stake']); ?></strong> of <?php echo formatCurrency($invite['total_stake']); ?>
                            (<?php echo round($ratio * 100, 1); ?>%) · returns <strong><?php echo formatCurrency(calculatePotentialReturn((float)$invite['stake'], (float)$invite['odds'])); ?></strong> if it wins
                        </div>
                    </div>
                    <div class="row wrap invite-actions">
                        <form method="POST" action="/shared/<?php echo (int)$invite['id']; ?>/decline">
                            <?php echo csrfField(); ?>
                            <button type="submit" class="btn btn-sm">Decline</button>
                        </form>
                        <form method="POST" action="/shared/<?php echo (int)$invite['id']; ?>/accept">
                            <?php echo csrfField(); ?>
                            <button type="submit" class="btn btn-primary btn-sm"><?php echo icon('check', 'icon-sm'); ?> Accept</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($sharedByMe): ?>
        <section class="card">
            <div class="card-header">
                <h2>Bets you share</h2>
                <span class="hint">You settle these</span>
            </div>
            <div class="table-wrap">
                <table class="table responsive">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>With</th>
                            <th class="num">Your stake</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sharedByMe as $bet):
                            $partnersStake = array_sum(array_map('floatval', array_column($bet['shares'], 'stake')));
                        ?>
                        <tr class="is-link" data-href="/bets/<?php echo (int)$bet['id']; ?>#sharing">
                            <td class="m-main" data-label="Event">
                                <div class="cell-main">
                                    <?php echo sportBadge($bet['sport_name']); ?>
                                    <div style="min-width:0">
                                        <a href="/bets/<?php echo (int)$bet['id']; ?>#sharing" class="cell-title"><?php echo e($bet['event_name']); ?></a>
                                        <span class="cell-sub"><?php echo e($bet['selection']); ?> · <span class="mono">@<?php echo formatOdds($bet['odds']); ?></span></span>
                                    </div>
                                </div>
                            </td>
                            <td class="m-inline" data-label="With">
                                <div class="avatar-stack">
                                    <?php foreach ($bet['shares'] as $share): ?>
                                    <span title="<?php echo e(($share['username'] ?: $share['invited_email']) . ($share['status'] === SharedBet::STATUS_INVITED ? ' (invited)' : '')); ?>" class="<?php echo $share['status'] === SharedBet::STATUS_INVITED ? 'is-invited' : ''; ?>"><?php echo avatar($share['username'] ?: $share['invited_email'], 'sm round'); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="num m-inline" data-label="Your stake"><?php echo formatCurrency((float)$bet['stake'] - $partnersStake); ?> <span class="text-muted">of <?php echo formatCurrency($bet['stake']); ?></span></td>
                            <td class="m-end" data-label="Status"><?php echo getStatusBadge($bet['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($sharedWithMe): ?>
        <section class="card">
            <div class="card-header">
                <h2>Shared with you</h2>
                <span class="hint">Their owner settles these</span>
            </div>
            <div class="table-wrap">
                <table class="table responsive">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>From</th>
                            <th class="num">Your stake</th>
                            <th class="num">P&amp;L</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sharedWithMe as $bet): $profit = betProfit($bet); ?>
                        <tr class="is-link" data-href="/bets/<?php echo (int)$bet['id']; ?>">
                            <td class="m-main" data-label="Event">
                                <div class="cell-main">
                                    <?php echo sportBadge($bet['sport_name']); ?>
                                    <div style="min-width:0">
                                        <a href="/bets/<?php echo (int)$bet['id']; ?>" class="cell-title"><?php echo e($bet['event_name']); ?></a>
                                        <span class="cell-sub"><?php echo e($bet['selection']); ?> · <span class="mono">@<?php echo formatOdds($bet['odds']); ?></span></span>
                                    </div>
                                </div>
                            </td>
                            <td class="m-inline text-2" data-label="From"><?php echo e($bet['owner_name']); ?></td>
                            <td class="num m-inline" data-label="Your stake"><?php echo formatCurrency($bet['stake']); ?> <span class="text-muted">of <?php echo formatCurrency($bet['total_stake']); ?></span></td>
                            <td class="num m-inline <?php echo $profit === null ? 'text-muted' : toneClass($profit); ?>" data-label="P&amp;L" style="font-weight:620">
                                <?php echo $profit === null ? '→ ' . formatCurrency($bet['potential_return']) : formatSigned($profit); ?>
                            </td>
                            <td class="m-end" data-label="Status"><?php echo getStatusBadge($bet['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>
    </div>

    <div class="stack">
        <section class="card kpi">
            <div class="kpi-top">
                <span class="kpi-label">Shared bets balance</span>
                <span class="kpi-icon accent"><?php echo icon('handshake'); ?></span>
            </div>
            <div class="kpi-value <?php echo toneClass($bookkeeperBalance); ?>"><?php echo formatSigned($bookkeeperBalance); ?></div>
            <div class="kpi-meta">
                <?php if ($bookkeeperBalance > 0): ?>
                Your partners owe you this from settled bets.
                <?php elseif ($bookkeeperBalance < 0): ?>
                You owe your partners this from settled bets.
                <?php else: ?>
                Everyone is square.
                <?php endif; ?>
            </div>
        </section>

        <section class="card callout">
            <div class="card-body">
                <div class="callout-icon"><?php echo icon('info'); ?></div>
                <h3>How the split works</h3>
                <p>The person who placed the bet keeps it at their bookmaker and settles it. Everyone else gets their own copy with their part of the stake, booked on a Shared bets bookkeeper.</p>
                <p>When it settles, each copy gets the same part of the return. Your bookkeeper moves by your profit or loss, and the owner's moves the other way, so it shows who owes whom.</p>
            </div>
        </section>
    </div>
</div>
<?php endif; ?>
