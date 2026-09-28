<?php
$now = new DateTime('now');
$oddsExamples = [
    ODDS_FORMAT_DECIMAL => ['Decimal', '2.50', 'Used across Europe and Australia'],
    ODDS_FORMAT_FRACTIONAL => ['Fractional', '6/4', 'Traditional in the UK and Ireland'],
    ODDS_FORMAT_AMERICAN => ['American', '+150', 'Moneyline odds, common in the US'],
];
$dateFormats = ['Y-m-d' => 'ISO', 'd/m/Y' => 'Day first', 'm/d/Y' => 'Month first'];
$currencyNames = ['USD' => 'US dollar', 'EUR' => 'Euro', 'GBP' => 'British pound', 'AUD' => 'Australian dollar', 'CAD' => 'Canadian dollar'];
?>
<div class="page-header">
    <div>
        <h1>Settings</h1>
        <p class="subtitle">Your account and how numbers and dates are shown.</p>
    </div>
</div>

<div class="settings-layout">
    <nav class="settings-nav desktop-only" aria-label="Settings sections">
        <a href="#profile" class="nav-link"><?php echo icon('user'); ?> Profile</a>
        <a href="#preferences" class="nav-link"><?php echo icon('sliders-horizontal'); ?> Preferences</a>
        <a href="#appearance" class="nav-link"><?php echo icon('palette'); ?> Appearance</a>
        <a href="#password" class="nav-link"><?php echo icon('lock'); ?> Password</a>
    </nav>

    <div class="stack" style="gap:20px">
        <section class="card settings-section" id="profile">
            <div class="card-header">
                <div><h2>Profile</h2><div class="hint">How you sign in</div></div>
            </div>
            <form method="POST" action="/settings">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update-profile">
                <div class="card-body">
                    <div class="profile-head mb-2">
                        <?php echo avatar($userData['username'], 'xl round'); ?>
                        <div>
                            <div style="font-size:17px;font-weight:620;letter-spacing:-0.02em"><?php echo e($userData['username']); ?></div>
                            <div class="text-muted" style="font-size:13px">Member since <?php echo formatUserDate($userData['created_at']); ?> · <?php echo (int)$betCount; ?> <?php echo (int)$betCount === 1 ? 'bet' : 'bets'; ?> logged</div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="field col-6">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required minlength="3" maxlength="50" autocomplete="username" value="<?php echo e(plainText($userData['username'])); ?>">
                        </div>
                        <div class="field col-6">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required maxlength="100" autocomplete="email" value="<?php echo e(plainText($userData['email'])); ?>">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <span class="text-muted" style="font-size:12.5px">You can sign in with either one.</span>
                    <button type="submit" class="btn btn-primary"><?php echo icon('check', 'icon-sm'); ?> Save profile</button>
                </div>
            </form>
        </section>

        <section class="card settings-section" id="preferences">
            <div class="card-header">
                <div><h2>Preferences</h2><div class="hint">Applied everywhere in the app</div></div>
            </div>
            <form method="POST" action="/settings">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update-preferences">
                <div class="card-body stack" style="gap:22px">
                    <div class="form-grid">
                        <div class="field col-6">
                            <label for="currency">Currency</label>
                            <select id="currency" name="currency">
                                <?php foreach (CURRENCY_SYMBOLS as $code => $symbol): ?>
                                <option value="<?php echo e($code); ?>" <?php echo $userData['currency'] === $code ? 'selected' : ''; ?>><?php echo e($symbol . '  ' . ($currencyNames[$code] ?? $code) . ' (' . $code . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="help">Only changes the symbol. Amounts are not converted.</span>
                        </div>
                        <div class="field col-6">
                            <label for="timezone">Time zone</label>
                            <select id="timezone" name="timezone">
                                <?php foreach (DateTimeZone::listIdentifiers() as $tz): ?>
                                <option value="<?php echo e($tz); ?>" <?php echo $userData['timezone'] === $tz ? 'selected' : ''; ?>><?php echo e(str_replace('_', ' ', $tz)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <fieldset class="field">
                        <legend class="field-label">Odds format</legend>
                        <div class="choice-grid">
                            <?php foreach ($oddsExamples as $value => [$label, $example, $hint]): ?>
                            <label class="choice">
                                <input type="radio" name="odds_format" value="<?php echo $value; ?>" <?php echo $userData['odds_format'] === $value ? 'checked' : ''; ?>>
                                <span class="choice-body">
                                    <span class="choice-value"><?php echo $example; ?></span>
                                    <span class="choice-title"><?php echo $label; ?></span>
                                    <span class="choice-hint"><?php echo $hint; ?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <fieldset class="field">
                        <legend class="field-label">Date format</legend>
                        <div class="choice-grid">
                            <?php foreach ($dateFormats as $format => $label): ?>
                            <label class="choice">
                                <input type="radio" name="date_format" value="<?php echo $format; ?>" <?php echo $userData['date_format'] === $format ? 'checked' : ''; ?>>
                                <span class="choice-body">
                                    <span class="choice-value"><?php echo $now->format($format); ?></span>
                                    <span class="choice-title"><?php echo $label; ?></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                </div>
                <div class="card-footer">
                    <span></span>
                    <button type="submit" class="btn btn-primary"><?php echo icon('check', 'icon-sm'); ?> Save preferences</button>
                </div>
            </form>
        </section>

        <section class="card settings-section" id="appearance">
            <div class="card-header">
                <div><h2>Appearance</h2><div class="hint">Saved on this device and applied straight away</div></div>
            </div>
            <div class="card-body">
                <div class="choice-grid two">
                    <label class="choice">
                        <input type="radio" name="theme" value="dark" data-theme-choice>
                        <span class="choice-body">
                            <span class="theme-preview dark" aria-hidden="true"><span></span><span></span><span></span></span>
                            <span class="choice-title"><?php echo icon('moon', 'icon-sm'); ?> Dark</span>
                        </span>
                    </label>
                    <label class="choice">
                        <input type="radio" name="theme" value="light" data-theme-choice>
                        <span class="choice-body">
                            <span class="theme-preview light" aria-hidden="true"><span></span><span></span><span></span></span>
                            <span class="choice-title"><?php echo icon('sun', 'icon-sm'); ?> Light</span>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        <section class="card settings-section" id="password">
            <div class="card-header">
                <div><h2>Password</h2><div class="hint">At least 6 characters. Longer is better.</div></div>
            </div>
            <form method="POST" action="/settings">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update-password">
                <input type="text" name="username" value="<?php echo e(plainText($userData['username'])); ?>" autocomplete="username" hidden>
                <div class="card-body">
                    <div class="form-grid">
                        <div class="field col-12">
                            <label for="current_password">Current password</label>
                            <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                        </div>
                        <div class="field col-6">
                            <label for="new_password">New password</label>
                            <div class="password-field">
                                <input type="password" id="new_password" name="new_password" required minlength="6" autocomplete="new-password" data-strength="pwStrength">
                                <button type="button" class="btn btn-ghost btn-icon btn-sm reveal" data-reveal aria-label="Show password"><?php echo icon('eye', 'icon-sm'); ?></button>
                            </div>
                            <div class="strength" id="pwStrength" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                        </div>
                        <div class="field col-6">
                            <label for="confirm_password">Repeat new password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" autocomplete="new-password">
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <span></span>
                    <button type="submit" class="btn btn-primary"><?php echo icon('lock', 'icon-sm'); ?> Change password</button>
                </div>
            </form>
        </section>
    </div>
</div>
