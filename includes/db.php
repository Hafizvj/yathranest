<?php



function db(): PDO

{

    static $pdo = null;



    if ($pdo instanceof PDO) {

        try {

            $pdo->query('SELECT 1');

            return $pdo;

        } catch (PDOException $e) {

            $pdo = null;

        }

    }



    $pdo = db_connect();

    return $pdo;

}



function db_connect(): PDO

{

    $host = (string) config('db.host', '127.0.0.1');

    $port = (int) config('db.port', 3306);

    $name = (string) config('db.name', 'yathranest');

    $user = (string) config('db.user', 'root');

    $pass = (string) config('db.pass', '');

    $charset = (string) config('db.charset', 'utf8mb4');

    $timeout = max(5, (int) config('db.timeout', 15));



    // Prefer IPv4 for remote hosts — avoids broken IPv6 routes on Windows/XAMPP.

    $connectHost = $host;

    if ($host !== '127.0.0.1' && $host !== 'localhost' && filter_var($host, FILTER_VALIDATE_IP) === false) {

        $resolved = gethostbyname($host);

        if ($resolved !== $host && filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {

            $connectHost = $resolved;

        }

    }



    $dsn = "mysql:host={$connectHost};port={$port};dbname={$name};charset={$charset}";

    $options = [

        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        PDO::ATTR_EMULATE_PREPARES => false,

    ];

    if (defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')) {

        $options[PDO::MYSQL_ATTR_CONNECT_TIMEOUT] = $timeout;

    }

    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {

        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES {$charset}";

    }



    $attempts = 3;

    $last = null;

    for ($i = 1; $i <= $attempts; $i++) {

        try {

            return new PDO($dsn, $user, $pass, $options);

        } catch (PDOException $e) {

            $last = $e;

            if (!db_is_retryable_connect_error($e) || $i >= $attempts) {

                throw $e;

            }

            usleep(250000 * $i);

        }

    }



    throw $last ?? new PDOException('Database connection failed.');

}



function db_is_retryable_connect_error(PDOException $e): bool

{

    $code = (int) ($e->errorInfo[1] ?? 0);

    if (in_array($code, [2002, 2003, 2006, 2013], true)) {

        return true;

    }

    $msg = strtolower($e->getMessage());

    return str_contains($msg, 'gone away')

        || str_contains($msg, 'lost connection')

        || str_contains($msg, 'timed out')

        || str_contains($msg, 'connection refused');

}

