<?php

use App\Models\User;
use PHPUnit\Framework\TestCase;

final class UserModelTest extends TestCase
{
    public function testUsesPreparedQueriesAndKeepsPasswordOutOfPublicLookup(): void
    {
        $connection = new UserModelPdoStub();
        $users = new User([], $connection);

        self::assertSame(91, $users->create([
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password-hash',
        ]));
        $publicUser = $users->findById(91);
        $authUser = $users->findByEmail('ada@example.com');

        self::assertArrayNotHasKey('password', $publicUser);
        self::assertSame('admin', $publicUser['role']);
        self::assertSame('password-hash', $authUser['password']);
        self::assertCount(3, $connection->queries);
        self::assertStringContainsString('WHERE users.id = :id', $connection->queries[1]['sql']);
        self::assertSame(['id' => 91], $connection->queries[1]['parameters']);
        self::assertSame(['email' => 'ada@example.com'], $connection->queries[2]['parameters']);
    }
}

final class UserModelPdoStub extends PDO
{
    public array $queries = [];

    public function __construct()
    {
    }

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return new UserModelStatementStub($this, $query);
    }

    public function lastInsertId(?string $name = null): string|false
    {
        return '91';
    }
}

final class UserModelStatementStub extends PDOStatement
{
    private array $parameters = [];

    public function __construct(private UserModelPdoStub $connection, private string $query)
    {
    }

    public function execute(?array $params = null): bool
    {
        $this->parameters = $params ?? [];
        $this->connection->queries[] = ['sql' => $this->query, 'parameters' => $this->parameters];

        return true;
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        $user = ['id' => 91, 'name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'role' => 'admin'];
        if (str_contains($this->query, 'users.password')) {
            $user['password'] = 'password-hash';
        }

        return $user;
    }
}