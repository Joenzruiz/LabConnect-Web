<?php
session_start();
require_once '../../config/database.php';
if (!in_array($_SESSION['user_role'] ?? '', ['admin','staff'])) { header('Location: ../../index.php'); exit; }

// Handle POST — always inserts as 'Awaiting doctor'
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = (int)$_POST['doctor_id'];
    $date      = $_POST['schedule_date'];
    $type      = $_POST['block_type'];
    $note      = $_POST['note'] ?? '';
    $st        = $_POST['start_time'] ?? '08:00';
    $et        = $_POST['end_time']   ?? '17:00';

    $pdo->prepare("INSERT INTO doctor_schedule_blocks (doctor_id,schedule_date,start_time,end_time,block_type,status,note,created_by)
                   VALUES (?,?,?,?,?,'Awaiting doctor',?,?)")
        ->execute([$doctor_id,$date,$st,$et,$type,$note,$_SESSION['user_id']]);

    header('Location: index.php?year='.date('Y',strtotime($date)).'&month='.date('n',strtotime($date)));
    exit;
}

$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));
$first = mktime(0,0,0,$month,1,$year);
$days_in_month = (int)date('t',$first);
$start_dow     = (int)date('N',$first) - 1; // 0=Mon

// Fetch blocks for this month
$startDate = "$year-".str_pad($month,2,'0',STR_PAD_LEFT)."-01";
$endDate   = "$year-".str_pad($month,2,'0',STR_PAD_LEFT)."-".str_pad($days_in_month,2,'0',STR_PAD_LEFT);
$stmt = $pdo->prepare("SELECT b.*, d.first_name||' '||d.last_name as doc_name, d.specialization
                        FROM doctor_schedule_blocks b
                        JOIN doctors d ON b.doctor_id=d.doctor_id
                        WHERE schedule_date >= ? AND schedule_date <= ?
                        ORDER BY b.schedule_date, b.start_time");
$stmt->execute([$startDate,$endDate]);
$blocksList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$block_map = [];
foreach ($blocksList as $b) { $block_map[$b['schedule_date']][] = $b; }

// Today's blocks
$td = $pdo->prepare("SELECT b.*, d.first_name||' '||d.last_name as doc_name
                     FROM doctor_schedule_blocks b
                     JOIN doctors d ON b.doctor_id=d.doctor_id
                     WHERE b.schedule_date=CURRENT_DATE ORDER BY b.start_time");
$td->execute();
$today_blocks = $td->fetchAll(PDO::FETCH_ASSOC);

// Doctors list
$doctors = $pdo->query("SELECT doctor_id, first_name||' '||last_name as name, specialization FROM doctors ORDER BY first_name")->fetchAll(PDO::FETCH_ASSOC);

$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

$status_class = ['Awaiting doctor'=>'badge-pending','Confirmed'=>'badge-confirmed','Declined'=>'badge-cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Schedule - SLIMM Lab</title>
<link rel="stylesheet" href="../../css/style.css">
<style>
.cal-nav { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.cal-nav h2 { margin:0; font-size:20px; font-weight:700; color:var(--text); }
.cal-grid { display:grid; grid-template-columns:repeat(7,1fr); border-top:1px solid var(--border); border-left:1px solid var(--border); border-radius:8px; overflow:hidden; }
.cal-head { text-align:center; padding:10px; font-size:12px; font-weight:700; color:var(--muted); background:var(--surface-alt); border-right:1px solid var(--border); border-bottom:1px solid var(--border); }
.cal-day { min-height:100px; padding:8px; border-right:1px solid var(--border); border-bottom:1px solid var(--border); background:var(--surface); }
.cal-day.empty { background:var(--surface-alt); }
.cal-day.today { background:rgba(20,184,166,0.08); }
.day-num { font-size:12px; font-weight:700; color:var(--muted); margin-bottom:6px; }
.day-num.today-num { color:var(--teal); font-weight:800; }
.block-pill { border-radius:6px; padding:3px 6px; font-size:11px; font-weight:600; margin-bottom:4px; color:#fff; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; display:block; }
.block-pill.awaiting  { background:#f59e0b; }
.block-pill.confirmed { background:var(--teal); }
.block-pill.declined  { background:var(--danger); }
</style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title">
            <h1>Schedule</h1>
            <p>Manage doctor duty blocks and monthly schedule. Doctors confirm/decline via their portal.</p>
        </div>
        <div class="user-area">
            <span><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></span>
        </div>
    </header>

    <div class="page-body">
    <!-- Calendar -->
    <div class="panel" style="margin-bottom:24px; padding:20px;">
        <div class="cal-nav">
            <a class="btn" href="?year=<?= $prev_year ?>&month=<?= $prev_month ?>">‹ Previous</a>
            <h2><?= date('F Y', $first) ?></h2>
            <a class="btn" href="?year=<?= $next_year ?>&month=<?= $next_month ?>">Next ›</a>
        </div>
        <div>
        <div class="cal-grid">
            <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $h): ?>
            <div class="cal-head"><?= $h ?></div>
            <?php endforeach; ?>

            <?php for ($i = 0; $i < $start_dow; $i++): ?>
            <div class="cal-day empty"></div>
            <?php endfor; ?>

            <?php for ($day = 1; $day <= $days_in_month; $day++):
                $date_str = "$year-".str_pad($month,2,'0',STR_PAD_LEFT)."-".str_pad($day,2,'0',STR_PAD_LEFT);
                $is_today = $date_str === date('Y-m-d');
            ?>
            <div class="cal-day <?= $is_today ? 'today' : '' ?>">
                <div class="day-num <?= $is_today ? 'today-num' : '' ?>"><?= $day ?><?= $is_today ? ' ●' : '' ?></div>
                <?php foreach ($block_map[$date_str] ?? [] as $b):
                    $cls = ['Awaiting doctor'=>'awaiting','Confirmed'=>'confirmed','Declined'=>'declined'][$b['status']] ?? 'awaiting';
                    $parts = explode(' ', $b['doc_name']);
                    $abbrev = 'Dr. '.($parts[0][0] ?? '').'. '.($parts[1] ?? '');
                ?>
                <div class="block-pill <?= $cls ?>" title="Dr. <?= htmlspecialchars($b['doc_name']) ?> | <?= $b['block_type'] ?> <?= substr($b['start_time'],0,5) ?>-<?= substr($b['end_time'],0,5) ?> | <?= $b['status'] ?>">
                    <?= htmlspecialchars($abbrev) ?> · <?= $b['block_type'] ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>
        </div>
    </div>

    <!-- Assign form + Today's blocks -->
    <div class="grid-2" style="margin-bottom:24px;">
        <div class="panel">
            <div class="panel-header"><h3 class="panel-title">Assign Duty Block</h3><p class="panel-subtitle">Block will be sent to doctor for confirmation.</p></div>
            <form method="POST" style="padding:16px;">
                <div class="field">
                    <label>Doctor *</label>
                    <select class="input" name="doctor_id" required>
                        <option value="">— Select Doctor —</option>
                        <?php foreach ($doctors as $doc): ?>
                        <option value="<?= $doc['doctor_id'] ?>">Dr. <?= htmlspecialchars($doc['name']) ?> (<?= htmlspecialchars($doc['specialization']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Duty Date *</label>
                    <input class="input" type="date" name="schedule_date" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="field">
                    <label>Block Type / Preset *</label>
                    <select class="input" name="block_type" id="block_type_sel" required onchange="applyPreset(this.value)">
                        <option value="Morning">Morning (8:00 AM – 12:00 PM)</option>
                        <option value="Afternoon">Afternoon (1:00 PM – 5:00 PM)</option>
                        <option value="Whole Day" selected>Whole Day (8:00 AM – 5:00 PM)</option>
                    </select>
                    <small style="color:var(--muted);font-size:11px;margin-top:4px;display:block;">⏱ You can adjust the start/end times below freely.</small>
                </div>
                <div class="form-grid" style="grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="field">
                        <label>Start Time *</label>
                        <input class="input" type="time" name="start_time" id="start_time" value="08:00" required>
                    </div>
                    <div class="field">
                        <label>End Time *</label>
                        <input class="input" type="time" name="end_time" id="end_time" value="17:00" required>
                    </div>
                </div>
                <div class="field">
                    <label>Notes / Instructions</label>
                    <textarea class="input textarea" name="note" rows="2" placeholder="Optional (e.g. On-call, Pathologist duty)"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px;">+ Assign Duty Block</button>
            </form>
        </div>

        <div class="panel">
            <div class="panel-header"><h3 class="panel-title">Today's Duty Blocks</h3><p class="panel-subtitle"><?= date('F d, Y') ?></p></div>
            <?php if ($today_blocks): ?>
            <div class="table-card">
            <table style="width:100%;">
                <thead><tr><th>Doctor</th><th>Type</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($today_blocks as $b): ?>
                <tr>
                    <td><strong>Dr. <?= htmlspecialchars($b['doc_name']) ?></strong></td>
                    <td><?= $b['block_type'] ?></td>
                    <td><?= substr($b['start_time'],0,5) ?> – <?= substr($b['end_time'],0,5) ?></td>
                    <td><span class="badge <?= $status_class[$b['status']] ?? 'badge-neutral' ?>"><?= $b['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php else: ?>
            <p style="padding:24px;text-align:center;color:var(--muted);">No duty blocks scheduled for today.</p>
            <?php endif; ?>
        </div>
    </div>
    </div>

</main>
<script>
const presets = {
    'Morning':   ['08:00','12:00'],
    'Afternoon': ['13:00','17:00'],
    'Whole Day': ['08:00','17:00']
};
function applyPreset(val) {
    const [st, et] = presets[val] || [];
    if (st) document.getElementById('start_time').value = st;
    if (et) document.getElementById('end_time').value   = et;
}
// Init on load
applyPreset(document.getElementById('block_type_sel').value);
</script>
</body>
</html>
