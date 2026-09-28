<?php

namespace App\Controllers\Api;

use App\Models\User;
use App\Services\AuthService;
use App\Services\JwtService;
use RuntimeException;

class AuthController
{
	public function __construct(private ?User $users = null)
	{
	}

	public function register(?array $payload = null): array
	{
		$payload = $this->readPayload($payload);
		if ($payload === null) {
			http_response_code(400);
			return ['error' => 'Request body must be valid JSON.'];
		}

		$name = $payload['name'] ?? null;
		$email = $payload['email'] ?? null;
		$password = $payload['password'] ?? null;
		if (!is_string($name) || !is_string($email) || !is_string($password)) {
			http_response_code(422);
			return ['error' => 'Name, email, and password are required.'];
		}

		$name = trim($name);
		$email = strtolower(trim($email));
		if (
			$name === ''
			|| strlen($name) > 150
			|| filter_var($email, FILTER_VALIDATE_EMAIL) === false
			|| strlen($email) > 191
			|| strlen($password) < 8
			|| trim($password) === ''
		) {
			http_response_code(422);
			return ['error' => 'Registration data is invalid.'];
		}

		try {
			$users = $this->users ?? new User();
			if ($users->findByEmail($email) !== null) {
				http_response_code(409);
				return ['error' => 'An account with this email already exists.'];
			}

			$passwordHash = password_hash($password, PASSWORD_DEFAULT);
			if ($passwordHash === false) {
				throw new RuntimeException('Unable to hash password.');
			}

			$id = $users->create([
				'name' => $name,
				'email' => $email,
				'password' => $passwordHash,
			]);
		} catch (\Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to register user.'];
		}

		http_response_code(201);
		return [
			'message' => 'User registered successfully.',
			'user' => [
				'id' => $id,
				'name' => $name,
				'email' => $email,
			],
		];
	}

	public function login(?array $payload = null): array
	{
		$payload = $this->readPayload($payload);
		if ($payload === null) {
			http_response_code(400);
			return ['error' => 'Request body must be valid JSON.'];
		}

		$email = $payload['email'] ?? null;
		$password = $payload['password'] ?? null;
		if (!is_string($email) || !is_string($password)) {
			http_response_code(422);
			return ['error' => 'Email and password are required.'];
		}

		$credentials = ['email' => strtolower(trim($email)), 'password' => $password];
		$authService = new AuthService($this->users);
		if (!$authService->validateCredentials($credentials)) {
			http_response_code(422);
			return ['error' => 'Login data is invalid.'];
		}

		try {
			$user = $authService->login($credentials);
		} catch (\Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to authenticate user.'];
		}

		if ($user === null) {
			http_response_code(401);
			return ['error' => 'Invalid email or password.'];
		}

		try {
			$jwtService = new JwtService();
			$role = isset($user['role']) && is_string($user['role']) ? $user['role'] : null;
			$token = $jwtService->generate((int) $user['id'], $role);
		} catch (\Throwable $exception) {
			http_response_code(500);
			return ['error' => 'Unable to issue access token.'];
		}

		http_response_code(200);
		return [
			'message' => 'Login successful.',
			'user' => $user,
			'token_type' => 'Bearer',
			'access_token' => $token,
			'expires_in' => $jwtService->getExpirationSeconds(),
		];
	}

	private function readPayload(?array $payload): ?array
	{
		if ($payload !== null) {
			return $payload;
		}

		$requestBody = file_get_contents('php://input');
		$decodedPayload = is_string($requestBody) ? json_decode($requestBody, true) : null;

		return is_array($decodedPayload) && json_last_error() === JSON_ERROR_NONE
			? $decodedPayload
			: null;
	}
}