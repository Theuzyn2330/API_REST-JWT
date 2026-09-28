<?php

use App\Services\PriceService;
use PHPUnit\Framework\TestCase;

final class PriceServiceTest extends TestCase
{
    public function testNormalizesPackagingAndPreservesRawValues(): void
    {
        $result = (new PriceService())->processRecords([
            [
                'product_id' => 1,
                'product_name' => 'Milho',
                'source_id' => 2,
                'price' => '10.00',
                'unit' => 'kg',
                'location' => 'Centro',
                'collected_at' => '2026-09-28 10:00:00',
            ],
            [
                'product_id' => 1,
                'product_name' => 'Milho',
                'source_id' => 3,
                'price' => '3.00',
                'unit' => '500g',
                'location' => 'Centro',
                'collected_at' => '2026-09-28 11:00:00',
            ],
        ]);

        self::assertSame(2, $result['accepted']);
        self::assertSame(0, $result['rejected']);
        self::assertCount(1, $result['groups']);
        self::assertSame('Milho', $result['groups'][0]['product_name']);
        self::assertSame('6.0000', $result['groups'][0]['records'][1]['price']);
        self::assertSame('3.0000', $result['groups'][0]['records'][1]['raw_price']);
        self::assertSame('500g', $result['groups'][0]['records'][1]['raw_unit']);
    }

    public function testRejectsInvalidAmountAndDate(): void
    {
        $result = (new PriceService())->processRecords([
            ['product_id' => 1, 'source_id' => 1, 'price' => -1, 'unit' => 'kg', 'collected_at' => '2026-09-28 10:00:00'],
            ['product_id' => 1, 'source_id' => 1, 'price' => 1, 'unit' => 'kg', 'collected_at' => '2026-02-30 10:00:00'],
        ]);

        self::assertSame(0, $result['accepted']);
        self::assertSame(2, $result['rejected']);
        self::assertSame([], $result['groups']);
    }
}