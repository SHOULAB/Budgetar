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

        if ($action === 'deactivate') {
            if (isset($_SESSION['user_id']) && $user_id == $_SESSION['user_id']) {
                $error = 'Jūs nevarat deāktivēt savu kontu!';
            } else {
                $stmt = mysqli_prepare($savienojums, "UPDATE BU_users SET is_active = 0 WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "i", $user_id);

                if (mysqli_stmt_execute($stmt)) {
                    $success = 'Lietotājs veiksmīgi deāktivēts!';
                } else {
                    $error = 'Kļūda deāktivējot lietotāju!';
                }
                mysqli_stmt_close($stmt);
            }
        }

        if ($action === 'activate') {
            $stmt = mysqli_prepare($savienojums, "UPDATE BU_users SET is_active = 1 WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Lietotājs veiksmīgi aktivēts!';
            } else {
                $error = 'Kļūda aktivējot lietotāju!';
            }
            mysqli_stmt_close($stmt);
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

$query = "SELECT id, username, email, role, created_at, last_login, is_active FROM BU_users WHERE 1=1";
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
        <?php $active_page = 'users'; include 'sidebar.php'; ?>

        <main class="admin-content">
            <div class="admin-header">
                <h1 class="admin-title">Lietotāju pārvaldība</h1>
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
                                <th>Pēdējā pieslēgšanās</th>
                                <th>Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr class="<?php echo !$user['is_active'] ? 'row-deactivated' : ''; ?>">
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details">
                                                <span class="user-name">
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                    <?php if (!$user['is_active']): ?><span class="badge-deactivated">Deāktivēts</span><?php endif; ?>
                                                </span>
                                                <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="td-muted"><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td class="td-muted"><?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : '—'; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="tbl-btn tbl-btn--edit" onclick="alert('Edit funkcionalitāte tiks pievienota drīzumā')" title="Rediģēt">
                                                <i class="fa-solid fa-pencil"></i>
                                            </button>
                                            <?php if ($user['is_active']): ?>
                                            <button class="tbl-btn tbl-btn--delete"
                                                title="Deāktivēt"
                                                onclick="openDeactivateModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')"
                                            >
                                                <i class="fa-solid fa-ban"></i>
                                            </button>
                                            <?php else: ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="activate">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="tbl-btn tbl-btn--activate" title="Aktivēt">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
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

    <!-- Deactivate confirmation modal -->
    <div id="deactivateModal" class="adm-modal" style="display:none;">
        <div class="adm-modal-box">
            <div class="adm-modal-icon"><i class="fa-solid fa-ban"></i></div>
            <h2 class="adm-modal-title">Deāktivēt kontu?</h2>
            <p class="adm-modal-desc">Lietotājs <strong id="deactivateUsername"></strong> nevarēs piekļūst savam kontam, līdz tas tiks aktivēts atkārtoti.</p>
            <div class="adm-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeactivateModal()">Atcelt</button>
                <form method="POST" id="deactivateForm" style="display:inline;">
                    <input type="hidden" name="action" value="deactivate">
                    <input type="hidden" name="user_id" id="deactivateUserId">
                    <button type="submit" class="btn btn-danger">Deāktivēt</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function openDeactivateModal(userId, username) {
        document.getElementById('deactivateUserId').value = userId;
        document.getElementById('deactivateUsername').textContent = username;
        document.getElementById('deactivateModal').style.display = 'flex';
    }
    function closeDeactivateModal() {
        document.getElementById('deactivateModal').style.display = 'none';
    }
    document.getElementById('deactivateModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeactivateModal();
    });
    </script>
</body>
</html>