<?php
session_start();
require_once('../../assets/database.php');

if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role'] ?? ''), ['administrator', 'moderator'])) {
    header("Location: ../../user/php/login.php");
    exit();
}

$stats = [
    'total_users' => 0,
    'active_users' => 0,
    'total_transactions' => 0,
    'total_income' => 0,
    'total_expenses' => 0
];

$result = mysqli_query($savienojums, "SELECT COUNT(*) as count FROM BU_users");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $stats['total_users'] = $row['count'];
}

$system_info = [
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_version' => mysqli_get_server_info($savienojums)
];

// ── Load language + translations ──────────────────────────────────────────────
$_lang = $_SESSION['language'] ?? 'lv';
$_traw = json_decode(file_get_contents(__DIR__ . '/translate.json'), true) ?? [];
$_t    = $_traw[$_lang] ?? $_traw['lv'] ?? [];
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($_t['dashboard.page.title'] ?? 'Dashboard'); ?> - Budgetar</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../../assets/image/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php $active_page = 'dashboard'; include 'sidebar.php'; ?>

        <main class="admin-content">
            <div class="admin-header">
                <h1 class="admin-title" data-i18n="dashboard.page.title"><?php echo $_t['dashboard.page.title'] ?? 'Dashboard'; ?></h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label" data-i18n="dashboard.stat.total.users"><?php echo $_t['dashboard.stat.total.users'] ?? 'Kopā lietotāji'; ?></div>
                            <div class="stat-card-value"><?php echo number_format($stats['total_users']); ?></div>
                        </div>
                        <div class="stat-card-icon"><i class="fa-solid fa-user"></i></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label" data-i18n="dashboard.stat.active"><?php echo $_t['dashboard.stat.active'] ?? 'Aktīvi lietotāji'; ?></div>
                            <div class="stat-card-value"><?php echo number_format($stats['active_users']); ?></div>
                        </div>
                        <div class="stat-card-icon"><i class="fa-solid fa-check"></i></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label" data-i18n="dashboard.stat.tx"><?php echo $_t['dashboard.stat.tx'] ?? 'Transakcijas'; ?></div>
                            <div class="stat-card-value"><?php echo number_format($stats['total_transactions']); ?></div>
                        </div>
                        <div class="stat-card-icon"><i class="fa-solid fa-credit-card"></i></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label" data-i18n="dashboard.stat.status"><?php echo $_t['dashboard.stat.status'] ?? 'Sistēmas statuss'; ?></div>
                            <div class="stat-card-value" style="font-size: 20px;" data-i18n="dashboard.stat.status.val"><?php echo $_t['dashboard.stat.status.val'] ?? 'Online'; ?></div>
                        </div>
                        <div class="stat-card-icon"><i class="fa-solid fa-globe"></i></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/script.js"></script>
    <script>window._i18nData=<?php echo json_encode($_traw); ?>;window._i18nLang=<?php echo json_encode($_lang); ?>;window._i18nIsDefault=false;</script>
    <script src="../../user/js/language.js"></script>
</body>
</html>