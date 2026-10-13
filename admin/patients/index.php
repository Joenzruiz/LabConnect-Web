<?php
session_start();
require_once '../../config/database.php';
if (!in_array($_SESSION['user_role'] ?? '', ['admin','staff'])) { header('Location: ../../index.php'); exit; }

$msg = '';
// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'add') {
        $stmt = $pdo->prepare("INSERT INTO patients (first_name,last_name,email,phone,address,gender,birth_date) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['first_name'],$_POST['last_name'],$_POST['email'],$_POST['phone'],$_POST['address'],$_POST['gender'] ?: null,$_POST['birth_date'] ?: null]);
    } elseif ($act === 'edit') {
        $stmt = $pdo->prepare("UPDATE patients SET first_name=?,last_name=?,email=?,phone=?,address=?,gender=?,birth_date=? WHERE patient_id=?");
        $stmt->execute([$_POST['first_name'],$_POST['last_name'],$_POST['email'],$_POST['phone'],$_POST['address'],$_POST['gender'] ?: null,$_POST['birth_date'] ?: null,$_POST['patient_id']]);
    } elseif ($act === 'delete') {
        $pdo->prepare("DELETE FROM patients WHERE patient_id=?")->execute([$_POST['patient_id']]);
    }
    header('Location: index.php'); exit;
}

$q = $_GET['q'] ?? '';
$per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$count_sql = "SELECT COUNT(*) FROM patients WHERE (first_name||' '||last_name ILIKE ? OR email ILIKE ?)";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute(["%$q%","%$q%"]);
$total = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$sql = "SELECT * FROM patients WHERE (first_name||' '||last_name ILIKE ? OR email ILIKE ?) ORDER BY created_at DESC LIMIT ? OFFSET ?";
$patients = $pdo->prepare($sql);
$patients->execute(["%$q%","%$q%",$per_page,$offset]);
$patients = $patients->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Patients - SLIMM Lab</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>Patients</h1><p>Registered patients in the system.</p></div>
        <div class="user-area">
            <button class="btn btn-primary" onclick="document.getElementById('dlg-add').showModal()">+ Add Patient</button>
            <span><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></span>
        </div>
    </header>

    <div class="page-body">
    <div class="panel">
        <div class="toolbar">
            <form method="GET" class="toolbar-left">
                <input class="input search" name="q" placeholder="Search by name or email…" value="<?= htmlspecialchars($q) ?>">
                <button class="btn btn-primary" type="submit">Search</button>
                <?php if ($q): ?><a class="btn" href="index.php">Clear</a><?php endif; ?>
            </form>
            <span style="font-size:12px;color:var(--muted)"><?= $total ?> patient<?= $total!=1?'s':'' ?> total</span>
        </div>

        <?php
        $base = '?'.http_build_query(array_filter(['q'=>$q]));
        if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a class="btn btn-sm" href="<?= $base ?>&page=<?= $page-1 ?>">‹ Prev</a>
            <?php endif; ?>
            <?php for ($p = max(1,$page-2); $p <= min($total_pages,$page+2); $p++): ?>
            <a class="btn btn-sm <?= $p===$page?'btn-primary':'' ?>" href="<?= $base ?>&page=<?= $p ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
            <a class="btn btn-sm" href="<?= $base ?>&page=<?= $page+1 ?>">Next ›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <p class="pagination-info">
            Page <?= $page ?> of <?= $total_pages ?> &nbsp;·&nbsp; Showing <?= count($patients) ?> of <?= $total ?> patients
        </p>

        <div class="table-card table-scroll">
        <table>
            <thead><tr>
                <th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Birth Date</th><th>Registered</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($patients as $p): ?>
            <tr>
                <td><?= $p['patient_id'] ?></td>
                <td><strong><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></strong></td>
                <td><?= htmlspecialchars($p['email']) ?></td>
                <td><?= htmlspecialchars($p['phone']) ?></td>
                <td><?= $p['gender'] ?></td>
                <td><?= $p['birth_date'] ?></td>
                <td><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
                <td class="actions">
                    <button class="btn btn-sm" onclick='openEdit(<?= htmlspecialchars(json_encode($p)) ?>)'>Edit</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this patient?')">
                        <input type="hidden" name="_action" value="delete">
                        <input type="hidden" name="patient_id" value="<?= $p['patient_id'] ?>">
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$patients): ?><tr><td colspan="8" class="empty">No patients found.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    </div>
</main>

<!-- Add Modal -->
<dialog id="dlg-add">
<form method="POST">
<input type="hidden" name="_action" value="add">
<h3 style="margin:0 0 18px">Add Patient</h3>
<div class="form-grid">
    <div class="field"><label>First Name *</label><input class="input" name="first_name" required></div>
    <div class="field"><label>Last Name *</label><input class="input" name="last_name" required></div>
    <div class="field"><label>Email</label><input class="input" type="email" name="email"></div>
    <div class="field"><label>Phone</label><input class="input" name="phone"></div>
    <div class="field"><label>Gender</label>
        <select class="input" name="gender"><option value="">— select —</option><option>Male</option><option>Female</option><option>Other</option></select>
    </div>
    <div class="field"><label>Birth Date</label><input class="input" type="date" name="birth_date"></div>
    <div class="field full"><label>Address</label><textarea class="input textarea" name="address" rows="2"></textarea></div>
</div>
<div class="form-actions">
    <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
    <button type="submit" class="btn btn-primary">Save</button>
</div>
</form>
</dialog>

<!-- Edit Modal -->
<dialog id="dlg-edit">
<form method="POST">
<input type="hidden" name="_action" value="edit">
<input type="hidden" name="patient_id" id="edit-id">
<h3 style="margin:0 0 18px">Edit Patient</h3>
<div class="form-grid">
    <div class="field"><label>First Name *</label><input class="input" name="first_name" id="edit-fn" required></div>
    <div class="field"><label>Last Name *</label><input class="input" name="last_name" id="edit-ln" required></div>
    <div class="field"><label>Email</label><input class="input" type="email" name="email" id="edit-em"></div>
    <div class="field"><label>Phone</label><input class="input" name="phone" id="edit-ph"></div>
    <div class="field"><label>Gender</label>
        <select class="input" name="gender" id="edit-gn"><option value="">— select —</option><option>Male</option><option>Female</option><option>Other</option></select>
    </div>
    <div class="field"><label>Birth Date</label><input class="input" type="date" name="birth_date" id="edit-bd"></div>
    <div class="field full"><label>Address</label><textarea class="input textarea" name="address" id="edit-ad" rows="2"></textarea></div>
</div>
<div class="form-actions">
    <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
    <button type="submit" class="btn btn-primary">Update</button>
</div>
</form>
</dialog>

<script>
function openEdit(p) {
    document.getElementById('edit-id').value = p.patient_id;
    document.getElementById('edit-fn').value = p.first_name;
    document.getElementById('edit-ln').value = p.last_name;
    document.getElementById('edit-em').value = p.email || '';
    document.getElementById('edit-ph').value = p.phone || '';
    document.getElementById('edit-gn').value = p.gender || '';
    document.getElementById('edit-bd').value = p.birth_date || '';
    document.getElementById('edit-ad').value = p.address || '';
    document.getElementById('dlg-edit').showModal();
}
</script>
</body></html>