<?php
// Doctor portal sidebar — included in doctor pages
$doc_name_nav = $doc_name ?? 'Doctor';
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="brand">
        <div class="logo-slot" style="background: transparent; border: none;">
            <img src="../img/logo.jpg" alt="Logo" style="width: 42px; height: 42px; border-radius: 50%; object-fit: cover;">
        </div>
        <div>
            <strong style="font-size:13px;display:block;">Doctor Portal</strong>
            <span style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($doc_name_nav) ?></span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="appointments.php" class="<?= $current==='appointments.php'?'active':'' ?>">📅 My Appointments</a>
        <a href="schedule.php"     class="<?= $current==='schedule.php'?'active':'' ?>">🗓 My Schedule</a>
    </nav>
    <div style="margin-top:auto;padding:16px 8px 8px;">
        <a href="../api/logout.php" style="color:var(--danger);font-size:14px;text-decoration:none;">🚪 Logout</a>
    </div>
</aside>
