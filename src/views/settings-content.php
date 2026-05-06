<div class="settings-container">
    <h1>Settings</h1>
    
    <!-- Preferences -->
    <div class="settings-section">
        <h2>Preferences</h2>
        
        <form method="POST" action="/settings" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency">
                        <?php foreach (CURRENCY_SYMBOLS as $code => $symbol): ?>
                        <option value="<?php echo $code; ?>" <?php echo ($userData['currency'] === $code) ? 'selected' : ''; ?>>
                            <?php echo $code; ?> (<?php echo $symbol; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="timezone">Timezone</label>
                    <select id="timezone" name="timezone">
                        <?php $timezones = DateTimeZone::listIdentifiers();
                        foreach ($timezones as $tz): ?>
                        <option value="<?php echo $tz; ?>" <?php echo ($userData['timezone'] === $tz) ? 'selected' : ''; ?>>
                            <?php echo $tz; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="odds_format">Odds Format</label>
                    <select id="odds_format" name="odds_format">
                        <option value="decimal" <?php echo ($userData['odds_format'] === 'decimal') ? 'selected' : ''; ?>>Decimal (1.50)</option>
                        <option value="fractional" <?php echo ($userData['odds_format'] === 'fractional') ? 'selected' : ''; ?>>Fractional (1/2)</option>
                        <option value="american" <?php echo ($userData['odds_format'] === 'american') ? 'selected' : ''; ?>>American (-200)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_format">Date Format</label>
                    <select id="date_format" name="date_format">
                        <option value="Y-m-d" <?php echo ($userData['date_format'] === 'Y-m-d') ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                        <option value="d/m/Y" <?php echo ($userData['date_format'] === 'd/m/Y') ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                        <option value="m/d/Y" <?php echo ($userData['date_format'] === 'm/d/Y') ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Preferences</button>
        </form>
    </div>
    
    <!-- Change Password -->
    <div class="settings-section">
        <h2>Change Password</h2>
        
        <form method="POST" action="/settings" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="update-password">
            
            <div class="form-group">
                <label for="current_password">Current Password *</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password *</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            
            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>
    
    <!-- Account Info -->
    <div class="settings-section">
        <h2>Account Information</h2>
        
        <table class="info-table">
            <tr>
                <td><strong>Username:</strong></td>
                <td><?php echo sanitize($userData['username']); ?></td>
            </tr>
            <tr>
                <td><strong>Email:</strong></td>
                <td><?php echo sanitize($userData['email']); ?></td>
            </tr>
            <tr>
                <td><strong>Member Since:</strong></td>
                <td><?php echo formatDate($userData['created_at']); ?></td>
            </tr>
        </table>
    </div>
</div>
