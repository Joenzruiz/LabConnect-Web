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
    // Fetch results joined with appointments to get date, and appointment details to get test names
    $stmt = $pdo->prepare("
        SELECT r.lab_result_id AS id, 
               r.appointment_id, 
               a.appointment_date AS date,
               (SELECT string_agg(lt.test_name, ', ') 
                FROM appointment_details ad 
                JOIN laboratory_tests lt ON ad.test_id = lt.test_id 
                WHERE ad.appointment_id = a.appointment_id) AS test_name,
               r.file_path AS pdf_url, 
               'Released' AS status,
               COALESCE('Dr. ' || d.first_name || ' ' || d.last_name, (
                   SELECT 'Dr. ' || first_name || ' ' || last_name FROM doctors ORDER BY doctor_id ASC LIMIT 1
               ), 'SLIMM Laboratory') AS doctor_name
        FROM lab_results r
        JOIN appointments a ON r.appointment_id = a.appointment_id
        LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
        WHERE a.patient_id = :patient_id
        ORDER BY a.appointment_date DESC, r.created_at DESC
    ");
    $stmt->execute([':patient_id' => $patientId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
