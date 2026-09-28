<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT title, description, to_char(created_at, 'FMDay, FMMonth DD, YYYY') as date FROM announcements WHERE is_active = TRUE ORDER BY created_at DESC");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($announcements);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
