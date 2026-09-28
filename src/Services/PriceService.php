<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

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

    public function processRecords(array $records): array
    {
        $groups = [];
        $accepted = 0;
        $rejected = 0;

        foreach ($records as $record) {
            if (!is_array($record)) {
                $rejected++;
                continue;
            }

            $productId = filter_var($record['product_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $sourceId = filter_var($record['source_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $amount = filter_var($record['price'] ?? null, FILTER_VALIDATE_FLOAT);
            $unit = $record['unit'] ?? null;
            $location = $record['location'] ?? null;
            $collectedAt = $record['collected_at'] ?? null;

            if (
                $productId === false
                || $sourceId === false
                || $amount === false
                || !is_finite((float) $amount)
                || $amount <= 0
                || !is_string($unit)
                || trim($unit) === ''
                || strlen(trim($unit)) > 50
                || ($location !== null && (!is_string($location) || strlen(trim($location)) > 191))
                || !self::isValidDate($collectedAt)
            ) {
                $rejected++;
                continue;
            }

            [$normalizedUnit, $factor] = $this->normalizeUnit(trim($unit));
            $normalizedAmount = (float) $amount * $factor;
            if (!is_finite($normalizedAmount) || $normalizedAmount <= 0) {
                $rejected++;
                continue;
            }

            $location = is_string($location) && trim($location) !== '' ? trim($location) : null;
            $groupKey = json_encode([$productId, $normalizedUnit, $location], JSON_UNESCAPED_UNICODE);
            $groups[$groupKey] ??= [
                'product_id' => $productId,
                'product_name' => is_string($record['product_name'] ?? null) ? $record['product_name'] : null,
                'unit' => $normalizedUnit,
                'location' => $location,
                'records' => [],
            ];
            $groups[$groupKey]['records'][] = [
                'source_id' => $sourceId,
                'raw_price' => number_format((float) $amount, 4, '.', ''),
                'raw_unit' => trim($unit),
                'price' => number_format(round($normalizedAmount, 4), 4, '.', ''),
                'unit' => $normalizedUnit,
                'location' => $location,
                'collected_at' => $collectedAt,
            ];
            $accepted++;
        }

        return [
            'groups' => array_values($groups),
            'accepted' => $accepted,
            'rejected' => $rejected,
        ];
    }

    private function normalizeUnit(string $unit): array
    {
        $normalizedUnit = mb_strtolower($unit, 'UTF-8');
        $conversions = [
            'kg' => ['kg', 1.0],
            'kilo' => ['kg', 1.0],
            'quilo' => ['kg', 1.0],
            'quilos' => ['kg', 1.0],
            'kilogram' => ['kg', 1.0],
            'kilograms' => ['kg', 1.0],
            'g' => ['kg', 1000.0],
            'gram' => ['kg', 1000.0],
            'grama' => ['kg', 1000.0],
            'gramas' => ['kg', 1000.0],
            'mg' => ['kg', 1000000.0],
            'milligram' => ['kg', 1000000.0],
            'ton' => ['kg', 0.001],
            'tonne' => ['kg', 0.001],
            'tonelada' => ['kg', 0.001],
            'toneladas' => ['kg', 0.001],
            'l' => ['l', 1.0],
            'liter' => ['l', 1.0],
            'litro' => ['l', 1.0],
            'litros' => ['l', 1.0],
            'ml' => ['l', 1000.0],
            'milliliter' => ['l', 1000.0],
            'mililitro' => ['l', 1000.0],
            'un' => ['unit', 1.0],
            'unidade' => ['unit', 1.0],
            'unidades' => ['unit', 1.0],
            'unit' => ['unit', 1.0],
        ];

        if (preg_match('/\A([0-9]+(?:[.,][0-9]+)?)\s*([a-z]+)\z/', $normalizedUnit, $matches) === 1) {
            $quantity = (float) str_replace(',', '.', $matches[1]);
            if ($quantity > 0 && isset($conversions[$matches[2]])) {
                [$baseUnit, $factor] = $conversions[$matches[2]];

                return [$baseUnit, $factor / $quantity];
            }
        }

        return $conversions[$normalizedUnit] ?? [$normalizedUnit, 1.0];
    }

    private static function isValidDate(mixed $date): bool
    {
        if (!is_string($date)) {
            return false;
        }

        $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $date, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();

        return $parsedDate !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
    }
}
