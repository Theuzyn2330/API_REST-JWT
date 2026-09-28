<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class Price extends AbstractModel
{
	protected PDO $connection;

	public function __construct(array $attributes = [], ?PDO $connection = null)
	{
		parent::__construct($attributes);
		$this->connection = $connection ?? Connection::getConnection();
	}

	public function findAll(?int $productId = null, ?int $sourceId = null, int $limit = 100): array
	{
		$conditions = [];
		$parameters = [];
		if ($productId !== null) {
			$conditions[] = 'price_records.product_id = :product_id';
			$parameters['product_id'] = $productId;
		}
		if ($sourceId !== null) {
			$conditions[] = 'price_records.source_id = :source_id';
			$parameters['source_id'] = $sourceId;
		}

		$sql = 'SELECT price_records.id, price_records.product_id, products.name AS product_name, '
			. 'price_records.source_id, sources.name AS source_name, price_records.price, price_records.unit, '
			. 'price_records.location, price_records.collected_at, price_records.created_at '
			. 'FROM prices AS price_records '
			. 'INNER JOIN products ON products.id = price_records.product_id '
			. 'INNER JOIN sources ON sources.id = price_records.source_id';
		if ($conditions !== []) {
			$sql .= ' WHERE ' . implode(' AND ', $conditions);
		}
		$sql .= ' ORDER BY price_records.collected_at DESC LIMIT :limit';

		$statement = $this->connection->prepare($sql);
		foreach ($parameters as $name => $value) {
			$statement->bindValue(':' . $name, $value, PDO::PARAM_INT);
		}
		$statement->bindValue(':limit', max(1, min($limit, 100)), PDO::PARAM_INT);
		$statement->execute();

		return $statement->fetchAll();
	}

	public function findById(int $id): ?array
	{
		$statement = $this->connection->prepare(
			'SELECT price_records.id, price_records.product_id, products.name AS product_name, '
			. 'price_records.source_id, sources.name AS source_name, price_records.price, price_records.unit, '
			. 'price_records.location, price_records.collected_at, price_records.created_at '
			. 'FROM prices AS price_records '
			. 'INNER JOIN products ON products.id = price_records.product_id '
			. 'INNER JOIN sources ON sources.id = price_records.source_id '
			. 'WHERE price_records.id = :id LIMIT 1'
		);
		$statement->execute(['id' => $id]);
		$price = $statement->fetch();

		return is_array($price) ? $price : null;
	}

	public function create(array $price): int
	{
		$statement = $this->connection->prepare(
			'INSERT INTO prices (product_id, source_id, price, unit, location, collected_at) '
			. 'VALUES (:product_id, :source_id, :price, :unit, :location, :collected_at)'
		);
		$statement->execute([
			'product_id' => $price['product_id'],
			'source_id' => $price['source_id'],
			'price' => $price['price'],
			'unit' => $price['unit'],
			'location' => $price['location'],
			'collected_at' => $price['collected_at'],
		]);

		return (int) $this->connection->lastInsertId();
	}
}
