<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenvPath = dirname(__DIR__) . '/.env';
if (file_exists($dotenvPath)) {
    $env = parse_ini_file($dotenvPath, true, INI_SCANNER_TYPED);
    if (is_array($env)) {
        foreach ($env as $key => $value) {
            $_ENV[$key] = $value;
        }
    }
}

header('Content-Type: application/json; charset=utf-8');

$route = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if ($route === '/health') {
    echo json_encode([
        'status' => 'ok',
        'service' => $_ENV['APP_NAME'] ?? 'economy-api',
        'timestamp' => date(DATE_ATOM),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

echo json_encode([
    'message' => 'Economy API is running',
    'version' => '0.1.0',
    'route' => $route,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
