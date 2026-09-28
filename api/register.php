<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed.'
    ]);

    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data.'
    ]);

    exit;
}

$fullName = trim($data['full_name'] ?? '');
$email = strtolower(trim($data['email'] ?? ''));
$mobile = trim($data['mobile'] ?? '');
$dob = trim($data['dob'] ?? '');
// Normalize date: if MM/DD/YYYY convert to YYYY-MM-DD
if ($dob !== '' && strpos($dob, '/') !== false) {
    $parts = explode('/', $dob);
    if (count($parts) === 3) {
        $dob = $parts[2] . '-' . $parts[0] . '-' . $parts[1];
    }
}
$gender = trim($data['gender'] ?? '');
$address = trim($data['address'] ?? '');
$password = $data['password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';
$resultPin = trim($data['result_pin'] ?? '');

if (
    $fullName === '' ||
    $email === '' ||
    $mobile === '' ||
    $dob === '' ||
    $gender === '' ||
    $address === '' ||
    $password === '' ||
    $confirmPassword === '' ||
    $resultPin === ''
) {
    echo json_encode([
        'success' => false,
        'message' => 'Please complete all required fields.'
    ]);

    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address.'
    ]);

    exit;
}

if (strlen($password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters.'
    ]);

    exit;
}

if ($password !== $confirmPassword) {
    echo json_encode([
        'success' => false,
        'message' => 'Passwords do not match.'
    ]);

    exit;
}

if (!preg_match('/^\d{4}$/', $resultPin)) {
    echo json_encode([
        'success' => false,
        'message' => 'Result PIN must contain exactly 4 digits.'
    ]);

    exit;
}

$nameParts = preg_split('/\s+/', $fullName);

$firstName = $nameParts[0];

if (count($nameParts) > 1) {
    $lastName = implode(' ', array_slice($nameParts, 1));
} else {
    $lastName = '';
}

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

        echo json_encode([
            'success' => false,
            'message' => 'Email address is already registered.'
        ]);

        exit;
    }

    $pdo->beginTransaction();

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $resultPinHash = password_hash(
        $resultPin,
        PASSWORD_DEFAULT
    );

    $userStatement = $pdo->prepare(
        "INSERT INTO users
        (email, password_hash, role, status)
        VALUES
        (:email, :password_hash, 'Patient', 'Active')
        RETURNING user_id"
    );

    $userStatement->execute([
        ':email' => $email,
        ':password_hash' => $passwordHash
    ]);

    $user = $userStatement->fetch(PDO::FETCH_ASSOC);

    $patientStatement = $pdo->prepare(
        "INSERT INTO patients
        (
            user_id,
            first_name,
            last_name,
            gender,
            birth_date,
            result_pin_hash,
            mobile,
            address
        )
        VALUES
        (
            :user_id,
            :first_name,
            :last_name,
            :gender,
            :birth_date,
            :result_pin_hash,
            :mobile,
            :address
        )
        RETURNING patient_id"
    );

    $patientStatement->execute([
        ':user_id' => $user['user_id'],
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':gender' => $gender,
        ':birth_date' => $dob,
        ':result_pin_hash' => $resultPinHash,
        ':mobile' => $mobile,
        ':address' => $address
    ]);

    $patient = $patientStatement->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful.',
        'user' => [
            'id' => (int) $patient['patient_id'],
            'full_name' => $fullName,
            'email' => $email,
            'mobile' => $mobile,
            'dob' => $dob,
            'gender' => $gender,
            'result_pin' => ''
        ]
    ]);

} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
}