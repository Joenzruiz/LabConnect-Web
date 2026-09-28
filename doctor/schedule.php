<?php
session_start();
require_once '../config/database.php';
if (($_SESSION['user_role'] ?? '') !== 'doctor') { header('Location: ../index.php'); exit; }

$doctor = $pdo->prepare("SELECT * FROM doctors WHERE user_id=?");
$doctor->execute([$_SESSION['user_id']]);
$doctor = $doctor->fetch(PDO::FETCH_ASSOC);
if (!$doctor) { session_destroy(); header('Location: ../index.php'); exit; }
$doc_id   = $doctor['doctor_id'];
$doc_name = 'Dr. '.$doctor['first_name'].' '.$doctor['last_name'];

// Handle confirm/decline
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $block_id = (int)$_POST['block_id'];
    $action   = $_POST['action'] ?? '';
    if (in_array($action, ['Confirmed','Declined'])) {
        $pdo->prepare("UPDATE doctor_schedule_blocks SET status=?, confirmed_at=NOW() WHERE schedule_block_id=? AND doctor_id=?")
            ->execute([$action, $block_id, $doc_id]);
    }
    header('Location: schedule.php'); exit;
}

// Calendar
$year  = (int)($_GET['year']  ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));
$first = mktime(0,0,0,$month,1,$year);
$days_in_month = (int)date('t',$first);
$start_dow = (int)date('N',$first) - 1; // Mon=0

// Load blocks for this month
$blocks_month = $pdo->prepare("SELECT schedule_date, block_type, status FROM doctor_schedule_blocks WHERE doctor_id=? AND EXTRACT(YEAR FROM schedule_date)=? AND EXTRACT(MONTH FROM schedule_date)=?");
$blocks_month->execute([$doc_id, $year, $month]);
$cal_blocks = [];
foreach ($blocks_month->fetchAll(PDO::FETCH_ASSOC) as $b) {
    $cal_blocks[$b['schedule_date']][] = $b;
}

// Load all upcoming blocks for list view
$upcoming_blocks = $pdo->prepare("SELECT * FROM doctor_schedule_blocks WHERE doctor_id=? AND schedule_date >= CURRENT_DATE ORDER BY schedule_date, start_time");
$upcoming_blocks->execute([$doc_id]);
$upcoming_blocks = $upcoming_blocks->fetchAll(PDO::FETCH_ASSOC);

$status_colors = ['Awaiting doctor'=>'badge-pending','Confirmed'=>'badge-confirmed','Declined'=>'badge-cancelled'];
$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Schedule — <?= htmlspecialchars($doc_name) ?></title>
<link rel="stylesheet" href="../css/style.css">
<style>
.cal-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:1px; background:var(--border); border:1px solid var(--border); border-radius:8px; overflow:hidden; }
.cal-head { background:var(--teal); color:#fff; text-align:center; padding:8px 4px; font-size:12px; font-weight:600; }
.cal-day { background:#fff; min-height:80px; padding:6px; font-size:12px; }
.cal-day.today { background:#f0fdfa; }
.cal-day.empty { background:var(--bg); }
.cal-day-num { font-weight:600; color:var(--muted); margin-bottom:4px; }
.cal-pill { border-radius:4px; padding:2px 6px; font-size:10px; margin-bottom:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pill-awaiting { background:#FFF8E1; color:#F57F17; }
.pill-confirmed { background:#E0F2F1; color:#00695C; }
.pill-declined  { background:#FFEBEE; color:#C62828; }
.block-card { background:#fff; border:1px solid var(--border); border-radius:10px; padding:16px 20px; margin-bottom:12px; display:flex; justify-content:space-between; align-items:center; gap:12px; }
.block-info strong { display:block; font-size:15px; margin-bottom:2px; }
.block-info span   { font-size:13px; color:var(--muted); }
.block-actions { display:flex; gap:8px; }
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>My Schedule</h1><p>View and confirm your duty blocks.</p></div>
        <div class="user-area">
            <span><?= htmlspecialchars($doc_name) ?></span>
            <a href="../api/logout.php" class="btn">Logout</a>
        </div>
    </header>

    <div style="padding:24px 28px 0;">
        <!-- Calendar nav -->
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
            <a href="?year=<?= $prev_year ?>&month=<?= $prev_month ?>" class="btn">← Prev</a>
            <h2 style="margin:0;font-size:18px;"><?= date('F Y', $first) ?></h2>
            <a href="?year=<?= $next_year ?>&month=<?= $next_month ?>" class="btn">Next →</a>
        </div>

        <!-- Calendar grid -->
        <div class="cal-grid" style="margin-bottom:32px;">
            <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d): ?>
            <div class="cal-head"><?= $d ?></div>
            <?php endforeach; ?>
            <?php for ($i = 0; $i < $start_dow; $i++): ?>
            <div class="cal-day empty"></div>
            <?php endfor; ?>
            <?php for ($day = 1; $day <= $days_in_month; $day++):
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $isToday = ($dateStr === date('Y-m-d'));
            ?>
            <div class="cal-day <?= $isToday?'today':'' ?>">
                <div class="cal-day-num"><?= $day ?></div>
                <?php foreach ($cal_blocks[$dateStr] ?? [] as $b):
                    $cls = $b['status']==='Confirmed'?'pill-confirmed':($b['status']==='Declined'?'pill-declined':'pill-awaiting');
                ?>
                <div class="cal-pill <?= $cls ?>"><?= $b['block_type'] ?></div>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>

        <!-- Upcoming Blocks -->
        <h3 style="margin:0 0 16px;font-size:16px;">Upcoming Duty Blocks</h3>
        <?php if ($upcoming_blocks): ?>
        <?php foreach ($upcoming_blocks as $b): ?>
        <div class="block-card">
            <div class="block-info">
                <strong><?= date('D, M j, Y', strtotime($b['schedule_date'])) ?> &nbsp;
                    <span class="badge <?= $status_colors[$b['status']] ?? '' ?>"><?= $b['status'] ?></span>
                </strong>
                <span><?= $b['block_type'] ?> &bull; <?= substr($b['start_time'],0,5) ?> – <?= substr($b['end_time'],0,5) ?></span>
                <?php if ($b['note']): ?><span style="display:block;margin-top:4px;color:var(--muted)"><?= htmlspecialchars($b['note']) ?></span><?php endif; ?>
            </div>
            <?php if ($b['status'] === 'Awaiting doctor'): ?>
            <div class="block-actions">
                <form method="POST">
                    <input type="hidden" name="block_id" value="<?= $b['schedule_block_id'] ?>">
                    <button name="action" value="Confirmed" class="btn btn-success btn-sm">✓ Confirm</button>
                    <button name="action" value="Declined" class="btn btn-danger btn-sm" onclick="return confirm('Decline this block?')">✗ Decline</button>
                </form>
            </div>
            <?php elseif ($b['status'] === 'Confirmed'): ?>
            <span style="color:var(--success);font-size:13px;font-weight:600;">✓ Confirmed</span>
            <?php elseif ($b['status'] === 'Declined'): ?>
            <span style="color:var(--danger);font-size:13px;font-weight:600;">✗ Declined</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="empty" style="text-align:center;padding:40px;color:var(--muted);">No upcoming duty blocks assigned yet.</div>
        <?php endif; ?>
    </div>
</main>
</body></html>
