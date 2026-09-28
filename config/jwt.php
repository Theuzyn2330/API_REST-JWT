<?php

use Dotenv\Dotenv;

$projectRoot = dirname(__DIR__);
$dotenvValues = [];
if (is_file($projectRoot . DIRECTORY_SEPARATOR . '.env')) {
    Dotenv::createImmutable($projectRoot)->safeLoad();
    $dotenvContent = file_get_contents($projectRoot . DIRECTORY_SEPARATOR . '.env');
    if (is_string($dotenvContent)) {
        $dotenvValues = Dotenv::parse($dotenvContent);
    }
}

$secret = null;
foreach ([getenv('JWT_SECRET'), $_ENV['JWT_SECRET'] ?? null, $_SERVER['JWT_SECRET'] ?? null, $dotenvValues['JWT_SECRET'] ?? null] as $candidate) {
    if (
        is_string($candidate)
        && strlen($candidate) >= 32
        && preg_match('/\A(?:your_|change[-_]?me|replace[-_]?with)/i', $candidate) !== 1
    ) {
        $secret = $candidate;
        break;
    }
}
if ($secret === null) {
    throw new \RuntimeException('JWT_SECRET must be a non-placeholder value of at least 32 bytes.');
}

$expiration = null;
foreach ([getenv('JWT_EXPIRATION'), $_ENV['JWT_EXPIRATION'] ?? null, $_SERVER['JWT_EXPIRATION'] ?? null, $dotenvValues['JWT_EXPIRATION'] ?? null] as $candidate) {
    $parsedExpiration = filter_var($candidate, FILTER_VALIDATE_INT);
    if ($parsedExpiration !== false && $parsedExpiration > 0) {
        $expiration = $parsedExpiration;
        break;
    }
}
if ($expiration === null) {
    throw new \RuntimeException('JWT_EXPIRATION must be a positive integer.');
}

return [
    'secret' => $secret,
    'expiration' => $expiration,
];