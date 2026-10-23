<?php

namespace App\Controllers\Api;

use App\Models\User;
use PDOException;
use RuntimeException;

class UserController
{
	public function __construct(private ?User $users = null)
	{
	}

	public function profile(object $request): array
	{
		$claims = $request->user ?? [];
		$userId = is_array($claims)
			? filter_var($claims['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
			: false;
		if ($userId === false) {
			http_response_code(401);
			return ['error' => 'Authentication is required.'];
		}

		try {
			$user = ($this->users ?? new User())->findById($userId);
		} catch (\Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to load user profile.'];
		}

		if ($user === null) {
			http_response_code(404);
			return ['error' => 'User not found.'];
		}

		http_response_code(200);
		return ['user' => $user];
	}

	public function index(): array
	{
		try {
			$users = ($this->users ?? new User())->findAll();
			foreach ($users as &$user) {
				unset($user['password']);
			}
			unset($user);
			http_response_code(200);
			return ['data' => $users];
		} catch (\Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to load users.'];
		}
	}

	public function create(?array $payload = null): array
	{
		if ($payload === null) {
			$body = file_get_contents('php://input');
			$payload = is_string($body) ? json_decode($body, true) : null;
		}
		if (!is_array($payload)) {
			http_response_code(400);
			return ['error' => 'Request body must be valid JSON.'];
		}

		$name = $payload['name'] ?? null;
		$email = $payload['email'] ?? null;
		$password = $payload['password'] ?? null;
		$role = $payload['role'] ?? 'client';
		if (
			!is_string($name) || trim($name) === '' || strlen(trim($name)) > 150
			|| !is_string($email) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false || strlen(trim($email)) > 191
			|| !is_string($password) || strlen($password) < 8 || trim($password) === ''
			|| $role !== 'client'
		) {
			http_response_code(422);
			return ['error' => 'User data is invalid. New panel users must have the client role.'];
		}

		$email = strtolower(trim($email));
		try {
			$model = $this->users ?? new User();
			if ($model->findByEmail($email) !== null) {
				http_response_code(409);
				return ['error' => 'An account with this email already exists.'];
			}

			$passwordHash = password_hash($password, PASSWORD_DEFAULT);
			if (!is_string($passwordHash)) {
				throw new RuntimeException('Unable to hash user password.');
			}
			$id = $model->create(['name' => trim($name), 'email' => $email, 'password' => $passwordHash]);
		} catch (PDOException $exception) {
			if ($exception->getCode() === '23000') {
				http_response_code(409);
				return ['error' => 'An account with this email already exists.'];
			}
			http_response_code(500);
			return ['error' => 'Unable to create user.'];
		} catch (\Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to create user.'];
		}

		http_response_code(201);
		return ['data' => ['id' => $id, 'name' => trim($name), 'email' => $email, 'role' => 'client']];
	}
}
