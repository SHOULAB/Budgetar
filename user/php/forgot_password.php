<?php
session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header('Location: calendar.php');
    exit();
}

require_once '../../assets/database.php';
require_once '../../assets/mailer.php';

// ─── Language detection ───────────────────────────────────────────────────────
$_supported   = ['lv', 'en'];
// Read the language passed from localStorage via the hidden form field
$_postedLang  = trim($_POST['_lang'] ?? '');
$_browserLang = (in_array($_postedLang, $_supported)) ? $_postedLang : 'lv';
$_traw = json_decode(file_get_contents(__DIR__ . '/translate.json'), true) ?? [];
$_t    = $_traw[$_browserLang] ?? $_traw['lv'];

// Ensure the password resets table exists
mysqli_query($savienojums,
    "CREATE TABLE IF NOT EXISTS BU_password_resets (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        token      VARCHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used       TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT NOW(),
        FOREIGN KEY (user_id) REFERENCES BU_users(id) ON DELETE CASCADE
    )");

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = $_t['forgot.err.empty'] ?? 'Lūdzu ievadiet savu e-pasta adresi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = $_t['forgot.err.invalid'] ?? 'Lūdzu ievadiet derīgu e-pasta adresi!';
    } else {
        // Look up user by email
        $stmt = mysqli_prepare($savienojums, "SELECT id, username FROM BU_users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res  = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($user) {
            // Delete any existing tokens for this user before creating a new one
            $del = mysqli_prepare($savienojums, "DELETE FROM BU_password_resets WHERE user_id = ?");
            mysqli_stmt_bind_param($del, "i", $user['id']);
            mysqli_stmt_execute($del);
            mysqli_stmt_close($del);

            // Generate a secure token valid for 1 hour
            $token      = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600);

            $ins = mysqli_prepare($savienojums,
                "INSERT INTO BU_password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($ins, "iss", $user['id'], $token, $expires_at);
            mysqli_stmt_execute($ins);
            mysqli_stmt_close($ins);

            // Build the reset link dynamically based on current script location
            $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host       = $_SERVER['HTTP_HOST'];
            $script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
            $reset_link = $protocol . '://' . $host . $script_dir . '/reset_password.php?token=' . urlencode($token);

            send_reset_email($email, $user['username'], $reset_link, $_browserLang);
        }

        // Always show the same message regardless (prevents user enumeration)
        if (!$error) {
            $success = $_t['forgot.success'] ?? 'Ja šī e-pasta adrese ir reģistrēta, tuvākajā laikā saņemsiet paroles atjaunošanas saiti.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgetar</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../../assets/image/logo.png" type="image/png">
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#14b8a6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="../../assets/image/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="login.php" class="back-link" data-i18n="forgot.back">← Atpakaļ</a>
                <h1 class="auth-title" data-i18n="forgot.title">Aizmirsta parole</h1>
                <p class="auth-subtitle" data-i18n="forgot.subtitle">Ievadiet savu e-pastu un mēs nosūtīsīm atjaunošanas saiti</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form class="auth-form" method="POST" action="">
                <input type="hidden" name="_lang" id="_lang" value="lv">
                <div class="form-group">
                    <label for="email" class="form-label" data-i18n="forgot.email.label">E-pasts</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="tavs@epasts.lv"
                        data-i18n-placeholder="forgot.email.placeholder"
                        required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-full" data-i18n="forgot.btn">
                    Nosūtīt atjaunošanas saiti
                </button>
            </form>
            <?php endif; ?>

            <div class="auth-footer">
                <p><span data-i18n="forgot.remembered">Atcerējāties paroli?</span> <a href="login.php" class="link" data-i18n="forgot.login.link">Ielogojieties</a></p>
            </div>
        </div>

        <div class="auth-visual">
            <div class="visual-content">
                <h2 class="visual-title" data-i18n="login.visual.title">Tavi finanšu mērķi gaida tevi</h2>
                <div class="visual-features">
                    <div class="visual-feature">
                        <span class="visual-icon"><i class="fa-solid fa-chart-simple"></i></span>
                        <span data-i18n="login.visual.feat1">Detalizēti ienākumu un izdevumu pārskati</span>
                    </div>
                    <div class="visual-feature">
                        <span class="visual-icon"><i class="fa-solid fa-calendar"></i></span>
                        <span data-i18n="login.visual.feat2">Kalendāra skats visām transakcijām</span>
                    </div>
                    <div class="visual-feature">
                        <span class="visual-icon"><i class="fa-solid fa-sack-dollar"></i></span>
                        <span data-i18n="login.visual.feat3">Izseko savu budžetu reāllaikā</span>
                    </div>
                    <div class="visual-feature">
                        <span class="visual-icon"><i class="fa-solid fa-bullseye"></i></span>
                        <span data-i18n="login.visual.feat4">Sasniedz savus finanšu mērķus</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>window._i18nData=<?php echo json_encode($_traw); ?>;window._i18nIsDefault=true;</script>
    <script src="../js/language.js"></script>
    <script src="../js/script.js"></script>
    <script>
        // Populate the hidden language field from localStorage so PHP knows the active language
        (function () {
            var lang = localStorage.getItem('budgetar_language') || 'lv';
            var el = document.getElementById('_lang');
            if (el) el.value = lang;
        })();
    </script>
</body>
</html>
