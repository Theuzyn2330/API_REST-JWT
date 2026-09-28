<?php

namespace App\Models;

use App\Database\Connection;
use InvalidArgumentException;
use PDO;

class User extends AbstractModel
{
	protected PDO $connection;

	public function __construct(array $attributes = [], ?PDO $connection = null)
	{
		parent::__construct($attributes);
		$this->connection = $connection ?? Connection::getConnection();
	}

	public function create(array $user): int
	{
		foreach (['name', 'email', 'password'] as $field) {
			if (!isset($user[$field]) || !is_string($user[$field]) || trim($user[$field]) === '') {
				throw new InvalidArgumentException(sprintf('User field "%s" is required.', $field));
			}
		}

		$statement = $this->connection->prepare(
			'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)'
		);
		$statement->execute([
			'name' => $user['name'],
			'email' => $user['email'],
			'password' => $user['password'],
		]);

		return (int) $this->connection->lastInsertId();
	}

	public function findById(int $id): ?array
	{
		$statement = $this->connection->prepare(
			'SELECT users.id, users.name, users.email, roles.code AS role, users.created_at, users.updated_at '
			. 'FROM users INNER JOIN roles ON roles.id = users.role_id WHERE users.id = :id LIMIT 1'
		);
		$statement->execute(['id' => $id]);
		$user = $statement->fetch();

		return is_array($user) ? $user : null;
	}

	public function findByEmail(string $email): ?array
	{
		$statement = $this->connection->prepare(
			'SELECT users.id, users.name, users.email, users.password, roles.code AS role, users.created_at, users.updated_at '
			. 'FROM users INNER JOIN roles ON roles.id = users.role_id WHERE users.email = :email LIMIT 1'
		);
		$statement->execute(['email' => $email]);
		$user = $statement->fetch();

		return is_array($user) ? $user : null;
	}
}
