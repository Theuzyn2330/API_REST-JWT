<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Source extends AbstractModel
{
	protected PDO $connection;

	public function __construct(array $attributes = [], ?PDO $connection = null)
	{
		parent::__construct($attributes);
		$this->connection = $connection ?? Connection::getConnection();
	}

	public function findAll(?bool $active = null): array
	{
		$sql = 'SELECT id, name, type, url, active, created_at, updated_at FROM sources';
		$parameters = [];
		if ($active !== null) {
			$sql .= ' WHERE active = :active';
			$parameters['active'] = $active ? 1 : 0;
		}
		$sql .= ' ORDER BY name ASC';

		$statement = $this->connection->prepare($sql);
		$statement->execute($parameters);

		return $statement->fetchAll();
	}

	public function findById(int $id): ?array
	{
		$statement = $this->connection->prepare(
			'SELECT id, name, type, url, active, created_at, updated_at FROM sources WHERE id = :id LIMIT 1'
		);
		$statement->execute(['id' => $id]);
		$source = $statement->fetch();

		return is_array($source) ? $source : null;
	}

	public function create(array $source): int
	{
		$statement = $this->connection->prepare(
			'INSERT INTO sources (name, type, url, active) VALUES (:name, :type, :url, :active)'
		);
		$statement->execute([
			'name' => $source['name'],
			'type' => $source['type'],
			'url' => $source['url'] ?? null,
			'active' => $source['active'] ?? 1,
		]);

		return (int) $this->connection->lastInsertId();
	}

	public function update(int $id, array $source): bool
	{
		$statement = $this->connection->prepare(
			'UPDATE sources SET name = :name, type = :type, url = :url, active = :active WHERE id = :id'
		);
		$statement->execute([
			'id' => $id,
			'name' => $source['name'],
			'type' => $source['type'],
			'url' => $source['url'] ?? null,
			'active' => $source['active'] ?? 1,
		]);

		return $statement->rowCount() > 0;
	}

	public function deactivate(int $id): bool
	{
		$statement = $this->connection->prepare('UPDATE sources SET active = 0 WHERE id = :id');
		$statement->execute(['id' => $id]);

		return $statement->rowCount() > 0;
	}
}
