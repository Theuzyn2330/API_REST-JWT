<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Category extends AbstractModel
{
	protected PDO $connection;

	public function __construct(array $attributes = [], ?PDO $connection = null)
	{
		parent::__construct($attributes);
		$this->connection = $connection ?? Connection::getConnection();
	}

	public function findAll(): array
	{
		$statement = $this->connection->query(
			'SELECT id, name, slug, description, created_at, updated_at FROM categories ORDER BY name ASC'
		);

		return $statement === false ? [] : $statement->fetchAll();
	}

	public function findById(int $id): ?array
	{
		$statement = $this->connection->prepare(
			'SELECT id, name, slug, description, created_at, updated_at FROM categories WHERE id = :id LIMIT 1'
		);
		$statement->execute(['id' => $id]);
		$category = $statement->fetch();

		return is_array($category) ? $category : null;
	}

	public function create(array $category): int
	{
		$statement = $this->connection->prepare(
			'INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)'
		);
		$statement->execute([
			'name' => $category['name'],
			'slug' => $category['slug'],
			'description' => $category['description'] ?? null,
		]);

		return (int) $this->connection->lastInsertId();
	}

	public function update(int $id, array $category): bool
	{
		$statement = $this->connection->prepare(
			'UPDATE categories SET name = :name, slug = :slug, description = :description WHERE id = :id'
		);
		$statement->execute([
			'id' => $id,
			'name' => $category['name'],
			'slug' => $category['slug'],
			'description' => $category['description'] ?? null,
		]);

		return $statement->rowCount() > 0;
	}

	public function delete(int $id): bool
	{
		$statement = $this->connection->prepare('DELETE FROM categories WHERE id = :id');
		$statement->execute(['id' => $id]);

		return $statement->rowCount() > 0;
	}
}
