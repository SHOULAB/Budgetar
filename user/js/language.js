(function () {
    'use strict';

    var LS_KEY = 'budgetar_language';
    var SUPPORTED = ['lv', 'en'];

    // Safety fallback: if something goes wrong, always reveal the body after 800 ms.
    var _revealTimer = setTimeout(function () {
        if (document.body) document.body.classList.add('i18n-ready');
    }, 800);

    function applyTranslations(T, lang) {
        var dict = T[lang] || T['lv'];
        document.querySelectorAll('[data-i18n]').forEach(function (el) {
            var key = el.getAttribute('data-i18n');
            if (dict[key] !== undefined) el.textContent = dict[key];
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach(function (el) {
            var key = el.getAttribute('data-i18n-placeholder');
            if (dict[key] !== undefined) el.placeholder = dict[key];
        });
        document.querySelectorAll('[data-i18n-tooltip]').forEach(function (el) {
            var key = el.getAttribute('data-i18n-tooltip');
            if (dict[key] !== undefined) el.setAttribute('data-tooltip', dict[key]);
        });
        document.querySelectorAll('.lang-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.dataset.lang === lang);
        });
    }

    function detectLanguage() {
        var browserLang = (navigator.language || navigator.userLanguage || 'lv').slice(0, 2).toLowerCase();
        return SUPPORTED.indexOf(browserLang) !== -1 ? browserLang : 'lv';
    }

    var stored = localStorage.getItem(LS_KEY);
    var lang;
    if (stored) {
        // User has visited before — always honour their stored choice.
        lang = stored;
    } else if (window._i18nIsDefault === false) {
        // Logged-in user with a saved DB preference — use it.
        lang = window._i18nLang || 'lv';
    } else {
        // New / guest user with no saved preference — detect from browser.
        lang = detectLanguage();
        localStorage.setItem(LS_KEY, lang);
    }

    function revealBody() {
        clearTimeout(_revealTimer);
        if (document.body) document.body.classList.add('i18n-ready');
    }

    if (window._i18nData) {
        // Translation data was inlined by PHP — apply synchronously, no flash.
        applyTranslations(window._i18nData, lang);
        window._i18n = { T: window._i18nData, lang: lang, apply: applyTranslations };
        revealBody();
    } else {
        // Fallback: fetch the JSON file (only when no inline data is present).
        fetch('../php/translate.json')
            .then(function (r) { return r.json(); })
            .then(function (T) {
                window._i18n = { T: T, lang: lang, apply: applyTranslations };
                applyTranslations(T, lang);
                revealBody();
            })
            .catch(revealBody); // Show body even if fetch fails
    }
})();
