<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php'); exit;
}

$email    = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter your email and password.';
    header('Location: ../index.php'); exit;
}

try {
    $stmt = $pdo->prepare("SELECT u.*, 
        COALESCE(d.first_name||' '||d.last_name, p.first_name||' '||p.last_name) as full_name
        FROM users u
        LEFT JOIN doctors d ON d.user_id=u.user_id
        LEFT JOIN patients p ON p.user_id=u.user_id
        WHERE u.email=:email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_error'] = 'Invalid email or password.';
        header('Location: ../index.php'); exit;
    }

    if ($user['status'] !== 'Active') {
        $_SESSION['login_error'] = 'Your account is disabled. Contact administrator.';
        header('Location: ../index.php'); exit;
    }

    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['full_name']  = $user['full_name'] ?? $user['email'];

    // Role-based redirect
    switch ($user['role']) {
        case 'Administrator':
            $_SESSION['user_role'] = 'admin';
            $pdo->prepare("UPDATE users SET last_active=NOW() WHERE user_id=?")->execute([$user['user_id']]);
            header('Location: ../admin/dashboard.php'); break;
        case 'Laboratory Staff':
            $_SESSION['user_role'] = 'staff';
            $pdo->prepare("UPDATE users SET last_active=NOW() WHERE user_id=?")->execute([$user['user_id']]);
            header('Location: ../admin/dashboard.php'); break;
        case 'Doctor':
            $_SESSION['user_role'] = 'doctor';
            $pdo->prepare("UPDATE users SET last_active=NOW() WHERE user_id=?")->execute([$user['user_id']]);
            header('Location: ../doctor/appointments.php'); break;
        default:
            $_SESSION['login_error'] = 'Invalid account role.';
            session_destroy();
            header('Location: ../index.php');
    }
    exit;

} catch (PDOException $e) {
    $_SESSION['login_error'] = 'Database error. Please try again.';
    header('Location: ../index.php'); exit;
}