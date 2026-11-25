<?php

namespace App\Support;

use App\Models\District;
use App\Models\SubDistrict;

class GeographyReference
{
    private static ?array $data = null;

    public static function reset(): void
    {
        self::$data = null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function data(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        $path = database_path('data/somaliland-sub-districts-interim.json');

        if (! is_file($path)) {
            return self::$data = ['sub_districts' => []];
        }

        return self::$data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{latitude: float, longitude: float, zoom_pad: float}|null
     */
    public static function centroidFromJson(string $districtName, string $subDistrictName): ?array
    {
        foreach (self::data()['sub_districts'][$districtName] ?? [] as $row) {
            if ($row['name'] !== $subDistrictName) {
                continue;
            }

            if (! isset($row['latitude'], $row['longitude'])) {
                return null;
            }

            return [
                'latitude' => (float) $row['latitude'],
                'longitude' => (float) $row['longitude'],
                'zoom_pad' => (float) ($row['zoom_pad'] ?? self::defaultZoomPad()),
            ];
        }

        return null;
    }

    /**
     * @return array{south: float, west: float, north: float, east: float}|null
     */
    public static function boundsForSubDistrict(SubDistrict $subDistrict): ?array
    {
        $bounds = $subDistrict->mapBounds();

        if ($bounds) {
            return $bounds;
        }

        $district = $subDistrict->relationLoaded('district')
            ? $subDistrict->district
            : $subDistrict->district()->first();

        if (! $district) {
            return null;
        }

        $centroid = self::centroidFromJson($district->name, $subDistrict->name);

        if (! $centroid) {
            return null;
        }

        return self::boundsAroundPoint($centroid['latitude'], $centroid['longitude'], $centroid['zoom_pad']);
    }

    public static function nearestSubDistrictId(District $district, float $latitude, float $longitude): ?int
    {
        $subDistricts = $district->subDistricts()->get();

        if ($subDistricts->isEmpty()) {
            return null;
        }

        if ($subDistricts->count() === 1) {
            return $subDistricts->first()->id;
        }

        foreach ($subDistricts as $subDistrict) {
            if ($subDistrict->containsPoint($latitude, $longitude)) {
                return $subDistrict->id;
            }
        }

        $nearestId = null;
        $nearestDistance = PHP_FLOAT_MAX;

        foreach ($subDistricts as $subDistrict) {
            $center = self::centerForSubDistrict($subDistrict);

            if (! $center) {
                continue;
            }

            $distance = self::distanceSquared(
                $latitude,
                $longitude,
                $center['latitude'],
                $center['longitude'],
            );

            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestId = $subDistrict->id;
            }
        }

        return $nearestId ?? $subDistricts->first()->id;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    public static function centerForSubDistrict(SubDistrict $subDistrict): ?array
    {
        if ($subDistrict->latitude !== null && $subDistrict->longitude !== null) {
            return [
                'latitude' => (float) $subDistrict->latitude,
                'longitude' => (float) $subDistrict->longitude,
            ];
        }

        $bounds = $subDistrict->mapBounds();

        if ($bounds) {
            return [
                'latitude' => ($bounds['south'] + $bounds['north']) / 2,
                'longitude' => ($bounds['west'] + $bounds['east']) / 2,
            ];
        }

        $district = $subDistrict->relationLoaded('district')
            ? $subDistrict->district
            : $subDistrict->district()->first();

        if (! $district) {
            return null;
        }

        $centroid = self::centroidFromJson($district->name, $subDistrict->name);

        if (! $centroid) {
            return null;
        }

        return [
            'latitude' => $centroid['latitude'],
            'longitude' => $centroid['longitude'],
        ];
    }

    /**
     * Approximate outline boxes of the six regions, only used to zoom map pickers.
     */
    private const REGION_BOUNDS = [
        'awdal' => ['south' => 9.70, 'west' => 42.65, 'north' => 11.45, 'east' => 43.75],
        'maroodi jeex' => ['south' => 8.95, 'west' => 43.35, 'north' => 10.05, 'east' => 44.75],
        'sahil' => ['south' => 9.75, 'west' => 43.75, 'north' => 10.95, 'east' => 45.35],
        'togdheer' => ['south' => 8.40, 'west' => 44.40, 'north' => 10.10, 'east' => 46.40],
        'sanaag' => ['south' => 9.70, 'west' => 46.00, 'north' => 11.35, 'east' => 48.95],
        'sool' => ['south' => 8.00, 'west' => 46.10, 'north' => 10.10, 'east' => 48.20],
    ];

    /**
     * @return array{south: float, west: float, north: float, east: float}|null
     */
    public static function regionBounds(?string $regionName): ?array
    {
        return self::REGION_BOUNDS[strtolower(trim((string) $regionName))] ?? null;
    }

    /**
     * @param  iterable<\App\Models\Region>  $regions
     * @return array<string, array{south: float, west: float, north: float, east: float}|null>
     */
    public static function regionBoundsById(iterable $regions): array
    {
        $bounds = [];

        foreach ($regions as $region) {
            $bounds[(string) $region->id] = self::regionBounds($region->name_en);
        }

        return $bounds;
    }

    /**
     * @param  iterable<array{south: float, west: float, north: float, east: float}|null>  $boundsList
     * @return array{south: float, west: float, north: float, east: float}|null
     */
    public static function unionBounds(iterable $boundsList): ?array
    {
        $south = $west = $north = $east = null;

        foreach ($boundsList as $bounds) {
            if (! is_array($bounds)
                || ! isset($bounds['south'], $bounds['west'], $bounds['north'], $bounds['east'])) {
                continue;
            }

            $south = $south === null ? (float) $bounds['south'] : min($south, (float) $bounds['south']);
            $west = $west === null ? (float) $bounds['west'] : min($west, (float) $bounds['west']);
            $north = $north === null ? (float) $bounds['north'] : max($north, (float) $bounds['north']);
            $east = $east === null ? (float) $bounds['east'] : max($east, (float) $bounds['east']);
        }

        if ($south === null || $west === null || $north === null || $east === null) {
            return null;
        }

        return [
            'south' => $south,
            'west' => $west,
            'north' => $north,
            'east' => $east,
        ];
    }

    /**
     * @return array{south: float, west: float, north: float, east: float}
     */
    public static function boundsAroundPoint(float $latitude, float $longitude, ?float $pad = null): array
    {
        $pad ??= self::defaultZoomPad();

        return [
            'south' => $latitude - $pad,
            'west' => $longitude - $pad,
            'north' => $latitude + $pad,
            'east' => $longitude + $pad,
        ];
    }

    /**
     * @param  array{latitude?: float|null, longitude?: float|null, bounds_south?: float|null, bounds_west?: float|null, bounds_north?: float|null, bounds_east?: float|null}  $row
     */
    public static function applySeedArea(SubDistrict $subDistrict, array $row): void
    {
        if (isset($row['bounds_south'], $row['bounds_west'], $row['bounds_north'], $row['bounds_east'])) {
            $subDistrict->applyMapBounds([
                'south' => (float) $row['bounds_south'],
                'west' => (float) $row['bounds_west'],
                'north' => (float) $row['bounds_north'],
                'east' => (float) $row['bounds_east'],
            ]);

            return;
        }

        if (! isset($row['latitude'], $row['longitude'])) {
            return;
        }

        $bounds = self::boundsAroundPoint(
            (float) $row['latitude'],
            (float) $row['longitude'],
            isset($row['zoom_pad']) ? (float) $row['zoom_pad'] : null,
        );

        $subDistrict->applyMapBounds($bounds);
    }

    private static function defaultZoomPad(): float
    {
        return (float) (self::data()['_meta']['default_zoom_pad'] ?? 0.012);
    }

    private static function distanceSquared(float $latA, float $lngA, float $latB, float $lngB): float
    {
        return ($latA - $latB) ** 2 + ($lngA - $lngB) ** 2;
    }
}
