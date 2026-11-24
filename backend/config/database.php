<?php

declare(strict_types=1);

require_once __DIR__ . '/env.php';

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $required = [
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
        'DB_PASSWORD',
    ];

    $config = [];

    foreach ($required as $key) {
        $value = appEnv($key);

        if ($value === null) {
            throw new RuntimeException(
                "Missing required environment variable: {$key}"
            );
        }

        $config[$key] = $value;
    }

    $port = appEnv('DB_PORT', '3306');

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $config['DB_HOST'],
        $port,
        $config['DB_DATABASE']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $sslCa = appEnv('DB_SSL_CA');

    if ($sslCa !== null) {
        if (!is_file($sslCa) || !is_readable($sslCa)) {
            throw new RuntimeException(
                'Database TLS CA certificate is unavailable.'
            );
        }

        if (
            !defined('PDO::MYSQL_ATTR_SSL_CA')
            || !defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')
        ) {
            throw new RuntimeException(
                'PDO MySQL SSL verification is not supported by this PHP installation.'
            );
        }

        $options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $pdo = new PDO(
        $dsn,
        $config['DB_USERNAME'],
        $config['DB_PASSWORD'],
        $options
    );

    return $pdo;
}
