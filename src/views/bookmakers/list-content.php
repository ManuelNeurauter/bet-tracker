<?php
$renderBookmaker = function ($bm) use ($bookkeeperId) {
    $isBookkeeper = (int)$bm['id'] === $bookkeeperId;
    $stats = $bm['stats'] ?? [];
    $totalBets = (int)($stats['total_bets'] ?? 0);
    $pl = (float)($stats['profit_loss'] ?? 0);
    $host = $bm['url'] ? preg_replace('#^www\.#', '', (string)parse_url($bm['url'], PHP_URL_HOST)) : '';
    ob_start(); ?>
    <article class="card entity <?php echo $bm['is_archived'] ? 'archived' : ''; ?>">
        <div class="entity-head">
            <?php echo avatar($bm['name'], 'lg'); ?>
            <div class="grow">
                <div class="entity-name"><?php echo e($bm['name']); ?></div>
                <div class="entity-sub">
                    <?php if ($isBookkeeper): ?>
                    <a href="/shared" class="inline-link">Settles your shared bets</a>
                    <?php elseif ($host): ?>
                    <a href="<?php echo e($bm['url']); ?>" target="_blank" rel="noopener noreferrer" class="inline-link"><?php echo e($host); ?><?php echo icon('external-link', 'icon-xs'); ?></a>
                    <?php else: ?>
                    No website saved
                    <?php endif; ?>
                </div>
            </div>
            <?php if ((float)$bm['tax_percentage'] > 0): ?>
            <span class="badge" title="Tax taken from winnings"><?php echo rtrim(rtrim(number_format((float)$bm['tax_percentage'], 2), '0'), '.'); ?>% tax</span>
            <?php endif; ?>
            <?php if ($isBookkeeper): ?><span class="badge badge-accent" title="What you and the people you bet with owe each other"><?php echo icon('handshake', 'icon-xs'); ?> Bookkeeper</span><?php endif; ?>
            <?php if ($bm['is_archived']): ?><span class="badge">Archived</span><?php endif; ?>
        </div>
        <div class="entity-balance">
            <div class="label">Balance</div>
            <div class="value"><?php echo formatCurrency($bm['account_balance']); ?></div>
            <?php if ((float)$bm['bonus_balance'] > 0): ?>
            <div class="entity-bonus"><?php echo icon('sparkles', 'icon-xs'); ?> <?php echo formatCurrency($bm['bonus_balance']); ?> bonus</div>
            <?php endif; ?>
        </div>
        <div class="entity-stats">
            <div>
                <div class="label">Settled</div>
                <div class="value"><?php echo $totalBets; ?></div>
            </div>
            <div>
                <div class="label">Win rate</div>
                <div class="value"><?php echo $totalBets ? round($bm['win_rate'], 1) . '%' : '—'; ?></div>
            </div>
            <div>
                <div class="label">Profit</div>
                <div class="value <?php echo $totalBets ? toneClass($pl) : 'text-muted'; ?>"><?php echo $totalBets ? formatSigned($pl) : '—'; ?></div>
            </div>
        </div>
        <div class="entity-foot">
            <a href="/bets?bookmaker_id=<?php echo (int)$bm['id']; ?>" class="btn btn-ghost btn-sm"><?php echo icon('receipt-text', 'icon-sm'); ?> Bets</a>
            <span class="spacer"></span>
            <a href="/bookmakers/<?php echo (int)$bm['id']; ?>/edit" class="btn btn-sm"><?php echo icon('pencil', 'icon-sm'); ?> Edit</a>
            <?php if (!$isBookkeeper): ?>
            <form method="POST" action="/bookmakers/<?php echo (int)$bm['id']; ?>/delete" data-confirm="Its bets stay in your history without a bookmaker. This cannot be undone." data-confirm-title="Delete <?php echo e($bm['name']); ?>?" data-confirm-button="Delete bookmaker">
                <?php echo csrfField(); ?>
                <button type="submit" class="btn btn-ghost btn-sm btn-icon" title="Delete" aria-label="Delete <?php echo e($bm['name']); ?>"><?php echo icon('trash-2', 'icon-sm'); ?></button>
            </form>
            <?php endif; ?>
        </div>
    </article>
    <?php return ob_get_clean();
};
?>
<div class="page-header">
    <div>
        <h1>Bookmakers</h1>
        <p class="subtitle">Your accounts and what is in them. Balances move as your bets settle.</p>
    </div>
    <div class="actions">
        <a href="/bookmakers/add" class="btn btn-primary"><?php echo icon('plus'); ?> Add bookmaker</a>
    </div>
</div>

<?php if (!$activeBookmakers && !$archivedBookmakers): ?>
<div class="card">
    <div class="empty">
        <div class="empty-icon"><?php echo icon('landmark'); ?></div>
        <h3>No bookmakers yet</h3>
        <p>Add the accounts you bet with. Each one keeps its own balance and tax rate, and the stats split your results by account.</p>
        <a href="/bookmakers/add" class="btn btn-primary"><?php echo icon('plus'); ?> Add your first bookmaker</a>
    </div>
</div>
<?php else: ?>

<div class="grid grid-3 mb-2">
    <div class="card kpi">
        <div class="kpi-top">
            <span class="kpi-label">Bankroll</span>
            <span class="kpi-icon accent"><?php echo icon('wallet'); ?></span>
        </div>
        <div class="kpi-value"><?php echo formatCurrency($totalBalance + $totalBonus); ?></div>
        <div class="kpi-meta">Across <?php echo count($activeBookmakers); ?> active <?php echo count($activeBookmakers) === 1 ? 'account' : 'accounts'; ?></div>
    </div>
    <div class="card kpi">
        <div class="kpi-top">
            <span class="kpi-label">Bonus funds</span>
            <span class="kpi-icon pending"><?php echo icon('sparkles'); ?></span>
        </div>
        <div class="kpi-value"><?php echo formatCurrency($totalBonus); ?></div>
        <div class="kpi-meta"><?php echo formatCurrency($totalBalance); ?> is cash</div>
    </div>
    <div class="card kpi">
        <div class="kpi-top">
            <span class="kpi-label">Profit from settled bets</span>
            <span class="kpi-icon <?php echo $totalProfit >= 0 ? 'win' : 'loss'; ?>"><?php echo icon($totalProfit >= 0 ? 'trending-up' : 'trending-down'); ?></span>
        </div>
        <div class="kpi-value <?php echo toneClass($totalProfit); ?>"><?php echo formatSigned($totalProfit); ?></div>
        <div class="kpi-meta"><?php echo $totalTax > 0 ? formatCurrency($totalTax) . ' paid in tax on winnings' : 'All bookmakers, archived included'; ?></div>
    </div>
</div>

<?php if ($activeBookmakers): ?>
<div class="entity-grid">
    <?php foreach ($activeBookmakers as $bm) echo $renderBookmaker($bm); ?>
    <a href="/bookmakers/add" class="entity-add">
        <span class="entity-add-icon"><?php echo icon('plus'); ?></span>
        <span>Add bookmaker</span>
    </a>
</div>
<?php else: ?>
<div class="card">
    <div class="empty sm">
        <div class="empty-icon"><?php echo icon('landmark'); ?></div>
        <p>All your bookmakers are archived.</p>
        <a href="/bookmakers/add" class="btn btn-primary btn-sm"><?php echo icon('plus', 'icon-sm'); ?> Add bookmaker</a>
    </div>
</div>
<?php endif; ?>

<?php if ($archivedBookmakers): ?>
<details class="section-toggle" <?php echo !$activeBookmakers ? 'open' : ''; ?>>
    <summary><?php echo icon('chevron-right', 'icon-sm'); ?> Archived <span class="count"><?php echo count($archivedBookmakers); ?></span></summary>
    <p class="text-muted" style="font-size:13px;margin:0 0 14px">Archived accounts are hidden from the bet form and the bankroll, but their bets still count in your stats.</p>
    <div class="entity-grid">
        <?php foreach ($archivedBookmakers as $bm) echo $renderBookmaker($bm); ?>
    </div>
</details>
<?php endif; ?>

<?php endif; ?>
