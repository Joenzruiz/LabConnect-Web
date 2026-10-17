<?php
// Dynamically set BASE_URL depending on the request path.
// When the application is served from a sub‑folder (e.g. /LabConnect-Web) we need that prefix.
// Otherwise, for root deployments (e.g. Render.com), use an empty string.
$script = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($script, '/LabConnect-Web/') !== false) {
    define('BASE_URL', '/LabConnect-Web');
} else {
    define('BASE_URL', '');
}
?>
