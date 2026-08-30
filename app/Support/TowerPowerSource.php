<?php

namespace App\Support;

class TowerPowerSource
{
    /** @var list<string> */
    public const OPTIONS = [
        'grid',
        'generator',
        'battery',
        'solar',
    ];

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_map(
            fn (string $key) => __('app.towers.power_sources.'.$key),
            array_combine(self::OPTIONS, self::OPTIONS),
        );
    }

    public static function label(string $key): string
    {
        return __('app.towers.power_sources.'.$key);
    }

    /**
     * @param  list<string>|null  $sources
     */
    public static function labelList(?array $sources): string
    {
        if (! $sources) {
            return '—';
        }

        return collect($sources)
            ->map(fn (string $source) => self::label($source))
            ->join(', ');
    }

    /**
     * @param  mixed  $value
     * @return list<string>
     */
    public static function normalize(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $allowed = array_flip(self::OPTIONS);

        return array_values(array_filter(
            $value,
            fn (mixed $source) => is_string($source) && isset($allowed[$source]),
        ));
    }

}