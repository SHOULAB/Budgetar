<?php
session_start();
require_once('../../assets/database.php');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'administrator') {
    header("Location: ../../user/php/login.php");
    exit();
}

$success = '';
$error = '';

// user edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        $action = $_POST['action'];

        if ($action === 'delete') {
            if (isset($_SESSION['user_id']) && $user_id == $_SESSION['user_id']) {
                $error = 'Jūs nevarat dzēst savu kontu!';
            } else {
                $stmt = mysqli_prepare($savienojums, "DELETE FROM BU_users WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $user_id);

                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Lietotājs veiksmīgi dzēsts!';
                } else {
                    $error = 'Kļūda dzēšot lietotāju!';
                }
                mysqli_stmt_close($stmt);
            }
        }

        if ($action === 'toggle_role') {
            $stmt = mysqli_prepare($savienojums, "SELECT role FROM BU_users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $current_role);
            mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            $new_role = ($current_role === 'administrator') ? 'user' : 'administrator';
            $stmt = mysqli_prepare($savienojums, "UPDATE BU_users SET role = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new_role, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = $new_role === 'administrator' ? 'Lietotājs veiksmīgi iecelts par administratoru!' : 'Administratora tiesības veiksmīgi atsauktas!';
            } else {
                $error = 'Kļūda mainīot lomās!';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// search bar
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT id, username, email, role, created_at FROM BU_users WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY created_at DESC";

if (!empty($params)) {
    $stmt = mysqli_prepare($savienojums, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($savienojums, $query);
}

$users = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

$total_users = count($users);
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lietotāju pārvaldība - Budgetar</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="icon" href="../../assets/image/logo.png" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="admin-logo">
                <img src="../../assets/image/logo.png" alt="Budgetar Logo" class="logo-img">
                <span style="font-size: 20px; font-weight: 700;">Admin Panel</span>
            </div>

            <nav class="admin-nav">
                <a href="index.php" class="admin-nav-item">
                    <span class="admin-nav-icon"><i class="fa-solid fa-chart-pie"></i></span>
                    <span>Dashboard</span>
                </a>
                <a href="users.php" class="admin-nav-item active">
                    <span class="admin-nav-icon"><i class="fa-solid fa-user"></i></span>
                    <span>Lietotāji</span>
                </a>
                <a href="settings.php" class="admin-nav-item">
                    <span class="admin-nav-icon"><i class="fa-solid fa-gear"></i></span>
                    <span>Iestatījumi</span>
                </a>
            </nav>

            <div style="margin-top: auto;">
                <a href="../../user/php/calendar.php" class="admin-nav-item" style="color: var(--text-secondary);">
                    <span class="admin-nav-icon"><i class="fa-solid fa-door-closed"></i></span>
                    <span>Iziet</span>
                </a>
            </div>
        </aside>

        <main class="admin-content">
            <div class="admin-header">
                <h1 class="admin-title">Lietotāju pārvaldība</h1>
                <div class="admin-user">
                    <span><i class="fa-solid fa-user-tie"></i></span>
                    <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error" style="margin-bottom: 24px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 24px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="toolbar">
                <div class="search-box">
                    <span class="search-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <form method="GET" action="">
                        <input
                            type="text"
                            name="search"
                            class="search-input"
                            placeholder="Meklēt lietotājus..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </form>
                </div>
            </div>

            <div class="users-table-container">
                <div class="table-header">
                    <h2 class="table-title">Lietotāji</h2>
                    <span class="table-count"><?php echo $total_users; ?> rezultāti</span>
                </div>

                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div class="empty-text">Nav atrasti lietotāji</div>
                        <div class="empty-subtext">Mēģiniet mainīt meklēšanas kritērijus</div>
                    </div>
                <?php else: ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Lietotājs</th>
                                <th>Reģistrācijas datums</th>
                                <th>Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details">
                                                <span class="user-name"><?php echo htmlspecialchars($user['username']); ?></span>
                                                <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                                <span class="user-role-badge user-role-badge--<?php echo $user['role']; ?>"><?php echo htmlspecialchars($user['role']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn btn-edit" onclick="alert('Edit funkcionalitāte tiks pievienota drīzumā')">
                                                <i class="fa-solid fa-pencil"></i> Rediģēt
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="action-btn <?php echo $user['role'] === 'administrator' ? 'btn-revoke' : 'btn-promote'; ?>">
                                                    <?php if ($user['role'] === 'administrator'): ?>
                                                        <i class="fa-solid fa-shield-halved"></i> Atsaukt
                                                    <?php else: ?>
                                                        <i class="fa-solid fa-shield-halved"></i> Administrators
                                                    <?php endif; ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Vai tiešām vēlaties dzēst šo lietotāju?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="action-btn btn-delete">
                                                    <i class="fa-solid fa-trash"></i> Dzēst
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../js/script.js"></script>
</body>
</html>