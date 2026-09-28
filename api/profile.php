<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$patientId = $_GET['patient_id'] ?? null;
if (!$patientId) {
    http_response_code(400);
    echo json_encode(["error" => "Missing patient_id"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            p.patient_id AS id,
            p.first_name || ' ' || p.last_name AS full_name,
            u.email,
            p.mobile,
            to_char(p.birth_date, 'MM/DD/YYYY') AS dob,
            p.gender,
            p.address
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.patient_id = :patient_id
        LIMIT 1
    ");
    $stmt->execute([':patient_id' => $patientId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        http_response_code(404);
        echo json_encode(["error" => "Patient not found"]);
        exit;
    }

    echo json_encode($profile);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
