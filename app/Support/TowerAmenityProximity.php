<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TowerAmenityProximity
{
    private const OVERPASS_URL = 'https://overpass-api.de/api/interpreter';

    private const SEARCH_RADIUS_M = 5000;

    /**
     * @return array{
     *     school: ?array{distance_m: int, name: ?string},
     *     hospital: ?array{distance_m: int, name: ?string},
     *     house: ?array{distance_m: int, name: ?string}
     * }
     */
    public static function detect(float $latitude, float $longitude): array
    {
        try {
            $elements = self::fetchElements($latitude, $longitude);
        } catch (\Throwable $exception) {
            Log::warning('Tower amenity lookup failed', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'message' => $exception->getMessage(),
            ]);

            return [
                'school' => null,
                'hospital' => null,
                'house' => null,
            ];
        }

        return [
            'school' => self::nearestMatch($latitude, $longitude, $elements, ['school', 'college', 'university']),
            'hospital' => self::nearestMatch($latitude, $longitude, $elements, ['hospital', 'clinic', 'doctors']),
            'house' => self::nearestMatch($latitude, $longitude, $elements, ['house', 'residential', 'detached', 'terrace']),
        ];
    }

    /**
     * @return list<array{lat: float, lon: float, tags: array<string, string>}>
     */
    private static function fetchElements(float $latitude, float $longitude): array
    {
        $radius = self::SEARCH_RADIUS_M;
        $query = <<<QUERY
            [out:json][timeout:25];
            (
              node["amenity"~"^(school|college|university|hospital|clinic|doctors)\$"](around:{$radius},{$latitude},{$longitude});
              way["amenity"~"^(school|college|university|hospital|clinic|doctors)\$"](around:{$radius},{$latitude},{$longitude});
              node["building"~"^(house|residential|detached|terrace)\$"](around:1000,{$latitude},{$longitude});
              way["building"~"^(house|residential|detached|terrace)\$"](around:1000,{$latitude},{$longitude});
            );
            out center tags;
            QUERY;

        $response = Http::timeout(12)
            ->asForm()
            ->post(self::OVERPASS_URL, ['data' => $query]);

        if (! $response->successful()) {
            throw new \RuntimeException('Overpass API returned '.$response->status());
        }

        $elements = [];

        foreach ($response->json('elements', []) as $element) {
            $lat = $element['lat'] ?? $element['center']['lat'] ?? null;
            $lon = $element['lon'] ?? $element['center']['lon'] ?? null;

            if ($lat === null || $lon === null) {
                continue;
            }

            $elements[] = [
                'lat' => (float) $lat,
                'lon' => (float) $lon,
                'tags' => $element['tags'] ?? [],
            ];
        }

        return $elements;
    }

    /**
     * @param  list<array{lat: float, lon: float, tags: array<string, string>}>  $elements
     * @param  list<string>  $kinds
     * @return array{distance_m: int, name: ?string}|null
     */
    private static function nearestMatch(float $latitude, float $longitude, array $elements, array $kinds): ?array
    {
        $nearest = null;

        foreach ($elements as $element) {
            if (! self::matchesKind($element['tags'], $kinds)) {
                continue;
            }

            $distance = (int) round(TowerProximity::haversineMeters(
                $latitude,
                $longitude,
                $element['lat'],
                $element['lon'],
            ));

            if ($nearest === null || $distance < $nearest['distance_m']) {
                $nearest = [
                    'distance_m' => $distance,
                    'name' => self::labelFromTags($element['tags']),
                ];
            }
        }

        return $nearest;
    }

    /**
     * @param  array<string, string>  $tags
     */
    private static function labelFromTags(array $tags): ?string
    {
        foreach (['name', 'name:en', 'brand', 'operator'] as $key) {
            $value = trim((string) ($tags[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $tags
     * @param  list<string>  $kinds
     */
    private static function matchesKind(array $tags, array $kinds): bool
    {
        $amenity = $tags['amenity'] ?? null;
        $building = $tags['building'] ?? null;

        foreach ($kinds as $kind) {
            if ($amenity === $kind || $building === $kind) {
                return true;
            }
        }

        return false;
    }
}
