<?php

namespace App\Middleware;

final class RoleMiddleware implements MiddlewareInterface
{
    private array $requiredRoles;

    public function __construct(array $requiredRoles = [])
    {
        $this->requiredRoles = $requiredRoles;
    }

    public function handle(object $request, callable $next): mixed
    {
        if ($this->requiredRoles === []) {
            return $next($request);
        }

        $claims = $request->user ?? null;
        $role = is_array($claims) ? ($claims['role'] ?? null) : null;
        if (!is_string($role) || !in_array($role, $this->requiredRoles, true)) {
            http_response_code(403);
            return ['error' => 'Insufficient permissions.'];
        }

        return $next($request);
    }
}
