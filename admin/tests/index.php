<?php
session_start();
require_once '../../config/database.php';
if (!in_array($_SESSION['user_role'] ?? '', ['admin','staff'])) { header('Location: ../../index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'add') {
        $stmt = $pdo->prepare("INSERT INTO laboratory_tests (test_name,category,price,preparation_instructions,is_active) VALUES (?,?,?,?,true)");
        $stmt->execute([$_POST['test_name'],$_POST['category'],$_POST['price'],$_POST['prep']]);
    } elseif ($act === 'edit') {
        $stmt = $pdo->prepare("UPDATE laboratory_tests SET test_name=?,category=?,price=?,preparation_instructions=?,is_active=? WHERE test_id=?");
        $stmt->execute([$_POST['test_name'],$_POST['category'],$_POST['price'],$_POST['prep'],isset($_POST['is_active'])?'true':'false',$_POST['test_id']]);
    } elseif ($act === 'delete') {
        $pdo->prepare("UPDATE laboratory_tests SET is_active=false WHERE test_id=?")->execute([$_POST['test_id']]);
    }
    header('Location: index.php'); exit;
}

$q = $_GET['q'] ?? '';
$show_inactive = isset($_GET['all']);
$sql = "SELECT * FROM laboratory_tests WHERE (test_name ILIKE ? OR category ILIKE ?)".(!$show_inactive ? " AND is_active=true" : "")." ORDER BY category,test_name";
$tests = $pdo->prepare($sql);
$tests->execute(["%$q%","%$q%"]);
$tests = $tests->fetchAll(PDO::FETCH_ASSOC);
$cats = ['Clinical Diagnostics','X-Ray','Ultrasound'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Lab Tests - SLIMM Lab</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>Laboratory Tests</h1><p>Manage available tests and their pricing.</p></div>
        <div class="user-area"><span><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></span></div>
    </header>

    <div class="page-body">
    <div class="panel">
        <div class="toolbar">
            <form method="GET" class="toolbar-left">
                <input class="input search" name="q" placeholder="Search tests…" value="<?= htmlspecialchars($q) ?>">
                <button class="btn btn-primary" type="submit">Search</button>
                <?php if ($q): ?><a class="btn" href="index.php">Clear</a><?php endif; ?>
                <a class="btn" href="?<?= $show_inactive ? '' : 'all=1' ?>"><?= $show_inactive ? 'Hide Inactive' : 'Show All' ?></a>
            </form>
            <button class="btn btn-primary" onclick="document.getElementById('dlg-add').showModal()">+ Add Test</button>
        </div>

        <div class="table-card table-scroll">
        <table>
            <thead><tr><th>#</th><th>Test Name</th><th>Category</th><th>Price (PHP)</th><th>Preparation</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($tests as $t): ?>
            <tr>
                <td><?= $t['test_id'] ?></td>
                <td><strong><?= htmlspecialchars($t['test_name'] ?? '') ?></strong></td>
                <td><span class="badge badge-neutral"><?= $t['category'] ?? '' ?></span></td>
                <td>₱<?= number_format($t['price'] ?? 0,2) ?></td>
                <td style="max-width:200px;white-space:normal;"><?= htmlspecialchars(substr($t['preparation_instructions'] ?? '',0,80)) ?><?= strlen($t['preparation_instructions'] ?? '')>80?'…':'' ?></td>
                <td><?= $t['is_active'] ? '<span class="badge badge-confirmed">Yes</span>' : '<span class="badge badge-cancelled">No</span>' ?></td>
                <td class="actions">
                    <button class="btn btn-sm" onclick='openEdit(<?= htmlspecialchars(json_encode($t)) ?>)'>Edit</button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Deactivate this test?')">
                        <input type="hidden" name="_action" value="delete">
                        <input type="hidden" name="test_id" value="<?= $t['test_id'] ?>">
                        <button class="btn btn-danger btn-sm">Deactivate</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$tests): ?><tr><td colspan="7" class="empty">No tests found.</td></tr><?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    </div>
</main>

<dialog id="dlg-add">
<form method="POST">
<input type="hidden" name="_action" value="add">
<h3 style="margin:0 0 18px">Add Laboratory Test</h3>
<div class="form-grid">
    <div class="field full"><label>Test Name *</label><input class="input" name="test_name" required></div>
    <div class="field"><label>Category *</label>
        <select class="input" name="category" required><?php foreach ($cats as $c): ?><option><?= $c ?></option><?php endforeach; ?></select>
    </div>
    <div class="field"><label>Price (PHP) *</label><input class="input" type="number" name="price" min="0" step="0.01" required></div>
    <div class="field full"><label>Preparation Instructions</label><textarea class="input textarea" name="prep" rows="3" placeholder="e.g. Fasting required for 8 hours…"></textarea></div>
</div>
<div class="form-actions">
    <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
    <button type="submit" class="btn btn-primary">Save</button>
</div>
</form>
</dialog>

<dialog id="dlg-edit">
<form method="POST">
<input type="hidden" name="_action" value="edit">
<input type="hidden" name="test_id" id="e-id">
<h3 style="margin:0 0 18px">Edit Test</h3>
<div class="form-grid">
    <div class="field full"><label>Test Name *</label><input class="input" name="test_name" id="e-name" required></div>
    <div class="field"><label>Category *</label>
        <select class="input" name="category" id="e-cat"><?php foreach ($cats as $c): ?><option><?= $c ?></option><?php endforeach; ?></select>
    </div>
    <div class="field"><label>Price (PHP) *</label><input class="input" type="number" name="price" id="e-price" min="0" step="0.01" required></div>
    <div class="field full"><label>Preparation Instructions</label><textarea class="input textarea" name="prep" id="e-prep" rows="3"></textarea></div>
    <div class="field"><label><input type="checkbox" name="is_active" id="e-active"> Active</label></div>
</div>
<div class="form-actions">
    <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
    <button type="submit" class="btn btn-primary">Update</button>
</div>
</form>
</dialog>

<script>
function openEdit(t) {
    document.getElementById('e-id').value = t.test_id;
    document.getElementById('e-name').value = t.test_name;
    document.getElementById('e-cat').value = t.category;
    document.getElementById('e-price').value = t.price;
    document.getElementById('e-prep').value = t.preparation_instructions || '';
    document.getElementById('e-active').checked = (t.is_active === true || t.is_active === 't' || t.is_active == 1);
    document.getElementById('dlg-edit').showModal();
}
</script>
</body></html>