<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Sports Betting Tracker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body class="<?php echo isLoggedIn() ? 'app-shell authenticated-shell' : 'app-shell guest-shell'; ?>">
    <?php $currentRoute = $request_uri ?? ''; ?>
    <div class="app-shell-frame">
        <header class="app-header">
            <div class="container-fluid px-4 py-3 header-content">
                <a href="/" class="logo text-decoration-none">
                    <?php if (isLoggedIn()): ?>
                    <span class="brand-mark"><?php echo substr(APP_NAME, 0, 1); ?></span>
                    <?php endif; ?>
                    <span>
                        <h1><?php echo APP_NAME; ?></h1>
                        <span class="tagline">Sports betting tracker</span>
                    </span>
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
                        <a href="/settings" class="btn btn-outline-light btn-sm">Settings</a>
                        <a href="/logout" class="btn btn-light btn-sm">Logout</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </header>

        <div class="main-layout">
            <?php if (isLoggedIn()): ?>
            <aside class="sidebar">
                <div class="sidebar-inner">
                    <nav class="nav-menu nav nav-pills flex-column">
                        <a href="/" class="nav-item nav-link <?php echo ($currentRoute === '' || $currentRoute === 'dashboard') ? 'active' : ''; ?>">Dashboard</a>
                        <a href="/bets" class="nav-item nav-link <?php echo strpos($currentRoute, 'bets') === 0 ? 'active' : ''; ?>">Bets</a>
                        <a href="/statistics" class="nav-item nav-link <?php echo strpos($currentRoute, 'statistics') === 0 ? 'active' : ''; ?>">Statistics</a>
                        <a href="/bookmakers" class="nav-item nav-link <?php echo strpos($currentRoute, 'bookmakers') === 0 ? 'active' : ''; ?>">Bookmakers</a>
                        <a href="/tags" class="nav-item nav-link <?php echo strpos($currentRoute, 'tags') === 0 ? 'active' : ''; ?>">Tags</a>
                        <a href="/tipsters" class="nav-item nav-link <?php echo strpos($currentRoute, 'tipsters') === 0 ? 'active' : ''; ?>">Tipsters</a>
                    </nav>
                </div>
            </aside>
            <?php endif; ?>

            <main class="main-content">
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
    </div>

    <script src="/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>
