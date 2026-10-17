<?php
// Dynamically set BASE_URL based on the server hostname.
// On localhost (XAMPP), the app lives under /LabConnect-Web.
// On Render (or any remote server), the app is at the root.
if (!defined('BASE_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        define('BASE_URL', '/LabConnect-Web');
    } else {
        define('BASE_URL', '');
    }
}
