<?php
session_start();
require_once '../../config/database.php';
if (!in_array($_SESSION['user_role'] ?? '', ['admin','staff'])) { header('Location: ../../index.php'); exit; }

// Quick approve/reject/complete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $appt_id = (int)$_POST['appointment_id'];
    $map = ['approve'=>'Confirmed','reject'=>'Cancelled','complete'=>'Completed'];
    if (isset($map[$action]) && $appt_id) {
        $pdo->prepare("UPDATE appointments SET status=?, approved_by=? WHERE appointment_id=?")
            ->execute([$map[$action], $_SESSION['user_id'], $appt_id]);
        if ($action === 'approve' || $action === 'reject') {
            $appt = $pdo->prepare("SELECT a.patient_id, STRING_AGG(t.test_name,', ') as test_name, d.first_name||' '||d.last_name as doc, a.appointment_date, a.appointment_time FROM appointments a LEFT JOIN doctors d ON a.doctor_id=d.doctor_id LEFT JOIN appointment_details ad ON ad.appointment_id=a.appointment_id LEFT JOIN laboratory_tests t ON t.test_id=ad.test_id WHERE a.appointment_id=? GROUP BY a.patient_id, d.first_name, d.last_name, a.appointment_date, a.appointment_time");
            $appt->execute([$appt_id]);
            $appt = $appt->fetch(PDO::FETCH_ASSOC);
            if ($appt) {
                if ($action === 'approve') {
                    $title = 'Appointment Confirmed';
                    $msg = "Your {$appt['test_name']} appointment".($appt['doc']?" with Dr. {$appt['doc']}":'')." on {$appt['appointment_date']} has been approved. Please arrive 15 minutes early.";
                } else {
                    $title = 'Appointment Cancelled';
                    $msg = "Your {$appt['test_name']} appointment on {$appt['appointment_date']} has been cancelled. Please contact us for more information or book a new schedule.";
                }
                $pdo->prepare("INSERT INTO notifications (patient_id,title,message,notification_type,is_read) VALUES (?,?,?,?,false)")
                    ->execute([$appt['patient_id'], $title, $msg, 'appointment']);
            }
        }
    }
    header('Location: index.php?'.http_build_query($_GET)); exit;
}

$status_filter = $_GET['status'] ?? '';
$q = $_GET['q'] ?? '';

$sql = "SELECT a.*, p.first_name||' '||p.last_name as patient_name,
    COALESCE(d.first_name||' '||d.last_name,'—') as doctor_name,
    STRING_AGG(t.test_name, ', ') as test_name,
    COALESCE(SUM(ad.price_at_booking),0) as price
    FROM appointments a
    JOIN patients p ON a.patient_id=p.patient_id
    LEFT JOIN doctors d ON a.doctor_id=d.doctor_id
    LEFT JOIN appointment_details ad ON ad.appointment_id=a.appointment_id
    LEFT JOIN laboratory_tests t ON t.test_id=ad.test_id
    WHERE 1=1";
$params = [];
if ($status_filter) { $sql .= " AND a.status=?"; $params[] = $status_filter; }
if ($q) { $sql .= " AND (p.first_name||' '||p.last_name ILIKE ? OR t.test_name ILIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
$sql .= " GROUP BY a.appointment_id, p.first_name, p.last_name, d.first_name, d.last_name ORDER BY a.created_at DESC, a.appointment_id DESC";

$appts = $pdo->prepare($sql);
$appts->execute($params);
$appts = $appts->fetchAll(PDO::FETCH_ASSOC);

$status_badge = ['Pending'=>'badge-pending','Confirmed'=>'badge-confirmed','Completed'=>'badge-completed','Cancelled'=>'badge-cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Appointments - SLIMM Lab</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>Appointments</h1><p>Manage all laboratory appointment requests.</p></div>
        <div class="user-area"><span><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></span></div>
    </header>

    <div class="panel">
        <div class="toolbar">
            <form method="GET" class="toolbar-left" style="display:flex;gap:8px;flex-wrap:wrap;">
                <input class="input search" name="q" placeholder="Search patient or test…" value="<?= htmlspecialchars($q) ?>">
                <select class="input" name="status" style="width:160px" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php foreach (['Pending','Confirmed','Completed','Cancelled'] as $s): ?>
                    <option <?= $status_filter===$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary">Search</button>
                <?php if ($q||$status_filter): ?><a class="btn" href="index.php">Clear</a><?php endif; ?>
            </form>
        </div>

        <div class="table-card table-scroll">
        <table>
            <thead><tr><th>#</th><th>Patient</th><th>Test</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th><th>Price</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($appts as $a): ?>
            <tr>
                <td><?= $a['appointment_id'] ?></td>
                <td><strong><?= htmlspecialchars($a['patient_name']) ?></strong></td>
                <td><?= htmlspecialchars($a['test_name']) ?></td>
                <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                <td><?= $a['appointment_date'] ?></td>
                <td><?= substr($a['appointment_time'],0,5) ?></td>
                <td><span class="badge <?= $status_badge[$a['status']] ?? 'badge-neutral' ?>"><?= $a['status'] ?></span></td>
                <td>₱<?= number_format($a['price'],2) ?></td>
                <td class="actions">
                    <form method="POST" style="display:contents">
                        <input type="hidden" name="appointment_id" value="<?= $a['appointment_id'] ?>">
                        <?php if ($a['status']==='Pending'): ?>
                        <button name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                        <button name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Reject?')">Reject</button>
                        <?php elseif ($a['status']==='Confirmed'): ?>
                        <button name="action" value="complete" class="btn btn-sm" style="background:var(--success);color:#fff;border-color:var(--success)">Complete</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$appts): ?><tr><td colspan="9" class="empty">No appointments found.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</main>
</body></html>
