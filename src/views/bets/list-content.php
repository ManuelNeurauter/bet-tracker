<?php
$statusLabels = betStatuses();
$totalAll = array_sum($statusCounts);
$advancedKeys = ['sport_id', 'bookmaker_id', 'bet_type', 'tag_id', 'tipster_id', 'date_from', 'date_to', 'min_odds', 'max_odds', 'min_stake', 'max_stake'];
$advancedActive = count(array_filter($advancedKeys, function ($k) use ($filters) { return $filters[$k] !== ''; }));

// Labels for the active filter chips
$lookup = function ($rows, $id, $field = 'name') {
    foreach ($rows as $row) {
        if ((string)$row['id'] === (string)$id) return plainText($row[$field]);
    }
    return $id;
};
$chips = [];
if ($filters['search'] !== '') $chips['search'] = 'Search: “' . $filters['search'] . '”';
if ($filters['sport_id'] !== '') $chips['sport_id'] = 'Sport: ' . $lookup($sports, $filters['sport_id']);
if ($filters['bookmaker_id'] !== '') $chips['bookmaker_id'] = 'Bookmaker: ' . $lookup($bookmakers, $filters['bookmaker_id']);
if ($filters['bet_type'] !== '') $chips['bet_type'] = 'Type: ' . betTypeLabel($filters['bet_type']);
if ($filters['tag_id'] !== '') $chips['tag_id'] = 'Tag: ' . $lookup($tags, $filters['tag_id']);
if ($filters['tipster_id'] !== '') $chips['tipster_id'] = 'Tipster: ' . $lookup($tipsters, $filters['tipster_id']);
if ($filters['date_from'] !== '') $chips['date_from'] = 'From ' . formatUserDate($filters['date_from']);
if ($filters['date_to'] !== '') $chips['date_to'] = 'Until ' . formatUserDate($filters['date_to']);
if ($filters['min_odds'] !== '') $chips['min_odds'] = 'Odds ≥ ' . $filters['min_odds'];
if ($filters['max_odds'] !== '') $chips['max_odds'] = 'Odds ≤ ' . $filters['max_odds'];
if ($filters['min_stake'] !== '') $chips['min_stake'] = 'Stake ≥ ' . $filters['min_stake'];
if ($filters['max_stake'] !== '') $chips['max_stake'] = 'Stake ≤ ' . $filters['max_stake'];

$sortLink = function ($key, $label, $class = '') use ($sort) {
    $isDesc = $sort === $key . '_desc';
    $isAsc = $sort === $key . '_asc';
    $next = $isDesc ? $key . '_asc' : $key . '_desc';
    $iconName = $isDesc ? 'chevron-down' : ($isAsc ? 'chevron-down' : 'arrow-up-down');
    $style = $isAsc ? ' style="transform:rotate(180deg)"' : '';
    return '<a href="' . e(urlWithQuery(['sort' => $next, 'page' => null])) . '" class="' . ($isDesc || $isAsc ? 'sorted ' : '') . $class . '">' . e($label)
        . '<svg class="icon icon-xs" aria-hidden="true"' . $style . '><use href="' . asset('assets/icons.svg') . '#' . $iconName . '"></use></svg></a>';
};
$exportQuery = http_build_query(array_filter($filters + ['sort' => $sort], function ($v) { return $v !== ''; }));
$firstRow = $totalBets ? ($page - 1) * ITEMS_PER_PAGE + 1 : 0;
$lastRow = min($totalBets, $page * ITEMS_PER_PAGE);
?>
<div class="page-header">
    <div>
        <h1>Bets</h1>
        <p class="subtitle">
            <?php echo number_format($totalBets); ?> bet<?php echo $totalBets === 1 ? '' : 's'; ?><?php echo ($chips || $filters['status']) ? ' match your filters' : ' in total'; ?>
            · <?php echo formatCurrency($totals['total_staked']); ?> staked
            · <span class="<?php echo toneClass($totals['profit_loss']); ?>" style="font-weight:600"><?php echo formatSigned($totals['profit_loss']); ?></span>
        </p>
    </div>
    <div class="actions">
        <a href="/bets/export<?php echo $exportQuery ? '?' . e($exportQuery) : ''; ?>" class="btn"><?php echo icon('download'); ?> Export CSV</a>
        <a href="/bets/add" class="btn btn-primary keep"><?php echo icon('plus'); ?> New bet</a>
    </div>
</div>

<div class="card">
    <nav class="tabs" aria-label="Filter by status">
        <a href="<?php echo e(urlWithQuery(['status' => null, 'page' => null])); ?>" class="tab <?php echo $filters['status'] === '' ? 'active' : ''; ?>">All <span class="count"><?php echo $totalAll; ?></span></a>
        <?php foreach ($statusLabels as $key => $label): ?>
        <a href="<?php echo e(urlWithQuery(['status' => $key, 'page' => null])); ?>" class="tab <?php echo $filters['status'] === $key ? 'active' : ''; ?>"><?php echo e($label); ?> <span class="count"><?php echo $statusCounts[$key]; ?></span></a>
        <?php endforeach; ?>
    </nav>

    <form method="GET" action="/bets" id="filterForm">
        <?php if ($filters['status'] !== ''): ?><input type="hidden" name="status" value="<?php echo e($filters['status']); ?>"><?php endif; ?>
        <div class="toolbar">
            <div class="search">
                <?php echo icon('search', 'icon-sm'); ?>
                <input type="search" name="search" value="<?php echo e($filters['search']); ?>" placeholder="Search event, selection or notes" aria-label="Search bets">
            </div>
            <button type="button" class="btn" data-toggle-filters aria-expanded="<?php echo $advancedActive ? 'true' : 'false'; ?>" aria-controls="filtersPanel">
                <?php echo icon('sliders-horizontal'); ?> Filters
                <?php if ($advancedActive): ?><span class="badge badge-accent"><?php echo $advancedActive; ?></span><?php endif; ?>
            </button>
            <span class="spacer"></span>
            <label class="sr-only" for="sortSelect">Sort by</label>
            <select name="sort" id="sortSelect" class="select" style="width:auto;height:36px" data-autosubmit>
                <?php foreach (['date_desc' => 'Newest first', 'date_asc' => 'Oldest first', 'pl_desc' => 'Best result', 'pl_asc' => 'Worst result', 'stake_desc' => 'Highest stake', 'odds_desc' => 'Highest odds', 'odds_asc' => 'Lowest odds', 'created_desc' => 'Recently added'] as $key => $label): ?>
                <option value="<?php echo $key; ?>" <?php echo $sort === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filters-panel <?php echo $advancedActive ? 'open' : ''; ?>" id="filtersPanel">
            <div class="filters-grid">
                <div class="field">
                    <label for="f_sport">Sport</label>
                    <select name="sport_id" id="f_sport">
                        <option value="">Any sport</option>
                        <?php foreach ($sports as $sport): ?>
                        <option value="<?php echo (int)$sport['id']; ?>" <?php echo (string)$filters['sport_id'] === (string)$sport['id'] ? 'selected' : ''; ?>><?php echo e($sport['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="f_bookmaker">Bookmaker</label>
                    <select name="bookmaker_id" id="f_bookmaker">
                        <option value="">Any bookmaker</option>
                        <?php foreach ($bookmakers as $bm): ?>
                        <option value="<?php echo (int)$bm['id']; ?>" <?php echo (string)$filters['bookmaker_id'] === (string)$bm['id'] ? 'selected' : ''; ?>><?php echo e($bm['name']); ?><?php echo $bm['is_archived'] ? ' (archived)' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="f_type">Bet type</label>
                    <select name="bet_type" id="f_type">
                        <option value="">Any type</option>
                        <?php foreach (betTypes() as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $filters['bet_type'] === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="f_tag">Tag</label>
                    <select name="tag_id" id="f_tag">
                        <option value="">Any tag</option>
                        <?php foreach ($tags as $tag): ?>
                        <option value="<?php echo (int)$tag['id']; ?>" <?php echo (string)$filters['tag_id'] === (string)$tag['id'] ? 'selected' : ''; ?>><?php echo e($tag['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="f_tipster">Tipster</label>
                    <select name="tipster_id" id="f_tipster">
                        <option value="">Any tipster</option>
                        <?php foreach ($tipsters as $tipster): ?>
                        <option value="<?php echo (int)$tipster['id']; ?>" <?php echo (string)$filters['tipster_id'] === (string)$tipster['id'] ? 'selected' : ''; ?>><?php echo e($tipster['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <span class="field-label">Event date</span>
                    <div class="range">
                        <input type="date" name="date_from" value="<?php echo e($filters['date_from']); ?>" aria-label="From date">
                        <span>–</span>
                        <input type="date" name="date_to" value="<?php echo e($filters['date_to']); ?>" aria-label="To date">
                    </div>
                </div>
                <div class="field">
                    <span class="field-label">Odds</span>
                    <div class="range">
                        <input type="number" name="min_odds" step="0.01" min="1" value="<?php echo e($filters['min_odds']); ?>" placeholder="Min" aria-label="Minimum odds">
                        <span>–</span>
                        <input type="number" name="max_odds" step="0.01" min="1" value="<?php echo e($filters['max_odds']); ?>" placeholder="Max" aria-label="Maximum odds">
                    </div>
                </div>
                <div class="field">
                    <span class="field-label">Stake</span>
                    <div class="range">
                        <input type="number" name="min_stake" step="0.01" min="0" value="<?php echo e($filters['min_stake']); ?>" placeholder="Min" aria-label="Minimum stake">
                        <span>–</span>
                        <input type="number" name="max_stake" step="0.01" min="0" value="<?php echo e($filters['max_stake']); ?>" placeholder="Max" aria-label="Maximum stake">
                    </div>
                </div>
            </div>
            <div class="form-actions mt-2">
                <button type="submit" class="btn btn-primary"><?php echo icon('filter', 'icon-sm'); ?> Apply filters</button>
                <a href="/bets<?php echo $filters['status'] ? '?status=' . e($filters['status']) : ''; ?>" class="btn btn-ghost">Reset</a>
            </div>
        </div>
    </form>

    <?php if ($chips): ?>
    <div class="active-filters">
        <span>Filtered by</span>
        <?php foreach ($chips as $key => $label): ?>
        <span class="filter-tag"><?php echo e($label); ?><a href="<?php echo e(urlWithQuery([$key => null, 'page' => null])); ?>" aria-label="Remove filter"><?php echo icon('x', 'icon-xs'); ?></a></span>
        <?php endforeach; ?>
        <a href="/bets<?php echo $filters['status'] ? '?status=' . e($filters['status']) : ''; ?>" class="card-link" style="margin-left:4px">Clear all</a>
    </div>
    <?php endif; ?>

    <?php if ($bets): ?>
    <div class="table-wrap">
        <table class="table responsive">
            <thead>
                <tr>
                    <th>Event</th>
                    <th><?php echo $sortLink('date', 'Date'); ?></th>
                    <th>Bookmaker</th>
                    <th class="num"><?php echo $sortLink('odds', 'Odds'); ?></th>
                    <th class="num"><?php echo $sortLink('stake', 'Stake'); ?></th>
                    <th class="num"><?php echo $sortLink('pl', 'P&L'); ?></th>
                    <th>Status</th>
                    <th class="text-right"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bets as $bet): $profit = betProfit($bet); $rowTags = $betTags[$bet['id']] ?? []; ?>
                <tr class="is-link" data-href="/bets/<?php echo (int)$bet['id']; ?>">
                    <td class="m-main" data-label="Event">
                        <div class="cell-main">
                            <?php echo sportBadge($bet['sport_name']); ?>
                            <div style="min-width:0">
                                <a href="/bets/<?php echo (int)$bet['id']; ?>" class="cell-title"><?php echo e($bet['event_name']); ?></a>
                                <span class="cell-sub">
                                    <?php echo e($bet['selection']); ?>
                                    <?php if ($bet['bet_type'] !== 'single'): ?> · <?php echo e(betTypeLabel($bet['bet_type'])); ?><?php endif; ?>
                                </span>
                                <?php if ($rowTags): ?>
                                <div class="chips" style="margin-top:6px">
                                    <?php foreach ($rowTags as $tag): ?>
                                    <span class="chip" style="--chip: <?php echo e($tag['color']); ?>;height:20px;font-size:11px"><?php echo e($tag['name']); ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="m-inline nowrap text-2" data-label="Date"><?php echo formatUserDate($bet['event_date'] ?: $bet['created_at']); ?></td>
                    <td class="m-hide text-2" data-label="Bookmaker"><?php echo $bet['bookmaker_name'] ? e($bet['bookmaker_name']) : '<span class="text-muted">—</span>'; ?></td>
                    <td class="num m-inline" data-label="Odds"><span class="mono"><?php echo formatOdds($bet['odds']); ?></span></td>
                    <td class="num m-inline" data-label="Stake"><?php echo formatCurrency($bet['stake']); ?></td>
                    <td class="num m-inline <?php echo $profit === null ? 'text-muted' : toneClass($profit); ?>" data-label="P&amp;L" style="font-weight:620">
                        <?php echo $profit === null ? '<span title="Potential return">→ ' . formatCurrency($bet['potential_return']) . '</span>' : formatSigned($profit); ?>
                    </td>
                    <td class="m-end" data-label="Status"><?php echo getStatusBadge($bet['status']); ?></td>
                    <td class="m-actions">
                        <div class="row-actions">
                            <?php if ($bet['status'] === BET_STATUS_PENDING): ?>
                            <button type="button" class="btn btn-win btn-icon btn-sm" data-settle="won" data-bet-id="<?php echo (int)$bet['id']; ?>" title="Mark as won" aria-label="Mark as won"><?php echo icon('check', 'icon-sm'); ?></button>
                            <button type="button" class="btn btn-loss btn-icon btn-sm" data-settle="lost" data-bet-id="<?php echo (int)$bet['id']; ?>" title="Mark as lost" aria-label="Mark as lost"><?php echo icon('x', 'icon-sm'); ?></button>
                            <button type="button" class="btn btn-cashout btn-icon btn-sm" data-settle="cashout" data-bet-id="<?php echo (int)$bet['id']; ?>" data-event="<?php echo e(plainText($bet['event_name'])); ?>" data-stake="<?php echo e($bet['stake']); ?>" title="Cash out" aria-label="Cash out"><?php echo icon('hand-coins', 'icon-sm'); ?></button>
                            <?php endif; ?>
                            <details class="dropdown">
                                <summary class="btn btn-ghost btn-icon btn-sm" aria-label="More actions"><?php echo icon('ellipsis', 'icon-sm'); ?></summary>
                                <div class="dropdown-menu">
                                    <a href="/bets/<?php echo (int)$bet['id']; ?>"><?php echo icon('eye', 'icon-sm'); ?> View</a>
                                    <a href="/bets/<?php echo (int)$bet['id']; ?>/edit"><?php echo icon('pencil', 'icon-sm'); ?> Edit</a>
                                    <hr>
                                    <form method="POST" action="/bets/<?php echo (int)$bet['id']; ?>/delete" data-confirm="“<?php echo e(plainText($bet['event_name'])); ?>” will be removed and its result taken off the bookmaker balance." data-confirm-title="Delete this bet?" data-confirm-button="Delete bet">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="redirect" value="<?php echo e($_SERVER['REQUEST_URI']); ?>">
                                        <button type="submit" class="danger"><?php echo icon('trash-2', 'icon-sm'); ?> Delete</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <span>Showing <strong class="text-2"><?php echo $firstRow; ?>–<?php echo $lastRow; ?></strong> of <?php echo number_format($totalBets); ?></span>
        <?php if ($totalPages > 1): ?>
        <div class="pages">
            <a class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo e(urlWithQuery(['page' => $page - 1])); ?>" aria-label="Previous page"><?php echo icon('chevron-left', 'icon-sm'); ?></a>
            <?php foreach (getPaginationPages($page, $totalPages) as $p): ?>
            <a class="page-link <?php echo $p === $page ? 'active' : ''; ?>" href="<?php echo e(urlWithQuery(['page' => $p])); ?>"><?php echo $p; ?></a>
            <?php endforeach; ?>
            <a class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo e(urlWithQuery(['page' => $page + 1])); ?>" aria-label="Next page"><?php echo icon('chevron-right', 'icon-sm'); ?></a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="empty">
        <div class="empty-icon"><?php echo icon($chips || $filters['status'] ? 'search' : 'receipt-text'); ?></div>
        <?php if ($chips || $filters['status']): ?>
        <h3>No bets match</h3>
        <p>Try removing a filter or searching for something else.</p>
        <a href="/bets" class="btn btn-sm"><?php echo icon('rotate-ccw', 'icon-sm'); ?> Clear filters</a>
        <?php else: ?>
        <h3>No bets yet</h3>
        <p>Log your first bet and it will show up here with its odds, stake and result.</p>
        <a href="/bets/add" class="btn btn-primary btn-sm"><?php echo icon('plus', 'icon-sm'); ?> New bet</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../partials/cashout-dialog.php'; ?>
