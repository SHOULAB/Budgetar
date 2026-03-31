<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
require_once('../../assets/database.php');

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

$success_message = '';
$error_message   = '';

// ── Handle preference saves ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $theme = $_POST['theme'] === 'light' ? 'light' : 'dark';
    $allowed_currencies = ['EUR', 'USD', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'INR', 'MXN'];
    $currency = isset($_POST['currency']) && in_array($_POST['currency'], $allowed_currencies) 
                ? $_POST['currency'] 
                : 'EUR';

    $success = true;

    // Save theme
    $stmt = mysqli_prepare($savienojums,
        "INSERT INTO BU_user_settings (user_id, setting_key, setting_value)
         VALUES (?, 'theme', ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "is", $user_id, $theme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['theme'] = $theme;
    } else {
        $success = false;
    }

    // Save currency
    $stmt = mysqli_prepare($savienojums,
        "INSERT INTO BU_user_settings (user_id, setting_key, setting_value)
         VALUES (?, 'currency', ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "is", $user_id, $currency);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['currency'] = $currency;
    } else {
        $success = false;
    }

    if ($success) {
        $success_message = 'Iestatījumi saglabāti veiksmīgi!';
    } else {
        $error_message = 'Kļūda saglabājot iestatījumus.';
    }
}

// ── Load current settings ─────────────────────────────────────────────────────
$current_theme = $_SESSION['theme'] ?? 'dark';
$current_currency = $_SESSION['currency'] ?? 'EUR';

// Try to pull from DB (in case session is stale)
$stmt = mysqli_prepare($savienojums,
    "SELECT setting_key, setting_value FROM BU_user_settings WHERE user_id = ? AND setting_key IN ('theme', 'currency')");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        if ($row['setting_key'] === 'theme') {
            $current_theme = $row['setting_value'];
            $_SESSION['theme'] = $current_theme;
        } elseif ($row['setting_key'] === 'currency') {
            $current_currency = $row['setting_value'];
            $_SESSION['currency'] = $current_currency;
        }
    }
    mysqli_stmt_close($stmt);
}
?>
<?php $active_page = 'settings'; ?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iestatījumi - Budgetiva</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/settings.css">
    <link rel="icon" href="../../assets/image/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body class="<?php echo $current_theme === 'light' ? 'light-mode' : ''; ?>">
    <div class="dashboard-container">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="dashboard-header">
                <h1 class="dashboard-title">Iestatījumi</h1>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- ── Appearance & Currency ────────────────────────────────────────── -->
            <section class="settings-section">
                <div class="settings-section-header">
                    <div class="settings-section-icon">
                        <i class="fa-solid fa-palette"></i>
                    </div>
                    <div>
                        <h2 class="settings-section-title">Izskats</h2>
                        <p class="settings-section-subtitle">Pielāgojiet lietotnes vizuālo stilu un valūtu</p>
                    </div>
                </div>

                <form method="POST" action="" id="settingsForm">
                    <input type="hidden" name="save_settings" value="1">

                    <div class="settings-card">
                        <!-- Theme ──────────────────────────────────────────────────── -->
                        <div class="settings-row">
                            <div class="settings-row-info">
                                <span class="settings-row-label">Krāzu shēma</span>
                                <span class="settings-row-desc">Izvēlieties tumšo vai gaišo režīmu</span>
                            </div>
                            <div class="theme-toggle-group">
                                <label class="theme-option <?php echo $current_theme === 'dark' ? 'active' : ''; ?>" id="theme-dark-label">
                                    <input type="radio" name="theme" value="dark"
                                        <?php echo $current_theme === 'dark' ? 'checked' : ''; ?>>
                                    <div class="theme-preview theme-preview-dark">
                                        <div class="preview-sidebar"></div>
                                        <div class="preview-content">
                                            <div class="preview-bar"></div>
                                            <div class="preview-bar short"></div>
                                        </div>
                                    </div>
                                    <span class="theme-label">
                                        <i class="fa-solid fa-moon"></i> Tumšais
                                    </span>
                                </label>

                                <label class="theme-option <?php echo $current_theme === 'light' ? 'active' : ''; ?>" id="theme-light-label">
                                    <input type="radio" name="theme" value="light"
                                        <?php echo $current_theme === 'light' ? 'checked' : ''; ?>>
                                    <div class="theme-preview theme-preview-light">
                                        <div class="preview-sidebar"></div>
                                        <div class="preview-content">
                                            <div class="preview-bar"></div>
                                            <div class="preview-bar short"></div>
                                        </div>
                                    </div>
                                    <span class="theme-label">
                                        <i class="fa-solid fa-sun"></i> Gaišais
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-divider"></div>

                        <!-- Currency ────────────────────────────────────────────────── -->
                        <div class="settings-row">
                            <div class="settings-row-info">
                                <span class="settings-row-label">Valūta</span>
                                <span class="settings-row-desc">Izvēlieties valūtu budžeta parādīšanai. Šīs izmaiņas ir tikai kosmētiskas un neietekmēs vērtības.</span>
                            </div>
                            <div class="currency-selector">
                                <select name="currency" id="currencySelect" class="currency-select">
                                    <option value="EUR" <?php echo $current_currency === 'EUR' ? 'selected' : ''; ?>>€ EUR - Eiro</option>
                                    <option value="USD" <?php echo $current_currency === 'USD' ? 'selected' : ''; ?>>$ USD - ASV Dolārs</option>
                                    <option value="GBP" <?php echo $current_currency === 'GBP' ? 'selected' : ''; ?>>£ GBP - Sterliņu mārciņa</option>
                                    <option value="JPY" <?php echo $current_currency === 'JPY' ? 'selected' : ''; ?>>¥ JPY - Japānas Jena</option>
                                    <option value="CAD" <?php echo $current_currency === 'CAD' ? 'selected' : ''; ?>>$ CAD - Kanādas Dolārs</option>
                                    <option value="AUD" <?php echo $current_currency === 'AUD' ? 'selected' : ''; ?>>$ AUD - Austrālijas Dolārs</option>
                                    <option value="CHF" <?php echo $current_currency === 'CHF' ? 'selected' : ''; ?>>CHF - Šveices Franks</option>
                                    <option value="CNY" <?php echo $current_currency === 'CNY' ? 'selected' : ''; ?>>¥ CNY - Ķīnas Juaņ</option>
                                    <option value="INR" <?php echo $current_currency === 'INR' ? 'selected' : ''; ?>>₹ INR - Indijas Rupija</option>
                                    <option value="MXN" <?php echo $current_currency === 'MXN' ? 'selected' : ''; ?>>$ MXN - Meksikas Peso</option>
                                </select>
                                <div class="currency-preview">
                                    <span class="currency-symbol" id="currencySymbol"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Saglabāt izmaiņas
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <?php include __DIR__ . '/mobile_nav.php'; ?>

    <script src="../js/currency.js"></script>
    <script>
        // Initialize currency from PHP session
        if ('<?php echo $current_currency; ?>') {
            localStorage.setItem('budgetiva_currency', '<?php echo $current_currency; ?>');
        }
    </script>
    <script src="../js/settings.js"></script>
</body>
</html>