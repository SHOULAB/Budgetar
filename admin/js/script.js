(function () {
    'use strict';

    const REFRESH_MS = 30000;

    // Keys of currently displayed rows
    let knownRegistered = [];
    let knownLogins     = [];

    function rowKey(username, dateStr) {
        return username + '|' + dateStr;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr.replace(' ', 'T'));
        if (isNaN(d)) return dateStr;
        const p = n => String(n).padStart(2, '0');
        return `${p(d.getDate())}.${p(d.getMonth() + 1)}.${d.getFullYear()} ${p(d.getHours())}:${p(d.getMinutes())}`;
    }

    function buildRow(user, dateField) {
        const tr = document.createElement('tr');

        const tdUser    = document.createElement('td');
        const infoUser  = document.createElement('div');
        infoUser.className = 'info-user';

        const avatar = document.createElement('div');
        avatar.className = 'info-avatar';
        avatar.textContent = user.username.charAt(0).toUpperCase();

        const infoText = document.createElement('div');

        const unameEl = document.createElement('div');
        unameEl.className = 'info-username';
        unameEl.textContent = user.username;

        const emailEl = document.createElement('div');
        emailEl.className = 'info-email';
        emailEl.textContent = user.email;

        infoText.appendChild(unameEl);
        infoText.appendChild(emailEl);
        infoUser.appendChild(avatar);
        infoUser.appendChild(infoText);
        tdUser.appendChild(infoUser);

        const tdDate = document.createElement('td');
        tdDate.className = 'info-date';
        tdDate.textContent = formatDate(user[dateField]);

        tr.appendChild(tdUser);
        tr.appendChild(tdDate);
        return tr;
    }

    function initKnownRows() {
        document.querySelectorAll('#tbody-registered tr').forEach(tr => {
            const u = tr.querySelector('.info-username')?.textContent ?? '';
            const d = tr.querySelector('.info-date')?.textContent ?? '';
            knownRegistered.push(rowKey(u, d));
        });
        document.querySelectorAll('#tbody-logins tr').forEach(tr => {
            const u = tr.querySelector('.info-username')?.textContent ?? '';
            const d = tr.querySelector('.info-date')?.textContent ?? '';
            knownLogins.push(rowKey(u, d));
        });
    }

    function updateTable(tbodyId, entries, dateField, knownKeys) {
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return knownKeys;

        const newEntries = entries.filter(
            u => !knownKeys.includes(rowKey(u.username, formatDate(u[dateField])))
        );

        if (newEntries.length > 0) {
            // Insert in reverse so topmost new entry ends up first
            [...newEntries].reverse().forEach(user => {
                const tr = buildRow(user, dateField);
                tr.classList.add('row-new');
                tbody.insertBefore(tr, tbody.firstChild);
            });
            // Trim to 10 rows
            while (tbody.rows.length > 10) tbody.deleteRow(tbody.rows.length - 1);
        }

        // Return updated keys reflecting current DOM state
        return Array.from(tbody.querySelectorAll('tr')).map(tr => {
            const u = tr.querySelector('.info-username')?.textContent ?? '';
            const d = tr.querySelector('.info-date')?.textContent ?? '';
            return rowKey(u, d);
        });
    }

    function updateStats(stats, dbLatency, latencyLevel) {
        const cards = document.querySelectorAll('.stat-card');

        const setVal = (card, text) => {
            const el = card?.querySelector('.stat-card-value');
            if (el && !el.querySelector('.status-dot')) el.textContent = text;
        };

        if (cards[0]) setVal(cards[0], stats.total_users.toLocaleString());
        if (cards[1]) setVal(cards[1], stats.total_budget_count.toLocaleString());
        if (cards[2]) setVal(cards[2], stats.total_transactions.toLocaleString());
        if (cards[3]) setVal(cards[3], stats.tx_this_month.toLocaleString());

        // System status card (contains .status-dot span)
        if (cards[4]) {
            const el = cards[4].querySelector('.stat-card-value');
            if (el) {
                const dot = el.querySelector('.status-dot') || document.createElement('span');
                dot.className = 'status-dot';
                el.className = `stat-card-value stat-status--${latencyLevel}`;
                el.textContent = `${dbLatency} ms`;
                el.insertBefore(dot, el.firstChild);
            }
        }
    }

    async function refresh() {
        try {
            const resp = await fetch('dashboard_data.php', { credentials: 'same-origin' });
            if (!resp.ok) return;
            const data = await resp.json();
            if (data.error) return;

            knownRegistered = updateTable('tbody-registered', data.recent_registered, 'created_at', knownRegistered);
            knownLogins     = updateTable('tbody-logins',     data.recent_logins,     'last_login',  knownLogins);
            updateStats(data.stats, data.db_latency_ms, data.latency_level);
        } catch (_) { /* silent — don't disrupt the UI */ }
    }

    document.addEventListener('DOMContentLoaded', () => {
        initKnownRows();
        setInterval(refresh, REFRESH_MS);
    });
}());
