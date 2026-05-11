<div class="bet-form-container">
    <h1>Add New Bet</h1>
    
    <form method="POST" action="/bets/add" class="bet-form">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
        <div class="form-row">
            <div class="form-group full">
                <label for="event_name">Event Name *</label>
                <input type="text" id="event_name" name="event_name" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="sport_id">Sport</label>
                <select id="sport_id" name="sport_id">
                    <option value="">Select Sport</option>
                    <?php foreach ($sports as $sport): ?>
                    <option value="<?php echo $sport['id']; ?>"><?php echo sanitize($sport['name']); ?></option>
                    <?php endforeach; ?>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="competition_id">Competition</label>
                <select id="competition_id" name="competition_id">
                    <option value="">Select Competition</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="bookmaker_id">Bookmaker</label>
                <div class="form-input-with-action">
                    <select id="bookmaker_id" name="bookmaker_id">
                        <option value="">Select Bookmaker</option>
                        <?php foreach ($bookmakers as $bm): ?>
                        <option value="<?php echo $bm['id']; ?>"><?php echo sanitize($bm['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a href="/bookmakers/add" class="btn btn-small btn-secondary">+ Add</a>
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="event_date">Event Date/Time</label>
                <input type="datetime-local" id="event_date" name="event_date">
            </div>
            
            <div class="form-group">
                <label for="bet_type">Bet Type *</label>
                <select id="bet_type" name="bet_type" required>
                    <option value="single">Single</option>
                    <option value="double">Double</option>
                    <option value="treble">Treble</option>
                    <option value="accumulator">Accumulator</option>
                    <option value="lucky_15">Lucky 15</option>
                    <option value="lucky_31">Lucky 31</option>
                    <option value="lucky_63">Lucky 63</option>
                    <option value="eachway">Each Way</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group full">
                <label for="selection">Selection *</label>
                <textarea id="selection" name="selection" required rows="2" placeholder="e.g., Manchester United to Win, Over 2.5 Goals"></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="odds">Odds (Decimal) *</label>
                <input type="number" id="odds" name="odds" step="0.01" min="1" required>
                <small id="odds-help"></small>
            </div>
            
            <div class="form-group">
                <label for="stake">Stake *</label>
                <input type="number" id="stake" name="stake" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label>Potential Return</label>
                <input type="number" id="potential_return" disabled readonly>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="pending" selected>Pending</option>
                    <option value="won">Won</option>
                    <option value="lost">Lost</option>
                    <option value="cashout">Cashed Out</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="actual_return">Actual Return</label>
                <input type="number" id="actual_return" name="actual_return" step="0.01">
            </div>
            
            <div class="form-group">
                <label for="cashout_amount">Cashout Amount</label>
                <input type="number" id="cashout_amount" name="cashout_amount" step="0.01">
            </div>
            
            <div class="form-group">
                <label for="tax_amount">Tax Amount</label>
                <input type="number" id="tax_amount" name="tax_amount" step="0.01" value="0">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group full">
                <label for="tags">Tags</label>
                <div class="tag-selector">
                    <?php foreach ($tags as $tag): ?>
                    <label class="tag-option">
                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>">
                        <span class="tag-label" style="background-color: <?php echo $tag['color']; ?>">
                            <?php echo sanitize($tag['name']); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group full">
                <label for="tipsters">Tipster(s) <small>(optional)</small></label>
                <div class="tag-selector">
                    <?php foreach ($tipsters as $tipster): ?>
                    <label class="tag-option">
                        <input type="checkbox" name="tipsters[]" value="<?php echo $tipster['id']; ?>">
                        <span class="tag-label" style="background-color: #6366f1;">
                            <?php echo sanitize($tipster['name']); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group full">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Add any notes about this bet..."></textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Bet</button>
            <a href="/bets" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const oddsInput = document.getElementById('odds');
    const stakeInput = document.getElementById('stake');
    const potentialReturnInput = document.getElementById('potential_return');
    const oddsHelpText = document.getElementById('odds-help');
    const sportSelect = document.getElementById('sport_id');
    const competitionSelect = document.getElementById('competition_id');
    
    // Calculate potential return
    function updatePotentialReturn() {
        const odds = parseFloat(oddsInput.value) || 1;
        const stake = parseFloat(stakeInput.value) || 0;
        const potentialReturn = (odds * stake).toFixed(2);
        potentialReturnInput.value = potentialReturn;
    }
    
    oddsInput.addEventListener('change', updatePotentialReturn);
    stakeInput.addEventListener('change', updatePotentialReturn);
    
    // Load competitions when sport changes
    // Mapping of common competitions per sport name (case-insensitive keys)
    const competitionMap = {
        'football': ['Premier League', 'Champions League', 'Europa League', 'FA Cup', 'EFL Cup'],
        'soccer': ['Premier League', 'Champions League', 'Europa League', 'FA Cup', 'EFL Cup'],
        'tennis': ['Wimbledon', 'US Open', 'French Open', 'Australian Open', 'ATP Tour'],
        'horse racing': ['Cheltenham', 'Aintree', 'Royal Ascot', 'Grand National'],
        'cricket': ['IPL', 'The Ashes', 'County Championship', 'T20 Blast'],
        'basketball': ['NBA', 'EuroLeague', 'EuroCup'],
        'boxing': ['World Title', 'Regional Title'],
        'mma': ['UFC', 'Bellator']
    };

    function populateCompetitionsForSport(sportName) {
        const defaultOption = '<option value="">Select Competition</option>';
        if (!sportName) {
            competitionSelect.innerHTML = defaultOption;
            return;
        }

        const key = sportName.trim().toLowerCase();
        let items = competitionMap[key] || [];
        // Always include 'Other' as last option
        const options = [defaultOption].concat(items.map(c => `<option value="${c}">${c}</option>`)).concat(['<option value="other">Other</option>']);
        competitionSelect.innerHTML = options.join('\n');
    }

    sportSelect.addEventListener('change', function() {
        const sportText = this.options[this.selectedIndex] ? this.options[this.selectedIndex].text : '';
        if (this.value === 'other') {
            // For 'Other' sport, just provide an 'Other' competition option
            competitionSelect.innerHTML = '<option value="">Select Competition</option><option value="other">Other</option>';
            return;
        }
        populateCompetitionsForSport(sportText);
    });

    // Initialize competitions on load if a sport is pre-selected
    (function initCompetitions() {
        const selectedIndex = sportSelect.selectedIndex;
        const sportText = selectedIndex > -1 ? (sportSelect.options[selectedIndex].text || '') : '';
        populateCompetitionsForSport(sportText);
    })();

    // Bookmaker tax auto-fill
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
    
    // Initialize tax amount on page load
    updateTaxAmount();
});
</script>
