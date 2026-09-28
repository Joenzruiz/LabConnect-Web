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
    $stmt = $pdo->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE patient_id = :patient_id AND is_read = FALSE");
    $stmt->execute([':patient_id' => $patientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(["unread_count" => (int) $row['unread_count']]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
