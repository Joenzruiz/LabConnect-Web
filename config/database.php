<?php

$host = "localhost";
$port = "5432";
$dbname = "labconnect";
$username = "postgres";
$password = "root";

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET TIME ZONE 'Asia/Manila'");
    date_default_timezone_set('Asia/Manila');

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}