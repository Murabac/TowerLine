<?php

namespace App\Support;

class TowerFenceDistance
{
    /** @var list<float> */
    public const PRESETS = [6, 8, 10];

    public static function resolve(?string $preset, mixed $custom): float
    {
        if ($preset === 'custom') {
            return max(0, (float) $custom);
        }

        if (is_numeric($preset)) {
            return (float) $preset;
        }

        return 6.0;
    }

    public static function presetKeyForValue(?float $value): string
    {
        if ($value === null) {
            return '6';
        }

        foreach (self::PRESETS as $preset) {
            if ((float) $value === (float) $preset) {
                return (string) $preset;
            }
        }

        return 'custom';
    }
}
