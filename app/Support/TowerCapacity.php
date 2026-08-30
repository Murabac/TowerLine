<?php

namespace App\Support;

class TowerCapacity
{
    /** @var list<string> */
    public const OPTIONS = [
        '3g',
        '4g',
        '5g',
        'tv',
    ];

    public static function label(?string $key): string
    {
        if (! $key || ! in_array($key, self::OPTIONS, true)) {
            return '—';
        }

        return __('app.towers.capacities.'.$key);
    }

    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        return in_array($value, self::OPTIONS, true) ? $value : null;
    }
}
