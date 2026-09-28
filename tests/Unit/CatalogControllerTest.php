<?php

use App\Controllers\Api\CategoryController;
use App\Controllers\Api\ProductController;
use App\Controllers\Api\SourceController;
use App\Models\Category;
use App\Models\Product;
use App\Models\Source;
use PHPUnit\Framework\TestCase;

final class CatalogControllerTest extends TestCase
{
    public function testCategorySlugSupportsPortugueseAccents(): void
    {
        $store = new CategoryStoreStub();
        $response = (new CategoryController($store))->create(['name' => 'Grãos']);

        self::assertSame(201, http_response_code());
        self::assertSame('graos', $response['data']['slug']);
        self::assertSame(31, $response['data']['id']);
    }

    public function testProductCreateNormalizesNameAndValidatesCategory(): void
    {
        $store = new ProductStoreStub();
        $controller = new ProductController($store);
        $created = $controller->create(['category_id' => '4', 'name' => 'Café moído', 'unit' => 'kg']);

        self::assertSame(201, http_response_code());
        self::assertSame('cafe-moido', $created['data']['slug']);
        self::assertSame(4, $store->created['category_id']);

        $invalid = $controller->create(['category_id' => 0, 'name' => '', 'unit' => 'kg']);
        self::assertSame(422, http_response_code());
        self::assertArrayHasKey('error', $invalid);
    }

    public function testSourceRejectsNonHttpUrl(): void
    {
        $response = (new SourceController(new SourceStoreStub()))->create([
            'name' => 'External source',
            'type' => 'api',
            'url' => 'javascript:alert(1)',
        ]);

        self::assertSame(422, http_response_code());
        self::assertArrayHasKey('error', $response);
    }
}

final class CategoryStoreStub extends Category
{
    public function __construct()
    {
    }

    public function create(array $category): int
    {
        return 31;
    }
}

final class ProductStoreStub extends Product
{
    public array $created = [];

    public function __construct()
    {
    }

    public function create(array $product): int
    {
        $this->created = $product;
        return 41;
    }
}

final class SourceStoreStub extends Source
{
    public function __construct()
    {
    }

    public function create(array $source): int
    {
        return 51;
    }
}