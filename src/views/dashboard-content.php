<div class="dashboard-container">

    <!-- Dashboard header: greeting + primary CTA -->
    <div class="dashboard-header">
        <h1><?php
            $hour = (int)date('H');
            if ($hour < 12) $greeting = 'Good morning';
            elseif ($hour < 18) $greeting = 'Good afternoon';
            else $greeting = 'Good evening';
            $username = sanitize($userData['username'] ?? '');
            echo $username ? $greeting . ', ' . $username : $greeting;
        ?></h1>
        <a href="/bets/add" class="btn btn-primary">Add bet</a>
    </div>

    <!-- KPI Cards: P&L and ROI carry primary visual weight -->
    <div class="kpi-grid">
        <div class="kpi-card kpi-card--primary">
            <div class="kpi-label">Total Profit / Loss</div>
            <div class="kpi-value <?php echo $totalProfit >= 0 ? 'positive' : 'negative'; ?>"
                 data-countup="<?php echo $totalProfit; ?>"
                 data-countup-fmt="currency"
                 data-countup-currency="<?php echo $userData['currency']; ?>">
                <?php echo formatCurrency($totalProfit, $userData['currency']); ?>
            </div>
        </div>
        <div class="kpi-card kpi-card--primary">
            <div class="kpi-label">ROI</div>
            <div class="kpi-value"
                 data-countup="<?php echo $roi; ?>"
                 data-countup-fmt="percent">
                <?php echo $roi; ?>%
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Win Rate</div>
            <div class="kpi-value"
                 data-countup="<?php echo $winRate; ?>"
                 data-countup-fmt="percent">
                <?php echo $winRate; ?>%
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Bets</div>
            <div class="kpi-value"
                 data-countup="<?php echo $totalBets; ?>"
                 data-countup-fmt="integer">
                <?php echo $totalBets; ?>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label kpi-label--with-info">
                <abbr title="Yield is your profit as a percentage of total amount wagered (Profit ÷ Total Staked × 100). Higher means better returns relative to what you risked.">Yield</abbr>
            </div>
            <div class="kpi-value"
                 data-countup="<?php echo $yield; ?>"
                 data-countup-fmt="percent">
                <?php echo $yield; ?>%
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Avg Odds</div>
            <div class="kpi-value"
                 data-countup="<?php echo $avgOdds; ?>"
                 data-countup-fmt="decimal">
                <?php echo number_format($avgOdds, 2); ?>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
        <div class="chart-container">
            <h3>Bankroll Over Time</h3>
            <div class="chart-viewport">
                <canvas id="bankrollChart" role="img" aria-label="Line chart: bankroll balance over time"></canvas>
            </div>
        </div>
        <div class="chart-container">
            <h3>P&amp;L by Sport</h3>
            <div class="chart-viewport">
                <canvas id="sportChart" role="img" aria-label="Bar chart: profit and loss grouped by sport"></canvas>
            </div>
        </div>
    </div>

    <!-- Detail zone: Recent Bets | Calendar | Pending Bets
         DOM order: recent, calendar, pending.
         On tablet: calendar drops below the two bet panels via CSS order.
         On mobile: all stack. -->
    <div class="dashboard-row dashboard-row--3col">

        <!-- Column 1: Recent Bets (widest) -->
        <div class="dashboard-section">
            <h3>Recent Bets</h3>
            <?php if (empty($recentBets)): ?>
                <div class="recent-empty-state">
                    <p>No bets recorded yet.</p>
                    <a href="/bets/add" class="btn btn-primary">Record your first bet</a>
                </div>
            <?php else: ?>
                <div class="bets-table">
                    <table aria-label="Recent betting activity">
                        <caption class="sr-only">Your 10 most recent bets</caption>
                        <thead>
                            <tr>
                                <th scope="col">Event</th>
                                <th scope="col">Type</th>
                                <th scope="col">Odds</th>
                                <th scope="col">Stake</th>
                                <th scope="col">Status</th>
                                <th scope="col">Return</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBets as $bet): ?>
                            <tr class="row-<?php echo $bet['status']; ?>">
                                <td><a href="/bets/<?php echo $bet['id']; ?>"><?php echo sanitize($bet['event_name']); ?></a></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $bet['bet_type'])); ?></td>
                                <td><?php echo number_format($bet['odds'], 2); ?></td>
                                <td><?php echo formatCurrency($bet['stake'], $userData['currency']); ?></td>
                                <td><?php echo getStatusBadge($bet['status']); ?></td>
                                <td><?php echo $bet['actual_return'] ? formatCurrency($bet['actual_return'], $userData['currency']) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <a href="/bets" class="btn btn-secondary">View all bets</a>
            <?php endif; ?>
        </div>

        <!-- Column 2: Calendar (collapsed by default; expand to review history) -->
        <div class="calendar-container is-collapsed" id="calendarContainer">
            <div class="calendar-header">
                <h3>Calendar</h3>
                <div class="calendar-header-right">
                    <div class="calendar-nav">
                        <button id="calPrev" class="btn btn-small btn-outline-light" aria-label="Previous month">&#8249;</button>
                        <span id="calMonthLabel" class="calendar-month-label"></span>
                        <button id="calNext" class="btn btn-small btn-outline-light" aria-label="Next month">&#8250;</button>
                    </div>
                    <button class="btn btn-small btn-outline-light" id="calToggle" aria-expanded="false" aria-controls="miniCalendar">Show</button>
                </div>
            </div>
            <div class="weekday-headers" aria-hidden="true"></div>
            <div id="miniCalendar" class="mini-calendar" role="grid" aria-label="Monthly calendar"></div>
        </div>

        <!-- Column 3: Pending Bets (narrowest) -->
        <div class="dashboard-section dashboard-section--pending">
            <h3>Pending <span class="pending-count">(<?php echo count($pendingBets); ?>)</span></h3>
            <div class="pending-bets">
                <?php if (count($pendingBets) > 0): ?>
                    <?php foreach (array_slice($pendingBets, 0, 5) as $bet): ?>
                    <div class="pending-bet-card">
                        <div class="bet-event">
                            <strong><?php echo sanitize($bet['event_name']); ?></strong>
                            <span class="date"><?php echo formatDate($bet['event_date']); ?></span>
                        </div>
                        <div class="pending-bet-meta">
                            <span class="pending-meta-item">
                                <span class="stat-label">Odds</span>
                                <?php echo number_format($bet['odds'], 2); ?>
                            </span>
                            <span class="pending-meta-item">
                                <span class="stat-label">Stake</span>
                                <?php echo formatCurrency($bet['stake'], $userData['currency']); ?>
                            </span>
                            <span class="pending-meta-item">
                                <span class="stat-label">Return</span>
                                <?php echo formatCurrency($bet['potential_return'], $userData['currency']); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($pendingBets) > 5): ?>
                    <a href="/bets?status=pending" class="btn btn-small btn-outline-light">View all <?php echo count($pendingBets); ?> pending</a>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="pending-empty-state">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M8 12l3 3 5-5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>All settled, nothing pending.</span>
                        <a href="/bets/add" class="btn btn-small btn-outline-light">Add a bet</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    /* Chart.js 3.x only understands hex/rgb/hsl — not oklch.
       These are hsla() equivalents of the Deep Violet + Amber palette. */
    var colorPrimary     = 'hsla(262, 70%, 54%, 1)';    /* --primary  */
    var colorPrimaryFill = 'hsla(262, 70%, 54%, 0.28)';
    var colorAccent      = 'hsla(50,  60%, 50%, 0.85)';  /* --accent (amber) */
    var colorLoss        = 'hsla(12,  50%, 44%, 0.82)';  /* warm dim red — in-palette */
    var colorPointHover  = 'hsla(50,  60%, 56%, 1)';
    var tickColor        = 'hsla(258, 8%,  40%, 1)';     /* --text-3 */
    var gridColor        = 'hsla(260, 18%, 22%, 0.40)';  /* --border */

    Chart.defaults.color = tickColor;
    Chart.defaults.font.family = "'Inter', sans-serif";

    var bankrollHistory = <?php echo json_encode($bankrollHistory); ?>;
    var bankrollLabels  = bankrollHistory.map(function(p) { return p.snapshot_date; });
    var bankrollValues  = bankrollHistory.map(function(p) { return Number(p.balance); });

    // Bankroll chart — indigo line with violet gradient fill
    var bankrollCtx = document.getElementById('bankrollChart');
    if (bankrollCtx) {
        var ctx2d = bankrollCtx.getContext('2d');
        var bankrollGradient = ctx2d.createLinearGradient(0, 0, 0, 260);
        bankrollGradient.addColorStop(0, 'hsla(262, 70%, 54%, 0.30)');
        bankrollGradient.addColorStop(1, 'hsla(262, 70%, 54%, 0.02)');

        new Chart(bankrollCtx, {
            type: 'line',
            data: {
                labels: bankrollLabels,
                datasets: [{
                    label: 'Bankroll',
                    data: bankrollValues,
                    borderColor: colorPrimary,
                    backgroundColor: bankrollGradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: colorPrimary,
                    pointBorderColor: 'hsla(262, 30%, 12%, 1)',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: colorPointHover,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 800, easing: 'easeOutQuart' },
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: gridColor } },
                    y: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: gridColor } }
                }
            }
        });
    }

    // Sport P&L chart — amber for profit, dim warm-red for loss (in-palette, no semantic green)
    var sportCtx = document.getElementById('sportChart');
    if (sportCtx) {
        var profitBySport = <?php echo json_encode($profitBySport); ?>;
        new Chart(sportCtx, {
            type: 'bar',
            data: {
                labels: profitBySport.map(function(r) { return r.sport_name || r.bookmaker_name || 'Unknown'; }),
                datasets: [{
                    data: profitBySport.map(function(r) { return Number(r.profit_loss || r.profit || 0); }),
                    backgroundColor: profitBySport.map(function(r) {
                        return Number(r.profit_loss || r.profit || 0) >= 0
                            ? colorAccent
                            : colorLoss;
                    }),
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 700, easing: 'easeOutQuart' },
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: 'transparent' } },
                    y: { ticks: { color: tickColor, font: { size: 11 } }, grid: { color: gridColor } }
                }
            }
        });
    }
});

// Calendar
document.addEventListener('DOMContentLoaded', function() {
    var dailySummary = <?php echo json_encode($dailySummary ?? []); ?>;
    var betsByDate   = <?php echo json_encode($betsByDate ?? []); ?>;
    var calendarEl   = document.getElementById('miniCalendar');
    var currency     = <?php echo json_encode($userData['currency']); ?>;

    // Calendar modal
    var modal = document.createElement('div');
    modal.id = 'calendarModal';
    modal.className = 'modal';
    modal.style.display = 'none';
    modal.innerHTML = '<div class="modal-content small"><div class="modal-header"><h4 id="modalTitle"></h4><button class="modal-close" onclick="closeCalendarModal()">&times;</button></div><div class="modal-body" id="modalBody"></div></div>';
    document.body.appendChild(modal);

    // Hover tooltip
    var calendarTooltip = document.createElement('div');
    calendarTooltip.id = 'calendarTooltip';
    calendarTooltip.style.cssText = 'position:absolute;display:none;pointer-events:none;';
    calendarTooltip.className = 'calendar-tooltip';
    document.body.appendChild(calendarTooltip);

    function showCalendarTooltip(e, key) {
        var data = dailySummary[key];
        var bets = betsByDate[key] || [];
        var html = '<div class="tt-row"><strong>' + bets.length + ' bet' + (bets.length !== 1 ? 's' : '') + '</strong></div>';
        if (data && typeof data.profit_loss !== 'undefined') {
            var pl = Number(data.profit_loss || 0);
            html += '<div class="tt-row">P/L: <span class="' + (pl >= 0 ? 'positive' : 'negative') + '">' + formatCurrency(pl, currency) + '</span></div>';
        }
        calendarTooltip.innerHTML = html;
        calendarTooltip.style.display = 'block';
        moveCalendarTooltip(e);
    }

    function moveCalendarTooltip(e) {
        if (!calendarTooltip || calendarTooltip.style.display === 'none') return;
        var pad = 12;
        var x = e.pageX + pad;
        var y = e.pageY + pad;
        var rect = calendarTooltip.getBoundingClientRect();
        if (x + rect.width  > window.pageXOffset + document.documentElement.clientWidth)  x = e.pageX - rect.width  - pad;
        if (y + rect.height > window.pageYOffset + document.documentElement.clientHeight) y = e.pageY - rect.height - pad;
        calendarTooltip.style.left = x + 'px';
        calendarTooltip.style.top  = y + 'px';
    }

    function hideCalendarTooltip() { calendarTooltip.style.display = 'none'; }

    // Calendar state
    var displayed = new Date();

    function renderWeekdayHeaders() {
        var headersEl = document.querySelector('.weekday-headers');
        if (!headersEl) return;
        var weekdays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        headersEl.innerHTML = '';
        weekdays.forEach(function(n) {
            var el = document.createElement('div');
            el.className = 'weekday-header';
            el.textContent = n;
            headersEl.appendChild(el);
        });
    }

    function startOfCalendarGrid(year, month) {
        var first   = new Date(year, month, 1);
        var weekday = (first.getDay() + 6) % 7;
        var start   = new Date(first);
        start.setDate(first.getDate() - weekday);
        return start;
    }

    var todayKey = new Date().toISOString().slice(0, 10);

    function renderCalendar() {
        if (!calendarEl) return;
        calendarEl.innerHTML = '';
        var year         = displayed.getFullYear();
        var month        = displayed.getMonth();
        var firstOfMonth = new Date(year, month, 1);
        var lastOfMonth  = new Date(year, month + 1, 0);
        var monthLabel   = firstOfMonth.toLocaleString(undefined, { month: 'long', year: 'numeric' });
        document.getElementById('calMonthLabel').textContent = monthLabel;

        var start     = startOfCalendarGrid(year, month);
        var totalDays = Math.ceil(((lastOfMonth - start) / 86400000 + 1) / 7) * 7;

        for (var i = 0; i < totalDays; i++) {
            var d   = new Date(start);
            d.setDate(start.getDate() + i);
            var key = d.toISOString().slice(0, 10);

            var dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-day';
            dayDiv.setAttribute('role', 'gridcell');

            var isCurrentMonth = d.getMonth() === month;
            if (!isCurrentMonth) {
                dayDiv.classList.add('muted');
                dayDiv.setAttribute('tabindex', '-1');
            } else {
                dayDiv.setAttribute('tabindex', '0');
            }

            // Today marker
            if (key === todayKey) {
                dayDiv.classList.add('day-today');
                dayDiv.setAttribute('aria-current', 'date');
            }

            var amount = dailySummary[key] ? Number(dailySummary[key].total_staked || 0) : 0;
            var profit = dailySummary[key] ? Number(dailySummary[key].profit_loss  || 0) : null;

            if (dailySummary[key]) {
                var betCount = (betsByDate[key] || []).length;
                dayDiv.classList.add('has-bets');
                dayDiv.setAttribute('aria-label', d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ', ' + betCount + ' bet' + (betCount !== 1 ? 's' : '') + (profit !== null ? ', P/L ' + (profit >= 0 ? '+' : '') + profit.toFixed(2) : ''));
                if (profit > 0)      dayDiv.classList.add('day-win');
                else if (profit < 0) dayDiv.classList.add('day-loss');
                else                 dayDiv.classList.add('day-neutral');
            } else {
                dayDiv.setAttribute('aria-label', d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }));
            }

            dayDiv.innerHTML = '<div class="date">' + d.getDate() + '</div><div class="amt">' + (amount > 0 ? formatCurrency(amount, currency) : '') + '</div>';
            dayDiv.dataset.date = key;

            dayDiv.addEventListener('click',      function() { showBetsForDate(this.dataset.date); });
            dayDiv.addEventListener('keydown',    function(e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); showBetsForDate(this.dataset.date); }
            });
            dayDiv.addEventListener('mouseenter', function(e) { showCalendarTooltip(e, this.dataset.date); });
            dayDiv.addEventListener('mousemove',  function(e) { moveCalendarTooltip(e); });
            dayDiv.addEventListener('mouseleave', hideCalendarTooltip);

            calendarEl.appendChild(dayDiv);
        }
    }

    // Month navigation
    document.getElementById('calPrev').addEventListener('click', function() { displayed.setMonth(displayed.getMonth() - 1); renderCalendar(); });
    document.getElementById('calNext').addEventListener('click', function() { displayed.setMonth(displayed.getMonth() + 1); renderCalendar(); });

    // Calendar toggle: when expanding, focus the grid so keyboard users land on it
    var calContainer = document.getElementById('calendarContainer');
    var calToggle    = document.getElementById('calToggle');
    calToggle.addEventListener('click', function() {
        var isCollapsed = calContainer.classList.toggle('is-collapsed');
        calToggle.textContent = isCollapsed ? 'Show' : 'Hide';
        calToggle.setAttribute('aria-expanded', (!isCollapsed).toString());
        if (!isCollapsed) {
            var firstActive = calendarEl.querySelector('[tabindex="0"]');
            if (firstActive) firstActive.focus();
        }
    });

    renderWeekdayHeaders();

    // Modal: focus close button on open; close on Escape
    window.closeCalendarModal = function() {
        var modal = document.getElementById('calendarModal');
        modal.style.display = 'none';
        // Return focus to the day that opened the modal
        if (window._calendarModalTrigger) {
            window._calendarModalTrigger.focus();
            window._calendarModalTrigger = null;
        }
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var modal = document.getElementById('calendarModal');
            if (modal && modal.style.display !== 'none') closeCalendarModal();
        }
    });

    function showBetsForDate(date) {
        // Store the triggering element so focus can return on close
        window._calendarModalTrigger = document.activeElement;

        var list  = betsByDate[date] || [];
        var body  = document.getElementById('modalBody');
        var d     = new Date(date + 'T00:00:00');
        var label = d.toLocaleDateString(undefined, { weekday: 'long', month: 'long', day: 'numeric' });
        document.getElementById('modalTitle').textContent = label;

        if (list.length === 0) {
            body.innerHTML = '<p>No bets recorded for this day.</p>';
        } else {
            var html = '<table class="table small" aria-label="Bets on ' + sanitizeClient(label) + '">'
                + '<thead><tr>'
                + '<th scope="col">Event</th><th scope="col">Odds</th>'
                + '<th scope="col">Stake</th><th scope="col">Status</th><th scope="col">Return</th>'
                + '</tr></thead><tbody>';
            list.forEach(function(b) {
                html += '<tr>'
                    + '<td><a href="/bets/' + b.id + '">' + sanitizeClient(b.event_name) + '</a></td>'
                    + '<td>' + Number(b.odds).toFixed(2) + '</td>'
                    + '<td>' + formatCurrency(Number(b.stake), currency) + '</td>'
                    + '<td>' + sanitizeClient(b.status) + '</td>'
                    + '<td>' + (b.actual_return ? formatCurrency(Number(b.actual_return), currency) : '-') + '</td>'
                    + '</tr>';
            });
            html += '</tbody></table>';
            body.innerHTML = html;
        }

        var modal = document.getElementById('calendarModal');
        modal.style.display = 'flex';
        // Move focus to close button so keyboard users are inside the modal
        var closeBtn = modal.querySelector('.modal-close');
        if (closeBtn) closeBtn.focus();
    }

    function sanitizeClient(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    renderCalendar();
});

/* ── KPI countup animation ─────────────────────────────────────── */
(function () {
    var DURATION = 900;
    var DELAY    = 300;

    function easeOutExpo(t) {
        return t === 1 ? 1 : 1 - Math.pow(2, -10 * t);
    }

    function formatValue(val, fmt, cur) {
        if (fmt === 'currency') {
            return formatCurrency(val, cur || 'USD');
        } else if (fmt === 'percent') {
            return (val >= 0 ? '' : '') + Number(val).toFixed(2) + '%';
        } else if (fmt === 'integer') {
            return Math.round(val).toString();
        } else {
            return Number(val).toFixed(2);
        }
    }

    function animateCountup(el) {
        var target  = parseFloat(el.dataset.countup);
        var fmt     = el.dataset.countupFmt   || 'integer';
        var cur     = el.dataset.countupCurrency || 'USD';
        if (isNaN(target)) return;

        var negative  = target < 0;
        var absTarget = Math.abs(target);
        var start     = null;

        function step(ts) {
            if (!start) start = ts;
            var elapsed  = ts - start;
            var progress = Math.min(elapsed / DURATION, 1);
            var eased    = easeOutExpo(progress);
            var current  = negative ? -(absTarget * eased) : absTarget * eased;
            el.textContent = formatValue(current, fmt, cur);
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = formatValue(target, fmt, cur);
            }
        }

        setTimeout(function () {
            requestAnimationFrame(step);
        }, DELAY);
    }

    document.querySelectorAll('[data-countup]').forEach(function (el) {
        animateCountup(el);
    });
})();
</script>
