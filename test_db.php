<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/config/database.php';
echo "SUCCESS: Database connected to Neon!\n";
$users = $pdo->query("SELECT user_id, email, role, status FROM users")->fetchAll(PDO::FETCH_ASSOC);
echo "Registered Users:\n";
foreach ($users as $u) {
    echo "- [{$u['role']}] {$u['email']} ({$u['status']})\n";
}
?>