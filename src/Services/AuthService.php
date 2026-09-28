<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final class AuthService
{
    public function __construct(private ?User $users = null)
    {
    }

    public function login(array $credentials): ?array
    {
        if (!$this->validateCredentials($credentials)) {
            return null;
        }

        $email = strtolower(trim($credentials['email']));
        $user = ($this->users ?? new User())->findByEmail($email);
        $passwordHash = $user['password'] ?? null;
        if (!is_string($passwordHash) || !password_verify($credentials['password'], $passwordHash)) {
            return null;
        }

        unset($user['password']);

        return $user;
    }

    public function validateCredentials(array $credentials): bool
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;

        return is_string($email)
            && filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false
            && is_string($password)
            && $password !== '';
    }
}
