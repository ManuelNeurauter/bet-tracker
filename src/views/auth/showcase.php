<aside class="auth-showcase" aria-hidden="true">
    <a href="/login" class="brand" tabindex="-1">
        <span class="brand-mark">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 16.5l5-6 4 3.2 7-8.2"/><path d="M15 5.5h5v5"/></svg>
        </span>
        <span>
            <span class="brand-name"><?php echo e(APP_NAME); ?></span>
            <span class="brand-sub">Sports betting tracker</span>
        </span>
    </a>

    <div class="showcase-copy">
        <h2>Know exactly where your <span>betting money</span> goes.</h2>
        <p>Log every bet, settle it in one tap, and see which sports, bookmakers, odds and tipsters actually pay.</p>

        <div class="showcase-card">
            <div class="top">
                <div>
                    <div class="lbl">Bankroll</div>
                    <div class="big">€2,418.60</div>
                </div>
                <span class="pill pill-won" style="margin-top:4px">+12.4% this month</span>
            </div>
            <svg class="spark" viewBox="0 0 300 90" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="sparkFill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#8b7bff" stop-opacity="0.45"/>
                        <stop offset="1" stop-color="#8b7bff" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                <path d="M0 70 C20 66 30 72 50 62 S80 50 100 56 S130 40 150 44 S185 30 205 36 S240 18 260 22 S285 10 300 8 L300 90 L0 90 Z" fill="url(#sparkFill)"/>
                <path d="M0 70 C20 66 30 72 50 62 S80 50 100 56 S130 40 150 44 S185 30 205 36 S240 18 260 22 S285 10 300 8" fill="none" stroke="#a99dff" stroke-width="2.4" stroke-linecap="round"/>
            </svg>
            <div class="showcase-rows">
                <div class="showcase-row">
                    <div class="ev"><strong>Arsenal vs Chelsea</strong><span>Arsenal to win · @ 2.10</span></div>
                    <span class="pill pill-won">+€21.00</span>
                </div>
                <div class="showcase-row">
                    <div class="ev"><strong>Sinner vs Alcaraz</strong><span>Over 38.5 games · @ 1.85</span></div>
                    <span class="pill pill-pending">Pending</span>
                </div>
                <div class="showcase-row">
                    <div class="ev"><strong>Lakers vs Celtics</strong><span>Celtics -4.5 · @ 1.91</span></div>
                    <span class="pill pill-lost">−€15.00</span>
                </div>
            </div>
        </div>
    </div>

    <div class="showcase-foot">
        <span><?php echo icon('chart-line', 'icon-sm'); ?> Profit by sport, odds and bookmaker</span>
        <span><?php echo icon('percent', 'icon-sm'); ?> Tax-aware returns</span>
        <span><?php echo icon('lock', 'icon-sm'); ?> Private to you</span>
    </div>
</aside>
