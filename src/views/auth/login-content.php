<?php
$oldLogin = $_SESSION['old_login'] ?? '';
unset($_SESSION['old_login']);
?>
<div class="auth">
    <?php include __DIR__ . '/showcase.php'; ?>

    <main class="auth-panel">
        <?php include __DIR__ . '/panel-top.php'; ?>
        <div class="auth-form-wrap">
            <form method="POST" action="/login" class="auth-form stack" style="gap:16px">
                <div>
                    <h1>Welcome back</h1>
                    <p class="lead" style="margin-bottom:8px">Sign in to see how your bets are doing.</p>
                </div>
                <?php echo csrfField(); ?>
                <div class="field">
                    <label for="email">Email or username</label>
                    <div class="input-affix">
                        <span class="affix"><?php echo icon('mail', 'icon-sm'); ?></span>
                        <input type="text" id="email" name="email" required autocomplete="username" autocapitalize="off" spellcheck="false" value="<?php echo e($oldLogin); ?>" <?php echo $oldLogin === '' ? 'autofocus' : ''; ?>>
                    </div>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" required autocomplete="current-password" <?php echo $oldLogin !== '' ? 'autofocus' : ''; ?>>
                        <button type="button" class="btn btn-ghost btn-icon btn-sm reveal" data-reveal aria-label="Show password"><?php echo icon('eye', 'icon-sm'); ?></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block">Sign in <?php echo icon('arrow-up-right', 'icon-sm'); ?></button>
                <p class="auth-switch">New here? <a href="/register">Create a free account</a></p>
            </form>
        </div>
    </main>
</div>
