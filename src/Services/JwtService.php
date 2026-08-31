<?php

declare(strict_types=1);

namespace App\Services;

final class JwtService
{
    public function __construct(
        private readonly string $secretKey = 'change_me'
    ) {
    }

    public function generate(array $payload): string
    {
        return 'jwt.token.not.implemented';
    }

    public function validate(string $token): array
    {
        return [
            'valid' => false,
            'message' => 'JWT validation not implemented yet.',
            'token' => $token,
        ];
    }
}
