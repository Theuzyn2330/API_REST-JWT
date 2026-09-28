<?php

declare(strict_types=1);

namespace App\Services;

final class JwtService
{
    private readonly string $secretKey;
    private readonly int $expirationSeconds;

    public function __construct()
    {
        $configuration = require dirname(__DIR__, 2) . '/config/jwt.php';
        $this->secretKey = $configuration['secret'];
        $this->expirationSeconds = $configuration['expiration'];
    }

    public function getExpirationSeconds(): int
    {
        return $this->expirationSeconds;
    }
}
