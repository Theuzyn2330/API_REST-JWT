<?php

use App\Controllers\Api\AuthController;

return [
    'GET /api/health' => ['status' => 'online'],
    'POST /api/auth/register' => static fn (): array => (new AuthController())->register(),
    'POST /api/auth/login' => static fn (): array => (new AuthController())->login(),
];