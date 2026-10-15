<?php
session_start();
require_once '../config/database.php';
require_once '../config/email.php';
if (($_SESSION['user_role'] ?? '') !== 'doctor') { header('Location: ../index.php'); exit; }

$doctor = $pdo->prepare("SELECT * FROM doctors WHERE user_id=?");
$doctor->execute([$_SESSION['user_id']]);
$doctor = $doctor->fetch(PDO::FETCH_ASSOC);
if (!$doctor) { session_destroy(); header('Location: ../index.php'); exit; }
$doc_id   = $doctor['doctor_id'];
$doc_name = 'Dr. '.$doctor['first_name'].' '.$doctor['last_name'];
$doc_spec = $doctor['specialization'] ?? '';

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['_action']        ?? '';
    $appt_id = (int)($_POST['appointment_id'] ?? 0);
    $user_id = (int)($_SESSION['user_id']     ?? 0);

    if ($action === 'confirm' && $appt_id) {
        $stmt = $pdo->prepare(
            "UPDATE appointments
             SET status='Confirmed', approved_by=:user_id, approved_at=NOW(), updated_at=NOW()
             WHERE appointment_id=:appt_id AND doctor_id=:doc_id"
        );
        $stmt->execute([':user_id' => $user_id, ':appt_id' => $appt_id, ':doc_id' => $doc_id]);

        // Notify patient in-app
        $pdo->prepare("INSERT INTO notifications (patient_id, title, message, notification_type)
            SELECT a.patient_id, 'Appointment Confirmed',
            'Your appointment has been confirmed by the doctor. See you soon!', 'appointment'
            FROM appointments a WHERE a.appointment_id=?")->execute([$appt_id]);

        // Send email via EmailJS
        try {
            $pInfo = $pdo->prepare(
                "SELECT u.email, p.first_name||' '||p.last_name as full_name,
                        a.appointment_date, a.appointment_time,
                        STRING_AGG(DISTINCT t.test_name,', ') as test_names
                 FROM appointments a
                 JOIN patients p ON a.patient_id=p.patient_id
                 JOIN users u ON p.user_id=u.user_id
                 LEFT JOIN appointment_details ad ON a.appointment_id=ad.appointment_id
                 LEFT JOIN laboratory_tests t ON ad.test_id=t.test_id
                 WHERE a.appointment_id=?
                 GROUP BY u.email,p.first_name,p.last_name,a.appointment_date,a.appointment_time"
            );
            $pInfo->execute([$appt_id]);
            $pi = $pInfo->fetch(PDO::FETCH_ASSOC);
            if ($pi && !empty($pi['email'])) {
                brevo_send_appointment(
                    $pi['email'],
                    $pi['full_name'] ?? 'Patient',
                    $pi['test_names'] ?? 'Your test',
                    $pi['appointment_date'],
                    substr($pi['appointment_time'],0,5),
                    'Confirmed',
                    $doc_name,
                    $doc_spec
                );
            }
        } catch (Exception $ignored) {}

    } elseif ($action === 'decline' && $appt_id) {
        $stmt = $pdo->prepare(
            "UPDATE appointments
             SET status='Cancelled', updated_at=NOW()
             WHERE appointment_id=:appt_id AND doctor_id=:doc_id"
        );
        $stmt->execute([':appt_id' => $appt_id, ':doc_id' => $doc_id]);

        // Notify patient in-app
        $pdo->prepare("INSERT INTO notifications (patient_id, title, message, notification_type)
            SELECT a.patient_id, 'Appointment Declined',
            'Your appointment could not be accommodated at this time and has been declined by the doctor.', 'appointment'
            FROM appointments a WHERE a.appointment_id=?")->execute([$appt_id]);

        // Send email via Brevo
        try {
            $pInfo = $pdo->prepare(
                "SELECT u.email, p.first_name||' '||p.last_name as full_name,
                        a.appointment_date, a.appointment_time,
                        STRING_AGG(DISTINCT t.test_name,', ') as test_names
                 FROM appointments a
                 JOIN patients p ON a.patient_id=p.patient_id
                 JOIN users u ON p.user_id=u.user_id
                 LEFT JOIN appointment_details ad ON a.appointment_id=ad.appointment_id
                 LEFT JOIN laboratory_tests t ON ad.test_id=t.test_id
                 WHERE a.appointment_id=?
                 GROUP BY u.email,p.first_name,p.last_name,a.appointment_date,a.appointment_time"
            );
            $pInfo->execute([$appt_id]);
            $pi = $pInfo->fetch(PDO::FETCH_ASSOC);
            if ($pi && !empty($pi['email'])) {
                brevo_send_appointment(
                    $pi['email'],
                    $pi['full_name'] ?? 'Patient',
                    $pi['test_names'] ?? 'Your test',
                    $pi['appointment_date'],
                    substr($pi['appointment_time'],0,5),
                    'Cancelled',
                    $doc_name,
                    $doc_spec
                );
            }
        } catch (Exception $ignored) {}

    } elseif ($action === 'complete' && $appt_id) {
        $stmt = $pdo->prepare(
            "UPDATE appointments
             SET status='Completed', updated_at=NOW()
             WHERE appointment_id=:appt_id AND doctor_id=:doc_id"
        );
        $stmt->execute([':appt_id' => $appt_id, ':doc_id' => $doc_id]);

        // Notify patient in-app
        $pdo->prepare("INSERT INTO notifications (patient_id, title, message, notification_type)
            SELECT a.patient_id, 'Appointment Completed',
            'Your appointment has been marked as Completed. Your lab results will be available shortly.', 'appointment'
            FROM appointments a WHERE a.appointment_id=?")->execute([$appt_id]);

        // Send email via Brevo
        try {
            $pInfo = $pdo->prepare(
                "SELECT u.email, p.first_name||' '||p.last_name as full_name,
                        a.appointment_date, a.appointment_time,
                        STRING_AGG(DISTINCT t.test_name,', ') as test_names
                 FROM appointments a
                 JOIN patients p ON a.patient_id=p.patient_id
                 JOIN users u ON p.user_id=u.user_id
                 LEFT JOIN appointment_details ad ON a.appointment_id=ad.appointment_id
                 LEFT JOIN laboratory_tests t ON ad.test_id=t.test_id
                 WHERE a.appointment_id=?
                 GROUP BY u.email,p.first_name,p.last_name,a.appointment_date,a.appointment_time"
            );
            $pInfo->execute([$appt_id]);
            $pi = $pInfo->fetch(PDO::FETCH_ASSOC);
            if ($pi && !empty($pi['email'])) {
                brevo_send_appointment(
                    $pi['email'],
                    $pi['full_name'] ?? 'Patient',
                    $pi['test_names'] ?? 'Your test',
                    $pi['appointment_date'],
                    substr($pi['appointment_time'],0,5),
                    'Completed',
                    $doc_name,
                    $doc_spec
                );
            }
        } catch (Exception $ignored) {}
    }

    header('Location: appointments.php');
    exit;
}

// ── GET / filter logic ────────────────────────────────────────────────────────
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

    <div class="page-body">
    <div class="panel">
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
            <thead><tr><th>#</th><th>Patient</th><th>Test</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($appts as $a): ?>
            <tr>
                <td><?= $a['appointment_id'] ?></td>
                <td><strong><?= htmlspecialchars($a['patient_name']) ?></strong></td>
                <td><?= htmlspecialchars($a['test_name'] ?? '—') ?></td>
                <td><?= $a['appointment_date'] ?></td>
                <td><?= substr($a['appointment_time'],0,5) ?></td>
                <td><span class="badge <?= $badges[$a['status']] ?? 'badge-neutral' ?>"><?= $a['status'] ?></span></td>
                <td>
                    <?php if ($a['status'] === 'Pending'): ?>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <form method="POST" onsubmit="return confirm('Confirm and approve this appointment?')" style="display:inline">
                                <input type="hidden" name="_action"        value="confirm">
                                <input type="hidden" name="appointment_id" value="<?= $a['appointment_id'] ?>">
                                <input type="hidden" name="doctor_id"      value="<?= $doc_id ?>">
                                <button class="btn btn-sm" style="background:#16a34a;color:#fff;border-color:#16a34a;">Approve</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Decline this appointment? The patient will be notified.')" style="display:inline">
                                <input type="hidden" name="_action"        value="decline">
                                <input type="hidden" name="appointment_id" value="<?= $a['appointment_id'] ?>">
                                <input type="hidden" name="doctor_id"      value="<?= $doc_id ?>">
                                <button class="btn btn-sm btn-danger">Decline</button>
                            </form>
                        </div>
                    <?php elseif ($a['status'] === 'Confirmed'): ?>
                        <form method="POST" onsubmit="return confirm('Are you sure?')" style="display:inline">
                            <input type="hidden" name="_action"        value="complete">
                            <input type="hidden" name="appointment_id" value="<?= $a['appointment_id'] ?>">
                            <input type="hidden" name="doctor_id"      value="<?= $doc_id ?>">
                            <button class="btn btn-sm" style="background:#0d9488;color:#fff;border-color:#0d9488;">Mark Complete</button>
                        </form>
                    <?php else: ?>
                        <span style="color:#9ca3af;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$appts): ?><tr><td colspan="7" class="empty">No appointments found.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    </div>
</main>
</body></html>
