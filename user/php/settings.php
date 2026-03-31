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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_appearance'])) {
    $theme = $_POST['theme'] === 'light' ? 'light' : 'dark';

    // Upsert into BU_user_settings
    $stmt = mysqli_prepare($savienojums,
        "INSERT INTO BU_user_settings (user_id, setting_key, setting_value)
         VALUES (?, 'theme', ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "is", $user_id, $theme);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $_SESSION['theme'] = $theme;
        $success_message = 'Izskats saglabāts veiksmīgi!';
    } else {
        $error_message = 'Kļūda saglabājot iestatījumus.';
    }
}

// ── Load current settings ─────────────────────────────────────────────────────
$current_theme = $_SESSION['theme'] ?? 'dark';

// Try to pull from DB (in case session is stale)
$stmt = mysqli_prepare($savienojums,
    "SELECT setting_value FROM BU_user_settings WHERE user_id = ? AND setting_key = 'theme'");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $current_theme = $row['setting_value'];
        $_SESSION['theme'] = $current_theme;
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

            <!-- ── Appearance ──────────────────────────────────────────────── -->
            <section class="settings-section">
                <div class="settings-section-header">
                    <div class="settings-section-icon">
                        <i class="fa-solid fa-palette"></i>
                    </div>
                    <div>
                        <h2 class="settings-section-title">Izskats</h2>
                        <p class="settings-section-subtitle">Pielāgojiet lietotnes vizuālo stilu</p>
                    </div>
                </div>

                <form method="POST" action="" id="appearanceForm">
                    <input type="hidden" name="save_appearance" value="1">

                    <div class="settings-card">
                        <div class="settings-row">
                            <div class="settings-row-info">
                                <span class="settings-row-label">Krāsu shēma</span>
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

    <script src="../js/settings.js"></script>
</body>
</html>