<?php

use Dotenv\Dotenv;

$projectRoot = dirname(__DIR__);
if (is_file($projectRoot . DIRECTORY_SEPARATOR . '.env')) {
    Dotenv::createImmutable($projectRoot)->safeLoad();
}

$secret = getenv('JWT_SECRET');
if (!is_string($secret)) {
    $secret = $_SERVER['JWT_SECRET'] ?? $_ENV['JWT_SECRET'] ?? null;
}
if (
    !is_string($secret)
    || strlen($secret) < 32
    || preg_match('/\A(?:your_|change[-_]?me|replace[-_]?with)/i', $secret) === 1
) {
    throw new \RuntimeException('JWT_SECRET must be a non-placeholder value of at least 32 bytes.');
}

$expirationValue = getenv('JWT_EXPIRATION');
if (!is_string($expirationValue)) {
    $expirationValue = $_SERVER['JWT_EXPIRATION'] ?? $_ENV['JWT_EXPIRATION'] ?? null;
}
$expiration = filter_var($expirationValue, FILTER_VALIDATE_INT);
if ($expiration === false || $expiration < 1) {
    throw new \RuntimeException('JWT_EXPIRATION must be a positive integer.');
}

return [
    'secret' => $secret,
    'expiration' => $expiration,
];