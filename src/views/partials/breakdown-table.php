<?php
/**
 * Breakdown table. Expects $rows: list of ['label', 'bets', 'won', 'staked', 'profit', 'roi', 'win_rate'],
 * $labelHeader, and optionally $labelBadge (callable returning HTML before the label).
 */
$maxAbs = 0;
foreach ($rows as $r) {
    $maxAbs = max($maxAbs, abs((float)$r['profit']));
}
?>
<?php if ($rows): ?>
<div class="table-wrap breakdown">
    <table class="table responsive">
        <thead>
            <tr>
                <th><?php echo e($labelHeader); ?></th>
                <th class="num">Bets</th>
                <th class="num">Win rate</th>
                <th class="num col-staked">Staked</th>
                <th>Profit</th>
                <th class="num">ROI</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): $pl = (float)$r['profit']; ?>
            <tr>
                <td class="m-main" data-label="<?php echo e($labelHeader); ?>">
                    <div class="cell-main">
                        <?php echo isset($labelBadge) ? $labelBadge($r) : ''; ?>
                        <span class="cell-title" style="font-weight:560"><?php echo e($r['label']); ?></span>
                    </div>
                </td>
                <td class="num m-inline" data-label="Bets"><?php echo (int)$r['bets']; ?></td>
                <td class="num m-inline" data-label="Win rate"><?php echo $r['win_rate']; ?>%</td>
                <td class="num m-inline col-staked" data-label="Staked"><?php echo formatCurrency($r['staked']); ?></td>
                <td class="m-inline" data-label="Profit">
                    <div class="bar-cell">
                        <div class="bar <?php echo $pl >= 0 ? 'win' : 'loss'; ?>"><span style="width:<?php echo $maxAbs > 0 ? max(2, round(abs($pl) / $maxAbs * 100)) : 0; ?>%"></span></div>
                        <span class="num <?php echo toneClass($pl); ?>" style="font-weight:620;min-width:80px;text-align:right"><?php echo formatSigned($pl); ?></span>
                    </div>
                </td>
                <td class="num m-end <?php echo toneClass($r['roi']); ?>" data-label="ROI" style="font-weight:600"><?php $roi = round((float)$r['roi'], 1); echo ($roi > 0 ? '+' : '') . $roi; ?>%</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="empty sm">
    <div class="empty-icon"><?php echo icon('chart-column'); ?></div>
    <p>No settled bets to break down yet.</p>
</div>
<?php endif; ?>
<?php unset($labelBadge); ?>
