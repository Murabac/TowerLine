<?php

namespace App\Support;

use App\Models\Tower;
use Illuminate\Support\Collection;

class TowerProximity
{
    /** Nearby towers within this radius are listed on the registration form. */
    public const NEARBY_RADIUS_METERS = 1000;

    public static function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6_371_000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;

        return 2 * $earthRadius * asin(min(1, sqrt($a)));
    }

    /**
     * @return Collection<int, array{tower: Tower, distance_m: int}>
     */
    public static function nearby(Tower $tower, int $radiusMeters = self::NEARBY_RADIUS_METERS): Collection
    {
        if ($tower->latitude === null || $tower->longitude === null) {
            return collect();
        }

        return self::nearbyAt(
            (float) $tower->latitude,
            (float) $tower->longitude,
            $tower->exists ? $tower->id : null,
            $radiusMeters,
        );
    }

    /**
     * @param  callable(\Illuminate\Database\Eloquent\Builder): void|null  $scopeQuery
     * @return Collection<int, array{tower: Tower, distance_m: int}>
     */
    public static function nearbyAt(
        float $lat,
        float $lng,
        ?int $exceptTowerId = null,
        int $radiusMeters = self::NEARBY_RADIUS_METERS,
        ?callable $scopeQuery = null,
    ): Collection {
        $latDelta = $radiusMeters / 111_320;
        $lngDelta = $radiusMeters / (111_320 * max(cos(deg2rad($lat)), 0.01));

        $query = Tower::query()
            ->with('operator')
            ->when($exceptTowerId, fn ($builder) => $builder->whereKeyNot($exceptTowerId))
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);

        if ($scopeQuery) {
            $scopeQuery($query);
        }

        return $query
            ->get()
            ->map(function (Tower $candidate) use ($lat, $lng) {
                $distanceM = (int) round(self::haversineMeters(
                    $lat,
                    $lng,
                    (float) $candidate->latitude,
                    (float) $candidate->longitude,
                ));

                return [
                    'tower' => $candidate,
                    'distance_m' => $distanceM,
                ];
            })
            ->filter(fn (array $item) => $item['distance_m'] <= $radiusMeters)
            ->sortBy('distance_m')
            ->values();
    }

    /**
     * @param  Collection<int, array{tower: Tower, distance_m: int}>  $nearby
     */
    public static function formatSummary(Collection $nearby, int $radiusMeters = self::NEARBY_RADIUS_METERS): ?string
    {
        if ($nearby->isEmpty()) {
            return __('app.towers.nearby.none', ['radius' => number_format($radiusMeters)]);
        }

        return $nearby
            ->map(fn (array $item) => __('app.towers.nearby.entry', [
                'operator' => $item['tower']->operator->name,
                'name' => $item['tower']->name,
                'distance' => number_format($item['distance_m']),
            ]))
            ->implode('; ');
    }

    public static function refreshStoredSummary(Tower $tower): void
    {
        $nearby = self::nearby($tower);

        $tower->forceFill([
            'other_towers_nearby' => self::formatSummary($nearby),
        ])->saveQuietly();
    }

    public static function refreshAffectedTowers(Tower $tower): void
    {
        $neighborIds = self::nearby($tower)->pluck('tower.id');

        self::refreshStoredSummary($tower);

        Tower::query()
            ->whereIn('id', $neighborIds)
            ->each(fn (Tower $neighbor) => self::refreshStoredSummary($neighbor));
    }

    /**
     * @param  list<int>  $towerIds
     */
    public static function refreshMany(array $towerIds): void
    {
        Tower::query()
            ->whereIn('id', $towerIds)
            ->each(fn (Tower $tower) => self::refreshStoredSummary($tower));
    }
}
