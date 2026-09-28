<?php
session_start();
require_once '../../config/database.php';
if (!in_array($_SESSION['user_role'] ?? '', ['admin','staff'])) { header('Location: ../../index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    if ($act === 'add') {
        $stmt = $pdo->prepare("INSERT INTO doctors (first_name,last_name,email,phone,specialization) VALUES (?,?,?,?,?)");
        $stmt->execute([$_POST['first_name'],$_POST['last_name'],$_POST['email'],$_POST['phone'],$_POST['specialization']]);
    } elseif ($act === 'edit') {
        $stmt = $pdo->prepare("UPDATE doctors SET first_name=?,last_name=?,email=?,phone=?,specialization=? WHERE doctor_id=?");
        $stmt->execute([$_POST['first_name'],$_POST['last_name'],$_POST['email'],$_POST['phone'],$_POST['specialization'],$_POST['doctor_id']]);
    } elseif ($act === 'delete') {
        $pdo->prepare("DELETE FROM doctors WHERE doctor_id=?")->execute([$_POST['doctor_id']]);
    } elseif ($act === 'avail') {
        // Delete existing and re-insert availability
        $pdo->prepare("DELETE FROM doctor_weekly_availability WHERE doctor_id=?")->execute([$_POST['doctor_id']]);
        $days = $_POST['days'] ?? [];
        foreach ($days as $day) {
            $st = $_POST["start_$day"] ?? '08:00';
            $et = $_POST["end_$day"] ?? '17:00';
            $pdo->prepare("INSERT INTO doctor_weekly_availability (doctor_id,day_of_week,start_time,end_time) VALUES (?,?,?,?)")
                ->execute([$_POST['doctor_id'],$day,$st,$et]);
        }
    }
    header('Location: index.php'); exit;
}

$q = $_GET['q'] ?? '';
$doctors = $pdo->prepare("SELECT * FROM doctors WHERE (first_name||' '||last_name ILIKE ? OR specialization ILIKE ?) ORDER BY first_name");
$doctors->execute(["%$q%","%$q%"]);
$doctors = $doctors->fetchAll(PDO::FETCH_ASSOC);

// Get availability for all doctors
$avails = $pdo->query("SELECT * FROM doctor_weekly_availability WHERE is_active=true ORDER BY day_of_week")->fetchAll(PDO::FETCH_ASSOC);
$avail_map = [];
foreach ($avails as $a) { $avail_map[$a['doctor_id']][] = $a; }

$days_label = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Doctors - SLIMM Lab</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.doctor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
.doctor-card { background:#fff; border:1px solid var(--border); border-radius:12px; padding:20px; }
.doc-avatar { width:56px; height:56px; border-radius:50%; background:var(--teal-soft); color:var(--teal); display:grid; place-items:center; font-size:22px; font-weight:700; margin-bottom:12px; }
.avail-badge { display:inline-block; background:var(--teal-soft); color:var(--teal-dark); border-radius:6px; padding:2px 6px; font-size:10px; font-weight:700; margin:2px; }
</style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>Doctors</h1><p>Manage doctor profiles and availability.</p></div>
        <div class="user-area">
            <form method="GET" style="display:flex;gap:8px;">
                <input class="input" name="q" placeholder="Search…" value="<?= htmlspecialchars($q) ?>" style="width:200px">
                <button class="btn btn-primary">Search</button>
            </form>
            <button class="btn btn-primary" onclick="document.getElementById('dlg-add').showModal()">+ Add Doctor</button>
        </div>
    </header>

    <div class="doctor-grid">
    <?php foreach ($doctors as $d): ?>
    <?php $initials = strtoupper(substr($d['first_name'],0,1).substr($d['last_name'],0,1)); ?>
    <div class="doctor-card">
        <div class="doc-avatar"><?= $initials ?></div>
        <strong>Dr. <?= htmlspecialchars($d['first_name'].' '.$d['last_name']) ?></strong>
        <p style="color:var(--muted);font-size:12px;margin:4px 0 8px;"><?= htmlspecialchars($d['specialization']) ?></p>
        <p style="font-size:12px;margin:0 0 8px;">📧 <?= htmlspecialchars($d['email']) ?> <?php if($d['phone']): ?>| 📞 <?= $d['phone'] ?><?php endif; ?></p>
        <div>
            <?php foreach ($avail_map[$d['doctor_id']] ?? [] as $av): ?>
                <span class="avail-badge"><?= $days_label[$av['day_of_week']] ?></span>
            <?php endforeach; ?>
        </div>
        <div class="actions" style="margin-top:12px;">
            <button class="btn btn-sm" onclick='openEdit(<?= htmlspecialchars(json_encode($d)) ?>)'>Edit</button>
            <button class="btn btn-sm" onclick="openAvail(<?= $d['doctor_id'] ?>, '<?= htmlspecialchars($d['first_name'].' '.$d['last_name']) ?>', <?= htmlspecialchars(json_encode($avail_map[$d['doctor_id']] ?? [])) ?>)">Availability</button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this doctor?')">
                <input type="hidden" name="_action" value="delete">
                <input type="hidden" name="doctor_id" value="<?= $d['doctor_id'] ?>">
                <button class="btn btn-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$doctors): ?><p class="empty" style="grid-column:1/-1">No doctors found.</p><?php endif; ?>
    </div>
</main>

<!-- Add Doctor -->
<dialog id="dlg-add">
<form method="POST">
<input type="hidden" name="_action" value="add">
<h3 style="margin:0 0 18px">Add Doctor</h3>
<div class="form-grid">
    <div class="field"><label>First Name *</label><input class="input" name="first_name" required></div>
    <div class="field"><label>Last Name *</label><input class="input" name="last_name" required></div>
    <div class="field"><label>Email</label><input class="input" type="email" name="email"></div>
    <div class="field"><label>Phone</label><input class="input" name="phone"></div>
    <div class="field full"><label>Specialization *</label><input class="input" name="specialization" required></div>
</div>
<div class="form-actions">
    <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
    <button type="submit" class="btn btn-primary">Save</button>
</div>
</form>
</dialog>

<!-- Edit Doctor -->
<dialog id="dlg-edit">
<form method="POST">
<input type="hidden" name="_action" value="edit">
<input type="hidden" name="doctor_id" id="e-id">
<h3 style="margin:0 0 18px">Edit Doctor</h3>
<div class="form-grid">
    <div class="field"><label>First Name *</label><input class="input" name="first_name" id="e-fn" required></div>
    <div class="field"><label>Last Name *</label><input class="input" name="last_name" id="e-ln" required></div>
    <div class="field"><label>Email</label><input class="input" type="email" name="email" id="e-em"></div>
    <div class="field"><label>Phone</label><input class="input" name="phone" id="e-ph"></div>
    <div class="field full"><label>Specialization *</label><input class="input" name="specialization" id="e-sp" required></div>
</div>
<div class="form-actions">
    <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
    <button type="submit" class="btn btn-primary">Update</button>
</div>
</form>
</dialog>

<!-- Availability -->
<dialog id="dlg-avail">
<form method="POST">
<input type="hidden" name="_action" value="avail">
<input type="hidden" name="doctor_id" id="av-id">
<h3 id="av-title" style="margin:0 0 18px">Set Availability</h3>
<p style="color:var(--muted);font-size:12px;margin-bottom:16px;">Check the days this doctor is available.</p>
<div id="avail-rows"></div>
<div class="form-actions">
    <button type="button" class="btn" onclick="this.closest('dialog').close()">Cancel</button>
    <button type="submit" class="btn btn-primary">Save Availability</button>
</div>
</form>
</dialog>

<script>
function openEdit(d) {
    document.getElementById('e-id').value = d.doctor_id;
    document.getElementById('e-fn').value = d.first_name;
    document.getElementById('e-ln').value = d.last_name;
    document.getElementById('e-em').value = d.email || '';
    document.getElementById('e-ph').value = d.phone || '';
    document.getElementById('e-sp').value = d.specialization;
    document.getElementById('dlg-edit').showModal();
}
const dayNames = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
function openAvail(id, name, avails) {
    document.getElementById('av-id').value = id;
    document.getElementById('av-title').textContent = 'Availability — Dr. ' + name;
    const avMap = {};
    avails.forEach(a => { avMap[a.day_of_week] = a; });
    let html = '';
    dayNames.forEach((d, i) => {
        const checked = avMap[i] ? 'checked' : '';
        const st = avMap[i] ? avMap[i].start_time.substring(0,5) : '08:00';
        const et = avMap[i] ? avMap[i].end_time.substring(0,5) : '17:00';
        html += `<div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
            <input type="checkbox" name="days[]" value="${i}" ${checked} id="day${i}">
            <label for="day${i}" style="width:90px">${d}</label>
            <input type="time" name="start_${i}" value="${st}" class="input" style="width:100px">
            <span>–</span>
            <input type="time" name="end_${i}" value="${et}" class="input" style="width:100px">
        </div>`;
    });
    document.getElementById('avail-rows').innerHTML = html;
    document.getElementById('dlg-avail').showModal();
}
</script>
</body></html>