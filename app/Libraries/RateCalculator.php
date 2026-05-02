<?php

namespace App\Libraries;

class RateCalculator
{
    public static function resolveRate(array $pair): float
    {
        $rawRate = (float) ($pair['last_rate'] ?? 0);
        if ($rawRate <= 0) {
            return 0.0;
        }

        $mode = (string) ($pair['rate_mode'] ?? 'manual');
        if ($mode === 'inverse') {
            return 1 / $rawRate;
        }

        return $rawRate;
    }

    public static function calculate(float $amountSent, array $pair): array
    {
        $minAmount = (float) ($pair['min_amount'] ?? 0);
        $maxAmount = (float) ($pair['max_amount'] ?? 0);

        if ($amountSent < $minAmount) {
            return ['error' => 'Jumlah minimum: ' . $minAmount];
        }
        if ($maxAmount > 0 && $amountSent > $maxAmount) {
            return ['error' => 'Jumlah maksimum: ' . $maxAmount];
        }

        $rate  = self::resolveRate($pair);
        $gross = $amountSent * $rate;

        $feeType  = (string) ($pair['fee_type'] ?? 'fixed');
        $feeValue = (float) ($pair['fee_value'] ?? 0);
        $fee = match ($feeType) {
            'percent' => $amountSent * ($feeValue / 100),
            default   => $feeValue,
        };

        $spreadType  = (string) ($pair['spread_type'] ?? 'none');
        $spreadValue = (float) ($pair['spread_value'] ?? 0);
        $spread = match ($spreadType) {
            'fixed'   => $spreadValue,
            'percent' => $amountSent * ($spreadValue / 100),
            default   => 0.0,
        };

        $net = $gross - $fee - $spread;
        $precision = (int) ($pair['rounding_precision'] ?? 2);
        $multiplier = 10 ** $precision;
        $roundingMode = (string) ($pair['rounding_mode'] ?? 'floor');
        $net = match ($roundingMode) {
            'ceil'  => ceil($net * $multiplier) / $multiplier,
            'round' => round($net, $precision),
            default => floor($net * $multiplier) / $multiplier,
        };

        return [
            'gross'  => $gross,
            'fee'    => $fee,
            'spread' => $spread,
            'net'    => max(0, $net),
            'error'  => null,
        ];
    }
}
