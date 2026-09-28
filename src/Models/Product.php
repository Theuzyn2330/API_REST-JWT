<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Product extends AbstractModel
{
	protected PDO $connection;

	public function __construct(array $attributes = [], ?PDO $connection = null)
	{
		parent::__construct($attributes);
		$this->connection = $connection ?? Connection::getConnection();
	}

	public function findAll(?int $categoryId = null, ?bool $active = null): array
	{
		$conditions = [];
		$parameters = [];
		if ($categoryId !== null) {
			$conditions[] = 'p.category_id = :category_id';
			$parameters['category_id'] = $categoryId;
		}
		if ($active !== null) {
			$conditions[] = 'p.active = :active';
			$parameters['active'] = $active ? 1 : 0;
		}

		$sql = 'SELECT p.id, p.category_id, c.name AS category_name, p.name, p.slug, p.description, p.unit, p.active, p.created_at, p.updated_at '
			. 'FROM products p INNER JOIN categories c ON c.id = p.category_id';
		if ($conditions !== []) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}
		$sql .= ' ORDER BY p.name ASC';

		$statement = $this->connection->prepare($sql);
		$statement->execute($parameters);

		return $statement->fetchAll();
	}

	public function findById(int $id): ?array
	{
		$statement = $this->connection->prepare(
			'SELECT p.id, p.category_id, c.name AS category_name, p.name, p.slug, p.description, p.unit, p.active, p.created_at, p.updated_at '
			. 'FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE p.id = :id LIMIT 1'
		);
		$statement->execute(['id' => $id]);
		$product = $statement->fetch();

		return is_array($product) ? $product : null;
	}

	public function create(array $product): int
	{
		$statement = $this->connection->prepare(
			'INSERT INTO products (category_id, name, slug, description, unit, active) '
			. 'VALUES (:category_id, :name, :slug, :description, :unit, :active)'
		);
		$statement->execute([
			'category_id' => $product['category_id'],
			'name' => $product['name'],
			'slug' => $product['slug'],
			'description' => $product['description'] ?? null,
			'unit' => $product['unit'],
			'active' => $product['active'] ?? 1,
		]);

		return (int) $this->connection->lastInsertId();
	}

	public function update(int $id, array $product): bool
	{
		$statement = $this->connection->prepare(
			'UPDATE products SET category_id = :category_id, name = :name, slug = :slug, '
			. 'description = :description, unit = :unit, active = :active WHERE id = :id'
		);
		$statement->execute([
			'id' => $id,
			'category_id' => $product['category_id'],
			'name' => $product['name'],
			'slug' => $product['slug'],
			'description' => $product['description'] ?? null,
			'unit' => $product['unit'],
			'active' => $product['active'] ?? 1,
		]);

		return $statement->rowCount() > 0;
	}

	public function deactivate(int $id): bool
	{
		$statement = $this->connection->prepare('UPDATE products SET active = 0 WHERE id = :id');
		$statement->execute(['id' => $id]);

		return $statement->rowCount() > 0;
	}
}
