<div class="page-header">
    <div>
        <h1>Tags</h1>
        <p class="subtitle">Label bets your own way, like "value bet" or "live", then see which labels make money.</p>
    </div>
    <div class="actions">
        <a href="/tags/add" class="btn btn-primary"><?php echo icon('plus'); ?> New tag</a>
    </div>
</div>

<?php if (!$tags): ?>
<div class="card">
    <div class="empty">
        <div class="empty-icon"><?php echo icon('tag'); ?></div>
        <h3>No tags yet</h3>
        <p>Tags are your own labels. Add one to a bet when you log it and this page shows how each kind of bet performs.</p>
        <div class="chips" style="justify-content:center;margin:4px 0 6px">
            <span class="chip" style="--chip:#22c55e">Value bet</span>
            <span class="chip" style="--chip:#f59e0b">Live</span>
            <span class="chip" style="--chip:#8b7bff">Boosted odds</span>
            <span class="chip" style="--chip:#38bdf8">Gut feeling</span>
        </div>
        <a href="/tags/add" class="btn btn-primary"><?php echo icon('plus'); ?> Create your first tag</a>
    </div>
</div>
<?php else: ?>
<div class="entity-grid">
    <?php foreach ($tags as $tag):
        $st = $tagStats[$tag['id']] ?? [];
        $count = (int)($st['total_bets'] ?? 0);
        $settled = (int)($st['settled_bets'] ?? 0);
        $pl = (float)($st['profit_loss'] ?? 0);
        $staked = (float)($st['total_staked'] ?? 0);
        $roi = $staked > 0 ? round($pl / $staked * 100, 1) : null;
    ?>
    <article class="card entity" style="--chip: <?php echo e($tag['color']); ?>">
        <div class="entity-head">
            <span class="tag-swatch"><?php echo icon('tag', 'icon-sm'); ?></span>
            <div class="grow">
                <div class="entity-name"><?php echo e($tag['name']); ?></div>
                <div class="entity-sub"><?php echo $count; ?> <?php echo $count === 1 ? 'bet' : 'bets'; ?><?php echo $count > $settled ? ' · ' . ($count - $settled) . ' not settled' : ''; ?></div>
            </div>
        </div>
        <div class="entity-stats">
            <div>
                <div class="label">Win rate</div>
                <div class="value"><?php echo $settled ? round((int)$st['won_bets'] / $settled * 100, 1) . '%' : '—'; ?></div>
            </div>
            <div>
                <div class="label">ROI</div>
                <div class="value <?php echo $roi === null ? 'text-muted' : toneClass($roi); ?>"><?php echo $roi === null ? '—' : ($roi > 0 ? '+' : '') . $roi . '%'; ?></div>
            </div>
            <div>
                <div class="label">Profit</div>
                <div class="value <?php echo $settled ? toneClass($pl) : 'text-muted'; ?>"><?php echo $settled ? formatSigned($pl) : '—'; ?></div>
            </div>
        </div>
        <div class="entity-foot">
            <a href="/bets?tag_id=<?php echo (int)$tag['id']; ?>" class="btn btn-ghost btn-sm"><?php echo icon('receipt-text', 'icon-sm'); ?> Bets</a>
            <span class="spacer"></span>
            <a href="/tags/<?php echo (int)$tag['id']; ?>/edit" class="btn btn-sm"><?php echo icon('pencil', 'icon-sm'); ?> Edit</a>
            <form method="POST" action="/tags/<?php echo (int)$tag['id']; ?>/delete" data-confirm="The tag comes off every bet that has it. The bets themselves stay." data-confirm-title="Delete the <?php echo e($tag['name']); ?> tag?" data-confirm-button="Delete tag">
                <?php echo csrfField(); ?>
                <button type="submit" class="btn btn-ghost btn-sm btn-icon" title="Delete" aria-label="Delete <?php echo e($tag['name']); ?>"><?php echo icon('trash-2', 'icon-sm'); ?></button>
            </form>
        </div>
    </article>
    <?php endforeach; ?>
    <a href="/tags/add" class="entity-add" style="min-height:170px">
        <span class="entity-add-icon"><?php echo icon('plus'); ?></span>
        <span>New tag</span>
    </a>
</div>
<?php endif; ?>
