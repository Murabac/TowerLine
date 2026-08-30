<?php

namespace Database\Seeders;

use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Support\TowerGeographyResolver;
use Illuminate\Database\Seeder;

class TowerSeeder extends Seeder
{
    private TowerGeographyResolver $geography;

    public function __construct()
    {
        $this->geography = new TowerGeographyResolver;
    }

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
                $status = $this->randomStatus();
                $health = $this->randomHealth($status);

                $lat = $site['anchor'][0] + (($i % 2) * 0.07) - 0.03;
                $lng = $site['anchor'][1] + ((int) ($i / 2) * 0.11) - 0.05;
                [$districtId, $subDistrictId] = $this->geography->resolve($region->id, $town, $lat, $lng);

                $this->createTower(
                    name: $operator->name.' '.$town,
                    lat: $lat,
                    lng: $lng,
                    regionId: $region->id,
                    districtId: $districtId,
                    subDistrictId: $subDistrictId,
                    operator: $operator,
                    type: $types[$n % 3],
                    height: 32 + ($i * 6),
                    capacity: '3G / 4G',
                    radius: 9000 + ($i * 2500),
                    status: $status,
                    health: $health,
                    commissioned: $status === 'under_construction' ? null : now()->subYears(fake()->numberBetween(1, 8))->toDateString(),
                );

                $n++;
            }
        }

        $hargeisa = Region::query()->where('name_en', 'Maroodi Jeex')->firstOrFail();
        $awdal = Region::query()->where('name_en', 'Awdal')->firstOrFail();
        foreach ($broadcast->values() as $i => $operator) {
            $hargeisaLat = 9.5620 + ($i * 0.018) - 0.018;
            $hargeisaLng = 44.0770 + ($i * 0.022) - 0.022;
            [$hargeisaDistrict, $hargeisaSub] = $this->geography->resolve($hargeisa->id, 'Hargeisa', $hargeisaLat, $hargeisaLng);

            $boramaLat = 9.9360 + ($i * 0.016) - 0.016;
            $boramaLng = 43.1820 + ($i * 0.020) - 0.020;
            [$boramaDistrict, $boramaSub] = $this->geography->resolve($awdal->id, 'Borama', $boramaLat, $boramaLng);

            $this->createTower(
                name: $operator->name.' Hargeisa',
                lat: $hargeisaLat,
                lng: $hargeisaLng,
                regionId: $hargeisa->id,
                districtId: $hargeisaDistrict,
                subDistrictId: $hargeisaSub,
                operator: $operator,
                type: 'guyed',
                height: 48 + ($i * 4),
                capacity: 'TV / cable headend',
                radius: 32000 + ($i * 3000),
                status: 'active',
                health: $this->randomHealth('active'),
                commissioned: now()->subYears(8 - $i)->toDateString(),
            );

            $this->createTower(
                name: $operator->name.' Borama',
                lat: $boramaLat,
                lng: $boramaLng,
                regionId: $awdal->id,
                districtId: $boramaDistrict,
                subDistrictId: $boramaSub,
                operator: $operator,
                type: 'rooftop',
                height: 28 + ($i * 3),
                capacity: 'TV / cable headend',
                radius: 18000,
                status: 'under_construction',
                health: 'unknown',
                commissioned: null,
            );
        }
    }

    private function createTower(
        string $name,
        float $lat,
        float $lng,
        int $regionId,
        ?int $districtId,
        ?int $subDistrictId,
        Operator $operator,
        string $type,
        float $height,
        string $capacity,
        int $radius,
        string $status,
        string $health,
        ?string $commissioned,
    ): void {
        Tower::query()->create([
            'name' => $name,
            'latitude' => $lat,
            'longitude' => $lng,
            'region_id' => $regionId,
            'district_id' => $districtId,
            'sub_district_id' => $subDistrictId,
            'operator_id' => $operator->id,
            'type' => $type,
            'height_m' => $height,
            'capacity' => $capacity,
            'signal_radius_m' => $radius,
            'status' => $status,
            'health_status' => $health,
            'commissioned_at' => $commissioned,
        ]);
    }

    private function randomStatus(): string
    {
        return fake()->randomElement([
            'active',
            'active',
            'active',
            'active',
            'active',
            'active',
            'active',
            'under_construction',
            'under_construction',
            'decommissioned',
        ]);
    }

    private function randomHealth(string $status): string
    {
        if ($status !== 'active') {
            return 'unknown';
        }

        return fake()->randomElement([
            'good',
            'good',
            'good',
            'good',
            'good',
            'needs_attention',
            'needs_attention',
            'critical',
            'unknown',
        ]);
    }
}
