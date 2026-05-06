<?php
/**
 * sidebar.php — Shared admin sidebar include
 *
 * Expects this variable to already be set by the including page:
 *   $active_page  (string)  — one of: 'dashboard', 'users'
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
        <a href="../../user/php/calendar.php" class="nav-item <?php echo ($active_page === 'calendar') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-calendar"></i></span>
            <span class="nav-text" data-i18n="nav.calendar"><?php echo ($_t ?? [])['nav.calendar'] ?? 'Kalendārs'; ?></span>
        </a>
        <a href="../../user/php/parskati.php" class="nav-item <?php echo ($active_page === 'parskati') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span>
            <span class="nav-text" data-i18n="nav.reports"><?php echo ($_t ?? [])['nav.reports'] ?? 'Pārskati'; ?></span>
        </a>
        <a href="../../user/php/budget.php" class="nav-item <?php echo ($active_page === 'budget') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-wallet"></i></span>
            <span class="nav-text" data-i18n="nav.budget"><?php echo ($_t ?? [])['nav.budget'] ?? 'Budžets'; ?></span>
        </a>
        <a href="../../user/php/settings.php" class="nav-item <?php echo ($active_page === 'settings') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-gear"></i></span>
            <span class="nav-text" data-i18n="nav.settings"><?php echo ($_t ?? [])['nav.settings'] ?? 'Iestatījumi'; ?></span>
        </a>
        <div class="nav-divider"></div>
        <a href="index.php" class="nav-item <?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-chart-pie"></i></span>
            <span class="nav-text" data-i18n="sidebar.dashboard"><?php echo ($_t ?? [])['sidebar.dashboard'] ?? 'Dashboard'; ?></span>
        </a>
        <a href="users.php" class="nav-item <?php echo ($active_page === 'users') ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
            <span class="nav-text" data-i18n="sidebar.users"><?php echo ($_t ?? [])['sidebar.users'] ?? 'Lietotāji'; ?></span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'administrator'); ?></div>
            </div>
            <a href="../../user/php/logout.php" class="user-logout" title="Atpakaļ uz lietotni">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>
