<?php
$old = $_SESSION['old_register'] ?? [];
unset($_SESSION['old_register']);
$oldCurrency = $old['currency'] ?? 'EUR';
$oldTimezone = $old['timezone'] ?? '';
?>
<div class="auth">
    <?php include __DIR__ . '/showcase.php'; ?>

    <main class="auth-panel">
        <?php include __DIR__ . '/panel-top.php'; ?>
        <div class="auth-form-wrap">
            <form method="POST" action="/register" class="auth-form wide">
                <h1>Create your account</h1>
                <p class="lead">Takes a minute. You can change everything later in Settings.</p>
                <?php echo csrfField(); ?>
                <div class="form-grid">
                    <div class="field col-6">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required minlength="3" maxlength="50" autocomplete="username" autocapitalize="off" spellcheck="false" value="<?php echo e($old['username'] ?? ''); ?>" autofocus>
                    </div>
                    <div class="field col-6">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required maxlength="100" autocomplete="email" value="<?php echo e($old['email'] ?? ''); ?>">
                    </div>
                    <div class="field col-6">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password" data-strength="pwStrength">
                            <button type="button" class="btn btn-ghost btn-icon btn-sm reveal" data-reveal aria-label="Show password"><?php echo icon('eye', 'icon-sm'); ?></button>
                        </div>
                        <div class="strength" id="pwStrength" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                    </div>
                    <div class="field col-6">
                        <label for="password_confirm">Repeat password</label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="field col-6">
                        <label for="currency">Currency</label>
                        <select id="currency" name="currency">
                            <?php foreach (CURRENCY_SYMBOLS as $code => $symbol): ?>
                            <option value="<?php echo e($code); ?>" <?php echo $oldCurrency === $code ? 'selected' : ''; ?>><?php echo e($symbol . '  ' . $code); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field col-6">
                        <label for="timezone">Time zone</label>
                        <select id="timezone" name="timezone" <?php echo $oldTimezone === '' ? 'data-detect-timezone' : ''; ?>>
                            <?php foreach (DateTimeZone::listIdentifiers() as $tz): ?>
                            <option value="<?php echo e($tz); ?>" <?php echo ($oldTimezone === '' ? 'UTC' : $oldTimezone) === $tz ? 'selected' : ''; ?>><?php echo e(str_replace('_', ' ', $tz)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block" style="margin-top:22px">Create account <?php echo icon('arrow-up-right', 'icon-sm'); ?></button>
                <p class="auth-switch">Already have an account? <a href="/login">Sign in</a></p>
            </form>
        </div>
    </main>
</div>
