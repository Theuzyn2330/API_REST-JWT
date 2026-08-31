<?php

declare(strict_types=1);

namespace App\Services;

final class PriceService
{
    public function __construct()
    {
    }

    public function getCurrentPrice(string $symbol): array
    {
        return [
            'symbol' => $symbol,
            'price' => null,
            'message' => 'Price lookup not implemented yet.',
        ];
    }

    public function getHistoricalPrices(string $symbol, array $filters = []): array
    {
        return [
            'symbol' => $symbol,
            'filters' => $filters,
            'data' => [],
            'message' => 'Historical price data not implemented yet.',
        ];
    }
}
