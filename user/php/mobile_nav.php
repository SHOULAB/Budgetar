<?php
/**
 * mobile_nav.php — Shared mobile bottom navigation include
 * Mirrors the desktop user sidebar: user pages + admin pages (if admin/mod) + logout.
 *
 * Expects:
 *   $active_page  (string)  — one of: 'calendar', 'parskati', 'budget', 'settings', 'dashboard', 'users'
 */
?>
<nav class="mobile-bottom-nav">
    <a href="calendar.php" class="mobile-nav-item <?php echo ($active_page === 'calendar') ? 'active' : ''; ?>">
        <i class="fa-solid fa-calendar"></i>
        <span data-i18n="nav.calendar">Kalendārs</span>
    </a>
    <a href="parskati.php" class="mobile-nav-item <?php echo ($active_page === 'parskati') ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-pie"></i>
        <span data-i18n="nav.reports">Pārskati</span>
    </a>
    <a href="budget.php" class="mobile-nav-item <?php echo ($active_page === 'budget') ? 'active' : ''; ?>">
        <i class="fa-solid fa-wallet"></i>
        <span data-i18n="nav.budget">Budžets</span>
    </a>
    <a href="settings.php" class="mobile-nav-item <?php echo ($active_page === 'settings') ? 'active' : ''; ?>">
        <i class="fa-solid fa-gear"></i>
        <span data-i18n="nav.settings">Iestatījumi</span>
    </a>
    <?php if (in_array(strtolower($_SESSION['role'] ?? 'user'), ['administrator', 'moderator'])): ?>
    <button type="button" class="mobile-nav-item mobile-nav-more-btn" id="mobileMoreBtn" aria-expanded="false">
        <i class="fa-solid fa-ellipsis"></i>
        <span data-i18n="nav.more">Vairāk</span>
    </button>
    <?php else: ?>
    <a href="logout.php" class="mobile-nav-item">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span data-i18n="nav.logout">Iziet</span>
    </a>
    <?php endif; ?>
</nav>

<?php if (in_array(strtolower($_SESSION['role'] ?? 'user'), ['administrator', 'moderator'])): ?>
<div class="mobile-more-overlay" id="mobileMoreOverlay"></div>
<div class="mobile-more-sheet" id="mobileMoreSheet" aria-hidden="true">
    <div class="mobile-more-handle"></div>
    <a href="../../admin/php/index.php" class="mobile-more-item <?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-pie"></i>
        <span data-i18n="sidebar.dashboard">Dashboard</span>
    </a>
    <a href="../../admin/php/users.php" class="mobile-more-item <?php echo ($active_page === 'users') ? 'active' : ''; ?>">
        <i class="fa-solid fa-users"></i>
        <span data-i18n="sidebar.users">Lietotāji</span>
    </a>
    <a href="logout.php" class="mobile-more-item">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span data-i18n="nav.logout">Iziet</span>
    </a>
</div>

<script>
(function () {
    var btn     = document.getElementById('mobileMoreBtn');
    var sheet   = document.getElementById('mobileMoreSheet');
    var overlay = document.getElementById('mobileMoreOverlay');
    if (!btn || !sheet || !overlay) return;
    function open()  { sheet.classList.add('open'); overlay.classList.add('open'); btn.setAttribute('aria-expanded', 'true');  sheet.setAttribute('aria-hidden', 'false'); }
    function close() { sheet.classList.remove('open'); overlay.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); sheet.setAttribute('aria-hidden', 'true'); }
    btn.addEventListener('click', function () { sheet.classList.contains('open') ? close() : open(); });
    overlay.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>
<?php endif; ?>