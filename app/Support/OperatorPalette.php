<?php

namespace App\Support;

class OperatorPalette
{
    /**
     * Color-blind-safe qualitative hues (Okabe–Ito, then Paul Tol).
     * Yellow is omitted because it disappears on light map tiles.
     *
     * @var list<string>
     */
    public const COLORS = [
        '#009E73',
        '#0072B2',
        '#CC79A7',
        '#D55E00',
        '#56B4E9',
        '#E69F00',
        '#332288',
        '#117733',
        '#AA4499',
        '#44AA99',
        '#882255',
        '#88CCEE',
    ];

    /**
     * Fixed map colors for seeded demo operators.
     *
     * @var array<string, string>
     */
    public const NAMED = [
        'Telesom' => '#009E73',
        'Somtel' => '#0072B2',
        'Sogasho' => '#CC79A7',
        'Truecable' => '#D55E00',
        'Astaan' => '#56B4E9',
        'Horncable' => '#E69F00',
    ];

    public static function normalize(string $color): string
    {
        $color = trim($color);

        if ($color === '') {
            return self::COLORS[0];
        }

        if (! str_starts_with($color, '#')) {
            $color = '#'.$color;
        }

        return strtoupper($color);
    }

    public static function namedColor(string $name): ?string
    {
        foreach (self::NAMED as $named => $color) {
            if (strcasecmp($named, trim($name)) === 0) {
                return self::normalize($color);
            }
        }

        return null;
    }

    public static function forName(string $name): string
    {
        return self::namedColor($name) ?? self::COLORS[0];
    }

    /**
     * @param  list<string>  $used
     */
    public static function nextAvailable(array $used): string
    {
        $usedNorm = array_map(fn (string $color) => self::normalize($color), $used);

        foreach (self::COLORS as $color) {
            if (! in_array(self::normalize($color), $usedNorm, true)) {
                return self::normalize($color);
            }
        }

        $index = count($used);

        return sprintf(
            '#%02X%02X%02X',
            ($index * 67) % 180 + 40,
            ($index * 37) % 180 + 40,
            ($index * 97) % 180 + 40,
        );
    }

    /**
     * @param  list<string>  $used
     */
    public static function colorFor(string $name, array $used = []): string
    {
        $named = self::namedColor($name);
        $usedNorm = array_map(fn (string $color) => self::normalize($color), $used);

        if ($named && ! in_array($named, $usedNorm, true)) {
            return $named;
        }

        return self::nextAvailable($used);
    }

    /**
     * @param  iterable<int, object|array{id: int, name: string}>  $operators
     * @return array<int, string>
     */
    public static function uniqueColorsFor(iterable $operators): array
    {
        $list = [];

        foreach ($operators as $operator) {
            $list[] = [
                'id' => (int) (is_array($operator) ? $operator['id'] : $operator->id),
                'name' => (string) (is_array($operator) ? $operator['name'] : $operator->name),
            ];
        }

        $assigned = [];
        $used = [];

        foreach ($list as $operator) {
            $named = self::namedColor($operator['name']);

            if ($named && ! in_array($named, $used, true)) {
                $assigned[$operator['id']] = $named;
                $used[] = $named;
            }
        }

        foreach ($list as $operator) {
            if (isset($assigned[$operator['id']])) {
                continue;
            }

            $color = self::nextAvailable($used);
            $assigned[$operator['id']] = $color;
            $used[] = $color;
        }

        return $assigned;
    }
}
