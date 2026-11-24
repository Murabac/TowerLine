<?php

namespace Tests\Unit;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Support\TowerNameGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TowerNameGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_internal_code_from_operator_and_city(): void
    {
        [$region, $district, $operator] = $this->fixtures();

        $name = TowerNameGenerator::generate(
            operatorId: $operator->id,
            regionId: $region->id,
            city: 'Hargeisa',
            districtId: $district->id,
        );

        $this->assertSame('TEL-HAR-001', $name);
    }

    public function test_increments_sequence_for_duplicate_operator_and_location(): void
    {
        [$region, $district, $operator] = $this->fixtures();

        Tower::query()->create([
            'name' => 'TEL-HAR-001',
            'latitude' => 9.56,
            'longitude' => 44.07,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
            'city' => 'Hargeisa',
        ]);

        $name = TowerNameGenerator::generate(
            operatorId: $operator->id,
            regionId: $region->id,
            city: 'Hargeisa',
            districtId: $district->id,
        );

        $this->assertSame('TEL-HAR-002', $name);
    }

    public function test_keeps_existing_code_when_editing_same_operator_and_location(): void
    {
        [$region, $district, $operator] = $this->fixtures();

        $tower = Tower::query()->create([
            'name' => 'TEL-HAR-001',
            'latitude' => 9.56,
            'longitude' => 44.07,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
            'city' => 'Hargeisa',
        ]);

        $name = TowerNameGenerator::generate(
            operatorId: $operator->id,
            regionId: $region->id,
            city: 'Hargeisa',
            districtId: $district->id,
            exceptTowerId: $tower->id,
        );

        $this->assertSame('TEL-HAR-001', $name);
    }

    /**
     * @return array{0: Region, 1: District, 2: Operator}
     */
    public function test_somtel_and_somcable_share_one_som_sequence(): void
    {
        [$region, $district] = $this->fixtures();
        $somtel = Operator::query()->create(['name' => 'Somtel', 'category' => 'telecom', 'color' => '#1D4ED8']);
        $somcable = Operator::query()->create(['name' => 'Somcable', 'category' => 'telecom', 'color' => '#7C3AED']);

        $names = [];

        foreach ([$somcable, $somtel, $somcable] as $operator) {
            $name = TowerNameGenerator::generate(
                operatorId: $operator->id,
                regionId: $region->id,
                city: 'Hargeisa',
                districtId: $district->id,
            );
            $names[] = $name;

            Tower::query()->create([
                'name' => $name,
                'latitude' => 9.56,
                'longitude' => 44.07,
                'region_id' => $region->id,
                'district_id' => $district->id,
                'operator_id' => $operator->id,
                'type' => 'monopole',
                'height_m' => 40,
                'capacity' => '4g',
                'signal_radius_m' => 5000,
                'status' => 'active',
                'health_status' => 'good',
                'city' => 'Hargeisa',
            ]);
        }

        $this->assertSame(['SOM-HAR-001', 'SOM-HAR-002', 'SOM-HAR-003'], $names);
    }

    private function fixtures(): array
    {
        $region = Region::query()->create([
            'name_en' => 'Maroodi Jeex',
            'name_so' => 'Maroodi Jeex',
        ]);

        $district = District::query()->create([
            'region_id' => $region->id,
            'name' => 'Hargeisa',
        ]);

        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        return [$region, $district, $operator];
    }
}
