<?php

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use PHPUnit\Framework\TestCase;

final class RouteDispatcherTest extends TestCase
{
    public function testHealthAndUnknownRoutes(): void
    {
        $dispatcher = $this->dispatcher();
        [$status, $handler, $variables] = $dispatcher->dispatch('GET', '/api/health');

        self::assertSame(Dispatcher::FOUND, $status);
        self::assertSame(['status' => 'online'], $handler($variables, (object) ['headers' => []]));
        self::assertSame(Dispatcher::NOT_FOUND, $dispatcher->dispatch('GET', '/api/unknown')[0]);
    }

    public function testMethodNotAllowedAndDynamicIds(): void
    {
        $dispatcher = $this->dispatcher();
        [$status, $allowedMethods] = $dispatcher->dispatch('POST', '/api/health');
        self::assertSame(Dispatcher::METHOD_NOT_ALLOWED, $status);
        self::assertContains('GET', $allowedMethods);

        [$status, , $variables] = $dispatcher->dispatch('GET', '/api/products/73');
        self::assertSame(Dispatcher::FOUND, $status);
        self::assertSame('73', $variables['id']);
    }

    public function testWriteRouteRejectsMissingBearerToken(): void
    {
        [$status, $handler, $variables] = $this->dispatcher()->dispatch('POST', '/api/products');
        self::assertSame(Dispatcher::FOUND, $status);

        $response = $handler($variables, (object) ['headers' => []]);
        self::assertSame(401, http_response_code());
        self::assertArrayHasKey('error', $response);
    }

    private function dispatcher(): Dispatcher
    {
        $routes = require dirname(__DIR__, 2) . '/src/Routes/api.php';

        return \FastRoute\simpleDispatcher(static function (RouteCollector $collector) use ($routes): void {
            foreach ($routes as [$method, $path, $handler]) {
                $collector->addRoute($method, $path, $handler);
            }
        });
    }
}