<?php

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = config('db.host', '127.0.0.1');
    $port = (int) config('db.port', 3306);
    $name = config('db.name', 'yathranest');
    $user = config('db.user', 'root');
    $pass = config('db.pass', '');
    $charset = config('db.charset', 'utf8mb4');

    // Prefer TCP when host is 127.0.0.1 / remote — avoids broken unix socket on shared hosts
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
