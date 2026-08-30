<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TowerPowerSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_multiple_power_sources_on_tower(): void
    {
        [$region, $district, $operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('towers.store'), [
                'latitude' => 9.56,
                'longitude' => 44.07,
                'region_id' => $region->id,
                'district_id' => $district->id,
                'operator_id' => $operator->id,
                'city' => 'Hargeisa',
                'type' => 'monopole',
                'height_m' => 40,
                'capacity' => '4g',
                'signal_radius_m' => 5000,
                'status' => 'active',
                'power_sources' => ['solar', 'battery', 'generator'],
            ])
            ->assertRedirect();

        $tower = Tower::query()->where('city', 'Hargeisa')->where('operator_id', $operator->id)->firstOrFail();

        $this->assertSame(['solar', 'battery', 'generator'], $tower->powerSourceKeys());
    }

    public function test_admin_can_filter_towers_by_power_source(): void
    {
        [$region, $district, $operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        Tower::query()->create([
            'name' => 'Grid only',
            'latitude' => 9.56,
            'longitude' => 44.07,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'operator_id' => $operator->id,
            'type' => 'guyed',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
            'power_sources' => ['grid'],
        ]);

        Tower::query()->create([
            'name' => 'Solar site',
            'latitude' => 9.57,
            'longitude' => 44.08,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'operator_id' => $operator->id,
            'type' => 'guyed',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
            'power_sources' => ['solar', 'battery'],
        ]);

        $this->actingAs($admin)
            ->get(route('towers.index', ['power_source' => 'solar']))
            ->assertOk()
            ->assertSee('Solar site', false)
            ->assertDontSee('Grid only', false);
    }

    public function test_map_json_includes_power_sources(): void
    {
        [$region, $district, $operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        Tower::query()->create([
            'name' => 'Hybrid map site',
            'latitude' => 9.56,
            'longitude' => 44.07,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'operator_id' => $operator->id,
            'type' => 'guyed',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
            'power_sources' => ['generator', 'solar'],
        ]);

        $this->actingAs($admin)
            ->getJson(route('map.towers', ['power_source' => 'solar']))
            ->assertOk()
            ->assertJsonCount(1, 'towers')
            ->assertJsonPath('towers.0.power_sources', ['generator', 'solar']);
    }

    /**
     * @return array{0: Region, 1: District, 2: Operator}
     */
    private function fixtures(): array
    {
        foreach ([
            ['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex'],
        ] as $regionRow) {
            Region::query()->firstOrCreate(['name_en' => $regionRow['name_en']], $regionRow);
        }

        $this->seed(GeographySeeder::class);

        $region = Region::query()->where('name_en', 'Maroodi Jeex')->firstOrFail();
        $district = District::query()->where('region_id', $region->id)->firstOrFail();
        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        return [$region, $district, $operator];
    }
}
