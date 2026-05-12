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
    <link rel="stylesheet" href="../css/install.css">
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
    </div>

    <script src="../js/install.js"></script>
</body>
</html>
