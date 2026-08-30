<?php

namespace App\Support;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\SubDistrict;
use App\Models\Tower;

class TowerNameGenerator
{
    /**
     * Internal code format: "{OP3}-{LOC3}-{NNN}" e.g. "TEL-HAR-001".
     * OP3 / LOC3 are three-letter segments from operator and location labels.
     * Sequence increments per operator within the same location prefix.
     */
    public static function generate(
        int $operatorId,
        int $regionId,
        ?string $city = null,
        ?int $districtId = null,
        ?int $subDistrictId = null,
        ?int $exceptTowerId = null,
    ): string {
        $operator = Operator::query()->findOrFail($operatorId);
        $location = self::resolveLocation($city, $districtId, $subDistrictId, $regionId);
        $prefix = self::buildPrefix($operator->name, $location);

        if ($exceptTowerId) {
            $existing = Tower::query()->find($exceptTowerId);

            if ($existing && self::matchesPrefix($existing->name, $prefix)) {
                return $existing->name;
            }
        }

        return $prefix.self::nextSequenceSuffix($prefix, $operatorId, $exceptTowerId);
    }

    public static function previewPrefix(
        string $operatorName,
        ?string $city = null,
        ?string $districtName = null,
        ?string $subDistrictName = null,
        ?string $regionName = null,
    ): string {
        $location = trim((string) $city);

        if ($location === '') {
            $location = trim((string) ($subDistrictName ?: $districtName ?: $regionName ?: ''));
        }

        if ($location === '') {
            return '';
        }

        return self::buildPrefix($operatorName, $location).'###';
    }

    public static function generateForTower(Tower $tower): string
    {
        return self::generate(
            operatorId: $tower->operator_id,
            regionId: $tower->region_id,
            city: $tower->city,
            districtId: $tower->district_id,
            subDistrictId: $tower->sub_district_id,
            exceptTowerId: $tower->id,
        );
    }

    public static function locationLabel(
        ?string $city,
        ?int $districtId,
        ?int $subDistrictId,
        int $regionId,
    ): string {
        return self::resolveLocation($city, $districtId, $subDistrictId, $regionId);
    }

    public static function codeSegment(string $label): string
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z]/', '', $label) ?? '');

        if ($normalized === '') {
            return 'UNK';
        }

        return str_pad(substr($normalized, 0, 3), 3, 'X');
    }

    private static function buildPrefix(string $operatorName, string $location): string
    {
        return self::codeSegment($operatorName).'-'.self::codeSegment($location).'-';
    }

    private static function matchesPrefix(string $name, string $prefix): bool
    {
        return (bool) preg_match('/^'.preg_quote($prefix, '/').'\d{3}$/', $name);
    }

    private static function nextSequenceSuffix(string $prefix, int $operatorId, ?int $exceptTowerId): string
    {
        $used = Tower::query()
            ->where('operator_id', $operatorId)
            ->when($exceptTowerId, fn ($query) => $query->whereKeyNot($exceptTowerId))
            ->where('name', 'like', $prefix.'%')
            ->pluck('name')
            ->map(function (string $name) use ($prefix) {
                if (! preg_match('/^'.preg_quote($prefix, '/').'(\d{3})$/', $name, $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter()
            ->all();

        $next = $used === [] ? 1 : max($used) + 1;

        return str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    private static function resolveLocation(
        ?string $city,
        ?int $districtId,
        ?int $subDistrictId,
        int $regionId,
    ): string {
        $city = trim((string) $city);

        if ($city !== '') {
            return $city;
        }

        if ($subDistrictId) {
            $subDistrict = SubDistrict::query()->find($subDistrictId);

            if ($subDistrict?->name) {
                return $subDistrict->name;
            }
        }

        if ($districtId) {
            $district = District::query()->find($districtId);

            if ($district?->name) {
                return $district->name;
            }
        }

        return Region::query()->findOrFail($regionId)->localizedName();
    }
}
