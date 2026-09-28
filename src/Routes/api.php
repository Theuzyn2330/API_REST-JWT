<?php

use App\Controllers\Api\AuthController;
use App\Controllers\Api\CategoryController;
use App\Controllers\Api\MarketController;
use App\Controllers\Api\ProductController;
use App\Controllers\Api\PriceController;
use App\Controllers\Api\SourceController;
use App\Controllers\Api\UserController;
use App\Middleware\JwtMiddleware;
use App\Middleware\RoleMiddleware;

$authenticated = static function (object $request, callable $handler, array $roles = ['admin', 'manager']): array {
    return (new JwtMiddleware())->handle(
        $request,
        static fn (object $authenticatedRequest): array => (new RoleMiddleware($roles))->handle(
            $authenticatedRequest,
            static fn (): array => $handler()
        )
    );
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
    ['GET', '/api/products', static fn (array $vars, object $request): array => (new ProductController())->index()],
    ['GET', '/api/products/{id:\\d+}', static fn (array $vars, object $request): array => (new ProductController())->show($vars['id'])],
    ['POST', '/api/products', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new ProductController())->create())],
    ['PUT', '/api/products/{id:\\d+}', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new ProductController())->update($vars['id']))],
    ['DELETE', '/api/products/{id:\\d+}', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new ProductController())->delete($vars['id']))],
    ['GET', '/api/sources', static fn (array $vars, object $request): array => (new SourceController())->index()],
    ['GET', '/api/sources/{id:\\d+}', static fn (array $vars, object $request): array => (new SourceController())->show($vars['id'])],
    ['POST', '/api/sources', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new SourceController())->create())],
    ['PUT', '/api/sources/{id:\\d+}', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new SourceController())->update($vars['id']))],
    ['DELETE', '/api/sources/{id:\\d+}', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new SourceController())->deactivate($vars['id']))],
    ['GET', '/api/prices', static fn (array $vars, object $request): array => (new PriceController())->index()],
    ['GET', '/api/prices/{id:\\d+}', static fn (array $vars, object $request): array => (new PriceController())->show($vars['id'])],
    ['POST', '/api/prices', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new PriceController())->create())],
    ['GET', '/api/products/{id:\\d+}/history', static fn (array $vars, object $request): array => (new MarketController())->productHistory($vars['id'])],
    ['POST', '/api/products/{id:\\d+}/history', static fn (array $vars, object $request): array => $authenticated($request, static fn (): array => (new MarketController())->calculateProductHistory($vars['id']))],
];