<?php
session_start();
require_once '../config/database.php';
if (!in_array($_SESSION['user_role'] ?? '', ['admin','staff'])) { header('Location: ../index.php'); exit; }

// Live counts from DB
$total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$total_doctors  = $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$total_appts    = $pdo->query("SELECT COUNT(*) FROM appointments WHERE EXTRACT(MONTH FROM appointment_date)=EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM appointment_date)=EXTRACT(YEAR FROM CURRENT_DATE)")->fetchColumn();
$completed      = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status='Completed' AND EXTRACT(MONTH FROM appointment_date)=EXTRACT(MONTH FROM CURRENT_DATE)")->fetchColumn();
$pending_res    = $pdo->query("SELECT COUNT(*) FROM appointments a LEFT JOIN lab_results r ON r.appointment_id=a.appointment_id WHERE a.status='Completed' AND r.lab_result_id IS NULL")->fetchColumn();

// Pending approval queue
$queue = $pdo->query("SELECT a.appointment_id, p.first_name||' '||p.last_name as patient_name,
    d.first_name||' '||d.last_name as doctor_name,
    STRING_AGG(t.test_name, ', ') as test_name,
    a.appointment_date, a.appointment_time
    FROM appointments a
    JOIN patients p ON a.patient_id=p.patient_id
    LEFT JOIN doctors d ON a.doctor_id=d.doctor_id
    LEFT JOIN appointment_details ad ON ad.appointment_id=a.appointment_id
    LEFT JOIN laboratory_tests t ON t.test_id=ad.test_id
    WHERE a.status='Pending'
    GROUP BY a.appointment_id, p.first_name, p.last_name, d.first_name, d.last_name, a.appointment_date, a.appointment_time
    ORDER BY a.appointment_date
    LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

// Handle quick approve/reject from queue
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $appt_id = (int)$_POST['appointment_id'];
    $map = ['approve'=>'Confirmed','reject'=>'Cancelled','complete'=>'Completed'];
    if (isset($map[$action]) && $appt_id) {
        $pdo->prepare("UPDATE appointments SET status=?, approved_by=? WHERE appointment_id=?")
            ->execute([$map[$action], $_SESSION['user_id'], $appt_id]);
        // Notify patient on approve
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
    header('Location: dashboard.php'); exit;
}

// Daily bar chart data (today's appointments by hour)
$daily = $pdo->query("SELECT EXTRACT(HOUR FROM appointment_time::time) as hr, COUNT(*) as cnt FROM appointments WHERE appointment_date=CURRENT_DATE GROUP BY hr ORDER BY hr")->fetchAll(PDO::FETCH_ASSOC);
$daily_map = [];
foreach ($daily as $d) $daily_map[(int)$d['hr']] = (int)$d['cnt'];
$max_daily = max(array_merge(array_values($daily_map),[1]));

// Monthly bar chart data
$monthly = $pdo->query("SELECT EXTRACT(MONTH FROM appointment_date) as mo, COUNT(*) as cnt FROM appointments WHERE EXTRACT(YEAR FROM appointment_date)=EXTRACT(YEAR FROM CURRENT_DATE) GROUP BY mo ORDER BY mo")->fetchAll(PDO::FETCH_ASSOC);
$monthly_map = [];
foreach ($monthly as $m) $monthly_map[(int)$m['mo']] = (int)$m['cnt'];
$max_monthly = max(array_merge(array_values($monthly_map),[1]));
$month_names = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard - SLIMM Lab</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title">
            <h1>Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?> · <?= date('l, F j, Y') ?></p>
        </div>
        <div class="user-area">
            <span class="avatar"><?= strtoupper(substr($_SESSION['full_name'] ?? 'A',0,1)) ?></span>
            <a class="btn logout" href="../api/logout.php">Logout</a>
        </div>
    </header>

    <!-- Stat Cards -->
    <div class="cards">
        <div class="card">
            <div class="stat-label">Total Patients</div>
            <div class="stat-value"><?= number_format($total_patients) ?></div>
            <div class="stat-note">Registered patients</div>
        </div>
        <div class="card">
            <div class="stat-label">Total Doctors</div>
            <div class="stat-value"><?= number_format($total_doctors) ?></div>
            <div class="stat-note">Active doctors</div>
        </div>
        <div class="card">
            <div class="stat-label">Appointments</div>
            <div class="stat-value"><?= number_format($total_appts) ?></div>
            <div class="stat-note">This month</div>
        </div>
        <div class="card">
            <div class="stat-label">Completed Tests</div>
            <div class="stat-value"><?= number_format($completed) ?></div>
            <div class="stat-note">This month</div>
        </div>
        <div class="card">
            <div class="stat-label">Pending Results</div>
            <div class="stat-value" style="color:var(--warning)"><?= number_format($pending_res) ?></div>
            <div class="stat-note">Awaiting upload</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid-2" style="margin-bottom:20px;">
        <div class="panel">
            <div class="panel-header">
                <div><h3 class="panel-title">Today's Appointments</h3><p class="panel-subtitle">By hour — <?= date('F j') ?></p></div>
            </div>
            <div class="chart">
                <?php for ($hr = 8; $hr <= 16; $hr++): $cnt = $daily_map[$hr] ?? 0; $h = $cnt > 0 ? max(20, round(($cnt/$max_daily)*180)) : 0; ?>
                <div class="bar-wrap">
                    <?php if ($cnt > 0): ?><div style="position:absolute;font-size:10px;font-weight:700;color:var(--teal);bottom:<?= $h+8 ?>px"><?= $cnt ?></div><?php endif; ?>
                    <div class="bar" style="height:<?= $h ?>px;position:relative"></div>
                    <div class="bar-label"><?= $hr<12 ? $hr.'AM' : ($hr==12?'12PM':($hr-12).'PM') ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <div><h3 class="panel-title">Monthly Overview</h3><p class="panel-subtitle">Appointments — <?= date('Y') ?></p></div>
            </div>
            <div class="chart">
                <?php for ($mo=1; $mo<=12; $mo++): $cnt = $monthly_map[$mo] ?? 0; $h = $cnt > 0 ? max(20, round(($cnt/$max_monthly)*180)) : 4; $is_cur = $mo == date('n'); ?>
                <div class="bar-wrap">
                    <?php if ($cnt > 0): ?><div style="position:absolute;font-size:10px;font-weight:700;color:var(--teal);bottom:<?= $h+8 ?>px"><?= $cnt ?></div><?php endif; ?>
                    <div class="bar" style="height:<?= $h ?>px;background:<?= $is_cur ? 'var(--teal)' : 'var(--teal-soft)' ?>;position:relative"></div>
                    <div class="bar-label" style="<?= $is_cur?'color:var(--teal);font-weight:700':'' ?>"><?= $month_names[$mo] ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Approval Queue -->
    <div class="panel">
        <div class="panel-header">
            <div>
                <h3 class="panel-title">Awaiting Approval <?php if($queue): ?><span style="background:var(--warning-soft);color:var(--warning);border-radius:999px;padding:2px 8px;font-size:12px;margin-left:6px;"><?= count($queue) ?></span><?php endif; ?></h3>
                <p class="panel-subtitle">Review and approve or reject pending appointment requests.</p>
            </div>
            <a class="btn" href="appointments/index.php">View all appointments</a>
        </div>

        <?php if ($queue): ?>
        <?php foreach ($queue as $appt): ?>
        <div class="queue-item">
            <div class="queue-main">
                <strong><?= htmlspecialchars($appt['patient_name']) ?> &mdash; <?= htmlspecialchars($appt['test_name']) ?></strong>
                <span><?= $appt['doctor_name'] ? 'Dr. '.htmlspecialchars($appt['doctor_name']) : 'No doctor assigned' ?> &bull; <?= date('M d, Y', strtotime($appt['appointment_date'])) ?> at <?= substr($appt['appointment_time'],0,5) ?></span>
            </div>
            <div class="actions">
                <form method="POST" style="display:contents">
                    <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">✓ Approve</button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Reject this appointment?')">✗ Reject</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p class="empty">🎉 No pending appointments. All caught up!</p>
        <?php endif; ?>
    </div>
</main>
</body></html>
