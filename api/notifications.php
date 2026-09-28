<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch notifications
    $patientId = $_GET['patient_id'] ?? null;
    if (!$patientId) {
        http_response_code(400);
        echo json_encode(["error" => "Missing patient_id"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                n.notification_id AS id,
                n.title,
                n.message,
                n.notification_type AS type,
                n.is_read,
                to_char(n.created_at, 'FMMonth DD, YYYY') AS date
            FROM notifications n
            WHERE n.patient_id = :patient_id
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([':patient_id' => $patientId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($notifications as &$n) {
            $n['is_read'] = (bool) $n['is_read'];
        }

        echo json_encode($notifications);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark all notifications as read for this patient
    $data = json_decode(file_get_contents('php://input'), true);
    $patientId = $data['patient_id'] ?? null;

    if (!$patientId) {
        http_response_code(400);
        echo json_encode(["error" => "Missing patient_id"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE patient_id = :patient_id AND is_read = FALSE");
        $stmt->execute([':patient_id' => $patientId]);
        echo json_encode(["success" => true, "updated" => $stmt->rowCount()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
