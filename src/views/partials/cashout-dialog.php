<dialog class="modal" id="cashoutDialog">
    <form>
        <div class="modal-head">
            <span class="modal-icon cashout"><?php echo icon('hand-coins'); ?></span>
            <div>
                <h2>Cash out bet</h2>
                <p><span data-cashout-event>this bet</span><span class="text-muted"> · stake <span data-cashout-stake></span></span></p>
            </div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm close" data-dialog-close aria-label="Close"><?php echo icon('x', 'icon-sm'); ?></button>
        </div>
        <div class="modal-body">
            <div class="field">
                <label for="cashoutAmount">Amount received</label>
                <div class="input-affix">
                    <span class="affix"><?php echo e(CURRENCY_SYMBOLS[userPref('currency', DEFAULT_CURRENCY)] ?? '$'); ?></span>
                    <input type="number" id="cashoutAmount" name="amount" step="0.01" min="0" inputmode="decimal" required placeholder="0.00">
                </div>
                <span class="help">What the bookmaker paid out, after any tax.</span>
            </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="btn" data-dialog-close>Cancel</button>
            <button type="submit" class="btn btn-primary"><?php echo icon('check', 'icon-sm'); ?> Confirm cashout</button>
        </div>
    </form>
</dialog>
