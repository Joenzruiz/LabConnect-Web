<?php

$dbUrl = getenv('DATABASE_URL');
if ($dbUrl) {
    $parts = parse_url($dbUrl);
    $host     = $parts['host'] ?? 'localhost';
    $port     = $parts['port'] ?? '5432';
    $dbname   = ltrim($parts['path'] ?? '/labconnect', '/');
    $username = $parts['user'] ?? 'postgres';
    $password = $parts['pass'] ?? 'root';
    $sslmode  = 'require';
} else {
    $host     = getenv('DB_HOST')     ?: "localhost";
    $port     = getenv('DB_PORT')     ?: "5432";
    $dbname   = getenv('DB_NAME')     ?: "labconnect";
    $username = getenv('DB_USER')     ?: "postgres";
    $password = getenv('DB_PASSWORD') ?: "root";
    $sslmode  = getenv('DB_SSLMODE')  ?: ($host === 'localhost' ? 'disable' : 'require');
}

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
if ($sslmode && $sslmode !== 'disable') {
    $dsn .= ";sslmode=$sslmode";
}

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET TIME ZONE 'Asia/Manila'");
    date_default_timezone_set('Asia/Manila');

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}