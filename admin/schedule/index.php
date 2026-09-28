<?php
session_start();
require_once '../../config/database.php';
if (!in_array($_SESSION['user_role'] ?? '', ['admin','staff'])) { header('Location: ../../index.php'); exit; }

// Handle assign block
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $date = $_POST['schedule_date'];
    $type = $_POST['block_type'];
    $note = $_POST['note'] ?? '';
    $times = ['Morning'=>['08:00','12:00'],'Afternoon'=>['13:00','17:00'],'Whole Day'=>['08:00','17:00']];
    [$st,$et] = $times[$type] ?? ['08:00','17:00'];
    $pdo->prepare("INSERT INTO doctor_schedule_blocks (doctor_id,schedule_date,start_time,end_time,block_type,status,note,created_by) VALUES (?,?,?,?,?,'Awaiting doctor',?,?)")
        ->execute([$doctor_id,$date,$st,$et,$type,$note,$_SESSION['user_id']]);
    header('Location: index.php?year='.date('Y',strtotime($date)).'&month='.date('n',strtotime($date))); exit;
}

$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));
$first = mktime(0,0,0,$month,1,$year);
$days_in_month = (int)date('t',$first);
$start_dow = (int)date('N',$first) - 1; // 0=Mon

// Fetch blocks for this month
$blocks = $pdo->prepare("SELECT b.*, d.first_name||' '||d.last_name as doc_name FROM doctor_schedule_blocks b JOIN doctors d ON b.doctor_id=d.doctor_id WHERE schedule_date >= ? AND schedule_date <= ?");
$blocks->execute(["$year-".str_pad($month,2,'0',STR_PAD_LEFT)."-01", "$year-".str_pad($month,2,'0',STR_PAD_LEFT)."-".str_pad($days_in_month,2,'0',STR_PAD_LEFT)]);
$blocks = $blocks->fetchAll(PDO::FETCH_ASSOC);
$block_map = [];
foreach ($blocks as $b) { $block_map[$b['schedule_date']][] = $b; }

// Today's blocks
$today_blocks = $pdo->prepare("SELECT b.*, d.first_name||' '||d.last_name as doc_name FROM doctor_schedule_blocks b JOIN doctors d ON b.doctor_id=d.doctor_id WHERE b.schedule_date=CURRENT_DATE ORDER BY b.start_time");
$today_blocks->execute();
$today_blocks = $today_blocks->fetchAll(PDO::FETCH_ASSOC);

// Doctors list
$doctors = $pdo->query("SELECT doctor_id, first_name||' '||last_name as name FROM doctors ORDER BY first_name")->fetchAll(PDO::FETCH_ASSOC);

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
.cal-nav { display:flex; align-items:center; gap:16px; margin-bottom:16px; }
.cal-nav h2 { margin:0; font-size:18px; }
.cal-grid { display:grid; grid-template-columns:repeat(7,1fr); border-top:1px solid var(--border); border-left:1px solid var(--border); }
.cal-head { text-align:center; padding:8px; font-size:11px; font-weight:700; color:var(--muted); background:#fafbfb; border-right:1px solid var(--border); border-bottom:1px solid var(--border); }
.cal-day { min-height:90px; padding:6px; border-right:1px solid var(--border); border-bottom:1px solid var(--border); background:#fff; }
.cal-day.empty { background:#fafbfb; }
.cal-day.today { background:#fffbf0; }
.day-num { font-size:11px; font-weight:700; margin-bottom:4px; }
.day-num.today-num { color:var(--teal); }
.block-pill { border-radius:4px; padding:2px 5px; font-size:10px; margin-bottom:2px; color:#fff; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
.block-pill.awaiting { background:#b78a00; }
.block-pill.confirmed { background:var(--teal); }
.block-pill.declined { background:var(--danger); }
</style>
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>Schedule</h1><p>Assign doctor duty blocks and view monthly schedule.</p></div>
        <div class="user-area"><span><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin') ?></span></div>
    </header>

    <!-- Calendar -->
    <div class="panel" style="margin-bottom:20px;">
        <div class="cal-nav">
            <a class="btn" href="?year=<?= $prev_year ?>&month=<?= $prev_month ?>">‹ Prev</a>
            <h2><?= date('F Y', $first) ?></h2>
            <a class="btn" href="?year=<?= $next_year ?>&month=<?= $next_month ?>">Next ›</a>
        </div>
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
                <div class="day-num <?= $is_today ? 'today-num' : '' ?>"><?= $day ?></div>
                <?php foreach ($block_map[$date_str] ?? [] as $b):
                    $cls = ['Awaiting doctor'=>'awaiting','Confirmed'=>'confirmed','Declined'=>'declined'][$b['status']] ?? 'awaiting';
                    $name_short = explode(' ',$b['doc_name']);
                    $abbrev = 'Dr. '.($name_short[0][0] ?? '').'. '.($name_short[1] ?? '');
                ?>
                <div class="block-pill <?= $cls ?>" title="Dr. <?= htmlspecialchars($b['doc_name']) ?> | <?= $b['block_type'] ?> | <?= $b['status'] ?>"><?= htmlspecialchars($abbrev) ?> (<?= $b['block_type'] ?>)</div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Assign Duty Block -->
    <div class="grid-2" style="margin-bottom:20px;">
        <div class="panel">
            <div class="panel-header"><h3 class="panel-title">Assign Duty Block</h3></div>
            <form method="POST" style="padding-top:12px;">
                <div class="field"><label>Doctor *</label>
                    <select class="input" name="doctor_id" required>
                        <option value="">— select doctor —</option>
                        <?php foreach ($doctors as $doc): ?>
                        <option value="<?= $doc['doctor_id'] ?>">Dr. <?= htmlspecialchars($doc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label>Date *</label><input class="input" type="date" name="schedule_date" required min="<?= date('Y-m-d') ?>"></div>
                <div class="field"><label>Block Type *</label>
                    <select class="input" name="block_type" required>
                        <option value="Morning">Morning (8:00 AM – 12:00 PM)</option>
                        <option value="Afternoon">Afternoon (1:00 PM – 5:00 PM)</option>
                        <option value="Whole Day">Whole Day (8:00 AM – 5:00 PM)</option>
                    </select>
                </div>
                <div class="field"><label>Note</label><textarea class="input textarea" name="note" rows="2" placeholder="Optional note…"></textarea></div>
                <button type="submit" class="btn btn-primary" style="width:100%">Assign Block</button>
            </form>
        </div>

        <div class="panel">
            <div class="panel-header"><h3 class="panel-title">Today's Blocks</h3></div>
            <?php if ($today_blocks): ?>
            <table style="width:100%">
                <thead><tr><th>Doctor</th><th>Type</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($today_blocks as $b): ?>
                <tr>
                    <td>Dr. <?= htmlspecialchars($b['doc_name']) ?></td>
                    <td><?= $b['block_type'] ?></td>
                    <td><?= substr($b['start_time'],0,5) ?> – <?= substr($b['end_time'],0,5) ?></td>
                    <td><span class="badge <?= $status_class[$b['status']] ?? 'badge-neutral' ?>"><?= $b['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?><p class="empty">No blocks assigned today.</p><?php endif; ?>
        </div>
    </div>
</main>
</body></html>
