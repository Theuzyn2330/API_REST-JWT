<?php

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json; charset=UTF-8');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($uri) && $uri !== '' ? '/' . trim($uri, '/') : '/';
$path = $path === '' ? '/' : $path;
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$routes = require __DIR__ . '/../src/Routes/api.php';
$dispatcher = FastRoute\simpleDispatcher(static function (FastRoute\RouteCollector $collector) use ($routes): void {
	foreach ($routes as [$routeMethod, $routePath, $handler]) {
		$collector->addRoute($routeMethod, $routePath, $handler);
	}
});
$request = (object) [
	'method' => $method,
	'path' => $path,
	'headers' => [
		'Authorization' => $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '',
	],
];


http_response_code(200);
[$routeStatus, $routeHandler, $routeVariables] = $dispatcher->dispatch($method, $path);
if ($routeStatus === FastRoute\Dispatcher::NOT_FOUND) {
	http_response_code(404);
	$response = ['error' => 'Not Found'];
} elseif ($routeStatus === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
	http_response_code(405);
	header('Allow: ' . implode(', ', $routeHandler));
	$response = ['error' => 'Method Not Allowed'];
} else {
	$response = $routeHandler($routeVariables, $request);
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
