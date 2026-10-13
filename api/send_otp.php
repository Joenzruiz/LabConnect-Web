<?php
/**
 * send_otp.php — Generate OTP, store it, send email via Brevo.
 * POST JSON: { "email": "user@example.com", "type": "password" | "pin" }
 * Returns:   { "success": true/false, "message": "..." }
 * NOTE: OTP code is NOT returned — it is sent directly to the user's email by the server.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once '../config/database.php';
require_once '../config/email.php';

$data  = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($data['email'] ?? ''));
$type  = $data['type'] ?? 'password'; // 'password' or 'pin'

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

// Look up user
$stmt = $pdo->prepare("
    SELECT u.user_id, u.email, p.first_name, p.last_name
    FROM users u
    LEFT JOIN patients p ON p.user_id = u.user_id
    WHERE LOWER(u.email) = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'No account found with that email address.']);
    exit;
}

// Generate 6-digit OTP
$otp        = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// Ensure otp_codes table exists
$pdo->exec("CREATE TABLE IF NOT EXISTS otp_codes (
    otp_id     SERIAL PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    otp_code   VARCHAR(10)  NOT NULL,
    type       VARCHAR(20)  NOT NULL DEFAULT 'password',
    expires_at TIMESTAMP    NOT NULL,
    used       BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
)");

// Delete old OTPs for this email/type and insert new one
$pdo->prepare("DELETE FROM otp_codes WHERE email=? AND type=?")->execute([$email, $type]);
$pdo->prepare("INSERT INTO otp_codes (email, otp_code, type, expires_at) VALUES (?,?,?, NOW() + INTERVAL '10 minutes')")
    ->execute([$email, $otp, $type]);

$to_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if (!$to_name) $to_name = 'User';

// Send email via Brevo
$sent = brevo_send_otp($email, $to_name, $otp, $type);

if (!$sent) {
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Verification code sent to ' . $email . '. Check your inbox.',
]);
