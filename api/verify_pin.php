<?php
/**
 * verify_pin.php — Secure server-side PIN verification and update endpoint
 *
 * Actions:
 *   1. "verify" (default):
 *      POST JSON: { "patient_id": 12, "pin": "1234" }
 *      Returns:   { "success": true/false, "message": "..." }
 *
 *   2. "update":
 *      POST JSON: { "patient_id": 12, "current_pin": "1234", "new_pin": "5678" }
 *      Returns:   { "success": true/false, "message": "..." }
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$patient_id  = (int)($data['patient_id']  ?? 0);
$pin         = trim($data['pin']          ?? '');
$current_pin = trim($data['current_pin']  ?? '');
$new_pin     = trim($data['new_pin']      ?? '');
$action      = trim($data['action']       ?? 'verify');

if (!$patient_id) {
    echo json_encode(['success' => false, 'message' => 'Missing patient ID.']);
    exit;
}

try {
    // Fetch stored pin hash for this patient
    $stmt = $pdo->prepare("SELECT patient_id, result_pin_hash FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        echo json_encode(['success' => false, 'message' => 'Patient record not found.']);
        exit;
    }

    $hash = $patient['result_pin_hash'];

    if ($action === 'update') {
        if (strlen($new_pin) !== 4) {
            echo json_encode(['success' => false, 'message' => 'New PIN must be exactly 4 digits.']);
            exit;
        }

        // Verify current PIN
        $isMatch = false;
        if (!empty($hash)) {
            $isMatch = password_verify($current_pin, $hash);
        } else {
            // Default pin fallback if not yet set
            $isMatch = ($current_pin === '1234');
        }

        if (!$isMatch) {
            echo json_encode(['success' => false, 'message' => 'Incorrect current PIN.']);
            exit;
        }

        // Hash and save new PIN
        $newHash = password_hash($new_pin, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE patients SET result_pin_hash = ? WHERE patient_id = ?");
        $upd->execute([$newHash, $patient_id]);

        echo json_encode(['success' => true, 'message' => 'PIN updated successfully.']);
        exit;

    } else {
        // Standard verify action
        if (strlen($pin) !== 4) {
            echo json_encode(['success' => false, 'message' => 'PIN must be 4 digits.']);
            exit;
        }

        $isMatch = false;
        if (!empty($hash)) {
            $isMatch = password_verify($pin, $hash);
        } else {
            // Fallback default PIN is 1234
            $isMatch = ($pin === '1234');
        }

        if ($isMatch) {
            echo json_encode(['success' => true, 'message' => 'PIN verified.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect Result PIN.']);
        }
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
