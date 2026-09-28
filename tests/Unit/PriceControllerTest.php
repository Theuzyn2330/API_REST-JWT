<?php

use App\Controllers\Api\PriceController;
use App\Models\Price;
use PHPUnit\Framework\TestCase;

final class PriceControllerTest extends TestCase
{
    public function testCreatesValidatedPriceRecordWithDecimalPrecision(): void
    {
        $store = new PriceStoreStub();
        $response = (new PriceController($store))->create([
            'product_id' => '2',
            'source_id' => '3',
            'price' => '12.3456',
            'unit' => 'kg',
            'location' => 'São Paulo',
            'collected_at' => '2026-09-28 12:00:00',
        ]);

        self::assertSame(201, http_response_code());
        self::assertSame('12.3456', $store->created['price']);
        self::assertSame('2026-09-28 12:00:00', $store->created['collected_at']);
        self::assertSame(61, $response['data']['id']);
    }

    public function testRejectsInvalidCalendarDate(): void
    {
        $response = (new PriceController(new PriceStoreStub()))->create([
            'product_id' => 2,
            'source_id' => 3,
            'price' => 2,
            'unit' => 'kg',
            'collected_at' => '2026-02-30 12:00:00',
        ]);

        self::assertSame(422, http_response_code());
        self::assertArrayHasKey('error', $response);
    }
}

final class PriceStoreStub extends Price
{
    public array $created = [];

    public function __construct()
    {
    }

    public function create(array $price): int
    {
        $this->created = $price;
        return 61;
    }
}