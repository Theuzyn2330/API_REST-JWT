<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class MarketService
{
    public function __construct(private ?PriceService $priceService = null)
    {
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

            $prices = array_map(static fn (array $record): float => (float) $record['price'], $groupRecords);
            $collectedDates = array_column($groupRecords, 'collected_at');
            sort($collectedDates, SORT_STRING);
            $count = count($prices);
            $accepted += $count;
            $statistics[] = [
                'product_id' => $group['product_id'],
                'unit' => $group['unit'],
                'location' => $group['location'],
                'count' => $count,
                'average' => round(array_sum($prices) / $count, 4),
                'minimum' => min($prices),
                'maximum' => max($prices),
                'collected_from' => $collectedDates[0],
                'collected_to' => $collectedDates[$count - 1],
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
