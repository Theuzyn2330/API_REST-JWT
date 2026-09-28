<?php

require_once __DIR__ . '/../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

ini_set('display_errors', '0');
ini_set('log_errors', '1');

$debugSetting = getenv('APP_DEBUG');
if (!is_string($debugSetting)) {
	$debugSetting = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? 'false';
}
$debug = filter_var($debugSetting, FILTER_VALIDATE_BOOLEAN);

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
	if ((error_reporting() & $severity) === 0) {
		return false;
	}

	throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $exception) use ($debug): void {
	error_log(sprintf('%s in %s:%d', $exception->getMessage(), $exception->getFile(), $exception->getLine()));
	if (!headers_sent()) {
		header('Content-Type: application/json; charset=UTF-8');
	}
	http_response_code(500);
	$response = ['error' => 'Internal Server Error'];
	if ($debug) {
		$response['details'] = $exception->getMessage();
	}
	echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
});

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');

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
$routeResult = $dispatcher->dispatch($method, $path);
$routeStatus = $routeResult[0];
if ($routeStatus === FastRoute\Dispatcher::NOT_FOUND) {
	http_response_code(404);
	$response = ['error' => 'Not Found'];
} elseif ($routeStatus === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
	http_response_code(405);
	header('Allow: ' . implode(', ', $routeResult[1]));
	$response = ['error' => 'Method Not Allowed'];
} else {
	$routeHandler = $routeResult[1];
	$routeVariables = $routeResult[2];
	$response = $routeHandler($routeVariables, $request);
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
