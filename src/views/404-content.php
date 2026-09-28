<div class="error-page <?php echo isLoggedIn() ? '' : 'full'; ?>">
    <div class="error-code">404</div>
    <h1>Nothing here</h1>
    <p><?php echo !empty($notFoundMessage) ? e($notFoundMessage) : 'The page you asked for does not exist. The link may be old, or the address has a typo.'; ?></p>
    <div class="actions">
        <?php if (isLoggedIn()): ?>
        <a href="/" class="btn btn-primary"><?php echo icon('layout-dashboard', 'icon-sm'); ?> Go to dashboard</a>
        <a href="/bets" class="btn"><?php echo icon('receipt-text', 'icon-sm'); ?> All bets</a>
        <?php else: ?>
        <a href="/login" class="btn btn-primary">Sign in</a>
        <?php endif; ?>
    </div>
</div>
