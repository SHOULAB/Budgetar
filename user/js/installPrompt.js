/**
 * installPrompt.js
 * Modular PWA installation handler.
 *
 * Responsibilities:
 *  - Capture and store the `beforeinstallprompt` event (prevents mini-infobar)
 *  - Show/hide the install button based on availability
 *  - Trigger the native install prompt only after user interaction
 *  - Detect already-installed (standalone) state and hide button
 *  - Detect iOS Safari and show step-by-step manual instruction modal
 */
(function () {
    'use strict';

    // ── Detection ─────────────────────────────────────────────────────────────

    /** True if the app is already running as an installed standalone app */
    var isStandalone = window.matchMedia('(display-mode: standalone)').matches
                    || window.navigator.standalone === true;

    /** True if running on an iOS device */
    var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);

    /** True if the browser is Safari (excludes Chrome/Firefox on iOS) */
    var isSafariUA = /^((?!chrome|android|fxios|crios).)*safari/i.test(navigator.userAgent);

    /** iOS Safari is the only iOS browser that supports PWA add-to-homescreen */
    var isIOSSafari = isIOS && isSafariUA;

    // ── State ─────────────────────────────────────────────────────────────────

    /**
     * The deferred install prompt event, captured from `beforeinstallprompt`.
     * Initialised from window._pwaPrompt in case script.js captured it earlier.
     */
    var _deferredPrompt = window._pwaPrompt || null;

    // ── UI helpers ────────────────────────────────────────────────────────────

    function getEl(id) { return document.getElementById(id); }

    /**
     * Refresh the visibility of the install button, iOS button, and
     * installed message based on the current state.
     */
    function updateUI() {
        var installBtn        = getEl('pwaInstallBtn');
        var iosBtn            = getEl('pwaIOSInstallBtn');
        var installedMsg      = getEl('pwaInstalledMsg');
        var overlayInstallBtn = getEl('pwaOverlayInstallBtn');
        var overlayIOSBtn     = getEl('pwaOverlayIOSBtn');
        var overlayInstalledMsg = getEl('pwaOverlayInstalledMsg');

        // Hide everything first, then show only what applies
        [installBtn, iosBtn, installedMsg, overlayInstallBtn, overlayIOSBtn, overlayInstalledMsg]
            .forEach(function (el) { if (el) el.style.display = 'none'; });

        if (isStandalone) {
            if (installedMsg)        installedMsg.style.display        = '';
            if (overlayInstalledMsg) overlayInstalledMsg.style.display = '';
            return;
        }

        if (_deferredPrompt) {
            // Native install prompt is available (Android Chrome, desktop Chrome/Edge)
            if (installBtn)        installBtn.style.display        = '';
            if (overlayInstallBtn) overlayInstallBtn.style.display = '';
        } else if (isIOSSafari) {
            // iOS Safari cannot trigger install programmatically — show manual button
            if (iosBtn)        iosBtn.style.display        = '';
            if (overlayIOSBtn) overlayIOSBtn.style.display = '';
        }
    }

    // ── Install prompt ────────────────────────────────────────────────────────

    /**
     * Trigger the native browser install prompt.
     * Must be called from a user gesture (click event).
     */
    function triggerInstall() {
        if (!_deferredPrompt) return;

        // Show the native install dialog
        _deferredPrompt.prompt();

        // Wait for user's choice
        _deferredPrompt.userChoice.then(function (result) {
            // Clear the stored prompt — it can only be used once
            _deferredPrompt = null;
            window._pwaPrompt = null;

            if (result.outcome === 'accepted') {
                // User accepted — app will install, treat as standalone
                isStandalone = true;
            }

            updateUI();
        });
    }

    // ── QR install overlay ─────────────────────────────────────────────────

    /**
     * When the page is opened via QR code (URL contains ?install=1),
     * show a full-screen overlay so the user just needs one tap to install.
     * Browser security requires the actual prompt() call to come from a
     * real user gesture — this overlay provides that single tap.
     */
    function openInstallOverlay() {
        var overlay = getEl('pwaInstallOverlay');
        if (!overlay) return;
        overlay.style.display = 'flex';
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                overlay.style.opacity = '1';
            });
        });
    }

    function closeInstallOverlay() {
        var overlay = getEl('pwaInstallOverlay');
        if (!overlay) return;
        overlay.style.opacity = '0';
        setTimeout(function () { overlay.style.display = 'none'; }, 300);
    }

    // ── iOS instruction modal ─────────────────────────────────────────────────

    /** Open the iOS "Add to Home Screen" instruction modal with slide-up animation */
    function openIOSModal() {
        var modal = getEl('pwaIOSModal');
        var sheet = getEl('pwaIOSModalSheet');
        if (!modal) return;

        modal.style.display = 'flex';

        // Animate in on the next two frames to ensure display:flex is applied first
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                if (sheet) sheet.style.transform = 'translateY(0)';
            });
        });
    }

    /** Close the iOS modal with slide-down animation */
    function closeIOSModal() {
        var modal = getEl('pwaIOSModal');
        var sheet = getEl('pwaIOSModalSheet');
        if (!modal) return;

        if (sheet) sheet.style.transform = 'translateY(100%)';

        // Hide after the CSS transition finishes (300ms)
        setTimeout(function () {
            modal.style.display = 'none';
        }, 300);
    }

    // ── Event listeners ───────────────────────────────────────────────────────

    /**
     * Listen for `beforeinstallprompt` in case it fires after this script loads.
     * script.js also listens early — whichever captures it first stores it in
     * window._pwaPrompt so both can share the same reference.
     */
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault(); // Prevent the browser's default mini-infobar
        _deferredPrompt = e;
        window._pwaPrompt = e;
        updateUI();
    });

    /** Clear prompt reference and update UI when the app is successfully installed */
    window.addEventListener('appinstalled', function () {
        _deferredPrompt = null;
        window._pwaPrompt = null;
        isStandalone = true;
        updateUI();
    });

    // ── DOM initialisation ────────────────────────────────────────────────────

    function init() {
        // Set initial button visibility
        updateUI();

        var installBtn        = getEl('pwaInstallBtn');
        var iosBtn            = getEl('pwaIOSInstallBtn');
        var modalClose        = getEl('pwaIOSModalClose');
        var modalOverlay      = getEl('pwaIOSModalOverlay');
        var overlayInstallBtn = getEl('pwaOverlayInstallBtn');
        var overlayIOSBtn     = getEl('pwaOverlayIOSBtn');
        var overlayClose      = getEl('pwaOverlayClose');

        // Native install button in settings row
        if (installBtn) installBtn.addEventListener('click', triggerInstall);

        // iOS instruction button in settings row
        if (iosBtn) iosBtn.addEventListener('click', openIOSModal);

        // Close modal via close button or backdrop tap
        if (modalClose)   modalClose.addEventListener('click', closeIOSModal);
        if (modalOverlay) modalOverlay.addEventListener('click', closeIOSModal);

        // Full-screen overlay buttons
        if (overlayInstallBtn) {
            overlayInstallBtn.addEventListener('click', function () {
                closeInstallOverlay();
                triggerInstall();
            });
        }
        if (overlayIOSBtn) {
            overlayIOSBtn.addEventListener('click', function () {
                closeInstallOverlay();
                openIOSModal();
            });
        }
        if (overlayClose) overlayClose.addEventListener('click', closeInstallOverlay);

        // Auto-open overlay when page is opened via QR code (?install=1)
        if (new URLSearchParams(window.location.search).get('install') === '1' && !isStandalone) {
            // Wait briefly for beforeinstallprompt to fire, then show the overlay
            setTimeout(function () {
                // Update deferred prompt in case it fired after script loaded
                if (window._pwaPrompt) _deferredPrompt = window._pwaPrompt;
                updateUI();
                openInstallOverlay();
            }, 600);
        }
    }

    // Run init after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

}());
