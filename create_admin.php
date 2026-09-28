<?php

require_once __DIR__ . "/config/database.php";

$email = "admin@slimmlaboratory.com";
$password = "Admin123!";
$role = "Administrator";

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (email, password_hash, role)
        VALUES (:email, :password_hash, :role)
        ON CONFLICT (email) DO NOTHING";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ":email" => $email,
    ":password_hash" => $passwordHash,
    ":role" => $role
]);

echo "Administrator account created successfully.";