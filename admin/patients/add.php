<?php

session_start();

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

require_once '../../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $birthDate = trim($_POST['birth_date'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (
        $firstName === '' ||
        $lastName === '' ||
        $gender === '' ||
        $birthDate === '' ||
        $email === '' ||
        $password === ''
    ) {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {

        try {

            $checkEmail = $pdo->prepare(
                "SELECT user_id
                 FROM users
                 WHERE email = :email
                 LIMIT 1"
            );

            $checkEmail->execute([
                ':email' => $email
            ]);

            if ($checkEmail->fetch()) {

                $error = 'Email address is already registered.';

            } else {

                $pdo->beginTransaction();

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $userStatement = $pdo->prepare(
                    "INSERT INTO users
                    (email, password_hash, role, status)
                    VALUES
                    (:email, :password_hash, 'Laboratory Staff', 'Active')
                    RETURNING user_id"
                );

                $userStatement->execute([
                    ':email' => $email,
                    ':password_hash' => $passwordHash
                ]);

                $user = $userStatement->fetch(PDO::FETCH_ASSOC);

                $patientStatement = $pdo->prepare(
                    "INSERT INTO patients
                    (user_id, first_name, last_name, gender, birth_date)
                    VALUES
                    (:user_id, :first_name, :last_name, :gender, :birth_date)"
                );

                $patientStatement->execute([
                    ':user_id' => $user['user_id'],
                    ':first_name' => $firstName,
                    ':last_name' => $lastName,
                    ':gender' => $gender,
                    ':birth_date' => $birthDate
                ]);

                $pdo->commit();

                header('Location: index.php');
                exit;
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = 'Unable to create patient: ' . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Add Patient - SLIMM Laboratory Inc.</title>

    <link
        rel="stylesheet"
        href="../../css/style.css"
    >

</head>

<body>

<?php include '../../includes/sidebar.php'; ?>

<main class="content">

    <header class="topbar">

        <div class="page-title">

            <h1>Add Patient</h1>

            <p>
                SLIMM LABORATORY INC. · Baybay City, Leyte
            </p>

        </div>

        <div class="user-area">

            <div class="avatar">
                <?= strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)) ?>
            </div>

            <a
                class="btn logout"
                href="../../api/logout.php"
            >
                Logout
            </a>

        </div>

    </header>

    <?php if ($error): ?>

        <div class="alert error">
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>

    <div class="table-card">

        <form method="POST">

            <div class="field">

                <label for="first_name">
                    First Name
                </label>

                <input
                    class="input"
                    id="first_name"
                    name="first_name"
                    type="text"
                    required
                >

            </div>

            <div class="field" style="margin-top: 14px;">

                <label for="last_name">
                    Last Name
                </label>

                <input
                    class="input"
                    id="last_name"
                    name="last_name"
                    type="text"
                    required
                >

            </div>

            <div class="field" style="margin-top: 14px;">

                <label for="gender">
                    Gender
                </label>

                <select
                    class="select"
                    id="gender"
                    name="gender"
                    required
                >

                    <option value="">
                        Select Gender
                    </option>

                    <option value="Male">
                        Male
                    </option>

                    <option value="Female">
                        Female
                    </option>

                </select>

            </div>

            <div class="field" style="margin-top: 14px;">

                <label for="birth_date">
                    Birth Date
                </label>

                <input
                    class="input"
                    id="birth_date"
                    name="birth_date"
                    type="date"
                    required
                >

            </div>

            <div class="field" style="margin-top: 14px;">

                <label for="email">
                    Email
                </label>

                <input
                    class="input"
                    id="email"
                    name="email"
                    type="email"
                    required
                >

            </div>

            <div class="field" style="margin-top: 14px;">

                <label for="password">
                    Password
                </label>

                <input
                    class="input"
                    id="password"
                    name="password"
                    type="password"
                    minlength="8"
                    required
                >

            </div>

            <div style="margin-top: 20px;">

                <button
                    class="btn btn-primary"
                    type="submit"
                >
                    Save Patient
                </button>

                <a
                    class="btn"
                    href="index.php"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>

</main>

</body>

</html>