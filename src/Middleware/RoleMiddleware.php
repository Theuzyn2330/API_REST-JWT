<?php

namespace App\Middleware;

class RoleMiddleware implements MiddlewareInterface
{
    private array $requiredRoles;

    public function __construct(array $requiredRoles = [])
    {
        $this->requiredRoles = $requiredRoles;
    }

    public function handle(object $request, callable $next): mixed
    {
        // TODO: verificar as permissões/roles do usuário autenticado.
        // Exemplo:
        // $user = $request->user ?? null;
        // if (!$user || !array_intersect($this->requiredRoles, $user->roles ?? [])) {
        //     throw new \Exception('Acesso negado.');
        // }

        return $next($request);
    }
}
