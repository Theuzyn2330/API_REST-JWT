<?php

namespace App\Middleware;

class JwtMiddleware implements MiddlewareInterface
{
    public function handle(object $request, callable $next): mixed
    {
        // TODO: validar JWT do usuário antes de prosseguir.
        // Exemplo:
        // $token = $request->headers['Authorization'] ?? null;
        // if (!$token || !$this->jwtService->validate($token)) {
        //     throw new \Exception('Token inválido ou ausente.');
        // }

        return $next($request);
    }
}
