<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$appointmentId = $data['appointment_id'] ?? null;
$patientId     = $data['patient_id']     ?? null;

if (!$appointmentId || !$patientId) {
    http_response_code(400);
    echo json_encode(["error" => "Missing appointment_id or patient_id"]);
    exit;
}

try {
    // Verify the appointment belongs to this patient and is cancellable
    $check = $pdo->prepare("SELECT status, patient_id FROM appointments WHERE appointment_id=?");
    $check->execute([$appointmentId]);
    $appt = $check->fetch(PDO::FETCH_ASSOC);

    if (!$appt || (int)$appt['patient_id'] !== (int)$patientId) {
        http_response_code(403);
        echo json_encode(["error" => "Appointment not found"]);
        exit;
    }

    if (!in_array($appt['status'], ['Pending', 'Confirmed'])) {
        http_response_code(400);
        echo json_encode(["error" => "Only Pending or Confirmed appointments can be cancelled"]);
        exit;
    }

    // Cancel it
    $pdo->prepare("UPDATE appointments SET status='Cancelled' WHERE appointment_id=?")
        ->execute([$appointmentId]);

    // Insert notification for the patient
    $pdo->prepare("INSERT INTO notifications (patient_id, title, message, notification_type, is_read) VALUES (?,?,?,?,false)")
        ->execute([
            $patientId,
            'Appointment Cancelled',
            'You have successfully cancelled your appointment. You may book a new one anytime.',
            'appointment'
        ]);
    brevo_notify_patient($pdo, (int)$patientId, 'Appointment Cancelled', 'You have successfully cancelled your appointment. You may book a new one anytime.');

    echo json_encode(["success" => true, "message" => "Appointment cancelled successfully"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
