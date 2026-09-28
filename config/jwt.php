<?php

use Dotenv\Dotenv;

$projectRoot = dirname(__DIR__);
if (is_file($projectRoot . DIRECTORY_SEPARATOR . '.env')) {
    Dotenv::createImmutable($projectRoot)->safeLoad();
}

$secret = $_ENV['JWT_SECRET'] ?? $_SERVER['JWT_SECRET'] ?? getenv('JWT_SECRET');
if (
    !is_string($secret)
    || strlen($secret) < 32
    || preg_match('/\A(?:your_|change[-_]?me|replace[-_]?with)/i', $secret) === 1
) {
    throw new \RuntimeException('JWT_SECRET must be a non-placeholder value of at least 32 bytes.');
}

$expiration = filter_var(
    $_ENV['JWT_EXPIRATION'] ?? $_SERVER['JWT_EXPIRATION'] ?? getenv('JWT_EXPIRATION'),
    FILTER_VALIDATE_INT
);
if ($expiration === false || $expiration < 1) {
    throw new \RuntimeException('JWT_EXPIRATION must be a positive integer.');
}

return [
    'secret' => $secret,
    'expiration' => $expiration,
];