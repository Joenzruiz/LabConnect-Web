<?php
/**
 * verify_otp.php — Verify an OTP and optionally reset the password.
 * POST body (JSON):
 *   { "email": "...", "otp_code": "123456", "type": "password"|"pin", "new_password": "..." (only for password reset) }
 * Returns: { "success": true/false, "message": "..." }
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/database.php';

$data        = json_decode(file_get_contents('php://input'), true);
$email       = strtolower(trim($data['email']    ?? ''));
$otp_code    = trim($data['otp_code']            ?? '');
$type        = $data['type']                     ?? 'password';
$new_password= $data['new_password']             ?? '';

if (!$email || !$otp_code) {
    echo json_encode(['success' => false, 'message' => 'Email and OTP are required.']);
    exit;
}

// Check OTP
$stmt = $pdo->prepare("SELECT * FROM otp_codes WHERE email=? AND otp_code=? AND type=? AND used=FALSE AND expires_at > NOW()");
$stmt->execute([$email, $otp_code, $type]);
$otp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$otp) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP. Please try again.']);
    exit;
}

// Mark OTP as used
$pdo->prepare("UPDATE otp_codes SET used=TRUE WHERE otp_id=?")->execute([$otp['otp_id']]);

// If password reset, update the password
if ($type === 'password') {
    if (!$new_password || strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
        exit;
    }
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $updated = $pdo->prepare("UPDATE users SET password_hash=? WHERE LOWER(email)=?");
    $updated->execute([$hashed, $email]);
    if ($updated->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }
} elseif ($type === 'pin') {
    $new_pin = trim($data['new_pin'] ?? '');
    if (!empty($new_pin) && strlen($new_pin) === 4) {
        $pinHash = password_hash($new_pin, PASSWORD_DEFAULT);
        // Look up patient_id for this email
        $pdo->prepare("
            UPDATE patients
            SET result_pin_hash = ?
            WHERE user_id = (SELECT user_id FROM users WHERE LOWER(email) = ?)
        ")->execute([$pinHash, $email]);
    }
}

echo json_encode(['success' => true, 'message' => 'OTP verified successfully.']);
