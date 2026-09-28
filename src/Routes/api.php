<?php

use App\Controllers\Api\AuthController;
use App\Controllers\Api\CategoryController;
use App\Controllers\Api\UserController;
use App\Middleware\JwtMiddleware;

$authenticated = static function (object $request, callable $handler): array {
    return (new JwtMiddleware())->handle($request, $handler);
};

return [
    ['GET', '/api/health', static fn (array $vars, object $request): array => ['status' => 'online']],
    ['POST', '/api/auth/register', static fn (array $vars, object $request): array => (new AuthController())->register()],
    ['POST', '/api/auth/login', static fn (array $vars, object $request): array => (new AuthController())->login()],
    ['GET', '/api/profile', static function (array $vars, object $request): array {
        return (new JwtMiddleware())->handle(
            $request,
            static fn (object $authenticatedRequest): array => (new UserController())->profile($authenticatedRequest)
        );
    }],
    ['GET', '/api/categories', static fn (array $vars, object $request): array => (new CategoryController())->index()],
    ['GET', '/api/categories/{id:\\d+}', static fn (array $vars, object $request): array => (new CategoryController())->show($vars['id'])],
    ['POST', '/api/categories', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new CategoryController())->create())],
    ['PUT', '/api/categories/{id:\\d+}', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new CategoryController())->update($vars['id']))],
    ['DELETE', '/api/categories/{id:\\d+}', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new CategoryController())->delete($vars['id']))],
];