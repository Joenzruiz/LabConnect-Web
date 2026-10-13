<?php
/**
 * email.php — Brevo (formerly Sendinblue) transactional email helper.
 * Clean, lightweight, professional email layout (zero image dependencies).
 */

// Load API key from git-ignored keys.php or environment
if (file_exists(__DIR__ . '/keys.php')) {
    require_once __DIR__ . '/keys.php';
}
if (!defined('BREVO_API_KEY')) {
    define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');
}
define('BREVO_SENDER_EMAIL', 'jogu.salibio.swu@phinmaed.com');
define('BREVO_SENDER_NAME',  'SLIMM Laboratory');
define('BREVO_API_URL',      'https://api.brevo.com/v3/smtp/email');

/**
 * Clean branded header without image dependencies.
 */
function get_email_header_html(string $subtitle = 'Clinical & Diagnostic Laboratory'): string {
    return "
    <div style='background:linear-gradient(135deg, #0F8B8D, #0d7375);padding:32px 24px;border-radius:12px 12px 0 0;text-align:center;'>
      <div style='display:inline-block;background:rgba(255,255,255,0.18);border:1px solid rgba(255,255,255,0.3);border-radius:24px;padding:6px 16px;margin-bottom:12px;'>
        <span style='color:#ffffff;font-size:11px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;'>SLIMM LABORATORY</span>
      </div>
      <h1 style='color:#ffffff;margin:0;font-size:24px;font-weight:700;letter-spacing:0.5px;'>SLIMM Laboratory, Inc.</h1>
      <p style='color:#e0f2f1;margin:6px 0 0;font-size:13px;letter-spacing:0.2px;'>{$subtitle}</p>
    </div>";
}

/**
 * Clean branded footer.
 */
function get_email_footer_html(): string {
    return "
      <hr style='border:none;border-top:1px solid #e5e7eb;margin:28px 0;'>
      <div style='text-align:center;'>
        <p style='color:#6b7280;font-size:12px;margin:0 0 4px;'><strong>SLIMM Laboratory, Inc.</strong> &bull; Baybay City, Leyte</p>
        <p style='color:#9ca3af;font-size:11px;margin:0;'>Service from the Heart &bull; Confidential Medical Notification</p>
      </div>";
}

/**
 * Core send function.
 */
function brevo_send(string $to_email, string $to_name, string $subject, string $html_body): bool {
    $payloadData = [
        'sender'      => ['name' => BREVO_SENDER_NAME, 'email' => BREVO_SENDER_EMAIL],
        'to'          => [['email' => $to_email, 'name'  => $to_name]],
        'subject'     => $subject,
        'htmlContent' => $html_body,
    ];

    $payload = json_encode($payloadData);

    $ch = curl_init(BREVO_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Accept: application/json',
            'api-key: ' . BREVO_API_KEY,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 201) {
        error_log("[Brevo] Failed ({$httpCode}): {$response}");
        return false;
    }
    return true;
}

// ── Named helpers ────────────────────────────────────────────────────────────

/**
 * Send OTP code email (for Forgot Password or Forgot PIN).
 */
function brevo_send_otp(string $to_email, string $to_name, string $otp_code, string $type = 'password'): bool {
    $action = $type === 'pin' ? 'reset your Result PIN' : 'reset your account password';
    $header = get_email_header_html('Security Verification');
    $footer = get_email_footer_html();

    $html = "
    <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:20px auto;background:#f8fafc;padding:12px;border-radius:16px;'>
      <div style='background:#ffffff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.06);overflow:hidden;border:1px solid #e2e8f0;'>
        {$header}
        <div style='padding:32px 28px;'>
          <p style='color:#1e293b;font-size:16px;margin:0 0 12px;'>Hello <strong>{$to_name}</strong>,</p>
          <p style='color:#64748b;font-size:14px;line-height:1.6;margin:0 0 24px;'>You requested to {$action}. Please enter this 6-digit verification code in the app to continue:</p>
          
          <div style='background:#f0fdfb;border:2px dashed #0F8B8D;border-radius:12px;padding:24px;text-align:center;margin:0 0 24px;'>
            <span style='font-size:42px;font-weight:800;letter-spacing:12px;color:#0F8B8D;display:inline-block;font-family:monospace;'>{$otp_code}</span>
          </div>
          
          <div style='background:#f1f5f9;border-radius:8px;padding:12px 16px;margin-bottom:20px;'>
            <p style='color:#475569;font-size:13px;margin:0;'>⏱ This code is valid for <strong>10 minutes</strong>. Do not share it with anyone.</p>
          </div>
          <p style='color:#94a3b8;font-size:12px;margin:0;'>If you did not make this request, you can safely ignore this email.</p>
          {$footer}
        </div>
      </div>
    </div>";

    return brevo_send($to_email, $to_name, 'Your SLIMM Lab Verification Code', $html);
}

/**
 * Send appointment booking / confirmation / cancellation / completion email.
 */
function brevo_send_appointment(string $to_email, string $to_name, string $test_name, string $appt_date, string $appt_time, string $status = 'Pending', string $doctor_name = '', string $doctor_spec = ''): bool {
    $header = get_email_header_html('Appointment Notification');
    $footer = get_email_footer_html();

    $statusColors = [
        'Confirmed' => ['bg' => '#ecfdf5', 'text' => '#065f46', 'border' => '#a7f3d0', 'badge' => '#059669', 'title' => 'Appointment Confirmed', 'desc' => 'Your appointment has been approved and confirmed by our medical team. Please arrive 15 minutes before your scheduled time.'],
        'Pending'   => ['bg' => '#fefce8', 'text' => '#713f12', 'border' => '#fef08a', 'badge' => '#d97706', 'title' => 'Appointment Pending',   'desc' => 'Your booking request has been received and is waiting for confirmation from our laboratory staff.'],
        'Cancelled' => ['bg' => '#fef2f2', 'text' => '#991b1b', 'border' => '#fecaca', 'badge' => '#dc2626', 'title' => 'Appointment Cancelled', 'desc' => 'Your appointment has been cancelled. You may schedule a new appointment anytime in the SLIMM Lab app.'],
        'Completed' => ['bg' => '#f0f9ff', 'text' => '#075985', 'border' => '#bae6fd', 'badge' => '#0284c7', 'title' => 'Appointment Completed', 'desc' => 'Your laboratory appointment has been completed. Your test results will be uploaded and available shortly.'],
    ];

    $cfg = $statusColors[$status] ?? $statusColors['Pending'];

    $doctorRows = '';
    if (!empty($doctor_name)) {
        $docLabel = htmlspecialchars($doctor_name);
        if (!empty($doctor_spec)) {
            $docLabel .= " <span style='font-size:12px;color:#64748b;font-weight:normal;'>(" . htmlspecialchars($doctor_spec) . ")</span>";
        }
        $doctorRows = "
        <tr>
          <td style='padding:12px 16px;color:#64748b;font-size:13px;border-bottom:1px solid #e2e8f0;'>Doctor / Specialist</td>
          <td style='padding:12px 16px;color:#1e293b;font-size:14px;font-weight:700;border-bottom:1px solid #e2e8f0;'>{$docLabel}</td>
        </tr>";
    }

    $html = "
    <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:20px auto;background:#f8fafc;padding:12px;border-radius:16px;'>
      <div style='background:#ffffff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.06);overflow:hidden;border:1px solid #e2e8f0;'>
        {$header}
        <div style='padding:32px 28px;'>
          <p style='color:#1e293b;font-size:16px;margin:0 0 16px;'>Hello <strong>{$to_name}</strong>,</p>

          <!-- Status Highlight Card -->
          <div style='background:{$cfg['bg']};border:1px solid {$cfg['border']};border-radius:12px;padding:20px;margin-bottom:24px;text-align:center;'>
            <span style='background:{$cfg['badge']};color:#ffffff;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:4px 12px;border-radius:12px;display:inline-block;margin-bottom:8px;'>STATUS</span>
            <div style='color:{$cfg['text']};font-size:20px;font-weight:800;margin-bottom:6px;'>{$cfg['title']}</div>
            <p style='color:{$cfg['text']};font-size:13px;line-height:1.5;margin:0;'>{$cfg['desc']}</p>
          </div>

          <!-- Appointment Details Table -->
          <table style='width:100%;border-collapse:collapse;background:#f8fafc;border-radius:10px;overflow:hidden;margin-bottom:24px;border:1px solid #e2e8f0;'>
            <tr>
              <td style='padding:12px 16px;color:#64748b;font-size:13px;border-bottom:1px solid #e2e8f0;'>Laboratory Test</td>
              <td style='padding:12px 16px;color:#1e293b;font-size:14px;font-weight:700;border-bottom:1px solid #e2e8f0;'>{$test_name}</td>
            </tr>
            {$doctorRows}
            <tr>
              <td style='padding:12px 16px;color:#64748b;font-size:13px;border-bottom:1px solid #e2e8f0;'>Appointment Date</td>
              <td style='padding:12px 16px;color:#1e293b;font-size:14px;font-weight:700;border-bottom:1px solid #e2e8f0;'>{$appt_date}</td>
            </tr>
            <tr>
              <td style='padding:12px 16px;color:#64748b;font-size:13px;'>Scheduled Time</td>
              <td style='padding:12px 16px;color:#1e293b;font-size:14px;font-weight:700;'>{$appt_time}</td>
            </tr>
          </table>

          <p style='color:#64748b;font-size:13px;line-height:1.5;margin:0;'>You can check your appointment status anytime directly in the <strong>SLIMM Lab</strong> mobile app.</p>
          {$footer}
        </div>
      </div>
    </div>";

    $subject = "Appointment {$status}: {$test_name} — SLIMM Lab";
    return brevo_send($to_email, $to_name, $subject, $html);
}

/**
 * Send lab result ready email without images.
 */
function brevo_send_result_ready(string $to_email, string $to_name, string $test_name): bool {
    $header = get_email_header_html('Laboratory Results');
    $footer = get_email_footer_html();

    $html = "
    <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:20px auto;background:#f8fafc;padding:12px;border-radius:16px;'>
      <div style='background:#ffffff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.06);overflow:hidden;border:1px solid #e2e8f0;'>
        {$header}
        <div style='padding:32px 28px;'>
          <p style='color:#1e293b;font-size:16px;margin:0 0 16px;'>Hello <strong>{$to_name}</strong>,</p>

          <div style='background:#ecfdf5;border-left:4px solid #059669;padding:16px 20px;border-radius:0 8px 8px 0;margin-bottom:24px;'>
            <div style='color:#065f46;font-size:16px;font-weight:700;margin-bottom:4px;'>Results Available</div>
            <p style='color:#065f46;font-size:13px;margin:0;line-height:1.4;'>Your official lab test result for <strong>{$test_name}</strong> is now ready to view.</p>
          </div>

          <p style='color:#475569;font-size:14px;font-weight:600;margin:0 0 8px;'>How to view and download your result:</p>
          <ol style='color:#64748b;font-size:13px;line-height:1.8;margin:0 0 24px;padding-left:20px;'>
            <li>Open the <strong>SLIMM Lab</strong> mobile app</li>
            <li>Tap on <strong>Lab Results</strong></li>
            <li>Enter your <strong>4-digit Result PIN</strong></li>
          </ol>

          <p style='color:#94a3b8;font-size:12px;margin:0;'>If you forgot your PIN, tap <em>\"Forgot PIN? Reset via email\"</em> in the app.</p>
          {$footer}
        </div>
      </div>
    </div>";

    return brevo_send($to_email, $to_name, "Your Lab Result is Ready ({$test_name}) — SLIMM Lab", $html);
}

/**
 * Send an email notification directly to a patient by patient_id using Brevo without images.
 */
function brevo_notify_patient(PDO $pdo, int $patient_id, string $title, string $message): bool {
    try {
        $stmt = $pdo->prepare("
            SELECT u.email, p.first_name, p.last_name
            FROM patients p
            JOIN users u ON p.user_id = u.user_id
            WHERE p.patient_id = ?
        ");
        $stmt->execute([$patient_id]);
        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$patient || empty($patient['email'])) {
            return false;
        }

        $to_name = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
        if (empty($to_name)) $to_name = 'Valued Patient';

        $header = get_email_header_html('Notification Update');
        $footer = get_email_footer_html();

        $html = "
        <div style='font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,Helvetica,Arial,sans-serif;max-width:520px;margin:20px auto;background:#f8fafc;padding:12px;border-radius:16px;'>
          <div style='background:#ffffff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.06);overflow:hidden;border:1px solid #e2e8f0;'>
            {$header}
            <div style='padding:32px 28px;'>
              <p style='color:#1e293b;font-size:16px;margin:0 0 16px;'>Hello <strong>{$to_name}</strong>,</p>
              
              <div style='background:#f0fdfb;border-left:4px solid #0F8B8D;padding:16px 20px;border-radius:0 8px 8px 0;margin-bottom:24px;'>
                <div style='color:#0F8B8D;font-size:16px;font-weight:700;margin-bottom:6px;'>{$title}</div>
                <p style='color:#334155;font-size:14px;margin:0;line-height:1.5;'>{$message}</p>
              </div>

              <p style='color:#64748b;font-size:13px;line-height:1.5;margin:0;'>Open the <strong>SLIMM Lab</strong> app anytime to view more information.</p>
              {$footer}
            </div>
          </div>
        </div>";

        return brevo_send($patient['email'], $to_name, "{$title} — SLIMM Lab", $html);
    } catch (Exception $e) {
        error_log("[Brevo Notify Error] " . $e->getMessage());
        return false;
    }
}
