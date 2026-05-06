<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Sports Betting Tracker</title>
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="app-header">
            <div class="header-content">
                <div class="logo">
                    <h1><?php echo APP_NAME; ?></h1>
                    <span class="tagline">Sports Betting Tracker</span>
                </div>
                
                <?php if (isLoggedIn()): ?>
                <div class="header-right">
                    <div class="bankroll-display">
                        <?php 
                        $user = new User();
                        $bankroll = $user->getCurrentBankroll(getCurrentUserId());
                        $currency = getCurrentUser()['currency'] ?? 'USD';
                        ?>
                        <span class="label">Bankroll:</span>
                        <span class="amount <?php echo $bankroll < 0 ? 'negative' : 'positive'; ?>">
                            <?php echo formatCurrency($bankroll, $currency); ?>
                        </span>
                    </div>
                    <div class="user-menu">
                        <span class="username"><?php echo sanitize(getCurrentUser()['username']); ?></span>
                        <a href="/settings" class="text-link" title="Settings">Settings</a>
                        <a href="/logout" class="text-link" title="Logout">Logout</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- Main Layout -->
        <div class="main-layout">
            <?php if (isLoggedIn()): ?>
            <!-- Sidebar Navigation -->
            <aside class="sidebar">
                <nav class="nav-menu">
                    <a href="/" class="nav-item">
                        Dashboard
                    </a>
                    <a href="/bets" class="nav-item">
                        Bets
                    </a>
                    <a href="/statistics" class="nav-item">
                        Statistics
                    </a>
                    <a href="/bookmakers" class="nav-item">
                        Bookmakers
                    </a>
                    <a href="/tags" class="nav-item">
                        Tags
                    </a>
                    <a href="/tipsters" class="nav-item">
                        Tipsters
                    </a>
                </nav>
            </aside>
            <?php endif; ?>
            
            <!-- Main Content -->
            <main class="main-content">
                <!-- Flash Messages -->
                <?php if (hasFlash('success')): ?>
                <div class="alert alert-success">
                    <?php echo getFlash('success'); ?>
                </div>
                <?php endif; ?>
                
                <?php if (hasFlash('error')): ?>
                <div class="alert alert-error">
                    <?php echo getFlash('error'); ?>
                </div>
                <?php endif; ?>
                
                <!-- Page Content -->
                <?php include $content_view; ?>
            </main>
        </div>
    </div>
    
    <script src="/js/main.js"></script>
</body>
</html>
