<?php
$role = $_SESSION['user_role'] ?? '';
$current = $_SERVER['PHP_SELF'];
function nav($href, $label, $current) {
    $active = strpos($current, $href) !== false ? 'active' : '';
    return "<a href=\"$href\" class=\"$active\">$label</a>";
}
?>
<aside class="sidebar">
    <div class="brand">
        <div class="logo-slot" style="background: transparent; border: none;">
            <img src="/LabConnect-Web/img/logo.jpg" alt="Logo" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover;">
        </div>
        <div>
            <strong>SLIMM LAB</strong>
            <span><?= ucfirst($role) ?></span>
        </div>
    </div>

    <?php if ($role === 'admin' || $role === 'staff'): ?>
    <div class="nav-title">Main</div>
    <?= nav('/LabConnect-Web/admin/dashboard.php', '📊 Dashboard', $current) ?>
    <?= nav('/LabConnect-Web/admin/patients/', '👤 Patients', $current) ?>
    <?= nav('/LabConnect-Web/admin/doctors/', '🩺 Doctors', $current) ?>
    <?= nav('/LabConnect-Web/admin/tests/', '🧪 Lab Tests', $current) ?>
    <?= nav('/LabConnect-Web/admin/appointments/', '📅 Appointments', $current) ?>
    <?= nav('/LabConnect-Web/admin/schedule/', '🗓 Schedule', $current) ?>
    <?= nav('/LabConnect-Web/admin/results/', '📄 Results', $current) ?>
    <?= nav('/LabConnect-Web/admin/reports/', '📈 Reports', $current) ?>

    <?php if ($role === 'admin'): ?>
    <div class="nav-title">Admin</div>
    <?= nav('/LabConnect-Web/admin/users/', '👥 Users', $current) ?>
    <?= nav('/LabConnect-Web/admin/settings/', '⚙️ Settings', $current) ?>
    <?php endif; ?>

    <?php elseif ($role === 'doctor'): ?>
    <div class="nav-title">Doctor Portal</div>
    <?= nav('/LabConnect-Web/doctor/appointments.php', '📅 My Appointments', $current) ?>
    <?= nav('/LabConnect-Web/doctor/schedule.php', '🗓 My Schedule', $current) ?>
    <?php endif; ?>

    <div style="margin-top: auto; padding: 16px 8px 0;">
        <a href="/LabConnect-Web/api/logout.php" style="color: var(--danger);">🚪 Logout</a>
    </div>
</aside>
