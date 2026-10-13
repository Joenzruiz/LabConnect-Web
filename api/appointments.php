<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $patientId = $_GET['patient_id'] ?? null;
    if (!$patientId) {
        http_response_code(400);
        echo json_encode(["error" => "Missing patient_id"]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                a.appointment_id AS id,
                a.patient_id,
                ad.test_id,
                COALESCE(t.test_name, 'Unknown Test') AS test_name,
                to_char(a.appointment_date, 'MM/DD/YYYY') AS date,
                to_char(a.appointment_time, 'HH12:MI AM') AS time,
                a.status,
                CASE WHEN d.doctor_id IS NOT NULL THEN 'Dr. ' || d.first_name || ' ' || d.last_name ELSE 'SLIMM Laboratory' END AS doctor_name
            FROM appointments a
            LEFT JOIN appointment_details ad ON a.appointment_id = ad.appointment_id
            LEFT JOIN laboratory_tests t ON ad.test_id = t.test_id
            LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
            WHERE a.patient_id = :patient_id
            ORDER BY a.created_at DESC, a.appointment_id DESC
        ");
        $stmt->execute([':patient_id' => $patientId]);
        $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($appointments);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $patientId = $data['patient_id'] ?? null;
    $testId    = $data['test_id']    ?? null;
    $date      = $data['date']       ?? null;
    $time      = $data['time']       ?? null;
    $doctorId  = $data['doctor_id']  ?? null;

    if (!$patientId || !$testId || !$date || !$time) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields"]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Convert MM/DD/YYYY → YYYY-MM-DD
        if (strpos($date, '/') !== false) {
            $dateObj = DateTime::createFromFormat('m/d/Y', $date);
            if ($dateObj) $date = $dateObj->format('Y-m-d');
        }

        // Convert "08:00 AM" → "08:00:00"
        $timeObj = DateTime::createFromFormat('h:i A', trim($time));
        if ($timeObj) $time = $timeObj->format('H:i:s');

        // 1. Insert appointment
        $stmt = $pdo->prepare("
            INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status)
            VALUES (:patient_id, :doctor_id, :date, :time, 'Pending')
            RETURNING appointment_id
        ");
        $stmt->execute([':patient_id' => $patientId, ':doctor_id' => $doctorId, ':date' => $date, ':time' => $time]);
        $appointmentId = $stmt->fetchColumn();

        // 2. Get test info
        $priceStmt = $pdo->prepare("SELECT price, test_name FROM laboratory_tests WHERE test_id = :test_id");
        $priceStmt->execute([':test_id' => $testId]);
        $testData = $priceStmt->fetch(PDO::FETCH_ASSOC);
        $price    = $testData ? $testData['price']     : 0;
        $testName = $testData ? $testData['test_name'] : 'Unknown Test';

        // 3. Insert appointment_details
        $detailStmt = $pdo->prepare("
            INSERT INTO appointment_details (appointment_id, test_id, price_at_booking)
            VALUES (:appointment_id, :test_id, :price)
        ");
        $detailStmt->execute([
            ':appointment_id' => $appointmentId,
            ':test_id'        => $testId,
            ':price'          => $price
        ]);

        // 4. Insert notification into notifications table
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (patient_id, notification_type, title, message, is_read)
            VALUES (:patient_id, 'appointment', :title, :message, FALSE)
        ");
        $notifStmt->execute([
            ':patient_id' => $patientId,
            ':title'      => 'Appointment Booked',
            ':message'    => 'Your ' . $testName . ' appointment is scheduled on ' . $date . ' at ' . $time . '. Status: Pending.'
        ]);

        // 5. Insert audit log
        $auditStmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, details)
            VALUES (:user_id, 'BOOK_APPOINTMENT', :details)
        ");
        $auditStmt->execute([
            ':user_id'  => $patientId,
            ':details'  => 'Booked appointment #' . $appointmentId . ' for ' . $testName . ' on ' . $date
        ]);

        $pdo->commit();

        // Send booking confirmation email via Brevo (non-blocking — ignore failure)
        try {
            $patientInfo = $pdo->prepare("SELECT u.email, p.first_name||' '||p.last_name as full_name
                FROM patients p JOIN users u ON p.user_id=u.user_id WHERE p.patient_id=?");
            $patientInfo->execute([$patientId]);
            $pInfo = $patientInfo->fetch(PDO::FETCH_ASSOC);

            // Fetch doctor details if assigned
            $docName = '';
            $docSpec = '';
            if ($doctorId) {
                $docStmt = $pdo->prepare("SELECT first_name, last_name, specialization FROM doctors WHERE doctor_id=?");
                $docStmt->execute([$doctorId]);
                $docRow = $docStmt->fetch(PDO::FETCH_ASSOC);
                if ($docRow) {
                    $docName = 'Dr. ' . $docRow['first_name'] . ' ' . $docRow['last_name'];
                    $docSpec = $docRow['specialization'] ?? '';
                }
            }

            if ($pInfo && !empty($pInfo['email'])) {
                brevo_send_appointment(
                    $pInfo['email'],
                    $pInfo['full_name'] ?? 'Patient',
                    $testName,
                    $date,
                    $time,
                    'Pending',
                    $docName,
                    $docSpec
                );
            }
        } catch (Exception $ignored) {}

        echo json_encode([
            "id"         => (int)$appointmentId,
            "patient_id" => (int)$patientId,
            "test_id"    => (int)$testId,
            "test_name"  => $testName,
            "date"       => $date,
            "time"       => $time,
            "status"     => "Pending"
        ]);

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }

} elseif ($method === 'DELETE') {
    // Cancel appointment
    $data = json_decode(file_get_contents('php://input'), true);
    $appointmentId = $data['appointment_id'] ?? null;
    $patientId = $data['patient_id'] ?? null;

    if (!$appointmentId || !$patientId) {
        http_response_code(400);
        echo json_encode(["error" => "Missing appointment_id or patient_id"]);
        exit;
    }

    try {
        // Only allow cancellation if Pending
        $check = $pdo->prepare("SELECT status, patient_id FROM appointments WHERE appointment_id=?");
        $check->execute([$appointmentId]);
        $appt = $check->fetch(PDO::FETCH_ASSOC);

        if (!$appt || $appt['patient_id'] != $patientId) {
            http_response_code(403);
            echo json_encode(["error" => "Appointment not found"]);
            exit;
        }
        if (!in_array($appt['status'], ['Pending', 'Confirmed'])) {
            http_response_code(400);
            echo json_encode(["error" => "Only Pending or Confirmed appointments can be cancelled"]);
            exit;
        }

        $pdo->prepare("UPDATE appointments SET status='Cancelled' WHERE appointment_id=?")
            ->execute([$appointmentId]);

        // Notify patient
        $pdo->prepare("INSERT INTO notifications (patient_id,title,message,notification_type,is_read) VALUES (?,?,?,?,false)")
            ->execute([$patientId, 'Appointment Cancelled', 'You have cancelled your appointment. Book a new one anytime.', 'appointment']);
        brevo_notify_patient($pdo, (int)$patientId, 'Appointment Cancelled', 'You have cancelled your appointment. Book a new one anytime.');

        echo json_encode(["success" => true, "message" => "Appointment cancelled"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
}
