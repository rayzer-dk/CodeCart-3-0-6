<?php

namespace CodeCart\Core;

final class StorefrontH1Colors {
    public const DARK_DEFAULT = '#8fc3ff';

    public static function normalize(mixed $color): ?string {
        if (!is_string($color)) {
            return null;
        }

        $color = strtolower(trim($color));

        return $color === '' || preg_match('/^#[0-9a-f]{6}$/D', $color) ? $color : null;
    }

    public static function lightOverride(mixed $color, mixed $legacy): string {
        $legacy = self::normalize($legacy);

        // Older builds seeded this color for generic headings, while page titles used the accent.
        return self::normalize($color) ?? ($legacy === '#273142' ? '' : ($legacy ?? ''));
    }

    public static function fromSettings(array $settings, string $prefix, array $submitted = []): array {
        $light = self::lightOverride($settings[$prefix . 'h1_light_color'] ?? null, $settings[$prefix . 'h1_color'] ?? null);

        return [
            'light' => self::normalize($submitted[$prefix . 'h1_light_color'] ?? $submitted[$prefix . 'h1_color'] ?? null) ?? $light,
            'dark' => self::normalize($submitted[$prefix . 'h1_dark_color'] ?? null) ?? self::normalize($settings[$prefix . 'h1_dark_color'] ?? null) ?? '',
        ];
    }
}
