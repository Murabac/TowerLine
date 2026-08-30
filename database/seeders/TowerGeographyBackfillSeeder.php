<?php

namespace Database\Seeders;

use App\Models\Tower;
use App\Support\TowerGeographyResolver;
use Illuminate\Database\Seeder;

class TowerGeographyBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $resolver = new TowerGeographyResolver;

        Tower::query()->each(function (Tower $tower) use ($resolver) {
            $town = $resolver->townFromTowerName($tower->name);

            if (! $town) {
                return;
            }

            [$districtId, $subDistrictId] = $resolver->resolve(
                $tower->region_id,
                $town,
                $tower->latitude,
                $tower->longitude,
            );

            if ($districtId === null) {
                return;
            }

            $tower->update([
                'district_id' => $districtId,
                'sub_district_id' => $subDistrictId,
            ]);
        });
    }
}
