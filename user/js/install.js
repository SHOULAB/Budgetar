// Capture beforeinstallprompt as early as possible
window._pwaPrompt = null;
window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    window._pwaPrompt = e;
    showInstallBtn();
});

window.addEventListener('appinstalled', function () {
    window._pwaPrompt = null;
    showInstalledMsg();
});

var isStandalone = window.matchMedia('(display-mode: standalone)').matches
                || window.navigator.standalone === true;
var isIOS    = /iphone|ipad|ipod/i.test(navigator.userAgent);
var isSafari = /^((?!chrome|android|fxios|crios).)*safari/i.test(navigator.userAgent);

function showInstallBtn() {
    document.getElementById('pwaInstallBtn').style.display = 'block';
    document.getElementById('pwaInstalledMsg').style.display = 'none';
    document.getElementById('iosSteps').style.display = 'none';
}

function showInstalledMsg() {
    document.getElementById('pwaInstallBtn').style.display = 'none';
    document.getElementById('iosSteps').style.display = 'none';
    document.getElementById('pwaInstalledMsg').style.display = 'flex';
}

function showIOSSteps() {
    document.getElementById('pwaInstallBtn').style.display = 'none';
    document.getElementById('pwaInstalledMsg').style.display = 'none';
    document.getElementById('iosSteps').style.display = 'flex';
}

if (isStandalone) {
    showInstalledMsg();
} else if (isIOS && isSafari) {
    // iOS Safari — show steps immediately, no button needed
    showIOSSteps();
} else {
    // Android/Chrome — wait for beforeinstallprompt (fires within ~500ms)
    setTimeout(function () {
        if (window._pwaPrompt) {
            showInstallBtn();
        }
    }, 800);
}

document.getElementById('pwaInstallBtn').addEventListener('click', function () {
    if (!window._pwaPrompt) return;
    window._pwaPrompt.prompt();
    window._pwaPrompt.userChoice.then(function (result) {
        window._pwaPrompt = null;
        if (result.outcome === 'accepted') showInstalledMsg();
    });
});

// Service worker registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('../../sw.js')
            .catch(function (err) { console.error('SW registration failed:', err); });
    });
}
