<?php

namespace App\Controllers\Api;

use App\Models\User;

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
}