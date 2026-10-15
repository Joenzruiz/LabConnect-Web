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

// ── POST: upload result ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    $remarks        = $_POST['remarks'] ?? '';

    if ($appointment_id && isset($_FILES['result_file']) && $_FILES['result_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'C:\\xampp\\htdocs\\LabConnect-Web\\uploads\\results\\';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0775, true);
        }

        $filename  = time().'_'.basename($_FILES['result_file']['name']);
        $dest      = $upload_dir.$filename;
        $file_path = 'uploads/results/'.$filename;
        $file_type = $_FILES['result_file']['type'];

        if (move_uploaded_file($_FILES['result_file']['tmp_name'], $dest)) {
            // Insert lab result
            $ins = $pdo->prepare(
                "INSERT INTO lab_results (appointment_id, file_path, file_type, remarks, released_by, released_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $ins->execute([$appointment_id, $file_path, $file_type, $remarks, $_SESSION['user_id']]);

            // Mark appointment completed
            $upd = $pdo->prepare(
                "UPDATE appointments SET status='Completed', updated_at=NOW() WHERE appointment_id=?"
            );
            $upd->execute([$appointment_id]);

            // Notify patient
            $notif = $pdo->prepare(
                "INSERT INTO notifications (patient_id, title, message, notification_type)
                 SELECT a.patient_id, 'Lab Result Ready',
                        'Your lab result is now available in the SLIMM Lab app.',
                        'result'
                 FROM appointments a WHERE a.appointment_id=?"
            );
            $notif->execute([$appointment_id]);

            // Send result-ready email via EmailJS
            try {
                $pInfo = $pdo->prepare(
                    "SELECT u.email, p.first_name||' '||p.last_name as full_name,
                            STRING_AGG(DISTINCT t.test_name, ', ') as test_names
                     FROM appointments a
                     JOIN patients p ON a.patient_id=p.patient_id
                     JOIN users u ON p.user_id=u.user_id
                     LEFT JOIN appointment_details ad ON a.appointment_id=ad.appointment_id
                     LEFT JOIN laboratory_tests t ON ad.test_id=t.test_id
                     WHERE a.appointment_id=?
                     GROUP BY u.email, p.first_name, p.last_name"
                );
                $pInfo->execute([$appointment_id]);
                $pi = $pInfo->fetch(PDO::FETCH_ASSOC);
                if ($pi && !empty($pi['email'])) {
                    brevo_send_result_ready(
                        $pi['email'],
                        $pi['full_name'] ?? 'Patient',
                        $pi['test_names'] ?? 'Laboratory'
                    );
                }
            } catch (Exception $ignored) {}
        }
    }

    header('Location: results.php?success=1');
    exit;
}

// ── GET: fetch appointments with result info ──────────────────────────────────
$rows = $pdo->prepare(
    "SELECT a.appointment_id, a.status, a.appointment_date, a.appointment_time,
       p.first_name||' '||p.last_name as patient_name, p.patient_id,
       STRING_AGG(DISTINCT t.test_name, ', ') as test_names,
       lr.lab_result_id, lr.file_path, lr.released_at
     FROM appointments a
     JOIN patients p ON a.patient_id=p.patient_id
     LEFT JOIN appointment_details ad ON a.appointment_id=ad.appointment_id
     LEFT JOIN laboratory_tests t ON ad.test_id=t.test_id
     LEFT JOIN lab_results lr ON lr.appointment_id=a.appointment_id
     WHERE a.doctor_id=? AND a.status IN ('Confirmed','Completed')
     GROUP BY a.appointment_id, a.status, a.appointment_date, a.appointment_time,
       p.first_name, p.last_name, p.patient_id, lr.lab_result_id, lr.file_path, lr.released_at
     ORDER BY a.appointment_date DESC"
);
$rows->execute([$doc_id]);
$rows = $rows->fetchAll(PDO::FETCH_ASSOC);

$badges = ['Pending'=>'badge-pending','Confirmed'=>'badge-confirmed','Completed'=>'badge-completed','Cancelled'=>'badge-cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lab Results — <?= htmlspecialchars($doc_name) ?></title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>Lab Results</h1><p>Upload and manage patient lab results.</p></div>
        <div class="user-area"><span><?= htmlspecialchars($doc_name) ?></span>
            <a href="../api/logout.php" class="btn">Logout</a>
        </div>
    </header>

    <div class="page-body">
    <?php if (($_GET['success'] ?? '') === '1'): ?>
    <div style="margin-bottom:16px;padding:12px 18px;background:#dcfce7;border:1px solid #86efac;border-radius:8px;color:#15803d;font-weight:500;">
        ✅ Result uploaded successfully!
    </div>
    <?php endif; ?>

    <div class="panel">
        <div class="table-card table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Test(s)</th>
                    <th>Appt Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Result</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['patient_name']) ?></strong></td>
                <td><?= htmlspecialchars($r['test_names'] ?? '—') ?></td>
                <td><?= $r['appointment_date'] ?></td>
                <td><?= substr($r['appointment_time'], 0, 5) ?></td>
                <td><span class="badge <?= $badges[$r['status']] ?? 'badge-neutral' ?>"><?= $r['status'] ?></span></td>
                <td>
                    <?php if ($r['lab_result_id']): ?>
                        <span class="badge badge-confirmed" style="background:#d1fae5;color:#065f46;">Uploaded ✓</span>
                        <a href="../<?= htmlspecialchars($r['file_path']) ?>" target="_blank"
                           style="font-size:12px;margin-left:6px;">View</a>
                    <?php else: ?>
                        <span style="color:#9ca3af;">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!$r['lab_result_id']): ?>
                        <button class="btn btn-primary btn-sm"
                                data-appt-id="<?= $r['appointment_id'] ?>">
                            📤 Upload Result
                        </button>
                    <?php else: ?>
                        <span style="color:#9ca3af;">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
            <tr><td colspan="7" class="empty">No confirmed or completed appointments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    </div>
</main>

<!-- Upload Dialog -->
<dialog id="uploadDialog" style="border-radius:12px;border:1px solid #e5e7eb;padding:28px;width:440px;max-width:95vw;box-shadow:0 8px 32px rgba(0,0,0,.15);">
    <h3 style="margin:0 0 18px;font-size:18px;">Upload Lab Result</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="appointment_id" value="">

        <div style="margin-bottom:14px;">
            <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Result File <span style="color:#ef4444;">*</span></label>
            <input class="input" type="file" name="result_file" accept=".pdf,image/*" required style="width:100%;padding:8px;">
        </div>

        <div style="margin-bottom:18px;">
            <label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Remarks / Interpretation Notes</label>
            <textarea class="input" name="remarks" rows="4"
                      placeholder="Remarks / interpretation notes (optional)"
                      style="width:100%;resize:vertical;"></textarea>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" class="btn"
                    onclick="document.getElementById('uploadDialog').close()">Cancel</button>
            <button type="submit" class="btn btn-primary">📤 Upload</button>
        </div>
    </form>
</dialog>

<script>
document.querySelectorAll('[data-appt-id]').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelector('#uploadDialog input[name=appointment_id]').value = this.dataset.apptId;
        document.getElementById('uploadDialog').showModal();
    });
});
</script>
</body></html>
