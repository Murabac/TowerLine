<?php

namespace Database\Seeders;

use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Support\TowerFenceDistance;
use App\Support\TowerGeographyResolver;
use App\Support\TowerLandArea;
use App\Support\TowerNameGenerator;
use App\Support\TowerSignalRadius;
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
                $capacity = fake()->randomElement(['3g', '4g', '4g', '5g']);

                $lat = $site['anchor'][0] + (($i % 2) * 0.07) - 0.03;
                $lng = $site['anchor'][1] + ((int) ($i / 2) * 0.11) - 0.05;
                [$districtId, $subDistrictId] = $this->geography->resolve($region->id, $town, $lat, $lng);
                $commissioned = $status === 'under_construction' ? null : now()->subYears(fake()->numberBetween(1, 8))->toDateString();

                $this->createTower(
                    city: $town,
                    lat: $lat,
                    lng: $lng,
                    regionId: $region->id,
                    districtId: $districtId,
                    subDistrictId: $subDistrictId,
                    operator: $operator,
                    type: $types[$n % 3],
                    height: 32 + ($i * 6),
                    capacity: $capacity,
                    status: $status,
                    health: $health,
                    commissioned: $commissioned,
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
                city: 'Hargeisa',
                lat: $hargeisaLat,
                lng: $hargeisaLng,
                regionId: $hargeisa->id,
                districtId: $hargeisaDistrict,
                subDistrictId: $hargeisaSub,
                operator: $operator,
                type: 'guyed',
                height: 48 + ($i * 4),
                capacity: 'tv',
                status: 'active',
                health: $this->randomHealth('active'),
                commissioned: now()->subYears(8 - $i)->toDateString(),
            );

            $this->createTower(
                city: 'Borama',
                lat: $boramaLat,
                lng: $boramaLng,
                regionId: $awdal->id,
                districtId: $boramaDistrict,
                subDistrictId: $boramaSub,
                operator: $operator,
                type: 'rooftop',
                height: 28 + ($i * 3),
                capacity: 'tv',
                status: 'under_construction',
                health: 'unknown',
                commissioned: null,
            );
        }

        $this->seedHargeisaProximityCluster($hargeisa, $telecom);
    }

    /**
     * A small cluster so seeded towers demonstrate automatic other-towers-nearby links.
     */
    private function seedHargeisaProximityCluster(Region $region, $telecom): void
    {
        $cluster = [
            ['lat' => 9.5620, 'lng' => 44.0770, 'operator' => $telecom[0], 'type' => 'monopole', 'height' => 42, 'capacity' => '4g'],
            ['lat' => 9.5625, 'lng' => 44.0778, 'operator' => $telecom[1 % $telecom->count()], 'type' => 'guyed', 'height' => 38, 'capacity' => '5g'],
            ['lat' => 9.5614, 'lng' => 44.0762, 'operator' => $telecom[2 % $telecom->count()], 'type' => 'rooftop', 'height' => 24, 'capacity' => '3g'],
        ];

        foreach ($cluster as $site) {
            [$districtId, $subDistrictId] = $this->geography->resolve($region->id, 'Hargeisa', $site['lat'], $site['lng']);

            $this->createTower(
                city: 'Hargeisa',
                lat: $site['lat'],
                lng: $site['lng'],
                regionId: $region->id,
                districtId: $districtId,
                subDistrictId: $subDistrictId,
                operator: $site['operator'],
                type: $site['type'],
                height: $site['height'],
                capacity: $site['capacity'],
                status: 'active',
                health: 'good',
                commissioned: now()->subYears(fake()->numberBetween(2, 6))->toDateString(),
            );
        }
    }

    private function createTower(
        string $city,
        float $lat,
        float $lng,
        int $regionId,
        ?int $districtId,
        ?int $subDistrictId,
        Operator $operator,
        string $type,
        float $height,
        string $capacity,
        string $status,
        string $health,
        ?string $commissioned,
        ?array $powerSources = null,
    ): void {
        $proximity = $this->proximityForCity($city);
        $applicationDate = $this->applicationDate($status, $commissioned);
        $notes = $this->registrationNotes($status);

        Tower::query()->create([
            'name' => TowerNameGenerator::generate(
                operatorId: $operator->id,
                regionId: $regionId,
                city: $city,
                districtId: $districtId,
                subDistrictId: $subDistrictId,
            ),
            'latitude' => $lat,
            'longitude' => $lng,
            'region_id' => $regionId,
            'district_id' => $districtId,
            'sub_district_id' => $subDistrictId,
            'operator_id' => $operator->id,
            'type' => $type,
            'height_m' => $height,
            'capacity' => $capacity,
            'power_sources' => $powerSources ?? $this->randomPowerSources(),
            'city' => $city,
            'land_area' => $this->randomLandArea(),
            'nearest_school_name' => $proximity['school_name'],
            'nearest_school_m' => $proximity['school_m'],
            'nearest_hospital_name' => $proximity['hospital_name'],
            'nearest_hospital_m' => $proximity['hospital_m'],
            'nearest_house_name' => $proximity['house_name'],
            'nearest_house_m' => $proximity['house_m'],
            'fence_distance_m' => fake()->randomElement(TowerFenceDistance::PRESETS),
            'site_map_notes' => $this->siteMapNotes($city, $type),
            'application_date' => $applicationDate,
            'registration_inspector_notes' => $notes['inspector'],
            'registration_director_notes' => $notes['director'],
            'signal_radius_m' => TowerSignalRadius::defaultForCapacity($capacity),
            'status' => $status,
            'health_status' => $health,
            'commissioned_at' => $commissioned,
        ]);
    }

    /**
     * @return array{school_name: string, school_m: int, hospital_name: string, hospital_m: int, house_name: string, house_m: int}
     */
    private function proximityForCity(string $city): array
    {
        return [
            'school_name' => fake()->randomElement([
                "{$city} Primary School",
                "Al-Noor School ({$city})",
                "{$city} Secondary School",
            ]),
            'school_m' => fake()->numberBetween(120, 950),
            'hospital_name' => fake()->randomElement([
                "{$city} General Hospital",
                "{$city} Health Centre",
                "Regional Hospital ({$city})",
            ]),
            'hospital_m' => fake()->numberBetween(250, 2200),
            'house_name' => fake()->randomElement([
                'Nearest residential compound',
                'Village overlook',
                'Private dwelling',
            ]),
            'house_m' => fake()->numberBetween(18, 95),
        ];
    }

    private function randomLandArea(): string
    {
        return fake()->randomElement(array_values(TowerLandArea::PRESETS));
    }

    private function siteMapNotes(string $city, string $type): string
    {
        return fake()->randomElement([
            "{$city} compound with fenced generator shed and access road.",
            "Site layout for {$type} installation at {$city}; operator to attach survey map.",
            "Rectangular compound; main equipment shelter south-west of the mast.",
        ]);
    }

    private function applicationDate(string $status, ?string $commissioned): ?string
    {
        if ($status === 'decommissioned') {
            return now()->subYears(fake()->numberBetween(10, 15))->toDateString();
        }

        if ($commissioned) {
            return now()->parse($commissioned)->subMonths(fake()->numberBetween(2, 8))->toDateString();
        }

        return now()->subMonths(fake()->numberBetween(1, 6))->toDateString();
    }

    /**
     * @return array{inspector: ?string, director: ?string}
     */
    private function registrationNotes(string $status): array
    {
        if ($status === 'decommissioned') {
            return [
                'inspector' => 'Historical record — site decommissioned.',
                'director' => 'Archived registration.',
            ];
        }

        if ($status === 'under_construction') {
            return [
                'inspector' => 'Site visit completed; construction in progress.',
                'director' => 'Provisional approval pending commissioning.',
            ];
        }

        return [
            'inspector' => fake()->randomElement([
                'Site visit completed. Compound and fencing meet ministry requirements.',
                'GPS verified on site. Nearest amenities confirmed with operator.',
                'Registration inspection passed; documentation complete.',
            ]),
            'director' => fake()->randomElement([
                'Approved for registration.',
                'Registration endorsed by department director.',
                'Cleared for active operation.',
            ]),
        ];
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

    /**
     * @return list<string>
     */
    private function randomPowerSources(): array
    {
        $options = ['grid', 'generator', 'battery', 'solar'];
        $count = fake()->numberBetween(1, 2);
        $picked = fake()->randomElements($options, $count);

        if (fake()->boolean(35)) {
            $picked[] = 'solar';
        }

        return array_values(array_unique($picked));
    }
}
