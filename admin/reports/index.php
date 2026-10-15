<?php
session_start();
require_once '../../config/database.php';
if (!in_array($_SESSION['user_role'] ?? '', ['admin','staff'])) { header('Location: ../../index.php'); exit; }

// Date range
$preset = $_GET['preset'] ?? 'month';
switch ($preset) {
    case 'today':  $from = $to = date('Y-m-d'); break;
    case 'week':   $from = date('Y-m-d', strtotime('monday this week')); $to = date('Y-m-d', strtotime('sunday this week')); break;
    case 'year':   $from = date('Y-01-01'); $to = date('Y-12-31'); break;
    default:       $from = date('Y-m-01'); $to = date('Y-m-t'); break; // month
}
if (!empty($_GET['from'])) $from = $_GET['from'];
if (!empty($_GET['to']))   $to   = $_GET['to'];

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_'.date('Ymd').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['#','Patient','Test','Date','Status','Price']);
    $rows = $pdo->prepare("SELECT a.appointment_id, p.first_name||' '||p.last_name as patient, t.test_name, a.appointment_date, a.status, ad.price_at_booking as price
        FROM appointments a JOIN patients p ON a.patient_id=p.patient_id JOIN appointment_details ad ON a.appointment_id=ad.appointment_id JOIN laboratory_tests t ON ad.test_id=t.test_id
        WHERE a.appointment_date BETWEEN ? AND ? ORDER BY a.appointment_date");
    $rows->execute([$from,$to]);
    foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r)
        fputcsv($out, [$r['appointment_id'],$r['patient'],$r['test_name'],$r['appointment_date'],$r['status'],$r['price']]);
    fclose($out); exit;
}

// Summary
$summary = $pdo->prepare("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status='Cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN status='Confirmed' THEN 1 ELSE 0 END) as approved
    FROM appointments WHERE appointment_date BETWEEN ? AND ?");
$summary->execute([$from,$to]);
$summary = $summary->fetch(PDO::FETCH_ASSOC);

$revenue = $pdo->prepare("SELECT COALESCE(SUM(ad.price_at_booking),0) as total FROM appointments a JOIN appointment_details ad ON a.appointment_id=ad.appointment_id WHERE a.status='Completed' AND a.appointment_date BETWEEN ? AND ?");
$revenue->execute([$from,$to]);
$revenue = $revenue->fetchColumn();

$rows = $pdo->prepare("SELECT a.appointment_id, p.first_name||' '||p.last_name as patient, t.test_name, a.appointment_date, a.status, ad.price_at_booking as price
    FROM appointments a JOIN patients p ON a.patient_id=p.patient_id JOIN appointment_details ad ON a.appointment_id=ad.appointment_id JOIN laboratory_tests t ON ad.test_id=t.test_id
    WHERE a.appointment_date BETWEEN ? AND ? ORDER BY a.appointment_date DESC LIMIT 100");
$rows->execute([$from,$to]);
$rows = $rows->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reports - SLIMM Lab</title>
<link rel="stylesheet" href="../../css/style.css">
<style>@media print { .sidebar,.topbar,.no-print { display:none!important; } .content { margin:0!important; padding:0!important; } }</style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>Reports</h1><p>Analytics and data export.</p></div>
        <div class="user-area no-print">
            <a class="btn" href="?<?= http_build_query(array_merge($_GET,['export'=>'csv'])) ?>">📥 Export CSV</a>
            <button class="btn btn-primary" onclick="window.print()">🖨 Print PDF</button>
        </div>
    </header>

    <div class="page-body">
    <!-- Filters -->
    <form method="GET" class="panel no-print" style="margin-bottom:20px;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
            <strong>Period:</strong>
            <?php foreach (['today'=>'Today','week'=>'This Week','month'=>'This Month','year'=>'This Year'] as $k=>$v): ?>
            <a class="btn <?= $preset===$k?'btn-primary':'' ?>" href="?preset=<?= $k ?>"><?= $v ?></a>
            <?php endforeach; ?>
            <span style="color:var(--muted)">or custom:</span>
            <input class="input" type="date" name="from" value="<?= $from ?>" style="width:140px">
            <span>–</span>
            <input class="input" type="date" name="to" value="<?= $to ?>" style="width:140px">
            <button class="btn btn-primary">Apply</button>
        </div>
    </form>

    <!-- Summary Cards -->
    <div class="cards" style="grid-template-columns:repeat(5,1fr);margin-bottom:20px;">
        <div class="card"><div class="stat-label">Total Appointments</div><div class="stat-value"><?= $summary['total'] ?></div></div>
        <div class="card"><div class="stat-label">Completed</div><div class="stat-value" style="color:var(--success)"><?= $summary['completed'] ?></div></div>
        <div class="card"><div class="stat-label">Pending</div><div class="stat-value" style="color:var(--warning)"><?= $summary['pending'] ?></div></div>
        <div class="card"><div class="stat-label">Cancelled</div><div class="stat-value" style="color:var(--danger)"><?= $summary['cancelled'] ?></div></div>
        <div class="card"><div class="stat-label">Revenue</div><div class="stat-value" style="color:var(--teal)">₱<?= number_format($revenue,2) ?></div></div>
    </div>

    <!-- Detail Table -->
    <div class="panel">
        <div class="panel-header"><h3 class="panel-title">Appointment Records</h3><p class="panel-subtitle"><?= $from ?> to <?= $to ?></p></div>
        <div class="table-card table-scroll">
        <table>
            <thead><tr><th>#</th><th>Patient</th><th>Test</th><th>Date</th><th>Status</th><th>Price</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= $r['appointment_id'] ?></td>
                <td><?= htmlspecialchars($r['patient']) ?></td>
                <td><?= htmlspecialchars($r['test_name']) ?></td>
                <td><?= $r['appointment_date'] ?></td>
                <td><?= $r['status'] ?></td>
                <td>₱<?= number_format($r['price'],2) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="6" class="empty">No data for this period.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</main>
</body></html>
