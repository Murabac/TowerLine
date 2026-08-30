<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\Region;
use App\Models\SubDistrict;
use App\Support\GeographyReference;
use Illuminate\Database\Seeder;

class GeographySeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/somaliland-geography-interim.json');

        if (! is_file($path)) {
            return;
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $researched = $this->loadResearchedSubDistricts();
        $template = $data['sub_district_placeholder_template'] ?? '{district} — Central';

        foreach ($data['regions'] as $regionName => $regionData) {
            $region = Region::query()->where('name_en', $regionName)->first();

            if (! $region) {
                continue;
            }

            foreach ($regionData['districts'] as $districtRow) {
                $district = District::query()->updateOrCreate(
                    [
                        'region_id' => $region->id,
                        'name' => $districtRow['name'],
                    ],
                    [
                        'grade' => $districtRow['grade'] ?? null,
                    ]
                );

                $this->seedSubDistricts($district, $researched, $template);
            }
        }
    }

    /**
     * @return array<string, list<array{name: string}>>
     */
    private function loadResearchedSubDistricts(): array
    {
        $path = database_path('data/somaliland-sub-districts-interim.json');

        if (! is_file($path)) {
            return [];
        }

        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $data['sub_districts'] ?? [];
    }

    /**
     * @param  array<string, list<array{name: string}>>  $researched
     */
    private function seedSubDistricts(District $district, array $researched, string $template): void
    {
        $placeholder = str_replace('{district}', $district->name, $template);

        if (isset($researched[$district->name])) {
            SubDistrict::query()
                ->where('district_id', $district->id)
                ->where('name', $placeholder)
                ->delete();

            foreach ($researched[$district->name] as $subRow) {
                $subDistrict = SubDistrict::query()->updateOrCreate(
                    [
                        'district_id' => $district->id,
                        'name' => $subRow['name'],
                    ],
                    []
                );

                if (! $subDistrict->hasMapArea()) {
                    GeographyReference::applySeedArea($subDistrict, $subRow);
                    $subDistrict->save();
                }
            }

            return;
        }

        SubDistrict::query()->updateOrCreate(
            [
                'district_id' => $district->id,
                'name' => $placeholder,
            ],
            []
        );
    }
}
