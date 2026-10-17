<?php
require_once __DIR__ . '/../config/base_url.php';

$role = $_SESSION['user_role'] ?? '';
$current = $_SERVER['PHP_SELF'];

function nav($href, $iconSvg, $label, $current) {
    $active = strpos($current, $href) !== false ? 'active' : '';
    return "<a href=\"$href\" class=\"$active\"><span class="nav-icon">$iconSvg</span><span>$label</span></a>";
}

// Crisp, themed SVG icons (outline stroke) matching --teal theme
$icon_dashboard    = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>';
$icon_patients     = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$icon_doctors      = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.8 2.3A.3.3 0 1 0 5 2H4a2 2 0 0 0-2 2v5a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6V4a2 2 0 0 0-2-2h-1a.2.2 0 1 0 .3.3"/><path d="M8 15v1a6 6 0 0 0 6 6v0a6 6 0 0 0 6-6v-4"/><circle cx="20" cy="10" r="2"/></svg>';
$icon_tests        = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2v6a2 2 0 0 0 .58 1.42l5.42 5.42A4 4 0 0 1 17.17 22H6.83a4 4 0 0 1-2.83-6.83l5.42-5.42A2 2 0 0 0 10 8V2"/><path d="M8 2h8"/><path d="M7 16h10"/></svg>';
$icon_appointments = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>';
$icon_schedule     = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
$icon_results      = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
$icon_reports      = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>';
$icon_users        = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$icon_settings     = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>';
$icon_logout       = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';
?>
<aside class="sidebar">
    <div class="brand">
        <div class="logo-slot" style="background: transparent; border: none;">
            <img src="<?= BASE_URL ?>/img/logo.jpg" alt="Logo" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover;">
        </div>
        <div>
            <strong>SLIMM LAB</strong>
            <span><?= ucfirst($role) ?></span>
        </div>
    </div>

    <?php if ($role === 'admin' || $role === 'staff'): ?>
        <div class="nav-title">Main</div>
        <?= nav(BASE_URL . '/admin/dashboard.php', $icon_dashboard, 'Dashboard', $current) ?>
        <?= nav(BASE_URL . '/admin/patients/', $icon_patients, 'Patients', $current) ?>
        <?= nav(BASE_URL . '/admin/doctors/', $icon_doctors, 'Doctors', $current) ?>
        <?= nav(BASE_URL . '/admin/tests/', $icon_tests, 'Lab Tests', $current) ?>
        <?= nav(BASE_URL . '/admin/appointments/', $icon_appointments, 'Appointments', $current) ?>
        <?= nav(BASE_URL . '/admin/schedule/', $icon_schedule, 'Schedule', $current) ?>
        <?= nav(BASE_URL . '/admin/results/', $icon_results, 'Results', $current) ?>
        <?= nav(BASE_URL . '/admin/reports/', $icon_reports, 'Reports', $current) ?>

        <?php if ($role === 'admin'): ?>
            <div class="nav-title">Admin</div>
            <?= nav(BASE_URL . '/admin/users/', $icon_users, 'Users', $current) ?>
            <?= nav(BASE_URL . '/admin/settings/', $icon_settings, 'Settings', $current) ?>
        <?php endif; ?>
    <?php elseif ($role === 'doctor'): ?>
        <div class="nav-title">Doctor Portal</div>
        <?= nav(BASE_URL . '/doctor/appointments.php', $icon_appointments, 'My Appointments', $current) ?>
        <?= nav(BASE_URL . '/doctor/schedule.php', $icon_schedule, 'My Schedule', $current) ?>
        <?= nav(BASE_URL . '/doctor/results.php', $icon_results, 'Lab Results', $current) ?>
    <?php endif; ?>

    <div style="margin-top: auto; padding: 16px 8px 0;">
        <a href="<?= BASE_URL ?>/api/logout.php" style="color: var(--danger); display: flex; align-items: center; gap: 11px; padding: 10px 12px; border-radius: 8px; text-decoration: none;">
            <span class="nav-icon" style="color: var(--danger);"><?= $icon_logout ?></span>
            <span>Logout</span>
        </a>
    </div>
</aside>
