<?php
/**
 * emailjs_notify.php — Helper to trigger EmailJS notification emails from the server.
 * Include this file and call emailjs_send($templateId, $params) where needed.
 *
 * IMPORTANT: Fill in your EmailJS credentials below.
 */

define('EMAILJS_PUBLIC_KEY',   'YOUR_EMAILJS_PUBLIC_KEY');
define('EMAILJS_SERVICE_ID',   'YOUR_EMAILJS_SERVICE_ID');

/**
 * Send an email via the EmailJS REST API.
 * @param string $templateId   — EmailJS template ID (e.g. 'template_appt_booked')
 * @param array  $params       — Key/value pairs matching the template variables
 * @return bool                — true if sent successfully (HTTP 200), false otherwise
 */
function emailjs_send(string $templateId, array $params): bool {
    $payload = json_encode([
        'service_id'      => EMAILJS_SERVICE_ID,
        'template_id'     => $templateId,
        'user_id'         => EMAILJS_PUBLIC_KEY,
        'template_params' => $params,
    ]);

    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 200;
}
