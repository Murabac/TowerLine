<?php

namespace App\Support;

use App\Models\District;
use App\Models\SubDistrict;

class TowerGeographyResolver
{
    /** @var array<string, string> */
    private const TOWN_DISTRICT_MAP = [
        'Borama' => 'Borama',
        'Zeila' => 'Zeila',
        'Baki' => 'Baki',
        'Lughaya' => 'Lughaya',
        'Hargeisa' => 'Hargeisa',
        'Gabiley' => 'Gabiley',
        'Arabsiyo' => 'Arabsiyo',
        'Salahley' => 'Salahlay',
        'Berbera' => 'Berbera',
        'Sheikh' => 'Sheikh',
        'Bulhar' => 'Bulaxaar',
        'Mandheera' => 'Mandheera',
        'Burao' => 'Burao',
        'Oodweyne' => 'Odweyne',
        'Buuhoodle' => 'Buuhoodle',
        'Duruqsi' => 'Duruqsi',
        'Erigavo' => 'Erigavo',
        'Ceel Afweyn' => 'El Afweyn',
        'Las Qoray' => 'Las Khorey',
        'Badhan' => 'Badhan',
        'Las Anod' => 'Las Anod',
        'Caynabo' => 'Aynabo',
        'Taleh' => 'Taleh',
        'Xudun' => 'Hudun',
    ];

    /**
     * @return array{0: ?int, 1: ?int}
     */
    public function resolve(int $regionId, string $town, ?float $latitude = null, ?float $longitude = null): array
    {
        $districtName = self::TOWN_DISTRICT_MAP[$town] ?? $town;

        $district = District::query()
            ->where('region_id', $regionId)
            ->where('name', $districtName)
            ->first();

        if (! $district) {
            return [null, null];
        }

        if ($latitude !== null && $longitude !== null) {
            $subDistrictId = GeographyReference::nearestSubDistrictId($district, $latitude, $longitude);

            if ($subDistrictId !== null) {
                return [$district->id, $subDistrictId];
            }
        }

        $subDistrict = SubDistrict::query()
            ->where('district_id', $district->id)
            ->orderBy('id')
            ->first();

        return [$district->id, $subDistrict?->id];
    }

    public function townFromTowerName(string $name): ?string
    {
        $towns = array_keys(self::TOWN_DISTRICT_MAP);

        usort($towns, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($towns as $town) {
            if (str_ends_with($name, ' '.$town) || $name === $town) {
                return $town;
            }
        }

        return null;
    }
}
