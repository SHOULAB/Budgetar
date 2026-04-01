// settings.js — Live theme switching and radio/toggle sync

(function () {
    'use strict';

    const LS_KEY = 'budgetiva_theme';

    // ── Element refs ──────────────────────────────────────────────────────────
    const body       = document.body;
    const darkLabel  = document.getElementById('theme-dark-label');
    const lightLabel = document.getElementById('theme-light-label');
    const darkRadio  = darkLabel  ? darkLabel.querySelector('input[type="radio"]')  : null;
    const lightRadio = lightLabel ? lightLabel.querySelector('input[type="radio"]') : null;

    // ── Apply theme visually + sync controls ──────────────────────────────────
    function applyTheme(theme) {
        body.classList.toggle('light-mode', theme === 'light');
        if (darkLabel)  darkLabel.classList.toggle('active',  theme === 'dark');
        if (lightLabel) lightLabel.classList.toggle('active', theme === 'light');
    }

    // ── On page load: apply confirmed saved preference ────────────────────────
    // localStorage is only written on Save, so this always reflects a confirmed
    // preference — never an abandoned preview.
    const saved = localStorage.getItem(LS_KEY);
    if (saved === 'light' || saved === 'dark') {
        applyTheme(saved);
        if (darkRadio)  darkRadio.checked  = (saved === 'dark');
        if (lightRadio) lightRadio.checked = (saved === 'light');
    }

    // ── Live preview on click (does NOT touch localStorage) ──────────────────
    [darkRadio, lightRadio].forEach(radio => {
        if (!radio) return;
        radio.addEventListener('change', function () {
            applyTheme(this.value);
        });
    });

    // ── On save: persist to localStorage so other pages pick it up ───────────
    const form = document.getElementById('settingsForm');
    if (form) {
        form.addEventListener('submit', function () {
            const selected = this.querySelector('input[name="theme"]:checked');
            if (selected) localStorage.setItem(LS_KEY, selected.value);

            const currency = document.getElementById('currencySelect');
            const newCurrency = currency ? currency.value : 'EUR';
            if (newCurrency) {
                localStorage.setItem('budgetiva_currency', newCurrency);
                // Notify other tabs/windows of currency change
                if (typeof notifyCurrencyChange === 'function') {
                    notifyCurrencyChange(newCurrency);
                }
            }

            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saglabā...';
                btn.disabled  = true;
            }
        });
    }

})();

// ── Currency selector with live preview ─────────────────────────────────────
(function () {
    'use strict';

    const currencySymbols = {
        'EUR': '<i class="fa-solid fa-euro-sign"></i>',
        'USD': '<i class="fa-solid fa-dollar-sign"></i>',
        'GBP': '<i class="fa-solid fa-sterling-sign"></i>',
        'JPY': '<i class="fa-solid fa-yen-sign"></i>',
        'CHF': '<i class="fa-solid fa-franc-sign"></i>',
        'INR': '<i class="fa-solid fa-indian-rupee-sign"></i>',
        'RUB': '<i class="fa-solid fa-ruble-sign"></i>',
        'TRY': '<i class="fa-solid fa-turkish-lira-sign"></i>',
        'KRW': '<i class="fa-solid fa-won-sign"></i>'
    };

    const currencyNames = {
        'EUR': 'Eiro',
        'USD': 'Dolārs',
        'GBP': 'Sterliņu mārciņa',
        'JPY': 'Japānas Jena',
        'CHF': 'Šveices Franks',
        'INR': 'Indijas Rupija',
        'RUB': 'Krievijas Rublis',
        'TRY': 'Turcijas Lira',
        'KRW': 'Korejas Vona'
    };

    const LS_KEY = 'budgetiva_currency';
    const select = document.getElementById('currencySelect');
    const customSelect = document.getElementById('customCurrencySelect');
    const trigger = document.getElementById('customSelectValue');
    const options = document.getElementById('customOptions');
    const preview = document.getElementById('currencySymbol');

    if (!select || !customSelect || !trigger || !options || !preview) return;

    let isOpen = false;

    // ── Helper: update currency symbol display ──────────────────────────────
    function updatePreview(currency) {
        const symbol = currencySymbols[currency] || currency;
        preview.innerHTML = symbol;
    }

    // ── Helper: update custom select display ────────────────────────────────
    function updateCustomSelect(currency) {
        const symbol = currencySymbols[currency] || currency;
        const name = currencyNames[currency] || currency;
        trigger.innerHTML = `${symbol} ${currency} - ${name}`;

        // Update selected class
        const optionElements = options.querySelectorAll('.custom-option');
        optionElements.forEach(option => {
            option.classList.toggle('selected', option.dataset.value === currency);
        });
    }

    // ── Reposition dropdown to avoid parent overflow clipping ───────────────
    function positionOptions() {
        const rect = customSelect.getBoundingClientRect();
        const top = rect.bottom + 8;
        const left = rect.left;
        options.style.position = 'fixed';
        options.style.top = `${top}px`;
        options.style.left = `${left}px`;
        options.style.width = `${rect.width}px`;
        options.style.maxHeight = '260px';
        options.style.zIndex = '10000';
    }

    // ── Toggle dropdown ─────────────────────────────────────────────────────
    function toggleDropdown() {
        if (!isOpen) {
            positionOptions();
        }
        isOpen = !isOpen;
        customSelect.classList.toggle('open', isOpen);
    }

    // ── Close dropdown ──────────────────────────────────────────────────────
    function closeDropdown() {
        isOpen = false;
        customSelect.classList.remove('open');
    }

    // ── Select option ───────────────────────────────────────────────────────
    function selectOption(currency) {
        select.value = currency;
        updateCustomSelect(currency);
        updatePreview(currency);
        closeDropdown();
    }

    // ── Event listeners ─────────────────────────────────────────────────────

    // Trigger click
    trigger.parentElement.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleDropdown();
    });

    // Option clicks
    options.addEventListener('click', function(e) {
        const option = e.target.closest('.custom-option');
        if (option) {
            const value = option.dataset.value;
            selectOption(value);
        }
    });

    // Click outside to close
    document.addEventListener('click', function(e) {
        if (!customSelect.contains(e.target)) {
            closeDropdown();
        }
    });

    // Close on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDropdown();
        }
    });

    // Reposition when window changes size/scroll
    window.addEventListener('resize', function() {
        if (isOpen) positionOptions();
    });
    window.addEventListener('scroll', function() {
        if (isOpen) positionOptions();
    }, true);

    // ── On page load: apply saved preference ────────────────────────────────
    const saved = localStorage.getItem(LS_KEY);
    const initialCurrency = saved && currencySymbols[saved] ? saved : select.value;
    select.value = initialCurrency;
    updateCustomSelect(initialCurrency);
    updatePreview(initialCurrency);

})();

(function () {
    'use strict';

    function closeDeleteAccountModal() {
        const modal = document.getElementById('deleteAccountModal');
        if (!modal) return;
        modal.classList.remove('modal-open');
        setTimeout(() => modal.remove(), 200);
    }

    function showDeleteAccountModal() {
        if (document.getElementById('deleteAccountModal')) return;

        const modal = document.createElement('div');
        modal.id = 'deleteAccountModal';
        modal.className = 'modal modal-open';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">Apstiprināt konta dzēšanu</h2>
                    <button type="button" class="modal-close" aria-label="Aizvērt">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="deletePassword" class="form-label">Ievadiet paroli</label>
                        <input type="password" id="deletePassword" class="form-input" placeholder="Parole" autocomplete="current-password">
                        <span id="deletePasswordError" class="form-hint" style="color: #ff6b6b; display: none;"></span>
                    </div>
                    <p>Vai tiešām vēlaties dzēst savu kontu? Šī darbība ir neatgriezeniska un tiks izdzēsti visi jūsu dati.</p>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="deleteAccountCancelBtn">Atcelt</button>
                    <button type="button" class="btn btn-danger" id="deleteAccountConfirmBtn">
                        <i class="fa-solid fa-trash-can"></i> Dzēst kontu
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelector('.modal-close').addEventListener('click', closeDeleteAccountModal);
        modal.querySelector('#deleteAccountCancelBtn').addEventListener('click', closeDeleteAccountModal);
        modal.querySelector('#deleteAccountConfirmBtn').addEventListener('click', function () {
            const passwordInput = modal.querySelector('#deletePassword');
            const passwordError = modal.querySelector('#deletePasswordError');
            if (!passwordInput || !passwordInput.value.trim()) {
                if (passwordError) {
                    passwordError.textContent = 'Lūdzu ievadiet savu paroli.';
                    passwordError.style.display = 'block';
                }
                passwordInput.focus();
                return;
            }

            if (passwordError) {
                passwordError.textContent = '';
                passwordError.style.display = 'none';
            }

            const form = document.getElementById('settingsForm');
            if (!form) return;

            const hiddenDelete = document.createElement('input');
            hiddenDelete.type = 'hidden';
            hiddenDelete.name = 'delete_account';
            hiddenDelete.value = '1';
            form.appendChild(hiddenDelete);

            const hiddenPassword = document.createElement('input');
            hiddenPassword.type = 'hidden';
            hiddenPassword.name = 'delete_password';
            hiddenPassword.value = passwordInput.value;
            form.appendChild(hiddenPassword);

            form.submit();
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                closeDeleteAccountModal();
            }
        });
    }

    const deleteBtn = document.getElementById('deleteAccountBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', showDeleteAccountModal);
    }
})();