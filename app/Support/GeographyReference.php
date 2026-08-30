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
