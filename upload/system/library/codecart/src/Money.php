<?php
namespace CodeCart\Core;

/**
 * Deterministic monetary boundary helpers for the legacy OpenCart scalar API.
 *
 * OpenCart 3.x and third-party extensions exchange monetary values as scalars.
 * This class deliberately keeps that public contract while normalising every
 * commerce boundary to a predictable decimal scale and half-up rounding.
 */
final class Money {
    public const SCALE = 4;
    public const RATE_SCALE = 8;

    public static function normalize($value, int $scale = self::SCALE): string {
        $scale = max(0, min(8, $scale));
        $number = self::number($value);
        $factor = 10 ** $scale;
        $rounded = round($number * $factor, 0, PHP_ROUND_HALF_UP) / $factor;
        if (abs($rounded) < (0.5 / max(1, $factor))) {
            $rounded = 0.0;
        }
        return number_format($rounded, $scale, '.', '');
    }

    public static function decimal($value, int $scale = self::SCALE): float {
        return (float)self::normalize($value, $scale);
    }

    public static function rate($value): string {
        return self::normalize($value, self::RATE_SCALE);
    }

    public static function add($left, $right, int $scale = self::SCALE): string {
        return self::normalize(self::number($left) + self::number($right), $scale);
    }

    public static function subtract($left, $right, int $scale = self::SCALE): string {
        return self::normalize(self::number($left) - self::number($right), $scale);
    }

    public static function multiply($left, $right, int $scale = self::SCALE): string {
        return self::normalize(self::number($left) * self::number($right), $scale);
    }

    public static function divide($left, $right, int $scale = self::SCALE): string {
        $divisor = self::number($right);
        if (abs($divisor) < 0.000000000001) {
            throw new \InvalidArgumentException('Money division by zero.');
        }
        return self::normalize(self::number($left) / $divisor, $scale);
    }

    public static function percent($base, $percent, int $scale = self::SCALE): string {
        return self::normalize(self::number($base) * self::number($percent) / 100, $scale);
    }

    public static function compare($left, $right, int $scale = self::SCALE): int {
        $a = self::scaledInteger($left, $scale);
        $b = self::scaledInteger($right, $scale);
        return $a <=> $b;
    }

    public static function abs($value, int $scale = self::SCALE): string {
        return self::normalize(abs(self::number($value)), $scale);
    }

    public static function min($left, $right, int $scale = self::SCALE): string {
        return self::compare($left, $right, $scale) <= 0 ? self::normalize($left, $scale) : self::normalize($right, $scale);
    }

    public static function max($left, $right, int $scale = self::SCALE): string {
        return self::compare($left, $right, $scale) >= 0 ? self::normalize($left, $scale) : self::normalize($right, $scale);
    }

    /**
     * Distribute a fixed amount by non-negative weights without losing the
     * rounding remainder. The sum of returned values always equals amount at
     * the requested scale.
     */
    public static function distribute($amount, array $weights, int $scale = self::SCALE): array {
        $scale = max(0, min(8, $scale));
        $target = self::scaledInteger($amount, $scale);
        if (!$weights) {
            return array();
        }

        $clean = array();
        $weightTotal = 0.0;
        foreach ($weights as $key => $weight) {
            $w = max(0.0, self::number($weight));
            $clean[$key] = $w;
            $weightTotal += $w;
        }

        $result = array();
        if ($weightTotal <= 0.0 || $target === 0) {
            foreach ($clean as $key => $unused) {
                $result[$key] = self::normalize(0, $scale);
            }
            return $result;
        }

        $sign = $target < 0 ? -1 : 1;
        $absoluteTarget = abs($target);
        $allocated = 0;
        $remainders = array();

        foreach ($clean as $key => $weight) {
            $raw = $absoluteTarget * ($weight / $weightTotal);
            $units = (int)floor($raw);
            $allocated += $units;
            $result[$key] = $units;
            $remainders[] = array('key' => $key, 'fraction' => $raw - $units);
        }

        usort($remainders, static function(array $a, array $b): int {
            if ($a['fraction'] == $b['fraction']) {
                return 0;
            }
            return $a['fraction'] > $b['fraction'] ? -1 : 1;
        });

        $remaining = $absoluteTarget - $allocated;
        $count = count($remainders);
        for ($i = 0; $i < $remaining; $i++) {
            $key = $remainders[$i % $count]['key'];
            $result[$key]++;
        }

        $factor = 10 ** $scale;
        foreach ($result as $key => $units) {
            $result[$key] = number_format(($sign * $units) / $factor, $scale, '.', '');
        }

        return $result;
    }

    private static function scaledInteger($value, int $scale): int {
        $scale = max(0, min(8, $scale));
        $factor = 10 ** $scale;
        return (int)round(self::number($value) * $factor, 0, PHP_ROUND_HALF_UP);
    }

    private static function number($value): float {
        if (is_int($value) || is_float($value)) {
            return is_finite((float)$value) ? (float)$value : 0.0;
        }
        if (is_string($value)) {
            $value = trim($value);
            if ($value !== '' && is_numeric($value)) {
                $number = (float)$value;
                return is_finite($number) ? $number : 0.0;
            }
        }
        if (is_numeric($value)) {
            $number = (float)$value;
            return is_finite($number) ? $number : 0.0;
        }
        return 0.0;
    }
}
