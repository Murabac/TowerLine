<?php

namespace App\Support;

class TowerLandArea
{
    /** @var array<string, string> */
    public const PRESETS = [
        '15x15' => '15 × 15 m',
        '20x20' => '20 × 20 m',
        '25x30' => '25 × 30 m',
    ];

    public static function label(?string $value): string
    {
        if (! $value) {
            return '—';
        }

        foreach (self::PRESETS as $preset => $label) {
            if ($value === $label || $value === $preset) {
                return $label;
            }
        }

        return $value;
    }

    public static function resolve(?string $preset, ?string $custom): ?string
    {
        if ($preset === 'custom') {
            $custom = trim((string) $custom);

            return $custom !== '' ? $custom : null;
        }

        if ($preset && isset(self::PRESETS[$preset])) {
            return self::PRESETS[$preset];
        }

        $custom = trim((string) $custom);

        return $custom !== '' ? $custom : null;
    }

    public static function presetKeyForValue(?string $value): string
    {
        if (! $value) {
            return '';
        }

        foreach (self::PRESETS as $preset => $label) {
            if ($value === $label) {
                return $preset;
            }
        }

        return 'custom';
    }
}
