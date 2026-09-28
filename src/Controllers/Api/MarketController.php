<?php

namespace App\Controllers\Api;

use App\Services\MarketService;
use InvalidArgumentException;
use Throwable;

final class MarketController
{
	public function __construct(private ?MarketService $markets = null)
	{
	}

	public function productHistory(string $productId): array
	{
		if (ctype_digit($productId) !== true || (int) $productId < 1) {
			http_response_code(400);
			return ['error' => 'Product ID is invalid.'];
		}

		$location = $_GET['location'] ?? null;
		if ($location !== null && (!is_string($location) || strlen($location) > 191)) {
			http_response_code(400);
			return ['error' => 'Location filter is invalid.'];
		}
		$limit = filter_var($_GET['limit'] ?? 100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 500]]);
		if ($limit === false) {
			http_response_code(400);
			return ['error' => 'Limit must be an integer between 1 and 500.'];
		}

		try {
			return ['data' => $this->service()->getProductHistory((int) $productId, $location, $limit)];
		} catch (Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to load product history.'];
		}
	}

	public function calculateProductHistory(string $productId, ?array $payload = null): array
	{
		if (ctype_digit($productId) !== true || (int) $productId < 1) {
			http_response_code(400);
			return ['error' => 'Product ID is invalid.'];
		}
		if ($payload === null) {
			$body = file_get_contents('php://input');
			$decoded = is_string($body) ? json_decode($body, true) : null;
			$payload = is_array($decoded) && json_last_error() === JSON_ERROR_NONE ? $decoded : [];
		}

		$from = $payload['from'] ?? null;
		$to = $payload['to'] ?? null;
		if (($from !== null && !is_string($from)) || ($to !== null && !is_string($to))) {
			http_response_code(422);
			return ['error' => 'Date filters must be date-time strings.'];
		}

		try {
			$result = $this->service()->calculateAndStore((int) $productId, $from, $to);
		} catch (InvalidArgumentException $exception) {
			http_response_code(422);
			return ['error' => $exception->getMessage()];
		} catch (Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to calculate product history.'];
		}

		http_response_code(201);
		return $result;
	}

	private function service(): MarketService
	{
		return $this->markets ?? new MarketService();
	}
}