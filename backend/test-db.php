<?php

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getPDO();

    $stmt = $pdo->query(
        'SELECT DATABASE() AS database_name, CURRENT_USER() AS db_user'
    );

    $result = $stmt->fetch();

    echo "PHP to MySQL connection successful!\n";
    echo "Database: " . $result['database_name'] . "\n";
    echo "User: " . $result['db_user'] . "\n";

} catch (Throwable $e) {
    fwrite(STDERR, "Database connection failed.\n");
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
