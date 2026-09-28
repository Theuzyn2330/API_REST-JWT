<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

try {
    $databaseName = App\Database\Connection::getConfiguredDatabaseName();
    if (preg_match('/\A[A-Za-z0-9_]+\z/', $databaseName) !== 1) {
        throw new RuntimeException('DB_DATABASE may contain only letters, numbers, and underscores.');
    }

    $connection = App\Database\Connection::getServerConnection();
    $connection->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        $databaseName
    ));

    fwrite(STDOUT, "Database is ready.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Database setup failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}