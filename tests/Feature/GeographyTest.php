<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\SubDistrict;
use App\Models\Tower;
use App\Models\User;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeographyTest extends TestCase
{
    use RefreshDatabase;

    public function test_geography_seeder_loads_districts_and_sub_districts(): void
    {
        $this->seedAllRegions();
        $this->seed(GeographySeeder::class);

        $this->assertGreaterThan(90, District::query()->count());
        $this->assertGreaterThan(100, SubDistrict::query()->count());

        $hargeisa = District::query()->where('name', 'Hargeisa')->firstOrFail();
        $this->assertSame(9, $hargeisa->subDistricts()->count());

        $burao = District::query()->where('name', 'Burao')->firstOrFail();
        $this->assertSame(4, $burao->subDistricts()->count());

        $borama = District::query()->where('name', 'Borama')->firstOrFail();
        $this->assertSame(1, $borama->subDistricts()->count());
        $this->assertSame('Borama — Central', $borama->subDistricts()->value('name'));
    }

    public function test_admin_can_filter_towers_by_district(): void
    {
        [$region, $districtA, $districtB, $operator] = $this->fixtures();

        $towerA = $this->tower('Alpha site', $region, $districtA, $operator);
        $this->tower('Beta site', $region, $districtB, $operator);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('towers.index', ['district_id' => $districtA->id]))
            ->assertOk()
            ->assertSee('Alpha site', false)
            ->assertDontSee('Beta site', false);
    }

    public function test_map_json_filters_by_sub_district(): void
    {
        [$region, $district, , $operator] = $this->fixtures();
        $subA = SubDistrict::query()->where('district_id', $district->id)->firstOrFail();
        $subB = SubDistrict::query()->create([
            'district_id' => $district->id,
            'name' => 'East quarter',
        ]);

        $this->tower('East site', $region, $district, $operator, $subB->id, 10.50, 45.20);
        $this->tower('Central site', $region, $district, $operator, $subA->id, 9.40, 44.10);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('map.towers', ['sub_district_id' => $subB->id]))
            ->assertOk()
            ->assertJsonCount(1, 'towers')
            ->assertJsonPath('towers.0.name', 'East site')
            ->assertJsonPath('bounds.south', 10.5)
            ->assertJsonPath('bounds.east', 45.2);
    }

    public function test_map_json_returns_geography_bounds_when_filters_exclude_all_towers(): void
    {
        [$region, $districtA, , $operator] = $this->fixtures();
        $this->tower('Hargeisa site', $region, $districtA, $operator, null, 9.56, 44.07);

        $emptyDistrict = District::query()
            ->where('region_id', $region->id)
            ->where('name', 'Gabiley')
            ->firstOrFail();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('map.towers', [
                'district_id' => $emptyDistrict->id,
                'status' => 'decommissioned',
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'towers')
            ->assertJsonStructure(['bounds' => ['south', 'west', 'north', 'east']]);
    }

    public function test_map_json_returns_sub_district_centroid_bounds_when_ward_has_no_towers(): void
    {
        $this->seedAllRegions();
        $this->seed(GeographySeeder::class);

        $gacanLibaax = SubDistrict::query()
            ->whereHas('district', fn ($query) => $query->where('name', 'Hargeisa'))
            ->where('name', 'Gacan Libaax')
            ->firstOrFail();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('map.towers', [
                'sub_district_id' => $gacanLibaax->id,
                'status' => 'decommissioned',
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'towers')
            ->assertJsonPath('bounds.south', 9.52)
            ->assertJsonPath('bounds.east', 44.04);
    }

    public function test_backfill_assigns_hargeisa_towers_to_nearest_ward(): void
    {
        $this->seedAllRegions();
        $this->seed(GeographySeeder::class);

        $region = Region::query()->where('name_en', 'Maroodi Jeex')->firstOrFail();
        $district = District::query()->where('name', 'Hargeisa')->firstOrFail();
        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        $somtel = Tower::query()->create([
            'name' => 'Somtel Hargeisa',
            'latitude' => 9.532,
            'longitude' => 44.027,
            'region_id' => $region->id,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
        ]);

        $this->seed(\Database\Seeders\TowerGeographyBackfillSeeder::class);

        $somtel->refresh();

        $this->assertSame('Gacan Libaax', $somtel->subDistrict?->name);
        $this->assertSame($district->id, $somtel->district_id);
    }

    public function test_inspector_cannot_assign_district_outside_their_region(): void
    {
        [$maroodi, $maroodiDistrict] = $this->regionWithDistrict('Maroodi Jeex', 'Hargeisa');
        [$sahil, $sahilDistrict] = $this->regionWithDistrict('Sahil', 'Berbera');
        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $maroodi->id,
        ]);
        $inspector->syncInspectorRegions([$maroodi->id]);

        $this->actingAs($inspector)
            ->post(route('towers.store'), [
                'name' => 'Invalid tower',
                'latitude' => 10.43,
                'longitude' => 45.01,
                'region_id' => $sahil->id,
                'district_id' => $sahilDistrict->id,
                'operator_id' => $operator->id,
                'type' => 'guyed',
                'height_m' => 40,
                'signal_radius_m' => 5000,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('region_id');

        $this->actingAs($inspector)
            ->post(route('towers.store'), [
                'name' => 'Mismatch tower',
                'latitude' => 9.56,
                'longitude' => 44.07,
                'region_id' => $maroodi->id,
                'district_id' => $sahilDistrict->id,
                'operator_id' => $operator->id,
                'type' => 'guyed',
                'height_m' => 40,
                'signal_radius_m' => 5000,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('district_id');
    }

    public function test_sub_district_must_belong_to_selected_district(): void
    {
        [$region, $districtA, $districtB, $operator] = $this->fixtures();
        $subB = SubDistrict::query()->where('district_id', $districtB->id)->firstOrFail();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('towers.store'), [
                'name' => 'Bad geography',
                'latitude' => 9.56,
                'longitude' => 44.07,
                'region_id' => $region->id,
                'district_id' => $districtA->id,
                'sub_district_id' => $subB->id,
                'operator_id' => $operator->id,
                'type' => 'guyed',
                'height_m' => 40,
                'signal_radius_m' => 5000,
                'status' => 'active',
            ])
            ->assertSessionHasErrors('sub_district_id');
    }

    public function test_inspector_cannot_fetch_districts_outside_assigned_region(): void
    {
        [$maroodi] = $this->regionWithDistrict('Maroodi Jeex', 'Hargeisa');
        [$sahil] = $this->regionWithDistrict('Sahil', 'Berbera');

        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $maroodi->id,
        ]);
        $inspector->syncInspectorRegions([$maroodi->id]);

        $this->actingAs($inspector)
            ->getJson(route('geography.districts', ['region_id' => $sahil->id]))
            ->assertForbidden();
    }

    public function test_admin_can_delete_sub_district_and_unassign_towers(): void
    {
        [$region, $district, , $operator] = $this->fixtures();
        $subDistrict = SubDistrict::query()->where('district_id', $district->id)->firstOrFail();
        $tower = $this->tower('Linked site', $region, $district, $operator, $subDistrict->id);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('districts.sub-districts.destroy', [$district, $subDistrict]))
            ->assertRedirect(route('districts.edit', $district))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('sub_districts', ['id' => $subDistrict->id]);
        $tower->refresh();
        $this->assertNull($tower->sub_district_id);
        $this->assertSame($district->id, $tower->district_id);
    }

    public function test_admin_can_delete_district_and_its_sub_districts(): void
    {
        [$region, $district, , $operator] = $this->fixtures();
        $subDistrict = SubDistrict::query()->where('district_id', $district->id)->firstOrFail();
        $tower = $this->tower('District site', $region, $district, $operator, $subDistrict->id);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->delete(route('districts.destroy', $district))
            ->assertRedirect(route('districts.index', ['region_id' => $region->id]))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('districts', ['id' => $district->id]);
        $this->assertDatabaseMissing('sub_districts', ['id' => $subDistrict->id]);
        $tower->refresh();
        $this->assertNull($tower->district_id);
        $this->assertNull($tower->sub_district_id);
    }

    public function test_inspector_cannot_delete_district(): void
    {
        [$region, $district] = $this->regionWithDistrict('Maroodi Jeex', 'Hargeisa');

        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $region->id,
        ]);
        $inspector->syncInspectorRegions([$region->id]);

        $this->actingAs($inspector)
            ->delete(route('districts.destroy', $district))
            ->assertForbidden();

        $this->assertDatabaseHas('districts', ['id' => $district->id]);
    }

    /**
     * @return array{0: Region, 1: District, 2: District, 3: Operator}
     */
    private function fixtures(): array
    {
        $this->seedRegions();
        $this->seed(GeographySeeder::class);

        $region = Region::query()->where('name_en', 'Maroodi Jeex')->firstOrFail();
        $districts = District::query()->where('region_id', $region->id)->orderBy('id')->take(2)->get();
        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        return [$region, $districts[0], $districts[1], $operator];
    }

    /**
     * @return array{0: Region, 1: District}
     */
    private function regionWithDistrict(string $regionName, string $districtName): array
    {
        $this->seedRegions();
        $this->seed(GeographySeeder::class);

        $region = Region::query()->where('name_en', $regionName)->firstOrFail();
        $district = District::query()
            ->where('region_id', $region->id)
            ->where('name', $districtName)
            ->firstOrFail();

        return [$region, $district];
    }

    private function seedRegions(): void
    {
        foreach ([
            ['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex'],
            ['name_en' => 'Sahil', 'name_so' => 'Saaxil'],
        ] as $region) {
            Region::query()->firstOrCreate(['name_en' => $region['name_en']], $region);
        }
    }

    private function seedAllRegions(): void
    {
        foreach ([
            ['name_en' => 'Awdal', 'name_so' => 'Awdal'],
            ['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex'],
            ['name_en' => 'Sahil', 'name_so' => 'Saaxil'],
            ['name_en' => 'Togdheer', 'name_so' => 'Togdheer'],
            ['name_en' => 'Sanaag', 'name_so' => 'Sanaag'],
            ['name_en' => 'Sool', 'name_so' => 'Sool'],
        ] as $region) {
            Region::query()->firstOrCreate(['name_en' => $region['name_en']], $region);
        }
    }

    private function tower(
        string $name,
        Region $region,
        District $district,
        Operator $operator,
        ?int $subDistrictId = null,
        float $latitude = 9.56,
        float $longitude = 44.07,
    ): Tower {
        return Tower::query()->create([
            'name' => $name,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrictId ?? SubDistrict::query()->where('district_id', $district->id)->value('id'),
            'operator_id' => $operator->id,
            'type' => 'guyed',
            'height_m' => 40,
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
        ]);
    }
}
