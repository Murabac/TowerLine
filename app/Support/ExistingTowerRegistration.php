<?php

namespace App\Support;

use App\Models\ApprovalRequest;
use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\SubDistrict;
use App\Models\Tower;
use App\Models\User;

/**
 * Quick registration of towers that already stand.
 *
 * Staff submit operator, location, type and approximate height. The submission waits in the
 * approval queue (type tower_existing) until the Director of Telecom confirms it; only then
 * does a live tower exist. Everything else on the tower can be completed later.
 */
class ExistingTowerRegistration
{
    /** Anything closer than this is probably the same tower. */
    public const DUPLICATE_RADIUS_METERS = 50;

    public const TYPES = ['guyed', 'monopole', 'rooftop'];

    /** Quick-pick heights in metres. */
    public const HEIGHT_PRESETS = [15, 30, 45, 60];

    /**
     * @param  array<string, mixed>  $attributes  validated quick-form fields
     */
    public static function submit(User $user, array $attributes): ApprovalRequest
    {
        $attributes = self::normalise($attributes);

        return ApprovalRequest::query()->create([
            'type' => ApprovalRequest::TYPE_TOWER_EXISTING,
            'tower_id' => null,
            'payload' => [
                'attributes' => $attributes,
                'summary' => self::summary($attributes),
            ],
            'submitted_by' => $user->id,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function amend(ApprovalRequest $approval, array $attributes): ApprovalRequest
    {
        $attributes = self::normalise($attributes);
        $payload = $approval->payload ?? [];
        $payload['attributes'] = $attributes;
        $payload['summary'] = self::summary($attributes);

        $approval->update(['payload' => $payload]);

        return $approval->fresh() ?? $approval;
    }

    /**
     * Turn a confirmed registration into a live tower.
     */
    public static function apply(ApprovalRequest $approval): Tower
    {
        $attributes = $approval->attributes();
        $regionId = (int) $attributes['region_id'];
        $districtId = isset($attributes['district_id']) ? (int) $attributes['district_id'] : null;
        $latitude = (float) $attributes['latitude'];
        $longitude = (float) $attributes['longitude'];

        $district = $districtId ? District::query()->find($districtId) : null;
        $subDistrictId = null;

        if ($district) {
            $subDistrictId = GeographyReference::nearestSubDistrictId($district, $latitude, $longitude);
        } elseif ($subDistrict = self::subDistrictAt($regionId, $latitude, $longitude)) {
            // District left blank on the form: take it from the GPS point.
            $subDistrictId = $subDistrict->id;
            $district = $subDistrict->district;
            $districtId = $district?->id;
        }
        $city = $district?->name ?? Region::query()->find($regionId)?->name_en ?? '';
        $siteName = trim((string) ($attributes['site_name'] ?? ''));

        $tower = Tower::query()->create([
            'name' => $siteName !== ''
                ? $siteName
                : TowerNameGenerator::generate(
                    operatorId: (int) $attributes['operator_id'],
                    regionId: $regionId,
                    city: $city,
                    districtId: $districtId,
                    subDistrictId: $subDistrictId,
                ),
            'operator_id' => (int) $attributes['operator_id'],
            'region_id' => $regionId,
            'district_id' => $districtId,
            'sub_district_id' => $subDistrictId,
            'city' => $city,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'type' => $attributes['type'],
            'height_m' => (float) $attributes['height_m'],
            'capacity' => null,
            'signal_radius_m' => TowerSignalRadius::defaultForCapacity(null),
            'status' => 'active',
        ]);

        Audits::log('created', $tower, $tower->only(['name', 'region_id', 'district_id', 'sub_district_id', 'operator_id', 'status']) + [
            'source' => 'existing_tower_registration',
        ]);

        return $tower->fresh() ?? $tower;
    }

    /**
     * Live towers and other pending registrations within the duplicate radius.
     *
     * @return list<array{name: string, operator: string, distance_m: int, pending: bool, url: ?string}>
     */
    public static function possibleDuplicates(float $latitude, float $longitude, ?int $exceptApprovalId = null): array
    {
        $matches = [];

        foreach (TowerProximity::nearbyAt($latitude, $longitude, null, self::DUPLICATE_RADIUS_METERS) as $item) {
            $matches[] = [
                'name' => $item['tower']->name,
                'operator' => $item['tower']->operator?->name ?? '—',
                'distance_m' => (int) round($item['distance_m']),
                'pending' => false,
                'url' => route('towers.show', $item['tower']),
            ];
        }

        $pending = ApprovalRequest::query()
            ->pending()
            ->where('type', ApprovalRequest::TYPE_TOWER_EXISTING)
            ->when($exceptApprovalId, fn ($query) => $query->whereKeyNot($exceptApprovalId))
            ->get();

        $operators = Operator::query()->pluck('name', 'id');

        foreach ($pending as $approval) {
            $attributes = $approval->attributes();

            if (! isset($attributes['latitude'], $attributes['longitude'])) {
                continue;
            }

            $distance = TowerProximity::haversineMeters($latitude, $longitude, (float) $attributes['latitude'], (float) $attributes['longitude']);

            if ($distance <= self::DUPLICATE_RADIUS_METERS) {
                $matches[] = [
                    'name' => $approval->summary(),
                    'operator' => $operators[$attributes['operator_id'] ?? 0] ?? '—',
                    'distance_m' => (int) round($distance),
                    'pending' => true,
                    'url' => null,
                ];
            }
        }

        usort($matches, fn ($a, $b) => $a['distance_m'] <=> $b['distance_m']);

        return $matches;
    }

    /**
     * Labelled values for the review page.
     *
     * @return array<string, string>
     */
    public static function details(ApprovalRequest $approval): array
    {
        $attributes = $approval->attributes();
        $district = isset($attributes['district_id']) ? District::query()->find($attributes['district_id']) : null;

        return [
            __('app.existing_towers.operator') => Operator::query()->find($attributes['operator_id'] ?? 0)?->name ?? '—',
            __('app.towers.region') => Region::query()->find($attributes['region_id'] ?? 0)?->localizedName() ?? '—',
            __('app.towers.district') => $district?->name ?? __('app.existing_towers.district_from_location'),
            __('app.existing_towers.location') => isset($attributes['latitude'], $attributes['longitude'])
                ? number_format((float) $attributes['latitude'], 6).', '.number_format((float) $attributes['longitude'], 6)
                : '—',
            __('app.existing_towers.type') => isset($attributes['type']) ? __('app.status.'.$attributes['type']) : '—',
            __('app.existing_towers.height') => isset($attributes['height_m']) ? rtrim(rtrim(number_format((float) $attributes['height_m'], 1), '0'), '.').' m' : '—',
            __('app.existing_towers.site_name') => trim((string) ($attributes['site_name'] ?? '')) ?: __('app.existing_towers.name_auto'),
        ];
    }

    /**
     * The sub-district in this region whose boundary contains the point, or else the one
     * whose centre is nearest.
     */
    public static function subDistrictAt(int $regionId, float $latitude, float $longitude): ?SubDistrict
    {
        $subDistricts = SubDistrict::query()
            ->with('district')
            ->whereHas('district', fn ($query) => $query->where('region_id', $regionId))
            ->get();

        foreach ($subDistricts as $subDistrict) {
            if ($subDistrict->containsPoint($latitude, $longitude)) {
                return $subDistrict;
            }
        }

        $nearest = null;
        $nearestDistance = PHP_FLOAT_MAX;

        foreach ($subDistricts as $subDistrict) {
            $center = GeographyReference::centerForSubDistrict($subDistrict);

            if (! $center) {
                continue;
            }

            $distance = TowerProximity::haversineMeters($latitude, $longitude, $center['latitude'], $center['longitude']);

            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearest = $subDistrict;
            }
        }

        return $nearest;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private static function normalise(array $attributes): array
    {
        return [
            'operator_id' => (int) $attributes['operator_id'],
            'region_id' => (int) $attributes['region_id'],
            'district_id' => ! empty($attributes['district_id']) ? (int) $attributes['district_id'] : null,
            'latitude' => round((float) $attributes['latitude'], 7),
            'longitude' => round((float) $attributes['longitude'], 7),
            'type' => (string) $attributes['type'],
            'height_m' => round((float) $attributes['height_m'], 1),
            'site_name' => trim((string) ($attributes['site_name'] ?? '')) ?: null,
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private static function summary(array $attributes): string
    {
        if (! empty($attributes['site_name'])) {
            return (string) $attributes['site_name'];
        }

        $operator = Operator::query()->find($attributes['operator_id'])?->name ?? '';
        $place = ! empty($attributes['district_id'])
            ? District::query()->find($attributes['district_id'])?->name
            : Region::query()->find($attributes['region_id'])?->name_en;

        return trim($operator.' · '.$place, ' ·');
    }
}
