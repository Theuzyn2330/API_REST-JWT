<?php

namespace App\Middleware;

interface MiddlewareInterface
{
    public function handle(object $request, callable $next): mixed;
}
