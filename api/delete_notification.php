<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$notificationId = intval($data['notification_id'] ?? 0);
$patientId      = intval($data['patient_id'] ?? 0);

if ($notificationId <= 0 || $patientId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

try {
    // Only allow deleting own notifications
    $stmt = $pdo->prepare(
        "DELETE FROM notifications WHERE notification_id = :nid AND patient_id = :pid"
    );
    $stmt->execute([':nid' => $notificationId, ':pid' => $patientId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Notification deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
