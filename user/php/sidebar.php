<?php
/**
 * sidebar.php — Shared sidebar include
 *
 * Expects these variables to already be set by the including page:
 *   $username     (string)  — logged-in user's display name
 *   $active_page  (string)  — one of: 'calendar', 'parskati', 'budget', 'settings'
 */
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <img src="../../assets/image/logo.png" alt="Budgetar Logo" class="logo-img">
            <span class="logo-text">Budgetar</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="calendar.php" class="nav-item <?php echo ($active_page === 'calendar')  ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-calendar"></i></span>
            <span class="nav-text" data-i18n="nav.calendar">Kalendārs</span>
        </a>
        <a href="parskati.php" class="nav-item <?php echo ($active_page === 'parskati') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span>
            <span class="nav-text" data-i18n="nav.reports">Pārskati</span>
        </a>
        <a href="budget.php" class="nav-item <?php echo ($active_page === 'budget')    ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-wallet"></i></span>
            <span class="nav-text" data-i18n="nav.budget">Budžets</span>
        </a>
        <?php if (($_SESSION['role'] ?? 'user') === 'administrator'): ?>
        <a href="../../admin/php/index.php" class="nav-item">
            <span class="nav-icon"><i class="fa-solid fa-shield-halved"></i></span>
            <span class="nav-text">Admin Panel</span>
        </a>
        <?php endif; ?>
        <a href="settings.php" class="nav-item <?php echo ($active_page === 'settings')  ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-gear"></i></span>
            <span class="nav-text" data-i18n="nav.settings">Iestatījumi</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'user'); ?></div>
            </div>
            <a href="logout.php" class="user-logout" title="Iziet"
               onclick="event.preventDefault(); showLogoutConfirm();">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>