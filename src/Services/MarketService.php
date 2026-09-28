<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MarketIndex;
use App\Models\Price;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class MarketService
{
    public function __construct(
        private ?PriceService $priceService = null,
        private ?Price $prices = null,
        private ?MarketIndex $indices = null
    ) {
    }

    public function calculate(array $records, ?string $from = null, ?string $to = null): array
    {
        $from = $this->normalizeDateBoundary($from, 'from');
        $to = $this->normalizeDateBoundary($to, 'to');
        if ($from !== null && $to !== null && $from > $to) {
            throw new InvalidArgumentException('The from date must not be after the to date.');
        }

        $processed = ($this->priceService ?? new PriceService())->processRecords($records);
        $statistics = [];
        $accepted = 0;

        foreach ($processed['groups'] as $group) {
            $groupRecords = array_values(array_filter(
                $group['records'],
                static fn (array $record): bool => ($from === null || $record['collected_at'] >= $from)
                    && ($to === null || $record['collected_at'] <= $to)
            ));
            if ($groupRecords === []) {
                continue;
            }

            usort($groupRecords, static fn (array $left, array $right): int => strcmp($left['collected_at'], $right['collected_at']));
            $prices = array_map(static fn (array $record): float => (float) $record['price'], $groupRecords);
            $collectedDates = array_column($groupRecords, 'collected_at');
            sort($collectedDates, SORT_STRING);
            $count = count($prices);
            $currentPrice = $prices[$count - 1];
            $previousPrice = $count > 1 ? $prices[$count - 2] : null;
            $variation = $previousPrice === null ? null : round($currentPrice - $previousPrice, 4);
            $accepted += $count;
            $statistics[] = [
                'product_id' => $group['product_id'],
                'product_name' => $group['product_name'],
                'unit' => $group['unit'],
                'location' => $group['location'],
                'count' => $count,
                'average' => round(array_sum($prices) / $count, 4),
                'minimum' => min($prices),
                'maximum' => max($prices),
                'current_price' => $currentPrice,
                'variation' => $variation,
                'variation_percent' => $previousPrice === null || $previousPrice == 0.0
                    ? null
                    : round(($variation / $previousPrice) * 100, 2),
                'source_count' => count(array_unique(array_column($groupRecords, 'source_id'))),
                'collected_from' => $collectedDates[0],
                'collected_to' => $collectedDates[$count - 1],
                'last_updated' => $collectedDates[$count - 1],
            ];
        }

        return [
            'statistics' => $statistics,
            'accepted' => $accepted,
            'rejected' => $processed['rejected'],
        ];
    }

    public function getMarkets(): array
    {
        return [
            'markets' => [],
            'message' => 'Market list not implemented yet.',
        ];
    }

    public function getMarketById(string $marketId): array
    {
        return [
            'marketId' => $marketId,
            'market' => null,
            'message' => 'Market lookup not implemented yet.',
        ];
    }

    public function calculateAndStore(int $productId, ?string $from = null, ?string $to = null): array
    {
        if ($productId < 1) {
            throw new InvalidArgumentException('Product ID must be a positive integer.');
        }

        $rawRecords = ($this->prices ?? new Price())->findAll($productId, null, 100);
        $result = $this->calculate($rawRecords, $from, $to);
        $calculatedAt = gmdate('Y-m-d H:i:s');
        $saved = [];
        $indexRepository = $this->indices ?? new MarketIndex();

        foreach ($result['statistics'] as $statistics) {
            $snapshot = [
                'product_id' => $productId,
                'unit' => $statistics['unit'],
                'location' => $statistics['location'],
                'average_price' => number_format($statistics['average'], 4, '.', ''),
                'minimum_price' => number_format($statistics['minimum'], 4, '.', ''),
                'maximum_price' => number_format($statistics['maximum'], 4, '.', ''),
                'record_count' => $statistics['count'],
                'calculated_at' => $calculatedAt,
            ];
            $snapshot['id'] = $indexRepository->create($snapshot);
            $saved[] = $snapshot;
        }

        return [
            'data' => $saved,
            'accepted' => $result['accepted'],
            'rejected' => $result['rejected'],
        ];
    }

    public function getProductHistory(int $productId, ?string $location = null, int $limit = 100): array
    {
        if ($productId < 1) {
            throw new InvalidArgumentException('Product ID must be a positive integer.');
        }

        return ($this->indices ?? new MarketIndex())->findHistory($productId, $location, $limit);
    }

    private function normalizeDateBoundary(?string $date, string $name): ?string
    {
        if ($date === null) {
            return null;
        }

        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();
        if ($parsed === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new InvalidArgumentException(sprintf('%s must use YYYY-MM-DD HH:MM:SS.', $name));
        }

        return $parsed->format('Y-m-d H:i:s');
    }
}
