<?php

declare(strict_types=1);

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;

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

    public function generate(int $userId, ?string $role = null): string
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('User ID must be a positive integer.');
        }

        $issuedAt = time();
        $payload = [
            'sub' => (string) $userId,
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $issuedAt + $this->expirationSeconds,
        ];
        if ($role !== null && trim($role) !== '') {
            $payload['role'] = $role;
        }

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function validate(string $token): array
    {
        try {
            $claims = (array) JWT::decode($token, new Key($this->secretKey, 'HS256'));
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Invalid or expired access token.', 0, $exception);
        }

        $userId = filter_var(
            $claims['sub'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($userId === false || !isset($claims['exp']) || !is_int($claims['exp'])) {
            throw new InvalidArgumentException('Access token is missing required claims.');
        }

        return $claims;
    }
}
