<?php
$currentRoute = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$section = explode('/', $currentRoute)[0] ?: 'dashboard';
$pageTitle = $pageTitle ?? APP_NAME;
$flashes = [];
foreach (['success', 'error'] as $flashType) {
    if (hasFlash($flashType)) {
        $flashes[] = ['type' => $flashType, 'message' => getFlash($flashType)];
    }
}

if (isLoggedIn() && getCurrentUser()) {
    $layoutUser = getCurrentUser();
    $userModel = new User();
    $layoutBankroll = (float)$userModel->getCurrentBankroll(getCurrentUserId());
    $layoutPending = $userModel->countPendingBets(getCurrentUserId());
    $sharedBetModel = new SharedBet();
    $layoutInvites = $sharedBetModel->countInvitesFor($layoutUser);
}

$navItems = [
    'Overview' => [
        ['dashboard', '/', 'layout-dashboard', 'Dashboard'],
        ['bets', '/bets', 'receipt-text', 'Bets'],
        ['statistics', '/statistics', 'chart-column', 'Statistics'],
        ['shared', '/shared', 'handshake', 'Shared bets'],
    ],
    'Manage' => [
        ['bookmakers', '/bookmakers', 'landmark', 'Bookmakers'],
        ['tags', '/tags', 'tag', 'Tags'],
        ['tipsters', '/tipsters', 'users', 'Tipsters'],
    ],
];
?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <meta name="theme-color" content="#07090d">
    <title><?php echo e($pageTitle === APP_NAME ? APP_NAME . ' · Sports betting tracker' : $pageTitle . ' · ' . APP_NAME); ?></title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme') || 'dark';
                if (t === 'system') t = matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'><stop offset='0' stop-color='%239b8cff'/><stop offset='1' stop-color='%234f7dff'/></linearGradient></defs><rect width='32' height='32' rx='9' fill='url(%23g)'/><path d='M8 21l5-6 4 3 7-8' fill='none' stroke='white' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'/></svg>">
    <link rel="preload" href="/assets/fonts/geist-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?php echo asset('css/app.css'); ?>">
    <script src="<?php echo asset('assets/vendor/chart.umd.min.js'); ?>" defer></script>
    <script src="<?php echo asset('js/app.js'); ?>" defer></script>
</head>
<?php
$bodyCurrency = isset($layoutUser) ? ($layoutUser['currency'] ?: DEFAULT_CURRENCY) : DEFAULT_CURRENCY;
?>
<body data-currency="<?php echo e($bodyCurrency); ?>" data-symbol="<?php echo e(CURRENCY_SYMBOLS[$bodyCurrency] ?? '$'); ?>" data-odds-format="<?php echo e(isset($layoutUser) ? ($layoutUser['odds_format'] ?: 'decimal') : 'decimal'); ?>" data-sprite="<?php echo asset('assets/icons.svg'); ?>">
<?php if (isset($layoutUser)): ?>
    <div class="app">
        <aside class="sidebar" id="sidebar">
            <a href="/" class="brand">
                <span class="brand-mark">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 16.5l5-6 4 3.2 7-8.2"/><path d="M15 5.5h5v5"/></svg>
                </span>
                <span>
                    <span class="brand-name"><?php echo e(APP_NAME); ?></span>
                    <span class="brand-sub">Sports betting tracker</span>
                </span>
            </a>

            <a href="/bets/add" class="btn btn-primary sidebar-cta"><?php echo icon('plus'); ?> New bet <kbd class="desktop-only" style="margin-left:auto;background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.2);color:#fff">N</kbd></a>

            <?php foreach ($navItems as $groupLabel => $items): ?>
            <div class="nav-label"><?php echo e($groupLabel); ?></div>
            <nav class="nav">
                <?php foreach ($items as [$key, $href, $iconName, $label]): ?>
                <a href="<?php echo $href; ?>" class="nav-link <?php echo $section === $key ? 'active' : ''; ?>">
                    <?php echo icon($iconName); ?>
                    <span><?php echo e($label); ?></span>
                    <?php if ($key === 'bets' && $layoutPending > 0): ?>
                    <span class="nav-count" title="<?php echo $layoutPending; ?> pending"><?php echo $layoutPending; ?></span>
                    <?php endif; ?>
                    <?php if ($key === 'shared' && $layoutInvites > 0): ?>
                    <span class="nav-count accent" title="<?php echo $layoutInvites; ?> invitation<?php echo $layoutInvites === 1 ? '' : 's'; ?>"><?php echo $layoutInvites; ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </nav>
            <?php endforeach; ?>

            <div class="sidebar-footer">
                <a href="/bookmakers" class="bankroll-card" style="color:inherit">
                    <div class="label"><?php echo icon('wallet', 'icon-xs'); ?> Bankroll</div>
                    <div class="value <?php echo $layoutBankroll < 0 ? 'text-loss' : ''; ?>"><?php echo formatCurrency($layoutBankroll); ?></div>
                    <div class="meta">Across all active bookmakers</div>
                </a>

                <details class="dropdown">
                    <summary class="user-chip">
                        <?php echo avatar($layoutUser['username'], 'sm round'); ?>
                        <span class="who">
                            <strong><?php echo e($layoutUser['username']); ?></strong>
                            <span class="truncate"><?php echo e($layoutUser['email']); ?></span>
                        </span>
                        <?php echo icon('ellipsis', 'icon-sm'); ?>
                    </summary>
                    <div class="dropdown-menu up">
                        <a href="/settings"><?php echo icon('settings', 'icon-sm'); ?> Settings</a>
                        <button type="button" data-theme-toggle><?php echo icon('sun', 'icon-sm theme-sun'); ?><?php echo icon('moon', 'icon-sm theme-moon'); ?> <span data-theme-label>Light mode</span></button>
                        <hr>
                        <form method="POST" action="/logout">
                            <?php echo csrfField(); ?>
                            <button type="submit" class="danger"><?php echo icon('log-out', 'icon-sm'); ?> Sign out</button>
                        </form>
                    </div>
                </details>
            </div>
        </aside>
        <div class="sidebar-backdrop" data-nav-close></div>

        <div class="main">
            <header class="topbar">
                <button type="button" class="btn btn-ghost btn-icon mobile-only" data-nav-open aria-label="Open menu"><?php echo icon('menu'); ?></button>
                <div class="topbar-title">
                    <?php if (!empty($breadcrumbs)): ?>
                    <nav class="crumbs" aria-label="Breadcrumb">
                        <?php foreach ($breadcrumbs as [$crumbLabel, $crumbUrl]): ?>
                        <a href="<?php echo e($crumbUrl); ?>"><?php echo e($crumbLabel); ?></a>
                        <?php echo icon('chevron-right', 'icon-xs'); ?>
                        <?php endforeach; ?>
                        <span class="current"><?php echo e($pageTitle); ?></span>
                    </nav>
                    <?php else: ?>
                    <span class="truncate"><?php echo e($pageTitle); ?></span>
                    <?php endif; ?>
                </div>
                <div class="topbar-actions">
                    <form class="search" action="/bets" method="GET" role="search">
                        <?php echo icon('search', 'icon-sm'); ?>
                        <input type="search" name="search" placeholder="Search bets…" aria-label="Search bets" data-search-input value="<?php echo $section === 'bets' ? e($_GET['search'] ?? '') : ''; ?>">
                        <kbd>/</kbd>
                    </form>
                    <button type="button" class="btn btn-ghost btn-icon" data-theme-toggle aria-label="Toggle colour theme" title="Toggle theme">
                        <?php echo icon('sun', 'theme-sun'); ?><?php echo icon('moon', 'theme-moon'); ?>
                    </button>
                    <a href="/bets/add" class="btn btn-primary btn-sm mobile-only" aria-label="New bet"><?php echo icon('plus'); ?></a>
                </div>
            </header>

            <main class="page <?php echo e($pageClass ?? ''); ?>" id="content">
                <?php include $content_view; ?>
            </main>
        </div>

        <nav class="bottom-nav" aria-label="Main">
            <a href="/" class="<?php echo $section === 'dashboard' ? 'active' : ''; ?>"><?php echo icon('layout-dashboard'); ?>Home</a>
            <a href="/bets" class="<?php echo $section === 'bets' && $currentRoute !== 'bets/add' ? 'active' : ''; ?>"><?php echo icon('receipt-text'); ?>Bets</a>
            <a href="/bets/add" class="fab" aria-label="New bet"><?php echo icon('plus'); ?></a>
            <a href="/statistics" class="<?php echo $section === 'statistics' ? 'active' : ''; ?>"><?php echo icon('chart-column'); ?>Stats</a>
            <a href="/bookmakers" class="<?php echo in_array($section, ['bookmakers', 'tags', 'tipsters', 'settings'], true) ? 'active' : ''; ?>"><?php echo icon('wallet'); ?>Books</a>
        </nav>
    </div>
<?php else: ?>
    <?php include $content_view; ?>
<?php endif; ?>

    <div class="toasts" id="toasts" aria-live="polite">
        <?php foreach ($flashes as $flash): ?>
        <div class="toast <?php echo e($flash['type']); ?>" role="<?php echo $flash['type'] === 'error' ? 'alert' : 'status'; ?>">
            <span class="toast-icon"><?php echo icon($flash['type'] === 'error' ? 'circle-alert' : 'circle-check', 'icon-sm'); ?></span>
            <div class="toast-body"><?php echo $flash['message']; ?></div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm close" data-toast-close aria-label="Dismiss"><?php echo icon('x', 'icon-sm'); ?></button>
        </div>
        <?php endforeach; ?>
    </div>

    <dialog class="modal" id="confirmDialog">
        <form method="dialog">
            <div class="modal-head">
                <span class="modal-icon danger"><?php echo icon('trash-2'); ?></span>
                <div>
                    <h2 data-confirm-title>Are you sure?</h2>
                    <p data-confirm-text>This cannot be undone.</p>
                </div>
            </div>
            <div class="modal-foot">
                <button value="cancel" class="btn">Cancel</button>
                <button value="confirm" class="btn btn-danger-solid" data-confirm-button>Delete</button>
            </div>
        </form>
    </dialog>
</body>
</html>
