<?php

declare(strict_types=1);

namespace App\Services;

final class MarketService
{
    public function __construct()
    {
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
}
