<?php

namespace App\Database;

use Dotenv\Dotenv;
use PDO;
use PDOException;
use RuntimeException;

final class Connection
{
    private static ?PDO $connection = null;
    private static bool $environmentLoaded = false;

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $configuration = self::getDatabaseConfiguration();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $configuration['host'],
            $configuration['port'],
            $configuration['database']
        );

        self::$connection = self::createPdo($dsn, $configuration['username'], $configuration['password']);

        return self::$connection;
    }

    public static function getServerConnection(): PDO
    {
        $configuration = self::getDatabaseConfiguration();
        $dsn = sprintf(
            'mysql:host=%s;port=%d;charset=utf8mb4',
            $configuration['host'],
            $configuration['port']
        );

        return self::createPdo($dsn, $configuration['username'], $configuration['password']);
    }

    public static function getConfiguredDatabaseName(): string
    {
        return self::getDatabaseConfiguration()['database'];
    }

    private static function getDatabaseConfiguration(): array
    {
        self::loadEnvironment();

        $port = filter_var(
            self::getRequiredEnvironmentVariable('DB_PORT'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 65535]]
        );
        if ($port === false) {
            throw new RuntimeException('DB_PORT must be an integer between 1 and 65535.');
        }

        return [
            'host' => self::getRequiredEnvironmentVariable('DB_HOST'),
            'port' => $port,
            'database' => self::getRequiredEnvironmentVariable('DB_DATABASE'),
            'username' => self::getRequiredEnvironmentVariable('DB_USERNAME'),
            'password' => self::getEnvironmentVariable('DB_PASSWORD') ?? '',
        ];
    }

    private static function loadEnvironment(): void
    {
        if (self::$environmentLoaded) {
            return;
        }

        $projectRoot = dirname(__DIR__, 2);
        if (is_file($projectRoot . DIRECTORY_SEPARATOR . '.env')) {
            Dotenv::createImmutable($projectRoot)->safeLoad();
        }

        self::$environmentLoaded = true;
    }

    private static function createPdo(string $dsn, string $username, string $password): PDO
    {
        try {
            return new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to connect to the database.', 0, $exception);
        }
    }

    private static function getRequiredEnvironmentVariable(string $name): string
    {
        $value = self::getEnvironmentVariable($name);
        if ($value === null || trim($value) === '') {
            throw new RuntimeException(sprintf('Missing required environment variable: %s.', $name));
        }

        return trim($value);
    }

    private static function getEnvironmentVariable(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) ? $value : null;
    }
}