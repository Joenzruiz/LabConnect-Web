<?php

session_start();

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter your email and password.';
    header('Location: ../index.php');
    exit;
}

try {
    $sql = "SELECT user_id, email, password_hash, role, status
            FROM users
            WHERE email = :email
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_error'] = 'Invalid email or password.';
        header('Location: ../index.php');
        exit;
    }

    if ($user['status'] !== 'Active') {
        $_SESSION['login_error'] = 'Your account is disabled. Please contact the administrator.';
        header('Location: ../index.php');
        exit;
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    $update = $pdo->prepare(
        "UPDATE users
         SET last_active = CURRENT_TIMESTAMP
         WHERE user_id = :user_id"
    );

    $update->execute([
        ':user_id' => $user['user_id']
    ]);

    if ($user['role'] === 'Administrator') {
        header('Location: ../admin/dashboard.php');
        exit;
    }

    if ($user['role'] === 'Laboratory Staff') {
        header('Location: ../admin/dashboard.php');
        exit;
    }

    if ($user['role'] === 'Doctor') {
        header('Location: ../doctor/appointments.php');
        exit;
    }

    $_SESSION['login_error'] = 'Invalid user role.';
    session_destroy();

    header('Location: ../index.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['login_error'] = 'Unable to connect to the database.';
    header('Location: ../index.php');
    exit;
}