<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Sports Betting Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body class="<?php echo isLoggedIn() ? 'app-shell authenticated-shell' : 'app-shell guest-shell'; ?>">
    <?php
    /* Derive the current route directly so it works regardless of call scope */
    $currentRoute = ltrim(rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), '/');
    ?>
    <div class="app-shell-frame">
        <header class="app-header">
            <div class="header-content">
                <a href="/" class="logo">
                    <?php
                    $name = APP_NAME;
                    /* Split at capital L so "BetLedger" → "Bet" + "Ledger" */
                    $pivot = strpos($name, 'L');
                    $part1 = $pivot !== false ? substr($name, 0, $pivot) : $name;
                    $part2 = $pivot !== false ? substr($name, $pivot) : '';
                    ?>
                    <span class="wordmark"><?php echo sanitize($part1); ?><span class="word-accent"><?php echo sanitize($part2); ?></span></span>
                    <?php if (!isLoggedIn()): ?>
                    <span class="tagline">Sports betting tracker</span>
                    <?php endif; ?>
                </a>

                <?php if (isLoggedIn()): ?>
                <div class="header-right">
                    <div class="bankroll-display">
                        <?php
                        $user = new User();
                        $bankroll = $user->getCurrentBankroll(getCurrentUserId());
                        $currency = getCurrentUser()['currency'] ?? 'USD';
                        ?>
                        <span class="label">Bankroll</span>
                        <span class="amount <?php echo $bankroll < 0 ? 'negative' : 'positive'; ?>">
                            <?php echo formatCurrency($bankroll, $currency); ?>
                        </span>
                    </div>
                    <div class="user-menu">
                        <span class="username"><?php echo sanitize(getCurrentUser()['username']); ?></span>
                        <a href="/settings" class="btn btn-outline-light btn-small">Settings</a>
                        <a href="/logout" class="btn btn-light btn-small">Log out</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <main class="main-content <?php echo isLoggedIn() ? 'has-dock' : ''; ?>">
            <?php if (hasFlash('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo getFlash('success'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (hasFlash('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo getFlash('error'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php include $content_view; ?>
        </main>
    </div>

    <?php if (isLoggedIn()): ?>
    <nav class="nav-dock" aria-label="Main navigation">
        <a href="/"
           class="dock-item <?php echo ($currentRoute === '' || $currentRoute === 'dashboard') ? 'active' : ''; ?>"
           aria-current="<?php echo ($currentRoute === '' || $currentRoute === 'dashboard') ? 'page' : 'false'; ?>">
            Dashboard
        </a>
        <a href="/bets"
           class="dock-item <?php echo strpos($currentRoute, 'bets') === 0 ? 'active' : ''; ?>"
           aria-current="<?php echo strpos($currentRoute, 'bets') === 0 ? 'page' : 'false'; ?>">
            Bets
        </a>
        <a href="/statistics"
           class="dock-item <?php echo strpos($currentRoute, 'statistics') === 0 ? 'active' : ''; ?>"
           aria-current="<?php echo strpos($currentRoute, 'statistics') === 0 ? 'page' : 'false'; ?>">
            Statistics
        </a>
        <a href="/bookmakers"
           class="dock-item <?php echo strpos($currentRoute, 'bookmakers') === 0 ? 'active' : ''; ?>"
           aria-current="<?php echo strpos($currentRoute, 'bookmakers') === 0 ? 'page' : 'false'; ?>">
            Bookmakers
        </a>
        <a href="/tags"
           class="dock-item <?php echo strpos($currentRoute, 'tags') === 0 ? 'active' : ''; ?>"
           aria-current="<?php echo strpos($currentRoute, 'tags') === 0 ? 'page' : 'false'; ?>">
            Tags
        </a>
        <a href="/tipsters"
           class="dock-item <?php echo strpos($currentRoute, 'tipsters') === 0 ? 'active' : ''; ?>"
           aria-current="<?php echo strpos($currentRoute, 'tipsters') === 0 ? 'page' : 'false'; ?>">
            Tipsters
        </a>
    </nav>
    <?php endif; ?>

    <script src="/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
