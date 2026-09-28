<?php

namespace App\Controllers\Api;

use App\Models\Price;
use DateTimeImmutable;
use DateTimeZone;
use PDOException;
use Throwable;

final class PriceController
{
	public function __construct(private ?Price $prices = null)
	{
	}

	public function index(): array
	{
		$filters = [];
		foreach (['product_id', 'source_id'] as $field) {
			if (!isset($_GET[$field])) {
				continue;
			}
			$value = filter_var($_GET[$field], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
			if ($value === false) {
				http_response_code(400);
				return ['error' => sprintf('%s must be a positive integer.', $field)];
			}
			$filters[$field] = $value;
		}

		try {
			return ['data' => ($this->prices ?? new Price())->findAll($filters['product_id'] ?? null, $filters['source_id'] ?? null)];
		} catch (Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to load price records.'];
		}
	}

	public function show(string $id): array
	{
		if (ctype_digit($id) !== true || (int) $id < 1) {
			http_response_code(400);
			return ['error' => 'Price record ID is invalid.'];
		}

		try {
			$price = ($this->prices ?? new Price())->findById((int) $id);
		} catch (Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to load price record.'];
		}

		if ($price === null) {
			http_response_code(404);
			return ['error' => 'Price record not found.'];
		}

		return ['data' => $price];
	}

	public function create(?array $payload = null): array
	{
		$payload = $this->readPayload($payload);
		if ($payload === null) {
			http_response_code(400);
			return ['error' => 'Request body must be valid JSON.'];
		}

		$price = $this->validatePrice($payload);
		if (isset($price['error'])) {
			http_response_code(422);
			return ['error' => $price['error']];
		}

		try {
			$id = ($this->prices ?? new Price())->create($price);
		} catch (PDOException $exception) {
			if ($exception->getCode() === '23000') {
				http_response_code(422);
				return ['error' => 'Product or source does not exist.'];
			}
			http_response_code(500);
			return ['error' => 'Unable to create price record.'];
		} catch (Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to create price record.'];
		}

		http_response_code(201);
		return ['data' => ['id' => $id] + $price];
	}

	private function readPayload(?array $payload): ?array
	{
		if ($payload !== null) {
			return $payload;
		}
		$body = file_get_contents('php://input');
		$decoded = is_string($body) ? json_decode($body, true) : null;

		return is_array($decoded) && json_last_error() === JSON_ERROR_NONE ? $decoded : null;
	}

	private function validatePrice(array $payload): array
	{
		$productId = filter_var($payload['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		$sourceId = filter_var($payload['source_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		$amount = filter_var($payload['price'] ?? null, FILTER_VALIDATE_FLOAT);
		$unit = $payload['unit'] ?? null;
		$location = $payload['location'] ?? null;
		$collectedAt = $payload['collected_at'] ?? null;

		if ($productId === false || $sourceId === false) {
			return ['error' => 'Product and source IDs must be positive integers.'];
		}
		if ($amount === false || !is_finite((float) $amount) || $amount <= 0 || $amount > 9999999999.9999) {
			return ['error' => 'Price must be greater than zero and within the supported range.'];
		}
		if (!is_string($unit) || trim($unit) === '' || strlen(trim($unit)) > 50) {
			return ['error' => 'Price unit is required and must be at most 50 characters.'];
		}
		if ($location !== null && (!is_string($location) || strlen(trim($location)) > 191)) {
			return ['error' => 'Location must be at most 191 characters.'];
		}
		if ($location === '') {
			$location = null;
		}

		if ($collectedAt === null) {
			$collectedAt = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
		} elseif (is_string($collectedAt)) {
			$date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $collectedAt, new DateTimeZone('UTC'));
			$dateErrors = DateTimeImmutable::getLastErrors();
			if ($date === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
				return ['error' => 'collected_at must use YYYY-MM-DD HH:MM:SS.'];
			}
			$collectedAt = $date->format('Y-m-d H:i:s');
		} else {
			return ['error' => 'collected_at must be a date-time string.'];
		}

		return [
			'product_id' => $productId,
			'source_id' => $sourceId,
			'price' => number_format((float) $amount, 4, '.', ''),
			'unit' => trim($unit),
			'location' => $location === null ? null : trim($location),
			'collected_at' => $collectedAt,
		];
	}
}