<?php
/**
 * Shared bet form for add and edit.
 * Expects $bet (array|null), $bookmakers, $sports, $tags, $tipsters, $selectedTags, $selectedTipsters, $old (array|null).
 */
$isEdit = !empty($bet);
$old = $old ?? null;
$val = function ($key, $default = '') use ($bet, $old) {
    if ($old !== null && array_key_exists($key, $old)) return is_string($old[$key]) ? $old[$key] : $default;
    if ($bet && array_key_exists($key, $bet) && $bet[$key] !== null) return plainText($bet[$key]);
    return $default;
};
$status = $val('status', BET_STATUS_PENDING);
if (!array_key_exists($status, selectableStatuses($isEdit ? $bet['status'] : null))) {
    $status = $isEdit ? $bet['status'] : BET_STATUS_PENDING;
}
$selectedTags = $old !== null ? array_map('intval', (array)($old['tags'] ?? [])) : ($selectedTags ?? []);
$selectedTipsters = $old !== null ? array_map('intval', (array)($old['tipsters'] ?? [])) : ($selectedTipsters ?? []);
$eventDate = $val('event_date');
$eventDate = $eventDate ? date('Y-m-d\TH:i', strtotime($eventDate)) : '';
$symbol = CURRENCY_SYMBOLS[userPref('currency', DEFAULT_CURRENCY)] ?? '$';
$taxRates = [];
$balances = [];
foreach ($bookmakers as $bm) {
    $taxRates[$bm['id']] = (float)$bm['tax_percentage'];
    $balances[$bm['id']] = (float)$bm['account_balance'] + (float)$bm['bonus_balance'];
}
$statusIcons = ['pending' => 'clock', 'won' => 'circle-check', 'lost' => 'circle-x', 'cashout' => 'hand-coins', 'void' => 'circle-slash'];
$action = $isEdit ? '/bets/' . (int)$bet['id'] . '/edit' : '/bets/add';
?>
<form method="POST" action="<?php echo $action; ?>" id="betForm" data-bet-form
      data-tax-rates="<?php echo e(json_encode($taxRates)); ?>"
      data-balances="<?php echo e(json_encode($balances)); ?>"
      data-tax-touched="<?php echo $isEdit ? '1' : '0'; ?>">
    <?php echo csrfField(); ?>

    <div class="grid grid-main-side" style="align-items:start">
        <div class="card">
            <section class="form-section">
                <div class="form-section-head">
                    <span class="step"><?php echo icon('trophy'); ?></span>
                    <div>
                        <h2>Event</h2>
                        <p>What you are betting on and when it starts.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="field col-12">
                        <label for="event_name">Event <span class="req">*</span></label>
                        <input type="text" id="event_name" name="event_name" required maxlength="255" value="<?php echo e($val('event_name')); ?>" placeholder="e.g. Arsenal vs Chelsea" autocomplete="off" <?php echo $isEdit ? '' : 'autofocus'; ?>>
                    </div>
                    <div class="field col-4">
                        <label for="sport_id">Sport</label>
                        <select id="sport_id" name="sport_id">
                            <option value="">No sport</option>
                            <?php foreach ($sports as $sport): ?>
                            <option value="<?php echo (int)$sport['id']; ?>" <?php echo (string)$val('sport_id') === (string)$sport['id'] ? 'selected' : ''; ?>><?php echo e($sport['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field col-4">
                        <label for="competition_id">Competition</label>
                        <select id="competition_id" name="competition_id" data-selected="<?php echo e($val('competition_id')); ?>">
                            <option value="">Pick a sport first</option>
                        </select>
                    </div>
                    <div class="field col-4">
                        <label for="event_date">Starts</label>
                        <input type="datetime-local" id="event_date" name="event_date" value="<?php echo e($eventDate); ?>">
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="step"><?php echo icon('ticket'); ?></span>
                    <div>
                        <h2>Your bet</h2>
                        <p>The selection, the odds you took and how much you staked.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="field col-12">
                        <label for="selection">Selection <span class="req">*</span></label>
                        <input type="text" id="selection" name="selection" required maxlength="500" value="<?php echo e($val('selection')); ?>" placeholder="e.g. Arsenal to win, Over 2.5 goals" autocomplete="off">
                    </div>
                    <div class="field col-4">
                        <label for="bet_type">Bet type</label>
                        <select id="bet_type" name="bet_type">
                            <?php foreach (betTypes() as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $val('bet_type', BET_TYPE_SINGLE) === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field col-4">
                        <label for="odds">Odds (decimal) <span class="req">*</span></label>
                        <input type="number" id="odds" name="odds" step="0.001" min="1.01" required inputmode="decimal" value="<?php echo $val('odds') !== '' ? e(rtrim(rtrim(inputNumber($val('odds'), 3), '0'), '.')) : ''; ?>" placeholder="2.10">
                    </div>
                    <div class="field col-4">
                        <label for="stake">Stake <span class="req">*</span></label>
                        <div class="input-affix">
                            <span class="affix"><?php echo e($symbol); ?></span>
                            <input type="number" id="stake" name="stake" step="0.01" min="0.01" required inputmode="decimal" value="<?php echo $val('stake') !== '' ? e(inputNumber($val('stake'))) : ''; ?>" placeholder="10.00">
                        </div>
                    </div>
                    <div class="col-12">
                        <span class="help text-muted" style="font-size:12.5px" data-odds-help>Decimal odds, e.g. 2.10</span>
                    </div>
                    <div class="col-12">
                        <label class="check">
                            <input type="checkbox" name="each_way" value="1" <?php echo $val('each_way') ? 'checked' : ''; ?>>
                            <span>Each-way bet <small>Half the stake on the win, half on the place.</small></span>
                        </label>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="step"><?php echo icon('landmark'); ?></span>
                    <div>
                        <h2>Bookmaker</h2>
                        <p>Settled results update this bookmaker's balance.</p>
                    </div>
                </div>
                <div class="form-grid">
                    <div class="field col-6">
                        <label for="bookmaker_id">Bookmaker</label>
                        <select id="bookmaker_id" name="bookmaker_id">
                            <option value="">No bookmaker</option>
                            <?php foreach ($bookmakers as $bm): ?>
                            <option value="<?php echo (int)$bm['id']; ?>" <?php echo (string)$val('bookmaker_id') === (string)$bm['id'] ? 'selected' : ''; ?>><?php echo e($bm['name']); ?><?php echo !empty($bm['is_archived']) ? ' (archived)' : ''; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="help" data-balance-hint></span>
                        <?php if (!$bookmakers): ?>
                        <a href="/bookmakers/add" class="card-link"><?php echo icon('plus', 'icon-xs'); ?> Add your first bookmaker</a>
                        <?php endif; ?>
                    </div>
                    <div class="field col-6">
                        <label for="tax_amount">Tax</label>
                        <div class="input-group">
                            <div class="input-affix">
                                <span class="affix"><?php echo e($symbol); ?></span>
                                <input type="number" id="tax_amount" name="tax_amount" step="0.01" min="0" inputmode="decimal" value="<?php echo e(inputNumber($val('tax_amount', 0))); ?>">
                            </div>
                            <button type="button" class="btn btn-icon" data-tax-reset title="Recalculate from the bookmaker's tax rate" aria-label="Recalculate tax"><?php echo icon('rotate-ccw', 'icon-sm'); ?></button>
                        </div>
                        <span class="help" data-tax-hint>Taken off the payout when the bet wins</span>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="step"><?php echo icon('flame'); ?></span>
                    <div>
                        <h2>Result</h2>
                        <p><?php echo ($isEdit && $bet['status'] !== BET_STATUS_PENDING) ? 'You can change the result, but a settled bet cannot go back to pending.' : 'Leave it pending and settle it later from the bet list.'; ?></p>
                    </div>
                </div>
                <?php $statusChoices = selectableStatuses($isEdit ? $bet['status'] : null); ?>
                <div class="status-picker" role="radiogroup" aria-label="Status" data-count="<?php echo count($statusChoices); ?>">
                    <?php foreach ($statusChoices as $key => $label): ?>
                    <label class="status-option <?php echo $key; ?>">
                        <input type="radio" name="status" value="<?php echo $key; ?>" <?php echo $status === $key ? 'checked' : ''; ?>>
                        <span><?php echo icon($statusIcons[$key]); ?><?php echo e($label); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="form-grid mt-2">
                    <div class="field col-6" data-show-for="won" <?php echo $status === 'won' ? '' : 'hidden'; ?>>
                        <label for="actual_return">Actual return <span class="text-muted" style="font-weight:450">(optional)</span></label>
                        <div class="input-affix">
                            <span class="affix"><?php echo e($symbol); ?></span>
                            <input type="number" id="actual_return" name="actual_return" step="0.01" min="0" inputmode="decimal" value="<?php echo $status === 'won' && $val('actual_return') !== '' ? e(inputNumber($val('actual_return'))) : ''; ?>" placeholder="Calculated from odds">
                        </div>
                        <span class="help">Only if the payout differed, e.g. a boost or dead heat.</span>
                    </div>
                    <div class="field col-6" data-show-for="cashout" <?php echo $status === 'cashout' ? '' : 'hidden'; ?>>
                        <label for="cashout_amount">Cashout amount <span class="req">*</span></label>
                        <div class="input-affix">
                            <span class="affix"><?php echo e($symbol); ?></span>
                            <input type="number" id="cashout_amount" name="cashout_amount" step="0.01" min="0" inputmode="decimal" value="<?php echo $val('cashout_amount') !== '' ? e(inputNumber($val('cashout_amount'))) : ''; ?>" placeholder="0.00">
                        </div>
                        <span class="help">What the bookmaker paid you.</span>
                    </div>
                </div>
            </section>

            <section class="form-section">
                <div class="form-section-head">
                    <span class="step"><?php echo icon('tag'); ?></span>
                    <div>
                        <h2>Organise</h2>
                        <p>Tags and tipsters power the breakdowns in Statistics.</p>
                    </div>
                </div>
                <div class="stack">
                    <div class="field">
                        <span class="field-label">Tags</span>
                        <?php if ($tags): ?>
                        <div class="toggle-chips">
                            <?php foreach ($tags as $tag): ?>
                            <label class="toggle-chip" style="--chip: <?php echo e($tag['color']); ?>">
                                <input type="checkbox" name="tags[]" value="<?php echo (int)$tag['id']; ?>" <?php echo in_array((int)$tag['id'], $selectedTags, true) ? 'checked' : ''; ?>>
                                <span><?php echo e($tag['name']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <span class="help">No tags yet. <a href="/tags/add">Create one</a> to group bets by strategy.</span>
                        <?php endif; ?>
                    </div>
                    <div class="field">
                        <span class="field-label">Tipsters</span>
                        <?php if (array_filter($tipsters, function ($t) use ($selectedTipsters) { return !empty($t['is_active']) || in_array((int)$t['id'], $selectedTipsters, true); })): ?>
                        <div class="toggle-chips">
                            <?php foreach ($tipsters as $tipster): if (empty($tipster['is_active']) && !in_array((int)$tipster['id'], $selectedTipsters, true)) continue; ?>
                            <label class="toggle-chip" style="--chip: var(--cashout)">
                                <input type="checkbox" name="tipsters[]" value="<?php echo (int)$tipster['id']; ?>" <?php echo in_array((int)$tipster['id'], $selectedTipsters, true) ? 'checked' : ''; ?>>
                                <span><?php echo e($tipster['name']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <span class="help">No tipsters yet. <a href="/tipsters/add">Add one</a> to track whose picks pay off.</span>
                        <?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Reasoning, team news, anything worth remembering"><?php echo e($val('notes')); ?></textarea>
                    </div>
                </div>
            </section>

            <div class="card-footer">
                <?php if ($isEdit): ?>
                <button type="submit" form="deleteBetForm" class="btn btn-danger"><?php echo icon('trash-2', 'icon-sm'); ?> Delete</button>
                <?php else: ?>
                <span class="text-muted desktop-only" style="font-size:12.5px"><span class="req text-accent">*</span> Required</span>
                <?php endif; ?>
                <div class="form-actions">
                    <a href="<?php echo $isEdit ? '/bets/' . (int)$bet['id'] : '/bets'; ?>" class="btn btn-ghost">Cancel</a>
                    <?php if (!$isEdit): ?>
                    <button type="submit" name="add_another" value="1" class="btn">Save &amp; add another</button>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?php echo icon('check', 'icon-sm'); ?> <?php echo $isEdit ? 'Save changes' : 'Save bet'; ?></button>
                </div>
            </div>
        </div>

        <aside class="sticky-side">
            <div class="card slip">
                <div class="slip-head">
                    <div class="eyebrow"><?php echo icon('ticket', 'icon-xs'); ?> Bet slip</div>
                    <div class="slip-event" data-slip="event">Your event</div>
                    <div class="slip-selection" data-slip="selection">Pick a selection</div>
                </div>
                <div class="slip-lines">
                    <div class="slip-line"><span>Odds</span><strong class="mono" data-slip="odds">—</strong></div>
                    <div class="slip-line"><span>Implied chance</span><strong data-slip="implied">—</strong></div>
                    <div class="slip-line"><span>Stake</span><strong data-slip="stake"><?php echo e($symbol); ?>0.00</strong></div>
                    <div class="slip-line"><span>Payout</span><strong data-slip="payout"><?php echo e($symbol); ?>0.00</strong></div>
                    <div class="slip-line"><span>Tax</span><strong data-slip="tax"><?php echo e($symbol); ?>0.00</strong></div>
                </div>
                <div class="slip-total">
                    <div>
                        <div class="label" data-slip="total-label">Potential return</div>
                        <div class="value" data-slip="total"><?php echo e($symbol); ?>0.00</div>
                    </div>
                    <div class="slip-profit text-muted" data-slip="profit"></div>
                </div>
            </div>
            <p class="row text-muted mt-1 desktop-only" style="font-size:12.5px;padding:0 4px;gap:6px">
                <?php echo icon('info', 'icon-xs'); ?> Tip: press <kbd>N</kbd> on any page to log a new bet.
            </p>
        </aside>
    </div>
</form>
<?php if ($isEdit): ?>
<form method="POST" action="/bets/<?php echo (int)$bet['id']; ?>/delete" id="deleteBetForm" data-confirm="This bet will be removed and its result taken off the bookmaker balance." data-confirm-title="Delete this bet?" data-confirm-button="Delete bet">
    <?php echo csrfField(); ?>
</form>
<?php endif; ?>
