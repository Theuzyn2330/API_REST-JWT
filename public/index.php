<?php

require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json; charset=UTF-8');

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($uri) && $uri !== '' ? '/' . trim($uri, '/') : '/';
$path = $path === '' ? '/' : $path;
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$routes = [
	'GET /api/health' => ['status' => 'online'],
];

$routeKey = $method . ' ' . $path;
$statusCode = 200;
$response = $routes[$routeKey] ?? null;

if ($response === null) {
	$statusCode = 404;
	$response = ['error' => 'Not Found'];

	foreach (array_keys($routes) as $registeredRoute) {
		[$registeredMethod, $registeredPath] = explode(' ', $registeredRoute, 2);

		if ($registeredPath === $path) {
			$statusCode = 405;
			header('Allow: ' . $registeredMethod);
			$response = ['error' => 'Method Not Allowed'];
			break;
		}
	}
}

http_response_code($statusCode);
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
