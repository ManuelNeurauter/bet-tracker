/* BetLedger - front-end behaviour */
(function () {
    'use strict';

    const BL = (window.BL = window.BL || {});
    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const csrf = () => $('meta[name="csrf-token"]')?.content || '';

    /* ---------- Formatting ---------- */
    BL.currency = document.body.dataset.currency || 'USD';
    BL.symbol = document.body.dataset.symbol || '$';
    BL.oddsFormat = document.body.dataset.oddsFormat || 'decimal';

    BL.money = function (value, { sign = false, compact = false } = {}) {
        const n = Number(value) || 0;
        const abs = Math.abs(n);
        let body;
        if (compact && abs >= 10000) {
            body = (abs / 1000).toFixed(abs >= 100000 ? 0 : 1) + 'k';
        } else if (compact && Number.isInteger(abs)) {
            body = abs.toLocaleString('en-US');
        } else {
            body = abs.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        const prefix = n < -0.004 ? '-' : (sign && n > 0.004 ? '+' : '');
        return prefix + BL.symbol + body;
    };

    BL.escape = function (str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    };

    function gcd(a, b) { return b ? gcd(b, a % b) : a; }

    BL.odds = function (decimal, format = BL.oddsFormat) {
        const d = Number(decimal);
        if (!d || d <= 1) return '—';
        if (format === 'fractional') {
            const num = Math.round((d - 1) * 100);
            const g = gcd(num, 100);
            return (num / g) + '/' + (100 / g);
        }
        if (format === 'american') {
            return d >= 2 ? '+' + Math.round((d - 1) * 100) : String(Math.round(-100 / (d - 1)));
        }
        return d.toFixed(2);
    };

    BL.localDateKey = function (date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    /* ---------- Toasts ---------- */
    BL.toast = function (type, message) {
        const wrap = $('#toasts');
        if (!wrap) return;
        const el = document.createElement('div');
        el.className = `toast ${type}`;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        const iconName = type === 'error' ? 'circle-alert' : 'circle-check';
        el.innerHTML = `<span class="toast-icon">${BL.icon(iconName, 'icon-sm')}</span>
            <div class="toast-body">${BL.escape(message)}</div>
            <button type="button" class="btn btn-ghost btn-icon btn-sm close" data-toast-close aria-label="Dismiss">${BL.icon('x', 'icon-sm')}</button>`;
        wrap.appendChild(el);
        scheduleToast(el);
    };

    BL.icon = function (name, cls = '') {
        const sprite = document.body.dataset.sprite || '/assets/icons.svg';
        return `<svg class="icon ${cls}" aria-hidden="true"><use href="${sprite}#${name}"></use></svg>`;
    };

    function dismissToast(el) {
        if (!el || el.classList.contains('leaving')) return;
        el.classList.add('leaving');
        el.addEventListener('animationend', () => el.remove(), { once: true });
    }

    function scheduleToast(el) {
        const delay = el.classList.contains('error') ? 8000 : 4500;
        let timer = setTimeout(() => dismissToast(el), delay);
        el.addEventListener('mouseenter', () => clearTimeout(timer));
        el.addEventListener('mouseleave', () => { timer = setTimeout(() => dismissToast(el), 2000); });
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-toast-close]');
        if (btn) dismissToast(btn.closest('.toast'));
    });
    $$('#toasts .toast').forEach(scheduleToast);

    /* ---------- Theme ---------- */
    function currentTheme() {
        return document.documentElement.getAttribute('data-theme') || 'dark';
    }

    function syncThemeLabels() {
        const next = currentTheme() === 'dark' ? 'Light mode' : 'Dark mode';
        $$('[data-theme-label]').forEach((el) => { el.textContent = next; });
        const meta = $('meta[name="theme-color"]');
        if (meta) meta.content = currentTheme() === 'dark' ? '#07090d' : '#f4f5f9';
    }

    BL.setTheme = function (theme) {
        document.documentElement.setAttribute('data-theme', theme);
        try { localStorage.setItem('theme', theme); } catch (e) { /* storage unavailable */ }
        syncThemeLabels();
        $$('input[name="theme"]').forEach((r) => { r.checked = r.value === theme; });
        document.dispatchEvent(new CustomEvent('themechange'));
    };

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-theme-toggle]')) {
            BL.setTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        }
    });
    $$('input[data-theme-choice]').forEach((radio) => {
        radio.checked = radio.value === currentTheme();
        radio.addEventListener('change', () => { if (radio.checked) BL.setTheme(radio.value); });
    });
    syncThemeLabels();

    /* ---------- Settings section nav ---------- */
    const settingsLinks = $$('.settings-nav a[href^="#"]');
    if (settingsLinks.length && 'IntersectionObserver' in window) {
        const setActive = (id) => settingsLinks.forEach((a) => a.classList.toggle('active', a.getAttribute('href') === '#' + id));
        const visible = new Map();
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((en) => visible.set(en.target.id, en.isIntersecting ? en.intersectionRatio : 0));
            const best = [...visible.entries()].sort((a, b) => b[1] - a[1])[0];
            if (best && best[1] > 0) setActive(best[0]);
        }, { threshold: [0, 0.25, 0.5, 0.75, 1], rootMargin: '-80px 0px -35% 0px' });
        settingsLinks.forEach((a) => {
            const section = document.getElementById(a.getAttribute('href').slice(1));
            if (section) observer.observe(section);
        });
        setActive((location.hash || '#profile').slice(1));
    }

    /* ---------- Sidebar (mobile) ---------- */
    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-nav-open]')) document.body.classList.add('nav-open');
        if (e.target.closest('[data-nav-close]')) document.body.classList.remove('nav-open');
    });

    /* ---------- Dropdowns: close on outside click ---------- */
    document.addEventListener('click', (e) => {
        $$('details.dropdown[open]').forEach((d) => {
            if (!d.contains(e.target)) d.removeAttribute('open');
        });
    });

    /* ---------- Keyboard shortcuts ---------- */
    document.addEventListener('keydown', (e) => {
        const tag = (e.target.tagName || '').toLowerCase();
        const typing = ['input', 'textarea', 'select'].includes(tag) || e.target.isContentEditable;
        if (e.key === 'Escape') {
            document.body.classList.remove('nav-open');
            $$('details.dropdown[open]').forEach((d) => d.removeAttribute('open'));
        }
        if (typing || e.metaKey || e.ctrlKey || e.altKey) return;
        if (e.key === '/') {
            const input = $('[data-search-input]');
            if (input && input.offsetParent !== null) {
                e.preventDefault();
                input.focus();
                input.select();
            }
        } else if (e.key === 'n' || e.key === 'N') {
            if (!document.body.dataset.noShortcut) window.location.href = '/bets/add';
        }
    });

    /* ---------- Confirm dialogs ---------- */
    const confirmDialog = $('#confirmDialog');
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form.matches('form[data-confirm]') || form.dataset.confirmed === '1' || !confirmDialog) return;
        e.preventDefault();
        $('[data-confirm-title]', confirmDialog).textContent = form.dataset.confirmTitle || 'Are you sure?';
        $('[data-confirm-text]', confirmDialog).textContent = form.dataset.confirm;
        $('[data-confirm-button]', confirmDialog).textContent = form.dataset.confirmButton || 'Delete';
        confirmDialog.returnValue = '';
        confirmDialog.showModal();
        confirmDialog.addEventListener('close', function onClose() {
            confirmDialog.removeEventListener('close', onClose);
            if (confirmDialog.returnValue === 'confirm') {
                form.dataset.confirmed = '1';
                form.submit();
            }
        });
    });

    // Close any dialog when its backdrop is clicked
    document.addEventListener('click', (e) => {
        if (e.target.tagName === 'DIALOG' && e.target.open) {
            const r = e.target.getBoundingClientRect();
            const inside = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
            if (!inside) e.target.close('cancel');
        }
        const closer = e.target.closest('[data-dialog-close]');
        if (closer) closer.closest('dialog')?.close('cancel');
    });

    /* ---------- Clickable rows ---------- */
    document.addEventListener('click', (e) => {
        const row = e.target.closest('[data-href]');
        if (!row || e.target.closest('a, button, input, select, label, form, summary')) return;
        if (e.metaKey || e.ctrlKey) {
            window.open(row.dataset.href, '_blank');
        } else {
            window.location.href = row.dataset.href;
        }
    });

    /* ---------- Quick settle ---------- */
    BL.settle = async function (betId, status, cashoutAmount = null) {
        try {
            const res = await fetch('/api/bets/quick-settle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf() },
                body: JSON.stringify({ betId, status, cashoutAmount }),
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                BL.toast('error', data.message || 'Could not update the bet.');
            }
        } catch (err) {
            BL.toast('error', 'Could not reach the server. Please try again.');
        }
    };

    /* Result of one leg of a multiple */
    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-leg-settle]');
        if (!btn) return;
        const card = btn.closest('[data-legs-card]');
        btn.closest('.leg-settle').querySelectorAll('button').forEach((b) => { b.disabled = true; });
        try {
            const res = await fetch(`/api/bets/${Number(card.dataset.betId)}/legs/${Number(btn.dataset.legId)}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf() },
                body: JSON.stringify({ status: btn.dataset.legSettle }),
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
                return;
            }
            BL.toast('error', data.message || 'Could not update the selection.');
        } catch (err) {
            BL.toast('error', 'Could not reach the server. Please try again.');
        }
        btn.closest('.leg-settle').querySelectorAll('button').forEach((b) => { b.disabled = false; });
    });

    const cashoutDialog = $('#cashoutDialog');
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-settle]');
        if (!btn) return;
        e.preventDefault();
        const betId = Number(btn.dataset.betId);
        const status = btn.dataset.settle;
        if (status === 'cashout' && cashoutDialog) {
            const form = $('form', cashoutDialog);
            form.reset();
            form.dataset.betId = betId;
            $('[data-cashout-event]', cashoutDialog).textContent = btn.dataset.event || 'this bet';
            $('[data-cashout-stake]', cashoutDialog).textContent = btn.dataset.stake ? BL.money(btn.dataset.stake) : '';
            cashoutDialog.showModal();
            setTimeout(() => $('input[name="amount"]', cashoutDialog).focus(), 50);
            return;
        }
        btn.disabled = true;
        BL.settle(betId, status);
    });

    if (cashoutDialog) {
        $('form', cashoutDialog).addEventListener('submit', (e) => {
            e.preventDefault();
            const form = e.target;
            const amount = parseFloat($('input[name="amount"]', form).value);
            if (isNaN(amount) || amount < 0) {
                BL.toast('error', 'Enter the amount you received.');
                return;
            }
            $('button[type="submit"]', form).disabled = true;
            BL.settle(Number(form.dataset.betId), 'cashout', amount);
        });
    }

    /* ---------- Filters panel ---------- */
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-toggle-filters]');
        if (!btn) return;
        const panel = $('#filtersPanel');
        if (panel) {
            panel.classList.toggle('open');
            btn.setAttribute('aria-expanded', panel.classList.contains('open'));
        }
    });

    // Auto-submit selects that ask for it
    document.addEventListener('change', (e) => {
        if (e.target.matches('[data-autosubmit]')) e.target.form.submit();
    });

    /* ---------- Password fields ---------- */
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-reveal]');
        if (!btn) return;
        const input = btn.parentElement.querySelector('input');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.innerHTML = BL.icon(show ? 'eye' : 'eye', 'icon-sm');
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.classList.toggle('text-accent', show);
    });

    $$('select[data-detect-timezone]').forEach((select) => {
        try {
            const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            if (tz && [...select.options].some((o) => o.value === tz)) select.value = tz;
        } catch (e) { /* keep the default */ }
    });

    $$('[data-strength]').forEach((input) => {
        const meter = document.getElementById(input.dataset.strength);
        input.addEventListener('input', () => {
            const v = input.value;
            let score = 0;
            if (v.length >= 6) score++;
            if (v.length >= 10) score++;
            if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
            if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) score++;
            meter.dataset.score = v ? Math.max(1, score) : 0;
        });
    });

    /* ---------- Colour swatches ---------- */
    $$('[data-swatches]').forEach((wrap) => {
        const input = document.getElementById(wrap.dataset.swatches);
        const preview = document.querySelector('[data-color-preview]');
        const sync = () => {
            $$('.swatch', wrap).forEach((s) => s.classList.toggle('selected', s.dataset.color.toLowerCase() === input.value.toLowerCase()));
            if (preview) preview.style.setProperty('--chip', input.value);
        };
        wrap.addEventListener('click', (e) => {
            const s = e.target.closest('.swatch');
            if (!s) return;
            input.value = s.dataset.color;
            sync();
        });
        input.addEventListener('input', sync);
        sync();
    });

    $$('[data-mirror]').forEach((input) => {
        const target = document.querySelector(input.dataset.mirror);
        const fallback = target ? target.textContent : '';
        input.addEventListener('input', () => { if (target) target.textContent = input.value.trim() || fallback; });
    });

    /* ---------- Charts ---------- */
    const charts = [];

    BL.chartTheme = function () {
        const css = getComputedStyle(document.documentElement);
        const v = (name) => css.getPropertyValue(name).trim();
        return {
            text: v('--text'),
            text2: v('--text-2'),
            text3: v('--text-3'),
            grid: v('--border'),
            surface: v('--surface'),
            accent: v('--accent'),
            win: v('--win'),
            loss: v('--loss'),
            pending: v('--pending'),
            cashout: v('--cashout'),
            void: v('--void'),
            font: v('--font-sans'),
        };
    };

    BL.chartDefaults = function (t) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 500 },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: t.surface,
                    titleColor: t.text,
                    bodyColor: t.text2,
                    borderColor: t.grid,
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 10,
                    boxPadding: 4,
                    titleFont: { family: t.font, weight: '600' },
                    bodyFont: { family: t.font },
                    usePointStyle: true,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: t.text3, font: { family: t.font, size: 11 }, maxRotation: 0, autoSkipPadding: 16 },
                },
                y: {
                    grid: { color: t.grid, drawTicks: false },
                    border: { display: false },
                    ticks: { color: t.text3, font: { family: t.font, size: 11 }, padding: 8, maxTicksLimit: 6 },
                },
            },
        };
    };

    BL.chart = function (canvas, build) {
        if (!canvas || !window.Chart) return null;
        const entry = { canvas, build, instance: null };
        const render = () => {
            if (entry.instance) entry.instance.destroy();
            const t = BL.chartTheme();
            entry.instance = new Chart(canvas.getContext('2d'), build(t, canvas));
        };
        entry.render = render;
        render();
        charts.push(entry);
        return entry;
    };

    BL.gradient = function (canvas, color, height) {
        const ctx = canvas.getContext('2d');
        const g = ctx.createLinearGradient(0, 0, 0, height || canvas.clientHeight || 260);
        g.addColorStop(0, color.startsWith('#') ? color + '55' : color);
        g.addColorStop(1, color.startsWith('#') ? color + '00' : 'transparent');
        return g;
    };

    document.addEventListener('themechange', () => charts.forEach((c) => c.render()));

    /* ---------- Competition loader ---------- */
    BL.loadCompetitions = function (sportSelect, competitionSelect, selectedId) {
        const sportId = sportSelect.value;
        competitionSelect.innerHTML = '<option value="">No competition</option>';
        competitionSelect.disabled = !/^\d+$/.test(sportId);
        if (competitionSelect.disabled) return;
        fetch(`/api/competitions?sport_id=${encodeURIComponent(sportId)}`)
            .then((r) => r.json())
            .then((res) => {
                (res.competitions || []).forEach((c) => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.country ? `${c.name} · ${c.country}` : c.name;
                    if (String(c.id) === String(selectedId)) opt.selected = true;
                    competitionSelect.appendChild(opt);
                });
                if (!res.competitions || !res.competitions.length) {
                    competitionSelect.innerHTML = '<option value="">No competitions for this sport</option>';
                }
            })
            .catch(() => {});
    };

    /* ---------- Bet form ---------- */
    function initBetForm(form) {
        const f = (name) => form.elements[name];
        const odds = f('odds');
        const stake = f('stake');
        const tax = f('tax_amount');
        const bookmaker = f('bookmaker_id');
        const sport = f('sport_id');
        const competition = f('competition_id');
        const taxRates = JSON.parse(form.dataset.taxRates || '{}');
        const balances = JSON.parse(form.dataset.balances || '{}');
        let taxTouched = form.dataset.taxTouched === '1';

        const out = (key) => form.querySelector(`[data-slip="${key}"]`) || document.querySelector(`[data-slip="${key}"]`);
        const set = (key, value) => { const el = out(key); if (el) el.textContent = value; };

        function status() {
            const checked = form.querySelector('input[name="status"]:checked');
            return checked ? checked.value : 'pending';
        }

        function autoTax() {
            if (taxTouched) return;
            const rate = parseFloat(taxRates[bookmaker.value] || 0);
            const payout = (parseFloat(odds.value) || 0) * (parseFloat(stake.value) || 0);
            tax.value = rate > 0 && payout > 0 ? (payout * rate / 100).toFixed(2) : '0.00';
            const hint = form.querySelector('[data-tax-hint]');
            if (hint) hint.textContent = rate > 0 ? `${rate}% of the payout at this bookmaker` : 'No tax set for this bookmaker';
        }

        function update() {
            const legs = updateLegs() || [];
            const o = parseFloat(odds.value) || 0;
            const s = parseFloat(stake.value) || 0;
            const t = parseFloat(tax.value) || 0;
            const payout = o * s;
            const net = Math.max(0, payout - t);
            const st = status();

            set('event', f('event_name').value.trim() || (legs.length ? `${legs.length}-leg ${betType.selectedOptions[0].textContent.toLowerCase()}` : 'Your event'));
            set('selection', f('selection').value.trim() || (legs.length ? `${legs.length} selection${legs.length === 1 ? '' : 's'}` : 'Pick a selection'));
            set('odds', o > 1 ? BL.odds(o) : '—');
            set('implied', o > 1 ? (100 / o).toFixed(1) + '%' : '—');
            set('stake', BL.money(s));
            set('payout', BL.money(payout));
            set('tax', t > 0 ? '−' + BL.money(t) : BL.money(0));

            let total = net;
            let label = 'Potential return';
            if (st === 'lost') { total = 0; label = 'Return'; }
            if (st === 'void') { total = s; label = 'Refund'; }
            if (st === 'won') {
                const manual = parseFloat(f('actual_return').value);
                total = isNaN(manual) ? net : manual;
                label = 'Return';
            }
            if (st === 'cashout') {
                const c = parseFloat(f('cashout_amount').value);
                total = isNaN(c) ? 0 : c;
                label = 'Cashed out';
            }
            set('total-label', label);
            set('total', BL.money(total));
            const profit = total - s;
            const profitEl = out('profit');
            if (profitEl) {
                profitEl.textContent = (st === 'pending' ? 'Profit if it wins ' : 'Profit ') + BL.money(profit, { sign: true });
                profitEl.className = 'slip-profit ' + (profit > 0.004 ? 'text-win' : profit < -0.004 ? 'text-loss' : 'text-muted');
            }

            const oddsHelp = form.querySelector('[data-odds-help]');
            if (oddsHelp) {
                oddsHelp.textContent = o > 1
                    ? `Fractional ${BL.odds(o, 'fractional')} · American ${BL.odds(o, 'american')} · ${(100 / o).toFixed(1)}% implied`
                    : 'Decimal odds, e.g. 2.10';
            }

            form.querySelectorAll('[data-show-for]').forEach((el) => {
                el.hidden = !el.dataset.showFor.split(' ').includes(st);
            });

            const balanceHint = form.querySelector('[data-balance-hint]');
            if (balanceHint) {
                balanceHint.textContent = bookmaker.value && balances[bookmaker.value] !== undefined
                    ? `Balance ${BL.money(balances[bookmaker.value])}`
                    : 'Where you placed the bet';
            }
        }

        /* Legs of a multiple */
        const legsSection = form.querySelector('[data-legs]');
        const legsList = legsSection?.querySelector('[data-legs-list]');
        const legRules = JSON.parse(legsSection?.dataset.legRules || '{}');
        const betType = f('bet_type');
        const legRows = () => (legsList ? $$('[data-leg]', legsList) : []);
        const legValue = (row, name) => row.querySelector(`[name$="[${name}]"]`)?.value.trim() || '';

        function filledLegs() {
            return legRows()
                .map((row) => ({
                    event: legValue(row, 'event_name'),
                    selection: legValue(row, 'selection'),
                    odds: parseFloat(legValue(row, 'odds')) || 0,
                    status: legValue(row, 'status') || 'pending',
                }))
                .filter((l) => l.event || l.selection || l.odds);
        }

        function combinedOdds(legs) {
            if (!legs.length || legs.some((l) => l.odds < 1.01 && l.status !== 'void')) return 0;
            return legs.reduce((acc, l) => acc * (l.status === 'void' ? 1 : l.odds), 1);
        }

        function renumberLegs() {
            legRows().forEach((row, i) => {
                $('[data-leg-num]', row).textContent = i + 1;
                row.querySelectorAll('[data-name], [name^="legs["]').forEach((input) => {
                    const name = input.dataset.name || input.name.replace(/^legs\[\d+\]\[(\w+)\]$/, '$1');
                    input.name = `legs[${i}][${name}]`;
                    input.id = `leg_${i}_${name === 'event_name' ? 'event' : name}`;
                    input.previousElementSibling?.setAttribute('for', input.id);
                    delete input.dataset.name;
                });
                $('[data-leg-remove]', row).setAttribute('aria-label', `Remove selection ${i + 1}`);
            });
        }

        function addLeg(focus = true) {
            const tpl = legsSection.querySelector('[data-leg-template]');
            legsList.appendChild(tpl.content.cloneNode(true));
            renumberLegs();
            if (focus) legRows().at(-1).querySelector('input')?.focus();
        }

        // Keep the bet's odds in step with the legs until the user types their own (e.g. a boost)
        const legsAtLoad = filledLegs();
        let oddsTouched = odds.value !== '' && Math.abs(combinedOdds(legsAtLoad) - parseFloat(odds.value)) > 0.001;
        odds.addEventListener('input', () => { oddsTouched = true; });

        function updateLegs() {
            if (!legsSection) return;
            const rule = legRules[betType.value];
            const multi = !!rule;
            legsSection.hidden = !multi;
            form.querySelectorAll('[data-single-only]').forEach((el) => { el.hidden = multi; });
            form.querySelectorAll('[data-multi-only]').forEach((el) => { el.hidden = !multi; });

            const legs = multi ? filledLegs() : [];
            f('event_name').required = !(multi && legs.length);
            f('selection').required = !(multi && legs.length);
            odds.required = !(multi && rule.chained && legs.length);

            legRows().forEach((row) => {
                const sel = row.querySelector('select');
                if (sel) sel.dataset.legStatus = sel.value;
            });

            if (multi) {
                const hint = legsSection.querySelector('[data-legs-hint]');
                const article = /^[AEIOU]/.test(rule.label) ? 'An' : 'A';
                const need = rule.min === rule.max ? `exactly ${rule.min}` : `at least ${rule.min}`;
                const lost = legs.some((l) => l.status === 'lost');
                hint.textContent = rule.chained && lost
                    ? `A selection lost, so this ${rule.label.toLowerCase()} is lost.`
                    : `${article} ${rule.label.toLowerCase()} has ${need} selections. Each keeps its own odds and result.`;
                hint.classList.toggle('text-loss', rule.chained && lost);
                $('[data-leg-add]', legsSection).hidden = rule.max !== null && legRows().length >= rule.max;
                legRows().forEach((row) => { $('[data-leg-remove]', row).disabled = legRows().length <= 1; });

                const combined = rule.chained ? combinedOdds(legs) : 0;
                const box = legsSection.querySelector('[data-legs-combined]');
                box.hidden = !combined;
                $('[data-legs-odds]', box).textContent = combined ? BL.odds(combined) : '—';
                const differs = combined && Math.abs(combined - (parseFloat(odds.value) || 0)) > 0.001;
                if (combined && !oddsTouched && differs) {
                    odds.value = combined.toFixed(3).replace(/\.?0+$/, '');
                    autoTax();
                }
                $('[data-legs-use]', box).hidden = !(combined && Math.abs(combined - (parseFloat(odds.value) || 0)) > 0.001);
            }

            const slipLegs = document.querySelector('[data-slip-legs]');
            if (slipLegs) {
                slipLegs.hidden = !legs.length;
                slipLegs.innerHTML = legs.map((l) => `<li class="${l.status}"><span>${BL.escape(l.selection || l.event || 'Selection')}</span><strong class="mono">${l.odds ? BL.escape(BL.odds(l.odds)) : '—'}</strong></li>`).join('');
            }
            return legs;
        }

        if (legsSection) {
            legsSection.querySelector('[data-leg-add]').addEventListener('click', () => { addLeg(); update(); });
            legsList.addEventListener('click', (e) => {
                const btn = e.target.closest('[data-leg-remove]');
                if (!btn || legRows().length <= 1) return;
                btn.closest('[data-leg]').remove();
                renumberLegs();
                update();
            });
            legsSection.querySelector('[data-legs-use]').addEventListener('click', () => {
                oddsTouched = false;
                update();
            });
            betType.addEventListener('change', () => {
                const rule = legRules[betType.value];
                if (rule) {
                    while (legRows().length < rule.min) addLeg(false);
                }
            });
        }

        tax.addEventListener('input', () => { taxTouched = true; });
        form.querySelector('[data-tax-reset]')?.addEventListener('click', () => { taxTouched = false; autoTax(); update(); });

        ['odds', 'stake'].forEach((n) => f(n).addEventListener('input', () => { autoTax(); update(); }));
        bookmaker.addEventListener('change', () => { autoTax(); update(); });
        form.addEventListener('input', update);
        form.addEventListener('change', update);

        sport.addEventListener('change', () => BL.loadCompetitions(sport, competition, null));
        if (sport.value) BL.loadCompetitions(sport, competition, competition.dataset.selected);
        else competition.disabled = true;

        if (!taxTouched) autoTax();
        update();

        // Stop the "N" shortcut while filling in the form
        document.body.dataset.noShortcut = '1';
    }

    $$('form[data-bet-form]').forEach(initBetForm);

    /* ---------- Calendar ---------- */
    function initCalendar(root) {
        const data = JSON.parse($('#calendarData').textContent || '{}');
        const summary = data.summary || {};
        const bets = data.bets || {};
        const grid = $('[data-cal-grid]', root);
        const label = $('[data-cal-label]', root);
        const dialog = $('#dayDialog');
        let shown = new Date();
        shown.setDate(1);

        const maxAbs = Math.max(1, ...Object.values(summary).map((d) => Math.abs(d.profit_loss || 0)));

        function render() {
            grid.innerHTML = '';
            ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].forEach((d) => {
                const el = document.createElement('div');
                el.className = 'dow';
                el.textContent = d;
                grid.appendChild(el);
            });
            const year = shown.getFullYear();
            const month = shown.getMonth();
            label.textContent = shown.toLocaleString('en-US', { month: 'long', year: 'numeric' });
            const first = new Date(year, month, 1);
            const start = new Date(first);
            start.setDate(1 - ((first.getDay() + 6) % 7));
            const last = new Date(year, month + 1, 0);
            const cells = Math.ceil((((last - start) / 86400000) + 1) / 7) * 7;
            const todayKey = BL.localDateKey(new Date());

            for (let i = 0; i < cells; i++) {
                const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
                const key = BL.localDateKey(d);
                const s = summary[key];
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'cal-day';
                btn.dataset.date = key;
                if (d.getMonth() !== month) btn.classList.add('muted');
                if (key === todayKey) btn.classList.add('today');
                let inner = `<span class="d">${d.getDate()}</span>`;
                if (s) {
                    const pl = Number(s.profit_loss || 0);
                    const staked = Number(s.total_staked || 0);
                    const settled = s.bet_count - s.pending_count;
                    const shortMoney = (v) => {
                        const abs = Math.abs(v);
                        return abs >= 1000 ? (abs / 1000).toFixed(1) + 'k' : String(Math.round(abs));
                    };
                    if (settled > 0 && Math.abs(pl) > 0.004) {
                        btn.classList.add(pl > 0 ? 'win' : 'loss');
                        btn.style.setProperty('--i', (0.25 + 0.75 * Math.min(1, Math.abs(pl) / maxAbs)).toFixed(2));
                        inner += `<span class="pl">${BL.money(pl, { sign: true, compact: true })}</span><span class="pl-short">${pl > 0 ? '+' : '−'}${shortMoney(pl)}</span>`;
                    } else if (s.pending_count > 0) {
                        // Upcoming or open: show how much is riding on the day
                        btn.classList.add('open');
                        inner += `<span class="pl">${BL.money(staked, { compact: true })}</span><span class="pl-short">${shortMoney(staked)}</span>`;
                    } else {
                        inner += `<span class="pl text-muted">±0</span><span class="pl-short text-muted">±0</span>`;
                    }
                    const staker = `${BL.money(staked, { compact: true })} staked`;
                    inner += `<span class="n">${s.pending_count > 0 && settled === 0 ? `${s.pending_count} open` : staker}</span>`;
                    btn.setAttribute('aria-label', `${key}: ${s.bet_count} bet${s.bet_count === 1 ? '' : 's'}, ${BL.money(staked)} staked${settled > 0 ? ', ' + BL.money(pl, { sign: true }) : ''}`);
                } else {
                    btn.classList.add('empty-day');
                    btn.tabIndex = -1;
                }
                btn.innerHTML = inner;
                grid.appendChild(btn);
            }
        }

        grid.addEventListener('click', (e) => {
            const day = e.target.closest('.cal-day');
            if (!day || !bets[day.dataset.date] || !dialog) return;
            const list = bets[day.dataset.date];
            const date = new Date(day.dataset.date + 'T12:00:00');
            $('[data-day-title]', dialog).textContent = date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
            const s = summary[day.dataset.date] || {};
            $('[data-day-sub]', dialog).textContent = `${list.length} bet${list.length === 1 ? '' : 's'} · staked ${BL.money(s.total_staked || 0)} · P&L ${BL.money(s.profit_loss || 0, { sign: true })}`;
            $('[data-day-list]', dialog).innerHTML = list.map((b) => {
                const profit = b.status === 'pending' ? null : (b.status === 'void' ? 0 : Number(b.actual_return || 0) - Number(b.stake));
                const tone = profit === null ? 'text-muted' : profit > 0 ? 'text-win' : profit < 0 ? 'text-loss' : 'text-muted';
                return `<a class="list-item" href="/bets/${b.id}">
                    <div class="grow"><div class="title">${BL.escape(b.event_name)}</div>
                    <div class="sub">${BL.escape(b.selection)} · @ ${BL.escape(b.odds_display)} · ${BL.money(b.stake)}</div></div>
                    <div class="end"><span class="pill pill-${b.status}"><span class="pill-dot"></span>${b.status === 'cashout' ? 'Cashed out' : b.status.charAt(0).toUpperCase() + b.status.slice(1)}</span>
                    <div class="sub ${tone}" style="margin-top:4px;font-weight:600">${profit === null ? 'open' : BL.money(profit, { sign: true })}</div></div>
                </a>`;
            }).join('');
            dialog.showModal();
        });

        $('[data-cal-prev]', root).addEventListener('click', () => { shown.setMonth(shown.getMonth() - 1); render(); });
        $('[data-cal-next]', root).addEventListener('click', () => { shown.setMonth(shown.getMonth() + 1); render(); });
        $('[data-cal-today]', root)?.addEventListener('click', () => { shown = new Date(); shown.setDate(1); render(); });
        render();
    }

    const cal = $('[data-calendar]');
    if (cal) initCalendar(cal);
})();
