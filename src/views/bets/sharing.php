<?php
/*
 * Shared stake card on the bet page. Uses $bet, $slip (the owner's bet), $shares, $partnerShare and $oldShare.
 */
$isPartnerCopy = (bool)$partnerShare;
$slipPending = $slip['status'] === BET_STATUS_PENDING;
$slipStake = (float)$slip['stake'];
$partnersStake = array_sum(array_map('floatval', array_column($shares, 'stake')));
$ownerStake = round($slipStake - $partnersStake, 2);
$slipReturn = $slipPending
    ? max(0, (float)$slip['potential_return'] - (float)($slip['tax_amount'] ?? 0))
    : (float)($slip['actual_return'] ?? 0);
$currentUserId = (int)getCurrentUserId();

$people = [[
    'name' => $isPartnerCopy ? $partnerShare['owner_name'] : 'You',
    'sub' => 'Placed the bet',
    'stake' => $ownerStake,
    'status' => 'owner',
    'is_me' => !$isPartnerCopy,
    'share' => null,
]];
foreach ($shares as $share) {
    $people[] = [
        'name' => $share['username'] ?: $share['invited_email'],
        'sub' => $share['username'] ? $share['invited_email'] : 'No account yet',
        'stake' => (float)$share['stake'],
        'status' => $share['status'],
        'is_me' => (int)$share['user_id'] === $currentUserId && $share['status'] === SharedBet::STATUS_ACCEPTED,
        'share' => $share,
    ];
}
?>
<section class="card" id="sharing">
    <div class="card-header">
        <h2>Shared stake</h2>
        <span class="hint">
            <?php if ($isPartnerCopy): ?>
            <?php echo e($partnerShare['owner_name']); ?> settles this bet
            <?php else: ?>
            <?php echo $slipPending ? 'Returns split by stake' : 'Split by stake'; ?>
            <?php endif; ?>
        </span>
    </div>

    <?php if (!$shares && !$isPartnerCopy): ?>
    <div class="card-body">
        <p class="text-2" style="font-size:13px">Betting with friends? Invite them by email with the part of the stake they put in. When you settle, each person gets the same part of the return and their Shared bets balance moves with it.</p>
    </div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="table share-table">
            <thead>
                <tr>
                    <th>Who</th>
                    <th class="num">Stake</th>
                    <th class="num share-pct">Share</th>
                    <th class="num"><?php echo $slipPending ? 'To return' : 'Returned'; ?></th>
                    <th class="num"><?php echo $slipPending ? '' : 'P&amp;L'; ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($people as $person):
                    $ratio = $slipStake > 0 ? $person['stake'] / $slipStake : 0;
                    $personReturn = round($slipReturn * $ratio, 2);
                    $isOpenInvite = $person['status'] === SharedBet::STATUS_INVITED;
                ?>
                <tr class="<?php echo $person['is_me'] ? 'is-me' : ''; ?>">
                    <td>
                        <div class="cell-main">
                            <?php echo avatar($person['name'], 'sm round'); ?>
                            <span style="min-width:0">
                                <span class="cell-title"><?php echo e($person['name']); ?><?php if ($person['is_me'] && $isPartnerCopy): ?> <span class="text-muted">(you)</span><?php endif; ?></span>
                                <span class="cell-sub truncate">
                                    <?php if ($person['status'] === 'owner'): ?>
                                    <?php echo e($person['sub']); ?>
                                    <?php elseif ($isOpenInvite): ?>
                                    <span class="pill pill-pending"><span class="pill-dot"></span>Invited</span>
                                    <?php else: ?>
                                    <?php echo e($person['sub']); ?>
                                    <?php endif; ?>
                                </span>
                            </span>
                        </div>
                    </td>
                    <td class="num"><?php echo formatCurrency($person['stake']); ?></td>
                    <td class="num text-2 share-pct"><?php echo round($ratio * 100, 1); ?>%</td>
                    <td class="num <?php echo $isOpenInvite ? 'text-muted' : ''; ?>"><?php echo formatCurrency($personReturn); ?></td>
                    <td class="num">
                        <?php if (!$slipPending): ?>
                        <span class="<?php echo toneClass($personReturn - $person['stake']); ?>" style="font-weight:620"><?php echo formatSigned($personReturn - $person['stake']); ?></span>
                        <?php elseif (!$isPartnerCopy && $person['share']): ?>
                        <form method="POST" action="/shared/<?php echo (int)$person['share']['id']; ?>/remove" data-confirm="<?php echo e($person['name']); ?> is taken off this bet and their stake goes back to you." data-confirm-title="<?php echo $isOpenInvite ? 'Cancel this invitation?' : 'Remove ' . e($person['name']) . '?'; ?>" data-confirm-button="<?php echo $isOpenInvite ? 'Cancel invitation' : 'Remove'; ?>">
                            <?php echo csrfField(); ?>
                            <button type="submit" class="btn btn-ghost btn-icon btn-sm" title="<?php echo $isOpenInvite ? 'Cancel invitation' : 'Remove'; ?>" aria-label="Remove <?php echo e($person['name']); ?>"><?php echo icon('x', 'icon-sm'); ?></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!$isPartnerCopy && $slipPending): ?>
    <form method="POST" action="/bets/<?php echo (int)$bet['id']; ?>/share" class="card-footer share-form">
        <?php echo csrfField(); ?>
        <div class="field grow">
            <label for="share_email">Invite by email</label>
            <input type="email" id="share_email" name="email" required placeholder="friend@example.com" value="<?php echo e($oldShare['email'] ?? ''); ?>" autocomplete="off">
        </div>
        <div class="field share-stake">
            <label for="share_stake">Their stake</label>
            <div class="input-affix">
                <span class="affix"><?php echo e(CURRENCY_SYMBOLS[userPref('currency', DEFAULT_CURRENCY)] ?? '$'); ?></span>
                <input type="number" id="share_stake" name="share_stake" required min="0.01" max="<?php echo e(number_format(max(0.01, $ownerStake - 0.01), 2, '.', '')); ?>" step="0.01" inputmode="decimal" placeholder="<?php echo e(number_format($ownerStake / 2, 2, '.', '')); ?>" value="<?php echo e($oldShare['share_stake'] ?? ''); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?php echo icon('mail', 'icon-sm'); ?> Invite</button>
    </form>
    <?php elseif ($isPartnerCopy && $slipPending): ?>
    <div class="card-footer">
        <span class="text-2" style="font-size:13px">Changed your mind? You can leave until the bet settles.</span>
        <form method="POST" action="/shared/<?php echo (int)$partnerShare['id']; ?>/remove" data-confirm="Your part of the stake goes back to <?php echo e($partnerShare['owner_name']); ?> and the bet leaves your list." data-confirm-title="Leave this shared bet?" data-confirm-button="Leave bet">
            <?php echo csrfField(); ?>
            <button type="submit" class="btn btn-sm"><?php echo icon('log-out', 'icon-sm'); ?> Leave</button>
        </form>
    </div>
    <?php endif; ?>
</section>
