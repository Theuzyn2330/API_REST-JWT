<?php

namespace App\Controllers\Api;

use App\Models\Category;
use PDOException;
use Throwable;

final class CategoryController
{
    public function __construct(private ?Category $categories = null)
    {
    }

    public function index(): array
    {
        try {
            return ['data' => ($this->categories ?? new Category())->findAll()];
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to load categories.'];
        }
    }

    public function show(string $id): array
    {
        if (ctype_digit($id) !== true || (int) $id < 1) {
            http_response_code(400);
            return ['error' => 'Category ID is invalid.'];
        }

        try {
            $category = ($this->categories ?? new Category())->findById((int) $id);
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to load category.'];
        }

        if ($category === null) {
            http_response_code(404);
            return ['error' => 'Category not found.'];
        }

        return ['data' => $category];
    }

    public function create(): array
    {
        $payload = $this->readPayload();
        if ($payload === null) {
            http_response_code(400);
            return ['error' => 'Request body must be valid JSON.'];
        }

        $category = $this->validateCategory($payload);
        if (isset($category['error'])) {
            http_response_code(422);
            return ['error' => $category['error']];
        }

        try {
            $id = ($this->categories ?? new Category())->create($category);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                http_response_code(409);
                return ['error' => 'Category name or slug already exists.'];
            }

            http_response_code(500);
            return ['error' => 'Unable to create category.'];
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to create category.'];
        }

        http_response_code(201);
        return ['data' => ['id' => $id] + $category];
    }

    public function update(string $id): array
    {
        if (ctype_digit($id) !== true || (int) $id < 1) {
            http_response_code(400);
            return ['error' => 'Category ID is invalid.'];
        }

        $payload = $this->readPayload();
        if ($payload === null) {
            http_response_code(400);
            return ['error' => 'Request body must be valid JSON.'];
        }

        try {
            $model = $this->categories ?? new Category();
            $existing = $model->findById((int) $id);
            if ($existing === null) {
                http_response_code(404);
                return ['error' => 'Category not found.'];
            }

            $category = $this->validateCategory($payload, $existing);
            if (isset($category['error'])) {
                http_response_code(422);
                return ['error' => $category['error']];
            }

            $model->update((int) $id, $category);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                http_response_code(409);
                return ['error' => 'Category name or slug already exists.'];
            }

            http_response_code(500);
            return ['error' => 'Unable to update category.'];
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to update category.'];
        }

        return ['data' => ['id' => (int) $id] + $category];
    }

    public function delete(string $id): array
    {
        if (ctype_digit($id) !== true || (int) $id < 1) {
            http_response_code(400);
            return ['error' => 'Category ID is invalid.'];
        }

        try {
            $deleted = ($this->categories ?? new Category())->delete((int) $id);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                http_response_code(409);
                return ['error' => 'Category is in use and cannot be deleted.'];
            }

            http_response_code(500);
            return ['error' => 'Unable to delete category.'];
        } catch (Throwable $exception) {
            http_response_code(500);
            return ['error' => 'Unable to delete category.'];
        }

        if (!$deleted) {
            http_response_code(404);
            return ['error' => 'Category not found.'];
        }

        return ['message' => 'Category deleted successfully.'];
    }

    private function readPayload(): ?array
    {
        $body = file_get_contents('php://input');
        $payload = is_string($body) ? json_decode($body, true) : null;

        return is_array($payload) && json_last_error() === JSON_ERROR_NONE ? $payload : null;
    }

    private function validateCategory(array $payload, ?array $existing = null): array
    {
        $name = $payload['name'] ?? $existing['name'] ?? null;
        $description = $payload['description'] ?? $existing['description'] ?? null;
        $slug = $payload['slug'] ?? null;

        if (!is_string($name) || trim($name) === '' || strlen(trim($name)) > 150) {
            return ['error' => 'Category name is required and must be at most 150 characters.'];
        }
        $name = trim($name);

        if ($description !== null && (!is_string($description) || strlen($description) > 5000)) {
            return ['error' => 'Category description must be at most 5000 characters.'];
        }

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
            return ['error' => 'Category slug must use lowercase letters, numbers, and hyphens.'];
        }

        return ['name' => $name, 'slug' => $slug, 'description' => $description];
    }
}