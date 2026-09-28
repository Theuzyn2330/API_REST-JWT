<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

$projectRoot = dirname(__DIR__, 2);
if (is_file($projectRoot . DIRECTORY_SEPARATOR . '.env')) {
    Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
}

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/admin/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

function adminCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function adminApiRequest(string $path, string $method = 'GET', ?array $payload = null, ?string $token = null): array
{
    $baseUrl = getenv('APP_URL');
    if (!is_string($baseUrl) || trim($baseUrl) === '') {
        $baseUrl = $_SERVER['APP_URL'] ?? $_ENV['APP_URL'] ?? '';
    }
    if (!is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
        return ['status' => 0, 'data' => []];
    }

    $headers = ['Accept: application/json'];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
    ];
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_SLASHES);
    }
    if ($token !== null) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    $options[CURLOPT_HTTPHEADER] = $headers;

    $handle = curl_init(rtrim($baseUrl, '/') . $path);
    if ($handle === false) {
        return ['status' => 0, 'data' => []];
    }

    curl_setopt_array($handle, $options);
    $responseBody = curl_exec($handle);
    $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    curl_close($handle);

    $data = is_string($responseBody) ? json_decode($responseBody, true) : null;

    return [
        'status' => $statusCode,
        'data' => is_array($data) ? $data : [],
    ];
}

function adminRequireAuthentication(): void
{
    $token = $_SESSION['access_token'] ?? null;
    $expiresAt = $_SESSION['token_expires_at'] ?? 0;
    if (!is_string($token) || $token === '' || !is_int($expiresAt) || $expiresAt <= time()) {
        adminDestroySession();
        header('Location: /admin/');
        exit;
    }
}

function adminDestroySession(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}