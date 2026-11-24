<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';

$allowedOrigins = [
    'http://127.0.0.1:5500',
    'http://localhost:5500',
    'https://suramindhrasenareddy.github.io',
];

$extraOrigins = array_filter(
    array_map(
        static fn (string $origin): string => rtrim(trim($origin), '/'),
        explode(',', appEnv('CORS_ALLOWED_ORIGINS', '') ?? '')
    ),
    static fn (string $origin): bool => $origin !== ''
);

$allowedOrigins = array_values(array_unique(array_merge(
    $allowedOrigins,
    $extraOrigins
)));

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$originAllowed = in_array($origin, $allowedOrigins, true);

if ($originAllowed) {
    header("Access-Control-Allow-Origin: {$origin}");
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 600');
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Handle browser CORS preflight requests.
if ($method === 'OPTIONS') {
    if (!$originAllowed) {
        http_response_code(403);
        exit;
    }

    http_response_code(204);
    exit;
}

// Database health check.
if ($method === 'GET' && $path === '/api/health') {
    try {
        $pdo = getPDO();
        $pdo->query('SELECT 1');

        http_response_code(200);

        echo json_encode([
            'status' => 'success',
            'message' => 'Architecture and Interior Design API is running.',
            'database' => 'connected'
        ]);
    } catch (Throwable $e) {
        error_log($e->getMessage());
        http_response_code(503);

        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection unavailable.'
        ]);
    }

    exit;
}

// Firebase registration.
if ($method === 'POST' && $path === '/api/register') {
    require_once dirname(__DIR__) . '/routes/register.php';

    handleRegistrationRequest();
    exit;
}

http_response_code(404);
echo json_encode([
    'status' => 'error',
    'message' => 'API endpoint not found.'
]);
