<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT doctor_id::text AS id, 'Dr. ' || first_name || ' ' || last_name AS name, specialization AS specialty, substring(first_name from 1 for 1) || substring(last_name from 1 for 1) AS initials FROM doctors");
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($doctors);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
