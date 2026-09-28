<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Fallback to $_POST if not JSON
$email = strtolower(trim($data['email'] ?? $_POST['email'] ?? ''));
$password = $data['password'] ?? $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo json_encode(["success" => false, "message" => "Please enter your email and password."]);
    exit;
}

try {
    $sql = "SELECT user_id, email, password_hash, role, status
            FROM users
            WHERE email = :email
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "Email not found."]);
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(["success" => false, "message" => "Incorrect password."]);
        exit;
    }

    if ($user['status'] !== 'Active') {
        echo json_encode(["success" => false, "message" => "This account is disabled."]);
        exit;
    }

    // Only allow patients (and admins for testing if needed)
    if ($user['role'] !== 'Patient') {
        echo json_encode(["success" => false, "message" => "This app is for Patients only."]);
        exit;
    }

    // Fetch patient details
    $patientStmt = $pdo->prepare("SELECT patient_id, first_name, last_name, mobile, gender, birth_date, address FROM patients WHERE user_id = :user_id");
    $patientStmt->execute([':user_id' => $user['user_id']]);
    $patient = $patientStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "user" => [
            "id" => $patient ? $patient['patient_id'] : $user['user_id'],
            "full_name" => $patient ? $patient['first_name'] . ' ' . $patient['last_name'] : "Patient",
            "email" => $user['email'],
            "mobile" => $patient ? $patient['mobile'] : "",
            "dob" => $patient ? $patient['birth_date'] : "",
            "gender" => $patient ? $patient['gender'] : "",
            "address" => $patient ? $patient['address'] : ""
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
