<?php

use App\Controllers\Api\AuthController;
use App\Controllers\Api\UserController;
use App\Middleware\JwtMiddleware;

return [
    'GET /api/health' => ['status' => 'online'],
    'POST /api/auth/register' => static fn (object $request): array => (new AuthController())->register(),
    'POST /api/auth/login' => static fn (object $request): array => (new AuthController())->login(),
    'GET /api/profile' => static function (object $request): array {
        return (new JwtMiddleware())->handle(
            $request,
            static fn (object $authenticatedRequest): array => (new UserController())->profile($authenticatedRequest)
        );
    },
];