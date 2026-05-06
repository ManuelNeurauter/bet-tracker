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
                <select id="bookmaker_id" name="bookmaker_id">
                    <option value="">Select Bookmaker</option>
                    <?php foreach ($bookmakers as $bm): ?>
                    <option value="<?php echo $bm['id']; ?>"><?php echo sanitize($bm['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <small><a href="/bookmakers/add">+ Add new</a></small>
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
                    <option value="void">Void</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="each_way" name="each_way">
                    Each Way Bet
                </label>
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
                <label for="tipsters">Tipster(s)</label>
                <select id="tipsters" name="tipsters[]" multiple>
                    <?php foreach ($tipsters as $tipster): ?>
                    <option value="<?php echo $tipster['id']; ?>"><?php echo sanitize($tipster['name']); ?></option>
                    <?php endforeach; ?>
                </select>
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
    sportSelect.addEventListener('change', function() {
        const sportId = this.value;
        if (!sportId) {
            competitionSelect.innerHTML = '<option value="">Select Competition</option>';
            return;
        }
        
        // This would typically load from API
        competitionSelect.innerHTML = '<option value="">Select Competition</option>';
    });
});
</script>
