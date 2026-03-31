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
    const form = document.getElementById('appearanceForm');
    if (form) {
        form.addEventListener('submit', function () {
            const selected = this.querySelector('input[name="theme"]:checked');
            if (selected) localStorage.setItem(LS_KEY, selected.value);

            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saglabā...';
                btn.disabled  = true;
            }
        });
    }

})();