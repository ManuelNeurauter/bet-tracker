<div class="bet-form-container">
    <h1>Edit Bet</h1>
    
    <form method="POST" action="/bets/<?php echo $bet['id']; ?>/edit" class="bet-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-row">
            <div class="form-group full">
                <label for="event_name">Event Name *</label>
                <input type="text" id="event_name" name="event_name" value="<?php echo sanitize($bet['event_name']); ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="sport_id">Sport</label>
                <select id="sport_id" name="sport_id">
                    <option value="">Select Sport</option>
                    <?php foreach ($sports as $sport): ?>
                    <option value="<?php echo $sport['id']; ?>" <?php echo ($bet['sport_id'] == $sport['id']) ? 'selected' : ''; ?>>
                        <?php echo sanitize($sport['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="bookmaker_id">Bookmaker</label>
                <select id="bookmaker_id" name="bookmaker_id">
                    <option value="">Select Bookmaker</option>
                    <?php foreach ($bookmakers as $bm): ?>
                    <option value="<?php echo $bm['id']; ?>" <?php echo ($bet['bookmaker_id'] == $bm['id']) ? 'selected' : ''; ?>>
                        <?php echo sanitize($bm['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="event_date">Event Date/Time</label>
                <input type="datetime-local" id="event_date" name="event_date" value="<?php echo $bet['event_date'] ? date('Y-m-d\TH:i', strtotime($bet['event_date'])) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="bet_type">Bet Type *</label>
                <select id="bet_type" name="bet_type" required>
                    <option value="single" <?php echo ($bet['bet_type'] === 'single') ? 'selected' : ''; ?>>Single</option>
                    <option value="double" <?php echo ($bet['bet_type'] === 'double') ? 'selected' : ''; ?>>Double</option>
                    <option value="treble" <?php echo ($bet['bet_type'] === 'treble') ? 'selected' : ''; ?>>Treble</option>
                    <option value="accumulator" <?php echo ($bet['bet_type'] === 'accumulator') ? 'selected' : ''; ?>>Accumulator</option>
                    <option value="lucky_15" <?php echo ($bet['bet_type'] === 'lucky_15') ? 'selected' : ''; ?>>Lucky 15</option>
                    <option value="lucky_31" <?php echo ($bet['bet_type'] === 'lucky_31') ? 'selected' : ''; ?>>Lucky 31</option>
                    <option value="lucky_63" <?php echo ($bet['bet_type'] === 'lucky_63') ? 'selected' : ''; ?>>Lucky 63</option>
                    <option value="eachway" <?php echo ($bet['bet_type'] === 'eachway') ? 'selected' : ''; ?>>Each Way</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group full">
                <label for="selection">Selection *</label>
                <textarea id="selection" name="selection" required rows="2"><?php echo sanitize($bet['selection']); ?></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="odds">Odds (Decimal) *</label>
                <input type="number" id="odds" name="odds" step="0.01" min="1" value="<?php echo number_format($bet['odds'], 3); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stake">Stake *</label>
                <input type="number" id="stake" name="stake" step="0.01" min="0" value="<?php echo number_format($bet['stake'], 2); ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <?php if ($bet['status'] === 'pending'): ?>
                    <option value="pending" selected>Pending</option>
                    <?php endif; ?>
                    <option value="won" <?php echo ($bet['status'] === 'won') ? 'selected' : ''; ?>>Won</option>
                    <option value="lost" <?php echo ($bet['status'] === 'lost') ? 'selected' : ''; ?>>Lost</option>
                    <option value="cashout" <?php echo ($bet['status'] === 'cashout') ? 'selected' : ''; ?>>Cashed Out</option>
                </select>
            </div>
            
            <?php if ($bet['status'] !== 'pending'): ?>
            <div class="form-group">
                <label for="actual_return">Actual Return</label>
                <input type="number" id="actual_return" name="actual_return" step="0.01" value="<?php echo $bet['actual_return'] ? number_format($bet['actual_return'], 2) : ''; ?>">
            </div>
            
            <?php if ($bet['status'] === 'cashout'): ?>
            <div class="form-group">
                <label for="cashout_amount">Cashout Amount</label>
                <input type="number" id="cashout_amount" name="cashout_amount" step="0.01" value="<?php echo $bet['cashout_amount'] ? number_format($bet['cashout_amount'], 2) : ''; ?>">
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="tax_amount">Tax Amount</label>
                <input type="number" id="tax_amount" name="tax_amount" step="0.01" value="<?php echo $bet['tax_amount'] ? number_format($bet['tax_amount'], 2) : 0; ?>">
            </div>
            <?php endif; ?>
        </div>
        
        <div class="form-row">
            <div class="form-group full">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3"><?php echo sanitize($bet['notes']); ?></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Update Bet</button>
            <a href="/bets/<?php echo $bet['id']; ?>" class="btn btn-secondary">Cancel</a>
            <form method="POST" action="/bets/<?php echo $bet['id']; ?>/delete" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this bet?')">Delete</button>
            </form>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const oddsInput = document.getElementById('odds');
    const stakeInput = document.getElementById('stake');
    const bookmakerId = document.getElementById('bookmaker_id');
    const taxAmountInput = document.getElementById('tax_amount');
    
    const bookmakerData = <?php echo json_encode(array_reduce($bookmakers, function($carry, $item) {
        $carry[$item['id']] = ['name' => $item['name'], 'tax_percentage' => $item['tax_percentage'] ?? 0];
        return $carry;
    }, [])); ?>;

    function updateTaxAmount() {
        const selected = bookmakerId.value;
        if (selected && bookmakerData && bookmakerData[selected]) {
            const taxPercentage = parseFloat(bookmakerData[selected].tax_percentage) || 0;
            const stake = parseFloat(stakeInput.value) || 0;
            const odds = parseFloat(oddsInput.value) || 1;
            // Tax is calculated on the payout (stake * odds), not just the stake
            const payout = stake * odds;
            const taxAmount = payout > 0 ? (payout * taxPercentage / 100).toFixed(2) : '0.00';
            taxAmountInput.value = taxAmount;
        } else {
            taxAmountInput.value = '0.00';
        }
    }

    bookmakerId.addEventListener('change', updateTaxAmount);
    stakeInput.addEventListener('change', updateTaxAmount);
    oddsInput.addEventListener('change', updateTaxAmount);
});
</script>
