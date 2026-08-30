<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Operator;
use App\Models\Region;
use App\Models\SubDistrict;
use App\Models\Tower;
use App\Models\User;
use App\Support\TowerProximity;
use App\Support\TowerSignalRadius;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TowerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_ministry_registration_fields_on_tower(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('towers.store'), [
                'latitude' => 9.56,
                'longitude' => 44.07,
                'region_id' => $region->id,
                'district_id' => $district->id,
                'sub_district_id' => $subDistrict->id,
                'city' => 'Hargeisa',
                'land_area_preset' => '20x20',
                'operator_id' => $operator->id,
                'type' => 'monopole',
                'height_m' => 45,
                'fence_distance_preset' => '6',
                'capacity' => '4g',
                'nearest_school_name' => 'Al-Huda School',
                'nearest_school_m' => 420,
                'nearest_hospital_name' => 'Hargeisa General',
                'nearest_hospital_m' => 800,
                'nearest_house_name' => 'Residential overlook',
                'nearest_house_m' => 25,
                'site_map_notes' => 'Compound with generator shed',
                'status' => 'active',
                'application_date' => '2025-03-15',
                'registration_inspector_notes' => 'Site visit completed.',
                'registration_director_notes' => 'Approved for registration.',
            ])
            ->assertRedirect();

        $tower = Tower::query()->where('city', 'Hargeisa')->where('operator_id', $operator->id)->firstOrFail();

        $this->assertSame('TEL-HAR-001', $tower->name);
        $this->assertSame('Hargeisa', $tower->city);
        $this->assertSame('20 × 20 m', $tower->land_area);
        $this->assertSame('Al-Huda School', $tower->nearest_school_name);
        $this->assertSame(420, $tower->nearest_school_m);
        $this->assertSame('Hargeisa General', $tower->nearest_hospital_name);
        $this->assertSame(800, $tower->nearest_hospital_m);
        $this->assertSame('Residential overlook', $tower->nearest_house_name);
        $this->assertSame(25, $tower->nearest_house_m);
        $this->assertEquals(6.0, (float) $tower->fence_distance_m);
        $this->assertSame(TowerSignalRadius::defaultForCapacity('4g'), $tower->signal_radius_m);
        $this->assertNotNull($tower->commissioned_at);
        $this->assertSame(__('app.towers.nearby.none', ['radius' => '1,000']), $tower->other_towers_nearby);
        $this->assertSame('Compound with generator shed', $tower->site_map_notes);
        $this->assertSame('2025-03-15', $tower->application_date?->toDateString());
        $this->assertSame('Site visit completed.', $tower->registration_inspector_notes);
        $this->assertSame('Approved for registration.', $tower->registration_director_notes);
    }

    public function test_location_preview_returns_nearby_towers_for_map_picker(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        Tower::query()->create([
            'name' => 'Existing site',
            'latitude' => 9.5605,
            'longitude' => 44.0705,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
        ]);

        $this->actingAs($admin)
            ->getJson(route('towers.location-preview', [
                'latitude' => 9.56,
                'longitude' => 44.07,
            ]))
            ->assertOk()
            ->assertJsonPath('nearby_towers.0.name', 'Existing site')
            ->assertJsonStructure([
                'school',
                'hospital',
                'house',
                'nearby_towers',
                'nearby_radius_m',
            ]);
    }

    public function test_nearby_towers_are_detected_within_one_kilometer(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->fixtures();

        $first = Tower::query()->create([
            'name' => 'Primary site',
            'latitude' => 9.56,
            'longitude' => 44.07,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => '4g',
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
        ]);

        $second = Tower::query()->create([
            'name' => 'Nearby site',
            'latitude' => 9.5605,
            'longitude' => 44.0705,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'operator_id' => $operator->id,
            'type' => 'guyed',
            'height_m' => 35,
            'capacity' => '3g',
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
        ]);

        $first->refresh();
        $second->refresh();

        $this->assertCount(1, $first->nearbyTowers());
        $this->assertSame($second->id, $first->nearbyTowers()->first()['tower']->id);
        $this->assertLessThan(TowerProximity::NEARBY_RADIUS_METERS, $first->nearbyTowers()->first()['distance_m']);
        $this->assertStringContainsString('Nearby site', (string) $first->other_towers_nearby);
        $this->assertStringContainsString('Primary site', (string) $second->other_towers_nearby);
    }

    public function test_tower_show_displays_registration_fields(): void
    {
        [$region, $district, $subDistrict, $operator] = $this->fixtures();
        $admin = User::factory()->create(['role' => 'admin']);

        $tower = Tower::query()->create([
            'name' => 'Registration display site',
            'latitude' => 9.56,
            'longitude' => 44.07,
            'region_id' => $region->id,
            'district_id' => $district->id,
            'sub_district_id' => $subDistrict->id,
            'city' => 'Burco',
            'land_area' => '15 × 15 m',
            'operator_id' => $operator->id,
            'type' => 'guyed',
            'height_m' => 40,
            'nearest_school_m' => 200,
            'nearest_school_name' => 'Burco Primary',
            'nearest_hospital_m' => 500,
            'nearest_house_m' => 18,
            'fence_distance_m' => 6,
            'capacity' => '5g',
            'site_map_notes' => 'Flat compound',
            'signal_radius_m' => 5000,
            'status' => 'active',
            'health_status' => 'good',
            'application_date' => '2024-11-01',
            'registration_inspector_notes' => 'OK',
            'registration_director_notes' => 'Signed',
        ]);

        $this->actingAs($admin)
            ->get(route('towers.show', $tower))
            ->assertOk()
            ->assertSee('Burco', false)
            ->assertSee('15 × 15 m', false)
            ->assertSee('Burco Primary', false)
            ->assertSee('200 m', false)
            ->assertSee('Flat compound', false)
            ->assertSee('OK', false)
            ->assertSee('Signed', false);
    }

    /**
     * @return array{0: Region, 1: District, 2: SubDistrict, 3: Operator}
     */
    private function fixtures(): array
    {
        Region::query()->firstOrCreate(
            ['name_en' => 'Maroodi Jeex'],
            ['name_en' => 'Maroodi Jeex', 'name_so' => 'Maroodi Jeex'],
        );

        $this->seed(GeographySeeder::class);

        $region = Region::query()->where('name_en', 'Maroodi Jeex')->firstOrFail();
        $district = District::query()->where('region_id', $region->id)->firstOrFail();
        $subDistrict = SubDistrict::query()->where('district_id', $district->id)->firstOrFail();
        $operator = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);

        return [$region, $district, $subDistrict, $operator];
    }
}
