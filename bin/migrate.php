<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

try {
    $connection = App\Database\Connection::getConnection();
    $connection->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (' .
        'filename VARCHAR(255) NOT NULL PRIMARY KEY, ' .
        'applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP' .
        ') ENGINE=InnoDB DEFAULT CHARACTER SET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $migrationFiles = glob(dirname(__DIR__) . '/src/Database/migrations/*.sql') ?: [];
    sort($migrationFiles, SORT_STRING);

    foreach ($migrationFiles as $migrationFile) {
        $filename = basename($migrationFile);
        $check = $connection->prepare('SELECT filename FROM schema_migrations WHERE filename = :filename');
        $check->execute(['filename' => $filename]);
        if ($check->fetchColumn() !== false) {
            continue;
        }

        $sql = file_get_contents($migrationFile);
        if ($sql === false || trim($sql) === '') {
            throw new RuntimeException(sprintf('Migration file is empty or unreadable: %s.', $filename));
        }

        $connection->exec($sql);
        $record = $connection->prepare('INSERT INTO schema_migrations (filename) VALUES (:filename)');
        $record->execute(['filename' => $filename]);
    }

    fwrite(STDOUT, "Database migrations are up to date.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}