<?php

namespace App\Models;

use App\Database\Connection;
use PDO;

class MarketIndex extends AbstractModel
{
	protected PDO $connection;

	public function __construct(array $attributes = [], ?PDO $connection = null)
	{
		parent::__construct($attributes);
		$this->connection = $connection ?? Connection::getConnection();
	}

	public function create(array $index): int
	{
		$statement = $this->connection->prepare(
			'INSERT INTO market_indices '
			. '(product_id, unit, location, average_price, minimum_price, maximum_price, record_count, calculated_at) '
			. 'VALUES (:product_id, :unit, :location, :average_price, :minimum_price, :maximum_price, :record_count, :calculated_at)'
		);
		$statement->execute([
			'product_id' => $index['product_id'],
			'unit' => $index['unit'],
			'location' => $index['location'],
			'average_price' => $index['average_price'],
			'minimum_price' => $index['minimum_price'],
			'maximum_price' => $index['maximum_price'],
			'record_count' => $index['record_count'],
			'calculated_at' => $index['calculated_at'],
		]);

		return (int) $this->connection->lastInsertId();
	}

	public function findHistory(int $productId, ?string $location = null, int $limit = 100): array
	{
		$sql = 'SELECT id, product_id, unit, location, average_price, minimum_price, maximum_price, record_count, calculated_at '
			. 'FROM market_indices WHERE product_id = :product_id';
		if ($location !== null) {
			$sql .= ' AND location = :location';
		}
		$sql .= ' ORDER BY calculated_at DESC, id DESC LIMIT :limit';

		$statement = $this->connection->prepare($sql);
		$statement->bindValue(':product_id', $productId, PDO::PARAM_INT);
		if ($location !== null) {
			$statement->bindValue(':location', $location, PDO::PARAM_STR);
		}
		$statement->bindValue(':limit', max(1, min($limit, 500)), PDO::PARAM_INT);
		$statement->execute();

		return $statement->fetchAll();
	}
}
