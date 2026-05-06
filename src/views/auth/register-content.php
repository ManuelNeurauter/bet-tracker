<div class="auth-container">
    <div class="auth-box">
        <h2>Create Account on <?php echo APP_NAME; ?></h2>
        
        <form method="POST" action="/register" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required minlength="3">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="password_confirm">Confirm Password</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            
            <div class="form-group">
                <label for="currency">Currency</label>
                <select id="currency" name="currency">
                    <?php foreach (CURRENCY_SYMBOLS as $code => $symbol): ?>
                    <option value="<?php echo $code; ?>"><?php echo $code; ?> (<?php echo $symbol; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="timezone">Timezone</label>
                <select id="timezone" name="timezone">
                    <?php $timezones = DateTimeZone::listIdentifiers();
                    foreach ($timezones as $tz): ?>
                    <option value="<?php echo $tz; ?>" <?php echo ($tz === 'UTC') ? 'selected' : ''; ?>>
                        <?php echo $tz; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="bankroll_start">Starting Bankroll</label>
                <input type="number" id="bankroll_start" name="bankroll_start" step="0.01" value="0">
            </div>
            
            <button type="submit" class="btn btn-primary">Create Account</button>
        </form>
        
        <p class="auth-footer">
            Already have an account? <a href="/login">Login</a>
        </p>
    </div>
</div>
