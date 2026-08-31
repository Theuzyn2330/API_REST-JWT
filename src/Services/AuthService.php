<?php

declare(strict_types=1);

namespace App\Services;

final class AuthService
{
    public function __construct()
    {
    }

    public function login(array $credentials): array
    {
        return [
            'message' => 'Login not implemented yet.',
            'credentials' => $credentials,
        ];
    }

    public function validateCredentials(array $credentials): bool
    {
        return !empty($credentials);
    }
}
