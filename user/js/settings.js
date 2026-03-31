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
        'EUR': '€',
        'USD': '$',
        'GBP': '£',
        'JPY': '¥',
        'CAD': '$',
        'AUD': '$',
        'CHF': 'CHF',
        'CNY': '¥',
        'INR': '₹',
        'MXN': '$'
    };

    const LS_KEY = 'budgetiva_currency';
    const select = document.getElementById('currencySelect');
    const preview = document.getElementById('currencySymbol');

    if (!select || !preview) return;

    // ── Helper: update currency symbol display ──────────────────────────────
    function updatePreview(currency) {
        const symbol = currencySymbols[currency] || currency;
        preview.textContent = symbol;
    }

    // ── On page load: apply saved preference ────────────────────────────────
    const saved = localStorage.getItem(LS_KEY);
    if (saved && currencySymbols[saved]) {
        select.value = saved;
        updatePreview(saved);
    } else {
        // Apply current selected value
        updatePreview(select.value);
    }

    // ── Live preview on change (does NOT save yet) ─────────────────────────
    select.addEventListener('change', function () {
        updatePreview(this.value);
    });

})();