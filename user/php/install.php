<?php
// install.php — Dedicated PWA install page, linked from the QR code in settings.
// No login required. Simply shows an install card.
$_traw = json_decode(file_get_contents(__DIR__ . '/translate.json'), true) ?? [];
$lang  = $_COOKIE['language'] ?? 'lv';
$_t    = $_traw[$lang] ?? $_traw['lv'] ?? [];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetar — <?php echo htmlspecialchars($_t['app.install.page.title'] ?? 'Instalēt lietotni'); ?></title>
    <link rel="icon" href="../../assets/image/logo.png" type="image/png">
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#14b8a6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="../../assets/image/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100dvh;
            background: #0f1923;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 24px;
        }

        .install-card {
            background: #1a2535;
            border: 1px solid #1e3448;
            border-radius: 20px;
            padding: 40px 32px;
            width: 100%;
            max-width: 380px;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.4);
        }

        .app-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: #14b8a6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.2rem;
            color: #fff;
        }

        .app-name {
            font-size: 1.6rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 8px;
        }

        .app-desc {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .btn-install {
            display: none; /* shown by JS when prompt is available */
            width: 100%;
            padding: 16px;
            background: #14b8a6;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            margin-bottom: 12px;
        }
        .btn-install:active { transform: scale(0.97); }
        .btn-install:hover  { background: #0d9488; }

        .installed-msg {
            display: none; /* shown by JS when already installed */
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: #4ade80;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .btn-login {
            display: block;
            width: 100%;
            padding: 14px;
            background: transparent;
            color: #14b8a6;
            border: 2px solid #14b8a6;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
            margin-top: 8px;
        }
        .btn-login:hover { background: #14b8a6; color: #fff; }

        /* iOS steps card — hidden by default */
        .ios-steps {
            display: none;
            text-align: left;
            background: #0f1923;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            gap: 16px;
            flex-direction: column;
        }
        .ios-step {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .ios-step-num {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #14b8a6;
            color: #fff;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .ios-step span {
            color: #cbd5e1;
            font-size: 0.9rem;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="install-card">
        <div class="app-icon">
            <i class="fa-solid fa-wallet"></i>
        </div>
        <div class="app-name">Budgetar</div>
        <p class="app-desc" data-i18n="app.install.page.desc">
            <?php echo htmlspecialchars($_t['app.install.page.desc'] ?? 'Pārvaldiet savus finanses viegli un ērti. Instalējiet lietotni savā ierīcē.'); ?>
        </p>

        <!-- Android/Chrome: native install button -->
        <button class="btn-install" id="pwaInstallBtn" data-i18n="app.install.btn">
            <i class="fa-solid fa-download"></i>
            <?php echo htmlspecialchars($_t['app.install.btn'] ?? 'Instalēt'); ?>
        </button>

        <!-- iOS Safari: show steps -->
        <div class="ios-steps" id="iosSteps">
            <div class="ios-step">
                <div class="ios-step-num">1</div>
                <span data-i18n="app.ios.modal.step1"><?php echo htmlspecialchars($_t['app.ios.modal.step1'] ?? 'Nospiediet Kopīgot apakšā'); ?></span>
            </div>
            <div class="ios-step">
                <div class="ios-step-num">2</div>
                <span data-i18n="app.ios.modal.step2"><?php echo htmlspecialchars($_t['app.ios.modal.step2'] ?? 'Izvēlieties "Pievienot sākumekrānam"'); ?></span>
            </div>
            <div class="ios-step">
                <div class="ios-step-num">3</div>
                <span data-i18n="app.ios.modal.step3"><?php echo htmlspecialchars($_t['app.ios.modal.step3'] ?? 'Nospiediet "Pievienot" augšējā labajā stūrī'); ?></span>
            </div>
        </div>

        <!-- Already installed -->
        <div class="installed-msg" id="pwaInstalledMsg">
            <i class="fa-solid fa-circle-check"></i>
            <span data-i18n="app.install.already"><?php echo htmlspecialchars($_t['app.install.already'] ?? 'Instalēts'); ?></span>
        </div>

        <a href="login.php" class="btn-login" data-i18n="app.install.page.login">
            <?php echo htmlspecialchars($_t['app.install.page.login'] ?? 'Doties uz lietotni'); ?>
        </a>
    </div>

    <script>
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
    </script>

    <script>
    // Service worker registration
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('../../sw.js')
                .catch(function (err) { console.error('SW registration failed:', err); });
        });
    }
    </script>
</body>
</html>
