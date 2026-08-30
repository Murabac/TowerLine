<?php

namespace App\Support;

class TowerSignalRadius
{
    /** @var array<string, int> */
    public const BY_CAPACITY = [
        '3g' => 5000,
        '4g' => 8000,
        '5g' => 3000,
        'tv' => 30000,
    ];

    public static function defaultForCapacity(?string $capacity): int
    {
        $capacity = TowerCapacity::normalize($capacity);

        return self::BY_CAPACITY[$capacity] ?? 10000;
    }
}
