<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Region;
use App\Models\Tower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_map(): void
    {
        $this->get(route('map'))->assertRedirect(route('login'));
    }

    public function test_admin_map_json_includes_all_towers(): void
    {
        [$awdal, $sahil, $telesom, $somtel] = $this->seedMapFixtures();

        $this->makeTower('Awdal site', $awdal, $telesom, 9.93, 43.18);
        $this->makeTower('Sahil site', $sahil, $somtel, 10.43, 45.01);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('map.towers'))
            ->assertOk()
            ->assertJsonCount(2, 'towers');
    }

    public function test_inspector_map_json_is_limited_to_their_region(): void
    {
        [$awdal, $sahil, $telesom] = $this->seedMapFixtures();

        $this->makeTower('Awdal site', $awdal, $telesom, 9.93, 43.18);
        $this->makeTower('Sahil site', $sahil, $telesom, 10.43, 45.01);

        $inspector = User::factory()->create([
            'role' => 'inspector',
            'region_id' => $awdal->id,
        ]);

        $this->actingAs($inspector)
            ->getJson(route('map.towers'))
            ->assertOk()
            ->assertJsonCount(1, 'towers')
            ->assertJsonPath('towers.0.name', 'Awdal site');
    }

    public function test_operator_viewer_map_json_is_limited_to_their_operator(): void
    {
        [$awdal, , $telesom, $somtel] = $this->seedMapFixtures();

        $this->makeTower('Telesom site', $awdal, $telesom, 9.93, 43.18);
        $this->makeTower('Somtel site', $awdal, $somtel, 9.56, 44.07);

        $viewer = User::factory()->create([
            'role' => 'operator_viewer',
            'operator_id' => $telesom->id,
        ]);

        $this->actingAs($viewer)
            ->getJson(route('map.towers'))
            ->assertOk()
            ->assertJsonCount(1, 'towers')
            ->assertJsonPath('towers.0.name', 'Telesom site');
    }

    public function test_map_filters_by_status(): void
    {
        [$awdal, , $telesom] = $this->seedMapFixtures();

        $this->makeTower('Active site', $awdal, $telesom, 9.93, 43.18, 'active');
        $this->makeTower('Build site', $awdal, $telesom, 9.56, 44.07, 'under_construction');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('map.towers', ['status' => 'under_construction']))
            ->assertOk()
            ->assertJsonCount(1, 'towers')
            ->assertJsonPath('towers.0.name', 'Build site');
    }

    public function test_authenticated_users_can_open_the_map_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('map'))
            ->assertOk()
            ->assertSee(__('app.map.title'), false);
    }

    /**
     * @return array{0: Region, 1: Region, 2: Operator, 3: Operator}
     */
    private function seedMapFixtures(): array
    {
        $awdal = Region::query()->create(['name_en' => 'Awdal', 'name_so' => 'Awdal']);
        $sahil = Region::query()->create(['name_en' => 'Sahil', 'name_so' => 'Saaxil']);
        $telesom = Operator::query()->create([
            'name' => 'Telesom',
            'category' => 'telecom',
            'color' => '#0F766E',
        ]);
        $somtel = Operator::query()->create([
            'name' => 'Somtel',
            'category' => 'telecom',
            'color' => '#1D4ED8',
        ]);

        return [$awdal, $sahil, $telesom, $somtel];
    }

    private function makeTower(string $name, Region $region, Operator $operator, float $lat, float $lng, string $status = 'active'): Tower
    {
        return Tower::query()->create([
            'name' => $name,
            'latitude' => $lat,
            'longitude' => $lng,
            'region_id' => $region->id,
            'operator_id' => $operator->id,
            'type' => 'monopole',
            'height_m' => 40,
            'capacity' => 'Demo',
            'signal_radius_m' => 10000,
            'status' => $status,
            'health_status' => 'unknown',
        ]);
    }
}
