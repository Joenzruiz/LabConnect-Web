<?php

$rawUrl = getenv('DATABASE_URL') ?: getenv('DB_URL') ?: getenv('POSTGRES_URL');

// Also check if DB_HOST contains a full connection string or psql command
if (!$rawUrl) {
    $dbHostEnv = getenv('DB_HOST');
    if ($dbHostEnv && (strpos($dbHostEnv, 'postgres://') !== false || strpos($dbHostEnv, 'postgresql://') !== false)) {
        $rawUrl = $dbHostEnv;
    }
}

if ($rawUrl) {
    // Extract postgresql:// or postgres:// URL even if wrapped in psql '...', quotes, or whitespace
    if (preg_match('/(postgres(?:ql)?:\/\/[^\s\'"]+)/i', $rawUrl, $matches)) {
        $cleanUrl = $matches[1];
    } else {
        $cleanUrl = trim($rawUrl, " \t\n\r\0\x0B'\"");
    }

    $parts = parse_url($cleanUrl);

    $host     = $parts['host'] ?? 'localhost';
    $port     = $parts['port'] ?? '5432';
    $dbname   = ltrim($parts['path'] ?? '/labconnect', '/');
    $username = isset($parts['user']) ? urldecode($parts['user']) : 'postgres';
    $password = isset($parts['pass']) ? urldecode($parts['pass']) : 'root';

    $sslmode = 'require';
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $queryParams);
        if (!empty($queryParams['sslmode'])) {
            $sslmode = $queryParams['sslmode'];
        }
    }
} else {
    $host     = trim(getenv('DB_HOST')     ?: "localhost", " \t\n\r\0\x0B'\"");
    $port     = trim(getenv('DB_PORT')     ?: "5432", " \t\n\r\0\x0B'\"");
    $dbname   = trim(getenv('DB_NAME')     ?: "labconnect", " \t\n\r\0\x0B'\"");
    $username = trim(getenv('DB_USER')     ?: "postgres", " \t\n\r\0\x0B'\"");
    $password = trim(getenv('DB_PASSWORD') ?: "root", " \t\n\r\0\x0B'\"");
    $sslmode  = trim(getenv('DB_SSLMODE')  ?: ($host === 'localhost' ? 'disable' : 'require'), " \t\n\r\0\x0B'\"");
}

// Strip any query string left in dbname
if (strpos($dbname, '?') !== false) {
    $dbname = explode('?', $dbname)[0];
}

$dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
if ($sslmode && $sslmode !== 'disable') {
    $dsn .= ";sslmode=$sslmode";
}

// Neon pooled connections require the endpoint option for SNI routing
if (strpos($host, 'neon.tech') !== false) {
    $endpoint = explode('.', $host)[0];
    $endpoint = str_replace('-pooler', '', $endpoint);
    $dsn .= ";options='endpoint=$endpoint'";
}

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 15,
    ]);
    $pdo->exec("SET TIME ZONE 'Asia/Manila'");
    date_default_timezone_set('Asia/Manila');

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}