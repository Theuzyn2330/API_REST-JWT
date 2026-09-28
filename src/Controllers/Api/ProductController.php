<?php

namespace App\Controllers\Api;

use App\Models\Product;
use PDOException;
use Throwable;

final class ProductController
{
    public function __construct(private ?Product $products = null)
    {
    }

    public function index(): array
    {
        $categoryId = null;
        if (isset($_GET['category_id'])) {
            $categoryId = filter_var($_GET['category_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($categoryId === false) {
                http_response_code(400);
                return ['error' => 'Category ID is invalid.'];
            }
        }

        $active = null;
        if (isset($_GET['active'])) {
            $active = filter_var($_GET['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($active === null) {
                http_response_code(400);
                return ['error' => 'Active filter must be true or false.'];
            }
        }

        try {
            return ['data' => ($this->products ?? new Product())->findAll($categoryId, $active)];
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to load products.'];
        }
    }

    public function show(string $id): array
    {
        if (ctype_digit($id) !== true || (int) $id < 1) {
            http_response_code(400);
            return ['error' => 'Product ID is invalid.'];
        }

        try {
            $product = ($this->products ?? new Product())->findById((int) $id);
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to load product.'];
        }

        if ($product === null) {
            http_response_code(404);
            return ['error' => 'Product not found.'];
        }

        return ['data' => $product];
    }

    public function create(?array $payload = null): array
    {
        $payload = $this->readPayload($payload);
        if ($payload === null) {
            http_response_code(400);
            return ['error' => 'Request body must be valid JSON.'];
        }

        $product = $this->validateProduct($payload);
        if (isset($product['error'])) {
            http_response_code(422);
            return ['error' => $product['error']];
        }

        try {
            $id = ($this->products ?? new Product())->create($product);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                http_response_code(409);
                return ['error' => 'Product slug or category relationship conflicts with existing data.'];
            }

            http_response_code(500);
            return ['error' => 'Unable to create product.'];
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to create product.'];
        }

        http_response_code(201);
        return ['data' => ['id' => $id] + $product];
    }

    public function update(string $id, ?array $payload = null): array
    {
        if (ctype_digit($id) !== true || (int) $id < 1) {
            http_response_code(400);
            return ['error' => 'Product ID is invalid.'];
        }

        $payload = $this->readPayload($payload);
        if ($payload === null) {
            http_response_code(400);
            return ['error' => 'Request body must be valid JSON.'];
        }

        try {
            $model = $this->products ?? new Product();
            $existing = $model->findById((int) $id);
            if ($existing === null) {
                http_response_code(404);
                return ['error' => 'Product not found.'];
            }

            $product = $this->validateProduct($payload, $existing);
            if (isset($product['error'])) {
                http_response_code(422);
                return ['error' => $product['error']];
            }

            $model->update((int) $id, $product);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                http_response_code(409);
                return ['error' => 'Product slug or category relationship conflicts with existing data.'];
            }

            http_response_code(500);
            return ['error' => 'Unable to update product.'];
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to update product.'];
        }

        return ['data' => ['id' => (int) $id] + $product];
    }

    public function delete(string $id): array
    {
        if (ctype_digit($id) !== true || (int) $id < 1) {
            http_response_code(400);
            return ['error' => 'Product ID is invalid.'];
        }

        try {
            $model = $this->products ?? new Product();
            $product = $model->findById((int) $id);
            if ($product === null || !(bool) $product['active']) {
                http_response_code(404);
                return ['error' => 'Active product not found.'];
            }

            if (!$model->deactivate((int) $id)) {
                http_response_code(500);
                return ['error' => 'Unable to deactivate product.'];
            }
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to deactivate product.'];
        }

        return ['message' => 'Product deactivated successfully.'];
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

    private function validateProduct(array $payload, ?array $existing = null): array
    {
        $categoryId = filter_var(
            $payload['category_id'] ?? $existing['category_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $name = $payload['name'] ?? $existing['name'] ?? null;
        $unit = $payload['unit'] ?? $existing['unit'] ?? null;
        $description = $payload['description'] ?? $existing['description'] ?? null;
        $slug = $payload['slug'] ?? null;

        if ($categoryId === false || !is_string($name) || trim($name) === '' || strlen(trim($name)) > 191) {
            return ['error' => 'A valid category and product name are required.'];
        }
        if (!is_string($unit) || trim($unit) === '' || strlen(trim($unit)) > 50) {
            return ['error' => 'Product unit is required and must be at most 50 characters.'];
        }
        if ($description !== null && (!is_string($description) || strlen($description) > 5000)) {
            return ['error' => 'Product description must be at most 5000 characters.'];
        }

        $name = trim($name);
        $unit = trim($unit);
        if ($slug === null && $existing !== null && $name === $existing['name']) {
            $slug = $existing['slug'];
        }
        if ($slug === null || $slug === '') {
            $asciiName = strtr($name, [
                'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
                'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A',
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
                'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
                'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
                'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
                'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
                'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
                'ç' => 'c', 'Ç' => 'C',
            ]);
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $asciiName) ?? '', '-'));
        }
        if (!is_string($slug) || preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $slug) !== 1 || strlen($slug) > 191) {
            return ['error' => 'Product slug must use lowercase letters, numbers, and hyphens.'];
        }

        $active = filter_var($payload['active'] ?? $existing['active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($active === null) {
            return ['error' => 'Product active status must be true or false.'];
        }

        return [
            'category_id' => $categoryId,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'unit' => $unit,
            'active' => $active ? 1 : 0,
        ];
    }
}