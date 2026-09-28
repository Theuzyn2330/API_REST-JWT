<?php

namespace App\Controllers\Api;

use App\Models\Source;
use PDOException;
use Throwable;

final class SourceController
{
    private const TYPES = ['api', 'manual', 'automated_collection', 'external_database'];

    public function __construct(private ?Source $sources = null)
    {
    }

    public function index(): array
    {
        try {
            return ['data' => ($this->sources ?? new Source())->findAll()];
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to load sources.'];
        }
    }

    public function show(string $id): array
    {
        if (ctype_digit($id) !== true || (int) $id < 1) {
            http_response_code(400);
            return ['error' => 'Source ID is invalid.'];
        }

        try {
            $source = ($this->sources ?? new Source())->findById((int) $id);
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to load source.'];
        }

        if ($source === null) {
            http_response_code(404);
            return ['error' => 'Source not found.'];
        }

        return ['data' => $source];
    }

    public function create(?array $payload = null): array
    {
        $payload = $this->readPayload($payload);
        if ($payload === null) {
            http_response_code(400);
            return ['error' => 'Request body must be valid JSON.'];
        }
        $source = $this->validateSource($payload);
        if (isset($source['error'])) {
            http_response_code(422);
            return ['error' => $source['error']];
        }

        try {
            $id = ($this->sources ?? new Source())->create($source);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                http_response_code(409);
                return ['error' => 'Source name already exists.'];
            }
            http_response_code(500);
            return ['error' => 'Unable to create source.'];
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to create source.'];
        }

        http_response_code(201);
        return ['data' => ['id' => $id] + $source];
    }

    public function update(string $id, ?array $payload = null): array
    {
        if (ctype_digit($id) !== true || (int) $id < 1) {
            http_response_code(400);
            return ['error' => 'Source ID is invalid.'];
        }
        $payload = $this->readPayload($payload);
        if ($payload === null) {
            http_response_code(400);
            return ['error' => 'Request body must be valid JSON.'];
        }

        try {
            $model = $this->sources ?? new Source();
            $existing = $model->findById((int) $id);
            if ($existing === null) {
                http_response_code(404);
                return ['error' => 'Source not found.'];
            }
            $source = $this->validateSource($payload, $existing);
            if (isset($source['error'])) {
                http_response_code(422);
                return ['error' => $source['error']];
            }
            $model->update((int) $id, $source);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                http_response_code(409);
                return ['error' => 'Source name already exists.'];
            }
            http_response_code(500);
            return ['error' => 'Unable to update source.'];
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to update source.'];
        }

        return ['data' => ['id' => (int) $id] + $source];
    }

    public function deactivate(string $id): array
    {
        if (ctype_digit($id) !== true || (int) $id < 1) {
            http_response_code(400);
            return ['error' => 'Source ID is invalid.'];
        }

        try {
            if (!(($this->sources ?? new Source())->deactivate((int) $id))) {
                http_response_code(404);
                return ['error' => 'Source not found or already inactive.'];
            }
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to deactivate source.'];
        }

        return ['message' => 'Source deactivated successfully.'];
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

    private function validateSource(array $payload, ?array $existing = null): array
    {
        $name = $payload['name'] ?? $existing['name'] ?? null;
        $type = $payload['type'] ?? $existing['type'] ?? null;
        $url = $payload['url'] ?? $existing['url'] ?? null;
        $active = filter_var($payload['active'] ?? $existing['active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if (!is_string($name) || trim($name) === '' || strlen(trim($name)) > 191) {
            return ['error' => 'Source name is required and must be at most 191 characters.'];
        }
        if (!is_string($type) || !in_array($type, self::TYPES, true)) {
            return ['error' => 'Source type is invalid.'];
        }
        if ($url !== null && $url !== '') {
            $scheme = is_string($url) ? parse_url($url, PHP_URL_SCHEME) : null;
            if (!is_string($url) || strlen($url) > 2048 || filter_var($url, FILTER_VALIDATE_URL) === false || !in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
                return ['error' => 'Source URL must be a valid HTTP or HTTPS URL.'];
            }
        } else {
            $url = null;
        }
        if ($active === null) {
            return ['error' => 'Source active status must be true or false.'];
        }

        return ['name' => trim($name), 'type' => $type, 'url' => $url, 'active' => $active ? 1 : 0];
    }
}