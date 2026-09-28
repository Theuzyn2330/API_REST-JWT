<?php

use App\Controllers\Api\AuthController;
use App\Middleware\JwtMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use App\Services\AuthService;
use App\Services\JwtService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\TestCase;

final class AuthenticationTest extends TestCase
{
    private string|false $originalSecret;
    private string|false $originalExpiration;
    private string $testSecret = 'phpunit-test-secret-32-bytes-minimum';

    protected function setUp(): void
    {
        $this->originalSecret = getenv('JWT_SECRET');
        $this->originalExpiration = getenv('JWT_EXPIRATION');
        putenv('JWT_SECRET=' . $this->testSecret);
        putenv('JWT_EXPIRATION=120');
    }

    protected function tearDown(): void
    {
        $this->restoreEnvironment('JWT_SECRET', $this->originalSecret);
        $this->restoreEnvironment('JWT_EXPIRATION', $this->originalExpiration);
    }

    public function testJwtContainsIdentityRoleAndExpiryAndRejectsTampering(): void
    {
        $service = new JwtService();
        $token = $service->generate(27, 'manager');
        $claims = $service->validate($token);

        self::assertSame('27', $claims['sub']);
        self::assertSame('manager', $claims['role']);
        self::assertSame(120, $claims['exp'] - $claims['iat']);
        $this->expectException(\InvalidArgumentException::class);
        $service->validate($token . 'tampered');
    }

    public function testLoginVerifiesPasswordAndReturnsRoleWithoutHash(): void
    {
        $user = new AuthenticationUserStub([
            'id' => 8,
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'manager',
        ]);
        $service = new AuthService($user);
        $authenticated = $service->login(['email' => 'manager@example.com', 'password' => 'correct-password']);

        self::assertSame('manager', $authenticated['role']);
        self::assertArrayNotHasKey('password', $authenticated);
        self::assertNull($service->login(['email' => 'manager@example.com', 'password' => 'wrong-password']));

        $controller = new AuthController($user);
        $response = $controller->login(['email' => 'manager@example.com', 'password' => 'correct-password']);
        $claims = (array) JWT::decode($response['access_token'], new Key($this->testSecret, 'HS256'));
        self::assertSame('manager', $claims['role']);
        self::assertArrayNotHasKey('password', $response['user']);
    }

    public function testRegistrationHashesPasswordAndRejectsDuplicateEmail(): void
    {
        $existingUser = [
            'id' => 8,
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => password_hash('correct-password', PASSWORD_DEFAULT),
            'role' => 'manager',
        ];
        $user = new AuthenticationUserStub($existingUser);
        $controller = new AuthController($user);
        $created = $controller->register([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'new-password',
        ]);

        self::assertSame(201, http_response_code());
        self::assertSame(9, $created['user']['id']);
        self::assertArrayNotHasKey('password', $created['user']);
        self::assertTrue(password_verify('new-password', $user->created['password']));

        $duplicate = $controller->register([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => 'another-password',
        ]);
        self::assertSame(409, http_response_code());
        self::assertArrayHasKey('error', $duplicate);
    }

    public function testJwtMiddlewareBlocksMissingInvalidAndExpiredTokens(): void
    {
        $service = new JwtService();
        $middleware = new JwtMiddleware($service);
        $called = false;

        $validRequest = (object) ['headers' => ['Authorization' => 'Bearer ' . $service->generate(32, 'client')]];
        $claims = $middleware->handle($validRequest, static fn (object $request): array => $request->user);
        self::assertSame('32', $claims['sub']);
        self::assertSame('client', $claims['role']);

        $missing = $middleware->handle((object) ['headers' => []], function () use (&$called): array {
            $called = true;
            return [];
        });
        self::assertSame(401, http_response_code());
        self::assertFalse($called);
        self::assertArrayHasKey('error', $missing);

        $invalid = $middleware->handle(
            (object) ['headers' => ['Authorization' => 'Bearer invalid.token']],
            static fn (): array => []
        );
        self::assertSame(401, http_response_code());
        self::assertArrayHasKey('error', $invalid);

        $expiredAt = time() - 60;
        $expiredToken = JWT::encode([
            'sub' => '32',
            'iat' => $expiredAt - 60,
            'nbf' => $expiredAt - 60,
            'exp' => $expiredAt,
        ], $this->testSecret, 'HS256');
        $expired = $middleware->handle(
            (object) ['headers' => ['Authorization' => 'Bearer ' . $expiredToken]],
            static fn (): array => []
        );
        self::assertSame(401, http_response_code());
        self::assertArrayHasKey('error', $expired);
    }

    public function testRoleMiddlewareBlocksClientAndAllowsManager(): void
    {
        $middleware = new RoleMiddleware(['admin', 'manager']);
        $called = false;
        $blocked = $middleware->handle((object) ['user' => ['role' => 'client']], function () use (&$called): array {
            $called = true;
            return [];
        });

        self::assertSame(403, http_response_code());
        self::assertFalse($called);
        self::assertArrayHasKey('error', $blocked);

        $allowed = $middleware->handle((object) ['user' => ['role' => 'manager']], static fn (): array => ['allowed' => true]);
        self::assertSame(['allowed' => true], $allowed);
    }

    private function restoreEnvironment(string $name, string|false $value): void
    {
        if ($value === false) {
            putenv($name);
            return;
        }

        putenv($name . '=' . $value);
    }
}

final class AuthenticationUserStub extends User
{
    public array $created = [];

    public function __construct(private array $user)
    {
    }

    public function findByEmail(string $email): ?array
    {
        return strtolower($email) === strtolower($this->user['email']) ? $this->user : null;
    }

    public function create(array $user): int
    {
        $this->created = $user;

        return 9;
    }
}