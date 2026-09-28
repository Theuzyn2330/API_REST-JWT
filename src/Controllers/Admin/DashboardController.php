<?php

namespace App\Controllers\Admin;

use App\Database\Connection;
use App\Models\Price;
use App\Services\MarketService;
use PDO;
use PDOStatement;
use RuntimeException;

final class DashboardController
{
	private const TABLES = [
		'users' => 'users',
		'products' => 'products',
		'categories' => 'categories',
		'prices' => 'prices',
		'sources' => 'sources',
	];

	public function __construct(private ?PDO $connection = null, private ?Price $priceRecords = null)
	{
	}

	public function statistics(): array
	{
		$connection = $this->connection ?? Connection::getConnection();
		$tableCheck = $connection->prepare(
			'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table LIMIT 1'
		);
		$statistics = [];

		foreach (self::TABLES as $metric => $table) {
			$tableCheck->execute(['table' => $table]);
			if ($tableCheck->fetchColumn() === false) {
				$statistics[$metric] = null;
				continue;
			}

			$countQuery = $connection->query(sprintf('SELECT COUNT(*) FROM `%s`', $table));
			if (!$countQuery instanceof PDOStatement) {
				throw new RuntimeException('Unable to read dashboard statistics.');
			}

			$statistics[$metric] = (int) $countQuery->fetchColumn();
		}

		return $statistics;
	}

	public function monitoring(): array
	{
		$records = ($this->priceRecords ?? new Price())->findAll(null, null, 100);

		return (new MarketService())->calculate($records);
	}
}