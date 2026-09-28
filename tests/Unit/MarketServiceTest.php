<?php

use App\Services\MarketService;
use PHPUnit\Framework\TestCase;

final class MarketServiceTest extends TestCase
{
    public function testCalculatesRangeVariationAndSourceCount(): void
    {
        $statistics = (new MarketService())->calculate([
            $this->record(1, '10', 'kg', '2026-09-28 10:00:00'),
            $this->record(2, '3', '500g', '2026-09-28 11:00:00'),
            $this->record(3, '12', 'kg', '2026-09-28 12:00:00'),
        ])['statistics'][0];

        self::assertSame('Milho', $statistics['product_name']);
        self::assertSame(3, $statistics['count']);
        self::assertSame(9.3333, $statistics['average']);
        self::assertSame(6.0, $statistics['minimum']);
        self::assertSame(12.0, $statistics['maximum']);
        self::assertSame(12.0, $statistics['current_price']);
        self::assertSame(6.0, $statistics['variation']);
        self::assertSame(100.0, $statistics['variation_percent']);
        self::assertSame(3, $statistics['source_count']);
    }

    public function testFiltersPeriodAndRejectsReversedDates(): void
    {
        $service = new MarketService();
        $records = [
            $this->record(1, '10', 'kg', '2026-09-28 10:00:00'),
            $this->record(2, '3', '500g', '2026-09-28 11:00:00'),
            $this->record(3, '12', 'kg', '2026-09-28 12:00:00'),
        ];
        $filtered = $service->calculate($records, '2026-09-28 11:00:00', '2026-09-28 12:00:00');

        self::assertSame(2, $filtered['statistics'][0]['count']);
        self::assertSame(9.0, $filtered['statistics'][0]['average']);
        $this->expectException(\InvalidArgumentException::class);
        $service->calculate($records, '2026-09-28 12:00:00', '2026-09-28 10:00:00');
    }

    private function record(int $sourceId, string $price, string $unit, string $collectedAt): array
    {
        return [
            'product_id' => 1,
            'product_name' => 'Milho',
            'source_id' => $sourceId,
            'price' => $price,
            'unit' => $unit,
            'location' => 'Centro',
            'collected_at' => $collectedAt,
        ];
    }
}