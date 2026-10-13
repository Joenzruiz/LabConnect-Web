<?php
// Doctor portal sidebar — included in doctor pages
$doc_name_nav = $doc_name ?? 'Doctor';
$current = basename($_SERVER['PHP_SELF']);

$icon_appointments = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>';
$icon_schedule     = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
$icon_results      = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
$icon_logout       = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';
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
        <a href="appointments.php" class="<?= $current==='appointments.php'?'active':'' ?>">
            <span class="nav-icon"><?= $icon_appointments ?></span>
            <span>My Appointments</span>
        </a>
        <a href="schedule.php" class="<?= $current==='schedule.php'?'active':'' ?>">
            <span class="nav-icon"><?= $icon_schedule ?></span>
            <span>My Schedule</span>
        </a>
        <a href="results.php" class="<?= $current==='results.php'?'active':'' ?>">
            <span class="nav-icon"><?= $icon_results ?></span>
            <span>Lab Results</span>
        </a>
    </nav>
    <div style="margin-top:auto;padding:16px 8px 8px;">
        <a href="../api/logout.php" style="color:var(--danger);font-size:14px;text-decoration:none;display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:8px;">
            <span class="nav-icon" style="color:var(--danger);"><?= $icon_logout ?></span>
            <span>Logout</span>
        </a>
    </div>
</aside>
