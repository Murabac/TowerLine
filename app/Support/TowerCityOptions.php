<?php

namespace App\Support;

use App\Models\District;

class TowerCityOptions
{
    /**
     * @return list<string>
     */
    public static function forDistrict(?District $district): array
    {
        if (! $district) {
            return [];
        }

        $cities = [];

        foreach (TowerGeographyResolver::townDistrictMap() as $town => $districtName) {
            if (self::matchesDistrict($district->name, $districtName)) {
                $cities[] = $town;
            }
        }

        if (! in_array($district->name, $cities, true)) {
            array_unshift($cities, $district->name);
        }

        sort($cities);

        return array_values(array_unique($cities));
    }

    private static function matchesDistrict(string $districtName, string $mappedName): bool
    {
        return strcasecmp($districtName, $mappedName) === 0;
    }
}
