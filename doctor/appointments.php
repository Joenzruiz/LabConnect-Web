<?php
session_start();
require_once '../config/database.php';
if (($_SESSION['user_role'] ?? '') !== 'doctor') { header('Location: ../index.php'); exit; }

$doctor = $pdo->prepare("SELECT * FROM doctors WHERE user_id=?");
$doctor->execute([$_SESSION['user_id']]);
$doctor = $doctor->fetch(PDO::FETCH_ASSOC);
if (!$doctor) { session_destroy(); header('Location: ../index.php'); exit; }
$doc_id   = $doctor['doctor_id'];
$doc_name = 'Dr. '.$doctor['first_name'].' '.$doctor['last_name'];

$status = $_GET['status'] ?? '';
$q      = $_GET['q']      ?? '';

$sql = "SELECT a.*, p.first_name||' '||p.last_name as patient_name,
    STRING_AGG(t.test_name, ', ') as test_name
    FROM appointments a
    JOIN patients p ON a.patient_id=p.patient_id
    LEFT JOIN appointment_details ad ON a.appointment_id=ad.appointment_id
    LEFT JOIN laboratory_tests t ON ad.test_id=t.test_id
    WHERE a.doctor_id=?";
$params = [$doc_id];
if ($status) { $sql .= " AND a.status=?"; $params[] = $status; }
if ($q) {
    $sql .= " AND (p.first_name||' '||p.last_name ILIKE ? OR t.test_name ILIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%";
}
$sql .= " GROUP BY a.appointment_id, p.first_name, p.last_name ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$appts = $pdo->prepare($sql);
$appts->execute($params);
$appts = $appts->fetchAll(PDO::FETCH_ASSOC);

$badges = ['Pending'=>'badge-pending','Confirmed'=>'badge-confirmed','Completed'=>'badge-completed','Cancelled'=>'badge-cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Appointments — <?= htmlspecialchars($doc_name) ?></title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>My Appointments</h1><p>Your assigned patient appointments.</p></div>
        <div class="user-area"><span><?= htmlspecialchars($doc_name) ?></span>
            <a href="../api/logout.php" class="btn">Logout</a>
        </div>
    </header>

    <div class="panel" style="margin:24px 28px;">
        <div class="panel-header">
            <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;width:100%">
                <input class="input search" name="q" placeholder="Search patient or test…" value="<?= htmlspecialchars($q) ?>" style="flex:1;min-width:200px">
                <select class="input" name="status" onchange="this.form.submit()" style="width:160px">
                    <option value="">All Statuses</option>
                    <?php foreach (['Pending','Confirmed','Completed','Cancelled'] as $s): ?>
                    <option <?= $status===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary">Search</button>
                <?php if ($q||$status): ?><a class="btn" href="appointments.php">Clear</a><?php endif; ?>
            </form>
        </div>
        <div class="table-card table-scroll">
        <table>
            <thead><tr><th>#</th><th>Patient</th><th>Test</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($appts as $a): ?>
            <tr>
                <td><?= $a['appointment_id'] ?></td>
                <td><strong><?= htmlspecialchars($a['patient_name']) ?></strong></td>
                <td><?= htmlspecialchars($a['test_name'] ?? '—') ?></td>
                <td><?= $a['appointment_date'] ?></td>
                <td><?= substr($a['appointment_time'],0,5) ?></td>
                <td><span class="badge <?= $badges[$a['status']] ?? 'badge-neutral' ?>"><?= $a['status'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$appts): ?><tr><td colspan="6" class="empty">No appointments found.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</main>
</body></html>
