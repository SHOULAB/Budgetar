// open income modal
function openIncomeModal() {
    const modal = document.getElementById('incomeModal');
    modal.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
}

// close income modal
function closeIncomeModal() {
    const modal = document.getElementById('incomeModal');
    modal.classList.remove('modal-open');
    document.body.style.overflow = 'auto';
}

// open expense modal
function openExpenseModal() {
    const modal = document.getElementById('expenseModal');
    modal.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
}

// close expense modal
function closeExpenseModal() {
    const modal = document.getElementById('expenseModal');
    modal.classList.remove('modal-open');
    document.body.style.overflow = 'auto';
}

// open day details
function openDayModal(day, month, year) {
    if (!transactionsData[day]) return;
    
    const modal   = document.getElementById('dayModal');
    const title   = document.getElementById('dayModalTitle');
    const content = document.getElementById('dayModalContent');
    const monthNames = ['', 'Janvāris', 'Februāris', 'Marts', 'Aprīlis', 'Maijs', 'Jūnijs', 
                        'Jūlijs', 'Augusts', 'Septembris', 'Oktobris', 'Novembris', 'Decembris'];
    title.textContent = `${day}. ${monthNames[month]}, ${year}`;
    
    const transactions = transactionsData[day];
    let html = '';
    
    transactions.forEach(transaction => {
        const typeClass = transaction.type === 'income' ? 'income' : 'expense';
        const typeLabel = transaction.type === 'income' ? 'Ienākums' : 'Izdevums';
        const sign      = transaction.type === 'income' ? '+' : '-';
        const recurringBadge = transaction.is_recurring_display
            ? '<span class="recurring-badge">🔄 Ikmēneša</span>' : '';
        
        html += `
            <div class="transaction-item ${typeClass}">
                <div class="transaction-info">
                    <div class="transaction-description">${transaction.description} ${recurringBadge}</div>
                    <div class="transaction-type">${typeLabel}</div>
                </div>
                <div class="transaction-amount">${sign}€${parseFloat(transaction.amount).toFixed(2)}</div>
            </div>
        `;
    });
    
    content.innerHTML = html;
    modal.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
}

// close day modal
function closeDayModal() {
    const modal = document.getElementById('dayModal');
    modal.classList.remove('modal-open');
    document.body.style.overflow = 'auto';
}

// close modal if click outside
window.addEventListener('click', function(e) {
    if (e.target === document.getElementById('incomeModal'))  closeIncomeModal();
    if (e.target === document.getElementById('expenseModal')) closeExpenseModal();
    if (e.target === document.getElementById('dayModal'))     closeDayModal();
    const wm = document.getElementById('warningModal');
    if (wm && e.target === wm) closeWarningModal();
    const bm = document.getElementById('budgetWarningModal');
    if (bm && e.target === bm) closeBudgetWarningModal();
});

// close modal with esc
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeIncomeModal();
        closeExpenseModal();
        closeDayModal();
        closeWarningModal();
        closeBudgetWarningModal();
    }
});


// ─── Expense form validation ──────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function() {
    const expenseForm = document.querySelector('#expenseModal form');
    
    if (expenseForm) {
        expenseForm.addEventListener('submit', function(e) {
            const expenseAmount = parseFloat(document.getElementById('expense_amount').value) || 0;
            const expenseDate   = document.getElementById('expense_date').value; // 'YYYY-MM-DD'

            // 1. Check monthly income exceed (existing behaviour)
            const newTotalExpense = monthlyExpense + expenseAmount;
            if (newTotalExpense > monthlyIncome) {
                e.preventDefault();
                showWarningModal(expenseAmount, newTotalExpense);
                return;
            }

            // 2. Check if any active budget would be exceeded by this expense
            const breachedBudgets = getBudgetBreaches(expenseDate, expenseAmount);
            if (breachedBudgets.length > 0) {
                e.preventDefault();
                showBudgetWarningModal(expenseAmount, expenseDate, breachedBudgets);
            }
        });
    }
});

/**
 * Returns an array of budget objects that would be breached by adding
 * `amount` as an expense on `dateStr` (YYYY-MM-DD).
 * Only budgets whose date range covers `dateStr` are considered.
 */
function getBudgetBreaches(dateStr, amount) {
    if (!activeBudgets || activeBudgets.length === 0) return [];

    return activeBudgets.filter(b => {
        // Only budgets whose period covers the selected expense date
        if (dateStr < b.start_date || dateStr > b.end_date) return false;
        // Would adding this amount push spending over the budget?
        return (b.spent + amount) > b.budget_amount;
    });
}


// ─── Budget-exceed warning modal ──────────────────────────────────────────────

function showBudgetWarningModal(expenseAmount, expenseDate, breachedBudgets) {
    let existing = document.getElementById('budgetWarningModal');
    if (existing) existing.remove();

    // Build a row for each breached budget
    let budgetRows = '';
    breachedBudgets.forEach(b => {
        const newSpent    = b.spent + expenseAmount;
        const over        = newSpent - b.budget_amount;
        const pct         = Math.min((newSpent / b.budget_amount) * 100, 999).toFixed(0);
        budgetRows += `
            <div class="bw-budget-row">
                <div class="bw-budget-name">
                    <i class="fa-solid fa-wallet"></i>
                    <strong>${escHtml(b.budget_name)}</strong>
                </div>
                <div class="bw-budget-stats">
                    <div class="bw-stat">
                        <span class="bw-stat-label">Budžets:</span>
                        <span class="bw-stat-val">€${parseFloat(b.budget_amount).toFixed(2)}</span>
                    </div>
                    <div class="bw-stat">
                        <span class="bw-stat-label">Tērēts:</span>
                        <span class="bw-stat-val expense">€${parseFloat(b.spent).toFixed(2)}</span>
                    </div>
                    <div class="bw-stat">
                        <span class="bw-stat-label">Jauns izdevums:</span>
                        <span class="bw-stat-val expense">€${expenseAmount.toFixed(2)}</span>
                    </div>
                    <div class="bw-stat bw-stat-over">
                        <span class="bw-stat-label">Pārtērēts par:</span>
                        <span class="bw-stat-val deficit">€${over.toFixed(2)}</span>
                    </div>
                </div>
                <div class="bw-progress-wrap">
                    <div class="bw-progress-track">
                        <div class="bw-progress-fill" style="width:${Math.min(pct,100)}%"></div>
                        <div class="bw-progress-over" style="width:${Math.min(over/b.budget_amount*100,100)}%"></div>
                    </div>
                    <span class="bw-pct">${pct}% izmantots</span>
                </div>
            </div>`;
    });

    const plural = breachedBudgets.length > 1 ? 'budžetiem' : 'budžetam';

    const modal = document.createElement('div');
    modal.id = 'budgetWarningModal';
    modal.className = 'modal modal-open';
    modal.innerHTML = `
        <div class="modal-content bw-modal-content">
            <div class="modal-header bw-modal-header">
                <div class="bw-title-wrap">
                    <div class="bw-title-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div>
                        <h2 class="modal-title">Budžeta brīdinājums</h2>
                        <p class="bw-subtitle">Šis izdevums pārsniegs ${plural}</p>
                    </div>
                </div>
                <button class="modal-close" onclick="closeBudgetWarningModal()">✕</button>
            </div>
            <div class="bw-body">
                ${budgetRows}
                <p class="bw-question">Vai tiešām vēlies pievienot šo izdevumu?</p>
            </div>
            <div class="bw-actions">
                <button class="btn btn-secondary" onclick="closeBudgetWarningModal()">
                    <i class="fa-solid fa-xmark"></i> Atcelt
                </button>
                <button class="btn btn-danger" onclick="confirmBudgetExpense()">
                    <i class="fa-solid fa-check"></i> Jā, pievienot
                </button>
            </div>
        </div>`;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
}

function closeBudgetWarningModal() {
    const modal = document.getElementById('budgetWarningModal');
    if (modal) {
        modal.classList.remove('modal-open');
        document.body.style.overflow = 'auto';
        setTimeout(() => modal.remove(), 300);
    }
}

function confirmBudgetExpense() {
    closeBudgetWarningModal();
    const expenseForm = document.querySelector('#expenseModal form');
    if (expenseForm) {
        // Clone to strip event listeners, then submit directly
        const newForm = expenseForm.cloneNode(true);
        expenseForm.parentNode.replaceChild(newForm, expenseForm);
        newForm.submit();
    }
}

/** Simple HTML escape helper */
function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}


// ─── Income-exceed warning modal (existing) ───────────────────────────────────

function showWarningModal(expenseAmount, newTotalExpense) {
    const deficit = newTotalExpense - monthlyIncome;
    
    let warningModal = document.getElementById('warningModal');
    if (!warningModal) {
        warningModal = document.createElement('div');
        warningModal.id = 'warningModal';
        warningModal.className = 'modal';
        warningModal.innerHTML = `
            <div class="modal-content warning-modal-content">
                <div class="modal-header">
                    <h2 class="modal-title warning-title">⚠️ Brīdinājums!</h2>
                </div>
                <div class="warning-message">
                    <p class="warning-text">Šis izdevums pārsniegs tavus mēneša ienākumus!</p>
                    <div class="warning-details">
                        <div class="warning-stat">
                            <span class="warning-label">Mēneša ienākumi:</span>
                            <span class="warning-value income">+€${monthlyIncome.toFixed(2)}</span>
                        </div>
                        <div class="warning-stat">
                            <span class="warning-label">Pašreizējie izdevumi:</span>
                            <span class="warning-value expense">-€${monthlyExpense.toFixed(2)}</span>
                        </div>
                        <div class="warning-stat">
                            <span class="warning-label">Jauns izdevums:</span>
                            <span class="warning-value expense">-€${expenseAmount.toFixed(2)}</span>
                        </div>
                        <div class="warning-divider"></div>
                        <div class="warning-stat total">
                            <span class="warning-label">Kopējie izdevumi:</span>
                            <span class="warning-value expense">-€${newTotalExpense.toFixed(2)}</span>
                        </div>
                        <div class="warning-stat deficit">
                            <span class="warning-label">Deficīts:</span>
                            <span class="warning-value deficit-value">-€${deficit.toFixed(2)}</span>
                        </div>
                    </div>
                    <p class="warning-question">Vai tiešām vēlies pievienot šo izdevumu?</p>
                </div>
                <div class="warning-actions">
                    <button class="btn btn-secondary" onclick="closeWarningModal()">Atcelt</button>
                    <button class="btn btn-danger" onclick="confirmExpense()">Jā, pievienot</button>
                </div>
            </div>
        `;
        document.body.appendChild(warningModal);
    } else {
        warningModal.querySelector('.warning-value.income').textContent = `+€${monthlyIncome.toFixed(2)}`;
        warningModal.querySelectorAll('.warning-value.expense')[0].textContent = `-€${monthlyExpense.toFixed(2)}`;
        warningModal.querySelectorAll('.warning-value.expense')[1].textContent = `-€${expenseAmount.toFixed(2)}`;
        warningModal.querySelectorAll('.warning-value.expense')[2].textContent = `-€${newTotalExpense.toFixed(2)}`;
        warningModal.querySelector('.warning-value.deficit-value').textContent = `-€${deficit.toFixed(2)}`;
    }
    
    warningModal.classList.add('modal-open');
    document.body.style.overflow = 'hidden';
}

function closeWarningModal() {
    const modal = document.getElementById('warningModal');
    if (modal) {
        modal.classList.remove('modal-open');
        document.body.style.overflow = 'auto';
    }
}

function confirmExpense() {
    closeWarningModal();
    const expenseForm = document.querySelector('#expenseModal form');
    if (expenseForm) {
        const newForm = expenseForm.cloneNode(true);
        expenseForm.parentNode.replaceChild(newForm, expenseForm);
        newForm.submit();
    }
}


// ─── Animations ───────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease-out';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 50);
        }, index * 100);
    });
});

const calendarDays = document.querySelectorAll('.calendar-day:not(.calendar-day-empty)');
calendarDays.forEach(day => {
    day.addEventListener('mouseenter', function() {
        if (!this.classList.contains('calendar-day-empty')) {
            this.style.transform = 'scale(1.05)';
        }
    });
    day.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
});

document.addEventListener('DOMContentLoaded', function() {
    if (window.innerWidth <= 480) {
        const todayCard = document.querySelector('.calendar-day-today');
        if (todayCard) {
            setTimeout(() => {
                todayCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 150);
        }
    }
});