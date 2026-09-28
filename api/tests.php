<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("SELECT test_id AS id, test_name AS name, price, preparation_instructions AS instructions FROM laboratory_tests WHERE is_active = TRUE ORDER BY test_name ASC");
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($tests);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
