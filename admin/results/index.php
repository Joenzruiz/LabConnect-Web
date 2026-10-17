<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/email.php';
require_once '../../config/base_url.php';
if (!in_array($_SESSION['user_role'] ?? '', ['admin','staff'])) { header('Location: ../../index.php'); exit; }

$upload_dir = __DIR__.'/../../uploads/results/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appt_id = (int)$_POST['appointment_id'];
    $remarks = $_POST['remarks'] ?? '';
    $file_path = null; $file_type = null;

    if (!empty($_FILES['result_file']['name'])) {
        $ext = pathinfo($_FILES['result_file']['name'], PATHINFO_EXTENSION);
        $fname = 'result_'.$appt_id.'_'.time().'.'.$ext;
        move_uploaded_file($_FILES['result_file']['tmp_name'], $upload_dir.$fname);
        $file_path = 'uploads/results/'.$fname;
        $file_type = strtolower($ext) === 'pdf' ? 'PDF' : 'Image';
    }

    // Check if result already exists
    $existing = $pdo->prepare("SELECT lab_result_id FROM lab_results WHERE appointment_id=?");
    $existing->execute([$appt_id]);
    $existing = $existing->fetch();

    if ($existing) {
        $pdo->prepare("UPDATE lab_results SET file_path=?,file_type=?,remarks=?,released_by=?,released_at=NOW() WHERE appointment_id=?")
            ->execute([$file_path,$file_type,$remarks,$_SESSION['user_id'],$appt_id]);
    } else {
        $pdo->prepare("INSERT INTO lab_results (appointment_id,file_path,file_type,remarks,released_by,released_at) VALUES (?,?,?,?,?,NOW())")
            ->execute([$appt_id,$file_path,$file_type,$remarks,$_SESSION['user_id']]);
    }

    // Notify patient
    $appt = $pdo->prepare("SELECT a.patient_id, STRING_AGG(t.test_name, ', ') as test_name FROM appointments a JOIN appointment_details ad ON a.appointment_id=ad.appointment_id JOIN laboratory_tests t ON ad.test_id=t.test_id WHERE a.appointment_id=? GROUP BY a.patient_id");
    $appt->execute([$appt_id]);
    $appt = $appt->fetch(PDO::FETCH_ASSOC);
    if ($appt) {
        $title = 'Laboratory result available';
        $msg = 'Your '.$appt['test_name'].' result is now ready. Enter your result PIN to view and download it.';
        $pdo->prepare("INSERT INTO notifications (patient_id,title,message,notification_type,is_read) VALUES (?,?,?,?,false)")
            ->execute([$appt['patient_id'],$title,$msg,'result']);
        brevo_notify_patient($pdo, (int)$appt['patient_id'], $title, $msg);
    }

    header('Location: index.php'); exit;
}

// Pagination
$q_res = $_GET['q'] ?? '';
$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$where_res = "WHERE a.status IN ('Confirmed','Completed')";
$params_res = [];
if ($q_res) {
    $where_res .= " AND (p.first_name||' '||p.last_name ILIKE ? OR t.test_name ILIKE ?)";
    $params_res[] = "%$q_res%"; $params_res[] = "%$q_res%";
}

$count_stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.appointment_id)
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN appointment_details ad ON ad.appointment_id = a.appointment_id
    LEFT JOIN laboratory_tests t ON ad.test_id = t.test_id
    $where_res");
$count_stmt->execute($params_res);
$total = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$params_page = array_merge($params_res, [$per_page, $offset]);
$res_stmt = $pdo->prepare("SELECT a.appointment_id, a.appointment_date, a.status as appt_status,
    p.first_name||' '||p.last_name as patient_name,
    STRING_AGG(t.test_name, ', ') as test_name,
    r.lab_result_id, r.file_path, r.file_type, r.remarks, r.released_at
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN appointment_details ad ON ad.appointment_id = a.appointment_id
    LEFT JOIN laboratory_tests t ON ad.test_id = t.test_id
    LEFT JOIN lab_results r ON r.appointment_id = a.appointment_id
    $where_res
    GROUP BY a.appointment_id, p.first_name, p.last_name, r.lab_result_id, r.file_path, r.file_type, r.remarks, r.released_at
    ORDER BY a.appointment_date DESC
    LIMIT ? OFFSET ?");
$res_stmt->execute($params_page);
$results = $res_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Results - SLIMM Lab</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>Results</h1><p>Upload and release patient laboratory results.</p></div>
        <div class="user-area"><span><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></span></div>
    </header>

    <div class="page-body">
    <div class="panel">
        <div class="toolbar">
            <form method="GET" class="toolbar-left">
                <input class="input search" name="q" placeholder="Search patient or test…" value="<?= htmlspecialchars($q_res) ?>">
                <button class="btn btn-primary" type="submit">Search</button>
                <?php if ($q_res): ?><a class="btn" href="index.php">Clear</a><?php endif; ?>
            </form>
            <span style="font-size:12px;color:var(--muted)"><?= $total ?> record<?= $total!=1?'s':'' ?> total</span>
        </div>

        <?php
        $base_r = '?'.http_build_query(array_filter(['q'=>$q_res]));
        if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a class="btn btn-sm" href="<?= $base_r ?>&page=<?= $page-1 ?>">‹ Prev</a>
            <?php endif; ?>
            <?php for ($p = max(1,$page-2); $p <= min($total_pages,$page+2); $p++): ?>
            <a class="btn btn-sm <?= $p===$page?'btn-primary':'' ?>" href="<?= $base_r ?>&page=<?= $p ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
            <a class="btn btn-sm" href="<?= $base_r ?>&page=<?= $page+1 ?>">Next ›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <p class="pagination-info">
            Page <?= $page ?> of <?= $total_pages ?> &nbsp;·&nbsp; Showing <?= count($results) ?> of <?= $total ?> records
        </p>

        <div class="table-card table-scroll">
        <table>
            <thead><tr><th>#</th><th>Patient</th><th>Test</th><th>Appt. Date</th><th>Result</th><th>Released</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($results as $r): ?>
            <tr>
                <td><?= $r['appointment_id'] ?></td>
                <td><strong><?= htmlspecialchars($r['patient_name'] ?? '') ?></strong></td>
                <td><?= htmlspecialchars($r['test_name'] ?? '') ?></td>
                <td><?= $r['appointment_date'] ?></td>
                <td>
                    <?php if ($r['lab_result_id']): ?>
                        <span class="badge badge-confirmed">Ready</span>
                        <?php if ($r['file_path']): ?>
                        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($r['file_path']) ?>" target="_blank" class="btn btn-sm" style="margin-left:4px">View File</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-pending">Pending</span>
                    <?php endif; ?>
                </td>
                <td><?= $r['released_at'] ? date('M d, Y', strtotime($r['released_at'])) : '—' ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="openUpload(<?= $r['appointment_id'] ?>, '<?= htmlspecialchars($r['patient_name'] ?? '') ?>', '<?= htmlspecialchars($r['test_name'] ?? '') ?>', <?= $r['lab_result_id'] ? 'true' : 'false' ?>)">
                        <?= $r['lab_result_id'] ? 'Re-upload' : 'Upload Result' ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$results): ?><tr><td colspan="7" class="empty">No approved/completed appointments.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    </div>
</main>




<dialog id="dlg-upload">
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="appointment_id" id="u-appt">
<h3 id="u-title" style="margin:0 0 6px">Upload Result</h3>
<p id="u-sub" style="color:var(--muted);font-size:12px;margin:0 0 18px"></p>
<div class="field"><label>Result File (PDF or Image) *</label><input type="file" class="input" name="result_file" accept=".pdf,image/*" required></div>
<div class="field"><label>Remarks / Notes</label><textarea class="input textarea" name="remarks" rows="3" placeholder="e.g. All values within normal range…"></textarea></div>
<div class="form-actions">
    <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
    <button type="submit" class="btn btn-primary">Upload & Release</button>
</div>
</form>
</dialog>

<script>
function openUpload(id, patient, test, hasResult) {
    document.getElementById('u-appt').value = id;
    document.getElementById('u-title').textContent = (hasResult ? 'Re-upload Result' : 'Upload Result');
    document.getElementById('u-sub').textContent = patient + ' — ' + test;
    document.getElementById('dlg-upload').showModal();
}
</script>
</body></html>