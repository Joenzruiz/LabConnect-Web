<?php
session_start();
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff') {
        header('Location: admin/dashboard.php'); exit;
    }
    if ($_SESSION['user_role'] === 'doctor') {
        header('Location: doctor/appointments.php'); exit;
    }
}
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — SLIMM Laboratory Inc.</title>
<link rel="stylesheet" href="css/style.css">
<style>
.login-page { min-height:100vh; display:grid; place-items:center; background: linear-gradient(135deg,#e7f6f6 0%,#f5f7f8 60%); padding:24px; }
.login-card { width:420px; background:#fff; border:1px solid var(--border); border-radius:16px; padding:40px 36px; box-shadow:0 4px 40px rgba(15,139,141,.10); }
.login-brand { text-align:center; margin-bottom:32px; }
.login-logo { width:72px; height:72px; border-radius:50%; background:var(--teal); display:grid; place-items:center; margin:0 auto 16px; }
.login-logo svg { fill:#fff; }
.login-brand h1 { font-size:20px; margin:0 0 6px; letter-spacing:-.01em; }
.login-brand p { color:var(--muted); font-size:13px; margin:4px 0 0; }
.field label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:var(--muted); }
</style>
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-brand">
        <div class="login-logo" style="background: transparent;">
            <img src="img/logo.jpg" alt="SLIMM Lab Logo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
        </div>
        <h1>SLIMM Laboratory Inc.</h1>
        <p>Laboratory Appointment &amp; Scheduling System</p>
        <p style="margin-top:4px;color:var(--muted);font-size:12px;">Staff &amp; Doctor Portal</p>
    </div>

    <?php if ($error): ?>
    <div class="alert error" style="margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="api/login.php" method="POST">
        <div class="field" style="margin-bottom:14px;">
            <label for="email">Email address</label>
            <input class="input" id="email" type="email" name="email" placeholder="you@slimmlab.com" required autofocus>
        </div>
        <div class="field" style="margin-bottom:20px;">
            <label for="password">Password</label>
            <div class="password-wrap">
                <input class="input" id="password" type="password" name="password" placeholder="Enter your password" required>
                <button class="toggle-password" type="button" onclick="
                    var i=document.getElementById('password');
                    var b=this;
                    if(i.type==='password'){i.type='text';b.textContent='Hide';}else{i.type='password';b.textContent='Show';}
                ">Show</button>
            </div>
        </div>
        <button class="btn btn-primary login-button" type="submit">Sign in</button>
    </form>
</div>
</body>
</html>