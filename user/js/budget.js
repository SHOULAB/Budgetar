// ─── Budget search ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('budgetSearchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.budget-card');
        const noResults = document.getElementById('budgetNoResults');
        const grid = document.querySelector('.budgets-grid');
        let visibleCount = 0;

        cards.forEach(function (card) {
            const title = card.dataset.budgetTitle || '';
            const matches = title.includes(query);
            card.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        if (noResults) noResults.style.display = visibleCount === 0 ? 'flex' : 'none';
        if (grid) grid.style.display = visibleCount === 0 ? 'none' : '';
    });
});

// ─── Modal helpers ───────────────────────────────────────────────────────────

function openAddModal() {
    document.getElementById('addModal').classList.add('modal-open');
    document.body.style.overflow = 'hidden';
    // Reset recurring section state when opening fresh
    resetRecurringSection();
}

function closeAddModal() {
    document.getElementById('addModal').classList.remove('modal-open');
    document.body.style.overflow = 'auto';
}

function openEditModal(budget) {
    document.getElementById('edit_budget_id').value         = budget.id;
    document.getElementById('edit_budget_name').value       = budget.budget_name;
    document.getElementById('edit_budget_amount').value     = budget.budget_amount;
    document.getElementById('edit_warning_threshold').value = budget.warning_threshold;

    const editSection   = document.getElementById('edit_recurring_section');
    const editContainer = document.getElementById('edit_recurring_days_container');
    const editHidden    = document.getElementById('edit_recurring_days');

    if (budget.recurring_days && budget.recurring_days !== '') {
        const selectedDays = budget.recurring_days.split(',').map(Number);
        editContainer.querySelectorAll('.day-pill').forEach(pill => {
            pill.classList.toggle('selected', selectedDays.includes(parseInt(pill.dataset.day)));
        });
        editHidden.value = budget.recurring_days;
        editSection.style.display = 'block';
    } else {
        editContainer.querySelectorAll('.day-pill').forEach(p => p.classList.remove('selected'));
        editHidden.value = '';
        editSection.style.display = 'none';
    }

    document.getElementById('editModal').classList.add('modal-open');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('modal-open');
    document.body.style.overflow = 'auto';
}

// ─── Delete confirmation modal ─────────────────────────────────────────────────

function closeBudgetDeleteConfirm() {
    const modal = document.getElementById('budgetDeleteConfirmModal');
    if (!modal) return;
    modal.classList.remove('modal-open');
    document.body.style.overflow = 'auto';
    setTimeout(() => modal.remove(), 250);
}

function showBudgetDeleteConfirm(form, isGroup) {
    let existing = document.getElementById('budgetDeleteConfirmModal');
    if (existing) existing.remove();

    const T = window._i18n ? (window._i18n.T[window._i18n.lang] || window._i18n.T['lv']) : null;

    const message = isGroup
        ? (T && T['budget.delete.confirm.group']  ? T['budget.delete.confirm.group']  : 'Vai tiešām vēlies dzēst visus 4 cetur'+'kšņu budžetus? Šī darbība nevar tikt atsaukta.')
        : (T && T['budget.delete.confirm.single'] ? T['budget.delete.confirm.single'] : 'Vai tiešām vēlies dzēst šo budžestu? Šī darbība nevar tikt atsaukta.');

    const modal = document.createElement('div');
    modal.id = 'budgetDeleteConfirmModal';
    modal.className = 'modal modal-open';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">${T && T['budget.delete.confirm.title'] ? T['budget.delete.confirm.title'] : 'Apstiprīnāt dzēšanu'}</h2>
                <button type="button" class="modal-close" aria-label="Aizvērt">✕</button>
            </div>
            <div class="modal-body">
                <p>${message}</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="budgetDeleteCancelBtn">${T && T['budget.delete.cancel'] ? T['budget.delete.cancel'] : 'Atcelt'}</button>
                <button type="button" class="btn btn-danger" id="budgetDeleteConfirmBtn">
                    <i class="fa-solid fa-trash"></i> ${T && T['budget.delete.btn'] ? T['budget.delete.btn'] : 'Dzēst'}
                </button>
            </div>
        </div>`;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';

    modal.querySelector('.modal-close').addEventListener('click', closeBudgetDeleteConfirm);
    modal.querySelector('#budgetDeleteCancelBtn').addEventListener('click', closeBudgetDeleteConfirm);
    modal.querySelector('#budgetDeleteConfirmBtn').addEventListener('click', function () {
        closeBudgetDeleteConfirm();
        form.submit();
    });
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeBudgetDeleteConfirm();
    });
}

// Close modals when clicking outside
window.onclick = function (event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('modal-open');
        document.body.style.overflow = 'auto';
    }
};

// Close with ESC key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeAddModal();
        closeEditModal();
    }
});


// ─── Quarter card switching ───────────────────────────────────────────────────

function switchQuarterTab(card, q) {
    const quarters = JSON.parse(card.dataset.quartersJson);
    const qd = quarters[q];
    if (!qd) return;

    // Update active tab button
    card.querySelectorAll('.quarter-nav-btn').forEach(function (btn) {
        btn.classList.toggle('active', btn.dataset.q === q);
    });

    // Update period text
    const periodEl = card.querySelector('.qcard-period');
    if (periodEl) {
        periodEl.textContent = qFmtDate(qd.start_date) + ' \u2013 ' + qFmtDate(qd.end_date);
    }

    // Determine status
    const now    = Date.now();
    const start  = new Date(qd.start_date + 'T00:00:00').getTime();
    const end    = new Date(qd.end_date   + 'T23:59:59').getTime();
    const pct    = Math.min(parseFloat(qd.percentage), 100);
    const thresh = parseFloat(qd.warning_threshold);

    let statusClass, statusText, progressClass;
    if (start > now) {
        statusClass   = 'status-upcoming';
        statusText    = qGetT('budget.status.upcoming', 'Gaid\u0101mais');
        progressClass = 'progress-safe';
    } else if (end < now) {
        statusClass   = 'status-expired';
        statusText    = qGetT('budget.status.expired', 'Beidzies');
        progressClass = 'progress-danger';
    } else if (pct >= thresh) {
        statusClass   = 'status-warning';
        statusText    = qGetT('budget.status.warning', 'Br\u012Bdin\u0101jums');
        progressClass = 'progress-warning';
    } else {
        statusClass   = 'status-active';
        statusText    = qGetT('budget.status.active', 'Akt\u012Bvs');
        progressClass = 'progress-safe';
    }

    // Update status badge
    const statusEl = card.querySelector('.qcard-status');
    if (statusEl) {
        statusEl.className   = 'budget-status qcard-status ' + statusClass;
        statusEl.textContent = statusText;
    }

    // Update amounts
    const budgetNumEl    = card.querySelector('.qcard-budget-num');
    const spentNumEl     = card.querySelector('.qcard-spent-num');
    const remainingNumEl = card.querySelector('.qcard-remaining-num');
    if (budgetNumEl)    budgetNumEl.textContent    = qFmtAmt(qd.budget_amount);
    if (spentNumEl)     spentNumEl.textContent     = qFmtAmt(qd.spent);
    if (remainingNumEl) remainingNumEl.textContent = qFmtAmt(qd.remaining);

    // Update progress bar
    const bar = card.querySelector('.qcard-progress-bar');
    if (bar) {
        bar.style.width = pct + '%';
        bar.className   = 'budget-progress-bar qcard-progress-bar ' + progressClass;
    }

    // Update percentage text
    const pctEl = card.querySelector('.qcard-pct-num');
    if (pctEl) pctEl.textContent = pct.toFixed(1);

    // Store active quarter on card
    card.dataset.activeQ = q;
}

function openEditModalFromCard(btn) {
    const card     = btn.closest('.budget-card-quarterly');
    const quarters = JSON.parse(card.dataset.quartersJson);
    const activeQ  = card.dataset.activeQ;
    const budget   = quarters[activeQ] || quarters[Object.keys(quarters)[0]];
    openEditModal(budget);
}

function qFmtDate(ymd) {
    if (!ymd) return '';
    const p = ymd.split('-');
    return p[2] + '.' + p[1] + '.' + p[0];
}

function qFmtAmt(n) {
    return (parseFloat(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function qGetT(key, fallback) {
    if (window._i18n && window._i18n.T) {
        var lang = window._i18n.lang || 'lv';
        var T    = window._i18n.T[lang] || window._i18n.T['lv'];
        if (T && T[key]) return T[key];
    }
    return fallback;
}


// ─── Recurring weekday feature ────────────────────────────────────────────────

/**
 * Day index mapping (JS Date.getDay(): 0 = Sunday … 6 = Saturday)
 */
const DAY_NAMES_SHORT = ['Sv', 'P', 'O', 'T', 'C', 'Pk', 'S'];

/**
 * Given an array of day indices (0–6), calculates the start and end dates
 * for the current (or next) weekly period that contains all selected days.
 *
 * Strategy: anchor to the Monday of the current week. Map each selected day
 * into that week. If all mapped dates are in the past, shift the entire set
 * forward by 7 days so we always show an upcoming period.
 *
 * Returns { start: 'YYYY-MM-DD', end: 'YYYY-MM-DD' } or null.
 */
function calcRecurringDates(selectedDays) {
    if (!selectedDays || selectedDays.length === 0) return null;

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Monday of current week
    const monday = new Date(today);
    const dow = today.getDay();                          // 0 Sun … 6 Sat
    const diffToMonday = (dow === 0) ? -6 : 1 - dow;
    monday.setDate(monday.getDate() + diffToMonday);

    // Build Date for each selected day within this Mon-anchored week
    // Convert JS day index to Mon-anchored offset:
    //   Sun (0) → 6,  Mon (1) → 0,  Tue (2) → 1, …  Sat (6) → 5
    let candidates = selectedDays.map(d => {
        const offset = (d === 0) ? 6 : d - 1;
        const date   = new Date(monday);
        date.setDate(monday.getDate() + offset);
        return date;
    });

    // If every candidate is already past, roll the whole set forward a week
    if (candidates.every(d => d < today)) {
        candidates = candidates.map(d => {
            const shifted = new Date(d);
            shifted.setDate(shifted.getDate() + 7);
            return shifted;
        });
    }

    candidates.sort((a, b) => a - b);

    return {
        start: toYMD(candidates[0]),
        end:   toYMD(candidates[candidates.length - 1])
    };
}

/** Formats a Date object as 'YYYY-MM-DD'. */
function toYMD(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

/** Returns e.g. "Mon, Fri, Sat" for [1, 5, 6]. */
function friendlyDayList(days) {
    return days.slice().sort((a, b) => a - b).map(d => DAY_NAMES_SHORT[d]).join(', ');
}

/**
 * Wires click-toggle behaviour onto every .day-pill inside `container`.
 * Calls onChangeCallback(selectedDayIndices) on every change.
 */
function initDayPicker(container, onChangeCallback) {
    container.querySelectorAll('.day-pill').forEach(pill => {
        pill.addEventListener('click', function () {
            this.classList.toggle('selected');
            onChangeCallback(getSelectedDays(container));
        });
    });
}

/** Returns the array of currently selected day indices within `container`. */
function getSelectedDays(container) {
    return Array.from(container.querySelectorAll('.day-pill.selected'))
                .map(p => parseInt(p.dataset.day));
}

/**
 * Updates the preview banner text and optionally auto-fills + locks
 * the start/end date inputs.
 *
 * @param {number[]} selectedDays   - Array of selected JS day indices (0–6)
 * @param {Element}  previewEl      - The element that shows the preview text
 * @param {Element|null} startInput - <input type="date"> for start (or null)
 * @param {Element|null} endInput   - <input type="date"> for end   (or null)
 */
function updateRecurringPreview(selectedDays, previewEl, startInput, endInput) {
    if (selectedDays.length === 0) {
        previewEl.innerHTML = '';
        previewEl.style.display = 'none';
        if (startInput) { startInput.readOnly = false; startInput.value = ''; }
        if (endInput)   { endInput.readOnly   = false; endInput.value   = ''; }
        return;
    }

    const dates = calcRecurringDates(selectedDays);
    if (!dates) return;

    const label = friendlyDayList(selectedDays);
    previewEl.innerHTML =
        `<i class="fa-solid fa-rotate" style="color:var(--primary)"></i>` +
        `<strong>${label}</strong> — ` +
        `<strong>${dates.start}</strong> → <strong>${dates.end}</strong>`;
    previewEl.style.display = 'flex';

    // Auto-fill and lock the date pickers
    if (startInput) { startInput.value = dates.start; startInput.readOnly = true; }
    if (endInput)   { endInput.value   = dates.end;   endInput.readOnly   = true; }
}

/** Resets the Add modal's recurring section to its pristine off-state. */
function resetRecurringSection() {
    const toggle     = document.getElementById('add_recurring_toggle');
    const container  = document.getElementById('add_recurring_days_container');
    const preview    = document.getElementById('add_recurring_preview');
    const hidden     = document.getElementById('add_recurring_days');
    const startInput = document.getElementById('add_start_date');
    const endInput   = document.getElementById('add_end_date');
    const datesGroup = document.getElementById('add_dates_group');

    if (toggle)    toggle.checked = false;
    if (container) {
        container.style.display = 'none';
        container.querySelectorAll('.day-pill').forEach(p => p.classList.remove('selected'));
    }
    if (preview)    { preview.innerHTML = ''; preview.style.display = 'none'; }
    if (hidden)     hidden.value = '';
    // Always restore the date fields when resetting
    if (datesGroup) datesGroup.style.display = 'block';
    if (startInput) { startInput.readOnly = false; startInput.required = true; startInput.value = ''; }
    if (endInput)   { endInput.readOnly   = false; endInput.required   = true; endInput.value   = ''; }
}


// ─── DOM-ready wiring ─────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {

    // ── ADD MODAL ──────────────────────────────────────────────────────────────

    const addToggle    = document.getElementById('add_recurring_toggle');
    const addContainer = document.getElementById('add_recurring_days_container');
    const addPreview   = document.getElementById('add_recurring_preview');
    const addHidden    = document.getElementById('add_recurring_days');
    const addStart     = document.getElementById('add_start_date');
    const addEnd       = document.getElementById('add_end_date');

    if (addStart && addEnd) {
        addStart.addEventListener('change', function () {
            if (this.value) addEnd.min = this.value;
        });
    }

    if (addToggle && addContainer) {
        const addDatesGroup = document.getElementById('add_dates_group');

        addToggle.addEventListener('change', function () {
            if (this.checked) {
                addContainer.style.display = 'block';
                // Hide the manual date fields — PHP calculates dates from recurring_days
                if (addDatesGroup) addDatesGroup.style.display = 'none';
                if (addStart) { addStart.required = false; addStart.value = ''; }
                if (addEnd)   { addEnd.required   = false; addEnd.value   = ''; }
            } else {
                addContainer.style.display = 'none';
                addContainer.querySelectorAll('.day-pill').forEach(p => p.classList.remove('selected'));
                if (addPreview) { addPreview.innerHTML = ''; addPreview.style.display = 'none'; }
                if (addHidden)  addHidden.value = '';
                // Restore manual date fields
                if (addDatesGroup) addDatesGroup.style.display = 'block';
                if (addStart) { addStart.required = true;  addStart.readOnly = false; addStart.value = ''; }
                if (addEnd)   { addEnd.required   = true;  addEnd.readOnly   = false; addEnd.value   = ''; }
            }
        });

        initDayPicker(addContainer, function (selected) {
            if (addHidden) addHidden.value = selected.join(',');
            updateRecurringPreview(selected, addPreview, addStart, addEnd);
        });
    }

    // ── EDIT MODAL ─────────────────────────────────────────────────────────────

    const editContainer = document.getElementById('edit_recurring_days_container');
    const editHidden    = document.getElementById('edit_recurring_days');

    if (editContainer) {
        editContainer.querySelectorAll('.day-pill').forEach(pill => {
            pill.addEventListener('click', function () {
                const currentlySelected = editContainer.querySelectorAll('.day-pill.selected');
                // Prevent deselecting the last selected day
                if (this.classList.contains('selected') && currentlySelected.length === 1) {
                    return;
                }
                this.classList.toggle('selected');
                const selected = Array.from(editContainer.querySelectorAll('.day-pill.selected'))
                                      .map(p => parseInt(p.dataset.day));
                if (editHidden) editHidden.value = selected.join(',');
            });
        });
    }
});