<?php
session_start();
require_once '../../config/database.php';
if (($_SESSION['user_role'] ?? '') !== 'admin') { header('Location: ../../index.php'); exit; }

$config_file = __DIR__.'/../../config/settings.json';
$settings = file_exists($config_file) ? json_decode(file_get_contents($config_file), true) : [];
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'lab_name'      => $_POST['lab_name'] ?? '',
        'address'       => $_POST['address'] ?? '',
        'contact'       => $_POST['contact'] ?? '',
        'email'         => $_POST['email'] ?? '',
        'working_hours' => $_POST['working_hours'] ?? '',
        'footer_note'   => $_POST['footer_note'] ?? '',
    ];
    file_put_contents($config_file, json_encode($settings, JSON_PRETTY_PRINT));
    $saved = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings - SLIMM Lab</title>
<link rel="stylesheet" href="../../css/style.css">
</head>
<body>
<?php include '../../includes/sidebar.php'; ?>
<main class="content">
    <header class="topbar">
        <div class="page-title"><h1>Settings</h1><p>Laboratory information and configuration.</p></div>
    </header>

    <div class="page-body">
    <?php if ($saved): ?>
    <div class="alert" style="background:var(--success-soft);border-color:#b2d8c2;color:var(--success);margin-bottom:20px;">✅ Settings saved successfully.</div>
    <?php endif; ?>

    <div class="panel" style="max-width:600px;">
        <form method="POST">
            <div class="field"><label>Laboratory Name</label><input class="input" name="lab_name" value="<?= htmlspecialchars($settings['lab_name'] ?? 'SLIMM Laboratory Inc.') ?>"></div>
            <div class="field"><label>Address</label><input class="input" name="address" value="<?= htmlspecialchars($settings['address'] ?? 'Baybay City, Leyte') ?>"></div>
            <div class="field"><label>Contact Number</label><input class="input" name="contact" value="<?= htmlspecialchars($settings['contact'] ?? '') ?>"></div>
            <div class="field"><label>Email Address</label><input class="input" type="email" name="email" value="<?= htmlspecialchars($settings['email'] ?? '') ?>"></div>
            <div class="field"><label>Working Hours</label><input class="input" name="working_hours" placeholder="e.g. Mon–Fri 8:00 AM – 5:00 PM" value="<?= htmlspecialchars($settings['working_hours'] ?? '') ?>"></div>
            <div class="field"><label>Footer Note</label><textarea class="input textarea" name="footer_note" rows="3"><?= htmlspecialchars($settings['footer_note'] ?? '') ?></textarea></div>
            <div class="form-actions"><button type="submit" class="btn btn-primary">Save Settings</button></div>
        </form>
    </div>
    </div>
</main>
</body></html>