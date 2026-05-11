<div class="dashboard-container">
    <h1>Dashboard</h1>
    
    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Bets</div>
            <div class="kpi-value"><?php echo $totalBets; ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Win Rate</div>
            <div class="kpi-value"><?php echo $winRate; ?>%</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Profit/Loss</div>
            <div class="kpi-value <?php echo $totalProfit >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo formatCurrency($totalProfit, $userData['currency']); ?>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">ROI</div>
            <div class="kpi-value"><?php echo $roi; ?>%</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Yield</div>
            <div class="kpi-value"><?php echo $yield; ?>%</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Avg Odds</div>
            <div class="kpi-value"><?php echo number_format($avgOdds, 2); ?></div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="charts-row">
        <div class="chart-container">
            <h3>Bankroll Over Time</h3>
            <div class="chart-viewport">
                <canvas id="bankrollChart"></canvas>
            </div>
        </div>
        <div class="chart-container">
            <h3>P&L by Sport</h3>
            <div class="chart-viewport">
                <canvas id="sportChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Calendar Row -->
    <div class="calendar-row">
        <div class="calendar-container">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                    <h3 style="margin:0">Calendar</h3>
                    <div class="calendar-nav">
                        <button id="calPrev" class="btn btn-small btn-outline-light">‹</button>
                        <span id="calMonthLabel" style="margin:0 0.6rem;font-weight:700;color:var(--text-primary)"></span>
                        <button id="calNext" class="btn btn-small btn-outline-light">›</button>
                    </div>
                </div>
                <div class="weekday-headers" aria-hidden="true"></div>
                <div id="miniCalendar" class="mini-calendar" role="grid" aria-label="Monthly calendar"></div>
            </div>
    </div>
    
    <!-- Recent Bets and Pending Bets -->
    <div class="dashboard-row">
        <div class="dashboard-section">
            <h3>Recent Bets</h3>
            <div class="bets-table">
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Type</th>
                            <th>Odds</th>
                            <th>Stake</th>
                            <th>Status</th>
                            <th>Return</th>
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
            <a href="/bets" class="btn btn-secondary">View All Bets</a>
        </div>
        
        <div class="dashboard-section">
            <h3>Pending Bets (<?php echo count($pendingBets); ?>)</h3>
            <div class="pending-bets">
                <?php if (count($pendingBets) > 0): ?>
                <?php foreach (array_slice($pendingBets, 0, 5) as $bet): ?>
                <div class="pending-bet-card">
                    <div class="bet-event">
                        <strong><?php echo sanitize($bet['event_name']); ?></strong>
                        <span class="date"><?php echo formatDate($bet['event_date']); ?></span>
                    </div>
                    <div class="bet-odds">
                        <span class="label">Odds:</span>
                        <span><?php echo number_format($bet['odds'], 2); ?></span>
                    </div>
                    <div class="bet-stake">
                        <span class="label">Stake:</span>
                        <span><?php echo formatCurrency($bet['stake'], $userData['currency']); ?></span>
                    </div>
                    <div class="bet-return">
                        <span class="label">Potential Return:</span>
                        <span><?php echo formatCurrency($bet['potential_return'], $userData['currency']); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="empty-state">No pending bets</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="/bets/add" class="btn btn-primary btn-large">+ Add Bet</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bankrollHistory = <?php echo json_encode($bankrollHistory); ?>;
    const bankrollLabels = bankrollHistory.map(function(point) {
        return point.snapshot_date;
    });
    const bankrollValues = bankrollHistory.map(function(point) {
        return Number(point.balance);
    });

    // Bankroll chart
    const bankrollCtx = document.getElementById('bankrollChart');
    if (bankrollCtx) {
        const bankrollGradient = bankrollCtx.getContext('2d').createLinearGradient(0, 0, 0, 260);
        bankrollGradient.addColorStop(0, 'rgba(0, 208, 132, 0.35)');
        bankrollGradient.addColorStop(1, 'rgba(0, 208, 132, 0.02)');

        new Chart(bankrollCtx, {
            type: 'line',
            data: {
                labels: bankrollLabels,
                datasets: [{
                    label: 'Bankroll',
                    data: bankrollValues,
                    borderColor: '#00d084',
                    backgroundColor: bankrollGradient,
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.08)' }
                    },
                    y: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.08)' }
                    }
                }
            }
        });
    }
    
    // Sport chart
    const sportCtx = document.getElementById('sportChart');
    if (sportCtx) {
        const profitBySport = <?php echo json_encode($profitBySport); ?>;
        new Chart(sportCtx, {
            type: 'bar',
            data: {
                labels: profitBySport.map(function(row) {
                    return row.sport_name || row.bookmaker_name || 'Unknown';
                }),
                datasets: [{
                    data: profitBySport.map(function(row) {
                        return Number(row.profit_loss || row.profit || 0);
                    }),
                    backgroundColor: profitBySport.map(function(row) {
                        return Number(row.profit_loss || row.profit || 0) >= 0 ? '#00d084' : '#ff4757';
                    }),
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.08)' }
                    },
                    y: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.08)' }
                    }
                }
            }
        });
    }
});

// Calendar rendering
document.addEventListener('DOMContentLoaded', function() {
    const dailySummary = <?php echo json_encode($dailySummary ?? []); ?>;
    const betsByDate = <?php echo json_encode($betsByDate ?? []); ?>;
    const calendarEl = document.getElementById('miniCalendar');
    const modal = document.createElement('div');
    modal.id = 'calendarModal';
    modal.className = 'modal';
    modal.style.display = 'none';
    modal.innerHTML = `
        <div class="modal-content small">
            <div class="modal-header"><h4 id="modalTitle"></h4><button class="modal-close" onclick="closeCalendarModal()">&times;</button></div>
            <div class="modal-body" id="modalBody"></div>
        </div>`;
    document.body.appendChild(modal);

    // Tooltip for calendar day hover
    const calendarTooltip = document.createElement('div');
    calendarTooltip.id = 'calendarTooltip';
    calendarTooltip.style.position = 'absolute';
    calendarTooltip.style.display = 'none';
    calendarTooltip.style.pointerEvents = 'none';
    calendarTooltip.className = 'calendar-tooltip';
    document.body.appendChild(calendarTooltip);

    function showCalendarTooltip(e, key) {
        const data = dailySummary[key];
        const bets = betsByDate[key] || [];
        let html = '';
        html += `<div class="tt-row"><strong>${bets.length} bet${bets.length !== 1 ? 's' : ''}</strong></div>`;
        if (data && typeof data.profit_loss !== 'undefined') {
            const pl = Number(data.profit_loss || 0);
            html += `<div class="tt-row">P/L: <span class="${pl>=0? 'positive' : 'negative'}">${formatCurrency(pl, '<?php echo $userData['currency']; ?>')}</span></div>`;
        }
        calendarTooltip.innerHTML = html;
        calendarTooltip.style.display = 'block';
        moveCalendarTooltip(e);
    }

    function moveCalendarTooltip(e) {
        const tt = calendarTooltip;
        if (!tt || tt.style.display === 'none') return;
        const pad = 12;
        let x = e.pageX + pad;
        let y = e.pageY + pad;
        const rect = tt.getBoundingClientRect();
        if (x + rect.width > window.pageXOffset + document.documentElement.clientWidth) x = e.pageX - rect.width - pad;
        if (y + rect.height > window.pageYOffset + document.documentElement.clientHeight) y = e.pageY - rect.height - pad;
        tt.style.left = x + 'px';
        tt.style.top = y + 'px';
    }

    function hideCalendarTooltip() {
        calendarTooltip.style.display = 'none';
    }

    // Calendar state: currently displayed month
    let displayed = new Date();

    function renderWeekdayHeaders() {
        const headersEl = document.querySelector('.weekday-headers');
        if (!headersEl) return;
        const weekdayNames = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
        headersEl.innerHTML = '';
        weekdayNames.forEach(n => {
            const el = document.createElement('div');
            el.className = 'weekday-header';
            el.textContent = n;
            headersEl.appendChild(el);
        });
    }

    function startOfCalendarGrid(year, month) {
        const first = new Date(year, month, 1);
        const weekday = (first.getDay() + 6) % 7; // Monday=0
        const start = new Date(first);
        start.setDate(first.getDate() - weekday);
        return start;
    }

    function renderCalendar() {
        if (!calendarEl) return;
        calendarEl.innerHTML = '';
        const year = displayed.getFullYear();
        const month = displayed.getMonth();
        const firstOfMonth = new Date(year, month, 1);
        const lastOfMonth = new Date(year, month + 1, 0);
        const monthLabel = firstOfMonth.toLocaleString(undefined, { month: 'long', year: 'numeric' });
        document.getElementById('calMonthLabel').textContent = monthLabel;

        const start = startOfCalendarGrid(year, month);
        const totalDays = Math.ceil(( (lastOfMonth - start) / 86400000 + 1) / 7) * 7;

        for (let i = 0; i < totalDays; i++) {
            const d = new Date(start);
            d.setDate(start.getDate() + i);
            const key = d.toISOString().slice(0,10);
            const dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-day';
            if (d.getMonth() !== month) dayDiv.classList.add('muted');
            const amount = dailySummary[key] ? Number(dailySummary[key].total_staked || 0) : 0;
            const profit = dailySummary[key] ? Number(dailySummary[key].profit_loss || 0) : null;
            if (dailySummary[key]) {
                dayDiv.classList.add('has-bets');
                if (profit > 0) dayDiv.classList.add('day-win');
                else if (profit < 0) dayDiv.classList.add('day-loss');
                else dayDiv.classList.add('day-neutral');
            }
            dayDiv.innerHTML = `<div class="date">${d.getDate()}</div><div class="amt">${amount > 0 ? formatCurrency(amount, '<?php echo $userData['currency']; ?>') : ''}</div>`;
            dayDiv.dataset.date = key;
            dayDiv.addEventListener('click', function() {
                showBetsForDate(this.dataset.date);
            });
            // Tooltip handlers
            dayDiv.addEventListener('mouseenter', function(e){ showCalendarTooltip(e, key); });
            dayDiv.addEventListener('mousemove', function(e){ moveCalendarTooltip(e); });
            dayDiv.addEventListener('mouseleave', function(){ hideCalendarTooltip(); });

            calendarEl.appendChild(dayDiv);
        }
    }

    // Month navigation
    document.getElementById('calPrev').addEventListener('click', function(){ displayed.setMonth(displayed.getMonth()-1); renderCalendar(); });
    document.getElementById('calNext').addEventListener('click', function(){ displayed.setMonth(displayed.getMonth()+1); renderCalendar(); });

    renderWeekdayHeaders();

    window.closeCalendarModal = function() {
        document.getElementById('calendarModal').style.display = 'none';
    }

    function showBetsForDate(date) {
        const list = betsByDate[date] || [];
        const title = `Bets on ${date}`;
        const body = document.getElementById('modalBody');
        document.getElementById('modalTitle').textContent = title;
        if (list.length === 0) {
            body.innerHTML = '<p>No bets for this day.</p>';
        } else {
            let html = '<table class="table small"><thead><tr><th>Event</th><th>Odds</th><th>Stake</th><th>Status</th><th>Return</th></tr></thead><tbody>';
            list.forEach(b => {
                html += `<tr><td><a href="/bets/${b.id}">${sanitizeClient(b.event_name)}</a></td><td>${Number(b.odds).toFixed(2)}</td><td>${formatCurrency(Number(b.stake), '<?php echo $userData['currency']; ?>')}</td><td>${b.status}</td><td>${b.actual_return ? formatCurrency(Number(b.actual_return), '<?php echo $userData['currency']; ?>') : '-'}</td></tr>`;
            });
            html += '</tbody></table>';
            body.innerHTML = html;
        }
        document.getElementById('calendarModal').style.display = 'flex';
    }

    function sanitizeClient(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    renderCalendar();
});
</script>
