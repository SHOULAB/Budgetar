<?php
/**
 * mobile_nav.php — Admin mobile bottom navigation
 *
 * Expects:
 *   $active_page  (string)  — one of: 'dashboard', 'users', 'settings'
 */
?>
<nav class="mobile-bottom-nav">
    <a href="index.php" class="mobile-nav-item <?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-pie"></i>
        <span data-i18n="sidebar.dashboard"><?php echo ($_t ?? [])['sidebar.dashboard'] ?? 'Dashboard'; ?></span>
    </a>
    <a href="users.php" class="mobile-nav-item <?php echo ($active_page === 'users') ? 'active' : ''; ?>">
        <i class="fa-solid fa-users"></i>
        <span data-i18n="sidebar.users"><?php echo ($_t ?? [])['sidebar.users'] ?? 'Lietotāji'; ?></span>
    </a>
    <a href="settings.php" class="mobile-nav-item <?php echo ($active_page === 'settings') ? 'active' : ''; ?>">
        <i class="fa-solid fa-gear"></i>
        <span data-i18n="sidebar.settings"><?php echo ($_t ?? [])['sidebar.settings'] ?? 'Iestatījumi'; ?></span>
    </a>
    <a href="../../user/php/calendar.php" class="mobile-nav-item">
        <i class="fa-solid fa-calendar"></i>
        <span data-i18n="sidebar.user.panel"><?php echo ($_t ?? [])['sidebar.user.panel'] ?? 'User Panel'; ?></span>
    </a>
    <a href="../../user/php/logout.php" class="mobile-nav-item">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span data-i18n="nav.logout"><?php echo ($_t ?? [])['nav.logout'] ?? 'Iziet'; ?></span>
    </a>
</nav>
