<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$projectRoot = dirname(__DIR__, 2);

require_once $projectRoot . '/vendor/autoload.php';

Dotenv::createImmutable($projectRoot)->safeLoad();

if (!function_exists('appEnv')) {
    function appEnv(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }
}
