<?php

use App\Controllers\Api\UserController;
use App\Models\User;
use PHPUnit\Framework\TestCase;

final class UserControllerTest extends TestCase
{
    public function testListsUsersWithoutPasswordHash(): void
    {
        $response = (new UserController(new UserStoreStub()))->index();

        self::assertSame(200, http_response_code());
        self::assertCount(2, $response['data']);
        self::assertSame(['admin', 'client'], array_column($response['data'], 'role'));
        self::assertArrayNotHasKey('password', $response['data'][0]);
    }

    public function testRejectsInvalidRolesWhenCreatingUser(): void
    {
        $response = (new UserController(new UserStoreStub()))->create([
            'name' => 'Ana Silva',
            'email' => 'ana@example.com',
            'password' => 'senha123456',
            'role' => 'guest',
        ]);

        self::assertSame(422, http_response_code());
        self::assertArrayHasKey('error', $response);
    }

    public function testCreatesClientUserWithHashedPassword(): void
    {
        $store = new UserStoreStub();
        $response = (new UserController($store))->create([
            'name' => 'Ana Silva',
            'email' => 'ANA@example.com',
            'password' => 'senha123456',
        ]);

        self::assertSame(201, http_response_code());
        self::assertSame('ana@example.com', $response['data']['email']);
        self::assertSame('client', $response['data']['role']);
        self::assertTrue(password_verify('senha123456', $store->created['password']));
    }
}

final class UserStoreStub extends User
{
    public array $created = [];

    public function __construct()
    {
    }

    public function findAll(): array
    {
        return [
            ['id' => 1, 'name' => 'Root', 'email' => 'root@example.com', 'role' => 'admin', 'password' => 'hash1'],
            ['id' => 2, 'name' => 'Client', 'email' => 'client@example.com', 'role' => 'client', 'password' => 'hash2'],
        ];
    }

    public function findByEmail(string $email): ?array
    {
        return null;
    }

    public function create(array $user): int
    {
        $this->created = $user;
        return 7;
    }
}
