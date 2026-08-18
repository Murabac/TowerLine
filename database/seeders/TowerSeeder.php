<?php

namespace Database\Seeders;

use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use Illuminate\Database\Seeder;

class TowerSeeder extends Seeder
{
    public function run(): void
    {
        Tower::query()->delete();

        $telecom = Operator::query()->where('category', 'telecom')->orderBy('id')->get();
        $broadcast = Operator::query()->where('category', 'broadcast')->orderBy('id')->get();
        $types = ['guyed', 'monopole', 'rooftop'];

        $sites = [
            'Awdal' => [
                'anchor' => [9.9360, 43.1820],
                'towns' => ['Borama', 'Zeila', 'Baki', 'Lughaya'],
            ],
            'Maroodi Jeex' => [
                'anchor' => [9.5620, 44.0770],
                'towns' => ['Hargeisa', 'Gabiley', 'Arabsiyo', 'Salahley'],
            ],
            'Sahil' => [
                'anchor' => [10.4340, 45.0140],
                'towns' => ['Berbera', 'Sheikh', 'Bulhar', 'Mandheera'],
            ],
            'Togdheer' => [
                'anchor' => [9.5220, 45.5340],
                'towns' => ['Burao', 'Oodweyne', 'Buuhoodle', 'Duruqsi'],
            ],
            'Sanaag' => [
                'anchor' => [10.6160, 47.3680],
                'towns' => ['Erigavo', 'Ceel Afweyn', 'Las Qoray', 'Badhan'],
            ],
            'Sool' => [
                'anchor' => [8.4770, 47.3600],
                'towns' => ['Las Anod', 'Caynabo', 'Taleh', 'Xudun'],
            ],
        ];

        $n = 0;

        foreach ($sites as $regionName => $site) {
            $region = Region::query()->where('name_en', $regionName)->firstOrFail();

            foreach ($site['towns'] as $i => $town) {
                $operator = $telecom[$n % $telecom->count()];
                $status = 'active';

                if ($regionName === 'Maroodi Jeex' && $town === 'Salahley') {
                    $status = 'under_construction';
                }
                if ($regionName === 'Sool' && $town === 'Xudun') {
                    $status = 'decommissioned';
                }

                $lat = $site['anchor'][0] + (($i % 2) * 0.07) - 0.03;
                $lng = $site['anchor'][1] + ((int) ($i / 2) * 0.11) - 0.05;

                $this->createTower(
                    name: $operator->name.' '.$town,
                    lat: $lat,
                    lng: $lng,
                    regionId: $region->id,
                    operator: $operator,
                    type: $types[$n % 3],
                    height: 32 + ($i * 6),
                    capacity: '3G / 4G',
                    radius: 9000 + ($i * 2500),
                    status: $status,
                    commissioned: $status === 'under_construction' ? null : now()->subYears(3 - ($i % 3))->toDateString(),
                );

                $n++;
            }
        }

        $hargeisa = Region::query()->where('name_en', 'Maroodi Jeex')->firstOrFail();
        $awdal = Region::query()->where('name_en', 'Awdal')->firstOrFail();

        foreach ($broadcast->values() as $i => $operator) {
            $this->createTower(
                name: $operator->name.' Hargeisa',
                lat: 9.5620 + ($i * 0.018) - 0.018,
                lng: 44.0770 + ($i * 0.022) - 0.022,
                regionId: $hargeisa->id,
                operator: $operator,
                type: 'guyed',
                height: 48 + ($i * 4),
                capacity: 'TV / cable headend',
                radius: 32000 + ($i * 3000),
                status: 'active',
                commissioned: now()->subYears(8 - $i)->toDateString(),
            );

            $this->createTower(
                name: $operator->name.' Borama',
                lat: 9.9360 + ($i * 0.016) - 0.016,
                lng: 43.1820 + ($i * 0.020) - 0.020,
                regionId: $awdal->id,
                operator: $operator,
                type: 'rooftop',
                height: 28 + ($i * 3),
                capacity: 'TV / cable headend',
                radius: 18000,
                status: 'under_construction',
                commissioned: null,
            );
        }
    }

    private function createTower(
        string $name,
        float $lat,
        float $lng,
        int $regionId,
        Operator $operator,
        string $type,
        float $height,
        string $capacity,
        int $radius,
        string $status,
        ?string $commissioned,
    ): void {
        Tower::query()->create([
            'name' => $name,
            'latitude' => $lat,
            'longitude' => $lng,
            'region_id' => $regionId,
            'operator_id' => $operator->id,
            'type' => $type,
            'height_m' => $height,
            'capacity' => $capacity,
            'signal_radius_m' => $radius,
            'status' => $status,
            'health_status' => 'unknown',
            'commissioned_at' => $commissioned,
        ]);
    }
}
