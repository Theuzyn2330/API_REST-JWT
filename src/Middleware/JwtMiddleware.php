<?php

namespace App\Middleware;

use App\Services\JwtService;

class JwtMiddleware implements MiddlewareInterface
{
    public function __construct(private ?JwtService $jwtService = null)
    {
    }

    public function handle(object $request, callable $next): mixed
    {
        $headers = $request->headers ?? [];
        $authorization = is_array($headers)
            ? ($headers['Authorization'] ?? $headers['authorization'] ?? null)
            : null;
        if (!is_string($authorization) || preg_match('/\ABearer\s+(\S+)\z/i', trim($authorization), $matches) !== 1) {
            http_response_code(401);
            return ['error' => 'Authentication token is required.'];
        }

        try {
            $claims = ($this->jwtService ?? new JwtService())->validate($matches[1]);
        } catch (\Throwable $exception) {
            http_response_code(401);
            return ['error' => 'Invalid or expired access token.'];
        }

        $request->user = $claims;

        return $next($request);
    }
}
